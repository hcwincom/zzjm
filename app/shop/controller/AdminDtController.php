<?php
 
namespace app\shop\controller;

 
use app\common\controller\AdminInfo0Controller; 
use think\Db; 
  
class AdminDtController extends AdminInfo0Controller
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='部门';
        $this->table='dt';
        $this->m=Db::name('dt');
        $this->edit=['name','sort','dsc'];
        $this->search=[
            'name' => '名称', 
            'id' => 'id',
        ];
        //没有店铺区分
        $this->isshop=0;
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        
        
    }
    /**
     * 部门列表
     * @adminMenu(
     *     'name'   => '部门列表',
     *     'parent' => 'admin/User/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 2,
     *     'icon'   => '',
     *     'remark' => '部门列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
         parent::index();
        return $this->fetch();
    }
     
   
    /**
     * 部门添加
     * @adminMenu(
     *     'name'   => '部门添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '部门添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
        return $this->fetch();  
        
    }
    /**
     * 部门添加do
     * @adminMenu(
     *     'name'   => '部门添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '部门添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        parent::add_do();
        
    }
    /**
     * 部门详情
     * @adminMenu(
     *     'name'   => '部门详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '部门详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();  
        return $this->fetch();  
    }
    /**
     * 部门状态审核
     * @adminMenu(
     *     'name'   => '部门状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '部门状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 部门状态批量同意
     * @adminMenu(
     *     'name'   => '部门状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '部门状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    /**
     * 部门禁用
     * @adminMenu(
     *     'name'   => '信息状态禁用',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '信息状态禁用',
     *     'param'  => ''
     * )
     */
    public function ban()
    {
        parent::ban();
    }
    /**
     * 部门信息状态恢复
     * @adminMenu(
     *     'name'   => '部门信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '部门信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 部门编辑提交
     * @adminMenu(
     *     'name'   => '部门编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '部门编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
       
        parent::edit_do();
    }
    /**
     * 部门编辑列表
     * @adminMenu(
     *     'name'   => '部门编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '部门编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();  
    }
    
    /**
     * 部门审核详情
     * @adminMenu(
     *     'name'   => '部门审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '部门审核详情',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        parent::edit_info();
        return $this->fetch();  
    }
    /**
     * 部门信息编辑审核
     * @adminMenu(
     *     'name'   => '部门编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '部门编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
    /**
     * 部门编辑记录批量删除
     * @adminMenu(
     *     'name'   => '部门编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '部门编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    
    /**
     * 部门批量删除
     * @adminMenu(
     *     'name'   => '部门批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '部门批量删除',
     *     'param'  => ''
     * )
     */
    public function del_all()
    {
        if(empty($_POST['ids'])){
            $this->error('未选中信息');
        }
        $ids=$_POST['ids'];
        
        $m=$this->m;
      
        $admin=$this->admin;
        //其他店铺检查,如果没有shop属性就只能是1号主站操作,有shop属性就带上查询条件
        if($admin['shop']!=1){
            $this->error('店铺不能操作系统数据');
        } 
        if(in_array(1, $ids)){
            $this->error('总部门不能删除');
        }
        //彻底删除
        $where=['dt'=>['in',$ids]];
       //检查是否有用户
        $user=Db::name('user')->where($where)->find();
        if(!empty($user)){
            $this->error('部门下有用户，不能删除');
        }
        parent::del_all();
    }
   
     
     
}
