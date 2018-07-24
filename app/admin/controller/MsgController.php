<?php
 
namespace app\admin\controller;

 
use cmf\controller\AdminBaseController; 
use think\Db; 
/**
 * Class MsgController
 * @package app\admin\controller
 *
 * @adminMenuRoot(
 *     'name'   =>'信息管理',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 10,
 *     'icon'   =>'',
 *     'remark' =>'信息管理'
 * )
 *
 */
class MsgController extends AdminBaseController
{
    private $m;
    private $order;
    public function _initialize()
    {
        parent::_initialize();
        $this->m=Db::name('msg');
        $this->order='p.status asc,p.time desc';
        $this->assign('flag','信息');
        $this->assign('types', config('msg_types'));
        $this->assign('msg_status', config('msg_status'));
    }
     
    /**
     * 站内信
     * @adminMenu(
     *     'name'   => '站内信',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '站内信',
     *     'param'  => ''
     * )
     */
    public function index()
    { 
        
        $m=$this->m;
        $admin=$this->admin;
       
        $where=[
            'p.uid'=>['eq',$admin['id']],
            'p.status'=>['neq',4]
        ];
        $data=$this->request->param();
        if(empty($data['status'])){
            $data['status']=0;
        }else{
            $where['p.status']=$data['status'];
        }
        if(empty($data['type']) || $data['type']=='no'){
            $data['type']='no';
        }else{
            $where['p.type']=$data['type'];
        }
        if(empty($data['aid'])){
            $data['aid']=0;
        }else{
            $where['p.aid']=$data['aid'];
        }
        $list= $m
        ->alias('p')
        ->field('p.*,u.user_nickname as aname')
        ->join('cmf_user u','u.id = p.uid','left')
        ->where($where)
        ->order($this->order)
        ->paginate();
        
        // 获取分页显示
        $page = $list->appends($data)->render();  
        //更改状态未接收\未读为已读
        $where=[
            'uid'=>['eq',$admin['id']],
            'status'=>['elt',2],
        ];
        $update=['status'=>3,'time'=>time()];
        $m->where($where)->update($update);
        //得到所有发送管理员
        $where_admin=[
           'user_type'=>['eq',1],
           'shop'=>['in',[1,$admin['shop']]],
        ];
         
        $admins=Db::name('user')->where($where_admin)->column('id,user_nickname');
        $this->assign('page',$page);
        $this->assign('list',$list); 
        $this->assign('data',$data); 
        $this->assign('admins',$admins); 
        return $this->fetch();
    }
    /**
     * 我发送的信息
     * @adminMenu(
     *     'name'   => '我发送的信息',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '我发送的信息',
     *     'param'  => ''
     * )
     */
    public function send()
    {
        $m=$this->m;
        $admin=$this->admin;
        
        $where=[
            'p.aid'=>['eq',$admin['id']],
            'p.status'=>['neq',4]
        ];
        $data=$this->request->param();
        if(empty($data['status'])){
            $data['status']=0;
        }else{
            $where['p.status']=$data['status'];
        }
        
        $list= $m
        ->alias('p')
        ->field('p.*,u.user_nickname as uname')
        ->join('cmf_user u','u.id = p.uid','left')
        ->where($where)
        ->order($this->order)
        ->paginate();
        
        // 获取分页显示
        $page = $list->appends($data)->render();
        
        $this->assign('page',$page);
        $this->assign('list',$list);
        $this->assign('data',$data);
        
        return $this->fetch();
        
    }
     /**
     * 发送信息
     * @adminMenu(
     *     'name'   => '发送信息',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '发送信息',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        exit();
        
    }
    /**
     * 发送信息do
     * @adminMenu(
     *     'name'   => '发送信息do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '发送信息do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        exit();
        
    }
    /**
     * 全站信息
     * @adminMenu(
     *     'name'   => '全站信息',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '全站信息',
     *     'param'  => ''
     * )
     */
    public function msgs()
    {
        $m=$this->m;
        $admin=$this->admin;
        //总站显示所有，分站只显示分站
        $where=[];
        $where_admin=['user_type'=>1]; 
        if($admin['shop']!=1){
            $where['p.shop']=$admin['shop'];
            $where_admin['shop']=$admin['shop'];
        } 
        
        $data=$this->request->param();
        if(empty($data['status'])){
            $data['status']=0;
        }else{
            $where['p.status']=$data['status'];
        }
        if(empty($data['aid'])){
            $data['aid']=0;
        }else{
            $where['p.aid']=$data['aid'];
        }
        $list= $m
        ->alias('p')
        ->field('p.*,u.user_nickname as uname,a.user_nickname as aname')
        ->join('cmf_user u','u.id = p.uid','left')
        ->join('cmf_user a','u.id = p.aid','left')
        ->where($where)
        ->order($this->order)
        ->paginate();
        
        // 获取分页显示
        $page = $list->appends($data)->render();
        
        //发送者        
        $admins=Db::name('user')->where($where_admin)->column('id,user_nickname');
        $this->assign('page',$page);
        $this->assign('list',$list);
        $this->assign('data',$data);
        $this->assign('admins',$admins);
        return $this->fetch();
        
    }
    
}
