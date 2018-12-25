<?php
 
namespace app\store\controller;

 
use cmf\controller\AdminBaseController; 
use think\Db; 
  
class AdminGoodsController extends AdminBaseController
{
    protected $m;
    protected $statuss;
   
    protected $table;
    
    protected $flag;
    protected $isshop;
    //用于详情页中识别当前店铺,
    //列表页中分店铺查询
    protected $where_shop;
   
    public function _initialize()
    {
        parent::_initialize();
        $this->statuss=config('info_status');
        
       
        $this->assign('statuss',$this->statuss);
      
        $this->assign('html',$this->request->action());
        
        $this->isshop=1; 
        $this->where_shop=0;
       
        $this->flag='库存';
        $this->table='store_goods';
        $this->m=Db::name('store_goods');
      
       
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        
    }
    /**
     * 库存列表
     * @adminMenu(
     *     'name'   => '库存列表',
     *     'parent' => 'store/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 0,
     *     'icon'   => '',
     *     'remark' => '库存列表',
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
            $shops=Db::name('shop')->where('status',2)->column('id,name');
            //首页列表页去除总站
            unset($shops[1]); 
            $this->assign('shops',$shops);
            if(empty($data['shop'])){
                $data['shop']=0;
            }else{
                $where['p.shop']=['eq',$data['shop']];
            }
        }else{
            $where_shop=$admin['shop'];
            $where['p.shop']=['eq',$admin['shop']];
        }
        //产品分类
         if(empty($data['cid0'])){
             $data['cid0']=0;
         }else{
             $where['goods.cid0']=['eq',$data['cid0']];
         }
         if(empty($data['cid'])){
             $data['cid']=0;
         }else{
             $where['goods.cid']=['eq',$data['cid']];
         }
        //查询字段 
        $types=[
            'name' => '产品名称',
            'code'=>'产品编码',
            'id' => '产品id',
            'sn'=>'产品条码',
            
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
            $where['goods.'.$data['type1']]=zz_search($data['type2'],$data['name']);
        }
        //库存数量
        if(empty($data['num'])){
            $data['num']=0;
        }else{
            switch($data['num']){
                case 1:
                    $where['p.num']=['eq',0];
                    break;
                case 2:
                    $where['p.num']=['between',[1,10]];
                    break;
                case 3:
                    $where['p.num']=['between',[11,100]];
                    break;
                case 4:
                    $where['p.num']=['gt',100];
                    break; 
           }
        }
        //料位数量
        if(empty($data['box_num'])){
            $data['box_num']=0;
        }else{
            switch($data['box_num']){
                case 1:
                    $where['p.box_num']=['eq',1];
                    break;
                case 2:
                    $where['p.box_num']=['eq',2];
                    break;
                case 3:
                    $where['p.box_num']=['eq',3];
                    break;
                case 4:
                    $where['p.box_num']=['eq',4];
                    break;
                case 5:
                    $where['p.box_num']=['eq',5];
                    break;
                case 6:
                    $where['p.box_num']=['gt',5];
                    break;
            }
        }
        //时间类别
        $times=['time' => '更新时间'];
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
       
        $join=[
            ['cmf_shop shop','p.shop=shop.id','left'],
            ['cmf_goods goods','p.goods=goods.id','left'],
        ];
        $field='p.id,p.goods,p.shop,shop.name as sname,goods.name as goods_name,goods.code as goods_code'.
        ',p.store,p.safe,p.num,p.num1,p.box_num,p.time,(p.num*goods.price_in) as money';
        /*  $field.=',sum(p.safe) as safe,sum(p.num) as num,sum(p.num1) as num1,sum(p.box_num) as box_num'.
         ',(sum(p.num)*goods.price_in) as money,max(p.time) as time';*/
        //仓库为0，
        if(empty($data['store'])){
            $data['store']=0;
        }elseif($data['store']==-1){
            //-1为店铺总库存
            $where['p.store']=['eq',0]; 
        }else{
            $where['p.store']=['eq',$data['store']]; 
        }
        $list=$m
        ->alias('p')
        ->field($field)
        ->join($join)
        ->where($where)
        ->order('shop.sort asc,shop.id asc,p.time desc')
        ->paginate();
        
        // 获取分页显示
        $page = $list->appends($data)->render();
      
        //仓库
        $where=[
            'status'=>['eq',2],
            'type'=>['in',[1,2]],
        ]; 
        if(!empty($where_shop)){
            $where['shop']=$where_shop;
        }
         
        $stores=Db::name('store')->where($where)->order('shop asc,sort asc')->column('id,shop,name');
        $this->assign('stores',$stores); 
        
        //分类 
        $m_cate=Db::name('cate');
        $where_cate=[
            'fid'=>0,
            'status'=>2,
        ];
        $cates0=$m_cate->where($where_cate)->order('sort asc,code_num asc')->column('id,name,code');
        $where_cate=[
            'status'=>['eq',2],
            'fid'=>['gt',0], 
        ];
        $cates=$m_cate->where($where_cate)->order('sort asc,code_num asc')->column('id,name,fid,code');
        $this->assign('cates0',$cates0);
        $this->assign('cates',$cates);
        $this->assign('cid0',$data['cid0']);
        $this->assign('cid',$data['cid']);
        $this->assign('select_class','form-control');
        
        $this->assign('page',$page);
        $this->assign('list',$list);
        
        $this->assign('data',$data);
        $this->assign('types',$types);
        $this->assign('times',$times);
        $this->assign("search_types", $search_types);
         
        return $this->fetch();
    }
     
    /**
     * 安全库存详情
     * @adminMenu(
     *     'name'   => '安全库存详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '安全库存详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $back=url('index');
        if(empty($_POST['ids'])){
            $this->error('未选中信息',$back);
        }
        $ids=$_POST['ids'];
        
        $m=$this->m;
        //获取临时数据
        $tmp=$m->where('id','in',$ids)->column('goods,shop');
        $shop=current($tmp);
        //检查店铺
        $admin=$this->admin;
        if($admin['shop']!=1 && $admin['shop']!=$shop){
            $this->error('只能查看本店铺的数据',$back);
        }
        //获取所有产品
        $goods_id=array_keys($tmp);
        $where=[
            'id'=>['in',$goods_id],
            'shop'=>['eq',$shop],
        ];
        $goods=Db::name('goods')->where($where)->column('id,name,pic,code');
        //获取所有仓库
        $where=[
            'shop'=>$shop,
            'status'=>2,
        ];
        $stores=Db::name('store')->where($where)->order('sort asc')->column('id,name');
        
        //获取所有库存
        $where=[
            'id'=>['in',$ids],
            'shop'=>['eq',$shop],
        ];
        $list=$m->where($where)->column('id,store,goods,safe,safe_max,safe_count');
        //循环得到数据
        $res=[];
        foreach($list as $k=>$v){
            $res[$v['goods']][$v['store']]=[
                'id'=>$v['id'],
                'safe'=>$v['safe'],
                'safe_max'=>$v['safe_max'],
                'safe_count'=>$v['safe_count'],
            ];
        }
         
        $this->assign('stores',$stores);
        $this->assign('goods',$goods);
        $this->assign('res',$res);
        return $this->fetch();
    }
     
    /**
     * 安全库存编辑提交
     * @adminMenu(
     *     'name'   => '安全库存编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '安全库存编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        $back=url('index');
        $m=$this->m;
        $table=$this->table;
        $flag=$this->flag;
        $data=$this->request->param();
        
        $admin=$this->admin; 
      
        if(empty($data['safe'])){
            $this->error('数据错误',$back);
        }
        $safes=$data['safe'];
        $id0=key($safes);
        $info=$m->where('id',$id0)->find();
        
        if(empty($info)){
            $this->error('数据错误',$back);
        }
       
        if($admin['shop']!=1 ){ 
            if($admin['shop']!=$info['shop']){
                $this->error('店铺数据错误',$back);
            } 
        }
        $m->startTrans();
        $time=time();
        $ids=[];
        //循环设置所有输入的值
        foreach ($safes as $k=>$v){
            if($v!==''){
                $ids[]=$k;
                $update_info=[
                    'time'=>$time,
                    'safe'=>intval($v),
                ];
                $where=[
                    'id'=>$k,
                    'shop'=>$info['shop'], 
                ]; 
                $m->where($where)->update($update_info);
            }
           
        }
        if(empty($ids)){
            $m->rollback();
            $this->error('未修改',$back);
        }
        //先获取所有产品
        $goods=$m->where('id','in',$ids)->column('goods');
        //调整店铺总安全库存
        $where_store=[
            'shop'=>['eq',$info['shop']], 
        ];
        //先得到总库存在更新
        foreach($goods as $k=>$v){
            $where_store['store']=['gt',0];
            $where_store['goods']=['eq',$v];
            $safe_sum=$m->where($where_store)->sum('safe');
            $where_store['store']=['eq',0];
            $m->where($where_store)->setField('safe',$safe_sum);
        }
        $ids=implode(',',$ids);
        //记录操作记录
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'调整了安全库存'.$ids,
            'table'=>($this->table),
            'type'=>'edit',
            'pid'=>0,
            'link'=>'',
            'shop'=>$admin['shop'],
        ];
        Db::name('action')->insert($data_action);
        $m->commit();
        $this->success('已修改',$back);
    }
     
     /**
     * 查库存
     * @adminMenu(
     *     'name'   => '查库存',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '查库存',
     *     'param'  => ''
     * )
     */
    public function store_search(){
        $back=url('index');
        if(empty($_POST['ids'])){
            $this->error('未选中信息',$back);
        }
        $ids=$_POST['ids'];
        
        $m=$this->m;
        //获取临时数据
        $tmp=$m->where('id','in',$ids)->column('goods,shop'); 
        $shop=current($tmp); 
        //检查店铺
        $admin=$this->admin;
        if($admin['shop']!=1 && $admin['shop']!=$shop){
            $this->error('只能查看本店铺的数据',$back);
        }
        //获取所有产品
        $goods_id=array_keys($tmp);
        $where=[
            'id'=>['in',$goods_id],
            'shop'=>['eq',$shop],
        ];
        $goods=Db::name('goods')->where($where)->column('id,name,pic,code');
        //获取所有仓库
        $where=[
            'shop'=>$shop,
            'status'=>2,
        ];
        $stores=Db::name('store')->where($where)->order('sort asc')->column('id,name');
        $stores[0]='总库存';
        //获取所有库存
        $where=[ 
            'goods'=>['in',$goods_id],
            'shop'=>['eq',$shop],
        ];
        $list=$m->where($where)->column('id,store,goods,num,num1');
        //循环得到数据
        $res=[];
        foreach($list as $k=>$v){
            $res[$v['goods']][$v['store']]=[
                'num'=>$v['num'].'('.$v['num1'].')',
                'id'=>$v['id'],
            ];
             
        }
        $time0=strtotime(date('Y-m-d'));
        //获取一个月库存历史
        $where=[
            'goods'=>['in',$goods_id],
            'shop'=>['eq',$shop],
        ];
        $list=Db::name('store_goods_history')->where($where)->column('');
        for($i=0;$i<30;$i++){
            
        }
        
        $this->assign('stores',$stores);
        $this->assign('goods',$goods);
        $this->assign('res',$res);
        return $this->fetch();
    }
    /**
     * 库存调整
     * @adminMenu(
     *     'name'   => '库存调整',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '库存调整',
     *     'param'  => ''
     * )
     */
    public function store_do()
    {
        $back=url('index');
        $m=$this->m;
        $table=$this->table;
        $flag=$this->flag;
        $data=$this->request->param();
        
        $admin=$this->admin;
        
        if(empty($data['num'])){
            $this->error('数据错误',$back);
        }
       
        ////循环得到所有更改
        $ids=[];
        foreach ($data['num'] as $k=>$v){
            if($v!=='' || $data['num1'][$k]!==''){
                $ids[]=$k;
            }
        }
        //先获取所有库存
        $nums=$m
        ->alias('p')
        ->join('cmf_goods goods','goods.id=p.goods','left')
        ->join('cmf_store store','store.id=p.store','left')
        ->where('p.id','in',$ids)
        ->column('p.id,p.num,p.num1,p.store,p.goods,p.shop,goods.name as goods_name,store.name as store_name');
        if(empty($ids) || empty($nums)){
            $this->error('未更改',$back);
        }
        
        $info=current($nums); 
      
        if($admin['shop']!=1 ){
            if($admin['shop']!=$info['shop']){
                $this->error('店铺数据错误',$back);
            }
        }
        $time=time();
        $m->startTrans();
         
        //记录操作记录
        $data_action0=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'调整了库存',
            'table'=>($this->table),
            'type'=>'edit',
            'pid'=>0,
            'link'=>'',
            'shop'=>$admin['shop'],
        ];
        $data_action=[];
        $goods=[];
        //循环设置所有输入的值
        foreach ($nums as $k=>$v){
            if($v['shop']!=$info['shop']){
                $m->rollback();
                $this->error('店铺数据错误',$back);
            }
            $tmp=$data_action0;
            $goods[$v['goods']]=$v['goods'];
            $update_info=[
                'time'=>$time, 
            ];
            $tmp['action'].=$v['store_name'].'-'.$v['goods_name'];
            if($data['num'][$k]!==''){
                $update_info['num']=intval($data['num'][$k]);
                $tmp['action'].='，库存'.$v['num'].'调整为'.$update_info['num'];
            }
            if($data['num1'][$k]!==''){
                $update_info['num1']=intval($data['num1'][$k]);
                $tmp['action'].='，冻结库存'.$v['num1'].'调整为'.$update_info['num1'];
            } 
            $m->where('id',$k)->update($update_info);
            $data_action[]=$tmp; 
        }
        if(empty($ids)){
            $m->rollback();
            $this->error('未修改',$back);
        }
        
        //调整店铺总安全库存
        $where_store=[
            'shop'=>['eq',$info['shop']],
        ];
        //先得到总库存在更新,按产品更新
        foreach($goods as $k=>$v){
            $where_store['store']=['gt',0];
            $where_store['goods']=['eq',$v];
            $safe_sum=$m->where($where_store)->sum('safe');
            $where_store['store']=['eq',0];
            $m->where($where_store)->setField('safe',$safe_sum);
        }
        
        Db::name('action')->insertAll($data_action);
        $m->commit();
        $this->success('已修改',$back);
    }
    /**
     * 历史库存曲线
     * @adminMenu(
     *     'name'   => '历史库存曲线',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '历史库存曲线',
     *     'param'  => ''
     * )
     */
    public function store_history()
    {
        
    }
    
}
