<?php
 
namespace app\portal\controller;

use cmf\controller\HomeBaseController;
use think\db;

class PublicController extends HomeBaseController
{

     /* 
      * 根据fid获取城市地区 */
    public function city($fid=0,$all=0)
    {
        $where=[
            'status'=>2,
            'fid'=>intval($fid),
        ];
        if($all==0){
            $filed='id,name';
        }else{
            $filed='id,name,code,postcode';
        }
        $citys=Db::name('area')->where($where)->order('sort asc,name asc')->column($filed);
        $this->success('ok','',['list'=>$citys]);
    }

}
