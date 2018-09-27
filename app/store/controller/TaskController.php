<?php
 
namespace app\store\controller;

 
use cmf\controller\HomeBaseController; 
use think\Db; 
 
 /*
  * 库存的定时任务
  *   */
class TaskController extends HomeBaseController
{
    
    public function _initialize()
    {
         
    }
     //每天3点,历史库存
     public function store_history(){
         set_time_limit(300);
         $time=time();
         $date=date('Y-m-d',$time);
         $time0=strtotime($date);
         $m_new=Db::name('store_goods');
         $m_history=Db::name('store_goods_history');
         $m_new->startTrans();
         $count=$m_new->count('id');
         //1000个一组
         $group=ceil($count/1000);
         for($i=0;$i<$group;$i++){
             $list=$m_new->limit($i*1000,($i+1)*1000)->column('store,goods,num,shop','id');
             //如果直接在数据库查询中给定时间就不用循环了
             $tmp=[];
             foreach($list as $k=>$v){
                 $tmp[]=[
                     'store'=>$v['store'],
                     'goods'=>$v['goods'],
                     'num'=>$v['num'],
                     'shop'=>$v['shop'],
                     'time'=>$time0,
                 ];
             }
             $m_history->insertAll($tmp);
         }
         dump($count);
     }
     
     //每天2点,空间占用
     public function space_count(){
         set_time_limit(300);
         $time=time();
         $date=date('Y-m-d',$time);
         $time0=strtotime($date);
         $m=Db::name('store_box');
       
         $m->startTrans();
         $count=$m->count('id');
         //1000个一组
         $group=ceil($count/1000);
         dump('$group'.$group);
         for($i=0;$i<$group;$i++){
             $list=$m
             ->alias('box')
             ->join('cmf_goods goods','goods.id=box.goods','left')
             ->limit($i*1000,($i+1)*1000)
             ->column('box.length,box.space,box.num,goods.size0 as goods_size,goods.weight0 as goods_weight','box.id');
             
             //如果直接在数据库查询中给定时间就不用循环了
             $tmp=[];
             foreach($list as $k=>$v){
                 $tmp[]=[
                     'store'=>$v['store'],
                     'goods'=>$v['goods'],
                     'num'=>$v['num'],
                     'shop'=>$v['shop'],
                     'time'=>$time0,
                 ];
             }
            
         }
         dump($count);
     }
    
}
