<?php
 
namespace app\order\controller;

 
use app\common\controller\AdminInfo0Controller; 
use think\Db; 
  
class OrderBaseController extends AdminInfo0Controller
{
    
    public function _initialize()
    {
        parent::_initialize();
        
        //没有店铺区分
        $this->isshop=1;
        
    }
    
}
