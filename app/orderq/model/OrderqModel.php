<?php
 
namespace app\orderq\model;

use think\Model;
use think\Db;
 
class OrderqModel extends Model
{
      
     /* 询盘编辑 */
     public function orderq_edit($info,$data)
     {
          
         $content=[];
         //检测改变了哪些字段 
         
         $fields=['status','company','sourse','sourse_name','udsc','dsc','answer1','question1','question2','answer2'];
         
         //先比较总信息
         foreach($fields as $k=>$v){
             //如果原信息和$data信息相同就未改变，不为空就记录，？null测试
             if(isset($data[$v]) && $info[$v]!=$data[$v]){
                 $content[$v]=$data[$v];
             }
         }
         //图片信息
         $content= $this->pic_do($info,$data,$content);
         //客户信息
         $fields_custom=[
             'ucode','uname','name','position','sex','mobile','phone','province','city','area',
             'street','postcode','fax','qq','wechat','wechatphone','wechatname','email','taobaoid','aliid'
         ];
         $custom0=Db::name('orderq_custom')->where('id',$info['uid'])->find();
         if(!empty($data['uid'])){
             if($custom0['uid']!=$data['uid']){
                 $content['custom']['uid']=$data['uid'];
            }
         } 
         foreach($fields_custom as $v){
             if(isset($data['custom_'.$v]) && $custom0[$v]!=$data['custom_'.$v]){
                 $content['custom'][$v]=$data['custom_'.$v];
             } 
         }
         
         //获取原询盘和询盘产品
         $where_goods=['oid'=>$info['id']]; 
         //全部询盘产品
         $orderq_goods=Db::name('orderq_goods')
         ->where($where_goods)
         ->column('');
         //数据转化，按询盘分组
         $infos=[];
         $goods_info=[];
         foreach($orderq_goods as $k=>$v){
             $infos[$v['id']]=$v;
             $goods_info[$v['goods']]=$v;
         }
         //产品数据
         $goods_new=[];
         if(!empty($data['goods_ids'])){
             $goods_ids=$data['goods_ids'];
             //先把未知产品删除
             foreach($goods_ids as $k=>$v){
                 if($v<=0){
                     unset($goods_ids[$k]);
                 }
             }
             
             if(!empty($goods_ids)){
                 $where=[
                     'id'=>['in',$goods_ids],
                     'shop'=>$info['shop']
                 ];
                 $goods_new=Db::name('goods')->where($where)->column('id,cid0,cid,code,pic,code_name,name,price_in,price_sale');
             } 
         }
         //可用原产品数据替代的数据
         $field_goods0=[
             'code_name','name','price_in','price_sale','cid','cid0','code','pic',
         ];
         $field_goods00=[
             'code_name','name','price_in','price_sale','cid','cid0',
         ];
         //完全依赖输入的数据
         $field_goods1=[
             'price_real','dsc','send_dsc','is_sup','num','sup'
         ];
         $round=['price_real','price_in','price_sale'];
         //用原产品比较是否有删除和变化
         foreach($infos as $k=>$v){
             
             if(isset($data['goods_ids'][$k])){
                 //转化数字
                 foreach($round as $gfield){
                     $data[$gfield.'s'][$k]=round($data[$gfield.'s'][$k],2);
                 }
                 //比较数据变化
                 foreach($field_goods1 as $gfield){
                     if($data[$gfield.'s'][$k]!=$v[$gfield]){
                         $content['goods'][$k][$gfield]=$data[$gfield.'s'][$k];
                     }
                 }
                 //如果是已知产品 
                 if(isset($goods_new[$data['goods_ids'][$k]])){ 
                     //已知产品替换
                     if($data['goods_ids'][$k]!=$v['goods']){
                         $content['goods'][$k]['goods']=$data['goods_ids'][$k];
                         foreach($field_goods0 as $gfield){
                             $content['goods'][$k][$gfield]=$goods_new[$data['goods_ids'][$k]][$gfield];
                        } 
                     } 
                     //一直产品且未变就不比较固定参数
                 }else{
                     //未知产品 
                     if($data['goods_ids'][$k]!=$v['goods']){
                         $content['goods'][$k]['goods']=$data['goods_ids'][$k]; 
                     } 
                     //未知产品一个个比较
                     foreach($field_goods00 as $gfield){
                         if($data[$gfield.'s'][$k]!=$v[$gfield]){
                             $content['goods'][$k][$gfield]=$data[$gfield.'s'][$k];
                         } 
                     }
                 }
                  
             }else{
                 $content['goods_del'][$k]=$v;
             }
         }
         //用新产品比较是否有新增
         foreach($data['goods_ids'] as $k=>$v){
             if(!isset($infos[$k])){
                 //转化数字
                 foreach($round as $gfield){
                     $data[$gfield.'s'][$k]=round($data[$gfield.'s'][$k],2);
                 }
                 $content['goods_add'][$k]['goods']=$data['goods_ids'][$k]; 
                 $content['goods_add'][$k]['oid']=$info['id']; 
                 foreach($field_goods1 as $gfield){
                      
                    $content['goods_add'][$k][$gfield]=$data[$gfield.'s'][$k];
                     
                 }
                 //产品新增
                 if(isset($goods_new[$v])){ 
                     foreach($field_goods0 as $gfield){
                         $content['goods_add'][$k][$gfield]=$goods_new[$v][$gfield];
                     } 
                 }else{
                     //未知新增
                     foreach($field_goods0 as $gfield){
                         
                         $content['goods_add'][$k][$gfield]=isset($data[$gfield.'s'][$k])?$data[$gfield.'s'][$k]:'';
                         
                     }
                 }
             } 
         }
         return $content;
         
     }
     /* 审核询盘编辑 */
     public function orderq_edit_review($orderq,$change,$admin)
     {
         
         //获取询盘状态信息
         if($orderq['status']!=1 && empty($change['status'])){ 
             return '询单已完成，不能再修改';
         } 
         $time=time();
         //先处理用户信息
         if(isset($change['custom'])){
             Db::name('orderq_custom')->where('id',$orderq['uid'])->update($change['custom']);
             unset($change['custom']);
         }
         $m_ogoods=Db::name('orderq_goods');
         //先处理字询盘产品，再处理询盘信息
         //删除询盘产品信息
         if(isset($change['goods_del'])){
             $dels=array_keys($change['goods_del']);
             $where=[
                 'id'=>['in',$dels],
                 'oid'=>$orderq['id']
             ];
             $m_ogoods->where($where)->delete();
             unset($change['goods_del']);
         }
         //询盘产品编辑
         if(isset($change['goods'])){
             foreach($change['goods'] as $koid=>$vo){
                 
                 $where=['id'=>$koid];
                 $m_ogoods->where($where)->update($vo);
             }
             unset($change['goods']);
         }
         //新增询盘产品信息 
         if(isset($change['goods_add'])){ 
             $m_ogoods->insertAll($change['goods_add']); 
             unset($change['goods_add']);
         }
         
         $update_info=['time'=>$time];
        
         foreach($change as $k=>$v){
             $update_info[$k]=$v;
         } 
         //转化询单
         if(isset($change['status']) && $change['status']==2){
             $update_info['rtime']=$time;
             $update_info['rid']=$admin['id'];
             $res=$this->orderq_custom_do($orderq, $admin);
             if($res<=0){
                 return $res;
             }
             $res=$this->orderq_goods_do($orderq, $admin);
             if($res<=0){
                 return $res;
             }
         }
         $this->where('id',$orderq['id'])->update($update_info);
        
         return 1; 
     }
  
     /**
      * 询盘转化产品 和供应商进货
      * @param array $orderq
      * @param array $admin
      * @return string|number
      */
     public function orderq_goods_do($orderq,$admin){
         //获取原询盘和询盘产品
         $where_goods=['oid'=>$orderq['id']];
         //全部询盘产品
         $m_ogoods=Db::name('orderq_goods');
         $orderq_goods=$m_ogoods
         ->where($where_goods)
         ->column('');
         //数据转化，按询盘分组
         $infos=[];
         $goods_info=[];
         $m_cate=Db::name('cate');
         $m_goods=Db::name('goods');
         $m_sup_goods=Db::name('supplier_goods');
         foreach($orderq_goods as $k=>$v){
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
                         'shop'=>$orderq['shop']
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
                         'shop'=>$orderq['shop']
                     ];
                     $m_sup_goods->insert($data_sup_goods);
                 }
                
             }
             
         }
         return 1;
     }
     
     /**
      * 询盘转化用户
      * @param array $orderq
      * @param array $admin
      * @return string|number
      */
     public function orderq_custom_do($orderq,$admin){
         $custom=Db::name('orderq_custom')->where('id',$orderq['uid'])->find();
         if($custom['uid']>0){
             return $custom['uid']; 
         } 
         if(empty($custom['city'])){
             return '客户未选择城市';
         }
         $m=Db::name('custom');
         $data_custom=[
             'company'=>$orderq['company'],
             'shop'=>$orderq['shop'],
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
         Db::name('orderq_custom')->where('id',$orderq['uid'])->update($update);
         return $uid;
     }
     /* 询盘是否可以编辑 */
     public function orderq_edit_auth($orderq,$admin){
         
         //是否有待审核
         $where=[
             'pid'=>['eq',$orderq['id']],
             'table'=>['eq','orderq'],
             'rstatus'=>['eq',1],
         ];
         $aid=Db::name('edit')
         ->where($where) 
         ->value('aid');
         if(!empty($aid) && $admin['id']!=$aid){
             return '询盘有修改，需等待审核';
         }
         //创建人能修改询盘
         if($admin['id']==$orderq['aid'] || $orderq['accept_id']==$admin['id']){
             return 1;
         }
         //创建人的经理或是总部门经理
         if($admin['job']==1){
             if($admin['department']==1){
                 return 1;
             }else{
                 $department=Db::name('user')->where('id',$orderq['aid'])->value('department');
                 //创建人不存在或是部门经理
                 if(empty($department) || $department==$admin['department']){
                     return 1;
                 }
             }
         }
         //在orderq_aid表中可以
         $where=[
             'oid'=>$orderq['id'],
             'aid'=>$admin['id'],
             'type'=>3,
         ];
         $tmp=Db::name('order_aid')->where($where)->find();
         if(!empty($tmp)){
             return 1;
         }
         return '无权限查看该定询盘';
     }
      
      
     /* 检查产品的更新操作是否合法 */
     public function is_option($orderq,$change){
         
         
         return 1;
     }
     /**
      * 得到询盘的产品详情,返回字询盘和产品详情
      * */
     public function orderq_goods($info,$aid){
         //询盘产品
         $where_goods=['oid'=>$info['id']];
          
         //全部询盘产品
         $orderq_goods=Db::name('orderq_goods')
         ->where($where_goods)
         ->column('');
         
         //检查用户权限
         $authObj = new \cmf\lib\Auth();
         $name       = strtolower('goods/AdminGoodsauth/price_in_get');
         $is_auth=$authObj->check($aid, $name);
         //数据转化，按询盘分组
         $infos=[];
         $goods_id=[];
         $goods=[];
         foreach($orderq_goods as $k=>$v){
             if($v['goods']>0){
                 $goods_id[$v['goods']]=$v['goods'];
             }
             
             $goods[$v['goods']]=[];
             if($is_auth==false){
                 $v['price_in']='--';
             }
             
             $infos[$v['id']]=$v;
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
         return ['goods'=>$goods,'infos'=>$infos]; 
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
         $pathid='seller'.$info['shop'].'/orderq'.$info['id'].'/';
         //没有目录创建目录
         if(!is_dir($path.$pathid)){
             mkdir($path.$pathid);
         }
         //图片尺寸
         $pic_size=config('pic_size');
         $files=[];
         $file_type=['pic1'=>'用户提供图片','pic2'=>'供货商提供图片'];
        
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
         if(isset($file_type['pic1change'])){
             $content['pic1']=json_encode($files['pic1']);
         }
         if(isset($file_type['pic2change'])){
             $content['pic2']=json_encode($files['pic2']);
         }
         return $content;
     }
}
