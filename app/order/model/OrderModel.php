<?php
 
namespace app\order\model;

use think\Model;
use think\Db;
use app\store\model\StoreGoodsModel;
use app\money\model\OrdersInvoiceModel;
use app\money\model\OrdersPayModel;
class OrderModel extends Model
{
    /**
     * 获取单条数据，主要是为了返回data，去除对象影响
     * @param $where 查询条件
     * @param $field 字段
     * @param data
     */
    public function get_one($where,$field='*'){
         
        $order=$this->field($field)->where($where)->find();
        return $order->data;
    }
    /**
     * 下单时为仓库排序，按首重价格计算
     * @param $city收货地
     * @param $shop店铺
     * @param $store已选择的仓库,要排除
     */ 
    public function store_sort($city,$shop,$store=0){
        
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
            if(!in_array($stores[$v],$sort) && $stores[$v]!=$store){
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
        //获取优先的仓库,已去除默认的
        $sort=$this->store_sort($city,$shop,$store);
        if(empty($sort)){ 
            $sort=[];
        } 
        //获取可用的仓库，优先仓库+默认选择的
        $stores=$sort;
        $stores[]=$store;
         //获取所有库存
         $goods_id=array_keys($goods);
         $where=[
             'goods'=>['in',$goods_id],
             'shop'=>['eq',$shop],
             'store'=>['in',$stores],
         ];
         $list=Db::name('store_goods')->where($where)->column('id,store,goods,num');
         //循环得到数据
         $store_num=[];
         foreach($list as $k=>$v){
             $store_num[$v['goods']][$v['store']]=$v['num'];
         }
        
         
         //最终order
         $order=[];
         //
         $num0=0;
         $num1=0;
        
         //如果默认库存不足就按优先仓库发货,重新计算费用和重量体积
         foreach($goods as $k=>$v){
             $ave_discount=bcdiv($v['pay_discount'],$v['num'],2);
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
                $order[$store][$k]['pay_discount']=bcmul($ave_discount,$num0,2);
                $order[$store][$k]['pay']=bcsub($v['price_real']*$num0, $order[$store][$k]['pay_discount'],2);
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
                    $order[$vv][$k]['pay_discount']=bcmul($ave_discount,$num0,2);
                    $order[$vv][$k]['pay']=bcsub($v['price_real']*$num0, $order[$vv][$k]['pay_discount'],2);
                    $order[$vv][$k]['weight']=bcmul($v['weight1'],$num0,2);
                    $order[$vv][$k]['size']=bcmul($v['size1'],$num0,2);
                    
                    continue;
                }else{
                     //数量足够
                    $order[$vv][$k]=$v;
                    $order[$vv][$k]['num']=$num1;
                    $order[$vv][$k]['pay_discount']=bcmul($ave_discount,$num0,2);
                    $order[$vv][$k]['pay']=bcsub($v['price_real']*$num0, $order[$vv][$k]['pay_discount'],2);
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
                $order[0][$k]['pay_discount']=bcmul($ave_discount,$num0,2);
                $order[0][$k]['pay']=bcsub($v['price_real']*$num0, $order[0][$k]['pay_discount'],2);
               
                $order[0][$k]['weight']=bcmul($v['weight1'],$num1,2);
                $order[0][$k]['size']=bcmul($v['size1'],$num1,2);
               
            } 
        }
        
        return $order;
     }
     
     /* 订单是否可以编辑 */
     public function order_edit_auth($order,$admin){
         if($order['status']==1 && $order['aid']!=$admin['id']){
             //超管可以修改
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
             'type'=>1
         ];
         $tmp=Db::name('order_aid')->where($where)->find();
         if(!empty($tmp)){
             return 1;
         }
         return '无权限查看该定订单';
     }
     /* 订单排序 */
     public function order_sort($id){
          $sort=0;  
          // sort排序，线下订单待发货10，准备发货9，线下待确认货款8，线下待付款7，待确认和待提交6，淘宝待发货5，淘宝准备发货4，淘宝待确认货款3，淘宝待付款2，淘宝错误1，其他按时间顺序排
        
          $order=$this->where('id',$id)->find();
          $order=$order->data;
         /*  'order_status' =>
          array (
              1 => '未提交',
              2 => '提交待确认',
              10 => '待付款',
              20 => '待发货',
              22 => '已准备发货',
              24 => '已发货',
              26 => '已收货',
              30 => '订单完成',
              70 => '订单关闭',
              80 => '已取消',
              81 => '已废弃',
          ), */
          //线下发货就收获
          $status=$order['status']; 
          if($order['order_type']==1 && $status==24){
              $status=26;
          }
          if($order['pay_status']==3 && $status==26){
              $status=30;
          }
          switch ($status){
             case 20:
                 $sort=10; 
                 break;
             case 22:
                 $sort=9;
                 break;  
             case 10:
                 switch ($order['pay_status']){
                     case 1:
                         $sort=8;
                         break;
                     case 2:
                         $sort=7;
                         break; 
                     default: 
                         break;
                 }  
                 break;  
             case 2: 
             case 1:
                 $sort=6;
                 break; 
             default:
                 $sort=0;
                 break;
         }
         //淘宝订单落后
         if($order['order_type']==3 && $sort >5){
             $sort=$sort-5;
         }
         //有状态和排序变化就更新
         $update=[];
         if($status!=$order['status']){ 
             $update['status']=$status;
         }
         if($order['sort']!=$sort){
             $update['sort']=$sort;
         }
         if(!empty($update)){
             $this->where('id',$order['id'])->update($update);
         }
        //检查是否更新父级,且订单完成
        if($order['fid']>0 && $order['status']>=30){
            //如果子订单全部完成，则父级完成
            $where_child=[
                'fid'=>$order['fid'],
                'status'=>['lt',30],
            ];
            $tmp=$this->where($where_child)->find();
            if(empty($tmp)){
                $update=[
                    'sort'=>0,
                    'time'=>time(),
                    'status'=>30,
                ];
                $where=[
                    'id'=>$order['fid'],
                    'status'=>['lt',30]
                ];
                $this->where($where)->update($update);
            }
        }
         return 1;
     }
     /* 获取订单排序 */
     public function get_sort($order){
         $sort=0;
         // sort排序，线下订单待发货10，准备发货9，线下待确认货款8，线下待付款7，待确认和待提交6，淘宝待发货5，淘宝准备发货4，淘宝待确认货款3，淘宝待付款2，淘宝错误1，其他按时间顺序排
          
         /*  'order_status' =>
          array (
          1 => '未提交',
          2 => '提交待确认',
          10 => '待付款',
          20 => '待发货',
          22 => '已准备发货',
          24 => '已发货',
          26 => '已收货',
          30 => '订单完成',
          70 => '订单关闭',
          80 => '已取消',
          81 => '已废弃',
          ), */
         
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
                         $sort=8;
                         break;
                     case 2:
                         $sort=7;
                         break;
                     default:
                         break;
                 }
                 break;
             case 2:
             case 1:
                 $sort=6;
                 break;
             default:
                 $sort=0;
                 break;
         }
         //淘宝订单落后
         if($order['order_type']==3 && $sort >5){
             $sort=$sort-5;
         } 
         
         return $sort;
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
             'goods'=>['in',$goods_ids],
         ];
         $goods_store=Db::name('store_goods')->where($where)->column('goods,num');
         foreach($goods_order as $k=>$v){
             if(!isset($goods_store[$k]) || $goods_store[$k]<$v['num']){
                 return $v['goods_name'].'库存不足';
             }
         }
         return 1;
     }
     
     /**
      * 订单提交配货申请,只能是实际单号提交
      * @param number $id
      * @param number $num_ok是否严格审核库存,1严格，2不审核
      * @return number|string
      */
     public function order_storein0($id,$num_ok=1){
         //在store_goods表中num1数值减少
         $order=$this->where('id',$id)->find();
         $order=$order->data;
        
         //订单产品
         $where_goods=[];
         if($order['is_real']==1){
             $where_goods['oid']=['eq',$order['id']];  
         }else{ 
             return '虚拟主单号不能发货';
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
             if($order['store']==0){
                 return '没有选择仓库';
             }
             //没有产品的不出库
             if($v['goods']<=0){
                 continue;
             }
             $data=[
                 'shop'=>$order['shop'],
                 'store'=>$order['store'],
                 'goods'=>$v['goods'],
                 'num'=>(0-$v['num']),
                 'atime'=>$time,
                 'aid'=>$aid,
                 'adsc'=>'客户下单出库',
                 'rstatus'=>4,
                 'type'=>10,
                 'about'=>$v['oid'],
                 'about_name'=>$order['name'],
             ];
             $res=$m_store_goods->instore0($data,$num_ok);
             if(!($res>0)){
               return $res;
             }
         }
         
         return 1;
     }
    
     
    
     /**
      *  订单发货后，出库记录批量同意
      * @param number $id
      *  * @param number $num_ok是否检查库存，1严格2不检查
      * @return string|number 
      */
     public function order_storein2($id,$num_ok=1,$dsc='订单统一出库'){
          
         //出入库记录要变为已同意
         $where=[
             'type'=>10,
             'about'=>$id,
             'rstatus'=>1,
         ]; 
         $list=Db::name('store_in')->where($where)->column('');
        
         $m_store_goods=new StoreGoodsModel();
         foreach($list as $k=>$v){
             $res=$m_store_goods->instore2($v,0,'',$num_ok);
             if(!($res>0)){
                 return $res;
             }
         }
         $in_ids=array_keys($list); 
         $update_info=[
             'rstatus'=>2,
             'rtime'=>time(),
             'rdsc'=>$dsc,
             'rid'=>empty(session('ADMIN_ID'))?1:session('ADMIN_ID'),
         ];
         Db::name('store_in')->where('id','in',$in_ids)->update($update_info);
         return 1;
     }
     
     /**
      * 订单确认发货要检查出库记录是否都已审
      * @param number $id
      * @return string|number
      */
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
         $instores=Db::name('store_in')->where($where_about)->column('');
         if(empty($instores)){
             return 1;
         }
       
         $m_store_goods=new StoreGoodsModel();
         //一个个地废弃
         foreach($instores as $k=>$v){
             $res=$m_store_goods->instore5($v);
             if($res!==1){
                 return $res;
             }
         } 
         $in_ids=array_keys($instores);
         $update_info=[
             'rstatus'=>5,
             'rtime'=>time(),
             'rdsc'=>$dsc,
             'rid'=>session('ADMIN_ID'),
         ];
         Db::name('store_in')->where('id','in',$in_ids)->update($update_info);
         return 1;
     }
     
    
     /**
      * 检查产品的更新操作是否合法
      * @param array $order
      * @param array $change
      * @return number
      */
     public function is_option($order,$change){
         
         
         return 1;
     }
    
     /**
      * 得到订单的产品详情,返回字订单和产品详情
      * @param array $info
      * @param int $aid
      * @param array $change
      * @return array[]
      */
     public function order_goods($info,$aid,$change=[]){
         //订单产品
         $where_goods=[];
         if($info['is_real']==1){
             $where_goods['oid']=['eq',$info['id']];
             $orders=[$info['id']=>$info];
         }else{
             $fields='id,name,freight,store,weight,size,discount_money,goods_num,goods_money,pay_freight'.
                 ',real_freight,other_money,tax_money,order_amount,dsc,express_no,status,pay_status';
             $fields='*';
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
 
             $infos[$v['oid']][$v['goods']]=$v;
         }
         
         //要修改的订单中是否有新增产品
         if(isset($change['edit'])){
             foreach($change['edit'] as $k=>$v){
                 if(isset($v['goods_add'])){
                     foreach($v['goods_add'] as $kk=>$vv){
                         $goods_id[$vv['goods']]=$vv['goods'];
                     } 
                 }
             }
         }
         //新增订单中检查是否有新产品
         if(isset($change['add'])){
             foreach($change['add'] as $k=>$v){
                 if(isset($v['goods'])){
                     foreach($v['goods'] as $kk=>$vv){
                         if(!isset($goods_id[$vv['goods']])){
                             $goods_id[$vv['goods']]=$vv['goods'];
                         } 
                     }
                 }
             }
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
     /**
      * 把产品的重量和体积统一转化
      * @param array $goods
      * @return array
      */
     public function unit_change($goods){
         //判断产品重量体积单位,统一转化为kg,cm3
         switch($goods['type']){
             case 5:
                 //设备kg,m
                 $goods['weight1']=$goods['weight1'];
                 $goods['size1']=bcmul($goods['size1'],1000000,2);
                 break;
             default:
                 //其他g,cm
                 $goods['weight1']=bcdiv($goods['weight1'],1000,2);
                 $goods['size1']=$goods['size1'];
                 break;
         }
         $goods['weight1']=($goods['weight1']==0)?0.01:$goods['weight1'];
         $goods['size1']=($goods['size1']==0)?0.01:$goods['size1'];
         return $goods;
     }
     /**
      * 根据现有的状态判断是否做出库操作
      * @param number $oid
      * @param number $old_status
      * @param number $num_ok是否严格检查库存
      * @return number
      */
     public function status_change($oid,$old_status=1,$num_ok=1){
        
         $res=1;
         $order=$this->where('id',$oid)->find();
         $order=$order->data;
        
         if($order['status']==$old_status){
             return 1;
         } 
         //状态判断
         if($order['status']<22 || $order['status']>=80){
             //不应该发货的,统一废弃 
             if($old_status>=22 && $old_status<=30){
                 $res=$this->order_storein5($order['id']); 
             } 
         }elseif($order['status']==22){
             if($old_status>22 && $old_status<80){
                 //已发货的，废弃 
                 $res=$this->order_storein5($order['id']);
                 if(!($res>0)){
                     return $res;
                 }
             }
             //应该准备发货的 
             $res=$this->order_storein0($order['id'],$num_ok); 
         }elseif($order['status']<=30){
             //已发货
             if($old_status<22){
                 //没准备发货的先添加出库记录 
                 $res=$this->order_storein0($order['id'],$num_ok); 
                 if(!($res>0)){
                     return $res;
                 }
             }
             //审核出库 
             $res=$this->order_storein2($order['id'],$num_ok); 
         } 
         //已发货的跟新is_pay_freight,已结算的不能动，已发货的统一改为2，其他为1 
         if($order['is_freight_pay']<3){
             $update_info=['is_freight_pay'=>1];
             if($order['is_real']==1){
                 if($order['status']>=24 && $order['status']<80 && !empty($order['express_no'])){
                     //定期结账的是2，如果不是就是3
                     $freight=Db::name('freight')->where('id',$order['freight'])->value('pay_type');
                     switch($freight){
                         case 1:
                             //先付款
                             $update_info['is_freight_pay']=3;
                             break;
                         case 2:
                             //货到付款
                             if($order['status']>=26){
                                 $update_info['is_freight_pay']=3;
                             }else{
                                 $update_info['is_freight_pay']=1;
                             } 
                             break;
                         default:
                             $update_info['is_freight_pay']=2;
                             break; 
                     } 
                 }else{
                     $update_info['is_freight_pay']=1;
                 }
             }else{
                 $update_info['is_freight_pay']=1;
             }
            
             //有变化修改
             if($update_info['is_freight_pay']!=$order['is_freight_pay']){
                 $update_info['time']=time();
                 $this->where('id',$oid)->update($update_info);
             }
         } 
        
         return $res;
     }
     /**
      * 更新用户的订购数和金额
      * $uid用户
      */
     public function custom_update($uid){
         $where=[
             'uid'=>$uid,
             'status'=>30
         ];
         //已完成订单
         $order_do1=$this->where($where)->field('count(id) as nums,sum(order_amount) as moneys')->find();
         //已收货未付款订单
         $where=[
             'uid'=>$uid,
             'status'=>26
         ]; 
         $order_do0=$this->where($where)->field('count(id) as nums,sum(order_amount) as moneys')->find();
         $update=[
             'order_num'=>$order_do1['nums'],
             'order_money'=>empty($order_do1['moneys'])?0:$order_do1['moneys'],
             'order_num0'=>$order_do0['nums'],
             'order_money0'=>empty($order_do0['moneys'])?0:$order_do0['moneys'],
             'time'=>time(),
         ];
       
         Db::name('custom')->where('id',$uid)->update($update);
     }
      
}
