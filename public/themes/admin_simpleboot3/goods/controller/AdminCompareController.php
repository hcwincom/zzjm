<?php
 
namespace app\goods\controller;

 
use think\Db; 
 
 
class AdminCompareController extends GoodsBaseController
{
   
  
    public function _initialize()
    {
        parent::_initialize();
        $this->flag='产品对比';
        $this->assign('flag',$this->flag);
        $this->table='compare';
        $this->m=Db::name('compare'); 
        $this->edit=['name','sort','dsc','res'];
         
        $this->assign('table',$this->table); 
    }
     
     
    /**
     * 产品对比表
     * @adminMenu(
     *     'name'   => '产品对比表',
     *     'parent' => 'goods/work/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 2,
     *     'icon'   => '',
     *     'remark' => '产品对比表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $file_type=[
            1=>['pic_jm','极敏商城图片'],
            2=>['pic_pro','实物图片'],
            3=>['pic_logo','极敏logo图片'],
            4=>['pic_param','产品规格图'],
            5=>['pic_principle','产品原理图'],
            6=>['pic_other','其他图片'],
            7=>['file_instructions','产品说明书'],
            8=>['file_other','其他文档'],
        ];
        $this->assign('file_type',$file_type); 
        $this->assign('goods_type',config('goods_type'));
        $this->assign('sn_type',config('sn_type'));
        $this->assign('is_box',config('is_box'));
        $admin=$this->admin;
         
        $data=$this->request->param();
        
        $where=[];
        
        if($admin['shop']!=1){
            $where['comp.shop']=['eq',$admin['shop']];
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
        if(empty($data['bchar']) || $data['bchar']==-1){
            $data['bchar']=-1;
        }else{
            $where['p.bchar']=['eq',$data['bchar']];
        }
        //品牌
        if(empty($data['brand'])){
            $data['brand']=0;
        }else{
            $where['p.brand']=['eq',$data['brand']];
        }
        //关联设备数
        $goods_links=[
            '-1'=>'关联设备',
            '0'=>'0个',
            '1'=>'1个',
            '2'=>'2个',
            '3'=>'3个以上',
        ];
        $this->assign('goods_links',$goods_links);
        if(!isset($data['goods_link']) || $data['goods_link']==-1){
            $data['goods_link']=-1;
        }else{
            switch($data['goods_link']){
                case 3:
                    $where['p.goods_link']=['egt',3];
                    break;
                default:
                    $where['p.goods_link']=['eq',$data['goods_link']];
                    break;
            }
        }
        
        //关联资料数
       
        $about_link_nums=[
            '-1'=>'数量',
            '0'=>'0个',
            '1'=>'1个',
            '2'=>'2个',
            '3'=>'3个以上',
        ];
        $this->assign('about_link_nums',$about_link_nums);
        if(empty($data['about_link']) || !isset($data['about_link_num']) || $data['about_link_num']==-1){
            $data['about_link']=0;
            $data['about_link_num']=-1;
            
        }else{
            $about=$file_type[$data['about_link']][0];
            switch($data['about_link_num']){
                case -1:
                    break;
                case 3:
                    $where['p.'.$about]=['egt',3];
                    break;
                default:
                    $where['p.'.$about]=['eq',$data['about_link_num']];
                    break;
            }
        }
        //价格
        $prices=[
            'price0'=>'价格',
            'price_sale'=>'零售价格',
            'price_in'=>'入库价',
            'price_cost'=>'出厂价',
            'price_min'=>'最低销售价',
            'price_range1'=>'区间价1',
            'price_range2'=>'区间价2',
            'price_range3'=>'区间价3',
            'price_dealer1'=>'经销价1',
            'price_dealer2'=>'经销价2',
            'price_dealer3'=>'经销价3',
            'price_trade'=>'同行价',
            'price_factory'=>'工程配套价',
        ];
        $this->assign('prices',$prices);
        if(empty($data['price']) || $data['price']=='price0'){
            $data['price']='price0';
            $data['price1']='';
            $data['price2']='';
        }else{
            
            //判断处理价格参数
            if(!isset($data['price1']) || $data['price1']==''){
                $data['price1']=='';
                
            }else{
                $price1=0;
                $data['price1']=round($data['price1'],2);
                $price1=$data['price1'];
                if($price1<0){
                    $this->error('价格不能小于0');
                }
            }
            if(!isset($data['price2']) || $data['price2']==''){
                $data['price2']=='';
                
            }else{
                $price2=0;
                $data['price2']=round($data['price2'],2);
                $price2=$data['price2'];
                if($price2<0){
                    $this->error('价格不能小于0');
                }
            }
            //判断查询条件
            if(isset($price1)){
                if(isset($price2)){
                    //最大最小价格都有
                    if($price2<$price1){
                        $this->error('最大价格不能小于最小价格');
                    }
                    $where_price=['between',[$price1,$price2]];
                }else{
                    //最小价格
                    $where_price=['egt',$price1];
                }
            }elseif(isset($price2)){
                //只有最大价
                $where_price=['elt',$price2];
            }
            //组装
            if(!empty($where_price)){
                $where['p.'.$data['price']]=$where_price;
            }
            
        }
        
        
        //重量体积
        $bigs=[
            'big0'=>'重量体积',
            'weight0'=>'净重量',
            'size0'=>'净体积',
            'weight1'=>'毛重量',
            'size1'=>'毛体积',
            
        ];
        $this->assign('bigs',$bigs);
        if(empty($data['big']) || $data['big']=='big0'){
            $data['big']='big0';
            $data['big1']='';
            $data['big2']='';
            
        }else{
            
            //判断处理重量体积参数
            if(!isset($data['big1']) || $data['big1']==''){
                $data['big1']=='';
                
            }else{
                $big1=0;
                $data['big1']=round($data['big1'],2);
                $big1=$data['big1'];
                if($big1<0){
                    $this->error('重量体积不能小于0');
                }
            }
            if(!isset($data['big2']) || $data['big2']==''){
                $data['big2']=='';
                
            }else{
                $big2=0;
                $data['big2']=round($data['big2'],2);
                $big2=$data['big2'];
                if($big2<0){
                    $this->error('重量体积不能小于0');
                }
            }
            //判断查询条件
            if(isset($big1)){
                if(isset($big2)){
                    //最大最小重量体积都有
                    if($big2<$big1){
                        $this->error('最大重量体积不能小于最小重量体积');
                    }
                    $where_big=['between',[$big1,$big2]];
                }else{
                    //最小重量体积
                    $where_big=['egt',$big1];
                }
            }elseif(isset($big2)){
                //只有最大价
                $where_big=['elt',$big2];
            }
            //组装
            if(!empty($where_big)){
                $where['p.'.$data['big']]=$where_big;
            }
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
        //对比表
        //状态
        if(empty($data['gcstatus'])){
            $data['gcstatus']=0;
        }else{
            $where['comp.status']=['eq',$data['gcstatus']];
        }
        //对比名称
        if(empty($data['gcname'])){
            $data['gcname']='';
        }else{
            $where['comp.name']=['like','%'.$data['gcname'].'%'];
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
                    $where['comp.'.$data['time']]=['elt',$time2];
                }
            }else{
                //有开始时间
                $time1=strtotime($data['datetime1']);
                if(empty($data['datetime2'])){
                    $data['datetime2']='';
                    $where['comp.'.$data['time']]=['egt',$time1];
                }else{
                    //有结束时间有开始时间between
                    $time2=strtotime($data['datetime2']);
                    if($time2<=$time1){
                        $this->error('结束时间必须大于起始时间');
                    }
                    $where['comp.'.$data['time']]=['between',[$time1,$time2]];
                }
            }
        }
      
        $list=Db::name('goods_compare')
        ->alias('gc')
        ->field('comp.*,p.id as pid,p.name as pname,p.code as pcode,s.name as sname,b.name as bname,a.user_nickname as aname')
        ->join('cmf_goods p','p.id=gc.pid') 
        ->join('cmf_compare comp','comp.id=gc.compare_id') 
        ->join('cmf_shop s','s.id=comp.shop','left') 
        ->join('cmf_brand b','b.id=p.brand','left')
        ->join('cmf_user a','a.id=comp.aid','left') 
        ->where($where)
        ->order('comp.status asc,comp.time desc')
        ->paginate();
        
        
        // 获取分页显示
        $page = $list->appends($data)->render();
        
        $this->assign('page',$page);
        $this->assign('list',$list);
        
        $this->assign('data',$data);
        $this->assign('types',$types);
        $this->assign('times',$times);
        $this->assign("search_types", $search_types);
        
        //分类
        $goods0=new Goods0Controller();
        $goods0->cates();
        $this->assign('cid0',$data['cid0']);
        $this->assign('cid',$data['cid']);
        $this->assign('select_class','form-control');
        
        
        return $this->fetch();
         
    }
    /**
     * 产品对比表添加
     * @adminMenu(
     *     'name'   => '产品对比表添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 2,
     *     'icon'   => '',
     *     'remark' => '产品对比表添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
        return $this->fetch();
    }
    /**
     * 产品对比添加do
     * @adminMenu(
     *     'name'   => '产品对比添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品对比添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        $m=$this->m;
        $admin=$this->admin;
        $time=time();
        $data=$this->request->param();
        if(empty($data['name'])){
            $this->error('名称不能为空');
        }
        if(empty($data['pids'])){
            $this->error('未选择对比产品');
        }
        $url=url('index');
         
         
        $data_add=[
            'name'=>$data['name'],
            'cid'=>$data['cid0'],
            'res'=>$data['res'],
            'dsc'=>$data['dsc'],
            'template'=>$data['template'],
            'status'=>1,
            'aid'=>$admin['id'],
            'atime'=>$time,
            'time'=>$time,
            'shop'=>$admin['shop'],
        ];
        $m->startTrans();
        $id=$m->insertGetId($data_add);
         //添加关联
         $links=[];
         foreach($data['pids'] as $k=>$v){
             $links[]=[
                 'compare_id'=>$id,
                 'pid'=>$v,
             ];
         }
         Db::name('goods_compare')->insertAll($links);
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
     * 产品对比详情
     * @adminMenu(
     *     'name'   => '产品对比详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品对比详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
      
        $m=$this->m;
        $id=$this->request->param('id',0,'intval');
        $info=$m
        ->alias('p')
        ->field('p.*,c.name as cname,t.name as tname')
        ->join('cmf_cate c','c.id=p.cid','left')
        ->join('cmf_template t','t.id=p.template','left')
        ->where('p.id',$id)
        ->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        //二级分类
        $where_cate=[
            'status'=>2,
            'fid'=>$info['cid']
        ];
        $cates=Db::name('cate')->where($where_cate)->column('id,name');
        //所有产品
        $where_goods=[
            'status'=>2,
            'cid0'=>$info['cid'],
            'template'=>$info['template']
        ];
        $goods=Db::name('goods')->where($where_goods)->column('id,cid,name');
        
        //所有参数项
        $params=Db::name('template_param')
        ->alias('tp')
        ->join('cmf_param p','p.id=tp.p_id')
        ->where('tp.t_id',$info['template'])
        ->column('p.id,p.name');
        //对比的数据
        $list=Db::name('goods_compare') 
        ->where('compare_id',$info['id'])
        ->column('pid');
       
        $this->assign('info',$info);
        $this->assign('cates',$cates);
        $this->assign('goods',$goods);
        $this->assign('params',$params);
        $this->assign('list',$list);
       
        return $this->fetch();
    }
    /**
     * 产品对比状态审核
     * @adminMenu(
     *     'name'   => '产品对比状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品对比状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 产品对比状态批量同意
     * @adminMenu(
     *     'name'   => '产品对比状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品对比状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    /**
     * 产品对比禁用
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
     * 产品对比信息状态恢复
     * @adminMenu(
     *     'name'   => '产品对比信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品对比信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 产品对比编辑提交
     * @adminMenu(
     *     'name'   => '产品对比编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品对比编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        parent::edit_do();
    }
    /**
     * 产品对比编辑列表
     * @adminMenu(
     *     'name'   => '产品对比编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品对比编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();
    }
    
    /**
     * 产品对比审核详情
     * @adminMenu(
     *     'name'   => '产品对比审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品对比审核详情',
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
        
        //原关联产品
        $pids0=Db::name('goods_compare')
        ->alias('gc')
        ->join('cmf_goods g','g.id=gc.pid')
        ->where('gc.compare_id',$info['id'])
        ->column('gc.pid,g.name');
        $pids0=implode('--', $pids0);
        $this->assign('pids0',$pids0);
        
        //新关联产品
        if(isset($change['pids'])){
            $pids1=json_decode($change['pids'],true);
            
            $pids1=Db::name('goods')
            ->where('id','in',$pids1)
            ->column('id,name');
            $pids1=implode('--', $pids1);
            $this->assign('pids1',$pids1);
            
        }
                 
        
        $this->assign('info',$info);
        $this->assign('info1',$info1);
        $this->assign('change',$change);
        $this->cates();
        return $this->fetch();
    }
    /**
     * 产品对比信息编辑审核
     * @adminMenu(
     *     'name'   => '产品对比编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品对比编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
    /**
     * 产品对比编辑记录批量删除
     * @adminMenu(
     *     'name'   => '产品对比编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品对比编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    
    /**
     * 产品对比批量删除
     * @adminMenu(
     *     'name'   => '产品对比批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品对比批量删除',
     *     'param'  => ''
     * )
     */
    public function del_all()
    {
        
        parent::del_all();
        
    }
    
}
