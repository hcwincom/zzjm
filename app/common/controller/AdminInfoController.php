<?php
 
namespace app\common\controller;

use cmf\controller\AdminBaseController;
use think\Db; 

class AdminInfoController extends AdminBaseController
{
    protected $m;
    protected $statuss;
    protected $review_status;
    protected $table;
    protected $fields;
    protected $flag;
   
    public function _initialize()
    {
        parent::_initialize();
        
        $this->statuss=config('info_status');
        $this->review_status=config('review_status'); 
       
        $this->assign('statuss',$this->statuss);
        $this->assign('review_status',$this->review_status);
        $this->assign('html',$this->request->action());
    }
    /**
     * 信息公共类 
     */
     public function index()
    {
        $table=$this->table;
        $m=$this->m;
        $data=$this->request->param();
        $where=[];
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
        //审核人
        if(empty($data['rid'])){
            $data['rid']=0;
        }else{
            $where['p.rid']=['eq',$data['rid']];
        }
        //所属分类
        if(empty($data['cid'])){
            $data['cid']=0;
        }else{
            $where['p.cid']=['eq',$data['cid']];
        }
        //所属字母分类
        if(empty($data['char']) || $data['char']=='-1'){
            $data['char']='-1';
        }else{
            $where['p.char']=['eq',$data['char']];
        }
        //类型
        if(empty($data['type'])){
            $data['type']=0;
        }else{
            $where['p.type']=['eq',$data['type']];
        }
        //查询字段
        $types=config($table.'_search');
        if(empty($types)){
            $types=config('base_search');
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
        ->field('p.*')
        ->where($where)
        ->order('p.status asc,p.sort asc,p.time desc')
        ->paginate();
        
        // 获取分页显示
        $page = $list->appends($data)->render();
        
        $m_user=db('user');
        //创建人
        $where_aid=[
            'user_type'=>1,
            'shop'=>1,
        ];
        $aids=$m_user->where($where_aid)->column('id,user_nickname');
        //审核人
        $where_rid=[
            'user_type'=>1,
            'shop'=>1,
        ];
        $rids=$m_user->where($where_rid)->column('id,user_nickname');
        //分类信息
        $this->cates();
        $this->assign('page',$page);
        $this->assign('list',$list);
        $this->assign('aids',$aids);
        $this->assign('rids',$rids);
        $this->assign('data',$data);
        $this->assign('types',$types);
        $this->assign('times',$times);
        $this->assign("search_types", $search_types);
       
    } 
    /**
     * 信息添加
     */
    public function add()
    {
       $this->cates();
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
        
        //对应分类数据
        $this->cates();
       
        $this->assign('info',$info); 
       
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
        $flag=$this->flag; 
        $statuss=$this->statuss;
        $table=$this->table;
        //记录操作记录
        $link=url('admin/'.$table.'/edit',['id'=>$info['id']]);
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>'审核'.$flag.$info['id'].'-'.$info['name'].'的状态为'.$statuss[$status],
            'table'=>$table,
            'type'=>'review',
            'pid'=>$info['id'],
            'link'=>$link,
            'shop'=>$admin['shop'],
        ];
        //发送审核信息
        $data_msg=[
            'aid'=>1,
            'time'=>$time,
            'uid'=>$info['aid'],
            'dsc'=>'对'.$flag.$info['id'].'-'.$info['name'].'已审核，状态为'.$statuss[$status],
            'type'=>'review',
            'link'=>$link,
            'shop'=>$admin['shop'],
        ];
        db('action')->insert($data_action);
        db('msg')->insert($data_msg);
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
            $tmp=$m->where($where)->find();
            if(empty($tmp['shop']) || $tmp['shop']!=$admin['shop']){
                $this->error('不能审核其他店铺的信息');
            }else{
                $where['shop']=['eq',$admin['shop']];
            }
        }
        
        $update=[
            'status'=>2,
            'time'=>$time,
            'rid'=>$admin['id'],
            'rtime'=>$time,
        ];
        //得到要更改的数据
        $list=$m->where($where)->column('id,aid,name');
        $ids=implode(',',array_keys($list));
        
        //审核成功，记录操作记录,发送审核信息
        $flag=$this->flag;
       
        $table=$this->table;
        //记录操作记录 
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>'批量同意'.$flag.'('.$ids.')',
            'table'=>$table,
            'type'=>'review_all',
            'link'=>'',
            'shop'=>$admin['shop'],
        ];
        $link0=url('admin/'.$table.'/edit','',false,false);
        foreach($list as $k=>$v){
            //发送审核信息
            $data_msg[]=[
                'aid'=>1,
                'time'=>$time,
                'uid'=>$v['aid'],
                'dsc'=>'对'.$flag.$v['id'].'-'.$v['name'].'已批量审核，结果为同意',
                'type'=>'review',
                'link'=>$link0.'/id/'.$v['id'],
                'shop'=>$admin['shop'],
            ];
        }
        $m->startTrans();
        $rows=$m->where($where)->update($update);
        if($rows<=0){
            $m->rollback();
            $this->error('没有数据审核成功，批量审核只能把未审核的数据审核为正常');
        } 
        db('action')->insert($data_action);
        db('msg')->insertAll($data_msg);
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
            'rstatus'=>1,
            'rid'=>0,
            'rtime'=>0,
            'shop'=>$admin['shop'],
        ];
       
        $fields=config($table.'_edit');
        if(empty($fields)){
            $fields=config('base_edit');
        }
        $content=[];
        //检测改变了哪些字段
        foreach($fields as $k=>$v){
            //如果编辑结果为空 赋值
            if(!isset($data[$k])){ 
                //如果编辑结果为空 
                $data[$k]=$v;
            }
            //如果原信息和$data信息相同就未改变，不为空就记录，？null测试
            if($info[$k]!=$data[$k]){ 
                $content[$k]=$data[$k];
            }
        }
        switch ($table){
            case 'cate':
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
                
                break;
            case 'brand':
                //检查名称
                if(isset($content['name']) || isset($content['char'])){
                    if(empty($data['char'])){
                        $char=zz_first_char($data['name']);
                    }else{
                        $char=zz_first_char($data['char']);
                    }
                    
                    if(empty($char)){
                        $this->error('输入非法，无法获取首字母');
                    }
                    
                    $content['char']=$char;
                }
                
                //处理图片
                $path='upload/';
                if(!empty($content['pic'])){
                    if(is_file($path.$content['pic'])){
                        if(!is_dir($path.$info['path'])){
                            mkdir($path.$info['path']);
                        }
                        $pic_conf=config('pic_'.$table);
                        $content['pic']=$info['path'].'/'.$admin['id'].'-'.$time.'.jpg';
                        zz_set_image($data['pic'], $content['pic'], $pic_conf[0], $pic_conf[1],$pic_conf[2]);
                        unlink($path.$data['pic']);
                    }else{
                        unset($content['pic']);
                    }
                    
                }
                break;
            case 'param':
                //清除不规范输入导致的空格
                if(!empty($content['content'])){
                    //3是自由输入
                    if($info['type']==3){
                        unset($content['content']);
                    }else{
                        //清除不规范输入导致的空格
                        $content['content']=zz_delimiter($content['content']);
                    }
                }
                break;
            case 'template':
                //新关联的参数
                if(empty($_POST['ids'])){
                    $this->error('没有选择参数项');
                } 
                $ids=$_POST['ids'];  
                //原本关联的
                $ids0=db('template_param')->where('t_id',$data['id'])->column('p_id');
                 //计算新旧参数的差级，没有差级就是完全一样 
                if(!empty(array_diff($ids,$ids0)) ||  !empty(array_diff($ids0,$ids))){
                    $content['content']=implode(',', $ids);
                }
                break;
            case 'fee':
                //过滤费用值
                if(isset($content['fee'])){
                    $content['fee']=round($content['fee'],4);
                }
                break;
            case 'price':
                    $fee1=$data['fee1']; 
                    $type1=$data['type1']; 
                    $dsc1=$data['dsc1']; 
                    //获取所有价格参数来比较，暂时获取全部
                    $prices=config('prices');
                    //模板关联，获取全部，多点也没关系
                    $where=['pf.t_id'=>['eq',$data['id']]];
                    
                    $fees0=db('price_fee')
                    ->alias('pf')
                    ->where($where)
                    ->column('pf.p_id,pf.fee,pf.type,pf.dsc');
                    //循环比较
                    $data_fees=[];
                    foreach($fee1 as $k=>$vv){
                        $v=$fees0[$k];
                        $vv=round($vv,4);
                        if($vv!=$v['fee'] || $type1[$k]!=$v['type'] || $dsc1[$k]!=$v['dsc']){
                            $data_fees[$k]=[
                                'p_id'=>$k,
                                'fee'=>$vv,
                                'type'=>$type1[$k],
                                'dsc'=>$dsc1[$k],
                            ];
                        }
                    }
                    if(!empty($data_fees)){
                        $content['content']=json_encode($data_fees);
                    }
                    break;
        }
        
        if(empty($content)){
            $this->error('未修改');
        }
        //保存更改 
        $m_edit=db('edit');
        $m_edit->startTrans();
        $eid=$m_edit->insertGetId($update);
        if($eid>0){
            $data_content=[
                'eid'=>$eid,
                'content'=>json_encode($content),
            ];
            db('edit_info')->insert($data_content);
        }else{
            $m_edit->rollback();
            $this->error('保存数据错误，请重试');
        }
        //记录操作记录
        $link=url('admin/'.$table.'/edit_info',['id'=>$eid]);
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>'编辑'.$flag.$info['id'].'-'.$info['name'],
            'table'=>$table,
            'type'=>'edit',
            'pid'=>$info['id'],
            'link'=>$link,
            'shop'=>$admin['shop'],
        ];
        db('action')->insert($data_action);
        $m_edit->commit();
        $this->success('已提交修改');
    }
    /**
     * 编辑列表 
     */
    public function edit_list()
    {
         
        $table=$this->table;
        $m_edit=db('edit');
        $flag=$this->flag;
        $data=$this->request->param();
       //查找当前表的编辑
        $where=['e.table'=>['eq',$table]];
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
        $types=config($table.'_search');
        if(empty($types)){
            $types=config('base_search');
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
        $field='e.*,p.name as pname';
        $join=[['cmf_'.$table.' p','e.pid=p.id','left']];
        if($table=='cate'){
            $field='e.*,p.name as pname,p.fid,f.name as fname';
            $join[]=['cmf_cate f','p.fid=f.id','left'];
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
        $m_user=db('user');
        //创建人
        $where_aid=[
            'user_type'=>1,
            'shop'=>1,
        ];
        $aids=$m_user->where($where_aid)->column('id,user_nickname');
        //审核人
        $where_rid=[
            'user_type'=>1,
            'shop'=>1,
        ];
        $rids=$m_user->where($where_rid)->column('id,user_nickname');
        //分类信息
        $this->cates();
        $this->assign('page',$page);
        $this->assign('list',$list);
        $this->assign('aids',$aids);
        $this->assign('rids',$rids);
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
        $m_edit=db('edit');
        $info1=$m_edit->where('id',$id)->find();
        if(empty($info1)){
            $this->error('编辑信息不存在');
        }
        //获取原信息
        $info=$m->where('id',$info1['pid'])->find();
        if(empty($info)){
            $this->error('编辑关联的信息不存在');
        }
        //获取改变的信息
        $change=db('edit_info')->where('eid',$id)->value('content');
        $change=json_decode($change,true);
        
        $this->assign('info',$info);
        $this->assign('info1',$info1); 
        $this->assign('change',$change);
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
        $m_edit=db('edit');
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
        if($admin['shop']!=1){
            if(empty($info['shop']) || $info['shop']!=$admin['shop']){
                $this->error('不能审核其他店铺的信息');
            }
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
            $change=db('edit_info')->where('eid',$id)->value('content');
            $change=json_decode($change,true);
            
            foreach($change as $k=>$v){
                $update_info[$k]=$v; 
            }
            //修改了分类或编码
            if(($table=='cate') && (isset($update_info['fid']) || isset($update_info['code_num']))){
                //得到编码和fid
                $info_tmp=$m->where('id',$info['pid'])->find();
                //检查分类是否错误，级别不能错误
                if(isset($update_info['fid']) && ($info_tmp['fid']==0 || $update_info['fid']==0)){
                    $this->error('一级分类和二级分类不能直接转换');
                }
                $fid=isset($update_info['fid'])?$update_info['fid']:$info_tmp['fid'];
                $code_num=isset($update_info['code_num'])?$update_info['code_num']:$info_tmp['code_num'];
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
            $row=$m->where('id',$info['pid'])->update($update_info);
            if($row!==1){
                $m->rollback();
                $this->error('信息更新失败，请刷新后重试');
            } 
            
        }
        //分类更改子级
        if(($table=='cate') && (isset($update_info['code']))){
             
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
        $flag=$this->flag;
        $review_status=$this->review_status;
        //记录操作记录
        $link=url('admin/'.$table.'/edit_info',['id'=>$info['id']]);
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>'审核'.$info['aid'].'-'.$info['aname'].'对'.$flag.$info['pid'].'-'.$info['pname'].'的编辑为'.$review_status[$status],
            'table'=>$table,
            'type'=>'edit_review',
            'pid'=>$info['pid'],
            'link'=>$link,
            'shop'=>$admin['shop'], 
        ];
        //发送审核信息
        $data_msg=[
            'aid'=>1,
            'time'=>$time,
            'uid'=>$info['aid'],
            'dsc'=>'对'.$flag.$info['pid'].'-'.$info['pname'].'的编辑已审核，结果为'.$review_status[$status],
            'type'=>'edit_review',
            'link'=>$link,
            'shop'=>$admin['shop'],
        ];
        db('action')->insert($data_action);
        db('msg')->insert($data_msg);
         
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
        $m_edit=db('edit');
        $time=time();
        $where=[
            'e.id'=>['in',$eids], 
            'e.table'=>['eq',$table],
        ];
        
        //其他店铺检查,如果没有shop属性就只能是1号主站操作,有shop属性就带上查询条件
        if($admin['shop']!=1){
            
            $tmp=$m_edit
            ->field('e.*')
            ->alias('e') 
            ->where($where)
            ->find();
            if($tmp['shop']!=$admin['shop']){
                $this->error('不能审核其他店铺的信息');
            }else{
                $where['e.shop']=['eq',$admin['shop']];
            }
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
        
        //审核成功，记录操作记录,发送审核信息
        $flag=$this->flag;
        
        //记录操作记录
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>'批量删除'.$flag.'编辑记录('.$eidss.')',
            'table'=>$table,
            'type'=>'edit_del',
            'link'=>'',
            'shop'=>$admin['shop'],
        ];
        
        foreach($list as $k=>$v){
             
            //发送审核信息
            $data_msg[]=[
                'aid'=>1,
                'time'=>$time,
                'uid'=>$v['aid'],
                'dsc'=>date('Y-m-d H:i',$v['atime']).'对'.$flag.$v['pid'].'-'.$v['pname'].'的编辑记录已批量删除',
                'type'=>'edit_del',
                'link'=>'',
                'shop'=>$admin['shop'],
            ];
        }
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
        db('edit_info')->where(['eid'=>['in',$eids]])->delete();  
        db('action')->insert($data_action);
        db('msg')->insertAll($data_msg);
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
            $tmp=$m
            ->where($where)
            ->find();
            if(empty($tmp['shop']) || $tmp['shop']!=$admin['shop']){
                $this->error('只能操作自己店铺的信息');
            }else{
                $where['shop']=['eq',$admin['shop']];
            }
        }
       
        $count=count($ids);
        $m->startTrans();
        $tmp=$m->where($where)->delete();
        if($tmp!==$count){
            $m->rollback();
            $this->error('删除数据失败，请刷新重试');
        }
        
        //记录操作记录
        $idss=implode(',',$ids);
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>'批量删除'.$flag.'('.$idss.')',
            'table'=>$table,
            'type'=>'del',
            'link'=>'',
            'shop'=>$admin['shop'],
        ];
        db('action')->insert($data_action);
        
        //删除关联编辑记录
        $where_edit=[
            'table'=>['eq',$table],
            'pid'=>['in',$ids],
        ];
        //现获取编辑id来删除info
        $eids=db('edit')->where($where_edit)->column('id');
        if(!empty($eids)){
            db('edit_info')->where(['eid'=>['in',$eids]])->delete();
            db('edit')->where(['id'=>['in',$eids]])->delete();
        }
        //不同表处理
        switch($table){
            //模板表删除参数对应
            case 'template':
                db('template_param')->where(['t_id'=>['in',$ids]])->delete();
                break;
            case 'price':
                db('price_fee')->where(['t_id'=>['in',$ids]])->delete();
                break;
        }
        $m->commit(); 
        $this->success('成功删除数据'.$tmp.'条');
       
    }
    
    //分类信息展示
    /**
     * 分类信息
     *   */
    public function cates(){
        $table=$this->table; 
        $where_cate=['status'=>2];
        switch($table){
            case 'param': 
            case 'price':
                break;
            case 'fee': 
                $where_cate=[
                    'fid'=>0,
                    'status'=>2,
                    'table'=>$table,
                ];
                $cates=db('cate_any')->where($where_cate)->column('id,name');
                $this->assign('cates',$cates);
                
                break;
            case 'brand': 
                $cates=config('chars');
                $this->assign('cates',$cates);
                break;
            case 'template':
                //获取大类
                $where_cate['fid']=0;
                $cates=db('cate')->where($where_cate)->order('sort asc,code asc')->column('id,name');
                $this->assign('cates',$cates);
                break;
            default:
                break;
        }
    }
    
}
