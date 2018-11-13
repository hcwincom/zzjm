<?php
 
namespace app\order\model;

use think\Model;
use think\Db;
class OrderModel extends Model
{
    /**
     * 下单时为仓库排序，按首重价格计算
     * @param $city收货地
     * @param $shop店铺
     */ 
    public function store_sort($city,$shop){
        
        //先获取所有仓库，物流
        $where=[
            'shop'=>['eq',$shop],
            'status'=>['eq',2],
            'store'=>['gt',0]
        ];
        $stores=Db::name('freight')->where($where)->column('id,store');
        if(empty($stores)){
           return 0;
        }
        $freights=array_keys($stores);
        $freights=implode(',', $freights);
        //按首重费用排序，花费小的优先
        $fees=Db::name('freight_fee')
        ->alias('ff')
        ->field('ff.price0')
        ->join('cmf_express_area ea','ea.city='.$city.' and ea.area=ff.expressarea')
        ->where('ff.freight','in',$freights)
        ->order('ff.price0 asc')
        ->column('ff.freight');
        if(empty($fees)){
            return 0;
        }
        $sort=[];
        foreach($fees as $v){
            if(!in_array($stores[$v],$sort)){
                $sort[]=$stores[$v];
            }
        }
        return $sort;
        
    }
     
    /* 
     * 自动分单
     * @param $goods产品信息
     * @param $goods主单号
     * @param $store首选仓库
     * @param $city收货地
     * @param $shop
     *  */
    public function order_break($goods,$oid,$store,$city,$shop){
         //获取所有库存
         $goods_id=array_keys($goods);
         $where=[
             'goods'=>['in',$goods_id],
             'shop'=>['eq',$shop],
         ];
         $list=Db::name('store_goods')->where($where)->column('id,store,goods,num');
         //循环得到数据
         $store_num=[];
         foreach($list as $k=>$v){
             $store_num[$v['goods']][$v['store']]=$v['num'];
         }
         //获取优先的仓库,去除默认的
         $sort=$this->store_sort($city,$shop);
         if(empty($sort)){
             $sort=[];
         }else{
             $index=array_search ($store,$sort);
             if($index){
                 unset($sort[$index]);
             }
         }
         
         //最终order
         $order=[];
         //
         $num0=0;
         $num1=0;
         //如果默认库存不足就按优先仓库发货,重新计算费用和重量体积
         foreach($goods as $k=>$v){
            if(empty($store_num[$k][$store])){
                //没有库存，下面优先仓库发货
                $num0=0;
                $num1= $v['num'];
            }elseif($v['num']>$store_num[$k][$store]){
                //库存不足，先分单
                $num0=$store_num[$k][$store];
                $num1= $v['num']-$num0;
                $order[$store][$k]=$v;
                $order[$store][$k]['num']=$num0;
                $order[$store][$k]['pay']=bcmul($v['price_real'],$num0,2);
                $order[$store][$k]['weight']=bcmul($v['weight1'],$num0,2);
                $order[$store][$k]['size']=bcmul($v['size1'],$num0,2);
                
            }else{
                //库存足够，不用分单
                $order[$store][$k]=$v;
                continue;
            }
            //按优先仓库发货
            foreach($sort as $vv){
                if(empty($store_num[$k][$vv])){
                    //没有库存
                    continue;
                }elseif($num1 > $store_num[$k][$vv]){
                    //产品数量大于库存，先分一单
                    $num0=$store_num[$k][$vv];
                    $num1= $num1-$num0;
                    $order[$vv][$k]=$v;
                    $order[$vv][$k]['num']=$num0;
                    $order[$vv][$k]['pay']=bcmul($v['price_real'],$num0,2);
                    $order[$vv][$k]['weight']=bcmul($v['weight1'],$num0,2);
                    $order[$vv][$k]['size']=bcmul($v['size1'],$num0,2);
                    
                    continue;
                }else{
                     //数量足够
                    $order[$vv][$k]=$v;
                    $order[$vv][$k]['num']=$num1;
                    $order[$vv][$k]['pay']=bcmul($v['price_real'],$num1,2);
                    $order[$vv][$k]['weight']=bcmul($v['weight1'],$num1,2);
                    $order[$vv][$k]['size']=bcmul($v['size1'],$num1,2);
                    $num1=0;
                    break;
                }
            }
            //优先仓库货都不够，都标为暂时无货
            if($num1>0){
                $order[0][$k]=$v;
                $order[0][$k]['num']=$num1;
                $order[0][$k]['pay']=bcmul($v['price_real'],$num1,2);
                $order[0][$k]['weight']=bcmul($v['weight1'],$num1,2);
                $order[0][$k]['size']=bcmul($v['size1'],$num1,2);
               
            } 
        }
        
        return $order;
     }
     /* 订单草稿编辑,直接生效 */
     public function order_edit0($info,$data)
     {
         
         $content=[];
         //检测改变了哪些字段
         //所有订单都有,但只有虚拟订单会记录,真实订单在子订单里记录
         $edit_base=['dsc','store','freight','weight','size','pay_freight','real_freight',
             'goods_num','goods_money','discount_money','tax_money','other_money','order_amount',
             
         ];
         //收货信息，子订单可以单独修改，总订单修改后同步到子订单
         $edit_accept=['accept_name','mobile','phone','province','city','area','address','postcode',];
         
         //总订单信息系
         $edit_fid0=['company','udsc','paytype','invoice_type'];
         //组装需要判断的字段,普通订单未拆分的不比较总订单信息
         if($info['fid']==0){
             $fields=array_merge($edit_accept,$edit_fid0);
             if($info['is_real']==2 || count($data['oids'])>1){
                 $fields=array_merge($fields,$edit_base);
             }
         }else{
             $fields=$edit_accept;
         }
         //先比较总信息
         foreach($fields as $k=>$v){
             //如果原信息和$data信息相同就未改变，不为空就记录，？null测试
             if(isset($data[$v]) && $info[$v]!=$data[$v]){
                 $content[$v]=$data[$v];
             }
         }
         //主订单才有发票和付款信息
         if($info['fid']==0){
             //发票信息
             $edit_invoice=['title','ucode','point','invoice_money','tax_money','dsc'];
             //已有发票或写了发票抬头的要判断发票信息
             if(!empty($data['invoice_id']) || (!empty($data['invoice_title']) && !empty($data['invoice_type']))){
                 $data['invoice_id']=intval($data['invoice_id']);
                 if(empty($data['invoice_id'])){
                     $invoice=null;
                 }else{
                     $invoice=Db::name('order_invoice')->where('id',$data['invoice_id'])->find();
                 }
                 $content['invoice']=[];
                 foreach($edit_invoice as $k=>$v){
                     $field_tmp='invoice_'.$v;
                     //如果原信息和$data信息相同就未改变，不为空就记录，？null测试
                     if(isset($data[$field_tmp]) && $invoice[$v]!=$data[$field_tmp]){
                         $content['invoice'][$v]=$data[$field_tmp];
                     }
                 }
                 //没有改变清除
                 if(empty($content['invoice'])){
                     unset($content['invoice']);
                 }else{
                     $content['invoice']['id']= $data['invoice_id'];
                     $content['invoice']['oid']= $info['oid'];
                     $content['invoice']['oid_type']= 1;
                 }
             }
             
             //支付信息
             $edit_account=['bank1','name1','num1','location1','bank2','name2','num2','location2'];
             //已有付款账号信息和付款账户名
             if(!empty($data['account_id']) || !empty($data['account_name1']) ){
                 $data['account_id']=intval($data['account_id']);
                 if(empty($data['account_id'])){
                     $pay=null;
                 }else{
                     $pay=Db::name('order_pay')->where('id',$data['account_id'])->find();
                 }
                 $content['pay']=[];
                 foreach($edit_invoice as $k=>$v){
                     $field_tmp='account_'.$v;
                     //如果原信息和$data信息相同就未改变，不为空就记录，？null测试
                     if(isset($data[$field_tmp]) && $invoice[$v]!=$data[$field_tmp]){
                         $content['pay'][$v]=$data[$field_tmp];
                     }
                 }
                 //没有改变清除
                 if(empty($content['pay'])){
                     unset($content['pay']);
                 }else{
                     //记录id,review时检测
                     $content['pay']['id']= $data['account_id'];
                     $content['pay']['oid']= $info['oid'];
                     $content['pay']['oid_type']= 1;
                     
                 }
             }
         }
         
         //获取原订单和订单产品
         $where_goods=[];
         if($info['is_real']==1 ){
             $where_goods['oid']=['eq',$info['id']];
             $orders=[$info['id']=>$info];
             $order_ids=[$info['id']];
         }else{
             $fields='id,name,freight,store,weight,size,discount_money,goods_num,goods_money,pay_freight'.
                 ',real_freight,other_money,tax_money,order_amount,dsc';
             $orders=$this->where('fid',$info['id'])->column($fields);
             
             $order_ids=array_keys($orders);
             
             $where_goods['oid']=['in',$order_ids];
         }
         //全部订单产品
         $order_goods=Db::name('order_goods')
         ->where($where_goods)
         ->column('');
         //数据转化，按订单分组
         $infos=[];
         $goods_info=[];
         foreach($order_goods as $k=>$v){
             $infos[$v['oid']][$v['goods']]=$v;
             $goods_info[$v['goods']]=$v;
         }
         //子订单nums-{$kk}[{$key}],只有在主订单下才能拆分订单
         
         /*  $edit_base=['dsc','store','freight','weight','size','pay_freight','real_freight',
          'goods_num','goods_money','discount_money','tax_money','other_money','order_amount',
          ]; */
         $edit_goods=['num','pay','weight','size','dsc','price_real'];
         
         
         //多个要一个个比较,先比较是否存在
         foreach($data['oids'] as $k=>$void){
             if(in_array($void,$order_ids)){
                 //编辑订单信息
                 foreach($edit_base as $kk=>$vv){
                     $content['edit'][$void][$vv]=$data[$vv.'0'][$void];
                 }
                 //一个个比较产品，只有删除，没有新增
                 foreach ($infos[$void] as $kgoodsid=>$kv){
                     //data不存在就是没有该产品了,删除
                     if(!isset($data['nums-'.$void][$kgoodsid]) ){
                         $content['edit'][$void]['goods'][$kgoodsid]['del']=1;
                         continue;
                     }
                     //循环商品信息
                     foreach($edit_goods as $vv){
                         if($data[$vv.'s-'.$void][$kgoodsid] !=  $infos[$void][$kgoodsid][$vv]){
                             $content['edit'][$void]['goods'][$kgoodsid][$vv]=$data[$vv.'s-'.$void][$kgoodsid];
                         }
                     }
                 }
                 
             }else{
                 //不存在新增
                 $content['add'][$void]=[];
                 //添加订单信息
                 
                 foreach($edit_base as $kk=>$vv){
                     $content['add'][$void][$vv]=$data[$vv.'0'][$void];
                 }
                 foreach ($data['nums-'.$void] as $kgoodsid=>$kv){
                     //添加商品id
                     $content['add'][$void]['goods'][$kgoodsid]['goods']=$kgoodsid;
                     //循环商品信息
                     foreach($edit_goods as $vv){
                         $content['add'][$void]['goods'][$kgoodsid][$vv]=$data[$vv.'s-'.$void][$kgoodsid];
                     }
                     $content['add'][$void]['goods'][$kgoodsid]['goods_name']=$goods_info[$kgoodsid]['goods_name'];
                     $content['add'][$void]['goods'][$kgoodsid]['goods_code']=$goods_info[$kgoodsid]['goods_code'];
                     $content['add'][$void]['goods'][$kgoodsid]['goods_uname']=$goods_info[$kgoodsid]['goods_uname'];
                     $content['add'][$void]['goods'][$kgoodsid]['goods_ucate']=$goods_info[$kgoodsid]['goods_ucate'];
                     $content['add'][$void]['goods'][$kgoodsid]['goods_pic']=$goods_info[$kgoodsid]['goods_pic'];
                     $content['add'][$void]['goods'][$kgoodsid]['price_in']=$goods_info[$kgoodsid]['price_in'];
                     $content['add'][$void]['goods'][$kgoodsid]['price_sale']=$goods_info[$kgoodsid]['price_sale'];
                     $content['add'][$void]['goods'][$kgoodsid]['weight1']=$goods_info[$kgoodsid]['weight1'];
                     $content['add'][$void]['goods'][$kgoodsid]['size1']=$goods_info[$kgoodsid]['size1'];
                     
                 }
             }
         }
         
         return $content;
         
     }
     /* 订单编辑 */
     public function order_edit($info,$data,$is_do=0)
     {
          
         $content=[];
         //检测改变了哪些字段 
         //所有订单都有,但只有虚拟订单会记录,真实订单在子订单里记录
         $edit_base=['dsc','store','freight','weight','size','pay_freight','real_freight',
             'goods_num','goods_money','discount_money','tax_money','other_money','order_amount',
             
         ];
         //收货信息，子订单可以单独修改，总订单修改后同步到子订单
         $edit_accept=['accept_name','mobile','phone','province','city','area','address','postcode',];
          
         //总订单信息系
         $edit_fid0=['company','udsc','paytype','invoice_type'];
         //组装需要判断的字段,普通订单未拆分的不比较总订单信息
         if($info['fid']==0){
             $fields=array_merge($edit_accept,$edit_fid0);
             if($info['is_real']==2 || count($data['oids'])>1){
                 $fields=array_merge($fields,$edit_base);
             } 
         }else{
             $fields=$edit_accept;
         }
         //先比较总信息
         foreach($fields as $k=>$v){
             //如果原信息和$data信息相同就未改变，不为空就记录，？null测试
             if(isset($data[$v]) && $info[$v]!=$data[$v]){
                 $content[$v]=$data[$v];
             }
         }
         //主订单才有发票和付款信息
         if($info['fid']==0){
             //发票信息
             $edit_invoice=['title','ucode','point','invoice_money','tax_money','dsc'];
             //已有发票或写了发票抬头的要判断发票信息
             if(!empty($data['invoice_id']) || (!empty($data['invoice_title']) && !empty($data['invoice_type']))){
                 $data['invoice_id']=intval($data['invoice_id']);
                 if(empty($data['invoice_id'])){
                     $invoice=null;
                 }else{
                     $invoice=Db::name('order_invoice')->where('id',$data['invoice_id'])->find();
                 }
                 $content['invoice']=[];
                 foreach($edit_invoice as $k=>$v){
                     $field_tmp='invoice_'.$v;
                     //如果原信息和$data信息相同就未改变，不为空就记录，？null测试
                     if(isset($data[$field_tmp]) && $invoice[$v]!=$data[$field_tmp]){
                         $content['invoice'][$v]=$data[$field_tmp];
                     }
                 }
                 //没有改变清除
                 if(empty($content['invoice'])){
                     unset($content['invoice']);
                 }else{
                     $content['invoice']['id']= $data['invoice_id'];
                     $content['invoice']['oid']= $info['oid'];
                     $content['invoice']['oid_type']= 1;
                 }
             }
             
             //支付信息
             $edit_account=['bank1','name1','num1','location1','bank2','name2','num2','location2'];
             //已有付款账号信息和付款账户名
             if(!empty($data['account_id']) || !empty($data['account_name1']) ){
                 $data['account_id']=intval($data['account_id']);
                 if(empty($data['account_id'])){
                     $pay=null;
                 }else{
                     $pay=Db::name('order_pay')->where('id',$data['account_id'])->find();
                 }
                 $content['pay']=[];
                 foreach($edit_invoice as $k=>$v){
                     $field_tmp='account_'.$v;
                     //如果原信息和$data信息相同就未改变，不为空就记录，？null测试
                     if(isset($data[$field_tmp]) && $invoice[$v]!=$data[$field_tmp]){
                         $content['pay'][$v]=$data[$field_tmp];
                     }
                 }
                 //没有改变清除
                 if(empty($content['pay'])){
                     unset($content['pay']);
                 }else{
                     //记录id,review时检测
                     $content['pay']['id']= $data['account_id'];
                     $content['pay']['oid']= $info['oid'];
                     $content['pay']['oid_type']= 1;
                     
                 }
             }
         }
         
         //获取原订单和订单产品
         $where_goods=[];
         if($info['is_real']==1 ){
             $where_goods['oid']=['eq',$info['id']];
             $orders=[$info['id']=>$info];
             $order_ids=[$info['id']];
         }else{
             $fields='id,name,freight,store,weight,size,discount_money,goods_num,goods_money,pay_freight'.
                 ',real_freight,other_money,tax_money,order_amount,dsc';
             $orders=$this->where('fid',$info['id'])->column($fields);
             
             $order_ids=array_keys($orders);
             
             $where_goods['oid']=['in',$order_ids];
         }
         //全部订单产品
         $order_goods=Db::name('order_goods')
         ->where($where_goods)
         ->column('');
         //数据转化，按订单分组
         $infos=[];
         $goods_info=[];
         foreach($order_goods as $k=>$v){
             $infos[$v['oid']][$v['goods']]=$v;
             $goods_info[$v['goods']]=$v;
         }
         //子订单nums-{$kk}[{$key}],只有在主订单下才能拆分订单
         
         /*  $edit_base=['dsc','store','freight','weight','size','pay_freight','real_freight',
          'goods_num','goods_money','discount_money','tax_money','other_money','order_amount',
          ]; */
         $edit_goods=['num','pay','weight','size','dsc','price_real'];
        
          
         //多个要一个个比较,先比较是否存在
         foreach($data['oids'] as $k=>$void){
             if(in_array($void,$order_ids)){
                 //编辑订单信息
                 foreach($edit_base as $kk=>$vv){
                     $content['edit'][$void][$vv]=$data[$vv.'0'][$void];
                 }
                 //一个个比较产品，只有删除，没有新增
                 foreach ($infos[$void] as $kgoodsid=>$kv){ 
                     //data不存在就是没有该产品了,删除
                     if(!isset($data['nums-'.$void][$kgoodsid]) ){
                         $content['edit'][$void]['goods'][$kgoodsid]['del']=1;
                         continue;
                     }
                     //循环商品信息
                     foreach($edit_goods as $vv){
                         if($data[$vv.'s-'.$void][$kgoodsid] !=  $infos[$void][$kgoodsid][$vv]){
                             $content['edit'][$void]['goods'][$kgoodsid][$vv]=$data[$vv.'s-'.$void][$kgoodsid];
                         } 
                     } 
                 }
                 
             }else{
                 //不存在新增
                 $content['add'][$void]=[];
                 //添加订单信息
               
                 foreach($edit_base as $kk=>$vv){
                     $content['add'][$void][$vv]=$data[$vv.'0'][$void];
                 }
                 foreach ($data['nums-'.$void] as $kgoodsid=>$kv){
                     //添加商品id
                     $content['add'][$void]['goods'][$kgoodsid]['goods']=$kgoodsid;
                     //循环商品信息
                     foreach($edit_goods as $vv){
                         $content['add'][$void]['goods'][$kgoodsid][$vv]=$data[$vv.'s-'.$void][$kgoodsid]; 
                     }
                     $content['add'][$void]['goods'][$kgoodsid]['goods_name']=$goods_info[$kgoodsid]['goods_name']; 
                     $content['add'][$void]['goods'][$kgoodsid]['goods_code']=$goods_info[$kgoodsid]['goods_code']; 
                     $content['add'][$void]['goods'][$kgoodsid]['goods_uname']=$goods_info[$kgoodsid]['goods_uname']; 
                     $content['add'][$void]['goods'][$kgoodsid]['goods_ucate']=$goods_info[$kgoodsid]['goods_ucate']; 
                     $content['add'][$void]['goods'][$kgoodsid]['goods_pic']=$goods_info[$kgoodsid]['goods_pic']; 
                     $content['add'][$void]['goods'][$kgoodsid]['price_in']=$goods_info[$kgoodsid]['price_in']; 
                     $content['add'][$void]['goods'][$kgoodsid]['price_sale']=$goods_info[$kgoodsid]['price_sale']; 
                     $content['add'][$void]['goods'][$kgoodsid]['weight1']=$goods_info[$kgoodsid]['weight1']; 
                     $content['add'][$void]['goods'][$kgoodsid]['size1']=$goods_info[$kgoodsid]['size1']; 
                     
                 } 
             }
         }
         if($is_do==1){
             $row=$this->order_edit_review($info,$content);
             return $row;
         }else{
             return $content;
         }
         
     }
     /* 审核订单编辑 */
     public function order_edit_review($order,$change)
     {
         $time=time();
         $m_ogoods=Db::name('order_goods');
         $edit_base=['dsc','store','freight','weight','size','pay_freight','real_freight',
             'goods_num','goods_money','discount_money','tax_money','other_money','order_amount',
             
         ];
         //收货信息，子订单可以单独修改，总订单修改后同步到子订单
         $edit_accept=['accept_name','mobile','phone','province','city','area','address','postcode',];
         
         //总订单信息系
         $edit_fid0=['company','udsc','paytype','invoice_type'];
         
         //依次处理change的信息，处理后unset
         //先处理字订单产品，再处理订单信息
         if(isset($change['edit'])){
             foreach($change['edit'] as $koid=>$vo){
                 $where=['oid'=>$koid];
                 if(isset($vo['goods'])){
                     foreach($vo['goods'] as $kgoods_id=>$vgoods){
                         $where['goods']=$kgoods_id;
                         if(isset($vgoods['del'])){
                             $m_ogoods->where($where)->delete();
                             continue;
                         }
                         $m_ogoods->where($where)->update($vgoods);
                     }
                     unset($vo['goods']);
                 }
                 $where=['id'=>$koid];
                 $this->where($where)->update($vo);
             }
             unset($change['edit']);
         }
         //新增订单信息只能先保存产品信息，新增订单，才有单号给产品保存
         if(isset($change['add'])){
             //有新增一定是虚拟主单号了,拆分单号,删除原产品
             if($order['is_real']==1){
                 $change['is_real']=2;
                 $change['status']=10;
                 $m_ogoods->where('oid',$order['id'])->delete();
             }
            
             //得到子订单的序号
             $tmp=$this->where('fid',$order['id'])->count();
             
             if(empty($tmp)){
                 $tmp=0;
             }
             $goods_adds=[];
             $goods_ids=[];
             foreach($change['add'] as $koid=>$vo){ 
                 //订单信息,状态待定,跟随主订单
                 $tmp++; 
                 $data_order=[
                     'order_type'=>$order['order_type'],
                     'aid'=>$order['aid'],
                     'shop'=>$order['shop'],
                     'company'=>$order['company'],
                     'uid'=>$order['uid'], 
                     'udsc'=>$order['udsc'], 
                     'create_time'=>$time,
                     'time'=>$time,
                     'sort'=>$order['sort'],
                     'status'=>$order['status'],
                     'fid'=>$order['id'],
                     'name'=>$order['name'].'_'.$tmp, 
                 ];
                 //收货人信息
                 foreach ($edit_accept as $v){
                     $data_order[$v]=(isset($change[$v]))?$change[$v]:$order[$v]; 
                 }
                 //总单信息
                 foreach ($edit_fid0 as $v){
                     $data_order[$v]=(isset($change[$v]))?$change[$v]:$order[$v];
                 }
                 //子单信息
                 foreach ($edit_base as $v){
                     $data_order[$v]=$vo[$v];
                 }
                 $tmp_oid=$this->insertGetId($data_order);
                 //产品新增
                 if(isset($vo['goods'])){ 
                     foreach($vo['goods'] as $kgoods_id=>$vgoods){
                         $vgoods['oid']=$tmp_oid;
                         $vgoods['weight1']=bcdiv($vgoods['weight'],$vgoods['num'],2);
                         $vgoods['size1']=bcdiv($vgoods['size'],$vgoods['num'],2);
                         $vgoods['weight1']=($vgoods['weight1']<=0.01)?0.01:$vgoods['weight1'];
                         $vgoods['size1']=($vgoods['size1']<=0.01)?0.01:$vgoods['size1'];
                         $goods_adds[]=$vgoods;  
                     } 
                 } 
             }
             if(!empty($goods_adds)){
                 //最后统一新增产品  
                 $m_ogoods->insertAll($goods_adds);
             }
            
             unset($change['add']);
         }
         //支付账号信息
         if(isset($change['pay'])){
             if(empty($change['pay']['id'])){
                 Db::name('order_pay')->insert($change['pay']);
             }else{
                 Db::name('order_pay')->where('id',$change['pay']['id'])->update($change['pay']);
             }
             unset($change['pay']);
         }
         //支付账号信息
         if(isset($change['invoice'])){
             if(empty($change['invoice']['id'])){
                 Db::name('order_invoice')->insert($change['invoice']);
             }else{
                 Db::name('order_invoice')->where('id',$change['invoice']['id'])->update($change['invoice']);
             }
         }
         $update_info=['time'=>$time];
        
         foreach($change as $k=>$v){
             $update_info[$k]=$v;
         }
         $row=$this->where('id',$order['id'])->update($update_info);
         //有子订单,同步
         if($order['is_real']==2 || isset($change['add']) ){
             foreach($update_info as $k=>$v){
                  if(in_array($k,$edit_base)){
                      unset($update_info[$k]);
                  }
              }
              $this->where('fid',$order['id'])->update($update_info); 
         }
          
          return $row;
         
     }
     /* 订单是否可以编辑 */
     public function order_edit_auth($order,$admin){
         if($order['status']==1 && $order['aid']!=$admin['id']){
             return '不能修改他人的未提交订单的';
         }
         //是否有待审核
         $where=[
             'pid'=>['eq',$order['id']],
             'table'=>['eq','order'],
             'rstatus'=>['eq',1],
         ];
         $aids=Db::name('edit')
         ->where($where)
         ->value('aid');
         if(!empty($aids) && $admin['id']!=$aids){
             return '订单有修改，需等待审核';
         }
         //创建人能修改订单
         if($admin['id']==$order['aid'] ){
             return 1;
         }
         //创建人的经理或是总部门经理
         if($admin['job']==1){
             if($admin['department']==1){
                 return 1;
             }else{
                 $department=Db::name('user')->where('id',$order['aid'])->value('department');
                 //创建人不存在或是部门经理
                 if(empty($department) || $department==$admin['department']){
                     return 1;
                 }
             }
         }
         //在order_aid表中可以
         $where=[
             'oid'=>$order['id'],
             'aid'=>$admin['id'],
         ];
         $tmp=Db::name('order_aid')->where($where)->find();
         if(!empty($tmp)){
             return 1;
         }
         return '无权限查看该定订单';
     }
     /* 订单排序 */
     public function order_sort($order,$update){
         //         sort专门排序，待发货10，管理员有改动8，员工有改动7，待付款4，，待确认货款5，退货中3，待提交2，其他0
         //	3	订单总表中，“已付款等待发货的订单”显示红色排最上，
         // 订单数据有改动的显示“蓝色”或者“灰色”排第二，
         // “已下单，等待买家付款”黄色，排第三，
        /*  'pay_status' =>
         array (
             1 => '未付款',
             2 => '付款待确认',
             3 => '已确认付款',
             4 => '退款中',
             5 => '退款完成',
         ),
         'freight_status' =>
         array (
             1 => '未发货',
             2 => '待发货',
             3 => '已申请发货',
             4 => '已发货',
         ),
         'order_status' =>
         array (
             1=>'未提交',
             2=>'待审核',
             10 => '待付款',
             20 => '待发货',
             22 => '已准备发货',
             24 => '已发货',
             26 => '已收货',
             30 => '订单完成',
             40 => '退货中',
             42 => '退货完成',
             80 => '取消订单',
             81 => '废弃订单',
         ), */
         //pay_status
         //是否有待审核
         $where=[
             'edit.pid'=>['eq',$order['id']],
             'edit.table'=>['eq','order'],
             'edit.rstatus'=>['eq',1],
         ];
         $aids=Db::name('edit')
         ->alias('edit')
         ->join('cmf_user user','user.id=edit.aid')
         ->where($where)
         ->column('user.job,edit.aid');
         //管理员有改动8，员工有改动7，
         if(isset($aids[1])){
             return 8;
         }elseif(isset($aids[2])){
             return 7;
         }
         
         if(empty($update['status'])){
             $status=$order['status'];
         }else{
             $status=$update['status'];
         }
         $order['status']=isset($update['status'])?$update['status']:$order['status'];
         $order['pay_status']=isset($update['pay_status'])?$update['pay_status']:$order['pay_status'];
         $order['pay_type']=isset($update['pay_type'])?$update['pay_type']:$order['pay_type'];
         $order['status']=isset($update['status'])?$update['status']:$order['status'];
         switch ($status){
             case 2:
                 return 10;
             case 1:
                 if($order['pay_status']){
                     
                 }
         }
         
       /*   if($v['pay_type']==1 && $v['paystate']==0 && $v['order_type']==3){
             //待确认货款5，
             $v['sort']=5;
             $v['status']=1;
         }elseif($v['distribution_status']==0){
             if($v['pay_status']==1 || ($v['pay_type']==2 || $v['pay_type']==10)){
                 //待发货
                 $v['sort']=10;
                 $v['status']=2;
             }else{
                 //待付款4
                 $v['sort']=4;
                 $v['status']=1;
             }
         }  
           */
     }
}
