<?php
 
namespace app\custom\controller;


use cmf\controller\AdminBaseController; 
use think\Db; 
  
class CustomajaxController extends AdminBaseController
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
       $where=[
           'city'=>['eq',$city], 
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
       $where=[
           'city'=>['eq',$city],
           'code_num'=>['eq',$code_num],
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
        $field='invoice_type,tax_point,freight,announcement,paytype';
        $info=$m->field($field)->where($where_custom)->find();
        //联系人
        $m=Db::name('tel');
        $field='p.site,p.name,p.mobile,p.phone,p.street,p.postcode'.
            ',p.province,p.city,p.area'.
            ',province.name as province_name,city.name as city_name,area.name as area_name';
        $info['tels']=$m
        ->alias('p')
        ->join('cmf_area province','p.province=province.id and province.type=1')
        ->join('cmf_area city','p.city=city.id and city.type=2')
        ->join('cmf_area area','p.area=area.id and area.type=3')
        ->where($where) 
        ->order('p.sort asc,p.site asc')
        ->column($field);
       
        $this->success('ok','',$info);
    }
     
}
