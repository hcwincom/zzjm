<?php
 
namespace app\money\model;

use think\Model;
use think\Db;
class OrdersInvoiceModel extends Model
{
     
    function invoice_add($data){
        //有公司id，没有公司名，则查找公司
        if(!empty($data['paytype'])){
            $paytype=Db::name('paytype')->where('id',$data['paytype'])->find(); 
            $data['company_name']=$paytype['company_name']; 
            $data['company_code']=$paytype['feenum'];
            $data['company_address']=$paytype['address'].' '.$paytype['tel']; 
            $data['company_bank']=$paytype['bank'];  
            $data['company_bank_location']=$paytype['location'].' '.$paytype['num']; 
        }
       
        if(empty($data['atime'])){
            $time=time();
            $data['atime']=$time;
            $data['time']=$time;
        }
        if(isset($data['id'])){
            unset($data['id']);
        }
        return $this->insertGetId($data);
        
       
    }
    /**
     * 更新发票
     * @param array $data 发票信息
     * @return number
     */
    function invoice_update($data,$id=0){
        if(empty($id)){
            if(empty($data['id'])){
                return 0;
            }else{
                $id=$data['id'];
            }
        }
        if(!empty($data['paytype'])){
            $paytype=Db::name('paytype')->where('id',$data['paytype'])->find();
            $data['company_name']=$paytype['company_name'];
            $data['company_code']=$paytype['feenum']; 
            $data['company_bank']=$paytype['bank'];
            
            $data['company_address']=$paytype['address'].' '.$paytype['tel'];
            $data['company_bank']=$paytype['bank'];
            $data['company_bank_location']=$paytype['location'].' '.$paytype['num']; 
        }
       $data['time']=time();
        $res=$this->where('id',$id)->update($data);
        return 1; 
    }
     
}
