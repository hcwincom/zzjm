<?php
 
namespace app\goods\controller;
 
use think\Db; 
 
 
/**
 * Class AdminFeeController
 * @package app\admin\controller
 *
 * @adminMenuRoot(
 *     'name'   =>'价格参数',
 *     'action' =>'ii',
 *     'parent' =>'goods/AdminIndex/default',
 *     'display'=> true,
 *     'order'  => 30,
 *     'icon'   =>'',
 *     'remark' =>'价格参数'
 * )
 */
class AdminFeeController extends GoodsBaseController
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='价格参数';
        $this->table='fee';
        $this->m=Db::name('fee');
          
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        $this->assign('param_types',[1=>'固定值',2=>'比例']);
        $this->edit=['name','sort','dsc','cid','fee','type'];
    }
    /**
     * 价格参数列表
     * @adminMenu(
     *     'name'   => '价格参数列表',
     *     'parent' => 'ii',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格参数列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
         parent::index();
        return $this->fetch();
    }
     
   
    /**
     * 价格参数添加
     * @adminMenu(
     *     'name'   => '价格参数添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格参数添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
        return $this->fetch();  
        
    }
    /**
     * 价格参数添加do
     * @adminMenu(
     *     'name'   => '价格参数添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格参数添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    { 
        parent::add_do();
    }
    /**
     * 价格参数详情
     * @adminMenu(
     *     'name'   => '价格参数详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格参数详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();  
        return $this->fetch();  
    }
    /**
     * 价格参数状态审核
     * @adminMenu(
     *     'name'   => '价格参数状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格参数状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 价格参数状态批量同意
     * @adminMenu(
     *     'name'   => '价格参数状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格参数状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    /**
     * 价格参数禁用
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
     * 价格参数信息状态恢复
     * @adminMenu(
     *     'name'   => '价格参数信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格参数信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 价格参数编辑提交
     * @adminMenu(
     *     'name'   => '价格参数编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格参数编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        parent::edit_do();
    }
    /**
     * 价格参数编辑列表
     * @adminMenu(
     *     'name'   => '价格参数编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格参数编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();  
    }
    
    /**
     * 价格参数审核详情
     * @adminMenu(
     *     'name'   => '价格参数审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格参数审核详情',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        parent::edit_info();
        return $this->fetch();  
    }
    /**
     * 价格参数信息编辑审核
     * @adminMenu(
     *     'name'   => '价格参数编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格参数编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
    /**
     * 价格参数编辑记录批量删除
     * @adminMenu(
     *     'name'   => '价格参数编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格参数编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    
    /**
     * 价格参数批量删除
     * @adminMenu(
     *     'name'   => '价格参数批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格参数批量删除',
     *     'param'  => ''
     * )
     */
    public function del_all()
    {
        
        parent::del_all();
    }
   
     
}
