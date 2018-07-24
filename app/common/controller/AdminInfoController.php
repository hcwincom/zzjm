<?php
 
namespace app\common\controller;

use cmf\controller\AdminBaseController;
 

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
        $admin=session('admin');
        $this->assign('statuss',$this->statuss);
        $this->assign('review_status',$this->review_status);
        $this->assign('html',$this->request->action());
    }
    /**
     * 信息状态审核
     * @adminMenu(
     *     'name'   => '信息状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '信息状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        $status=$this->request->param('status',0,'intval');
        $id=$this->request->param('id',0,'intval');
        if($status<1 || $status>4 || $id<=0){
            $this->error('信息错误');
        }
        $m=$this->m;
        $info=$m->where('id',$id)->find();
        if(empty($info)){
            $this->error('信息不存在');
        }
        $admin=$this->admin;
        $time=time();
        $update=[
            'rid'=>$admin['id'],
            'rtime'=>$time,
            'status'=>$status,
            'time'=>$time,
        ];
        $row=$m->where('id',$id)->update($update);
        if($row===1){
            $this->success('审核成功');
        }else{
            $this->error('审核失败，请刷新后重试');
        }
    }
    /**
     * 信息状态批量同意
     * @adminMenu(
     *     'name'   => '信息状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '信息状态批量同意',
     *     'param'  => ''
     * )
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
        $update=[
            'status'=>2,
            'time'=>$time,
            'rid'=>$admin['id'],
            'rtime'=>$time,
        ];
        $rows=$m->where($where)->update($update);
        if($rows>0){
            $this->success('审核成功'.$rows.'条数据');
        }else{
            $this->error('没有数据审核成功，批量审核只能把未审核的数据审核为正常');
        }
    }
    
    /**
     * 信息状态禁用
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
     * @adminMenu(
     *     'name'   => '信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '信息状态恢复',
     *     'param'  => ''
     * )
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
     * @adminMenu(
     *     'name'   => '编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        
        $m=$this->m;
        $table=$this->table;
        $data=$this->request->param();
        $info=$m->where('id',$data['id'])->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        $time=time();
        $admin=$this->admin;
        $update=[
            'pid'=>$info['id'],
            'aid'=>$admin['id'],
            'atime'=>$time,
            'change'=>'',
        ];
       
        $fields=config('fields_edit_'.$table);
        //检测改变了哪些字段
        foreach($fields as $k=>$v){
            //给update赋值
            if(isset($data[$k])){
                $update[$k]=$data[$k];
            }else{
                //如果编辑结果为空
                $update[$k]=$v;
            }
            //如果原信息和update信息相同就未改变，不为空就记录，？null测试
            if($info[$k]!=$update[$k]){
                $update['change'].=','.$k;
            }
        }
        if($update['change']==''){
            $this->error('未修改');
        }
        db('cate_edit')->insert($update);
        $this->success('已提交修改');
    }
    /**
     * 编辑列表
     * @adminMenu(
     *     'name'   => '编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list()
    {
         
        $table=$this->table;
        $m_edit=db($table.'_edit');
        $flag=$this->flag;
        $data=$this->request->param();
       
        $where=[];
        if(empty($data['status'])){
            $data['status']=0;
        }else{
            $where['e.status']=['eq',$data['status']];
        }
        if(empty($data['aid'])){
            $data['aid']=0;
        }else{
            $where['e.aid']=['eq',$data['aid']];
        }
        if(empty($data['rid'])){
            $data['rid']=0;
        }else{
            $where['e.rid']=['eq',$data['rid']];
        }
        $types=[
            'name'=>$flag.'名称',
            'code'=>$flag.'编码',
            'id'=>$flag.'id', 
        ];
        if(empty($data['name'])){
            $data['name']='';
            $data['type1']='name';
            $data['type2']=1;
        }else{
            if($data['type2']==1){ 
                $where['p.'.$data['type1']]=['eq',$data['name']];
            }else{
                $where['p.'.$data['type1']]=['like','%'.$data['name'].'%'];
            }
        }
        $list=$m_edit
        ->alias('e')
        ->field('e.*,p.name as pname')
        ->join('cmf_'.$table.' p','e.pid=p.id','left')
        ->where($where)
        ->order('p.status asc,p.time desc')
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
        
        $this->assign('page',$page);
        $this->assign('list',$list);
        $this->assign('aids',$aids);
        $this->assign('rids',$rids);
        $this->assign('data',$data);
        $this->assign('types',$types);
        return $this->fetch();
         
    }
    /**
     * 信息编辑审核
     * @adminMenu(
     *     'name'   => '信息编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '信息编辑审核',
     *     'param'  => ''
     * )
     */
    public function review_edit()
    {
        //审核编辑的信息
        $status=$this->request->param('rstatus',0,'intval');
        $id=$this->request->param('id',0,'intval');
        if(($status!=2 && $status!=3) || $id<=0){
            $this->error('信息错误');
        }
        $m=$this->m;
        $table=$this->table;
        $m_edit=db($table.'_edit');
        $info=$m_edit->where('id',$id)->find();
        if(empty($info)){
            $this->error('信息不存在');
        }
        if($info['rstatus']!=1){
            $this->error('编辑信息已被审核！不能重复审核');
        }
        $admin=$this->admin;
        $time=time();
        //组装数据
        $update_info=[ 
            'time'=>$time, 
        ];
        //得到修改的字段
        $change=explode(',', $info['change']);
        array_shift($change);
        foreach($change as $v){
            $update_info[$v]=$info[$v];
            //如果是父类变化，编码也会变化
            if($v=='fid'){
                 
            }
        }
        $m->startTrans();
        $row=$m->where('id',$info['pid'])->update($update_info);
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
        
        if($row===1){
            $m->commit();
            $this->success('审核成功');
        }else{
            $m->rollback();
            $this->error('审核失败，请刷新后重试');
        }
    }
    
}
