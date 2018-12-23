<?php

namespace app\attendance\controller;


use app\common\controller\AdminInfo0Controller;
use think\Db;
use app\attendance\model\AttendanceDayModel;

class AdminAttendanceApplyController extends AdminInfo0Controller
{
    
    public function _initialize()
    {
        parent::_initialize();
        
        $this->flag='考勤申请';
        $this->table='attendance_apply';
        $this->m=Db::name('attendance_apply');
        //没有店铺区分
        $this->isshop=1;
        $this->edit=['rdsc','status'];
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        
    }
    /**
     * 考勤申请列表
     * @adminMenu(
     *     'name'   => '考勤申请列表',
     *     'parent' => 'attendance/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 2,
     *     'icon'   => '',
     *     'remark' => '考勤申请列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        parent::index();
        return $this->fetch();
    }
     
    
    /**
     * 考勤申请添加
     * @adminMenu(
     *     'name'   => '考勤申请添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '考勤申请添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
        return $this->fetch();
        
    }
    /**
     * 考勤申请添加do
     * @adminMenu(
     *     'name'   => '考勤申请添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '考勤申请添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        $m=$this->m;
        $data=$this->request->param();
        
        $url=url('index');
        
        $table=$this->table;
        $time=time();
        $admin=$this->admin;
        $data_add=$data;
        //判断是否有店铺
        if($this->isshop){
            $data_add['shop']=($admin['shop']==1)?2:$admin['shop'];
        }
        $apply_types=[2=>'补卡',17=>'请假',18=>'调休',19=>'出差'];
        $data_add['apply_type']=intval($data['apply_type']);
        if(empty($data['name'])){
            $data['name']=$admin['user_nickname'].'的'.$apply_types[$data_add['apply_type']].'申请';
        }
        $data_add['name']=$data['name'];
        $data_add['start_day']=$data['start_day'];
        $data_add['end_day']=$data['end_day'];
        $time1=strtotime($data_add['start_day']);
        $time2=strtotime($data_add['end_day']);
        $data_add['days']=intval(ceil(($time2-$time1)/86400));
        if($data_add['days']<1){
            $this->error('起止日期错误');
        }
        if($data_add['apply_type']<3 ){
            if($data_add['days']>1){
                $this->error('补卡只能选择1天');
            }
            if($time1>$time){
                $this->error('补卡时间错误');
           }
        } else{
            if($time1<$time){
                $this->error('请假调休等需要提前申请');
            }
        }
         
        $data_add['status']=1; 
        $data_add['aid']=$admin['id'];
        $data_add['atime']=$time;
        $data_add['time']=$time;
       
        $m->startTrans();
        $id=$m->insertGetId($data_add);
        
        //记录操作记录
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'添加'.($this->flag).$id.'-'.$data['name'],
            'table'=>($this->table),
            'type'=>'add',
            'pid'=>$id,
            'link'=>url('edit',['id'=>$id]),
            'shop'=>$admin['shop'],
            
        ];
        zz_action($data_action,['department'=>$admin['department']]);
        
        $m->commit();
        //直接审核
        $rule='review';
        $res=$this->check_review($admin,$rule);
        if($res){
            $this->redirect($rule,['id'=>$id,'status'=>2]);
        }
        $this->success('添加成功',$url);
        
    }
    /**
     * 考勤申请详情
     * @adminMenu(
     *     'name'   => '考勤申请详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '考勤申请详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();
        return $this->fetch();
    }
    /**
     * 考勤申请状态审核
     * @adminMenu(
     *     'name'   => '考勤申请状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '考勤申请状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        $status=$this->request->param('status',0,'intval');
        $rdsc=$this->request->param('rdsc');
        $id=$this->request->param('id',0,'intval');
        if($status<1 || $status>4 || $id<=0){
            $this->error('信息错误');
        }
        $m=$this->m;
        //查找信息
        $info=$m->where('id',$id)->find();
        
        if(empty($info) || $info['status']!=1 ){
            $this->error('没有可审核信息');
        }
       
        
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1){
            if(empty($info['shop']) || $info['shop']!=$admin['shop']){
                $this->error('不能审核其他店铺的信息');
            }
        }
        $time=time();
        $update=[
            'rid'=>$admin['id'],
            'rdsc'=>$rdsc,
            'rtime'=>$time,
            'status'=>$status,
            'time'=>$time,
        ];
        
        $row=$m->where('id',$id)->update($update);
        
        if($row!==1){
            $m->rollback();
            $this->error('审核失败，请刷新后重试');
        }
        //审核通过要添加考勤
        if($status==2){
            $time1=strtotime($info['start_day']);
            $time2=strtotime($info['end_day']);
            $day_time=strtotime(date('Y-m-d',$time1));
            $m_day= new AttendanceDayModel();
            if($info['apply_type']<3){
                $old=$m_day->where('day_time',$day_time)->find();
                if(empty($old)){
                    $m->rollback();
                    $this->error('没有当天考勤记录，不能补卡');
                }
                //此处可以查询当天考勤规则
                $data_update=[
                    'time'=>$time,
                    'start_status'=>5, 
                    'end_status'=>5, 
                    'start_time'=>$time1, 
                    'end_time'=>$time2, 
                    'day_status'=>2,
                    'work_hour'=>round(($time2-$time1)/3600,1),
                ];
                $rule=Db::name('attendance_date')->where('day_time',$day_time)->find();
                if($rule['start_time']<$time1 ){
                    //签到补卡
                    $data_update['day_status']=3;
                    $data_update['start_status']=3;
                }elseif($rule['end_time']>$time2){
                    //签退补卡
                    $data_update['day_status']=3;
                    $data_update['end_status']=3;
                }
               
                 $m_day->where('id',$old['id'])->update($data_update);
             }else{
                 $data_add=[];
                 for($i=0;$i<$info['days'];$i++){
                     $day_time+=($i*86400);
                     $data_add[]=[
                         'aid'=>$info['aid'],
                         'shop'=>$info['shop'],
                         'day_status'=>$info['apply_type'],
                         'start_status'=>1,
                         'end_status'=>1,
                         'day_time'=>$day_time,
                         'time'=>$time
                     ];
                 }
                 $m_day->insertAll($data_add);
             }
           
        }
        //审核成功，记录操作记录,发送审核信息
        $statuss=$this->statuss;
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'审核'.($this->flag).$info['id'].'-'.$info['name'].'的状态为'.$statuss[$status],
            'table'=>($this->table),
            'type'=>'review',
            'pid'=>$info['id'],
            'link'=>url('edit',['id'=>$info['id']]),
            'shop'=>$admin['shop'],
        ];
        zz_action($data_action,['aid'=>$info['aid']]);
        
        $m->commit();
        $this->success('审核成功');
    }
    /**
     * 考勤申请状态批量同意
     * @adminMenu(
     *     'name'   => '考勤申请状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '考勤申请状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        $this->error('不能批量同意');
        parent::review_all();
    }
    /**
     * 考勤申请批量删除
     * @adminMenu(
     *     'name'   => '考勤申请批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '考勤申请批量删除',
     *     'param'  => ''
     * )
     */
    public function del_all()
    {
        
        parent::del_all();
    }
    
    //
    public function cates($type=3){
        parent::cates($type);
        
        $this->assign('apply_types',[2=>'补卡',17=>'请假',18=>'调休',19=>'出差']);
        
    }
     
    
}
