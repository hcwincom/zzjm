<?php
 
namespace app\order\controller;

 
use think\Db; 
use app\order\model\OrderModel;
class AdminOrderController extends OrderBaseController
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='订单';
        $this->table='order';
        $this->m=new OrderModel();
       
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
            
        ];
        $field='p.*,custom.name as custom_name';
        $list=$m
        ->alias('p')
        ->field('p.id') 
        ->where($where)
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
            $goods=Db::name('order_goods')->where('oid','in',$ids)
            ->column('id,oid,goods,goods_name,goods_code,goods_pic,goods_sn,price_real,num,pay');
            foreach($goods as $k=>$v){ 
                $list[$v['oid']]['infos'][]=$v;
            }
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
       
        $admin=$this->admin;
        $this->where_shop=($admin['shop']==1)?2:$admin['shop'];
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
        $data=$this->request->param();
       
        $fields_int=[
            'company','uid','store','freight','accept','paytype','pay_type','num',
        ];
        foreach($fields_int as $v){
            $data[$v]=intval($data[$v]);
            if(empty($data[$v])){
                $this->error('订单数据不完整'.$v);
            }
        }
        $fields_round=[
            'pay_freight','real_freight','tax_money','order_amount',
            'goods_money','other_money','discount_money','weight','size'
        ];
        foreach($fields_round as $v){
            $data[$v]=round($data[$v],2); 
        }
        if(empty($data['nums'])){
            $this->error('未选择产品');
        }
        //店铺和下单人
        $admin=$this->admin;
        $time=time();
        $data_order=[
            'order_type'=>1,
            'aid'=>$admin['id'],
            'shop'=>($admin['shop']==1)?2:$admin['shop'],
            'company'=>$data['company'],
            'uid'=>$data['uid'],
            'store'=>$data['store'],
            'freight'=>$data['freight'],
            'paytype'=>$data['paytype'],
            'pay_type'=>$data['pay_type'],
            'num'=>$data['num'],
            
            'pay_freight'=>$data['pay_freight'],
            'real_freight'=>$data['real_freight'],
            'tax_money'=>$data['tax_money'],
            'order_amount'=>$data['order_amount'],
            'other_money'=>$data['other_money'],
            'goods_money'=>$data['goods_money'],
            'discount_money'=>$data['discount_money'],
            'weight'=>$data['weight'],
            'size'=>$data['size'],
            
            'udsc'=>$data['udsc'],
            'dsc'=>$data['dsc'],
            'create_time'=>$time,
            'sort'=>11,
        ];
        //收货地址信息 
        $field='p.name,p.mobile,p.phone,p.street,p.postcode'.
            ',p.province,p.city,p.area'.
            ',province.name as province_name,city.name as city_name,area.name as area_name';
        $tel=Db::name('tel')
        ->alias('p')
        ->field($field)
        ->join('cmf_area province','province.type=1 and p.province=province.id','left')
        ->join('cmf_area city','city.type=2 and p.city=city.id','left')
        ->join('cmf_area area','area.type=3 and p.area=area.id','left')
        ->where('p.id',$data['accept'])
        ->find(); 
        $data_order['province']=$tel['province'];
        $data_order['city']=$tel['city'];
        $data_order['area']=$tel['area'];
        $data_order['address']=$tel['street'];
        $data_order['accept_name']=$tel['name'];
        $data_order['phone']=$tel['phone'];
        $data_order['mobile']=$tel['mobile'];
        $data_order['addressinfo']=$tel['province_name'].'-'.$tel['city_name'].'-'.$tel['area_name'];
        $data_order['postcode']=$tel['postcode'];
        
        //单号
        $company=Db::name('company')->field('id,code,name,shop')->find();
        if($company['shop']!=$data_order['shop']){
            $this->error('订单来源错误');
        } 
        $data_order['order_sn']=$company['code'].date('Ymd').substr($time,-8);
        $m=$this->m;
        $m_info=Db::name('order_goods');
        $m->startTrans();
        $oid= $m->insertGetId($data_order);
        //添加订单产品order_goods
        $nums=$data['nums'];
        $store=$data_order['store'];
        $goods=array_keys($nums);
        
        //获取所有产品信息
        $where=[
            'id'=>['in',$goods], 
        ];
        $goods_infos=Db::name('goods')->where($where)->column('id,name,code,pic,price_in,price_sale');
        //order_break($goods,$store,$city,$shop)
        $order_goods=[];
        //标记是否需要拆分订单
        $is_break=0;
        foreach($nums as $k=>$v){
            $v=intval($v);
            if($v<=0){
                $this->error('产品数量错误');
            }
            $order_goods[$k]=[
                'oid'=>$oid,
                'goods'=>$k,
                'num'=>$v, 
                'price_real'=>$data['prices'][$k],
                'pay'=>bcmul($data['prices'][$k],$v,2),
                'goods_sn'=>$data['sns'][$k],
                'goods_name'=>$goods_infos[$k]['name'],
                'goods_code'=>$goods_infos[$k]['code'],
                'goods_pic'=>$goods_infos[$k]['pic'],
                'price_in'=>$goods_infos[$k]['price_in'],
                'price_sale'=>$goods_infos[$k]['price_sale'],
                
            ];
            if($order_goods[$k]['pay'] != $data['price_counts'][$k]){
                $this->error('产品费用错误');
            } 
        }
        //检查是否拆分订单
        $i=0;
        $orders=$m->order_break($order_goods, $oid,$store,  $data_order['city'],  $data_order['shop']);
        if(count($orders)==1){
            $m_info->insertAll($order_goods);
        }else{
            //主单号标记
            $m->where('id',$oid)->update(['is_real'=>2]);
            //拆分订单要生成子单号
            foreach($orders as $k=>$v){
                $i++;
                
                $tmp_order=[
                    'fid'=>$oid,
                    'order_sn'=>$data_order['order_sn'].'-'.$i,
                    'store'=>$k, 
                    'create_time'=>$time,
                    'aid'=>$admin['id'],
                    'order_type'=>$data_order['order_type'], 
                    'shop'=>$data_order['shop'],
                    'company'=>$data_order['company'],
                    'uid'=>$data_order['uid'], 
                    'paytype'=>$data_order['paytype'],
                    'pay_type'=>$data_order['pay_type'], 
                    'province'=>$data_order['province'], 
                    'city'=>$data_order['city'], 
                    'area'=>$data_order['area'], 
                    'address'=>$data_order['address'], 
                    'addressinfo'=>$data_order['addressinfo'], 
                    'phone'=>$data_order['phone'], 
                    'mobile'=>$data_order['mobile'], 
                    'postcode'=>$data_order['postcode'], 
                    'udsc'=>$data_order['udsc'],
                    'dsc'=>$data_order['dsc'],
                   
                    'goods_money'=>0, 
                ];
                foreach($v as $kk=>$vv){
                    $tmp_order['goods_money']+=$vv['pay'];
                }
                $tmp_oid=$m->insertGetId($tmp_order);
                foreach($v as $kk=>$vv){
                    $v[$kk]['oid']=$tmp_oid;
                }
                $m_info->insertAll($v);
            }
        }
        //发票信息,要开发票，有抬头的保存
        if(!empty($data['invoice_title']) && !empty($data['invoice_type'])){
            $data_invoice=[
                'invoice_type'=>$data['invoice_type'],
                'oid'=>$oid,
                'oid_type'=>1,
                'aid'=>$data_order['aid'],
                'title'=>$data['invoice_title'],
                'ucode'=>$data['invoice_ucode'],
                'point'=>$data['invoice_point'],
                'tax_money'=>$data['invoice_tax_money'],
                'invoice_money'=>$data['invoice_invoice_money'],
                'dsc'=>$data['invoice_dsc'],
                'company'=>$company['id'],
                'company_name'=>$company['name'], 
                'atime'=>$time,
            ];
            Db::name('order_invoice')->insert($data_invoice);
        }
        //支付信息，有账户名的保存
        if(!empty($data['account_name1']) ){
            $data_pay=[
                'pay_type'=>1,
                'oid'=>$oid,
                'oid_type'=>1, 
                'bank1'=>$data['account_bank1'],
                'num1'=>$data['account_num1'],
                'name1'=>$data['account_name1'],
                'location1'=>$data['account_location1'],
                'bank2'=>$data['account_bank2'],
                'num2'=>$data['account_num2'],
                'name2'=>$data['account_name2'],
                'location2'=>$data['account_location2'],
                'money'=>$data['order_amount'], 
            ];
            Db::name('order_pay')->insert($data_pay);
        }
        $m->commit();
        $this->success('订单添加成功，等待审核');
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
        $admin=$this->admin;
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
        if($admin['shop']>1 && $admin['shop']!=$shop){
            $this->error('只能查看本店铺的数据');
        }
        $this->where_shop=$shop;
        //获取客户信息
        $custom=Db::name('custom')->where('id',$info['uid'])->find();
        if(empty($custom)){
            $accounts=null; 
         }else{
             //可选支付账号
             $where=[
                 'uid'=>$custom['id'],
                 'type'=>1,
             ];
             $accounts=Db::name('account')->where($where)->order('site asc')->column('id,site,bank1,name1,num1,location1,bank2,name2,num2,location2');
             
         }
        //支付信息 
        $where=[
            'oid'=>$id,
            'oid_type'=>1,
        ];
        $pay=Db::name('order_pay')->where($where)->find();
        //发票
        $where=[
            'oid'=>$id,
            'oid_type'=>1,
        ];
        $invoice=Db::name('order_invoice')->where($where)->find();
        
       
        //订单产品
        $where_goods=[];
        if($info['is_real']==1){
            $where_goods['oid']=['eq',$info['id']];
        }else{
            $orders=$m->where('fid',$info['id'])->column('id,order_sn,freight,store,num,weight,size,discount_money,goods_money,pay_freight,real_freight,other_money,order_amount');
            dump($orders);
            $order_ids=array_keys($orders);
            $where_goods['oid']=['in',$order_ids];
        }
       
        $goods=Db::name('order_goods') 
        ->where($where_goods)
        ->column('goods,goods_name,goods_code,goods_sn,goods_pic,price_in,price_sale,price_real,num,pay,weight,size');
       
        $goods_id=array_keys($goods); 
        //检查用户权限
        $authObj = new \cmf\lib\Auth();
        $name       = strtolower('goods/AdminGoodsauth/price_in_get');
        $is_auth=$authObj->check($admin['id'], $name);
        //判断产品重量体积单位,统一转化为kg,cm3
        foreach($goods as $k=>$v){
            if($is_auth==false){
                $goods[$k]['price_in']='--';
            } 
            $goods[$k]['weight1']=bcdiv($v['weight'],$v['num'],2);
            $goods[$k]['size1']=bcdiv($v['size'],$v['num'],2); 
        } 
         //获取产品图片
         $where=[
             'pid'=>['in',$goods_id],
             'type'=>['eq',1],
         ];
         $pics=Db::name('goods_file')->where($where)->column('id,pid,file');
         $path=cmf_get_image_url('');
         foreach($pics as $k=>$v){
             $goods[$v['pid']]['pics'][]=[
                 'file1'=>$v['file'].'1.jpg',
                 'file3'=>$v['file'].'3.jpg',
             ];
         }
        
        //获取所有库存
        $where=[
            'id'=>['in',$goods_id],
            'shop'=>['eq',$shop],
        ];
        $list=Db::name('store_goods')->where($where)->column('id,store,goods,num,num1');
        //循环得到数据 
        foreach($list as $k=>$v){
            $goods[$v['goods']]['nums'][$v['store']]=[
                'num'=>$v['num'],
                'num1'=>$v['num1'], 
            ]; 
        } 
         
        $this->cates();
        
        $this->assign('info',$info); 
        $this->assign('goods',$goods); 
        $this->assign('accounts',$accounts);
        $this->assign('custom',$custom);
        $this->assign('pay',$pay);
        
        $this->assign('invoice',$invoice);
       
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
        if($type==3){
            $field='id,name';
            $order='sort asc';
        }else{
            $field='id,name,shop';
            $order='shop asc,sort asc';
        }
       
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
        $paytypes=Db::name('paytype')->where($where)->order($order)->column($field.',type');
        //获取所有仓库
        $stores=Db::name('store')->where($where)->order($order)->column($field); 
        //获取所有物流方式
        $freights=Db::name('freight')->where($where)->order('shop asc,sort asc,store asc')->column('id,name,shop,store'); 
        //管理员
        $aids=Db::name('user')->where($where_admin)->column('id,user_nickname as name,shop');
        if($type==3){
            $stores_json=json_encode($stores);
            $this->assign('stores_json',$stores_json);
        }
        $this->assign('companys',$companys);
        $this->assign('paytypes',$paytypes); 
        $this->assign('aids',$aids); 
        $this->assign('stores',$stores); 
        $this->assign('freights',$freights); 
        $this->assign('goods_url',url('goods/AdminGoods/edit',false,false)); 
        $this->assign('image_url',cmf_get_image_url('')); 
       
        
    }
    
    
}
