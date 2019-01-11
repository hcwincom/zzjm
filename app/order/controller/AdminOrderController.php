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
       $this->assign('ok_break',1);
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
        $data=$this->request->param();
       
        $fields_int=[
            'company','uid','store','freight','accept','paytype','pay_type','goods_num',
        ];
        foreach($fields_int as $v){
            $data[$v]=intval($data[$v]);
            if(empty($data[$v])){
                $this->error('订单数据不完整'.$v);
            }
        }
        $fields_round=[
            'pay_freight','real_freight','tax_money','order_amount',
            'goods_money','other_money','discount_money','weight','size'
        ];
        foreach($fields_round as $v){
            $data[$v]=round($data[$v],2); 
        }
        if(empty($data['nums-0'])){
            $this->error('未选择产品');
        }
        //店铺和下单人
        $admin=$this->admin;
        $time=time();
        $data_order=[
            'order_type'=>1,
            'aid'=>$admin['id'],
            'shop'=>($admin['shop']==1)?2:$admin['shop'],
            'company'=>$data['company'],
            'uid'=>$data['uid'],
            'store'=>$data['store'],
            'freight'=>$data['freight'],
            'paytype'=>$data['paytype'],
            'pay_type'=>$data['pay_type'],
            'goods_num'=>$data['goods_num'],
            
            'pay_freight'=>$data['pay_freight'],
            'real_freight'=>$data['real_freight'],
            'tax_money'=>$data['tax_money'],
            'order_amount'=>$data['order_amount'],
            'other_money'=>$data['other_money'],
            'goods_money'=>$data['goods_money'],
            'discount_money'=>$data['discount_money'],
            'weight'=>$data['weight'],
            'size'=>$data['size'],
            
            'udsc'=>$data['udsc'],
            'dsc'=>$data['dsc'],
            'create_time'=>$time,
            'sort'=>2,
            'ok_break'=>$data['ok_break'],
        ];
 
        //收货地址信息 
        $field='p.name,p.mobile,p.phone,p.street,p.postcode'.
            ',p.province,p.city,p.area'.
            ',province.name as province_name,city.name as city_name,area.name as area_name';
        $tel=Db::name('tel')
        ->alias('p')
        ->field($field)
        ->join('cmf_area province','province.type=1 and p.province=province.id','left')
        ->join('cmf_area city','city.type=2 and p.city=city.id','left')
        ->join('cmf_area area','area.type=3 and p.area=area.id','left')
        ->where('p.id',$data['accept'])
        ->find(); 
        $data_order['province']=$tel['province'];
        $data_order['city']=$tel['city'];
        $data_order['area']=$tel['area'];
        $data_order['address']=$tel['street'];
        $data_order['accept_name']=empty($tel['name'])?:$tel['name'];
        $data_order['phone']=$tel['phone'];
        $data_order['mobile']=$tel['mobile'];
        $data_order['addressinfo']=$tel['province_name'].'-'.$tel['city_name'].'-'.$tel['area_name'];
        $data_order['postcode']=$tel['postcode'];
        
        //公司信息
        $company=Db::name('company')->field('id,name,code,shop')->where('id',$data_order['company'])->find();
        if($company['shop']!=$data_order['shop']){
            $this->error('订单来源错误');
        } 
        //单号
        $data_order['name']=order_sn($admin['id'],$company['code']);
        $m=$this->m;
        $m_info=Db::name('order_goods');
        $m->startTrans();
        $oid= $m->insertGetId($data_order);
        //添加订单产品order_goods
        $nums=$data['nums-0'];
        $store=$data_order['store'];
        $goods=array_keys($nums);
        
        //获取所有产品信息
        $where=[
            'id'=>['in',$goods], 
        ];
        $goods_infos=Db::name('goods')->where($where)->column('id,name,name3,code,pic,price_in,price_sale,type,weight1,size1');
         
        $order_goods=[];
        //标记是否需要拆分订单 
        foreach($nums as $k=>$v){
            $v=intval($v);
            if($v<=0){
                $this->error('产品数量错误');
            }
            $order_goods[$k]=[
                'oid'=>$oid,
                'goods'=>$k,
                'num'=>intval($v), 
                'price_real'=>round($data['price_reals-0'][$k],2),
                'pay_discount'=>round($data['pay_discounts-0'][$k],2), 
                'pay'=>round($data['pays-0'][$k],2), 
                'dsc'=>$data['dscs-0'][$k],
                'weight'=>round($data['weights-0'][$k],2),
                'size'=>round($data['sizes-0'][$k],2), 
                
                'goods_uname'=>$data['goods_unames-0'][$k],
                'goods_ucate'=>$data['goods_ucates-0'][$k],
                
                'goods_name'=>$goods_infos[$k]['name'],
                'print_name'=>$goods_infos[$k]['name3'], 
                'goods_code'=>$goods_infos[$k]['code'],
                'goods_pic'=>$goods_infos[$k]['pic'],
                'price_in'=>$goods_infos[$k]['price_in'],
                'price_sale'=>$goods_infos[$k]['price_sale'],
                
               
            ]; 
            //计算产品费用
            $pay=round($order_goods[$k]['price_real']*$order_goods[$k]['num']-$order_goods[$k]['pay_discount'],2);
            if($order_goods[$k]['pay'] != $pay){
                $this->error('产品费用错误');
            } 
          
            //判断产品重量体积单位,统一转化为kg,cm3
            $tmp_goods=$m->unit_change($goods_infos[$k]); 
            $order_goods[$k]['weight1']=$tmp_goods['weight1'];
            $order_goods[$k]['size1']=$tmp_goods['size1'];
           
        }
        //检查是否拆分订单
        if($data_order['ok_break']==1){
            $orders=$m->order_break($order_goods, $oid,$store,  $data_order['city'],  $data_order['shop']);
        }else{
            $orders=[1];
        } 
        if(count($orders)==1){
            $dsc='订单添加成功';
            
            $m_info->insertAll($order_goods);
        }else{
            $dsc='订单已拆分';
            $i=0;
            //主单号标记
            $m->where('id',$oid)->update(['is_real'=>2]);
            //拆分订单要生成子单号
            foreach($orders as $k=>$v){
                $i++;
                
                $tmp_order=[
                    'fid'=>$oid,
                    'name'=>$data_order['name'].'_'.$i,
                    'store'=>$k, 
                    'create_time'=>$time,
                    'aid'=>$admin['id'],
                    'order_type'=>$data_order['order_type'], 
                    'shop'=>$data_order['shop'],
                    'company'=>$data_order['company'],
                    'uid'=>$data_order['uid'], 
                    'paytype'=>$data_order['paytype'],
                    'pay_type'=>$data_order['pay_type'], 
                    'province'=>$data_order['province'], 
                    'city'=>$data_order['city'], 
                    'area'=>$data_order['area'], 
                    'address'=>$data_order['address'], 
                    'addressinfo'=>$data_order['addressinfo'], 
                    'phone'=>$data_order['phone'], 
                    'mobile'=>$data_order['mobile'], 
                    'postcode'=>$data_order['postcode'], 
                    'udsc'=>$data_order['udsc'],
                    'dsc'=>$data_order['dsc'],
                    'sort'=>$data_order['sort'],
                    'ok_break'=>$data_order['ok_break'],
                    'goods_money'=>0, 
                    'goods_num'=>0, 
                    'weight'=>0, 
                    'size'=>0, 
                   
                ];
                foreach($v as $kk=>$vv){
                    $tmp_order['goods_money']+=$vv['pay']; 
                    $tmp_order['goods_num']+=$vv['num'];
                    $tmp_order['weight']+=$vv['weight'];
                    $tmp_order['size']+=$vv['size'];
                }
                $tmp_order['order_amount']= $tmp_order['goods_money'];
                $tmp_oid=$m->insertGetId($tmp_order);
                foreach($v as $kk=>$vv){
                    $v[$kk]['oid']=$tmp_oid;
                }
                $m_info->insertAll($v);
            }
        }
        $update=[];
        //发票信息,要开发票，有抬头的保存
        if(!empty($data['invoice_title']) && !empty($data['invoice_type'])){
            $data_invoice=[
                'name'=>'fp'.$data_order['name'], 
                'oid'=>$oid,
                'oid_type'=>1,
                'ptype'=>1,
                'status'=>1,
                'uid'=>$data_order['uid'],
                'aid'=>$data_order['aid'],
                'atime'=>$time,
                'invoice_type'=>$data['invoice_type'], 
                'uname'=>$data['invoice_uname'],
                'ucode'=>$data['invoice_ucode'],
                'point'=>$data['invoice_point'],
                'tax_money'=>$data['invoice_tax_money'],
                'invoice_money'=>$data['invoice_invoice_money'],
                'dsc'=>$data['invoice_dsc'],
                'company'=>$company['id'], 
                'paytype'=>$data_order['paytype'], 
                
                'address'=>$data['invoice_address'],
                'tel'=>$data['invoice_tel'],
                'bank'=>$data['account_bank'],
                'bank_num'=>$data['account_num'], 
                'bank_location'=>$data['account_location'],
                 
            ];
            $m_invoice=new OrdersInvoiceModel();
            $update['invoice_id']=$m_invoice->invoice_add($data_invoice); 
           
        }
        //支付信息，有账户名的保存
        if(!empty($data['account_name']) ){
            $data_pay=[
               
                'oid'=>$oid,
                'oid_type'=>1, 
                'ptype'=>1,
                'bank'=>$data['account_bank'],
                'num'=>$data['account_num'],
                'name'=>$data['account_name'],
                'location'=>$data['account_location'],
                'paytype'=>$data_order['paytype'],  
                'money'=>$data['order_amount'], 
            ];
            $m_pay=new OrdersPayModel();
            $update['pay_id']=$m_pay->pay_add($data_pay); 
            
        }
        //更新发票和支付信息
        if(!empty($update)){
            $m->where('id',$oid)->update($update);
        }
        $m->commit();
        $this->success($dsc,url('edit',['id'=>$oid]));
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
        
        $info=$m->get_one(['id'=>$id]);
        if(empty($info)){
            $this->error('数据不存在');
        }
        $time=time();
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1 && $info['shop']!=$admin['shop']){
            $this->error('不能编辑其他店铺的信息');
        }
        //是否有权查看
        $res=$m->order_edit_auth($info,$admin);
        if($res!==1){
            $this->error($res);
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
        $content=$m->order_edit($info, $data);
        if(!is_array($content)){
            $this->error($content);
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
                    $this->error($res);
                }
                break;
            case 22:
                //仓库发货
                $content['status']=24;
                $content['send_time']=$time;
                //检查库存
                $res=$m->order_store($id);
                if($res!==1){
                    $this->error($res);
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
                $this->error('操作错误');
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
        
        zz_action($data_action,['department'=>$admin['department']]);
        
        $m_edit->commit();
        $rule='edit_review';
        $res=$this->check_review($admin,$rule);
        if($res){
            $this->redirect($rule,['id'=>$eid,'rstatus'=>2,'rdsc'=>'直接审核']);
        }
        $this->success('已提交修改');
    }
    /**
     * 超管直接修改订单支付状态
     * @adminMenu(
     *     'name'   => '超管直接修改订单支付状态',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '超管直接修改订单支付状态',
     *     'param'  => ''
     * )
     */
    public function pay_do0(){
        
        $flag='超管直接修改订单支付状态';
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
    
}
