<?php
 
namespace app\admin\controller;

 
use cmf\controller\AdminBaseController; 
use think\Db; 
/*
 * 产品页面的ajax  */ 
class GoodsajaxController extends AdminBaseController
{
    private $m;
    private $statuss;
    private $review_status;
    private $table;
    
    private $fields;
    private $flag;
    private $file_type;
    public function _initialize()
    {
        parent::_initialize();
        $this->m=Db::name('goods');
        $this->flag='产品'; 
        $this->table='goods'; 
        
        $this->statuss=config('info_status');
        $this->review_status=config('review_status');
         
        //计算小数位
        bcscale(2);
        
        $this->file_type=[
            1=>['pic_jm','极敏商城图片'],
            2=>['pic_pro','实物图片'],
            3=>['pic_logo','极敏logo图片'],
            4=>['pic_param','产品规格图'],
            5=>['pic_principle','产品原理图'],
            6=>['pic_other','其他图片'],
            7=>['file_instructions','产品说明书'], 
            8=>['file_other','其他文档'],
        ];
         
    }
     
     
    
    //选择二级分类得到三级编码
    public function cid_change(){
        $cid=$this->request->param('cid',0,'intval');
        $id=$this->request->param('id',0,'intval');
        if($cid<1){
            $this->error('无效分类');
        }
        $m=$this->m;
        if($id!=0){
            $info=$m->where(['id'=>$id])->find();
            //分类没变
            if($info['cid']==$cid){
                $this->success($info['code_num']);
                exit;
            }
        }
        $where=[
            'id'=>$cid, 
            'fid'=>['neq',0],
        ];
        $info=db('cate')->field('id,max_num')->where($where)->find();
        if(empty($info)){
            $this->error('无效分类');
        }else{
            $this->success($info['max_num']+1);
        }
        
    }
    
    //产品添加编码
    public function add_code(){
        $id=$this->request->param('id',0,'intval');
        $cid=$this->request->param('cid',0,'intval');
        $code_num=$this->request->param('code_num',0,'intval');
        $name=$this->request->param('name','');
        $m=$this->m;
        //检查编码是否合法
        $where=[
            'cid'=>$cid,
            'code_num'=>$code_num,
        ];
        if(!empty($id)){
            $where['id']=['neq',$id];
        }
        $tmp=$m->where($where)->find();
        if(!empty($tmp)){
            $this->error('该编码已存在');
        }
         
        $cate=db('cate')
        ->field('c.*,f.name as fname')
        ->alias('c')
        ->join('cmf_cate f','f.id=c.fid')
        ->where('c.id',$cid)
        ->find();
        if(empty($cate) || $cate['fid']==0){
            $this->error('分类选择不合法');
        } 
        
        //下面组装产品名称和编码 
        $name0=$cate['name'].$name.$cate['fname'];
        $code=$cate['code'].'-'.str_pad($code_num, 2,'0',STR_PAD_LEFT);
        $this->success($name0,'',['code'=>$code]);
    }
    /**
     * 产品参数模板选择 
     */
    public function template_set(){
        $cid=$this->request->param('cid',0,'intval');
        if($cid<=0){
            $this->success('no');
        }
        $where=[
            'cid'=>$cid,
            'status'=>2,
        ];
        $tmps=db('template')->where($where)->order('sort asc')->column('id,name');
        if(empty($tmps)){
            $this->success('no');
        }
        $this->success('ok','',['list'=>$tmps]);
    }
    /**
     * 产品参数设置 
     */
    public function param_set(){
        $t_id=$this->request->param('t_id',0,'intval');
        if($t_id<=0){
            $this->success('no');
        }
        $where=[
            'tp.t_id'=>$t_id,
            'p.status'=>2,
        ];
        $tmps=db('template_param')
        ->alias('tp')
        ->join('cmf_param p','tp.p_id=p.id')
        ->where($where)
        ->order('p.sort asc')
        ->column('p.id,p.name,p.type,p.content,p.dsc');
       
        if(empty($tmps)){
            $this->success('no');
        }
        foreach($tmps as $k=>$v){
            if($tmps[$k]['type']==3){
                $tmps[$k]['content']='';
            }else{
                //清除不规范输入导致的空格
                $tmps[$k]['content']=explode(',',$tmps[$k]['content']);
            }
        }
        $this->success('ok','',['list'=>$tmps]);
    }
    
    //获取价格模板
    public function prices(){
        $where=[
            'status'=>2,
        ];
        $prices=db('price')->where($where)->order('sort asc,name asc')->column('id,name');
        $this->assign('prices',$prices);
        
    }
    /**
     * 产品价格模板确认 
     */
    public function price_set(){
        $t_id=$this->request->param('t_id',0,'intval');
        $price_in=$this->request->param('price_in',0);
        if($t_id<=0 || $price_in<=0){
            $this->success('no');
        }
        $price_in=round($price_in,2);
        //按模板规则计算得到各种价格，暂时不写
        $data=[];
        $data['price_cost']=$price_in;
        $data['price_min']=$price_in;
        $data['price_range1']=$price_in;
        $data['price_range2']=$price_in;
        $data['price_range3']=$price_in;
        $data['price_dealer1']=$price_in;
        $data['price_dealer2']=$price_in;
        $data['price_dealer3']=$price_in;
        $data['price_trade']=$price_in;
        $data['price_factory']=$price_in;
        $this->success('ok','',$data);
        
    }
     /*  
      * 收藏
      * */
    public function goods_collect(){
        $pid=$this->request->param('pid',0,'intval');
        $admin=$this->admin;
        $where=[
            'pid'=>$pid,
            'uid'=>$admin['id'],
        ];
        $m_collect=db('goods_collect');
        $tmp=$m_collect->where($where)->find();
        $time=time();
        if(empty($tmp)){
            $data=[
                'pid'=>$pid,
                'uid'=>$admin['id'],
                'type'=>4,
                'ctime'=>$time,
                'time'=>$time,
            ];
            $m_collect->insert($data);
            $this->success('已收藏');
        }else{
            $data=[ 
                'time'=>$time,
            ];
            $m_collect->where('id',$tmp['id'])->update($data);
            $this->success('已更新时间');
        }
    }
     
}
