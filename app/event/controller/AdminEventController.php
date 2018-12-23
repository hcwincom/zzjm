<?php

namespace app\event\controller;


use app\common\controller\AdminInfo0Controller;
use think\Db;
use app\event\model\EventModel;

class AdminEventController extends AdminInfo0Controller
{
    
    public function _initialize()
    {
        parent::_initialize();
        
        $this->flag='事件';
        $this->table='event';
        $this->m=new EventModel();
        //没有店铺区分
        $this->isshop=1;
        $this->edit=['name','start_hour','start_minute','end_hour','end_minute','start1','start2',
            'end1','end2','sort','day','work_type'
        ];
        $this->assign('flag',$this->flag);
        $this->assign('table',$this->table);
        
    }
    /**
     * 事件列表
     * @adminMenu(
     *     'name'   => '事件列表',
     *     'parent' => 'event/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '事件列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        parent::index();
        return $this->fetch();
    }
    
    /**
     * 事件添加
     * @adminMenu(
     *     'name'   => '事件添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '事件添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        parent::add();
        return $this->fetch();
        
    }
    /**
     * 事件添加do
     * @adminMenu(
     *     'name'   => '事件添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '事件添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        parent::add_do();
        
    }
    /**
     * 事件详情
     * @adminMenu(
     *     'name'   => '事件详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '事件详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        parent::edit();
        return $this->fetch();
    }
    /**
     * 事件状态审核
     * @adminMenu(
     *     'name'   => '事件状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '事件状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        parent::review();
    }
    /**
     * 事件状态批量同意
     * @adminMenu(
     *     'name'   => '事件状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '事件状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        parent::review_all();
    }
    
    /**
     * 事件编辑提交
     * @adminMenu(
     *     'name'   => '事件编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '事件编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        parent::edit_do();
    }
    /**
     * 事件编辑列表
     * @adminMenu(
     *     'name'   => '事件编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '事件编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        parent::edit_list();
        return $this->fetch();
    }
    
    /**
     * 事件审核详情
     * @adminMenu(
     *     'name'   => '事件审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '事件审核详情',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        parent::edit_info();
        return $this->fetch();
    }
    /**
     * 事件信息编辑审核
     * @adminMenu(
     *     'name'   => '事件编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '事件编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        parent::edit_review();
    }
    /**
     * 事件编辑记录批量删除
     * @adminMenu(
     *     'name'   => '事件编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '事件编辑记录批量删除',
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
