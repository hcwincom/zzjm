<?php
 
namespace app\orderback\controller;
 
class AdminOrderback1Controller extends OrderbackBaseController
{
   
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='订单售后';
        $this->order_type=1;
        $this->assign('flag',$this->flag);
        $this->assign('order_type',$this->order_type);
    }
    /**
     * 订单售后列表
     * @adminMenu(
     *     'name'   => '订单售后列表',
     *     'parent' => 'order/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '订单售后列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        parent::index();
        return $this->fetch();
    }
     
   
    /**
     * 订单售后添加
     * @adminMenu(
     *     'name'   => '订单售后添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单售后添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
         
        return $this->fetch();   
    }
    
    /**
     * 订单售后添加do
     * @adminMenu(
     *     'name'   => '订单售后添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单售后添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        parent::add_do();
       
    }
    /**
     * 订单售后详情
     * @adminMenu(
     *     'name'   => '订单售后详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单售后详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();
        return $this->fetch();  
    }
    /**
     * 订单售后编辑
     * @adminMenu(
     *     'name'   => '订单售后编辑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单售后编辑',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        parent::edit_do();
    }
    
    /**
     * 订单售后编辑列表
     * @adminMenu(
     *     'name'   => '订单售后编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '订单售后编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list()
    {
        parent::edit_list();
        return $this->fetch();
    }
    /**
     * 订单售后编辑审核页面
     * @adminMenu(
     *     'name'   => ' 订单售后编辑审核页面',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '订单售后编辑审核页面',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        
        parent::edit_info();
        return $this->fetch();  
        
    }
    /**
     * 订单售后编辑审核确认
     * @adminMenu(
     *     'name'   => ' 订单售后编辑审核确认',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '订单售后编辑审核确认',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
    /**
     * 确认售后单
     * @adminMenu(
     *     'name'   => '确认售后单',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '确认售后单',
     *     'param'  => ''
     * )
     */
    public function status_do(){
        
        parent::status_do();
    }
    /**
     * 售后产品提交状态确认
     * @adminMenu(
     *     'name'   => '售后产品提交状态确认',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '售后产品提交状态确认',
     *     'param'  => ''
     * )
     */
    public function status1_do(){
        parent::status1_do();
       
    }
    /**
     * 售后产品处理状态确认
     * @adminMenu(
     *     'name'   => '售后产品处理状态确认',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '售后产品处理状态确认',
     *     'param'  => ''
     * )
     */
    public function status2_do(){
        
        parent::status2_do();
    }
    /**
     *状态还原重新处理
     * @adminMenu(
     *     'name'   => '状态还原重新处理',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '状态还原重新处理',
     *     'param'  => ''
     * )
     */
    public function status0_do(){
        
        parent::status0_do();
    }
    /**
     *付款
     * @adminMenu(
     *     'name'   => '付款',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '付款',
     *     'param'  => ''
     * )
     */
    public function pay_do1(){
        
        parent::pay_do1();
    }
    /**
     *付款确认
     * @adminMenu(
     *     'name'   => '付款确认',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '付款确认',
     *     'param'  => ''
     * )
     */
    public function pay_do2(){
        parent::pay_do2();
        
    }
    /**
     *付款还原
     * @adminMenu(
     *     'name'   => '付款还原',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '付款还原',
     *     'param'  => ''
     * )
     */
    public function pay_do0(){
        parent::pay_do0();
    }
    /**
     *售后完成
     * @adminMenu(
     *     'name'   => '售后完成',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '售后完成',
     *     'param'  => ''
     * )
     */
    public function status_end(){
        parent::status_end();
        
    }
    
     
}
