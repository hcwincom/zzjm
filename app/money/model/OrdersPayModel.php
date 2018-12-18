<?php
 
namespace app\money\model;

use think\Model;
use think\Db;
class OrdersPayModel extends Model
{
     
    function pay_add($data){
        //有公司id，没有公司名，则查找公司
        if(!empty($data['paytype'])){
            $paytype=Db::name('paytype')->where('id',$data['paytype'])->find(); 
            $data['company_bank_name']=$paytype['account'];
            $data['company_bank']=$paytype['bank'];
            $data['company_bank_num']=$paytype['num'];
            $data['company_bank_location']=$paytype['location'];
        }
        if(isset($data['id'])){
            unset($data['id']);
        }
        return $this->insertGetId($data);
        
       
    }
    /**
     * 更新支付
     * @param array $data 支付信息
     * @return number
     */
    function pay_update($data,$id=0){
        if(empty($id)){
            if(empty($data['id'])){
                return 0;
            }else{
                $id=$data['id'];
            }
        }
        if(!empty($data['paytype'])){
            $paytype=Db::name('paytype')->where('id',$data['paytype'])->find();
            $data['company_bank_name']=$paytype['account'];   
            $data['company_bank']=$paytype['bank'];
            $data['company_bank_num']=$paytype['num'];
            $data['company_bank_location']=$paytype['location'];
        }
        $res=$this->where('id',$id)->update($data);
        return 1;
        
        
    }
     
}
