<?php

namespace app\orderq\controller;

use app\common\controller\AdminBase0Controller;
use think\Db;
use app\orderq\model\OrderqModel;

class OrderbackajaxController extends AdminBase0Controller
{
    
    
    /*
     *添加产品
     *  */
    public function goods_add()
    {
        $id=$this->request->param('id');
       
        $where=[
            'p.id'=>$id,
        ];
        
        $admin=$this->admin;
        
        $where['p.shop']=($admin['shop']==1)?2:$admin['shop'];
        //检查用户权限
        $authObj = new \cmf\lib\Auth();
        $name       = strtolower('goods/AdminGoodsauth/price_in_get');
        $is_auth=$authObj->check($admin['id'], $name);
        
        $goods=Db::name('goods')
        ->alias('p')
        ->field('p.id,p.name,p.code,p.pic,p.price_in,p.price_sale,p.code_name,cate1.name as cname1,cate2.name as cname2')
        ->join('cmf_cate cate1','cate1.id=p.cid0','left')
        ->join('cmf_cate cate2','cate2.id=p.cid','left')
        ->where($where)
        ->find();
        if($is_auth==false){
            $goods['price_in']='--';
        }
      
        //产品库存
        $where=[
            'goods'=>$id,
            'shop'=> $where['p.shop']
        ];
       
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
        $field='id,name,code';
        $list=$m->where($where)->order('sort asc,code asc')->column($field);
        $this->success('ok','',$list);
    }
    //根据客户得到联系人
    public function get_custom_info(){
        $admin=$this->admin;
        $uid=$this->request->param('uid',0,'intval'); 
        $where_custom=['id'=>$uid];
        $where=[
            'p.uid'=>$uid,
            'p.type'=>1,
            'p.status'=>1,
        ];
       
        if($admin['shop']>1){
//             $where['p.shop']=$admin['shop'];
            $where_custom['shop']=$admin['shop'];
        }
        //得到名称，编码和默认联系人 
        $m=Db::name('custom'); 
        $field='id,name,code,contacter';
        $info=$m->field($field)->where($where_custom)->find();
        //联系人
        $field='p.*';
        $tels=Db::name('tel')
        ->alias('p') 
        ->where($where)
        ->order('p.sort asc,p.site asc')
        ->column($field,'p.site');
        foreach($tels as $k=>$v){
            foreach($v as $kk=>$vv){
                if($vv!='0' && empty($vv)){
                    $tels[$k][$kk]='';
                }
            }
        }
        $info['tels']=$tels;
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
    
    
}
