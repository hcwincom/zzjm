<?php
 
namespace app\order\controller;

use app\common\controller\AdminBase0Controller;  
use think\Db; 
use app\order\model\OrderModel;
  
class OrderajaxController extends AdminBase0Controller
{
     
     
    /*
     *添加产品
     *  */
    public function goods_add()
    {
        $id=$this->request->param('id');
        $uid=$this->request->param('uid');
        $type=$this->request->param('type',1,'intval');
        $where=[
            'id'=>$id,
        ];
        
        $admin=$this->admin;
        
        $where['shop']=($admin['shop']==1)?2:$admin['shop'];
        //检查用户权限
        $authObj = new \cmf\lib\Auth(); 
        $name       = strtolower('goods/AdminGoodsauth/price_in_get'); 
        $is_auth=$authObj->check($admin['id'], $name);
        
        $goods=Db::name('goods')->field('id,name,code,pic,price_in,price_sale,type,weight1,size1')->where($where)->find();
        if($is_auth==false){
            $goods['price_in']='--';
        }
        //判断产品重量体积单位,统一转化为kg,cm3 
        $m=new OrderModel();
        $goods=$m->unit_change($goods); 
        //产品库存
        $where['goods']=$id;
        unset($where['id']); 
        $nums=Db::name('store_goods')->where($where)->column('store,num,num1');
        $goods['nums']=$nums;
         
         //产品图片
         $where=[
             'pid'=>$id,
             'type'=>1,
         ];
         
         $goods['pics']=Db::name('goods_file')->where($where)->column('id,file,pid');
         $path='upload/';
         foreach($goods['pics'] as $k=>$v){
             $goods['pics'][$k]=[
                 'file1'=>$v['file'].'1.jpg',
                 'file3'=>$v['file'].'3.jpg',
             ]; 
         }
         if($type==1){
             //添加客户用名
             if(empty($uid)){
                 $tmp=null;
             }else{
                 $where=['uid'=>$uid,'goods'=>$id];
                 $tmp=Db::name('custom_goods')->where($where)->find();
             }
             if(empty($tmp)){
                 $goods['goods_uname']='';
                 $goods['goods_ucate']='';
                 $goods['price_pay']=$goods['price_sale'];
             }else{
                 $goods['goods_uname']=$tmp['name'];
                 $goods['goods_ucate']=$tmp['cate'];
                 $goods['price_pay']=$tmp['price'];
                 $goods['dsc']=$tmp['dsc'];
             }
         }
         
        $this->success('ok','',$goods);
    }
    //根据所属公司和客户分类,客户所在地得到客户
    public function get_customs(){
        $admin=$this->admin;
        $cid=$this->request->param('cid',0,'intval');
        $company=$this->request->param('company',0,'intval');
        $province=$this->request->param('province',0,'intval');
        $city=$this->request->param('city',0,'intval');
        $type=$this->request->param('type',1,'intval');
        if($type==1){
            $m=Db::name('custom');
        }else{
            $m=Db::name('supplier');
        }
        $where=[
            'province'=>$province,
            'status'=>2,
        ];
        $where['shop']=($admin['shop']==1)?2:$admin['shop'];
        if($cid>0){
            $where['cid']=$cid;
        }
        if($company>0){
            $where['company']=$company;
        }
        if($city>0){
            $where['city']=$city;
        }
        $field='id,name';
        $list=$m->where($where)->order('sort asc,code asc')->column($field);
        $this->success('ok','',$list);
    }
    //根据客户得到联系人
    public function get_custom_info(){
       
        $admin=$this->admin;
        $uid=$this->request->param('uid',0,'intval');
        $type=$this->request->param('type',1,'intval');
        
        $where_custom=['id'=>$uid];
        $where=[
            'p.uid'=>$uid,
            'p.type'=>$type,
            'p.status'=>1,
        ];
        if($admin['shop']>1){
            //$where['p.shop']=$admin['shop'];
            $where_custom['shop']=$admin['shop'];
        }
        //付款方式,发票信息
        if($type==1){
            $m=Db::name('custom');
            $m_ugoods=Db::name('custom_goods'); 
        }else{
            $m=Db::name('supplier');
            $m_ugoods=Db::name('supplier_goods'); 
        }
        
        $info=$m->where($where_custom)->find();
        //联系人 
        $field='p.site,p.id,p.name,p.mobile,p.phone,p.street,p.postcode'.
            ',p.province,p.city,p.area'.
            ',province.name as province_name,city.name as city_name,area.name as area_name';
        $info['tels']=Db::name('tel')
        ->alias('p')
        ->join('cmf_area province','province.type=1 and p.province=province.id','left')
        ->join('cmf_area city','city.type=2 and p.city=city.id','left')
        ->join('cmf_area area','area.type=3 and p.area=area.id','left')
        ->where($where)
        ->order('p.sort asc,p.site asc')
        ->column($field);
        //支付账号
        unset($where['p.status']); 
        $info['accounts']=Db::name('account')
        ->alias('p') 
        ->where($where)
        ->order('p.site asc')
        ->column('p.*','p.site');
        //供应产品
         
        $info['ugoods']=$m_ugoods
        ->alias('p')
        ->join('cmf_goods goods','goods.id=p.goods')
        ->where('p.uid',$uid)
        ->column('p.goods,p.name,p.cate,p.num,p.price,goods.name as goods_name,goods.code as goods_code');
        $this->success('ok','',$info);
    }
    //根据客户得到联系人
    public function get_ugoods(){
        
        $admin=$this->admin;
        $uid=$this->request->param('uid',0,'intval');
        $type=$this->request->param('type',1,'intval');
        $name=$this->request->param('name','');
        
        $where=['p.uid'=>$uid]; 
        if($admin['shop']>1){ 
            $where['p.shop']=$admin['shop'];
        }
        if(!empty($name)){
            $where['p.name|goods.name']=['like','%'.$name.'%']; 
        }
        //付款方式,发票信息
        if($type==1){ 
            $m_ugoods=Db::name('custom_goods');
        }else{ 
            $m_ugoods=Db::name('supplier_goods');
        } 
        //供应产品 
        $info=$m_ugoods
        ->alias('p')
        ->join('cmf_goods goods','goods.id=p.goods')
        ->where($where)
        ->column('p.goods,p.name,p.cate,p.num,p.price,goods.name as goods_name,goods.code as goods_code');
        $this->success('ok','',$info);
    }
    
    /*
     *收货人变化，选择仓库和发货物流
     *  */
    public function accept_change()
    {
        $tel_id=$this->request->param('accept',0,'intval');
       
        $freight=$this->request->param('freight',0,'intval');
        $admin=$this->admin;
         
        $city=Db::name('tel')->where('id',$tel_id)->value('city');
        //如果存在已选物流，则比较已选物流是否能到达，如果不能则重新选择
        //先根据收货地址选出可选的物流
        $areas=Db::name('express_area')->where('city',$city)->column('area');
        $where=[
            'id'=>['in',$areas],
            'shop'=>($admin['shop']==1)?2:$admin['shop'],
        ];
        //按收费排序
        $freights=Db::name('expressarea')->where($where)->order('price0 asc,price asc,sort asc')->column('freight');
        if(empty($freights)){
            $this->error('该收货地址没有快递设置');
        }
        //如果默认快递未选择或无法选择，则重新赋值
        if(empty($freight) || !in_array($freight, $freights)){
            $freight=current($freights);
        }
        //默认仓库
        $store=Db::name('freight')->where('id',$freight)->value('store');
        
        $this->success('ok','',['freight'=>$freight,'store'=>$store,'freights'=>$freights]);
    }
    
    //freight_count运费计算
    public function freight_count(){
        $data=$this->request->param();
        $freight=intval($data['freight']);
        $city=intval($data['city']);
        $size=round($data['size'],2);
        $weight=round($data['weight'],2);
        if($freight==0 || $city==0 || $size==0 || $weight==0){
            $this->error('数据错误，请把收货信息和包装大小填写完整');
        }
        $fees=Db::name('freight_fee')
        ->alias('ff')
        ->field('ff.weight0,ff.price0,ff.price1,ff.weight1,ff.size')
        ->join('cmf_express_area ea','ff.freight='.$freight.' and ea.city='.$city.' and ea.area=ff.expressarea')
        ->find();
        
        if(empty($fees)){
            $this->error($freight.'没有计算规则,请手动填写'.$city);
        }
       
        $weight=bcmul($weight,1000);
        if(!empty($fees['size'])){
            //体积和重量换算比
            $weight1=bcdiv($size,$fees['size']);
            $weight=($weight>$weight1)?$weight:$weight1; 
        }
        //首重计算和判断
        $weight1=bcsub($weight, $fees['weight0']);
        //不足首重
        if($weight1<=0){
            $this->success('ok','',$fees['price0']);
        }
        //重量计算
        $num=ceil($weight1/$fees['weight1']);
        $price=bcadd($fees['price0'],$fees['price1']*$num,2);
        $this->success('ok','',$price);
        
    }
    /*
     *关联产品库存
     *  */
    public function goods_about_store()
    {
        $id=$this->request->param('id');
       
        $where=[
            'id'=>$id,
        ];
        
        $admin=$this->admin;
        if($admin['shop']>1){
            $where['shop']=$admin['shop'];
        }
        
        $type=Db::name('goods')->where($where)->value('type');
        if($type<2 || $type>5){
            $this->error('无关联');
        }
        $m_sg=Db::name('store_goods');
        
        if($type==3){
            $pid=Db::name('goods_label')->where('pid0',$id)->value('pid1');
            $nums=$m_sg->where('goods',$pid)->column('store,num');
        }else{
            //关联库存要先得到关联，再获取关联库存，再计算得到可拼凑库存
            $pnums=Db::name('goods_link')->where('pid0',$id)->column('pid1,num');
            $pids=array_keys($pnums); 
            
            $nums1=$m_sg->where('goods','in',$pids)->column('id,goods,store,num');
            //按仓库分组库存
            $stores=[];
            foreach($nums1 as $v){
                $stores[$v['store']][$v['goods']]=$v['num'];
            }
            
            //按仓库库存比较
            foreach($stores as $k=>$v){
                //定义临时库存数，最后取最小的
                $num_tmp=[];
                foreach($pnums as $kk=>$vv){
                    $num_tmp[$kk]=bcdiv($v[$kk],$vv,0);
                }
                //取最小的作为
                $nums[$k]=min($num_tmp);
            }
        }
       
        
        $this->success('ok','',$nums);
    }
    
}
