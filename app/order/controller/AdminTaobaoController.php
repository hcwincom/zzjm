<?php
 
namespace app\order\controller;

 
use think\Db; 
use app\order\model\OrderModel;
use cmf\controller\AdminBaseController;
use taobao\Taobao;
use app\store\model\StoreGoodsModel;

class AdminTaobaoController extends AdminBaseController
{
    
    public function _initialize()
    {
        parent::_initialize();
        //没有店铺区分
        $this->isshop=1;
        $this->flag='订单';
        $this->table='order';
        $this->m=new OrderModel();
       
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
        error_reporting(0);
        
        set_time_limit(0);
        exit('add');
        header("Content-type: text/html; charset=utf-8");
        $admin=$this->admin;
        $shop=($admin['shop']==1)?2:$admin['shop'];
        $where=[
            'shop'=>$shop,
            'type'=>2,
            'status'=>2,
        ];
        $companys=Db::name('company')->where()->column('id,key_account,key_key');
        $order_type=3;
        $log='taobao.log';
      
        $m_store_goods=new StoreGoodsModel();
//         $fields = "tid,type,status,payment,orders,rx_audit_status,post_fee,status,modified,pay_time"; 
        $fields = "tid,type,status,payment,orders,rx_audit_status,post_fee,status,modified,pay_time"; 
        $fields_info = "tid,orders,receiver_name,receiver_state,receiver_city,receiver_district,receiver_town,'.
        'receiver_address,receiver_mobile,receiver_phone,buyer_message,buyer_memo,invoice_name,invoice_type,buyer_cod_fee";
     
 
//批量查询只能得到是否有买家留言，发票类型也得不到
        //buyer_message	String	要送的礼物的，不要忘记的哦	买家留言
        //buyer_memo	String	上衣要大一号	买家备注（与淘宝网上订单的买家备注对应，只有买家才能查看该字段）
        //invoice_name	String	淘宝	发票抬头
//         invoice_type	String	水果，图书	发票类型 
//货到付款服务费暂时不计算
//         buyer_cod_fee	String	12.07	买家货到付款服务费。精确到2位小数;单位:元。如:12.07，表示:12元7分
        $time=time();
        $time_start=$time-24*3600*30;
        $date_start=date('Y-m-d',$time_start);
        $date_end=date('Y-m-d',$time_start);
        $start_created = $date_start."%2000:00:00";
        $end_created = $date_end."%2023:59:59";
        //获取近期的所有的淘宝id，比较是否需要更换
        $where=[
            'shop'=>['eq',$shop],
            'order_type'=>['eq',$order_type],
            'create_time'=>['egt',$time_start],
        ];
        $m=$this->m;
        $oids=$m->where($where)->column('name,id,status,pay_status,paytype,pay_type,order_amount','name');
        $m->startTrans();
        foreach($companys as $k=>$v){
            $ak0=$v['key_account'];
            $as0=$v['key_key']; 
            $client = new Taobao($ak0, $as0); 
            $status = ""; 
            $client->get('/JSB/rest/trade/TradesSoldGetRequest?fields='.$fields.'&start_created='.$start_created.'&end_created='.$end_created.'&status='.$status);
            
            $order = $client->status.$client->getContent(); 
            $state=intval($order);
            //返回状态失败
            if($state!=200){
                zz_log('分公司'.$k.'淘宝同步失败'.$order,$log);
                continue;
            } 
            $order=str_replace($state,'',$order);
            $json=json_decode($order,true);
            zz_log('分公司'.$k.'淘宝同步数'.$json['trades_sold_get_response']['total_results'],$log);
            //要废弃出入库的id
            $oids_close=[];
            //所有的订单
            $trades=$json['trades_sold_get_response']['trades']['trade'];
           
            foreach($trades as $kk=>$vv){
                //订单已存在
                $update_order=[];
                $where_order=['id'=>$oids[$vv['tid']]['id']];
                if(isset($oids[$vv['tid']])){
                    //要比较订单状态和产品
                    //根据订单状态比较
                    switch ($vv['status']){
                        case 'WAIT_SELLER_SEND_GOODS':
                            //等待卖家发货,即:买家已付款)
                            if($oids[$vv['tid']]['status']==10){
                                //付款了要状态修改
                                $update_order['pay_status']=3;
                                $update_order['status']=20;
                                $update_order['sort']=10;
                            } 
                            break;
                        case 'TRADE_CLOSED_BY_TAOBAO':
                            //付款以前，卖家或买家主动关闭交易) 
                            if($oids[$vv['tid']]['status']>=80){
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
                            if($oids[$vv['tid']]['status']==24){
                                //已收货，货款也到付了,待确认
                                $update_order['pay_status']=2;
                                $update_order['status']=26;
                                $update_order['sort']=5;
                            } 
                            break;
                        case 'TRADE_FINISHED':
                            //(交易成功) 
                            if($oids[$vv['tid']]['status']==24 || $oids[$vv['tid']]['status']==26){ 
                                $update_order['pay_status']=3;
                                $update_order['status']=30;
                                $update_order['sort']=0;
                            }
                            break;
                        case 'TRADE_CLOSED':
                            // * TRADE_CLOSED(付款以后用户退款成功，交易自动关闭) 
                            if($oids[$vv['tid']]['pay_status']==4){ 
                                $update_order['pay_status']=5;
                                $update_order['status']=70;
                                $update_order['sort']=0;
                            }
                            break; 
                    }
                    //有更新
                    if(!empty($update_order)){
                        $m->where($where_order)->update($update_order);
                        $res=$m->order_storein5($oids[$vv['tid']]['id'],'淘宝同步，取消订单');
                        if(!($res>0)){
                            $m->rollback();
                            exit('淘宝同步，取消订单'.$vv['tid'].'失败,'.$res);
                        }
                    } 
                }else{
                     
                    $update_order=[
                        'name'=>$vv['tid'],
                        'order_type'=>$order_type,
                        'company'=>$k,
                        'uid'=>0,
                        'aid'=>1,
                        'order_amount'=>$vv['payment'],
                        'pay_freight'=>$vv['post_fee'],
                          
                    ];
                    $oid=$m->insertGetId($update_order);
                }
                dump($vv);
                //237391564163094719
                $m->commit();
                exit('订单'.$vv['tid']);
                exit;
            }
            exit;
        }
        exit;
            
    }   
    /**
     * 根据淘宝订单状态判断是否需要更改
     * @param string $taobao_status
     * @param array $order
     * @return array|string
     */
    public function order_status($taobao_status,$order){
        $update_order=[];
        //根据订单状态比较
        switch ($taobao_status){
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
        return $update_order;
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
        
        

