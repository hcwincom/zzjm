<?php
 
namespace app\order\controller;
 
use app\order\model\OrderModel;
 
class AdminOrderController extends OrderBaseController
{
   
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='订单'; 
        $this->table='order';
        $this->m=new OrderModel(); 
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        $this->uflag='客户';
        $this->assign('uflag',$this->uflag);
        $this->utable='custom';
        $this->ogtable='order_goods';
        $this->utype=1;
        $this->assign('utype',$this->utype);
        $this->oid_type=1;
        $this->search=[
            1=>['p.name','订单编号'],
            2=>['p.express_no','物流编号'],
            3=>['p.id','订单id'],
            4=>['custom.name','客户名称'],
            5=>['custom.code','客户编号'], 
            6=>['p.accept_name','收货人'],
            7=>['p.mobile|p.phone','收货人电话'],
            
        ]; 
       
    }
    
    /**
     * 订单列表
     * @adminMenu(
     *     'name'   => '订单列表',
     *     'parent' => 'order/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '订单列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        parent::index();
        
        return $this->fetch();
        
    }
    /**
     * 我的订单
     * @adminMenu(
     *     'name'   => '我的订单',
     *     'parent' => 'index',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '我的订单',
     *     'param'  => ''
     * )
     */
    public function myorder()
    {
        parent::myorder();
        
        return $this->fetch();
        
    }
     
   
    /**
     * 订单添加
     * @adminMenu(
     *     'name'   => '订单添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
       parent::add(); 
       return $this->fetch();  
    }
    /**
     * 订单添加do
     * @adminMenu(
     *     'name'   => '订单添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        parent::add_do();
    }
    /**
     * 订单详情
     * @adminMenu(
     *     'name'   => '订单详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();
        
        return $this->fetch();  
    }
    /**
     * 订单编辑
     * @adminMenu(
     *     'name'   => '订单编辑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单编辑',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
         parent::edit_do();
    }
    
    /**
     * 订单编辑列表
     * @adminMenu(
     *     'name'   => '订单编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '订单编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list()
    {
        parent::edit_list();
        return $this->fetch();
    }
    /**
     * 订单编辑审核页面
     * @adminMenu(
     *     'name'   => ' 订单编辑审核页面',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '订单编辑审核页面',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        parent::edit_info();
        return $this->fetch();  
        
    }
    /**
     * 订单编辑审核确认
     * @adminMenu(
     *     'name'   => ' 订单编辑审核确认',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '订单编辑审核确认',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
         parent::edit_review();
    }
    
    //分类
    public function cates($type=3){
        parent::cates($type);
        $this->assign('order_types',config('order_type'));
        $this->assign('statuss',config('order_status')); 
        $this->assign('order_url',url('order/AdminOrder/edit',false,false));
        $this->assign('order_user_url',url('custom/AdminCustom/edit',false,false));
        
    }
    /**
     * 订单提交
     * @adminMenu(
     *     'name'   => '订单提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '订单提交',
     *     'param'  => ''
     * )
     */
    public function status_do1(){
        $flag='订单提交';
        $data=$this->request->param();
        $this->status_do($data,1,$flag);
        
    }
    /**
     * 订单确认
     * @adminMenu(
     *     'name'   => '订单确认',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '订单确认',
     *     'param'  => ''
     * )
     */ 
    public function status_do2(){
        $flag='确认订单';
        $data=$this->request->param();
        $this->status_do($data,2,$flag);
        
    }
    /**
     * 订单手动转为待发货
     * @adminMenu(
     *     'name'   => '订单手动转为待发货',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '订单手动转为待发货',
     *     'param'  => ''
     * )
     */ 
    public function status_do10(){
        
        $flag='将订单手动转为待发货';
        $data=$this->request->param();
        $this->status_do($data,10,$flag);
        
    }
    /**
     * 订单准备发货
     * @adminMenu(
     *     'name'   => '订单准备发货',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '订单准备发货',
     *     'param'  => ''
     * )
     */
    public function status_do20(){
        
        $flag='准备发货';
        $data=$this->request->param();
        $this->status_do($data,20,$flag);
        
    }
    /**
     * 订单仓库发货
     * @adminMenu(
     *     'name'   => '订单仓库发货',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '订单仓库发货',
     *     'param'  => ''
     * )
     */
    public function status_do22(){
        
        $flag='仓库发货';
        $data=$this->request->param();
       
        $this->status_do($data,22,$flag);
        
    }
    /**
     * 订单确认收货
     * @adminMenu(
     *     'name'   => '订单确认收货',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '订单确认收货',
     *     'param'  => ''
     * )
     */
    public function status_do24(){
        
        $flag='订单确认收货';
        $data=$this->request->param();
        $this->status_do($data,24,$flag);
        
    }
     
    /**
     *订单售后关闭
     * @adminMenu(
     *     'name'   => '订单售后关闭',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '订单售后关闭',
     *     'param'  => ''
     * )
     */
    public function status_do30(){
        
        $flag='订单售后关闭';
        $data=$this->request->param();
        $this->status_do($data,30,$flag);
        
    }
    
    /**
     * 超管还原订单状态
     * @adminMenu(
     *     'name'   => '超管还原订单状态',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '超管还原订单状态',
     *     'param'  => ''
     * )
     */
    public function status_do0(){
        
        $flag='超管还原订单状态';
        $data=$this->request->param();
        $this->status_do($data,0,$flag);
        
    }
    
    /**
     * 超管还原订单支付状态
     * @adminMenu(
     *     'name'   => '超管还原订单支付状态',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '超管还原订单支付状态',
     *     'param'  => ''
     * )
     */
    public function pay_do0(){
        
        $flag='超管还原订单支付状态';
        $data=$this->request->param();
        $this->pay_do($data,0,$flag);
        
    }
    /**
     * 用户付款订单
     * @adminMenu(
     *     'name'   => '用户付款订单',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '用户付款订单',
     *     'param'  => ''
     * )
     */
    public function pay_do1(){
        
        $flag='用户付款订单';
        $data=$this->request->param();
        $this->pay_do($data,1,$flag);
        
    }
    /**
     * 财务确认订单付款
     * @adminMenu(
     *     'name'   => '财务确认订单付款',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '财务确认订单付款',
     *     'param'  => ''
     * )
     */
    public function pay_do2(){
        
        $flag='财务确认订单付款';
        $data=$this->request->param();
        $this->pay_do($data,2,$flag);
        
    }
      
    /**
     * 废弃
     * @adminMenu(
     *     'name'   => '废弃订单',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '废弃订单',
     *     'param'  => ''
     * )
     */
    public function order_abandon(){
         
         parent::order_abandon();
    }
    /**
     * 配货单打印
     * @adminMenu(
     *     'name'   => '配货单打印',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '配货单打印',
     *     'param'  => ''
     * )
     */
    public function print_order(){ 
        parent::print_order(); 
        return $this->fetch();
    }
    /**
     * 状态更新直接确认
     * @adminMenu(
     *     'name'   => '状态更新直接确认',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 40,
     *     'icon'   => '',
     *     'remark' => '状态更新直接确认',
     *     'param'  => ''
     * )
     */
    public function status_review(){ 
    }
    /**
     * 订单提交直接确认
     * @adminMenu(
     *     'name'   => '订单提交直接确认',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 40,
     *     'icon'   => '',
     *     'remark' => '订单提交直接确认',
     *     'param'  => ''
     * )
     */
    public function status1_2(){
    }
    /**
     * 订单准备发货后直接仓库发货
     * @adminMenu(
     *     'name'   => '订单准备发货后直接仓库发货',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 40,
     *     'icon'   => '',
     *     'remark' => '订单准备发货后直接仓库发货',
     *     'param'  => ''
     * )
     */
    public function status20_22(){
    }
    /**
     * 付款后直接确认
     * @adminMenu(
     *     'name'   => '付款后直接确认',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 40,
     *     'icon'   => '',
     *     'remark' => '付款后直接确认',
     *     'param'  => ''
     * )
     */
    public function pay1_2(){
    }
    
    
}
