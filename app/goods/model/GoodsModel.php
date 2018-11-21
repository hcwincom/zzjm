<?php
 
namespace app\goods\model;

use think\Model;
use think\Db;
class GoodsModel extends Model
{
    /**
     * 获取产品的图片，库存
     * @param $goods_ids产品id
     * @param $shop店铺
     * @param $field产品信息字段
     */
    public function goods_pics($goods){
        $goods_ids=array_keys($goods);
         
        //获取产品图片
        $where=[
            'pid'=>['in',$goods_ids],
            'type'=>['eq',1],
        ];
        $pics=Db::name('goods_file')->where($where)->column('id,pid,file');
        if(!empty($pics)){
            foreach($pics as $k=>$v){
                if(!isset($goods[$v['pid']]['pics'])){
                    $goods[$v['pid']]['pics']=[];
                }
                $goods[$v['pid']]['pics'][]=[
                    'file1'=>$v['file'].'1.jpg',
                    'file3'=>$v['file'].'3.jpg',
                ];
            }
        }
        return $goods;
    }
    /**
     * 获取产品的图片，库存
     * @param $goods_ids产品id
     * @param $shop店铺
     * @param $field产品信息字段 
     */ 
    public function goods_infos($goods_ids,$shop,$field='id,name,pic,code'){
       
        //产品
        $where=[
            'id'=>['in',$goods_ids],
            'shop'=>['eq',$shop],
        ];
        $goods=$this->where($where)->column($field);
      
         //库存
        $where=[
            'goods'=>['in',$goods_ids], 
            'shop'=>['eq',$shop],
        ];
        $list=Db::name('store_goods')->where($where)->column('id,store,goods,num,num1,safe'); 
        if(!empty($list)){
            foreach($list as $k=>$v){
                $goods[$v['goods']]['nums'][$v['store']]=[
                    'num'=>$v['num'],
                    'num1'=>$v['num1'],
                    'safe'=>$v['safe'],
                ];
            } 
        }
       
       
        //获取产品图片
        $where=[
            'pid'=>['in',$goods_ids],
            'type'=>['eq',1],
        ];
        $pics=Db::name('goods_file')->where($where)->column('id,pid,file'); 
        if(!empty($pics)){
            foreach($pics as $k=>$v){
                if(!isset($goods[$v['pid']]['pics'])){
                    $goods[$v['pid']]['pics']=[];
                }
                $goods[$v['pid']]['pics'][]=[
                    'file1'=>$v['file'].'1.jpg',
                    'file3'=>$v['file'].'3.jpg',
                ];
            }
        }
        return $goods;
    }
    /**
     * 获取产品的图片，库存,单个产品ajax
     * @param $goods_id产品id
     * @param $shop店铺
     * @param $field产品字段
     */
    public function goods_info($goods_id,$shop,$field='id,name,pic,code'){
        $data=$this->field($field)->where('id','eq',$goods_id)->find();
        $goods=$data->data;
       
        //库存
        $where=[
            'goods'=>['eq',$goods_id],
            'shop'=>['eq',$shop],
        ];
        $goods['nums']=Db::name('store_goods')->where($where)->column('store,num,num1,safe');
        
        //获取产品图片
        $where=[
            'pid'=>['eq',$goods_id],
            'type'=>['eq',1],
        ];
        $pics=Db::name('goods_file')->where($where)->column('file');
       
        if(!empty($pics)){
            $goods['pics']=[]; 
            foreach($pics as $k=>$v){
                $goods['pics'][$k]=[
                    'file1'=>($v.'1.jpg'),
                    'file3'=>($v.'3.jpg'),
                ]; 
            }
        }
        
        return $goods;
    }
     
}
