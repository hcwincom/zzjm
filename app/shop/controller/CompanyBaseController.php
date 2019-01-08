<?php
 
namespace app\shop\controller;

 
use app\common\controller\AdminInfo0Controller; 
use think\Db; 
  
class CompanyBaseController extends AdminInfo0Controller
{
    protected $company_type;
    public function _initialize()
    {
        parent::_initialize();
        
        $this->table='company';
        $this->m=Db::name('company');
        $this->edit=['name','sort','dsc','code','allname','account_name','account_bank','account_num',
             'contact','address','key_account','key_key','store','paytype'
        ];
        $this->search=[
            'name' => '公司名称',
            'code' => '公司代码',
            'allname' => '公司全称',
            'contact' => '联系电话',
            'id' => 'id',
        ];
        //没有店铺区分
        $this->isshop=1; 
      
        $this->assign('table',$this->table);
        
    }
    /**
     * 子公司列表 
     */
    public function index()
    {
        $table=$this->table;
        $m=$this->m;
        $admin=$this->admin;
        
        $data=$this->request->param();
        $where=[
            'p.type'=>($this->company_type)
        ];
        //判断是否有店铺
        $join=[
            ['cmf_user a','a.id=p.aid','left'],
            ['cmf_user r','r.id=p.rid','left'],
            
        ];
        $field='p.*,a.user_nickname as aname,r.user_nickname as rname';
        //店铺,分店只能看到自己的数据，总店可以选择店铺
        if($this->isshop){
           
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
     * 子公司批量删除 
     */
    public function del_all()
    {
        if(empty($_POST['ids'])){
            $this->error('没有选择信息');
        }
        
        $tmp=Db::name('user')->where('company','in',$_POST['ids'])->find();
        if(!empty($tmp)){
            $this->error($tmp['company'].'有用户'.$tmp['user_nickname'].'不能删除');
        }
        parent::del_all();
    }
   
    public function cates($type=3){
        parent::cates($type);
        if($type==3){
            $this->assign('company_type', $this->company_type);
            $shop=$this->where_shop;
            if(empty($shop)){
                $admin=$this->admin;
                $shop=($admin['shop']==1)?2:$admin['shop'];
            }
            $where=[
                'shop'=>$shop,
                'status'=>2,
            ];
            $stores=Db::name('store')->where($where)->order('sort asc')->column('id,name');
            $this->assign('stores',$stores);
            $paytypes=Db::name('paytype')->where($where)->order('sort asc')->column('id,name');
            $this->assign('paytypes',$paytypes);
        } 
    }
     
}
