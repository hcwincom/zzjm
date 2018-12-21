<?php

namespace app\attendance\controller;

 
use think\Db;
use cmf\controller\AdminBaseController;

class AdminAttendanceRuleController extends AdminBaseController
{
    private $m;
    private $flag;
    private $table;
    public function _initialize()
    {
        parent::_initialize();
        
        $this->flag='考勤记录';
        $this->table='attendance_rule';
        $this->m=Db::name('attendance_rule');
         
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        
    }
    /**
     * 考勤记录列表
     * @adminMenu(
     *     'name'   => '考勤记录列表',
     *     'parent' => 'attendance/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 2,
     *     'icon'   => '',
     *     'remark' => '考勤记录列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
         $m=$this->m;
         $admin=$this->admin;
         
         $data=$this->request->param();
         $where=[];
         //判断是否有店铺
         $join=[
             ['cmf_user a','a.id=p.aid','left'], 
         ];
         $field='p.*,a.user_nickname as aname';
         
         //店铺,分店只能看到自己的数据，总店可以选择店铺 
         $res=zz_shop($admin, $data, $where,'p.shop');
         $data=$res['data'];
         $where=$res['where']; 
         $this->where_shop=$res['where_shop'];
         $where_aid=[
             'shop'=>$res['where_shop'],
             'job_status'=>2
         ];
         $aids=Db::name()->where()->column();
         //实际状态
         if(empty($data['real_day_status'])){
             $data['real_day_status']=0;
         }else{
             $where['p.real_day_status']=['eq',$data['real_day_status']];
         }
         //原始打卡状态
         if(empty($data['day_status'])){
             $data['day_status']=0;
         }else{
             $where['p.day_status']=['eq',$data['day_status']];
         }
         //签到状态
         if(empty($data['start_tatus'])){
             $data['start_tatus']=0;
         }else{
             $where['p.start_tatus']=['eq',$data['start_tatus']];
         }
         //签退状态
         if(empty($data['end_status'])){
             $data['end_status']=0;
         }else{
             $where['p.end_status']=['eq',$data['end_status']];
         }
         
         
         //时间类别
         $times=[
             1=>['p.start_time','签到时间'],
             2=>['p.end_time','签退时间'], 
         ];
         $res=zz_search_time($times, $data, $where);
         $data=$res['data'];
         $where=$res['where'];
          
         
         $list=$m
         ->alias('p')
         ->field($field)
         ->join($join)
         ->where($where)
         ->order('p.status asc,p.sort asc,p.time desc')
         ->paginate();
         
         // 获取分页显示
         $page = $list->appends($data)->render();
         
         $this->assign('page',$page);
         $this->assign('list',$list);
         
         $this->assign('data',$data);
        
         $this->assign('times',$times);
         
         $this->cates(1);
         
         
        return $this->fetch();
    }
    
     
    //
    public function cates($type=3){
        
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
        $this->assign('start_tatuss',[1=>'未签到',2=>'正常签到',3=>'迟到',4=>'缺卡']);
        $this->assign('end_tatuss',[1=>'未签退',2=>'正常签退',3=>'早退',4=>'缺卡']);
        $this->assign('day_statuss',[1=>'一天未结束',2=>'正常上下班',3=>'迟到',4=>'早退',5=>'迟到+早退',6=>'旷工',7=>'请假',8=>'调休',9=>'出差']);
         
    }
     
    
}
