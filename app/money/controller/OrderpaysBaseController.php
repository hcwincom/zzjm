<?php
 
namespace app\money\controller;

use cmf\controller\AdminBaseController;
use think\Db; 
 
use app\money\model\OrderpaysModel;
use app\order\model\OrderModel;
use app\ordersup\model\OrdersupModel;
 /**
  * 订单和采购单结算
  */
class OrderpaysBaseController extends AdminBaseController
{
    protected $m;     
    protected $review_status;
    protected $table;
    
    protected $flag; 
    //用于详情页中识别当前店铺,
    //列表页中分店铺查询
    protected $where_shop; 
    /**
     * 用户，1订单
     */
    protected $utype;
    /**
     * 订单类型，1订单
     */
    protected $otype;
    /**
     * 收款方式，1收款2付款
     */
    protected $ptype;
    protected $uflag;
    protected $oflag;
    protected $utable;
    protected $otable; 
    /**
     * 订单产品
     */
    protected $ogtable; 
     
    public function _initialize()
    {
        parent::_initialize();
        $this->table='orderpays';
        
        //付款方式
        $this->assign('pay_types',config('pay_type'));
        $this->assign('pay_status',config('pay_status'));
        
        $this->review_status=config('review_status');
        $this->assign('review_status',$this->review_status);
        $this->m=new OrderpaysModel();
        $this->table='orderpays';
    }
    /**
     *  定期结账用户首页
     */
    public function index()
    {
        $utable=$this->utable;
        $m=$this->m;
        $admin=$this->admin;
        $data=$this->request->param();
        $where=['p.status'=>2];
        //客户还是供货商
        $tel_type=$this->utype;
        $where_shop=$admin['shop'];
        //店铺,分店只能看到自己的数据，总店可以选择店铺
        if($admin['shop']==1){
            if(empty($data['shop'])){
                $data['shop']=0;
                $where_shop=2;
            }else{
                $where['p.shop']=['eq',$data['shop']];
                $where_shop=$data['shop'];
            }
        }else{
            $where['p.shop']=['eq',$admin['shop']]; 
            
        }
        
        //是否需结算
        if(!isset($data['order_num0'])){
            $data['order_num0']=2;
        }
        //已结清
        if($data['order_num0']==1){
            $where['p.order_num0']=['eq',0];
        }elseif($data['order_num0']==2){
            $where['p.order_num0']=['gt',0];
        }
        $this->where_shop=$where_shop;
        //分类
        if(empty($data['cid'])){
            $data['cid']=0;
        }else{
            $where['p.cid']=['eq',$data['cid']];
        }
       /*  'pay_type' =>
        array (
            1 => '先付款后发货',
            2 => '货到付款',
            3 => '定期结算',
            4 => '其他',
        ), */
        //所属公司
        if(!isset($data['pay_type'])){
            $data['pay_type']=3;
        } 
        if($data['pay_type']>0){
            $where['p.pay_type']=['eq',$data['pay_type']];
        }
        //所属公司
        if(empty($data['company'])){
            $data['company']=0;
        }else{
            $where['p.company']=['eq',$data['company']];
        }
       
        //客户类型
        if(empty($data['cid'])){
            $data['cid']=0;
        }else{
            $where['p.cid']=['eq',$data['cid']];
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
            1=>['p.name','客户名称'],
            2=>['p.code','客户编码'],
            3=>['p.id','客户id'], 
        ];
        $search_types=config('search_types');
        $res=zz_search_param($types,$search_types,$data,$where);
        $data=$res['data'];
        $where=$res['where'];
       
         
        //先查询得到id再关联得到数据，否则sql查询太慢
        $list0=Db::name($utable)
        ->alias('p') 
        ->field('p.id') 
        ->where($where)  
        ->order('p.status asc,p.sort asc,p.time desc')
        ->paginate();
        $page = $list0->appends($data)->render();
        $ids=[];
       
        foreach($list0 as $k=>$v){
            $ids[$v['id']]=$v['id'];
        }
        
        if(empty($ids)){
            $list=[];
            $page=null;
        }else{
            //关联表
            $join=[
//                 ['cmf_user a','a.id=p.aid','left'],
//                 ['cmf_user r','r.id=p.rid','left'],
//                 ['cmf_shop shop','p.shop=shop.id','left'], 
          
            ];
            //a.user_nickname as aname,r.user_nickname as rname,shop.name as sname
            $field='p.*';
            $list=Db::name($utable)
            ->alias('p')  
            ->where('p.id','in',$ids)
            ->order('p.sort asc,p.time desc')
            ->column($field);
            
        }
      
        $this->assign('page',$page);
        $this->assign('list',$list);
        
        $this->assign('data',$data);
        $this->assign('types',$types);
     
        $this->assign("search_types", $search_types);
        //客户分类
        $cates=Db::name($utable.'_cate')->where('status',2)->order('sort asc')->column('id,name');
        $this->assign('cates',$cates);
        $where=[
            'shop'=>$where_shop,
            'status'=>2,
        ];
        //公司
        $companys=Db::name('company')->where($where)->order('sort asc')->column('id,name');
        $this->assign('companys',$companys);
        //付款方式
        $this->assign('pay_types',config('pay_type'));
        //店铺
        if($admin['shop']==1){
            $shops=Db::name('shop')->where('status',2)->order('sort asc')->column('id,name');
            $this->assign('shops',$shops);
        }
    } 
    /**
     * 客户订单页面
     */
    public function orders()
    {
        $utable=$this->utable;
        $otable=$this->otable;
        $uflag=$this->uflag;
        $m=$this->m;
        $admin=$this->admin;
        $data=$this->request->param();
       
        if(empty($data['uid'])){
            $this->error('没有选择结算'.$uflag);
        }
        $data['uid']=intval($data['uid']);
        $custom=Db::name($utable)->field('id,name,code,shop')->where('id',$data['uid'])->find();
        if(empty($custom)){
            $this->error('没有选择结算'.$uflag);
        }
        //客户还是供货商
        $tel_type=$this->utype;
      
        $where=[
            'p.uid'=>$data['uid'],
            'p.status'=>['between',[26,30]]
            
        ];
        $this->where_shop=$custom['shop'];
        $where_shop=$custom['shop'];
       
        //所属公司
        if(empty($data['pay_type'])){
            $data['pay_type']=0;
        }else{
            $where['p.pay_type']=['eq',$data['pay_type']];
        }
        //所属公司
        if(empty($data['company'])){
            $data['company']=0;
        }else{
            $where['p.company']=['eq',$data['company']];
        }
        //支付状态
        if(empty($data['pay_status'])){
            $data['pay_status']=0;
        }else{
            $where['p.pay_status']=['eq',$data['pay_status']];
        }
         
        //类型
        if(empty($data['type'])){
            $data['type']=0;
        }else{
            $where['p.type']=['eq',$data['type']];
        }
        //查询字段
        $types=[
            1=>['p.name','订单号'],
            2=>['p.id','订单id'],
        ]; 
        $search_types=config('search_types');
        $res=zz_search_param($types,$search_types,$data,$where);
        $data=$res['data'];
        $where=$res['where'];
        
        //时间类别
        $times=config('order_time'); 
        $res=zz_search_time($times,$data,$where);
        if(is_array($res)){
            $data=$res['data'];
            $where=$res['where'];
        }else{
            $this->error($res);
        }
        
         
        //查询字段，商品查询暂时不要
        $types_goods=[ 
            'goods.goods_name|goods.print_name|goods.goods_uname'=>'产品名称',
            'goods.goods_code'=>'产品编码',
            'goods.goods'=>'产品id',
        ];
        
        $ogtable=$this->ogtable;
        //先查询得到id再关联得到数据，否则sql查询太慢
        $list0=Db::name($otable)
        ->alias('p')
        ->field('p.id') 
        ->where($where)
        ->order('p.id desc')
        ->paginate();
        
        $page = $list0->appends($data)->render();
        $ids=[]; 
        foreach($list0 as $k=>$v){
            $ids[$v['id']]=$v['id'];
        } 
        if(empty($ids)){
            $list=[]; 
        }else{
            
            //a.user_nickname as aname,r.user_nickname as rname,shop.name as sname
            $field='p.*';
            $list=Db::name($otable)
            ->alias('p') 
            ->where('p.id','in',$ids)
            ->order('p.sort asc,p.time desc')
            ->column($field);
            
        }
        
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('custom',$custom);
        
        $this->assign('data',$data);
        $this->assign('types',$types);
        $this->assign('times',$times);
        $this->assign("search_types", $search_types);
        
        //公司
        $where=[
            'status'=>2,
            'shop'=>$where_shop,
        ];
        $companys=Db::name('company')->where($where)->order('sort asc')->column('id,name');
        $this->assign('companys',$companys);
       
        
    } 
    /**
     * 订单结算页面
     */
    public function add()
    { 
         if(empty($_POST['ids'])){
             $this->error('没有选择订单');
         }
         $oids=$_POST['ids'];
         $otable=$this->otable;
         $utable=$this->utable;
         $ogtable=$this->ogtable;
         $utype=$this->utype;
         $m=$this->m;
         $uid=$this->request->param('uid',0,'intval');
         //得到相关信息
         $res=$m->pays_addinfo($uid,$utable,$utype,$oids,$otable,$ogtable);
         if(is_array($res)){
             $custom=$res['custom'];
             $orders=$res['orders'];
             $accounts=$res['accounts'];
         }else{
             $this->error($res);
         } 
        //统计订单结算数量和金额
         $money=0;
         $count=0;
         $this->where_shop=$custom['shop'];
         //不是此用户或不是已收货待收款的去掉
         foreach($orders as $k=>$v){
             if($v['uid']!=$uid || $v['pay_status']==3 || $v['status']!=26){
                 unset($orders[$k]);
             }else{
                 $count++;
                 $money=bcadd($money,$v['order_amount'],2);
                 $orders[$k]['money']=$v['order_amount'];
             }
         }
         if($count==0){ 
             $this->error('没有待结算订单');
         } 
         $this->cates();
         $this->assign('money',$money); 
         $this->assign('count',$count);
         $this->assign('orders',$orders);
         $this->assign('custom',$custom);
         $this->assign('accounts',$accounts); 
         $this->assign('change',null);
         $this->assign('pay',null);
         $this->assign('info',null);
        
    }
   //
    public function add_do()
    {
        $m=$this->m;
        $utable=$this->utable;
        $otable=$this->otable;
        $oflag=$this->oflag;
        $utype=$this->utype;
        $otype=$this->otype;
        $ptype=$this->ptype;
        $data=$this->request->param();
        if(empty($data['account_name1'])){
            $this->error('账号信息未填写');
        }
        
        $url=url('orderpays');
        
        $table=$this->table;
        $time=time();
        $admin=$this->admin;
        //用用户shop检测
        $uid=intval($data['uid']);
        $custom=Db::name($utable)->where('id',$uid)->find();
        if($admin['shop']>1 && $admin['shop']!=$custom['shop']){
            $this->error('店铺数据错误');
        }
        //订单状态检测
        $where_order=[ 
            'id'=>['in',$data['oids']], 
        ];
        $orders=Db::name($otable)->where($where_order)->column('id,name,order_amount,pay_status,uid,status');
        
        //统计订单结算数量和金额
        $money=0;
        $count=0; 
        //不是此用户或不是已收货待收款的去掉
        foreach($orders as $k=>$v){
            if($v['uid']!=$uid || $v['pay_status']==3 || $v['status']!=26){
                $this->error($oflag.$v['name'].'不可结算!');
            }else{
                $count++;
                $money=bcadd($money,$v['order_amount'],2); 
            }
        }
        if($count==0){
            $this->error('没有待结算订单');
        } 
       
        $data_add=[
            'name'=>order_sn($admin['id']),
            'type'=>$otype,
            'uid'=>$uid,
            'shop'=>$custom['shop'],
            'num'=>intval($data['num']), 
            'money'=>round($data['money'],2),
            'money0'=>round($data['money0'],2),
            'aid'=>$admin['id'],
            'atime'=>$time, 
            'adsc'=>$data['adsc'],
            'rstatus'=>1
        ];
       
        if($data_add['num'] != $count || $data_add['money0'] != $money){
            $this->error('结算数据有错误,请重新提交');
        }
       
        $m->startTrans();
        $id=$m->insertGetId($data_add);
        //添加结算记录
        $data_oid=[];
        foreach($orders as $k=>$v){
            $data_oid[]=[
                'pid'=>$id,
                'oid'=>$k,
                'money'=>$v['order_amount']
            ];
        }
        Db::name('orderpays_oid')->insertAll($data_oid);
        //添加支付关联 
        $data_pay=[
            'oid'=>$id,
            'oid_type'=>$otype,
            'pay_type'=>$ptype, 
            'status'=>1,
            'money'=>$money,
            'bank1'=>$data['account_bank1'],
            'name1'=>$data['account_name1'],
            'num1'=>$data['account_num1'],
            'location1'=>$data['account_location1'],
            'paytype2'=>$data['paytype2'], 
        ]; 
        Db::name('orderpays_pay')->insert($data_pay);
        
        //记录操作记录
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'添加'.($this->flag).$id.'-'.$data_add['name'],
            'table'=>($this->table),
            'type'=>'add',
            'pid'=>$id,
            'link'=>url('edit',['id'=>$id]),
            'shop'=>$admin['shop'],
            
        ];
        zz_action($data_action,['department'=>$admin['department']]); 
        $m->commit();
        //财务数据暂不添加自动审核
        $this->success('添加成功',$url);
        
    }
    /**
     *结算详情
     */
    public function edit()
    {
        $id=$this->request->param('id',0,'intval');
        $otable=$this->otable;
        $utable=$this->utable;
        $ogtable=$this->ogtable; 
        $utype=$this->utype;
        $m=$this->m;
       
        //得到相关信息 
        $res=$m->pays_info($id,$utable,$utype,$otable,$ogtable);
        if(is_array($res)){
            $custom=$res['custom'];
            $orders=$res['orders'];
            $accounts=$res['accounts'];
            $info=$res['info'];
            $pay=$res['pay'];
        }else{
            $this->error($res);
        } 
        
        $this->where_shop=$custom['shop'];
        $this->cates();
        $this->assign('orders',$orders);
        $this->assign('custom',$custom);
        $this->assign('accounts',$accounts);
        $this->assign('info',$info);
        $this->assign('pay',$pay);
      
        $this->assign('change',null);  
    }
    /**
     * 客户状态审核
     */
    public function review()
    {
        $rstatus=$this->request->param('res',0,'intval');
        $id=$this->request->param('id',0,'intval');
        if($rstatus!=2 && $rstatus!=3){
            $this->error('操作错误');
        }
        $rdsc=$this->request->param('rdsc','');
        $review_status=$this->review_status;
        $rdsc=empty($rdsc)?$review_status[$rstatus]:$rdsc;
       
        
        $m=$this->m;
        $admin=$this->admin;
        $otable=$this->otable;
        $oflag=$this->oflag;
        //得到关联的订单
        $res=$m->pays_order($id,$otable);
        if(is_array($res)){ 
            $orders=$res['orders']; 
            $info=$res['info'];  
        }else{
            $this->error($res);
        } 
        //不是此用户或不是已收货待收款的去掉
        foreach($orders as $k=>$v){
            if($v['pay_status']==3 || $v['status']!=26){
                $this->error($oflag.$v['name'].'不可结算!');
            } 
        }
        $time=time();
        
        $m->startTrans();
      
        $update=[
            'rid'=>$admin['id'],
            'rtime'=>$time,
            'rstatus'=>$rstatus, 
            'rdsc'=>$rdsc,
        ];
        
        //只有未审核的才能更新
        $where=[
            'id'=>$id,
            'rstatus'=>1,
        ];
        $row=$m->where($where)->update($update);
        if($row!==1){
            $m->rollback();
            $this->error('审核失败，请刷新后重试');
        }
        //是否更新,2同意，3不同意
        if($rstatus==2){
            //审核通过要更改订单的支付数据,支付完成，订单完成
            $update_order=[
                'pay_status'=>3,
                'status'=>30,
                'sort'=>0,
            ];
            $oids=array_keys($orders);
            if($otable=='order'){
                $m_order=new OrderModel();
            }else{
                $m_order=new OrdersupModel();
            } 
            $row=$m_order->where('id','in',$oids)->update($update_order);
            if(!($row>0)){
                $m->rollback();
                $this->error('信息更新失败，请刷新后重试');
            }
           //更新用户订购金额
            $m_order->custom_update($info['uid']);
            
        }
        
        //审核成功，记录操作记录,发送审核信息 
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'审核'.$info['aid'].'-'.$info['aname'].'提交的'.($this->flag).$info['id'].'-'.$info['name'].'为'.$review_status[$rstatus],
            'table'=>($this->table),
            'type'=>'review',
            'pid'=>$info['id'],
            'link'=>url('edit',['id'=>$info['id']]),
            'shop'=>$admin['shop'],
        ];
        
        zz_action($data_action,['aid'=>$info['aid']]);
        
        $m->commit();
        $this->success('审核成功');
    }
    
     
    
    /**
     *  定期结账记录
     */
    public function orderpays()
    {
        $utable=$this->utable;
        $uflag=$this->uflag;
        $utype=$this->utype;
        $m=$this->m;
        $admin=$this->admin;
        $data=$this->request->param();
        $where=['type'=>$utype];
        //客户还是供货商
        $tel_type=$this->utype;
        $where_shop=$admin['shop'];
        //店铺,分店只能看到自己的数据，总店可以选择店铺
        if($admin['shop']==1){
            if(empty($data['shop'])){
                $data['shop']=0;
                $where_shop=2;
            }else{
                $where['p.shop']=['eq',$data['shop']];
                $where_shop=$data['shop'];
            }
        }else{
            $where['p.shop']=['eq',$admin['shop']]; 
        }
        $this->where_shop=$where_shop;
        //结算对象
        if(empty($data['uid'])){
            $data['uid']=0;
        }else{
            $where['p.uid']=['eq',$data['uid']];
        }
        //状态
        if(empty($data['rstatus'])){
            $data['rstatus']=0;
        }else{
            $where['p.rstatus']=['eq',$data['rstatus']];
        }
        //查询字段
        $types=[
            1=>['p.name','结算单号'],
            2=>['p.id','结算id'], 
            3=>['custom.name',$uflag.'名称'],
            4=>['custom.code',$uflag.'编码'], 
            5=>['custom.id',$uflag.'id'], 
        ]; 
        $search_types=config('search_types');
        $res=zz_search_param($types,$search_types,$data,$where); 
        $data=$res['data'];
        $where=$res['where'];
         
        //时间类别
        $times= [ 
            1=>['p.atime','结算申请时间'],
            2=>['p.rtime','结算审核时间'],
            
        ];
        $res=zz_search_time($times,$data,$where);
        if(is_array($res)){
            $data=$res['data'];
            $where=$res['where'];
        }else{
            $this->error($res);
        }
         
        //先查询得到id再关联得到数据，否则sql查询太慢
        $join=[
            ['cmf_'.$utable.' custom','custom.id=p.uid','left'],
        ];
        $list0=$m
        ->alias('p')
        ->field('p.id') 
        ->join($join)
        ->where($where)
        ->order('p.rstatus asc,p.id desc')
        ->paginate();
        $page = $list0->appends($data)->render();
        $ids=[];
        
        foreach($list0 as $k=>$v){
            $ids[$v['id']]=$v['id'];
        }
        
        if(empty($ids)){
            $list=[];
            $page=null;
        }else{
            //关联表
            $join=[
                ['cmf_'.$utable.' custom','custom.id=p.uid','left'],
            ];
             
            $field='p.*,custom.name as uname,custom.code as ucode';
            $list=$m
            ->alias('p')
            ->join($join)
            ->where('p.id','in',$ids)
            ->order('p.rstatus asc,p.id desc')
            ->column($field);
            
        }
        
        $this->assign('page',$page);
        $this->assign('list',$list);
        
        $this->assign('data',$data);
        $this->assign('types',$types);
        $this->assign('times',$times);
        $this->assign("search_types", $search_types);
        $this->assign("purl", url('edit','',false,false));
        
        //店铺
        if($admin['shop']==1){
            $shops=Db::name('shop')->where('status',2)->order('sort asc')->column('id,name');
           
            $this->assign('shops',$shops);
        }
    } 
    //
    public function cates(){
        $banks=Db::name('bank')->where('status',2)->column('id,name');
        $this->assign('banks',$banks);
        $shop=$this->where_shop;
        $where=[
            'shop'=>$shop,
            'status'=>2,
        ];
        $paytypes=Db::name('paytype')->order('sort asc')->where($where)->column('id,name');
        $this->assign('paytypes',$paytypes);
    }
}
