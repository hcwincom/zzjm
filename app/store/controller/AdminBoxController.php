<?php
 
namespace app\store\controller;

 
use app\common\controller\AdminInfo0Controller; 
use think\Db; 
use app\store\model\StoreGoodsModel;
use app\store\model\StoreBoxModel;
class AdminBoxController extends AdminInfo0Controller
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='料位';
        $this->table='store_box';
        $this->m=Db::name('store_box');
        $this->edit=['name','sort','dsc','code_num','code','length','space'];
        $this->search=[
            'code'=>'料位号',
            'name'=>'名称',
            'id'=>'ID'
        ];
        //没有店铺区分
        $this->isshop=1;
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        
    }
    /**
     * 料位列表
     * @adminMenu(
     *     'name'   => '料位列表',
     *     'parent' => 'store/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 40,
     *     'icon'   => '',
     *     'remark' => '料位列表',
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
        //判断是否有店铺
        $join=[
            ['cmf_goods goods','goods.id=p.goods','left'],
            ['cmf_shop shop','p.shop=shop.id','left'], 
            ['cmf_store_goods sg','p.store=sg.store and p.goods=sg.goods','left'],
           
        ];
        $field='p.*,goods.name as goods_name,goods.code as goods_code,shop.name as sname,'.
        'sg.safe as sg_safe,sg.num as sg_num,sg.num1 as sg_num1,sg.box_num'; 
      
        //店铺,分店只能看到自己的数据，总店可以选择店铺
        if($admin['shop']==1){
            if(empty($data['shop'])){
                $data['shop']=0;
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
        //仓库
        if(empty($data['store'])){
            $data['store']=0;
        }else{
            $where['p.store']=['eq',$data['store']];
        }
        //货架
        if(empty($data['shelf'])){
            $data['shelf']=0;
        }else{
            $where['p.shelf']=['eq',$data['shelf']];
        }  
        //层号
        if(empty($data['floor'])){
            $data['floor']=0;
        }else{
            $where['p.floor']=['eq',$data['floor']];
        }
        
        
        //添加人
        if(empty($data['aid'])){
            $data['aid']=0;
        }else{
            $where['p.aid']=['eq',$data['aid']];
        }
        //审核人
        if(empty($data['rid'])){
            $data['rid']=0;
        }else{
            $where['p.rid']=['eq',$data['rid']];
        }
         
        //查询字段
        $types=$this->search;
        $this->search=[
            'code'=>'料位号',
            'name'=>'名称',
            'id'=>'ID'
        ];
        $types=[
            1=>['p.code','料位号'],
            2=>['p.name','料位名'],
            3=>['p.id','料位id'],
            4=>['goods.name','产品名'],
            5=>['goods.code','产品编号'],
            5=>['p.goods','产品id'],
        ];
        //选择查询字段
        if(empty($data['type1'])){
            $data['type1']=1;
        }
        //搜索类型
        $search_types=config('search_types');
        if(empty($data['type2'])){
            $data['type2']=key($search_types);
        }
        if(!isset($data['name']) || $data['name']==''){
            $data['name']='';
        }else{
            $where[$types[$data['type1']][0]]=zz_search($data['type2'],$data['name']);
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
        $list=$m
        ->alias('p')
        ->field($field)
        ->join($join)
        ->where($where)
        ->order('p.status asc,p.sort asc,p.time desc')
        ->paginate();
        
        // 获取分页显示
        $page = $list->appends($data)->render();
        
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
     * 料位添加
     * @adminMenu(
     *     'name'   => '料位添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '料位添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
        $data=$this->request->param(); 
        $this->assign('data',$data);
      
        return $this->fetch();  
        
    }
    /**
     * 料位添加do
     * @adminMenu(
     *     'name'   => '料位添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '料位添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        $m=$this->m;
        $data=$this->request->param();
        $data=$this->param_check($data);
        if(!is_array($data)){
            $this->error($data);
        }
        
        $data_add=$data;
        if(isset($data_add['floors'])){
            unset($data_add['floors']);
        }
        $admin=$this->admin;
        
        $url=url('index');
        
        $table=$this->table;
        $time=time();
       
        $data_add['status']=1; 
        $data_add['aid']=$admin['id'];
        $data_add['atime']=$time;
        $data_add['time']=$time;
        $data_add['num']=0;
        $m->startTrans();
       
        $id=$m->insertGetId($data_add);
        //是否初次审核,添加入库
        if(!empty($data_add['goods'])){
            
            $data_instore=[
                'store'=>$data_add['store'],
                'goods'=>$data_add['goods'],
                'box'=>$id,
                'shop'=>$data_add['shop'],
                'num'=>$data['num'],
                'box'=>$id,
                'type'=>30,
                'about'=>$id,
                'about_name'=> $data_add['name'],
                'aid'=>$data_add['aid'],
                'atime'=>$data_add['time'],
                'adsc'=>'管理员添加料位'.$data_add['name'].'入库',
                'rstatus'=>4,
            ];
            $m_store_goods=new StoreGoodsModel();
            $res=$m_store_goods->instore0($data_instore);
            if(!($res>0)){
                $id=$res;
            }
        }
        if($id<=0){
            $m->rollback();
            $this->error($id);
        }
        //记录操作记录
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'添加'.($this->flag).$id.'-'.$data['name'],
            'table'=>($this->table),
            'type'=>'add',
            'pid'=>$id,
            'link'=>url('edit',['id'=>$id]),
            'shop'=>$admin['shop'],
            
        ];
        zz_action($data_action,['department'=>$admin['department']]);
       
        $m->commit();
        $this->success('添加成功',$url);
        
    }
    /**
     * 料位详情
     * @adminMenu(
     *     'name'   => '料位详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '料位详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $m=$this->m;
        $id=$this->request->param('id',0,'intval');
        $field='p.*,floor.floor as floor_name,shelf.name as shelf_name,store.name as store_name,'.
        'goods.name as goods_name,cate1.name as cate1_name,cate2.name as cate2_name,a.user_nickname as aname,r.user_nickname as rname';
        $info=$m
        ->alias('p')
        ->field($field)
        ->join('cmf_store_floor floor','floor.id=p.floor','left') 
        ->join('cmf_store_shelf shelf','shelf.id=p.shelf','left')
        ->join('cmf_store store','store.id=p.store','left') 
        ->join('cmf_goods goods','goods.id=p.goods','left') 
        ->join('cmf_cate cate2','cate2.id=goods.cid','left') 
        ->join('cmf_cate cate1','cate1.id=goods.cid0','left') 
        ->join('cmf_user a','a.id=p.aid','left')
        ->join('cmf_user r','r.id=p.rid','left')
        ->where('p.id',$id)
        ->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        
        $this->assign('info',$info);
        $this->where_shop=$info['shop']; 
        
        return $this->fetch();  
    }
    /**
     * 料位状态审核
     * @adminMenu(
     *     'name'   => '料位状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '料位状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        $status=$this->request->param('status',0,'intval');
        $id=$this->request->param('id',0,'intval');
        if($status<1 || $status>4 || $id<=0){
            $this->error('信息错误');
        }
        $m=$this->m;
        //查找信息
        $info=$m->where('id',$id)->find();
        if(empty($info)){
            $this->error('信息不存在');
        }
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1){
            if(empty($info['shop']) || $info['shop']!=$admin['shop']){
                $this->error('不能审核其他店铺的信息');
            }
        }
        $time=time();
        $update=[
            'rid'=>$admin['id'],
            'rtime'=>$time,
            'status'=>$status,
            'time'=>$time,
        ];
        $m->startTrans();
        $row=$m->where('id',$id)->update($update);
        //更新入库记录   
        $where=[
            'type'=>30,
            'about'=>$id,
            'rstatus'=>4,
        ]; 
        $m_store_in=Db::name('store_in');
        $tmp=$m_store_in->where($where)->find();
        if(!empty($tmp)){
            if($status==2){
                $rstatus=1;
            }else{
                $rstatus=5;
                //废弃
                $m_store_goods=new StoreGoodsModel();
                $m_store_goods->instore5($tmp);
            }
            $m_store_in->where('id',$tmp['id'])->setField('rstatus',$rstatus);
        }
        
        if($row!==1){
            $m->rollback();
            $this->error('审核失败，请刷新后重试');
        }
        
        //审核成功，记录操作记录,发送审核信息
        $statuss=$this->statuss;
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'审核'.($this->flag).$info['id'].'-'.$info['name'].'的状态为'.$statuss[$status],
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
     * 料位状态批量同意
     * @adminMenu(
     *     'name'   => '料位状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '料位状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        if(empty($_POST['ids'])){
            $this->error('未选中信息');
        }
        $ids=$_POST['ids'];
        $m=$this->m;
        $admin=$this->admin;
        $time=time();
        $where=[
            'id'=>['in',$ids],
            'status'=>['eq',1],
        ];
        //其他店铺检查,如果没有shop属性就只能是1号主站操作,有shop属性就带上查询条件
        if($admin['shop']!=1){
            if($this->isshop){
                $where['shop']=['eq',$admin['shop']];
            }else{
                $this->error('店铺操作系统数据');
            }
        }
        
        $update=[
            'status'=>2,
            'time'=>$time,
            'rid'=>$admin['id'],
            'rtime'=>$time,
        ];
        //得到要更改的数据
        $list=$m->where($where)->column('id');
        if(empty($list)){
            $this->error('没有可以批量审核的数据');
        }
        
        $ids=implode(',',$list);
        
        //审核成功，记录操作记录,发送审核信息
        $flag=$this->flag;
        
        $table=$this->table;
        //记录操作记录
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'批量同意'.$flag.'('.$ids.')',
            'table'=>$table,
            'type'=>'review_all',
            'link'=>'',
            'shop'=>$admin['shop'],
        ];
        $m->startTrans();
        
        zz_action($data_action,['pids'=>$ids]);
        $rows=$m->where('id','in',$list)->update($update);
        if($rows<=0){
            $m->rollback();
            $this->error('没有数据审核成功，批量审核只能把未审核的数据审核为正常');
        }
        //出入库更新
        $where=[
            'type'=>30,
            'about'=>['in',$list],
            'rstatus'=>4,
        ];
        Db::name('store_in')->where($where)->setField('rstatus',1);
        $m->commit();
        $this->success('审核成功'.$rows.'条数据');
    }
    /**
     * 料位禁用
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
     * 料位信息状态恢复
     * @adminMenu(
     *     'name'   => '料位信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '料位信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 料位编辑提交
     * @adminMenu(
     *     'name'   => '料位编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '料位编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        $m=$this->m;
        $table=$this->table;
        $flag=$this->flag;
        $data=$this->request->param();
        $data=$this->param_check($data);
        if(!is_array($data)){
            $this->error($data);
        }
        
        $info=$m->where('id',$data['id'])->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        $time=time();
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1){
            if(empty($info['shop']) || $info['shop']!=$admin['shop']){
                $this->error('不能编辑其他店铺的信息');
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
        $fields=$this->edit;
        
        $content=[];
        //检测改变了哪些字段
        foreach($fields as $k=>$v){
            //如果原信息和$data信息相同就未改变，不为空就记录，？null测试
            if(isset($data[$v]) && $info[$v]!=$data[$v]){
                $content[$v]=$data[$v];
            } 
        }
         
        //选择了新料位
        if(!empty($data['box'])){
            if($data['box']==$info['id']){
                $this->error('新料位不能为原料位');
            }
            //检查新料位是否有产品
            $tmp=$m->where('id',$data['box'])->find();
            if(empty($tmp) || $tmp['status']!=2 || $tmp['goods']!=0){
                $this->error('新料位不是可选空料位');
            }
            $content['box']=$data['box'];
            $content['box_goods']=$info['goods'];
            
        }
        //新添加了产品
        if(empty($info['goods']) && !empty($data['goods'])){
            $content['goods']=$data['goods'];
            $data_instore=[
                'store'=>$info['store'],
                'goods'=>$data['goods'],
                'shop'=>$info['shop'], 
                'num'=>$data['num'],
                'box'=>$info['id'],
                'type'=>30,
                'about'=>$info['id'],
                'about_name'=>$data['name'],
                'aid'=>$admin['id'],
                'atime'=>$time,
                'rstatus'=>4,
                'adsc'=>'管理员为料位'.$info['id'].'-'.$data['name'].'设置产品',
            ];
            $m_store_goods=new StoreGoodsModel();
            $res=$m_store_goods->instore0($data_instore);
            if(!($res>0)){
                $m->rollback();
                $this->error($res);
            }
            
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
            'action'=>$admin['user_nickname'].'编辑了'.($this->flag).$info['id'].'-'.$info['name'],
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
     * 料位编辑列表
     * @adminMenu(
     *     'name'   => '料位编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '料位编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();  
    }
    
    /**
     * 料位审核详情
     * @adminMenu(
     *     'name'   => '料位审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '料位审核详情',
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
        $field='p.*,floor.floor as floor_name,shelf.name as shelf_name,store.name as store_name,'.
            'goods.name as goods_name,cate1.name as cate1_name,cate2.name as cate2_name,a.user_nickname as aname,r.user_nickname as rname';
        $info=$m
        ->alias('p')
        ->field($field)
        ->join('cmf_store_floor floor','floor.id=p.floor','left')
        ->join('cmf_store_shelf shelf','shelf.id=p.shelf','left')
        ->join('cmf_store store','store.id=p.store','left')
        ->join('cmf_goods goods','goods.id=p.goods','left')
        ->join('cmf_cate cate2','cate2.id=goods.cid','left')
        ->join('cmf_cate cate1','cate1.id=goods.cid0','left')
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
       
        //料位调整
        if(isset($change['box'])){
            $change['box_name']=$m->where('id',$change['box'])->value('name');
          
            $tmp=Db::name('goods')
            ->alias('goods')
            ->field('goods.name as gname,cate1.name as cname1,cate2.name as cname2')
            ->where('goods.id',$change['box_goods'])
            ->join('cmf_cate cate2','cate2.id=goods.cid')
            ->join('cmf_cate cate1','cate1.id=goods.cid0')
            ->find();
            $change['box_goods_name']=$tmp['cname1'].'-'.$tmp['cname2'].'-'.$tmp['gname'];
        }
        //料位调整
        if(isset($change['goods'])){
            
            $tmp=Db::name('goods')
            ->alias('goods')
            ->field('goods.name as gname,cate1.name as cname1,cate2.name as cname2')
            ->where('goods.id',$change['goods'])
            ->join('cmf_cate cate2','cate2.id=goods.cid')
            ->join('cmf_cate cate1','cate1.id=goods.cid0')
            ->find();
            $change['goods_name']=$tmp['cname1'].'-'.$tmp['cname2'].'-'.$tmp['gname'];
        }
        
        $this->assign('info',$info);
        $this->assign('info1',$info1);
        $this->assign('change',$change);
        
       
        $this->where_shop=$info['shop'];
        
        //分类关联信息
        $this->cates();
        
        return $this->fetch();  
    }
    /**
     * 料位信息编辑审核
     * @adminMenu(
     *     'name'   => '料位编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '料位编辑审核',
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
        ->field('e.*,p.name as pname,p.store,p.num,p.goods,p.shop as pshop,a.user_nickname as aname')
        ->alias('e')
        ->join('cmf_'.$table.' p','p.id=e.pid')
        ->join('cmf_user a','a.id=e.aid')
        ->where('e.id',$id)
        ->find();
        if(empty($info)){
            $this->error('无效信息');
        }
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
            //组装更新数据
            $update_info=[
                'time'=>$time,
            ];
            //得到修改的字段
            $change=Db::name('edit_info')->where('eid',$id)->value('content');
            $change=json_decode($change,true);
            
            foreach($change as $k=>$v){
                $update_info[$k]=$v;
            }
            //是否有更新料位号
            if(isset($update_info['code'])){ 
                //检查是否有重复
                $where=[ 
                    'code'=>['eq',$update_info['code']],
                    'id'=>['neq',$info['pid']],
                ]; 
                $tmp=$m->where($where)->value('id');
                if(!empty($tmp)){
                    $m->rollback();
                    $this->error('料位号已存在');
                }
            } 
           //是否调整料位
            if(isset($update_info['box'])){
                if($info['goods'] != $update_info['box_goods']){
                    $m->rollback();
                    $this->error('料位号产品已改变，此次编辑失效');
                }
               
                //原料位清空
                $update_info['goods']=0;
                $update_info['num']=0;
                //新料位赋值
                $box_data=[
                    'goods'=>$info['goods'],
                    'num'=>$info['num'],
                    'time'=>$time,
                ];
                $where=[
                    'id'=>['eq',$update_info['box']],
                    'status'=>['eq',2],
                    'goods'=>['eq',0],
                ];
                $row=$m->where($where)->update($box_data);
                if($row!==1){
                    $m->rollback();
                    $this->error('新料位更新失败，可能状态不正常或已有产品');
                }
                unset($update_info['box']);
                unset($update_info['box_goods']);
            } 
             
            $row=$m->where('id',$info['pid'])->update($update_info);
            if($row!==1){
                $m->rollback();
                $this->error('信息更新失败，请刷新后重试');
            }
        }
        //更新入库记录
        if(isset($change['goods'])){
            if($info['num'] != 0){
                $m->rollback();
                $this->error('料位号已有产品，此次编辑失效');
            }
            $where=[
                'type'=>30,
                'about'=>$info['pid'],
                'rstatus'=>4,
            ];
            $m_store_in=Db::name('store_in');
            $tmp=$m_store_in->where($where)->find();
            if(!empty($tmp)){
                if($status==2){
                    $rstatus=1;
                }else{
                    $rstatus=5;
                    //废弃
                    $m_store_goods=new StoreGoodsModel();
                    $m_store_goods->instore5($tmp);
                }
                $m_store_in->where('id',$tmp['id'])->setField('rstatus',$rstatus);
            } 
        }
         
        //审核成功，记录操作记录,发送审核信息
        
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'审核'.$info['aid'].'-'.$info['aname'].'对'.($this->flag).$info['pid'].'-'.$info['pname'].'的编辑为'.$review_status[$status],
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
     * 料位编辑记录批量删除
     * @adminMenu(
     *     'name'   => '料位编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '料位编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    
    /**
     * 料位批量删除
     * @adminMenu(
     *     'name'   => '料位批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '料位批量删除',
     *     'param'  => ''
     * )
     */
    public function del_all()
    {
         //检查库存
        if(empty($_POST['ids'])){
            $this->error('未选中信息');
        }
        $ids=$_POST['ids'];
        
        $m=$this->m; 
        $where=['id'=>['in',$ids],'num'=>['gt',0]];
        $tmp=$m->where($where)->find();
        if(!empty($tmp)){
            $this->error('只能删除产品数量为0的料位');
        }
        parent::del_all();
    }
    /**
     * 料位产品清除
     * @adminMenu(
     *     'name'   => '料位产品清除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '料位产品清除',
     *     'param'  => ''
     * )
     */
    public function goods_cancel()
    {
        $id=$this->request->param('id',0,'intval');
        $m=$this->m;
        $info=$m->where('id',$id)->find();
        if( $info['num']>0 || $info['goods']==0){
            $this->error('不存在关联产品或产品数量不为0，不能清除产品');
        }
        $m->startTrans();
        $m->where('id',$id)->setField('goods',0);
        //如果库存也没有，清除库存
        $where=[
            'store'=>$info['store'],
            'goods'=>$info['goods'],
        ];
        $store_num=Db::name('store_goods')->where($where)->value('num');
        if(empty($store_num)){
            Db::name('store_goods')->where($where)->delete();
        }
        $m->commit();
        $this->success('清除产品成功');
    }
    
    
    //相关信息
    public function cates($type=3)
    {
        parent::cates($type);
        $admin=$this->admin;
        //仓库
        $where=[
            'status'=>2, 
        ];
        $where_shop=$this->where_shop;
        if(!empty($where_shop)){
            $where['shop']=$where_shop;
        }
        //关联仓库
        $where['type']=1;
        if($type==3){
            $field='id,name';
        }else{
            $field='id,shop,name';
        }
        $stores=Db::name('store')->where($where)->order('shop asc,sort asc')->column($field);
          
        $this->assign('stores',$stores);
        
         
    }
    //参数处理
    public function param_check($data){
      //先获取货架层级信息，得到编号和计算体积
        $data['floor']=intval($data['floor']);
        $floor=Db::name('store_floor')
        ->alias('floor')
        ->field('floor.*,shelf.width,shelf.length')
        ->join('cmf_store_shelf shelf','shelf.id=floor.shelf')
        ->where('floor.id',$data['floor'])
        ->find();
        $data['store']=$floor['store'];
        $data['shelf']=$floor['shelf'];
        $data['shop']=$floor['shop'];
        $data['floor_num']=$floor['floor'];
        $data['length']=round($data['length'],2);
       
        $data['space']=round($data['length']*$floor['height']*$floor['width'],2);
        if( $data['space']<=0){
            return '料位空间错误';
        }
        //统计已有的料位，计算宽度
        $m=$this->m;
        $list=$m->where(['floor'=>$data['floor'],'status'=>2])->column('length');
        $length0=(empty($list))?0:array_sum($list);
        if(($data['length']+$length0)>$floor['length']){
            return '料位长度超出货架长度';
        }
        $data['code_num']=intval($data['code_num']);
        $data['code']=$floor['code'].'-'.str_pad( $data['code_num'], 2,'0',STR_PAD_LEFT);
     
        $data['num']=intval($data['num']);
        $data['sort']=intval($data['sort']); 
        $where=[
            'code'=>$data['code'], 
            'store'=>$data['store'],
        ];
        if(isset($data['id'])){
            $where['id']=['neq',$data['id']];
        } 
        $tmp=$m->where($where)->find();
        if(!empty($tmp)){
            return '料位编号已存在';
        }
        $data['name']=(empty($data['name']))?('料位'.$data['code']):$data['name'];
       
        return $data;
    }
    
     
}
