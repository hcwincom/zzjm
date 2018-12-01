<?php
 
namespace app\express\controller;

 
use app\common\controller\AdminInfo0Controller; 
use think\Db; 
  
class AdminFreightController extends AdminInfo0Controller
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='店铺合作快递';
        $this->table='freight';
        $this->m=Db::name('freight');
        $this->edit=['name','sort','dsc','code','paytype','pay_type','express','store'];
        
        //没有店铺区分
        $this->isshop=1;
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        
    }
    /**
     * 店铺合作快递列表
     * @adminMenu(
     *     'name'   => '店铺合作快递列表',
     *     'parent' => 'express/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '店铺合作快递列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
         parent::index();
        return $this->fetch();
    }
     
   
    /**
     * 店铺合作快递添加
     * @adminMenu(
     *     'name'   => '店铺合作快递添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '店铺合作快递添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
        return $this->fetch();  
        
    }
    /**
     * 店铺合作快递添加do
     * @adminMenu(
     *     'name'   => '店铺合作快递添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '店铺合作快递添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        parent::add_do();
        
    }
    /**
     * 店铺合作快递详情
     * @adminMenu(
     *     'name'   => '店铺合作快递详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '店铺合作快递详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();  
        
        return $this->fetch();  
    }
    /**
     * 店铺合作快递状态审核
     * @adminMenu(
     *     'name'   => '店铺合作快递状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '店铺合作快递状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 店铺合作快递状态批量同意
     * @adminMenu(
     *     'name'   => '店铺合作快递状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '店铺合作快递状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    /**
     * 店铺合作快递禁用
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
     * 店铺合作快递信息状态恢复
     * @adminMenu(
     *     'name'   => '店铺合作快递信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '店铺合作快递信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 店铺合作快递编辑提交
     * @adminMenu(
     *     'name'   => '店铺合作快递编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '店铺合作快递编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        parent::edit_do();
    }
    /**
     * 店铺合作快递编辑列表
     * @adminMenu(
     *     'name'   => '店铺合作快递编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '店铺合作快递编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        
        return $this->fetch();  
    }
    
    /**
     * 店铺合作快递审核详情
     * @adminMenu(
     *     'name'   => '店铺合作快递审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '店铺合作快递审核详情',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        parent::edit_info();
        return $this->fetch();  
    }
    /**
     * 店铺合作快递信息编辑审核
     * @adminMenu(
     *     'name'   => '店铺合作快递编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '店铺合作快递编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
    /**
     * 店铺合作快递编辑记录批量删除
     * @adminMenu(
     *     'name'   => '店铺合作快递编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '店铺合作快递编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    
    /**
     * 店铺合作快递批量删除
     * @adminMenu(
     *     'name'   => '店铺合作快递批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '店铺合作快递批量删除',
     *     'param'  => ''
     * )
     */
    public function del_all()
    {
         
        parent::del_all();
    }
   
    /**
     * 店铺合作快递联系人和付款账号
     * @adminMenu(
     *     'name'   => '店铺合作快递联系人和付款账号',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 30,
     *     'icon'   => '',
     *     'remark' => '店铺合作快递联系人和付款账号',
     *     'param'  => ''
     * )
     */
    public function tel_edit()
    {
        $m=$this->m;
        $id=$this->request->param('id',0,'intval');
        $info=$m
        ->alias('p')
        ->where('p.id',$id)
        ->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        //获取联系人1,2 
        $tels=Db::name('tel')->where('id','in',[$info['tel1'],$info['tel2']])->column(''); 
        $tel1=empty($tels[$info['tel1']])?null:$tels[$info['tel1']];
        $tel2=empty($tels[$info['tel2']])?null:$tels[$info['tel2']];
        
        //获取付款账号
        $accounts=Db::name('account')->where('id','in',[$info['dg'],$info['ds'],$info['zfb']])->column('');
        $dg=empty($accounts[$info['dg']])?null:$accounts[$info['dg']];
        $ds=empty($accounts[$info['ds']])?null:$accounts[$info['ds']];
        $zfb=empty($accounts[$info['zfb']])?null:$accounts[$info['zfb']];
        $this->assign('info',$info); 
        $this->assign('tel1',$tel1); 
        $this->assign('tel2',$tel2); 
        $this->assign('dg',$dg); 
        $this->assign('ds',$ds); 
        $this->assign('zfb',$zfb); 
        
        //获取所有区域
        $where=[
            'status'=>2,
            'shop'=>$info['shop'],
        ]; 
        $areas=Db::name('expressarea')->where($where)->order('sort asc')->column('id,name');
        
        //获取费用设置
        $where=[ 
            'ff.freight'=>$info['id']
        ]; 
        $fees=Db::name('freight_fee') 
        ->alias('ff')
        ->join('cmf_expressarea ea','ea.id=ff.expressarea')
        ->where($where)  
        ->column('ff.expressarea,ff.price0,ff.weight0,ff.price1,ff.weight1,ff.size,ea.name as ea_name');
        $this->assign('areas',$areas);
        $this->assign('fees',$fees);
        
        return $this->fetch();
    }
    /**
     * 店铺合作快递联系人和付款账号编辑
     * @adminMenu(
     *     'name'   => '店铺合作快递联系人和付款账号编辑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 30,
     *     'icon'   => '',
     *     'remark' => '店铺合作快递联系人和付款账号编辑',
     *     'param'  => ''
     * )
     */
    public function tel_edit_do()
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
            'url'=>url('tel_edit_info','',false,false),
        ];
        $update['adsc']=(empty($data['asc']))?'修改了快递联系人和付款信息':$data['asc'];
        //获取联系人1,2
        $m_tel=Db::name('tel');
        $tels=$m_tel->where('id','in',[$info['tel1'],$info['tel2']])->column('');
        $tel1=empty($tels[$info['tel1']])?null:$tels[$info['tel1']];
        $tel2=empty($tels[$info['tel2']])?null:$tels[$info['tel2']];
        $change=[];
        $tel_field=['name','sex','position','mobile','mobile1','phone','fax','qq','wechat','email','dsc'];
        //联系人信息比较
        $tmp_tel1=[];
        $tmp_tel2=[]; 
        foreach($tel_field as $k=>$v){
            if($tel1[$v] != $data['tel1_'.$v]){
                $tmp_tel1[$v]=$data['tel1_'.$v];
            }
            if($tel2[$v] != $data['tel2_'.$v]){
                $tmp_tel2[$v]=$data['tel2_'.$v];
            }
        }
        //保存修改的信息
        if(!empty($tmp_tel1)){
            $change['tel1']=$tmp_tel1;
        }
        if(!empty($tmp_tel2)){
            $change['tel2']=$tmp_tel2;
        }
         
        //获取付款账号
        $accounts=Db::name('account')->where('id','in',[$info['dg'],$info['ds'],$info['zfb']])->column('');
        $dg=empty($accounts[$info['dg']])?null:$accounts[$info['dg']];
        $ds=empty($accounts[$info['ds']])?null:$accounts[$info['ds']];
        $zfb=empty($accounts[$info['zfb']])?null:$accounts[$info['zfb']];
        
        $tel_field=['name1','num1','location1'];
        //付款账号比较
        $tmp_dg=[];
        $tmp_ds=[]; 
        $tmp_zfb=[]; 
        foreach($tel_field as $k=>$v){
            if($dg[$v] != $data['dg_'.$v]){
                $tmp_dg[$v]=$data['dg_'.$v];
            }
            if($ds[$v] != $data['ds_'.$v]){
                $tmp_ds[$v]=$data['ds_'.$v];
            }
            if($zfb[$v] != $data['zfb_'.$v]){
                $tmp_zfb[$v]=$data['zfb_'.$v];
            }
        }
        //保存修改的信息
        if(!empty($tmp_dg)){
            //$change['dg']=json_encode($tmp_dg);
            $change['dg']=$tmp_dg;
        }
        if(!empty($tmp_ds)){
            $change['ds']=$tmp_ds;
        }
        if(!empty($tmp_zfb)){
            $change['zfb']=$tmp_zfb;
        }
        //获取所有区域
        $where=[
            'status'=>2,
            'shop'=>$info['shop'],
        ];
        $areas=Db::name('expressarea')->where($where)->order('sort asc')->column('id,name');
        //关联费用比较 
        $fees=Db::name('freight_fee') 
        ->where('freight',$info['id'])
        ->column('expressarea,price0,weight0,price1,weight1,size');
        $tel_field=['price0','weight0','price1','weight1','size'];
        $tmp_fees=[];
        //循环比较
        $ids=[];
        $ids0=array_keys($fees);
        //有区域设置
        if(empty($data['price0'])){ 
            $this->error('没有添加配送区域');
        }
        
        foreach($data['price0'] as $k=>$v){
            $tmp=[];
            $ids[]=$k;
            //判断是否新增,新增记录区域，变化记录原id
            if(!isset($fees[$k]) ){ 
                $tmp['expressarea']=$k;
                $tmp['freight']=$info['id'];
                $fees[$k]=null;
            }
            foreach ($tel_field as $kk=>$vv){
                $data[$vv][$k]=round($data[$vv][$k],2);
                //比较变化 
                if($fees[$k][$vv]!=$data[$vv][$k]){
                    $tmp[$vv]=$data[$vv][$k];
                } 
            }
            if(!empty($tmp)){ 
                $tmp['ea_name']=$areas[$k];
                $tmp_fees[$k]=$tmp;
            }
        } 
        
        //计算已删除和新增的区域 
        $tmp_fees['del'] = array_diff ($ids0, $ids);
        $tmp_fees['add'] = array_diff ($ids, $ids0);
        
        if(!empty($tmp_fees)){ 
            $change['fees']=$tmp_fees;
        }
        
        if(empty($change)){
            $this->error('未修改');
        }
        //保存更改
        $m_edit=Db::name('edit');
        $m_edit->startTrans();
        $eid=$m_edit->insertGetId($update);
        if($eid>0){
            $data_content=[
                'eid'=>$eid,
                'content'=>json_encode($change),
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
            'action'=>$admin['user_nickname'].'编辑了'.($this->flag).$info['id'].'-'.$info['name'].'的关联信息',
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
     * 店铺合作快递关联信息审核详情
     * @adminMenu(
     *     'name'   => '店铺合作快递关联信息审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 30,
     *     'icon'   => '',
     *     'remark' => '店铺合作快递关联信息审核详情',
     *     'param'  => ''
     * )
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
        //获取联系人1,2
        $m_tel=Db::name('tel');
        $tels=$m_tel->where('id','in',[$info['tel1'],$info['tel2']])->column('');
        $tel1=empty($tels[$info['tel1']])?null:$tels[$info['tel1']];
        $tel2=empty($tels[$info['tel2']])?null:$tels[$info['tel2']];
        
        //获取付款账号
        $accounts=Db::name('account')->where('id','in',[$info['dg'],$info['ds'],$info['zfb']])->column('');
        $dg=empty($accounts[$info['dg']])?null:$accounts[$info['dg']];
        $ds=empty($accounts[$info['ds']])?null:$accounts[$info['ds']];
        $zfb=empty($accounts[$info['zfb']])?null:$accounts[$info['zfb']];
        
      
        //获取费用设置
        $where=[
            'ff.freight'=>$info['id']
        ];
        $fees=Db::name('freight_fee')
        ->alias('ff')
        ->join('cmf_expressarea ea','ea.id=ff.expressarea')
        ->where($where)
        ->column('ff.expressarea,ff.price0,ff.weight0,ff.price1,ff.weight1,ff.size,ea.name as ea_name');
        
        
        //获取改变的信息
        $change=Db::name('edit_info')->where('eid',$id)->value('content');
       
        $change=json_decode($change,true);
          
        $this->assign('info',$info);
        $this->assign('info1',$info1);
        $this->assign('change',$change);
        $this->assign('tel1',$tel1);
        $this->assign('tel2',$tel2);
        $this->assign('dg',$dg);
        $this->assign('ds',$ds);
        $this->assign('zfb',$zfb);
        $this->assign('fees',$fees);
        return $this->fetch();
    }
    /**
     * 店铺合作快递关联信息审核
     * @adminMenu(
     *     'name'   => '店铺合作快递关联信息审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 30,
     *     'icon'   => '',
     *     'remark' => '店铺合作快递关联信息审核',
     *     'param'  => ''
     * )
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
        
        $review_status=$this->review_status;
        $rdsc=$this->request->param('rdsc');
        $update['rdsc']=empty($rdsc)?$review_status[$status]:$rdsc;
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
            $info0=$m->where('id',$info['pid'])->find();
          
           
            $m_tel=Db::name('tel');
            $m_account=Db::name('account');
            //tel1
            if(isset($change['tel1'])){
                if($info0['tel1']==0){
                    $update_info['tel1']=$m_tel->insertGetId($change['tel1']);
                }else{
                    $m_tel->where('id',$info0['tel1'])->update($change['tel1']);
                }
            }
            //tel2
            if(isset($change['tel2'])){
                if($info0['tel2']==0){
                    $update_info['tel2']=$m_tel->insertGetId($change['tel2']);
                }else{
                    $m_tel->where('id',$info0['tel2'])->update($change['tel2']);
                }
            }
            //dg
            if(isset($change['dg'])){
                if($info0['dg']==0){
                    $update_info['dg']=$m_account->insertGetId($change['dg']);
                }else{
                    $m_account->where('id',$info0['dg'])->update($change['dg']);
                }
            }
            //ds
            if(isset($change['ds'])){
                if($info0['ds']==0){
                    $update_info['ds']=$m_account->insertGetId($change['ds']);
                }else{
                    $m_account->where('id',$info0['ds'])->update($change['ds']);
                }
            }
            //zfb
            if(isset($change['zfb'])){
                if($info0['zfb']==0){
                    $update_info['zfb']=$m_account->insertGetId($change['zfb']);
                }else{
                    $m_account->where('id',$info0['zfb'])->update($change['zfb']);
                }
            }
           
             //更新联系人和付款账号绑定
            $row=$m->where('id',$info['pid'])->update($update_info);
            if($row!==1){
                $m->rollback();
                $this->error('信息更新失败，请刷新后重试');
            }
            //区域价格设置
            $m_ff=Db::name('freight_fee');
            //先删除
            if(!empty($change['fees']['del'])){
                $where=[ 
                    'freight'=>['eq',$info0['id']],
                    'expressarea'=>['in',$change['fees']['del']]
                ];
                $m_ff->where($where)->delete();
                unset($change['fees']['del']);
            }
           
            //获取最新的关联区域
            $ids0=Db::name('freight_fee')
            ->where('freight',$info['pid'])
            ->column('expressarea');
            //获取添加数据 
            $adds=[];
            if(!empty($change['fees']['add'])){ 
                $adds=$change['fees']['add'];
                unset($change['fees']['add']);
            } 
           
            //循环判断是否更新和添加
            foreach($change['fees'] as $k=>$v){
                unset($v['ea_name']);
                //存在则更新
                if(in_array($k, $ids0)){
                    $where=[
                        'freight'=>['eq',$info0['id']],
                        'expressarea'=>['eq',$k]
                    ];
                    $m_ff->where($where)->update($v);
                   
                } elseif(in_array($k, $adds)){
                    //不存在，但是属于新增的要添加
                    $m_ff->insert($v);
                    
                }
            }
            
        }
        
        //审核成功，记录操作记录,发送审核信息
        
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'审核'.$info['aid'].'-'.$info['aname'].'对'.($this->flag).$info['pid'].'-'.$info['pname'].'的关联信息编辑为'.$review_status[$status],
            'table'=>$table,
            'type'=>'edit_review',
            'pid'=>$info['pid'],
            'link'=>url('tel_edit_info',['id'=>$info['id']]),
            'shop'=>$admin['shop'],
        ];
        
        zz_action($data_action,['aid'=>$info['aid']]);
        $m->commit();
        $this->success('审核成功');
    }
    /**
     * 分类等关联信息
     *   */
    public function cates($type=3){
        parent::cates($type);
        //关联快递
        $where=[
            'status'=>2,
        ];
        $expresses=Db::name('express')->order('sort asc')->where($where)->column('id,name');
        $this->assign('expresses',$expresses);
        
        //获取付款类型 
        $where_shop=$this->where_shop;
        if(!empty($where_shop)){
            $where['shop']=$where_shop;
        } 
        
        $paytypes=Db::name('paytype')->where($where)->column('id,name');
        $this->assign('paytypes',$paytypes);
        $this->assign('pay_types',config('pay_type'));
        //关联仓库
        $where['type']=1; 
        $stores=Db::name('store')->where($where)->order('shop asc,sort asc')->column('id,name');
        $this->assign('stores',$stores);
        
    }
    /**
     * 按单号查询快递
     * @adminMenu(
     *     'name'   => '按单号查询快递',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 30,
     *     'icon'   => '',
     *     'remark' => '按单号查询快递',
     *     'param'  => ''
     * )
     */
    public function express_query(){
        $freight=$this->request->param('freight',0,'intval');
        $no=$this->request->param('no');
        if(empty($no)){
            $this->error('没有单号');
        }
        
        $m=$this->m;
        $code=$m
        ->alias('freight')
        ->join('cmf_express express','express.id=freight.express')
        ->where('freight.id',$freight)
        ->value('express.code');
        
        $url='https://www.kuaidi100.com/chaxun?';
        header('location:'.$url.'com='.$code.'&nu='.$no);
    }
}
