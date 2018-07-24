<?php
 
namespace app\admin\controller;

 
use cmf\controller\AdminBaseController; 
use think\Db; 
 
/**
 * Class GoodsController
 * @package app\admin\controller
 *
 * @adminMenuRoot(
 *     'name'   =>'产品管理',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 10,
 *     'icon'   =>'',
 *     'remark' =>'产品管理'
 * )
 *
 * @adminMenuRoot(
 *     'name'   =>'产品信息',
 *     'action' =>'default1',
 *     'parent' =>'admin/Goods/default',
 *     'display'=> true,
 *     'order'  => 1,
 *     'icon'   =>'',
 *     'remark' =>'产品信息'
 * )
 */
class GoodsController extends AdminBaseController
{
    private $m;
    private $order;
   
    public function _initialize()
    {
        parent::_initialize();
        $this->m=Db::name('goods');
        $this->order='id desc'; 
        $this->assign('flag','产品');
         
    }
     
    /**
     * 产品列表
     * @adminMenu(
     *     'name'   => '产品列表',
     *     'parent' => 'default1',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '产品列表',
     *     'param'  => ''
     * )
     */
    public function index()
    { 
        exit('goods');
        $m=$this->m;
        $where=[];
        $data=$this->request->param();
        if(empty($data['type'])){
            $data['type']='';
        }else{
            $where['a.type']=$data['type'];
        }
        if(empty($data['aid'])){
            $data['aid']=0;
        }else{
            $where['a.aid']=$data['aid'];
        }
        $list= $m
        ->alias('a')
        ->field('a.*,u.user_login as uname0,u.user_nickname as uname1')
        ->join('cmf_user u','u.id = a.aid','left')
        ->where($where)
        ->order($this->order)
        ->paginate(10);
       
        // 获取分页显示
        $page = $list->render(); 
       //得到所有管理员
        $admins=Db::name('user')->where('user_type',1)->select();
        $this->assign('page',$page);
        $this->assign('list',$list); 
        $this->assign('data',$data); 
        $this->assign('admins',$admins); 
        return $this->fetch();
    }
    
}
