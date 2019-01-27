<?php
 
namespace app\money\controller;

use app\common\controller\AdminInfo0Controller; 
use think\Db; 
use app\money\model\OrdersInvoiceModel;
  
class AdminInvoiceController extends AdminInfo0Controller
{
   
    protected $invoice_status;
    
    /**
     * 收款方式，1收款2付款
     */
    protected $ptype;
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='发票';
        $this->table='orders_invoice';
        $this->m=new OrdersInvoiceModel();
       
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        $this->assign('oid_type',config('oid_type'));
        $this->invoice_status=config('invoice_status');
        $this->assign('invoice_status',$this->invoice_status);
        $this->assign('invoice_type',config('invoice_type'));
        $this->edit=['name','dsc','sn','company_name','company_code','company_address',
            'company_bank','company_bank_location',
            'uname','ucode','address','bank','bank_location',
        ];
         
    }
    /**
     * 发票列表
     * @adminMenu(
     *     'name'   => '发票列表',
     *     'parent' => 'money/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 5,
     *     'icon'   => '',
     *     'remark' => '发票列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
       
        $m=$this->m;
        $admin=$this->admin;
        $data=$this->request->param();
        $where=[];
        //客户还是供货商
   
        $where_shop=$admin['shop'];
        //店铺,分店只能看到自己的数据，总店可以选择店铺
        if($admin['shop']==1){
            if(empty($data['shop'])){
                $data['shop']=0;
                $where_shop=2;
            }else{
                $where['p.shop']=['eq',$data['shop']];
                $where_shop=$data['shop'];
            }
        }else{
            $where['p.shop']=['eq',$admin['shop']]; 
        }
        
        
        $this->where_shop=$where_shop;
        //发票状态
        if(empty($data['status'])){
            $data['status']=0;
        }else{
            $where['p.status']=['eq',$data['status']];
        }
       
        //所属公司
        if(empty($data['company'])){
            $data['company']=0;
        }else{
            $where['p.company']=['eq',$data['company']];
        }
        
        //发票类型
        if(empty($data['invoice_type'])){
            $data['invoice_type']=0;
        }else{
            $where['p.invoice_type']=['eq',$data['invoice_type']];
        }
        //订单类型
        if(empty($data['oid_type'])){
            $data['oid_type']=0;
        }else{
            $where['p.oid_type']=['eq',$data['oid_type']];
        }
        
        //查询字段
        $types=[
            1=>['p.name','发票名称'],
            6=>['p.sn','发票票号'],
            2=>['p.uname','发票客户公司名'],
            3=>['p.ucode','发票客户税号'],
            4=>['p.tel','发票客户电话'],
            5=>['p.address','发票客户地址'], 
        ];
        $search_types=config('search_types');
        $res=zz_search_param($types,$search_types,$data,$where);
        $data=$res['data'];
        $where=$res['where'];
        
        
        //先查询得到id再关联得到数据，否则sql查询太慢
        $list0=$m
        ->alias('p')
        ->field('p.id')
        ->where($where)
        ->order('p.id desc')
        ->paginate();
        $page = $list0->appends($data)->render();
        $ids=[];
        
        foreach($list0 as $k=>$v){
            $ids[$v['id']]=$v['id'];
        }
        
        if(empty($ids)){
            $list=[];
            $page=null;
        }else{
            //关联表
            $join=[
            //                 ['cmf_user a','a.id=p.aid','left'],
            //                 ['cmf_user r','r.id=p.rid','left'],
            //                 ['cmf_shop shop','p.shop=shop.id','left'],
                
            ];
            //a.user_nickname as aname,r.user_nickname as rname,shop.name as sname
            $field='p.*';
            $list=$m
            ->alias('p')
            ->where('p.id','in',$ids)
            ->order('p.id desc')
            ->column($field);
            
        }
        
        $this->assign('page',$page);
        $this->assign('list',$list);
        
        $this->assign('data',$data);
        $this->assign('types',$types);
        
        $this->assign("search_types", $search_types);
       
       
         
        $this->cates(1); 
        return $this->fetch();
    }
    /**
     * 发票详情
     * @adminMenu(
     *     'name'   => '发票详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '发票详情',
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
        $info=$info->getData();
        $this->assign('info',$info);
        
        $admin=$this->admin;
        if($admin['shop']!=1 && $admin['shop']!=$info['shop']){
            $this->error('只能查看自己店铺的编辑信息');
        }
        $this->where_shop=$info['shop'];
        $uflag='客户';
        $uurl=url('custom/AdminCustom/edit','',false,false);
        
        //客户数据 
        switch($info['oid_type']){
            case 2:
                $m_custom=Db::name('supplier');
                $uflag='供应商';
                $uurl=url('custom/AdminSupplier/edit','',false,false);
                break;
            case 5:
                $uflag='供应商';
                $uurl=url('custom/AdminSupplier/edit','',false,false);
                break;
        }
        $this->assign('uflag',$uflag);
        $this->assign('uurl',$uurl);
        //对应分类数据
        $this->cates(); 
        return $this->fetch();
    }
    /**
     * 分类信息,1-index,2-edit_index,3-add,edit,edit_info
     *   */
    public function cates($type=3){
        $admin=$this->admin; 
        if($type<3){
            //店铺
            if($admin['shop']==1){
                $shops=Db::name('shop')->where('status',2)->order('sort asc')->column('id,name');
                $this->assign('shops',$shops);
            }
            
        } 
        //公司
        $banks=Db::name('bank')->where('status',2)->order('sort asc')->column('id,name');
        $this->assign('banks',$banks);
        
        $where_shop=$this->where_shop;
        $where=[
            'shop'=>$where_shop,
            'status'=>2,
        ];
        //公司
        $companys=Db::name('company')->where($where)->order('sort asc')->column('id,name');
        $this->assign('companys',$companys);
        //公司
        $paytypes=Db::name('paytype')->where($where)->order('sort asc')->column('id,name');
        $this->assign('paytypes',$paytypes);
        
        
    }
    
    
    /**
     * 发票编辑提交
     * @adminMenu(
     *     'name'   => '发票编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '发票编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        parent::edit_do();
    }
    /**
     * 发票编辑列表
     * @adminMenu(
     *     'name'   => '发票编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '发票编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();
    }
    
    /**
     * 发票审核详情
     * @adminMenu(
     *     'name'   => '发票审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '发票审核详情',
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
        $info=$info->getData();
        //获取改变的信息
        $change=Db::name('edit_info')->where('eid',$id)->value('content');
        $change=json_decode($change,true);
        
        
        $this->assign('info',$info);
        $this->assign('info1',$info1);
        $this->assign('change',$change);
        
     
        $this->where_shop=$info['shop']; 
        $uflag='客户';
        $uurl=url('custom/AdminCustom/edit','',false,false);
        
        //客户数据
        switch($info['oid_type']){
            case 2:
                $m_custom=Db::name('supplier');
                $uflag='供应商';
                $uurl=url('custom/AdminSupplier/edit','',false,false);
                break;
            case 5:
                $uflag='供应商';
                $uurl=url('custom/AdminSupplier/edit','',false,false);
                break;
        }
        $this->assign('uflag',$uflag);
        $this->assign('uurl',$uurl);
        //分类关联信息
        $this->cates();
          
        return $this->fetch();
       
    }
    /**
     * 发票信息编辑审核
     * @adminMenu(
     *     'name'   => '发票编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '发票编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
    /**
     * 发票编辑记录批量删除
     * @adminMenu(
     *     'name'   => '发票编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '发票编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
     
    
}
