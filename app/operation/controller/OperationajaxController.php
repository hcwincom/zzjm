<?php
 
namespace app\operation\controller;

 
use app\common\controller\AdminBase0Controller; 
use think\Db; 
use app\goods\model\GoodsModel;
/*
 * 产品页面的ajax  */ 
class OperationajaxController extends AdminBase0Controller
{ 
    public function _initialize()
    { 
        parent::_initialize();
        //计算小数位
        bcscale(2); 
    }
    /*
     * 获取分类 */
    public function get_operation_cates()
    {
        $shop=session('where_shop');
        $where1=[
            'status'=>2,
            'fid'=>0,
            'shop'=>$shop,
        ];
        $list1=Db::name('operation_cate')->where($where1)->order('id asc')->column('id,name,code,type,fid','id');
        $where2=[
            'shop'=>$shop,
            'status'=>2,
            'fid'=>['gt',0],
        ];
        $list2=Db::name('operation_cate')->where($where2)->order('fid asc,sort asc')->column('id,name,code,type,fid','id');
        $this->success('ok','',['list1'=>$list1,'list2'=>$list2]);
    }
     
    /*
     * 根据id获取产品 */
    public function goods_add()
    {
        $id=$this->request->param('id',0,'intval');
        $admin=$this->admin;
        $shop=($admin['shop']==1)?2:$admin['shop']; 
        $where=[
            'id'=>$id,
            'shop'=>$shop,
        ]; 
        //先获取产品信息
        $goods=Db::name('goods')->field('id,name,code,name2,name3,code_name,dsc,sort')->where($where)->find();
        //在获取所有供应商名和客户名称  
        $where=[
            'goods'=>$id,
            'shop'=>$shop,
        ]; 
        $utmp=Db::name('custom_goods')->where($where)->column('id,name,cate');
        $unames=[];
        $ucates=[];
        foreach($utmp as $k=>$v){
            if(!empty($v['name'])){
                $unames[$v['name']]=$v['name'];
            }
            if(!empty($v['cate'])){
                $ucates[$v['cate']]=$v['cate'];
            }
            
        } 
        $goods['uname']=(empty($unames))?'':(implode(',', $unames));
        $goods['ucate']=(empty($ucates))?'':(implode(',', $ucates));
        
        //供应商名
        $utmp=Db::name('supplier_goods')->where($where)->column('id,name,cate');
        $unames=[];
        $ucates=[];
        foreach($utmp as $k=>$v){
            if(!empty($v['name'])){
                $unames[$v['name']]=$v['name'];
            }
            if(!empty($v['cate'])){
                $ucates[$v['cate']]=$v['cate'];
            }
            
        }
        $goods['sname']=(empty($unames))?'':(implode(',', $unames));
        $goods['scate']=(empty($ucates))?'':(implode(',', $ucates));
        $this->success('ok','',$goods);
    }
    
    /*
     * 根据ids获取产品关键字 */
    public function get_keywords()
    {
        $ids=$this->request->param('ids');
        zz_log($ids);
        $ids=explode(',', $ids);
        if(empty($ids)){
            $this->error('没有数据');
        }
        array_pop($ids);
        
        $admin=$this->admin;
        $shop=($admin['shop']==1)?2:$admin['shop'];
        $where=[
            'id'=>['in',$ids],
            'shop'=>$shop,
        ];
        $keywords=[];
        //先获取产品信息
        $goods=Db::name('goods')->where($where)->column('id,name,name2,name3,code_name');
        foreach ($goods as $k=>$v){
            if(!empty($v['name'])){
                $keywords[$v['name']]=$v['name'];
            }
            if(!empty($v['name2'])){
                $keywords[$v['name2']]=$v['name2'];
            }
            if(!empty($v['name3'])){
                $keywords[$v['name3']]=$v['name3'];
            }
            if(!empty($v['code_name'])){
                $keywords[$v['code_name']]=$v['code_name'];
            }
        }
        //在获取所有供应商名和客户名称
        $where=[
            'id'=>['in',$ids],
            'shop'=>$shop,
        ];
        $utmp=Db::name('custom_goods')->where($where)->column('name'); 
        foreach($utmp as $k=>$v){
            if(!empty($v)){
                $keywords[$v]=$v;
            } 
        }
        //供应商名
        $utmp=Db::name('supplier_goods')->where($where)->column('name');
        foreach($utmp as $k=>$v){
            if(!empty($v)){
                $keywords[$v]=$v;
            }
        }
         
        $keywords=(empty($keywords))?'':(implode(',', $keywords)); 
        zz_log($keywords);
        $this->success('ok','',$keywords);
    }
   
}
