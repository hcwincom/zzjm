<?php
 
namespace app\shop\controller;

 
use app\common\controller\AdminInfo0Controller; 
use think\Db; 
use app\shop\model\DepartmentModel;
  
class AdminDepartmentController extends AdminInfo0Controller
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='部门小组';
        $this->table='department';
        $this->m=new DepartmentModel();
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
     * 部门小组列表
     * @adminMenu(
     *     'name'   => '部门小组列表',
     *     'parent' => 'admin/User/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 3,
     *     'icon'   => '',
     *     'remark' => '部门小组列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
         parent::index();
        return $this->fetch();
    }
     
   
    /**
     * 部门小组添加
     * @adminMenu(
     *     'name'   => '部门小组添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '部门小组添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
        return $this->fetch();  
        
    }
    /**
     * 部门小组添加do
     * @adminMenu(
     *     'name'   => '部门小组添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '部门小组添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        parent::add_do();
        
    }
    /**
     * 部门小组详情
     * @adminMenu(
     *     'name'   => '部门小组详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '部门小组详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();  
        return $this->fetch();  
    }
    /**
     * 部门小组状态审核
     * @adminMenu(
     *     'name'   => '部门小组状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '部门小组状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 部门小组状态批量同意
     * @adminMenu(
     *     'name'   => '部门小组状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '部门小组状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    /**
     * 部门小组禁用
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
     * 部门小组信息状态恢复
     * @adminMenu(
     *     'name'   => '部门小组信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '部门小组信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 部门小组编辑提交
     * @adminMenu(
     *     'name'   => '部门小组编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '部门小组编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
       
        parent::edit_do();
    }
    /**
     * 部门小组编辑列表
     * @adminMenu(
     *     'name'   => '部门小组编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '部门小组编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();  
    }
    
    /**
     * 部门小组审核详情
     * @adminMenu(
     *     'name'   => '部门小组审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '部门小组审核详情',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        parent::edit_info();
        return $this->fetch();  
    }
    /**
     * 部门小组信息编辑审核
     * @adminMenu(
     *     'name'   => '部门小组编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '部门小组编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
    /**
     * 部门小组编辑记录批量删除
     * @adminMenu(
     *     'name'   => '部门小组编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '部门小组编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    
    /**
     * 部门小组批量删除
     * @adminMenu(
     *     'name'   => '部门小组批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '部门小组批量删除',
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
        
        //彻底删除
        $where=['department'=>['in',$ids]];
       //检查是否有用户
        $user=Db::name('user')->where($where)->find();
        if(!empty($user)){
            $this->error('部门小组下有用户，不能删除');
        }
        parent::del_all();
    }
   
     public function cates($type=3){
         parent::cates($type);
         $m=$this->m;
         $dts=$m->get_all1();
         $this->assign('dts',$dts);
     }
     
     
}
