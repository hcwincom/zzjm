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
    
     /**
      * 每天3点,历史库存
      */
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
         $m_new->commit();
         dump($count);
     }
     
     
     /**
      * 每天2点,空间占用
      */
     public function space_count(){
         set_time_limit(300);
         $time=time();
         $date=date('Y-m-d',$time);
         $time0=strtotime($date);
         //记录操作记录
         $data_action=[
             'aid'=>1,
             'time'=>$time,
             'ip'=>get_client_ip(),
             'action'=>'系统任务，更新仓库空间占用率',
             'table'=>'system',
             'type'=>'edit',
             'pid'=>0,
             'link'=>'',
             'shop'=>1,
         ];
         $m_action=Db::name('action');
         //每1000个一批
         $count0=1000;
         //临时中间表
         $m_tmp=Db::name('store_space');
        
         //先更新料位空间
         $m_box=Db::name('store_box'); 
         $m_tmp->execute('truncate table cmf_store_space');
     
         $count=$m_box->where('status',2)->count('id');
         //1000个一组
         $group=ceil($count/$count0);
       
         for($i=0;$i<$group;$i++){
             $list=$m_box
             ->alias('box')
             ->join('cmf_goods goods','goods.id=box.goods','left')
             ->limit($i*$count0,$count0)
             ->column('box.length,box.space,box.num,goods.price_sale,goods.size0 as goods_size,goods.weight0 as goods_weight','box.id');
           
             //循环计算
             $tmp=[];
             foreach($list as $k=>$v){
                 $space_use=bcmul($v['goods_size'],$v['num'],2);
                 $weight=bcmul($v['goods_weight']*1000,$v['num'],2);
                 $space_rate=bcdiv($space_use*100,$v['space'],2);
                 $money=bcmul($v['price_sale'],$v['num'],2);
                 $tmp[]=[
                     'id'=>$k,
                     'space_use'=>$space_use,
                     'weight'=>$weight,
                     'space_rate'=>$space_rate, 
                     'money'=>$money
                 ]; 
             } 
             $m_tmp->insertAll($tmp); 
             $sql='update cmf_store_box,cmf_store_space '.
             'set cmf_store_box.space_use=cmf_store_space.space_use, '.
             'cmf_store_box.weight=cmf_store_space.weight, '.
             'cmf_store_box.space_rate=cmf_store_space.space_rate, '.
             'cmf_store_box.money=cmf_store_space.money '.
             ' where cmf_store_box.id=cmf_store_space.id';
             $m_box->execute($sql);  
         }
         $data_action['action']='更新了料位空间占用率';
         $m_action->insert($data_action);
         
         $m_floor=Db::name('store_floor');
         $m_store=Db::name('store');
         $m_shelf=Db::name('store_shelf');
         $m_tmp->execute('truncate table cmf_store_space');
         $m_floor->startTrans();
         $where=[
             'status'=>2,
             'type'=>1,
         ];
         $stores0=$m_store->where($where)->column('id,name,space');
         //不同仓库分开来
         foreach($stores0 as $kk=>$vv){ 
             $where=[ 
                 'store'=>$kk,
             ];
             //所有层
             $floors0=$m_floor->where($where)->column('id,space');
             //分组得到层中货物体积
             $floors1=$m_box->where($where)->group('floor') 
             ->column('floor as id,sum(space_use) as space_use,sum(weight) as weight');
             
             //循环计算,floor新值 
             foreach($floors0 as $k=>$v){
                 if(empty($floors1[$k])){
                     $floors1[$k]=[
                         'id'=>$k,
                         'space_use'=>0,
                         'weight'=>0,
                         'space_rate'=>0,
                     ];
                }else{
                    //满仓，可能要发消息
                    if($v<=$floors1[$k]['space_use']){
                        $floors1[$k]['space_rate']=100;
                    }else{
                        $floors1[$k]['space_rate']=bcdiv($floors1[$k]['space_use']*100,$v,2);
                    } 
                } 
             }
             $m_tmp->insertAll($floors1);
             $sql='update cmf_store_floor,cmf_store_space '.
                 'set cmf_store_floor.space_use=cmf_store_space.space_use, '.
                 'cmf_store_floor.weight=cmf_store_space.weight, '.
                 'cmf_store_floor.space_rate=cmf_store_space.space_rate '.
                 ' where cmf_store_floor.id=cmf_store_space.id';
             $m_floor->execute($sql);
             $data_action['action']='更新了仓库'.$kk.'-'.$vv['name'].'的层级空间占用率';
             $m_action->insert($data_action);
             //货架空间
             $m_tmp->execute('truncate table cmf_store_space');
            
             $where=[
                 'store'=>$kk,
             ];
             //所有货架
             $shelfs0=$m_shelf->where($where)->column('id,space');
             //分组得到货架中货物体积
             $shelfs1=$m_floor->where($where)->group('shelf')
             ->column('shelf as id,sum(space_use) as space_use,sum(weight) as weight');
             
             //循环计算,shelf新值
             foreach($shelfs0 as $k=>$v){
                 if(empty($shelfs1[$k])){
                     $shelfs1[$k]=[
                         'id'=>$k,
                         'space_use'=>0,
                         'weight'=>0,
                         'space_rate'=>0,
                     ];
                 }else{
                     //满仓，可能要发消息
                     if($v<=$shelfs1[$k]['space_use']){
                         $shelfs1[$k]['space_rate']=100;
                     }else{
                         $shelfs1[$k]['space_rate']=bcdiv($shelfs1[$k]['space_use']*100,$v,2);
                     }
                 }
             }
             $m_tmp->insertAll($shelfs1);
             $sql='update cmf_store_shelf,cmf_store_space '.
                 'set cmf_store_shelf.space_use=cmf_store_space.space_use, '.
                 'cmf_store_shelf.weight=cmf_store_space.weight, '.
                 'cmf_store_shelf.space_rate=cmf_store_space.space_rate '.
                 ' where cmf_store_shelf.id=cmf_store_space.id';
             $m_shelf->execute($sql);
             $data_action['action']='更新了仓库'.$kk.'-'.$vv['name'].'的货架空间占用率';
             $m_action->insert($data_action); 
         }
         
         //分组得到仓库中货物体积
         $stores1=$m_shelf->group('store')
         ->column('store as id,sum(space_use) as space_use,sum(weight) as weight');
         //循环计算,store新值
          foreach($stores0 as $kk=>$vv){ 
              if(empty($stores1[$kk])){
                  $stores1[$kk]=[
                      'id'=>$kk,
                      'space_use'=>0,
                      'weight'=>0,
                      'space_rate'=>0,
                  ];
              }else{
                  //满仓，可能要发消息
                  if($vv['space']<=$stores1[$kk]['space_use']){
                      $stores1[$kk]['space_rate']=100;
                  }else{
                      $stores1[$kk]['space_rate']=bcdiv($stores1[$kk]['space_use']*100,$vv['space'],2);
                  }
              }
          }
         $m_tmp->insertAll($stores1);
         $sql='update cmf_store,cmf_store_space '.
             'set cmf_store.space_use=cmf_store_space.space_use, '.
             'cmf_store.weight=cmf_store_space.weight, '.
             'cmf_store.space_rate=cmf_store_space.space_rate '.
             ' where cmf_store.id=cmf_store_space.id';
         $m_store->execute($sql);
         $data_action['action']='更新了所有仓库的空间占用率';
         $m_action->insert($data_action);
         dump($count);
     }
    
}
