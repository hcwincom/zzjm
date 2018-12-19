<?php
 
namespace app\goods\controller;
 
use think\Db; 
 
class AdminPriceController extends GoodsBaseController
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='价格模板';
        $this->table='price';
        $this->m=Db::name('price'); 
        $this->assign('param_types',[1=>'固定值',2=>'比例']);
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        
    }
    /**
     * 价格模板列表
     * @adminMenu(
     *     'name'   => '价格模板列表',
     *     'parent' => 'goods/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 31,
     *     'icon'   => '',
     *     'remark' => '价格模板列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        parent::index();
        return $this->fetch();
    }
     
   
    /**
     * 价格模板添加
     * @adminMenu(
     *     'name'   => '价格模板添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格模板添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
        //获取所有价格参数，价格参数要分组 
        $prices=config('prices');
        $where=['p.status'=>['eq',2],'c.status'=>['eq',2]];
        foreach($prices as $k=>$v){
            
            $where['c.sort']=['between',[$k*10,$k*10+10]];
            $fees[$k]=Db::name('goods_fee')
            ->alias('p')
            ->join('cmf_goods_fee_cate c','c.id=p.cid')
            ->where($where)
            ->column('p.id,p.name,p.fee,p.type,p.dsc');
         }
         
         $this->assign('fees',$fees);
         $this->assign('prices',$prices);
        return $this->fetch();  
        
    }
    /**
     * 价格模板添加do
     * @adminMenu(
     *     'name'   => '价格模板添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格模板添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        
       parent::add_do();
    }
    /**
     * 价格模板详情
     * @adminMenu(
     *     'name'   => '价格模板详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格模板详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit(); 
        $id=$this->request->param('id');
        //获取所有价格参数，价格参数要分组
        $prices=config('prices');
        //模板关联，且状态正常的参数
        $where=['pf.t_id'=>['eq',$id],'p.status'=>['eq',2],'c.status'=>['eq',2]];
        foreach($prices as $k=>$v){
            
            $where['c.sort']=['between',[$k*10,$k*10+10]];
            $fees[$k]=Db::name('price_fee')
            ->alias('pf')
            ->join('cmf_goods_fee p','p.id=pf.p_id')
            ->join('cmf_goods_fee_cate c','c.id=p.cid')
            ->where($where)
            ->column('p.id,p.name,pf.fee,pf.type,pf.dsc');
        }
        
        $this->assign('fees',$fees);
        $this->assign('prices',$prices);
        return $this->fetch();  
          
    }
    /**
     * 价格模板状态审核
     * @adminMenu(
     *     'name'   => '价格模板状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格模板状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 价格模板状态批量同意
     * @adminMenu(
     *     'name'   => '价格模板状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格模板状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    /**
     * 价格模板禁用
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
     * 价格模板信息状态恢复
     * @adminMenu(
     *     'name'   => '价格模板信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格模板信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 价格模板编辑提交
     * @adminMenu(
     *     'name'   => '价格模板编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格模板编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        parent::edit_do();
    }
    /**
     * 价格模板编辑列表
     * @adminMenu(
     *     'name'   => '价格模板编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格模板编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){

        parent::edit_list();
        return $this->fetch();  
    }
    
    /**
     * 价格模板审核详情
     * @adminMenu(
     *     'name'   => '价格模板审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格模板审核详情',
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
        $info=$m->where('id',$info1['pid'])->find();
        if(empty($info)){
            $this->error('编辑关联的信息不存在');
        }
        //获取改变的信息
        $change=Db::name('edit_info')->where('eid',$id)->value('content');
        $change=json_decode($change,true);
        
         
        $this->assign('info',$info);
        $this->assign('info1',$info1);
        $this->assign('change',$change);
        $this->cates();
         
        //获取所有价格参数，价格参数要分组
        $prices=config('prices');
        //模板关联，且状态正常的参数
        $where=['pf.t_id'=>['eq',$info1['pid']],'p.status'=>['eq',2],'c.status'=>['eq',2]];
        foreach($prices as $k=>$v){
            
            $where['c.sort']=['between',[$k*10,$k*10+10]];
            $fees[$k]=Db::name('price_fee')
            ->alias('pf')
            ->join('cmf_goods_fee p','p.id=pf.p_id')
            ->join('cmf_goods_fee_cate c','c.id=p.cid')
            ->where($where)
            ->column('p.id,p.name,pf.fee,pf.type,pf.dsc');
        }
        
        $this->assign('fees',$fees);
        $this->assign('prices',$prices);
      
        return $this->fetch();  
    }
    /**
     * 价格模板信息编辑审核
     * @adminMenu(
     *     'name'   => '价格模板编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格模板编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
    /**
     * 价格模板编辑记录批量删除
     * @adminMenu(
     *     'name'   => '价格模板编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格模板编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    
    /**
     * 价格模板批量删除
     * @adminMenu(
     *     'name'   => '价格模板批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '价格模板批量删除',
     *     'param'  => ''
     * )
     */
    public function del_all()
    {
        parent::del_all();
        
    }
   
     
}
