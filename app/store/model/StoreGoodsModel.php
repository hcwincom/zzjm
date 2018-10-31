<?php
 
namespace app\store\model;

use think\Model;
use think\Db;
class StoreGoodsModel extends Model
{
    /**
     * 是否允许入库
     * @param $city收货地
     * @param $shop店铺
     */ 
    public function is_enough($goods,$num,$shop,$store=0){
       //数量》0表示入库
        if($num>=0){
            return 1;
        }
        $where=[
            'goods'=>$goods,
            'store'=>$store,
            'shop'=>$shop,
        ];
        $num=$this->where($where)->value('num');
        if(empty($num) || $num<(abs($num))){
            return '库存不足';
        }else{
            return 1;
        }
    }
    
    /* 申请入库 */
    function instore0($data){
         
        $where=[
            'store'=>['eq',$data['store']],
            'goods'=>['eq',$data['goods']], 
            'shop'=>['eq',$data['shop']], 
        ];
        $tmp=$this->where($where)->find();
       
        if($data['num']!=0){
            if($data['num']<0 && (empty($tmp['num']) || abs($data['num'])>$tmp['num']) ){
                return '没有库存，请选择其他产品或仓库';
            }
            //入库记录
            Db::name('store_in')->insert($data);
        }
        if(empty($tmp)){ 
            //不存在，要添加.总库存也要添加
           $data_store=[ 
                   'store'=>$data['store'],
                   'goods'=>$data['goods'], 
                   'shop'=>$data['shop'],
                   'time'=>$data['atime'],
                   'num1'=>$data['num'], 
           ];
           $this->insert($data_store);
           //总库存是否添加
           $where=[
               'store'=>['eq',0],
               'goods'=>['eq',$data['goods']],
               'shop'=>['eq',$data['shop']], 
           ];
           $tmp=$this->where($where)->find();
           if(empty($tmp)){
               $data_store=[
                       'store'=>0,
                       'goods'=>$data['goods'],
                       'shop'=>$data['shop'],
                       'time'=>$data['atime'],
                       'num1'=>$data['num'], 
                   ];
               $this->insert($data_store);
           }else{
               $this->where('id',$tmp['id'])->inc('num1',$data['num'])->setField('time',$data['atime']);
           } 
        }else{ 
            //已存在要更新
            $where=[ 
                'goods'=>['eq',$data['goods']],
                'shop'=>['eq',$data['shop']],
                'store'=>['in',[0,$data['store']]],
            ];
            $this->where($where)->inc('num1',$data['num'])->setField('time',$data['atime']);
            
        }
       
        return 1;
    }
    /* 确认入库 */
    public function instore2($goods,$store,$shop,$num,$box=0){
        //未选择料位则自动选择
       $m_box=Db::name('store_box');
        if($box==0){
            $where=[
                'store'=>$store,
                'goods'=>$goods,
                'shop'=>$shop,
                'status'=>2,
            ];
            $box=$m_box->where($where)->order('sort asc')->value('id');
            if(empty($box)){ 
                return '暂时没有适合存放的料位，请选择料位或等待料位审核';
            }
        }
        //更新料位库存
        $update_info=[
            'time'=>time(),
        ];
        $where=[
            'id'=>$box,
            'shop'=>$shop,
        ];
        
        $row=$m_box->where($where)->inc('num',$num)->update($update_info);
        if($row!==1){ 
            return '料位信息更新失败，请刷新后重试';
        }
        $where=[ 
            'goods'=>['eq',$goods],
            'shop'=>['eq',$shop], 
            'store'=>['in',[0,$store]],
        ];
        //库存判断
        if($num<0){
            $where['num']=['egt',abs($num)];
        }
        //更新仓库和总库存
        $row=$this->where($where)->inc('num',$num)->dec('num1',$num)->update($update_info); 
        if($row===2){
            return 1;
        }else{
            return '库存信息更新失败，请刷新后重试';
        }
    }
    /* 确认不入库 */
    public function instore3($goods,$store,$shop,$num){
         
        //更新料位库存
        $update_info=[
            'time'=>time(),
        ];
        
        $where=[
            'goods'=>['eq',$goods],
            'shop'=>['eq',$shop],
            'store'=>['in',[0,$store]],
        ];
        
        //更新仓库和总库存
        $row=$this->where($where)->dec('num1',$num)->update($update_info);
        if($row===1){
            return 1;
        }else{
            return '库存信息更新失败，请刷新后重试';
        }
    }
}
