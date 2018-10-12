<?php
 
namespace app\order\controller;

 
use app\common\controller\AdminInfo0Controller; 
use think\Db; 
  
class OrderBaseController extends AdminInfo0Controller
{
    
    public function _initialize()
    {
        parent::_initialize();
        
        //没有店铺区分
        $this->isshop=1;
        $this->edit=['name','company','cid','city_code','code_num','postcode','paytype',
            'email','mobile','level','url','shopurl','wechat','qq','fax',
            'province','city','area','street','other','announcement','invoice_type',
            'tax_point','freight','payer','dsc','sort',
        ];
        $this->search=[
            'p.name'=>'客户名称', 
            'p.code'=>'客户编码',
            'p.email'=>'客户邮箱',
            'p.mobile'=>'客户电话', 
            'tels.name'=>'联系人姓名',
            'tels.mobile|tels.mobile1|tels.mobile2|tels.phone|tels.phone1'=>'联系人手机',
            'tels.qq|p.qq'=>'联系人qq',
            'tels.wechat|p.wechat'=>'微信',
            'tels.taobaoid|tels.aliid'=>'淘宝阿里id'
        ];
      
        
    }
    /**
     * 首页
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
        //所属公司
        if(empty($data['company'])){
            $data['company']=0;
        }else{
            $where['p.company']=['eq',$data['company']];
        }
        //付款类型
        if(empty($data['paytype'])){
            $data['paytype']=0;
        }else{
            $where['p.paytype']=['eq',$data['paytype']];
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
            $where[$data['type1']]=zz_search($data['type2'],$data['name']);
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
 
        //先查询得到id再关联得到数据，否则sql查询太慢
        $list=$m
        ->alias('p')
        ->field('p.id')   
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
                ['cmf_user a','a.id=p.aid','left'],
                ['cmf_user r','r.id=p.rid','left'],
                ['cmf_shop shop','p.shop=shop.id','left'], 
                
            ];
            $field='p.*,a.user_nickname as aname,r.user_nickname as rname,shop.name as sname'.
                ',tel.name as tel_name,tel.mobile as tel_mobile,tel.qq as tel_qq,tel.wechat as tel_wechat'.
                ',tel.taobaoid as tel_taobaoid,tel.aliid as tel_aliid';
            $join=[];
            
            $list=$m
            ->alias('p')
            ->field('p.*') 
            ->where('p.id','in',$ids) 
            ->select(); 
        }
       
        $this->assign('page',$page);
        $this->assign('list',$list);
        
        $this->assign('data',$data);
        $this->assign('types',$types);
        $this->assign('times',$times);
        $this->assign("search_types", $search_types);
        
        $this->cates(1);
        
    } 
   
    /**
     * 客户添加 
     */
    public function add()
    {
        
        //客户分类信息
        $this->cates();
        $this->assign('info',null);
        $this->assign('account1',null);
        $this->assign('account2',null);
        $this->assign('account3',null);
         
       
        return $this->fetch();  
        
    }
   //
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
        //判断是否有店铺
        if($this->isshop){
            $data_add['shop']=($admin['shop']==1)?2:$admin['shop'];
        } elseif($admin['shop']!=1){
            $this->error('店铺不能添加系统数据');
        }
        //循环的到参数
        $edit=$this->edit;
        $data_add=[];
        foreach($edit as $v){
            $data_add[$v]=$data[$v];
        }
        if($table=='custom'){
            $code_first='KF';
            $tel_type=1;
        }else{
            $code_first='GY';
            $tel_type=2;
        }
        //部分参数检查
        $data_add['sort']=intval($data['sort']);
        $data_add['code_num']=intval($data['code_num']);
        $data_add['city_code']=intval($data['city_code']);
        //拼接客户编码
        $data_add['code']=$code_first.'-'.
        str_pad($data_add['city_code'], 4,'0',STR_PAD_LEFT).'-'.
        str_pad($data_add['code_num'], 3,'0',STR_PAD_LEFT);
        //判断客户编码是否合法
        $tmp=$m->where(['code'=>$data_add['code']])->find();
        if(!empty($tmp)){
            $this->error('客户编号已存在');
        }
        $data_add['tax_point']=round($data['tax_point'],2);
        $data_add['status']=1;
        
        $data_add['aid']=$admin['id'];
        $data_add['atime']=$time;
        $data_add['time']=$time;
        
        $m->startTrans();
        $id=$m->insertGetId($data_add);
        $data_account=[];
        //添加账号关联
        if(!empty($data['name1'][1])){
            $data_account[]=[
                'uid'=>$id,
                'site'=>1,
                'type'=>$tel_type,
                'bank1'=>$data['bank1'][1],
                'name1'=>$data['name1'][1],
                'num1'=>$data['num1'][1],
                'location1'=>$data['location1'][1],
                'bank2'=>$data['bank2'][1],
                'name2'=>$data['name2'][1],
                'num2'=>$data['num2'][1],
                'location2'=>$data['location2'][1],
            ]; 
        }
        if(!empty($data['name1'][2])){
            $data_account[]=[
                'uid'=>$id,
                'site'=>2,
                'type'=>$tel_type,
                'bank1'=>$data['bank1'][2],
                'name1'=>$data['name1'][2],
                'num1'=>$data['num1'][2],
                'location1'=>$data['location1'][2],
                'bank2'=>$data['bank2'][2],
                'name2'=>$data['name2'][2],
                'num2'=>$data['num2'][2],
                'location2'=>$data['location2'][2],
            ];
        }
        if(!empty($data['name1'][3])){
            $data_account[]=[
                'uid'=>$id,
                'site'=>3,
                'type'=>$tel_type,
                'bank1'=>$data['bank1'][3],
                'name1'=>$data['name1'][3],
                'num1'=>$data['num1'][3],
                'location1'=>$data['location1'][3],
                'bank2'=>$data['bank2'][3],
                'name2'=>$data['name2'][3],
                'num2'=>$data['num2'][3],
                'location2'=>$data['location2'][3],
            ];
        }
        if(!empty($data_account)){
            Db::name('account')->insertAll($data_account);
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
     * 客户详情
     */
    public function edit()
    {
        $id=$this->request->param('id',0,'intval');
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
        $table=$this->table;
        if($table=='custom'){ 
            $tel_type=1;
        }else{ 
            $tel_type=2;
        }
        //支付账号
        $where=[
            'uid'=>$id,
            'type'=>$tel_type, 
        ];
        $accounts=Db::name('account')->where($where)->column('site,id,bank1,name1,num1,location1,bank2,name2,num2,location2');
        $account1=(isset($accounts[1]))?$accounts[1]:null;
        $account2=(isset($accounts[2]))?$accounts[2]:null;
        $account3=(isset($accounts[3]))?$accounts[3]:null;
        //客户分类信息
        $this->cates();
        
        $this->assign('info',$info);
        $this->assign('account1',$account1);
        $this->assign('account2',$account2);
        $this->assign('account3',$account3);
       
        return $this->fetch();  
    }
    /**
     * 客户状态审核
     */
    public function review()
    {
        parent::review();
    }
    
   
    /**
     * 客户编辑提交
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
        if($table=='custom'){
            $code_first='KF';
            $tel_type=1;
        }else{
            $code_first='GY';
            $tel_type=2;
        }
        //编号处理
        if(isset($content['city_code']) || isset($content['code_num'])){
            //组装新编号
            $content['code']=$code_first.'-'.
                str_pad($data['city_code'], 4,'0',STR_PAD_LEFT).'-'.
                str_pad($data['code_num'], 3,'0',STR_PAD_LEFT);
            //判断是否合法 
            $tmp=$m->where(['code'=>$content['code']])->find();
            if(!empty($tmp)){
                $this->error('客户编号已存在');
            }
            
        }
        
        
        //支付账号
        $where=[
            'uid'=>$info['id'],
            'type'=>$tel_type,
        ];
        $accounts=Db::name('account')->where($where)->column('site,bank1,name1,num1,location1,bank2,name2,num2,location2');
        
        $account1=(isset($accounts[1]))?$accounts[1]:null;
        $account2=(isset($accounts[2]))?$accounts[2]:null;
        $account3=(isset($accounts[3]))?$accounts[3]:null;
        
        //比较账号1
        //账户名为空，表示账号为空,如果原本不存在且账号为空，就不用比较了
        if(!empty($data['name1'][1]) || !empty($account1)){
            if($data['name1'][1]!=$account1['name1']){
                $content['account1']['name1']=$data['name1'][1];
            }
            if($data['bank1'][1]!=$account1['bank1']){
                $content['account1']['bank1']=$data['bank1'][1];
            }
            if($data['num1'][1]!=$account1['num1']){
                $content['account1']['num1']=$data['num1'][1];
            }
            if($data['location1'][1]!=$account1['location1']){
                $content['account1']['location1']=$data['location1'][1];
            }
            if($data['bank2'][1]!=$account1['bank2']){
                $content['account1']['bank2']=$data['bank2'][1];
            }
            if($data['name2'][1]!=$account1['name2']){
                $content['account1']['name2']=$data['name2'][1];
            }
            if($data['num2'][1]!=$account1['num2']){
                $content['account1']['num2']=$data['num2'][1];
            }
            if($data['location2'][1]!=$account1['location2']){
                $content['account1']['location2']=$data['location2'][1];
            }
        }
        
        //比较账号2 
        if(!empty($data['name1'][2]) || !empty($account2)){
            
            if($data['name1'][2]!=$account2['name1']){
                $content['account2']['name1']=$data['name1'][2];
            }
            if($data['bank1'][2]!=$account2['bank1']){
                $content['account2']['bank1']=$data['bank1'][2];
            }
            if($data['num1'][2]!=$account2['num1']){
                $content['account2']['num1']=$data['num1'][2];
            }
            if($data['location1'][2]!=$account2['location1']){
                $content['account2']['location1']=$data['location1'][2];
            }
            if($data['bank2'][2]!=$account2['bank2']){
                $content['account2']['bank2']=$data['bank2'][2];
            }
            if($data['name2'][2]!=$account2['name2']){
                $content['account2']['name2']=$data['name2'][2];
            }
            if($data['num2'][2]!=$account2['num2']){
                $content['account2']['num2']=$data['num2'][2];
            }
            if($data['location2'][2]!=$account2['location2']){
                $content['account2']['location2']=$data['location2'][2];
            }
        }
        
        //比较账号3
        if(!empty($data['name1'][3]) || !empty($account3)){
            if($data['name1'][3]!=$account3['name1']){
                $content['account3']['name1']=$data['name1'][3];
            }
            if($data['bank1'][3]!=$account3['bank1']){
                $content['account3']['bank1']=$data['bank1'][3];
            }
            if($data['num1'][3]!=$account3['num1']){
                $content['account3']['num1']=$data['num1'][3];
            }
            if($data['location1'][3]!=$account3['location1']){
                $content['account3']['location1']=$data['location1'][3];
            }
            if($data['bank2'][3]!=$account3['bank2']){
                $content['account3']['bank2']=$data['bank2'][3];
            }
            if($data['name2'][3]!=$account3['name2']){
                $content['account3']['name2']=$data['name2'][3];
            }
            if($data['num2'][3]!=$account3['num2']){
                $content['account3']['num2']=$data['num2'][3];
            }
            if($data['location2'][3]!=$account3['location2']){
                $content['account3']['location2']=$data['location2'][3];
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
     * 客户审核详情
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
        $info=$m->where('id',$info1['pid'])->find();
        if(empty($info)){
            $this->error('编辑关联的信息不存在');
        }
        //获取改变的信息
        $change=Db::name('edit_info')->where('eid',$id)->value('content');
        $change=json_decode($change,true);
        
        //获取关联信息
        if($table=='custom'){
            $code_first='KF';
            $tel_type=1;
        }else{
            $code_first='GY';
            $tel_type=2;
        }
        //支付账号
        $where=[
            'uid'=>$info['id'],
            'type'=>$tel_type,
        ];
        $accounts=Db::name('account')->where($where)->column('site,bank1,name1,num1,location1,bank2,name2,num2,location2');
        
        $account1=(isset($accounts[1]))?$accounts[1]:null;
        $account2=(isset($accounts[2]))?$accounts[2]:null;
        $account3=(isset($accounts[3]))?$accounts[3]:null;
        //修改的城市信息
        $citys=[];
        if(isset($change['province'])){
            $citys[]=$change['province'];
        }
        if(isset($change['city'])){
            $citys[]=$change['city'];
        }
        if(isset($change['area'])){
            $citys[]=$change['area'];
        }
        if(!empty($citys)){
            $change['citys']=Db::name('area')->where('id','in',$citys)->column('id,name');
            $change['citys'][0]='未选择';
        }
        //客户分类信息
        $this->cates();
        $this->assign('info',$info);
        $this->assign('account1',$account1);
        $this->assign('account2',$account2);
        $this->assign('account3',$account3);
        $this->assign('info1',$info1);
        $this->assign('change',$change);
        
        unset($change);
        return $this->fetch();  
    }
    /**
     * 客户信息编辑审核
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
            //编号处理
            if(isset($change['code']) ){
                 //判断是否合法
                $tmp=$m->where(['code'=>$change['code']])->find();
                if(!empty($tmp)){
                    $this->error('客户编号已存在');
                }
                    
            }
            //获取账号信息
            if($table=='custom'){
                $tel_type=1;
            }else{
                $tel_type=2;
            }
            //支付账号
            $where=[
                'uid'=>$info['pid'],
                'type'=>$tel_type,
            ];
            $m_account=Db::name('account');
            $accounts=$m_account->where($where)->column('site,id');
            
            if(isset($change['account1'])){
                $data_account=$where;
                foreach($change['account1'] as $k=>$v){
                    $data_account[$k]=$v;
                }
                //存在更新，不存在添加
                if(empty($accounts[1])){
                    $m_account->insert($data_account);
                }else{
                    $m_account->where('id',$accounts[1])->update($data_account);
                }
                unset($change['account1']);
            }
            if(isset($change['account2'])){
                $data_account=$where;
                foreach($change['account2'] as $k=>$v){
                    $data_account[$k]=$v;
                }
                //存在更新，不存在添加
                if(empty($accounts[2])){
                    $m_account->insert($data_account);
                }else{
                    $m_account->where('id',$accounts[2])->update($data_account);
                }
                unset($change['account2']);
            }
            if(isset($change['account3'])){
                $data_account=$where;
                foreach($change['account3'] as $k=>$v){
                    $data_account[$k]=$v;
                }
                //存在更新，不存在添加
                if(empty($accounts[3])){
                    $m_account->insert($data_account);
                }else{
                    $m_account->where('id',$accounts[3])->update($data_account);
                }
                unset($change['account3']);
            }
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
        
        zz_action($data_action ,['aid'=>$info['aid']]);
        
        $m->commit();
        $this->success('审核成功');
    }
   
    /**
     * 客户批量删除
     */
    public function del_all()
    {
         
        parent::del_all();
    }
    /**
     * 分类等关联信息
     *   */
    public function cates($type=3){
        parent::cates($type);
        $table=$this->table;
        $admin=$this->admin;
        //分类
        $cates=Db::name($table.'_cate')->where('status',2)->order('sort asc')->column('id,name');
        //所属公司
        $where=[
            'status'=>2, 
        ];
        if($admin['shop']!=1){
            $where['shop']=$admin['shop'];
        }
       
        if($type==1){
            $field='id,name,shop'; 
        }else{
            $field='id,name';
            if(empty($this->where_shop)){
                $where['shop']=($admin['shop']==1)?2:$admin['shop'];
            }
            //可选物流
            $freights=Db::name('freight')->where($where)->order('shop asc,sort asc')->column($field);
            //开票类型
            $invoice_types=config('invoice_type');
            //付款银行
            $where=[
                'status'=>2,
            ];
            $banks=Db::name('bank')->where($where)->column('id,name');
          
            $this->assign('freights',$freights);
            $this->assign('invoice_types',$invoice_types);
            $this->assign('banks',$banks);
        }
        $companys=Db::name('company')->where($where)->order('shop asc,sort asc')->column($field);
        
        //付款类型 
        $paytypes=Db::name('paytype')->where($where)->order('shop asc,sort asc')->column($field);
        
        $this->assign('companys',$companys);
        $this->assign('paytypes',$paytypes);
        
        $this->assign('cates',$cates);
    }
    //联系人
    public function tel_edit(){
        
        $m=$this->m;
        $id=$this->request->param('id',0,'intval');
        $info=$m->where('id',$id)->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        $table=$this->table;
        if($table=='custom'){
            $tel_type=1;
        }else{
            $tel_type=2;
        }
        //支付账号
        $where=[
            'uid'=>$info['id'],
            'type'=>$tel_type,
        ];
        $tels=Db::name('tel')->where($where)->order('status desc,sort asc,site asc')->column('*','site');
        //若没有新增赋值null
        $change=['add'=>null];
        
        $this->assign('info',$info);
        $this->assign('tels',$tels);
        $this->assign('change',$change);
       
        return $this->fetch();  
    }
    //联系人编辑
    public function tel_edit_do(){
        $m=$this->m;
        $table=$this->table;
        $flag=$this->flag;
        $data=$this->request->param();
        $id=intval($data['id']);
        $info=$m->where('id',$id)->find();
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
            'url'=>url('tel_edit_info','',false,false),
            'rstatus'=>1,
            'rid'=>0,
            'rtime'=>0,
            'shop'=>$admin['shop'],
        ];
        $update['adsc']=(empty($data['adsc']))?'联系人信息编辑':$data['adsc'];
        $fields=['contacter','receiver','checker'];
        
        $content=[];
        //检测改变了哪些字段
        foreach($fields as $k=>$v){
            //如果原信息和$data信息相同就未改变，不为空就记录，？null测试
            if(isset($data[$v]) && $info[$v]!=$data[$v]){
                $content[$v]=$data[$v];
            }
            
        }
        if($table=='custom'){
            $tel_type=1;
        }else{
            $tel_type=2;
        }
       
        //联系方式
        $where=[
            'uid'=>$id,
            'type'=>$tel_type,
        ];
        $tels=Db::name('tel')->where($where)->column('*','site');
        $fields=['name','position','sex','mobile','mobile1','mobile2','phone','phone1',
            'province','city','area','street','postcode','fax','other','qq','dsc',
            'wechat','wechatphone','wechatname','email','taobaoid','aliid','sort','status',
        ];
        //循环所有变量
        foreach($data['name'] as $k=>$v){
            $tmp=[];
            //存在的比较
            if(isset($tels[$k])){
                foreach($fields as $vv){
                   if($tels[$k][$vv] != $data[$vv][$k]){
                        $tmp[$vv]=$data[$vv][$k];
                   }
                }
                //有差异保存id
                if(!empty($tmp)){
                    $tmp['id']=$tels[$k]['id'];
                    $content['tel'][$k]=$tmp;
                }
            }else{
                //不存在的直接添加,保存uid,site,type
                $tmp=['uid'=>$id,'type'=>$tel_type,'site'=>$k];
                foreach($fields as $vv){
                    $tmp[$vv]=$data[$vv][$k];
                }
                $content['add'][$k]=$tmp;
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
            'link'=>url('tel_edit_info',['id'=>$eid]),
            'shop'=>$admin['shop'],
        ];
        
        zz_action($data_action,['department'=>$admin['department']]);
        
        $m_edit->commit();
        $this->success('已提交修改'); 
    }
    /**
     * 联系人审核详情
     */
    public function tel_edit_info()
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
        $info=$m->where('id',$info1['pid'])->find();
        if(empty($info)){
            $this->error('编辑关联的信息不存在');
        }
        //获取改变的信息
        $change=Db::name('edit_info')->where('eid',$id)->value('content');
        $change=json_decode($change,true);
        
        //获取关联信息
        if($table=='custom'){
            $code_first='KF';
            $tel_type=1;
        }else{
            $code_first='GY';
            $tel_type=2;
        }
        //联系方式
        $where=[
            'uid'=>$info['id'],
            'type'=>$tel_type,
        ];
        $tels=Db::name('tel')->where($where)->order('status desc,sort asc,site asc')->column('*','site');
        
        //获取修改的城市信息
        $citys=[];
        if(isset($change['tel'])){
            foreach($change['tel'] as $k=>$v){ 
                if(isset($v['province'])){
                    $citys[]=$v['province'];
                }
                if(isset($v['city'])){
                    $citys[]=$v['city'];
                }
                if(isset($v['area'])){
                    $citys[]=$v['area'];
                } 
            }
        }else{
            $change['tel']=null;
        }
        if(isset($change['add'])){
            foreach($change['add'] as $k=>$v){
                $citys[]=$v['province'];
                $citys[]=$v['city'];
                $citys[]=$v['area'];
            }
        }else{
            $change['add']=null;
        } 
        
        if(!empty($citys)){
            $change['citys']=Db::name('area')->where('id','in',$citys)->column('id,name');
            $change['citys'][0]='未选择';
        }
        
        $this->assign('info',$info);
        $this->assign('tels',$tels);
        $this->assign('info1',$info1);
        $this->assign('change',$change); 
       
        return $this->fetch();
    }
    /**
     *联系人编辑审核
     */
    public function tel_edit_review()
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
            
            //获取账号信息
            if($table=='custom'){
                $tel_type=1;
            }else{
                $tel_type=2;
            }
            //联系方式
            $where=[
                'uid'=>$info['pid'],
                'type'=>$tel_type,
            ];
            $m_tel=Db::name('tel');
            //获取现有联系方式比较
            $tels=$m_tel->where($where)->column('site,id');
            //更新
            if(isset($change['tel'])){
                foreach($change['tel'] as $k=>$v){
                    $m_tel->update($v);
                }
                unset($change['tel']);
            } 
            
            //添加，若是有已存在则更新
            if(isset($change['add'])){
                foreach($change['add'] as $k=>$v){
                    if(isset($tels[$k])){
                        $m_tel->where('id',$tels[$k])->update($v);
                        //清除已更新的
                        unset($change['add'][$k]);
                    }
                }
                if(!empty($change['add'])){
                    $m_tel->insertAll($change['add']);
                }
                unset($change['add']);
            } 
            
             
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
        $review_status=$this->review_status;
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'审核'.$info['aid'].'-'.$info['aname'].'对'.($this->flag).$info['pid'].'-'.$info['pname'].'联系人的编辑为'.$review_status[$status],
            'table'=>$table,
            'type'=>'edit_review',
            'pid'=>$info['pid'],
            'link'=>url('tel_edit_info',['id'=>$info['id']]),
            'shop'=>$admin['shop'],
        ];
        
        zz_action($data_action);
        
        $m->commit();
        $this->success('审核成功');
    }
}
