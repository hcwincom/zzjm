<?php
 
namespace app\common\controller;

use cmf\controller\AdminBaseController; 
use think\Db; 
/*
 * 和admininfo功能相同，为了单个进程代码更简洁，复制了一份
 */
class AdminInfo0Controller extends AdminBaseController
{
    protected $m;
    protected $statuss;
    protected $review_status;
    protected $table;
    protected $fields;
    protected $flag;
    protected $isshop;
    //用于详情页中识别当前店铺, 
    //列表页中分店铺查询
    protected $where_shop;
    protected $edit;
    protected $search;
   
    public function _initialize()
    {
        parent::_initialize();
        
        $this->statuss=config('info_status');
        $this->review_status=config('review_status'); 
        $this->isshop=0;
         
        $this->where_shop=0; 
        $this->edit=['name','sort','dsc','code'];
        $this->search=[ 'name' => '名称','id' => 'id',];  
        $this->assign('statuss',$this->statuss);
        $this->assign('review_status',$this->review_status);
        $this->assign('html',$this->request->action());
    }
    /**
     *首页
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
                }else{
                    $where['p.shop']=['eq',$data['shop']];
                }
            }else{
                $where['p.shop']=['eq',$admin['shop']];
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
        $types=$this->search;
        
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
       
    } 
    /**
     * 信息添加
     */
    public function add()
    {
        $this->cates();
        $this->assign("info", null);
    }
    /* 添加 */
    public function add_do()
    {
        $m=$this->m;
        $data=$this->request->param();
        if(empty($data['name'])){
            $this->error('名称不能为空');
        }
        
        $url=url('index');
        
        $table=$this->table;
        $time=time();
        $admin=$this->admin;
        $data_add=$data;
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
     * 信息详情 
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
            $this->where_shop=$info['shop'];
        }
        //对应分类数据
        $this->cates(); 
    }
    
    //信息审核
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
        
        $row=$m->where('id',$id)->update($update);
         
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
     * 信息状态批量同意 
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
        $m->commit();
        $this->success('审核成功'.$rows.'条数据');
    }
    
    /**
     * 信息状态禁用 
     */
    public function ban()
    {
        //区分是一个还是数组
        $id=$this->request->param('id',0,'intval');
       
        $where=['status'=>['eq',2]];
        if($id>0){
            $where['id']=['eq',$id];
        }elseif(empty($_POST['ids'])){
            $this->error('未选中信息');
        }else{
            $ids=$_POST['ids'];
            $where['id']=['in',$ids];
        }
        //其他店铺检查,如果没有shop属性就只能是1号主站操作,有shop属性就带上查询条件
        $admin=$this->admin;
        if($admin['shop']!=1){
            if($this->isshop){
                $where['shop']=['eq',$admin['shop']];
            }else{
                $this->error('店铺不能操作系统数据');
            }
        }
        $m=$this->m;
       
        $update=['status'=>4];
        $rows=$m->where($where)->update($update);
         
        if($rows>=1){
             
            $this->success('已禁用'.$rows.'条数据');
        }else{
            $this->error('没有成功禁用数据，禁用是指将状态为正常改为禁用');
        }
    }
    /**
     * 信息状态恢复 
     */
    public function cancel_ban()
    {
        
        //区分是一个还是数组
        $id=$this->request->param('id',0,'intval');
        
        $where=['status'=>['eq',4]];
        if($id>0){
            $where['id']=['eq',$id];
        }elseif(empty($_POST['ids'])){
            $this->error('未选中信息');
        }else{
            $ids=$_POST['ids'];
            $where['id']=['in',$ids];
        }
        //其他店铺检查,如果没有shop属性就只能是1号主站操作,有shop属性就带上查询条件
        $admin=$this->admin;
        if($admin['shop']!=1){
            if($this->isshop){
                $where['shop']=['eq',$admin['shop']];
            }else{
                $this->error('店铺操作系统数据');
            }
        }
        $m=$this->m; 
        $update=['status'=>2];
        $rows=$m->where($where)->update($update);
        
        if($rows>=1){
            $this->success('已恢复'.$rows.'条数据');
        }else{
            $this->error('没有成功恢复数据,恢复是指将状态为禁用改为正常');
        }
    }
     
    /**
     * 编辑提交 
     */
    public function edit_do(){
         
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
     * 编辑列表 
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
            $where['e.shop']=['eq',$data['shop']];
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
        //查询字段
        $types=$this->search;
        //选择查询字段
        if(empty($data['type1'])){
            $data['type1']=key($types);
        } 
        //搜索类型
        $search_types=config('search_types');
        if(empty($data['type2'])){
            $data['type2']=key($search_types);
        }
        //检查拼接搜索语句
        if(empty($data['name'])){
            $data['name']='';
        }else{
            $where['p.'.$data['type1']]=zz_search($data['type2'],$data['name']);
        }
        //时间类别
        $times=config('time2_search');
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
                    $where['e.'.$data['time']]=['elt',$time2];
                }
            }else{
                //有开始时间
                $time1=strtotime($data['datetime1']);
                if(empty($data['datetime2'])){
                    $data['datetime2']='';
                    $where['e.'.$data['time']]=['egt',$time1];
                }else{
                    //有结束时间有开始时间between
                    $time2=strtotime($data['datetime2']);
                    if($time2<=$time1){
                        $this->error('结束时间必须大于起始时间');
                    }
                    $where['e.'.$data['time']]=['between',[$time1,$time2]];
                }
            }
        }
       
         
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
        $this->cates(2);
        $this->assign('page',$page);
        $this->assign('list',$list);
      
        $this->assign('data',$data);
        $this->assign('types',$types);
        $this->assign('times',$times);
        $this->assign("search_types", $search_types);
       
    }
    /**
     * 编辑审核详情 
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
        
      
        $this->assign('info',$info);
        $this->assign('info1',$info1); 
        $this->assign('change',$change);
        
        if($this->isshop){
            $this->where_shop=$info['shop'];
        }
        //分类关联信息
        $this->cates();
    }
    
    /**
     * 信息编辑审核 
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
            
            foreach($change as $k=>$v){
                $update_info[$k]=$v; 
            }
            
            $row=$m->where('id',$info['pid'])->update($update_info);
            if($row!==1){
                $m->rollback();
                $this->error('信息更新失败，请刷新后重试');
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
     * 编辑记录批量删除 
     */
    public function edit_del_all()
    {
        if(empty($_POST['ids'])){
            $this->error('未选中信息');
        }
        $eids=$_POST['ids'];
        
        $admin=$this->admin;
        $table=$this->table;
        $m_edit=Db::name('edit');
        $time=time();
        $where=[
            'e.id'=>['in',$eids], 
            'e.table'=>['eq',$table],
        ];
        
        //其他店铺检查,如果没有shop属性就只能是1号主站操作,有shop属性就带上查询条件
        if($admin['shop']!=1){
            $where['e.shop']=['eq',$admin['shop']]; 
        }
        
        //得到要删除的数据
        $list=$m_edit 
        ->alias('e')
        ->join('cmf_'.$table.' p','p.id=e.pid','left')
        ->where($where)
        ->column('e.*,p.name as pname');
        
        if(empty($list)){
            $this->error('没有要删除的数据');
        }
        $eidss=implode(',',array_keys($list));
        
        
        
        $m_edit->startTrans();
        //id 删除
        $where_edit=[
            'table'=>['eq',$table],
            'id'=>['in',$eids],
        ];
        
        $rows=$m_edit->where($where_edit)->delete();
        if($rows<=0){
            $m_edit->rollback();
            $this->error('没有删除数据');
        } 
        //删除编辑详情
        Db::name('edit_info')->where(['eid'=>['in',$eids]])->delete();  
       
        //审核成功，记录操作记录,发送审核信息
        $flag=$this->flag; 
        //记录操作记录
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'批量删除'.$flag.'编辑记录('.$eidss.')',
            'table'=>$table,
            'type'=>'edit_del',
            'link'=>'',
            'pid'=>0,
            'shop'=>$admin['shop'],
        ]; 
         
        zz_action($data_action,['eids'=>$eidss]);
        
        $m_edit->commit();
        $this->success('已批量删除'.$rows.'条数据');
    }
    /* 批量删除 */
    public function del_all()
    {
        if(empty($_POST['ids'])){
            $this->error('未选中信息');
        }
        $ids=$_POST['ids'];
        
        $m=$this->m;
        $flag=$this->flag;
        $table=$this->table;
        $admin=$this->admin;
        $time=time();
        //彻底删除
        $where=['id'=>['in',$ids]];
        //其他店铺检查,如果没有shop属性就只能是1号主站操作,有shop属性就带上查询条件 
        if($admin['shop']!=1){
            if($this->isshop){
                $where['shop']=['eq',$admin['shop']];
            }else{
                $this->error('店铺不能操作系统数据');
            }
        } 
       
        $count=count($ids);
        $m->startTrans();
        $tmp=$m->where($where)->delete();
        if($tmp!==$count){
            $m->rollback();
            $this->error('删除数据失败，请刷新重试');
        }
        
        
        
        //删除关联编辑记录
        $where_edit=[
            'table'=>['eq',$table],
            'pid'=>['in',$ids],
        ];
        //现获取编辑id来删除info
        $eids=Db::name('edit')->where($where_edit)->column('id');
        if(!empty($eids)){
            Db::name('edit_info')->where(['eid'=>['in',$eids]])->delete();
            Db::name('edit')->where(['id'=>['in',$eids]])->delete();
        }
        
        //记录操作记录
        $idss=implode(',',$ids);
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'批量删除'.$flag.'('.$idss.')',
            'table'=>$table,
            'type'=>'del',
            'link'=>'',
            'pid'=>0,
            'shop'=>$admin['shop'],
        ];
       
        zz_action($data_action,['pids'=>$idss]);
        $m->commit(); 
        $this->success('成功删除数据'.$tmp.'条');
       
    }
    /**
     * 分类信息,1-index,2-edit_index,3-add,edit,edit_info
     *   */
    public function cates($type=3){
        $admin=$this->admin;
         
        if($type<3){
            //显示编辑人和审核人
            $m_user=Db::name('user');
            //可以加权限判断，目前未加
            //创建人
            $where_aid=[
                'user_type'=>1,
                'shop'=>['in',[1,$admin['shop']]],
            ];
            
            $aids=$m_user->where($where_aid)->column('id,user_nickname');
            //审核人
            $where_rid=[
                'user_type'=>1,
                'shop'=>['in',[1,$admin['shop']]],
            ];
            $rids=$m_user->where($where_rid)->column('id,user_nickname');
            $this->assign('aids',$aids);
            $this->assign('rids',$rids);
            
            //如果分店铺又是列表页查找,显示所有店铺
            if($admin['shop']==1 && ($this->isshop) ){
                $shops=Db::name('shop')->where('status',2)->column('id,name');
                //首页列表页去除总站
                if($type==1){
                    unset($shops[1]);
                }
               
                $this->assign('shops',$shops);
            }
            
        } 
         
    }
    
}
