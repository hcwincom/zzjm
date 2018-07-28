<?php
 
namespace app\admin\controller;

 
use app\common\controller\AdminInfoController; 
use think\Db; 
 
class ParamController extends AdminInfoController
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='技术参数';
        $this->table='param';
        $this->m=Db::name('param');
          
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        $this->assign('param_types',[1=>'单选',2=>'多选',3=>'输入']);
    }
    /**
     * 技术参数列表
     * @adminMenu(
     *     'name'   => '技术参数列表',
     *     'parent' => 'admin/Goods/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '技术参数列表',
     *     'param'  => ''
     * )
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
        $this->assign('times',$times);
        $this->assign("search_types", $search_types);
        return $this->fetch();
    }
     
   
    /**
     * 技术参数添加
     * @adminMenu(
     *     'name'   => '技术参数添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '技术参数添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
       
        return $this->fetch();  
        
    }
    /**
     * 技术参数添加do
     * @adminMenu(
     *     'name'   => '技术参数添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '技术参数添加do',
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
        if(empty($data['type'])){
            $this->error('类型必须选择');
        }
        //清除不规范输入导致的空格
        if(!empty($data['content'])){
            //3是自由输入
            if($data['type']==3){
                $data['content']='';
            }else{
                //清除不规范输入导致的空格
                $data['content']=zz_delimiter($data['content']); 
            }
        }
       
        $url=url('index');
        //处理图片
        $pic='';
        $table=$this->table;
        $time=time();
        $admin=$this->admin;
        
        $data_add=[
            'name'=>$data['name'],
            'dsc'=>$data['dsc'],
            'type'=>$data['type'],
            'content'=>$data['content'],
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
        $m->commit();
        $this->success('添加成功',$url);
    }
    /**
     * 技术参数详情
     * @adminMenu(
     *     'name'   => '技术参数详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '技术参数详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();  
        return $this->fetch();  
    }
    /**
     * 技术参数状态审核
     * @adminMenu(
     *     'name'   => '技术参数状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '技术参数状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 技术参数状态批量同意
     * @adminMenu(
     *     'name'   => '技术参数状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '技术参数状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    /**
     * 技术参数禁用
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
     * 技术参数信息状态恢复
     * @adminMenu(
     *     'name'   => '技术参数信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '技术参数信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        parent::cancel_ban();
    }
    /**
     * 技术参数编辑提交
     * @adminMenu(
     *     'name'   => '技术参数编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '技术参数编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        parent::edit_do();
    }
    /**
     * 技术参数编辑列表
     * @adminMenu(
     *     'name'   => '技术参数编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '技术参数编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();  
    }
    
    /**
     * 技术参数审核详情
     * @adminMenu(
     *     'name'   => '技术参数审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '技术参数审核详情',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        parent::edit_info();
        return $this->fetch();  
    }
    /**
     * 技术参数信息编辑审核
     * @adminMenu(
     *     'name'   => '技术参数编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '技术参数编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
    /**
     * 技术参数编辑记录批量删除
     * @adminMenu(
     *     'name'   => '技术参数编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '技术参数编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    
    /**
     * 技术参数批量删除
     * @adminMenu(
     *     'name'   => '技术参数批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '技术参数批量删除',
     *     'param'  => ''
     * )
     */
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
        //检查是否有产品
        
        //彻底删除
        $where=['id'=>['in',$ids]];
        $m->startTrans();
        $tmp=$m->where($where)->delete();
        if($tmp>0){
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
            db('edit')->where($where_edit)->delete();
            $m->commit();
            //删除图片
            $path=getcwd().'/upload/';
            foreach($ids as $v){
                $path1=$path.$table.'/'.$v;
                zz_dirdel($path1);
            }
            $this->success('成功删除数据'.$tmp.'条');
        }else{
            $this->error('没有删除数据');
        }
        
    }
   
     
}
