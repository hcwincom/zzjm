<?php
 
namespace app\shop\controller;

 
use app\common\controller\AdminInfo0Controller; 
use think\Db; 
  
class AdminMyshopController extends AdminInfo0Controller
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='店铺设置';
        $this->table='shop';
        $this->m=Db::name('shop');
        //没有店铺区分
        $this->isshop=0;
        $this->edit=['name','is_review','tel','address','url','logo','print_img'];
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        $this->assign('is_review',[1=>'二次审核',2=>'直接审核']);
    }
    
    /**
     * 我的店铺
     * @adminMenu(
     *     'name'   => '我的店铺',
     *     'parent' => 'shop/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '我的店铺',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $m=$this->m;
        $id=session('shop');
         
        $info=$m
        ->alias('p')
        ->field('p.*,a.user_nickname as aname,r.user_nickname as rname')
        ->join('cmf_user a','a.id=p.aid','left')
        ->join('cmf_user r','r.id=p.rid','left')
        ->where('p.id',$id)
        ->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        $this->assign('info',$info);
        
        $this->where_shop=$id;
        
        //对应分类数据
        $this->cates(); 
        return $this->fetch();  
    }
    
    
    /**
     * 我的店铺编辑提交
     * @adminMenu(
     *     'name'   => '我的店铺编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '我的店铺编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        parent::edit_do();
    }
    /**
     * 我的店铺编辑列表
     * @adminMenu(
     *     'name'   => '我的店铺编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '我的店铺编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();  
    }
    
    /**
     * 我的店铺审核详情
     * @adminMenu(
     *     'name'   => '我的店铺审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '我的店铺审核详情',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        parent::edit_info();
        return $this->fetch();  
    }
    /**
     * 我的店铺信息编辑审核
     * @adminMenu(
     *     'name'   => '我的店铺编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '我的店铺编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
     
     
}
