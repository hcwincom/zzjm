<?php
 
namespace app\order\controller;

 
use think\Db; 
use app\order\model\OrderModel;
 
use app\money\model\OrdersInvoiceModel;
use app\money\model\OrdersPayModel;
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
      
        if(empty($data['express_no0'][$data['id']])){
            $this->error('发货前请填写快递单号');
        }
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
    /* 改变订单状态 */
    public function status_do($data,$status,$flag){
        
        
        $m=$this->m;
        $table=$this->table;
      
        $id=intval($data['id']);
        $url_error=url('edit',['id'=>$id]); 
        $info=$m->get_one(['id'=>$id]);
        if(empty($info)){
            $this->error('数据不存在',$url_error);
        }
        $time=time();
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1 && $info['shop']!=$admin['shop']){
            $this->error('不能编辑其他店铺的信息',$url_error);
        }
        //有还原权限的为最高权限
        $res=$this->check_review($admin,'status_do0'); 
        if(!$res){
            //是否有权查看
            $res=$m->order_edit_auth($info,$admin);
            if($res!==1){
                $this->error($res);
            }
        }
      
        $update=[
            'pid'=>$info['id'],
            'aid'=>$admin['id'],
            'atime'=>$time,
            'table'=>$table,
            'url'=>url('edit_info','',false,false),
            'rstatus'=>1,
            'rid'=>0,
            'rtime'=>0,
            'shop'=>$admin['shop'],
        ];
        $update['adsc']=(empty($adsc))?$flag:$data['adsc'];
        
        if($status>0 && $info['status']!=$status){
            $this->error('状态信息错误',$url_error);
        }
        if(isset($data['express_no'])){
            $dsc=$data['dsc'];
            $express_no=$data['express_no'];
        }elseif(isset($data['dsc0'][$info['id']])){
            $dsc=$data['dsc0'][$info['id']];
            $express_no=$data['express_no0'][$info['id']];
        }
        
        if(isset($express_no)){
            if($info['dsc']!=$dsc){
                $content['dsc']=$dsc;
            }
            if($info['express_no']!=$dsc){
                $content['express_no']=$dsc;
            }
        }
       
        switch ($status){
            case 1:
                //手动待发货
                $content['status']=2;
                break;
            case 2:
                //判断是先付款后发货还是先发货
                $pay_type=isset($content['pay_type'])?$content['pay_type']:$info['pay_type'];
                if($pay_type==1){
                    $content['status']=10;
                }else{
                    $content['status']=20;
                }
                break;
            case 10:
                //手动待发货
                $content['status']=20;
                break;
            case 20:
                //准备发货
                $content['status']=22;
                //检查库存
                $res=$m->order_store($id);
                if($res!==1){
                    $this->error($res,$url_error);
                }
                break;
            case 22:
                //仓库发货
                $content['status']=24;
                $content['send_time']=$time;
                //检查库存
                $res=$m->order_store($id);
                if($res!==1){
                    $this->error($res,$url_error);
                }
                break;
            case 24:
                // 点击“确认收货”，订单状态为已收货，若已支付，则订单状态为已完成。
                $content['accept_time']=$time;
                $content['status']=26;
                if($info['pay_status']==3){
                    $content['completion_time']=$time;
                    $content['status']=30;
                }  
                break; 
            case 30:
                //退货
                $content['status']=70;
                break; 
            case 0:
                //超管编辑
                $content['status']=1;
                break;
            default:
                $this->error('操作错误',$url_error);
        }
        //淘宝订单的不能线下先收款，和到货
        if(isset($content['status']) && $info['order_type']==3){
            //淘宝订单只能点击准备发货和确认发货，暂时不做
        }
        
        //保存更改
        $m_edit=Db::name('edit');
        $m_edit->startTrans();
        $eid=$m_edit->insertGetId($update);
        if($eid>0){
            $data_content=[
                'eid'=>$eid,
                'content'=>json_encode($content),
            ];
            Db::name('edit_info')->insert($data_content);
        }else{
            $m_edit->rollback();
            $this->error('保存数据错误，请重试',$url_error);
        }
        
        //记录操作记录
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].$flag.$info['id'].'-单号'.$info['name'],
            'table'=>($this->table),
            'type'=>'edit',
            'pid'=>$info['id'],
            'link'=>url('edit_info',['id'=>$eid]),
            'shop'=>$admin['shop'],
        ];
        
        zz_action($data_action,$admin);
        
        $m_edit->commit();
        $this->redirect('edit_review',['id'=>$eid,'rstatus'=>2,'rdsc'=>'直接审核']);
        $rule='status_review';
        $res=$this->check_review($admin,$rule);
        if($res){
            $this->redirect('edit_review',['id'=>$eid,'rstatus'=>2,'rdsc'=>'直接审核']);
        } 
        $this->success('已提交修改');
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
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '状态更新直接确认',
     *     'param'  => ''
     * )
     */
    public function status_review(){ 
    }
    
}
