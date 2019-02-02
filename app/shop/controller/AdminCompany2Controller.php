<?php
 
namespace app\shop\controller;
  
class AdminCompany2Controller extends CompanyBaseController
{
    
    public function _initialize()
    {
        parent::_initialize(); 
        $this->flag='淘宝店铺'; 
        $this->company_type=2; 
        $this->edit=['name','sort','dsc','code','allname','account_name','account_bank','account_num',
            'contact','address','store','paytype','key_account','key_key','company_url','goods_url'
        ];
        $this->assign('flag',$this->flag);
    }
    /**
     * 淘宝店铺列表
     * @adminMenu(
     *     'name'   => '淘宝店铺列表',
     *     'parent' => 'shop/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 2,
     *     'icon'   => '',
     *     'remark' => '淘宝店铺列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
         parent::index();
        return $this->fetch();
    }
     
   
    /**
     * 淘宝店铺添加
     * @adminMenu(
     *     'name'   => '淘宝店铺添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '淘宝店铺添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
        return $this->fetch();  
        
    }
    /**
     * 淘宝店铺添加do
     * @adminMenu(
     *     'name'   => '淘宝店铺添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '淘宝店铺添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        parent::add_do();
        
    }
    /**
     * 淘宝店铺详情
     * @adminMenu(
     *     'name'   => '淘宝店铺详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '淘宝店铺详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();  
        return $this->fetch();  
    }
    /**
     * 淘宝店铺状态审核
     * @adminMenu(
     *     'name'   => '淘宝店铺状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '淘宝店铺状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 淘宝店铺状态批量同意
     * @adminMenu(
     *     'name'   => '淘宝店铺状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '淘宝店铺状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    /**
     * 淘宝店铺禁用
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
     * 淘宝店铺信息状态恢复
     * @adminMenu(
     *     'name'   => '淘宝店铺信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '淘宝店铺信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 淘宝店铺编辑提交
     * @adminMenu(
     *     'name'   => '淘宝店铺编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '淘宝店铺编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
       
        parent::edit_do();
    }
    /**
     * 淘宝店铺编辑列表
     * @adminMenu(
     *     'name'   => '淘宝店铺编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '淘宝店铺编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();  
    }
    
    /**
     * 淘宝店铺审核详情
     * @adminMenu(
     *     'name'   => '淘宝店铺审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '淘宝店铺审核详情',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        parent::edit_info();
        return $this->fetch();  
    }
    /**
     * 淘宝店铺信息编辑审核
     * @adminMenu(
     *     'name'   => '淘宝店铺编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '淘宝店铺编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
    /**
     * 淘宝店铺编辑记录批量删除
     * @adminMenu(
     *     'name'   => '淘宝店铺编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '淘宝店铺编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    
    /**
     * 淘宝店铺批量删除
     * @adminMenu(
     *     'name'   => '淘宝店铺批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '淘宝店铺批量删除',
     *     'param'  => ''
     * )
     */
    public function del_all()
    {
         
        parent::del_all();
    }
    
}
