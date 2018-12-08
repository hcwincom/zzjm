<?php
 
namespace app\order\controller;

 
use think\Db; 
use app\order\model\OrderModel;
use cmf\controller\HomeBaseController;
use taobao\Taobao;
use app\store\model\StoreGoodsModel;
/**
 * 定时任务 
 */
class TaskController extends HomeBaseController
{
    private $m;
    private $flag;
    private $table;
    private $order_type;
   
   
    public function _initialize()
    {
        
        $this->flag='订单';
        $this->table='order';
        $this->m=new OrderModel();
        $this->order_type=3;
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
         
    }
    /**
     * 淘宝订单同步，30分钟一次
     */
    public function tabao_get()
    {
        set_time_limit(300);
        $log='taobao_get.txt'; 
        //统一文件锁
        $file = "log/taobao_lock.txt";  
        $fp = fopen($file, "r"); 
        if (flock($fp, LOCK_EX )){ 
            
            header("Content-type: text/html; charset=utf-8");
              
            $where=[
                
                'type'=>2,
                'status'=>2,
            ];
            $companys0=Db::name('company')->where($where)->order('sort asc')->column('id,name,key_account,key_key,store,shop');
            if(empty($companys0)){
                zz_log('没有需要查询的数据',$log);
                exit;
            }
            $order_type=$this->order_type; 
           
            $m_store_goods=new StoreGoodsModel(); 
            $fields = 'tid,type,status,payment,orders,rx_audit_status,post_fee,status,modified,pay_time,'.
                'receiver_name,receiver_state,receiver_city,receiver_district,receiver_address,receiver_mobile'; 
              
            $time=time();
            $time_start=$time-2400*24;
            $time_end=$time;
            $date_start=date('Y-m-d',$time_start);
            $date_end=date('Y-m-d',$time_end);
            $start_created = $date_start."%2000:00:00";
            $end_created = $date_end."%2023:59:59";
            $companys=[];
            foreach($companys0 as $k=>$v){
                $companys[$v['shop']][]=$v;
            }
            $shops=array_keys($companys);
            //获取近期的所有的淘宝id，比较是否需要更换
            $where=[
                'shop'=>['in',$shops],
                'order_type'=>['eq',$order_type],
                'create_time'=>['egt',$time_start],
            ];
            $m=$this->m;
            $m_store_goods=new StoreGoodsModel();
            $oids0=$m->where($where)->column('id,name,status,pay_status,paytype,pay_type,order_amount,pay_time,shop');
            $oids=[];
            foreach($oids0 as $k=>$v){
                $oids[$v['shop']][$v['name']]=$v;
            }
            $m->startTrans();
            //淘宝店铺循环查询
            foreach($companys as $shop=>$vcompanys){
                foreach($vcompanys as $k=>$v){
                    $client = new Taobao($v['key_account'], $v['key_key']);
                    $status = "";
                    $client->get('/JSB/rest/trade/TradesSoldGetRequest?fields='.$fields.'&start_created='.$start_created.'&end_created='.$end_created.'&status='.$status);
                    
                    $order = $client->getContent(); 
                    $state=intval($client->status); 
                    //返回状态失败
                    if($state!=200){ 
                        zz_log('分公司'.$v['name'].'淘宝同步失败',$log);
                        continue;
                    }
                    $json=json_decode($order,true);
                    if(empty($json['trades_sold_get_response'])){ 
                        zz_log('分公司'.$v['name'].'淘宝同步失败'.$order,$log);
                        continue;
                    }
                    //所有的订单
                    $trades=$json['trades_sold_get_response']['trades']['trade'];
                    foreach($trades as $kk=>$vv){ 
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
                                zz_log('分公司'.$v['name'].'淘宝更新订单'.$vv['tid'].'失败,'.$res,$log);
                                continue;
                            }
                            //已存在订单可能有修改价格,暂时不管
                        }else{
                            //新增
                            $oid=$this->order_add($vv,$v,$shop);
                            if(!($oid>0)){
                                zz_log('分公司'.$v['name'].'淘宝添加订单'.$vv['tid'].'失败,'.$res,$log);
                                continue; 
                            }
                        }
                        $res=$m->status_change($oid,$old_status,2);
                        if(!($res>0)){ 
                            zz_log('分公司'.$v['name'].'淘宝订单'.$vv['tid'].'库存更新失败,'.$res,$log);
                            continue; 
                        }
                    } 
                } 
            }
            $m->commit();
        }  
        flock($fp,LOCK_UN);
        fclose($fp);
        zz_log('淘宝同步结束',$log);
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
            'company'=>$company['id'],
            'store'=>$company['store'],
            'uid'=>0,
            'aid'=>$aid,
            'shop'=>$shop,
            'order_amount'=>$taobao['payment'],
            'pay_freight'=>$taobao['post_fee'], 
            'addressinfo'=>$taobao['receiver_state'].'-'.$taobao['receiver_city'].'-'.(isset($taobao['receiver_district'])?$taobao['receiver_district']:''),
            'address'=>$taobao['receiver_address'],
            'accept_name'=>$taobao['receiver_name'],
            'mobile'=>isset($taobao['receiver_mobile'])?$taobao['receiver_mobile']:'',
            'pay_time'=>isset($taobao['pay_time'])?strtotime($taobao['pay_time']):0,
            'create_time'=>time(),
            'pay_status'=>1,
            'status'=>10,
            'sort'=>5,
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
        //标记排序，如果产品没有编码20，没找到产品21 
        $sort=0;
        $iids=[];
        $goods_add=[];
        $ogs=$taobao['orders']['order'];
        //用于没查找到数据的产品的goods-id
        $flag=0;
        //outer_sku_id和outer_iid都有，outer_sku_id更多，以outer_sku_id为主
        foreach($ogs as $k=>$v){
            
            $rows=['oid'=>$oid];
            
            if(empty($v['outer_sku_id'])){
                if(empty($v['outer_iid'])){
                    //标记排序，如果产品没有编码,没找到产品 
                    $sort=20; 
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
                $sort=20; 
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
        if($sort>0){
            $m->where('id',$oid)->update(['sort'=>$sort,'dsc'=>'产品要调整']);
        }
        return $oid;
    }
    
}
       