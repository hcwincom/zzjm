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

/**
 * Class UserController
 * @package app\admin\controller
 * @adminMenuRoot(
 *     'name'   => '管理组',
 *     'action' => 'default',
 *     'parent' => 'user/AdminIndex/default',
 *     'display'=> true,
 *     'order'  => 10000,
 *     'icon'   => '',
 *     'remark' => '管理组'
 * )
 */
class UserController extends AdminBaseController
{
    public function _initialize()
    {
        parent::_initialize();
         
        $this->assign('jobs',[1=>'经理',2=>'员工']);
        $this->assign('job_statuss',[1=>'试用期',2=>'已转正',3=>'已离职']);
        
    }

    /**
     * 管理员列表
     * @adminMenu(
     *     'name'   => '管理员',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理员管理',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        
        $types=config('user_search');
        $search_types=config('search_types');
        $where = ["p.user_type" => ['eq',1]];
        $admin=$this->admin;
        
        /**搜索条件**/
        $data = $this->request->param();
        $res=zz_shop($admin, $data, $where,'p.shop');
        $data=$res['data'];
        $where=$res['where'];
        
        $res=zz_search_param($types, $search_types, $data, $where,['alias'=>'p.']);
        $data=$res['data'];
        $where=$res['where'];
        $ids0 = Db::name('user')
        ->field('p.id')
        ->alias('p') 
        ->where($where)
        ->order("p.id asc")
        ->paginate();
        // 获取分页显示
        $page = $ids0->appends($data)->render();
        $ids=[];
        foreach($ids0 as $v){
            $ids[]=$v['id'];
        }
        if(empty($ids)){
            $users=[];
            $roles=[];
        }else{
            
            $users = Db::name('user')
            ->field('p.*,shop.name as shop_name,dt.name as dt_name')
            ->alias('p')
            ->join('cmf_shop shop','shop.id=p.shop','left')
            ->join('cmf_department dt','dt.id=p.department','left')
            ->where('p.id','in',$ids) 
            ->column('p.*,shop.name as shop_name,dt.name as dt_name');
            //角色信息
            $roles_user=Db::name('role_user')
            ->alias('ru')
            ->join('cmf_role r','r.id=ru.role_id')
            ->where('ru.user_id','in',$ids)
            ->column('ru.*,r.name as rname');
            $roles_user[0]=['id'=>0,'role_id'=>1,'user_id'=>1,'rname'=>'系统超管'];
            foreach($roles_user as $v){
                if(empty($users[$v['user_id']])){
                   continue;
                }
                if(isset($users[$v['user_id']]['roles'])){
                    $users[$v['user_id']]['roles'].=','.$v['rname'];
                }else{
                    $users[$v['user_id']]['roles']=$v['rname'];
                }
                
            }
        }
        
        if($admin['shop']==1){
            $shops=Db::name('shop')->where('status',2)->order('sort asc')->column('id,name');
            $this->assign("shops", $shops);
        }
        $this->assign("page", $page);
      
        $this->assign("users", $users);
        $this->assign("data", $data);
        $this->assign("types", $types);
        $this->assign("search_types", $search_types);
        return $this->fetch();
    }

    /**
     * 管理员添加
     * @adminMenu(
     *     'name'   => '管理员添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理员添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        
        $where=[
            'status'=>['eq',1], 
        ];
        //只能添加比自己权限小的角色 ，即list_order>=自己
        $admin=$this->admin;
        $aid=$admin['id'];
        if($aid!=1){
            $roles=Db::name('role_user')
            ->field('r.list_order')
            ->alias('ru')
            ->join('cmf_role r','ru.role_id=r.id')
            ->where('ru.user_id',$aid)
            ->order('r.list_order asc')
            ->find();
            $where['list_order']=['egt',$roles['list_order']];
        }
        $roles = Db::name('role')->where($where)->order("list_order asc,id asc")->select();
        //商家 
        if($admin['shop']==1){ 
            $m_shop=Db::name('shop');
            $where_shop=['status'=>2];
            $shops=$m_shop->where($where_shop)->column('id,name'); 
        }else{
            $shops=[];
        }
        //部门
        $where_dt=['status'=>2];
        $departments=Db::name('department')->where($where_dt)->column('id,name');
        
        $this->assign("shops", $shops);
        $this->assign("departments", $departments);
        $this->assign("roles", $roles);
        return $this->fetch();
    }

    /**
     * 管理员添加提交
     * @adminMenu(
     *     'name'   => '管理员添加提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理员添加提交',
     *     'param'  => ''
     * )
     */
    public function addPost()
    {
        if(empty($_POST['role_id'])){
            $this->error('管理员角色未选择');
        }
        //zz添加验证添加角色权限小于自己
        $where=[
            'status'=>['eq',1], 
        ];
        $admin=$this->admin;
        $aid=$admin['id'];
        if($aid!=1){
            $roles=Db::name('role_user')
            ->field('r.list_order')
            ->alias('ru')
            ->join('cmf_role r','ru.role_id=r.id')
            ->where('ru.user_id',$aid)
            ->order('r.list_order asc')
            ->find();
            $where['list_order']=['egt',$roles['list_order']];
        }
        $roles = Db::name('role')->where($where)->column('id');
        $role_ids = $_POST['role_id'];
        //对比数组得到在$role_ids中却不在$roles中的值，如果有就错误了
        $result = array_diff($role_ids, $roles);
        if(!empty($result)){
            $this->error('数据错误');
        }
        unset($where);
        //原程序
        if ($this->request->isPost()) {
            if (!empty($_POST['role_id']) && is_array($_POST['role_id'])) {
                $role_ids = $_POST['role_id'];
                unset($_POST['role_id']);
                $result = $this->validate($this->request->param(), 'User');
                if ($result !== true) {
                    $this->error($result);
                } else {
                    //zz添加默认昵称,添加用户
                    $data=$this->request->param();
                    $reg=config('reg');
                    if(preg_match($reg['user_login'][0], $data['user_login'])!=1){
                        $this->error($reg['user_login'][1]);
                    }
                    if(empty($data['user_nickname'])){
                        $data['user_nickname']=$data['user_login'];
                    }
                    if(preg_match($reg['mobile'][0], $data['mobile'])!=1){
                        $this->error($reg['mobile'][1]);
                    }
                    
                    $data_user=[
                        'user_login'=>$data['user_login'],
                        'user_nickname'=>$data['user_nickname'],
                        'user_email'=>$data['user_email'],
                        'user_pass'=>cmf_password($data['user_pass']),
                        'mobile'=>$data['mobile'],
                        //判断是总站添加还是分站添加
                        'shop'=>(($admin['shop']==1)?$data['shop']:$admin['shop']),
                        'department'=>$data['department'],
                    ];
                    
                    $result = DB::name('user')->insertGetId($data_user);
                    if ($result !== false) {
                        //$role_user_model=M("RoleUser");
                        foreach ($role_ids as $role_id) {
                            if (cmf_get_current_admin_id() != 1 && $role_id == 1) {
                                $this->error("为了网站的安全，非网站创建者不可创建超级管理员！");
                            }
                            Db::name('RoleUser')->insert(["role_id" => $role_id, "user_id" => $result]);
                        }
                        $this->success("添加成功！", url("user/index"));
                    } else {
                        $this->error("添加失败！");
                    }
                }
            } else {
                $this->error("请为此用户指定角色！");
            }

        }
    }

    /**
     * 管理员编辑
     * @adminMenu(
     *     'name'   => '管理员编辑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理员编辑',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $id    = $this->request->param('id', 0, 'intval');
        $roles = DB::name('role')->where(['status' => 1])->order("id DESC")->select();
        $this->assign("roles", $roles);
        $role_ids = DB::name('RoleUser')->where(["user_id" => $id])->column("role_id");
        $this->assign("role_ids", $role_ids);
        //部门
        $where_dt=['status'=>2];
        $departments=Db::name('department')->where($where_dt)->column('id,name');
         
        $this->assign("departments", $departments);
        $user = DB::name('user') 
        ->where(["id" => $id])
        ->find();
        if(empty($user['in_time'])){
            $user['in_time']='';
        }else{
            $user['in_time']=date('Y-m-d', $user['in_time']);
        }
        if(empty($user['on_time'])){
            $user['on_time']='';
        }else{
            $user['on_time']=date('Y-m-d', $user['on_time']);
        }
        if(empty($user['out_time'])){
            $user['out_time']='';
        }else{
            $user['out_time']=date('Y-m-d', $user['out_time']);
        }
        $this->assign($user);
        
        return $this->fetch();
    }

    /**
     * 管理员编辑提交
     * @adminMenu(
     *     'name'   => '管理员编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理员编辑提交',
     *     'param'  => ''
     * )
     */
    public function editPost()
    {
        if ($this->request->isPost()) {
            if (!empty($_POST['role_id']) && is_array($_POST['role_id'])) {
                if (empty($_POST['user_pass'])) {
                    unset($_POST['user_pass']);
                } else {
                    $_POST['user_pass'] = cmf_password($_POST['user_pass']);
                }
                $role_ids = $this->request->param('role_id/a');
                unset($_POST['role_id']);
                $result = $this->validate($this->request->param(), 'User.edit');

                if ($result !== true) {
                    // 验证失败 输出错误信息
                    $this->error($result);
                } else {
                    //zz添加默认昵称,逐个添加用户
                    $data=$this->request->param();
                    $reg=config('reg');
                    if(preg_match($reg['user_login'][0], $data['user_login'])!=1){
                        $this->error($reg['user_login'][1]);
                    }
                    
                    if(empty($data['user_nickname'])){
                        $data['user_nickname']=$data['user_login'];
                    }
                    if(preg_match($reg['mobile'][0], $data['mobile'])!=1){
                        $this->error($reg['mobile'][1]);
                    }
                    $data_user=[
                        'id'=>$data['id'],
                        'user_login'=>$data['user_login'],
                        'user_nickname'=>$data['user_nickname'],
                        'user_email'=>$data['user_email'], 
                        'mobile'=>$data['mobile'], 
                        'department'=>$data['department'],
                        'emergency_mobile'=>$data['emergency_mobile'],
                        'idcard'=>$data['idcard'],
                        'address'=>$data['address'],
                        'qq'=>$data['qq'],
                        'weixin'=>$data['weixin'],
                        'wangwang'=>$data['wangwang'],
                        'job_status'=>intval($data['job_status']),
                    ];
                    if(!empty($data['in_time'])){ 
                        $data_user['in_time']=strtotime($data['in_time']);
                    }
                    if(!empty($data['on_time'])){ 
                        $data_user['on_time']=strtotime($data['on_time']);
                    }
                    if(!empty($data['out_time'])){
                        $data_user['out_time']=strtotime($data['out_time']);
                    }
                    
                    if(!empty($_POST['user_pass'])){
                        $data_user['user_pass']=$_POST['user_pass'];
                    }
                    $result = DB::name('user')->update($data_user);
                    if ($result !== false) {
                        $uid = $this->request->param('id', 0, 'intval');
                        DB::name("RoleUser")->where(["user_id" => $uid])->delete();
                        foreach ($role_ids as $role_id) {
                            if (cmf_get_current_admin_id() != 1 && $role_id == 1) {
                                $this->error("为了网站的安全，非网站创建者不可创建超级管理员！");
                            }
                            DB::name("RoleUser")->insert(["role_id" => $role_id, "user_id" => $uid]);
                        }
                        $this->success("保存成功！");
                    } else {
                        $this->error("保存失败！");
                    }
                }
            } else {
                $this->error("请为此用户指定角色！");
            }

        }
    }

    /**
     * 管理员个人信息修改
     * @adminMenu(
     *     'name'   => '个人信息',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理员个人信息修改',
     *     'param'  => ''
     * )
     */
    public function userInfo()
    {
        $id   = cmf_get_current_admin_id();
        $user = Db::name('user')->where(["id" => $id])->find();
         
        $dt=Db::name('department')->where('id',$user['department'])->value('name');
        $this->assign($user);
        $this->assign('dt',$dt);
        return $this->fetch();
    }

    /**
     * 管理员个人信息修改提交
     * @adminMenu(
     *     'name'   => '管理员个人信息修改提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理员个人信息修改提交',
     *     'param'  => ''
     * )
     */
    public function userInfoPost()
    {
        if ($this->request->isPost()) {

            $data             = $this->request->post();
            $data['birthday'] = strtotime($data['birthday']);
            $data['id']       = cmf_get_current_admin_id();
            $reg=config('reg');
             
            if(preg_match($reg['mobile'][0], $data['mobile'])!=1){
                $this->error($reg['mobile'][1]);
            }
            $data_user=[
                'id'=>$data['id'],  
                'mobile'=>$data['mobile'],
                'birthday'=>$data['birthday'],
                'sex'=>$data['sex'],
            ];
            
            $create_result    = Db::name('user')->update($data);;
            if ($create_result !== false) {
                $this->success("保存成功！");
            } else {
                $this->error("保存失败！");
            }
        }
    }

    /**
     * 管理员删除
     * @adminMenu(
     *     'name'   => '管理员删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理员删除',
     *     'param'  => ''
     * )
     */
    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        if ($id == 1) {
            $this->error("最高管理员不能删除！");
        }

        if (Db::name('user')->delete($id) !== false) {
            Db::name("RoleUser")->where(["user_id" => $id])->delete();
            $this->success("删除成功！");
        } else {
            $this->error("删除失败！");
        }
    }

    /**
     * 停用管理员
     * @adminMenu(
     *     'name'   => '停用管理员',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '停用管理员',
     *     'param'  => ''
     * )
     */
    public function ban()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (!empty($id)) {
            $result = Db::name('user')->where(["id" => $id, "user_type" => 1])->setField('user_status', '0');
            if ($result !== false) {
                $this->success("管理员停用成功！", url("user/index"));
            } else {
                $this->error('管理员停用失败！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }

    /**
     * 启用管理员
     * @adminMenu(
     *     'name'   => '启用管理员',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '启用管理员',
     *     'param'  => ''
     * )
     */
    public function cancelBan()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (!empty($id)) {
            $result = Db::name('user')->where(["id" => $id, "user_type" => 1])->setField('user_status', '1');
            if ($result !== false) {
                $this->success("管理员启用成功！", url("user/index"));
            } else {
                $this->error('管理员启用失败！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }
}