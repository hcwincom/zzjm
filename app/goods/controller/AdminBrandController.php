<?php
 
namespace app\goods\controller;

 
use think\Db; 
 

class AdminBrandController extends GoodsBaseController
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='产品品牌';
        $this->table='brand';
        $this->isshop=0;
        $this->m=Db::name('brand');
        $this->edit=['name','sort','dsc','pic','char'];
       
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
         
    }
    /**
     * 产品品牌列表
     * @adminMenu(
     *     'name'   => '产品品牌列表',
     *     'parent' => 'goods/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 40,
     *     'icon'   => '',
     *     'remark' => '产品品牌列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        parent::index();
        return $this->fetch();
       
    }
     
   
    /**
     * 产品品牌添加
     * @adminMenu(
     *     'name'   => '产品品牌添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品品牌添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
        return $this->fetch();
    }
    /**
     * 产品品牌添加do
     * @adminMenu(
     *     'name'   => '产品品牌添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品品牌添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        $m=$this->m;
        $data=$this->request->param();
        if(empty($data['name'])){
            $this->error('名称不能为空');
        }
        $url=url('index');
        //处理图片
        $pic='';
        $table=$this->table;
        $time=time();
        $admin=$this->admin;
        //判断是否有店铺
        if($this->isshop){
            $data_add['shop']=($admin['shop']==1)?2:$admin['shop'];
        } elseif($admin['shop']!=1){
            $this->error('店铺不能添加系统数据');
        }
        if(empty($data['char'])){
            $char=zz_first_char($data['name']);
        }else{
            $char=zz_first_char($data['char']);
        }
       
        if(empty($char)){
            $this->error('输入非法，无法获取首字母');
        }
        $data_add=[
            'name'=>$data['name'],
            'dsc'=>$data['dsc'],
            'char'=>$char,
            'pic'=>'',
            'sort'=>intval($data['sort']),
            'status'=>1,
            'aid'=>$admin['id'],
            'atime'=>$time,
            'time'=>$time,
        ];
        $m->startTrans();
        $id=$m->insertGetId($data_add);
        //处理图片
        $path='upload/';
        $path1=$table.'/'.$id.'/';
        $data_update=[
            'path'=>$path1, 
        ];
        if(!empty($data['pic']) && is_file($path.$data['pic'])){
            
            if(!is_dir($path.$path1)){
                mkdir($path.$path1);
            } 
            $pic_conf=config('pic_'.$table);
            $data_update['pic']=$path1.'/'.$admin['id'].'-'.$time.'.jpg';
            zz_set_image($data['pic'], $data_update['pic'], $pic_conf[0], $pic_conf[1],$pic_conf[2]);
            unlink($path.$data['pic']);
        }
        
        $m->where('id',$id)->update($data_update);
        //记录操作记录
        $flag=$this->flag;
        $table=$this->table;
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>'添加'.$flag.$id.'-'.$data['name'],
            'table'=>$table,
            'type'=>'add',
            'pid'=>$id,
            'link'=>url('admin/'.$table.'/edit',['id'=>$id]),
            'shop'=>$admin['shop'],
        ];
        Db::name('action')->insert($data_action);
        $m->commit();
        $this->success('添加成功',$url);
    }
    /**
     * 产品品牌详情
     * @adminMenu(
     *     'name'   => '产品品牌详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品品牌详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();  
        return $this->fetch();  
    }
    /**
     * 产品品牌状态审核
     * @adminMenu(
     *     'name'   => '产品品牌状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品品牌状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 产品品牌状态批量同意
     * @adminMenu(
     *     'name'   => '产品品牌状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品品牌状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    /**
     * 产品品牌禁用
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
     * 产品品牌信息状态恢复
     * @adminMenu(
     *     'name'   => '产品品牌信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品品牌信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 产品品牌编辑提交
     * @adminMenu(
     *     'name'   => '产品品牌编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品品牌编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        parent::edit_do();
    }
    /**
     * 产品品牌编辑列表
     * @adminMenu(
     *     'name'   => '产品品牌编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品品牌编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();  
    }
    
    /**
     * 产品品牌审核详情
     * @adminMenu(
     *     'name'   => '产品品牌审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品品牌审核详情',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        parent::edit_info();
        
        return $this->fetch();  
    }
    /**
     * 产品品牌信息编辑审核
     * @adminMenu(
     *     'name'   => '产品品牌编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品品牌编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
    /**
     * 产品品牌编辑记录批量删除
     * @adminMenu(
     *     'name'   => '产品品牌编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品品牌编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    
    /**
     * 产品品牌批量删除
     * @adminMenu(
     *     'name'   => '产品品牌批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品品牌批量删除',
     *     'param'  => ''
     * )
     */
    public function del_all()
    {
        if(empty($_POST['ids'])){
            $this->error('未选中信息');
        }
        $ids=$_POST['ids'];
        
        $m=$this->m;
        $flag=$this->flag;
        $table=$this->table;
        $admin=$this->admin;
        $time=time();
        //检查是否有产品
        $where=['brand'=>['in',$ids]];
        $tmp=Db::name('goods')->where($where)->find();
        if(!empty($tmp)){
            $this->error($flag.$tmp['brand'].'下有产品'.$tmp['name'].$tmp['code']);
        }
        parent::del_all();
        
    }
   
     
}
