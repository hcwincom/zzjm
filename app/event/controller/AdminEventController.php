<?php

namespace app\event\controller;


use app\common\controller\AdminInfo0Controller;
use think\Db;
use app\event\model\EventModel;
use app\msg\model\MsgModel;

class AdminEventController extends AdminInfo0Controller
{
    
    public function _initialize()
    {
        parent::_initialize();
        $this->statuss=[
            1=>'待审核',
            2=>'已审核通过',
            3=>'审核不通过',
            4=>'已接收',
            5=>'已完成',
        ];
        $this->assign('statuss',$this->statuss);
        $this->flag='事件';
        $this->table='event';
        $this->m=new EventModel();
        //没有店铺区分
        $this->isshop=1;
        $this->edit=['name','progress','reward','dsc'];
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        
    }
    /**
     * 事件列表
     * @adminMenu(
     *     'name'   => '事件列表',
     *     'parent' => 'event/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '事件列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $table=$this->table;
        $m=$this->m;
        $admin=$this->admin;
        
        $data=$this->request->param();
        $where=[];
        //判断是否有店铺
        $join=[
            ['cmf_user a','a.id=p.aid','left'],
            ['cmf_user r','r.id=p.rid','left'],
            ['cmf_user u','u.id=p.uid','left'],
            
        ];
        $field='p.*,a.user_nickname as aname,r.user_nickname as rname,u.user_nickname as uname';
        $join[]= ['cmf_shop shop','p.shop=shop.id','left'];
        $field.=',shop.name as sname';
        
        $res=zz_shop($admin, $data, $where,'p.shop');
        $data=$res['data'];
        $where=$res['where'];
        if(empty($data['shop']) && $admin['shop']==1){
            $this->where_shop=$admin['shop']; 
        }else{
            $this->where_shop=$res['where_shop'];
        }
       
        
        
        //状态
        if(empty($data['status'])){
            $data['status']=0;
        }else{
            $where['p.status']=['eq',$data['status']];
        }
        
        
        //添加人
        if(empty($data['aid'])){
            $data['aid']=0;
        }else{
            $where['p.aid']=['eq',$data['aid']];
        }
        //审核人
        if(empty($data['rid'])){
            $data['rid']=0;
        }else{
            $where['p.rid']=['eq',$data['rid']];
        }
        //审核人
        if(empty($data['uid'])){
            $data['uid']=0;
        }else{
            $where['p.uid']=['eq',$data['uid']];
        }
        
        //类型
        if(empty($data['table'])){
            $data['type']='no';
        }else{
            $where['p.table']=['eq',$data['table']];
        }
        
        //搜索类型
        $search_types=config('search_types');
        
        //查询字段
        $types=[
            1=>['p.name','事件名称'],
            2=>['p.id','事件id'],
            
        ];
        $res=zz_search_param($types, $search_types,$data, $where);
        $data=$res['data'];
        $where=$res['where'];
        
        //时间类别
        $times=[
            1=>['p.atime','提交时间'],
            2=>['p.rtime','审核时间'],
            3=>['p.utime','事件接受时间'],
            1=>['p.time','更新时间'],
        ];
        $res=zz_search_time($times, $data, $where);
        $data=$res['data'];
        $where=$res['where'];
        
        $list=$m
        ->alias('p')
        ->field($field)
        ->join($join)
        ->where($where)
        ->order('p.status asc,p.sort asc,p.time desc')
        ->paginate();
        
        // 获取分页显示
        $page = $list->appends($data)->render();
        
        $this->assign('page',$page);
        $this->assign('list',$list);
        
        $this->assign('data',$data);
        $this->assign('types',$types);
        $this->assign('times',$times);
        $this->assign("search_types", $search_types);
        
        $this->cates(1);
        return $this->fetch();
    }
    
    /**
     * 事件添加
     * @adminMenu(
     *     'name'   => '事件添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '事件添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        $admin=$this->admin;
        
        
       $this->where_shop=$admin['shop'];
       
        
        $this->cates();
        $this->assign("info", null);
        
        return $this->fetch();
        
    }
    /**
     * 事件添加do
     * @adminMenu(
     *     'name'   => '事件添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '事件添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        $m=$this->m;
        $data=$this->request->param();
        if(empty($data['name'])){
            $this->error('名称不能为空');
        }
        
        $url=url('index');
        
        $table=$this->table;
        $time=time();
        $admin=$this->admin;
        $data_add=$data;
        //判断是否有店铺
        $data_add['shop']=$admin['shop'];
         
        $data_add['status']=1;
        
        $data_add['aid']=$admin['id'];
        $data_add['atime']=$time;
        $data_add['time']=$time;
        $data_add['utime']=$time;
        
        
        $m->startTrans();
        $id=$m->insertGetId($data_add);
        if(!empty($data_add['uid'])){
            $date_uid=[
                'uid'=>$data_add['uid'],
                'aid'=>$admin['id'],
                'event'=>$id,
                'astatus'=>2,
                'atime'=>$data_add['time'],
                'adsc'=>'邀请完成'
            ];
            $uidd=Db::name('event_uid')->insertGetId($date_uid);
            $m_msg=new MsgModel();
            $data_msg=[
                'dsc'=>$admin['user_nickname'].'邀请你完成事件'.$id.'-'.$data_add['name'],
                'type'=>'edit',
                'link'=>url('uidd',['id'=>$uidd]),
            ];
            $m_msg->send($data_msg,$admin,[$data_add['uid']]);
        }
        //记录操作记录
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'添加'.($this->flag).$id.'-'.$data['name'],
            'table'=>($this->table),
            'type'=>'add',
            'pid'=>$id,
            'link'=>url('edit',['id'=>$id]),
            'shop'=>$admin['shop'],
            
        ];
        zz_action($data_action,['department'=>$admin['department']]);
       
        
        $m->commit();
        //直接审核
        $rule='review';
        $res=$this->check_review($admin,$rule);
        if($res){
            $this->redirect($rule,['id'=>$id,'status'=>2]);
        }
        $this->success('添加成功',$url);
        
    }
    /**
     * 事件详情
     * @adminMenu(
     *     'name'   => '事件详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '事件详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();
        return $this->fetch();
    }
    /**
     * 事件接收记录
     * @adminMenu(
     *     'name'   => '事件接收记录',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '事件接收记录',
     *     'param'  => ''
     * )
     */
    public function uidds()
    {
       $id=$this->request->param('id',0,'intval');
       $m=$this->m;
       $info=$m->where('id',$id)->find();
       $list=Db::name('event_uid')
       ->alias('p')
       ->join('cmf_user u','u.id=p.uid')
       ->where('p.event',$id)
       ->column('p.*,u.user_nickname as uname');
       
       $this->assign('info',$info);
       $this->assign('list',$list);
      
        
       return $this->fetch();
    }
    /**
     * 事件接收详情
     * @adminMenu(
     *     'name'   => '事件接收详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '事件接收详情',
     *     'param'  => ''
     * )
     */
    public function uid()
    {
        $id=$this->request->param('id',0,'intval');
        $info1=Db::name('event_uid') 
        ->where('id',$id)
        ->find();
        $info=Db::name('event')
        ->where('id',$info1['event'])
        ->find();
        $admin=$this->admin;
        $type=0;
        if($admin['id']==$info1['aid']  ){
            if($info1['astatus']==1){
                $type=2;
            } 
        }elseif($admin['id']==$info1['uid'] ){
            
            if($info1['ustatus']==1){
                $type=1;
            } 
        }else{
           $this->error('不能查看他人信息');
        }
         
        $this->assign('info',$info);
        $this->assign('info1',$info1);
        $this->assign('type',$type);
        return $this->fetch();
    }
    /**
     * 事件接收详情
     * @adminMenu(
     *     'name'   => '事件接收详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '事件接收详情',
     *     'param'  => ''
     * )
     */
    public function uid_do()
    {
        $admin=$this->admin;
        $data=$this->request->param();
        $id=$data['id'];
        $m_event_uid=Db::name('event_uid');
        $m_event=Db::name('event');
        $info=$m_event_uid->where('id',$id)->find();
        $event=$m_event->where('id',$info['event'])->find();
       
        if(empty($event) ){
            $this->error('事件不存在');
        }
        if(empty($event) || $event['status']!=2){
            $this->error('审核通过且未被他人接收的事件才能确认');
        }
        
        //默认是接收人同意
        $type=$data['type'];
        switch($data['type'] ){
            case 1:
                if($admin['id']!=$info['uid']){
                    $this->error('只有本人才能确认');
                }
                $date_uid=[
                'ustatus'=>$data['status'],
                'udsc'=>$data['udsc'],
                ];
                break;
            case 2:
                if($admin['id']!=$info['aid']){
                    $this->error('只有本人才能确认');
                }
                $date_uid=[
                    'astatus'=>$data['status'],
                'adsc'=>$data['adsc'],
                ];
                break;
            default:
                $this->error('数据错误');
        }
        if($data['status']==2){
            $time=time();
            $data_event=[
                'ustatus'=>2,
                'status'=>4,
                'uid'=>$info['uid'],
                'time'=>$time,
            ];
            $m_event_uid->startTrans();
            $m_event->where('id',$info['event'])->update($data_event);
        }
       
        
        $m_event_uid->where('id',$info['id'])->update($date_uid);
        $m_event_uid->commit();
        $this->success('确认成功');
    }
    /**
     * 事件状态审核
     * @adminMenu(
     *     'name'   => '事件状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '事件状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 事件状态批量同意
     * @adminMenu(
     *     'name'   => '事件状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '事件状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    
    /**
     * 事件编辑提交
     * @adminMenu(
     *     'name'   => '事件编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '事件编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        $m=$this->m;
        $table=$this->table;
        $flag=$this->flag;
        $data=$this->request->param();
        $info=$m->where('id',$data['id'])->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        $time=time();
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['id']!=$info['aid'] && $admin['id']!=$info['uid']){
            $this->error('不能修改他人的数据');
        }
        
        $update=[
            'pid'=>$info['id'],
            'aid'=>$admin['id'],
            'atime'=>$time,
            'table'=>$table,
            'url'=>url('edit_info','',false,false),
            'rstatus'=>1,
            'rid'=>0,
            'rtime'=>0,
            'shop'=>$admin['shop'],
        ];
        $update['adsc']=(empty($data['adsc']))?('修改了'.$flag.'信息'):$data['adsc'];
        $fields=$this->edit;
        
        $content=[];
        //检测改变了哪些字段
        foreach($fields as $k=>$v){
            //如果原信息和$data信息相同就未改变，不为空就记录，？null测试
            if(isset($data[$v]) && $info[$v]!=$data[$v]){
                $content[$v]=$data[$v];
            }
            
        }
        if(isset($content['progress']) && $content['progress']>=100){
            $content['status']=5;
            $content['progress']=100;
        }
        if(empty($content)){
            $this->error('未修改');
        }
        //保存更改
        $m_edit=Db::name('edit');
        $m_edit->startTrans();
        $eid=$m_edit->insertGetId($update);
        if($eid>0){
            $data_content=[
                'eid'=>$eid,
                'content'=>json_encode($content),
            ];
            Db::name('edit_info')->insert($data_content);
        }else{
            $m_edit->rollback();
            $this->error('保存数据错误，请重试');
        }
        
        //记录操作记录
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'编辑了'.($this->flag).$info['id'].'-'.$info['name'],
            'table'=>($this->table),
            'type'=>'edit',
            'pid'=>$info['id'],
            'link'=>url('edit_info',['id'=>$eid]),
            'shop'=>$admin['shop'],
        ];
        
        zz_action($data_action,['department'=>$admin['department']]);
        
        $m_edit->commit();
        //判断是否直接审核
        $rule='edit_review';
        $res=$this->check_review($admin,$rule);
        if($res){
            $this->redirect($rule,['id'=>$eid,'rstatus'=>2,'rdsc'=>'直接审核']);
        }
        
        $this->success('已提交修改');
    }
    /**
     * 事件编辑列表
     * @adminMenu(
     *     'name'   => '事件编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '事件编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();
    }
    
    /**
     * 事件审核详情
     * @adminMenu(
     *     'name'   => '事件审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '事件审核详情',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        parent::edit_info();
        return $this->fetch();
    }
    /**
     * 事件信息编辑审核
     * @adminMenu(
     *     'name'   => '事件编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '事件编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
    /**
     * 事件编辑记录批量删除
     * @adminMenu(
     *     'name'   => '事件编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '事件编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    
    //
    public function cates($type=3){
        parent::cates($type);
        $this->assign('tables',config('tables'));
        $shop=$this->where_shop;
         
        //显示编辑人和审核人
        $m_user=Db::name('user');
        //可以加权限判断，目前未加
        //创建人
        $where_aid=[
            'user_type'=>1,
            'shop'=>$shop,
        ];
        
        $aids=$m_user->where($where_aid)->column('id,user_nickname');
        $this->assign('uids',$aids);
         
    }
     
    
}
