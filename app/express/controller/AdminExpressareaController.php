<?php
 
namespace app\express\controller;

 
use app\common\controller\AdminInfo0Controller; 
use think\Db; 
  
class AdminExpressareaController extends AdminInfo0Controller
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='快递区域';
        $this->table='expressarea';
        $this->m=Db::name('expressarea');
        //没有店铺区分
        $this->isshop=1;
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        
    }
    /**
     * 自定义快递区域列表
     * @adminMenu(
     *     'name'   => '自定义快递区域列表',
     *     'parent' => 'express/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 2,
     *     'icon'   => '',
     *     'remark' => '自定义快递区域列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
         parent::index();
        return $this->fetch();
    }
     
   
    /**
     * 快递区域添加
     * @adminMenu(
     *     'name'   => '快递区域添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '快递区域添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
       
        return $this->fetch();  
        
    }
    /**
     * 快递区域添加do
     * @adminMenu(
     *     'name'   => '快递区域添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '快递区域添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        parent::add_do();
        
    }
    /**
     * 快递区域详情
     * @adminMenu(
     *     'name'   => '快递区域详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '快递区域详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $m=$this->m;
        $id=$this->request->param('id',0,'intval');
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
        
        //得到省 
        $where=[
            'status'=>2,
            'fid'=>1,
        ];
        $city1s=Db::name('area')->where($where)->column('id,name');
        $this->assign('city1s',$city1s);
        
        //得到选中地区
        $citys=Db::name('express_area')
        ->alias('ea')
        ->join('cmf_area a','a.id=ea.city')
        ->where('ea.area',$id)
        ->order('a.fid asc,a.sort asc,a.name asc')
        ->column('a.id,a.name,a.fid');
        //按省分组
        $list=[];
        foreach($citys as $k=>$v){
            $list[$v['fid']][$k]=$v['name'];
        }
        $this->assign('citys',$list);
        return $this->fetch();  
    }
    /**
     * 快递区域状态审核
     * @adminMenu(
     *     'name'   => '快递区域状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '快递区域状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 快递区域状态批量同意
     * @adminMenu(
     *     'name'   => '快递区域状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '快递区域状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    /**
     * 快递区域禁用
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
     * 快递区域信息状态恢复
     * @adminMenu(
     *     'name'   => '快递区域信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '快递区域信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 快递区域编辑提交
     * @adminMenu(
     *     'name'   => '快递区域编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '快递区域编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        parent::edit_do();
    }
    /**
     * 快递区域编辑列表
     * @adminMenu(
     *     'name'   => '快递区域编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '快递区域编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();  
    }
    
    /**
     * 快递区域审核详情
     * @adminMenu(
     *     'name'   => '快递区域审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '快递区域审核详情',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        $m=$this->m;
        $id=$this->request->param('id',0,'intval');
        $table=$this->table;
        //获取编辑信息
        $m_edit=Db::name('edit');
        $info1=$m_edit->where('id',$id)->find();
        if(empty($info1)){
            $this->error('编辑信息不存在');
        }
        //获取原信息
        $info=$m->where('id',$info1['pid'])->find();
        if(empty($info)){
            $this->error('编辑关联的信息不存在');
        }
        //获取改变的信息
        $change=Db::name('edit_info')->where('eid',$id)->value('content');
        $change=json_decode($change,true);
        //得到选中地区
        $citys=Db::name('express_area')
        ->alias('ea')
        ->join('cmf_area a','a.id=ea.city')
        ->where('ea.area',$info1['pid'])
        ->order('a.fid asc,a.sort asc,a.name asc')
        ->column('a.id,a.name,a.fid');
        //按省分组
        $list0=[];
        foreach($citys as $k=>$v){
            $list0[$v['fid']][$k]=$v['name'];
        }
        
        //新关联产品
        if(isset($change['citys'])){
            $ids1=json_decode($change['citys'],true);
            if(!empty($ids1)){
                $citys=Db::name('area')
                ->where('id','in',$ids1)
                ->order('fid asc,sort asc,name asc')
                ->column('id,name,fid');
                //按省分组
                $list1=[];
                foreach($citys as $k=>$v){
                    $list1[$v['fid']][$k]=$v['name'];
                }
            } 
        }
        //得到省
        $where=[
            'status'=>2,
            'fid'=>1,
        ];
        $city1s=Db::name('area')->where($where)->column('id,name');
        $this->assign('city1s',$city1s);
        $this->assign('list1',$list1);
        $this->assign('list0',$list0);
        $this->assign('info',$info);
        $this->assign('info1',$info1);
        $this->assign('change',$change);
       
        return $this->fetch();  
    }
    /**
     * 快递区域信息编辑审核
     * @adminMenu(
     *     'name'   => '快递区域编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '快递区域编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
    /**
     * 快递区域编辑记录批量删除
     * @adminMenu(
     *     'name'   => '快递区域编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '快递区域编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    
    /**
     * 快递区域批量删除
     * @adminMenu(
     *     'name'   => '快递区域批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '快递区域批量删除',
     *     'param'  => ''
     * )
     */
    public function del_all()
    {
         
        parent::del_all();
    }
   
     
}
