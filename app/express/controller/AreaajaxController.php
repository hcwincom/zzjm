<?php
 
namespace app\express\controller;

use app\common\controller\AdminBase0Controller;  
use think\Db; 
 
  
class AreaajaxController extends AdminBase0Controller
{
     
    /*
     * 根据fid获取城市地区 */
    public function city()
    {
        $fid=$this->request->param('fid',1,'intval');
        $where=[
            'status'=>2,
            'fid'=>$fid,
        ];
        $citys=Db::name('area')->where($where)->order('sort asc,name asc')->column('id,name');
        $this->success('ok','',['list'=>$citys]);
    }
    
    //获取一个城市的区号，邮编
    public function city_one()
    {
        $id=$this->request->param('id',0,'intval');
        if($id<1){
            $this->error('数据错误');
        }
        $where=[
            'id'=>$id,
        ];
        $city=Db::name('area')->field('id,name,code,postcode,fid')->where($where)->find();
        $this->success('ok','',['name'=>$city['name'],'city_code'=>$city['code'],'postcode'=>$city['postcode'],'fid'=>$city['fid']]);
    }
    //获取同级的所有地址
    public function city_fid()
    {
       
        $id=$this->request->param('id',0,'intval');
        if($id<=1){
            $this->error('数据错误');
        }
        $where=[
            'id'=>$id,
        ];
        $fid=Db::name('area')->where($where)->value('fid');
        $where=[
            'fid'=>$fid,
        ];
        $citys=Db::name('area')->where($where)->column('id,name,code,postcode,fid');
        $this->success('ok','',$citys);
    }
    
    
}
