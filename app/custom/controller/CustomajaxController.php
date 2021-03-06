<?php
 
namespace app\custom\controller;

use app\common\controller\AdminBase0Controller;
use think\Db; 
use app\goods\model\GoodsModel;
  
class CustomajaxController extends AdminBase0Controller
{
    
    public function _initialize()
    {
        $session_admin_id = session('ADMIN_ID');
        if (!empty($session_admin_id)) {
            $user = Db::name('user')->where(['id' => $session_admin_id])->find();
            $this->admin=$user; 
        } else {
            $this->error("您还没有登录！", url("admin/Public/login")); 
        }
    }
     
   
    
   //客户编码
   public function code_add(){
       $id=$this->request->param('id',0,'intval');
       $city=$this->request->param('city',0,'intval');
       $type=$this->request->param('type',1,'intval');
       
       if($type==1){
           $m=Db::name('custom');
       }else{
           $m=Db::name('supplier');
       }
       $admin=$this->admin;
       $where=[
           'city'=>['eq',$city], 
           'shop'=>['eq',($admin['shop']==1)?2:$admin['shop']]
       ];
       
       $tmp=$m->where($where)->order('code_num desc')->column('id,code,city_code,code_num,postcode');
       if(empty($tmp)){
           //无此城市,则查询城市
           $tmp=Db::name('area')->where('id',$city)->field('code,postcode')->find();
           $city_code=$tmp['code'];
           $postcode=$tmp['postcode'];
           $code_num=1; 
       }else{
           //判断id是否已存在
           if(isset($tmp[$id])){
               $city_code=$tmp[$id]['city_code'];
               $code_num=$tmp[$id]['code_num'];
               $postcode=$tmp[$id]['postcode'];
           }else{
               //不存在就是城市新增
               $first=key($tmp);
               $city_code=$tmp[$first]['city_code'];
               $postcode=$tmp[$first]['postcode'];
               $code_num=intval($tmp[$first]['code_num'])+1;
           }
       } 
     
       $this->success('ok','',['city_code'=>$city_code,'code_num'=>$code_num,'postcode'=>$postcode]);
   }
   //客户编码检查
   public function code_check(){
       $id=$this->request->param('id',0,'intval');
       $city=$this->request->param('city',0,'intval'); 
       $type=$this->request->param('type',1,'intval');
       $code_num=$this->request->param('code_num',1,'intval');
       if($type==1){
           $m=Db::name('custom');
       }else{
           $m=Db::name('supplier');
       }
       $admin=$this->admin;
       
       $where=[
           'city'=>['eq',$city],
           'code_num'=>['eq',$code_num],
           'shop'=>['eq',($admin['shop']==1)?2:$admin['shop']]
       ];
       
       $tmp=$m->where($where)->find();
       if(empty($tmp)){
           //找不到说明无重复
           $this->success('ok');
       }else{
           //判断id是否相同
           if($tmp['id']==$id){
               $this->success('ok');
           }else{
               //不相同就是编号已存在
               $this->error('该编号已被占用');
           }
       }
       
   }
   /*
    *添加产品
    *  */
   public function goods_add()
   {
       $id=$this->request->param('id'); 
       $admin=$this->admin; 
       $shop=($admin['shop']==1)?2:$admin['shop'];
       $m_goods=new GoodsModel();
       $field='id,name,pic,code,weight1,length1,width1,height1,size1,unit,price_sale,dsc';
       $goods=$m_goods->goods_info($id,$shop,$field);
        
       $this->success('ok','',$goods);
   }
   
}
