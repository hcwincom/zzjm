<?php
 
namespace app\orderq\controller;

 
use think\Db; 
use app\orderq\model\OrderqModel;
use app\common\controller\AdminInfo0Controller; 
use app\order\model\OrderModel;
class AdminOrderqController extends AdminInfo0Controller
{
    
    public function _initialize()
    {
        parent::_initialize();
        //没有店铺区分
        $this->isshop=1;
        $this->flag='询盘';
        $this->table='orderq';
        $this->m=new OrderqModel();
       
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
         
    }
    /**
     * 询盘列表
     * @adminMenu(
     *     'name'   => '询盘列表',
     *     'parent' => 'orderq/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '询盘列表',
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
         
        //查询字段
        $types=[
            'p.name'=>'询盘编号',
            
            'custom.uname'=>'客户名称',
            'custom.ucode'=>'客户编号',
            'custom.name'=>'客户联系人',
             
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
        $times=[
            'atime' => '创建时间',
            'rtime' => '转化时间',
            'time' => '更新时间',
        ];
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
        
        //关联表
        $join=[
            ['cmf_orderq_custom custom','p.uid=custom.id','left'], 
        ];
        $field='p.*,custom.name as custom_name';
        $list=$m
        ->alias('p')
        ->field('p.id') 
        ->join($join)
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
            //关联表
            $join=[ 
                ['cmf_orderq_custom custom','p.uid=custom.id','left'], 
            ];
            $field='p.*,custom.uname as custom_name,custom.name as custom_telname,custom.mobile as custom_mobile';
            
            $list=$m
            ->alias('p')
            ->join($join)
            ->where('p.id','in',$ids)
            ->order('p.time desc')
            ->column($field); 
            $goods=Db::name('orderq_goods')->where('oid','in',$ids)
            ->column('id,oid,goods,name,code,pic,price_sale,price_real,num');
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
     * 询盘添加
     * @adminMenu(
     *     'name'   => '询盘添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '询盘添加',
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
            $custom=null;
        }else{
            //获取客户信息
            $custom=Db::name('custom')->where('id',$uid)->find();
            
        }
        $this->assign('info',null);
      
        $this->assign('custom',$custom);
        $this->assign('tels',null);
        $this->assign('ok_add',1);
       
       
        return $this->fetch();  
        
    }
    /**
     * 询盘添加do
     * @adminMenu(
     *     'name'   => '询盘添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '询盘添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        $data=$this->request->param();
        
        //客户的字段，如果uid<=0需要判断
        $fields_custom=[
           'ucode','uname','name','position','sex','mobile','phone','province','city','area',
            'street','postcode','fax','qq','wechat','wechatphone','wechatname','email','taobaoid','aliid'
        ];
        
        $data_custom=[];
        if(empty($data['uid'])){
            $data_custom['uid']=0;
        }else{
            $data_custom['uid']=intval($data['uid']);
        }
        if(empty($data['custom_sex'])){
            $data['custom_sex']=1;
        }
        foreach($fields_custom as $v){
            $data_custom[$v]=$data['custom_'.$v];
        }
        $uid=Db::name('orderq_custom')->insertGetId($data_custom);
        //店铺和下单人
        $admin=$this->admin;
        $time=time();
        $data_orderq=[
            'name'=>date('Ymd').substr($time,-6).$admin['id'],
            'aid'=>$admin['id'],
            'shop'=>($admin['shop']==1)?2:$admin['shop'], 
            'uid'=>$uid, 
            'atime'=>$time,
            'time'=>$time,
            'pic1'=>'',
            'pic2'=>'',
        ]; 
        $fields=['company','sourse','sourse_name','udsc','dsc','answer1','question1','question2','answer2'];
        foreach($fields as $v){
            $data_orderq[$v]=$data[$v];
        }
        $m=$this->m;
        $m_info=Db::name('orderq_goods');
        $m->startTrans();
        $oid= $m->insertGetId($data_orderq);
        $goods=[];
        //产品数据
        if(!empty($data['goods_ids'])){ 
            $goods_ids=$data['goods_ids'];
            //先把未知产品删除
            foreach($goods_ids as $k=>$v){
                if($v<=0){
                    unset($goods_ids[$k]);
                }
            }
           
            if(!empty($goods_ids)){ 
                $where=[
                    'id'=>['in',$goods_ids],
                    'shop'=>$data_orderq['shop']
                ];
                $goods=Db::name('goods')->where($where)->column('id,cid0,cid,code,pic,code_name,name,price_in,price_sale');
            }
            
        }
        $data_goods=[];
        $tmp=[];
        //组装产品数据，如果是一直产品，产品名称等遵循数据ku
        foreach($data['goods_ids'] as $k=>$v){
            $tmp=[
                'oid'=>$oid,
                'goods'=>intval($v),
                'price_real'=>round($data['price_reals'][$k],2),
                'dsc'=>$data['dscs'][$k],
                'send_dsc'=>$data['send_dscs'][$k],
                'is_sup'=>$data['is_sups'][$k],
                'sup'=>intval($data['sups'][$k]),
                'num'=>intval($data['nums'][$k]),
                'sup'=>intval($data['sups'][$k]),
                
                'code_name'=>$data['code_names'][$k],
                'name'=>$data['names'][$k],
                'price_in'=>round($data['price_ins'][$k],2),
                'price_sale'=>round($data['price_sales'][$k],2),
                'cid'=>intval($data['cids'][$k]),
                'cid0'=>intval($data['cid0s'][$k]),
                'pic'=>'', 
                'code'=>'',
            ];
            if(isset($goods[$v])){
                $tmp['code_name']=$goods[$v]['code_name'];
                $tmp['name']=$goods[$v]['name'];
                $tmp['price_in']=$goods[$v]['price_in'];
                $tmp['price_sale']=$goods[$v]['price_sale'];
                $tmp['cid']=$goods[$v]['cid'];
                $tmp['cid0']=$goods[$v]['cid0'];
                $tmp['code']=$goods[$v]['code'];
                $tmp['pic']=$goods[$v]['pic'];
            }
            $data_goods[]=$tmp;
        }
        if(!empty($data_goods)){
            $m_info->insertAll($data_goods);
        }
        $data_orderq['id']=$oid;
        $pics= $m->pic_do($data_orderq,$data,[]);
        if(!empty($pics)){
            $m->where('id',$oid)->update($pics);
        }
        $m->commit();
        $this->success('提交成功',url('edit',['id'=>$oid]));
    }
    /**
     * 询盘详情
     * @adminMenu(
     *     'name'   => '询盘详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '询盘详情',
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
        if(!empty($info['pic1'])){
            $info['pic1']=json_decode($info['pic1'],true);
        }
        if(!empty($info['pic2'])){
            $info['pic2']=json_decode($info['pic2'],true);
        }
     
        $shop=$info['shop'];
        if($admin['shop']>1 && $admin['shop']!=$shop){
            $this->error('只能查看本店铺的数据');
        }
        $this->where_shop=$shop;
        //获取客户信息
        $custom=Db::name('orderq_custom')->where('id',$info['uid'])->find();
        if(empty($custom)){
            $custom=null; 
            $tels=null;
        }elseif($custom['uid']>0){
            //联系人
            $where=[
                'uid'=>$custom['uid'],
                'type'=>1,
                'status'=>1,
            ]; 
            $tels=Db::name('tel')
            ->where($where)
            ->order('sort asc,site asc')
            ->column('*','site');
            foreach($tels as $k=>$v){
                foreach($v as $kk=>$vv){
                    if($vv!='0' && empty($vv)){
                        $tels[$k][$kk]='';
                    }
                }
            } 
         }else{
             $tels=null;
         }
        
        $ok_add=($info['status']<2)?1:2;
         
        //询盘产品
        $res=$m->orderq_goods($info,$admin['id']);
         
        $this->cates();
        $this->assign('infos',$res['infos']);
      
        $this->assign('goods',$res['goods']);
        
        $this->assign('info',$info); 
        $this->assign('ok_add',$ok_add); 
        $this->assign('custom',$custom);
        $this->assign('tels',$tels);
        
        return $this->fetch();  
    }
    /**
     * 询盘编辑
     * @adminMenu(
     *     'name'   => '询盘编辑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '询盘编辑',
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
        $res=$m->orderq_edit_auth($info,$admin);
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
       
        $content=$m->orderq_edit($info, $data);
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
     * 询盘转化订单
     * @adminMenu(
     *     'name'   => '询盘转化订单',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '询盘转化订单',
     *     'param'  => ''
     * )
     */
    public function order_do()
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
        if($info['status']!=2){
            $this->error('状态错误，不能转化');
        }
        //是否有权查看
        $res=$m->orderq_edit_auth($info,$admin);
        if($res!==1){
            $this->error($res);
        }
        $custom0=Db::name('orderq_custom')
        ->alias('c')
        ->field('c.*,province.name as city1,city.name as city2,area.name as city3')
        ->join('cmf_area province','province.id=c.province','left')
        ->join('cmf_area city','city.id=c.city','left')
        ->join('cmf_area area','area.id=c.area','left') 
        ->where('c.id',$info['uid'])
        ->find();
        
        $data_order=[
            'shop'=>$info['shop'],
            'company'=>$info['company'],
            'name'=>$info['name'],
            'aid'=>$admin['id'],
            'uid'=>$custom0['uid'],
            'accept_name'=>$custom0['name'],
            'postcode'=>$custom0['postcode'],
            'mobile'=>$custom0['mobile'],
            'phone'=>$custom0['phone'],
            'province'=>$custom0['province'],
            'city'=>$custom0['city'],
            'area'=>$custom0['area'],
            'address'=>$custom0['street'],
            'addressinfo'=>($custom0['city1'].'-'.$custom0['city2'].'-'.$custom0['city3']),
            'sort'=>2,
            'time'=>$time,
            'create_time'=>$time,
            'status'=>1,
        ];
        $m->startTrans();
        $m_order=new OrderModel();
        $oid=$m_order->insertGetId($data_order);
        $where_goods=[
            'oid'=>$info['id'],
        ];
        $update_order=['goods_num'=>0,'goods_money'=>0,'weight'=>0,'size'=>0];
        $goods0=Db::name('orderq_goods')->where($where_goods)->column('*','goods');
       
        if(!empty($goods0)){
            $goods_ids=array_keys($goods0); 
            $goods=Db::name('goods')->where('id','in',$goods_ids)->column('*');
            foreach($goods as $k=>$v){
                $v=$m_order->unit_change($v);
                $tmp=[
                    'oid'=>$oid,
                    'goods'=>$v['id'],
                    'goods_name'=>$v['name'],
                    'print_name'=>$v['name3'],
                    'goods_code'=>$v['code'],
                    'goods_pic'=>$v['pic'],
                    'price_in'=>$v['price_in'],
                    'price_sale'=>$v['price_sale'],
                    'weight1'=>$v['weight1'],
                    'size1'=>$v['size1'],
                    'price_real'=>$goods0[$k]['price_real'],
                    'dsc'=>$goods0[$k]['dsc'],
                    'num'=>$goods0[$k]['num'], 
                    
                ];
                $tmp['pay']=bcmul($tmp['num'],$tmp['price_real'],2);
                $tmp['weight']=bcmul($tmp['num'],$tmp['weight1'],2);
                $tmp['size']=bcmul($tmp['num'],$tmp['size1'],2);
               
                $data_goods[]=$tmp;
                $update_order['goods_num']+=$tmp['num'];
                $update_order['weight']+=$tmp['weight'];
                $update_order['size']+=$tmp['size'];
                $update_order['goods_money']+=$tmp['pay'];
                
            }
            Db::name('order_goods')->insertAll($data_goods);
            $m_order->where('id',$oid)->update($update_order);
        }
        
        //记录操作记录
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'转化询盘'.$info['id'].'为订单'.$oid.'-单号'.$info['name'],
            'table'=>($this->table),
            'type'=>'edit',
            'pid'=>$info['id'],
            'link'=>url('order/AdminOrder/edit',['id'=>$oid]),
            'shop'=>$admin['shop'],
        ];
        
        zz_action($data_action,['department'=>$admin['department']]);
        $m->commit();
        $this->success('已转化询盘为订单，跳转到订单页面',url('order/AdminOrder/edit',['id'=>$oid]));
    }
    /**
     * 询盘编辑列表
     * @adminMenu(
     *     'name'   => '询盘编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '询盘编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list()
    {
        parent::edit_list();
        return $this->fetch();
    }
    /**
     * 询盘编辑审核页面
     * @adminMenu(
     *     'name'   => ' 询盘编辑审核页面',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '询盘编辑审核页面',
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
        if(!empty($info['pic1'])){
            $info['pic1']=json_decode($info['pic1'],true);
        }
        if(!empty($info['pic2'])){
            $info['pic2']=json_decode($info['pic2'],true);
        }
        //获取改变的信息
        $change=Db::name('edit_info')->where('eid',$id)->value('content');
        $change=json_decode($change,true);
        if(!empty($change['pic1'])){
            $change['pic1']=json_decode($change['pic1'],true);
        }
        if(!empty($change['pic2'])){
            $change['pic2']=json_decode($change['pic2'],true);
        }
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
        //获取客户信息
        $custom=Db::name('orderq_custom')->where('id',$info['uid'])->find();
        if(empty($custom)){
            $custom=null;
            $tels=null;
        }elseif($custom['uid']>0){
            //联系人
            $where=[
                'uid'=>$custom['uid'],
                'type'=>1,
                'status'=>1,
            ];
            $tels=Db::name('tel')
            ->where($where)
            ->order('sort asc,site asc')
            ->column('*','site');
            foreach($tels as $k=>$v){
                foreach($v as $kk=>$vv){
                    if($vv!='0' && empty($vv)){
                        $tels[$k][$kk]='';
                    }
                }
            }
        }else{
            $tels=null;
        }
         
        //询盘产品
         $res=$m->orderq_goods($info,$admin['id']);
        $this->cates(); 
        $this->assign('infos',$res['infos']);
      
        $this->assign('goods',$res['goods']);
 
        $this->assign('info',$info);
        $this->assign('info1',$info1);
        $this->assign('change',$change);
        $this->assign('ok_add',2); 
       
        $this->assign('custom',$custom);
        $this->assign('tels',$tels);
         
        return $this->fetch();  
        
    }
    /**
     * 询盘编辑审核确认
     * @adminMenu(
     *     'name'   => ' 询盘编辑审核确认',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '询盘编辑审核确认',
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
        $orderq=$m->where('id',$info['pid'])->find();
        
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
            $row=$m->orderq_edit_review($orderq, $change,$admin);
           
            if($row!==1){
                $m->rollback();
                $this->error($row);
            }
             
        }
        
        //审核成功，记录操作记录,发送审核信息
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'审核'.$info['aid'].'-'.$info['aname'].'对'.($this->flag).$info['pid'].'-'.$orderq['name'].'的编辑为'.$review_status[$status],
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
     * 询盘图片下载
     * @adminMenu(
     *     'name'   => '询盘图片下载',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>140,
     *     'icon'   => '',
     *     'remark' => '询盘图片下载',
     *     'param'  => ''
     * )
     */
    public function orderq_pic_load(){
        $id=$this->request->param('id',0,'intval');
        $type=$this->request->param('type','pic1');
        $sort=$this->request->param('sort',0,'intval');
        $m=$this->m;
        $info=$m->where('id',$id)->find();
        if(empty($info[$type])){
            $this->error('数据错误，文件不存在');
        }
        $pics=json_decode($info[$type],true);
        if(empty($pics[$sort]['url'])){
            $this->error('数据错误，文件不存在');
        }
        
        $path='upload/';
        $file=$path.$pics[$sort]['url'];
        
        if(is_file($file)){
            $fileinfo=pathinfo($file);
            $ext=$fileinfo['extension'];
            $filename=$pics[$sort]['name'].'.'.$ext;
            header('Content-type: application/x-'.$ext);
            header('content-disposition:attachment;filename='.$filename);
            header('content-length:'.filesize($file));
            readfile($file);
            exit;
        }else{
            $this->error('文件损坏，不存在');
        }
    }
    //分类
    public function cates($type=3){
        parent::cates($type);
        $this->assign('statuss',[1=>'询单',2=>'已转化',3=>'已废弃']);
        $this->assign('is_sups',[1=>'未确定',2=>'否',3=>'是']);
        //店铺所属，管理员，公司，付款方式
        $where_shop=$this->where_shop;
        $where=['status'=>2];
        $where_admin=[
            'user_type'=>1,
            'user_status'=>1,
        ];
         
        $field='id,name';
        $order='shop asc,sort asc';
        if(empty($where_shop)){
            $shops=Db::name('shop')->where($where)->order('sort asc')->column('id,name');
            $this->assign('shops',$shops);  
        }else{
            $where['shop']=$where_shop; 
            $where_admin['shop']=$where_shop;
        }
        //获取供货商 
        $sups=Db::name('supplier')->where($where)->order('code asc')->column('id,code,name');
        //询盘来源
        $sourses=Db::name('sourse')->where($where)->order('sort asc')->column($field);
      
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
        $this->assign('sups',$sups); 
        $this->assign('companys',$companys);
        $this->assign('sourses',$sourses);
        $this->assign('aids',$aids);
        $this->assign('rids',$aids); 
        $this->assign('stores',$stores); 
       
        $this->assign('goods_url',url('goods/AdminGoods/edit',false,false)); 
        $this->assign('image_url',cmf_get_image_url('')); 
        
        //获取分类
        $where=[
            'status'=>2,
            'fid'=>0
        ];
        $cate1=Db::name('cate')->where($where)->order('code_num asc')->column('id,code,name');
        $where=[
            'status'=>2,
            'fid'=>['gt',0]
        ];
        $cate2=Db::name('cate')->where($where)->order('code asc')->column('id,code,fid,name');
        $this->assign('cate1',$cate1);
        $this->assign('cate2',$cate2); 
      
        //custom_cates
        $where=[
            'status'=>2,
        ];
        $custom_cates=Db::name('custom_cate')->where($where)->order('sort asc')->column('id,name');
        $this->assign('custom_cates',$custom_cates); 
    }
     
}
