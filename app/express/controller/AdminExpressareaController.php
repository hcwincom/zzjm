<?php
 
namespace app\express\controller;

 
use app\common\controller\AdminInfo0Controller; 
use think\Db; 
  
class AdminExpressareaController extends AdminInfo0Controller
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='快递区域';
        $this->table='expressarea';
        $this->m=Db::name('expressarea');
        //没有店铺区分
        $this->isshop=1;
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        
    }
    /**
     * 自定义快递区域列表
     * @adminMenu(
     *     'name'   => '自定义快递区域列表',
     *     'parent' => 'express/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 2,
     *     'icon'   => '',
     *     'remark' => '自定义快递区域列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
         parent::index();
        return $this->fetch();
    }
     
   
    /**
     * 快递区域添加
     * @adminMenu(
     *     'name'   => '快递区域添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '快递区域添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
       parent::add();
        return $this->fetch();  
        
    }
    /**
     * 快递区域添加do
     * @adminMenu(
     *     'name'   => '快递区域添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '快递区域添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        parent::add_do();
        
    }
    /**
     * 快递区域详情
     * @adminMenu(
     *     'name'   => '快递区域详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '快递区域详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $m=$this->m;
        $id=$this->request->param('id',0,'intval');
        $info=$m
        ->alias('p')
        ->field('p.*,a.user_nickname as aname,r.user_nickname as rname')
        ->join('cmf_user a','a.id=p.aid','left')
        ->join('cmf_user r','r.id=p.rid','left')
        ->where('p.id',$id)
        ->find();
        if(empty($info)){
            $this->error('数据不存在');
        } 
        $this->assign('info',$info); 
        
        //得到省 
        $where=[
            'status'=>2,
            'fid'=>1,
        ];
        $city1s=Db::name('area')->where($where)->column('id,name');
        $this->assign('city1s',$city1s);
        
        //得到选中地区
        $citys=Db::name('express_area')
        ->alias('ea')
        ->join('cmf_area a','a.id=ea.city')
        ->where('ea.area',$id)
        ->order('a.fid asc,a.sort asc,a.name asc')
        ->column('a.id,a.name,a.fid');
        //按省分组
        $list=[];
        foreach($citys as $k=>$v){
            $list[$v['fid']][$k]=$v['name'];
        }
        $this->assign('citys',$list);
        return $this->fetch();  
    }
    /**
     * 快递区域状态审核
     * @adminMenu(
     *     'name'   => '快递区域状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '快递区域状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 快递区域状态批量同意
     * @adminMenu(
     *     'name'   => '快递区域状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '快递区域状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    /**
     * 快递区域禁用
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
     * 快递区域信息状态恢复
     * @adminMenu(
     *     'name'   => '快递区域信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '快递区域信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 快递区域编辑提交
     * @adminMenu(
     *     'name'   => '快递区域编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '快递区域编辑提交',
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
       
        //快递区域选择
      
        if(empty($data['citys'])){
            $this->error('请选择覆盖区域');
        }
        $ids=$data['citys'];
        $ids0=Db::name('express_area')->where('area',$info['id'])->column('city');
        //计算新旧参数的差级，没有差级就是完全一样
        if(!empty(array_diff($ids,$ids0)) ||  !empty(array_diff($ids0,$ids))){
            $content['citys']=json_encode($ids);
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
     * 快递区域编辑列表
     * @adminMenu(
     *     'name'   => '快递区域编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '快递区域编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();  
    }
    
    /**
     * 快递区域审核详情
     * @adminMenu(
     *     'name'   => '快递区域审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '快递区域审核详情',
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
        $info1=$m_edit
            ->alias('p')
            ->field('p.*,a.user_nickname as aname,r.user_nickname as rname')
            ->join('cmf_user a','a.id=p.aid','left')
            ->join('cmf_user r','r.id=p.rid','left')
            ->where('p.id',$id)
            ->find();
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
        //得到选中地区
        $citys=Db::name('express_area')
        ->alias('ea')
        ->join('cmf_area a','a.id=ea.city')
        ->where('ea.area',$info1['pid'])
        ->order('a.fid asc,a.sort asc,a.name asc')
        ->column('a.id,a.name,a.fid');
        //按省分组
        $list0=[];
        foreach($citys as $k=>$v){
            $list0[$v['fid']][$k]=$v['name'];
        }
        //按省分组
        $list1=[];
        //新关联产品
        if(isset($change['citys'])){
            $ids1=json_decode($change['citys'],true);
            if(!empty($ids1)){
                $citys=Db::name('area')
                ->where('id','in',$ids1)
                ->order('fid asc,sort asc,name asc')
                ->column('id,name,fid');

                foreach($citys as $k=>$v){
                    $list1[$v['fid']][$k]=$v['name'];
                }
            } 
        }
        //得到省
        $where=[
            'status'=>2,
            'fid'=>1,
        ];
        $city1s=Db::name('area')->where($where)->column('id,name');
        $this->assign('city1s',$city1s);
        $this->assign('list1',$list1);
        $this->assign('list0',$list0);
        $this->assign('info',$info);
        $this->assign('info1',$info1);
        $this->assign('change',$change);
       
        return $this->fetch();  
    }
    /**
     * 快递区域信息编辑审核
     * @adminMenu(
     *     'name'   => '快递区域编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '快递区域编辑审核',
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
        $review_status=$this->review_status;
        $update['rdsc']=$this->request->param('rdsc','');
        if(empty($update['rdsc'])){
            $update['rdsc']=$review_status[$status];
        }
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
            
           
            //处理覆盖区域
            if(isset($change['citys'])){
                $ids=json_decode($change['citys'],'true');
                unset($update_info['citys']);
            }
            if(empty($ids)){
                break;
            }
            $m_ea=Db::name('express_area');
            $m_ea->where('area',$info['pid'])->delete();
            $data_city=[];
            foreach($ids as $v){
                $data_city[]=[
                    'city'=>$v,
                    'area'=>$info['pid'],
                ];
            }
            $m_ea->insertAll($data_city);
                    
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
     * 快递区域编辑记录批量删除
     * @adminMenu(
     *     'name'   => '快递区域编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '快递区域编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    
    /**
     * 快递区域批量删除
     * @adminMenu(
     *     'name'   => '快递区域批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '快递区域批量删除',
     *     'param'  => ''
     * )
     */
    public function del_all()
    {
         
        parent::del_all();
    }
   
     
}
