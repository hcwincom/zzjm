<?php
 
namespace app\money\controller;
 
use think\Db; 
use app\common\controller\AdminInfo0Controller;
 
 
 
class AdminFeeController extends AdminInfo0Controller
{
    
    public function _initialize()
    {
        parent::_initialize();
        $this->isshop=1;
        $this->flag='店铺费用';
        $this->table='shop_fee';
        $this->m=Db::name('shop_fee');
          
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        $this->assign('param_types',[1=>'每年缴纳',2=>'每月缴纳',3=>'不定期']);
        $this->edit=['name','sort','dsc','cid','fee','type','month','day','last_day'];
    }
    /**
     * 店铺费用列表
     * @adminMenu(
     *     'name'   => '店铺费用列表', 
     *     'parent' => 'money/AdminIndex/default', 
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 21,
     *     'icon'   => '',
     *     'remark' => '店铺费用列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
         parent::index();
        return $this->fetch();
    }
     
   
    /**
     * 店铺费用添加
     * @adminMenu(
     *     'name'   => '店铺费用添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '店铺费用添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
        return $this->fetch();  
        
    }
    /**
     * 店铺费用添加do
     * @adminMenu(
     *     'name'   => '店铺费用添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '店铺费用添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    { 
        parent::add_do();
    }
    /**
     * 店铺费用详情
     * @adminMenu(
     *     'name'   => '店铺费用详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '店铺费用详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();  
        return $this->fetch();  
    }
    /**
     * 店铺费用状态审核
     * @adminMenu(
     *     'name'   => '店铺费用状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '店铺费用状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 店铺费用状态批量同意
     * @adminMenu(
     *     'name'   => '店铺费用状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '店铺费用状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    /**
     * 店铺费用禁用
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
     * 店铺费用信息状态恢复
     * @adminMenu(
     *     'name'   => '店铺费用信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '店铺费用信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 店铺费用编辑提交
     * @adminMenu(
     *     'name'   => '店铺费用编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '店铺费用编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        parent::edit_do();
    }
    /**
     * 店铺费用编辑列表
     * @adminMenu(
     *     'name'   => '店铺费用编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '店铺费用编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();  
    }
    
    /**
     * 店铺费用审核详情
     * @adminMenu(
     *     'name'   => '店铺费用审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '店铺费用审核详情',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        parent::edit_info();
        return $this->fetch();  
    }
    /**
     * 店铺费用信息编辑审核
     * @adminMenu(
     *     'name'   => '店铺费用编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '店铺费用编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
    /**
     * 店铺费用编辑记录批量删除
     * @adminMenu(
     *     'name'   => '店铺费用编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '店铺费用编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    
    /**
     * 店铺费用批量删除
     * @adminMenu(
     *     'name'   => '店铺费用批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '店铺费用批量删除',
     *     'param'  => ''
     * )
     */
    public function del_all()
    {
        
        parent::del_all();
    }
    
    public function cates($type=3){
        parent::cates($type);
        $where_shop=$this->where_shop;
        $where=[
            'shop'=>$where_shop,
            'status'=>2,
        ];
        $cates=Db::name('shop_fee_cate')->where($where)->column('id,name');
        $this->assign('cates',$cates);
    }
     
}
