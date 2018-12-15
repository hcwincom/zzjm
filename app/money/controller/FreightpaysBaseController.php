<?php
 
namespace app\money\controller;

use cmf\controller\AdminBaseController;
use think\Db; 
 
use app\money\model\FreightpaysModel;
use app\order\model\OrderModel;
use app\ordersup\model\OrdersupModel;
 /**
  *订单发货和售后发货
  */
class FreightpaysBaseController extends AdminBaseController
{
    protected $m;     
    protected $review_status;
    protected $table;
    
    protected $flag; 
    //用于详情页中识别当前店铺,
    //列表页中分店铺查询
    protected $where_shop; 
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
    protected $ogtable; 
   
    
    public function _initialize()
    {
        parent::_initialize();
        $this->table='freightpays';
        $this->uflag='合作物流';
        $this->utable='freight'; 
       
        $this->assign('uflag',$this->uflag);
        
        //付款方式
        $this->assign('pay_types',config('pay_type'));
        $this->assign('pay_status',config('pay_status'));
        $this->assign('freight_pay_status',config('freight_pay_status'));
        
        $this->review_status=config('review_status');
        $this->assign('review_status',$this->review_status);
        $this->assign('uurl',url('express/AdminFreight/edit','',false,false)); 
        $this->assign('eurl',url('express/AdminFreight/express_query','',false,false)); 
        $this->m=new FreightpaysModel();
       
    }
    /**
     *  定期结账物流首页
     */
    public function index()
    {
        $utable=$this->utable;
        $m=$this->m;
        $admin=$this->admin;
        $data=$this->request->param();
        $where=[
            'p.status'=>2, 
        ];
       
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
        //支付方式
        if(!isset($data['pay_type'])){
            $data['pay_type']=3;
        } 
        if($data['pay_type']>0){
            $where['p.pay_type']=['eq',$data['pay_type']];
        }
         
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
       
        if(empty($data['freight'])){
            $this->error('没有选择结算'.$uflag);
        }
        $data['freight']=intval($data['freight']);
        $freight=Db::name($utable)->where('id',$data['freight'])->find();
        
        if(empty($freight)){
            $this->error('没有选择结算'.$uflag);
        }
        
        $where=[
            'p.freight'=>$data['freight'],
            'p.status'=>['between',[24,70]]
            
        ];
        $this->where_shop=$freight['shop'];
        $where_shop=$freight['shop'];
       
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
        //支付状态
        if(empty($data['freight_pay_status'])){
            $data['freight_pay_status']=0;
        }else{
            $where['p.freight_pay_status']=['eq',$data['freight_pay_status']];
        }
        
        //支付方式
        if(empty($data['pay_type'])){
            $data['pay_type']=0;
        }else{
            $where['p.pay_type']=['eq',$data['pay_type']];
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
        $this->assign('freight',$freight);
        
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
        
         $m=$this->m;
         $freight_id=$this->request->param('freight',0,'intval');
         //得到相关信息
         $res=$m->pays_addinfo($freight_id,$utable,$oids,$otable,$ogtable);
         if(is_array($res)){
             $freight=$res['freight'];
             $orders=$res['orders'];
             $accounts=$res['accounts'];
         }else{
             $this->error($res);
         } 
        //统计订单结算数量和金额
         $money=0;
         $count=0;
         //不是此用户或不是已收货待收款的去掉
          
         foreach($orders as $k=>$v){
             if($v['freight']!=$freight_id || $v['is_freight_pay']==3 || empty('express_no')){
                 unset($orders[$k]);
             }else{
                 $count++;
                 $money=bcadd($money,$v['real_freight'],2);
                 $orders[$k]['money']=$v['real_freight'];
             }
         }
        
         if($count==0){ 
             $this->error('没有待结算订单');
         } 
         //付款银行
         $banks=Db::name('bank')->where('status',2)->column('id,name');
         $where=[
             'status'=>2,
             'shop'=>$freight['shop'],
         ];
         
         $paytypes=Db::name('paytype')->where($where)->order('sort asc')->column('id,name,bank,location,num');
         $this->assign('paytypes',$paytypes);
         
         $this->assign('money',$money);
         $this->assign('count',$count);
         $this->assign('orders',$orders);
         $this->assign('freight',$freight);
         $this->assign('accounts',$accounts);
         $this->assign('banks',$banks);
        
         $this->assign('change',null);
         $this->assign('pay',null);
         $this->assign('info',null);
        
    }
   //
    public function add_do()
    {
        $m=$this->m;
        $utable=$this->utable;
        $table=$this->table;
        $otable=$this->otable;
        $oflag=$this->oflag;
        $otype=$this->otype;
        $ptype=$this->ptype;
        $data=$this->request->param();
        $freight_id=intval($data['freight']);
        $url=url('orderpays');
        $url_error=url('orders',['freight'=>$freight_id]);
        if(empty($data['account_name1'])){
            $this->error('账号信息未填写',$url_error);
        } 
       
        $table=$this->table;
        $time=time();
        $admin=$this->admin;
        //用用户shop检测
        
        $freight=Db::name($utable)->where('id',$freight_id)->find();
        if($admin['shop']>1 && $admin['shop']!=$freight['shop']){
            $this->error('店铺数据错误',$url_error);
        }
        //订单状态检测
        $where_order=[ 
            'id'=>['in',$data['oids']], 
            'is_real'=>1,
        ];
        $orders=Db::name($otable)->where($where_order)->column('id,name,express_no,real_freight,is_freight_pay,freight,status');
        
        //统计订单结算数量和金额
        $money=0;
        $count=0; 
        //不是此物流或不是已付款的去掉或没有单号的去掉
        foreach($orders as $k=>$v){
            if($v['freight']!=$freight_id || $v['is_freight_pay']==3 || empty($v['express_no'])){
                $this->error($oflag.$v['name'].'运费不可结算!',$url_error);
            }else{
                $count++;
                $money=bcadd($money,$v['real_freight'],2); 
            }
        }
        if($count==0){
            $this->error('没有待结算订单',$url_error);
        } 
       
        $data_add=[
            'name'=>order_sn($admin['id']),
            'type'=>$otype,
            'freight'=>$freight_id,
            'shop'=>$freight['shop'],
            'num'=>intval($data['num']), 
            'money'=>round($data['money'],2),
            'money0'=>round($data['money0'],2),
            'aid'=>$admin['id'],
            'atime'=>$time, 
            'adsc'=>$data['adsc'],
            'rstatus'=>1
        ];
       
        if($data_add['num'] != $count || $data_add['money0'] != $money){
            $this->error('结算数据有错误,请重新提交',$url_error);
        }
       
        $m->startTrans();
        $id=$m->insertGetId($data_add);
        //添加结算记录
        $data_oid=[];
        foreach($orders as $k=>$v){
            $data_oid[]=[
                'pid'=>$id,
                'oid'=>$k,
                'money'=>$v['real_freight']
            ];
        }
        Db::name($table.'_oid')->insertAll($data_oid);
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
            'bank2'=>$data['account_bank2'],
            'name2'=>$data['account_name2'],
            'num2'=>$data['account_num2'],
            'location2'=>$data['account_location2'],
        ]; 
        Db::name($table.'_pay')->insert($data_pay);
        
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
        $otype=$this->otype;
        $m=$this->m;
       
        //得到相关信息 
        $res=$m->pays_info($id,$utable,$otable,$ogtable);
        if(is_array($res)){
            $freight=$res['freight'];
            $orders=$res['orders'];
            $accounts=$res['accounts'];
            $info=$res['info'];
            $pay=$res['pay'];
        }else{
            $this->error($res);
        } 
        
        $banks=Db::name('bank')->where('status',2)->column('id,name');
        $where=[
            'shop'=>$freight['shop'],
            'status'=>2,
        ];
        $paytypes=Db::name('paytype')->where($where)->order('sort asc')->column('id,name,bank,location,num');
        $this->assign('paytypes',$paytypes);
        $this->assign('orders',$orders);
        $this->assign('freight',$freight);
        $this->assign('accounts',$accounts);
        $this->assign('info',$info);
        $this->assign('pay',$pay);
        $this->assign('banks',$banks);
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
        //不是此物流或不是已付款的去掉或没有单号的去掉
        foreach($orders as $k=>$v){
            if($v['freight']!=$info['freight'] || $v['is_freight_pay']==3 || empty($v['express_no'])){
                $this->error($oflag.$v['name'].'运费不可结算!');
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
                'is_freight_pay'=>3, 
                'time'=>$time,
            ];
            $oids=array_keys($orders);
            
            $row=Db::name($otable)->where('id','in',$oids)->update($update_order);
            if(!($row>0)){
                $m->rollback();
                $this->error('信息更新失败，请刷新后重试');
            }
           //更新物流结算费用
            $m->freight_update($info['freight'],$info['money'],$info['num']);
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
        $otype=$this->otype;
        $m=$this->m;
        $admin=$this->admin;
        $data=$this->request->param();
        $where=['type'=>$otype];
        
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
        if(empty($data['freight'])){
            $data['freight']=0;
        }else{
            $where['p.freight']=['eq',$data['freight']];
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
            3=>['freight.name',$uflag.'名称'],
            4=>['freight.code',$uflag.'编码'], 
            5=>['freight.id',$uflag.'id'], 
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
            ['cmf_'.$utable.' freight','freight.id=p.freight','left'],
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
                ['cmf_'.$utable.' freight','freight.id=p.freight','left'],
            ];
             
            $field='p.*,freight.name as uname,freight.code as ucode';
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
    /**
     * 更新物流费用
     */
    public function freight_update()
    {
        $admin=$this->admin;
        $utable=$this->utable;
        $shop=($admin['shop']==1)?2:$admin['shop'];
        $where=[
            'shop'=>$shop,
            'status'=>2,
        ];
        $m=$this->m;
        $freights=Db::name($utable)->where($where)->column('id');
        //更新物流结算费用
        foreach($freights as $v){
            $m->freight_update($v);
        }
        $this->redirect(url('index'));
        
    }
}
