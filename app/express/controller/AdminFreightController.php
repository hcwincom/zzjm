<?php
 
namespace app\express\controller;

 
use app\common\controller\AdminInfo0Controller; 
use think\Db; 
  
class AdminFreightController extends AdminInfo0Controller
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='我的快递';
        $this->table='freight';
        $this->m=Db::name('freight');
        //没有店铺区分
        $this->isshop=1;
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        
    }
    /**
     * 我的快递列表
     * @adminMenu(
     *     'name'   => '我的快递列表',
     *     'parent' => 'express/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '我的快递列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
         parent::index();
        return $this->fetch();
    }
     
   
    /**
     * 我的快递添加
     * @adminMenu(
     *     'name'   => '我的快递添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '我的快递添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
        return $this->fetch();  
        
    }
    /**
     * 我的快递添加do
     * @adminMenu(
     *     'name'   => '我的快递添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '我的快递添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        parent::add_do();
        
    }
    /**
     * 我的快递详情
     * @adminMenu(
     *     'name'   => '我的快递详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '我的快递详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();  
        return $this->fetch();  
    }
    /**
     * 我的快递状态审核
     * @adminMenu(
     *     'name'   => '我的快递状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '我的快递状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 我的快递状态批量同意
     * @adminMenu(
     *     'name'   => '我的快递状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '我的快递状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    /**
     * 我的快递禁用
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
     * 我的快递信息状态恢复
     * @adminMenu(
     *     'name'   => '我的快递信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '我的快递信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 我的快递编辑提交
     * @adminMenu(
     *     'name'   => '我的快递编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '我的快递编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        parent::edit_do();
    }
    /**
     * 我的快递编辑列表
     * @adminMenu(
     *     'name'   => '我的快递编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '我的快递编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();  
    }
    
    /**
     * 我的快递审核详情
     * @adminMenu(
     *     'name'   => '我的快递审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '我的快递审核详情',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        parent::edit_info();
        return $this->fetch();  
    }
    /**
     * 我的快递信息编辑审核
     * @adminMenu(
     *     'name'   => '我的快递编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '我的快递编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
    /**
     * 我的快递编辑记录批量删除
     * @adminMenu(
     *     'name'   => '我的快递编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '我的快递编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    
    /**
     * 我的快递批量删除
     * @adminMenu(
     *     'name'   => '我的快递批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '我的快递批量删除',
     *     'param'  => ''
     * )
     */
    public function del_all()
    {
         
        parent::del_all();
    }
   
     
}
