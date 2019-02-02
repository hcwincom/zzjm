<?php
 
namespace app\shop\model;

use think\Model; 
use think\Db; 
class DepartmentModel extends Model
{
    /**
     * 返回所有一级部门
     * @param array $where
     * @param string $fields
     * @return array
     */
   public function get_all1($where=['status'=>2],$fields='id,name'){
       $list=Db::name('dt')->where($where)->order('sort asc')->column($fields);
       return $list;
   }
   /**
    * 返回所有二级部门
    * @param array $where
    * @param string $fields
    * @return array
    */
   public function get_all2($where=['status'=>2],$fields='id,dt,name'){
       $list=$this->where($where)->order('dt asc,sort asc')->column($fields);
       return $list;
   }
   
}
