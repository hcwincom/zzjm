<?php
 
namespace app\goods\controller;

 
use think\Db; 
/* 产品信息和产品分类的添加，编辑，审核不继承父级 */
class AdminCateController extends GoodsBaseController
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='产品分类';
        $this->table='cate';
        $this->m=Db::name('cate');
        $this->edit=['name','sort','dsc','fid','code_num','type'];
         
        $this->search =[ 
            'name' => '名称',
            'code' => '编码',
            'id' => 'id',
        ];
        $this->assign('cate_type',[1=>'产品',2=>'设备']);
        $this->assign('flag','产品分类');
         
    }
     
    /**
     * 产品分类
     * @adminMenu(
     *     'name'   => '产品分类',
     *     'parent' => 'goods/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类',
     *     'param'  => ''
     * )
     */
    public function index()
    { 
       
        $m=$this->m;
//         $where=['status'=>2];
        $where=[];
        $tmp=$m->where($where)->order('fid asc,sort asc,code_num asc')->column('id,name,code,fid,type,sort,status');
        $list=[];
        foreach($tmp as $k=>$v){
            if($v['fid']==0){
                $list[$v['id']][]=$v;
            }else{
                $list[$v['fid']][]=$v;
            }
        }
        
        $this->assign('list',$list); 
        
        return $this->fetch();
    }
   
    /**
     * 产品分类添加
     * @adminMenu(
     *     'name'   => '产品分类添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        
        $m=$this->m;
        $id=$this->request->param('id',0,'intval');
        $info=$m->where('id',$id)->find();
        if(empty($info)){
           $fid=0;
        }elseif($info['fid']==0){
            $fid=$info['id'];
        }else{
            $fid=$info['fid'];
        }
        
        $this->assign('fid',$fid);
        $this->assign('info',null);
        $this->cates();
        return $this->fetch();
    }
    /**
     * 产品分类添加do
     * @adminMenu(
     *     'name'   => '产品分类添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        $m=$this->m;
        $data=$this->request->param('');
        if(empty($data['name'])){
            $this->error('名称不能为空');
        }
        $url=url('index');
        $fid=intval($data['fid']);
        $time=time();
        $admin=$this->admin;
        $data_add=[
            'name'=>$data['name'],
            'fid'=>$fid,
            'sort'=>intval($data['sort']),
            'code_num'=>intval($data['code_num']),
            'type'=>intval($data['type']),
            'status'=>1,
            'aid'=>$admin['id'],
            'atime'=>$time,
            'time'=>$time,
        ];
        if($data_add['code_num']<=0){
            $this->error('编码错误');
        }
        //二级分类类型跟随父级
        if($fid!=0){
            $fcate=$m->where(['id'=>$fid])->find();
            $data_add['type']=$fcate['type'];
        }
        //检查编码是否合法
        $where=['code_num'=>$data_add['code_num'],'fid'=>$fid];
        $tmp=$m->where($where)->find();
        if(!empty($tmp)){
            $this->error('该编码已存在');
        }
        if($fid==0){ 
            $max_code=config('cate_max');
            //如果一级分类要更新配置中记录的最大编码
            if($max_code<$data_add['code_num']){
                cmf_set_dynamic_config(['cate_max'=>$data_add['code_num']]);
            } 
            $data_add['code']=(str_pad($data_add['code_num'],2,'0',STR_PAD_LEFT));
        }else{
            //，如果是2级要更新一级中的最大编码 
            if($data_add['code_num'] > $fcate['max_num']){
                $m->where(['id'=>$fid])->update(['max_num'=>$data_add['code_num']]); 
            } 
            $data_add['code']=$fcate['code'].'-'.(str_pad($data_add['code_num'],2,'0',STR_PAD_LEFT));
        }
        try {
            $id=$m->insertGetId($data_add);
        } catch (\Exception $e) {
            $this->error('操作失败，请重试');
        }
        
        //记录操作记录
        $flag=$this->flag;
        $table=$this->table; 
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>'添加'.$flag.$id.'-'.$data['name'],
            'table'=>$table,
            'type'=>'add',
            'pid'=>$id,
            'link'=>url('admin/'.$table.'/edit',['id'=>$id]),
            'shop'=>$admin['shop'],
        ];
        Db::name('action')->insert($data_action);
        
        $this->success('添加成功',$url);
    }
    /**
     * 产品分类创建记录
     * @adminMenu(
     *     'name'   => '产品分类创建记录',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类创建记录',
     *     'param'  => ''
     * )
     */
    public function create()
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
        if(empty($data['fid'])){
            $data['fid']=0;
        }else{
            $where['p.fid']=['eq',$data['fid']];
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
        return $this->fetch();
    }
    /**
     * 产品分类详情
     * @adminMenu(
     *     'name'   => '产品分类详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类详情',
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
        if($info['fid']==0){
            $cates=['0'=>['id'=>0,'name'=>'一级分类','type'=>1,'code'=>0]];
        }else{
            $cates=$m->where('fid',0)->order('sort asc,code_num asc')->column('id,name,type,code');
        }
        
        $this->assign('fid',$info['fid']);
        $this->assign('info',$info);
        $this->assign('cates',$cates);
        return $this->fetch();
    }
    /**
     * 产品分类状态审核
     * @adminMenu(
     *     'name'   => '产品分类状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 产品分类状态批量同意
     * @adminMenu(
     *     'name'   => '产品分类状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    /**
     * 产品分类禁用
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
     * 产品分类信息状态恢复
     * @adminMenu(
     *     'name'   => '产品分类信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 产品分类编辑提交
     * @adminMenu(
     *     'name'   => '产品分类编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类编辑提交',
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
        //二级分类类型跟随父级 
        if(isset($content['type']) && $info['fid']!=0){
            unset($content['type']);
        }
        //修改了分类或编码
        if(isset($content['fid']) || isset($content['code_num'])){
            //检查分类是否错误，级别不能错误
            if(isset($content['fid']) && ($content['fid']==0 || $data['fid']==0)){
                $this->error('一级分类和二级分类不能直接转换');
            }
            //检查编码是否合法
            $where=['code_num'=>$data['code_num'],'fid'=>$data['fid']];
            $tmp=$m->where($where)->find();
            if(!empty($tmp)){
                $this->error('该编码已存在');
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
     * 产品分类编辑列表
     * @adminMenu(
     *     'name'   => '产品分类编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();  
    }
    
    /**
     * 产品分类审核详情
     * @adminMenu(
     *     'name'   => '产品分类审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类审核详情',
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
        $info=$m->where('id',$info1['pid'])->find();
        if(empty($info)){
            $this->error('编辑关联的信息不存在');
        }
        //获取改变的信息  
        $change=Db::name('edit_info')->where('eid',$id)->value('content');
        $change=json_decode($change,true);
       
        $this->assign('fid',$info['fid']);
        $this->assign('info',$info);
        $this->assign('info1',$info1);
        $this->cates();
        $this->assign('change',$change);
        return $this->fetch();
    }
    /**
     * 产品分类信息编辑审核
     * @adminMenu(
     *     'name'   => '产品分类编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类编辑审核',
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
        ->field('e.*,p.name as pname,p.fid as pfid,p.type as ptype,p.code_num as pcode_num,a.user_nickname as aname')
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
            //有修改类型要同步下级
            if(isset($update_info['type']) && $info['pfid']==0){
                $m->where('fid',$info['pid'])->update(['type'=>$update_info['type']]);
             }
            //修改了分类或编码
            if((isset($update_info['fid']) || isset($update_info['code_num']))){
                 
                //检查分类是否错误，级别不能错误
                if(isset($update_info['fid']) && ($info['pfid']==0 || $update_info['fid']==0)){
                    $this->error('一级分类和二级分类不能直接转换');
                }
                //得到编码和fid
                $fid=isset($update_info['fid'])?$update_info['fid']:$info['pfid'];
                $code_num=isset($update_info['code_num'])?$update_info['code_num']:$info['pcode_num'];
                //检查编码是否合法
                $where=['code_num'=>$code_num,'fid'=>$fid];
                $tmp=$m->where($where)->find();
                if(!empty($tmp)){
                    $this->error('该编码已存在');
                }
                if($fid==0){
                    $max_code=config('cate_max');
                    //如果一级分类要更新配置中记录的最大编码
                    if($max_code<$code_num){
                        cmf_set_dynamic_config(['cate_max'=>$code_num]);
                    }
                    $update_info['code']=(str_pad($code_num,2,'0',STR_PAD_LEFT));
                }else{
                    //，如果是2级要更新一级中的最大编码
                    $fcate=$m->where(['id'=>$fid])->find();
                    if($code_num > $fcate['max_num']){
                        $m->where(['id'=>$fid])->update(['max_num'=>$code_num]);
                    }
                    $update_info['code']=$fcate['code'].'-'.(str_pad($code_num,2,'0',STR_PAD_LEFT));
                }
                
            }
            //修改了类型
            
            $row=$m->where('id',$info['pid'])->update($update_info);
            if($row!==1){
                $m->rollback();
                $this->error('信息更新失败，请刷新后重试');
            }
        }
        //分类更改子级
        if(isset($update_info['code'])){
            
            //一级分类要更新下级编码
            if($fid==0){
                //获取下级分类更新编码
                $cates=$m->where('fid',$info['pid'])->column('id,code_num');
                
                foreach($cates as $k=>$v){
                    $data_cate=[
                        'id'=>$k,
                        'code'=>$update_info['code'].'-'.(str_pad($v,2,'0',STR_PAD_LEFT)),
                    ];
                    $m->update($data_cate);
                    //更新产品编码
                    $sql='update cmf_goods set code=concat("'.$data_cate['code'].'","-0",code_num) '.
                        'where cid='.$data_cate['id'].' and code_num<10';
                    Db::execute($sql);
                    $sql='update cmf_goods set code=concat("'.$data_cate['code'].'","-",code_num) '.
                        'where cid='.$data_cate['id'].' and code_num>=10';
                    Db::execute($sql);
                }
            }else{
                
                //更新产品编码
                $sql='update cmf_goods set cid0='.$fid.',code=concat("'.$update_info['code'].'","-0",code_num) '.
                    'where cid='.$info['id'].' and code_num<10';
                Db::execute($sql);
                $sql='update cmf_goods set code=concat("'.$update_info['code'].'","-",code_num) '.
                    'where cid='.$info['id'].' and code_num>=10';
                Db::execute($sql);
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
     * 产品分类编辑记录批量删除
     * @adminMenu(
     *     'name'   => '产品分类编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    /**
     * 产品分类批量删除
     * @adminMenu(
     *     'name'   => '产品分类批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品分类批量删除',
     *     'param'  => ''
     * )
     */
    public function del_all()
    {
        if(empty($_POST['ids'])){
            $this->error('未选中信息');
        }
        $ids=$_POST['ids'];
        
        $m=$this->m;
        //先检查是否有子类
        $where=['fid'=>['in',$ids]];
        $tmp=$m->where($where)->find();
        if(!empty($tmp)){
            $this->error('分类'.$tmp['fid'].'下有子类'.$tmp['name'].$tmp['code']);
        }
        //检查是否有产品
        $where=['cid'=>['in',$ids]];
        $tmp=Db::name('goods')->where($where)->find();
        if(!empty($tmp)){
            $this->error('分类'.$tmp['cid'].'下有产品'.$tmp['name'].$tmp['code']);
        }
        parent::del_all();
        
    }
    
    public function cates($type=3){
        parent::cates($type);
        $m=$this->m;
        $cates=$m->where('fid',0)->order('sort asc,code_num asc')->column('id,name,type,code');
         
        $this->assign('cates',$cates);
    }
    //获取同级分类编码
    public function cid_change(){
        $id=$this->request->param('id',0,'intval');
        $fid=$this->request->param('cid',0,'intval');
        $m=$this->m;
        
        if($id!=0){
            $info=$m->where(['id'=>$id])->find();
            //分类没变
            if($info['fid']==$fid){
                $this->success($info['code_num']);
                exit;
            }
        }
        if($fid==0){
            $max_code=config('cate_max'); 
        }else{
            //比较父类中记录的最大值和查找到的最大值
            $max_code=$m->where(['id'=>$fid])->value('max_num'); 
        }
        $this->success($max_code+1);
    }
    //获取同级分类编码
    public function code_change(){
        $id=$this->request->param('id',0,'intval');
        $fid=$this->request->param('cid',0,'intval');
        $code_num=$this->request->param('code_num',0,'intval');
        $m=$this->m;
        //检查编码是否合法
        $where=['code_num'=>$code_num,'fid'=>$fid];
        $tmp=$m->where($where)->find();
        if(!empty($tmp) && $tmp['id']!=$id){
            $this->error('该编码已存在');
        } 
        $this->success('ok');
    }
    //获取分类下所有产品
    public function goods(){
        $cid=$this->request->param('cid');
        $where=[
            'cid'=>$cid,
            'status'=>2,
        ];
        $admin=$this->admin;
        $where['shop']=($admin['shop']==1)?2:$admin['shop'];
        $goods=Db::name('goods')->where($where)->column('id,name');
        $this->success('ok','',$goods);
    }
    
}
