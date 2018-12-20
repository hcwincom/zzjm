<?php
 
namespace app\msg\controller;

 
use think\Db; 
use cmf\controller\AdminBaseController; 
use app\msg\model\MsgModel;
class AdminMsgController extends AdminBaseController
{
    
    private $m;
    private $order;
    public function _initialize()
    {
        parent::_initialize();
        $this->m=new MsgModel();
        $this->order='p.status asc,mt.time desc';
        $this->assign('flag','信息');
        $this->assign('types', config('msg_types'));
        $this->assign('msg_status', config('msg_status'));
    }
    
    /**
     * 站内信
     * @adminMenu(
     *     'name'   => '站内信',
     *     'parent' => 'msg/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '站内信',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        
        $m=$this->m;
        $admin=$this->admin;
        
        $where=[
            'p.uid'=>['eq',$admin['id']],
            'p.udelete'=>['eq',0]
        ];
        $data=$this->request->param();
        //状态
        if(empty($data['status'])){
            $data['status']=0;
        }else{
            $where['p.status']=$data['status'];
        }
        //类型
        if(empty($data['type']) || $data['type']=='no'){
            $data['type']='no';
        }else{
            $where['mt.type']=$data['type'];
        }
        //发送者
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
                $where['mt.time']=['elt',$time2];
            }
        }else{
            //有开始时间
            $time1=strtotime($data['datetime1']);
            if(empty($data['datetime2'])){
                $data['datetime2']='';
                $where['mt.time']=['egt',$time1];
            }else{
                //有结束时间有开始时间between
                $time2=strtotime($data['datetime2']);
                if($time2<=$time1){
                    $this->error('结束时间必须大于起始时间');
                }
                $where['mt.time']=['between',[$time1,$time2]];
            }
        }
        
        $list= $m
        ->alias('p')
        ->field('p.*,mt.dsc,mt.type,mt.time,mt.link,a.user_nickname as aname')
        ->join('cmf_msg_txt mt','mt.id = p.msg')
        ->join('cmf_user a','a.id = p.aid','left')
        ->where($where)
        ->order($this->order)
        ->paginate();
        
        // 获取分页显示
        $page = $list->appends($data)->render();
        //更改状态未接收\未读为已读
        $ids=[];
        foreach($list as $v){
            if($v['status']<=2){
                $ids[]=$v['id'];
            }
        }
        if(!empty($ids)){
            $where=[
                'id'=>['in',$ids], 
            ];
            $update=['status'=>3];
            $m->where($where)->update($update);
        }
        
        $this->assign('page',$page);
        $this->assign('list',$list);
        $this->assign('data',$data);
        
        return $this->fetch();
    }
    /**
     * 我发送的信息
     * @adminMenu(
     *     'name'   => '我发送的信息',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '我发送的信息',
     *     'param'  => ''
     * )
     */
    public function send()
    {
        $m=$this->m;
        $admin=$this->admin;
        
        $where=[
            'p.aid'=>['eq',$admin['id']],
            'p.adelete'=>['eq',0]
        ];
        
        $data=$this->request->param();
        //状态
        if(empty($data['status'])){
            $data['status']=0;
        }else{
            $where['p.status']=$data['status'];
        }
        //类型
        if(empty($data['type']) || $data['type']=='no'){
            $data['type']='no';
        }else{
            $where['mt.type']=$data['type'];
        }
        //发送者
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
                $where['mt.time']=['elt',$time2];
            }
        }else{
            //有开始时间
            $time1=strtotime($data['datetime1']);
            if(empty($data['datetime2'])){
                $data['datetime2']='';
                $where['mt.time']=['egt',$time1];
            }else{
                //有结束时间有开始时间between
                $time2=strtotime($data['datetime2']);
                if($time2<=$time1){
                    $this->error('结束时间必须大于起始时间');
                }
                $where['mt.time']=['between',[$time1,$time2]];
            }
        }
        $list= $m
        ->alias('p')
        ->field('p.*,mt.dsc,mt.type,mt.time,mt.link,u.user_nickname as uname')
        ->join('cmf_msg_txt mt','mt.id = p.msg')
        ->join('cmf_user u','u.id = p.uid','left')
        ->where($where)
        ->order($this->order)
        ->paginate();
        // 获取分页显示
        $page = $list->appends($data)->render();
        
        $this->assign('page',$page);
        $this->assign('list',$list);
        $this->assign('data',$data);
        
        return $this->fetch();
        
    }
    /**
     * 发送信息
     * @adminMenu(
     *     'name'   => '发送信息',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '发送信息',
     *     'param'  => ''
     * )
     */
    public function add()
    {
      
       
        $this->cates();
        return $this->fetch();
    }
    /**
     * 发送信息do
     * @adminMenu(
     *     'name'   => '发送信息do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,5
     *     'icon'   => '',
     *     'remark' => '发送信息do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
       $data=$this->request->param();
       $admin=$this->admin;
       $where_user=[];
      
       $res=zz_shop($admin, $data, $where_user);
       $data=$res['data'];
       $where_user=$res['where'];
       
       if(empty($data['is_all'])){
          if(empty($data['uids'])){
              $this->error('没有选择发送对象');
          }
          $where_user['id']=['in',$data['uids']];
       }else{
           switch ($data['is_all']){
               case 2:
                   $where_user['user_type']=2;
                   break;
               case 3:
                   $where_user['user_type']=1;
                   break;
           }
       }
       $uids=Db::name('user')->where($where_user)->column('id');
       $m=$this->m;
       $m->send($data, $admin, $uids);
       $this->success('信息已发送');
    }
    /**
     * 全站信息
     * @adminMenu(
     *     'name'   => '全站信息',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '全站信息',
     *     'param'  => ''
     * )
     */
    public function msgs()
    {
        $m=$this->m;
        $admin=$this->admin;
        //总站显示所有，分站只显示分站
        $where=[];
        
        if($admin['shop']!=1){
            $where['p.shop']=$admin['shop'];
        }
        $data=$this->request->param();
        $res=zz_shop($admin, $data, $where,'p.shop');
        $data=$res['data'];
        $where=$res['where'];
        
       
        if(empty($data['status'])){
            $data['status']=0;
        }else{
            $where['p.status']=$data['status'];
        }
        if(empty($data['aid'])){
            $data['aid']=0;
        }else{
            $where['p.aid']=$data['aid'];
        }
        $list= $m
        ->alias('p')
        ->field('p.*,mt.dsc,mt.type,mt.time,mt.link,u.user_nickname as uname,a.user_nickname as aname')
        ->join('cmf_msg_txt mt','mt.id = p.msg')
        ->join('cmf_user u','u.id = p.uid','left')
        ->join('cmf_user a','a.id = p.aid','left')
        ->where($where)
        ->order($this->order)
        ->paginate();
        
        // 获取分页显示
        $page = $list->appends($data)->render();
        
        $this->assign('page',$page);
        $this->assign('list',$list);
        $this->assign('data',$data);
        $this->cates();
        return $this->fetch();
        
    }
    
    //分类
    public function cates($type=3){
        
        
        $admin=$this->admin;
        if($admin['shop']==1){
            $where=[
                'status'=>2, 
            ];
            $shops=Db::name('shop')->where('status',2)->order('sort asc')->column('id,name');
            $this->assign('shops',$shops);
        }
        $utypes=config('user_search');
        $search_types=config('search_types');
        $this->assign('utypes',$utypes);
        $this->assign('search_types',$search_types);
       
    }
     
}
