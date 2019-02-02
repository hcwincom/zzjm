<?php
 
namespace app\money\controller;
 
class AdminFreightpays2Controller extends FreightpaysBaseController
{
    
    public function _initialize()
    {
        parent::_initialize();
       
       
        $this->oflag='客户售后';
       
        $this->otable='orderback';
        $this->otype=2;
        $this->ogtable='orderback_goods';
       
        $this->flag='客户售后运费结算';
         
        $this->assign('flag',$this->flag); 
       
        $this->assign('oflag',$this->oflag); 
        $this->assign('otype',$this->otype); 
    
        $this->assign('order_status',[
            1=>'待审核',2=>'确认',3=>'售后产品提交中',4=>'售后产品处理中',5=>'已完成',6=>'已取消',7=>'已废弃']);
        $this->assign('ourl',url('orderback/AdminOrderback1/edit','',false,false)); 
        
    }
    /**
     * 客户售后运费结算
     * @adminMenu(
     *     'name'   => ' 客户售后运费结算',
     *     'parent' => 'money/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 12,
     *     'icon'   => '',
     *     'remark' => ' 客户售后运费结算',
     *     'param'  => ''
     * )
     */
    public function index()
    {
         parent::index();
        return $this->fetch('admin_freightpays/index');
    }
     
    
    /**
     * 客户客户售后列表
     * @adminMenu(
     *     'name'   => ' 客户客户售后列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户客户售后列表',
     *     'param'  => ''
     * )
     */
    public function orders()
    {
        parent::orders(); 
        return $this->fetch('admin_freightpays/orders');
    }
    /**
     * 客户售后运费结算添加页面
     * @adminMenu(
     *     'name'   => '客户售后运费结算添加页面',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户售后运费结算添加页面',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
        return $this->fetch('admin_freightpays/add');
    }
    /**
     * 客户售后运费结算添加
     * @adminMenu(
     *     'name'   => '客户售后运费结算添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户售后运费结算添加',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        parent::add_do();
       
    }
    
     
    /**
     * 客户售后运费结算列表
     * @adminMenu(
     *     'name'   => ' 客户售后运费结算列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户售后运费结算列表',
     *     'param'  => ''
     * )
     */
    public function orderpays()
    {
        parent::orderpays();
        return $this->fetch('admin_freightpays/orderpays');
    }
    /**
     * 客户售后运费结算详情
     * @adminMenu(
     *     'name'   => ' 客户售后运费结算详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户售后运费结算详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();
        return $this->fetch('admin_freightpays/edit');
    }
    
    /**
     * 客户售后运费结算审核
     * @adminMenu(
     *     'name'   => '客户售后运费结算审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户售后运费结算审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
        return $this->fetch();  
    }
    
}
