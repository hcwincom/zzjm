<?php

namespace app\goods\controller;
 
use think\Db;
 
 
class AdminFeeCateController extends GoodsBaseController
{
    
    public function _initialize()
    {
        parent::_initialize();
        
        $this->flag='价格参数分类';
        $this->table='goods_fee_cate';
        $this->m=Db::name('goods_fee_cate');
        $prices=config('prices');
        $this->assign('prices',$prices);
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
       
    }
    /**
     * 价格参数分类列表
     * @adminMenu(
     *     'name'   => '价格参数分类列表',
     *     'parent' => 'goods/AdminFee/ii',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格参数分类列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        parent::index();
        return $this->fetch();
    }
    
    
    /**
     * 价格参数分类添加
     * @adminMenu(
     *     'name'   => '价格参数分类添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格参数分类添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
        return $this->fetch();
        
    }
    /**
     * 价格参数分类添加do
     * @adminMenu(
     *     'name'   => '价格参数分类添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格参数分类添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        parent::add_do();
       
    }
    /**
     * 价格参数分类详情
     * @adminMenu(
     *     'name'   => '价格参数分类详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格参数分类详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();
        return $this->fetch();
    }
    /**
     * 价格参数分类状态审核
     * @adminMenu(
     *     'name'   => '价格参数分类状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格参数分类状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 价格参数分类状态批量同意
     * @adminMenu(
     *     'name'   => '价格参数分类状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格参数分类状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    /**
     * 价格参数分类禁用
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
     * 价格参数分类信息状态恢复
     * @adminMenu(
     *     'name'   => '价格参数分类信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格参数分类信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 价格参数分类编辑提交
     * @adminMenu(
     *     'name'   => '价格参数分类编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格参数分类编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        parent::edit_do();
    }
    /**
     * 价格参数分类编辑列表
     * @adminMenu(
     *     'name'   => '价格参数分类编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格参数分类编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();
    }
    
    /**
     * 价格参数分类审核详情
     * @adminMenu(
     *     'name'   => '价格参数分类审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格参数分类审核详情',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        parent::edit_info();
        return $this->fetch();
    }
    /**
     * 价格参数分类信息编辑审核
     * @adminMenu(
     *     'name'   => '价格参数分类编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格参数分类编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
    /**
     * 价格参数分类编辑记录批量删除
     * @adminMenu(
     *     'name'   => '价格参数分类编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格参数分类编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    
    /**
     * 价格参数分类批量删除
     * @adminMenu(
     *     'name'   => '价格参数分类批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格参数分类批量删除',
     *     'param'  => ''
     * )
     */
    public function del_all()
    {
        //分类下有参数不能删除
        if(empty($_POST['ids'])){
            $this->error('未选中信息');
        }
        $ids=$_POST['ids']; 
        $where=['cid'=>['in',$ids]];
        $tmp=Db::name('goods_fee')->where($where)->find();
        if(!empty($tmp)){
            $this->error('分类'.$tmp['cid'].'下有价格参数'.$tmp['name']);
        }
        
        parent::del_all();
    }
    
    
}
