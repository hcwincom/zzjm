<?php

namespace app\store\controller;


use cmf\controller\AdminBaseController;
use think\Db;

class AdminStoreinController extends AdminBaseController
{
    protected $m;
    protected $statuss;
    protected $review_status;
    protected $table;
    
    protected $flag;
    protected $isshop;
    //用于详情页中识别当前店铺,
    //列表页中分店铺查询
    protected $where_shop;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->statuss=config('info_status');
        $this->review_status=config('review_status'); 
        $this->assign('statuss',$this->statuss);
        $this->assign('review_status',$this->review_status);
       
        $this->assign('html',$this->request->action());
        $this->assign('about_type',config('store_in_type'));
        $this->isshop=1;
        $this->where_shop=0;
        
        $this->flag='出入库';
        $this->table='store_in';
        $this->m=Db::name('store_in');
        
        
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        
    }
    /**
     * 出入库列表
     * @adminMenu(
     *     'name'   => '出入库列表',
     *     'parent' => 'store/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 0,
     *     'icon'   => '',
     *     'remark' => '出入库列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $table=$this->table;
        $m=$this->m;
        $admin=$this->admin;
        $data=$this->request->param();
        $where=[];
        
        //店铺,分店只能看到自己的数据，总店可以选择店铺
        if($admin['shop']==1){
            $shops=Db::name('shop')->where('status',2)->column('id,name');
            //首页列表页去除总站
            unset($shops[1]);
            $this->assign('shops',$shops);
            if(empty($data['shop'])){
                $data['shop']=0;
            }else{
                $where['p.shop']=['eq',$data['shop']];
            }
        }else{
            $where_shop=$admin['shop'];
            $where['p.shop']=['eq',$admin['shop']];
        }
        //产品分类
        if(empty($data['cid0'])){
            $data['cid0']=0;
        }else{
            $where['goods.cid0']=['eq',$data['cid0']];
        }
        if(empty($data['cid'])){
            $data['cid']=0;
        }else{
            $where['goods.cid']=['eq',$data['cid']];
        }
        //查询字段
        $types=[
            'goods.name' => '产品名称',
            'goods.code'=>'产品编码',
            'goods.id' => '产品id',
            'goods.sn'=>'产品条码',
            'box.code'=>'料位编号',
        ];
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
            $where[$data['type1']]=zz_search($data['type2'],$data['name']);
        }
        
        //入库数量和金额
        //whereRaw运行原始sql--$where_num
        $where_num=1;
        $nums=['p.num'=>'入库数量','(p.num*goods.price_in)' => '总金额'];
       
        if(empty($data['num'])){
            $data['num']=key($nums);
            $data['num1']='';
            $data['num2']='';
        }else{
            //时间处理
            if(empty($data['num1'])){
                $data['num1']=''; 
                if(empty($data['num2'])){
                    $data['num2']=''; 
                }else{
                    //只有结束时间
                    $data['num2']=intval($data['num2']); 
                    $where_num=$data['num'].' <= '.$data['num2'];
                }
            }else{
                //有开始时间
                $data['num1']=intval($data['num1']);
                if(empty($data['num2'])){
                    $data['num2']='';
                    $where_num=$data['num'].' >= '.$data['num1']; 
                }else{
                    //有结束时间有开始时间between
                    $data['num2']=intval($data['num2']);
                    if($data['num2']<=$data['num1']){
                        $this->error('数值区间错误');
                    } 
                    $where_num=$data['num'].' >= '.$data['num1'].' and '.$data['num'].' <= '.$data['num2']; 
                }
            }
           
        }
      
        //时间类别
        $times=['atime'=>'申请入库时间','rtime' => '审核时间'];
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
        
        $join=[
            ['cmf_shop shop','p.shop=shop.id','left'],
            ['cmf_goods goods','p.goods=goods.id','left'],
            ['cmf_store_box box','p.box=box.id','left'],
            ['cmf_user a','p.aid=a.id','left'],
            ['cmf_user r','p.rid=r.id','left'],
        ];
        $field='p.*,shop.name as sname,goods.name as goods_name,goods.code as goods_code'.
            ',(p.num*goods.price_in) as money,box.code as box_code,a.user_nickname as aname,r.user_nickname as rname';
        /*  $field.=',sum(p.safe) as safe,sum(p.num) as num,sum(p.num1) as num1,sum(p.box_num) as box_num'.
         ',(sum(p.num)*goods.price_in) as money,max(p.time) as time';*/
        //仓库为0，
        if(empty($data['store'])){
            $data['store']=0;
        }elseif($data['store']==-1){
            $where['p.store']=['eq',0];
        }else{
            $where['p.store']=['eq',$data['store']];
        }
       //whereRaw运行原始sql
        $list=$m
        ->alias('p')
        ->field($field)
        ->join($join)
        ->where($where)
        ->whereRaw($where_num) 
        ->order('p.atime desc')
        ->paginate();
        // 获取分页显示
        $page = $list->appends($data)->render();
          
        //仓库
        $where=[
            'status'=>['eq',2],
            'type'=>['in',[1,2]],
        ];
        if(!empty($where_shop)){
            $where['shop']=$where_shop;
        }
        
        $stores=Db::name('store')->where($where)->order('shop asc,sort asc')->column('id,shop,name');
        $this->assign('stores',$stores);
        
        //分类
        $m_cate=Db::name('cate');
        $where_cate=[
            'fid'=>0,
            'status'=>2,
        ];
        $cates0=$m_cate->where($where_cate)->order('sort asc,code_num asc')->column('id,name,code');
        $where_cate=[
            'status'=>['eq',2],
            'fid'=>['gt',0],
        ];
        $cates=$m_cate->where($where_cate)->order('sort asc,code_num asc')->column('id,name,fid,code');
        $this->assign('cates0',$cates0);
        $this->assign('cates',$cates);
        $this->assign('cid0',$data['cid0']);
        $this->assign('cid',$data['cid']);
        $this->assign('select_class','form-control');
        
        $this->assign('page',$page);
        $this->assign('list',$list);
        
        $this->assign('data',$data);
        $this->assign('types',$types);
        $this->assign('times',$times);
        $this->assign('nums',$nums);
        $this->assign("search_types", $search_types);
        
        return $this->fetch();
    }
    
    /**
     * 出入库详情
     * @adminMenu(
     *     'name'   => '出入库详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '出入库详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $back=url('index');
        $id=$this->request->param('id',0,'intval');
        
        $m=$this->m;
        $join=[
            ['cmf_shop shop','p.shop=shop.id','left'],
            ['cmf_goods goods','p.goods=goods.id','left'],
            ['cmf_store_box box','p.box=box.id','left'],
            ['cmf_store store','p.store=store.id','left'],
            ['cmf_user a','p.aid=a.id','left'],
            ['cmf_user r','p.rid=r.id','left'],
        ];
        $field='p.*,shop.name as sname,goods.name as goods_name,goods.code as goods_code,store.name as store_name'.
            ',(p.num*goods.price_in) as money,box.code as box_code,a.user_nickname as aname,r.user_nickname as rname';
        
        //获取 数据
        $info=$m
        ->alias('p')
        ->field($field)
        ->join($join) 
        ->where('p.id',$id)->find();
        if(empty($info)){
            $this->error('数据不存在',$back);
        }
        //检查店铺
        $admin=$this->admin;
        if($admin['shop']!=1 && $admin['shop']!=$info['shop']){
            $this->error('只能查看本店铺的数据',$back);
        }
        //获取产品库存
        
        $where=[
            'goods'=>['eq',$info['goods']],
            'shop'=>['eq',$info['shop']],
        ];
        $goods=Db::name('store_goods')->where($where)->column('store,num,num1');
        //获取所有仓库
        $where=[
            'shop'=>$info['shop'],
            'status'=>2,
        ];
        $stores=Db::name('store')->where($where)->order('sort asc')->column('id,name');
        $stores[0]='总库存';
      
        $this->assign('stores',$stores);
        $this->assign('goods',$goods);
        $this->assign('info',$info);
        return $this->fetch();
    }
    
    /**
     * 出入库审核
     * @adminMenu(
     *     'name'   => '出入库审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '出入库审核',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        $back=url('index');
        $m=$this->m;
        $table=$this->table;
        $flag=$this->flag;
        $data=$this->request->param();
        
        $admin=$this->admin;
        $m->startTrans();
        $time=time();
        $ids='-';
        foreach ($data['safe'] as $k=>$v){
            if($v!==''){
                $ids.=$k;
                $update_info=[
                    'time'=>$time,
                    'safe'=>intval($v),
                ];
                $where=[
                    'id'=>$k,
                ];
                if($admin['shop']!=1 ){
                    $where['shop']=$admin['shop'];
                }
                $m->where($where)->update($update_info);
            }
            
        }
        if(empty($update_info)){
            $m->rollback();
            $this->error('未修改',$back);
        }
        //记录操作记录
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'调整了安全出入库'.$ids,
            'table'=>($this->table),
            'type'=>'edit',
            'pid'=>0,
            'link'=>'',
            'shop'=>$admin['shop'],
        ];
        Db::name('action')->insert($data_action);
        $m->commit();
        $this->success('已修改',$back);
    }
    
    
    
}