<?php
 
namespace app\order\controller;

 
use think\Db; 
use app\admin\model\UserModel;
 
use cmf\controller\AdminBaseController; 
use app\money\model\OrdersInvoiceModel;
use app\money\model\OrdersPayModel;
use barcode\Barcode;
use app\msg\model\MsgModel;
use app\order\model\OrderModel;
use app\ordersup\model\OrdersupModel;
class OrderBaseController extends AdminBaseController
{
    protected $m;
    protected $statuss;
    protected $review_status;
    protected $table;
    protected $fields;
    protected $flag;
 
    //用于详情页中识别当前店铺,
    //列表页中分店铺查询
    protected $where_shop;
   
    protected $uflag;
    protected $utable;
    protected $ogtable;
    protected $search; 
    protected $utype; 
    protected $oid_type; 
    protected $ptype; 
    public function _initialize()
    {
        parent::_initialize();
       
        
        $this->review_status=config('review_status');
        $this->assign('review_status',$this->review_status); 
        //is_back
//         0无售后，1需要售后，2有售后，3售后结束
    }
    //订单列表
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
        $res=zz_shop($admin, $data, $where,'p.shop');
        $data=$res['data'];
        $where=$res['where'];
        $this->where_shop=$res['where_shop'];
        
        //状态
        if(empty($data['status'])){
            $data['status']=0;
        }else{
            $where['p.status']=['eq',$data['status']];
        }
        //订单类型
        if(empty($data['order_type'])){
            $data['order_type']=0;
        }else{
            $where['p.order_type']=['eq',$data['order_type']];
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
        $types=$this->search; 
        //搜索类型
        $search_types=config('search_types'); 
        $res=zz_search_param($types, $search_types,$data, $where);
        $data=$res['data'];
        $where=$res['where'];
        
        //时间类别
        $times=config('order_time');
        $res=zz_search_time($times, $data, $where);
        $data=$res['data'];
        $where=$res['where'];
        //客户类型
        if(empty($data['cid'])){
            $data['cid']=0;
        }else{
            $where['custom.cid']=['eq',$data['cid']];
        }
        $utable=$this->utable;
        //关联表
        $join=[
            ['cmf_'.$utable.' custom','p.uid=custom.id','left'],
            
        ];
        $field='p.*,custom.name as custom_name';
       
       
        $list0=$m
        ->alias('p')
        ->field('p.id')
        ->join($join)
        ->where($where)
        ->order('p.sort desc,p.time desc')
        ->paginate();
        
        // 获取分页显示
        $page = $list0->appends($data)->render();
       
        $ids=[];
        foreach($list0 as $k=>$v){
            $ids[$v['id']]=$v['id'];
            
        }
        $list=$m
        ->alias('p') 
        ->join($join)
        ->where('p.id','in',$ids)
        ->order('p.sort desc,p.id asc')
        ->column($field);
     
        if(!empty($list)){
            $ogtable=$this->ogtable;
            $goods=Db::name($ogtable)->where('oid','in',$ids)
            ->column('id,oid,goods,goods_name,goods_code,goods_pic,price_sale,price_real,num,pay');
            foreach($goods as $k=>$v){
                $list[$v['oid']]['infos'][]=$v;
            }
        }
        
        //公司
        $where=[
            'status'=>2, 
        ];
        if(empty($data['shop'])){
            $where['shop']=($admin['shop']==1)?2:$admin['shop'];
        }else{
            $where['shop']=$data['shop'];
        }
        $companys=Db::name('company')->where($where)->order('shop asc,sort asc')->column('id,name');
        $this->assign('companys',$companys);
        
        $this->assign('page',$page);
        $this->assign('list',$list);
        
        $this->assign('data',$data);
        $this->assign('types',$types);
        $this->assign('times',$times);
        $this->assign("search_types", $search_types);
        
        $this->cates(1);
        
        return $this->fetch();
    }
    //我的订单列表
    public function myorder()
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
        $res=zz_shop($admin, $data, $where,'p.shop');
        $data=$res['data'];
        $where=$res['where'];
        $this->where_shop=$res['where_shop'];
        
        //状态
        if(empty($data['status'])){
            $data['status']=0;
        }else{
            $where['p.status']=['eq',$data['status']];
        }
        //订单类型
        if(empty($data['order_type'])){
            $data['order_type']=0;
        }else{
            $where['p.order_type']=['eq',$data['order_type']];
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
        $types=$this->search;
        //搜索类型
        $search_types=config('search_types');
        $res=zz_search_param($types, $search_types,$data, $where);
        $data=$res['data'];
        $where=$res['where'];
        
        //时间类别
        $times=config('order_time');
        $res=zz_search_time($times, $data, $where);
        $data=$res['data'];
        $where=$res['where'];
        //客户类型
        if(empty($data['cid'])){
            $data['cid']=0;
        }else{
            $where['custom.cid']=['eq',$data['cid']];
        }
        $utable=$this->utable;
        //关联表
        $join=[
            ['cmf_'.$utable.' custom','p.uid=custom.id','left'],
            ['cmf_'.$table.'_aid oaid','p.id=oaid.pid and oaid.aid='.$admin['id']],
            
        ];
        $where['oaid.aid']=$admin['id'];
        $field='p.*,custom.name as custom_name';
         
        $list0=$m
        ->alias('p')
        ->field('p.id')
        ->join($join)
        ->where($where)
        ->order('p.sort desc,p.time desc')
        ->paginate();
        
        // 获取分页显示
        $page = $list0->appends($data)->render();
        
        $ids=[];
        foreach($list0 as $k=>$v){
            $ids[$v['id']]=$v['id'];
            
        }
        $list=$m
        ->alias('p')
        ->join($join)
        ->where('p.id','in',$ids)
        ->order('p.sort desc,p.id asc')
        ->column($field);
        
        if(!empty($list)){
            $ogtable=$this->ogtable;
            $goods=Db::name($ogtable)->where('oid','in',$ids)
            ->column('id,oid,goods,goods_name,goods_code,goods_pic,price_sale,price_real,num,pay');
            foreach($goods as $k=>$v){
                $list[$v['oid']]['infos'][]=$v;
            }
        }
        
        //公司
        $where=[
            'status'=>2,
        ];
        if(empty($data['shop'])){
            $where['shop']=($admin['shop']==1)?2:$admin['shop'];
        }else{
            $where['shop']=$data['shop'];
        }
        $companys=Db::name('company')->where($where)->order('shop asc,sort asc')->column('id,name');
        $this->assign('companys',$companys);
        
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
     */
    public function add()
    {
       
        $admin=$this->admin;
        $this->where_shop=($admin['shop']==1)?2:$admin['shop'];
        $this->cates();
        $uid=$this->request->param('uid',0,'intval');
        if($uid==0){
            $custom=null;
        }else{
            //获取客户信息
            $utable=$this->utable;
            $custom=Db::name($utable)->where('id',$uid)->find();
            
        }
       
        //公司
        $where=[
            'shop'=>($admin['shop']==1)?2:$admin['shop'],
            'type'=>1,
            'status'=>2,
        ];
        $companys=Db::name('company')->where($where)->order('sort asc')->column('id,name');
        $this->assign('companys',$companys);
        
        $this->assign('info',null);
      
        $this->assign('tels',null);
        $this->assign('accounts',null);
        $this->assign('custom',$custom);
        $this->assign('pay',null);
        $this->assign('invoice',null);
        $this->assign('ok_break',2); 
        $this->assign('ok_add',1); 
        $utype=$this->utype;
        if($utype==1){
            $this->assign('ok_freight',1); 
        }else{
            $this->assign('ok_freight',2); 
        }
        return $this->fetch();  
        
    }
    /**
     * 订单添加do 
     */
    public function add_do()
    {
        $m=$this->m;
        $ogtable=$this->ogtable;
        $table=$this->table;
        $flag=$this->flag;
        $oid_type=$this->oid_type;
        $ptype=$this->ptype;
        
        $data=$this->request->param();
       /*?不知道为什么添加页面单选按钮无法提交值*/
        if(empty($data['ok_break'])){
            $data['ok_break']=2;
        }
        $fields_int=[
            'company','uid','store','freight','accept','paytype','pay_type','goods_num','ok_break'
        ];
        foreach($fields_int as $v){
            $data[$v]=intval($data[$v]);
            if(empty($data[$v])){
                $this->error($flag.'数据不完整'.$v);
            }
        }
        $fields_round=[
            'pay_freight','real_freight','order_amount',
            'goods_money','other_money','discount_money','weight','size',
            'weight_box','weight_real',
        ];
        foreach($fields_round as $v){
            $data[$v]=round($data[$v],2); 
        }
        if(empty($data['nums-0'])){
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
            'goods_num'=>$data['goods_num'],
            
            'pay_freight'=>$data['pay_freight'],
            'real_freight'=>$data['real_freight'], 
            'order_amount'=>$data['order_amount'],
            'other_money'=>$data['other_money'],
            'goods_money'=>$data['goods_money'],
            'discount_money'=>$data['discount_money'],
            'weight'=>$data['weight'],
            'size'=>$data['size'],
            'box_out'=>$data['box_out'],
            'weight_box'=>$data['weight_box'],
            'weight_real'=>$data['weight_real'],
            'udsc'=>$data['udsc'],
            'dsc'=>$data['dsc'],
            'express_no'=>$data['express_no'],
            'create_time'=>$time,
            'time'=>$time,
            'sort'=>5,
            'ok_break'=>$data['ok_break'],
        ];
        $utable=$this->utable;
        $custom=Db::name($utable)->where('id',$data_order['uid'])->find();
        $data_order['uname']=$custom['name'];
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
        $data_order['accept_name']=empty($tel['name'])?:$tel['name'];
        $data_order['phone']=$tel['phone'];
        $data_order['mobile']=$tel['mobile'];
        $data_order['addressinfo']=$tel['province_name'].'-'.$tel['city_name'].'-'.$tel['area_name'];
        $data_order['postcode']=$tel['postcode'];
        
        //公司信息
        $company=Db::name('company')->field('id,name,code,shop')->where('id',$data_order['company'])->find();
        if($company['shop']!=$data_order['shop']){
            $this->error($flag.'所属公司错误');
        } 
        //单号
        $data_order['name']=order_sn($admin['id'],$company['code']);
      
        $m_info=Db::name($ogtable);
        $m->startTrans();
        $oid= $m->insertGetId($data_order);
        //添加订单产品order_goods
        $nums=$data['nums-0'];
        $store=$data_order['store'];
        $goods=array_keys($nums);
        
        //获取所有产品信息
        $where=[
            'id'=>['in',$goods], 
        ];
        $goods_infos=Db::name('goods')->where($where)->column('id,name,name3,code,pic,price_in,price_sale,type,weight1,size1');
         
        $order_goods=[];
        //标记是否需要拆分订单 
        foreach($nums as $k=>$v){
            $v=intval($v);
            if($v<=0){
                $this->error('产品数量错误');
            }
            $order_goods[$k]=[
                'oid'=>$oid,
                'goods'=>$k,
                'num'=>intval($v), 
                'price_real'=>round($data['price_reals-0'][$k],2),
                'pay_discount'=>round($data['pay_discounts-0'][$k],2), 
                'pay'=>round($data['pays-0'][$k],2), 
                'dsc'=>$data['dscs-0'][$k],
                'weight'=>round($data['weights-0'][$k],2),
                'size'=>round($data['sizes-0'][$k],2),  
                'goods_uname'=>$data['goods_unames-0'][$k],
                'goods_ucate'=>$data['goods_ucates-0'][$k], 
                'goods_name'=>$goods_infos[$k]['name'],
                'print_name'=>$goods_infos[$k]['name3'], 
                'goods_code'=>$goods_infos[$k]['code'],
                'goods_pic'=>$goods_infos[$k]['pic'],
                'price_in'=>$goods_infos[$k]['price_in'],
                'price_sale'=>$goods_infos[$k]['price_sale'], 
            ]; 
            //计算产品费用
            $pay=round($order_goods[$k]['price_real']*$order_goods[$k]['num']-$order_goods[$k]['pay_discount'],2);
            if($order_goods[$k]['pay'] != $pay){
                $this->error('产品费用错误');
            } 
          
            //判断产品重量体积单位,统一转化为kg,cm3
            $tmp_goods=$m->unit_change($goods_infos[$k]); 
            $order_goods[$k]['weight1']=$tmp_goods['weight1'];
            $order_goods[$k]['size1']=$tmp_goods['size1'];
           
        }
        //检查是否拆分订单
        if($data_order['ok_break']==1){
            $orders=$m->order_break($order_goods, $oid,$store,  $data_order['city'],  $data_order['shop']);
        }else{
            $orders=[1];
        } 
        if(count($orders)==1){
            $dsc=$flag.'添加成功';
            
            $m_info->insertAll($order_goods);
        }else{
            $dsc=$flag.'已拆分';
            $i=0;
            //主单号标记
            $m->where('id',$oid)->update(['is_real'=>2]);
            //拆分订单要生成子单号
            foreach($orders as $k=>$v){
                $i++;
                
                $tmp_order=[
                    'fid'=>$oid,
                    'name'=>$data_order['name'].'_'.$i,
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
                    'sort'=>$data_order['sort'],
                    'ok_break'=>$data_order['ok_break'],
                    'goods_money'=>0, 
                    'goods_num'=>0, 
                    'weight'=>0, 
                    'size'=>0, 
                   
                ];
                foreach($v as $kk=>$vv){
                    $tmp_order['goods_money']+=$vv['pay']; 
                    $tmp_order['goods_num']+=$vv['num'];
                    $tmp_order['weight']+=$vv['weight'];
                    $tmp_order['size']+=$vv['size'];
                }
                $tmp_order['order_amount']= $tmp_order['goods_money'];
                $tmp_order['invoice0_money']= $tmp_order['goods_money'];
                $tmp_oid=$m->insertGetId($tmp_order);
                foreach($v as $kk=>$vv){
                    $v[$kk]['oid']=$tmp_oid;
                }
                $m_info->insertAll($v);
            }
        }
        $update=[];
        //发票信息,要开发票，有税费金额的保存
        if(!empty($data['invoice_tax_money'])){
            $data_invoice=[
                'name'=>'fp'.$data_order['name'], 
                'oid'=>$oid,
                'oid_type'=>$oid_type,
                'ptype'=>$ptype,
                'status'=>1,
                'uid'=>$data_order['uid'],
                'aid'=>$data_order['aid'],
                'atime'=>$time,
                'invoice_type'=>$data['invoice_type'], 
                'uname'=>$data['invoice_uname'],
                'ucode'=>$data['invoice_ucode'],
                'point'=>$data['invoice_point'],
                'tax_money'=>$data['invoice_tax_money'],
                'invoice_money'=>$data['invoice_invoice_money'],
                'dsc'=>$data['invoice_dsc'],
                'company'=>$company['id'], 
                'paytype'=>$data_order['paytype'], 
                
                'address'=>$data['invoice_address'],  
                'bank_location'=>$data['invoice_bank_location'],
                 
            ];
            $m_invoice=new OrdersInvoiceModel();
            $update['invoice_id']=$m_invoice->invoice_add($data_invoice); 
           
        }
        //支付信息，有账户名的保存
        if(!empty($data['account_name']) ){
            $data_pay=[ 
                'oid'=>$oid,
                'oid_type'=>$oid_type,
                'ptype'=>$ptype,
                'bank'=>$data['account_bank'],
                'num'=>$data['account_num'],
                'name'=>$data['account_name'],
                'location'=>$data['account_location'],
                'paytype'=>$data_order['paytype'],  
                'money'=>$data['order_amount'], 
            ];
            $m_pay=new OrdersPayModel();
            $update['pay_id']=$m_pay->pay_add($data_pay); 
            
        }
        //更新发票和支付信息
        if(!empty($update)){
            $m->where('id',$oid)->update($update);
        }
        $m_user=new UserModel();
        $m_user->aid_add($admin['id'], $oid, $table.'_aid');
        $m->commit(); 
        $this->success($dsc,url('edit',['id'=>$oid]));
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
        $utype=$this->utype;
        if($utype==1){
            $m_custom=Db::name('custom');
            $m_ugoods=Db::name('custom_goods');
        }else{
            $m_custom=Db::name('supplier');
            $m_ugoods=Db::name('supplier_goods');
        }
        //获取客户信息
        $custom=$m_custom->where('id',$info['uid'])->find();
        if(empty($custom)){
            $accounts=null; 
         }else{
             //可选支付账号
             $where=[
                 'uid'=>$custom['id'],
                 'type'=>$utype,
             ];
             $accounts=Db::name('account')->where($where)->order('site asc')->column('id,site,bank1,name1,num1,location1,paytype2');
             $ugoods=$m_ugoods
             ->alias('p')
             ->join('cmf_goods goods','goods.id=p.goods')
             ->where('p.uid',$custom['id']) 
             ->column('p.goods,p.name,p.cate,p.num,p.price,goods.name as goods_name,goods.code as goods_code');
             $this->assign('ugoods',$ugoods); 
         }
        //支付信息 
        if(empty($info['pay_id'])){
            $pay=null;
        }else{
            $pay=Db::name('orders_pay')->where('id',$info['pay_id'])->find();
        }
        
        //发票
        if(empty($info['invoice_id'])){
            $invoice=null;
        }else{
            $invoice=Db::name('orders_invoice')->where('id',$info['invoice_id'])->find();
        }
        
        
        //订单产品
        $res=$m->order_goods($info,$admin['id']);
         
        $this->cates();
        //是否允许拆分
        if($info['ok_break']!=1 || $info['fid']!=0){
            $ok_break=2;
        }else{
            $ok_break=1;
        }
        $this->assign('ok_break',$ok_break); 
        //是否允许添加，删除
        if($info['fid']!=0){
            $ok_add=2;
        }else{
            $ok_add=1;
        }
        $m_user=new UserModel();
        $table=$this->table;
        $users=$m_user->aid_check($admin,$info['aid'],$info['id'],$table.'_aid');
        if($users['code']==1){
            $this->assign('users',$users['users']);
            $this->assign('aids',$users['aids']);
        }
        //公司
        $where=[
            'shop'=>($admin['shop']==1)?2:$admin['shop'],
            'type'=>($info['order_type']==1)?1:2,
            'status'=>2,
        ]; 
        $companys=Db::name('company')->where($where)->order('sort asc')->column('id,name');
        $this->assign('companys',$companys);
        
        $this->assign('ok_add',$ok_add); 
        
        $this->assign('infos',$res['infos']);
        $this->assign('orders',$res['orders']);
        $this->assign('goods',$res['goods']);
        
        $this->assign('info',$info); 
        
        $this->assign('accounts',$accounts);
        $this->assign('custom',$custom);
        $this->assign('pay',$pay);
        
        $this->assign('invoice',$invoice);
        if($utype==1){
            $this->assign('ok_freight',1);
        }else{
            $this->assign('ok_freight',2);
        }
        
        return $this->fetch();  
    }
    /**
     * 订单编辑
     * @adminMenu(
     *     'name'   => '订单编辑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单编辑',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        $m=$this->m;
        $table=$this->table;
        $flag=$this->flag;
        $data=$this->request->param();  
      
        $info=$m->get_one(['id'=>$data['id']]);
      
        if(empty($info)){
            $this->error('数据不存在');
        }
        $time=time();
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1 && $info['shop']!=$admin['shop']){
           $this->error('不能编辑其他店铺的信息'); 
        }
        //有还原权限的为最高权限
        $res=$this->check_review($admin,'status_do0'); 
        if(!$res){ 
            //是否有权查看
            $res=$m->order_edit_auth($info,$admin);
            if($res!==1){
                $this->error($res);
            }
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
       
        $content=$this->m_order_edit($info, $data,$table);
        if(!is_array($content)){
            $this->error($content);
        }  
       
        //检测是否有授权变化
        if(!empty($data['aids'])){
            $m_user=new UserModel();
            $res=$m_user->aid_edit($admin,$info['aid'],$data['aids'],$info['id'],$table.'_aid');
            if($res==1){
                $content['aids']=$data['aids'];
            }
        }
        
        if(empty($content)){
            $this->error('未修改');
        }
        //保存更改
        $m_edit=Db::name('edit');
        $m_edit->startTrans();
        //未提交的直接修改
        if($info['status']==1){
            if($data['status']==2){
                $content['status']=2;
            }
            $res=$this->m_order_edit_review($info,$content,$table);
            if(!($res>0)){
                $m_edit->rollback();
                $this->error($res);
            }
            $m_edit->commit();
            $this->success('已修改',url('edit',['id'=>$info['id']]));
        } 
       
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
        //订单排序
        $m->order_sort($info['id']);
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
        
        zz_action($data_action,$admin);
        
        $m_edit->commit();
        //直接审核
        $rule='edit_review';
        $res=$this->check_review($admin,$rule);
        if($res){
            $this->redirect($rule,['id'=>$eid,'rstatus'=>2,'rdsc'=>'直接审核']);
        }
        $this->success('已提交修改');
    }
    
    /**
     * 订单编辑列表 
     */
    public function edit_list()
    {
        $table=$this->table;
        $m_edit=Db::name('edit');
        $flag=$this->flag;
        $data=$this->request->param();
        $admin=$this->admin;
        //查找当前表的编辑
        $where=['e.table'=>['eq',$table]];
        $join=[
            ['cmf_user a','a.id=e.aid','left'],
            ['cmf_user r','r.id=e.rid','left'],
            ['cmf_'.$table.' p','e.pid=p.id','left'],
            ['cmf_shop shop','e.shop=shop.id','left'],
        ];
        
        $field='e.*,a.user_nickname as aname,r.user_nickname as rname,p.name as pname,shop.name as sname';
        //店铺
        if($admin['shop']==1){
            if(empty($data['shop'])){
                $data['shop']=0;
            }else{
                $where['e.shop']=['eq',$data['shop']];
            }
        }else{
            $where['e.shop']=['eq',$admin['shop']];
        }
        //状态
        if(empty($data['status'])){
            $data['status']=0;
        }else{
            $where['e.rstatus']=['eq',$data['status']];
        }
        //编辑人
        if(empty($data['aid'])){
            $data['aid']=0;
        }else{
            $where['e.aid']=['eq',$data['aid']];
        }
        //审核人
        if(empty($data['rid'])){
            $data['rid']=0;
        }else{
            $where['e.rid']=['eq',$data['rid']];
        }
        //所属分类
        if(empty($data['cid'])){
            $data['cid']=0;
        }else{
            $where['p.cid']=['eq',$data['cid']];
        }
       
         //搜索类型
        $search_types=config('search_types');
        $types= [ 2=>['p.name' , '名称'],1=>['p.id','id']];  
        $res=zz_search_param($types, $search_types,$data, $where);
        $data=$res['data'];
        $where=$res['where']; 
        
        //时间类别
        $times=config('edit_time_search');
        $res=zz_search_time($times, $data, $where,['alias'=>'e.']);
        $data=$res['data'];
        $where=$res['where'];
         
        
        $list=$m_edit
        ->alias('e')
        ->field($field)
        ->join($join)
        ->where($where)
        ->order('e.rstatus asc,e.atime desc')
        ->paginate();
        
        // 获取分页显示
        $page = $list->appends($data)->render();
        
        //分类信息
        //显示编辑人和审核人
        $m_user=Db::name('user');
        //可以加权限判断，目前未加
        $where_aid=[
            'user_type'=>1,
        ];
        //创建人
       
        //如果分店铺又是列表页查找,显示所有店铺
        if($admin['shop']==1){
            $shops=Db::name('shop')->where('status',2)->column('id,name');
            $this->assign('shops',$shops);
        }else{
            $where_aid['shop']=$admin['shop'];
        }
       
        
        $aids=$m_user->where($where_aid)->column('id,user_nickname as name,shop');
        //审核人
        $this->assign('aids',$aids);
        $this->assign('rids',$aids);
        
        $this->assign('page',$page);
        $this->assign('list',$list);
        
        $this->assign('data',$data);
        $this->assign('types',$types);
        $this->assign('times',$times);
        $this->assign("search_types", $search_types);
        return $this->fetch();
    }
    /**
     * 订单编辑审核页面
     * @adminMenu(
     *     'name'   => ' 订单编辑审核页面',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '订单编辑审核页面',
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
       
       
        $this->where_shop=$info['shop'];
      
        $id=$info['id'];
        $shop=$info['shop'];
        $admin=$this->admin;
        if($admin['shop']>1 && $admin['shop']!=$shop){
            $this->error('只能查看本店铺的数据');
        }
        $this->where_shop=$shop;
        $accounts=null;
        $pay=null;
        $invoice=null;
        //获取客户信息
        $utype=$this->utype;
        if($utype==1){
            $m_custom=Db::name('custom');
            $m_ugoods=Db::name('custom_goods');
        }else{
            $m_custom=Db::name('supplier');
            $m_ugoods=Db::name('supplier_goods');
        }
        $custom=$m_custom->where('id',$info['uid'])->find();
        if($info['fid']==0){  
            if(!empty($custom)){ 
                //可选支付账号
                $where=[
                    'uid'=>$custom['id'],
                    'type'=>$utype,
                ];
                 $accounts=Db::name('account')->where($where)->order('site asc')->column('id,site,bank1,name1,num1,location1,paytype2');
                 $ugoods=$m_ugoods
                 ->alias('p')
                 ->join('cmf_goods goods','goods.id=p.goods')
                 ->where('p.uid',$custom['id'])
                 ->column('p.goods,p.name,p.cate,p.num,p.price,goods.name as goods_name,goods.code as goods_code');
                 $this->assign('ugoods',$ugoods);
            }
            //支付信息
            if(!empty($info['pay_id'])){
                $pay=Db::name('orders_pay')->where('id',$info['pay_id'])->find();
            }
           
            //发票
            if(!empty($info['invoice_id'])){
                $invoice=Db::name('orders_invoice')->where('id',$info['invoice_id'])->find();
            } 
         } 
         
        //订单产品
         $res=$m->order_goods($info,$admin['id'],$change);
        $this->cates(); 
        
        $m_user=new UserModel();
        $users=$m_user->aid_check($admin,$info['aid'],$info['id'],$table.'_aid');
        if($users['code']==1){
            $this->assign('users',$users['users']);
            $this->assign('aids',$users['aids']);
        }
        //公司
        $where=[
            'shop'=>$info['shop'],
            'type'=>($info['order_type']==1)?1:2,
            'status'=>2,
        ];
        $companys=Db::name('company')->where($where)->order('sort asc')->column('id,name');
        $this->assign('companys',$companys);
        
        $this->assign('infos',$res['infos']);
        $this->assign('orders',$res['orders']);
        $this->assign('goods',$res['goods']);
 
        $this->assign('info',$info);
        $this->assign('info1',$info1);
        $this->assign('change',$change);
       
        $this->assign('accounts',$accounts);
        $this->assign('custom',$custom);
        $this->assign('pay',$pay);
        
        $this->assign('invoice',$invoice);
        //是否允许拆分,添加，删除
        $this->assign('ok_break',2); 
        $this->assign('ok_add',2); 
       //不允许计算运费
        $this->assign('ok_freight',2);
        
        return $this->fetch();  
        
    }
    /**
     * 订单编辑审核确认
     * @adminMenu(
     *     'name'   => ' 订单编辑审核确认',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '订单编辑审核确认',
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
        $order=$m->get_one(['id'=>$info['pid']]);
        
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
            if(isset($change['status']) || isset($change['pay_status'])){
               
                $row=$this->m_status_edit_review($order,$change,$table); 
            }else{
                //检测是否有授权变化
                if(isset($change['aids'])){
                    $m_user=new UserModel();
                    $m_user->aid_edit_do($admin,$order['aid'],$change['aids'],$order['id'],$table.'_aid');
                    unset($change['aids']);
                } 
                $row=$this->m_order_edit_review($order,$change,$table);
            }
            
            if($row!==1){
                $m->rollback();
                $this->error($row);
            }
            
            //排序
            $m->order_sort($order['id']);
            $order1=$m->get_one(['id'=>$info['pid']]); 
            $m_msg=new MsgModel();
            if($table=='order'){
                $pay_do2='order/AdminOrder/pay_do2';
                $order_name='订单'.$order1['id'].'--'.$order1['name'];
            }else{
                $pay_do2='ordersup/AdminOrdersup/pay_do2';
                $order_name='采购单'.$order1['id'].'--'.$order1['name'];
            }
           
            $msg_data=[ 
                'link'=>url('edit',['id'=>$order1['id']]),
                'shop'=>$info['shop'],
                'aid'=>$admin['id']
            ];
            $msg_dsc=$order_name.'需要准备发货 ';
            $msg_auth='order/AdminOrder/status_do20';
            //判断是否需要出库
            if(isset($change['status'])){ 
                //先根据状态出入库
                $res=$m->status_change($order['id'],$order['status']);
                if(!($res>0)){
                    $m->rollback();
                    $this->error($res);
                }
                //根据状态通知
                if($table=='order'){
                    switch($change['status']){
                        case 20:
                            //准备发货 5
                            $msg_dsc=$order_name.'需要准备发货 ';
                            $msg_auth='order/AdminOrder/status_do20';
                            $msg_data['dsc']=$msg_dsc;
                            $m_msg->auth_send($msg_auth,$msg_data);
                            break;
                    }
                }else{
                    switch($change['status']){
                        case 10:
                            //采购待付款 
                            $msg_dsc=$order_name.'需要付款 ';
                            $msg_auth='ordersup/AdminOrdersup/pay_do1';
                            $msg_data['dsc']=$msg_dsc;
                            $m_msg->auth_send($msg_auth,$msg_data);
                            break;
                        case 20:
                            //采购待付款
                            $msg_dsc=$order_name.'等待发货，请关注';
                            $msg_auth='ordersup/AdminOrdersup/status_do20';
                            $msg_data['dsc']=$msg_dsc;
                            $m_msg->auth_send($msg_auth,$msg_data);
                            break;
                    }
                }
                
            }
            //判断付款更新
            if(isset($change['pay_status']) ){ 
                
                if($change['pay_status']==3){
                    //付款成功后更新发票
                    if(!empty($order1['invoice_id'])){
                        $m_invoice=new OrdersInvoiceModel();
                        $where=[
                            'id'=>$order['invoice_id'],
                            'status'=>1
                        ];
                        $m_invoice->where($where)->update(['status'=>2]);
                    } 
                }elseif($change['pay_status']==2){ 
                    //提示财务确认付款  
                    if($table=='order'){
                        $msg_dsc=$order_name.'已提交付款，请确认';
                        $msg_auth='order/AdminOrder/pay_do2';
                        $msg_data['dsc']=$msg_dsc;
                        $m_msg->auth_send($msg_auth,$msg_data);
                    }
                   
               }
               
            }
           
        }
        
        //审核成功，记录操作记录,发送审核信息
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'审核'.$info['aid'].'-'.$info['aname'].'对'.($this->flag).$info['pid'].'-'.$order['name'].'的编辑为'.$review_status[$status],
            'table'=>$table,
            'type'=>'edit_review',
            'pid'=>$info['pid'],
            'link'=>url('edit_info',['id'=>$info['id']]),
            'shop'=>$admin['shop'],
        ];
        
        zz_action($data_action,['aid'=>$info['aid']]);
        
        $m->commit();
        //订单准备发货后直接发货，采购单准备收货后直接收货 
       
        if(isset($change['status'])){
            $status=0;
            switch($change['status']){
                case 2:
                    //提交直接确认
                    $auth='status1_2';
                    $action='status_do2';
                    $flag='直接确认';
                    $status=2;
                    break;
                case 22:
                    //准备发货直接仓库发货
                    if($table=='order'){
                        $auth='status20_22';
                        $action='status_do22';
                        $flag='直接仓库发货';
                        $status=22;
                    }
                   
                    break;
                case 24:
                    //准备收货直接收货完成
                    if($table=='ordersup'){
                        $auth='status22_24';
                        $action='status_do24';
                        $flag='直接收货完成';
                        $status=24;
                    }
                    break;
            }
            if(!empty($status)){ 
                $res=$this->check_review($admin,$auth);
                if($res){
                    $this->status_do(['id'=>$info['pid']],$status,$flag);
                } 
            }
        }
        if(isset($change['pay_status']) && $change['pay_status']==2){
            //付款后直接确认 
            $auth='pay1_2'; 
            $flag='付款直接确认';
            $status=2;
            $res=$this->check_review($admin,$auth);
            if($res){
                $this->pay_do(['id'=>$info['pid']],$status,$flag);
            } 
        }
        $this->success('审核成功');
    }
    
    //分类
    public function cates($type=3){
        $this->assign('invoice_types',config('invoice_type'));
     
        $this->assign('pay_status',config('pay_status'));
        $this->assign('pay_types',config('pay_type'));
        $this->assign('invoice_status',config('invoice_status'));
       
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
        $utable=$this->utable;
        $custom_cates=Db::name($utable.'_cate')->where($where)->order('sort asc')->column('id,name');
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
        $order='shop asc,sort asc';
        $field='id,name'; 
        $admin=$this->admin;
        if($admin['shop']==1){
            $shops=Db::name('shop')->where($where)->order('sort asc')->column('id,name');
            $this->assign('shops',$shops);  
        }
        if(!empty($where_shop)){ 
            $where['shop']=$where_shop;
            $where_admin['shop']=$where_shop;
        } 
        if($where_admin['shop']==1 || $where_admin['shop']==2){
            $where_admin['shop']=['lt',3];
        }
      
       
        //付款方式
        $paytypes=Db::name('paytype')->where($where)->order($order)->column($field);
        //获取所有仓库
        $stores=Db::name('store')->where($where)->order($order)->column($field); 
        //获取所有物流方式
        $freights=Db::name('freight')->where($where)->order('shop asc,sort asc,store asc')->column('id,name,shop,store'); 
        //管理员
        $aids=Db::name('user')->where($where_admin)->column('id,user_nickname as name');
        if($type==3){ 
            $stores_tr='<thead><tr>';
            foreach($stores as $k=>$v){
                $stores_tr.='<th>'.$v.'</th>';
            } 
            $stores_tr.='</tr></thead>'; 
            $this->assign('stores_tr',$stores_tr);
            $this->assign('stores_json',json_encode($stores)); 
        }
    
        $this->assign('paytypes',$paytypes); 
        $this->assign('aids',$aids); 
        $this->assign('rids',$aids); 
        $this->assign('stores',$stores); 
        $this->assign('freights',$freights); 
        $this->assign('goods_url',url('goods/AdminGoods/edit',false,false)); 
        $this->assign('image_url',cmf_get_image_url('')); 
        if($utable=='custom'){
            $this->assign('order_url',url('order/AdminOrder/edit',false,false));
            $this->assign('order_user_url',url('custom/AdminCustom/edit',false,false));
            $this->assign('order_types',config('order_type'));
            $this->assign('statuss',config('order_status'));
            $url_status=[
                1=>['提交下单 ',url('status_do1','',false,false)],
                2=>['确认订单 ',url('status_do2','',false,false)],
                10=>['手动转为待发货 ',url('status_do10','',false,false)],
                20=>['准备发货 ',url('status_do20','',false,false)],
                22=>['仓库发货 ',url('status_do22','',false,false)],
                24=>['确认收货 ',url('status_do24','',false,false)],
            ];
      }else{
          $this->assign('order_url',url('ordersup/AdminOrdersup/edit',false,false));
          $this->assign('order_user_url',url('custom/AdminSupplier/edit',false,false));
          $this->assign('statuss',config('ordersup_status'));
          $this->assign('order_types',config('ordersup_type')); 
          $url_status=[
              1=>['提交下单 ',url('status_do1','',false,false)],
              2=>['确认订单 ',url('status_do2','',false,false)],
              10=>['手动转为待收货 ',url('status_do10','',false,false)],
              20=>['供货商发货',url('status_do20','',false,false)],
              22=>['准备收货 ',url('status_do22','',false,false)],
              24=>['收货完成 ',url('status_do24','',false,false)],
          ];
      }
        
        $this->assign('edit_url',url('edit_list',['type1'=>'id','type2'=>1],false));
        $this->assign('url_status',$url_status);
        
    }
    
   
     
    /* 改变订单支付状态 */
    public function pay_do($data,$pay_status,$flag){
        
        
        $m=$this->m;
        $table=$this->table;
        
        $id=intval($data['id']);
        
        
        $info=$m->get_one(['id'=>$id]);
        if(empty($info)){
            $this->error('数据不存在');
        }
       
        $time=time();
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1 && $info['shop']!=$admin['shop']){
            $this->error('不能编辑其他店铺的信息');
        }
        //有还原权限的为最高权限
        $res=$this->check_review($admin,'pay_do0');
        if(!$res){
            //是否有权查看
            $res=$m->order_edit_auth($info,$admin);
            if($res!==1){
                $this->error($res);
            }
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
        if($info['status']<10){
            $this->error('先提交确认才能付款');
        }
        if($pay_status!=0 && $info['pay_status']!=$pay_status){
            $this->error('状态信息错误');
        }
        
        switch ($pay_status){
            case 1:
                //用户付款提交
                $content['pay_status']=2; 
                break;
            case 2:
                //财务确认付款,未发货的可以发货了
                $content['pay_status']=3;
                if($info['status']<20){
                    $content['status']=20;
                }elseif($info['status']==26){
                    $content['status']=30;
                } 
                break; 
            case 0:
                //超管编辑
                $content['pay_status']=1;
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
        $rule='edit_review';
        $this->redirect($rule,['id'=>$eid,'rstatus'=>2,'rdsc'=>'无需审核，直接通过']);
        /* $rule='edit_review';
        $res=$this->check_review($admin,$rule);
        if($res){
            $this->redirect($rule,['id'=>$eid,'rstatus'=>2,'rdsc'=>'直接审核']);
        } */
        $this->success('已提交修改');
    }
    /* 改变订单状态 */
    public function status_do($data,$status,$flag){
        
        
        $m=$this->m;
        $table=$this->table;
        
        $id=intval($data['id']);
        $url_error=url('edit',['id'=>$id]);
        $info=$m->get_one(['id'=>$id]);
        if(empty($info)){
            $this->error('数据不存在',$url_error);
        }
        $time=time();
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1 && $info['shop']!=$admin['shop']){
            $this->error('不能编辑其他店铺的信息',$url_error);
        }
        //有还原权限的为最高权限
        $res=$this->check_review($admin,'status_do0');
        if(!$res){
            //是否有权查看
            $res=$m->order_edit_auth($info,$admin);
            if($res!==1){
                $this->error($res);
            }
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
            $this->error('状态信息错误',$url_error);
        }
        if(isset($data['express_no'])){
            $dsc=$data['dsc'];
            $express_no=$data['express_no'];
        }elseif(isset($data['dsc0'][$info['id']])){
            $dsc=$data['dsc0'][$info['id']];
            $express_no=$data['express_no0'][$info['id']];
        }
        
        if(isset($express_no)){
            if($info['dsc']!=$dsc){
                $content['dsc']=$dsc;
            }
            if($info['express_no']!=$express_no){
                $content['express_no']=$express_no;
            }
        }
        
        switch ($status){
            case 1:
                //提交订单
                $content['status']=2;
                break;
            case 2:
                //确认订单
                //判断是先付款后发货还是先发货
                $pay_type=isset($content['pay_type'])?$content['pay_type']:$info['pay_type'];
                if($pay_type==1){
                    $content['status']=10;
                }else{
                    $content['status']=20;
                }
                break;
            case 10:
                //手动待发货
                $content['status']=20;
                break;
            case 20:
                //准备发货
                $content['status']=22;
                //检查库存
                if($table=='order'){
                    
                    $res=$m->order_store($id);
                    if($res!==1){
                        $this->error($res,$url_error);
                    }
                }
                
                break;
            case 22:
                //仓库发货
                $content['status']=24;
                $content['send_time']=$time;
                //检查库存
                if($table=='order'){
                    if(empty($info['express_no']) && empty($content['express_no'])){
                        $this->error('快递单号未填写',$url_error);
                    }
                    $res=$m->order_store($id);
                    if($res!==1){
                        $this->error($res,$url_error);
                    }
                }
               
                break;
            case 24:
                // 点击“确认收货”，订单状态为已收货，若已支付，则订单状态为已完成。
                $content['accept_time']=$time;
                $content['status']=26;
                if($info['pay_status']==3){
                    $content['completion_time']=$time;
                    $content['status']=30;
                }
                break;
            case 30:
                //退货
                $content['status']=70;
                break;
            case 0:
                //超管编辑
                $content['status']=1;
                break;
            default:
                $this->error('操作错误',$url_error);
        }
        //淘宝订单的不能线下先收款，和到货
        if(isset($content['status']) && $info['order_type']==3){
            //淘宝订单只能点击准备发货和确认发货，暂时不做
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
            $this->error('保存数据错误，请重试',$url_error);
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
        
        zz_action($data_action,$admin);
        
        $m_edit->commit();
       
        $rule='status_review';
        $res=$this->check_review($admin,$rule);
        if($res){
            $this->redirect('edit_review',['id'=>$eid,'rstatus'=>2,'rdsc'=>'直接审核']);
        }
        $this->success('已提交修改');
    }
    /**
     * 废弃 
     */
    public function order_abandon(){
         
        $flag='废弃订单';
        $data=$this->request->param();
        
        $m=$this->m;
        $table=$this->table;
        
        $id=intval($data['id']);
        
        $info=$m->get_one(['id'=>$id]);
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
        $res=$m->order_edit_auth($info,$admin);
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
        //只有未发货且未付款的才能废弃
        if($info['status']>22 || $info['pay_status']>2){
            $this->error('只有未发货且未付款的才能废弃');
        }
        $content=[
            'status'=>81, 
        ];
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
        $rule='edit_review';
        $res=$this->check_review($admin,$rule);
        if($res){
            $this->redirect($rule,['id'=>$eid,'rstatus'=>2,'rdsc'=>'直接审核']);
        }
        $this->success('已提交修改');
    }
    /**
     * 配货单打印 
     */
    public function print_order(){
        $id=$this->request->param('id',0,'intval');
        $m=$this->m; 
        $info=$m
        ->alias('p')
        ->field('p.*,custom.name as uname,custom.mobile as umobile') 
        ->join('cmf_custom custom','custom.id=p.uid','left')
        ->where('p.id',$id)->find();
       
        if($info['is_real']!=1){
            $this->error('已拆分订单请单独打印');
        }
        if($info['status']<22){
            $this->error('请先准备发货');
        }
        $shop=Db::name('shop')->where('id',$info['shop'])->find();
        $goods=Db::name('order_goods')->where('oid',$id)->column('*','goods');
        $where=[
            'type'=>10,
            'about'=>$id,
            'rstatus'=>['in',[1,2]]
        ];
        $goods_instore=Db::name('store_in')
        ->where($where)
        ->column('goods,box');
        if(empty($goods_instore)){
            $boxes=[];
        }else{
            $boxes=Db::name('store_box')->where('id','in',$goods_instore)->column('id,code');
        }
        
        foreach($goods as $k=>$v){
            if(empty($goods_instore[$k]) || empty($boxes[$goods_instore[$k]])){
                $goods[$k]['box']='--';
            }else{
                $goods[$k]['box']=$boxes[$goods_instore[$k]];
            }
        }
        $this->assign('info',$info);
        $this->assign('shop',$shop);
       
        $this->assign('goods',$goods);
        $this->assign('date',date('Y-m-d'));
        return $this->fetch();
    }
    /* 订单编辑 */
    public function m_order_edit($info,$data,$table='order')
    {
        $m_order=$this->m;
        //订单和采购单区分
        if($table=='order'){
            $otype=1;
            $ptype=1; 
            $m_ogoods=Db::name('order_goods');
        }else{
            $otype=2;
            $ptype=2; 
            $m_ogoods=Db::name('ordersup_goods');
        }
        $content=[];
        //检测改变了哪些字段
        //所有订单都有,都能修改
        $edit_base=['dsc','store','freight','weight','size','pay_freight','real_freight',
            'goods_num','goods_money','discount_money','other_money','express_no',
            'weight_box','weight_real','box_out','invoice0_money','order_amount'
        ];
        //收货信息，子订单可以单独修改，总订单修改后同步到子订单
        $edit_accept=['accept_name','mobile','phone','province','city','area','address','postcode','addressinfo'];
        
        //总订单信息系
        $edit_fid0=['company','udsc','paytype','pay_type','invoice_type','order_type','ok_break'];
        //组装需要判断的字段,子订单没有总订单信息
        if($info['fid']==0){
            $fields=array_merge($edit_accept,$edit_base,$edit_fid0);
        }else{
            //子订单能修改的字段
            $fields=array_merge($edit_accept,$edit_base);
        }
        //先比较总信息
        foreach($fields as $k=>$v){
            //如果原信息和$data信息相同就未改变，不为空就记录，？null测试
            if(isset($data[$v]) && $info[$v]!=$data[$v]){
                $content[$v]=$data[$v];
            }
        }
        //子订单不能改修改产品和费用，所以到此结束
        if($info['fid']>0){
            return $content;
        }
        //主订单才有发票和付款信息
        if($info['fid']==0 ){
            //发票信息
            $edit_invoice=['uname','ucode','point','invoice_money','tax_money','dsc','address','bank_location'];
            
            //已有发票或有税费金额的要判断发票信息
            if(!empty($info['invoice_id']) || !empty($data['invoice_tax_money']) ){
                $data['invoice_id']=$info['invoice_id'];
                $data['invoice_point']=round( $data['invoice_point'],2);
                $data['invoice_invoice_money']=round( $data['invoice_invoice_money'],2);
                $data['invoice_tax_money']=round( $data['invoice_tax_money'],2);
                if($data['invoice_id']==0){
                    $invoice=null;
                }else{
                    //发票
                    $where=[
                        'id'=>$info['invoice_id'],
                    ];
                    $m_invoice=new OrdersInvoiceModel();
                    $invoice=$m_invoice->where($where)->find();
                    if(empty($invoice)){
                        $invoice=null;
                        $data['invoice_id']=0;
                    }
                }
                
                $content['invoice']=[];
                foreach($edit_invoice as $k=>$v){
                    $field_tmp='invoice_'.$v;
                    //如果原信息和$data信息相同就未改变，不为空就记录，？null测试
                    if(isset($data[$field_tmp]) && $invoice[$v]!=$data[$field_tmp]){
                        $content['invoice'][$v]=$data[$field_tmp];
                    }
                }
                //支付账号
                if($data['paytype'] != $invoice['paytype']){
                    $content['invoice']['paytype']=$data['paytype'];
                }
                
                //没有改变清除
                if(empty($content['invoice'])){
                    unset($content['invoice']);
                }else{
                    $content['invoice']['id']= $data['invoice_id'];
                    $content['invoice']['oid']= $info['id'];
                    $content['invoice']['oid_type']= $otype;
                }
            }
            //同步订单信息
            if(isset( $content['invoice']['tax_money'])){
                $content['tax_money']=$content['invoice']['tax_money'];
            }
            if(isset( $content['invoice']['invoice_money'])){
                $content['invoice_money']=$content['invoice']['invoice_money'];
            }
            //支付信息
            $edit_account=['bank','name','num','location'];
            //已有付款账号信息和付款账户名
            if(!empty($info['pay_id']) || !empty($data['account_name']) ){
                $data['account_id']=$info['pay_id'];
                if($data['account_id']==0){
                    $pay=null;
                }else{
                    //发票
                    $where=[
                        'id'=>$data['account_id'],
                    ];
                    $m_pay=new OrdersPayModel();
                    $pay=$m_pay->where($where)->find();
                    if(empty($pay)){
                        $data['account_id']=0;
                    }
                }
                
                $content['pay']=[];
                foreach($edit_account as $k=>$v){
                    $field_tmp='account_'.$v;
                    //如果原信息和$data信息相同就未改变，不为空就记录，？null测试
                    if(isset($data[$field_tmp]) && $pay[$v]!=$data[$field_tmp]){
                        $content['pay'][$v]=$data[$field_tmp];
                    }
                }
                //店铺支付账号
                if($pay['paytype']!=$data['paytype']){
                    $content['pay']['paytype']=$data['paytype'];
                }
                //没有改变清除
                if(empty($content['pay'])){
                    unset($content['pay']);
                }else{
                    //记录id,review时检测
                    $content['pay']['id']= $data['account_id'];
                    $content['pay']['oid']= $info['id'];
                    $content['pay']['oid_type']= $otype;
                    $content['pay']['ptype']= $ptype;
                    
                }
            }
        }
        
        //获取原订单和订单产品
        $where_goods=[];
        if($info['is_real']==1 ){
            $where_goods['oid']=['eq',$info['id']];
            $orders=[$info['id']=>$info];
            $order_ids=[$info['id']];
        }else{
            $fields='id,name,'.(implode(',',$edit_base));
            /*   $fields='id,name,freight,store,weight,size,discount_money,goods_num,goods_money,pay_freight'.
             ',real_freight,other_money,tax_money,order_amount,dsc'; */
            $orders=$m_order->where('fid',$info['id'])->column($fields);
            
            $order_ids=array_keys($orders);
            
            $where_goods['oid']=['in',$order_ids];
        }
        
        //全部订单产品
        $order_goods=$m_ogoods
        ->where($where_goods)
        ->column('');
        //数据转化，按订单分组
        $infos=[];
        //先组装所有订单，防止有的订单没有产品
        foreach($order_ids as $v){
            $infos[$v]=[];
        }
        $goods_info=[];
        
        foreach($order_goods as $k=>$v){
            /*  if($v['goods']<=0){
             return '产品'.$v['goods_code'].$v['goods_uname'].'不存在，要调整';
             } */
            $infos[$v['oid']][$v['goods']]=$v;
            $goods_info[$v['goods']]=$v;
        }
        //得到原有产品
        $goods_ids0=array_keys($goods_info);
        
        $goods_ids1=$data['goods_ids'];
        $ids_add=array_diff($goods_ids1,$goods_ids0);
        if(!empty($ids_add)){
            //有新增产品
            $goods_add=Db::name('goods')->where('id','in',$ids_add)->column('id as goods,name as goods_name,name3 as print_name,code as goods_code,pic as goods_pic,price_in,price_sale,type,weight1,size1');
            foreach($goods_add as $k=>$v){
                //判断产品重量体积单位,统一转化为kg,cm3
                $v=$m_order->unit_change($v);
                unset($v['type']);
                $goods_info[$k]=$v;
            }
        }
        
        //子订单nums-{$kk}[{$key}],只有在主订单下才能拆分订单
        
        /*  $edit_base=['dsc','store','freight','weight','size','pay_freight','real_freight',
         'goods_num','goods_money','discount_money','tax_money','other_money','order_amount',
         ]; */
        $edit_goods=['num','pay','weight','size','dsc','price_real','pay_discount','goods_uname','goods_ucate'];
        
        
        //多个要一个个比较,先比较是否存在
        foreach($data['oids'] as $k=>$void){
            
            if($void==$info['id']){
                $data['order_amount0'][$void]= $data['order_amount'];
            }else{
                $data['order_amount0'][$void]= $data['invoice0_money0'][$void];
            }
            if(in_array($void,$order_ids)){
                //编辑订单信息
                foreach($edit_base as $kk=>$vv){
                    if(isset($data[$vv.'0'][$void]) && $orders[$void][$vv]!=$data[$vv.'0'][$void]){
                        $content['edit'][$void][$vv]=$data[$vv.'0'][$void];
                    }
                }
                
                //一个个比较产品，是否有删除或编辑
                foreach ($infos[$void] as $kgoodsid=>$kv){
                    //data不存在就是没有该产品了,删除
                    if(!isset($data['nums-'.$void][$kgoodsid]) ){
                        $content['edit'][$void]['goods_del'][$kgoodsid]=$kv;
                        continue;
                    }
                    if($kgoodsid<=0){
                        return '产品不存在，请重新选择产品';
                    }
                    //循环商品信息
                    foreach($edit_goods as $vv){
                        if($data[$vv.'s-'.$void][$kgoodsid] !=  $kv[$vv]){
                            $content['edit'][$void]['goods'][$kgoodsid][$vv]=$data[$vv.'s-'.$void][$kgoodsid];
                        }
                    }
                }
                if(isset($data['nums-'.$void])){
                    //再用data数据循环，检查是否有新增，没有继续向下
                    foreach ($data['nums-'.$void] as $kgoodsid=>$kv){
                        if($kgoodsid<=0){
                            return '产品不存在，请重新选择产品';
                        }
                        if(isset($infos[$void][$kgoodsid])){
                            continue;
                        }
                        $content['edit'][$void]['goods_add'][$kgoodsid]=[];
                        //保存订单号
                        $content['edit'][$void]['goods_add'][$kgoodsid]['oid']=$void;
                        //添加商品id
                        $content['edit'][$void]['goods_add'][$kgoodsid]['goods']=$kgoodsid;
                        //循环商品信息
                        foreach($edit_goods as $vv){
                            $content['edit'][$void]['goods_add'][$kgoodsid][$vv]=$data[$vv.'s-'.$void][$kgoodsid];
                        }
                        $content['edit'][$void]['goods_add'][$kgoodsid]['goods_name']=$goods_info[$kgoodsid]['goods_name'];
                        $content['edit'][$void]['goods_add'][$kgoodsid]['print_name']=$goods_info[$kgoodsid]['print_name'];
                        $content['edit'][$void]['goods_add'][$kgoodsid]['goods_code']=$goods_info[$kgoodsid]['goods_code'];
                        $content['edit'][$void]['goods_add'][$kgoodsid]['goods_pic']=$goods_info[$kgoodsid]['goods_pic'];
                        $content['edit'][$void]['goods_add'][$kgoodsid]['price_in']=$goods_info[$kgoodsid]['price_in'];
                        $content['edit'][$void]['goods_add'][$kgoodsid]['price_sale']=$goods_info[$kgoodsid]['price_sale'];
                        $content['edit'][$void]['goods_add'][$kgoodsid]['weight1']=$goods_info[$kgoodsid]['weight1'];
                        $content['edit'][$void]['goods_add'][$kgoodsid]['size1']=$goods_info[$kgoodsid]['size1'];
                        
                    }
                }
            }else{
                //不存在新增
                $content['add'][$void]=[];
                //添加订单信息
               
                foreach($edit_base as $kk=>$vv){
                    if($vv=='express_no'){
                        $content['add'][$void][$vv]='';
                    }else{
                        $content['add'][$void][$vv]=$data[$vv.'0'][$void];
                    } 
                }
                if(isset($data['nums-'.$void])){
                    foreach ($data['nums-'.$void] as $kgoodsid=>$kv){
                        $content['add'][$void]['goods'][$kgoodsid]=[];
                        $content['add'][$void]['goods'][$kgoodsid]['oid']=0;
                        //添加商品id
                        $content['add'][$void]['goods'][$kgoodsid]['goods']=$kgoodsid;
                        
                        //循环商品信息
                        foreach($edit_goods as $vv){
                            $content['add'][$void]['goods'][$kgoodsid][$vv]=$data[$vv.'s-'.$void][$kgoodsid];
                        }
                        $content['add'][$void]['goods'][$kgoodsid]['goods_name']=$goods_info[$kgoodsid]['goods_name'];
                        $content['add'][$void]['goods'][$kgoodsid]['print_name']=$goods_info[$kgoodsid]['print_name'];
                        $content['add'][$void]['goods'][$kgoodsid]['goods_code']=$goods_info[$kgoodsid]['goods_code'];
                        $content['add'][$void]['goods'][$kgoodsid]['goods_pic']=$goods_info[$kgoodsid]['goods_pic'];
                        $content['add'][$void]['goods'][$kgoodsid]['price_in']=$goods_info[$kgoodsid]['price_in'];
                        $content['add'][$void]['goods'][$kgoodsid]['price_sale']=$goods_info[$kgoodsid]['price_sale'];
                        $content['add'][$void]['goods'][$kgoodsid]['weight1']=$goods_info[$kgoodsid]['weight1'];
                        $content['add'][$void]['goods'][$kgoodsid]['size1']=$goods_info[$kgoodsid]['size1'];
                    }
                }else{
                    $content['add'][$void]['goods']=[];
                }
                
            }
        }
        return $content;
        
    }
    /**
     * 审核订单编辑
     * @param array $order
     * @param array $change
     * @return number|string
     */
    public function m_order_edit_review($order,$change,$table='order')
    {
        $m_order=$this->m;
        //订单和采购单区分
        if($table=='order'){
            $otype=1;
            $ptype=1; 
            $m_ogoods=Db::name('order_goods');
        }else{
            $otype=2;
            $ptype=2; 
            $m_ogoods=Db::name('ordersup_goods');
        }
      
        //获取订单状态信息
        if($order['is_real']==1 ){
            $orders=[$order['id']=>$order];
        }else{
            $orders=$m_order->where('fid',$order['id'])->column('id,is_real,pay_status,status,sort');
            $orders[$order['id']]=$order;
        }
        $time=time();
        
        //所有订单都有,都能修改
        $edit_base=['dsc','store','freight','weight','size','pay_freight','real_freight',
            'goods_num','goods_money','discount_money','other_money','express_no',
            'weight_box','weight_real','box_out','invoice0_money','order_amount'
        ];
        //收货信息，状态信息，子订单可以单独修改，总订单修改后同步到子订单
        $edit_accept=['accept_name','mobile','phone','province','city','area','address','postcode','addressinfo','status'];
        
        //总订单信息系，子订单不能单独修改，总订单修改后同步到子订单
        $edit_fid0=['company','udsc','paytype','pay_type','invoice_type','order_type','pay_status','ok_break'];
        
        
        //记录有订单变化，需要废弃原出入库的订单id,重新添加
        $instore_oids=[];
        //新添加订单号
        $instore_add_oids=[];
        //依次处理change的信息，处理后unset
        
        //先处理字订单产品，再处理订单信息
        if(isset($change['edit'])){
            foreach($change['edit'] as $koid=>$vo){
                $where=['oid'=>$koid];
                //先处理已删除的产品
                if(isset($vo['goods_del'])){
                    $instore_oids[]=$koid;
                    $where['goods']=['in',array_keys($vo['goods_del'])];
                    $m_ogoods->where($where)->delete();
                    unset($vo['goods_del']);
                }
                //编辑产品
                if(isset($vo['goods'])){
                    foreach($vo['goods'] as $kgoods_id=>$vgoods){
                        $where['goods']=$kgoods_id;
                        //产品数量变化要重新出入库
                        if(isset($vgoods['num'])){
                            $instore_oids[]=$koid;
                        }
                        $m_ogoods->where($where)->update($vgoods);
                    }
                    unset($vo['goods']);
                }
                //添加产品
                if(isset($vo['goods_add'])){
                    $instore_oids[]=$koid;
                    $m_ogoods->insertAll($vo['goods_add']);
                    unset($vo['goods_add']);
                }
                $where=['id'=>$koid];
                $m_order->where($where)->update($vo);
            }
            unset($change['edit']);
        }
        //新增订单信息只能先保存产品信息，新增订单，才有单号给产品保存
        if(isset($change['add'])){
            //有新增一定是虚拟主单号了,拆分单号,删除原产品
            if($order['is_real']==1){
                $change['is_real']=2;
                $instore_oids[]=$order['id'];
                $m_ogoods->where('oid',$order['id'])->delete();
            }
            
            //得到子订单的序号
            $tmp=$m_order->where('fid',$order['id'])->count();
            
            if(empty($tmp)){
                $tmp=0;
            }
            $goods_adds=[];
            $goods_ids=[];
            foreach($change['add'] as $koid=>$vo){
                //订单信息,状态待定,跟随主订单
                $tmp++;
                $data_order=[
                    'order_type'=>$order['order_type'],
                    'aid'=>$order['aid'],
                    'shop'=>$order['shop'],
                    'company'=>$order['company'],
                    'uid'=>$order['uid'],
                    'udsc'=>$order['udsc'],
                    'create_time'=>$time,
                    'time'=>$time,
                    'sort'=>$order['sort'],
                    'status'=>$order['status'],
                    'fid'=>$order['id'],
                    'name'=>$order['name'].'_'.$tmp,
                ];
                //收货人信息
                foreach ($edit_accept as $v){
                    $data_order[$v]=(isset($change[$v]))?$change[$v]:$order[$v];
                }
                //总单信息
                foreach ($edit_fid0 as $v){
                    $data_order[$v]=(isset($change[$v]))?$change[$v]:$order[$v];
                }
                //子单信息
                foreach ($edit_base as $v){
                    $data_order[$v]=$vo[$v];
                }
                $tmp_oid=$m_order->insertGetId($data_order);
                $instore_add_oids[]=$tmp_oid;
                //产品新增
                if(isset($vo['goods'])){
                    foreach($vo['goods'] as $kgoods_id=>$vgoods){
                        $vgoods['oid']=$tmp_oid;
                        $goods_adds[]=$vgoods;
                    }
                }
            }
            if(!empty($goods_adds)){
                //最后统一新增产品
                $m_ogoods->insertAll($goods_adds);
            }
            
            unset($change['add']);
        }
        //支付账号信息
        if(isset($change['pay'])){
            $m_pay=new OrdersPayModel();
            if(empty($order['pay_id'])){
                $change['pay_id']=$m_pay->pay_add($change['pay']);
            }else{
                $change['pay']['id']=$order['pay_id'];
                $m_pay->pay_update($change['pay']);
                
            }
            unset($change['pay']);
        }
        //发票信息
        if(isset($change['invoice'])){
            $m_invoice=new OrdersInvoiceModel();
            if(empty($order['invoice_id'])){
                $change['invoice_id']=$m_invoice->invoice_add($change['invoice']);
            }else{
                $change['invoice']['id']=$order['invoice_id'];
                $m_invoice->invoice_update($change['invoice']);
                
            }
            unset($change['invoice']);
        }
        $update_info=['time'=>$time];
        
        foreach($change as $k=>$v){
            $update_info[$k]=$v;
        }
        //状态更新
        if(isset($change['is_real'])){
            $order['is_real']=$change['is_real'];
        }
        if(isset($change['status'])){
            $order['status']=$change['status'];
        }
        if(isset($change['pay_status'])){
            $order['pay_status']=$change['pay_status'];
        }
        if($order['status']==26 && $order['pay_status']==3){
            $update_info['status']=30;
        }
        
        $m_order->where('id',$order['id'])->update($update_info);
        //有子订单,同步
        if($order['is_real']==2 || isset($change['add']) ){
            //只有总订单才有的信息和总订单同步的信息
            $filed_child=array_merge($edit_fid0,$edit_accept);
            $update_child=['time'=>$time];
            
            foreach($filed_child as  $v){
                if(isset($update_info[$v])){
                    $update_child[$v]=$update_info[$v];
                }
            }
            if(isset($update_child['is_real'])){
                unset($update_child['is_real']);
            }
            if(isset($update_child['ok_break'])){
                unset($update_child['ok_break']);
            }
            if(!empty($update_child)){
                $m_order->where('fid',$order['id'])->update($update_child);
            }
        }
        //检查库存,删除旧出库，添加新出库
        if(!empty($instore_oids) && $order['status']>20 ){
            //有产品数量变化的
            $instore_oids=array_unique($instore_oids);
            foreach($instore_oids as $v){
                $res=$m_order->order_storein5($v);
                if($res!==1){
                    return $res;
                }
            }
            //新添加订单号
            $instore_add_oids=array_merge($instore_add_oids,$instore_oids);
            foreach($instore_add_oids as $v){
                $res=$m_order->order_storein0($v);
                if($res!==1){
                    return $res;
                }
            }
        }
        
        //通知各级发货
        ///$order
        //更新用户数据
        $m_order->custom_update($order['uid']);
        return 1;
    }
    /**
     * 审核订单状态编辑
     * @param array $order
     * @param array $change
     * @return number|string
     */
    public function m_status_edit_review($order,$change,$table='order')
    {
        $m_order=$this->m;
        //订单和采购单区分
        if($table=='order'){
            $otype=1;
            $ptype=1;
          
        }else{
            $otype=2;
            $ptype=2;
           
        }
         
        $time=time();
        
        //所有订单都有,都能修改
        $edit_base=['dsc', 'express_no'];
        //收货信息，状态信息，子订单可以单独修改，总订单修改后同步到子订单
        $edit_accept=['status'];
        
        //总订单信息系，子订单不能单独修改，总订单修改后同步到子订单
        $edit_fid0=['pay_status'];
         
        //依次处理change的信息，处理后unset
         
        $update_info=['time'=>$time];
        
        foreach($change as $k=>$v){
            $update_info[$k]=$v;
        }
        //状态更新
       
        if(isset($change['status'])){
            $order['status']=$change['status'];
        }
        if(isset($change['pay_status'])){
            $order['pay_status']=$change['pay_status'];
        }
        if($order['status']==26 && $order['pay_status']==3){
            $update_info['status']=30;
        }
        
        $m_order->where('id',$order['id'])->update($update_info);
        //有子订单,同步
        if($order['is_real']==2  ){
            //只有总订单才有的信息和总订单同步的信息
            $filed_child=array_merge($edit_fid0,$edit_accept);
            $update_child=['time'=>$time];
            
            foreach($filed_child as  $v){
                if(isset($update_info[$v])){
                    $update_child[$v]=$update_info[$v];
                }
            } 
            if(!empty($update_child)){
                $where_child=[
                    'fid'=>$order['id'],
                    'status'=>['elt',$order['status']]
                ];
                $m_order->where('fid',$order['id'])->update($update_child);
            }
        }
         
        //通知各级发货
        ///$order
        //更新用户数据
        $m_order->custom_update($order['uid']);
        return 1;
    }
}
