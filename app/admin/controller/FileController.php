<?php
 
namespace app\admin\controller;

use cmf\controller\AdminBaseController; 
 use think\Db;
 /**
  * Class FileController
  * @package app\admin\controller
  * @adminMenuRoot(
  *     'name'   => '文件管理',
  *     'action' => 'default',
  *     'parent' => '',
  *     'display'=> true,
  *     'order'  => 10000,
  *     'icon'   => '',
  *     'remark' => '文件管理'
  * )
  */
class FileController extends AdminBaseController
{
    
    public function _initialize()
    {
        parent::_initialize();
       
        $this->flag='文件管理';
        $admin=$this->admin;
        if($admin['shop']!=1){
            exit('只有系统超管才能查看');
        }
        //没有店铺区分
        $this->isshop=1;
        $this->assign('flag',$this->flag);
      
        
    }
    /**
     * 文件管理
     * @adminMenu(
     *     'name'   => '文件管理',
     *     'parent' => 'default',
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
        $names=Db::name('shop')->column('id,name');
        $path='upload/';
        $list=scandir($path); 
        $dirs=['brand'=>['品牌',url('brand')]];
        foreach($list as $v){
            if(substr($v, 0,1)==='.'){
                continue;
            }
            if(is_dir($path.$v) && substr($v, 0,6)==='seller'){
                $id=substr($v, 6);
                $dirs[$v]=[
                    isset($names[$id])?$names[$id]:'店铺不存在'.$id,
                    url('seller',['id'=>$id]),
                ];    
            }
        }
       
        $this->assign('dirs',$dirs);
        return $this->fetch();
    }
    /**
    * 纯文件目录
    * @adminMenu(
    *     'name'   => '纯文件目录',
    *     'parent' => 'default',
    *     'display'=> true,
    *     'hasView'=> true,
    *     'order'  => 90,
    *     'icon'   => '',
    *     'remark' => '纯文件目录',
    *     'param'  => ''
    * )
    */
    public function files()
    {
        $data=$this->request->param();
        $path0='upload/';
        $code='dirdir';
         
        
        $dir0=isset($data['dir'])?$data['dir']:$code;
        $dir=str_replace($code, '/',$dir0);
        $path=$path0.$dir;
       
        if(!is_dir($path)){
            $this->error('不是目录');
        }
        //获取上级目录 ,先取出upload再转化
        $fpath=str_replace('/',$code, substr(dirname($path),6));
        dump($fpath);
        $url_up=url('files',['dir'=>$fpath]);
        $list=scandir($path);
        //目录
        $dirs=[];
        $url_scan=(url('files','',false,false)).'/dir/'.$dir0;
        //文件
        $files=[];
        $url_load=(url('load','',false,false)).'/dir/'.$dir0;
        
        foreach($list as $v){
            if(substr($v, 0,1)==='.'){
                continue;
            }
            if(is_dir($path.$v) ){
               
                $dirs[]=[
                    'name'=>$v,
                    'scan'=>$url_scan.$v.$code,
                    'load'=>$url_load.$v.$code,
                    
                ];
            }else{
                $files[]=[
                    'name'=>$v,
                    'load'=>$url_load.$v,
                    
                ];
            }
        }
        
        $this->assign('dirs',$dirs);
        $this->assign('files',$files);
        $this->assign('url_up',$url_up);
        return $this->fetch();
    }
    /**
     * 品牌图片
     * @adminMenu(
     *     'name'   => '品牌图片',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 90,
     *     'icon'   => '',
     *     'remark' => '品牌图片',
     *     'param'  => ''
     * )
     */
    public function brand()
    {
        
        $names=Db::name('brand')->column('id,name');
        $path='upload/brand/';
        $list=scandir($path);
        $dirs=[];
        foreach($list as $v){
            if(substr($v, 0,1)==='.'){
                continue;
            }
            if(is_dir($path.$v)){
                $dirs[$v]=[
                    'name'=>isset($names[$v])?$names[$v]:'品牌不存在'.$v,   
                ];
                $dirs[$v]['url']=url('files',['table'=>'brand','shop'=>'','id'=>$v]);
            }
        }
        
        $this->assign('dirs',$dirs);
        
        return $this->fetch();
    }
    /**
     * 文件列表
     * @adminMenu(
     *     'name'   => '文件列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 90,
     *     'icon'   => '',
     *     'remark' => '文件列表',
     *     'param'  => ''
     * )
     */
    public function files1()
    {
        $data=$this->request->data();
        switch($data['table']){
            case 'brand':
                $path='upload/brand/'.$data['id'].'/';
                $flag='品牌';
                break;
                
            case 'shop':
                $path='upload/seller'.$data['shop'].'/';
                break;
        }
       
        $list=scandir($path);
        $files=[];
        foreach($list as $v){
            if(substr($v, 0,1)==='.'){
                continue;
            }
            $files[]=$v;
        }
        
        $this->assign('files',$files);
        $this->assign('path',$path);
        $this->assign('flag',$flag);
        return $this->fetch();
    }
    /**
     * 店铺图片文件
     * @adminMenu(
     *     'name'   => '店铺图片文件',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 90,
     *     'icon'   => '',
     *     'remark' => '店铺图片文件',
     *     'param'  => ''
     * )
     */
    public function seller()
    {
        $id=$this->request->param('id',0,'intval');
        $names=Db::name('goods')->where('shop',$id)->column('id,name');
        $path='upload/seller'.$id.'/';
        $list=scandir($path);
        $dirs=[];
        foreach($list as $v){
            if(substr($v, 0,1)==='.'){
                continue;
            }
            if(is_dir($path.$v) && substr($v, 0,5)==='goods'){
                $id=substr($v, 5);
                $dirs[$id]=isset($names[$id])?$names[$id]:'产品不存在'.$id;
            }
            if(is_dir($path.$v)){
                $dirs[$v]=isset($names[$v])?$names[$v]:'产品不存在'.$v;
            }
        }
        
        $this->assign('dirs',$dirs);
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
