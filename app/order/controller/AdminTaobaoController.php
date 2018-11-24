<?php
 
namespace app\order\controller;

 
use think\Db; 
use app\order\model\OrderModel;
use cmf\controller\AdminBaseController;
use taobao\Taobao;
use app\store\model\StoreGoodsModel;

class AdminTaobaoController extends AdminBaseController
{
    private $m;
    private $flag;
    private $table;
    private $order_type;
   
   
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='订单';
        $this->table='order';
        $this->m=new OrderModel();
        $this->order_type=3;
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
         
    }
    /**
     * 淘宝订单导入
     * @adminMenu(
     *     'name'   => '淘宝订单导入',
     *     'parent' => 'order/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 2,
     *     'icon'   => '',
     *     'remark' => '淘宝订单导入',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        
        set_time_limit(0);
       
        header("Content-type: text/html; charset=utf-8");
        $admin=$this->admin;
        $shop=($admin['shop']==1)?2:$admin['shop'];
        $where=[
            'shop'=>$shop,
            'type'=>2,
            'status'=>2,
        ];
        $companys=Db::name('company')->where($where)->order('sort asc')->column('id,name,key_account,key_key');
        $order_type=$this->order_type;
      
       
        $log='taobao.log';
      
        $m_store_goods=new StoreGoodsModel();
//      
        $fields = 'tid,type,status,payment,orders,rx_audit_status,post_fee,status,modified,pay_time,'.
            'receiver_name,receiver_state,receiver_city,receiver_district,receiver_address,receiver_mobile'; 
          
        $time=time();
        $time_start=$time-3600*24-30;
        $time_end=$time-3600*24-30;
        $date_start=date('Y-m-d',$time_start);
        $date_end=date('Y-m-d',$time_end);
        $start_created = $date_start."%2000:00:00";
        $end_created = $date_end."%2023:59:59";
        //获取近期的所有的淘宝id，比较是否需要更换
        $where=[
            'shop'=>['eq',$shop],
            'order_type'=>['eq',$order_type],
            'create_time'=>['egt',$time_start],
        ];
        $m=$this->m;
        $oids=$m->where($where)->column('name,id,status,pay_status,paytype,pay_type,order_amount,pay_time','name');
        $m->startTrans();
        foreach($companys as $k=>$v){
           
            $client = new Taobao($v['key_account'], $v['key_key']); 
            $status = ""; 
            $client->get('/JSB/rest/trade/TradesSoldGetRequest?fields='.$fields.'&start_created='.$start_created.'&end_created='.$end_created.'&status='.$status);
            
            $order = $client->getContent(); 
            
            $state=intval($client->status);
            //返回状态失败
            if($state!=200){
                echo '<h2>分公司'.$v['name'].'淘宝同步失败</h2>'; 
                continue;
            } 
            
            $json=json_decode($order,true);
            
            //所有的订单
            $trades=$json['trades_sold_get_response']['trades']['trade'];
           
            foreach($trades as $kk=>$vv){
                
                //订单已存在
                $update_order=[];
               
                if(isset($oids[$vv['tid']])){
                    $oid=$oids[$vv['tid']]['id'];
                    //要比较订单状态和产品
                    //根据订单状态比较
                    $res=$this->order_update($vv,$oids[$vv['tid']]);
                    if(!($res>0)){
                        $m->rollback();
                        exit('分公司'.$v['name'].'淘宝更新订单'.$vv['tid'].'失败,'.$res);
                    }  
                    //已存在订单可能有修改价格,暂时不管
                }else{ 
                     //新增 
                    $oid=$this->order_add($vv,$k,$shop,$admin['id']); 
                    if(!($oid>0)){
                        $m->rollback();
                        exit('分公司'.$v['name'].'淘宝增加订单'.$vv['tid'].'失败,'.$oid);
                    }  
                } 
            }
            echo '<h2>分公司'.$v['name'].'淘宝同步完成</h2>'; 
           
        }
        $m->commit();
        exit;
            
    }   
    /**
     * 根据淘宝订单状态判断是否需要更改
     * @param string $taobao_status
     * @param array $order
     * @return number|string
     */
    public function order_update($taobao,$order){
        $update_order=[];
        //根据订单状态比较
        switch ($taobao['status']){
            case 'WAIT_SELLER_SEND_GOODS':
                //等待卖家发货,即:买家已付款)
                if($order['status']==10){
                    //付款了要状态修改
                    $update_order['pay_status']=3;
                    $update_order['status']=20;
                    $update_order['sort']=10;
                }
                break;
            case 'TRADE_CLOSED_BY_TAOBAO':
                //付款以前，卖家或买家主动关闭交易)
                if($order['status']>=80){
                    //'已废弃',
                    continue;
                }else{
                    $update_order['status']=80;
                    $update_order['pay_status']=1;
                    $update_order['sort']=0;
                    
                }
                break;
            case 'TRADE_BUYER_SIGNED':
                //买家已签收,货到付款专用)
                if($order['status']==24){
                    //已收货，货款也到付了,待确认
                    $update_order['pay_status']=2;
                    $update_order['status']=26;
                    $update_order['sort']=5;
                }
                break;
            case 'TRADE_FINISHED':
                //(交易成功)
                if($order['status']==24 || $order['status']==26){
                    $update_order['pay_status']=3;
                    $update_order['status']=30;
                    $update_order['sort']=0;
                }
                break;
            case 'TRADE_CLOSED':
                // * TRADE_CLOSED(付款以后用户退款成功，交易自动关闭)
                if($order['pay_status']==4){
                    $update_order['pay_status']=5;
                    $update_order['status']=70;
                    $update_order['sort']=0;
                }
                break;
        }
        $m=$this->m;
        if(empty($update_order)){
            return 1;
        }else{
            if(empty($order['pay_time']) && isset($taobao['pay_time'])){
                $update_order['pay_time']=strtotime($taobao['pay_time']);
            }
            $m->where('id',$order['id'])->update($update_order); 
            if(isset($update_order['status']) && $update_order['status']>80){
                $res=$m->order_storein5($order['id'],'淘宝同步，取消订单');
                if(!($res>0)){ 
                    return ('淘宝同步，取消订单'.$order['name'].'失败,'.$res);
                }
             }
        } 
        return 1;
    }
    /**
     * 根据淘宝订单数组组装新增数据
     * @param array $taobao
     * @param number $company所属公司
     * @param number $shop店铺
     * @param number $aid管理员
     * @return number|string
     */
    public function order_add($taobao,$company,$shop=2,$aid=1){
        //新增
        $update_order=[
            'name'=>$taobao['tid'],
            'order_type'=>$this->order_type,
            'company'=>$company,
            'uid'=>0,
            'aid'=>$aid,
            'shop'=>$shop,
            'order_amount'=>$taobao['payment'],
            'pay_freight'=>$taobao['post_fee'], 
            'addressinfo'=>$taobao['receiver_state'].'-'.$taobao['receiver_city'].'-'.$taobao['receiver_district'],
            'address'=>$taobao['receiver_address'],
            'accept_name'=>$taobao['receiver_name'],
            'mobile'=>$taobao['receiver_mobile'],
            'pay_time'=>isset($taobao['pay_time'])?strtotime($taobao['pay_time']):0,
            'create_time'=>time(),
            
        ];
        //根据订单状态比较
        switch ($taobao['status']){
            case 'WAIT_SELLER_SEND_GOODS':
                //等待卖家发货,即:买家已付款) 
                $update_order['pay_status']=3;
                $update_order['status']=20;
                $update_order['sort']=10; 
                break;
            case 'TRADE_CLOSED_BY_TAOBAO':
                //付款以前，卖家或买家主动关闭交易) 
                $update_order['status']=80;
                $update_order['pay_status']=1;
                $update_order['sort']=0; 
                break;
            case 'TRADE_BUYER_SIGNED':
                //买家已签收,货到付款专用) 
                $update_order['pay_status']=2;
                $update_order['status']=26;
                $update_order['sort']=5; 
                break;
            case 'TRADE_FINISHED':
                //(交易成功) 
                $update_order['pay_status']=3;
                $update_order['status']=30;
                $update_order['sort']=0; 
                break;
            case 'TRADE_CLOSED':
                // * TRADE_CLOSED(付款以后用户退款成功，交易自动关闭) 
                $update_order['pay_status']=5;
                $update_order['status']=70;
                $update_order['sort']=0; 
                break;
        }
        $m=$this->m;
        $oid=$m->insertGetId($update_order);
        
        $iids=[];
        $goods_add=[];
        $ogs=$taobao['orders']['order'];
        foreach($ogs as $k=>$v){
            
            $rows=['oid'=>$oid];
           
            //outer_sku_id	String	81893848	外部网店自己定义的Sku编号
            //outer_sku_id
            //$tmp_goods['goods_code'] = $v['outer_sku_id'];//产品编码
            //outer_iid	String	152e442aefe88dd41cb0879232c0dcb0
            //商家外部编码(可与商家外部系统对接)。外部商家自己定义的商品Item的id，可以通过taobao.items.custom.get获取商品的Item的信息
            //outer_sku_id和outer_iid都有，outer_sku_id更多，以outer_sku_id为主
            if(empty($v['outer_sku_id'])){
                if(empty($v['outer_iid'])){
                    return '产品'.$v['title'].'没有编码outer_sku_id和outer_iid，'.'num_iid'.$v['num_iid'];
                }else{
                    $rows['goods_code'] = $v['outer_iid'];//产品编码
                }
            }else{
                $rows['goods_code'] = $v['outer_sku_id'];//产品编码
            }
           
            $iids[]= $rows['goods_code']; 
            $rows['goods_uname'] = $v['title'];//产品名称
            $rows['goods_ucate']=isset($v['sku_properties_name'])?$v['sku_properties_name']:'';//产品型号
            $rows['num'] = $v['num'];//产品数量
            $rows['goods_pic'] = $v['pic_path'];//产品数量
            
            $rows['price_real'] = $v['price'];//产品单价
            $rows['pay_discount'] = $v['discount_fee'];//优惠费用
            
            $rows['pay'] = $v['total_fee'];//产品总价
          
            $goods_add[$rows['goods_code']]=$rows;
        }
        $where_goods=[
            'shop'=>['eq',$shop],
            'code'=>['in',$iids],
        ];
        $goods_infos=Db::name('goods')->where($where_goods)->column('code,id,name,name3,price_in,price_sale');
        foreach ($goods_add as $k=>$v){
            if(empty($goods_infos[$k])){
                $goods_infos[$k]=[
                    'id'=>1,
                    'name'=>'name',
                    'name3'=>'name3',
                    'price_in'=>'11', 
                ];
                return '未找到产品'.$k.$v['goods_uname'];
            }
            $goods_add[$k]['goods']=$goods_infos[$k]['id'];
            $goods_add[$k]['goods_name']=$goods_infos[$k]['name'];
            $goods_add[$k]['print_name']=$goods_infos[$k]['name3'];
            $goods_add[$k]['price_in']=$goods_infos[$k]['price_in'];
            $goods_add[$k]['price_sale']=$goods_infos[$k]['price_sale'];
        }
        Db::name('order_goods')->insertAll($goods_add);
       
        return $oid;
    }
    
}
        //string(12913) "{"trades_sold_get_response":{"total_results":13,"trades":{"trade":[{"modified":"2018-10-23 10
       
        //trades":{"trade":[
//         {"modified":"2018-10-23 10:31:20",
//         "orders":
//         {"order":[
//         {"adjust_fee":"0.00","buyer_rate":true,"cid":50021631,"consign_time":"2018-09-28 10:21:38",
//         "discount_fee":"20.00","divide_order_fee":"140.00","end_time":"2018-10-08 10:21:44",
//         "invoice_no":"048929553270","is_daixiao":false,"logistics_company":"顺丰速运","num":1,
//         "num_iid":559884639463,"oid":"227668460417201790","outer_iid":"06-18-08","payment":"148.00",
//         "pic_path":"https://img.alicdn.com/bao/uploaded/i2/21040156/TB2IatkjxOMSKJjSZFlXXXqQFXa_!!21040156.jpg",
//         "price":"160.00","refund_status":"NO_REFUND","seller_rate":true,"seller_type":"C","shipping_type":"express",
//         "status":"TRADE_FINISHED","title":"潍柴发动机专用停机断油熄火电磁阀612600180175/+A装载机熄火器","total_fee":"140.00"
//         }    ]   }
//         ,"pay_time":"2018-09-27 19:54:34","payment":"148.00","post_fee":"8.00","status":"TRADE_FINISHED","tid":"227668460417201790",
//"type":"fixed"},
//         {"modified":"20
        //echo '状态：'.$state."<br>";
        
        //echo '订单：'.$order."<br><br>";
        
        

