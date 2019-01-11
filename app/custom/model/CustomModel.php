<?php
 
namespace app\custom\model;

use think\Model;
use think\Db;
class CustomModel extends Model
{
    /**
     * 
     * @param array $admin当前管理员
     * @param unknown $aid创建管理员
     * @param unknown $utype用户类型
     * @return number
     */
    public function is_aid($admin,$aid,$utype){
        $where=[
            'shop'=>$admin['shop'],
            'user_type'=>1,
            'user_status'=>1,
            'job_status'=>['lt',3]
        ];
        $m_user=Db::name('user');
        //总部门总负责人有权限
        if($admin['dt']==1 && $admin['department']==0){
            
        }
        if($utype==1){
            $m_aid=Db::name('custom_aid');
        }else{
            $m_aid=Db::name('supplier_aid');
        }
        
    }
}
