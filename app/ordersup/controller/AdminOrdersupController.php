<?php
 
namespace app\ordersup\controller;

 
use think\Db; 
use app\ordersup\model\OrdersupModel;
use app\common\controller\AdminInfo0Controller; 
class AdminOrdersupController extends AdminInfo0Controller
{
    
    public function _initialize()
    {
        parent::_initialize();
        //没有店铺区分
        $this->isshop=1;
        $this->flag='采购单';
        $this->table='ordersup';
        $this->m=new OrdersupModel();
       
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
         
    }
    /**
     * 采购单列表
     * @adminMenu(
     *     'name'   => '采购单列表',
     *     'parent' => 'ordersup/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '采购单列表',
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
            'p.name'=>'采购单编号',
            'p.express_no'=>'物流编号',
            'supplier.name'=>'客户名称',
            'supplier.code'=>'客户编号',
            
            
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
        if(empty($data['supplier_cate'])){
            $data['supplier_cate']=0;
        }else{
            $where['supplier.cid']=['eq',$data['supplier_cate']];
        }
        //关联表
        $join=[
            ['cmf_supplier supplier','p.uid=supplier.id','left'],
            
        ];
        $field='p.*,supplier.name as supplier_name';
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
                ['cmf_supplier supplier','p.uid=supplier.id','left'], 
            ];
            $field='p.*,supplier.name as supplier_name';
            
            $list=$m
            ->alias('p')
            ->join($join)
            ->where('p.id','in',$ids)
            ->order('p.sort desc,p.id asc')
            ->column($field); 
            $goods=Db::name('ordersup_goods')->where('oid','in',$ids)
            ->column('id,oid,goods,goods_name,goods_code,goods_pic,price_sale,price_real,num,pay');
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
     * 采购单添加
     * @adminMenu(
     *     'name'   => '采购单添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '采购单添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
       
        $admin=$this->admin;
        $this->where_shop=($admin['shop']==1)?2:$admin['shop'];
        $this->cates();
        $uid=$this->request->param('uid',0,'intval');
        if($uid==0){
            $supplier=null;
        }else{
            //获取客户信息
            $supplier=Db::name('supplier')->where('id',$uid)->find();
            
        }
        $this->assign('info',null);
      
        $this->assign('tels',null);
        $this->assign('accounts',null);
        $this->assign('supplier',$supplier);
        $this->assign('pay',null);
        $this->assign('invoice',null);
       
        return $this->fetch();  
        
    }
    /**
     * 采购单添加do
     * @adminMenu(
     *     'name'   => '采购单添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '采购单添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        $data=$this->request->param();
       
        $fields_int=[
           'uid','store','paytype','pay_type','goods_num',
        ];
        foreach($fields_int as $v){
            $data[$v]=intval($data[$v]);
            if(empty($data[$v])){
                $this->error('采购单数据不完整'.$v);
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
        $data_ordersup=[
            'order_type'=>1,
            'aid'=>$admin['id'],
            'shop'=>($admin['shop']==1)?2:$admin['shop'],
            'company'=>$data['company'],
            'uid'=>$data['uid'],
            'store'=>$data['store'],
            'freight'=>$data['freight'],
            'paytype'=>$data['paytype'],
            'pay_type'=>$data['pay_type'],
            'goods_num'=>$data['goods_num'],
            
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
            'sort'=>2,
        ];
 
        //收货地址信息 
        
        $data_ordersup['province']=$data['province'];
        $data_ordersup['city']=$data['city'];
        $data_ordersup['area']=$data['area'];
        $data_ordersup['address']=$data['address'];
        $data_ordersup['accept_name']=$data['accept_name'];
        $data_ordersup['phone']=$data['phone'];
        $data_ordersup['mobile']=$data['mobile'];
        
        $data_ordersup['postcode']=$data['postcode'];
        
        //单号 
        $data_ordersup['name']=date('Ymd').substr($time,-8);
        $m=$this->m;
        $m_info=Db::name('ordersup_goods');
        $m->startTrans();
        $oid= $m->insertGetId($data_ordersup);
        //添加采购单产品ordersup_goods
        $nums=$data['nums'];
        $store=$data_ordersup['store'];
        $goods=array_keys($nums);
        
        //获取所有产品信息
        $where=[
            'id'=>['in',$goods], 
        ];
        $goods_infos=Db::name('goods')->where($where)->column('id,name,name3,code,pic,price_in,price_sale,unit,weight1,size1');
        //添加客户用名
        $where=['uid'=>$data_ordersup['uid'],'goods'=>['in',$goods]];
        $ugoods=Db::name('supplier_goods')->where($where)->column('goods,name,cate');
        
        $ordersup_goods=[];
        //标记是否需要拆分采购单 
        foreach($nums as $k=>$v){
            $v=intval($v);
            if($v<=0){
                $this->error('产品数量错误');
            }
            $ordersup_goods[$k]=[
                'oid'=>$oid,
                'goods'=>$k,
                'num'=>$v, 
                'price_real'=>round($data['prices'][$k],2),
                'pay_discount'=>round($data['pay_discounts'][$k],2),
                'pay'=>round($data['price_counts'][$k],2), 
                
                'goods_name'=>$goods_infos[$k]['name'],
                'print_name'=>$goods_infos[$k]['name3'],
                'goods_uname'=>(isset($ugoods[$k]['name'])?$ugoods[$k]['name']:''),
                'goods_ucate'=>(isset($ugoods[$k]['cate'])?$ugoods[$k]['cate']:''),
                'goods_code'=>$goods_infos[$k]['code'],
                'goods_pic'=>$goods_infos[$k]['pic'],
                'price_in'=>$goods_infos[$k]['price_in'],
                'price_sale'=>$goods_infos[$k]['price_sale'],
                'dsc'=>$data['dscs'][$k],
                'weight'=>round($data['weights'][$k],2),
                'size'=>round($data['sizes'][$k],2),
                
            ]; 
            //计算产品费用
            $pay=round($ordersup_goods[$k]['price_real']*$ordersup_goods[$k]['num']-$ordersup_goods[$k]['pay_discount'],2);
            if($ordersup_goods[$k]['pay'] != $pay){
                $this->error('产品费用错误');
            } 
            
            
            //判断产品重量体积单位,统一转化为kg,cm3
            switch($goods_infos[$k]['unit']){
                case 1:
                    $order_goods[$k]['weight1']=bcdiv($goods_infos[$k]['weight1'],1000,2);
                    $order_goods[$k]['size1']=bcdiv($goods_infos[$k]['size1'],1000000000,2);
                    break;
                case 3:
                    $order_goods[$k]['weight1']=bcmul($goods_infos[$k]['weight1'],1000,2);
                    $order_goods[$k]['size1']=bcmul($goods_infos[$k]['size1'],1000000000,2);
                    break;
                default:
                    $order_goods[$k]['weight1']=$goods_infos[$k]['weight1'];
                    $order_goods[$k]['size1']=$goods_infos[$k]['size1'];
                    break;
            }
            $order_goods[$k]['weight1']=($order_goods[$k]['weight1']==0)?0.01:$order_goods[$k]['weight1'];
            $order_goods[$k]['size1']=($order_goods[$k]['size1']==0)?0.01:$order_goods[$k]['size1'];
        }
         
        $dsc='采购单添加成功';
        
        $m_info->insertAll($ordersup_goods);
        
        //发票信息,要开发票，有抬头的保存
        if(!empty($data['invoice_title']) && !empty($data['invoice_type'])){
            $data_invoice=[
                'invoice_type'=>$data['invoice_type'],
                'oid'=>$oid,
                
                'aid'=>$data_ordersup['aid'],
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
            Db::name('ordersup_invoice')->insert($data_invoice);
        }
        //支付信息，有账户名的保存
        if(!empty($data['account_name1']) ){
            $data_pay=[
                'pay_type'=>1,
                'oid'=>$oid, 
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
            Db::name('ordersup_pay')->insert($data_pay);
        }
        $m->commit();
        $this->success($dsc,url('edit',['id'=>$oid]));
    }
    /**
     * 采购单详情
     * @adminMenu(
     *     'name'   => '采购单详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '采购单详情',
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
        $supplier=Db::name('supplier')->where('id',$info['uid'])->find();
        if(empty($supplier)){
            $accounts=null; 
         }else{
             //可选支付账号
             $where=[
                 'uid'=>$supplier['id'],
                 'type'=>1,
             ];
             $accounts=Db::name('account')->where($where)->order('site asc')->column('id,site,bank1,name1,num1,location1,bank2,name2,num2,location2');
             
         }
        //支付信息 
        $where=[
            'oid'=>$id,
            'oid_type'=>1,
        ];
        $pay=Db::name('ordersup_pay')->where($where)->find();
        //发票
        $where=[
            'oid'=>$id,
            'oid_type'=>1,
        ];
        $invoice=Db::name('ordersup_invoice')->where($where)->find();
         
        //采购单产品
        $res=$m->ordersup_goods($info,$admin['id']);
         
        $this->cates();
        $this->assign('infos',$res['infos']);
        $this->assign('orders',$res['orders']);
        $this->assign('goods',$res['goods']);
        
        $this->assign('info',$info); 
        
        $this->assign('accounts',$accounts);
        $this->assign('supplier',$supplier);
        $this->assign('pay',$pay);
        
        $this->assign('invoice',$invoice);
        
        return $this->fetch();  
    }
    /**
     * 采购单编辑
     * @adminMenu(
     *     'name'   => '采购单编辑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '采购单编辑',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        $m=$this->m;
        $table=$this->table;
        $flag=$this->flag;
        $data=$this->request->param();
        $info=$m->where('id',$data['id'])->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        $time=time();
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1 && $info['shop']!=$admin['shop']){
           $this->error('不能编辑其他店铺的信息'); 
        }
         //是否有权查看
        $res=$m->ordersup_edit_auth($info,$admin);
        if($res!==1){
            $this->error($res); 
        }
        $update=[
            'pid'=>$info['id'],
            'aid'=>$admin['id'],
            'atime'=>$time,
            'table'=>$table,
            'url'=>url('edit_info','',false,false),
            'rstatus'=>1,
            'rid'=>0,
            'rtime'=>0,
            'shop'=>$admin['shop'],
        ];
        $update['adsc']=(empty($data['adsc']))?('修改了'.$flag.'信息'):$data['adsc'];
      
        if($info['status']==1){
            $m->ordersup_edit($info, $data,1);
            
            $this->success('已修改',url('edit',['id'=>$info['id']]));
        } 
        $content=$m->ordersup_edit($info, $data);
        if(!is_array($content)){
            $this->error($content);
        } 
        if(empty($content)){
            $this->error('未修改');
        }
        //保存更改
        $m_edit=Db::name('edit');
        $m_edit->startTrans();
        $eid=$m_edit->insertGetId($update);
        if($eid>0){
            $data_content=[
                'eid'=>$eid,
                'content'=>json_encode($content),
            ];
            Db::name('edit_info')->insert($data_content);
        }else{
            $m_edit->rollback();
            $this->error('保存数据错误，请重试');
        }
        //采购单排序
        $m->ordersup_sort($info['id']);
        //记录操作记录
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'编辑了'.($this->flag).$info['id'].'-单号'.$info['name'],
            'table'=>($this->table),
            'type'=>'edit',
            'pid'=>$info['id'],
            'link'=>url('edit_info',['id'=>$eid]),
            'shop'=>$admin['shop'],
        ];
        
        zz_action($data_action,['department'=>$admin['department']]);
        
        $m_edit->commit();
        $this->success('已提交修改');
    }
    
    /**
     * 采购单编辑列表
     * @adminMenu(
     *     'name'   => '采购单编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '采购单编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list()
    {
        parent::edit_list();
        return $this->fetch();
    }
    /**
     * 采购单编辑审核页面
     * @adminMenu(
     *     'name'   => ' 采购单编辑审核页面',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '采购单编辑审核页面',
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
        $info1=$m_edit
        ->alias('p')
        ->field('p.*,a.user_nickname as aname,r.user_nickname as rname')
        ->join('cmf_user a','a.id=p.aid','left')
        ->join('cmf_user r','r.id=p.rid','left')
        ->where('p.id',$id)
        ->find();
        
        if(empty($info1)){
            $this->error('编辑信息不存在');
        }
        //获取原信息
        $info=$m
        ->alias('p')
        ->field('p.*,a.user_nickname as aname,r.user_nickname as rname')
        ->join('cmf_user a','a.id=p.aid','left')
        ->join('cmf_user r','r.id=p.rid','left')
        ->where('p.id',$info1['pid'])
        ->find();
        if(empty($info)){
            $this->error('编辑关联的信息不存在');
        }
        //获取改变的信息
        $change=Db::name('edit_info')->where('eid',$id)->value('content');
        $change=json_decode($change,true);
          
        if($this->isshop){
            $this->where_shop=$info['shop'];
        }
        $id=$info['id'];
        $shop=$info['shop'];
        $admin=$this->admin;
        if($admin['shop']>1 && $admin['shop']!=$shop){
            $this->error('只能查看本店铺的数据');
        }
        $this->where_shop=$shop;
        //获取客户信息
        $supplier=Db::name('supplier')->where('id',$info['uid'])->find();
        if($info['fid']==0){  
            if(empty($supplier)){
                $accounts=null;
            }else{
                //可选支付账号
                $where=[
                    'uid'=>$supplier['id'],
                    'type'=>1,
                ];
                $accounts=Db::name('account')->where($where)->order('site asc')->column('id,site,bank1,name1,num1,location1,bank2,name2,num2,location2');
                
            }
            //支付信息
            $where=[
                'oid'=>$id,
                'oid_type'=>1,
            ];
            $pay=Db::name('ordersup_pay')->where($where)->find();
            //发票
            $where=[
                'oid'=>$id,
                'oid_type'=>1,
            ];
            $invoice=Db::name('ordersup_invoice')->where($where)->find();
         }else{
             $accounts=null;
             $pay=null;
             $invoice=null;
             
         }
        //采购单产品
         $res=$m->ordersup_goods($info,$admin['id']);
        $this->cates(); 
        $this->assign('infos',$res['infos']);
        $this->assign('orders',$res['orders']);
        $this->assign('goods',$res['goods']);
 
        $this->assign('info',$info);
        $this->assign('info1',$info1);
        $this->assign('change',$change);
       
        $this->assign('accounts',$accounts);
        $this->assign('supplier',$supplier);
        $this->assign('pay',$pay);
        
        $this->assign('invoice',$invoice);
      
        return $this->fetch();  
        
    }
    /**
     * 采购单编辑审核确认
     * @adminMenu(
     *     'name'   => ' 采购单编辑审核确认',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '采购单编辑审核确认',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        //审核编辑的信息
        $status=$this->request->param('rstatus',0,'intval');
        $id=$this->request->param('id',0,'intval');
        if(($status!=2 && $status!=3) || $id<=0){
            $this->error('信息错误');
        }
        $m=$this->m;
        $table=$this->table;
        $m_edit=Db::name('edit');
        $info=$m_edit
        ->field('e.*,a.user_nickname as aname')
        ->alias('e') 
        ->join('cmf_user a','a.id=e.aid')
        ->where('e.id',$id)
        ->find();
        if(empty($info)){
            $this->error('无效信息');
        }
        $ordersup=$m->where('id',$info['pid'])->find();
        
        if($info['rstatus']!=1){
            $this->error('编辑信息已被审核！不能重复审核');
        }
        
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1 && $info['shop']!=$admin['shop']){
            $this->error('不能审核其他店铺的信息');
        }
        
        $time=time();
        
        $m->startTrans();
        
        $update=[
            'rid'=>$admin['id'],
            'rtime'=>$time,
            'rstatus'=>$status,
        ];
        $review_status=$this->review_status;
        $update['rdsc']=$this->request->param('rdsc','');
        if(empty($update['rdsc'])){
            $update['rdsc']=$review_status[$status];
        }
        
        //只有未审核的才能更新
        $where=[
            'id'=>$id,
            'rstatus'=>1,
        ];
        $row=$m_edit->where($where)->update($update);
        if($row!==1){
            $m->rollback();
            $this->error('审核失败，请刷新后重试');
        }
        //是否更新,2同意，3不同意
        if($status==2){
            
            //得到修改的字段
            $change=Db::name('edit_info')->where('eid',$id)->value('content');
            $change=json_decode($change,true);
            $row=$m->ordersup_edit_review($ordersup, $change);
           
            if($row!==1){
                $m->rollback();
                $this->error($row);
            }
            //排序
            $m->ordersup_sort($ordersup['id']);
            //判断是否需要出库
            if(isset($change['status'])){
                switch ($change['status']){
                    case 10:
                    case 20:
                        //确认采购单后添加出库记录
                        if($ordersup['status']<10){
                            $row=$m->ordersup_storein0($ordersup['id']);
                        }
                        break;
                    case 22:
                        //供货商发货，检测单号有无填写
                        if(empty($ordersup['express_no']) && empty($change['edit'][$ordersup['id']]['express_no'])){
                            $row='快递单号未填写';
                        } 
                        break;
                    case 24:
                        //准备收货后更新出库未待审核
                        if($ordersup['status']==22){
                            $row=$m->ordersup_storein1($ordersup['id']);
                        } 
                        break;
                    case 26: 
                        //仓库收货要检查出库记录是否都已审核
                        if($ordersup['status']==24){
                            $row=$m->ordersup_storein_check($ordersup['id']);
                        } 
                        break;
                        
                }
                if($row!==1){
                    $m->rollback();
                    $this->error($row);
                }
            }
            
        }
        
        //审核成功，记录操作记录,发送审核信息
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'审核'.$info['aid'].'-'.$info['aname'].'对'.($this->flag).$info['pid'].'-'.$ordersup['name'].'的编辑为'.$review_status[$status],
            'table'=>$table,
            'type'=>'edit_review',
            'pid'=>$info['pid'],
            'link'=>url('edit_info',['id'=>$info['id']]),
            'shop'=>$admin['shop'],
        ];
        
        zz_action($data_action,['aid'=>$info['aid']]);
        
        $m->commit();
        $this->success('审核成功');
    }
    
    //分类
    public function cates($type=3){
        $this->assign('invoice_types',config('invoice_type'));
       
        $this->assign('statuss',config('ordersup_status'));
        $this->assign('pay_status',config('pay_status'));
        
        $this->assign('pay_types',config('pay_type'));
        $this->assign('order_types',config('ordersup_type'));
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
        $supplier_cates=Db::name('supplier_cate')->where($where)->order('sort asc')->column('id,name');
        $this->assign('supplier_cates',$supplier_cates);
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
            $ordersup='sort asc';
        }else{
            $field='id,name,shop';
            $ordersup='shop asc,sort asc';
        }
       
        if(empty($where_shop)){
            $shops=Db::name('shop')->where($where)->order('sort asc')->column('id,name');
            $this->assign('shops',$shops);  
        }else{
            $where['shop']=$where_shop;
            $where_admin['shop']=$where_shop;
        }
        
       
        //公司
        $companys=Db::name('company')->where($where)->order($ordersup)->column($field);
        //付款方式
        $paytypes=Db::name('paytype')->where($where)->order($ordersup)->column($field);
        //获取所有仓库
        $stores=Db::name('store')->where($where)->order($ordersup)->column($field); 
        //获取所有物流方式
        $freights=Db::name('freight')->where($where)->order('shop asc,sort asc,store asc')->column('id,name,shop,store'); 
        //管理员
        $aids=Db::name('user')->where($where_admin)->column('id,user_nickname as name,shop');
        if($type==3){ 
            $stores_tr='<thead><tr>';
            foreach($stores as $k=>$v){
                $stores_tr.='<th>'.$v.'</th>';
            } 
            $stores_tr.='</tr></thead>'; 
            $this->assign('stores_tr',$stores_tr);
            $this->assign('stores_json',json_encode($stores)); 
        }
        $this->assign('companys',$companys);
        $this->assign('paytypes',$paytypes); 
        $this->assign('aids',$aids); 
        $this->assign('rids',$aids); 
        $this->assign('stores',$stores); 
        $this->assign('freights',$freights); 
        $this->assign('goods_url',url('goods/AdminGoods/edit',false,false)); 
        $this->assign('image_url',cmf_get_image_url('')); 
        $this->assign('order_url',url('ordersup/AdminOrdersup/edit',false,false)); 
        $this->assign('order_user_url',url('custom/AdminSupplier/edit',false,false)); 
        $this->assign('edit_url',url('edit_list',['type1'=>'id','type2'=>1],false)); 
       
        
    }
    /**
     * 采购单确认
     * @adminMenu(
     *     'name'   => '采购单确认',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '采购单确认',
     *     'param'  => ''
     * )
     */ 
    public function status_do2(){
        $flag='确认采购单';
        $data=$this->request->param();
        $this->status_do($data,2,$flag);
        
    }
    /**
     * 采购单手动转为待收货
     * @adminMenu(
     *     'name'   => '采购单手动转为待收货',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '采购单手动转为待收货',
     *     'param'  => ''
     * )
     */ 
    public function status_do10(){
        
        $flag='将采购单手动转为待收货';
        $data=$this->request->param();
        $this->status_do($data,10,$flag);
        
    }
    /**
     * 采购单已发货
     * @adminMenu(
     *     'name'   => '采购单已发货',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '采购单已发货',
     *     'param'  => ''
     * )
     */
    public function status_do20(){
        
        $flag='已发货';
        $data=$this->request->param();
        $this->status_do($data,20,$flag);
        
    }
    /**
     * 采购单准备收货
     * @adminMenu(
     *     'name'   => '采购单准备收货',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '采购单准备收货',
     *     'param'  => ''
     * )
     */
    public function status_do22(){
        
        $flag='准备收货';
        $data=$this->request->param();
        $this->status_do($data,22,$flag);
        
    }
    /**
     * 采购单确认收货
     * @adminMenu(
     *     'name'   => '采购单确认收货',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '采购单确认收货',
     *     'param'  => ''
     * )
     */
    public function status_do24(){
        
        $flag='采购单确认收货';
        $data=$this->request->param();
        $this->status_do($data,24,$flag);
        
    }
    /**
     * 采购单退货
     * @adminMenu(
     *     'name'   => '采购单退货',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '采购单退货',
     *     'param'  => ''
     * )
     */
    public function status_do26(){
        
        $flag='采购单退货';
        $data=$this->request->param();
        $this->status_do($data,26,$flag);
        
    }
    /**
     * 采购单退货
     * @adminMenu(
     *     'name'   => '采购单退货',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '采购单退货',
     *     'param'  => ''
     * )
     */
    public function status_do30(){
        
        $flag='采购单退货';
        $data=$this->request->param();
        $this->status_do($data,30,$flag);
        
    }
    /**
     * 采购单退货完成
     * @adminMenu(
     *     'name'   => '采购单退货完成',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '采购单退货完成',
     *     'param'  => ''
     * )
     */
    public function status_do40(){
        
        $flag='采购单退货完成';
        $data=$this->request->param();
        $this->status_do($data,40,$flag);
        
    }
    /**
     * 超管直接修改采购单状态
     * @adminMenu(
     *     'name'   => '超管直接修改采购单状态',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '超管直接修改采购单状态',
     *     'param'  => ''
     * )
     */
    public function status_do0(){
        
        $flag='超管直接修改采购单状态';
        $data=$this->request->param();
        $this->status_do($data,0,$flag);
        
    }
    /* 改变采购单状态 */
    public function status_do($data,$status,$flag){
        
        
        $m=$this->m;
        $table=$this->table;
        
        $id=intval($data['id']);
        
        $info=$m->where('id',$id)->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        $time=time();
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1 && $info['shop']!=$admin['shop']){
            $this->error('不能编辑其他店铺的信息');
        }
        //是否有权查看
        $res=$m->ordersup_edit_auth($info,$admin);
        if($res!==1){
            $this->error($res);
        }
        $update=[
            'pid'=>$info['id'],
            'aid'=>$admin['id'],
            'atime'=>$time,
            'table'=>$table,
            'url'=>url('edit_info','',false,false),
            'rstatus'=>1,
            'rid'=>0,
            'rtime'=>0,
            'shop'=>$admin['shop'],
        ];
        $update['adsc']=(empty($adsc))?$flag:$data['adsc'];
        
        if($status>0 && $info['status']!=$status){
            $this->error('状态信息错误');
        }
        $content=$m->ordersup_edit($info, $data);
        if(!is_array($content)){
            $this->error($content);
        }
        switch ($status){
            case 2:
                //判断是先付款后收货还是先收货
                $pay_type=isset($content['pay_type'])?$content['pay_type']:$info['pay_type'];
                if($pay_type==1){
                    $content['status']=10;
                }else{
                    $content['status']=20;
                }
                break;
            case 10:
                //手动待收货
                $content['status']=20;
                break;
            case 20:
                //供货商发货
                $content['status']=22;
                
                break;
            case 22:
                //准备收货
                $content['status']=24;
                
                break;
            case 24:
                // 点击“确认收货”，采购单状态为已收货，若已支付，则采购单状态为已完成。
                $content['status']=($info['pay_status']==3)?30:26;
                break;
            case 26:
                //退货
                $content['status']=40;
                break;
            case 30:
                //退货
                $content['status']=40;
                break;
            case 40:
                //退货完成
                $content['status']=42;
                break; 
            case 0:
                //超管编辑
                $content['status']=intval($data['status']);
                break;
            default:
                $this->error('操作错误');
        }
          
        
        //保存更改
        $m_edit=Db::name('edit');
        $m_edit->startTrans();
        $eid=$m_edit->insertGetId($update);
        if($eid>0){
            $data_content=[
                'eid'=>$eid,
                'content'=>json_encode($content),
            ];
            Db::name('edit_info')->insert($data_content);
        }else{
            $m_edit->rollback();
            $this->error('保存数据错误，请重试');
        }
        
        //记录操作记录
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].$flag.$info['id'].'-单号'.$info['name'],
            'table'=>($this->table),
            'type'=>'edit',
            'pid'=>$info['id'],
            'link'=>url('edit_info',['id'=>$eid]),
            'shop'=>$admin['shop'],
        ];
        
        zz_action($data_action,['department'=>$admin['department']]);
        
        $m_edit->commit();
        $this->success('已提交修改');
    }
    /**
     * 超管直接修改采购单支付状态
     * @adminMenu(
     *     'name'   => '超管直接修改采购单支付状态',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '超管直接修改采购单支付状态',
     *     'param'  => ''
     * )
     */
    public function pay_do0(){
        
        $flag='超管直接修改采购单支付状态';
        $data=$this->request->param();
        $this->pay_do($data,0,$flag);
        
    }
    /**
     * 用户付款采购单
     * @adminMenu(
     *     'name'   => '用户付款采购单',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '用户付款采购单',
     *     'param'  => ''
     * )
     */
    public function pay_do1(){
        
        $flag='用户付款采购单';
        $data=$this->request->param();
        $this->pay_do($data,1,$flag);
        
    }
    /**
     * 财务确认采购单付款
     * @adminMenu(
     *     'name'   => '财务确认采购单付款',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '财务确认采购单付款',
     *     'param'  => ''
     * )
     */
    public function pay_do2(){
        
        $flag='财务确认采购单付款';
        $data=$this->request->param();
        $this->pay_do($data,2,$flag);
        
    }
    /**
     * 采购单退款
     * @adminMenu(
     *     'name'   => '采购单退款',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '采购单退款',
     *     'param'  => ''
     * )
     */
    public function pay_do3(){
        
        $flag='采购单退款';
        $data=$this->request->param();
        $this->pay_do($data,3,$flag);
        
    }
    /**
     * 采购单退款完成
     * @adminMenu(
     *     'name'   => ' 采购单退款完成',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => ' 采购单退款完成',
     *     'param'  => ''
     * )
     */
    public function pay_do4(){
        
        $flag=' 采购单退款完成';
        $data=$this->request->param();
        $this->pay_do($data,4,$flag);
        
    }
    /* 改变采购单支付状态 */
    public function pay_do($data,$pay_status,$flag){
        
        
        $m=$this->m;
        $table=$this->table;
        
        $id=intval($data['id']);
        
        $info=$m->where('id',$id)->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
       
        $time=time();
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1 && $info['shop']!=$admin['shop']){
            $this->error('不能编辑其他店铺的信息');
        }
        //是否有权查看
        $res=$m->ordersup_edit_auth($info,$admin);
        if($res!==1){
            $this->error($res);
        }
        $update=[
            'pid'=>$info['id'],
            'aid'=>$admin['id'],
            'atime'=>$time,
            'table'=>$table,
            'url'=>url('edit_info','',false,false),
            'rstatus'=>1,
            'rid'=>0,
            'rtime'=>0,
            'shop'=>$admin['shop'],
        ];
        $update['adsc']=(empty($adsc))?$flag:$data['adsc'];
        
        if($info['pay_status']!=$pay_status){
            $this->error('状态信息错误');
        }
        $content=$m->ordersup_edit($info, $data);
        if(!is_array($content)){
            $this->error($content);
        }
        switch ($pay_status){
            case 1:
                //用户付款提交
                $content['pay_status']=2; 
                break;
            case 2:
                //财务确认付款,未收货的可以收货了
                $content['pay_status']=3;
                if($info['status']<20){
                    $content['status']=20;
                }
                break;
            case 3:
                //发起退款
                $content['pay_status']=4;
                break;
            case 4:
                //退款完成
                $content['pay_status']=5;
                break; 
            case 0:
                //超管编辑
                $content['pay_status']=intval($data['pay_status']);
                break;
            default:
                $this->error('操作错误');
        }
        
        
        //保存更改
        $m_edit=Db::name('edit');
        $m_edit->startTrans();
        $eid=$m_edit->insertGetId($update);
        if($eid>0){
            $data_content=[
                'eid'=>$eid,
                'content'=>json_encode($content),
            ];
            Db::name('edit_info')->insert($data_content);
        }else{
            $m_edit->rollback();
            $this->error('保存数据错误，请重试');
        }
        
        //记录操作记录
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].$flag.$info['id'].'-单号'.$info['name'],
            'table'=>($this->table),
            'type'=>'edit',
            'pid'=>$info['id'],
            'link'=>url('edit_info',['id'=>$eid]),
            'shop'=>$admin['shop'],
        ];
        
        zz_action($data_action,['department'=>$admin['department']]);
        
        $m_edit->commit();
        $this->success('已提交修改');
    }
    /**
     * 快递查询
     * @adminMenu(
     *     'name'   => ' 快递查询',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '快递查询',
     *     'param'  => ''
     * )
     */
    public function express_query(){
        $id=$this->request->param('id');
        
    }
}
