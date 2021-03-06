<?php
 
 

use think\Model;
use think\Db;
use app\store\model\StoreGoodsModel;
use app\money\model\OrdersInvoiceModel;
use app\money\model\OrdersPayModel;
class Ordersup33Model extends Model
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
     /* 采购单编辑 */
     public function ordersup_edit($info,$data,$is_do=0)
     {
          
         $content=[];
         //检测改变了哪些字段 
         //所有采购单都有,但只有虚拟采购单会记录,真实采购单在子采购单里记录
         $edit_base=['dsc','store','freight','weight','size','pay_freight','real_freight','order_type',
             'goods_num','goods_money','discount_money','tax_money','other_money','order_amount','express_no'
             
         ];
         //收货信息，子采购单可以单独修改，总采购单修改后同步到子采购单
         $edit_accept=['accept_name','mobile','phone','province','city','area','address','postcode',];
          
         //总采购单信息系
         $edit_fid0=['company','udsc','paytype','invoice_type','pay_type'];
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
         //主订单才有发票和付款信息
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
                     $content['invoice']['oid_type']= 2;
                     $content['invoice']['ptype']= 2;
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
                     $content['pay']['oid_type']= 2;
                     $content['pay']['ptype']= 2;
                     
                 }
             }
         }
         //获取原采购单和采购单产品
         $where_goods=[];
         if($info['is_real']==1 ){
             $where_goods['oid']=['eq',$info['id']];
             $orders=[$info['id']=>$info];
             $ordersup_ids=[$info['id']];
         }else{
          /*    $fields='id,name,freight,store,weight,size,discount_money,goods_num,goods_money,pay_freight'.
                 ',real_freight,other_money,tax_money,order_amount,dsc'; */
             $fields='id,name,'.(implode(',',$edit_base));
             $orders=$this->where('fid',$info['id'])->column($fields);
             
             $ordersup_ids=array_keys($orders);
             
             $where_goods['oid']=['in',$ordersup_ids];
         }
         //全部采购单产品
         $ordersup_goods=Db::name('ordersup_goods')
         ->where($where_goods)
         ->column('');
         //数据转化，按采购单分组
         $infos=[];
         $goods_info=[];
         foreach($ordersup_goods as $k=>$v){
             $infos[$v['oid']][$v['goods']]=$v;
             $goods_info[$v['goods']]=$v;
         }
         //子采购单nums-{$kk}[{$key}],只有在主采购单下才能拆分采购单
         
         /*  $edit_base=['dsc','store','freight','weight','size','pay_freight','real_freight',
          'goods_num','goods_money','discount_money','tax_money','other_money','order_amount',
          ]; */
        
         $edit_goods=['num','pay','weight','size','dsc','price_real','pay_discount'];
          
         //多个要一个个比较,先比较是否存在
         foreach($data['oids'] as $k=>$void){
             if(in_array($void,$ordersup_ids)){
                 //编辑采购单信息
                 foreach($edit_base as $kk=>$vv){
                     if($orders[$void][$vv]!=$data[$vv.'0'][$void]){
                         $content['edit'][$void][$vv]=$data[$vv.'0'][$void];
                     }
                     
                 }
                 //一个个比较产品，只有删除，没有新增
                 foreach ($infos[$void] as $kgoodsid=>$kv){ 
                     //data不存在就是没有该产品了,删除
                     if(!isset($data['nums-'.$void][$kgoodsid]) ){
                         $content['edit'][$void]['goods'][$kgoodsid]['del']=1;
                         continue;
                     }
                     //循环商品信息
                     foreach($edit_goods as $vv){
                         if($data[$vv.'s-'.$void][$kgoodsid] !=  $infos[$void][$kgoodsid][$vv]){
                             $content['edit'][$void]['goods'][$kgoodsid][$vv]=$data[$vv.'s-'.$void][$kgoodsid];
                         } 
                     } 
                 }
                 
             }else{
                 //不存在新增
                 $content['add'][$void]=[];
                 //添加采购单信息 
                 foreach($edit_base as $kk=>$vv){ 
                     $content['add'][$void][$vv]=$data[$vv.'0'][$void];
                 }
                 foreach ($data['nums-'.$void] as $kgoodsid=>$kv){
                     //添加商品id
                     $content['add'][$void]['goods'][$kgoodsid]['goods']=$kgoodsid;
                     //循环商品信息
                     foreach($edit_goods as $vv){
                         $content['add'][$void]['goods'][$kgoodsid][$vv]=$data[$vv.'s-'.$void][$kgoodsid]; 
                     }
                     $content['add'][$void]['goods'][$kgoodsid]['goods_name']=$goods_info[$kgoodsid]['goods_name']; 
                     $content['add'][$void]['goods'][$kgoodsid]['goods_code']=$goods_info[$kgoodsid]['goods_code']; 
                     $content['add'][$void]['goods'][$kgoodsid]['goods_uname']=$goods_info[$kgoodsid]['goods_uname']; 
                     $content['add'][$void]['goods'][$kgoodsid]['goods_ucate']=$goods_info[$kgoodsid]['goods_ucate']; 
                     $content['add'][$void]['goods'][$kgoodsid]['goods_pic']=$goods_info[$kgoodsid]['goods_pic']; 
                     $content['add'][$void]['goods'][$kgoodsid]['price_in']=$goods_info[$kgoodsid]['price_in']; 
                     $content['add'][$void]['goods'][$kgoodsid]['price_sale']=$goods_info[$kgoodsid]['price_sale']; 
                     $content['add'][$void]['goods'][$kgoodsid]['weight1']=$goods_info[$kgoodsid]['weight1']; 
                     $content['add'][$void]['goods'][$kgoodsid]['size1']=$goods_info[$kgoodsid]['size1']; 
                     
                 } 
             }
         }
         if(isset($change['add']) && $info['status']>=26){
             return '已收货不能拆分';
         }
         if($is_do==1){
             if($info['status']==1 && $data['status']==2){
                 $content['status']=2;
             }
             $row=$this->ordersup_edit_review($info,$content);
             return $row;
         }else{
             return $content;
         }
         
     }
     /* 审核采购单编辑 */
     public function ordersup_edit_review($ordersup,$change)
     {
         if(isset($change['add']) && $ordersup['status']>=26){
             return '已收货不能拆分';
         }
         //获取采购单状态信息
         if($ordersup['is_real']==1 ){ 
             $orders=[$ordersup['id']=>$ordersup]; 
         }else{ 
             $orders=$this->where('fid',$ordersup['id'])->column('id,is_real,pay_status,status,sort');
             $orders[$ordersup['id']]=$ordersup; 
         }
         $time=time();
         $m_ogoods=Db::name('ordersup_goods');
         $edit_base=['dsc','store','freight','weight','size','pay_freight','real_freight','order_type',
             'goods_num','goods_money','discount_money','tax_money','other_money','order_amount','express_no'
             
         ];
         //收货信息，状态信息，子采购单可以单独修改，总采购单修改后同步到子采购单
         $edit_accept=['accept_name','mobile','phone','province','city','area','address','postcode','status','pay_status'];
         
         //总采购单信息系，子采购单不能单独修改，总采购单修改后同步到子采购单
         $edit_fid0=['company','udsc','paytype','pay_type','invoice_type'];
         //记录有采购单拆分，需要废弃原出入库的采购单id,重新添加
         $instore_oids=[];
         //新添加采购单号
         $instore_add_oids=[];
         //依次处理change的信息，处理后unset
         //先处理字采购单产品，再处理采购单信息
         if(isset($change['edit'])){
             foreach($change['edit'] as $koid=>$vo){
                 $where=['oid'=>$koid];
                 if(isset($vo['goods'])){
                     foreach($vo['goods'] as $kgoods_id=>$vgoods){
                         $where['goods']=$kgoods_id;
                        
                         if(isset($vgoods['del'])){
                             $instore_oids[]=$koid;
                             $m_ogoods->where($where)->delete();
                             continue;
                         }
                         if(isset($vgoods['num'])){
                             $instore_oids[]=$koid;
                         }
                         $m_ogoods->where($where)->update($vgoods);
                     }
                     unset($vo['goods']);
                 }
                 $where=['id'=>$koid];
                 $this->where($where)->update($vo);
             }
             unset($change['edit']);
         }
         //新增采购单信息只能先保存产品信息，新增采购单，才有单号给产品保存
         if(isset($change['add'])){
             //有新增一定是虚拟主单号了,拆分单号,删除原产品
             if($ordersup['is_real']==1){
                 $change['is_real']=2;
                 
                 $m_ogoods->where('oid',$ordersup['id'])->delete();
             }
            
             //得到子采购单的序号
             $tmp=$this->where('fid',$ordersup['id'])->count();
             
             if(empty($tmp)){
                 $tmp=0;
             }
             $goods_adds=[];
             $goods_ids=[];
             foreach($change['add'] as $koid=>$vo){ 
                 //采购单信息,状态待定,跟随主采购单
                 $tmp++; 
                 $data_ordersup=[
                     'order_type'=>$ordersup['order_type'],
                     'aid'=>$ordersup['aid'],
                     'shop'=>$ordersup['shop'],
                     'company'=>$ordersup['company'],
                     'uid'=>$ordersup['uid'], 
                     'udsc'=>$ordersup['udsc'], 
                     'create_time'=>$time,
                     'time'=>$time,
                     'sort'=>$ordersup['sort'],
                     'status'=>$ordersup['status'],
                     'fid'=>$ordersup['id'],
                     'name'=>$ordersup['name'].'_'.$tmp, 
                 ];
                 //收货人信息
                 foreach ($edit_accept as $v){
                     $data_ordersup[$v]=(isset($change[$v]))?$change[$v]:$ordersup[$v]; 
                 }
                 //总单信息
                 foreach ($edit_fid0 as $v){
                     $data_ordersup[$v]=(isset($change[$v]))?$change[$v]:$ordersup[$v];
                 }
                 //子单信息
                 foreach ($edit_base as $v){
                     $data_ordersup[$v]=$vo[$v];
                 }
                 $tmp_oid=$this->insertGetId($data_ordersup);
                 $instore_add_oids[]=$tmp_oid;
                 //产品新增,给oid
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
                 $change['pay']['id']=$ordersup['pay_id'];
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
                 $change['invoice']['id']=$ordersup['invoice_id'];
                 $m_invoice->invoice_update($change['invoice']);
                 
             }
             unset($change['invoice']);
         }
         $update_info=['time'=>$time];
        
         foreach($change as $k=>$v){
             $update_info[$k]=$v;
         }
         
         $this->where('id',$ordersup['id'])->update($update_info);
         //有子采购单,同步
         if($ordersup['is_real']==2 || isset($change['add']) ){
             foreach($update_info as $k=>$v){
                  if(in_array($k,$edit_base)){
                      unset($update_info[$k]);
                  }
              }
              if(isset($change['is_real'])){
                  unset($update_info['is_real']);
              }
              if(!empty($update_info)){
                  $this->where('fid',$ordersup['id'])->update($update_info); 
              }
             
         }
         
         
          
         //检查库存,删除旧入库，添加新入库
         if(!empty($instore_oids)){
             //有产品数量变化的  
             $instore_oids=array_unique($instore_oids);
             foreach($instore_oids as $v){
                 $res=$this->ordersup_storein5($v);
                 if($res!==1){
                     return $res;
                 }
             }
             //新添加采购单号
             $instore_add_oids=array_merge($instore_add_oids,$instore_oids);
             foreach($instore_add_oids as $v){
                 $res=$this->ordersup_storein0($v);
                 if($res!==1){
                     return $res;
                 }
             } 
         } 
         return 1; 
     }
     /* 采购单是否可以编辑 */
     public function ordersup_edit_auth($ordersup,$admin){
         if($ordersup['status']==1 && $ordersup['aid']!=$admin['id']){
             return '不能修改他人的未提交采购单的';
         }
         //是否有待审核
         $where=[
             'pid'=>['eq',$ordersup['id']],
             'table'=>['eq','ordersup'],
             'rstatus'=>['eq',1],
         ];
         $aid=Db::name('edit')
         ->where($where) 
         ->value('aid');
         if(!empty($aid) && $admin['id']!=$aid){
             return '采购单有修改，需等待审核';
         }
         //创建人能修改采购单
         if($admin['id']==$ordersup['aid'] ){
             return 1;
         }
         //创建人的经理或是总部门经理
         if($admin['job']==1){
             if($admin['department']==1){
                 return 1;
             }else{
                 $department=Db::name('user')->where('id',$ordersup['aid'])->value('department');
                 //创建人不存在或是部门经理
                 if(empty($department) || $department==$admin['department']){
                     return 1;
                 }
             }
         }
         //在ordersup_aid表中可以
         $where=[
             'oid'=>$ordersup['id'],
             'aid'=>$admin['id'],
             'type'=>2,
         ];
         $tmp=Db::name('order_aid')->where($where)->find();
         if(!empty($tmp)){
             return 1;
         }
         return '无权限查看该定采购单';
     }
     /* 采购单排序 */
     public function ordersup_sort($id){
         //   sort专门排序，待收货10，仓库收货9，管理员有改动8，员工有改动7，待付款4，待确认货款5，退货退款中3，未提交2，其他0 
         //pay_status
         //是否有待审核
         $where=[
             'edit.pid'=>['eq',$id],
             'edit.table'=>['eq','ordersup'],
             'edit.rstatus'=>['eq',1],
         ];
         $aids=Db::name('edit')
         ->alias('edit')
         ->join('cmf_user user','user.id=edit.aid')
         ->where($where)
         ->column('user.job,edit.aid');
         $sort=0;
         //管理员有改动8，员工有改动7，
         if(isset($aids[1])){
             $sort=8;
         }elseif(isset($aids[2])){
             $sort=7;
         }else{
             $ordersup=$this->where('id',$id)->find();
             if($ordersup['order_type']==2){
                 $sort=11;
             }else{
                 switch ($ordersup['status']){
                     case 22:
                         $sort=10;
                         break;
                     case 24:
                         $sort=9;
                         break;
                     case 10:
                         switch ($ordersup['pay_status']){
                             case 1:
                                 $sort=4;
                                 break;
                             case 2:
                                 $sort=5;
                                 break;
                             case 4:
                                 $sort=3;
                                 break;
                             default:
                                 break;
                         }
                         break;
                     case 40:
                         $sort=3;
                         break;
                     case 2:
                         $sort=2;
                         break;
                     case 1:
                         $sort=1;
                         break;
                     default:
                         break;
                 }
             }
              
         }
         $this->where('id',$id)->setField('sort',$sort);
           
     }
     
     /* 采购单确认后，产品入库未提交 */
     public function ordersup_storein0($id,$dsc='采购入库'){
         //在store_goods表中num1数值减少
         $ordersup=$this->where('id',$id)->find();
         $ordersup=$ordersup->data;
         if($ordersup['status']<10 || $ordersup['status']>20){
             return '采购单状态错误';
         }
         //采购单产品
         $where_goods=[];
         if($ordersup['is_real']==1){
             $where_goods['oid']=['eq',$ordersup['id']]; 
             $orders=[$ordersup['id']=>['id'=>$ordersup['id'],'name'=>$ordersup['name'],'store'=>$ordersup['store']]];
         }else{ 
             $orders=$this->where('fid',$ordersup['id'])->column('id,name,store'); 
             $ordersup_ids=array_keys($orders);
             $where_goods['oid']=['in',$ordersup_ids];
         }
         //全部采购单产品
         $goods_ordersup=Db::name('ordersup_goods')
         ->where($where_goods)
         ->column('id,goods,num,oid');
           
         if(empty($goods_ordersup)){
             return 1;
         }
         $goods_ids=[];
         $m_store_goods=new StoreGoodsModel();
         $time=time();
         $aid=session('ADMIN_ID');
         //一个个地入库
         foreach($goods_ordersup as $k=>$v){
             if($orders[$v['oid']]['store']==0){
                 continue;
             }
             $data=[
                 'shop'=>$ordersup['shop'],
                 'store'=>$orders[$v['oid']]['store'],
                 'goods'=>$v['goods'],
                 'num'=>$v['num'],
                 'atime'=>$time,
                 'aid'=>$aid,
                 'adsc'=>$dsc,
                 'rstatus'=>4,
                 'type'=>1,
                 'about'=>$v['oid'],
                 'about_name'=>$orders[$v['oid']]['name'],
             ];
             $res=$m_store_goods->instore0($data);
             if(!($res>0)){
                 return $res;
             }
         }
         
         return 1;
     }
     /**
      *  采购单准备收货后，入库记录可审核 */
     public function ordersup_storein1($id){
         //在store_goods表中num1数值减少
         $ordersup=$this->where('id',$id)->find();
         $ordersup=$ordersup->data;
         if($ordersup['status']!=24){
             return '采购单状态错误';
         }
         if($ordersup['is_real']!=1){
             return '已拆分采购单请在子采购单页面收货';
         }
          
         //出入库记录要变为待审核
         $where=[
             'type'=>1,
             'about'=>$id,
             'rstatus'=>4,
         ];
         $update=[ 
             'rstatus'=>1,
         ];
         Db::name('store_in')->where($where)->update($update);
         return 1;
     }
     /**
      *  采购单确认收货要检查入库记录是否都已审核 */
     public function ordersup_storein_check($id){
         //在store_goods表中num1数值减少
         $ordersup=$this->where('id',$id)->find();
         $ordersup=$ordersup->data;
         
         if($ordersup['is_real']!=1){
             return '已拆分采购单请在子采购单页面收货';
         }
         
         $m_store_in=Db::name('store_in');
         $where=[
             'type'=>1,
             'about'=>$id,
             'rstatus'=>2,
         ];
          
         $goods_store=$m_store_in->where($where)->order('goods')->column('goods,num');
         $goods_ordersup=Db::name('ordersup_goods')->where('oid',$id)->order('goods')->column('goods,num');
         foreach($goods_ordersup as $k=>$v){
             if(!isset($goods_store[$k]) || $goods_store[$k] != $v){
                 return '入库不完全';
             }
         } 
         return 1;
         
     }
     /* 采购单改变后废弃原入库记录 */
     public function ordersup_storein5($id,$dsc='采购单变化，废弃原出入库'){
         //在store_goods表中num1数值减少
         $ordersup=$this->where('id',$id)->find();
         $ordersup=$ordersup->data;
         
         //采购单产品
         $where_about=['type'=>1];
         
         if($ordersup['is_real']==1){
             $where_about['about']=['eq',$ordersup['id']]; 
         }else{
             $ordersup_ids=$this->where('fid',$ordersup['id'])->column('id'); 
             $where_about['about']=['in',$ordersup_ids];
         }
         //获取所有出入库记录
         $instores=Db::name('store_in')->where($where_about)->column('id,store,shop,goods,num');
         if(empty($instores)){
             return 1;
         }
         $in_ids=array_keys($instores);
         $m_store_goods=new StoreGoodsModel();
         foreach($instores as $k=>$v){
             $res=$m_store_goods->instore5($v);
             if($res!==1){
                 return $res;
             }
         } 
         $update_info=[
             'rstatus'=>5,
             'time'=>time(),
             'rdsc'=>$dsc,
             'rid'=>session('ADMIN_ID'),
         ];
         Db::name('store_in')->where('id','in',$in_ids)->update($update_info);
         return 1;
     }
     
     /* 检查产品的更新操作是否合法 */
     public function is_option($ordersup,$change){
         
         
         return 1;
     }
     /**
      * 得到采购单的产品详情,返回字采购单和产品详情
      * */
     public function ordersup_goods($info,$aid){
         //采购单产品
         $where_goods=[];
         if($info['is_real']==1){
             $where_goods['oid']=['eq',$info['id']];
             $orders=[$info['id']=>$info];
         }else{
             $fields='id,name,freight,store,weight,size,discount_money,goods_num,goods_money,pay_freight'.
                 ',real_freight,other_money,tax_money,order_amount,dsc,express_no,order_type,status,pay_status';
             $orders=$this->where('fid',$info['id'])->column($fields);
             
             $ordersup_ids=array_keys($orders);
             $where_goods['oid']=['in',$ordersup_ids];
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
      * 更新供应商的订购数和金额
      * $uid用户
      */
     public function custom_update($uid){
         $where=[
             'uid'=>$uid,
             'status'=>30
         ];
         //已完成订单
         $order_do1=$this->where($where)->field('count(id) as nums,sum(order_amount) as moneys')->find();
         //已收货未付款订单
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
