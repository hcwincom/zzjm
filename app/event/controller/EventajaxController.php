<?php

namespace app\event\controller;

use app\common\controller\AdminBase0Controller;
use think\Db;
use app\msg\model\MsgModel;
 

class EventajaxController extends AdminBase0Controller
{
    /**
     * 申请参与
     */
     public function uidd_add(){
         $admin=$this->admin;
         $id=$this->request->param('id',0,'intval');
         $info=Db::name('event')->where('id',$id)->find();
         $dsc=$this->request->param('dsc');
         $time=time();
         $date_uid=[
             'uid'=>$admin['id'],
             'aid'=>$info['aid'],
             'event'=>$id,
             'ustatus'=>2,
             'utime'=>$time,
             'udsc'=>$dsc, 
         ];
         $uidd=Db::name('event_uid')->insertGetId($date_uid);
         $m_msg=new MsgModel();
         $data_msg=[
             'dsc'=>$admin['user_nickname'].'申请完成事件'.$id.'-'.$info['name'],
             'type'=>'edit',
             'link'=>url('uidd',['id'=>$uidd]),
         ];
         $m_msg->send($data_msg,$admin,[$info['aid']]);
         $this->success('ok',url('event/AdminEvent/uid',['id'=>$uidd]));
     }
     /**
      * 确认参与
      */
     public function uidd_do(){
         $admin=$this->admin;
         $id=$this->request->param('id',0,'intval');
         $m_event_uid=Db::name('event_uid');
         $m_event=Db::name('event');
         $info=$m_event_uid->where('id',$id)->find();
         $event=$m_event->where('id',$info['event'])->find();
         if(empty($event) || $event['status']!=2){
             $this->error('审核通过且未被他人接收的事件才能确认');
         }
         $dsc=$this->request->param('dsc');
         //默认是接收人同意
         $type=$this->request->param('type',1,'intval');
         $time=time();
         $data_event=[
             'ustatus'=>2,
             'tatus'=>4,
             'uid'=>$info['uid'],
             'time'=>$time,
         ];
         $m_event_uid->startTrans();
         $m_event->where('id',$info['event'])->update($data_event);
         if($type==1){
             $date_uid=[ 
                 'ustatus'=>2, 
                 'udsc'=>$dsc,
             ];
            
         }else{
             $date_uid=[
                 'astatus'=>2,
                 'adsc'=>$dsc,
             ];
         }
         $m_event_uid->where('id',$info['id'])->update($date_uid);
         $m_event_uid->commit();
         $this->success('ok','',['uidd'=>$id]);
     }
     
}
