<?php

namespace app\attendance\controller;

use app\common\controller\AdminBase0Controller;
use think\Db;
use app\attendance\model\AttendanceDayModel;
 

class AttendanceajaxController extends AdminBase0Controller
{
    
    function attendance_do(){
        zz_log('attendance_do');
        $admin=$this->admin;
        $m=new AttendanceDayModel();
        $res=$m->attendance_add($admin);
        zz_log($res);
        if($res>0){
            $this->success('ok');
        }else{
            $this->error($res);
        }
        
    }
     
}
