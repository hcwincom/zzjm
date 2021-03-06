<?php
 
namespace app\shop\controller;
 
  
class AdminCompany1Controller extends CompanyBaseController
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='线下子公司';
         
        $this->company_type=1;
        $this->edit=['name','sort','dsc','code','allname','account_name','account_bank','account_num',
            'contact','address','store','paytype', 
        ];
        $this->assign('flag',$this->flag);
        
    }
    /**
     * 线下子公司列表
     * @adminMenu(
     *     'name'   => '线下子公司列表',
     *     'parent' =>  'admin/User/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 2,
     *     'icon'   => '',
     *     'remark' => '线下子公司列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
         parent::index();
        return $this->fetch();
    }
     
   
    /**
     * 线下子公司添加
     * @adminMenu(
     *     'name'   => '线下子公司添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '线下子公司添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
        return $this->fetch();  
        
    }
    /**
     * 线下子公司添加do
     * @adminMenu(
     *     'name'   => '线下子公司添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '线下子公司添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        parent::add_do();
        
    }
    /**
     * 线下子公司详情
     * @adminMenu(
     *     'name'   => '线下子公司详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '线下子公司详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();  
        return $this->fetch();  
    }
    /**
     * 线下子公司状态审核
     * @adminMenu(
     *     'name'   => '线下子公司状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '线下子公司状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 线下子公司状态批量同意
     * @adminMenu(
     *     'name'   => '线下子公司状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '线下子公司状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    /**
     * 线下子公司禁用
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
     * 线下子公司信息状态恢复
     * @adminMenu(
     *     'name'   => '线下子公司信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '线下子公司信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 线下子公司编辑提交
     * @adminMenu(
     *     'name'   => '线下子公司编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '线下子公司编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
       
        parent::edit_do();
    }
    /**
     * 线下子公司编辑列表
     * @adminMenu(
     *     'name'   => '线下子公司编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '线下子公司编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();  
    }
    
    /**
     * 线下子公司审核详情
     * @adminMenu(
     *     'name'   => '线下子公司审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '线下子公司审核详情',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        parent::edit_info();
        return $this->fetch();  
    }
    /**
     * 线下子公司信息编辑审核
     * @adminMenu(
     *     'name'   => '线下子公司编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '线下子公司编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
    /**
     * 线下子公司编辑记录批量删除
     * @adminMenu(
     *     'name'   => '线下子公司编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '线下子公司编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    
    /**
     * 线下子公司批量删除
     * @adminMenu(
     *     'name'   => '线下子公司批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '线下子公司批量删除',
     *     'param'  => ''
     * )
     */
    public function del_all()
    {
         
        parent::del_all();
    }
    
}
