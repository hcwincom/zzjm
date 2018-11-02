<?php
 
namespace app\common\controller;


use cmf\controller\AdminBaseController; 
use think\Db; 
 /*
  * 取消了权限判断，只有登录判断的基类，主要用于一般的ajax
  *   */ 
class AdminBase0Controller extends AdminBaseController
{
    
    public function _initialize()
    {
        parent::_initialize();
        $session_admin_id = session('ADMIN_ID');
        if (!empty($session_admin_id)) {
            $user = Db::name('user')->where(['id' => $session_admin_id])->find();
            $this->admin=$user;
            
        } else {
           $this->error("您还没有登录！", url("admin/Public/login")); 
        }
    }
    
}