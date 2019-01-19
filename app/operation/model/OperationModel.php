<?php
 
namespace app\operation\model;

use think\Model; 
use think\Db; 
class OperationModel extends Model
{
    /**
     * 获取运营相关的产品信息
     * @param array $data
     * @return array
     */
    function get_goods_info($data){
        $ids=array_keys($data);
        $where=[
            'id'=>['in',$ids] 
        ];
        //先获取产品信息
        $goods=Db::name('goods')->where($where)->column('id,name,code,name2,name3,code_name,dsc');
       
        //在获取所有供应商名和客户名称
        $where=[
            'goods'=>['in',$ids]  
        ];
        $utmp=Db::name('custom_goods')->where($where)->column('id,goods,name,cate');
        $unames=[];
        $ucates=[];
        foreach($utmp as $k=>$v){
            if(!empty($v['name'])){
                $unames[$v['goods']][$v['name']]=$v['name'];
            }
            if(!empty($v['cate'])){
                $ucates[$v['goods']][$v['cate']]=$v['cate'];
            } 
        }
       
        //供应商名
        $stmp=Db::name('supplier_goods')->where($where)->column('id,goods,name,cate');
        $snames=[];
        $scates=[];
        foreach($stmp as $k=>$v){
            if(!empty($v['name'])){
                $snames[$v['goods']][$v['name']]=$v['name'];
            }
            if(!empty($v['cate'])){
                $scates[$v['goods']][$v['cate']]=$v['cate'];
            }
            
        }
        //组装数据
        foreach($goods as $k=>$v){
            $data[$k]['name']=$v['name'];
            $data[$k]['code']=$v['code'];
            $data[$k]['dsc']=$v['dsc'];
        }
       foreach($data as $k=>$v){
           if(empty($goods[$k])){
               //产品不存在了，删除
               unset($data[$k]);
               continue;
           }
           $v['name']=$goods[$k]['name'];
           $v['code']=$goods[$k]['code'];
           $v['dsc']=$goods[$k]['dsc'];
           $v['uname']=(empty($unames[$k]))?'':(implode(',', $unames[$k]));
           $v['ucate']=(empty($ucates[$k]))?'':(implode(',', $ucates[$k]));
           $v['sname']=(empty($snames[$k]))?'':(implode(',', $snames[$k]));
           $v['scate']=(empty($scates[$k]))?'':(implode(',', $scates[$k]));
           $data[$k]=$v;
       }
       return $data;
    }
   /**
    * 统计运营的上架，推广等信息
    * @param number $id
    */
    public function status_count($id){
        $where=[
            'operation'=>$id,
        ];
        $companys=Db::name('operation_company')->where($where)->column('');
        $update=[
            'company_num'=>0,
            'propagate_num'=>0,
            'foreign_num'=>0,
            'online_num'=>0,
        ];
        foreach($companys as $k=>$v){
            $update['company_num']++;
            if($v['is_online']==1){
                $update['online_num']++;
            }
            if($v['is_propagate']==1){
                $update['propagate_num']++;
            }
            if($v['is_foreign']==1){
                $update['foreign_num']++;
            }
        }
        $update['goods_num']=Db::name('operation_goods')->where($where)->count('id');
        $this->where('id',$id)->update($update);
    }
}
