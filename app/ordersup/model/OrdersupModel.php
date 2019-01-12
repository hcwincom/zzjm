<?php

namespace app\ordersup\model;

use think\Model;
use think\Db;
use app\store\model\StoreGoodsModel;
use app\money\model\OrdersInvoiceModel;
use app\money\model\OrdersPayModel;
class OrdersupModel extends Model
{
    /**
     * 获取单条数据，主要是为了返回data，去除对象影响
     * @param $where 查询条件
     * @param $field 字段
     * @param data
     */
    public function get_one($where,$field='*'){
        
        $order=$this->field($field)->where($where)->find();
        return $order->data;
    }
    /**
     * 下单时为仓库排序，按首重价格计算
     * @param $city收货地
     * @param $shop店铺
     * @param $store已选择的仓库,要排除
     */
    public function store_sort($city,$shop,$store=0){
         
        return []; 
    }
    
    /*
     * 自动分单,采购暂时没有自动分单
     * @param $goods产品信息
     * @param $goods主单号
     * @param $store首选仓库
     * @param $city收货地
     * @param $shop
     *  */
    public function order_break($goods,$oid,$store,$city,$shop){
        //获取优先的仓库,已去除默认的
        $sort=$this->store_sort($city,$shop,$store);
        if(empty($sort)){
            $sort=[];
        }
        //获取可用的仓库，优先仓库+默认选择的
        $stores=$sort;
        $stores[]=$store;
        //获取所有库存
        $goods_id=array_keys($goods);
        $where=[
            'goods'=>['in',$goods_id],
            'shop'=>['eq',$shop],
            'store'=>['in',$stores],
        ];
        $list=Db::name('store_goods')->where($where)->column('id,store,goods,num');
        //循环得到数据
        $store_num=[];
        foreach($list as $k=>$v){
            $store_num[$v['goods']][$v['store']]=$v['num'];
        }
        
        
        //最终order
        $order=[];
        //
        $num0=0;
        $num1=0;
        
        //如果默认库存不足就按优先仓库收货,重新计算费用和重量体积
        foreach($goods as $k=>$v){
            $ave_discount=bcdiv($v['pay_discount'],$v['num'],2);
            if(empty($store_num[$k][$store])){
                //没有库存，下面优先仓库收货
                $num0=0;
                $num1= $v['num'];
            }elseif($v['num']>$store_num[$k][$store]){
                //库存不足，先分单
                $num0=$store_num[$k][$store];
                $num1= $v['num']-$num0;
                $order[$store][$k]=$v;
                $order[$store][$k]['num']=$num0;
                $order[$store][$k]['pay_discount']=bcmul($ave_discount,$num0,2);
                $order[$store][$k]['pay']=bcsub($v['price_real']*$num0, $order[$store][$k]['pay_discount'],2);
                $order[$store][$k]['weight']=bcmul($v['weight1'],$num0,2);
                $order[$store][$k]['size']=bcmul($v['size1'],$num0,2);
                
            }else{
                //库存足够，不用分单
                $order[$store][$k]=$v;
                continue;
            }
            
            //按优先仓库收货
            foreach($sort as $vv){
                
                if(empty($store_num[$k][$vv])){
                    //没有库存
                    continue;
                }elseif($num1 > $store_num[$k][$vv]){
                    //产品数量大于库存，先分一单
                    $num0=$store_num[$k][$vv];
                    $num1= $num1-$num0;
                    $order[$vv][$k]=$v;
                    $order[$vv][$k]['num']=$num0;
                    $order[$vv][$k]['pay_discount']=bcmul($ave_discount,$num0,2);
                    $order[$vv][$k]['pay']=bcsub($v['price_real']*$num0, $order[$vv][$k]['pay_discount'],2);
                    $order[$vv][$k]['weight']=bcmul($v['weight1'],$num0,2);
                    $order[$vv][$k]['size']=bcmul($v['size1'],$num0,2);
                    
                    continue;
                }else{
                    //数量足够
                    $order[$vv][$k]=$v;
                    $order[$vv][$k]['num']=$num1;
                    $order[$vv][$k]['pay_discount']=bcmul($ave_discount,$num0,2);
                    $order[$vv][$k]['pay']=bcsub($v['price_real']*$num0, $order[$vv][$k]['pay_discount'],2);
                    $order[$vv][$k]['weight']=bcmul($v['weight1'],$num1,2);
                    $order[$vv][$k]['size']=bcmul($v['size1'],$num1,2);
                    $num1=0;
                    break;
                }
            }
            //优先仓库货都不够，都标为暂时无货
            if($num1>0){
                $order[0][$k]=$v;
                $order[0][$k]['num']=$num1;
                $order[0][$k]['pay_discount']=bcmul($ave_discount,$num0,2);
                $order[0][$k]['pay']=bcsub($v['price_real']*$num0, $order[0][$k]['pay_discount'],2);
                
                $order[0][$k]['weight']=bcmul($v['weight1'],$num1,2);
                $order[0][$k]['size']=bcmul($v['size1'],$num1,2);
                
            }
        }
        
        return $order;
    }
    
    /* 采购单编辑 */
    public function order_edit($info,$data,$is_do=0)
    {
        
        $content=[];
        //检测改变了哪些字段
        //所有采购单都有,都能修改
        $edit_base=['dsc','store','freight','weight','size','pay_freight','real_freight','order_type',
            'goods_num','goods_money','discount_money','tax_money','other_money','order_amount','express_no'
            
        ];
        //收货信息，子采购单可以单独修改，总采购单修改后同步到子采购单
        $edit_accept=['accept_name','mobile','phone','province','city','area','address','postcode','addressinfo'];
        
        //总采购单信息系
        $edit_fid0=['company','udsc','paytype','pay_type','invoice_type','order_type','ok_break'];
        //组装需要判断的字段,普通采购单未拆分的不比较总采购单信息
        if($info['fid']==0){
            $fields=array_merge($edit_accept,$edit_fid0);
            if($info['is_real']==2 || count($data['oids'])>1){
                $fields=array_merge($fields,$edit_base);
            }
        }else{
            $fields=$edit_accept;
        }
        //先比较总信息
        foreach($fields as $k=>$v){
            //如果原信息和$data信息相同就未改变，不为空就记录，？null测试
            if(isset($data[$v]) && $info[$v]!=$data[$v]){
                $content[$v]=$data[$v];
            }
        }
        
        //主采购单才有发票和付款信息
        if($info['fid']==0 ){
            //发票信息
            $edit_invoice=['uname','ucode','point','invoice_money','tax_money','dsc','address','tel'];
            //已有发票或写了发票抬头的要判断发票信息
            if(!empty($info['invoice_id']) || (!empty($data['invoice_uname']) && !empty($data['invoice_type']))){
                $data['invoice_id']=$info['invoice_id'];
                $data['invoice_point']=round( $data['invoice_point'],2);
                $data['invoice_invoice_money']=round( $data['invoice_invoice_money'],2);
                $data['invoice_tax_money']=round( $data['invoice_tax_money'],2);
                if($data['invoice_id']==0){
                    $invoice=null;
                }else{
                    //发票
                    $where=[
                        'id'=>$info['invoice_id'],
                    ];
                    $m_invoice=new OrdersInvoiceModel();
                    $invoice=$m_invoice->where($where)->find();
                    if(empty($invoice)){
                        $data['invoice_id']=0;
                    }
                }
                
                $content['invoice']=[];
                
                foreach($edit_invoice as $k=>$v){
                    $field_tmp='invoice_'.$v;
                    //如果原信息和$data信息相同就未改变，不为空就记录，？null测试
                    if(isset($data[$field_tmp]) && $invoice[$v]!=$data[$field_tmp]){
                        $content['invoice'][$v]=$data[$field_tmp];
                    }
                }
                //支付账号
                if($data['paytype'] != $invoice['paytype']){
                    $content['invoice']['paytype']=$data['paytype'];
                }
                
                //没有改变清除
                if(empty($content['invoice'])){
                    unset($content['invoice']);
                }else{
                    $content['invoice']['id']= $data['invoice_id'];
                    $content['invoice']['oid']= $info['id'];
                    $content['invoice']['oid_type']= 1;
                }
            }
            
            //支付信息
            $edit_account=['bank','name','num','location'];
            //已有付款账号信息和付款账户名
            if(!empty($info['pay_id']) || !empty($data['account_name']) ){
                $data['account_id']=$info['pay_id'];
                if($data['account_id']==0){
                    $pay=null;
                }else{
                    //发票
                    $where=[
                        'id'=>$data['account_id'],
                    ];
                    $m_pay=new OrdersPayModel();
                    $pay=$m_pay->where($where)->find();
                    if(empty($pay)){
                        $data['account_id']=0;
                    }
                }
                
                $content['pay']=[];
                foreach($edit_account as $k=>$v){
                    $field_tmp='account_'.$v;
                    //如果原信息和$data信息相同就未改变，不为空就记录，？null测试
                    if(isset($data[$field_tmp]) && $pay[$v]!=$data[$field_tmp]){
                        $content['pay'][$v]=$data[$field_tmp];
                    }
                }
                //店铺支付账号
                if($pay['paytype']!=$data['paytype']){
                    $content['pay']['paytype']=$data['paytype'];
                }
                //没有改变清除
                if(empty($content['pay'])){
                    unset($content['pay']);
                }else{
                    //记录id,review时检测
                    $content['pay']['id']= $data['account_id'];
                    $content['pay']['oid']= $info['id'];
                    $content['pay']['oid_type']= 1;
                    $content['pay']['ptype']= 1;
                    
                }
            }
        }
        
        //获取原采购单和采购单产品
        $where_goods=[];
        if($info['is_real']==1 ){
            $where_goods['oid']=['eq',$info['id']];
            $orders=[$info['id']=>$info];
            $order_ids=[$info['id']];
        }else{
            $fields='id,name,'.(implode(',',$edit_base));
            /*   $fields='id,name,freight,store,weight,size,discount_money,goods_num,goods_money,pay_freight'.
             ',real_freight,other_money,tax_money,order_amount,dsc'; */
            $orders=$this->where('fid',$info['id'])->column($fields);
            
            $order_ids=array_keys($orders);
            
            $where_goods['oid']=['in',$order_ids];
        }
        
        //全部采购单产品
        $ordersup_goods=Db::name('ordersup_goods')
        ->where($where_goods)
        ->column('');
        //数据转化，按采购单分组
        $infos=[];
        //先组装所有采购单，防止有的采购单没有产品
        foreach($order_ids as $v){
            $infos[$v]=[];
        }
        $goods_info=[];
        
        foreach($ordersup_goods as $k=>$v){
            /*  if($v['goods']<=0){
             return '产品'.$v['goods_code'].$v['goods_uname'].'不存在，要调整';
             } */
            $infos[$v['oid']][$v['goods']]=$v;
            $goods_info[$v['goods']]=$v;
        }
        //得到原有产品
        $goods_ids0=array_keys($goods_info);
        
        $goods_ids1=$data['goods_ids'];
        $ids_add=array_diff($goods_ids1,$goods_ids0);
        if(!empty($ids_add)){
            //有新增产品
            $goods_add=Db::name('goods')->where('id','in',$ids_add)->column('id as goods,name as goods_name,name3 as print_name,code as goods_code,pic as goods_pic,price_in,price_sale,type,weight1,size1');
            foreach($goods_add as $k=>$v){
                //判断产品重量体积单位,统一转化为kg,cm3
                $v=$this->unit_change($v);
                unset($v['type']);
                $goods_info[$k]=$v;
            }
        }
        
        //子采购单nums-{$kk}[{$key}],只有在主采购单下才能拆分采购单
        
        /*  $edit_base=['dsc','store','freight','weight','size','pay_freight','real_freight',
         'goods_num','goods_money','discount_money','tax_money','other_money','order_amount',
         ]; */
        $edit_goods=['num','pay','weight','size','dsc','price_real','pay_discount','goods_uname','goods_ucate'];
        
        
        //多个要一个个比较,先比较是否存在
        foreach($data['oids'] as $k=>$void){
            if(in_array($void,$order_ids)){
                //编辑采购单信息
                foreach($edit_base as $kk=>$vv){
                    if($orders[$void][$vv]!=$data[$vv.'0'][$void]){
                        $content['edit'][$void][$vv]=$data[$vv.'0'][$void];
                    }
                }
                
                //一个个比较产品，是否有删除或编辑
                foreach ($infos[$void] as $kgoodsid=>$kv){
                    //data不存在就是没有该产品了,删除
                    if(!isset($data['nums-'.$void][$kgoodsid]) ){
                        $content['edit'][$void]['goods_del'][$kgoodsid]=$kv;
                        continue;
                    }
                    if($kgoodsid<=0){
                        return '产品不存在，请重新选择产品';
                    }
                    //循环商品信息
                    foreach($edit_goods as $vv){
                        if($data[$vv.'s-'.$void][$kgoodsid] !=  $kv[$vv]){
                            $content['edit'][$void]['goods'][$kgoodsid][$vv]=$data[$vv.'s-'.$void][$kgoodsid];
                        }
                    }
                }
                if(isset($data['nums-'.$void])){
                    //再用data数据循环，检查是否有新增，没有继续向下
                    foreach ($data['nums-'.$void] as $kgoodsid=>$kv){
                        if($kgoodsid<=0){
                            return '产品不存在，请重新选择产品';
                        }
                        if(isset($infos[$void][$kgoodsid])){
                            continue;
                        }
                        $content['edit'][$void]['goods_add'][$kgoodsid]=[];
                        //保存采购单号
                        $content['edit'][$void]['goods_add'][$kgoodsid]['oid']=$void;
                        //添加商品id
                        $content['edit'][$void]['goods_add'][$kgoodsid]['goods']=$kgoodsid;
                        //循环商品信息
                        foreach($edit_goods as $vv){
                            $content['edit'][$void]['goods_add'][$kgoodsid][$vv]=$data[$vv.'s-'.$void][$kgoodsid];
                        }
                        $content['edit'][$void]['goods_add'][$kgoodsid]['goods_name']=$goods_info[$kgoodsid]['goods_name'];
                        $content['edit'][$void]['goods_add'][$kgoodsid]['print_name']=$goods_info[$kgoodsid]['print_name'];
                        $content['edit'][$void]['goods_add'][$kgoodsid]['goods_code']=$goods_info[$kgoodsid]['goods_code'];
                        $content['edit'][$void]['goods_add'][$kgoodsid]['goods_pic']=$goods_info[$kgoodsid]['goods_pic'];
                        $content['edit'][$void]['goods_add'][$kgoodsid]['price_in']=$goods_info[$kgoodsid]['price_in'];
                        $content['edit'][$void]['goods_add'][$kgoodsid]['price_sale']=$goods_info[$kgoodsid]['price_sale'];
                        $content['edit'][$void]['goods_add'][$kgoodsid]['weight1']=$goods_info[$kgoodsid]['weight1'];
                        $content['edit'][$void]['goods_add'][$kgoodsid]['size1']=$goods_info[$kgoodsid]['size1'];
                        
                    }
                }
            }else{
                //不存在新增
                $content['add'][$void]=[];
                //添加采购单信息
                
                foreach($edit_base as $kk=>$vv){
                    $content['add'][$void][$vv]=$data[$vv.'0'][$void];
                }
                if(isset($data['nums-'.$void])){
                    foreach ($data['nums-'.$void] as $kgoodsid=>$kv){
                        $content['add'][$void]['goods'][$kgoodsid]=[];
                        $content['add'][$void]['goods'][$kgoodsid]['oid']=0;
                        //添加商品id
                        $content['add'][$void]['goods'][$kgoodsid]['goods']=$kgoodsid;
                        
                        //循环商品信息
                        foreach($edit_goods as $vv){
                            $content['add'][$void]['goods'][$kgoodsid][$vv]=$data[$vv.'s-'.$void][$kgoodsid];
                        }
                        $content['add'][$void]['goods'][$kgoodsid]['goods_name']=$goods_info[$kgoodsid]['goods_name'];
                        $content['add'][$void]['goods'][$kgoodsid]['print_name']=$goods_info[$kgoodsid]['print_name'];
                        $content['add'][$void]['goods'][$kgoodsid]['goods_code']=$goods_info[$kgoodsid]['goods_code'];
                        $content['add'][$void]['goods'][$kgoodsid]['goods_pic']=$goods_info[$kgoodsid]['goods_pic'];
                        $content['add'][$void]['goods'][$kgoodsid]['price_in']=$goods_info[$kgoodsid]['price_in'];
                        $content['add'][$void]['goods'][$kgoodsid]['price_sale']=$goods_info[$kgoodsid]['price_sale'];
                        $content['add'][$void]['goods'][$kgoodsid]['weight1']=$goods_info[$kgoodsid]['weight1'];
                        $content['add'][$void]['goods'][$kgoodsid]['size1']=$goods_info[$kgoodsid]['size1'];
                    }
                }else{
                    $content['add'][$void]['goods']=[];
                }
                
            }
        }
        
        return $content;
        
    }
    
    /**
     * 审核采购单编辑
     * @param array $order
     * @param array $change
     * @return number|string
     */
    public function order_edit_review($order,$change)
    {
        //获取采购单状态信息
        if($order['is_real']==1 ){
            $orders=[$order['id']=>$order];
        }else{
            $orders=$this->where('fid',$order['id'])->column('id,is_real,pay_status,status,sort');
            $orders[$order['id']]=$order;
        }
        $time=time();
        $m_ogoods=Db::name('ordersup_goods');
        $edit_base=['dsc','store','freight','weight','size','pay_freight','real_freight',
            'goods_num','goods_money','discount_money','tax_money','other_money','order_amount','express_no'
            
        ];
        //收货信息，状态信息，子采购单可以单独修改，总采购单修改后同步到子采购单
        $edit_accept=['accept_name','mobile','phone','province','city','area','address','postcode','status','pay_status'];
        
        //总采购单信息系，子采购单不能单独修改，总采购单修改后同步到子采购单
        $edit_fid0=['company','udsc','paytype','pay_type','invoice_type','order_type','ok_break'];
        //记录有采购单变化，需要废弃原出入库的采购单id,重新添加
        $instore_oids=[];
        //新添加采购单号
        $instore_add_oids=[];
        //依次处理change的信息，处理后unset
        //先处理字采购单产品，再处理采购单信息
        if(isset($change['edit'])){
            foreach($change['edit'] as $koid=>$vo){
                $where=['oid'=>$koid];
                //先处理已删除的产品
                if(isset($vo['goods_del'])){
                    $instore_oids[]=$koid;
                    $where['goods']=['in',array_keys($vo['goods_del'])];
                    $m_ogoods->where($where)->delete();
                    unset($vo['goods_del']);
                }
                //编辑产品
                if(isset($vo['goods'])){
                    foreach($vo['goods'] as $kgoods_id=>$vgoods){
                        $where['goods']=$kgoods_id;
                        //产品数量变化要重新出入库
                        if(isset($vgoods['num'])){
                            $instore_oids[]=$koid;
                        }
                        $m_ogoods->where($where)->update($vgoods);
                    }
                    unset($vo['goods']);
                }
                //添加产品
                if(isset($vo['goods_add'])){
                    $instore_oids[]=$koid;
                    $m_ogoods->insertAll($vo['goods_add']);
                    unset($vo['goods_add']);
                }
                $where=['id'=>$koid];
                $this->where($where)->update($vo);
            }
            unset($change['edit']);
        }
        //新增采购单信息只能先保存产品信息，新增采购单，才有单号给产品保存
        if(isset($change['add'])){
            //有新增一定是虚拟主单号了,拆分单号,删除原产品
            if($order['is_real']==1){
                $change['is_real']=2;
                $instore_oids[]=$order['id'];
                $m_ogoods->where('oid',$order['id'])->delete();
            }
            
            //得到子采购单的序号
            $tmp=$this->where('fid',$order['id'])->count();
            
            if(empty($tmp)){
                $tmp=0;
            }
            $goods_adds=[];
            $goods_ids=[];
            foreach($change['add'] as $koid=>$vo){
                //采购单信息,状态待定,跟随主采购单
                $tmp++;
                $data_order=[
                    'order_type'=>$order['order_type'],
                    'aid'=>$order['aid'],
                    'shop'=>$order['shop'],
                    'company'=>$order['company'],
                    'uid'=>$order['uid'],
                    'udsc'=>$order['udsc'],
                    'create_time'=>$time,
                    'time'=>$time,
                    'sort'=>$order['sort'],
                    'status'=>$order['status'],
                    'fid'=>$order['id'],
                    'name'=>$order['name'].'_'.$tmp,
                ];
                //收货人信息
                foreach ($edit_accept as $v){
                    $data_order[$v]=(isset($change[$v]))?$change[$v]:$order[$v];
                }
                //总单信息
                foreach ($edit_fid0 as $v){
                    $data_order[$v]=(isset($change[$v]))?$change[$v]:$order[$v];
                }
                //子单信息
                foreach ($edit_base as $v){
                    $data_order[$v]=$vo[$v];
                }
                $tmp_oid=$this->insertGetId($data_order);
                $instore_add_oids[]=$tmp_oid;
                //产品新增
                if(isset($vo['goods'])){
                    foreach($vo['goods'] as $kgoods_id=>$vgoods){
                        $vgoods['oid']=$tmp_oid;
                        $goods_adds[]=$vgoods;
                    }
                }
            }
            if(!empty($goods_adds)){
                //最后统一新增产品
                $m_ogoods->insertAll($goods_adds);
            }
            
            unset($change['add']);
        }
        //支付账号信息
        if(isset($change['pay'])){
            $m_pay=new OrdersPayModel();
            if(empty($order['pay_id'])){
                $change['pay_id']=$m_pay->pay_add($change['pay']);
            }else{
                $change['pay']['id']=$order['pay_id'];
                $m_pay->pay_update($change['pay']);
                
            }
            unset($change['pay']);
        }
        //发票信息
        if(isset($change['invoice'])){
            $m_invoice=new OrdersInvoiceModel();
            if(empty($order['invoice_id'])){
                $change['invoice_id']=$m_invoice->invoice_add($change['invoice']);
            }else{
                $change['invoice']['id']=$order['invoice_id'];
                $m_invoice->invoice_update($change['invoice']);
                
            }
            unset($change['invoice']);
        }
        $update_info=['time'=>$time];
        
        foreach($change as $k=>$v){
            $update_info[$k]=$v;
        }
        if(isset($change['is_real'])){
            $order['is_real']=$change['is_real'];
        }
        if(isset($change['status'])){
            $order['status']=$change['status'];
        }
        if(isset($change['pay_status'])){
            $order['pay_status']=$change['pay_status'];
        }
        if($order['status']==26 && $order['pay_status']==3){
            $update_info['status']=30;
        }
        
        $this->where('id',$order['id'])->update($update_info);
        //有子采购单,同步
        if($order['is_real']==2 || isset($change['add']) ){
            foreach($update_info as $k=>$v){
                if(in_array($k,$edit_base)){
                    unset($update_info[$k]);
                }
            }
            if(isset($change['is_real'])){
                unset($update_info['is_real']);
            }
            if(!empty($update_info)){
                $this->where('fid',$order['id'])->update($update_info);
            }
            
        }
        
        
        
        //检查库存,删除旧入库，添加新入库s
        if(!empty($instore_oids) && $order['status']>22 ){
            //有产品数量变化的
            $instore_oids=array_unique($instore_oids);
            foreach($instore_oids as $v){
                $res=$this->order_storein5($v);
                if($res!==1){
                    return $res;
                }
            }
            //新添加采购单号
            $instore_add_oids=array_merge($instore_add_oids,$instore_oids);
            foreach($instore_add_oids as $v){
                $res=$this->order_storein0($v);
                if($res!==1){
                    return $res;
                }
            }
        }
        
        ///$order
        //更新用户数据
        $this->custom_update($order['uid']);
        return 1;
    }
    /* 采购单是否可以编辑 */
    public function order_edit_auth($order,$admin){
        if($order['status']==1 && $order['aid']!=$admin['id']){
            return '不能修改他人的未提交采购单的';
        }
        //是否有待审核
        $where=[
            'pid'=>['eq',$order['id']],
            'table'=>['eq','order'],
            'rstatus'=>['eq',1],
        ];
        $aids=Db::name('edit')
        ->where($where)
        ->value('aid');
        if(!empty($aids) && $admin['id']!=$aids){
            return '采购单有修改，需等待审核';
        }
        //创建人能修改采购单
        if($admin['id']==$order['aid'] ){
            return 1;
        }
        //创建人的经理或是总部门经理
        if($admin['job']==1){
            if($admin['department']==1){
                return 1;
            }else{
                $department=Db::name('user')->where('id',$order['aid'])->value('department');
                //创建人不存在或是部门经理
                if(empty($department) || $department==$admin['department']){
                    return 1;
                }
            }
        }
        //在order_aid表中可以
        $where=[
            'oid'=>$order['id'],
            'aid'=>$admin['id'],
            'type'=>1,5
        ];
        $tmp=Db::name('order_aid')->where($where)->find();
        if(!empty($tmp)){
            return 1;
        }
        return '无权限查看该定采购单';
    }
   
    /* 采购单排序 */
    public function order_sort($id){
       
        // sort排序，转运营10，准备发货9，线下待确认货款8，线下待付款7，待确认和待提交6，淘宝待发货5，淘宝准备发货4，淘宝待确认货款3，淘宝待付款2，淘宝错误1，其他按时间顺序排
        // sort排序，转运营10，已准备收货9，已发货8，待发货7，待确认和待提交6，付款待确认3，待付款2，其他按时间顺序排
        
        $order=$this->where('id',$id)->find();
        
        /*  
        'ordersup_status' => 
  array (
    1 => '未提交',
    2 => '提交待确认',
    10 => '待付款',
    20 => '待发货',
    22 => '已发货',
    24 => '已准备收货',
    26 => '已收货',
    30 => '订单完成',
    70 => '订单关闭',
    80 => '已取消',
    81 => '已废弃',
  ),  // sort排序，转运营10，已准备收货9，已发货8，待发货7，待确认和待提交6，付款待确认3，待付款2，其他按时间顺序排
         ), */
        if($order['order_type']==2){
            $sort=10;
        }else{
            switch ($order['status']){
                
                case 24:
                    $sort=9;
                    break;
                case 22:
                    $sort=8;
                    break;
                case 20:
                    $sort=7;
                    break;
                case 2: 
                case 1:
                    $sort=6;
                    break;
                case 10:
                    switch ($order['pay_status']){
                        case 1:
                            $sort=3;
                            break;
                        case 2:
                            $sort=2;
                            break;
                        default:
                            break;
                    }
                    break; 
                default:
                    $sort=0;
                    break;
            }
        } 
        if($order['sort']!=$sort){
            $this->where('id',$id)->setField('sort',$sort);
        }
        //检查是否更新父级,且订单完成
        if($order['fid']>0 && $order['status']>=30){
            //如果子订单全部完成，则父级完成
            $where_child=[
                'fid'=>$order['fid'],
                'status'=>['lt',30],
            ];
            $tmp=$this->where($where_child)->find();
            if(empty($tmp)){
                $update=[
                    'sort'=>0,
                    'time'=>time(),
                    'status'=>30,
                ];
                $where=[
                    'id'=>$order['fid'],
                    'status'=>['lt',30]
                ];
                $this->where($where)->update($update);
            }
        }
        return 1;
        
    }
    /* 采购单产品数量检查 */
    public function order_store($id){
        //采购不检查库存
        return 1;
        //在store_goods表中num1数值减少
        $order=$this->where('id',$id)->find();
        if($order['is_real']==2){
            return '已拆分采购单请分开收货';
        }
        $goods_order=Db::name('ordersup_goods')->where('oid',$id)->column('goods,goods_name,num');
        if(empty($goods_order)){
            return 1;
        }
        $goods_ids=array_keys($goods_order);
        $where=[
            'store'=>['eq',$order['store']],
            'goods'=>['in',$goods_ids],
        ];
        $goods_store=Db::name('store_goods')->where($where)->column('goods,num');
        foreach($goods_order as $k=>$v){
            if(!isset($goods_store[$k]) || $goods_store[$k]<$v['num']){
                return $v['goods_name'].'库存不足';
            }
        }
        return 1;
    }
    
    /**
     * 采购单提交配货申请,只能是实际单号提交
     * @param number $id 
     * @return number|string
     */
    public function order_storein0($id){
        //在store_goods表中num1数值减少
        $order=$this->where('id',$id)->find();
        $order=$order->data;
        
        //采购单产品
        $where_goods=[];
        if($order['is_real']==1){
            $where_goods['oid']=['eq',$order['id']];
        }else{
            return '虚拟主单号不能收货';
        }
        //全部采购单产品
        $goods_order=Db::name('ordersup_goods')
        ->where($where_goods)
        ->column('id,goods,num');
        
        if(empty($goods_order)){
            return 1;
        }
        $goods_ids=[];
        $m_store_goods=new StoreGoodsModel();
        $time=time();
        $aid=session('ADMIN_ID');
        //一个个地入库
        foreach($goods_order as $k=>$v){
            if($order['store']==0){
                return '没有选择仓库';
            }
            
            $data=[
                'shop'=>$order['shop'],
                'store'=>$order['store'],
                'goods'=>$v['goods'],
                'num'=>$v['num'],
                'atime'=>$time,
                'aid'=>$aid,
                'adsc'=>'采购入库',
                'rstatus'=>4,
                'type'=>1,
                'about'=>$order['id'],
                'about_name'=>$order['name'],
            ];
            $res=$m_store_goods->instore0($data);
            if(!($res>0)){
                return $res;
            }
        }
        
        return 1;
    }
    
    
    /**
     *  采购单收货后，入库记录批量同意
     * @param number $id 
     * @return string|number
     */
    public function order_storein2($id,$dsc='采购统一入库'){
        
        //出入库记录要变为已同意
        $where=[
            'type'=>1,
            'about'=>$id,
            'rstatus'=>1,
        ];
        $list=Db::name('store_in')->where($where)->column('');
        
        $m_store_goods=new StoreGoodsModel();
        foreach($list as $k=>$v){
            $res=$m_store_goods->instore2($v);
            if(!($res>0)){
                return $res;
            }
        }
        $in_ids=array_keys($list);
        $update_info=[
            'rstatus'=>2,
            'rtime'=>time(),
            'rdsc'=>$dsc,
            'rid'=>empty(session('ADMIN_ID'))?1:session('ADMIN_ID'),
        ];
        Db::name('store_in')->where('id','in',$in_ids)->update($update_info);
        return 1;
    }
    
    /**
     * 采购单确认收货要检查入库记录是否都已审
     * @param number $id
     * @return string|number
     */
    public function order_storein_check($id){
        
        $order=$this->where('id',$id)->find();
        $order=$order->data;
        
        if($order['is_real']!=1){
            return '已拆分采购单请在子采购单页面收货';
        }
        
        $m_store_in=Db::name('store_in');
        $where=[
            'type'=>1,
            'about'=>$id,
            'rstatus'=>3,
        ];
        
        $goods_store=$m_store_in->where($where)->order('goods')->column('goods,num');
        $goods_order=Db::name('ordersup_goods')->where('oid',$id)->order('goods')->column('goods,num');
        foreach($goods_order as $k=>$v){
            if(!isset($goods_store[$k]) || $goods_store[$k] != $v){
                return '入库不完全';
            }
        }
        
        return 1;
        
    }
    /* 采购单改变后废弃原入库记录 */
    public function order_storein5($id,$dsc='采购单变化，废弃原出入库'){
        
        $order=$this->where('id',$id)->find();
        $order=$order->data;
        
        //采购单产品
        $where_about=['type'=>1];
        
        if($order['is_real']==1){
            $where_about['about']=['eq',$order['id']];
        }else{
            $order_ids=$this->where('fid',$order['id'])->column('id');
            $where_about['about']=['in',$order_ids];
        }
        //获取所有出入库记录
        $instores=Db::name('store_in')->where($where_about)->column('');
        if(empty($instores)){
            return 1;
        }
        
        $m_store_goods=new StoreGoodsModel();
        //一个个地废弃
        foreach($instores as $k=>$v){
            $res=$m_store_goods->instore5($v);
            if($res!==1){
                return $res;
            }
        }
        $in_ids=array_keys($instores);
        $update_info=[
            'rstatus'=>5,
            'rtime'=>time(),
            'rdsc'=>$dsc,
            'rid'=>session('ADMIN_ID'),
        ];
        Db::name('store_in')->where('id','in',$in_ids)->update($update_info);
        return 1;
    }
    
    
    /**
     * 检查产品的更新操作是否合法
     * @param array $order
     * @param array $change
     * @return number
     */
    public function is_option($order,$change){
        
        
        return 1;
    }
    
    /**
     * 得到采购单的产品详情,返回字采购单和产品详情
     * @param array $info
     * @param int $aid
     * @param array $change
     * @return array[]
     */
    public function order_goods($info,$aid,$change=[]){
        //采购单产品
        $where_goods=[];
        if($info['is_real']==1){
            $where_goods['oid']=['eq',$info['id']];
            $orders=[$info['id']=>$info];
        }else{
            $fields='id,name,freight,store,weight,size,discount_money,goods_num,goods_money,pay_freight,order_type'.
                ',real_freight,other_money,tax_money,order_amount,dsc,express_no,status,pay_status';
            $orders=$this->where('fid',$info['id'])->column($fields);
            
            $order_ids=array_keys($orders);
            $where_goods['oid']=['in',$order_ids];
        }
        //全部采购单产品
        $ordersup_goods=Db::name('ordersup_goods')
        ->where($where_goods)
        ->column('');
        
        //检查用户权限
        $authObj = new \cmf\lib\Auth();
        $name       = strtolower('goods/AdminGoodsauth/price_in_get');
        $is_auth=$authObj->check($aid, $name);
        //数据转化，按采购单分组
        $infos=[];
        $goods_id=[];
        $goods=[];
        foreach($ordersup_goods as $k=>$v){
            $goods_id[$v['goods']]=$v['goods'];
            $goods[$v['goods']]=[];
            if($is_auth==false){
                $v['price_in']='--';
            }
            
            $infos[$v['oid']][$v['goods']]=$v;
        }
        
        //要修改的采购单中是否有新增产品
        if(isset($change['edit'])){
            foreach($change['edit'] as $k=>$v){
                if(isset($v['goods_add'])){
                    foreach($v['goods_add'] as $kk=>$vv){
                        $goods_id[$vv['goods']]=$vv['goods'];
                    }
                }
            }
        }
        //新增采购单中检查是否有新产品
        if(isset($change['add'])){
            foreach($change['add'] as $k=>$v){
                if(isset($v['goods'])){
                    foreach($v['goods'] as $kk=>$vv){
                        if(!isset($goods_id[$vv['goods']])){
                            $goods_id[$vv['goods']]=$vv['goods'];
                        }
                    }
                }
            }
        }
        
        //获取产品图片
        $where=[
            'pid'=>['in',$goods_id],
            'type'=>['eq',1],
        ];
        $pics=Db::name('goods_file')->where($where)->column('id,pid,file');
        $path=cmf_get_image_url('');
        foreach($pics as $k=>$v){
            $goods[$v['pid']]['pics'][]=[
                'file1'=>$v['file'].'1.jpg',
                'file3'=>$v['file'].'3.jpg',
            ];
        }
        
        //获取所有库存
        $where=[
            'goods'=>['in',$goods_id],
            'shop'=>['eq',$info['shop']],
        ];
        $list=Db::name('store_goods')->where($where)->column('id,store,goods,num,num1');
        
        //循环得到数据
        foreach($list as $k=>$v){
            $goods[$v['goods']]['nums'][$v['store']]=[
                'num'=>$v['num'],
                'num1'=>$v['num1'],
            ];
        }
        
        return ['orders'=>$orders,'goods'=>$goods,'infos'=>$infos];
    }
    /**
     * 把产品的重量和体积统一转化
     * @param array $goods
     * @return array
     */
    public function unit_change($goods){
        //判断产品重量体积单位,统一转化为kg,cm3
        switch($goods['type']){
            case 5:
                //设备kg,m
                $goods['weight1']=$goods['weight1'];
                $goods['size1']=bcmul($goods['size1'],1000000,2);
                break;
            default:
                //其他g,cm
                $goods['weight1']=bcdiv($goods['weight1'],1000,2);
                $goods['size1']=$goods['size1'];
                break;
        }
        $goods['weight1']=($goods['weight1']==0)?0.01:$goods['weight1'];
        $goods['size1']=($goods['size1']==0)?0.01:$goods['size1'];
        return $goods;
    }
    /**
     * 根据现有的状态判断是否做入库操作
     * @param number $oid
     * @param number $old_status
     * @param number $num_ok是否严格检查库存
     * @return number
     */
    public function status_change($oid,$old_status=1){
        
        $res=1;
        $order=$this->where('id',$oid)->find();
        $order=$order->data;
      
        if($order['status']==$old_status){
            return 1;
        }
        if($order['order_type']!=1){
            return '状态更新前请先更新为采购单';
        }
        //状态判断24准备入库，26,30入库
        if($order['status']<24 || $order['status']>=80){
            //已收货的废弃
            if($old_status>=24 && $old_status<=30){
                $res=$this->order_storein5($order['id']);
            }
        }elseif($order['status']==24){
            if($old_status>24 && $old_status<80){
                //已收货的，废弃
                $res=$this->order_storein5($order['id']);
                if(!($res>0)){
                    return $res;
                }
            }
            //应该准备收货的
            $res=$this->order_storein0($order['id']);
        }elseif($order['status']<=30){
            //已收货
            if($old_status<24){
                //没准备收货的先添加入库记录
                $res=$this->order_storein0($order['id']);
                if(!($res>0)){
                    return $res;
                }
            }
            //审核入库
            $res=$this->order_storein2($order['id']);
        }
        //已收货的跟新is_pay_freight,已结算的不能动，已收货的统一改为2，其他为1
        //采购收货不统计快递费用
        return $res;
    } 
    /**
     * 更新用户的订购数和金额
     * $uid用户
     */
    public function custom_update($uid){
        $where=[
            'uid'=>$uid,
            'status'=>30
        ];
        //已完成采购单
        $order_do1=$this->where($where)->field('count(id) as nums,sum(order_amount) as moneys')->find();
        //已收货未付款采购单
        $where=[
            'uid'=>$uid,
            'status'=>26
        ];
        $order_do0=$this->where($where)->field('count(id) as nums,sum(order_amount) as moneys')->find();
        $update=[
            'order_num'=>$order_do1['nums'],
            'order_money'=>empty($order_do1['moneys'])?0:$order_do1['moneys'],
            'order_num0'=>$order_do0['nums'],
            'order_money0'=>empty($order_do0['moneys'])?0:$order_do0['moneys'],
            'time'=>time(),
        ];
        
        Db::name('supplier')->where('id',$uid)->update($update);
    }
    
}
