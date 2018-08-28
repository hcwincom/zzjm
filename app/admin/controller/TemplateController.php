<?php
 
namespace app\admin\controller;

 
use app\common\controller\AdminInfoController; 
use think\Db; 
 
class TemplateController extends AdminInfoController
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='技术参数模板';
        $this->table='template';
        $this->m=Db::name('template');
         
        $this->assign('param_types',[1=>'单选',2=>'多选',3=>'输入']);
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
       
       
        
    }
    /**
     * 技术参数模板列表
     * @adminMenu(
     *     'name'   => '技术参数模板列表',
     *     'parent' => 'admin/Goods/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '技术参数模板列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        parent::index();
        return $this->fetch();
    }
     
   
    /**
     * 技术参数模板添加
     * @adminMenu(
     *     'name'   => '技术参数模板添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '技术参数模板添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
        $params=Db::name('param')->field('id,name,content,type')->where('status',2)->order('id asc')->select();
       
       $this->assign('params',$params);
       $this->assign('end',count($params)-1);
        return $this->fetch();  
        
    }
    /**
     * 技术参数模板添加do
     * @adminMenu(
     *     'name'   => '技术参数模板添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '技术参数模板添加do',
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
       
        $data_add=[
            'name'=>$data['name'],
            'dsc'=>$data['dsc'],
            'cid'=>$data['cid'], 
            'sort'=>intval($data['sort']),
            'status'=>1,
            'aid'=>$admin['id'],
            'atime'=>$time,
            'time'=>$time,
        ];
        
        $m->startTrans();
        $id=$m->insertGetId($data_add);
         
        //记录操作记录
        $flag=$this->flag;
        $table=$this->table;
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>'添加'.$flag.$id.'-'.$data['name'],
            'table'=>$table,
            'type'=>'add',
            'pid'=>$id,
            'link'=>url('admin/'.$table.'/edit',['id'=>$id]),
            'shop'=>$admin['shop'],
        ];
        Db::name('action')->insert($data_action); 
        //关联的参数 
        if(empty($_POST['ids'])){
            $this->error('没有选择参数项');
        } else
        
        $ids=$_POST['ids'];
        $data_param=[];
        foreach($ids as $v){
            $data_param[]=[
                'p_id'=>$v,
                't_id'=>$id,
            ];
        }
        Db::name('template_param')->insertAll($data_param);
        
        $m->commit();
        $this->success('添加成功',$url);
    }
    /**
     * 技术参数模板详情
     * @adminMenu(
     *     'name'   => '技术参数模板详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '技术参数模板详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit(); 
        //获取所有参数供选择
        $params=Db::name('param')->field('id,name,content,type')->where('status',2)->order('id asc')->select();
        //获取关联的参数
        $id=input('id',0,'intval');
        $ids=Db::name('template_param')->where('t_id',$id)->column('p_id');
        $this->assign('params',$params);
        $this->assign('end',count($params)-1);
        $this->assign('ids',$ids);
        
        return $this->fetch();  
    }
    /**
     * 技术参数模板状态审核
     * @adminMenu(
     *     'name'   => '技术参数模板状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '技术参数模板状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 技术参数模板状态批量同意
     * @adminMenu(
     *     'name'   => '技术参数模板状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '技术参数模板状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    /**
     * 技术参数模板禁用
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
     * 技术参数模板信息状态恢复
     * @adminMenu(
     *     'name'   => '技术参数模板信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '技术参数模板信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 技术参数模板编辑提交
     * @adminMenu(
     *     'name'   => '技术参数模板编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '技术参数模板编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        parent::edit_do();
    }
    /**
     * 技术参数模板编辑列表
     * @adminMenu(
     *     'name'   => '技术参数模板编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '技术参数模板编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();  
    }
    
    /**
     * 技术参数模板审核详情
     * @adminMenu(
     *     'name'   => '技术参数模板审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '技术参数模板审核详情',
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
        //模板编辑要转化content的值
        $id=$this->request->param('id',0,'intval');
        $change=Db::name('edit_info')->where('eid',$id)->value('content');
        
        $change=json_decode($change,true);
        //获取改变的参数对应，转化为数组
        if(!empty($change['content'])){
            $change['content']=explode(',', $change['content']);
        } 
        $this->assign('info',$info);
        $this->assign('info1',$info1);
        $this->assign('change',$change);
        $this->cates();
         
        //获取所有参数供选择
        $params=Db::name('param')->field('id,name,content,type')->where('status',2)->order('id asc')->select();
        //获取关联的参数
        $id=input('id',0,'intval');
        $ids=Db::name('template_param')->where('t_id',$info1['pid'])->column('p_id');
        $this->assign('params',$params);
        $this->assign('end',count($params)-1);
        $this->assign('ids',$ids);
      
        return $this->fetch();  
    }
    /**
     * 技术参数模板信息编辑审核
     * @adminMenu(
     *     'name'   => '技术参数模板编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '技术参数模板编辑审核',
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
        if($admin['shop']!=1){
            if(empty($info['shop']) || $info['shop']!=$admin['shop']){
                $this->error('不能审核其他店铺的信息');
            }
        }
        $time=time();
        //组装数据
        $update_info=[
            'time'=>$time,
        ];
        
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
            //得到修改的字段
            $change=Db::name('edit_info')->where('eid',$id)->value('content');
            $change=json_decode($change,true);
           
            foreach($change as $k=>$v){
                $update_info[$k]=$v;
                //模板参数变化
                if($k=='content'){
                    unset($update_info['content']);
                    $p_ids=explode(',', $change['content']); 
                }
            }
            $row=$m->where('id',$info['pid'])->update($update_info);
            if($row!==1){
                $m->rollback();
                $this->error('信息更新失败，请刷新后重试');
            } 
            //模板参数变化
            if(isset($p_ids)){
                $m_t_p=Db::name('template_param');
                $data_t_p=[];
                foreach($p_ids as $v){
                    $data_t_p[]=[
                        't_id'=>$info['pid'],
                        'p_id'=>$v,
                    ];
                }
                $m_t_p->where('t_id',$info['pid'])->delete();
                $m_t_p->insertAll($data_t_p);
            }
        }
       
        //审核成功，记录操作记录,发送审核信息
        $flag=$this->flag;
        $review_status=$this->review_status;
        //记录操作记录
        $link=url('admin/'.$table.'/edit_info',['id'=>$info['id']]);
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>'审核'.$info['aid'].'-'.$info['aname'].'对'.$flag.$info['pid'].'-'.$info['pname'].'的编辑为'.$review_status[$status],
            'table'=>$table,
            'type'=>'edit_review',
            'pid'=>$info['pid'],
            'link'=>$link,
            'shop'=>$admin['shop'],
        ];
        //发送审核信息
        $data_msg=[
            'aid'=>1,
            'time'=>$time,
            'uid'=>$info['aid'],
            'dsc'=>'对'.$flag.$info['pid'].'-'.$info['pname'].'的编辑已审核，结果为'.$review_status[$status],
            'type'=>'edit_review',
            'link'=>$link,
            'shop'=>$admin['shop'],
        ];
        Db::name('action')->insert($data_action);
        Db::name('msg')->insert($data_msg);
         
        $m->commit();
       
        $this->success('审核成功');
    }
    /**
     * 技术参数模板编辑记录批量删除
     * @adminMenu(
     *     'name'   => '技术参数模板编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '技术参数模板编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    
    /**
     * 技术参数模板批量删除
     * @adminMenu(
     *     'name'   => '技术参数模板批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '技术参数模板批量删除',
     *     'param'  => ''
     * )
     */
    public function del_all()
    {
        parent::del_all();
        
    }
   
     
}
