<?php
 
namespace app\orderback\model;

use think\Model;
use think\Db;
use app\store\model\StoreGoodsModel;
class OrderbackModel extends Model
{
    /* find */
    public function get_one($where,$field='*')
    {
        $data=$this->field($field)->where($where)->find();
        return $data->data;
    }
     /* 售后编辑 */
     public function orderback_edit($info,$data)
     {
          
         $content=[];
         //检测改变了哪些字段 
         $fields_int=['store1','store2','express1','express2','province','city','area'];
         foreach($fields_int as $v){ 
             $data[$v]=intval($data[$v]);
             if($info[$v]!=$data[$v]){
                 $content[$v]=$data[$v];
             }
         }
         $fields_round=['goods_money','back_money'];
         foreach($fields_round as $v){
             $data[$v]=round($data[$v],2);
             if($info[$v]!=$data[$v]){
                 $content[$v]=$data[$v];
             }
         }
         $fields_str=['express_no1','express_no2','postcode','accept_name','mobile','phone','address','addressinfo'];
         foreach($fields_str as $v){
             if($info[$v]!=$data[$v]){
                 $content[$v]=$data[$v];
             }
         }
         
         //图片信息
         $content= $this->pic_do($info,$data,$content);
         
         
         //获取原售后和售后产品
         $where_goods=['oid'=>$info['id']]; 
         //全部售后产品
         $infos=Db::name('orderback_goods')
         ->where($where_goods)
         ->column('*','goods');
           
         
         //用原产品比较是否有删除和变化
         foreach($infos as $k=>$v){
             
             if(isset($data['goods_ids'][$k])){
                 $data['nums'][$k]=intval($data['nums'][$k]);
                 if($data['nums'][$k] != $v['num']){
                     $content['goods'][$k]['num']=$data['nums'][$k];
                 }
                 $data['pays'][$k]=round($data['pays'][$k],2);
                 if($data['pays'][$k] != $v['pay']){
                     $content['goods'][$k]['pay']=$data['pays'][$k];
                 }
                 if($data['dscs'][$k] != $v['dsc']){
                     $content['goods'][$k]['dsc']=$data['dscs'][$k];
                 } 
             }else{
                 $content['goods_del'][$k]=$v;
             }
         }
          
         return $content;
         
     }
     /* 审核售后编辑 */
     public function orderback_edit_review($orderback,$change,$admin)
     {
         
           
         $time=time();
         
         $m_ogoods=Db::name('orderback_goods');
         //先处理字售后产品，再处理售后信息
         //删除售后产品信息
         if(isset($change['goods_del'])){
             $dels=array_keys($change['goods_del']);
             $where=[
                 'oid'=>$orderback['id'],
                 'goods'=>['in',$dels],
                
             ];
             $m_ogoods->where($where)->delete();
             unset($change['goods_del']);
         }
         //售后产品编辑
         if(isset($change['goods'])){
             foreach($change['goods'] as $k=>$vo){
                 
                 $where=[
                     'oid'=>$orderback['id'] ,
                     'goods'=>$k, 
                 ];
                 $m_ogoods->where($where)->update($vo);
             }
             unset($change['goods']);
         }
          
         $update_info=['time'=>$time];
        
         foreach($change as $k=>$v){
             $update_info[$k]=$v;
         } 
         
         
         //售后完成
         if($orderback['status']==4){
             //已收货，货款结清是完成
             if(isset($update_info['status2'])){
                 $orderback['status2']=$update_info['status2'];
             }
             if(isset($update_info['pay_status'])){
                 $orderback['pay_status']=$update_info['pay_status'];
             }
             if(isset($update_info['type'])){
                 $orderback['type']=$update_info['type'];
             }
             //重新下单的需要手动点完成
             if($orderback['status2']==5){
                 
             }
             //标记发货和支付是否完成
             $store=0;
             $pay=0;
             //重新下单的需要手动点完成
             if($orderback['status2']==5){
                 $store=0;
             }elseif($orderback['order_type']==1 && $orderback['status2']>3){
                 $store=1;
             }elseif($orderback['order_type']==2 && $orderback['status2']>2){
                 $store=1;
             }
             if($orderback['type']==1){
                 $pay=1;
             }elseif($orderback['pay_status']==3){
                 $pay=1;
             }
             //售后完成
             if($pay && $store){
                 $update_info['status']=5;
             }
         }
         
         
         $this->where('id',$orderback['id'])->update($update_info);
        
         return 1; 
     }
  
     /**
      * 售后转化产品 和供应商进货
      * @param array $orderback
      * @param array $admin
      * @return string|number
      */
     public function orderback_goods_do($orderback,$admin){
         //获取原售后和售后产品
         $where_goods=['oid'=>$orderback['id']];
         //全部售后产品
         $m_ogoods=Db::name('orderback_goods');
         $orderback_goods=$m_ogoods
         ->where($where_goods)
         ->column('');
         //数据转化，按售后分组
         $infos=[];
         $goods_info=[];
         $m_cate=Db::name('cate');
         $m_goods=Db::name('goods');
         $m_sup_goods=Db::name('supplier_goods');
         foreach($orderback_goods as $k=>$v){
             if($v['goods']<=0){
                 $data_goods=[
                     'cid'=>$v['cid'],
                     'code_name'=>$v['code_name'],
                     'name'=>$v['name'],
                     'price_in'=>$v['price_in'],
                     'price_sale'=>$v['price_sale'], 
                     'weight'=>0.01,
                     'weight1'=>0.01,
                     'size'=>0.01,
                     'size1'=>0.01,
                 ]; 
                 
                 $cate=$m_cate
                 ->field('c.*')
                 ->alias('c')
                 ->where('c.id',$data_goods['cid'])
                 ->find();
                 if(empty($cate) || $cate['fid']==0){
                     return $v['name'].'分类错误';
                 }
                 $data_goods['code_num']=$cate['max_num']+1;
                 $data_goods['code']=$cate['code'].'-'.str_pad( $data_goods['code_num'], 2,'0',STR_PAD_LEFT);
                 
                 //给产品分类加1
                 $m_cate->where('id',$data_goods['cid'])->setInc('max_num');
                 $goods_id=$m_goods->insertGetId($data_goods);
                 $update=[
                     'goods'=>$goods_id,
                     'code'=>$data_goods['code'],
                     'cid0'=>$cate['fid'],
                 ];
                 $m_ogoods->where('id',$k)->update($update);
                 //转化供货商产品
                 if($v['sup']>0){ 
                     $data_sup_goods=[
                         'uid'=>$v['sup'],
                         'goods'=>$goods_id,
                         'price'=>$v['price_in'],
                         'name'=>$v['name'],
                         'num'=>1,
                         'shop'=>$orderback['shop']
                     ];
                     $m_sup_goods->insert($data_sup_goods); 
                 } 
             }elseif($v['sup']>0){
                //转化供货商产品
                 $where=[
                     'uid'=>$v['sup'],
                     'goods'=>$v['goods'],
                 ];
                 $tmp=$m_sup_goods->where($where)->find();
                 if(empty($tmp)){
                     $data_sup_goods=[
                         'uid'=>$v['sup'],
                         'goods'=>$v['goods'],
                         'price'=>$v['price_in'],
                         'name'=>$v['name'],
                         'num'=>1,
                         'shop'=>$orderback['shop']
                     ];
                     $m_sup_goods->insert($data_sup_goods);
                 }
                
             }
             
         }
         return 1;
     }
     
     /**
      * 售后转化用户
      * @param array $orderback
      * @param array $admin
      * @return string|number
      */
     public function orderback_custom_do($orderback,$admin){
         $custom=Db::name('orderback_custom')->where('id',$orderback['uid'])->find();
         if($custom['uid']>0){
             return $custom['uid']; 
         } 
         if(empty($custom['city'])){
             return '客户未选择城市';
         }
         $m=Db::name('custom');
         $data_custom=[
             'company'=>$orderback['company'],
             'shop'=>$orderback['shop'],
             'aid'=>$admin['id'],
             'province'=>$custom['province'],
             'name'=>$custom['uname'],
             'city'=>$custom['city'],
             'area'=>$custom['area'],
           
             'mobile'=>$custom['mobile'],
             'qq'=>$custom['qq'],
             'fax'=>$custom['fax'],
             'postcode'=>$custom['postcode'],
             'street'=>$custom['street'],
             'email'=>$custom['email'],
             'wechat'=>$custom['wechat'],
         ];
         //查询客户编码
         $where=[
             'city'=>['eq',$data_custom['city']],
             'shop'=>['eq',$data_custom['shop']]
         ];
         $tmp=$m->where($where)->field('id,code,city_code,code_num,postcode')->order('code_num desc')->find();
         if(empty($tmp)){
             //无此城市,则查询城市
             $tmp=Db::name('area')->where('id',$data_custom['city'])->field('code,postcode')->find();
             $data_custom['city_code']=$tmp['code'];
             $data_custom['postcode']=$tmp['postcode'];
             $data_custom['code_num']=1;
         }else{ 
             //不存在就是城市新增 
             $data_custom['city_code']=$tmp['city_code'];
             $data_custom['postcode']=$tmp['postcode'];
             $data_custom['code_num']=intval($tmp['code_num'])+1; 
         } 
        
         //拼接客户编码
         $data_custom['code']='KH-'.
         str_pad( $data_custom['city_code'], 4,'0',STR_PAD_LEFT).'-'.
         str_pad($data_custom['code_num'], 3,'0',STR_PAD_LEFT);
         //判断客户编码是否合法
         $tmp=$m->where(['code'=>$data_custom['code']])->find();
         if(!empty($tmp)){
            return '客户编号已存在';
         }
         $uid=$m->insertGetId($data_custom);
         
         $data_tel=[
             'site'=>1,
             'uid'=>1,
             'type'=>1,
             'province'=>$custom['province'],
             'position'=>$custom['position'],
             'sex'=>$custom['sex'],
             'wechatphone'=>$custom['wechatphone'],
             'wechatname'=>$custom['wechatname'],
             'aliid'=>$custom['aliid'],
             'taobaoid'=>$custom['taobaoid'], 
             'name'=>$custom['name'],
             'city'=>$custom['city'],
             'area'=>$custom['area'],
             'phone'=>$custom['phone'],
             'mobile'=>$custom['mobile'],
             'qq'=>$custom['qq'],
             'fax'=>$custom['fax'],
             'postcode'=>$data_custom['postcode'],
             'street'=>$custom['street'],
             'email'=>$custom['email'],
             'wechat'=>$custom['wechat'],
         ];
         Db::name('tel')->insert($data_tel);
         $update=[
             'uid'=>$uid,
             'ucode'=>$data_custom['code'],
         ];
         Db::name('orderback_custom')->where('id',$orderback['uid'])->update($update);
         return $uid;
     }
     /* 售后是否可以编辑 */
     public function orderback_edit_auth($orderback,$admin){
         
         //是否有待审核
         $where=[
             'pid'=>['eq',$orderback['id']],
             'table'=>['eq','orderback'],
             'rstatus'=>['eq',1],
         ];
         $aid=Db::name('edit')
         ->where($where) 
         ->value('aid');
         if(!empty($aid) && $admin['id']!=$aid){
             return '售后有修改，需等待审核';
         }
         //创建人能修改售后
         if($admin['id']==$orderback['aid']  ){
             return 1;
         }
         //创建人的经理或是总部门经理
         if($admin['job']==1){
             if($admin['department']==1){
                 return 1;
             }else{
                 $department=Db::name('user')->where('id',$orderback['aid'])->value('department');
                 //创建人不存在或是部门经理
                 if(empty($department) || $department==$admin['department']){
                     return 1;
                 }
             }
         }
         //在orderback_aid表中可以
         $where=[
             'oid'=>$orderback['id'],
             'aid'=>$admin['id'],
             'type'=>4,
         ];
         $tmp=Db::name('order_aid')->where($where)->find();
         if(!empty($tmp)){
             return 1;
         }
         return '无权限查看该定售后';
     }
      
      
     /* 检查产品的更新操作是否合法 */
     public function is_option($orderback,$change){
         
         
         return 1;
     }
     
     /**
      * 得到售后的产品 详情
      * @param array $order_goods产品数据
      * @param int $aid管理员
      * @param int $shop店铺
      * @return array[]
      */
     public function orderback_goods($order_goods,$aid,$shop){
          
         $goods_id=array_keys($order_goods);
         //获取产品图片
         $where=[
             'pid'=>['in',$goods_id],
             'type'=>['eq',1],
         ];
         $pics=Db::name('goods_file')->where($where)->column('id,pid,file');
         $path=cmf_get_image_url('');
         foreach($pics as $k=>$v){
             $order_goods[$v['pid']]['pics'][]=[
                 'file1'=>$v['file'].'1.jpg',
                 'file3'=>$v['file'].'3.jpg',
             ];
         }
         
         //获取所有库存
         $where=[
             'goods'=>['in',$goods_id],
             'shop'=>['eq',$shop],
         ];
         $list=Db::name('store_goods')->where($where)->column('id,store,goods,num,num1');
         
         //循环得到数据
         foreach($list as $k=>$v){
             $order_goods[$v['goods']]['nums'][$v['store']]=[
                 'num'=>$v['num'],
                 'num1'=>$v['num1'],
             ];
         } 
         return $order_goods; 
     }
    
     /**
      * 图片比较
      * @param array $info原信息
      * @param array $data上传信息
      * @param array $content修改信息
      * @return array返回修改结果
      */
     public function pic_do($info,$data,$content=[]){
         $path='upload/';
         $pathid='seller'.$info['shop'].'/orderback'.$info['id'].'/';
         //没有目录创建目录
         if(!is_dir($path.$pathid)){
             mkdir($path.$pathid);
         }
         //图片尺寸
        
         $files=[];
         $file_type=['pics'=>'产品图片'];
        
         //循环得到上传后的数据
         foreach($file_type as $k=>$v){
             $urls=($k).'_urls';
             $names=($k).'_names';
             //没文件的为空
             $files[$k]=[];
             if(!empty($data[$urls])){
                 foreach($data[$urls] as $kk=>$vv){
                     //名称中不能有逗号5
                     if(empty($data[$names][$kk])){
                         $data[$names][$kk]=$v[1].$kk;
                     }else{
                         //可以改为正则检测
                         if(strpos($data[$names][$kk], ',')!==false){
                             $this->error('文件名称中不能有逗号');
                         }
                     }
                     $files[$k][]=['name'=>$data[$names][$kk],'url'=>$data[$urls][$kk]];
                 }
             }
             $json=json_encode($files[$k]);
             
             //比较变化
             if($json!=$info[$k]){
                 //标记变化
                 $file_type[$k.'change']=1;
                 //有变化就保存图片，然后保存json
                 foreach($files[$k] as $kk=>$vv){
                    
                     //不是文件直接删除
                     if (!is_file($path.$vv['url']))
                     {
                         unset($files[$k][$kk]); 
                         continue;
                     }
                     //先比较是否需要额外保存,非指定位置的要复制粘贴
                     if(strpos($vv['url'], $pathid)!==0){
                         //获取后缀名,复制文件
                         $ext=substr($vv['url'], strrpos($vv['url'],'.'));
                         $new_file=$pathid.($k).$kk.date('Ymd-His').$ext;
                         $result =copy($path.$vv['url'], $path.$new_file);
                         if ($result == false)
                         {
                             //不是文件直接删除
                             unset($files[$k][$kk]); 
                             continue; 
                         }else{ 
                             $files[$k][$kk]['url']=$new_file;
                             //删除原图片
                             unlink($path.$vv['url']);  
                             
                         } 
                     } 
                 } 
             }
         }
         if(isset($file_type['picschange'])){
             $content['pics']=json_encode($files['pics']);
         }
         
         return $content;
     }
     /**
      * 入库操作更新
      * @param number $oid
      * @param number $old_status
      * @param number $num_ok是否严格检查库存
      * @return number
      */
     public function status_store_change($oid,$old_status=1,$status_name='status1',$num_ok=1){
          
         $res=1;
         $where=[
             'id'=>$oid, 
         ];
         $order=$this->where('id',$oid)->find();
         if(empty($order)){
             return '订单错误';
         }
         $order=$order->data;
         //仅退款
         if($order['type']==3){
             return 1;
         }
         $status_name=trim($status_name);
         $new_status=$order[$status_name];
         $type=21;
         if($order['order_type']==1 && $status_name=='status2'){
             $type=22; 
         }elseif($order['order_type']==2 && $status_name=='status1'){
             $type=22; 
         }
         
         //状态未改变
         if($new_status==$old_status){
             return 1;
         }
         
         switch ($new_status){
             case 1:
                 //如果有发货要取消
                 $res=$this->order_storein5($order['id'],$type,$num_ok);
                 break;
             case 2:
                 //入库准备
                 $res=$this->order_storein0($order['id'],$type,$num_ok);
                 break;
             case 3:  
                 //入库确认
                 $res=$this->order_storein2($order['id'],$type,$num_ok); 
                 break;
             case 5:
                 //重新下单
                 $res=$this->order_storein5($order['id'],$type,$num_ok);
                 break;
         } 
        
         return $res;
     }
     
     /**
      * 出入库申请,只能是实际单号提交
      * @param number $id
      * @param number $type是入库类型，21售后入库，22售后出库
      * @param number $num_ok是否严格审核库存,1严格，2不审核
      * @return number|string
      */
     public function order_storein0($id,$type=21,$num_ok=1){
         $types=[
             21 =>'售后入库',
             22 => '售后出库',
         ];
         
         //在store_goods表中num1数值减少
         $order=$this->where('id',$id)->find();
         $order=$order->data;
          
         if(empty($order)){
             return '未找到售后订单';
         } 
         //客户提交订单，售后提交，store1
         $store=$order['store1'];
         //客户下单返回是售后处理store2,采购下单时售后处理store2
         if($order['order_type']==1 && $type==22){
             $store=$order['store2'];
         }elseif($order['order_type']==2 && $type==21){
             $store=$order['store2'];
         }
         if(empty($store)){
             return '没有选择仓库';
         }
         //订单产品
         $where_goods=['oid'=>$order['id']];
         //全部订单产品
         $goods_order=Db::name('orderback_goods')
         ->where($where_goods)
         ->column('id,goods,num,oid');
         
         if(empty($goods_order)){
             return 1;
         }
        
         $m_store_goods=new StoreGoodsModel();
         $time=time();
         $aid=session('ADMIN_ID');
         //一个个地出库
         foreach($goods_order as $k=>$v){
             if($type==21){
                 $num=$v['num'];
              }else{
                  $num=0-$v['num'];
              }
             $data=[
                 'shop'=>$order['shop'],
                 'store'=>$store,
                 'goods'=>$v['goods'],
                 'num'=>$num,
                 'atime'=>$time,
                 'aid'=>$aid,
                 'adsc'=>$types[$type],
                 'rstatus'=>1,
                 'type'=>$type,
                 'about'=>$order['id'],
                 'about_name'=>$order['name'],
             ];
             $res=$m_store_goods->instore0($data,$num_ok);
             if(!($res>0)){
                 return $res;
             }
         }
         
         return 1;
     }
      
     /**
      *  订单发货后，出库记录批量同意
      * @param number $id
      *  * @param number $num_ok是否检查库存，1严格2不检查
      * @return string|number
      */
     public function order_storein2($id,$type=21,$num_ok=1,$dsc='统一审核'){
        
         //出入库记录要变为已同意
         $where=[
             'type'=>$type,
             'about'=>$id,
             'rstatus'=>1,
         ];
         $list=Db::name('store_in')->where($where)->column('');
        
         $m_store_goods=new StoreGoodsModel();
         foreach($list as $k=>$v){
             $res=$m_store_goods->instore2($v,0,'',$num_ok);
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
      * 订单确认发货要检查出库记录是否都已审
      * @param number $id
      * @return string|number
      */
     public function order_storein_check($id,$type=21){
         
         $order=$this->where('id',$id)->find();
         $order=$order->data;
          
         
         $m_store_in=Db::name('store_in');
         $where=[
             'type'=>$type,
             'about'=>$id,
             'rstatus'=>2,
         ];
         
         $goods_store=$m_store_in->where($where)->order('goods')->column('goods,num');
         $goods_order=Db::name('order_goods')->where('oid',$id)->order('goods')->column('goods,num');
         foreach($goods_order as $k=>$v){
             if(!isset($goods_store[$k]) || $goods_store[$k] != $v){
                 return '出库不完全';
             }
         }
         
         return 1;
         
     }
     /* 订单改变后废弃原出库记录 */
     public function order_storein5($id,$type=21,$dsc='订单变化，废弃原出入库'){
          
         //订单产品
         $where_about=[
             'type'=>$type,
             'about'=>$id
         ];
          
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
             'rid'=>empty(session('ADMIN_ID'))?1:session('ADMIN_ID'),
         ];
         Db::name('store_in')->where('id','in',$in_ids)->update($update_info);
         return 1;
     }
     
     
}
