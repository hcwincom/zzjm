<?php
 
namespace app\money\model;

use think\Model;
use think\Db;
class OrderpaysModel extends Model
{
     
    /**
     * 获取结算的详情
     * @param $id 结算id
     * @param $utable客户表
     * @param $utype客户类型 
     * @param $otable订单表
     * @param $ogtable订单产品表
     * @return string 错误信息 |array ['info'=>$info,'custom'=>$custom,'orders'=>$orders,'accounts'=>$accounts]
     */
    public function pays_info($id,$utable,$utype,$otable,$ogtable){
        $res=$this->pays_order($id,$otable);
        if(is_array($res)){
            $info=$res['info'];
            $orders=$res['orders'];
        }else{
            return $res;
        }
          
        $uid=$info['uid'];
        $custom=Db::name($utable)->where('id',$uid)->find();
        $where=[
            'uid'=>$uid,
            'type'=>$utype
        ];
        $accounts=Db::name('account')->where($where)->column('*','site'); 
          
        $oids=array_keys($orders);
        $ogoods=Db::name($ogtable)->where('oid','in',$oids)->column('oid,goods,goods_name,goods_code,goods_uname,num,pay');
        foreach($ogoods as $k=>$v){
            $orders[$v['oid']]['goods'][$v['goods']]=$v;
        }
        $pay= Db::name('orderpays_pay')->where('oid',$id)->find();
        return ['info'=>$info,'custom'=>$custom,'orders'=>$orders,'accounts'=>$accounts,'pay'=>$pay];
    }
    /**
     * 获取结算的订单
     * @param $id 结算id
     * @param $otable订单表
     * @return string 错误信息 |array ['info'=>$info,'orders'=>$orders]
     */
    public function pays_order($id,$otable){
        
        $info=$this
        ->alias('p')
        ->field('p.*,a.user_nickname as aname,r.user_nickname as rname')
        ->join('cmf_user a','a.id=p.aid','left')
        ->join('cmf_user r','r.id=p.rid','left')
        ->where('p.id',$id)
        ->find();
        $info=$info->data;
        if(empty($info)){
            return '结算信息不存在';
        }
        $orders=Db::name('orderpays_oid')
        ->alias('p')
        ->join('cmf_'.$otable.' order','order.id=p.oid')
        ->where('p.pid',$id)
        ->column('p.oid,p.money,order.name,order.order_amount,order.goods_money,order.pay_status,order.create_time,order.accept_time,order.status');
        if(empty($orders)){
            return '结算相关订单不存在';
        } 
        return ['info'=>$info,'orders'=>$orders];
    }
    
    /**
     * 获取结算的添加详情
     * @param $uid客户id
     * @param $utable客户表
     * @param $utype客户类型
     * @param $oids订单ids
     * @param $otable订单表
     * @param $ogtable订单产品表
     * @return array ['custom'=>$custom,'orders'=>$orders,'accounts'=>$accounts]客户和订单信息
     */
    public function pays_addinfo($uid,$utable,$utype,$oids,$otable,$ogtable){
         
        $custom=Db::name($utable)->where('id',$uid)->find();
        $where_account=[
            'uid'=>$uid,
            'type'=>$utype,
        ];
       
        $accounts=Db::name('account')->where($where_account)->column('*','site');
        //关联订单
        $where_oids=[ 
            'id'=>['in',$oids],
        ];
        $orders=Db::name($otable) 
        ->where($where_oids)
        ->column('id,name,uid,order_amount,goods_money,pay_status,create_time,accept_time,status');
        if(empty($orders)){
            return '结算相关订单不存在';
        }
        
        $ogoods=Db::name($ogtable)->where('oid','in',$oids)->column('oid,goods,goods_name,goods_code,goods_uname,num,pay');
        foreach($ogoods as $k=>$v){
            $orders[$v['oid']]['goods'][$v['goods']]=$v;
        }
        
        return ['custom'=>$custom,'orders'=>$orders,'accounts'=>$accounts];
    }
     
}
