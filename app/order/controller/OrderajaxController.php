<?php
 
namespace app\order\controller;

use app\common\controller\AdminBase0Controller;  
use think\Db; 
  
class OrderajaxController extends AdminBase0Controller
{
     
     
    /*
     *添加产品
     *  */
    public function goods_add()
    {
        $id=$this->request->param('id');
        $where=[
            'id'=>$id,
        ];
        
        $admin=$this->admin;
        
        $where['shop']=($admin['shop']==1)?2:$admin['shop'];
        //检查用户权限
        $authObj = new \cmf\lib\Auth(); 
        $name       = strtolower('goods/AdminGoodsauth/price_in_get'); 
        $is_auth=$authObj->check($admin['id'], $name);
        
        $goods=Db::name('goods')->field('id,name,code,sn,pic,price_in,price_sale,unit,weight1,size1')->where($where)->find();
        if($is_auth==false){
            $goods['price_in']='--';
        }
        //判断产品重量体积单位,统一转化为kg,cm3
        switch($goods['unit']){
            case 1:
                $goods['weight1']=bcdiv($goods['weight1'],1000,2);
                $goods['size1']=bcdiv($goods['size1'],1000000000,2);
                break; 
            case 3:
                $goods['weight1']=bcmul($goods['weight1'],1000,2);
                $goods['size1']=bcmul($goods['size1'],1000000000,2);
                break;
            default:
                $goods['weight1']=$goods['weight1'];
                $goods['size1']=$goods['size1'];
                break;
        }
        $goods['weight1']=($goods['weight1']==0)?0.01:$goods['weight1'];
        $goods['size1']=($goods['size1']==0)?0.01:$goods['size1'];
        
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
        if($admin['shop']>1){
            $where['shop']=$admin['shop'];
        }
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
            $where['p.shop']=$admin['shop'];
            $where_custom['shop']=$admin['shop'];
        }
        //付款方式,发票信息
        if($type==1){
            $m=Db::name('custom');
        }else{
            $m=Db::name('supplier');
        }
        $field='invoice_type,tax_point,freight,announcement,paytype,receiver,payer';
        $info=$m->field($field)->where($where_custom)->find();
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
            $this->error('没有计算规则,请手动填写');
        }
       
        $weight=bcmul($weight,1000);
        if(!empty($fees['size'])){
            //体积和重量换算比
            $weight1=bcdiv($size,$fees['size']);
            $weight=($weight>$weight1)?$weight:$weight1;
            $this->error('没有计算规则');
        }
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
    
    
}
