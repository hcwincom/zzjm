<?php
 
namespace app\goods\controller;

 
use app\common\controller\AdminBase0Controller; 
use think\Db; 
use app\goods\model\GoodsModel;
/*
 * 产品页面的ajax  */ 
class GoodsajaxController extends AdminBase0Controller
{ 
    public function _initialize()
    { 
        parent::_initialize();
        //计算小数位
        bcscale(2); 
    }
     
     
    
    //选择二级分类得到三级编码
    public function cid_change(){
        $cid=$this->request->param('cid',0,'intval');
        $id=$this->request->param('id',0,'intval');
        if($cid<1){
            $this->error('无效分类');
        }
        $m=Db::name('goods');
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
        $info=Db::name('cate')->field('id,max_num')->where($where)->find();
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
        $m=Db::name('goods');
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
         
        $cate=Db::name('cate')
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
        $tmps=Db::name('template')->where($where)->order('sort asc')->column('id,name');
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
        $tmps=Db::name('template_param')
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
        $m_collect=Db::name('goods_collect');
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
    //获取价格模板
    public function prices(){
        $where=[
            'status'=>2,
        ];
        $prices=Db::name('price')->where($where)->order('sort asc,name asc')->column('id,name');
        $this->assign('prices',$prices); 
    }
    
    //根据分类和技术模板得到二级分类和产品
    public function goods_get_by_template(){
        $cid0=$this->request->param('cid0',0,'intval');
        $tid=$this->request->param('tid',0,'intval');
        if($cid0<=0){
            $this->error('请选择一级分类');
        }
        if($tid<=0){
            $this->error('请选择技术模板');
        }
        $where_cate=[
            'fid'=>$cid0,
            'status'=>2,
        ];
        $cate=Db::name('cate')->where($where_cate)->column('id,name');
        if(empty($cate)){
            $this->error('没有找到符合条件的产品');
        }
        $where_goods=[
            'cid0'=>$cid0,
            'status'=>2,
            'template'=>$tid,
        ];
        $goods=Db::name('goods')->where($where_goods)->column('id,cid,name');
        if(empty($goods)){
            $this->error('没有找到符合条件的产品');
        }
        $where_param=[
            'tp.t_id'=>$tid,
            'p.status'=>2,
        ];
        $params=Db::name('template_param')
        ->alias('tp')
        ->join('cmf_param p','p.id=tp.p_id')
        ->where($where_param)->column('p.id,p.name');
        $this->success('ok','',['cate'=>$cate,'goods'=>$goods,'params'=>$params]);
        
    }
    //获取分类下所有产品
    public function goods(){
        $cid=$this->request->param('cid');
        $where=[
            'cid'=>$cid,
            'status'=>2,
        ];
        $admin=$this->admin; 
        $where['shop']=($admin['shop']==1)?2:$admin['shop'];
        
        $goods=Db::name('goods')->where($where)->column('id,name');
        $this->success('ok','',$goods);
    }
    //获取产品的参数值
    public function get_param_by_goods(){
        $pid=$this->request->param('pid');
        $goods=Db::name('goods')->where('id',$pid)->find();
        if(empty($goods)){
            $this->error('没有找到符合条件的产品');
        }
        $units=config('units');
        $unit=$units[$goods['unit']];
        $goods['unit_name']=implode(',', $unit);
        $param=Db::name('goods_param')->where('pid',$pid)->column('param_id,value');
        $this->success('ok','',['goods'=>$goods,'param'=>$param]);
    }
    /*
     * 根据fid获取分类 */
    public function get_cates()
    {
        $fid=$this->request->param('fid',0,'intval'); 
        $where=[
            'status'=>2,
            'fid'=>$fid,
        ];
        $list=Db::name('cate')->where($where)->order('sort asc,code_num asc')->column('id,name');
        $this->success('ok','',$list);
    }
    /*
     * 根据cid获取产品 */
    public function get_goods()
    {
        $cid=$this->request->param('cid');
        $where=[
            'cid'=>$cid,
            'status'=>2,
        ];
        $admin=$this->admin;
        $where['shop']=($admin['shop']==1)?2:$admin['shop']; 
        $goods=Db::name('goods')->where($where)->column('id,name');
        $this->success('ok','',$goods);
    }
    /*
     * 根据id获取产品 */
    public function get_goods_info()
    {
        $id=$this->request->param('id');
        $shop=$this->request->param('shop');
        if(empty($shop)){
            $admin=$this->admin;
            $shop=($admin['shop']==1)?2:$admin['shop'];
        }
        
        $m_goods=new GoodsModel();
        $goods=$m_goods->goods_info($id,$shop);
        $this->success('ok','',$goods);
    }
    
   
}
