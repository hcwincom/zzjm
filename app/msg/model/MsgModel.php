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
      /**
       * 发信息给指定权限都人
       * @param string $auth 权限规则 
       * @param array $data 信息
       */
      public function auth_send($auth,$data){
          $aid=(empty($data['aid']))?session('admin.id'):$data['aid']; 
          $shop=(empty($data['shop']))?session('admin.shop'):$data['shop'];
            
         //检测权限
          $auth=strtolower($auth);
          $role_ids=Db::name('auth_access')->where('rule_name',$auth)->column('role_id');
         
          if(empty($role_ids)){
              return 0;
          }
          //获取接收者
          $uids=Db::name('role_user')->where('role_id','in',$role_ids)->column('user_id');
         
          if(empty($uids)){
              return 0;
          }
          $where=[
              'id'=>['in',$uids]
          ];
          //获取接收者
          if($shop==2){
              $where['shop']=['in',[1,2]];
          }else{
              $where['shop']=['eq',$shop];
          }
          $uids=Db::name('user')->where($where)->column('id');
          if(empty($uids)){
              return 0;
          }
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
                  'aid'=>$aid,
                  'msg'=>$msg_id,
                  'shop'=>$shop,
              ];
          }
          $this->insertAll($data_msg);
          
          return 1;
      }
}
