<?php
 
namespace app\store\controller;

 
use app\common\controller\AdminInfo0Controller; 
use think\Db; 
  
class AdminStoreController extends AdminInfo0Controller
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='仓库';
        $this->table='store';
        $this->m=Db::name('store');
        $this->edit=['name','sort','dsc','province','city','area',
            'street','tel_name','tel_phone','tel_mobile','size0','size',
            'length','width','height','price1','price2','fee1','fee2','fee0',
            'city_code','code_num', 
        ];
        //没有店铺区分
        $this->isshop=1;
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        
    }
    /**
     * 仓库列表
     * @adminMenu(
     *     'name'   => '仓库列表',
     *     'parent' => 'store/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '仓库列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
         parent::index();
        return $this->fetch();
    }
     
   
    /**
     * 仓库添加
     * @adminMenu(
     *     'name'   => '仓库添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '仓库添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
        return $this->fetch();  
        
    }
    /**
     * 仓库添加do
     * @adminMenu(
     *     'name'   => '仓库添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '仓库添加do',
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
        $admin=$this->admin;
        //判断是否有店铺
        if($this->isshop){
            $data_add['shop']=($admin['shop']==1)?2:$admin['shop'];
        } elseif($admin['shop']!=1){
            $this->error('店铺不能添加系统数据');
        }
        $url=url('index');
        
        $table=$this->table;
        $time=time();
       
        $data_add=$data;
        $data_add['city_code']=intval($data['city_code']);
        $data_add['code_num']=intval($data['code_num']);
        $data_add['code']=str_pad($data['city_code'], 4,'0',STR_PAD_LEFT).'-'.str_pad($data['code_num'], 2,'0',STR_PAD_LEFT);
        //检查是否有重复
        $tmp=$m->where(['code'=>$data_add['code']])->find();
        if(!empty($tmp)){
            $this->error('编号已存在');
        }
        $data_add['sort']=intval($data['sort']);
        $data_add['status']=1;
        
        $data_add['aid']=$admin['id'];
        $data_add['atime']=$time;
        $data_add['time']=$time;
       
        $m->startTrans();
        $id=$m->insertGetId($data_add);
        
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
        $this->success('添加成功',$url);
        
    }
    /**
     * 仓库详情
     * @adminMenu(
     *     'name'   => '仓库详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '仓库详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();  
        return $this->fetch();  
    }
    /**
     * 仓库状态审核
     * @adminMenu(
     *     'name'   => '仓库状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '仓库状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 仓库状态批量同意
     * @adminMenu(
     *     'name'   => '仓库状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '仓库状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    /**
     * 仓库禁用
     * @adminMenu(
     *     'name'   => '信息状态禁用',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '信息状态禁用',
     *     'param'  => ''
     * )
     */
    public function ban()
    {
        parent::ban();
    }
    /**
     * 仓库信息状态恢复
     * @adminMenu(
     *     'name'   => '仓库信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '仓库信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 仓库编辑提交
     * @adminMenu(
     *     'name'   => '仓库编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '仓库编辑提交',
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
        if($admin['shop']!=1){
            if(empty($info['shop']) || $info['shop']!=$admin['shop']){
                $this->error('不能编辑其他店铺的信息');
            }
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
        
        $fields=$this->edit;
        
        $content=[];
        //检测改变了哪些字段
        foreach($fields as $k=>$v){
            //如果原信息和$data信息相同就未改变，不为空就记录，？null测试
            if(isset($data[$v]) && $info[$v]!=$data[$v]){
                $content[$v]=$data[$v];
            } 
        }
        if(isset($content['city_code']) || isset($content['city_code'])){
            $content['city_code']=(isset($content['city_code']))?(intval($content['city_code'])):$info['city_code'];
            $content['code_num']=(isset($content['code_num']))?(intval($content['code_num'])):$info['code_num'];
            $content['code']=str_pad($content['city_code'], 4,'0',STR_PAD_LEFT).'-'.str_pad($content['code_num'], 2,'0',STR_PAD_LEFT);
            $where=[
                'code'=>$content['code'], 
            ];
            $tmp=$m->where($where)->find();
            if(!empty($tmp)){
                $m->rollback();
                $this->error('编号重复');
            }
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
        $this->success('已提交修改');
        
    }
    /**
     * 仓库编辑列表
     * @adminMenu(
     *     'name'   => '仓库编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '仓库编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();  
    }
    
    /**
     * 仓库审核详情
     * @adminMenu(
     *     'name'   => '仓库审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '仓库审核详情',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        $m=$this->m;
        $id=$this->request->param('id',0,'intval');
        $table=$this->table;
        //获取编辑信息
        $m_edit=Db::name('edit');
        $info1=$m_edit->where('id',$id)->find();
        if(empty($info1)){
            $this->error('编辑信息不存在');
        }
        //获取原信息
        $info=$m->where('id',$info1['pid'])->find();
        if(empty($info)){
            $this->error('编辑关联的信息不存在');
        }
        //获取改变的信息
        $change=Db::name('edit_info')->where('eid',$id)->value('content');
        $change=json_decode($change,true);
        
        $city_ids=[];
        if(isset($change['province'])){
            $city_ids[]=$change['province'];
        }
        if(isset($change['city'])){
            $city_ids[]=$change['city'];
        }
        if(isset($change['area'])){
            $city_ids[]=$change['area'];
        }
        $change['citys']=null;
        if(!empty($city_ids)){
            $change['citys']=Db::name('area')->where('id','in',$city_ids)->column('id,name');
            $change['citys'][0]='未选择';
        }
        
        $this->assign('info',$info);
        $this->assign('info1',$info1);
        $this->assign('change',$change);
        //分类关联信息
        $this->cates();
         
        return $this->fetch();  
    }
    /**
     * 仓库信息编辑审核
     * @adminMenu(
     *     'name'   => '仓库编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '仓库编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        //审核编辑的信息
        $status=$this->request->param('rstatus',0,'intval');
        $id=$this->request->param('id',0,'intval');
        if(($status!=2 && $status!=3) || $id<=0){
            $this->error('信息错误');
        }
        $m=$this->m;
        $table=$this->table;
        $m_edit=Db::name('edit');
        $info=$m_edit
        ->field('e.*,p.name as pname,a.user_nickname as aname')
        ->alias('e')
        ->join('cmf_'.$table.' p','p.id=e.pid')
        ->join('cmf_user a','a.id=e.aid')
        ->where('e.id',$id)
        ->find();
        if(empty($info)){
            $this->error('无效信息');
        }
        if($info['rstatus']!=1){
            $this->error('编辑信息已被审核！不能重复审核');
        }
        
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1 && $info['shop']!=$admin['shop']){
            $this->error('不能审核其他店铺的信息');
        }
        
        $time=time();
        
        $m->startTrans();
        
        $update=[
            'rid'=>$admin['id'],
            'rtime'=>$time,
            'rstatus'=>$status,
        ];
        //只有未审核的才能更新
        $where=[
            'id'=>$id,
            'rstatus'=>1,
        ];
        $row=$m_edit->where($where)->update($update);
        if($row!==1){
            $m->rollback();
            $this->error('审核失败，请刷新后重试');
        }
        //是否更新,2同意，3不同意
        if($status==2){
            //组装更新数据
            $update_info=[
                'time'=>$time,
            ];
            //得到修改的字段
            $change=Db::name('edit_info')->where('eid',$id)->value('content');
            $change=json_decode($change,true);
            
            foreach($change as $k=>$v){
                $update_info[$k]=$v;
            }
            if(isset($update_info['code']) ){
                $where=[
                    'code'=>['eq',$update_info['code']],
                    'id'=>['neq',$info['pid']]
                ];
                $tmp=$m->where($where)->find();
                if(!empty($tmp)){
                    $m->rollback();
                    $this->error('编号重复');
                }
            } 
            $row=$m->where('id',$info['pid'])->update($update_info);
            if($row!==1){
                $m->rollback();
                $this->error('信息更新失败，请刷新后重试');
            }
        }
        
        //审核成功，记录操作记录,发送审核信息
        $review_status=$this->review_status;
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'审核'.$info['aid'].'-'.$info['aname'].'对'.($this->flag).$info['pid'].'-'.$info['pname'].'的编辑为'.$review_status[$status],
            'table'=>$table,
            'type'=>'edit_review',
            'pid'=>$info['pid'],
            'link'=>url('edit_info',['id'=>$info['id']]),
            'shop'=>$admin['shop'],
        ];
        
        zz_action($data_action,['aid'=>$info['aid']]);
        
        $m->commit();
        $this->success('审核成功');
    }
    /**
     * 仓库编辑记录批量删除
     * @adminMenu(
     *     'name'   => '仓库编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '仓库编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    
    /**
     * 仓库批量删除
     * @adminMenu(
     *     'name'   => '仓库批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '仓库批量删除',
     *     'param'  => ''
     * )
     */
    public function del_all()
    {
         
        parent::del_all();
    }
    
    
    /**
     * 分类等关联信息
     *   */
    public function cates($type=3){
        parent::cates($type);
        if($type==3){
            $this->assign('types',[1=>'存储仓库',2=>'不良品仓库',3=>'虚拟调货仓库']); 
        }
       
    }
    
     
}
