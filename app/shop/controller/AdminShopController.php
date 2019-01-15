<?php
 
namespace app\shop\controller;

 
use app\common\controller\AdminInfo0Controller; 
use think\Db; 
  
class AdminShopController extends AdminInfo0Controller
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='加盟店铺';
        $this->table='shop';
        $this->m=Db::name('shop');
        //没有店铺区分
        $this->isshop=0;
        $this->edit=['name','dsc','sort','is_review','tel','address','url','logo','print_img'];
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        $this->assign('is_review',[1=>'二次审核',2=>'直接审核']);
    }
    /**
     * 加盟店铺列表
     * @adminMenu(
     *     'name'   => '加盟店铺列表',
     *     'parent' => 'shop/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 2,
     *     'icon'   => '',
     *     'remark' => '加盟店铺列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
         parent::index();
        return $this->fetch();
    }
     
   
    /**
     * 加盟店铺添加
     * @adminMenu(
     *     'name'   => '加盟店铺添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '加盟店铺添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
        return $this->fetch();  
        
    }
    /**
     * 加盟店铺添加do
     * @adminMenu(
     *     'name'   => '加盟店铺添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '加盟店铺添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        parent::add_do();
        
    }
    /**
     * 加盟店铺详情
     * @adminMenu(
     *     'name'   => '加盟店铺详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '加盟店铺详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();  
        return $this->fetch();  
    }
    /**
     * 加盟店铺状态审核
     * @adminMenu(
     *     'name'   => '加盟店铺状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '加盟店铺状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 加盟店铺状态批量同意
     * @adminMenu(
     *     'name'   => '加盟店铺状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '加盟店铺状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    /**
     * 加盟店铺禁用
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
     * 加盟店铺信息状态恢复
     * @adminMenu(
     *     'name'   => '加盟店铺信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '加盟店铺信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 加盟店铺编辑提交
     * @adminMenu(
     *     'name'   => '加盟店铺编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '加盟店铺编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        parent::edit_do();
    }
    /**
     * 加盟店铺编辑列表
     * @adminMenu(
     *     'name'   => '加盟店铺编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '加盟店铺编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();  
    }
    
    /**
     * 加盟店铺审核详情
     * @adminMenu(
     *     'name'   => '加盟店铺审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '加盟店铺审核详情',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        parent::edit_info();
        return $this->fetch();  
    }
    /**
     * 加盟店铺信息编辑审核
     * @adminMenu(
     *     'name'   => '加盟店铺编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '加盟店铺编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
    /**
     * 加盟店铺编辑记录批量删除
     * @adminMenu(
     *     'name'   => '加盟店铺编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '加盟店铺编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    
    /**
     * 加盟店铺
     * @adminMenu(
     *     'name'   => '加盟店铺',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '加盟店铺',
     *     'param'  => ''
     * )
     */
    public function del_all()
    { 
       if(empty($_POST['ids'])){
            $this->error('没有选择店铺');
       }
       $ids=$_POST['ids'];
       if(in_array(1,$ids) || in_array(2,$ids)){
           $this->error('总站和总站对应店铺不能修改');
       }
       $where=['shop'=>['in',$ids]];
       $user=Db::name('user')->where($where)->find();
       if(!empty($user)){
            $this->error('店铺'.$user['shop'].'下有用户，不能删除');
        }
      
       
        Db::name('goods')->where($where)->delete();
        Db::name('goods_label')->where($where)->delete();
        Db::name('goods_link')->where($where)->delete();
        Db::name('goods_sn')->where($where)->delete();
        Db::name('goods_label')->where($where)->delete();
        Db::name('msg')->where($where)->delete();
        Db::name('action')->where($where)->delete();
        
        Db::name('store_goods')->where($where)->delete();
        Db::name('store_goods_history')->where($where)->delete();
        Db::name('store_in')->where($where)->delete();
        Db::name('store_box')->where($where)->delete();
        Db::name('store_floor')->where($where)->delete();
        Db::name('store_shelf')->where($where)->delete();
        Db::name('store')->where($where)->delete();
         
        Db::name('attendance_day')->where($where)->delete();
        Db::name('attendance_apply')->where($where)->delete();
        Db::name('attendance_date')->where($where)->delete();
        Db::name('attendance_rule')->where($where)->delete();
        
        //有相关垃圾未删除，如event_uid ,role_user
        Db::name('event')->where($where)->delete();
        Db::name('user')->where($where)->delete();
        Db::name('order')->where($where)->delete();
        Db::name('ordersup')->where($where)->delete();
        Db::name('orderq')->where($where)->delete();
        Db::name('orderback')->where($where)->delete();
        Db::name('custom')->where($where)->delete();
        Db::name('supplier')->where($where)->delete();
        Db::name('edit')->where($where)->delete();
        Db::name('freightpays')->where($where)->delete();
        Db::name('expressarea')->where($where)->delete();
        $rows=Db::name('shop')->where('id','in',$ids)->delete();
        $this->success('成功删除店铺');
         
    }
    
     
}
