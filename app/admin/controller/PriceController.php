<?php
 
namespace app\admin\controller;

 
use app\common\controller\AdminInfoController; 
use think\Db; 
 
class PriceController extends AdminInfoController
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
     *     'parent' => 'admin/Goods/default',
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
            $fees[$k]=db('fee')
            ->alias('p')
            ->join('cmf_cate_any c','c.id=p.cid')
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
        
        $m=$this->m;
        $data=$this->request->param();
        if(empty($data['name'])){
            $this->error('名称不能为空');
        }
        
        $url=url('index');
         
        $table=$this->table;
        $time=time();
        $admin=$this->admin;
       
        $data_add=[
            'name'=>$data['name'],
            'dsc'=>$data['dsc'], 
            'sort'=>intval($data['sort']),
            'status'=>1,
            'aid'=>$admin['id'],
            'atime'=>$time,
            'time'=>$time,
        ];
        
        $m->startTrans();
        $id=$m->insertGetId($data_add);
        
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
        db('action')->insert($data_action); 
        //价格关联的参数
        $data_fee=[];
        foreach($data['fee1'] as $k=>$v){
            $data_fee[]=[
                't_id'=>$id,
                'p_id'=>$k,
                'fee'=>round($data['fee1'][$k],4),
                'dsc'=>$data['dsc1'][$k],
                'type'=>$data['type1'][$k],
            ];
        }
        
        db('price_fee')->insertAll($data_fee);
         
        $m->commit();
        $this->success('添加成功',$url);
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
            $fees[$k]=db('price_fee')
            ->alias('pf')
            ->join('cmf_fee p','p.id=pf.p_id')
            ->join('cmf_cate_any c','c.id=p.cid')
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
        //模板编辑要转化content的值
        $id=$this->request->param('id',0,'intval');
        $change=db('edit_info')->where('eid',$id)->value('content');
        
        $change=json_decode($change,true);
        //获取改变的参数对应，转化为数组
        if(!empty($change['content'])){
            $change['content']=json_decode($change['content'],true);
        } 
         
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
            $fees[$k]=db('price_fee')
            ->alias('pf')
            ->join('cmf_fee p','p.id=pf.p_id')
            ->join('cmf_cate_any c','c.id=p.cid')
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
        //组装数据
        $update_info=[
            'time'=>$time,
        ];
        
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
            //得到修改的字段
            $change=db('edit_info')->where('eid',$id)->value('content');
            $change=json_decode($change,true);
           
            foreach($change as $k=>$v){
                $update_info[$k]=$v;
                //模板参数变化
                if($k=='content'){ 
                    unset($update_info['content']); 
                }
            }
            $row=$m->where('id',$info['pid'])->update($update_info);
            if($row!==1){
                $m->rollback();
                $this->error('信息更新失败，请刷新后重试');
            } 
            //模板参数变化
            if(isset($change['content'])){
                $m_price_fee=db('price_fee');
                //再还原为数组一次
                $data_fees=json_decode($change['content'],true);
                foreach($data_fees as $kk=>$vv){
                    $tmp_data=[
                        'fee'=>$vv['fee'],
                        'type'=>$vv['type'],
                        'dsc'=>$vv['dsc'],
                    ];
                    $tmp_where=['t_id'=>$info['pid'],'p_id'=>$kk];
                    $m_price_fee->where($tmp_where)->update($tmp_data);
                }
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
