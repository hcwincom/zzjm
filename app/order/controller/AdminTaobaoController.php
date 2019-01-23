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
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 2,
     *     'icon'   => '',
     *     'remark' => '淘宝订单导入',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        set_time_limit(300);
        //统一文件锁
        $file = "log/taobao_lock.txt";  
        $fp = fopen($file, "r"); 
        if (flock($fp, LOCK_EX )){ 
            
            header("Content-type: text/html; charset=utf-8");
            $admin=$this->admin;
            $shop=($admin['shop']==1)?2:$admin['shop'];
            $where=[
                'shop'=>$shop,
                'type'=>2,
                'status'=>2,
            ];
            $companys=Db::name('company')->where($where)->order('sort asc')->column('id,name,key_account,key_key,store');
            $order_type=$this->order_type; 
            $log='taobao.log'; 
            $m_store_goods=new StoreGoodsModel(); 
          /*   payment	String	200.07	实付金额。精确到2位小数;单位:元。如:200.07，表示:200元7分
            seller_rate	Boolean	true	卖家是否已评价。可选值:true(已评价),false(未评价)
            post_fee	String	200.07	邮费。精确到2位小数;单位:元。如:200.07，表示:200元7分
            receiver_name	String	东方不败	收货人的姓名
            receiver_state	String	浙江省	收货人的所在省份
            receiver_address	String	淘宝城911号	收货人的详细地址
            receiver_zip	String	223700	收货人的邮编
            receiver_mobile	String	13512501826	收货人的手机号码
            receiver_phone	String	13819175372	收货人的电话号码
            consign_time	Date	2000-01-01 00:00:00	卖家发货时间。格式:yyyy-MM-dd HH:mm:ss
            received_payment	String	200.07	卖家实际收到的支付宝打款金额（由于子订单可以部分确认收货，这个金额会随着子订单的
            确认收货而不断增加，交易成功后等于买家实付款减去退款金额）。精确到2位小数;单位:元。如:200.07，表示:200元7分
            status	String	TRADE_NO_CREATE_PAY	交易状态。可选值:  
            TRADE_NO_CREATE_PAY(没有创建支付宝交易) * WAIT_BUYER_PAY(等待买家付款) * SELLER_CONSIGNED_PART(卖家部分发货) 
            * WAIT_SELLER_SEND_GOODS(等待卖家发货,即:买家已付款) * WAIT_BUYER_CONFIRM_GOODS(等待买家确认收货,即:卖家已发货) 
            * * TRADE_BUYER_SIGNED(买家已签收,货到付款专用) * TRADE_FINISHED(交易成功) 
            * * TRADE_CLOSED(付款以后用户退款成功，交易自动关闭) 
            * * TRADE_CLOSED_BY_TAOBAO(付款以前，卖家或买家主动关闭交易) * PAY_PENDING(国际信用卡支付付款确认中)
             * WAIT_PRE_AUTH_CONFIRM(0元购合约中)	* PAID_FORBID_CONSIGN(拼团中订单或者发货强管控的订单，已付款但禁止发货)
             *price	String	200.07	商品价格。精确到2位小数；单位：元。如：200.07，表示：200元7分
discount_fee	String	200.07	可以使用trade.promotion_details查询系统优惠系统优惠金额（如打折，VIP，满就送等），精确到2位小数，单位：元。如：200.07，表示：200元7分
total_fee	String	200.07	商品金额（商品价格乘以数量的总金额）。精确到2位小数;单位:元。如:200.07，表示:200元7分
created	Date	2000-01-01 00:00:00	交易创建时间。格式:yyyy-MM-dd HH:mm:ss
pay_time	Date	2000-01-01 00:00:00	付款时间。格式:yyyy-MM-dd HH:mm:ss。订单的付款时间即为物流订单的创建时间。
modified	Date	2000-01-01 00:00:00	交易修改时间(用户对订单的任何修改都会更新此字段)。格式:yyyy-MM-dd HH:mm:ss
end_time	Date	2000-01-01 00:00:00	交易结束时间。交易成功时间(更新交易状态为成功的同时更新)/确认收货时间或者交易关闭时间 。格式:yyyy-MM-dd HH:mm:ss
             buyer_nick	String	我在测试	买家昵称
has_buyer_message	Boolean	true	判断订单是否有买家留言，有买家留言返回true，否则返回false
*
             */
            $fields = 'tid,type,status,payment,orders,rx_audit_status,post_fee,status,modified,pay_time'.
                ',receiver_name,receiver_state,receiver_city,receiver_district,receiver_address,receiver_mobile'.
                ',buyer_nick,created,has_buyer_message,end_time,discount_fee,total_fee';
//             'discount_fee,created,total_fee,end_time,has_buyer_message'; 
            /* 
             * buyer_message	String	要送的礼物的，不要忘记的哦	买家留言
buyer_memo	String	上衣要大一号	买家备注（与淘宝网上订单的买家备注对应，只有买家才能查看该字段）
buyer_flag	Number	1	买家备注旗帜（与淘宝网上订单的买家备注旗帜对应，只有买家才能查看该字段）红、黄、绿、蓝、紫 分别对应 1、2、3、4、5
seller_memo	String	好的	卖家备注（与淘宝网上订单的卖家备注对应，只有卖家才能查看该字段）
seller_flag	Number	1	卖家备注旗帜（与淘宝网上订单的卖家备注旗帜对应，只有卖家才能查看该字段）红、黄、绿、蓝、紫 分别对应 1、2、3、4、5
invoice_name	String	淘宝	发票抬头
invoice_type	String	水果，图书	发票类型
buyer_nick	String	我在测试	买家昵称
             *  */
            $fields_full = 'tid,logistics_company,buyer_message,seller_memo,buyer_memo,invoice_name'.
                ',type,status,payment,orders,receiver_name,receiver_state,receiver_city,'.
                'receiver_district,receiver_address,receiver_mobile,receiver_phone,'.
            'receiver_zip,consign_time,received_payment,invoice_kind,'.
            'invoice_type,buyer_cod_fee'; 
            
            //"logistics_company":"中通快递"
            $time=time();
            $time_start=$time-3600*24;
            $time_end=$time;
            $date_start=date('Y-m-d',$time_start);
            $date_end=date('Y-m-d',$time_end);
            $start_created = $date_start."%2000:00:00";
            $end_created = $date_end."%2023:59:59";
            //获取近期的所有的淘宝id，比较是否需要更换
            $where=[
                'shop'=>['eq',$shop],
                'order_type'=>['eq',$order_type],
                'time'=>['egt',$time_start],
            ];
            $m=$this->m;
          
            $oids=$m->where($where)->column('name,id,status,is_back,pay_status,paytype,pay_type,order_amount,pay_time','name');
            $m->startTrans();
            foreach($companys as $k=>$v){
               
                $client = new Taobao($v['key_account'], $v['key_key']); 
                $status = ""; 
                $client->get('/JSB/rest/trade/TradesSoldGetRequest?fields='.$fields.'&start_created='.$start_created.'&end_created='.$end_created.'&status='.$status);
                
                $order = $client->getContent(); 
                zz_log($order);
                $state=intval($client->status);
               
                //返回状态失败
                if($state!=200){
                    echo '<h2>分公司'.$v['name'].'淘宝同步失败</h2>'; 
                    continue;
                }  
                $json=json_decode($order,true);
                if(empty($json['trades_sold_get_response'])){
                    echo '<h2>分公司'.$v['name'].'淘宝同步失败'.$order.'</h2>';
                    echo '<h2>'.$order.'</h2>';
                    continue;
                } 
                //所有的订单
                $trades=$json['trades_sold_get_response']['trades']['trade'];
               
                foreach($trades as $kk=>$vv){
                    $vv['dsc']='';
                    $vv['udsc']='';
                    //"buyer_message":"麻烦不要放价格清单","buyer_nick":"tb913800314",
                    //order--"logistics_company":"中通快递",
                    if(!empty($vv['has_buyer_message'])){
                        $client->get('/JSB/rest/trade/TradeFullinfoGetRequest?fields='.$fields_full.'&tid='.$vv['tid']);
                        $order_full = $client->getContent();
                        zz_log($order_full);
                        $arr=json_decode($order_full,true);
                        $trade=$arr['trade_fullinfo_get_response']['trade'];
                        if(!empty($trade['buyer_message'])){
                            $vv['udsc'].=$trade['buyer_message'].'。';
                        }
                        if(!empty($trade['buyer_memo'])){
                            $vv['udsc'].=$trade['buyer_memo'].'。';
                        }
                        if(!empty($trade['seller_message'])){
                            $vv['dsc'].=$trade['seller_message'].'。';
                        }
                        if(!empty($trade['seller_memo'])){
                            $vv['dsc'].=$trade['seller_memo'].'。';
                        }
                        
                    }
                   
                   
                   
                    //订单已存在
                    $update_order=[];
                    $old_status=1;
                    if(isset($oids[$vv['tid']])){
                        $oid=$oids[$vv['tid']]['id']; 
                        $old_status=$oids[$vv['tid']]['status'];
                        //要比较订单状态和产品
                        //根据订单状态比较
                        $res=$this->order_update($vv,$oids[$vv['tid']],$v);
                        if(!($res>0)){
                            $m->rollback();
                            flock($fp,LOCK_UN);
                            fclose($fp);
                            exit('分公司'.$v['name'].'淘宝更新订单'.$vv['tid'].'失败,'.$res);
                        }  
                        //已存在订单可能有修改价格,暂时不管
                    }else{ 
                         //新增 
                        $oid=$this->order_add($vv,$v,$shop,$admin['id']); 
                        if(!($oid>0)){
                            $m->rollback();
                            flock($fp,LOCK_UN);
                            fclose($fp);
                            exit('分公司'.$v['name'].'淘宝增加订单'.$vv['tid'].'失败,'.$oid);
                        }  
                    } 
                    $res=$m->status_change($oid,$old_status,2);
                    if(!($res>0)){
                        $m->rollback();
                        flock($fp,LOCK_UN);
                        fclose($fp);
                        exit('分公司'.$v['name'].'淘宝订单'.$vv['tid'].'库存更新失败,'.$res);
                    }  
                }
                echo '<h2>分公司'.$v['name'].'淘宝同步完成</h2>'; 
               
            }
            $m->commit();
        }  
        flock($fp,LOCK_UN);
        fclose($fp);
      
        exit();
    }   
    /**
     * 根据淘宝订单状态判断是否需要更改
     * @param string $taobao_status
     * @param array $order
     * @return number|string
     */
    public function order_update($taobao,$order,$company){
        $update_order=[];
        //根据订单状态比较
        switch ($taobao['status']){
            case 'TRADE_NO_CREATE_PAY':
            case 'WAIT_BUYER_PAY':
                //没有创建支付宝交易，等待买家付款
                if($order['status']<10){
                    //付款了要状态修改
                    $update_order['pay_status']=1;
                    $update_order['status']=10;
                } 
                break;
            case 'PAY_PENDING':
                //国际信用卡支付付款确认中
                if($order['status']<10){
                    //付款了要状态修改
                    $update_order['pay_status']=2;
                    $update_order['status']=10;
                }  
                break;
            case 'TRADE_CLOSED_BY_TAOBAO':
                //付款以前，卖家或买家主动关闭交易)
                if($order['status']<80){ 
                    $update_order['status']=80;
                    $update_order['pay_status']=1;
                }
               
                break;
            case 'WAIT_SELLER_SEND_GOODS':
                //等待卖家发货,即:买家已付款)
                if($order['status']<20){
                    //付款了要状态修改
                    $update_order['pay_status']=3;
                    $update_order['status']=20;
                } 
                break;
            case 'WAIT_BUYER_CONFIRM_GOODS':
                //等待买家确认收货,即:卖家已发货)
                if($order['status']<24){
                    $update_order['pay_status']=3;
                    $update_order['status']=24;
                } 
                break;
            case 'TRADE_BUYER_SIGNED':
                //买家已签收,货到付款专用)
                if($order['status']<26){
                    //已收货，货款也到付了,待确认
                    $update_order['pay_status']=2;
                    $update_order['status']=26;
                } 
                break;
            case 'TRADE_FINISHED':
                //(交易成功)
                if($order['status']<30){
                    $update_order['pay_status']=3;
                    $update_order['status']=30;
                    $update_order['completion_time']=strtotime($taobao['modified']);
                } 
                break;
            case 'TRADE_CLOSED':
                // * TRADE_CLOSED(付款以后用户退款成功，交易自动关闭)
                if($order['is_back']==0){
                    $update_order['pay_status']=3;
                    $update_order['is_back']=1;
                    
                    $update_order['status']=70;
                } 
                break;
             
        } 
        if(empty($update_order)){
            return 1;
        } 
        $m=$this->m;
        $update_order['order_type']=$this->order_type;
        $update_order['sort']=$m->get_sort($update_order); 
        $update_order['time']=time();
        if(empty($order['pay_time']) && isset($taobao['pay_time'])){
            $update_order['pay_time']=strtotime($taobao['pay_time']);
        }
        $m->where('id',$order['id'])->update($update_order); 
            
        if(!empty($update_order['is_back'])){
            $this->orderback_add($order['id']);
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
        if(empty($taobao['receiver_district'])){
            $taobao['receiver_district']=''; 
        }
        //新增
        $update_order=[
            'name'=>$taobao['tid'],
            'uname'=>$taobao['buyer_nick'],
            'order_type'=>$this->order_type,
            'ok_break'=>2,
            'company'=>$company['id'],
            'store'=>$company['store'],
            'uid'=>0,
            'aid'=>$aid,
            'shop'=>$shop,
            'order_amount'=>$taobao['payment'],
            'pay_freight'=>$taobao['post_fee'], 
            'addressinfo'=>$taobao['receiver_state'].'-'.$taobao['receiver_city'].'-'.$taobao['receiver_district'],
            'address'=>$taobao['receiver_address'],
            'accept_name'=>$taobao['receiver_name'],
            'mobile'=>isset($taobao['receiver_mobile'])?$taobao['receiver_mobile']:'',
            'pay_time'=>isset($taobao['pay_time'])?strtotime($taobao['pay_time']):0,
            'create_time'=>strtotime($taobao['created']),
            'time'=>time(),
            'pay_status'=>1,
            'status'=>10,
            'sort'=>0,
            'udsc'=>$taobao['udsc'],
            'dsc'=>$taobao['dsc']
        ];
        //省市县查询
        $m_area=Db::name('area');
        $where_area=[
            'type'=>1,
            'name'=>['like',mb_substr($taobao['receiver_state'], 0,2).'%']
        ];
        $province=$m_area->where($where_area)->find();
        if(!empty($province)){
            $update_order['province']=$province['id'];
        }
        $where_area=[
            'type'=>2,
            'name'=>['like',mb_substr($taobao['receiver_city'], 0,2).'%']
        ];
        $city=$m_area->where($where_area)->find();
        if(!empty($city)){
            $update_order['city']=$city['id'];
            $update_order['postcode']=$city['postcode']; 
        }
        if(!empty($taobao['receiver_district'])){
            $where_area=[
                'type'=>3,
                'name'=>['like',mb_substr($taobao['receiver_district'], 0,2).'%']
            ];
            $area=$m_area->where($where_area)->find();
            if(!empty($area)){
                $update_order['area']=$area['id'];
                $update_order['postcode']=$area['postcode']; 
            } 
        }
     
        //根据订单状态比较
        switch ($taobao['status']){
            case 'TRADE_NO_CREATE_PAY':
            case 'WAIT_BUYER_PAY':
                //没有创建支付宝交易，等待买家付款
                $update_order['pay_status']=1;
                $update_order['status']=10;
                break;
            case 'PAY_PENDING':
                //国际信用卡支付付款确认中
                $update_order['pay_status']=2;
                $update_order['status']=10;
                break;
            case 'TRADE_CLOSED_BY_TAOBAO':
                //付款以前，卖家或买家主动关闭交易)
                $update_order['status']=80;
                $update_order['pay_status']=1;
                break;
            case 'WAIT_SELLER_SEND_GOODS':
                //等待卖家发货,即:买家已付款) 
                $update_order['pay_status']=3;
                $update_order['status']=20; 
                break;
            case 'WAIT_BUYER_CONFIRM_GOODS':
                //等待买家确认收货,即:卖家已发货)
                $update_order['pay_status']=3;
                $update_order['status']=24;
                break;  
            case 'TRADE_BUYER_SIGNED':
                //买家已签收,货到付款专用) 
                $update_order['pay_status']=2;
                $update_order['status']=26; 
                break;
            case 'TRADE_FINISHED':
                //(交易成功) 
                $update_order['pay_status']=3;
                $update_order['status']=30; 
                $update_order['completion_time']=strtotime($taobao['modified']);
                break;
            case 'TRADE_CLOSED':
                // * TRADE_CLOSED(付款以后用户退款成功，交易自动关闭) 
                $update_order['pay_status']=3;
                $update_order['is_back']=1;
                $update_order['status']=70; 
                break;
        }
        $m=$this->m;
        $update_order['sort']=$m->get_sort($update_order);
        $oid=$m->insertGetId($update_order);
        //标记排序，如果产品没有编码20，没找到产品21 
        $sort=0;
        $iids=[];
        $goods_add=[];
        $ogs=$taobao['orders']['order'];
        //用于没查找到数据的产品的goods-id
        $flag=0;
        $buyer_nick='';
        //统计数量
        $num=0;
        foreach($ogs as $k=>$v){
             
            $buyer_nick=(empty($v['buyer_nick']))?$buyer_nick:$v['buyer_nick'];
            $rows=['oid'=>$oid];
           
            //outer_sku_id	String	81893848	外部网店自己定义的Sku编号
            //outer_sku_id
            //$tmp_goods['goods_code'] = $v['outer_sku_id'];//产品编码
            //outer_iid	String	152e442aefe88dd41cb0879232c0dcb0
            //商家外部编码(可与商家外部系统对接)。外部商家自己定义的商品Item的id，可以通过taobao.items.custom.get获取商品的Item的信息
            //outer_sku_id和outer_iid都有，outer_sku_id更多，以outer_sku_id为主
            if(empty($v['outer_sku_id'])){
                if(empty($v['outer_iid'])){
                    //标记排序，如果产品没有编码,没找到产品 
                    $sort=1; 
                    $rows['goods_code']=$flag--;
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
            $num+=$v['num'];
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
                $sort=1; 
                $goods_add[$k]['goods']=$flag--;
                $goods_add[$k]['goods_name']='';
                $goods_add[$k]['print_name']='';
                $goods_add[$k]['price_in']=0;
                $goods_add[$k]['price_sale']=0;
            }else{
                $goods_add[$k]['goods']=$goods_infos[$k]['id'];
                $goods_add[$k]['goods_name']=$goods_infos[$k]['name'];
                $goods_add[$k]['print_name']=$goods_infos[$k]['name3'];
                $goods_add[$k]['price_in']=$goods_infos[$k]['price_in'];
                $goods_add[$k]['price_sale']=$goods_infos[$k]['price_sale'];
            }
            
        }
        Db::name('order_goods')->insertAll($goods_add);
        $update=[
            'goods_num'=>$num,
        ];
        if(empty($update_order['sort']) && $sort>0){
            $update['sort']=$sort; 
        }
        $m->where('id',$oid)->update($update);
        if(!empty($update_order['is_back'])){
          $this->orderback_add($oid);
        }
        return $oid;
    }
    //自动添加售后
    public function orderback_add($oid){
        //淘宝同步售后都由超管添加
        $aid=1;
        //都定为退货退款
        $type=2;
        $order_type=1;  
        $m_order=Db::name('order');
        $m_ogoods=Db::name('order_goods'); 
        $where_order=[
            'id'=>$oid
        ];
       
        $order=$m_order->where($where_order)->find();
        if(empty($order)){
            $this->error('未找到订单');
        }
         
        //店铺
        $shop=$order['shop'];
       
        //原订单产品
        $infos=$m_ogoods->where('oid',$oid)->column('*','goods');
         
        $time=time();
        $data_orderback=[
            'name'=>date('Ymd').substr($time,-6).$aid,
            'aid'=>$aid,
            'atime'=>$time,
            'create_time'=>$time,
            'time'=>$time,
            'type'=>$type,
            'order_type'=>$order_type,
            'shop'=>$order['shop'],
            'company'=>$order['company'],
            'uid'=>$order['uid'],
            'uname'=>$order['uname'],
            'about'=>$order['id'],
            'about_name'=>$order['name'],
            'store1'=>$order['store'],
            'back_money'=>$order['order_amount'],
            'goods_money'=>$order['goods_money'],
        ];
        /* $fields_int=['store1','store2','express1','express2','province','city','area','freight','pay_type'];
        foreach($fields_int as $v){
            $data_orderback[$v]=intval($data[$v]);
        }
        $fields_round=['goods_money','back_money','weight','size','real_freight','pay_freight'];
        foreach($fields_round as $v){
            $data_orderback[$v]=round($data[$v],2);
        }
        $fields_str=['express_no1','express_no2','postcode','accept_name','mobile','phone','address','addressinfo'];
        foreach($fields_str as $v){
            $data_orderback[$v]=$data[$v];
        } */
        $fields_str=['accept_name','mobile','phone','address','addressinfo',
            'province','city','area','size','weight'];
        foreach($fields_str as $v){
            $data_orderback[$v]=$order[$v];
        }
        $m=Db::name('orderback');
        $m_info=Db::name('orderback_goods');
      
        $oid= $m->insertGetId($data_orderback);
       
        //产品数据
        if(!empty($infos)){ 
            foreach($infos as $k=>$v){
                $data_goods[]=[
                    'oid'=>$oid,
                    'goods'=>$k,
                    'goods_name'=>$v['goods_name'],
                    'print_name'=>$v['print_name'],
                    'goods_uname'=>$v['goods_uname'],
                    'goods_ucate'=>$v['goods_ucate'],
                    'goods_code'=>$v['goods_code'],
                    'goods_pic'=>$v['goods_pic'],
                    'price_real'=>$v['price_real'],
                    'price_sale'=>$v['price_sale'],
                    'num'=>$v['num'],
                    'pay'=>$v['pay'],
                    'dsc'=>$v['dsc'],
                ];
            }
            $m_info->insertAll($data_goods);
        }
         
    }
}
       