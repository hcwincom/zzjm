<?php
 
namespace app\attendance\controller;

 
use cmf\controller\HomeBaseController; 
use think\Db; 
 
 /*
  * 考勤定时任务
  *   */
class TaskController extends HomeBaseController
{
    
    public function _initialize()
    {
         
    }
    
     /**
      * 每天0点1分,生成当天的考勤规则
      */
     public function attendance_date_set(){
         set_time_limit(300);
         $time0=time();
         $today=date('Y-m-d',$time0);
         $time=strtotime($today);
         /* if($time0>($time+36000)){
             exit('已生成过考勤规则');
         } */
         $m_date=Db::name('attendance_date');
         $where_date=[
             'day_time'=>$time, 
         ];
         $tmp=$m_date->where($where_date)->find();
         if(!empty($tmp)){
             exit('已生成过考勤规则');
         }
         //考勤规则
         $m_rule=Db::name('attendance_rule');
         //查询周几
         $week=date("w");
         if(empty($week)){
             $week=7;
         }
         $where_rule=[
             'week'=>$week,
         ];
         $today_rule=$m_rule->where($where_rule)->column('*','shop');
         //先看今天有没有自定义设置
         $where_rule=[
             'day'=>$today,
             'status'=>2,
         ]; 
         $today_rule1=$m_rule->where($where_rule)->column('*','shop');
         //合并规则，自定义为主
         $today_rule=array_merge($today_rule,$today_rule1);
         //循环得到要生成的规则
         $data_date=[];
         foreach($today_rule as $k=>$v){
             $start_time=strtotime($today.' '.$v['start_hour'].':'.$v['start_minute'].':0');
             $end_time=strtotime($today.' '.$v['end_hour'].':'.$v['end_minute'].':0');
             $data_date[]=[
                 'shop'=>$v['shop'],
                 'start_time'=>$start_time,
                 'end_time'=>$end_time,
                 'start1'=>$start_time+$v['start1']*60,
                 'start2'=>$start_time+86400,
                 'end1'=>$end_time-$v['end1']*60,
                 'end2'=>$end_time-86400,
                 'day_time'=>$time,
                 'work_type'=>$v['work_type'],
             ];
         }
         $m_date->insertAll($data_date);
         exit('已生成考勤规则');
     }
     /**
      * 每天0点10分,统计昨天的考勤
      */
     public function attendance_day_count(){
         set_time_limit(300);
         //得到昨天的考勤规则
         $time0=time();
         $today=date('Y-m-d',$time0-86400);
         $time=strtotime($today);
         $m_date=Db::name('attendance_date');
         $where_date=[
             'day_time'=>$time
         ];
         $rules=$m_date->where($where_date)->column('*','shop');
         dump($rules);
         //得到所有要考勤的人
         $shops=array_keys($rules);
         $where_aid=[
             'job_status'=>['lt',3],
             'user_type'=>1
         ];
         $tmp=Db::name('user')->where($where_aid)->column('id,shop');
         $aids=[];
         //管理员用shop分组
         foreach($tmp as $k=>$v){
             $aids[$v][$k]=$k;
         }
         //需要统计的考勤,
         $where_day=[
             'day_time'=>$time,
         ];
         $m_day=Db::name('attendance_day');
         $list=$m_day->where($where_day)->column('*','aid');
         //记录旷工人员
         $data_insert=[];
         //下班缺卡id
         $data_end0=[];
         foreach($rules as $k=>$v){
             $users=$aids[$k];
             //遍历用户，没有添加旷工，有则检查是否更新
             foreach($users as $vv){
                 if($v['work_type']==1){
                     if(empty($list[$vv])){ 
                         $data_insert[]=[
                             'aid'=>$vv,
                             'shop'=>$k,
                             'day_status'=>6,
                             'start_status'=>4,
                             'end_status'=>4,
                             'day_time'=>$time,
                             'time'=>$time0
                         ];
                     }elseif($list[$vv]['day_status']==1){ 
                         $data_id[]=$list[$vv]['id'];
                     }
                 } 
             }
         }
         //下班缺卡更新
         if(!empty($data_id)){
             $update=[
                 'end_status'=>4,
                 'day_status'=>3,
                 'time'=>$time0
             ];
             $m_day->where('id','in',$data_id)->update($update);
         }
        
         //旷工添加 
         if(!empty($data_insert)){  
            $m_day->insertAll($data_insert);
         }
         
         //real_day_status
         exit('已统计昨日考勤');
     }
     
     
     
     
}
