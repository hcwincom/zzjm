<?php
 
namespace app\goods\controller;
 
use think\Db; 
 
class AdminTemplateController extends GoodsBaseController
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
     *     'parent' => 'goods/AdminIndex/default',
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
         parent::add_do();
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
        parent::edit_review();
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
