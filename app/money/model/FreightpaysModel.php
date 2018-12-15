<?php
 
namespace app\money\model;

use think\Model;
use think\Db;
class FreightpaysModel extends Model
{
     
    /**
     * 获取结算的详情
     * @param $id 结算id
     * @param $utable物流表 
     * @param $otable订单表 
     * @return string 错误信息 |array ['info'=>$info,'freight'=>$freight,'orders'=>$orders,'accounts'=>$accounts]
     */
    public function pays_info($id,$utable,$otable,$ogtable){
        $res=$this->pays_order($id,$otable);
        if(is_array($res)){
            $info=$res['info'];
            $orders=$res['orders'];
        }else{
            return $res;
        }
          
        $freight_id=$info['freight'];
        $freight=Db::name($utable)->where('id',$freight_id)->find();
        $account_ids=[];
        if(!empty($freight['dg'])){
            $account_ids[]=$freight['dg'];
        }
        if(!empty($freight['ds'])){
            $account_ids[]=$freight['ds'];
        }
        if(!empty($freight['zfb'])){
            $account_ids[]=$freight['zfb'];
        }
        if(empty($account_ids)){
            $accounts=[];
        }else{
            $accounts=Db::name('account')->where('id','in',$account_ids)->column('*');
        }
        
          
        $oids=array_keys($orders);
        $ogoods=Db::name($ogtable)->where('oid','in',$oids)->column('oid,goods,goods_name,goods_code,goods_uname,num,pay');
        foreach($ogoods as $k=>$v){
            $orders[$v['oid']]['goods'][$v['goods']]=$v;
        }
        $pay= Db::name('freightpays_pay')->where('oid',$id)->find();
        return ['info'=>$info,'freight'=>$freight,'orders'=>$orders,'accounts'=>$accounts,'pay'=>$pay];
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
        $ofileds=[
             'name', 'express_no','weight','size','addressinfo','order_amount',
            'freight','pay_freight','real_freight','is_freight_pay','goods_money','pay_status',
            'create_time','accept_time','send_time','status'
        ];
        $field='p.oid,p.money,order.'.implode(',order.',$ofileds);
        
        $orders=Db::name('freightpays_oid')
        ->alias('p')
        ->join('cmf_'.$otable.' order','order.id=p.oid')
        ->where('p.pid',$id)
        ->column($field);
        if(empty($orders)){
            return '结算相关订单不存在';
        } 
        return ['info'=>$info,'orders'=>$orders];
    }
    
    /**
     * 获取结算的添加详情
     * @param $freight_id 物流id
     * @param $utable物流表 
     * @param $oids订单ids
     * @param $otable订单表
     * @param $ogtable订单产品表
     * @return array ['freight'=>$freight,'orders'=>$orders,'accounts'=>$accounts]物流和订单信息
     */
    public function pays_addinfo($freight_id,$utable,$oids,$otable,$ogtable){
         //合作物流和付款账号
        $freight=Db::name($utable)->where('id',$freight_id)->find();
       
        $account_ids=[];
        if(!empty($freight['dg'])){
            $account_ids[]=$freight['dg'];
        }
        if(!empty($freight['ds'])){
            $account_ids[]=$freight['ds'];
        }
        if(!empty($freight['zfb'])){
            $account_ids[]=$freight['zfb'];
        }
        if(empty($account_ids)){
            $accounts=[];
        }else{
            $accounts=Db::name('account')->where('id','in',$account_ids)->column('*');
        }
       
        //关联订单
        $where_oids=[ 
            'id'=>['in',$oids],
            'is_real'=>1,
        ];
        $ofileds=[
            'id','name','freight','express_no','weight','size','addressinfo','order_amount', 
            'pay_freight','real_freight','is_freight_pay','goods_money','pay_status',
            'create_time','accept_time','send_time','status'
        ];
        $ofileds=implode(',', $ofileds);
        $orders=Db::name($otable) 
        ->where($where_oids)
        ->column($ofileds);
        if(empty($orders)){
            return '结算相关订单不存在';
        }
        
        $ogoods=Db::name($ogtable)->where('oid','in',$oids)->column('oid,goods,goods_name,goods_code,goods_uname,num,pay');
        foreach($ogoods as $k=>$v){
            $orders[$v['oid']]['goods'][$v['goods']]=$v;
        }
        
        return ['freight'=>$freight,'orders'=>$orders,'accounts'=>$accounts];
    }
    /**
     * 更新物流结算费用
     * $freight合作物流
     * $money 结算费用
     * $num 结算订单数
     */
    public function freight_update($freight,$money=0,$num=0){
        
        $m_order=Db::name('order');
       
        //已收货未付款订单
        $where=[
            'freight'=>$freight,
            'is_freight_pay'=>2
        ];
        $order_do0=$m_order->where($where)->field('count(id) as nums,sum(real_freight) as moneys')->find();
        $update=[ 
            'order_num0'=>$order_do0['nums'],
            'order_money0'=>empty($order_do0['moneys'])?0:$order_do0['moneys'],
            'time'=>time(),
        ];
      
        Db::name('freight')->where('id',$freight)->inc('order_num',$num)->inc('order_money',$money)->update($update);
    }
     
}
