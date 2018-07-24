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
        $data_add=[
            'name'=>$data['name'],
            'fid'=>$fid,
            'sort'=>intval($data['sort']),
            'status'=>2,
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
            $m->insert($data_add);
        } catch (\Exception $e) {
            $this->error('操作失败，请重试');
        }
        //如果一级分类要更新配置中记录的最大编码，如果是2级要更新一级中的最大编码
        if($fid==0){
            cmf_set_dynamic_config(['cate_max'=>$data_add['code_num']]);
        }else{
            $m->where(['id'=>$fid])->update(['max_num'=>$data_add['code_num']]); 
        }
        
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
        $types=[
            'name'=>'名称',
            'code'=>'编码', 
            'id'=>'id',
        ];
        if(empty($data['name'])){
            $data['name']='';
            $data['type1']='name';
            $data['type2']=1;
        }else{
            if($data['type2']==1){
                $where['p.'.$data['type1']]=['eq',$data['name']];
            }else{
                $where['p.'.$data['type1']]=['like','%'.$data['name'].'%'];
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
        $m_edit=db($table.'_edit');
        $info1=$m_edit->where('id',$id)->find(); 
        //得到修改的字段
        $change=array_flip(explode(',', $info1['change'])); 
        
        if(empty($info1)){
            $this->error('编辑信息不存在');
        }
        $info=$m->where('id',$info1['pid'])->find();
        if(empty($info)){
            $this->error('编辑关联的信息不存在');
        }
        $cates=$m->where('fid',0)->order('sort asc,code_num asc')->column('id,name');
        
        $this->assign('info',$info);
        $this->assign('info1',$info1);
        $this->assign('cates',$cates);
        $this->assign('change',$change);
        return $this->fetch();
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
        //彻底删除
        $where=['id'=>['in',$ids]];
        $tmp=$m->where($where)->delete();
        if($tmp>0){
            $this->success('成功删除数据'.$tmp.'条');
        }else{
            $this->error('没有删除数据');
        }
        
    }
}
