<?php
 
namespace app\order\controller;

 
use think\Db; 
  
class AdminOrderController extends OrderBaseController
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='订单';
        $this->table='order';
        $this->m=Db::name('order');
       
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        
       
    }
    /**
     * 订单列表
     * @adminMenu(
     *     'name'   => '订单列表',
     *     'parent' => 'order/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '订单列表',
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
            if(empty($data['shop'])){
                $data['shop']=0;
            }else{
                $where['p.shop']=['eq',$data['shop']];
            }
        }else{
            $where['p.shop']=['eq',$admin['shop']];
            $this->where_shop=$admin['shop'];
            
        }
        
        //状态
        if(empty($data['status'])){
            $data['status']=0;
        }else{
            $where['p.status']=['eq',$data['status']];
        }
        //分类
        if(empty($data['order_type'])){
            $data['order_type']=0;
        }else{
            $where['p.order_type']=['eq',$data['order_type']];
        }
        
        //添加人
        if(empty($data['aid'])){
            $data['aid']=0;
        }else{
            $where['p.aid']=['eq',$data['aid']];
        }
        
        //所属公司
        if(empty($data['company'])){
            $data['company']=0;
        }else{
            $where['p.company']=['eq',$data['company']];
        }
        //付款方式
        if(empty($data['paytype'])){
            $data['paytype']=0;
        }else{
            $where['p.paytype']=['eq',$data['paytype']];
        }
        //付款类型
        if(empty($data['pay_type'])){
            $data['pay_type']=0;
        }else{
            $where['p.pay_type']=['eq',$data['pay_type']];
        }
       
        //省
        if(empty($data['province'])){
            $data['province']=0;
        }else{
            $where['p.province']=['eq',$data['province']];
        }
        //市
        if(empty($data['city'])){
            $data['city']=0;
        }else{
            $where['p.city']=['eq',$data['city']];
        }
        
        //类型
        if(empty($data['type'])){
            $data['type']=0;
        }else{
            $where['p.type']=['eq',$data['type']];
        }
        //查询字段
        $types=[
            'p.order_sn'=>'订单编号',
            'p.express_no'=>'物流编号',
            'custom.name'=>'客户名称',
            'custom.code'=>'客户编号',
            'p.order_sn'=>'淘宝id',
            'p.order_sn'=>'收货人',
            'p.order_sn'=>'订单编号',
            'p.order_sn'=>'订单编号',
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
        //客户类型
        if(empty($data['custom_cate'])){
            $data['custom_cate']=0;
        }else{
            $where['custom.cid']=['eq',$data['custom_cate']];
        }
        //关联表
        $join=[
            ['cmf_custom custom','p.uid=custom.id','left'],
            ['cmf_order p','p.id=p0.fid','left'],
        ];
        $field='p.*,custom.name as custom_name';
        $list=$m
        ->alias('p')
        ->field('p.id') 
        ->order('p.sort desc,p.id asc')
        ->paginate();
        // 获取分页显示
        $page = $list->appends($data)->render();
        $ids=[];
        foreach($list as $k=>$v){
            $ids[$v['id']]=$v['id'];
        }
        
        if(empty($ids)){
            $list=[];
        }else{
            //关联表
            $join=[ 
                ['cmf_custom custom','p.uid=custom.id','left'], 
            ];
            $field='p.*,custom.name as custom_name';
            
            $list=$m
            ->alias('p')
            ->join($join)
            ->where('p.id','in',$ids)
            ->order('p.sort desc,p.id asc')
            ->column($field);
        }
        
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
     * 订单添加
     * @adminMenu(
     *     'name'   => '订单添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        
        
        $this->cates();
        
        $this->assign('info',null);
      
        $this->assign('tels',null);
        $this->assign('accounts',null);
        $this->assign('custom',null);
        $this->assign('pay',null);
        $this->assign('invoice',null);
        
        return $this->fetch();  
        
    }
    /**
     * 订单添加do
     * @adminMenu(
     *     'name'   => '订单添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
       parent::add_do();
        
    }
    /**
     * 订单详情
     * @adminMenu(
     *     'name'   => '订单详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        
        $m=$this->m;
        $id=$this->request->param('id',0,'intval');
        $info=$m
        ->alias('p')
        ->field('p.*,a.user_nickname as aname')
        ->join('cmf_user a','a.id=p.aid','left') 
        ->where('p.id',$id)
        ->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        $shop=$info['shop'];
        $this->where_shop=$shop;
        //获取客户信息
        $custom=Db::name('custom')->where('id',$info['uid'])->find();
        //选择收货人
        $where=[
            'tel.type'=>1,
            'tel.uid'=>$info['uid'],
            'tel.status'=>1, 
        ];
        $tels=Db::name('tel')
        ->alias('tel')
        ->join('cmf_area province','province.id=tel.province','left')
        ->join('cmf_area city','city.id=tel.city','left')
        ->join('cmf_area area','area.id=tel.area','left')
        ->where($where)
        ->order('tel.sort asc')
        ->column('tel.site,concat(tel.name,",",tel.mobile,",",province.name,city.name,area.name,tel.street) as addressinfo,tel.province,tel.city,tel.area');
        //addressinfo王天飞，13807522063，海南省 三亚市 吉阳镇 南边海路292号 ，572000
        //可选支付账号
        $where=[
            'uid'=>$id,
            'type'=>1,
        ];
        $accounts=Db::name('account')->where($where)->order('site asc')->column('id,site,bank1,name1,num1,location1,bank2,name2,num2,location2');
        //支付信息
        if($info['pay']==0){
            $pay=null;
        }else{
            $pay=Db::name('order_pay')->where('id',$info['pay'])->find();
        }
        //订单产品
        $goods=Db::name('order_goods') 
        ->where('oid',$info['id'])
        ->column('goods,goods_name,goods_code,goods_sn,goods_pic,price_in,price_sell,price_real,num,weight,size');
        
       
        //当前库存
        $goods_id=array_keys($goods); 
        //获取所有库存
        $where=[
            'id'=>['in',$goods_id],
            'shop'=>['eq',$shop],
        ];
        $list=Db::name('store_goods')->where($where)->column('id,store,goods,num,num1');
        //循环得到数据
        $goods_num=[];
        foreach($list as $k=>$v){
            $goods_num[$v['goods']][$v['store']]=[
                'num'=>$v['num'],
                'num1'=>$v['num1'], 
            ];
            
        }
       
        $this->cates();
        
        $this->assign('info',$info);
        $this->assign('goods_num',$goods_num);
        $this->assign('tels',$tels);
        $this->assign('accounts',$accounts);
        $this->assign('custom',$custom);
        $this->assign('pay',$pay);
        return $this->fetch();  
    }
    //分类
    public function cates($type=3){
        $this->assign('invoice_types',config('invoice_type'));
        $this->assign('order_types',config('order_type'));
        $this->assign('statuss',config('order_status'));
        $this->assign('pay_status',config('pay_status'));
        //获取产品分类
        $where=[
            'fid'=>0,
            'status'=>2,
        ];
        $cates0=Db::name('cate')->where($where)->column('id,name');
        $where=[
            'fid'=>['gt',0],
            'status'=>['eq',2],
        ];
        $cates=Db::name('cate')->where($where)->column('id,fid,name');
        $this->assign('cates0',$cates0);
        $this->assign('cates',$cates);
        //客户类型
        $where=[ 
            'status'=>2,
        ];
        $custom_cates=Db::name('custom_cate')->where($where)->order('sort asc')->column('id,name');
        $this->assign('custom_cates',$custom_cates);
        //付款银行
        $where=[
            'status'=>2,
        ];
        $banks=Db::name('bank')->where($where)->order('sort asc')->column('id,name');
        $this->assign('banks',$banks);
        //店铺所属，管理员，公司，付款方式
        $where_shop=$this->where_shop;
        $where=['status'=>2];
        $where_admin=[
            'user_type'=>1,
            'user_status'=>1,
        ];
        $field='id,name,shop';
        $order='shop asc,sort asc';
        if(empty($where_shop)){
            $shops=Db::name('shop')->where($where)->order('sort asc')->column('id,name');
            $this->assign('shops',$shops); 
        }else{
            $where['shop']=$where_shop;
            $where_admin['shop']=$where_shop;
        }
        //公司
        $companys=Db::name('company')->where($where)->order($order)->column($field);
        //付款方式
        $paytypes=Db::name('paytype')->where($where)->order($order)->column($field);
        //获取所有仓库 
        $stores=Db::name('store')->where($where)->order($order)->column($field); 
        //获取所有物流方式
        $freights=Db::name('freight')->where($where)->order('shop asc,sort asc,store asc')->column('id,name,shop,store'); 
        //管理员
        $aids=Db::name('user')->where($where_admin)->column('id,user_nickname as name,shop');
        $this->assign('companys',$companys);
        $this->assign('paytypes',$paytypes); 
        $this->assign('aids',$aids); 
        $this->assign('stores',$stores); 
        $this->assign('freights',$freights); 
        
    }
    
}
