<?php
 
namespace app\admin\model;

use think\Model;
use think\Db;

class UserModel extends Model
{

    /**
     *检查是否有权限
     * @param array $admin 当前管理员
     * @param number $aid 创建管理员
     * @param number $id 数据id
     * @param number $table 关联表 
     * @return array 返回code 1可授权，2可查看，3不可查看
     */
    public function aid_check($admin,$aid,$id,$table){
        $where=[
            'shop'=>$admin['shop'],
            'user_type'=>1,
            'user_status'=>1,
            'job_status'=>['lt',3]
        ];
        
        //经理有权限授权
        if($admin['job']==1){
            //总部门总负责人有权限
            if($admin['dt']==1 && $admin['department']==0){
                
            }else{
                $user=$this->where('id',$aid)->find();
                if($admin['dt']==$user['dt']){
                    //部门相同才能下一步
                    $where['dt']=$admin['dt'];
                    if($admin['department'] == 0){
                        
                    }else{
                        if($admin['department']==$user['department']){
                            $where['department']=$admin['department']; 
                        }else{
                            //非关联上司，没权限
                            $where=[];
                        }
                    }
                }else{
                    $where=[];
                }
            }
        }
        $m_aid=Db::name($table);
        //有where的有权限且能授权
        if(!empty($where)){
            $users=$this->where($where)->order('dt asc,department asc,job asc')->column('id,user_nickname as name'); 
            $aids=array_keys($users);
            $where_aid=[
                'pid'=>$id,
                'aid'=>['in',$aids],
            ];
            $aids=$m_aid->where($where_aid)->column('aid');
            return ['aids'=>$aids,'users'=>$users,'code'=>1];
        }
          
        //普通员工，检查权限 
        $where_aid=[
            'pid'=>$id,
            'aid'=>$aid,
        ];
        $m_aid->where($where_aid)->find();
        //无访问权限,1可授权，2可查看，3不可查看
        if(empty($m_aid)){
            return ['code'=>3];
        }else{
            return ['code'=>2];
        }
    }
    
    /**
     * 添加数据增加关联
     * @param number $aid 创建管理员
     * @param number $id 数据id
     * @param number $table 关联表 
     */
    public function aid_add($aid,$id,$table){ 
        $m_aid=Db::name($table);
        $data=[
            'pid'=>$id,
            'aid'=>$aid,
        ];
        $m_aid->insert($data);
        return 1; 
    }
    
    /**
     *修改授权用户
     * @param array $admin 当前管理员
     * @param number $aid 创建管理员
     * @param array $aids 新授权
     * @param number $id 数据id
     * @param number $table 关联表
     */
    public function aid_edit($admin,$aid,$aids,$id,$table){
        $where=[
            'shop'=>$admin['shop'],
            'user_type'=>1,
            'user_status'=>1,
            'job_status'=>['lt',3]
        ];
        
        //经理有权限授权
        if($admin['job']==1){
            //总部门总负责人有权限
            if($admin['dt']==1 && $admin['department']==0){
                
            }else{
                $user=$this->where('id',$aid)->find();
                if($admin['dt']==$user['dt']){
                    //部门相同才能下一步
                    $where['dt']=$admin['dt'];
                    if($admin['department'] == 0){
                        
                    }else{
                        if($admin['department']==$user['department']){
                            $where['department']=$admin['department'];
                        }else{
                            //非关联上司，没权限
                            $where=[];
                        }
                    }
                }else{
                    $where=[];
                }
            }
        }
        //有where的有权限且能授权,没有则不能授权
        if(empty($where)){
            return 0;
        }
        $m_aid=Db::name($table);
        //获取全部可授权
        $users=$this->where($where)->column('id');
        $where_aid=[
            'pid'=>$id,
            'aid'=>['in',$users]
        ];
        //获取原授权
        $aids0=$m_aid->where($where_aid)->column('aid');
        
        //要删除的
        $aids_del=array_diff($aids0, $aids);
        //要增加的
        $aids_add=array_diff($aids,$aids0);
        //是否有修改变化
        if(empty($aids_del) && empty($aids_add)){
           return 0;
        }else{
            return 1;
        }
        
        return 1;
        
    }
    
    /**
     *修改授权用户
     * @param array $admin 当前管理员
     * @param number $aid 创建管理员
     * @param array $aids 新授权
     * @param number $id 数据id
     * @param number $table 关联表 
     */
    public function aid_edit_do($admin,$aid,$aids,$id,$table){
        $where=[
            'shop'=>$admin['shop'],
            'user_type'=>1,
            'user_status'=>1,
            'job_status'=>['lt',3]
        ];
        
        //经理有权限授权
        if($admin['job']==1){
            //总部门总负责人有权限
            if($admin['dt']==1 && $admin['department']==0){
                
            }else{
                $user=$this->where('id',$aid)->find();
                if($admin['dt']==$user['dt']){
                    //部门相同才能下一步
                    $where['dt']=$admin['dt'];
                    if($admin['department'] == 0){
                        
                    }else{
                        if($admin['department']==$user['department']){
                            $where['department']=$admin['department'];
                        }else{
                            //非关联上司，没权限
                            $where=[];
                        }
                    }
                }else{
                    $where=[];
                }
            }
        }
        //有where的有权限且能授权,没有则不能授权
        if(empty($where)){ 
            return 0;
        }
        $m_aid=Db::name($table);
        //获取全部可授权 
        $users=$this->where($where)->column('id');
        $where_aid=[
            'pid'=>$id,
            'aid'=>['in',$users]
        ];
        //获取原授权
        $aids0=$m_aid->where($where_aid)->column('aid');
        
        //要删除的
        $aids_del=array_diff($aids0, $aids);
        //要增加的
        $aids_add=array_diff($aids,$aids0);
         //删除原授权
        if(!empty($aids_del)){
            $where_aid=[
                'pid'=>$id,
                'aid'=>['in',$aids_del]
            ];
            $m_aid->where($where_aid)->delete();
        }
        //添加新授权 
        if(!empty($aids_add)){
            $data_add=[];
            foreach($aids_add as $v){
                $data_add[]=[
                    'pid'=>$id,
                    'aid'=>$v,
                ];
            }
            $m_aid->insertAll($data_add);
        }
        return 1;
       
    }
   
}