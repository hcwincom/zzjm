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
        //是否允许添加，删除 
        $this->assign('ok_add',2); 
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
    /* 改变采购单状态 */
    public function status_do($data,$status,$flag){
        
        
        $m=$this->m;
        $table=$this->table;
        
        $id=intval($data['id']);
        
        $info=$m->where('id',$id)->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        $time=time();
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1 && $info['shop']!=$admin['shop']){
            $this->error('不能编辑其他店铺的信息');
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
            $this->error('状态信息错误');
        }
      /*   $content=$m->order_edit($info, $data);
        if(!is_array($content)){
            $this->error($content);
        } */
        switch ($status){
            case 1: 
                $content['status']=2; 
                break;
            case 2:
                //判断是先付款后收货还是先收货
                $pay_type=isset($content['pay_type'])?$content['pay_type']:$info['pay_type'];
                if($pay_type==1){
                    $content['status']=10;
                }else{
                    $content['status']=20;
                }
                break;
            case 10:
                //手动待收货
                $content['status']=20;
                break;
            case 20:
                //供货商发货
                $content['status']=22;
                $content['send_time']=$time;
                break;
            case 22:
                //准备收货
                $content['status']=24;
               
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
            case 26:
                //退货
                $content['status']=70;
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
                $this->error('操作错误');
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
            $this->error('保存数据错误，请重试');
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
        $rule='edit_review';
        $res=$this->check_review($admin,$rule);
        if($res){
            $this->redirect($rule,['id'=>$eid,'rstatus'=>2,'rdsc'=>'直接审核']);
        }
        $this->success('已提交修改');
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
    
}
