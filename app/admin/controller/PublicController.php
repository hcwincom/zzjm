<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class PublicController extends AdminBaseController
{
    public function _initialize()
    {
        
    }

    /**
     * 后台登陆界面
     */
    public function login()
    {
        $this->assign('zzsite',config('zzsite'));
        $loginAllowed = session("__LOGIN_BY_CMF_ADMIN_PW__");
        if (empty($loginAllowed)) {
            //$this->error('非法登录!', cmf_get_root() . '/');
            return redirect(cmf_get_root() . "/");
        }

        $admin_id = session('ADMIN_ID');
        if (!empty($admin_id)) {//已经登录
            return redirect(url("admin/Index/index"));
        } else {
            $site_admin_url_password = config("cmf_SITE_ADMIN_URL_PASSWORD");
            $upw                     = session("__CMF_UPW__");
            if (!empty($site_admin_url_password) && $upw != $site_admin_url_password) {
                return redirect(cmf_get_root() . "/");
            } else {
                session("__SP_ADMIN_LOGIN_PAGE_SHOWED_SUCCESS__", true);
                $result = hook_one('admin_login');
                if (!empty($result)) {
                    return $result;
                }
                return $this->fetch(":login");
            }
        }
    }

    /**
     * 登录验证
     */
    public function doLogin()
    {
        $loginAllowed = session("__LOGIN_BY_CMF_ADMIN_PW__");
        if (empty($loginAllowed)) {
            $this->error('非法登录!', cmf_get_root() . '/');
        }

       /*  $captcha = $this->request->param('captcha');
        if (empty($captcha)) {
            $this->error(lang('CAPTCHA_REQUIRED'));
        }
        //验证码
        if (!cmf_captcha_check($captcha)) {
            $this->error(lang('CAPTCHA_NOT_RIGHT'));
        } */

        $name = $this->request->param("username");
        if (empty($name)) {
            $this->error(lang('USERNAME_OR_EMAIL_EMPTY'));
        }
        $pass = $this->request->param("password");
        if (empty($pass)) {
            $this->error(lang('PASSWORD_REQUIRED'));
        }
        if (strpos($name, "@") > 0) {//邮箱登陆
            $where['user_email'] = $name;
        } else {
            $where['user_login'] = $name;
        }

        $result = Db::name('user')->where($where)->find();

        if (!empty($result) && $result['user_type'] == 1) {
            if (cmf_compare_password($pass, $result['user_pass'])) {
                $groups = Db::name('RoleUser')
                    ->alias("a")
                    ->join('__ROLE__ b', 'a.role_id =b.id')
                    ->where(["user_id" => $result["id"], "status" => 1])
                    ->value("role_id");
                if ($result["id"] != 1 && (empty($groups) || empty($result['user_status']))) {
                    $this->error(lang('USE_DISABLED'));
                }
                //登入成功页面跳转
                session('ADMIN_ID', $result["id"]);
                session('name', $result["user_login"]);
                 
                $result['last_login_ip']   = get_client_ip(0, true);
                $result['last_login_time'] = time();
                $token                     = cmf_generate_user_token($result["id"], 'web');
                if (!empty($token)) {
                    session('token', $token);
                }
                Db::name('user')->update($result);
                cookie("admin_username", $name, 3600 * 24 * 30);
                session("__LOGIN_BY_CMF_ADMIN_PW__", null);
                $this->success(lang('LOGIN_SUCCESS'), url("admin/Index/index"));
            } else {
                $this->error(lang('PASSWORD_NOT_RIGHT'));
            }
        } else {
            $this->error(lang('USERNAME_NOT_EXIST'));
        }
    }

    /**
     * 后台管理员退出
     */
    public function logout()
    {
        session('ADMIN_ID', null);
        return redirect(url('/', [], false, true));
    }
    /**
     * 消息提醒
     */
    public function msg_new()
    {
        $uid=session('ADMIN_ID');
        $m=db('msg');
        $where=[
            'uid'=>$uid,
            'status'=>1,
        ];
        $list=$m->where($where)->column('id,dsc,link');
        if(empty($list)){
            return null;
        }else{
            return json_encode($list);
        }
       
    }
    
    /**
     * 文件下载
     */
    public function file_load()
    {
      
        $str=$_REQUEST['s'];
       
        $arr=explode(',',$str);
        $info['file']=substr($arr[1], 0,strrpos($arr[1],'.'));
        $info['name']=substr($arr[0], strrpos($arr[0],'/')+1);
       
        $path='upload/';
        $file=$path.$info['file'];
        $filename=empty($info['name'])?date('Ymd-His'):$info['name'];
        if(is_file($file)){
            $fileinfo=pathinfo($file);
            $ext=$fileinfo['extension'];
            $filename=$filename.'.'.$ext;
            header('Content-type: application/x-'.$ext);
            header('content-disposition:attachment;filename='.$filename);
            header('content-length:'.filesize($file));
            readfile($file);
            exit;
        }else{
            $this->error('文件损坏，不存在');
        }
    }
    /**
     * 下载修改的文件 
     */
    public function change_load()
    {
         $data=$this->request->param();
        $change=db('edit_info')->where('id',$data['eid'])->value('content');
        if(empty($change)){
            $this->error('未找到修改数据');
        }
        $change=json_decode($change,true);
        
        if(empty($change[$data['name']])){
            $this->error('未找到要下载的文件');
        }
        $files=$change[$data['name']];
        //判断是多文件还是单文件
        if(isset($data['key'])){
            $files=json_decode($files,true);
            $file=$files[$data['key']]['file'];
            $filename=$files[$data['key']]['name'];
        }else{
            $file=$change[$data['name']];
            $filename=date('Ymd-His');
        }
       
        $path='upload/';
        $file=$path.$file;
        
        if(is_file($file)){
            $fileinfo=pathinfo($file);
            $ext=$fileinfo['extension'];
            $filename=$filename.'.'.$ext;
            header('Content-type: application/x-'.$ext);
            header('content-disposition:attachment;filename='.$filename);
            header('content-length:'.filesize($file));
            readfile($file);
            exit;
        }else{
            $this->error('文件损坏，不存在');
        }
    }
    
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
   
}