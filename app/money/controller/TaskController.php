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
     * 物流结算信息同步，每天凌晨3点1分
     */
    public function freight_update()
    { 
        zz_log('物流结算信息','task.log');
        $time=time();
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
        //记录操作记录
        $data_action=[
            'aid'=>1,
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>'系统任务，更新物流结算信息',
            'table'=>'system',
            'type'=>'edit',
            'pid'=>0,
            'link'=>'',
            'shop'=>1,
        ];
        Db::name('action')->insert($data_action);
        exit('物流结算信息ok');
    }
    /**
     * 每月费用更新,3点11
     */
    public function fee_month()
    {
        //先提醒已到期未交费用，再添加费用
        zz_log('每月费用信息','task.log');
        $time=time();
        $date=date('Y-m-d',$time);
        $time0=strtotime($date);
        $arr=explode('-', $date);
        $year=intval($arr[0]);
        $month=intval($arr[1]);
        $day=intval($arr[2]);
        $m_fee=Db::name('shop_fee');
        $data_fees=[];
        //先增加每年费用
        $where=[
            'status'=>2,  
            'day'=>$day,
            'type'=>1,
            'month'=>$month,
        ]; 
        $fees1=$m_fee->where($where)->column('');
        foreach($fees1 as $k=>$v){
            $data_fees[]=[
                'year'=>$year,
                'month'=>$month,
                'year'=>$year,
                'aid'=>1,
                'rid'=>1,
                'atime'=>$time,
                'rtime'=>$time,
                'time'=>$time,
                'status'=>2,
                'name'=>$year.'年'.$v['name'],
                'fee'=>$v['id'],
                'shop'=>$v['shop'],
                'money'=>$v['fee'],
                'money0'=>$v['fee'],
                'last_time'=>$time0+86400*$v['last_day'],
            ];
        }
        $where=[
            'status'=>2,
            'day'=>$day,
            'type'=>2, 
        ];
        $fees2=$m_fee->where($where)->column('');
        
        foreach($fees2 as $k=>$v){
            $data_fees[]=[ 
                'year'=>$year,
                'month'=>$month, 
                'year'=>$year,
                'aid'=>1,
                'rid'=>1,
                'atime'=>$time,
                'rtime'=>$time,
                'time'=>$time,
                'status'=>2,
                'name'=>$year.'年'.$month.'月'.$v['name'],
                'fee'=>$v['id'],
                'shop'=>$v['shop'],
                'money'=>$v['fee'],
                'money0'=>$v['fee'],
                'last_time'=>$time0+86400*$v['last_day'],
            ];
        }
        $m_fee_month=Db::name('shop_fee_month');
        $m_fee_month->insertAll($data_fees);
        zz_log('每月费用信息ok','task.log');
        //记录操作记录
        $data_action=[
            'aid'=>1,
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>'系统任务，更新每月费用信息',
            'table'=>'system',
            'type'=>'add',
            'pid'=>0,
            'link'=>'',
            'shop'=>1,
        ];
        Db::name('action')->insert($data_action);
        exit('每月费用信息ok');
    }
    
}
       