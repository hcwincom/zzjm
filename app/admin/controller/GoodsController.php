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
    private $statuss;
    private $review_status;
    private $table;
    private $fields;
    private $flag;
   
    public function _initialize()
    {
        parent::_initialize();
        $this->m=Db::name('goods');
        $this->flag='产品'; 
        $this->table='goods'; 
        $this->assign('flag',$this->flag);
        $this->statuss=config('info_status');
        $this->review_status=config('review_status');
        
        $this->assign('statuss',$this->statuss);
        $this->assign('review_status',$this->review_status);
        $this->assign('html',$this->request->action());
        $this->assign('goods_type',config('goods_type'));
        $this->assign('sn_type',config('sn_type'));
        $this->assign('is_box',config('is_box'));
        //计算小数位
        bcscale(2);
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
        $admin=$this->admin;
         
        $m=$this->m;
        $data=$this->request->param();
        $admin=$this->admin;
        $where=[];
        if($admin['shop']!=1){
            $where['p.shop']=['eq',$admin['shop']];
        }
       
        //状态
        if(empty($data['status'])){
            $data['status']=0;
        }else{
            $where['p.status']=['eq',$data['status']];
        }
        //类型
        if(empty($data['type'])){
            $data['type']=0;
        }else{
            $where['p.type']=['eq',$data['type']];
        }
        //创建 人
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
        //一级分类
        if(empty($data['cid0'])){
            $data['cid0']=0;
        }else{
            $where['p.cid0']=['eq',$data['cid0']];
        }
        //二级分类
        if(empty($data['cid'])){
            $data['cid']=0;
        }else{
            $where['p.cid']=['eq',$data['cid']];
        }
        
        //品牌分类
        if(empty($data['bchar'])){
            $data['bchar']=-1;
        }else{
            $where['b.bchar']=['eq',$data['bchar']];
        }
        //品牌
        if(empty($data['brand'])){
            $data['brand']=-1;
        }else{
            $where['p.brand']=['eq',$data['brand']];
        }
        
        //查询字段
        $types=config('goods_search');
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
        ->field('p.*,s.name as sname,b.name as bname')
        ->join('cmf_shop s','s.id=p.shop','left')
        ->join('cmf_brand b','b.id=p.brand','left')
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
        //分类
        $this->cates();
        $this->assign('cid0',$data['cid0']);
        $this->assign('cid',$data['cid']);
        $this->assign('select_class','form-control');
        //品牌
        $this->brands();
        $this->assign('bchar',$data['bchar']);
        $this->assign('brand',$data['brand']);
        return $this->fetch();
    }
    /**
     * 产品添加
     * @adminMenu(
     *     'name'   => '产品添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        //分类
       $this->cates();
       $this->assign('cid0',0);
       $this->assign('cid',0);
       //品牌
       $this->brands();
       $this->assign('bchar',-1);
       $this->assign('brand',-1); 
       //权限检测
        $admin=$this->admin;
        $actions=$this->auth_get($admin['id']); 
        $this->assign('actions',$actions); 
        
        //价格模板
        if(isset($actions['auth']) || isset($actions['price_set'])){
            $this->prices();
        }else{
            $this->assign('prices',[]);
        }
        $this->assign('info',null);
        
       return $this->fetch();
    }
    //选择二级分类得到三级编码
    public function cid_change(){
        $cid=$this->request->param('cid',0,'intval');
        $id=$this->request->param('id',0,'intval');
        if($cid<1){
            $this->error('无效分类');
        }
        $m=$this->m;
        if($id!=0){
            $info=$m->where(['id'=>$id])->find();
            //分类没变
            if($info['cid']==$cid){
                $this->success($info['code_num']);
                exit;
            }
        }
        $where=[
            'id'=>$cid, 
            'fid'=>['neq',0],
        ];
        $info=db('cate')->field('id,max_num')->where($where)->find();
        if(empty($info)){
            $this->error('无效分类');
        }else{
            $this->success($info['max_num']+1);
        }
        
    }
    
    //产品添加编码
    public function add_code(){
        $id=$this->request->param('id',0,'intval');
        $cid=$this->request->param('cid',0,'intval');
        $code_num=$this->request->param('code_num',0,'intval');
        $name=$this->request->param('name','');
        $m=$this->m;
        //检查编码是否合法
        $where=[
            'cid'=>$cid,
            'code_num'=>$code_num,
        ];
        if(!empty($id)){
            $where['id']=['neq',$id];
        }
        $tmp=$m->where($where)->find();
        if(!empty($tmp)){
            $this->error('该编码已存在');
        }
         
        $cate=db('cate')
        ->field('c.*,f.name as fname')
        ->alias('c')
        ->join('cmf_cate f','f.id=c.fid')
        ->where('c.id',$cid)
        ->find();
        if(empty($cate) || $cate['fid']==0){
            $this->error('分类选择不合法');
        } 
        
        //下面组装产品名称和编码 
        $name0=$cate['name'].$name.$cate['fname'];
        $code=$cate['code'].'-'.str_pad($code_num, 2,'0',STR_PAD_LEFT);
        $this->success($name0,'',['code'=>$code]);
    }
    /**
     * 产品参数模板选择
     * @adminMenu(
     *     'name'   => '产品参数模板选择',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 80,
     *     'icon'   => '',
     *     'remark' => '产品参数模板选择',
     *     'param'  => ''
     * )
     */
    public function template_set(){
        $cid=$this->request->param('cid',0,'intval');
        if($cid<=0){
            $this->success('no');
        }
        $where=[
            'cid'=>$cid,
            'status'=>2,
        ];
        $tmps=db('template')->where($where)->order('sort asc')->column('id,name');
        if(empty($tmps)){
            $this->success('no');
        }
        $this->success('ok','',['list'=>$tmps]);
    }
    /**
     * 产品参数设置
     * @adminMenu(
     *     'name'   => '产品参数设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 80,
     *     'icon'   => '',
     *     'remark' => '产品参数设置',
     *     'param'  => ''
     * )
     */
    public function param_set(){
        $t_id=$this->request->param('t_id',0,'intval');
        if($t_id<=0){
            $this->success('no');
        }
        $where=[
            'tp.t_id'=>$t_id,
            'p.status'=>2,
        ];
        $tmps=db('template_param')
        ->alias('tp')
        ->join('cmf_param p','tp.p_id=p.id')
        ->where($where)
        ->order('p.sort asc')
        ->column('p.id,p.name,p.type,p.content,p.dsc');
       
        if(empty($tmps)){
            $this->success('no');
        }
        foreach($tmps as $k=>$v){
            if($tmps[$k]['type']==3){
                $tmps[$k]['content']='';
            }else{
                //清除不规范输入导致的空格
                $tmps[$k]['content']=explode(',',$tmps[$k]['content']);
            }
        }
        $this->success('ok','',['list'=>$tmps]);
    }
    //获取价格模板
    public function prices(){
        $where=[
            'status'=>2,
        ];
        $prices=db('price')->where($where)->order('sort asc,name asc')->column('id,name');
        $this->assign('prices',$prices);
        
    }
    /**
     * 产品价格模板确认
     * @adminMenu(
     *     'name'   => '产品价格模板确认',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>90,
     *     'icon'   => '',
     *     'remark' => '产品价格模板确认',
     *     'param'  => ''
     * )
     */
    public function price_set(){
        $t_id=$this->request->param('t_id',0,'intval');
        $price_in=$this->request->param('price_in',0);
        if($t_id<=0 || $price_in<=0){
            $this->success('no');
        }
        //按模板规则计算得到各种价格，暂时不写
        $data=[];
        $data['price_cost']=$price_in;
        $data['price_min']=$price_in;
        $data['price_range1']=$price_in;
        $data['price_range2']=$price_in;
        $data['price_range3']=$price_in;
        $data['price_dealer1']=$price_in;
        $data['price_dealer2']=$price_in;
        $data['price_dealer3']=$price_in;
        $data['price_trade']=$price_in;
        $data['price_factory']=$price_in;
        $this->success('ok','',$data);
        
    }
    /**
     * 产品入库价设置
     * @adminMenu(
     *     'name'   => '产品入库价设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>91,
     *     'icon'   => '',
     *     'remark' => '产品入库价设置',
     *     'param'  => ''
     * )
     */
    public function price_in_set(){
        $this->success('ok');
    }
    /**
     * 产品出库价设置
     * @adminMenu(
     *     'name'   => '产品出库价设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>92,
     *     'icon'   => '',
     *     'remark' => '产品出库价设置',
     *     'param'  => ''
     * )
     */
    public function price_cost_set(){
        $this->success('ok');
    }
    /**
     * 产品最低销售价设置
     * @adminMenu(
     *     'name'   => '产品最低销售价设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>93,
     *     'icon'   => '',
     *     'remark' => '产品最低销售价设置',
     *     'param'  => ''
     * )
     */
    public function price_min_set(){
        $this->success('ok');
    }
    /**
     * 产品区间价格设置
     * @adminMenu(
     *     'name'   => '产品区间价设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>94,
     *     'icon'   => '',
     *     'remark' => '产品区间1价设置',
     *     'param'  => ''
     * )
     */
    public function price_range_set(){
        $this->success('ok');
    }
    /**
     * 产品经销价1设置
     * @adminMenu(
     *     'name'   => '产品经销价1设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>95,
     *     'icon'   => '',
     *     'remark' => '产品经销价1设置',
     *     'param'  => ''
     * )
     */
    public function price_dealer1_set(){
        $this->success('ok');
    }
    /**
     * 产品经销价2设置
     * @adminMenu(
     *     'name'   => '产品经销价2设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>96,
     *     'icon'   => '',
     *     'remark' => '产品经销价2设置',
     *     'param'  => ''
     * )
     */
    public function price_dealer2_set(){
        $this->success('ok');
    }
    /**
     * 产品经销价3设置
     * @adminMenu(
     *     'name'   => '产品经销价3设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>97,
     *     'icon'   => '',
     *     'remark' => '产品经销价3设置',
     *     'param'  => ''
     * )
     */
    public function price_dealer3_set(){
        $this->success('ok');
    }
    /**
     * 产品同行价设置
     * @adminMenu(
     *     'name'   => '产品同行价设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>97,
     *     'icon'   => '',
     *     'remark' => '产品同行价设置',
     *     'param'  => ''
     * )
     */
    public function price_trade_set(){
        $this->success('ok');
    }
    /**
     * 产品工厂配套价设置
     * @adminMenu(
     *     'name'   => '产品工厂配套价设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>98,
     *     'icon'   => '',
     *     'remark' => '产品工厂配套价设置',
     *     'param'  => ''
     * )
     */
    public function price_factory_set(){
        $this->success('ok');
    }
    
    /**
     * 产品入库价查看
     * @adminMenu(
     *     'name'   => '产品入库价查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>101,
     *     'icon'   => '',
     *     'remark' => '产品入库价查看',
     *     'param'  => ''
     * )
     */
    public function price_in_get(){
        $this->success('ok');
    }
    /**
     * 产品出库价查看
     * @adminMenu(
     *     'name'   => '产品出库价查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>102,
     *     'icon'   => '',
     *     'remark' => '产品出库价查看',
     *     'param'  => ''
     * )
     */
    public function price_cost_get(){
        $this->success('ok');
    }
    /**
     * 产品最低销售价查看
     * @adminMenu(
     *     'name'   => '产品最低销售价查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>103,
     *     'icon'   => '',
     *     'remark' => '产品最低销售价查看',
     *     'param'  => ''
     * )
     */
    public function price_min_get(){
        $this->success('ok');
    }
    /**
     * 产品区间价格查看
     * @adminMenu(
     *     'name'   => '产品区间价查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>104,
     *     'icon'   => '',
     *     'remark' => '产品区间1价查看',
     *     'param'  => ''
     * )
     */
    public function price_range_get(){
        $this->success('ok');
    }
    /**
     * 产品经销价1查看
     * @adminMenu(
     *     'name'   => '产品经销价1查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>105,
     *     'icon'   => '',
     *     'remark' => '产品经销价1查看',
     *     'param'  => ''
     * )
     */
    public function price_dealer1_get(){
        $this->success('ok');
    }
    /**
     * 产品经销价2查看
     * @adminMenu(
     *     'name'   => '产品经销价2查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>106,
     *     'icon'   => '',
     *     'remark' => '产品经销价2查看',
     *     'param'  => ''
     * )
     */
    public function price_dealer2_get(){
        $this->success('ok');
    }
    /**
     * 产品经销价3查看
     * @adminMenu(
     *     'name'   => '产品经销价3查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>107,
     *     'icon'   => '',
     *     'remark' => '产品经销价3查看',
     *     'param'  => ''
     * )
     */
    public function price_dealer3_get(){
        $this->success('ok');
    }
    /**
     * 产品同行价查看
     * @adminMenu(
     *     'name'   => '产品同行价查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>107,
     *     'icon'   => '',
     *     'remark' => '产品同行价查看',
     *     'param'  => ''
     * )
     */
    public function price_trade_get(){
        $this->success('ok');
    }
    /**
     * 产品工厂配套价查看
     * @adminMenu(
     *     'name'   => '产品工厂配套价查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>108,
     *     'icon'   => '',
     *     'remark' => '产品工厂配套价查看',
     *     'param'  => ''
     * )
     */
    public function price_factory_get(){
        $this->success('ok');
    }
     
    /**
     * 产品添加do
     * @adminMenu(
     *     'name'   => '产品添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        exit();
        $m=$this->m;
        $data=$this->request->param('');
        
        $url=url('index');
         
        $time=time();
        $admin=$this->admin;
        $data_add=[
            'cid'=>$data['cid'],
            'code_num'=>intval($data['code_num']),
            'code_name'=>$data['code_name'],
            'name'=>$data['name'], 
            'sort'=>intval($data['sort']),
            'status'=>1, 
            'dsc'=>$data['dsc'],
            'type'=>$data['type'],
            'sn_type'=>$data['sn_type'],
            'sn'=>$data['sn'],
            'name2'=>$data['name2'],
            'name3'=>$data['name3'],
            'brand'=>$data['brand'],
            'weight0'=>$data['weight0'],
            'length0'=>$data['length0'],
            'width0'=>$data['width0'],
            'height0'=>$data['height0'], 
            'is_box'=>$data['is_box'],
            'weight1'=>$data['weight1'],
            'length1'=>$data['length1'],
            'width1'=>$data['width1'],
            'height1'=>$data['height1'],
            'aid'=>$admin['id'],
            'atime'=>$time,
             
        ];
        $data_add=$this->param_check($data_add);
        if(!is_array($data_add)){
            $this->error($data_add);
        }
        //超管添加自营产品
        if($admin['shop']==1){
            $data_add['shop']=2;
        }
        $m->startTrans();
        try {
            $id=$m->insertGetId($data_add);
        } catch (\Exception $e) {
            $m->rollback();
            $this->error($e->getMessage());
        }
        if($id<=0){
            $m->rollback();
            $this->error('添加失败，请刷新重试');
        }
        $m_cate=db('cate');
        $max=$m_cate->where('id',$data_add['cid'])->value('max_num');
        if($data_add['code_num']>$max){
            $m_cate->where('id',$data_add['cid'])->update(['max_num'=>$data_add['code_num']]);
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
        $m->commit();
        $this->success('添加成功',$url);
    }
     
    /**
     * 产品详情
     * @adminMenu(
     *     'name'   => '产品详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品详情',
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
        $this->cates();
        $this->assign('cid0',$info['cid0']);
        $this->assign('cid',$info['cid']);
        $this->assign('info',$info);
         
        return $this->fetch();
    }
    /**
     * 产品状态审核
     * @adminMenu(
     *     'name'   => '产品状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 产品状态批量同意
     * @adminMenu(
     *     'name'   => '产品状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        if(empty($_POST['ids'])){
            $this->error('未选中信息');
        }
        $ids=$_POST['ids'];
        $m=$this->m;
        $admin=$this->admin;
        $time=time();
        $where=[
            'id'=>['in',$ids],
            'status'=>['eq',1],
        ];
        //其他店铺检查,如果没有shop属性就只能是1号主站操作,有shop属性就带上查询条件
        if($admin['shop']!=1){
            $tmp=$m->where($where)->find();
            if(empty($tmp['shop']) || $tmp['shop']!=$admin['shop']){
                $this->error('不能审核其他店铺的信息');
            }else{
                $where['shop']=['eq',$admin['shop']];
            }
        }
        
        $update=[
            'status'=>2,
            'time'=>$time,
            'rid'=>$admin['id'],
            'rtime'=>$time,
        ];
        //得到要更改的数据
        $list=$m->where($where)->column('id,aid,name');
        $ids=implode(',',array_keys($list));
        
        //审核成功，记录操作记录,发送审核信息
        $flag=$this->flag;
        
        $table=$this->table;
        //记录操作记录
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>'批量同意'.$flag.'('.$ids.')',
            'table'=>$table,
            'type'=>'review_all',
            'link'=>'',
            'shop'=>$admin['shop'],
        ];
        $link0=url('admin/'.$table.'/edit','',false,false);
        foreach($list as $k=>$v){
            //发送审核信息
            $data_msg[]=[
                'aid'=>1,
                'time'=>$time,
                'uid'=>$v['aid'],
                'dsc'=>'对'.$flag.$v['id'].'-'.$v['name'].'已批量审核，结果为同意',
                'type'=>'review',
                'link'=>$link0.'/id/'.$v['id'],
                'shop'=>$admin['shop'],
            ];
        }
        $m->startTrans();
        $rows=$m->where($where)->update($update);
        if($rows<=0){
            $m->rollback();
            $this->error('没有数据审核成功，批量审核只能把未审核的数据审核为正常');
        }
        db('action')->insert($data_action);
        db('msg')->insertAll($data_msg);
        $m->commit();
        $this->success('审核成功'.$rows.'条数据');
    }
    /**
     * 产品禁用
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
        //区分是一个还是数组
        $id=$this->request->param('id',0,'intval');
        
        $where=['status'=>['eq',2]];
        if($id>0){
            $where['id']=['eq',$id];
        }elseif(empty($_POST['ids'])){
            $this->error('未选中信息');
        }else{
            $ids=$_POST['ids'];
            $where['id']=['in',$ids];
        }
        
        $m=$this->m;
        
        $update=['status'=>4];
        $rows=$m->where($where)->update($update);
        
        if($rows>=1){
            
            $this->success('已禁用'.$rows.'条数据');
        }else{
            $this->error('没有成功禁用数据，禁用是指将状态为正常改为禁用');
        }
    }
    /**
     * 产品信息状态恢复
     * @adminMenu(
     *     'name'   => '产品信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        //区分是一个还是数组
        $id=$this->request->param('id',0,'intval');
        
        $where=['status'=>['eq',4]];
        if($id>0){
            $where['id']=['eq',$id];
        }elseif(empty($_POST['ids'])){
            $this->error('未选中信息');
        }else{
            $ids=$_POST['ids'];
            $where['id']=['in',$ids];
        }
        
        $m=$this->m;
        $update=['status'=>2];
        $rows=$m->where($where)->update($update);
        
        if($rows>=1){
            $this->success('已恢复'.$rows.'条数据');
        }else{
            $this->error('没有成功恢复数据,恢复是指将状态为禁用改为正常');
        }
    }
    /**
     * 产品编辑提交
     * @adminMenu(
     *     'name'   => '产品编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        parent::edit_do();
    }
    /**
     * 产品编辑列表
     * @adminMenu(
     *     'name'   => '产品编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        exit('no');
        return $this->fetch();
    }
    
    /**
     * 产品审核详情
     * @adminMenu(
     *     'name'   => '产品审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品审核详情',
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
     * 产品信息编辑审核
     * @adminMenu(
     *     'name'   => '产品编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
    /**
     * 产品编辑记录批量删除
     * @adminMenu(
     *     'name'   => '产品编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    /**
     * 产品批量删除
     * @adminMenu(
     *     'name'   => '产品批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品批量删除',
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
    //获取分类信息
    public function cates(){
        //分类
        $m_cate=db('cate');
        $where_cate=[
            'fid'=>0,
            'status'=>2,
        ];
        $cates0=$m_cate->where($where_cate)->order('sort asc,code_num asc')->column('id,name,code');
        $where_cate=[
            'fid'=>['neq',0],
            'status'=>['eq',2],
        ];
        $cates=$m_cate->where($where_cate)->order('sort asc,code_num asc')->column('id,name,fid,code');
        $this->assign('cates0',$cates0);
        $this->assign('cates',$cates);
    }
    //获取品牌信息
    public function brands(){
        //分类 
        $bcates=config('chars');
        $where_brand=[ 
            'status'=>['eq',2],
        ];
        $brands=db('brand')->where($where_brand)->order('sort asc')->column('id,name,char');
        $this->assign('bcates',$bcates);
        $this->assign('brands',$brands);
    }
    //获取权限信息
    public function auth_get($id,$str='admin/goods/'){
        $actions=[];
        //检测是否超级管理员
        if($id==1){
            $actions['auth']=1;
            return $actions;
        }
        $roles=db('role_user')->where('user_id',$id)->column('role_id');
        //检测是否超级管理员
        if(in_array(1,$roles)){
            $actions['auth']=1;
        } else{
            $where=[
                'role_id'=>['in',$roles],
                'rule_name'=>['like',$str.'%'],
            ];
            $len=strlen($str)+1;
            $actions=db('auth_access')->where($where)->column("substring(rule_name,$len)");
            $actions=array_flip($actions);
        }
        return $actions;
    }
    //检查产品参数
    public function param_check($data){
        if(empty($data['code_num'])){
           return ('未添加编码');
        }
        if(empty($data['name'])){
           return ('名称不能为空');
        }
        //补充分类和编码
        $cate=db('cate')
        ->field('c.*,f.name as fname')
        ->alias('c')
        ->join('cmf_cate f','f.id=c.fid')
        ->where('c.id',$data['cid'])
        ->find();
        if(empty($cate) || $cate['fid']==0){
            return ('分类选择不合法');
        } 
        $data['cid0']=$cate['fid'];
        $data['code']=$cate['code'].str_pad($data['code_num'], 2,'0',STR_PAD_LEFT);
        $m=$this->m;
        $where=[
           'cid'=>$data['cid'],
           'code_num'=>$data['code_num'], 
        ];
        if(!empty($data['id'])){
            $where['id']=['neq',$data['id']];
        }
        $tmp=$m->where($where)->find();
        if(!empty($tmp)){
            return '编码已被占用';
        }
         //转化基本参数
        $data['weight0']=round($data['weight0'],2);
        $data['length0']=round($data['length0'],2);
        $data['width0']=round($data['width0'],2);
        $data['height0']=round($data['height0'],2);
        $data['is_box']=intval($data['is_box']);
        
        $data['weight1']=round($data['weight1'],2);
        $data['length1']=round($data['length1'],2);
        $data['width1']=round($data['width1'],2);
        $data['height1']=round($data['height1'],2);
        
        if($data['weight0'] <= 0){
            return '请填写产品重量';
        } 
        if($data['length0'] <= 0){
            return '请填写产品长度';
        }
        if($data['width0'] <= 0){
            return '请填写产品宽度';
        }
        if($data['height0'] <= 0){
            return '请填写产品高度';
        }
        $data['size0']=bcmul($data['length0']*$data['width0'],$data['height0']);
        
        if($data['is_box']==2){
            $data['weight1']=$data['weight0'];
            $data['length1']=$data['length0'];
            $data['width1']=$data['width0'];
            $data['height1']=$data['height0'];
            $data['size1']=$data['size0'];
        }else{
            $data['weight1']=round($data['weight1'],2);
            $data['length1']=round($data['length1'],2);
            $data['width1']=round($data['width1'],2);
            $data['height1']=round($data['height1'],2);
            if($data['weight1'] <= 0){
                return '请填写产品毛重量';
            }
            if($data['length1'] <= 0){
                return '请填写产品内盒长度';
            }
            if($data['width1'] <= 0){
                return '请填写产品内盒宽度';
            }
            if($data['height1'] <= 0){
                return '请填写产品内盒高度';
            }
            $data['size1']=bcmul($data['length1']*$data['width1'],$data['height1']);
        }
        //检测价格权限$check=cmf_auth_check(20, 'admin/goods/index');
        return $data;
    }
}
