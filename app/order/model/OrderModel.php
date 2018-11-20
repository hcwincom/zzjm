<?php
 
namespace app\order\model;

use think\Model;
use think\Db;
use app\store\model\StoreGoodsModel;
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
     
     /* 订单编辑 */
     public function order_edit($info,$data,$is_do=0)
     {
          
         $content=[];
         //检测改变了哪些字段 
         //所有订单都有,但只有虚拟订单会记录,真实订单在子订单里记录
         $edit_base=['dsc','store','freight','weight','size','pay_freight','real_freight',
             'goods_num','goods_money','discount_money','tax_money','other_money','order_amount','express_no'
             
         ];
         //收货信息，子订单可以单独修改，总订单修改后同步到子订单
         $edit_accept=['accept_name','mobile','phone','province','city','area','address','postcode',];
          
         //总订单信息系
         $edit_fid0=['company','udsc','paytype','pay_type','invoice_type','order_type'];
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
                     $content['invoice']['oid']= $info['id'];
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
                 foreach($edit_account as $k=>$v){
                     $field_tmp='account_'.$v;
                     //如果原信息和$data信息相同就未改变，不为空就记录，？null测试
                     if(isset($data[$field_tmp]) && $pay[$v]!=$data[$field_tmp]){
                         $content['pay'][$v]=$data[$field_tmp];
                     }
                 }
                 //没有改变清除
                 if(empty($content['pay'])){
                     unset($content['pay']);
                 }else{
                     //记录id,review时检测
                     $content['pay']['id']= $data['account_id'];
                     $content['pay']['oid']= $info['id'];
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
                     if($orders[$void][$vv]!=$data[$vv.'0'][$void]){
                         $content['edit'][$void][$vv]=$data[$vv.'0'][$void];
                     }
                     
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
             if($info['status']==1 && $data['status']==2){
                 $content['status']=2;
             }
             $row=$this->order_edit_review($info,$content);
             return $row;
         }else{
             return $content;
         }
         
     }
     /* 审核订单编辑 */
     public function order_edit_review($order,$change)
     {
         //获取订单状态信息
         if($order['is_real']==1 ){ 
             $orders=[$order['id']=>$order]; 
         }else{ 
             $orders=$this->where('fid',$order['id'])->column('id,is_real,pay_status,status,sort');
             $orders[$order['id']]=$order; 
         }
         $time=time();
         $m_ogoods=Db::name('order_goods');
         $edit_base=['dsc','store','freight','weight','size','pay_freight','real_freight',
             'goods_num','goods_money','discount_money','tax_money','other_money','order_amount','express_no'
             
         ];
         //收货信息，状态信息，子订单可以单独修改，总订单修改后同步到子订单
         $edit_accept=['accept_name','mobile','phone','province','city','area','address','postcode','status','pay_status'];
         
         //总订单信息系，子订单不能单独修改，总订单修改后同步到子订单
         $edit_fid0=['company','udsc','paytype','pay_type','invoice_type','order_type'];
         //记录有订单拆分，需要废弃原出入库的订单id,重新添加
         $instore_oids=[];
         //新添加订单号
         $instore_add_oids=[];
         //依次处理change的信息，处理后unset
         //先处理字订单产品，再处理订单信息
         if(isset($change['edit'])){
             foreach($change['edit'] as $koid=>$vo){
                 $where=['oid'=>$koid];
                 if(isset($vo['goods'])){
                     foreach($vo['goods'] as $kgoods_id=>$vgoods){
                         $where['goods']=$kgoods_id;
                        
                         if(isset($vgoods['del'])){
                             $instore_oids[]=$koid;
                             $m_ogoods->where($where)->delete();
                             continue;
                         }
                         if(isset($vgoods['num'])){
                             $instore_oids[]=$koid;
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
                 $instore_add_oids[]=$tmp_oid;
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
             unset($change['invoice']);
         }
         $update_info=['time'=>$time];
        
         foreach($change as $k=>$v){
             $update_info[$k]=$v;
         }
         
         $this->where('id',$order['id'])->update($update_info);
         //有子订单,同步
         if($order['is_real']==2 || isset($change['add']) ){
             foreach($update_info as $k=>$v){
                  if(in_array($k,$edit_base)){
                      unset($update_info[$k]);
                  }
              }
              if(isset($change['is_real'])){
                  unset($update_info['is_real']);
              }
              if(!empty($update_info)){
                  $this->where('fid',$order['id'])->update($update_info); 
              }
             
         }
         
         
          
         //检查库存,删除旧出库，添加新出库
         if(!empty($instore_oids)){
             //有产品数量变化的  
             $instore_oids=array_unique($instore_oids);
             foreach($instore_oids as $v){
                 $res=$this->order_storein5($v);
                 if($res!==1){
                     return $res;
                 }
             }
             //新添加订单号
             $instore_add_oids=array_merge($instore_add_oids,$instore_oids);
             foreach($instore_add_oids as $v){
                 $res=$this->order_storein0($v);
                 if($res!==1){
                     return $res;
                 }
             } 
         } 
         return 1; 
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
             'type'=>1,5
         ];
         $tmp=Db::name('order_aid')->where($where)->find();
         if(!empty($tmp)){
             return 1;
         }
         return '无权限查看该定订单';
     }
     /* 订单排序 */
     public function order_sort($id){
         //   sort专门排序，待发货10，仓库发货9，管理员有改动8，员工有改动7，待付款4，待确认货款5，退货退款中3，未提交2，其他0 
         //pay_status
         //是否有待审核
         $where=[
             'edit.pid'=>['eq',$id],
             'edit.table'=>['eq','order'],
             'edit.rstatus'=>['eq',1],
         ];
         $aids=Db::name('edit')
         ->alias('edit')
         ->join('cmf_user user','user.id=edit.aid')
         ->where($where)
         ->column('user.job,edit.aid');
         $sort=0;
         //管理员有改动8，员工有改动7，
         if(isset($aids[1])){
             $sort=8;
         }elseif(isset($aids[2])){
             $sort=7;
         }else{
             $order=$this->where('id',$id)->find();
             
             //   sort专门排序，待发货10，准备发货9，管理员有改动8，员工有改动7，待付款4，待确认货款5，退货退款中3，未提交2，其他0
             switch ($order['status']){
                 case 20:
                     $sort=10;
                     break;
                 case 22:
                     $sort=9;
                     break;
                 case 10:
                     switch ($order['pay_status']){
                         case 1:
                             $sort=4;
                             break;
                         case 2:
                             $sort=5;
                             break;
                         case 4:
                             $sort=3;
                             break;
                         default: 
                             break;
                     }  
                     break;
                 case 40:
                     $sort=3;
                     break;
                 default:
                     break;
             }
         }
         $this->where('id',$id)->setField('sort',$sort);
           
     }
     /* 订单产品数量检查 */
     public function order_store($id){
         //在store_goods表中num1数值减少
         $order=$this->where('id',$id)->find();
         if($order['is_real']==2){
             return '已拆分订单请分开发货';
         }
         $goods_order=Db::name('order_goods')->where('oid',$id)->column('goods,goods_name,num');
         if(empty($goods_order)){
             return 1;
         }
         $goods_ids=array_keys($goods_order);
         $where=[
             'store'=>['eq',$order['store']],
             'goods'=>['in',$goods_ids]
         ];
         $goods_store=Db::name('store_goods')->where($where)->column('goods,num');
         foreach($goods_order as $k=>$v){
             if(!isset($goods_store[$k]) || $goods_store[$k]<$v['num']){
                 return $v['goods_name'].'库存不足';
             }
         }
         return 1;
     }
     /* 订单确认后，产品出库未提交 */
     public function order_storein0($id){
         //在store_goods表中num1数值减少
         $order=$this->where('id',$id)->find();
         $order=$order->data;
         if($order['status']<10 || $order['status']>20){
             return '订单状态错误';
         }
         //订单产品
         $where_goods=[];
         if($order['is_real']==1){
             $where_goods['oid']=['eq',$order['id']]; 
             $orders=[$order['id']=>['id'=>$order['id'],'name'=>$order['name'],'store'=>$order['store']]];
         }else{ 
             $orders=$this->where('fid',$order['id'])->column('id,name,store'); 
             $order_ids=array_keys($orders);
             $where_goods['oid']=['in',$order_ids];
         }
         //全部订单产品
         $goods_order=Db::name('order_goods')
         ->where($where_goods)
         ->column('id,goods,num,oid');
           
         if(empty($goods_order)){
             return 1;
         }
         $goods_ids=[];
         $m_store_goods=new StoreGoodsModel();
         $time=time();
         $aid=session('ADMIN_ID');
         //一个个地出库
         foreach($goods_order as $k=>$v){
             if($orders[$v['oid']]['store']==0){
                 continue;
             }
             $data=[
                 'shop'=>$order['shop'],
                 'store'=>$orders[$v['oid']]['store'],
                 'goods'=>$v['goods'],
                 'num'=>(0-$v['num']),
                 'atime'=>$time,
                 'aid'=>$aid,
                 'adsc'=>'客户下单出库',
                 'rstatus'=>4,
                 'type'=>10,
                 'about'=>$v['oid'],
                 'about_name'=>$orders[$v['oid']]['name'],
             ];
             $res=$m_store_goods->instore0($data);
             if($res!==1){
                 return $res;
             }
         }
         
         return 1;
     }
     /**
      *  订单准备发货后，出库记录可审核 */
     public function order_storein1($id){
         
         $order=$this->where('id',$id)->find();
         $order=$order->data;
         if($order['status']!=22){
             return '订单状态错误';
         }
         if($order['is_real']!=1){
             return '已拆分订单请在子订单页面发货';
         }
          
         //出入库记录要变为待审核
         $where=[
             'type'=>10,
             'about'=>$id,
             'rstatus'=>4,
         ];
         $update=[ 
             'rstatus'=>1,
         ];
         Db::name('store_in')->where($where)->update($update);
         return 1;
     }
     /**
      *  订单确认发货要检查出库记录是否都已审核 */
     public function order_storein_check($id){
        
         $order=$this->where('id',$id)->find();
         $order=$order->data;
         
         if($order['is_real']!=1){
             return '已拆分订单请在子订单页面发货';
         }
         
         $m_store_in=Db::name('store_in');
         $where=[
             'type'=>10,
             'about'=>$id,
             'rstatus'=>3,
         ];
          
         $goods_store=$m_store_in->where($where)->order('goods')->column('goods,num');
         $goods_order=Db::name('order_goods')->where('oid',$id)->order('goods')->column('goods,num');
         foreach($goods_order as $k=>$v){
             if(!isset($goods_store[$k]) || $goods_store[$k] != $v){
                 return '出库不完全';
             }
         }
         
         return 1;
         
     }
     /* 订单改变后废弃原出库记录 */
     public function order_storein5($id,$dsc='订单变化，废弃原出入库'){
        
         $order=$this->where('id',$id)->find();
         $order=$order->data;
         
         //订单产品
         $where_about=['type'=>10];
         
         if($order['is_real']==1){
             $where_about['about']=['eq',$order['id']]; 
         }else{
             $order_ids=$this->where('fid',$order['id'])->column('id'); 
             $where_about['about']=['in',$order_ids];
         }
         //获取所有出入库记录
         $instores=Db::name('store_in')->where($where_about)->column('id,store,shop,goods,num');
         if(empty($instores)){
             return 1;
         }
         $in_ids=array_keys($instores);
         $m_store_goods=new StoreGoodsModel();
         //一个个地废弃
         foreach($instores as $k=>$v){
             $res=$m_store_goods->instore5($v);
             if($res!==1){
                 return $res;
             }
         } 
         $update_info=[
             'rstatus'=>5,
             'time'=>time(),
             'rdsc'=>$dsc,
             'rid'=>session('ADMIN_ID'),
         ];
         Db::name('store_in')->where('id','in',$in_ids)->update($update_info);
         return 1;
     }
     
     /* 检查产品的更新操作是否合法 */
     public function is_option($order,$change){
         
         
         return 1;
     }
     /**
      * 得到订单的产品详情,返回字订单和产品详情
      * */
     public function order_goods($info,$aid){
         //订单产品
         $where_goods=[];
         if($info['is_real']==1){
             $where_goods['oid']=['eq',$info['id']];
             $orders=[$info['id']=>$info];
         }else{
             $fields='id,name,freight,store,weight,size,discount_money,goods_num,goods_money,pay_freight'.
                 ',real_freight,other_money,tax_money,order_amount,dsc,express_no,status,pay_status';
             $orders=$this->where('fid',$info['id'])->column($fields);
             
             $order_ids=array_keys($orders);
             $where_goods['oid']=['in',$order_ids];
         }
         //全部订单产品
         $order_goods=Db::name('order_goods')
         ->where($where_goods)
         ->column('');
         
         //检查用户权限
         $authObj = new \cmf\lib\Auth();
         $name       = strtolower('goods/AdminGoodsauth/price_in_get');
         $is_auth=$authObj->check($aid, $name);
         //数据转化，按订单分组
         $infos=[];
         $goods_id=[];
         $goods=[];
         foreach($order_goods as $k=>$v){
             $goods_id[$v['goods']]=$v['goods'];
             $goods[$v['goods']]=[];
             if($is_auth==false){
                 $v['price_in']='--';
             }
             $v['weight1']=bcdiv($v['weight'],$v['num'],2);
             $v['size1']=bcdiv($v['size'],$v['num'],2);
             
             $infos[$v['oid']][$v['goods']]=$v;
         }
         
         //获取产品图片
         $where=[
             'pid'=>['in',$goods_id],
             'type'=>['eq',1],
         ];
         $pics=Db::name('goods_file')->where($where)->column('id,pid,file');
         $path=cmf_get_image_url('');
         foreach($pics as $k=>$v){
             $goods[$v['pid']]['pics'][]=[
                 'file1'=>$v['file'].'1.jpg',
                 'file3'=>$v['file'].'3.jpg',
             ];
         }
         
         //获取所有库存
         $where=[
             'goods'=>['in',$goods_id],
             'shop'=>['eq',$info['shop']],
         ];
         $list=Db::name('store_goods')->where($where)->column('id,store,goods,num,num1');
         
         //循环得到数据
         foreach($list as $k=>$v){
             $goods[$v['goods']]['nums'][$v['store']]=[
                 'num'=>$v['num'],
                 'num1'=>$v['num1'],
             ];
         } 
         return ['orders'=>$orders,'goods'=>$goods,'infos'=>$infos]; 
     }
}
