<?php
 
namespace app\operation\controller;

 
use app\common\controller\AdminInfo0Controller; 
use think\Db; 
use Qiniu\Processing\Operation;
use app\operation\model\OperationModel;
  
class AdminOperationController extends AdminInfo0Controller
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='产品运营';
        $this->table='operation';
        $this->m=new OperationModel();
        $this->edit=['name','sort','dsc','cid','cid0', 
            'company_num', 'propagate_num','foreign_num','online_num'
        ];
       
        //没有店铺区分
        $this->isshop=1;
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
         
    }
    /**
     * 产品运营列表
     * @adminMenu(
     *     'name'   => '产品运营列表',
     *     'parent' => 'operation/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 2,
     *     'icon'   => '',
     *     'remark' => '产品运营列表',
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
            ['cmf_user a','a.id=p.aid','left'],
            ['cmf_user r','r.id=p.rid','left'],
            
        ];
        $field='p.*,a.user_nickname as aname,r.user_nickname as rname';
        //店铺,分店只能看到自己的数据，总店可以选择店铺
        if($this->isshop){
            $join[]= ['cmf_shop shop','p.shop=shop.id','left'];
            $field.=',shop.name as sname';
            //店铺,分店只能看到自己的数据，总店可以选择店铺
            if($admin['shop']==1){
                if(empty($data['shop'])){
                    $data['shop']=0;
                    $this->where_shop=2;
                }else{
                    $where['p.shop']=['eq',$data['shop']];
                    $this->where_shop=$data['shop'];
                }
            }else{
                $where['p.shop']=['eq',$admin['shop']];
                $this->where_shop=$admin['shop'];
            }
        }
        
        
        
        //状态
        if(empty($data['status'])){
            $data['status']=0;
        }else{
            $where['p.status']=['eq',$data['status']];
        }
        //分类
        if(empty($data['cid'])){
            $data['cid']=0;
        }else{
            $where['p.cid']=['eq',$data['cid']];
        }
        //分类
        if(empty($data['cid0'])){
            $data['cid0']=0;
        }else{
            $where['p.cid0']=['eq',$data['cid0']];
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
        
        //类型
        if(empty($data['type'])){
            $data['type']=0;
        }else{
            $where['p.type']=['eq',$data['type']];
        }
        //查询字段 
        $types=[
            'name' => '名称',
            'id' => 'id',
            'keywords' => '关键字',
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
            $where['p.'.$data['type1']]=zz_search($data['type2'],$data['name']);
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
     * 产品运营添加
     * @adminMenu(
     *     'name'   => '产品运营添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品运营添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        $admin=$this->admin;
        
        if($this->isshop){
            $this->where_shop=($admin['shop']==1)?2:$admin['shop'];
        }
        
        $this->cates();
        $this->assign("info", null);
        $this->assign("operation_webs", null);
        $this->assign("operation_companys", null); 
        $this->assign("operation_goods", null); 
        return $this->fetch();  
        
    }
    /**
     * 产品运营添加do
     * @adminMenu(
     *     'name'   => '产品运营添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品运营添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        $m=$this->m;
        $data=$this->request->param(); 
        $url=url('index'); 
        $table=$this->table;
        $time=time();
        $admin=$this->admin;
        $data_add0=$this->param_check($data);
        if(!is_array($data_add0)){
            $this->error($data_add0);
        }
        $data_add=[ 
            'name'=>$data_add0['name'],
            'dsc'=>$data_add0['dsc'],
            'keywords'=>$data_add0['keywords'],
            'cid'=>$data_add0['cid'],
            'cid0'=>$data_add0['cid0'],
           
        ];
        //判断是否有店铺
        if($this->isshop){
            $data_add['shop']=($admin['shop']==1)?2:$admin['shop'];
        }
        $data_add['sort']=intval($data['sort']);
        $data_add['status']=1;
        
        $data_add['aid']=$admin['id'];
        $data_add['atime']=$time;
        $data_add['time']=$time;
        
        $m->startTrans();
        $id=$m->insertGetId($data_add);
        //添加关联产品
        $data_goods=[];
        foreach($data['goods_sort'] as $k=>$v){
            $data_goods[]=[
                'operation'=>$id,
                'goods'=>$k,
                'sort'=>$v,
            ];
        }
        Db::name('operation_goods')->insertAll($data_goods);
        //添加运营网址
        $data_companys=[];
        foreach($data['company'] as $k=>$v){
            $data_companys[]=[
                'operation'=>$id,
                'company'=>$v,
                'code'=>$data['code'][$k],
                'url'=>$data['url'][$k],  
                'is_online'=>$data['is_online'][$k],
                'is_propagate'=>$data['is_propagate'][$k],
                'is_foreign'=>$data['is_foreign'][$k],
                'is_end'=>$data['is_end'][$k], 
            ];
        }
        Db::name('operation_company')->insertAll($data_companys);
        
        //参考链接
        if(!empty($data['web_id'])){
            $data_webs=[];
            foreach($data['web_id'] as $k=>$v){
                $data_webs[]=[
                    'operation'=>$id,
                    'web'=>$v,
                    'code'=>$data['web_code'][$k],
                    'url'=>$data['web_url'][$k], 
                ];
            }
            Db::name('operation_like')->insertAll($data_webs);
        }
        $m->status_count($id);
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
        
        zz_action($data_action,$admin);
        $m->commit();
        //直接审核
        $rule='review';
        $res=$this->check_review($admin,$rule);
        if($res){
            $this->redirect($rule,['id'=>$id,'status'=>2]);
        }
        $this->success('添加成功',$url);
        
    }
    /**
     * 产品运营详情
     * @adminMenu(
     *     'name'   => '产品运营详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品运营详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $m=$this->m;
        $id=$this->request->param('id',0,'intval');
        $info=$m
        ->alias('p')
        ->field('p.*,a.user_nickname as aname,r.user_nickname as rname')
        ->join('cmf_user a','a.id=p.aid','left')
        ->join('cmf_user r','r.id=p.rid','left')
        ->where('p.id',$id)
        ->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        $this->assign('info',$info);
        if($this->isshop){
            $admin=$this->admin;
            if($admin['shop']!=1 && $admin['shop']!=$info['shop']){
                $this->error('只能查看自己店铺的编辑信息');
            }
            $this->where_shop=$info['shop'];
        }
        $operation_webs=Db::name('operation_like')->where('operation',$info['id'])->column('');
        $operation_companys=Db::name('operation_company')->where('operation',$info['id'])->column('*','company');
        $operation_goods=Db::name('operation_goods')->where('operation',$info['id'])->order('sort asc')->column('goods,sort,operation');
        $operation_goods=$m->get_goods_info($operation_goods);
        $this->assign("operation_webs", $operation_webs);
        $this->assign("operation_companys", $operation_companys);
        $this->assign("operation_goods", $operation_goods); 
       //分类
        $this->assign("operation_cid", $info['cid']); 
        $this->assign("operation_cid0", $info['cid0']); 
        //对应分类数据
        $this->cates(); 
        return $this->fetch();  
    }
    /**
     * 产品运营状态审核
     * @adminMenu(
     *     'name'   => '产品运营状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品运营状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 产品运营状态批量同意
     * @adminMenu(
     *     'name'   => '产品运营状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品运营状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    /**
     * 产品运营禁用
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
     * 产品运营信息状态恢复
     * @adminMenu(
     *     'name'   => '产品运营信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品运营信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 产品运营编辑提交
     * @adminMenu(
     *     'name'   => '产品运营编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品运营编辑提交',
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
        $info=$info->getData();
        dump($info);
        $time=time();
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1){
            if(empty($info['shop']) || $info['shop']!=$admin['shop']){
                $this->error('不能编辑其他店铺的信息');
            }
        }
        $operation_webs=Db::name('operation_like')->where('operation',$info['id'])->column('');
        $operation_companys=Db::name('operation_company')->where('operation',$info['id'])->column('*','company');
        $operation_goods=Db::name('operation_goods')->where('operation',$info['id'])->column('goods,sort');
        
       
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
        $content=[];
        //运营信息
        $data_edit=[
            'name'=>$data['name'],
            'dsc'=>$data['dsc'],
            'keywords'=>$data['keywords'],
            'cid'=>$data['cid'], 
        ]; 
        //检测改变了哪些字段
        foreach($data_edit as $k=>$v){
            //如果原信息和$data信息相同就未改变，不为空就记录，？null测试
            if($info[$k]!= $v){
                $content[$k]=$v;
            } 
        }
        //关联产品比较  
        //先比较修改和新增
        foreach($data['goods_sort'] as $k=>$v){
            if(isset($operation_goods[$k])){
                if($operation_goods[$k]!=$v){
                    $content['goods']['edit'][$k]=[ 
                        'sort'=>$v,
                    ]; 
                }
            }else{
                $content['goods']['add'][$k]=[ 
                    'sort'=>$v,
                    'operation'=>$info['id'],
                    'goods'=>$k,
                ]; 
            }
        }
        //比较删除
        foreach($operation_goods as $k=>$v){
            if(!isset($data['goods_sort'][$k])){
                $content['goods']['del'][$k]=$k;
            } 
        }
        //运营网址 
        //先比较修改和新增
        $field_company=[
            'code', 'url','is_online', 'is_propagate','is_foreign','is_end'
        ];
        foreach($data['company'] as $k=>$v){
            
            if(isset($operation_companys[$k])){
                foreach($field_company as $kk=>$vv){
                    if($operation_companys[$k][$vv] != $data[$vv][$k]){
                        $content['company']['edit'][$k][$vv]=$data[$vv][$k];
                    }
                }
            }else{
                $content['company']['add'][$k]=[
                    'operation'=>$info['id'],
                    'company'=>$v, 
                ];
                foreach($field_company as $kk=>$vv){
                    $content['company']['add'][$k][$vv]=$data[$vv][$k]; 
                }
            }
        }
        //先比较修改和删除
        foreach($operation_companys as $k=>$v){
            if(!isset($data['company'][$k])){
                $content['company']['del'][$k]=$k;
            }
        }
        
        //参考链接
        //先比较修改和新增
        $field_web=[
            'code', 'url', 
        ];
        //没有参考链接
        if(empty($data['web_id'])){
            $data['web_id']=[];
        }
        //先比较修改和新增
        foreach($data['web_id'] as $k=>$v){
            if(isset($operation_webs[$k])){
                foreach($field_web as $kk=>$vv){
                    if($operation_webs[$k][$vv] != $data[$vv][$k]){
                        $content['web']['edit'][$k][$vv]=$data['web_'.$vv][$k];
                    }
                } 
            }else{
                $content['web']['add'][$k]=[
                    'operation'=>$info['id'], 
                    'web'=>$v, 
                ];
               
                foreach($field_web as $kk=>$vv){
                    $content['web']['add'][$k][$vv]=$data['web_'.$vv][$k];
                }
            }
        }
        //比较删除
        foreach($operation_webs as $k=>$v){
            if(!isset($data['web_id'][$k])){
                $content['web']['del'][$k]=$k;
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
        
        zz_action($data_action,$admin);
        $m_edit->commit(); 
        //判断是否直接审核
        $rule='edit_review';
        $res=$this->check_review($admin,$rule);
        if($res){
            $this->redirect($rule,['id'=>$eid,'rstatus'=>2,'rdsc'=>'直接审核']);
        }
        
        $this->success('已提交修改');
    }
    /**
     * 产品运营编辑列表
     * @adminMenu(
     *     'name'   => '产品运营编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品运营编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();  
    }
    
    /**
     * 产品运营审核详情
     * @adminMenu(
     *     'name'   => '产品运营审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品运营审核详情',
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
        $admin=$this->admin;
        if($admin['shop']!=1 && $admin['shop']!=$info1['shop']){
            $this->error('只能查看自己店铺的编辑信息');
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
        //分类修改
        if(isset($change['cid'])){
            $cname=Db::name('operation_cate')
            ->alias('c2')
            ->field('c1.name as cname1,c2.name as cname2')
            ->join('cmf_operation_cate c1','c1.id=c2.fid')
            ->where('c2.id',$change['cid'])
            ->find();
            
            $change['cname']=empty($cname)?'不存在':($cname['cname1'].$cname['cname2']);
        }
        //产品添加
        if(isset($change['goods']['add'])){
            $change['goods']['add']=$m->get_goods_info($change['goods']['add']);
        }
        
        $this->assign('info',$info);
        $this->assign('info1',$info1);
        $this->assign('change',$change);
        
        if($this->isshop){
            $this->where_shop=$info['shop'];
        }
        //分类关联信息
        $this->cates();
        $operation_webs=Db::name('operation_like')->where('operation',$info['id'])->column('');
        $operation_companys=Db::name('operation_company')->where('operation',$info['id'])->column('*','company');
        $operation_goods=Db::name('operation_goods')->where('operation',$info['id'])->order('sort asc')->column('goods,sort,operation');
        $operation_goods=$m->get_goods_info($operation_goods);
        $this->assign("operation_webs", $operation_webs);
        $this->assign("operation_companys", $operation_companys);
        $this->assign("operation_goods", $operation_goods);
        //分类
        $this->assign("operation_cid", $info['cid']);
        $this->assign("operation_cid0", $info['cid0']); 
       
        return $this->fetch();  
    }
    /**
     * 产品运营信息编辑审核
     * @adminMenu(
     *     'name'   => '产品运营编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品运营编辑审核',
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
        ->field('e.*,p.name as pname,a.user_nickname as aname')
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
            //产品处理
            $m_op_goods=Db::name('operation_goods');
            //先删除
            if(isset($change['goods']['del'])){
                $where_del=[
                    'operation'=>$info['pid'],
                    'goods'=>['in',$change['goods']['del']],
                ];
                $m_op_goods->where($where_del)->delete(); 
            }
            //编辑
            if(isset($change['goods']['edit'])){
                foreach($change['goods']['edit'] as $k=>$v){
                    $where_update=[
                        'operation'=>$info['pid'],
                        'goods'=>$k,
                    ];
                    $m_op_goods->where($where_update)->update($v);
                } 
            }
            //添加
            if(isset($change['goods']['add'])){
                //先删除可能已添加过的 
                $where_del=[
                    'operation'=>$info['pid'],
                    'goods'=>['in',array_keys($change['goods']['add'])],
                ];
                $m_op_goods->where($where_del)->delete(); 
                //添加
                $m_op_goods->insertAll($change['goods']['add']); 
            }
            //产品处理完成
            if(isset($change['goods'])){
                unset($change['goods']);
            }
            //运营链接处理
            $m_op_company=Db::name('operation_company');
            //先删除
            if(isset($change['company']['del'])){
                $where_del=[
                    'operation'=>$info['pid'],
                    'company'=>['in',$change['company']['del']],
                ];
                $m_op_company->where($where_del)->delete();
            }
            //编辑
            if(isset($change['company']['edit'])){
                foreach($change['company']['edit'] as $k=>$v){
                    $where_update=[
                        'operation'=>$info['pid'],
                        'company'=>$k,
                    ];
                    $m_op_company->where($where_update)->update($v);
                }
            }
            //添加
            if(isset($change['company']['add'])){
                //先删除可能已添加过的
                $where_del=[
                    'operation'=>$info['pid'],
                    'company'=>['in',array_keys($change['company']['add'])],
                ];
                $m_op_company->where($where_del)->delete();
                //添加
                $m_op_company->insertAll($change['company']['add']);
            }
            //运营链接处理完成
            if(isset($change['company'])){
                unset($change['company']);
            }
            
            //参考连接处理
            $m_op_web=Db::name('operation_like');
            //先删除
            if(isset($change['web']['del'])){
                $where_del=[ 
                    'id'=>['in',$change['web']['del']],
                ];
                $m_op_web->where($where_del)->delete();
            }
            //编辑
            if(isset($change['web']['edit'])){
                foreach($change['web']['edit'] as $k=>$v){
                    $where_update=[ 
                        'id'=>$k,
                    ];
                    $m_op_web->where($where_update)->update($v);
                }
            }
            //添加
            if(isset($change['web']['add'])){
                //链接添加可以有重复，所以没有删除环节
                //添加
                $m_op_web->insertAll($change['web']['add']);
            }
            //运营链接处理完成
            if(isset($change['web'])){
                unset($change['web']);
            }
            
            foreach($change as $k=>$v){
                $update_info[$k]=$v;
            }
            
            $row=$m->where('id',$info['pid'])->update($update_info);
            if($row!==1){
                $m->rollback();
                $this->error('信息更新失败，请刷新后重试');
            }
            $m->status_count($info['pid']);
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
     * 产品运营编辑记录批量删除
     * @adminMenu(
     *     'name'   => '产品运营编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品运营编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    
    /**
     * 产品运营批量删除
     * @adminMenu(
     *     'name'   => '产品运营批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品运营批量删除',
     *     'param'  => ''
     * )
     */
    public function del_all()
    { 
        parent::del_all();
    }
   
    public function cates($type=3){
        parent::cates($type);
        $shop=$this->where_shop;
        session('where_shop',$shop);
        
        //平台
        $where_company=['status'=>2,'shop'=>$shop,'type'=>['gt',1]];
        $companys=Db::name('company')->where($where_company)->order('sort asc')->column('id,name,goods_url');
        
        //参考链接
        $where_web=['status'=>2,'shop'=>$shop];
        $webs=Db::name('operation_web')->where($where_web)->order('sort asc')->column('id,name,goods_url');
         
        $this->assign('companys',$companys);
        $this->assign('webs',$webs);
    }
    /**
     * 检查参数
     * @param array $data
     * @return array|string错误返回错误说明
     */
    public function param_check($data)
    {
        if(empty($data['name'])){
            return '名称不能为空';
        }
        if(empty($data['operation_cid'])){
            return '分类不能为空';
        }else{
            $data['cid']=intval($data['operation_cid']);
            $data['cid0']=Db::name('operation_cate')->where('id',$data['cid'])->value('fid');
            if(empty($data['cid0'])){
                return '分类错误';
            }
        }
        if(empty($data['goods_id'])){
            return '没有选择产品';
        }
        if(empty($data['company'])){
            return '没有添加运营链接';
        }
       //处理上架状态
        foreach($data['company'] as $k=>$v){ 
            $data['is_online'][$k]=empty($data['is_online'][$k])?2:1;
            $data['is_propagate'][$k]=empty($data['is_propagate'][$k])?2:1;
            $data['is_foreign'][$k]=empty($data['is_foreign'][$k])?2:1;
            $data['is_end'][$k]=empty($data['is_end'][$k])?2:1;
        }
       
        return $data;
    }
}
