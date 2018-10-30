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
         //如果默认库存不足就按优先仓库发货
         foreach($goods as $k=>$v){
            if(empty($store_num[$k][$store])){
                $num0=0;
                $num1= $v['num'];
            }elseif($v['num']>$store_num[$k][$store]){
                $num0=$store_num[$k][$store];
                $num1= $v['num']-$num0;
                $order[$store][$k]=[
                    'goods'=>$k,
                    'num'=>$num0,
                    'price_real'=>$v['price_real'],
                    'pay'=>bcmul($v['price_real'],$num0,2), 
                    'goods_sn'=>$v['goods_sn'],
                    'goods_name'=>$v['goods_name'],
                    'goods_code'=>$v['goods_code'],
                    'goods_pic'=>$v['goods_pic'],
                    'price_in'=>$v['price_in'],
                    'price_sale'=>$v['price_sale'], 
                ];
            }else{
                $order[$store][$k]=$v;
                continue;
            }
            //按优先仓库发货
            foreach($sort as $vv){
                if(empty($store_num[$k][$vv])){
                    continue;
                }elseif($num1 > $store_num[$k][$vv]){
                    $num0=$store_num[$k][$vv];
                    $num1= $num1-$num0;
                    $order[$vv][$k]=[
                        'goods'=>$k,
                        'num'=>$num0,
                        'price_real'=>$v['price_real'],
                        'pay'=>bcmul($v['price_real'],$num0,2), 
                        'goods_sn'=>$v['goods_sn'],
                        'goods_name'=>$v['goods_name'],
                        'goods_code'=>$v['goods_code'],
                        'goods_pic'=>$v['goods_pic'],
                        'price_in'=>$v['price_in'],
                        'price_sale'=>$v['price_sale'], 
                    ];
                    continue;
                }else{
                    $order[$vv][$k]=[
                        'goods'=>$k,
                        'num'=>$num1,
                        'price_real'=>$v['price_real'],
                        'pay'=>bcmul($v['price_real'],$num1,2), 
                        'goods_sn'=>$v['goods_sn'],
                        'goods_name'=>$v['goods_name'],
                        'goods_code'=>$v['goods_code'],
                        'goods_pic'=>$v['goods_pic'],
                        'price_in'=>$v['price_in'],
                        'price_sale'=>$v['price_sale'], 
                    ];
                    $num1=0;
                    break;
                }
            }
            //优先仓库货都不够，都标为暂时无货
            if($num1>0){
                $order[0][$k]=[
                    'goods'=>$k,
                    'num'=>$num1,
                    'price_real'=>$v['price_real'],
                    'pay'=>bcmul($v['price_real'],$num1,2), 
                    'goods_sn'=>$v['goods_sn'],
                    'goods_name'=>$v['goods_name'],
                    'goods_code'=>$v['goods_code'],
                    'goods_pic'=>$v['goods_pic'],
                    'price_in'=>$v['price_in'],
                    'price_sale'=>$v['price_sale'], 
                ];
            } 
        }
        return $order;
     }
}
