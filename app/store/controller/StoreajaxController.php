<?php
 
namespace app\store\controller;
 
use think\Db; 

use app\common\controller\AdminBase0Controller;
 
class StoreajaxController extends AdminBase0Controller
{
    private $m;
    public function _initialize()
    {
       parent::_initialize();
        $this->m=Db::name('store');
    }
      
  
   /**
    * 获取仓库编码
    */
   public function code_add(){
       
       $id=$this->request->param('id',0,'intval');
       $city=$this->request->param('city',0,'intval');
       $m=$this->m;
      
       $where=[
           'city'=>['eq',$city], 
       ];
       
       $tmp=$m->where($where)->order('code_num desc')->column('id,code,city_code,code_num');
      
       if(empty($tmp)){
           //无此城市,则查询城市
           $city_code=Db::name('area')->where('id',$city)->value('code'); 
           $code_num=1; 
       }else{
           //判断id是否已存在
           if(isset($tmp[$id])){
               $city_code=$tmp[$id]['city_code'];
               $code_num=$tmp[$id]['code_num']; 
           }else{
               //不存在就是城市新增
               $first=key($tmp);
               $city_code=$tmp[$first]['city_code']; 
               $code_num=intval($tmp[$first]['code_num'])+1;
           }
       } 
     
       $this->success('ok','',['city_code'=>$city_code,'code_num'=>$code_num]);
   }
   /**
    * 仓库编码检查
    */
   public function code_check(){
       $id=$this->request->param('id',0,'intval');
       $city=$this->request->param('city',0,'intval'); 
      
       $code_num=$this->request->param('code_num',1,'intval');
       $m=$this->m;
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
   
    /**
     * 根据仓库获取货架号
     */
    public function shelf_add(){
        $store=$this->request->param('store',0,'intval');
        $m=$this->m;
        $height=$m->where('id',$store)->value('height');
        $num=Db::name('store_shelf')->where('store',$store)->order('num desc')->value('num');
        $num=(empty($num))?1:$num+1;
          
        $this->success('ok','',['height'=>$height,'num'=>$num]);
    }
    //根据仓库和货架号检查
    public function shelf_check(){
        $store=$this->request->param('store',0,'intval');
        $num=$this->request->param('num',0,'intval');
        $id=$this->request->param('id',0,'intval');
        $where=[
           'store'=>['eq',$store],
           'num'=>['eq',$num],
        ];
        if($id>0){
            $where['id']=['neq',$id];
        }
        $tmp=Db::name('store_shelf')->where($where)->value('id');
        if(empty($tmp)){
            $this->success('ok');
        }else{
            $this->error('货架号已存在');
        }
         
    }
    //根据仓库获取货架
    public function get_shelfs(){
        $store=$this->request->param('store',0,'intval');
        $where=[
            'store'=>$store,
            'status'=>2,
        ];
        $list=Db::name('store_shelf')->where($where)->column('id,name');
        $this->success('ok','',$list);
    }
    //根据货架获取层号
    public function get_floors(){
        $shelf=$this->request->param('shelf',0,'intval');
        $where=[
            'shelf'=>$shelf, 
        ];
        $list=Db::name('store_floor')->where($where)->column('id,floor');
        $this->success('ok','',$list);
    }
    //根据层号获取空白料位
    public function get_boxes0(){
        $floor=$this->request->param('floor',0,'intval');
        $where=[
            'floor'=>$floor,
            'status'=>2,
            'goods'=>0,
        ];
        $list=Db::name('store_box')->where($where)->column('id,name');
        $this->success('ok','',$list);
    }
    
    //料位编码
    public function box_code_add(){
       
        $floor=$this->request->param('floor',0,'intval'); 
        $tmp=Db::name('store_box')->where('floor',$floor)->order('code_num desc')->value('code_num'); 
        $code_num=(empty($tmp))?1:($tmp+1);
        $tmp=Db::name('store_floor')->where('id',$floor)->value('code');
        $code=$tmp.'-'.str_pad($code_num, 2,'0',STR_PAD_LEFT);
        $this->success('ok','',['code'=>$code,'code_num'=>$code_num]);
    }
    //料位编码
    public function box_code_check(){
        $id=$this->request->param('id',0,'intval');
        $floor=$this->request->param('floor',0,'intval');
        $code_num=$this->request->param('code_num',0,'intval');
        
        $m=Db::name('store_box');
        $where=[
            'floor'=>['eq',$floor],
            'code_num'=>['eq',$code_num],
        ];
        if($id>0){
            $where['id']=['neq',$id];
        }
        $tmp=$m->where($where)->find();
        if(empty($tmp)){
            //找不到说明无重复
            $tmp=Db::name('store_floor')->where('id',$floor)->value('code');
            $code=$tmp.'-'.str_pad($code_num, 2,'0',STR_PAD_LEFT);
            $this->success('ok','',['code'=>$code]); 
        }else{
            $this->error('该编号已被占用'); 
        } 
    }
    //根据层号获取有产品料位
    public function get_boxes(){
        $floor=$this->request->param('floor',0,'intval');
        $where=[
            'box.floor'=>$floor,
            'box.status'=>2,
            'box.goods'=>['gt',0],
        ];
        $list=Db::name('store_box')
        ->alias('box')
        ->join('cmf_goods goods','goods.id=box.goods')
        ->where($where)
        ->column('box.id,box.name,box.code,box.goods,goods.code as goods_code,goods.name as goods_name,box.num');
        $this->success('ok','',$list);
    }
    //根据仓库和产品获取料位和库存
    public function get_box_by_goods(){
        $store=$this->request->param('store',0,'intval');
        $goods=$this->request->param('goods',0,'intval');
        if($store<=0 || $goods<=0){
            $this->error('先选择产品和仓库');
        }
       //得到所有料位
        $where=[
            'store'=>$store,
            'goods'=>$goods,
            'status'=>2, 
        ];
        $list=Db::name('store_box') 
        ->where($where)
        ->column('id,name,code,num');
        if(empty($list)){
            $list='';
        }
        //检查库存
        $where=[
            'store'=>$store,
            'goods'=>$goods, 
        ];
        $store=Db::name('store_goods')->field('id,num,num1')->where($where)->find();
        if(empty($store)){
            $store='没有库存';
        }else{
            $store='库存为'.$store['num'].'('.$store['num1'].')';
        }
        $this->success('ok','',['box'=>$list,'store'=>$store]);
    }
    
}
