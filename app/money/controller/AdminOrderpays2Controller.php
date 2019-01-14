<?php
 
namespace app\money\controller;
 
class AdminOrderpays2Controller extends OrderpaysBaseController
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->uflag='供应商';
        $this->oflag='采购单';
        $this->utable='supplier';
        $this->otable='ordersup';
        $this->ogtable='ordersup_goods';
        $this->flag='供应商结算';
       
        $this->utype=2;
        $this->ptype=2;
        $this->otype=2;
        $this->assign('flag',$this->flag); 
        $this->assign('uflag',$this->uflag); 
        $this->assign('oflag',$this->oflag); 
        $this->assign('uurl',url('custom/AdminSupplier/edit','',false,false)); 
        $this->assign('ourl',url('order/AdminOrdersup/edit','',false,false)); 
        
    }
    /**
     * 定期结账供应商列表
     * @adminMenu(
     *     'name'   => ' 定期结账供应商列表',
     *     'parent' => 'money/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 2,
     *     'icon'   => '',
     *     'remark' => ' 定期结账供应商列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        parent::index();
        return $this->fetch('admin_orderpays/index');
    }
     
    
    /**
     * 供应商订单列表
     * @adminMenu(
     *     'name'   => ' 供应商订单列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '供应商订单列表',
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
     * 供应商结算列表
     * @adminMenu(
     *     'name'   => ' 供应商结算列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '供应商结算列表',
     *     'param'  => ''
     * )
     */
    public function orderpays()
    {
        parent::orderpays();
        return $this->fetch('admin_orderpays/orderpays');
    }
    /**
     * 供应商结算详情
     * @adminMenu(
     *     'name'   => ' 供应商结算详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '供应商结算详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();
        return $this->fetch('admin_orderpays/edit');
    }
    
    /**
     * 供应商结算审核
     * @adminMenu(
     *     'name'   => '供应商结算审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '供应商结算审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
        return $this->fetch();  
    }
    
}
