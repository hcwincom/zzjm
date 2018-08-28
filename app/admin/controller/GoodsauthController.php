<?php
 
namespace app\admin\controller;

 
use cmf\controller\AdminBaseController; 
use think\Db; 
 /* 权限细分 */
class GoodsauthController extends AdminBaseController
{
    
    public function _initialize()
    {
        parent::_initialize();
       
    }
     
    /**
     * 产品权限细分
     * @adminMenu(
     *     'name'   => '产品权限细分',
     *     'parent' => 'admin/goods/index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '产品权限细分',
     *     'param'  => ''
     * )
     */
    public function index()
    { 
        
    }
     
    /**
     * 产品参数模板选择
     * @adminMenu(
     *     'name'   => '产品参数模板选择',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 80,
     *     'icon'   => '',
     *     'remark' => '产品参数模板选择',
     *     'param'  => ''
     * )
     */
    public function template_set(){
        
    }
    /**
     * 产品参数设置
     * @adminMenu(
     *     'name'   => '产品参数设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 81,
     *     'icon'   => '',
     *     'remark' => '产品参数设置',
     *     'param'  => ''
     * )
     */
    public function param_set(){
        
    }
    /**
     * 产品参数查看
     * @adminMenu(
     *     'name'   => '产品参数查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 82,
     *     'icon'   => '',
     *     'remark' => '产品参数查看',
     *     'param'  => ''
     * )
     */
    public function param_get(){
        $this->success('ok');
    }
    
    /**
     * 产品价格模板确认
     * @adminMenu(
     *     'name'   => '产品价格模板确认',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>90,
     *     'icon'   => '',
     *     'remark' => '产品价格模板确认',
     *     'param'  => ''
     * )
     */
    public function price_set(){
         
    }
   
     
    /**
     * 产品入库价设置
     * @adminMenu(
     *     'name'   => '产品入库价设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>91,
     *     'icon'   => '',
     *     'remark' => '产品入库价设置',
     *     'param'  => ''
     * )
     */
    public function price_in_set(){
        $this->success('ok');
    }
    /**
     * 产品出库价设置
     * @adminMenu(
     *     'name'   => '产品出库价设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>92,
     *     'icon'   => '',
     *     'remark' => '产品出库价设置',
     *     'param'  => ''
     * )
     */
    public function price_cost_set(){
        $this->success('ok');
    }
    /**
     * 产品最低销售价设置
     * @adminMenu(
     *     'name'   => '产品最低销售价设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>93,
     *     'icon'   => '',
     *     'remark' => '产品最低销售价设置',
     *     'param'  => ''
     * )
     */
    public function price_min_set(){
        $this->success('ok');
    }
    /**
     * 产品区间价格设置
     * @adminMenu(
     *     'name'   => '产品区间价设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>94,
     *     'icon'   => '',
     *     'remark' => '产品区间1价设置',
     *     'param'  => ''
     * )
     */
    public function price_range_set(){
        $this->success('ok');
    }
    /**
     * 产品经销价1设置
     * @adminMenu(
     *     'name'   => '产品经销价1设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>95,
     *     'icon'   => '',
     *     'remark' => '产品经销价1设置',
     *     'param'  => ''
     * )
     */
    public function price_dealer1_set(){
        $this->success('ok');
    }
    /**
     * 产品经销价2设置
     * @adminMenu(
     *     'name'   => '产品经销价2设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>96,
     *     'icon'   => '',
     *     'remark' => '产品经销价2设置',
     *     'param'  => ''
     * )
     */
    public function price_dealer2_set(){
        $this->success('ok');
    }
    /**
     * 产品经销价3设置
     * @adminMenu(
     *     'name'   => '产品经销价3设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>97,
     *     'icon'   => '',
     *     'remark' => '产品经销价3设置',
     *     'param'  => ''
     * )
     */
    public function price_dealer3_set(){
        $this->success('ok');
    }
    /**
     * 产品同行价设置
     * @adminMenu(
     *     'name'   => '产品同行价设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>97,
     *     'icon'   => '',
     *     'remark' => '产品同行价设置',
     *     'param'  => ''
     * )
     */
    public function price_trade_set(){
        $this->success('ok');
    }
    /**
     * 产品工厂配套价设置
     * @adminMenu(
     *     'name'   => '产品工厂配套价设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>98,
     *     'icon'   => '',
     *     'remark' => '产品工厂配套价设置',
     *     'param'  => ''
     * )
     */
    public function price_factory_set(){
        $this->success('ok');
    }
    
    /**
     * 产品入库价查看
     * @adminMenu(
     *     'name'   => '产品入库价查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>101,
     *     'icon'   => '',
     *     'remark' => '产品入库价查看',
     *     'param'  => ''
     * )
     */
    public function price_in_get(){
        $this->success('ok');
    }
    /**
     * 产品出库价查看
     * @adminMenu(
     *     'name'   => '产品出库价查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>102,
     *     'icon'   => '',
     *     'remark' => '产品出库价查看',
     *     'param'  => ''
     * )
     */
    public function price_cost_get(){
        $this->success('ok');
    }
    /**
     * 产品最低销售价查看
     * @adminMenu(
     *     'name'   => '产品最低销售价查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>103,
     *     'icon'   => '',
     *     'remark' => '产品最低销售价查看',
     *     'param'  => ''
     * )
     */
    public function price_min_get(){
        $this->success('ok');
    }
    /**
     * 产品区间价格查看
     * @adminMenu(
     *     'name'   => '产品区间价查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>104,
     *     'icon'   => '',
     *     'remark' => '产品区间1价查看',
     *     'param'  => ''
     * )
     */
    public function price_range_get(){
        $this->success('ok');
    }
    /**
     * 产品经销价1查看
     * @adminMenu(
     *     'name'   => '产品经销价1查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>105,
     *     'icon'   => '',
     *     'remark' => '产品经销价1查看',
     *     'param'  => ''
     * )
     */
    public function price_dealer1_get(){
        $this->success('ok');
    }
    /**
     * 产品经销价2查看
     * @adminMenu(
     *     'name'   => '产品经销价2查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>106,
     *     'icon'   => '',
     *     'remark' => '产品经销价2查看',
     *     'param'  => ''
     * )
     */
    public function price_dealer2_get(){
        $this->success('ok');
    }
    /**
     * 产品经销价3查看
     * @adminMenu(
     *     'name'   => '产品经销价3查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>107,
     *     'icon'   => '',
     *     'remark' => '产品经销价3查看',
     *     'param'  => ''
     * )
     */
    public function price_dealer3_get(){
        $this->success('ok');
    }
    /**
     * 产品同行价查看
     * @adminMenu(
     *     'name'   => '产品同行价查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>107,
     *     'icon'   => '',
     *     'remark' => '产品同行价查看',
     *     'param'  => ''
     * )
     */
    public function price_trade_get(){
        $this->success('ok');
    }
    /**
     * 产品工厂配套价查看
     * @adminMenu(
     *     'name'   => '产品工厂配套价查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>108,
     *     'icon'   => '',
     *     'remark' => '产品工厂配套价查看',
     *     'param'  => ''
     * )
     */
    public function price_factory_get(){
        $this->success('ok');
    }
    /**
     * 实物图片设置
     * @adminMenu(
     *     'name'   => '实物图片设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>110,
     *     'icon'   => '',
     *     'remark' => '实物图片设置',
     *     'param'  => ''
     * )
     */
    public function pic_pro_set(){
        $this->success('ok');
    }
    /**
     * logo图片设置
     * @adminMenu(
     *     'name'   => 'logo图片设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>111,
     *     'icon'   => '',
     *     'remark' => 'logo图片设置',
     *     'param'  => ''
     * )
     */
    public function pic_logo_set(){
        $this->success('ok');
    }
    /**
     * 规格图片设置
     * @adminMenu(
     *     'name'   => '规格图片设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>112,
     *     'icon'   => '',
     *     'remark' => '规格图片设置',
     *     'param'  => ''
     * )
     */
    public function pic_param_set(){
        $this->success('ok');
    }
    /**
     * 原理图片设置
     * @adminMenu(
     *     'name'   => '原理图片设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>113,
     *     'icon'   => '',
     *     'remark' => '原理图片设置',
     *     'param'  => ''
     * )
     */
    public function pic_principle_set(){
        $this->success('ok');
    }
    /**
     * 其他图片设置
     * @adminMenu(
     *     'name'   => '其他图片设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>114,
     *     'icon'   => '',
     *     'remark' => '其他图片设置',
     *     'param'  => ''
     * )
     */
    public function pic_other_set(){
        $this->success('ok');
    }
    /**
     * 说明书设置
     * @adminMenu(
     *     'name'   => '说明书设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>115,
     *     'icon'   => '',
     *     'remark' => '说明书设置',
     *     'param'  => ''
     * )
     */
    public function file_instructions_set(){
        $this->success('ok');
    }
    /**
     * 其他文档设置
     * @adminMenu(
     *     'name'   => '其他文档设置',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>116,
     *     'icon'   => '',
     *     'remark' => '其他文档设置',
     *     'param'  => ''
     * )
     */
    public function file_other_set(){
        $this->success('ok');
    }
    
    /**
     * 实物图片查看
     * @adminMenu(
     *     'name'   => '实物图片查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>120,
     *     'icon'   => '',
     *     'remark' => '实物图片查看',
     *     'param'  => ''
     * )
     */
    public function pic_pro_get(){
        $this->success('ok');
    }
    /**
     * logo图片查看
     * @adminMenu(
     *     'name'   => 'logo图片查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>121,
     *     'icon'   => '',
     *     'remark' => 'logo图片查看',
     *     'param'  => ''
     * )
     */
    public function pic_logo_get(){
        $this->success('ok');
    }
    /**
     * 规格图片查看
     * @adminMenu(
     *     'name'   => '规格图片查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>122,
     *     'icon'   => '',
     *     'remark' => '规格图片查看',
     *     'param'  => ''
     * )
     */
    public function pic_param_get(){
        $this->success('ok');
    }
    /**
     * 原理图片查看
     * @adminMenu(
     *     'name'   => '原理图片查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>123,
     *     'icon'   => '',
     *     'remark' => '原理图片查看',
     *     'param'  => ''
     * )
     */
    public function pic_principle_get(){
        $this->success('ok');
    }
    /**
     * 其他图片查看
     * @adminMenu(
     *     'name'   => '其他图片查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>124,
     *     'icon'   => '',
     *     'remark' => '其他图片查看',
     *     'param'  => ''
     * )
     */
    public function pic_other_get(){
        $this->success('ok');
    }
    /**
     * 说明书查看
     * @adminMenu(
     *     'name'   => '说明书查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>125,
     *     'icon'   => '',
     *     'remark' => '说明书查看',
     *     'param'  => ''
     * )
     */
    public function file_instructions_get(){
        $this->success('ok');
    }
    /**
     * 其他文档查看
     * @adminMenu(
     *     'name'   => '其他文档查看',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>126,
     *     'icon'   => '',
     *     'remark' => '其他文档查看',
     *     'param'  => ''
     * )
     */
    public function file_other_get(){
        $this->success('ok');
    }
     
    //图片下载
    public function goods_file_load($type=1){
        $id=$this->request->param('id',0,'intval');
        $pid=$this->request->param('pid',0,'intval');
        $where=[
            'id'=>$id,
            'type'=>$type,
        ];
        $info=Db::name('goods_file')->where($where)->find();
        if(empty($info)){
            $this->error('数据错误，文件不存在');
        }
        $path='upload/';
        $file=$path.$info['file'];
        $filename=empty($info['name'])?date('Ymd-His'):$info['name'];
        if(is_file($file)){
            $fileinfo=pathinfo($file);
            $ext=$fileinfo['extension'];
            $filename=$filename.'.'.$ext;
            header('Content-type: application/x-'.$ext);
            header('content-disposition:attachment;filename='.$filename);
            header('content-length:'.filesize($file));
            readfile($file);
            exit;
        }else{
            $this->error('文件损坏，不存在');
        }
    }
    
    /**
     * 商城图片下载
     * @adminMenu(
     *     'name'   => '商城图片下载',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>130,
     *     'icon'   => '',
     *     'remark' => '商城图片下载',
     *     'param'  => ''
     * )
     */
    public function pic_jm_load(){
        $this->goods_file_load(1);
    }
    /**
     * 实物图片下载
     * @adminMenu(
     *     'name'   => '实物图片下载',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>131,
     *     'icon'   => '',
     *     'remark' => '实物图片下载',
     *     'param'  => ''
     * )
     */
    public function pic_pro_load(){
        $this->goods_file_load(2);
    }
    /**
     * logo图片下载
     * @adminMenu(
     *     'name'   => 'logo图片下载',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>132,
     *     'icon'   => '',
     *     'remark' => 'logo图片下载',
     *     'param'  => ''
     * )
     */
    public function pic_logo_load(){
        $this->goods_file_load(3);
    }
    /**
     * 规格图片下载
     * @adminMenu(
     *     'name'   => '规格图片下载',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>133,
     *     'icon'   => '',
     *     'remark' => '规格图片下载',
     *     'param'  => ''
     * )
     */
    public function pic_param_load(){
        $this->goods_file_load(4);
    }
    /**
     * 原理图片下载
     * @adminMenu(
     *     'name'   => '原理图片下载',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>134,
     *     'icon'   => '',
     *     'remark' => '原理图片下载',
     *     'param'  => ''
     * )
     */
    public function pic_principle_load(){
        $this->goods_file_load(5);
    }
    /**
     * 其他图片下载
     * @adminMenu(
     *     'name'   => '其他图片下载',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>135,
     *     'icon'   => '',
     *     'remark' => '其他图片下载',
     *     'param'  => ''
     * )
     */
    public function pic_other_load(){
        $this->goods_file_load(6);
    }
    /**
     * 说明书下载
     * @adminMenu(
     *     'name'   => '说明书下载',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>136,
     *     'icon'   => '',
     *     'remark' => '说明书下载',
     *     'param'  => ''
     * )
     */
    public function file_instructions_load(){
        $this->goods_file_load(7);
    }
    /**
     * 其他文档下载
     * @adminMenu(
     *     'name'   => '其他文档下载',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>137,
     *     'icon'   => '',
     *     'remark' => '其他文档下载',
     *     'param'  => ''
     * )
     */
    public function file_other_load(){
        $this->goods_file_load(8);
    }
    
    
    /**
     * 标签图片下载
     * @adminMenu(
     *     'name'   => '标签图片下载',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>140,
     *     'icon'   => '',
     *     'remark' => '标签图片下载',
     *     'param'  => ''
     * )
     */
    public function label_pic_load(){
        $pid=$this->request->param('pid',0,'intval');
        $key=$this->request->param('key',0,'intval');
        $where=[
            'pid0'=>$pid, 
        ];
        $info=Db::name('goods_label')->where($where)->find();
        if(empty($info)){
            $this->error('数据错误，文件不存在');
        }
        $path='upload/';
        $file=$path.$info['pic'.$key];
       
        if(is_file($file)){
            $fileinfo=pathinfo($file);
            $ext=$fileinfo['extension'];
            $filename='标签图片'.date('Ymd-His').'.'.$ext;
            header('Content-type: application/x-'.$ext);
            header('content-disposition:attachment;filename='.$filename);
            header('content-length:'.filesize($file));
            readfile($file);
            exit;
        }else{
            $this->error('文件损坏，不存在');
        }
    }
    /**
     * 标签文档下载
     * @adminMenu(
     *     'name'   => '标签文档下载',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  =>141,
     *     'icon'   => '',
     *     'remark' => '标签文档下载',
     *     'param'  => ''
     * )
     */
    public function label_file_load(){
        $pid=$this->request->param('pid',0,'intval');
        $key=$this->request->param('key',0,'intval');
        $where=[
            'pid0'=>$pid,
        ];
        $info=Db::name('goods_label')->where($where)->find();
        if(empty($info)){
            $this->error('数据错误，文件不存在');
        }
        $path='upload/';
        $files=json_decode($info['files'],true);
        if(empty($files[$key]['file'])){
            $this->error('数据错误，文件不存在');
        }
        $file=$path.$files[$key]['file'];
       
        if(is_file($file)){
            $fileinfo=pathinfo($file);
            $ext=$fileinfo['extension'];
            $filename='标签文档'.$files[$key]['name'].'.'.$ext;
            header('Content-type: application/x-'.$ext);
            header('content-disposition:attachment;filename='.$filename);
            header('content-length:'.filesize($file));
            readfile($file);
            exit;
        }else{
            $this->error('文件损坏，不存在');
        }
    }
}
