<?php
 
namespace app\custom\controller;

 
use think\Db; 
/**
 * Class AdminSupplierController
 * @package app\custom\controller
 * @adminMenuRoot(
 *     'name'   =>'供货商管理',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 41,
 *     'icon'   =>'',
 *     'remark' =>'供货商管理'
 * )
 */
class AdminSupplierController extends CustomBaseController
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='供货商';
        $this->table='supplier';
        $this->m=Db::name('supplier');
        
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        
    }
    /**
     * 供货商列表
     * @adminMenu(
     *     'name'   => '供货商列表',
     *     'parent' => 'custom/AdminSupplier/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '供货商列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
         parent::index();
        return $this->fetch();
    }
     
   
    /**
     * 供货商添加
     * @adminMenu(
     *     'name'   => '供货商添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '供货商添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
        return $this->fetch();  
        
    }
    /**
     * 供货商添加do
     * @adminMenu(
     *     'name'   => '供货商添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '供货商添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
       parent::add_do();
        
    }
    /**
     * 供货商详情
     * @adminMenu(
     *     'name'   => '供货商详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '供货商详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();
       
        return $this->fetch();  
    }
    /**
     * 供货商状态审核
     * @adminMenu(
     *     'name'   => '供货商状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '供货商状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 供货商状态批量同意
     * @adminMenu(
     *     'name'   => '供货商状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '供货商状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    /**
     * 供货商禁用
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
     * 供货商信息状态恢复
     * @adminMenu(
     *     'name'   => '供货商信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '供货商信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 供货商编辑提交
     * @adminMenu(
     *     'name'   => '供货商编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '供货商编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        parent::edit_do();
    }
    /**
     * 供货商编辑列表
     * @adminMenu(
     *     'name'   => '供货商编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '供货商编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();  
    }
    
    /**
     * 供货商审核详情
     * @adminMenu(
     *     'name'   => '供货商审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '供货商审核详情',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        parent::edit_info();
        return $this->fetch();  
    }
    /**
     * 供货商信息编辑审核
     * @adminMenu(
     *     'name'   => '供货商编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '供货商编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
    /**
     * 供货商编辑记录批量删除
     * @adminMenu(
     *     'name'   => '供货商编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '供货商编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    
    /**
     * 供货商批量删除
     * @adminMenu(
     *     'name'   => '供货商批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '供货商批量删除',
     *     'param'  => ''
     * )
     */
    public function del_all()
    {
         
        parent::del_all();
    }
    /**
     * 客户联系人详情
     * @adminMenu(
     *     'name'   => '客户联系人详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户联系人详情',
     *     'param'  => ''
     * )
     */
    public function tel_edit()
    {
        parent::tel_edit();
        
        return $this->fetch();
    }
    /**
     * 客户联系人编辑提交
     * @adminMenu(
     *     'name'   => '客户联系人编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户联系人编辑提交',
     *     'param'  => ''
     * )
     */
    public function tel_edit_do()
    {
        parent::tel_edit_do();
    }
    /**
     * 客户联系人审核详情
     * @adminMenu(
     *     'name'   => '客户联系人审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户联系人审核详情',
     *     'param'  => ''
     * )
     */
    public function tel_edit_info()
    {
        parent::tel_edit_info();
        return $this->fetch();
    }
    /**
     * 客户联系人信息编辑审核
     * @adminMenu(
     *     'name'   => '客户联系人编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户联系人编辑审核',
     *     'param'  => ''
     * )
     */
    public function tel_edit_review()
    {
        parent::tel_edit_review();
    }
    
    /**
     * 客户供应产品详情
     * @adminMenu(
     *     'name'   => '客户供应产品详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户供应产品详情',
     *     'param'  => ''
     * )
     */
    public function goods_edit()
    {
        parent::goods_edit();
        
        return $this->fetch();
    }
    /**
     * 客户供应产品编辑提交
     * @adminMenu(
     *     'name'   => '客户供应产品编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户供应产品编辑提交',
     *     'param'  => ''
     * )
     */
    public function goods_edit_do()
    {
        parent::goods_edit_do();
    }
    /**
     * 客户供应产品审核详情
     * @adminMenu(
     *     'name'   => '客户供应产品审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户供应产品审核详情',
     *     'param'  => ''
     * )
     */
    public function goods_edit_info()
    {
        parent::goods_edit_info();
        return $this->fetch();
    }
    /**
     * 客户供应产品信息编辑审核
     * @adminMenu(
     *     'name'   => '客户供应产品编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户供应产品编辑审核',
     *     'param'  => ''
     * )
     */
    public function goods_edit_review()
    {
        parent::goods_edit_review();
    }
    
    /**
     * 供应商产品供应列表
     * @adminMenu(
     *     'name'   => '供应商产品供应列表',
     *     'parent' => 'custom/AdminSupplier/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '供应商产品供应列表',
     *     'param'  => ''
     * )
     */
    public function goods_list()
    {
        parent::goods_list();
        return $this->fetch();
    }
     
}
