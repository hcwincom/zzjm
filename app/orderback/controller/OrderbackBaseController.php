<?php
 
namespace app\orderback\controller;

 
use think\Db; 
use app\orderback\model\OrderbackModel;
use app\common\controller\AdminInfo0Controller; 
class OrderbackBaseController extends AdminInfo0Controller
{
    protected $order_type; 
    public function _initialize()
    {
        parent::_initialize();
        //没有店铺区分
        $this->isshop=1;
     
        $this->table='orderback';
        $this->m=new OrderbackModel();
       
       
        $this->assign('table',$this->table);
         
    }
    /**
     * 客户售后列表 
     */
    public function index()
    {
        $order_type=$this->order_type;
        $table=$this->table;
        $m=$this->m;
        $admin=$this->admin;
        $data=$this->request->param();
        $where=[
            'p.order_type'=>$order_type
        ]; 
        //店铺,分店只能看到自己的数据，总店可以选择店铺
        if($admin['shop']==1){
            if(empty($data['shop'])){
                $data['shop']=0;
                $this->where_shop=2;
            }else{
                $this->where_shop=$data['shop']; 
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
        //状态
        if(empty($data['company'])){
            $data['company']=0;
        }else{
            $where['p.company']=['eq',$data['company']];
        }
        
        //添加人
        if(empty($data['aid'])){
            $data['aid']=0;
        }else{
            $where['p.aid']=['eq',$data['aid']];
        }
        
         
        //查询字段
        if($order_type==1){
            $types=[
                'p.name'=>'售后编号',
                'p.about_name'=>'订单编号',
                'p.about'=>'订单id',
                'p.express_no1'=>'收货运单号',
                'p.express_no1'=>'发货运单号',
                'p.uname'=>'客户名称',
                'p.accept_name'=>'联系人',
                'p.mobile'=>'联系手机',
                'p.phone'=>'联系电话',
                
            ];
        
        }else{
            $types=[
                'p.name'=>'售后编号',
                'p.about_name'=>'采购编号',
                'p.about'=>'采购id',
                'p.express_no1'=>'发货运单号',
                'p.express_no1'=>'收货运单号',
                'p.uname'=>'供应商名称',
                'p.accept_name'=>'联系人',
                'p.mobile'=>'联系手机',
                'p.phone'=>'联系电话',
            ];
        }
        
         
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
        $times=[
            'atime' => '创建时间',
            'rtime' => '确认时间',
            'time' => '更新时间',
        ];
        if(empty($data['time'])){
            $data['time']=key($times);
            $data['datetime1']=date('Y-m-d H:i',time()-3600*24*30);
            $data['datetime2']=date('Y-m-d H:i');
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
        
        //关联表
        $join=[
            
        ];
        $field='p.*';
        $list=$m
        ->alias('p')
        ->field('p.id')  
        ->where($where)
        ->order('p.time desc')
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
            
            $field='p.*,company.name as company_name';
            $join=[
                ['cmf_company company','company.id=p.company','left'],
                
            ];
            $list=$m
            ->alias('p') 
            ->join($join)
            ->where('p.id','in',$ids)
            ->order('p.time desc')
            ->column($field); 
            $goods=Db::name('orderback_goods')->where('oid','in',$ids)
            ->column('id,oid,goods,goods_name,goods_code,goods_pic,price_real,num,pay');
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
     * 订单售后添加
     * (
     *     'name'   => '订单售后添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单售后添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
       
        $admin=$this->admin;
        $shop=($admin['shop']==1)?2:$admin['shop'];
        $this->where_shop=$shop;
       
        $oid=$this->request->param('oid',0,'intval');
       
        $order_type=$this->order_type;
        $order=null;
        $custom=null;
        $goods=null;
        $infos=null;
        if($oid>0){
            if($order_type==1){
                $m_order=Db::name('order');
                $m_custom=Db::name('custom');
                $m_ogoods=Db::name('order_goods');
               
            }else{
                $m_order=Db::name('ordersup');
                $m_custom=Db::name('supplier');
                $m_ogoods=Db::name('ordersup_goods');
                
            }
            $where_order=[
                'id'=>$oid,
                'shop'=>$shop, 
            ];
            $order=$m_order->where($where_order)->find();
            if(empty($order)){
                $this->error('未找到订单');
            }
            if($order['is_real']!=1 || $order['status']<26 || $order['status']>30){
//                 $this->error('订单不可申请售后');
            }
            if(!empty($order['uid'])){
                $where_custom=[
                    'id'=>$order['uid'],
                    'shop'=>$shop
                ];
                $custom=$m_custom->where($where_custom)->find();
                
            } 
            $infos=$m_ogoods->where('oid',$oid)->column('*','goods');
          
            $m=$this->m;
            $infos=$m->orderback_goods($infos,$admin['id'],$shop);
          
        }
        
        $this->assign('info',null); 
       
        $this->assign('infos',$infos); 
        $this->assign('custom',$custom); 
        $this->assign('order',$order); 
        $this->assign('order_type',$order_type); 
        $this->cates();
        return $this->fetch();   
    }
    
    /**
     * 订单售后添加do
     * (
     *     'name'   => '订单售后添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单售后添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        $data=$this->request->param();
        
        $order_type=intval($data['order_type']);
        $data['about']=intval($data['about']);
        
        if($order_type==1){
            $m_order=Db::name('order'); 
            $m_ogoods=Db::name('order_goods');
        }else{
            $m_order=Db::name('ordersup'); 
            $m_ogoods=Db::name('ordersup_goods');
        }
        $admin=$this->admin;
        $where_order=[];
        if($admin['shop']>1){
            $where_order['shop']=$admin['shop'];
        }
        if(empty( $data['about'])){
            $where_order['name']=$data['about_name'];
        }else{
            $where_order['id']=$data['about'];
        } 
        $order=$m_order->where($where_order)->find();
        if(empty($order)){
            $this->error('未找到订单');
        }
        if($order['is_real']!=1 || $order['status']<26){
            $this->error('订单不可申请售后');
        }
       
        //店铺
        $shop=$order['shop'];
        $this->where_shop=$shop;
         //原订单产品
        $infos=$m_ogoods->where('oid',$data['about'])->column('*','goods');
         
        $admin=$this->admin;
        $time=time();
        $data_orderback=[
            'name'=>date('Ymd').substr($time,-6).$admin['id'],
            'aid'=>$admin['id'], 
            'atime'=>$time,
            'create_time'=>$time, 
            'time'=>$time,
            'type'=>intval($data['type']),
            'order_type'=>$order_type,
            'shop'=>$order['shop'], 
            'company'=>$order['company'], 
            'uid'=>$order['uid'], 
            'uname'=>$data['uname'],
            'about'=>$order['id'],
            'about_name'=>$order['name'], 
            'pics'=>'',
            'files'=>'',
            
        ]; 
        $fields_int=['store1','store2','express1','express2','province','city','area','freight','pay_type'];
        foreach($fields_int as $v){
            $data_orderback[$v]=intval($data[$v]);
        }
        $fields_round=['goods_money','back_money','weight','size','real_freight','pay_freight'];
        foreach($fields_round as $v){
            $data_orderback[$v]=round($data[$v],2);
        }
        $fields_str=['express_no1','express_no2','postcode','accept_name','mobile','phone','address','addressinfo'];
        foreach($fields_str as $v){
            $data_orderback[$v]=$data[$v];
        }
      
        $m=$this->m;
        $m_info=Db::name('orderback_goods');
        $m->startTrans();
        $oid= $m->insertGetId($data_orderback);
        $data_goods=[];
        
        //产品数据
        if(!empty($data['goods_ids'])){ 
           
            foreach($data['goods_ids'] as $k=>$v){
                $data_goods[]=[
                    'oid'=>$oid,
                    'goods'=>$v,
                    'goods_name'=>$infos[$v]['goods_name'],
                    'print_name'=>$infos[$v]['print_name'],
                    'goods_uname'=>$infos[$v]['goods_uname'],
                    'goods_ucate'=>$infos[$v]['goods_ucate'],
                    'goods_code'=>$infos[$v]['goods_code'],
                    'goods_pic'=>$infos[$v]['goods_pic'],
                    'price_real'=>$infos[$v]['price_real'],
                    'price_sale'=>$infos[$v]['price_sale'],
                    'num'=>$data['nums'][$k],
                    'pay'=>$data['pays'][$k],
                    'dsc'=>$data['dscs'][$k], 
                ]; 
            }
            $m_info->insertAll($data_goods);
        }
         
        $data_orderback['id']=$oid;
        $pics= $m->pic_do($data_orderback,$data,[]);
        if(!empty($pics)){
            $m->where('id',$oid)->update($pics);
        }
        $m_order->where('id',$data_orderback['about'])->update(['is_back'=>1]);
        $m->commit();
        $this->success('提交成功',url('edit',['id'=>$oid]));
    }
    /**
     * 订单售后详情
     * (
     *     'name'   => '订单售后详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单售后详情',
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
        if(!empty($info['pics'])){
            $info['pics']=json_decode($info['pics'],true);
        }
        if(!empty($info['files'])){
            $info['files']=json_decode($info['files'],true);
        }
        
        $shop=$info['shop'];
        if($admin['shop']>1 && $admin['shop']!=$shop){
            $this->error('只能查看本店铺的数据');
        }
        $this->where_shop=$shop;
        
        $ok_add=($info['status']<2)?1:2;
         
        //订单售后产品
        $infos=Db::name('orderback_goods')->where('oid',$id)->column('*','goods');
        $infos=$m->orderback_goods($infos,$admin['id'],$shop);
        //原订单 
        if($info['order_type']==1){
            $m_order=Db::name('order'); 
        }else{
            $m_order=Db::name('ordersup'); 
        }
        $where_order=[
            'id'=>$info['about'],
            'shop'=>$info['shop'],
            'is_real'=>1,
        ];
        $order=$m_order->where($where_order)->find();
        if(empty($order)){
            $order=null; 
        }
     
        $this->cates();
        $this->assign('infos',$infos); 
        
        $this->assign('info',$info); 
        $this->assign('order',$order); 
        $this->assign('ok_add',$ok_add); 
        
        return $this->fetch();  
    }
    /**
     * 订单售后编辑
     * (
     *     'name'   => '订单售后编辑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '订单售后编辑',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        $m=$this->m;
        $table=$this->table;
        $flag=$this->flag;
        $data=$this->request->param();
        if(empty($data['custom_sex'])){
            $data['custom_sex']=1;
        }
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
        $res=$m->orderback_edit_auth($info,$admin);
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
       
        $content=$m->orderback_edit($info, $data);
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
        //直接审核
        $rule='edit_review';
        $res=$this->check_review($admin,$rule);
        if($res){
            $this->redirect($rule,['id'=>$eid,'rstatus'=>2,'rdsc'=>'直接审核']);
        }
        $this->success('已提交修改');
    }
    
    /**
     * 订单售后编辑列表
     * (
     *     'name'   => '订单售后编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '订单售后编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list()
    {
        parent::edit_list();
        return $this->fetch();
    }
    /**
     * 订单售后编辑审核页面
     * (
     *     'name'   => ' 订单售后编辑审核页面',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '订单售后编辑审核页面',
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
        if(!empty($info['pics'])){
            $info['pics']=json_decode($info['pics'],true);
        }
        if(!empty($info['files'])){
            $info['files']=json_decode($info['files'],true);
        }
        
        //获取改变的信息
        $change=Db::name('edit_info')->where('eid',$id)->value('content');
        $change=json_decode($change,true);
        if(!empty($change['pics'])){
            $change['pics']=json_decode($change['pics'],true);
           
        }
        if(!empty($change['files'])){
            $change['files']=json_decode($change['files'],true);
            
        }
       
        $this->where_shop=$info['shop'];
      
        $id=$info['id'];
        $shop=$info['shop'];
        $admin=$this->admin;
        if($admin['shop']>1 && $admin['shop']!=$shop){
            $this->error('只能查看本店铺的数据');
        }
        $this->where_shop=$shop;
        
        //订单售后产品
        $infos=Db::name('orderback_goods')->where('oid',$id)->column('*','goods');
        $infos=$m->orderback_goods($infos,$admin['id'],$shop);
        //原订单
        if($info['order_type']==1){
            $m_order=Db::name('order');
        }else{
            $m_order=Db::name('ordersup');
        }
        $where_order=[
            'id'=>$info['about'],
            'shop'=>$info['shop'],
            'is_real'=>1,
        ];
        $order=$m_order->where($where_order)->find();
        if(empty($order)){
            $order=null;
        }
       
        
        $this->cates(); 
       
  
        $this->assign('info1',$info1);
        $this->assign('change',$change);
       
        $this->assign('infos',$infos); 
        $this->assign('info',$info);
        $this->assign('order',$order);
        $this->assign('ok_add',2);
       
        return $this->fetch();  
        
    }
    /**
     * 订单售后编辑审核确认
     * (
     *     'name'   => ' 订单售后编辑审核确认',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '订单售后编辑审核确认',
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
//         $orderback=$m->where('id',$info['pid'])->find();
        $orderback=$m->get_one(['id'=>$info['pid']]);
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
            $row=$m->orderback_edit_review($orderback, $change,$admin);
           
            if($row!==1){
                $m->rollback();
                $this->error($row);
            }
           
            if(isset($change['status1'])){
                $res=$m->status_store_change($orderback['id'],$orderback['status1'],'status1');
                if(!($res>0)){
                    $m->rollback();
                    $this->error($res);
                }
            }
           
            if(isset($change['status2'])){
                $res=$m->status_store_change($orderback['id'],$orderback['status2'],'status2');
                if(!($res>0)){
                    $m->rollback();
                    $this->error($res);
                }
            }
             
        }
        
        //审核成功，记录操作记录,发送审核信息
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'审核'.$info['aid'].'-'.$info['aname'].'对'.($this->flag).$info['pid'].'-'.$orderback['name'].'的编辑为'.$review_status[$status],
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
    /**
     * 确认售后单
     * (
     *     'name'   => '确认售后单',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '确认售后单',
     *     'param'  => ''
     * )
     */
    public function status_do(){
        
        $flag='确认售后单';
        $data=$this->request->param();
        
        $m=$this->m;
        $table=$this->table;
        
        $id=intval($data['id']);
        
        $info=$m->where('id',$id)->find();
        if(empty($info) || $info['status']!=1){
            $this->error('数据不存在或已确认');
        }
        $time=time();
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1 && $info['shop']!=$admin['shop']){
            $this->error('不能编辑其他店铺的信息');
        }
        //是否有权查看
        $res=$m->orderback_edit_auth($info,$admin);
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
         
        $content=$m->orderback_edit($info, $data);
        if(!is_array($content)){
            $this->error($content);
        }
        $content['status']=2;
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
     * 售后产品提交状态确认
     * (
     *     'name'   => '售后产品提交状态确认',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '售后产品提交状态确认',
     *     'param'  => ''
     * )
     */
    public function status1_do(){
        
        $flag='确认售后产品提交状态';
        $data=$this->request->param();
        
        $m=$this->m;
        $table=$this->table;
        
        $id=intval($data['id']);
        
        $info=$m->where('id',$id)->find();
        if(empty($info) ){
            $this->error('数据不存在或已确认');
        }
        $time=time();
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1 && $info['shop']!=$admin['shop']){
            $this->error('不能编辑其他店铺的信息');
        }
        //是否有权查看
        $res=$m->orderback_edit_auth($info,$admin);
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
        
        $content=$m->orderback_edit($info, $data);
        if(!is_array($content)){
            $this->error($content);
        }
        $content['status1']=intval($data['status1']);
        if($content['status1']<=$info['status1']){
            $this->error('数据状态错误，请重试');
        }
         
        //更新时间和状态
        if($info['order_type']==2){
            switch($content['status1']){
                case 2:
                    $content['status']=3;
                    break; 
                case 3:
                    $content['send_time']=$time;
                    break;
                case 4:
                    $content['accept_time']=$time;
                    $content['status']=4; 
                    break; 
           }
        }else{
            switch($content['status1']){
                case 2:
                    $content['status']=3;
                    break; 
                case 3:
                    $content['status']=4;
                    break; 
            }
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
        $res=$this->check_review($admin,$rule);
        if($res){
            $this->redirect($rule,['id'=>$eid,'rstatus'=>2,'rdsc'=>'直接审核']);
        }
        $this->success('已提交修改');
    }
    /**
     * 售后产品处理状态确认
     * (
     *     'name'   => '售后产品处理状态确认',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '售后产品处理状态确认',
     *     'param'  => ''
     * )
     */
    public function status2_do(){
        
        $flag='确认售后产品处理状态';
        $data=$this->request->param();
        
        $m=$this->m;
        $table=$this->table;
        
        $id=intval($data['id']);
        
        $info=$m->where('id',$id)->find();
        if(empty($info) ){
            $this->error('数据不存在或已确认');
        }
        $time=time();
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1 && $info['shop']!=$admin['shop']){
            $this->error('不能编辑其他店铺的信息');
        }
        //是否有权查看
        $res=$m->orderback_edit_auth($info,$admin);
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
        
        $content=$m->orderback_edit($info, $data);
        if(!is_array($content)){
            $this->error($content);
        }
        $content['status2']=intval($data['status2']);
        if($content['status2']<=$info['status2']){
            $this->error('数据状态错误，请重试');
        }
        //更新时间和状态
        if($info['order_type']==1){
            switch($content['status2']){ 
                case 3:
                    $content['send_time']=$time;
                    break;
                case 4:
                    $content['accept_time']=$time; 
                    break;
            }
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
        $res=$this->check_review($admin,$rule);
        if($res){
            $this->redirect($rule,['id'=>$eid,'rstatus'=>2,'rdsc'=>'直接审核']);
        }
        $this->success('已提交修改');
    }
    /**
     *状态还原重新处理
     * (
     *     'name'   => '状态还原重新处理',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '状态还原重新处理',
     *     'param'  => ''
     * )
     */
    public function status0_do(){
        
        $flag='状态还原重新处理';
        $data=$this->request->param();
        
        $m=$this->m;
        $table=$this->table;
        
        $id=intval($data['id']);
        
        $info=$m->where('id',$id)->find();
        if(empty($info) ){
            $this->error('数据不存在或已确认');
        }
        $time=time();
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1 && $info['shop']!=$admin['shop']){
            $this->error('不能编辑其他店铺的信息');
        }
        //是否有权查看
        $res=$m->orderback_edit_auth($info,$admin);
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
        
        $content=$m->orderback_edit($info, $data);
        if(!is_array($content)){
            $this->error($content);
        }
        $content['status2']=1;
        $content['status1']=1;
        $content['status']=1;
        
        
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
     *付款
     * (
     *     'name'   => '付款',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '付款',
     *     'param'  => ''
     * )
     */
    public function pay_do1(){
        
        $flag='售后单付款';
        $data=$this->request->param();
        
        $m=$this->m;
        $table=$this->table;
        
        $id=intval($data['id']);
        
        $info=$m->where('id',$id)->find();
        if(empty($info) ){
            $this->error('数据不存在');
        }
        $time=time();
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1 && $info['shop']!=$admin['shop']){
            $this->error('不能编辑其他店铺的信息');
        }
        //是否有权查看
        $res=$m->orderback_edit_auth($info,$admin);
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
        
        $content=$m->orderback_edit($info, $data);
        if(!is_array($content)){
            $this->error($content);
        }
      
        $content['pay_status']=2;
        $content['pay_time']=$time;
        
        
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
     *付款确认
     * (
     *     'name'   => '付款确认',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '付款确认',
     *     'param'  => ''
     * )
     */
    public function pay_do2(){
        
        $flag='售后单付款确认';
        $data=$this->request->param();
        
        $m=$this->m;
        $table=$this->table;
        
        $id=intval($data['id']);
        
        $info=$m->where('id',$id)->find();
        if(empty($info) ){
            $this->error('数据不存在');
        }
        $time=time();
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1 && $info['shop']!=$admin['shop']){
            $this->error('不能编辑其他店铺的信息');
        }
        //是否有权查看
        $res=$m->orderback_edit_auth($info,$admin);
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
        
        $content=$m->orderback_edit($info, $data);
        if(!is_array($content)){
            $this->error($content);
        }
        
        $content['pay_status']=3;
        
        
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
     *付款还原
     * (
     *     'name'   => '付款还原',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '付款还原',
     *     'param'  => ''
     * )
     */
    public function pay_do0(){
        
        $flag='售后单付款还原';
        $data=$this->request->param();
        
        $m=$this->m;
        $table=$this->table;
        
        $id=intval($data['id']);
        
        $info=$m->where('id',$id)->find();
        if(empty($info) ){
            $this->error('数据不存在');
        }
        $time=time();
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1 && $info['shop']!=$admin['shop']){
            $this->error('不能编辑其他店铺的信息');
        }
        //是否有权查看
        $res=$m->orderback_edit_auth($info,$admin);
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
        
        $content=$m->orderback_edit($info, $data);
        if(!is_array($content)){
            $this->error($content);
        }
        
        $content['pay_status']=1;
        
        
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
     *售后完成
     * (
     *     'name'   => '售后完成',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '售后完成',
     *     'param'  => ''
     * )
     */
    public function status_end(){
        
        $flag='售后完成';
        $data=$this->request->param();
        
        $m=$this->m;
        $table=$this->table;
        
        $id=intval($data['id']);
        
        $info=$m->where('id',$id)->find();
        if(empty($info) ){
            $this->error('数据不存在或已确认');
        }
        $time=time();
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1 && $info['shop']!=$admin['shop']){
            $this->error('不能编辑其他店铺的信息');
        }
        //是否有权查看
        $res=$m->orderback_edit_auth($info,$admin);
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
        
        $content=$m->orderback_edit($info, $data);
        if(!is_array($content)){
            $this->error($content);
        }
        $content['status']=5; 
        
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
    //分类
    public function cates($type=3){
        parent::cates($type);
        $this->assign('statuss',[
            1=>'待审核',2=>'确认',3=>'售后产品提交中',4=>'售后产品处理中',5=>'已完成',6=>'已取消',7=>'已废弃']);
        $this->assign('orderback_types',[1=>'免费换货',2=>'退货退款',3=>'仅退款',4=>'付费维修']);
         
        $this->assign('pay_status',[ 1=>'未付款/退款',2=>'付款/退款中',3=>'货款确认']);
        $this->assign('pay_types',config('pay_type'));
        
        //店铺所属，管理员，公司，付款方式
        $where_shop=$this->where_shop;
        $where=['status'=>2];
        $where_admin=[
            'user_type'=>1,
            'user_status'=>1,
        ];
         
        $field='id,name';
        $order='shop asc,sort asc';
        $admin=$this->admin;
        if($type<3 && $admin['shop']==1){
            $shops=Db::name('shop')->where($where)->order('sort asc')->column('id,name');
            $this->assign('shops',$shops);  
        }
        if(!empty($where_shop)){ 
            $where['shop']=$where_shop; 
            $where_admin['shop']=$where_shop;
        }
        $order_type=$this->order_type;
        if($order_type==1){
            //客户发货
            $this->assign('status1',[ 1=>'未发货',2=>'寄出',3=>'确认收货',4=>'检测入库']);
            $this->assign('status2',[ 1=>'未发货',2=>'配货',3=>'寄出',4=>'已收货',5=>'重新下单发货']);
        }else{
            //采购售后
            $this->assign('status1',[1=>'未发货',2=>'配货',3=>'寄出',4=>'已收货']);
            $this->assign('status2',[ 1=>'未发货',2=>'寄出',3=>'确认收货',4=>'检测入库',5=>'重新下单发货']);
        }
      
        $typeinfo=[
            'order_type'=>[1=>'客户订单',2=>'采购订单'],
            'uflag'=>[1=>'客户',2=>'供货商'],
            'udo1'=>[1=>'客户退货',2=>'采购退货'],
            'udo2'=>[1=>'重新发货',2=>'重新收货'],
            'order_url'=>[1=>url('order/AdminOrder/edit','',false,false),2=>url('ordersup/AdminOrdersup/edit','',false,false)],
            'custom_url'=>[1=>url('custom/AdminCustom/edit','',false,false),2=>url('custom/AdminSupplier/edit','',false,false)],
        ];
        $this->assign('typeinfo',$typeinfo); 
        //公司
        $companys=Db::name('company')->where($where)->order($order)->column($field);
        
        //获取所有仓库
        $stores=Db::name('store')->where($where)->order($order)->column($field); 
        //管理员
        $aids=Db::name('user')->where($where_admin)->column('id,user_nickname');
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
        $this->assign('aids',$aids);
        $this->assign('rids',$aids); 
        $this->assign('stores',$stores); 
       
        $this->assign('goods_url',url('goods/AdminGoods/edit',false,false)); 
        $this->assign('image_url',cmf_get_image_url('')); 
         //快递公司
        $expresses=Db::name('express')->order('sort asc')->where('status',2)->column('id,name,code');
        $this->assign('expresses',$expresses);
        
        //合作快递公司
        $freights=Db::name('freight')->order('sort asc')->where('status',2)->column('id,name,express');
        $this->assign('freights',$freights);  
        
    }
     //文件下载
    public function orderback_file_load(){
        $id=$this->request->param('id',0,'intval');
        $sort=$this->request->param('sort',0,'intval');
        $m=$this->m;
        $files=$m->where('id',$id)->value('files');
        if(empty($files)){
            $this->error('没有文件，请刷新');
        }
        $files=json_decode($files,true);
        if(empty($files[$sort]['url'])){
            $this->error('没有该文件，请刷新');
        }
       
        $path='upload/';
        $file=$path.$files[$sort]['url'];
        $filename=empty($files[$sort]['name'])?date('Ymd-His'):$files[$sort]['name'];
        if(is_file($file)){
            $fileinfo=pathinfo($file);
            $ext=$fileinfo['extension'];
            $filename=$filename.'.'.$ext;
            header('Content-type: application/x-'.$ext);
            header('content-disposition:attachment;filename='.$filename);
            header('content-length:'.filesize($file));
            readfile($file);
            exit;
        }else{
            $this->error('文件损坏，不存在');
        }
    }
    
    public function file_load(){
        $id=$this->request->param('id',0,'intval');
        $sort=$this->request->param('sort',0,'intval');
        $field=$this->request->param('field','pics');
        $m=$this->m;
        $files=$m->where('id',$id)->value($field);
        if(empty($files)){
            $this->error('没有文件，请刷新');
        }
        $files=json_decode($files,true);
        if(empty($files[$sort]['url'])){
            $this->error('没有该文件，请刷新');
        }
        
        $path='upload/';
        $file=$path.$files[$sort]['url'];
        $filename=empty($files[$sort]['name'])?date('Ymd-His'):$files[$sort]['name'];
        if(is_file($file)){
            $fileinfo=pathinfo($file);
            $ext=$fileinfo['extension'];
            $filename=$filename.'.'.$ext;
            header('Content-type: application/x-'.$ext);
            header('content-disposition:attachment;filename='.$filename);
            header('content-length:'.filesize($file));
            readfile($file);
            exit;
        }else{
            $this->error('文件损坏，不存在');
        }
    }
}
