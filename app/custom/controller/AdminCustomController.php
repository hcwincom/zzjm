<?php
 
namespace app\custom\controller;

 
use think\Db; 
  
class AdminCustomController extends CustomBaseController
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='客户';
        $this->table='custom';
        $this->m=Db::name('custom');
        
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        
    }
    /**
     * 客户列表
     * @adminMenu(
     *     'name'   => '客户列表',
     *     'parent' => 'custom/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '客户列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
         parent::index();
        return $this->fetch();
    }
     
   
    /**
     * 客户添加
     * @adminMenu(
     *     'name'   => '客户添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
        return $this->fetch();  
        
    }
    /**
     * 客户添加do
     * @adminMenu(
     *     'name'   => '客户添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
       parent::add_do();
        
    }
    /**
     * 客户详情
     * @adminMenu(
     *     'name'   => '客户详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();
       
        return $this->fetch();  
    }
    /**
     * 客户状态审核
     * @adminMenu(
     *     'name'   => '客户状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 客户状态批量同意
     * @adminMenu(
     *     'name'   => '客户状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    /**
     * 客户禁用
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
     * 客户信息状态恢复
     * @adminMenu(
     *     'name'   => '客户信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 客户编辑提交
     * @adminMenu(
     *     'name'   => '客户编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        parent::edit_do();
    }
    /**
     * 客户编辑列表
     * @adminMenu(
     *     'name'   => '客户编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();  
    }
    
    /**
     * 客户审核详情
     * @adminMenu(
     *     'name'   => '客户审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户审核详情',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        parent::edit_info();
        return $this->fetch();  
    }
    /**
     * 客户信息编辑审核
     * @adminMenu(
     *     'name'   => '客户编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
    /**
     * 客户编辑记录批量删除
     * @adminMenu(
     *     'name'   => '客户编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    
    /**
     * 客户批量删除
     * @adminMenu(
     *     'name'   => '客户批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '客户批量删除',
     *     'param'  => ''
     * )
     */
    public function del_all()
    {
         
        parent::del_all();
    }
   //客户编码
   public function code_add(){
       $id=$this->request->param('id',0,'intval');
       $city=$this->request->param('city',0,'intval');
        
       $m=$this->m;
       $where=[
           'city'=>['eq',$city], 
       ];
       
       $tmp=$m->where($where)->order('code_num desc')->column('id,code,city_code,code_num');
       if(empty($tmp)){
           //无此城市,则查询城市
           $city_code=Db::name('area')->where('id',$city)->value('city_code');
           $code_num=1; 
       }else{
           //判断id是否已存在
           if(isset($tmp[$id])){
               $city_code=$tmp[$id]['city_code'];
               $code_num=$tmp[$id]['code_num'];
           }else{
               //不存在就是城市新增
               $first=key($tmp);
               $city_code=$tmp[$first]['city_code'];
               $code_num=$tmp[$first]['code_num']+1;
           }
       } 
       $this->success('ok','',['city_code'=>$city_code,'code_num'=>$code_num]);
   }
   //客户编码检查
   public function code_check(){
       $id=$this->request->param('id',0,'intval');
       $city=$this->request->param('city',0,'intval');
       
       $m=$this->m;
       $where=[
           'city'=>['eq',$city],
       ];
       
       $tmp=$m->where($where)->order('code_num desc')->column('id,code,city_code,code_num');
       if(empty($tmp)){
           //无此城市,则查询城市
           $city_code=Db::name('area')->where('id',$city)->value('city_code');
           $code_num=1;
       }else{
           //判断id是否已存在
           if(isset($tmp[$id])){
               $city_code=$tmp[$id]['city_code'];
               $code_num=$tmp[$id]['code_num'];
           }else{
               //不存在就是城市新增
               $first=key($tmp);
               $city_code=$tmp[$first]['city_code'];
               $code_num=$tmp[$first]['code_num']+1;
           }
       }
       $this->success('ok','',['city_code'=>$city_code,'code_num'=>$code_num]);
   }
     
}
