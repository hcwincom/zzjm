<?php
 
namespace app\express\controller;

 
use app\common\controller\AdminInfo0Controller; 
use think\Db; 
  
class AdminAreaController extends AdminInfo0Controller
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='地区';
        $this->table='area';
        $this->m=Db::name('area');
        $this->edit=['name','sort','dsc','code','postcode',];
        //没有店铺区分
        $this->isshop=0;
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        
    }
    /**
     * 地区列表
     * @adminMenu(
     *     'name'   => '地区列表',
     *     'parent' => 'express/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 11,
     *     'icon'   => '',
     *     'remark' => '地区列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
         parent::index();
        return $this->fetch();
    }
     
   
    /**
     * 地区添加
     * @adminMenu(
     *     'name'   => '地区添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '地区添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
        return $this->fetch();  
        
    }
    /**
     * 地区添加do
     * @adminMenu(
     *     'name'   => '地区添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '地区添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        parent::add_do();
        
    }
    /**
     * 地区详情
     * @adminMenu(
     *     'name'   => '地区详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '地区详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();  
        return $this->fetch();  
    }
    /**
     * 地区状态审核
     * @adminMenu(
     *     'name'   => '地区状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '地区状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 地区状态批量同意
     * @adminMenu(
     *     'name'   => '地区状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '地区状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    /**
     * 地区禁用
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
     * 地区信息状态恢复
     * @adminMenu(
     *     'name'   => '地区信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '地区信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 地区编辑提交
     * @adminMenu(
     *     'name'   => '地区编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '地区编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        parent::edit_do();
    }
    /**
     * 地区编辑列表
     * @adminMenu(
     *     'name'   => '地区编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '地区编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();  
    }
    
    /**
     * 地区审核详情
     * @adminMenu(
     *     'name'   => '地区审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '地区审核详情',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        parent::edit_info();
        return $this->fetch();  
    }
    /**
     * 地区信息编辑审核
     * @adminMenu(
     *     'name'   => '地区编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '地区编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
    /**
     * 地区编辑记录批量删除
     * @adminMenu(
     *     'name'   => '地区编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '地区编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    
    /**
     * 地区批量删除
     * @adminMenu(
     *     'name'   => '地区批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '地区批量删除',
     *     'param'  => ''
     * )
     */
    public function del_all()
    {
         
        parent::del_all();
    }
   
     
}
