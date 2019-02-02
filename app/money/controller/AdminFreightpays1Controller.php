<?php
 
namespace app\money\controller;
 
class AdminFreightpays1Controller extends FreightpaysBaseController
{
    
    public function _initialize()
    {
        parent::_initialize();
       
       
        $this->oflag='订单';
       
        $this->otable='order';
        $this->otype=1;
        $this->ogtable='order_goods';
       
        $this->flag='订单运费结算';
         
        $this->assign('flag',$this->flag); 
       
        $this->assign('oflag',$this->oflag); 
        $this->assign('order_status',config('order_status')); 
       
        $this->assign('ourl',url('order/AdminOrder/edit','',false,false)); 
        
    }
    /**
     * 订单运费结算
     * @adminMenu(
     *     'name'   => ' 订单运费结算',
     *     'parent' => 'money/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 11,
     *     'icon'   => '',
     *     'remark' => ' 订单运费结算',
     *     'param'  => ''
     * )
     */
    public function index()
    {
         parent::index();
        return $this->fetch('admin_freightpays/index');
    }
     
    
    /**
     * 客户订单列表
     * @adminMenu(
     *     'name'   => ' 客户订单列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户订单列表',
     *     'param'  => ''
     * )
     */
    public function orders()
    {
        parent::orders(); 
        return $this->fetch('admin_freightpays/orders');
    }
    /**
     * 订单运费结算添加页面
     * @adminMenu(
     *     'name'   => '订单运费结算添加页面',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单运费结算添加页面',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
        return $this->fetch('admin_freightpays/add');
    }
    /**
     * 订单运费结算添加
     * @adminMenu(
     *     'name'   => '订单运费结算添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单运费结算添加',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        parent::add_do();
       
    }
    
     
    /**
     * 订单运费结算列表
     * @adminMenu(
     *     'name'   => ' 订单运费结算列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单运费结算列表',
     *     'param'  => ''
     * )
     */
    public function orderpays()
    {
        parent::orderpays();
        return $this->fetch('admin_freightpays/orderpays');
    }
    /**
     * 订单运费结算详情
     * @adminMenu(
     *     'name'   => ' 订单运费结算详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单运费结算详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();
        return $this->fetch('admin_freightpays/edit');
    }
    
    /**
     * 订单运费结算审核
     * @adminMenu(
     *     'name'   => '订单运费结算审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单运费结算审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
        return $this->fetch();  
    }
    
}
