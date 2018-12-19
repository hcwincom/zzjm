<?php
 
namespace app\money\controller;
 
use think\Db; 
use app\common\controller\AdminInfo0Controller;
 
 
 
class AdminFeeMonthController extends AdminInfo0Controller
{
    
    public function _initialize()
    {
        parent::_initialize();
        $this->isshop=1;
        $this->flag='每月费用';
        $this->table='shop_fee_month';
        $this->m=Db::name('shop_fee_month');
          
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        $this->assign('fee_types',[1=>'每年缴纳',2=>'每月缴纳',3=>'不定期']);
        $this->edit=['name','money','dsc','pay_status'];
    }
    /**
     * 每月费用列表
     * @adminMenu(
     *     'name'   => '每月费用列表', 
     *     'parent' => 'money/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 22,
     *     'icon'   => '',
     *     'remark' => '每月费用列表',
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
        $join=[ ];
        $field='p.*';
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
        //分类
        if(empty($data['cid'])){
            $data['cid']=0;
        }else{
            $where['p.cid']=['eq',$data['cid']];
        }
        //分类
        if(empty($data['pay_status'])){
            $data['pay_status']=0;
        }else{
            $where['p.pay_status']=['eq',$data['pay_status']];
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
        $times=config('pro_time_search');
        $res=zz_search_time($times, $data, $where,['alias'=>'p.']);
        $data=$res['data'];
        $where=$res['where'];
        $counts=$m
        ->alias('p') 
        ->join($join)
        ->where($where) 
        ->group('pay_status')
        ->column('count(id) as num,sum(money) as sum_money,pay_status','pay_status');
         
        $list=$m
        ->alias('p')
        ->field($field)
        ->join($join)
        ->where($where)
        ->order('p.status asc,p.pay_status,p.time desc')
        ->paginate();
        
        // 获取分页显示
        $page = $list->appends($data)->render();
        
        $this->assign('page',$page);
        $this->assign('list',$list);
        $this->assign('counts',$counts);
        
        $this->assign('data',$data);
        $this->assign('types',$types);
        $this->assign('times',$times);
        $this->assign("search_types", $search_types);
        
        $this->cates(1);
        return $this->fetch();
    }
     
   
    /**
     * 每月费用添加
     * @adminMenu(
     *     'name'   => '每月费用添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '每月费用添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
        $date=date('Y-m-d');
        $arr=explode('-', $date);
        $this->assign('year',$arr[0]);
        $this->assign('month',$arr[1]);
        return $this->fetch();  
        
    }
    /**
     * 每月费用添加do
     * @adminMenu(
     *     'name'   => '每月费用添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '每月费用添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    { 
        parent::add_do();
    }
    /**
     * 每月费用详情
     * @adminMenu(
     *     'name'   => '每月费用详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '每月费用详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();  
        return $this->fetch();  
    }
    /**
     * 每月费用状态审核
     * @adminMenu(
     *     'name'   => '每月费用状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '每月费用状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 每月费用状态批量同意
     * @adminMenu(
     *     'name'   => '每月费用状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '每月费用状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    /**
     * 每月费用禁用
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
     * 每月费用信息状态恢复
     * @adminMenu(
     *     'name'   => '每月费用信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '每月费用信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 每月费用编辑提交
     * @adminMenu(
     *     'name'   => '每月费用编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '每月费用编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        parent::edit_do();
    }
    /**
     * 每月费用编辑列表
     * @adminMenu(
     *     'name'   => '每月费用编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '每月费用编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();  
    }
    
    /**
     * 每月费用审核详情
     * @adminMenu(
     *     'name'   => '每月费用审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '每月费用审核详情',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        parent::edit_info();
        return $this->fetch();  
    }
    /**
     * 每月费用信息编辑审核
     * @adminMenu(
     *     'name'   => '每月费用编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '每月费用编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
    /**
     * 每月费用编辑记录批量删除
     * @adminMenu(
     *     'name'   => '每月费用编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '每月费用编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
     
    public function cates($type=3){
        parent::cates($type);
        $where_shop=$this->where_shop;
        $where=[
            'shop'=>$where_shop,
            'status'=>2,
        ];
        $cates=Db::name('shop_fee_cate')->order('sort asc')->where($where)->column('id,name');
        $this->assign('cates',$cates);
        $field='id,name';
         
        $fees=Db::name('shop_fee')->order('sort asc,cid asc')->where($where)->column($field);
        $this->assign('fees',$fees);
        $this->assign('pay_status',config('pay_status'));
    }
     
}
