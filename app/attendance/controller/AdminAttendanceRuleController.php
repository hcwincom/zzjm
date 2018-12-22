<?php

namespace app\attendance\controller;


use app\common\controller\AdminInfo0Controller;
use think\Db;

class AdminAttendanceRuleController extends AdminInfo0Controller
{
    
    public function _initialize()
    {
        parent::_initialize();
        
        $this->flag='考勤规则';
        $this->table='attendance_rule';
        $this->m=Db::name('attendance_rule');
        //没有店铺区分
        $this->isshop=1;
        $this->edit=['name','start_hour','start_minute','end_hour','end_minute','start1','start2',
            'end1','end2','sort','day','work_type'
        ];
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        
    }
    /**
     * 考勤规则列表
     * @adminMenu(
     *     'name'   => '考勤规则列表',
     *     'parent' => 'attendance/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '考勤规则列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        parent::index();
        return $this->fetch();
    }
    /**
     * 考勤规则初始化
     * @adminMenu(
     *     'name'   => '考勤规则初始化',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 2,
     *     'icon'   => '',
     *     'remark' => '考勤规则初始化',
     *     'param'  => ''
     * )
     */
    public function back0()
    {
        //初始化为删除原数据再给原始规则，早九晚五，周一到五
     
       //组装数据
       $admin=$this->admin;
       $data=[];
       $time=time();
       $weeks=[
           1=>'周一',
           2=>'周二',
           3=>'周三',
           4=>'周四',
           5=>'周五',
           6=>'周六',
           7=>'周日',
       ];
       foreach($weeks as $i=>$v){ 
           $tmp=[
               'shop'=>$admin['shop'],
               'name'=>$v.'考勤规则',
               'week'=>$i,
               'start_hour'=>9,
               'start_minute'=>0,
               'end_hour'=>17,
               'end_minute'=>0,
               'start1'=>10,
               'start2'=>30,
               'end1'=>10,
               'end2'=>0,
               'aid'=>$admin['id'],
               'atime'=>$time,
               'rid'=>$admin['id'],
               'rtime'=>$time,
               'time'=>$time,
               'status'=>2,
               'sort'=>$i,
               'day'=>0,
               'rule_type'=>1,
               'work_type'=>1, 
           ];
           if($i>5){
               $tmp['work_type']=2;
           }
           $data[]=$tmp;
       }
       $m=$this->m;
       //先删除旧数据
       $m->startTrans();
       $where_old=['shop'=>$admin['shop']];
       $m->where($where_old)->delete();
       $m->insertAll($data);
       $m->commit();
       $this->success('已重置考勤规则',url('index'));
    }
    
    /**
     * 考勤规则添加
     * @adminMenu(
     *     'name'   => '考勤规则添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '考勤规则添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
        return $this->fetch();
        
    }
    /**
     * 考勤规则添加do
     * @adminMenu(
     *     'name'   => '考勤规则添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '考勤规则添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        parent::add_do();
        
    }
    /**
     * 考勤规则详情
     * @adminMenu(
     *     'name'   => '考勤规则详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '考勤规则详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();
        return $this->fetch();
    }
    /**
     * 考勤规则状态审核
     * @adminMenu(
     *     'name'   => '考勤规则状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '考勤规则状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 考勤规则状态批量同意
     * @adminMenu(
     *     'name'   => '考勤规则状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '考勤规则状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    
    /**
     * 考勤规则编辑提交
     * @adminMenu(
     *     'name'   => '考勤规则编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '考勤规则编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        parent::edit_do();
    }
    /**
     * 考勤规则编辑列表
     * @adminMenu(
     *     'name'   => '考勤规则编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '考勤规则编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();
    }
    
    /**
     * 考勤规则审核详情
     * @adminMenu(
     *     'name'   => '考勤规则审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '考勤规则审核详情',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        parent::edit_info();
        return $this->fetch();
    }
    /**
     * 考勤规则信息编辑审核
     * @adminMenu(
     *     'name'   => '考勤规则编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '考勤规则编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
    /**
     * 考勤规则编辑记录批量删除
     * @adminMenu(
     *     'name'   => '考勤规则编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '考勤规则编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        parent::edit_del_all();
    }
    
    //
    public function cates($type=3){
        parent::cates($type);
        $weeks=[
            1=>'周一',
            2=>'周二',
            3=>'周三',
            4=>'周四',
            5=>'周五',
            6=>'周六',
            7=>'周日',
        ];
        $this->assign('weeks',$weeks);
        $this->assign('work_types',[1=>'工作',2=>'休息']);
        $this->assign('rule_types',[1=>'每周考勤',2=>'自定义']);
         
    }
     
    
}
