<?php

namespace app\attendance\controller;

use app\common\controller\AdminBase0Controller;
use think\Db;
 

class AttendanceajaxController extends AdminBase0Controller
{
    
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
     
}
