<?php
 
namespace app\shop\controller;

 
use cmf\controller\AdminBaseController; 
use think\Db; 
 
 /*
  * 获取公共信息都方法
  *   */
class Goods0Controller extends AdminBaseController
{
    
    public function _initialize()
    {
         
    }
      
    //获取分类信息
    public function cates(){
        //分类
        $m_cate=Db::name('cate');
        $where_cate=[
            'fid'=>0,
            'status'=>2,
        ];
        $cates0=$m_cate->where($where_cate)->order('sort asc,code_num asc')->column('id,name,code');
        $where_cate=[
            'fid'=>['neq',0],
            'status'=>['eq',2],
        ];
        $cates=$m_cate->where($where_cate)->order('sort asc,code_num asc')->column('id,name,fid,code');
        $this->assign('cates0',$cates0);
        $this->assign('cates',$cates);
    }
    //获取品牌信息
    public function brands(){
        //分类 
        $bcates=config('chars');
        $where_brand=[ 
            'status'=>['eq',2],
        ];
        $brands=Db::name('brand')->where($where_brand)->order('sort asc')->column('id,name,char');
        $this->assign('bcates',$bcates);
        $this->assign('brands',$brands);
    }
    
    //获取价格模板
    public function prices(){
        $where=[
            'status'=>2,
        ];
        $prices=Db::name('price')->where($where)->order('sort asc,name asc')->column('id,name');
        $this->assign('prices',$prices); 
    }
    
}
