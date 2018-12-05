<?php
 
namespace app\ordersup\controller;

use app\common\controller\AdminBase0Controller;  
use think\Db; 
  
class OrdersupajaxController extends AdminBase0Controller
{
     
     
    /*
     *添加产品
     *  */
    public function goods_add()
    {
        $id=$this->request->param('id');
        $uid=$this->request->param('uid');
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
         //添加客户用名
        $where=['uid'=>$uid,'goods'=>$id];
        $tmp=Db::name('supplier_goods')->where($where)->find();
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
        $this->success('ok','',$goods);
    }
    //根据所属公司和客户分类,客户所在地得到客户
    public function get_suppliers(){
        $admin=$this->admin;
        $cid=$this->request->param('cid',0,'intval');
        $company=$this->request->param('company',0,'intval');
        $province=$this->request->param('province',0,'intval');
        $city=$this->request->param('city',0,'intval');
        
        $m=Db::name('supplier');
       
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
    public function get_supplier_info(){
        $admin=$this->admin;
        $uid=$this->request->param('uid',0,'intval');
        $type=2;
        $where_supplier=['id'=>$uid];
        $where=[
            'p.uid'=>$uid,
            'p.type'=>$type,
            'p.status'=>1,
        ];
        if($admin['shop']>1){
            $where['p.shop']=$admin['shop'];
            $where_supplier['shop']=$admin['shop'];
        }
        //付款方式,发票信息
         $m=Db::name('supplier');
        
        $field='invoice_type,tax_point,freight,announcement,paytype,pay_type,receiver,payer';
        $info=$m->field($field)->where($where_supplier)->find();
         
        //支付账号
        unset($where['p.status']); 
        $info['accounts']=Db::name('account')
        ->alias('p') 
        ->where($where)
        ->order('p.site asc')
        ->column('p.*','p.site');
        //供应产品 
        $info['ugoods']=Db::name('supplier_goods')->where('uid',$uid)->column('goods,name,cate');
        
        $this->success('ok','',$info);
    }
    
    /*
     *收货人变化，选择仓库和发货物流
     **/
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
     
}
