<?php
 
namespace app\store\controller;

 
use app\common\controller\AdminInfo0Controller; 
use think\Db; 
  
class AdminShelfController extends AdminInfo0Controller
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='货架';
        $this->table='store_shelf';
        $this->m=Db::name('store_shelf');
        $this->edit=['name','sort','dsc','floor','length','width',
            'height','num','space',
        ];
        //没有店铺区分
        $this->isshop=1;
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        
    }
    /**
     * 货架列表
     * @adminMenu(
     *     'name'   => '货架列表',
     *     'parent' => 'store/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 30,
     *     'icon'   => '',
     *     'remark' => '货架列表',
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
        
        if($this->isshop){
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
            
            $join[]=['cmf_shop shop','p.shop=shop.id','left'];
            $field.=',shop.name as sname';
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
     * 货架添加
     * @adminMenu(
     *     'name'   => '货架添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '货架添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
        $this->assign('floors',null);
        return $this->fetch();  
        
    }
    /**
     * 货架添加do
     * @adminMenu(
     *     'name'   => '货架添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '货架添加do',
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
        //判断店铺
        $data_add['shop']=($admin['shop']==1)?2:$admin['shop'];
        
        $url=url('index');
        
        $table=$this->table;
        $time=time();
       
        $data_add['status']=1; 
        $data_add['aid']=$admin['id'];
        $data_add['atime']=$time;
        $data_add['time']=$time;
        
        $m->startTrans();
        $id=$m->insertGetId($data_add);
        //添加货架层高
        if(isset($data['floors'])){
            $data_floor=[];
            foreach ($data['floors'] as $k=>$v){
                $data_floor[]=[
                    'shop'=>$data_add['shop'],
                    'store'=>$data['store'],
                    'shelf'=>$id,
                    'floor'=>$k,
                    'height'=>$v,
                    'code'=>str_pad($data['num'], 2,'0', STR_PAD_LEFT).'-'.str_pad($k, 2,'0', STR_PAD_LEFT),
                ];
            }
            Db::name('store_floor')->insertAll($data_floor);
           
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
     * 货架详情
     * @adminMenu(
     *     'name'   => '货架详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '货架详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $m=$this->m;
        $id=$this->request->param('id',0,'intval');
        $info=$m
        ->alias('p')
        ->field('p.*,s.height as store_height,a.user_nickname as aname,r.user_nickname as rname')
        ->join('cmf_store s','s.id=p.store','left')
        ->join('cmf_user a','a.id=p.aid','left')
        ->join('cmf_user r','r.id=p.rid','left')
        ->where('p.id',$id)
        ->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        $this->assign('info',$info);
        if($this->isshop){
            $this->shop=$info['shop'];
        }
        //对应分类数据
        $this->cates(); 
        $floors=Db::name('store_floor')
        ->where('shelf',$id)
        ->column('floor,height');
        $this->assign('floors',$floors);
        return $this->fetch();  
    }
    /**
     * 货架状态审核
     * @adminMenu(
     *     'name'   => '货架状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '货架状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 货架状态批量同意
     * @adminMenu(
     *     'name'   => '货架状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '货架状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    /**
     * 货架禁用
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
     * 货架信息状态恢复
     * @adminMenu(
     *     'name'   => '货架信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '货架信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 货架编辑提交
     * @adminMenu(
     *     'name'   => '货架编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '货架编辑提交',
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
        
        $fields=$this->edit;
        
        $content=[];
        //检测改变了哪些字段
        foreach($fields as $k=>$v){
            //如果原信息和$data信息相同就未改变，不为空就记录，？null测试
            if(isset($data[$v]) && $info[$v]!=$data[$v]){
                $content[$v]=$data[$v];
            } 
        }
        
        if(isset($content['num'])  ){
           
            //检查是否有重复
            $where=[
                'store'=> $info['store'],
                'num'=>$content['num'],
            ];
            $tmp=$m->where($where)->find();
            if(!empty($tmp)){
                $this->error('货架编号已存在');
            } 
        } 
        //比较层高设置
        if(isset($data['floors'])){
            $floors=Db::name('store_floor')
            ->where('shelf',$info['id'])
            ->column('floor,height');
            $floors=(empty($floors))?null:$floors;
            $floors_change=[];
            foreach($data['floors'] as $k=>$v){
                if($floors[$k] != $v){
                    $floors_change[$k]=$v;
                }
            }
            if(!empty($floors_change)){
                $content['floors']=$floors_change;
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
     * 货架编辑列表
     * @adminMenu(
     *     'name'   => '货架编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '货架编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();  
    }
    
    /**
     * 货架审核详情
     * @adminMenu(
     *     'name'   => '货架审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '货架审核详情',
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
        $info1=$m_edit->where('id',$id)->find();
        if(empty($info1)){
            $this->error('编辑信息不存在');
        }
        //获取原信息
        $info=$m->alias('p')
        ->field('p.*,s.height as store_height')
        ->join('cmf_store s','s.id=p.store','left')
        ->where('p.id',$info1['pid'])->find();
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
            $this->shop=$info['shop'];
        }
        //分类关联信息
        $this->cates();
        $floors=Db::name('store_floor')
        ->where('shelf',$info1['pid'])
        ->column('floor,height');
        $this->assign('floors',$floors);
        return $this->fetch();  
    }
    /**
     * 货架信息编辑审核
     * @adminMenu(
     *     'name'   => '货架编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '货架编辑审核',
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
        ->field('e.*,p.name as pname,p.store,p.num,p.shop as pshop,a.user_nickname as aname')
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
            //获取货架号用于层级更新
            $num=isset($update_info['num'])?$update_info['num']:$info['num'];
            $shelf_code=str_pad($num, 2,'0', STR_PAD_LEFT);
            $m_floor=Db::name('store_floor');
            $floors=$m_floor->where('shelf',$info['pid'])->column('floor,id,height');
            if(isset($update_info['floors'])){ 
                $floor_update=[];
                //为空则插入，否则更新
                if(empty($floors)){
                    foreach($update_info['floors'] as $k=>$v){
                        $floor_update[]=[
                            'shop'=>$info['pshop'],
                            'store'=>$info['store'],
                            'shelf'=>$info['pid'],
                            'floor'=>$k,
                            'height'=>$v,
                            'code'=>$shelf_code.'-'.str_pad($k, 2,'0', STR_PAD_LEFT),
                        ];
                    }
                }else{
                    foreach($update_info['floors'] as $k=>$v){
                        $floor_update[]=[ 
                            'id'=>$floors[$k]['id'],
                            'height'=>$v, 
                        ];
                    }
                }
                unset($update_info['floors']);
            }
            if(!empty($floor_update)){
                if(empty($floors)){
                    //如果是没添加过层高，直接新增
                     $m_floor->insertAll($floor_update); 
                }else{
                    //有层高的则更新
                    $tmp=[];
                    foreach($floor_update as $k=>$v){
                        $tmp=[
                             
                        ];
                        //有更新
                        if(isset($floor_update[$k])){
                            $tmp['height']=$floor_update[$k]['height'];
                        }
                        $m_floor->where('id',$v['id'])->update($tmp);
                    }
                }
            }
            if(isset($update_info['num'])){ 
                //检查是否有重复
                $where=[
                    'store'=>['eq',$info['store']] ,
                    'num'=>['eq',$update_info['num']],
                    'id'=>['neq',$info['pid']],
                ];
                $tmp=$m->where($where)->find();
                if(!empty($tmp)){
                    $this->error('货架编号已存在');
                }
                //更改了货架编号，下属的料位号也要修改
                if(empty($floors)){
                    //如果是没添加过层高，直接新增
                    if(!empty($floor_update)){
                        $m_floor->insertAll($floor_update);
                    }
                }else{
                    //有层高的则更新
                    $tmp=[];
                    foreach($floors as $k=>$v){ 
                        $tmp=[ 
                            'code'=>$shelf_code.'-'.str_pad($k, 2,'0', STR_PAD_LEFT),
                        ];
                        //有更新
                        if(isset($floor_update[$k])){
                            $tmp['height']=$floor_update[$k]['height']; 
                        }
                        $m_floor->where('id',$v['id'])->update($tmp);
                    }
                }
                
            } 
           
            $row=$m->where('id',$info['pid'])->update($update_info);
            if($row!==1){
                $m->rollback();
                $this->error('信息更新失败，请刷新后重试');
            }
        }
        
        //审核成功，记录操作记录,发送审核信息
        $review_status=$this->review_status;
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
     * 货架编辑记录批量删除
     * @adminMenu(
     *     'name'   => '货架编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '货架编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    
    /**
     * 货架批量删除
     * @adminMenu(
     *     'name'   => '货架批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '货架批量删除',
     *     'param'  => ''
     * )
     */
    public function del_all()
    {
         
        parent::del_all();
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
        
        //默认长宽高
        if($type==3){
            $shelf_size=config('shelf_size');
            $this->assign('shelf_size',$shelf_size);
        } 
    }
    //参数处理
    public function param_check($data){
        $height=0;
        $data['height0']=round($data['height0'],2);
        $data['height']=round($data['height'],2);
        $data['length']=round($data['length'],2);
        $data['width']=round($data['width'],2);
        $data['space']=round($data['height']*$data['length']*$data['width'],2);
        if( $data['space']<=0){
            return '货架长宽高设置错误';
        }
        $data['num']=intval($data['num']);
        $data['sort']=intval($data['sort']);
        $data['store']=intval($data['store']);
        if(isset($data['floors'])){
            
            foreach($data['floors'] as $k=>$v){
                $v=round($v,2);
                $height+=$v;
                $data['floors'][$k]=$v;
            } 
            
           if($height>$data['height0']){
              return '货架层高累计超过仓库高度';
           }
          
           if( $data['height'] < ($height-$v)){
               return '货架层高超过货架高度';
           }
        }
        unset($data['height0']);
       
        $where=[
            'store'=>$data['store'],
            'num'=>$data['num'],
        ];
        if(isset($data['id'])){
            $where['id']=['neq',$data['id']];
        }
        $m=$this->m;
        $tmp=$m->where($where)->find();
        if(!empty($tmp)){
            return '货架编号已存在';
        }
        $data['name']=(empty($data['name']))?('货架'.$data['num']):$data['name'];
       
        return $data;
    }
    
     
}
