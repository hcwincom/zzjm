<?php
 
namespace app\operation\controller;

use app\common\controller\AdminInfo0Controller; 
use think\Db; 
/* 产品信息和运营分类的添加，编辑，审核不继承父级 */
class AdminOperationCateController extends AdminInfo0Controller
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='运营分类';
        $this->table='operation_cate';
        $this->m=Db::name('operation_cate');
        $this->edit=['name','sort','dsc','fid'];
         
        $this->search =[ 
            'name' => '名称',
            'code' => '编码',
            'id' => 'id',
        ];
       
        $this->assign('flag','运营分类');
         
    }
     
    /**
     * 运营分类
     * @adminMenu(
     *     'name'   => '运营分类',
     *     'parent' => 'operation/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '运营分类',
     *     'param'  => ''
     * )
     */
    public function index()
    { 
       
        $m=$this->m;
//         $where=['status'=>2];
        $where=[];
        $tmp=$m->where($where)->order('fid asc,sort asc,code_num asc')->column('id,name,code,fid,type,sort,status');
        $list=[];
        foreach($tmp as $k=>$v){
            if($v['fid']==0){
                $list[$v['id']][]=$v;
            }else{
                $list[$v['fid']][]=$v;
            }
        }
        
        $this->assign('list',$list); 
        
        return $this->fetch();
    }
   
    /**
     * 运营分类添加
     * @adminMenu(
     *     'name'   => '运营分类添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '运营分类添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        
        $m=$this->m;
        $id=$this->request->param('id',0,'intval');
        $info=$m->where('id',$id)->find();
        if(empty($info)){
           $fid=0;
        }elseif($info['fid']==0){
            $fid=$info['id'];
        }else{
            $fid=$info['fid'];
        }
        
        $this->assign('fid',$fid);
        $this->assign('info',null);
        $this->cates();
        return $this->fetch();
    }
    /**
     * 运营分类添加do
     * @adminMenu(
     *     'name'   => '运营分类添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '运营分类添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
         parent::add_do();
    }
    /**
     * 运营分类创建记录
     * @adminMenu(
     *     'name'   => '运营分类创建记录',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '运营分类创建记录',
     *     'param'  => ''
     * )
     */
    public function create()
    {
        
        $table=$this->table;
        $m=$this->m;
        $admin=$this->admin;
        $data=$this->request->param();
        $where=[];
        //判断是否有店铺
        $join=[
            ['cmf_user a','a.id=p.aid','left'],
            ['cmf_user r','r.id=p.rid','left'],
            
        ];
        $field='p.*,a.user_nickname as aname,r.user_nickname as rname';
        //店铺,分店只能看到自己的数据，总店可以选择店铺
        if($this->isshop){
            $join[]= ['cmf_shop shop','p.shop=shop.id','left'];
            $field.=',shop.name as sname';
            //店铺,分店只能看到自己的数据，总店可以选择店铺
            if($admin['shop']==1){
                if(empty($data['shop'])){
                    $data['shop']=0;
                }else{
                    $where['p.shop']=['eq',$data['shop']];
                }
            }else{
                $where['p.shop']=['eq',$admin['shop']];
            }
        }
        
        
        
        //状态
        if(empty($data['status'])){
            $data['status']=0;
        }else{
            $where['p.status']=['eq',$data['status']];
        }
        //分类
        if(empty($data['fid'])){
            $data['fid']=0;
        }else{
            $where['p.fid']=['eq',$data['fid']];
        }
        
        //添加人
        if(empty($data['aid'])){
            $data['aid']=0;
        }else{
            $where['p.aid']=['eq',$data['aid']];
        }
        //审核人
        if(empty($data['rid'])){
            $data['rid']=0;
        }else{
            $where['p.rid']=['eq',$data['rid']];
        }
        
        //类型
        if(empty($data['type'])){
            $data['type']=0;
        }else{
            $where['p.type']=['eq',$data['type']];
        }
        //查询字段
        $types=$this->search;
        
        //选择查询字段
        if(empty($data['type1'])){
            $data['type1']=key($types);
        }
        //搜索类型
        $search_types=config('search_types');
        if(empty($data['type2'])){
            $data['type2']=key($search_types);
        }
        if(!isset($data['name']) || $data['name']==''){
            $data['name']='';
        }else{
            $where['p.'.$data['type1']]=zz_search($data['type2'],$data['name']);
        }
        
        //时间类别
        $times=config('time1_search');
        if(empty($data['time'])){
            $data['time']=key($times);
            $data['datetime1']='';
            $data['datetime2']='';
        }else{
            //时间处理
            if(empty($data['datetime1'])){
                $data['datetime1']='';
                $time1=0;
                if(empty($data['datetime2'])){
                    $data['datetime2']='';
                    $time2=0;
                }else{
                    //只有结束时间
                    $time2=strtotime($data['datetime2']);
                    $where['p.'.$data['time']]=['elt',$time2];
                }
            }else{
                //有开始时间
                $time1=strtotime($data['datetime1']);
                if(empty($data['datetime2'])){
                    $data['datetime2']='';
                    $where['p.'.$data['time']]=['egt',$time1];
                }else{
                    //有结束时间有开始时间between
                    $time2=strtotime($data['datetime2']);
                    if($time2<=$time1){
                        $this->error('结束时间必须大于起始时间');
                    }
                    $where['p.'.$data['time']]=['between',[$time1,$time2]];
                }
            }
        }
        $list=$m
        ->alias('p')
        ->field($field)
        ->join($join)
        ->where($where)
        ->order('p.status asc,p.sort asc,p.time desc')
        ->paginate();
        
        // 获取分页显示
        $page = $list->appends($data)->render();
        
        $this->assign('page',$page);
        $this->assign('list',$list);
        
        $this->assign('data',$data);
        $this->assign('types',$types);
        $this->assign('times',$times);
        $this->assign("search_types", $search_types);
        
        $this->cates(1);
        return $this->fetch();
    }
    /**
     * 运营分类详情
     * @adminMenu(
     *     'name'   => '运营分类详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '运营分类详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();
        return $this->fetch();
    }
    /**
     * 运营分类状态审核
     * @adminMenu(
     *     'name'   => '运营分类状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '运营分类状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 运营分类状态批量同意
     * @adminMenu(
     *     'name'   => '运营分类状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '运营分类状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    /**
     * 运营分类禁用
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
     * 运营分类信息状态恢复
     * @adminMenu(
     *     'name'   => '运营分类信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '运营分类信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 运营分类编辑提交
     * @adminMenu(
     *     'name'   => '运营分类编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '运营分类编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        parent::edit_do();
    }
    /**
     * 运营分类编辑列表
     * @adminMenu(
     *     'name'   => '运营分类编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '运营分类编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();  
    }
    
    /**
     * 运营分类审核详情
     * @adminMenu(
     *     'name'   => '运营分类审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '运营分类审核详情',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        parent::edit_info();
        return $this->fetch();
    }
    /**
     * 运营分类信息编辑审核
     * @adminMenu(
     *     'name'   => '运营分类编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '运营分类编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
    /**
     * 运营分类编辑记录批量删除
     * @adminMenu(
     *     'name'   => '运营分类编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '运营分类编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    /**
     * 运营分类批量删除
     * @adminMenu(
     *     'name'   => '运营分类批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '运营分类批量删除',
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
        //先检查是否有子类
        $where=['fid'=>['in',$ids]];
        $tmp=$m->where($where)->find();
        if(!empty($tmp)){
            $this->error('分类'.$tmp['fid'].'下有子类'.$tmp['name'].$tmp['code']);
        }
        //检查是否有产品
        $where=['cid'=>['in',$ids]];
        $tmp=Db::name('goods_out')->where($where)->find();
        if(!empty($tmp)){
            $this->error('分类'.$tmp['cid'].'下有产品'.$tmp['name'].$tmp['code']);
        }
        parent::del_all();
        
    }
    
    public function cates($type=3){
        parent::cates($type);
        $m=$this->m;
        $cates=$m->where('fid',0)->order('sort asc,code_num asc')->column('id,name,type,code');
         
        $this->assign('cates',$cates);
    }
    
    
}
