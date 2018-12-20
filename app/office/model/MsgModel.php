<?php
 
namespace app\msg\model;

use think\Model;
use think\Db;
 
class MsgModel extends Model
{
      public function send($data,$admin,$uids){
          $data_msg_txt=[
              'time'=>time(),
              'dsc'=>$data['dsc'],
              'type'=>empty($data['type'])?'msg':$data['type'],
              'link'=>empty($data['link'])?'':$data['link'],
          ];
          $msg_id=Db::name('msg_txt')->insertGetId($data_msg_txt);
          $data_msg=[];
          foreach($uids as $v){
              $data_msg[]=[
                  'uid'=>$v,
                  'aid'=>$admin['id'],
                  'msg'=>$msg_id,
                  'shop'=>$admin['shop'],
              ];
          }
          $this->insertAll($data_msg);
      }
}
