<?php
 
namespace app\money\controller;

 
use think\Db; 
use app\order\model\OrderModel;
use cmf\controller\HomeBaseController;
use taobao\Taobao;
use app\store\model\StoreGoodsModel;
use app\money\model\FreightpaysModel;
/**
 * 定时任务 
 */
class TaskController extends HomeBaseController
{
    
    public function _initialize()
    {
         
    }
    /**
     * 物流结算信息同步，每天凌晨3点
     */
    public function freight_update()
    { 
        zz_log('物流结算信息','task.log');
        $where=[ 
            'status'=>2,
        ];
        $m=new FreightpaysModel();
        $freights=Db::name('freight')->where($where)->column('id');
        //更新物流结算费用
        foreach($freights as $v){
            $m->freight_update($v);
        }
        zz_log('物流结算信息ok','task.log');
        exit('物流结算信息ok');
    }
    
}
       