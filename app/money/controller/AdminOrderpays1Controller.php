<?php
 
namespace app\money\controller;
 
class AdminOrderpays1Controller extends OrderpaysBaseController
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->uflag='客户';
        $this->oflag='订单';
        $this->utable='custom';
        $this->otable='order';
        $this->ogtable='order_goods';
        $this->flag='客户结算';
       
        $this->utype=1;
        $this->otype=1;
        $this->ptype=1;
        $this->assign('flag',$this->flag); 
        $this->assign('uflag',$this->uflag); 
        $this->assign('oflag',$this->oflag); 
        $this->assign('uurl',url('custom/AdminCustom/edit','',false,false)); 
        $this->assign('ourl',url('order/AdminOrder/edit','',false,false)); 
        
    }
    /**
     * 定期结账客户列表
     * @adminMenu(
     *     'name'   => ' 定期结账客户列表',
     *     'parent' => 'money/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => ' 定期结账客户列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
         parent::index();
        return $this->fetch('admin_orderpays/index');
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
        return $this->fetch('admin_orderpays/orders');
    }
    /**
     * 订单结算添加页面
     * @adminMenu(
     *     'name'   => '订单结算添加页面',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单结算添加页面',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
        return $this->fetch('admin_orderpays/add');
    }
    /**
     * 订单结算添加
     * @adminMenu(
     *     'name'   => '订单结算添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单结算添加',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        parent::add_do();
       
    }
    
     
    /**
     * 客户结算列表
     * @adminMenu(
     *     'name'   => ' 客户结算列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户结算列表',
     *     'param'  => ''
     * )
     */
    public function orderpays()
    {
        parent::orderpays();
        return $this->fetch('admin_orderpays/orderpays');
    }
    /**
     * 客户结算详情
     * @adminMenu(
     *     'name'   => ' 客户结算详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户结算详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();
        return $this->fetch('admin_orderpays/edit');
    }
    
    /**
     * 客户结算审核
     * @adminMenu(
     *     'name'   => '客户结算审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户结算审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
        return $this->fetch();  
    }
    
}
