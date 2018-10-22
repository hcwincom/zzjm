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
 *     'order'  => 40,
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
   //供货商编码
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
   //供货商编码检查
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
