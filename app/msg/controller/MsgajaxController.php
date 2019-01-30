<?php

namespace app\msg\controller;

use app\common\controller\AdminBase0Controller;
use think\Db;
 

class MsgajaxController extends AdminBase0Controller
{
    /* 发信息页面，搜索用户 */
    function user_search(){
        $data=$this->request->param();
        $types=config('user_search');
        $search_types=config('search_types');
        $where = [];
        $admin=$this->admin;
        /**搜索条件**/
        $data = $this->request->param();
        if($admin['shop']==1){
            if(!empty($data['shop']) && $data['shop']>0){
                $where['shop'] =  ['eq',$data['shop']];
            }
           
        }else{
            $where['shop'] =  ['eq',$admin['shop']];
        }
        
       
        $res=zz_search_param($types, $search_types, $data, $where);
        $data=$res['data'];
        $where=$res['where']; 
        $users = Db::name('user')->where($where)->order('shop asc,department asc,user_type asc,id asc')->column('id,user_nickname');
        
        $this->success('ok','',$users);
    }
    /**
     * 消息提醒, 
     */
    public function msg_new()
    {
        $uid=session('ADMIN_ID');
        $m=Db::name('msg');
        $where=[
            'm.uid'=>$uid,
            'm.status'=>1,
        ];
        $list=$m
        ->alias('m')
        ->join('cmf_user a','a.id=m.aid')
        ->join('cmf_msg_txt mt','mt.id=m.msg')
        ->where($where)
        ->column('m.id,a.user_nickname as aname,mt.link,mt.dsc,mt.time');
        if(empty($list)){
            $this->error('没有未接收消息');
        }else{
            $ids=array_keys($list);
            $m->where('id','in',$ids)->setField('status',2);
            $this->success('ok','',$list);
        }
        
    }
     
}
