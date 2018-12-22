<?php
 
namespace app\attendance\model;

use think\Model;
use think\Db;
 
class AttendanceDayModel extends Model
{
    /***
     * 检测是否能打卡
     * @param array $admin打卡用户
     * @return string 可打卡的类型
     */
    public function is_attendance($admin){
        $today=date('Y-m-d');
        $time=time();
        $today_time=strtotime($today);
        //先看今天有没有自定义设置
        $where_rule=[
            'day_time'=>$today_time, 
            'shop'=>$admin['shop'],
        ];
        $m_rule=Db::name('attendance_date');
        $today_rule=$m_rule->where($where_rule)->find();
        //没有考勤规则，不能打卡
        if(empty($today_rule)){
            return 'no';
        }
        $where_today_attendance=[
            'day_time'=>$today_time,
            'aid'=>$admin['id'],
        ];
        $today_attendance=$this->where($where_today_attendance)->find();
        if(empty($today_attendance)){
            return 'start';
        }elseif($today_attendance['day_status']==1){
            return 'end';
        }elseif($today_attendance['day_status']<10){
            return 'end1';
        }else{
            return 'no';
        }
        
    }
    
    /**
     * 
     * @param array $admin打卡用户 
     * @return string|number打卡结果
     */
    public function attendance_add($admin){
        $today=date('Y-m-d');
        $time=time();
        $today_time=strtotime($today);
        //先看今天有没有自定义设置
        $where_rule=[
            'day_time'=>$today_time,
            'shop'=>$admin['shop'],
        ];
        $m_rule=Db::name('attendance_date');
        $today_rule=$m_rule->where($where_rule)->find();
        //没有考勤规则，不能打卡
        if(empty($today_rule)){
            return '没有考勤规则，不能打卡';
        }
      
        $where_today_attendance=[
            'day_time'=>$today_time,
            'aid'=>$admin['id'],
        ];
        $today_attendance=$this->where($where_today_attendance)->find();
        
        if(empty($today_attendance)){
            //新增签到 
          $data=[
              'aid'=>$admin['id'],
              'shop'=>$admin['shop'],
              'start_time'=>$time,
              'start_status'=>($today_rule['start1']>=$time)?2:3, 
              'day_time'=>$today_time,
              'time'=>$time
          ];
          
          $this->insert($data);
          return 1;
        }elseif($today_attendance['day_status']<10){
            $data=[ 
                'end_time'=>$time,
                'end_status'=>($today_rule['end1']<$time)?2:3, 
                'time'=>$time
            ];
            $data['work_hour']=round(($data['end_time']-$today_attendance['start_time'])/3600,1);
          
            if($today_attendance['start_status']==3 || $data['end_status']==3){
                $data['day_status']=3;
            }else{
                $data['day_status']=2;
            }
            
            $this->where('id',$today_attendance['id'])->update($data); 
            return 2;
        }else{
            return '不能重复打卡';
        }
        
        
    }
    
}
