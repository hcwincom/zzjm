<?php
 
namespace app\admin\controller;

 
use cmf\controller\AdminBaseController; 
use think\Db; 
 
class ActionController extends AdminBaseController
{
    private $m;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->m=Db::name('action');
        
        $this->assign('flag','管理员操作记录');
        
        $this->assign('types', config('action_types'));
        $this->assign('tables', config('tables'));
    }
     
    /**
     * 管理员操作记录
     * @adminMenu(
     *     'name'   => '管理员操作记录',
     *     'parent' => 'admin/User/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 0,
     *     'icon'   => '',
     *     'remark' => '管理员操作记录',
     *     'param'  => ''
     * )
     */
    public function index()
    { 
        $m=$this->m;
        $where=[];
        $data=$this->request->param();
        if(empty($data['type'])){
            $data['type']='';
        }else{
            $where['p.type']=$data['type'];
        }
        if(empty($data['table'])){
            $data['table']='';
        }else{
            $where['p.table']=$data['table'];
        }
        //关联id
        if(empty($data['pid'])){
            $data['pid']='';
        }else{
            $where['p.pid']=$data['pid'];
        }
        //操作人
        if(empty($data['aid'])){
            $data['aid']=0;
        }else{
            $where['p.aid']=$data['aid'];
        }
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
                $where['p.time']=['elt',$time2];
            }
        }else{
            //有开始时间
            $time1=strtotime($data['datetime1']);
            if(empty($data['datetime2'])){
                $data['datetime2']='';
                $where['p.time']=['egt',$time1];
            }else{
                //有结束时间有开始时间between
                $time2=strtotime($data['datetime2']);
                if($time2<=$time1){
                    $this->error('结束时间必须大于起始时间');
                }
                $where['p.time']=['between',[$time1,$time2]];
            }
        }
        $list= $m
        ->alias('p')
        ->field('p.*,u.user_login as uname0,u.user_nickname as uname1')
        ->join('cmf_user u','u.id = p.aid','left')
        ->where($where)
        ->order('p.time desc')
        ->paginate();
       
        // 获取分页显示
        $page = $list->render(); 
       //得到所有管理员
        $admins=Db::name('user')->where('user_type',1)->select();
        $this->assign('page',$page);
        $this->assign('list',$list); 
        $this->assign('data',$data); 
        $this->assign('admins',$admins); 
        return $this->fetch();
    }
    /**
     * 清空系统任务
     * @adminMenu(
     *     'name'   => '清空系统任务',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 0,
     *     'icon'   => '',
     *     'remark' => '清空系统任务',
     *     'param'  => ''
     * )
     */
    public function clear()
    {
        $m=$this->m;
        $m->where('type','system')->delete();
        $this->success('已清空');
    }
    
}
