<?php
 
namespace app\admin\controller;

use cmf\controller\AdminBaseController; 
 
  
class FileController extends AdminBaseController
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='文件管理';
        
        //没有店铺区分
        $this->isshop=1;
        $this->assign('flag',$this->flag);
      
        
    }
    /**
     * 文件管理
     * @adminMenu(
     *     'name'   => '文件管理',
     *     'parent' => '',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 90,
     *     'icon'   => '',
     *     'remark' => '文件管理',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $list=[];
        $this->assign('list',$list);
        return $this->fetch();
    }
    /**
     * 清除危险文件
     * @adminMenu(
     *     'name'   => '清除危险文件',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 2,
     *     'icon'   => '',
     *     'remark' => '清除危险文件',
     *     'param'  => ''
     * )
     */
    public function clear()
    {
        $uploadSetting = cmf_get_upload_setting();
        $uploads=$uploadSetting['upload_max_filesize'];
        
        
        $dir='upload/upload/';
        $lists=scandir($dir,1);
        $files=[];
        foreach($lists as $k=>$v){
            
            if(substr($v, 0,1)==='.'){
                break;
            }
            if(is_dir($dir.$v)){
                $lists0=scandir($dir.$v,1);
                foreach($lists0 as $kk=>$vv){
                    if(substr($vv, 0,1)==='.'){
                        break;
                    }elseif(is_file($dir.$v.'/'.$vv)){
                        $ext=substr(strrchr($vv, "."), 1);
                        if(!isset($uploads[$ext])){
                            $files[]=$v.'/'.$vv;
                            unlink($dir.$v.'/'.$vv);
                        } 
                    }
                    
                }
                
            }else{
                $ext=substr(strrchr($v, "."), 1);
                if(!isset($uploads[$ext])){
                    $files[]=$v;
                    unlink($dir.$v);
                }
               
            }
            
        }
        
        var_dump($files);
       $this->success('清除完成');
    }
    
}
