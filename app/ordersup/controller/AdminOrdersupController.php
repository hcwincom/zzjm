<?php
 
namespace app\ordersup\controller;

 
use think\Db; 
use app\ordersup\model\OrdersupModel;
use app\order\controller\OrderBaseController;
 
class AdminOrdersupController extends OrderBaseController
{
    
    public function _initialize()
    {
        parent::_initialize(); 
        $this->flag='采购单'; 
        $this->table='ordersup';
        $this->m=new OrdersupModel(); 
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table); 
        $this->uflag='供应商';
        $this->assign('uflag',$this->uflag);
        $this->utable='supplier';
        $this->ogtable='ordersup_goods';
        $this->utype=2;
        $this->oid_type=2;
        $this->ptype=2;
        $this->assign('utype',$this->utype);
         
        $this->search=[
            1=>['p.name','采购单编号'],
            2=>['p.express_no','物流编号'],
            3=>['p.id','采购单id'],
            4=>['custom.name','供应商名称'],
            5=>['custom.code','供应商编号'],
            6=>['p.accept_name','供应商发货人'],
            7=>['p.mobile|p.phone','供应商发货人电话'], 
        ]; 
         
    }
    /**
     * 采购单列表
     * @adminMenu(
     *     'name'   => '采购单列表',
     *     'parent' => 'ordersup/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '采购单列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        
        parent::index();
        return $this->fetch();
    }
     
   
    /**
     * 采购单添加
     * @adminMenu(
     *     'name'   => '采购单添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '采购单添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
       
       parent::add();
        return $this->fetch();  
        
    }
    /**
     * 采购单添加do
     * @adminMenu(
     *     'name'   => '采购单添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '采购单添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        parent::add_do();
    }
    /**
     * 采购单详情
     * @adminMenu(
     *     'name'   => '采购单详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '采购单详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit(); 
        return $this->fetch();  
    }
    /**
     * 采购单编辑
     * @adminMenu(
     *     'name'   => '采购单编辑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '采购单编辑',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        parent::edit_do();
    }
    
    /**
     * 采购单编辑列表
     * @adminMenu(
     *     'name'   => '采购单编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '采购单编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list()
    {
        parent::edit_list();
        return $this->fetch();
    }
    /**
     * 采购单编辑审核页面
     * @adminMenu(
     *     'name'   => ' 采购单编辑审核页面',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '采购单编辑审核页面',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        
         parent::edit_info();
        return $this->fetch();  
        
    }
    /**
     * 采购单编辑审核确认
     * @adminMenu(
     *     'name'   => ' 采购单编辑审核确认',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '采购单编辑审核确认',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
     
    /**
     * 采购单提交
     * @adminMenu(
     *     'name'   => '采购单提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '采购单提交',
     *     'param'  => ''
     * )
     */
    public function status_do1(){
        $flag='提交采购单';
        $data=$this->request->param();
        $this->status_do($data,1,$flag);
        
    }
    /**
     * 采购单确认
     * @adminMenu(
     *     'name'   => '采购单确认',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '采购单确认',
     *     'param'  => ''
     * )
     */ 
    public function status_do2(){
        $flag='确认采购单';
        $data=$this->request->param();
        $this->status_do($data,2,$flag);
        
    }
    /**
     * 采购单手动转为待收货
     * @adminMenu(
     *     'name'   => '采购单手动转为待收货',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '采购单手动转为待收货',
     *     'param'  => ''
     * )
     */ 
    public function status_do10(){
        
        $flag='将采购单手动转为待收货';
        $data=$this->request->param();
        $this->status_do($data,10,$flag);
        
    }
    /**
     * 采购单已发货
     * @adminMenu(
     *     'name'   => '采购单已发货',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '采购单已发货',
     *     'param'  => ''
     * )
     */
    public function status_do20(){
        
        $flag='已发货';
        $data=$this->request->param();
        $this->status_do($data,20,$flag);
        
    }
    /**
     * 采购单准备收货
     * @adminMenu(
     *     'name'   => '采购单准备收货',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '采购单准备收货',
     *     'param'  => ''
     * )
     */
    public function status_do22(){
        
        $flag='准备收货';
        $data=$this->request->param();
        $this->status_do($data,22,$flag);
        
    }
    /**
     * 采购单确认收货
     * @adminMenu(
     *     'name'   => '采购单确认收货',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '采购单确认收货',
     *     'param'  => ''
     * )
     */
    public function status_do24(){
        
        $flag='采购单确认收货';
        $data=$this->request->param();
        $this->status_do($data,24,$flag);
        
    }
    /**
     * 采购单退货
     * @adminMenu(
     *     'name'   => '采购单退货',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '采购单退货',
     *     'param'  => ''
     * )
     */
    public function status_do26(){
        
        $flag='采购单退货';
        $data=$this->request->param();
        $this->status_do($data,26,$flag);
        
    }
    /**
     * 采购单退货
     * @adminMenu(
     *     'name'   => '采购单退货',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '采购单退货',
     *     'param'  => ''
     * )
     */
    public function status_do30(){
        
        $flag='采购单退货';
        $data=$this->request->param();
        $this->status_do($data,30,$flag);
        
    }
    /**
     * 采购单退货完成
     * @adminMenu(
     *     'name'   => '采购单退货完成',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '采购单退货完成',
     *     'param'  => ''
     * )
     */
    public function status_do40(){
        
        $flag='采购单退货完成';
        $data=$this->request->param();
        $this->status_do($data,40,$flag);
        
    }
    /**
     * 超管直接修改采购单状态
     * @adminMenu(
     *     'name'   => '超管直接修改采购单状态',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '超管直接修改采购单状态',
     *     'param'  => ''
     * )
     */
    public function status_do0(){
        
        $flag='超管直接修改采购单状态';
        $data=$this->request->param();
        $this->status_do($data,0,$flag);
        
    }
   
    /**
     * 超管还原采购单支付状态
     * @adminMenu(
     *     'name'   => '超管还原采购单支付状态',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '超管还原采购单支付状态',
     *     'param'  => ''
     * )
     */
    public function pay_do0(){
        
        $flag='超管还原采购单支付状态';
        $data=$this->request->param();
        $this->pay_do($data,0,$flag);
        
    }
    /**
     * 用户付款采购单
     * @adminMenu(
     *     'name'   => '用户付款采购单',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '用户付款采购单',
     *     'param'  => ''
     * )
     */
    public function pay_do1(){
        
        $flag='用户付款采购单';
        $data=$this->request->param();
        $this->pay_do($data,1,$flag);
        
    }
    /**
     * 确认采购单付款
     * @adminMenu(
     *     'name'   => '确认采购单付款',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '确认采购单付款',
     *     'param'  => ''
     * )
     */
    public function pay_do2(){
        
        $flag='确认采购单付款';
        $data=$this->request->param();
        $this->pay_do($data,2,$flag);
        
    }
    /**
     * 废弃采购单
     * @adminMenu(
     *     'name'   => '废弃采购单',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '废弃采购单',
     *     'param'  => ''
     * )
     */
    public function order_abandon(){
        
        parent::order_abandon();
    }
    /**
     * 采购单打印
     * @adminMenu(
     *     'name'   => '采购单打印',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '采购单打印',
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
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '状态更新直接确认',
     *     'param'  => ''
     * )
     */
    public function status_review(){
        
    }
}
