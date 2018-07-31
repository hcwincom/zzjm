<?php
 
namespace app\admin\controller;

 
use app\common\controller\AdminInfoController; 
use think\Db; 
 
class CateController extends AdminInfoController
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='产品分类';
        $this->table='cate';
        $this->m=Db::name('cate');
         
        
        $this->assign('flag','产品分类');
         
    }
     
    /**
     * 产品分类
     * @adminMenu(
     *     'name'   => '产品分类',
     *     'parent' => 'admin/Goods/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类',
     *     'param'  => ''
     * )
     */
    public function index()
    { 
       
        $m=$this->m;
//         $where=['status'=>2];
        $where=[];
        $tmp=$m->where($where)->order('fid asc,sort asc,code_num asc')->column('id,name,code,fid,sort,status');
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
     * 产品分类添加
     * @adminMenu(
     *     'name'   => '产品分类添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类添加',
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
        $cates=$m->where('fid',0)->order('sort asc,code_num asc')->column('id,name');
        
        $this->assign('fid',$fid);
        $this->assign('cates',$cates);
        return $this->fetch();
    }
    /**
     * 产品分类添加do
     * @adminMenu(
     *     'name'   => '产品分类添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        $m=$this->m;
        $data=$this->request->param('');
        if(empty($data['name'])){
            $this->error('名称不能为空');
        }
        $url=url('index');
        $fid=intval($data['fid']);
        $time=time();
        $admin=$this->admin;
        $data_add=[
            'name'=>$data['name'],
            'fid'=>$fid,
            'sort'=>intval($data['sort']),
            'status'=>1,
            'aid'=>$admin['id'],
            'atime'=>$time,
            'time'=>$time,
        ];
        
        if($fid==0){
            $max_code=config('cate_max');
            $data_add['code_num']=$max_code+1;
            $data_add['code']=(str_pad($data_add['code_num'],2,'0',STR_PAD_LEFT));
        }else{
            //比较父类中记录的最大值和查找到的最大值
            $fcate=$m->where(['id'=>$fid])->find();
            $max_num=$m->where('fid',$fid)->order('code_num desc')->value('code_num');
            $data_add['code_num']=(($max_num>=$fcate['max_num'])?$max_num:$fcate['max_num'])+1;
            $data_add['code']=$fcate['code'].'-'.(str_pad($data_add['code_num'],2,'0',STR_PAD_LEFT));
        }
        try {
            $id=$m->insertGetId($data_add);
        } catch (\Exception $e) {
            $this->error('操作失败，请重试');
        }
        //如果一级分类要更新配置中记录的最大编码，如果是2级要更新一级中的最大编码
        if($fid==0){
            cmf_set_dynamic_config(['cate_max'=>$data_add['code_num']]);
        }else{
            $m->where(['id'=>$fid])->update(['max_num'=>$data_add['code_num']]); 
        }
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
        db('action')->insert($data_action);
        
        $this->success('添加成功',$url);
    }
    /**
     * 产品分类创建记录
     * @adminMenu(
     *     'name'   => '产品分类创建记录',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类创建记录',
     *     'param'  => ''
     * )
     */
    public function create()
    {
        
        $m=$this->m;
        $data=$this->request->param();
        $where=[];
        if(empty($data['status'])){
            $data['status']=0; 
        }else{
            $where['p.status']=['eq',$data['status']];
        }
        if(empty($data['aid'])){
            $data['aid']=0;
        }else{
            $where['p.aid']=['eq',$data['aid']];
        }
        if(empty($data['rid'])){
            $data['rid']=0;
        }else{
            $where['p.rid']=['eq',$data['rid']];
        }
        //查询字段
        $types=config('cate_search');
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
        ->field('p.*,c.name as fname')
        ->join('cmf_cate c','c.id=p.fid','left') 
        ->where($where)
        ->order('p.status asc,p.time desc')
        ->paginate();
        // 获取分页显示
        $page = $list->appends($data)->render(); 
        $m_user=db('user');
        //创建人
        $where_aid=[
            'user_type'=>1,
            'shop'=>1,
        ]; 
        $aids=$m_user->where($where_aid)->column('id,user_nickname');
        //审核人
        $where_rid=[
            'user_type'=>1,
            'shop'=>1,
        ]; 
        $rids=$m_user->where($where_rid)->column('id,user_nickname');
        
        $this->assign('page',$page); 
        $this->assign('list',$list);
        $this->assign('aids',$aids);
        $this->assign('rids',$rids);
        $this->assign('data',$data);
        $this->assign('types',$types);
        $this->assign('times',$times);
        $this->assign("search_types", $search_types);
        return $this->fetch();
    }
    /**
     * 产品分类详情
     * @adminMenu(
     *     'name'   => '产品分类详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $m=$this->m;
        $id=$this->request->param('id',0,'intval');
        $info=$m->where('id',$id)->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        $cates=$m->where('fid',0)->order('sort asc,code_num asc')->column('id,name');
        
        $this->assign('info',$info);
        $this->assign('cates',$cates);
        return $this->fetch();
    }
    /**
     * 产品分类状态审核
     * @adminMenu(
     *     'name'   => '产品分类状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 产品分类状态批量同意
     * @adminMenu(
     *     'name'   => '产品分类状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    /**
     * 产品分类禁用
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
     * 产品分类信息状态恢复
     * @adminMenu(
     *     'name'   => '产品分类信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 产品分类编辑提交
     * @adminMenu(
     *     'name'   => '产品分类编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        parent::edit_do();
    }
    /**
     * 产品分类编辑列表
     * @adminMenu(
     *     'name'   => '产品分类编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();  
    }
    
    /**
     * 产品分类审核详情
     * @adminMenu(
     *     'name'   => '产品分类审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类审核详情',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        $m=$this->m;
        $id=$this->request->param('id',0,'intval');
        $table=$this->table;
        //获取编辑信息
        $m_edit=db('edit');
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
        $change=db('edit_info')->where('eid',$id)->value('content');
        $change=json_decode($change,true);
        $cates=$m->where('fid',0)->order('sort asc,code_num asc')->column('id,name');
        
        $this->assign('info',$info);
        $this->assign('info1',$info1);
        $this->assign('cates',$cates);
        $this->assign('change',$change);
        return $this->fetch();
    }
    /**
     * 产品分类信息编辑审核
     * @adminMenu(
     *     'name'   => '产品分类编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
    /**
     * 产品分类编辑记录批量删除
     * @adminMenu(
     *     'name'   => '产品分类编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    /**
     * 产品分类批量删除
     * @adminMenu(
     *     'name'   => '产品分类批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类批量删除',
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
        $tmp=db('goods')->where($where)->find();
        if(!empty($tmp)){
            $this->error('分类'.$tmp['cid'].'下有产品'.$tmp['name'].$tmp['code']);
        }
        parent::del_all();
        
    }
}
