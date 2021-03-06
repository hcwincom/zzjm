<?php
 
namespace app\goods\controller;

 
use cmf\controller\AdminBaseController; 
use think\Db; 
use app\goods\model\GoodsModel;
 

class AdminGoodsController extends AdminBaseController
{
    private $m;
    private $statuss;
    private $review_status;
    private $table;
    private $tables;
    private $fields;
    private $flag;
    private $file_type;
    private $goods_type;
    private $units;
    private $fields_link;
    private $where_shop;
    
    public function _initialize()
    {
        parent::_initialize();
//         $this->m=Db::name('goods');
        $this->m=new GoodsModel();
        $this->flag='产品'; 
        $this->table='goods'; 
        $this->assign('flag',$this->flag);
        $this->statuss=config('info_status');
        $this->review_status=config('review_status');
        $this->goods_type=config('goods_type');
        
        $this->assign('statuss',$this->statuss);
        $this->assign('review_status',$this->review_status);
        $this->assign('html',$this->request->action());
        $this->assign('goods_type',$this->goods_type);
        $this->assign('sn_type',config('sn_type'));
        $this->assign('is_box',config('is_box'));
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
        $this->assign('file_type',$this->file_type);
        
        $this->tables=['goods','goods_file','goods_content','goods_type2','goods_type3','goods_type4','goods_type5'];
        //计量单位
        $units=config('units');
        foreach($units as $k=>$v){
            $units[$k]=implode(',', $v);
        }
        $this->units=$units;
        $this->assign('units',$units);
        //主产品修改审核，加标产品编辑审核都会同步这些字段。加标产品编辑会过滤这些字段
        $this->fields_link=['unit','weight0','length0','width0',
            'height0','size0','is_box','weight1','length1','width1',
            'height1','size1','template',
        ];
        $this->assign('url_goods',url('edit','',false,false));
        
    }
     
    /**
     * 产品列表
     * @adminMenu(
     *     'name'   => '产品列表',
     *     'parent' => 'goods/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 1,
     *     'icon'   => '',
     *     'remark' => '产品列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {  
        $m=$this->m;
        $data=$this->request->param();
        $admin=$this->admin;
        $where=[]; 
        
        $tmp=$this->search($where, $data,$admin);
        $where=$tmp['where'];
        $data=$tmp['data'];
       
        //类型
        if(empty($data['type'])){
            $data['type']=0;
        }else{
            $where['p.type']=['eq',$data['type']];
        }
         
        
        //关联设备数
        $goods_links=[
            '-1'=>'关联设备',
            '0'=>'0个',
            '1'=>'1个',
            '2'=>'2个',
            '3'=>'3个以上',
        ];
        $this->assign('goods_links',$goods_links);
        if(!isset($data['goods_link']) || $data['goods_link']==-1){
            $data['goods_link']=-1;
        }else{
            switch($data['goods_link']){
                case 3:
                    $where['p.goods_link']=['egt',3];
                    break;
                default:
                    $where['p.goods_link']=['eq',$data['goods_link']];
                    break;
            }
        }
        
        //关联资料数
        $file_type=$this->file_type;
        $about_link_nums=[
            '-1'=>'数量',
            '0'=>'0个',
            '1'=>'1个',
            '2'=>'2个',
            '3'=>'3个以上',
        ];
        $this->assign('about_link_nums',$about_link_nums);
        if(empty($data['about_link']) || !isset($data['about_link_num']) || $data['about_link_num']==-1){
            $data['about_link']=0;
            $data['about_link_num']=-1;
            
        }else{
            $about=$file_type[$data['about_link']][0];
            switch($data['about_link_num']){
                case 3:
                    $where['p.'.$about]=['egt',3];
                    break;
                default:
                    $where['p.'.$about]=['eq',$data['about_link_num']];
                    break;
            }
        }
        //先获取id和分页
        $list=$m
        ->alias('p')
        ->field('p.id') 
        ->where($where)
        ->order('p.status asc,p.time desc')
        ->paginate();
        // 获取分页显示
        $page = $list->appends($data)->render();
        $ids=[];
        foreach($list as $k=>$v){
            $ids[]=$v['id'];
        }
        if(empty($ids)){
            $list=[];
        }else{
            $list=$m
            ->alias('p') 
            ->join('cmf_brand b','b.id=p.brand','left')
            ->join('cmf_user a','a.id=p.aid','left')
            ->join('cmf_user r','r.id=p.rid','left')
            ->where('p.id','in',$ids)
            ->order('p.status asc,p.time desc')
            ->column('p.*,b.name as bname,a.user_nickname as aname,r.user_nickname as rname');
            //取商城图片
            $list=$m->goods_pics($list);
            
        }
         
        $this->assign('page',$page);
        $this->assign('list',$list); 
        $this->assign('data',$data);
      
        return $this->fetch();
    }
    /**
     * 产品查询
     */
    public function search($where,$data,$admin,$shop_field='p.shop'){
        
        //店铺 
        $res=zz_shop($admin, $data, $where,$shop_field);
        $data=$res['data'];
        $where=$res['where'];
        $this->where_shop=$res['where_shop'];
        
        //状态
        if(empty($data['status'])){
            $data['status']=0;
        }else{
            $where['p.status']=['eq',$data['status']];
        }
        //一级分类
        if(empty($data['cid0'])){
            $data['cid0']=0;
        }else{
            $where['p.cid0']=['eq',$data['cid0']];
        }
        //二级分类
        if(empty($data['cid'])){
            $data['cid']=0;
        }else{
            $where['p.cid']=['eq',$data['cid']];
        }
        
        //品牌分类
        if(empty($data['bchar']) || $data['bchar']==-1){
            $data['bchar']=-1;
        }else{
            $where['p.bchar']=['eq',$data['bchar']];
        }
        //品牌
        if(empty($data['brand'])){
            $data['brand']=0;
        }else{
            $where['p.brand']=['eq',$data['brand']];
        }
         
        //价格
        $prices=[
            'price0'=>'价格',
            'price_sale'=>'零售价格',
            'price_in'=>'入库价',
            'price_cost'=>'出厂价',
            'price_min'=>'最低销售价',
            'price_range1'=>'区间价1',
            'price_range2'=>'区间价2',
            'price_range3'=>'区间价3',
            'price_dealer1'=>'经销价1',
            'price_dealer2'=>'经销价2',
            'price_dealer3'=>'经销价3',
            'price_trade'=>'同行价',
            'price_factory'=>'工程配套价',
        ];
        $this->assign('prices',$prices);
        if(empty($data['price']) || $data['price']=='price0'){
            $data['price']='price0';
            $data['price1']='';
            $data['price2']='';
        }else{
            
            //判断处理价格参数
            if(!isset($data['price1']) || $data['price1']==''){
                $data['price1']=='';
                
            }else{
                $price1=0;
                $data['price1']=round($data['price1'],2);
                $price1=$data['price1'];
                if($price1<0){
                    $this->error('价格不能小于0');
                }
            }
            if(!isset($data['price2']) || $data['price2']==''){
                $data['price2']=='';
                
            }else{
                $price2=0;
                $data['price2']=round($data['price2'],2);
                $price2=$data['price2'];
                if($price2<0){
                    $this->error('价格不能小于0');
                }
            }
            //判断查询条件
            if(isset($price1)){
                if(isset($price2)){
                    //最大最小价格都有
                    if($price2<$price1){
                        $this->error('最大价格不能小于最小价格');
                    }
                    $where_price=['between',[$price1,$price2]];
                }else{
                    //最小价格
                    $where_price=['egt',$price1];
                }
            }elseif(isset($price2)){
                //只有最大价
                $where_price=['elt',$price2];
            }
            //组装
            if(!empty($where_price)){
                $where['p.'.$data['price']]=$where_price;
            }
            
        }
        
        
        //重量体积
        $bigs=[
            'big0'=>'重量体积',
            'weight0'=>'净重量',
            'size0'=>'净体积',
            'weight1'=>'毛重量',
            'size1'=>'毛体积', 
        ];
        $this->assign('bigs',$bigs);
        if(empty($data['big']) || $data['big']=='big0'){
            $data['big']='big0';
            $data['big1']='';
            $data['big2']='';
            
        }else{
            
            //判断处理重量体积参数
            if(!isset($data['big1']) || $data['big1']==''){
                $data['big1']=='';
                
            }else{
                $big1=0;
                $data['big1']=round($data['big1'],2);
                $big1=$data['big1'];
                if($big1<0){
                    $this->error('重量体积不能小于0');
                }
            }
            if(!isset($data['big2']) || $data['big2']==''){
                $data['big2']=='';
                
            }else{
                $big2=0;
                $data['big2']=round($data['big2'],2);
                $big2=$data['big2'];
                if($big2<0){
                    $this->error('重量体积不能小于0');
                }
            }
            //判断查询条件
            if(isset($big1)){
                if(isset($big2)){
                    //最大最小重量体积都有
                    if($big2<$big1){
                        $this->error('最大重量体积不能小于最小重量体积');
                    }
                    $where_big=['between',[$big1,$big2]];
                }else{
                    //最小重量体积
                    $where_big=['egt',$big1];
                }
            }elseif(isset($big2)){
                //只有最大价
                $where_big=['elt',$big2];
            }
            
            //组装
            if(!empty($where_big)){
                $where['p.'.$data['big']]=$where_big;
            }
        }
        
        //查询字段
        $types=[
            1=>['p.name','全名'],
            2=>['p.code' , '编码'],
            3=>['p.code_name' , '同级名称'],
            4=>['p.name2' , '商城名称'],
            5=>['p.name3' , '打印名称'],
            11=>['p.sn','条码'],
            6=>['p.id' , '产品id'],
            7=>['p.aid' , '创建人id'],
            
            9=>['p.rid' , '审核人id'],
           
        ];
           
        //选择查询字段
        if(empty($data['type1'])){
            $data['type1']=1;
        }
        //搜索类型
        $search_types=config('search_types');
        if(empty($data['type2'])){
            $data['type2']=key($search_types);
        }
        if(!isset($data['name']) || $data['name']==''){
            $data['name']='';
        }else{
            $where[$types[$data['type1']][0]]=zz_search($data['type2'],$data['name']);
        }
        $this->assign('types',$types);
        $this->assign("search_types", $search_types);
        
        //时间类别
        $times=config('time1_search');
        if(empty($data['time'])){
            $data['time']=key($times);
            $data['datetime1']='';
            $data['datetime2']='';
        }else{
            //时间处理
            if(empty($data['datetime1'])){
                $data['datetime1']='';
                $time1=0;
                if(empty($data['datetime2'])){
                    $data['datetime2']='';
                    $time2=0;
                }else{
                    //只有结束时间
                    $time2=strtotime($data['datetime2']);
                    $where['p.'.$data['time']]=['elt',$time2];
                }
            }else{
                //有开始时间
                $time1=strtotime($data['datetime1']);
                if(empty($data['datetime2'])){
                    $data['datetime2']='';
                    $where['p.'.$data['time']]=['egt',$time1];
                }else{
                    //有结束时间有开始时间between
                    $time2=strtotime($data['datetime2']);
                    if($time2<=$time1){
                        $this->error('结束时间必须大于起始时间');
                    }
                    $where['p.'.$data['time']]=['between',[$time1,$time2]];
                }
            }
        }
        $this->assign('times',$times);
      
        //分类
        $this->cates(1);
        $this->assign('cid0',$data['cid0']);
        $this->assign('cid',$data['cid']);
        $this->assign('select_class','form-control');
       
        $this->assign('bchar',$data['bchar']);
        $this->assign('brand',$data['brand']);
        
        return ['where'=>$where,'data'=>$data];
    }
     
    /**
     * 产品组合关联
     * @adminMenu(
     *     'name'   => '产品组合关联',
     *     'parent' => 'goods/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 2,
     *     'icon'   => '',
     *     'remark' => '产品组合关联',
     *     'param'  => ''
     * )
     */
    public function links2()
    {
        
        $admin=$this->admin;
        
        $m=$this->m;
        $data=$this->request->param();
        $admin=$this->admin;
        $where=[];
        //类型组合
        
        $where['gl.type']=['eq',2];
        
        $tmp=$this->search($where, $data,$admin,'gl.shop');
        $where=$tmp['where'];
        $data=$tmp['data'];
        //关联设备数
        $goods_links=[
            '-1'=>'关联设备',
            '0'=>'0个',
            '1'=>'1个',
            '2'=>'2个',
            '3'=>'3个以上',
        ];
        $this->assign('goods_links',$goods_links);
        if(!isset($data['goods_link']) || $data['goods_link']==-1){
            $data['goods_link']=-1;
        }else{
            switch($data['goods_link']){
                case 3:
                    $where['p.goods_link']=['egt',3];
                    break;
                default:
                    $where['p.goods_link']=['eq',$data['goods_link']];
                    break;
            }
        }
        
        //关联资料数
        $file_type=$this->file_type;
        $about_link_nums=[
            '-1'=>'数量',
            '0'=>'0个',
            '1'=>'1个',
            '2'=>'2个',
            '3'=>'3个以上',
        ];
        $this->assign('about_link_nums',$about_link_nums);
        if(empty($data['about_link']) || !isset($data['about_link_num']) || $data['about_link_num']==-1){
            $data['about_link']=0;
            $data['about_link_num']=-1;
            
        }else{
            $about=$file_type[$data['about_link']][0];
            switch($data['about_link_num']){
                case 3:
                    $where['p.'.$about]=['egt',3];
                    break;
                default:
                    $where['p.'.$about]=['eq',$data['about_link_num']];
                    break;
            }
        }
        //主产品还是副产品 
        if(empty($data['is_link'])){
            $data['is_link']=0;
            $list=Db::name('goods_link')
            ->alias('gl')
            ->field('p.*,gl.pid1,gl.num as link_num,gl.dsc,p1.name as link_name,p1.code as link_code,b.name as bname,a.user_nickname as aname,r.user_nickname as rname')
            ->join('cmf_goods p','p.id=gl.pid0')
            ->join('cmf_goods p1','p1.id=gl.pid1','left') 
            ->join('cmf_brand b','b.id=p.brand','left')
            ->join('cmf_user a','a.id=p.aid','left')
            ->join('cmf_user r','r.id=p.rid','left')
            ->where($where)
            ->order('p.status asc,p.time desc')
            ->paginate();
        }else{
            $list=Db::name('goods_link')
            ->alias('gl')
            ->field('p0.*,gl.pid1,gl.num as link_num,gl.dsc,p.name as link_name,p.code as link_code,b.name as bname,a.user_nickname as aname,r.user_nickname as rname')
            ->join('cmf_goods p0','p0.id=gl.pid0')
            ->join('cmf_goods p','p.id=gl.pid1','left')
            ->join('cmf_brand b','b.id=p.brand','left')
            ->join('cmf_user a','a.id=p.aid','left')
            ->join('cmf_user r','r.id=p.rid','left')
            ->where($where)
            ->order('p.status asc,p.time desc')
            ->paginate();
        }
        
        // 获取分页显示
        $page = $list->appends($data)->render();
        
        $this->assign('page',$page);
        $this->assign('list',$list);
        
        $this->assign('data',$data);
        
        
        return $this->fetch();
    }
    
    /**
     * 加工产品关联
     * @adminMenu(
     *     'name'   => '加工产品关联',
     *     'parent' => 'goods/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 4,
     *     'icon'   => '',
     *     'remark' => '加工产品关联',
     *     'param'  => ''
     * )
     */
    public function links4()
    {
        
        $admin=$this->admin;
        
        $m=$this->m;
        $data=$this->request->param();
        $admin=$this->admin;
        $where=[];
        //类型组合
        $where['gl.type']=['eq',4];
          
        $tmp=$this->search($where, $data,$admin,'gl.shop');
        $where=$tmp['where'];
        $data=$tmp['data'];
        //关联设备数
        $goods_links=[
            '-1'=>'关联设备',
            '0'=>'0个',
            '1'=>'1个',
            '2'=>'2个',
            '3'=>'3个以上',
        ];
        $this->assign('goods_links',$goods_links);
        if(!isset($data['goods_link']) || $data['goods_link']==-1){
            $data['goods_link']=-1;
        }else{
            switch($data['goods_link']){
                case 3:
                    $where['p.goods_link']=['egt',3];
                    break;
                default:
                    $where['p.goods_link']=['eq',$data['goods_link']];
                    break;
            }
        }
        
        //关联资料数
        $file_type=$this->file_type;
        $about_link_nums=[
            '-1'=>'数量',
            '0'=>'0个',
            '1'=>'1个',
            '2'=>'2个',
            '3'=>'3个以上',
        ];
        $this->assign('about_link_nums',$about_link_nums);
        if(empty($data['about_link']) || !isset($data['about_link_num']) || $data['about_link_num']==-1){
            $data['about_link']=0;
            $data['about_link_num']=-1;
            
        }else{
            $about=$file_type[$data['about_link']][0];
            switch($data['about_link_num']){
                case 3:
                    $where['p.'.$about]=['egt',3];
                    break;
                default:
                    $where['p.'.$about]=['eq',$data['about_link_num']];
                    break;
            }
        }
        //主产品还是副产品
        if(empty($data['is_link'])){
            $data['is_link']=0;
            $list=Db::name('goods_link')
            ->alias('gl')
            ->field('p.*,gl.pid1,gl.num as link_num,gl.dsc,p1.name as link_name,p1.code as link_code,b.name as bname,a.user_nickname as aname,r.user_nickname as rname')
            ->join('cmf_goods p','p.id=gl.pid0')
            ->join('cmf_goods p1','p1.id=gl.pid1','left')
            ->join('cmf_brand b','b.id=p.brand','left')
            ->join('cmf_user a','a.id=p.aid','left')
            ->join('cmf_user r','r.id=p.rid','left')
            ->where($where)
            ->order('p.status asc,p.time desc')
            ->paginate();
        }else{
            $list=Db::name('goods_link')
            ->alias('gl')
            ->field('p0.*,gl.pid1,gl.num as link_num,gl.dsc,p.name as link_name,p.code as link_code,b.name as bname,a.user_nickname as aname,r.user_nickname as rname')
            ->join('cmf_goods p0','p0.id=gl.pid0')
            ->join('cmf_goods p','p.id=gl.pid1','left')
            ->join('cmf_brand b','b.id=p.brand','left')
            ->join('cmf_user a','a.id=p.aid','left')
            ->join('cmf_user r','r.id=p.rid','left')
            ->where($where)
            ->order('p.status asc,p.time desc')
            ->paginate();
        }
        
        // 获取分页显示
        $page = $list->appends($data)->render();
        
        $this->assign('page',$page);
        $this->assign('list',$list);
        
        $this->assign('data',$data);
        
        
        return $this->fetch();
    }
    /**
     * 设备关联
     * @adminMenu(
     *     'name'   => '设备关联',
     *     'parent' => 'goods/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 5,
     *     'icon'   => '',
     *     'remark' => '设备关联',
     *     'param'  => ''
     * )
     */
    public function links5()
    {
        
        $admin=$this->admin;
        
        $m=$this->m;
        $data=$this->request->param();
        $admin=$this->admin;
        $where=[];
        $where['gl.type']=['eq',5];
        
        $tmp=$this->search($where, $data,$admin,'gl.shop');
        $where=$tmp['where'];
        $data=$tmp['data'];
        //关联设备数
        $goods_links=[
            '-1'=>'关联数量',
            '0'=>'0个',
            '1'=>'1个',
            '2'=>'2个',
            '3'=>'3个以上',
        ];
        $this->assign('goods_links',$goods_links);
        if(!isset($data['goods_link']) || $data['goods_link']==-1){
            $data['goods_link']=-1;
        }else{
            switch($data['goods_link']){
                case 3:
                    $where['p.goods_link']=['egt',3];
                    break;
                default:
                    $where['p.goods_link']=['eq',$data['goods_link']];
                    break;
            }
        }
        
        //关联资料数
        $file_type=$this->file_type;
        $about_link_nums=[
            '-1'=>'数量',
            '0'=>'0个',
            '1'=>'1个',
            '2'=>'2个',
            '3'=>'3个以上',
        ];
        $this->assign('about_link_nums',$about_link_nums);
        if(empty($data['about_link']) || !isset($data['about_link_num']) || $data['about_link_num']==-1){
            $data['about_link']=0;
            $data['about_link_num']=-1;
            
        }else{
            $about=$file_type[$data['about_link']][0];
            switch($data['about_link_num']){
                case 3:
                    $where['p.'.$about]=['egt',3];
                    break;
                default:
                    $where['p.'.$about]=['eq',$data['about_link_num']];
                    break;
            }
        }
        //主产品还是副产品
        if(empty($data['is_link'])){
            //类型组合
           
            
            $data['is_link']=0;
            $list=Db::name('goods_link')
            ->alias('gl')
            ->field('p.*,gl.pid1,gl.num as link_num,gl.dsc,p1.name as link_name,p1.code as link_code,b.name as bname,a.user_nickname as aname,r.user_nickname as rname')
            ->join('cmf_goods p','p.id=gl.pid0')
            ->join('cmf_goods p1','p1.id=gl.pid1','left')
            ->join('cmf_brand b','b.id=p.brand','left')
            ->join('cmf_user a','a.id=p.aid','left')
            ->join('cmf_user r','r.id=p.rid','left')
            ->where($where)
            ->order('p.status asc,p.time desc')
            ->paginate();
        }else{
           
            $list=Db::name('goods_link')
            ->alias('gl')
            ->field('p0.*,gl.pid1,gl.num as link_num,gl.dsc,p.name as link_name,p.code as link_code,b.name as bname,a.user_nickname as aname,r.user_nickname as rname')
            ->join('cmf_goods p0','p0.id=gl.pid0')
            ->join('cmf_goods p','p.id=gl.pid1','left')
            ->join('cmf_brand b','b.id=p.brand','left')
            ->join('cmf_user a','a.id=p.aid','left')
            ->join('cmf_user r','r.id=p.rid','left')
            ->where($where)
            ->order('p.status asc,p.time desc')
            ->paginate();
        }
        
        // 获取分页显示
        $page = $list->appends($data)->render();
        
        $this->assign('page',$page);
        $this->assign('list',$list);
        
        $this->assign('data',$data);
         
        return $this->fetch();
    }
    /**
     * 标签产品关联
     * @adminMenu(
     *     'name'   => '标签产品关联',
     *     'parent' => 'goods/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 3,
     *     'icon'   => '',
     *     'remark' => '标签产品关联',
     *     'param'  => ''
     * )
     */
    public function links3()
    {
        
        $admin=$this->admin;
        
        $m=$this->m;
        $data=$this->request->param();
        $admin=$this->admin;
        $where=[];
        //类型组合 
        $tmp=$this->search($where, $data,$admin,'gl.shop');
        $where=$tmp['where'];
        $data=$tmp['data'];
        //关联设备数
        $goods_links=[
            '-1'=>'标签数量',
            '0'=>'0个',
            '1'=>'1个',
            '2'=>'2个',
            '3'=>'3个以上',
        ];
        $this->assign('goods_links',$goods_links);
        if(!isset($data['goods_link']) || $data['goods_link']==-1){
            $data['goods_link']=-1;
        }else{
            switch($data['goods_link']){
                case 3:
                    $where['gl.num']=['egt',3];
                    break;
                default:
                    $where['gl.num']=['eq',$data['goods_link']];
                    break;
            }
        }
        //关联资料数
        $file_type=$this->file_type;
        $about_link_nums=[
            '-1'=>'数量',
            '0'=>'0个',
            '1'=>'1个',
            '2'=>'2个',
            '3'=>'3个以上',
        ];
        $this->assign('about_link_nums',$about_link_nums);
        if(empty($data['about_link']) || !isset($data['about_link_num']) || $data['about_link_num']==-1){
            $data['about_link']=0;
            $data['about_link_num']=-1; 
        }else{
            $about=$file_type[$data['about_link']][0];
            switch($data['about_link_num']){
                case -1:
                    break;
                case 3:
                    $where['p.'.$about]=['egt',3];
                    break;
                default:
                    $where['p.'.$about]=['eq',$data['about_link_num']];
                    break;
            }
        }
        
         
        //主产品还是副产品
        if(empty($data['is_link'])){
            $data['is_link']=0;
            $list=Db::name('goods_label')
            ->alias('gl')
            ->field('p.*,gl.pid1,gl.num as link_num,p1.name as link_name,p1.code as link_code,b.name as bname,a.user_nickname as aname,r.user_nickname as rname')
            ->join('cmf_goods p','p.id=gl.pid0')
            ->join('cmf_goods p1','p1.id=gl.pid1','left')
            ->join('cmf_brand b','b.id=p.brand','left')
            ->join('cmf_user a','a.id=p.aid','left')
            ->join('cmf_user r','r.id=p.rid','left')
            ->where($where)
            ->order('p.status asc,p.time desc')
            ->paginate();
        }else{
            $list=Db::name('goods_label')
            ->alias('gl')
            ->field('p0.*,gl.pid1,gl.num as link_num,p.name as link_name,p.code as link_code,b.name as bname,a.user_nickname as aname,r.user_nickname as rname')
            ->join('cmf_goods p0','p0.id=gl.pid0')
            ->join('cmf_goods p','p.id=gl.pid1','left')
            ->join('cmf_brand b','b.id=p.brand','left')
            ->join('cmf_user a','a.id=p.aid','left')
            ->join('cmf_user r','r.id=p.rid','left')
            ->where($where)
            ->order('p.status asc,p.time desc')
            ->paginate();
        }
        
        // 获取分页显示
        $page = $list->appends($data)->render();
        
        $this->assign('page',$page);
        $this->assign('list',$list);
        
        $this->assign('data',$data);
        
        return $this->fetch();
    }
    /**
     * 一货一码查询
     * @adminMenu(
     *     'name'   => '一货一码查询',
     *     'parent' => 'goods/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 6,
     *     'icon'   => '',
     *     'remark' => '一货一码查询',
     *     'param'  => ''
     * )
     */
    public function link_sn()
    {
        
        $admin=$this->admin;
        
        $m=$this->m;
        $data=$this->request->param();
        $admin=$this->admin;
        $where=[];
        
        //店铺
        $res=zz_shop($admin, $data, $where,'gs.shop');
        $data=$res['data'];
        $where=$res['where'];
        $this->where_shop=$res['where_shop'];
        //查询字段
        $types=[
            1=>['p.name','全名'], 
            3=>['p.code_name' , '同级名称'],
            4=>['p.name2' , '商城名称'],
            5=>['p.name3' , '打印名称'],
            11=>['gs.sn','条码'],
            6=>['p.id' , '产品id'],
            7=>['p.aid' , '创建人id'], 
            9=>['p.rid' , '审核人id'], 
        ];
        
        //选择查询字段
        if(empty($data['type1'])){
            $data['type1']=1;
        }
        //搜索类型
        $search_types=config('search_types');
        if(empty($data['type2'])){
            $data['type2']=key($search_types);
        }
        if(!isset($data['name']) || $data['name']==''){
            $data['name']='';
        }else{
            $where[$types[$data['type1']][0]]=zz_search($data['type2'],$data['name']);
        }
        $this->assign('types',$types);
        $this->assign("search_types", $search_types);
        //
        $fields='gs.*,p.name as goods_name,p.code as goods_code,p.time as goods_time'.
                ',si.type,si.about,si.about_name,si.atime,si.rtime';
        $list=Db::name('goods_sn')
        ->alias('gs')
        ->join('cmf_goods p','p.id=gs.goods')
        ->join('cmf_store_in si','si.id=gs.store_in') 
        ->field($fields)
        ->where($where)
        ->order('gs.goods asc,gs.store_in desc')
        ->paginate();
        // 获取分页显示
        $page = $list->appends($data)->render();
         
        $this->cates(1);
        $this->assign('page',$page);
        $this->assign('list',$list);
        
        $this->assign('data',$data);
        $this->assign('store_in_types',config('store_in_type'));
        $this->assign('goods_url',url('goods/AdminGoods/edit',false,false));
        $this->assign('image_url',cmf_get_image_url(''));
        $this->assign('order_url',url('ordersup/AdminOrdersup/edit',false,false));
        $this->assign('store_in_url',url('store/AdminStorein/edit',false,false));
       
        return $this->fetch();
    }
    /**
     * 产品技术资料列表
     * @adminMenu(
     *     'name'   => '产品技术资料列表',
     *     'parent' => 'goods/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 5,
     *     'icon'   => '',
     *     'remark' => '产品技术资料列表',
     *     'param'  => ''
     * )
     */
    public function params()
    {
        
        $admin=$this->admin;
        
        $m=$this->m;
        $data=$this->request->param();
        $admin=$this->admin;
        $where=[];
        //类型组合
        
        if($admin['shop']!=1){
            $where['p.shop']=['eq',$admin['shop']];
        }
        
        //状态
        if(empty($data['status'])){
            $data['status']=0;
        }else{
            $where['p.status']=['eq',$data['status']];
        }
        //分类
        $m_cate=Db::name('cate');
        $where_cate=[
            'fid'=>0,
            'status'=>2,
        ];
        $cates0=$m_cate->where($where_cate)->order('sort asc,code_num asc')->column('id,name,code');
        //一级分类
        if(empty($data['cid0'])){
            $data['cid0']=key($cates0);
        }
        $where['p.cid0']=['eq',$data['cid0']];
        //二级分类
        if(empty($data['cid'])){
            $data['cid']=0;
        }else{
            $where['p.cid']=['eq',$data['cid']];
        }
        $where_cate=[
            'fid'=>['eq',$data['cid0']],
            'status'=>['eq',2],
        ];
        $cates=$m_cate->where($where_cate)->order('sort asc,code_num asc')->column('id,name,fid,code');
        $this->assign('cates0',$cates0);
        $this->assign('cates',$cates);
         
        //参数模板
        $where_template=[
            'cid'=>['eq',$data['cid0']],
            'status'=>['eq',2],
        ];
        $templates=Db::name('template')->where($where_template)->column('id,name,cid');
        $this->assign('templates',$templates);
        if(empty($data['template'])){
            $data['template']=key($templates);
        }
        $where['p.template']=['eq',$data['template']];
        $params=Db::name('template_param')
        ->alias('tp')
        ->join('cmf_param p','p.id=tp.p_id')
        ->where('tp.t_id',$data['template'])
        ->column('p.id,p.name,p.content,p.type');
        //获取参数 
        $param=empty($data['param'])?[]:$data['param'];
        $where_value=[];
        $where_values=[];
        foreach($params as $k=>$v){
            switch ($v['type']){
                case 1:
                    //处理参数项
                    if(empty($v['content'])){
                        $params[$k]['content']=[];
                    }else{
                        $params[$k]['content']=explode(',',$v['content']);
                    } 
                    //所选参数值 
                    if(isset($param[$k])){
                        
                        $where_value[$k]=['eq',$param[$k]];
                    } else{
                        $param[$k]='';
                    }
                    break;
                case 2:
                    if(empty($v['content'])){
                        $params[$k]['content']=[];
                    }else{
                        $params[$k]['content']=explode(',',$v['content']);
                    }
                    
                    //所选参数值
                    if(empty($data['params'.$k])){
                        $param[$k]=[]; 
                    } else{
                        $param[$k]=$data['params'.$k];
                        //多选是多个
                        $where_values[$k]=[];
                        foreach($param[$k] as $k1=>$v1){
                            $where_values[$k][]=['like','%'.$v1.'%'];
                        }
                        
                    }
                    break;
                case 3: 
                   
                    if(empty($param[$k])){
                        $param[$k]=''; 
                    } else{
                        $where_value[$k]=['eq',$param[$k]];
                    }
                    break;
            }
            
        }
        $this->assign('params',$params);
       
          
        
         
        //查询字段
        $types=config('goods_search');
        //选择查询字段
        if(empty($data['type1'])){
            $data['type1']=key($types);
        }
        //搜索类型
        $search_types=config('search_types');
        if(empty($data['type2'])){
            $data['type2']=key($search_types);
        }
        if(!isset($data['name']) || $data['name']==''){
            $data['name']='';
        }else{
            $where['p.'.$data['type1']]=zz_search($data['type2'],$data['name']);
        }
         //要根据参数值得到pid
         $m_gp=Db::name('goods_param');
       //先得到ids
         $ids=$m
         ->alias('p') 
         ->where($where)
         ->column('id');
         if(empty($ids)){
             $ids=[0];
         }
         //单选,数值
         foreach($where_value as $k=>$v){
             if(empty($ids)){
                 $ids=[0]; 
                 break;
             }
             $where_tmp=[
                 'param_id'=>['eq',$k],
                 'value'=>$v,
                 'pid'=>['in',$ids],
             ]; 
             $ids=$m_gp->where($where_tmp)->column('pid');
           
         }
         //多选
         foreach($where_values as $k=>$v){
             
             if(empty($ids)){
                 $ids=[0];
                 break;
             }
             foreach($v as $kk=>$vv){
                 if(empty($ids)){
                     $ids=[0];
                     break;
                 }
                 $where_tmp=[
                     'param_id'=>['eq',$k],
                     'value'=>$vv,
                     'pid'=>['in',$ids],
                 ];
                 $ids=$m_gp->where($where_tmp)->column('pid');
                 
             } 
         }
        
         if(empty($ids)){
             $ids=[0]; 
         }
         $where=['p.id'=>['in',$ids]];
         $list=$m
         ->alias('p')
         ->field('p.*,s.name as sname')
         ->join('cmf_shop s','s.id=p.shop','left')
         ->where($where) 
         ->order('p.status asc,p.time desc')
         ->paginate();
         
         // 获取分页显示
         $page = $list->appends($data)->render();
         //循环读取和一次读取，差别?
         $tech=[];
         foreach($list as $k=>$v){
             $tech[$k]=$m_gp->where('pid',$v['id'])->column('param_id,value');
             
         }
          
        $this->assign('page',$page);
        $this->assign('list',$list);
        $this->assign('tech',$tech);
        $this->assign('data',$data);
        $this->assign('types',$types);
        
        $this->assign("search_types", $search_types);
       
        
         $this->assign('param',$param);
        
        return $this->fetch();
    }
    /**
     * 产品添加
     * @adminMenu(
     *     'name'   => '产品添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        //分类
       $this->cates();
       $this->assign('cid0',0);
       $this->assign('cid',0);
       
       $this->assign('bchar',-1);
       $this->assign('brand',-1); 
       //权限检测
        $admin=$this->admin;
        $actions=$this->auth_get($admin['id']); 
        $this->assign('actions',$actions); 
        
        //价格模板
        if(isset($actions['auth']) || isset($actions['price_set'])){
            $this->prices();
        }else{
            $this->assign('prices',[]);
        }
         
        $this->assign('info',null);
        $this->assign('params0',null);
        $this->assign('params1',null);
        $this->assign('templates',null);
        $this->assign('id_links',null);
        
       return $this->fetch();
    }
     
    
    
     
    /**
     * 产品添加do
     * @adminMenu(
     *     'name'   => '产品添加do',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品添加do',
     *     'param'  => ''
     * )
     */
    public function add_do()
    {
        
        $m=$this->m;
        $data=$this->request->param('');
       
        $time=time();
        $admin=$this->admin;
         //检查不合法参数
        $data_add=$this->param_check($data);
        
        if(!is_array($data_add)){
            $this->error($data_add);
        }
        //技术参数
        $value=$data_add['value'];
        unset($data_add['value']); 
        
        //创建人信息
        $data_add['aid']=$admin['id'];
        $data_add['status']=1;
        $data_add['atime']=$time;
        $data_add['time']=$time;
        //产品店铺,超管添加自营产品
        $data_add['shop']=($admin['shop']==1)?2:$admin['shop'];
       
        $m->startTrans();
        try {
            $id=$m->insertGetId($data_add);
        } catch (\Exception $e) {
            $m->rollback();
            $this->error($e->getMessage());
        }
        if($id<=0){
            $m->rollback();
            $this->error('添加失败，请刷新重试');
        }
       
        //最大编码更新
        $m_cate=Db::name('cate');
        $max=$m_cate->where('id',$data_add['cid'])->value('max_num');
        if($data_add['code_num']>$max){
            $m_cate->where('id',$data_add['cid'])->update(['max_num'=>$data_add['code_num']]);
        }
        //技术参数记录
        if(!empty($value)){
            $params=[];
            foreach($value as $k=>$v){
                if(is_array($v)){
                    $v=implode(',', $v);
                }
                $params[]=[
                    'pid'=>$id,
                    'param_id'=>$k,
                    'value'=>$v,
                ];
            }
            Db::name('goods_param')->insertAll($params);
        }
        
        //记录操作记录
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'添加'.($this->flag).$id.'-'.$data['name'],
            'table'=>($this->table),
            'type'=>'add',
            'pid'=>$id,
            'link'=>url('edit',['id'=>$id]),
            'shop'=>$admin['shop'],
            
        ];
        zz_action($data_action,['department'=>$admin['department']]);
        $m->commit();
        //添加收藏关联
        $this->goods_collect($id,$admin['id'],1);
        //直接审核5
        $rule='review';
        $res=$this->check_review($admin,$rule);
        if($res){
            $this->redirect($rule,['id'=>$id,'status'=>2]);
        }
        $this->success('添加成功',url('index'));
    }
     
    /**
     * 产品详情
     * @adminMenu(
     *     'name'   => '产品详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品详情',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        
        $m=$this->m;
        $id=$this->request->param('id',0,'intval');
        $info=$m->where('id',$id)->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        //获取分类
        $this->cates();
        $this->assign('cid0',$info['cid0']);
        $this->assign('cid',$info['cid']);
       
        $this->assign('bchar',$info['bchar']);
        $this->assign('brand',$info['brand']);
        //权限检测
        $admin=$this->admin;
        $actions=$this->auth_get($admin['id']);
        $this->assign('actions',$actions);
        //关联产品
        if($info['type']!=1 && (isset($actions['auth']) || isset($actions['link_set']))){ 
            $id_links=Db::name('goods_link')
            ->alias('gl')
            ->join('cmf_goods p','p.id=gl.pid1')
            ->where('gl.pid0',$id)
            ->column('gl.pid1,gl.num,p.name'); 
        }else{
            $id_links=[];
        }
        $this->assign('id_links',$id_links);
        //价格模板
        if(isset($actions['auth']) || isset($actions['price_set'])){
            $this->prices();
        }else{
            $this->assign('prices',[]);
        }
        //参数
        $templates=null;
        $params1=null;
        $params0=null;
        if(isset($actions['auth']) || isset($actions['param_set']) || isset($actions['param_get'])){
            //能选择模板
            if(isset($actions['auth']) || isset($actions['param_set'])){
                $templates=Db::name('template')->where('cid',$info['cid0'])->column('id,name');
            }
            //获取模板所有参数
            if($info['template']>0){  
                $where=[
                    'tp.t_id'=>$info['template'],
                    'p.status'=>2,
                ];
                $params0=Db::name('template_param')
                ->alias('tp')
                ->join('cmf_param p','tp.p_id=p.id')
                ->where($where)
                ->order('p.sort asc')
                ->column('p.id,p.name,p.type,p.content,p.dsc');
                
                if(!empty($params0)){ 
                    //获取设置的参数
                    $params1=Db::name('goods_param')
                    ->where('pid',$info['id'])
                    ->column('param_id,value');
                     
                    //处理技术参数,没有获取参数值的要设置默认
                    foreach($params0 as $k=>$v){
                        switch ($params0[$k]['type']){
                            case 3:
                                $params0[$k]['content']='';
                                //没有设置的参数值要给空值
                                if(!isset($params1[$k])){
                                    $params1[$k]='';
                                }
                                break;
                            case 1:
                                 //单选框
                                $params0[$k]['content']=explode(',',$params0[$k]['content']);
                                //没有设置的参数值要给空值
                                if(!isset($params1[$k])){
                                    $params1[$k]='';
                                }
                                break;
                            case 2:
                                //多选框
                                $params0[$k]['content']=explode(',',$params0[$k]['content']);
                                //没有设置的参数值要给空值
                                if(isset($params1[$k])){
                                    $params1[$k]=explode(',',$params1[$k]);
                                }else{
                                    $params1[$k]=[];
                                }
                                break;
                            default:
                                $this->error('参数类型不存在');
                                break;
                        }
                        
                    }
                   
                } 
            }
           
        } 
       
        $this->assign('info',$info);
        $this->assign('templates',$templates);
        $this->assign('params0',$params0);
        $this->assign('params1',$params1);
        
        return $this->fetch();
    }
    
    /**
     * 产品状态审核
     * @adminMenu(
     *     'name'   => '产品状态审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品状态审核',
     *     'param'  => ''
     * )
     */
    public function review()
    {
        //复制adminpro的审核
        $status=$this->request->param('status',0,'intval');
        $id=$this->request->param('id',0,'intval');
        if($status<1 || $status>4 || $id<=0){
            $this->error('信息错误');
        }
        $m=$this->m;
        //查找信息
        $info=$m->where('id',$id)->find();
        if(empty($info)){
            $this->error('信息不存在');
        }
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1){
            if(empty($info['shop']) || $info['shop']!=$admin['shop']){
                $this->error('不能审核其他店铺的信息');
            }
        }
        $time=time();
        $update=[
            'rid'=>$admin['id'],
            'rtime'=>$time,
            'status'=>$status,
            'time'=>$time,
        ];
        
        $row=$m->where('id',$id)->update($update);
        
        if($row!==1){
            $m->rollback();
            $this->error('审核失败，请刷新后重试');
        }
        
        //审核成功，记录操作记录,发送审核信息
        $statuss=$this->statuss;
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'审核'.($this->flag).$info['id'].'-'.$info['name'].'的状态为'.$statuss[$status],
            'table'=>($this->table),
            'type'=>'review',
            'pid'=>$info['id'],
            'link'=>url('edit',['id'=>$info['id']]),
            'shop'=>$admin['shop'],
        ];
        zz_action($data_action,['aid'=>$info['aid']]);
        
        $m->commit();
        $this->success('审核成功');
    }
    /**
     * 产品文件和图片
     * @adminMenu(
     *     'name'   => ' 产品文件和图片',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => ' 产品文件和图片',
     *     'param'  => ''
     * )
     */
    public function image()
    {
        
        $m=$this->m;
        $id=$this->request->param('id',0,'intval');
        $info=$m->where('id',$id)->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        //权限检测
        $admin=$this->admin;
        $actions=$this->auth_get($admin['id']);
        $this->assign('actions',$actions);
        //根据权限得到文件
        $where=[
            'pid'=>['eq',$id],
        ];
        //文件权限
        if(!isset($actions['auth'])){ 
            $file_type=$this->file_type;
            $file_type1=$file_type;
            //1极敏商城图不需要权限
            unset($file_type1[1]);
            $types=[1];
            foreach($file_type1 as $k=>$v){
                $tmp1=$v[0].'_set';
                $tmp2=$v[0].'_get';
                if(isset($actions[$tmp1]) || isset($actions[$tmp2])){
                    $types[]=$k;
                }else{
                    unset($file_type[$k]);
                }
            }
            //只显示有权限的
            $where['type']=['in',$types]; 
            $this->assign('file_type',$file_type);
        }
        
        $tmp=Db::name('goods_file')->where($where)->column('id,name,file,type');
        $list=[];
        $path='upload/';
        foreach($tmp as $k=>$v){

            if(is_file($path.$v['file'])){
                //直接加判断，防止错误
                //if(in_array($v['type'],[1,2,3])){
                if(is_file($path.$v['file'].'1.jpg')){
                    $v['file1']=$v['file'].'1.jpg'; 
                }else{
                    $v['file1']=$v['file']; 
                }
                if(is_file($path.$v['file'].'3.jpg')){ 
                    $v['file3']=$v['file'].'3.jpg';
                }else{ 
                    $v['file3']=$v['file'];
                }
            }else{
                $v['name'].='文件损坏';
                $v['file1']=$v['file'];
                $v['file2']=$v['file'];
                $v['file3']=$v['file'];
            }
            
           
            $list[$v['type']][$k]=$v;
        }
        $this->assign('info',$info);
        $this->assign('list',$list);
        
        return $this->fetch();
    }
    /**
     * 产品图片文件编辑提交
     * @adminMenu(
     *     'name'   => '产品图片文件编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品图片文件编辑提交',
     *     'param'  => ''
     * )
     */
    public function image_edit_do()
    {
        $m=$this->m;
        $table=$this->table;
        $flag=$this->flag;
        $data=$this->request->param();
        $id=intval($data['id']);
        $info=$m->where('id',$id)->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        
        $time=time();
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1){
            if(empty($info['shop']) || $info['shop']!=$admin['shop']){
                $this->error('不能编辑其他店铺的信息');
            }
        }
        $table0='image';
        $flag='产品图片';
        $update=[
            'pid'=>$info['id'],
            'aid'=>$admin['id'],
            'atime'=>$time,
            'table'=>'goods',
            'url'=>url($table0.'_edit_info','',false,false),
            'rstatus'=>1,
            'rid'=>0,
            'rtime'=>0,
            'shop'=>$admin['shop'],
        ];
        $update['adsc']=(empty($data['adsc']))?'修改了'.$flag:$data['adsc'];
        $content=[];
        
        //检查文件修改
        $tmp=Db::name('goods_file')->where('pid',$id)->column('id,name,file,type');
        $list=[];
        $path='upload/';
        $pathid='seller'.$info['shop'].'/goods'.$id.'/';
        //没有目录创建目录
        if(!is_dir($path.$pathid)){
            mkdir($path.$pathid);
        }
        //权限检测 
        $actions=$this->auth_get($admin['id']);
        foreach($tmp as $k=>$v){ 
            $list[$v['type']][]=$v['name'].','.$v['file'];
            
        }
        $file_type=$this->file_type;
        //文件权限
        if(!isset($actions['auth'])){
            
            $file_type1=$file_type;
            //1极敏商城图不需要权限
            unset($file_type1[1]);
            //循环检测哪些可以设置
            foreach($file_type1 as $k=>$v){
                $tmp1=$v[0].'_set'; 
                //没权限就删除
                if(!isset($actions[$tmp1])){
                    unset($file_type[$k]);
                }
            } 
        }
        //图片尺寸
        $pic_size=config('pic_size');
        $files=[];
        //循环得到上传后的数据
        foreach($file_type as $k=>$v){
            $urls=($v[0]).'_urls';
            $names=($v[0]).'_names';
            //没文件的为空
            $files[$k]=[];
            if(!empty($data[$urls])){ 
                foreach($data[$urls] as $kk=>$vv){
                    //名称中不能有逗号
                    if(empty($data[$names][$kk])){
                        $data[$names][$kk]=$v[1].$kk;
                    }else{
                        //可以改为正则检测
                        if(strpos($data[$names][$kk], ',')!==false){
                            $this->error('文件名称中不能有逗号');
                        }
                    }
                    $files[$k][]=$data[$names][$kk].','.$data[$urls][$kk]; 
                } 
            }
            //没文件的为空
            if(!isset($list[$k])){
                $list[$k]=[];
            }
            //比较变化
            if(!empty(array_diff($files[$k],$list[$k])) ||  !empty(array_diff($list[$k],$files[$k]))){
                //有变化就保存图片，然后保存json
                foreach($files[$k] as $kk=>$vv){
                    //先拆分才能比较,先得到,的位置
                    $tmp_file=explode(',', $vv);
                    if (!is_file($path.$tmp_file[1]))
                    {
                        $this->error($tmp_file[0].'文件损坏，请注意');
                    }
                    //先比较是否需要额外保存,非指定位置的要复制粘贴
                    if(strpos($tmp_file[1], $pathid)!==0){
                          //获取后缀名,复制文件
                        $ext=substr($tmp_file[1], strrpos($tmp_file[1],'.'));
                        $new_file=$pathid.($v[0]).$kk.date('Ymd-His').$ext;
                        $result =copy($path.$tmp_file[1], $path.$new_file);
                        if ($result == false)
                        {
                            $this->error($tmp_file[0].'文件复制错误，请重试');
                        }else{
                            $files[$k][$kk]=$tmp_file[0].','.$new_file;
                            //删除原图片
                            unlink($path.$tmp_file[1]);
                            
                        } 
                        $tmp_file[1]=$new_file;
                        
                    } 
                    //生成不同图片
                    //判断是否需要编制图片
                    if($k<4){
                        $tmp_file=['file'=>$tmp_file[1]];
                        $tmp_file['file1']= $tmp_file['file'].'1.jpg';
                        $tmp_file['file2']= $tmp_file['file'].'2.jpg';
                        $tmp_file['file3']= $tmp_file['file'].'3.jpg';
                        if(!is_file($path. $tmp_file['file1']) ){
                            zz_set_image($tmp_file['file'], $tmp_file['file1'], $pic_size[1][0], $pic_size[1][1]);
                        }
                        if(!is_file($path. $tmp_file['file2'])){
                            zz_set_image($tmp_file['file'], $tmp_file['file2'], $pic_size[2][0], $pic_size[2][1]);
                        }
                        if(!is_file($path. $tmp_file['file3'])){
                            zz_set_image($tmp_file['file'], $tmp_file['file3'], $pic_size[3][0], $pic_size[3][1]);
                        }
                    } elseif($k<7){
                        $tmp_file=['file'=>$tmp_file[1]];
                        $tmp_file['file1']= $tmp_file['file'].'1.jpg';
                       
                        if(!is_file($path. $tmp_file['file1']) ){
                            zz_set_image($tmp_file['file'], $tmp_file['file1'], $pic_size[1][0], $pic_size[1][1]);
                        }
                        
                    }
                    
                }
                $content[$k]=json_encode($files[$k]);
                
            }
        }
        
        if(empty($content)){
            $this->error('未修改');
        }
        //保存更改
        $m_edit=Db::name('edit');
        $m_edit->startTrans();
        $eid=$m_edit->insertGetId($update);
        if($eid>0){
            $data_content=[
                'eid'=>$eid,
                'content'=>json_encode($content),
            ];
            Db::name('edit_info')->insert($data_content);
        }else{
            $m_edit->rollback();
            $this->error('保存数据错误，请重试');
        }
        //记录操作记录
        
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'编辑了'.$flag.$info['id'].'-'.$info['name'],
            'table'=>($this->table),
            'type'=>'edit',
            'pid'=>$info['id'], 
            'link'=>url($table0.'_edit_info',['id'=>$eid]),
            'shop'=>$admin['shop'],
        ]; 
        zz_action($data_action,['department'=>$admin['department']]);
        $m_edit->commit();
        //添加收藏关联
        $this->goods_collect($info['id'],$admin['id'],2);
        //直接审核
        $rule='image_edit_review';
        $res=$this->check_review($admin,$rule);
        if($res){
            $this->redirect($rule,['id'=>$eid,'rstatus'=>2,'rdsc'=>'直接审核']);
        }
        $this->success('已提交修改');
    }
    /**
     * 产品图片文件编辑详情
     * @adminMenu(
     *     'name'   => '产品图片文件编辑详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品图片文件编辑详情',
     *     'param'  => ''
     * )
     */
    public function image_edit_info()
    {
        $m=$this->m;
        $eid=$this->request->param('id',0,'intval');
        $table=$this->table;
        //获取编辑信息
        $info1=Db::name('edit')
        ->field('e.*,p.name as pname,p.status as pstatus,p.type as ptype')
        ->alias('e')
        ->join('cmf_goods p','p.id=e.pid')
        ->where('e.id',$eid)
        ->find();
        if(empty($info1)){
            $this->error('编辑信息不存在');
        }
        
        $where_file=['pid'=>['eq',$info1['pid']]];
        //权限检测
        $admin=$this->admin;
        $actions=$this->auth_get($admin['id']);
        $this->assign('actions',$actions);
        //文件权限
        if(!isset($actions['auth'])){
            $file_type=$this->file_type;
            $file_type1=$file_type;
            //1极敏商城图不需要权限
            unset($file_type1[1]);
            $types=[1];
            foreach($file_type1 as $k=>$v){
                $tmp1=$v[0].'_set';
                $tmp2=$v[0].'_get';
                if(isset($actions[$tmp1]) || isset($actions[$tmp2])){
                    $types[]=$k;
                }else{
                    unset($file_type[$k]);
                }
            }
            //只显示有权限的
            $where_file['type']=['in',$types];
            $this->assign('file_type',$file_type);
             
        }
         
        //获取改变的信息
        $change=Db::name('edit_info')->where('eid',$eid)->value('content');
        $change=json_decode($change,true);
        $path='upload/';
        foreach($change as $k=>$v){
            //没权限的不展示
            if(!isset($actions['auth']) && !in_array($k,$types)){
                continue;
            }
            $v=json_decode($v,true);
            foreach($v as $kk=>$vv){
                $tmp_file=explode(',', $vv);
                $tmp_file=[
                    'name'=>$tmp_file[0],
                    'file'=>$tmp_file[1],
                ];
                if(!is_file($path.$tmp_file['file'])){
                    $tmp_file['name'].='文件已损坏';
                }
                //直接加判断，防止错误
                //判断是否需要编制图片
                if($k<4){
                    if(is_file($path. $tmp_file['file'].'1.jpg')){
                        $tmp_file['file1']= $tmp_file['file'].'1.jpg';
                        $tmp_file['file2']= $tmp_file['file'].'2.jpg';
                        $tmp_file['file3']= $tmp_file['file'].'3.jpg';
                    }else{
                        $tmp_file['file1']= $tmp_file['file'];
                        $tmp_file['file2']= $tmp_file['file'];
                        $tmp_file['file3']= $tmp_file['file'];
                    }
                }elseif($k<7){
                    $tmp_file['file1']= $tmp_file['file'].'1.jpg';
                    $tmp_file['file3']= $tmp_file['file'];
                }
                
                //要保留字符串
                $tmp_file['change0']=urlencode($vv);
                $v[$kk]=$tmp_file;
            }
            $change[$k]=$v;
            
           
        }
       
        //
        $tmp=Db::name('goods_file')->where($where_file)->column('id,name,file,type');
        $list=[];
       
        foreach($tmp as $k=>$tmp_file){
            if(!is_file($path.$tmp_file['file'])){
                $tmp_file['name'].='文件已损坏';
            }
            //判断是否需要编制图片
            if($tmp_file['type']<4){
                if(is_file($path. $tmp_file['file'].'1.jpg')){
                    $tmp_file['file1']= $tmp_file['file'].'1.jpg';
                    $tmp_file['file2']= $tmp_file['file'].'2.jpg';
                    $tmp_file['file3']= $tmp_file['file'].'3.jpg';
                }else{
                    $tmp_file['file1']= $tmp_file['file'];
                    $tmp_file['file2']= $tmp_file['file'];
                    $tmp_file['file3']= $tmp_file['file'];
                }
            }elseif($tmp_file['type']<7){
                $tmp_file['file1']= $tmp_file['file'].'1.jpg';
                $tmp_file['file3']= $tmp_file['file'];
            }
            
            $list[$tmp_file['type']][$k]=$tmp_file;
            
        }
        $this->assign('info1',$info1);
       
        $this->assign('list',$list);
        $this->assign('change',$change);
      
        return $this->fetch();
        
    }
    /**
     * 产品文件编辑审核
     * @adminMenu(
     *     'name'   => '产品文件编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品文件编辑审核',
     *     'param'  => ''
     * )
     */
    public function image_edit_review()
    {
        //审核编辑的信息
        $status=$this->request->param('rstatus',0,'intval');
        $id=$this->request->param('id',0,'intval');
        if(($status!=2 && $status!=3) || $id<=0){
            $this->error('信息错误');
        }
        $m=$this->m;
        $table=$this->table;
        $m_edit=Db::name('edit');
        $info=$m_edit
        ->field('e.*,p.name as pname,a.user_nickname as aname')
        ->alias('e')
        ->join('cmf_'.$table.' p','p.id=e.pid')
        ->join('cmf_user a','a.id=e.aid')
        ->where('e.id',$id)
        ->find();
        if(empty($info)){
            $this->error('无效信息');
        }
        if($info['rstatus']!=1){
            $this->error('编辑信息已被审核！不能重复审核');
        }
        
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1){
            if(empty($info['shop']) || $info['shop']!=$admin['shop']){
                $this->error('不能审核其他店铺的信息');
            }
        }
        $time=time();
        
        $m->startTrans();
        
        $update=[
            'rid'=>$admin['id'],
            'rtime'=>$time,
            'rstatus'=>$status,
        ];
        $review_status=$this->review_status;
        $rdsc=$this->request->param('rdsc');
        $update['rdsc']=(empty($rdsc))?$review_status[$status]:$rdsc;
        //只有未审核的才能更新
        $where=[
            'id'=>$id,
            'rstatus'=>1,
        ];
        $row=$m_edit->where($where)->update($update);
        if($row!==1){
            $m->rollback();
            $this->error('审核失败，请刷新后重试');
        }
        //是否更新,2同意，3不同意
        if($status==2){
            //组装更新数据
            $update_info=[
                'time'=>$time,
            ];
            $file_type=$this->file_type;
            //得到修改的字段
            $change=Db::name('edit_info')->where('eid',$id)->value('content');
            $change=json_decode($change,true);
            $where_delete=[
                'pid'=>['eq',$info['pid']],
            ];
            $data_add=[];
            $types=[];
            
            $path='upload/';
            $pic_size=config('pic_size');
            foreach($change as $k=>$v){
                //有变动要删除
                $types[]=$k;
                //分析数据
                $v=json_decode($v,true);
                foreach($v as $kk=>$vv){
                    $tmp_file=explode(',', $vv);
                    $tmp_file=[
                        'name'=>$tmp_file[0],
                        'file'=>$tmp_file[1],
                    ];
                    if(!is_file($path.$tmp_file['file'])){
                        $this->error($tmp_file['name'].'文件已损坏，不能更新');
                    }
                    $data_add[]=[
                        'pid'=>$info['pid'],
                        'type'=>$k,
                        'name'=>$tmp_file['name'],
                        'file'=>$tmp_file['file'],
                    ];
                    /* 
                     * 提前制作好了//判断是否需要编制图片
                    if($k<4){
                        $tmp_file['file1']= $tmp_file['file'].'1.jpg';
                        $tmp_file['file2']= $tmp_file['file'].'2.jpg';
                        $tmp_file['file3']= $tmp_file['file'].'3.jpg';
                        if(!is_file($path. $tmp_file['file1']) || !is_file($path. $tmp_file['file2']) || !is_file($path. $tmp_file['file3'])){
                            zz_set_image($tmp_file['file'], $tmp_file['file1'], $pic_size[1][0], $pic_size[1][1]);
                            zz_set_image($tmp_file['file'], $tmp_file['file2'], $pic_size[2][0], $pic_size[2][1]);
                            zz_set_image($tmp_file['file'], $tmp_file['file3'], $pic_size[3][0], $pic_size[3][1]);
                        }
                    }  */
                    
                }
                
               //统计图片文件数量 
                $update_info[$file_type[$k][0]]=count($v);
            }
            //商城图片变化保存封面
            if(!empty($change[1])){
                //分析数据
                $v=json_decode($change[1],true);
               //有数据才改变
                if(!empty($v[0])){
                    $tmp_file=explode(',', $v[0]);
                    $update_info['pic']=$tmp_file[1].'1.jpg';
                }
            }
            $row=$m->where('id',$info['pid'])->update($update_info);
            if($row!==1){
                $m->rollback();
                $this->error('信息更新失败，请刷新后重试');
            }
            $where_delete['type']=['in',$types];
            Db::name('goods_file')->where($where_delete)->delete();
            if(!empty($data_add)){
                Db::name('goods_file')->insertAll($data_add);
            } 
        }
        
        //审核成功，记录操作记录,发送审核信息
        $flag=$this->flag;
       
        //记录操作记录
        $table0='image';
        $flag='产品图片/文件';
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'审核'.$info['aid'].'-'.$info['aname'].'对'.($flag).$info['pid'].'-'.$info['pname'].'编辑为'.$review_status[$status],
            'table'=>$table,
            'type'=>'edit_review',
            'pid'=>$info['pid'],
            'link'=>url($table0.'_edit_info',['id'=>$info['id']]),
            'shop'=>$admin['shop'],
        ];
        zz_action($data_action,['aid'=>$info['aid']]); 
        $m->commit();
        //添加收藏关联
        $this->goods_collect($info['pid'],$admin['id'],3);
        $this->success('审核成功');
    }
    /**
     * 产品技术资料详情
     * @adminMenu(
     *     'name'   => '产品技术资料详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => ' 产品技术资料详情',
     *     'param'  => ''
     * )
     */
    public function content()
    {
        
        $m=$this->m;
        $id=$this->request->param('id',0,'intval');
        $info=$m->where('id',$id)->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
      
        $path='upload/';
        $pathid='seller'.$info['shop'].'/goods'.$id.'/';
        //没有目录创建目录
        if(!is_dir($path.$pathid)){
            mkdir($path.$pathid);
        }
        session('file_path',$pathid);
        $content=Db::name('goods_tech')->where('pid',$id)->value('content');
       
        $this->assign('info',$info);
        $this->assign('content',$content);
        return $this->fetch();
    }
    /**
     * 产品技术资料编辑提交
     * @adminMenu(
     *     'name'   => '产品技术资料编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品技术资料编辑提交',
     *     'param'  => ''
     * )
     */
    public function content_edit_do()
    {
        $m=$this->m;
        $table=$this->table;
        $flag=$this->flag;
        $data=$this->request->param();
        $id=intval($data['id']);
        $info=$m->where('id',$id)->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        
        $time=time();
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1){
            if(empty($info['shop']) || $info['shop']!=$admin['shop']){
                $this->error('不能编辑其他店铺的信息');
            }
        }
        
        $table0='content';
        $flag='产品技术资料';
        $update=[
            'pid'=>$info['id'],
            'aid'=>$admin['id'],
            'atime'=>$time,
            'table'=>'goods',
            'url'=>url($table0.'_edit_info','',false,false),
            'rstatus'=>1,
            'rid'=>0,
            'rtime'=>0,
            'shop'=>$admin['shop'],
        ];
        $update['adsc']=(empty($data['adsc']))?'修改了'.$flag:$data['adsc'];
        
        $content=[];
        //检查文件修改
        $tmp=Db::name('goods_tech')->where('pid',$id)->column('content');
        $content=trim($_POST['content']);
        if($tmp==$content){
            $this->error('未修改');
        }
        //保存更改
        $m_edit=Db::name('edit');
        $m_edit->startTrans();
        $eid=$m_edit->insertGetId($update);
        if($eid>0){
            $data_content=[
                'eid'=>$eid,
                'content'=>$content,
            ];
            Db::name('edit_info')->insert($data_content);
        }else{
            $m_edit->rollback();
            $this->error('保存数据错误，请重试');
        }
        
        //记录操作记录
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'编辑了'.$flag.$info['id'].'-'.$info['name'],
            'table'=>($this->table),
            'type'=>'edit',
            'pid'=>$info['id'],
            'link'=>url($table0.'_edit_info',['id'=>$eid]),
            'shop'=>$admin['shop'],
        ];
        zz_action($data_action,['department'=>$admin['department']]);
        
        $m_edit->commit();
        //添加收藏关联
        $this->goods_collect($info['id'],$admin['id'],2);
        //直接审核
        $rule='content_edit_review';
        $res=$this->check_review($admin,$rule);
        if($res){
            $this->redirect($rule,['id'=>$eid,'rstatus'=>2,'rdsc'=>'直接审核']);
        }
        $this->success('已提交修改');
    }
    /**
     * 产品技术资料编辑详情
     * @adminMenu(
     *     'name'   => '产品技术资料编辑详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品技术资料编辑详情',
     *     'param'  => ''
     * )
     */
    public function content_edit_info()
    {
        $m=$this->m;
        $eid=$this->request->param('id',0,'intval');
        $table=$this->table;
        //获取编辑信息
       
        $info1=Db::name('edit')
        ->field('e.*,p.name as pname,p.status as pstatus,p.type as ptype')
        ->alias('e')
        ->join('cmf_goods p','p.id=e.pid')
        ->where('e.id',$eid)
        ->find();
        if(empty($info1)){
            $this->error('编辑信息不存在');
        }
        //获取改变的信息
        $change=Db::name('edit_info')->where('eid',$eid)->value('content');
         
        $content=Db::name('goods_tech')->where('pid',$info1['pid'])->value('content');
        
        $this->assign('info1',$info1);
        $this->assign('content',$content);
        $this->assign('change',$change);
        
        return $this->fetch();
        
    }
    /**
     * 产品技术资料编辑审核
     * @adminMenu(
     *     'name'   => '产品技术资料编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品技术资料编辑审核',
     *     'param'  => ''
     * )
     */
    public function content_edit_review()
    {
        //审核编辑的信息
        $status=$this->request->param('rstatus',0,'intval');
        $id=$this->request->param('id',0,'intval');
        if(($status!=2 && $status!=3) || $id<=0){
            $this->error('信息错误');
        }
        $m=$this->m;
        $table=$this->table;
        $m_edit=Db::name('edit');
        $info=$m_edit
        ->field('e.*,p.name as pname,a.user_nickname as aname')
        ->alias('e')
        ->join('cmf_'.$table.' p','p.id=e.pid')
        ->join('cmf_user a','a.id=e.aid')
        ->where('e.id',$id)
        ->find();
        if(empty($info)){
            $this->error('无效信息');
        }
        if($info['rstatus']!=1){
            $this->error('编辑信息已被审核！不能重复审核');
        }
        
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1){
            if(empty($info['shop']) || $info['shop']!=$admin['shop']){
                $this->error('不能审核其他店铺的信息');
            }
        }
        $time=time();
        
        $m->startTrans();
        
        $update=[
            'rid'=>$admin['id'],
            'rtime'=>$time,
            'rstatus'=>$status,
        ];
        $review_status=$this->review_status;
        $rdsc=$this->request->param('rdsc');
        $update['rdsc']=(empty($rdsc))?$review_status[$status]:$rdsc;
        //只有未审核的才能更新
        $where=[
            'id'=>$id,
            'rstatus'=>1,
        ];
        $row=$m_edit->where($where)->update($update);
        if($row!==1){
            $m->rollback();
            $this->error('审核失败，请刷新后重试');
        }
        //是否更新,2同意，3不同意
        if($status==2){
            //组装更新数据
            $update_info=[
                'time'=>$time,
            ];
           
            
            $content=Db::name('edit_info')->where('eid',$id)->value('content');
            $where_delete=['pid'=>$info['pid']];
            $data_add=[
                'pid'=>$info['pid'],
                'content'=>$content,
            ];
            //检查是否存在，更新或添加
            $m_content=Db::name('goods_tech');
            $find=$m_content->where($where_delete)->find();
           
            if(empty($find)){
                $m_content->insert($data_add);
            }else{
                $m_content->where($where_delete)->update($data_add);
            }
            //统计资料字数
            $content_02 = htmlspecialchars_decode($content); //把一些预定义的 HTML 实体转换为字符
            $content_03 = str_replace("&nbsp;","",$content_02);//将空格替换成空
            $contents = strip_tags($content_03);//函数剥去字符串中的 HTML、XML 以及 PHP 的标签,获取纯文本内容
           
            $update_info['count_tech']=mb_strlen($contents); 
            
            $row=$m->where('id',$info['pid'])->update($update_info);
            if($row!==1){
                $m->rollback();
                $this->error('信息更新失败，请刷新后重试');
            }
        }
        
        //审核成功，记录操作记录,发送审核信息 
        $table0='content';
        $flag='产品技术资料';
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'审核'.$info['aid'].'-'.$info['aname'].'对'.($flag).$info['pid'].'-'.$info['pname'].'编辑为'.$review_status[$status],
            'table'=>$table,
            'type'=>'edit_review',
            'pid'=>$info['pid'],
            'link'=>url($table0.'_edit_info',['id'=>$info['id']]),
            'shop'=>$admin['shop'],
        ];
        zz_action($data_action,['aid'=>$info['aid']]); 
       
        $m->commit();
        //添加收藏关联
        $this->goods_collect($info['pid'],$admin['id'],3);
        $this->success('审核成功');
    }
    /**
     * 产品组合详情
     * @adminMenu(
     *     'name'   => '产品组合详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 50,
     *     'icon'   => '',
     *     'remark' => '产品组合详情',
     *     'param'  => ''
     * )
     */
    public function type2()
    { 
        $this->link_type(2);
       
        return $this->fetch();
    }
    /**
     * 产品组合编辑提交
     * @adminMenu(
     *     'name'   => '产品组合编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 51,
     *     'icon'   => '',
     *     'remark' => '产品组合编辑提交',
     *     'param'  => ''
     * )
     */
    public function type2_edit_do()
    {
        $this->link_type_do(2);
         
    }
    /**
     * 产品组合修改详情
     * @adminMenu(
     *     'name'   => '产品组合修改详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 52,
     *     'icon'   => '',
     *     'remark' => '产品组合修改详情',
     *     'param'  => ''
     * )
     */
    public function type2_edit_info()
    {
        $this->link_type_info(2);
        
        return $this->fetch();
    }
    /**
     * 产品组合审核
     * @adminMenu(
     *     'name'   => '产品组合审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 53,
     *     'icon'   => '',
     *     'remark' => '产品组合审核',
     *     'param'  => ''
     * )
     */
    public function type2_edit_review()
    {
        $this->link_type_review(2);
        
    }
    
    /**
     * 产品加工详情
     * @adminMenu(
     *     'name'   => '产品加工详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 60,
     *     'icon'   => '',
     *     'remark' => '产品加工详情',
     *     'param'  => ''
     * )
     */
    public function type4()
    {
       $this->link_type(4);
        return $this->fetch();
    }
    /**
     * 产品加工编辑提交
     * @adminMenu(
     *     'name'   => '产品加工编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 61,
     *     'icon'   => '',
     *     'remark' => '产品加工编辑提交',
     *     'param'  => ''
     * )
     */
    public function type4_edit_do()
    {
        $this->link_type_do(4);
        
        
    }
    /**
     * 产品加工修改详情
     * @adminMenu(
     *     'name'   => '产品加工修改详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 62,
     *     'icon'   => '',
     *     'remark' => '产品加工修改详情',
     *     'param'  => ''
     * )
     */
    public function type4_edit_info()
    {
        $this->link_type_info(4);
        
        return $this->fetch();
    }
    /**
     * 产品加工审核
     * @adminMenu(
     *     'name'   => '产品加工审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 63,
     *     'icon'   => '',
     *     'remark' => '产品加工审核',
     *     'param'  => ''
     * )
     */
    public function type4_edit_review()
    {
        $this->link_type_review(4);
        
    }
    
    /**
     * 产品标签详情
     * @adminMenu(
     *     'name'   => '产品标签详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 70,
     *     'icon'   => '',
     *     'remark' => '产品标签详情',
     *     'param'  => ''
     * )
     */
    public function type3()
    {
        $m=$this->m;
        $id=$this->request->param('id',0,'intval');
        $info=$m
        ->field('p.*,concat(cate1.name,"-",cate2.name) as cate_name')
        ->alias('p')
        ->join('cmf_cate cate2','cate2.id=p.cid','left')
        ->join('cmf_cate cate1','cate1.id=p.cid0','left')
        ->where('p.id',$id)
        ->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        //获取标签
        $label=Db::name('goods_label')
        ->alias('gl')
        ->field('gl.*,p1.name as pname1')
        ->join('cmf_goods p1','p1.id=gl.pid1')
        ->where('gl.pid0',$info['id'])
        ->find();
        if(empty($label)){
            $label=null; 
        }else{
            $label['files']=json_decode($label['files'],true);
        }
        
        //获取分类
        $this->cates();
        
        $pics=[
            '1'=>'正面标签',
            '2'=>'背面标签',
            '3'=>'侧面标签',
            '4'=>'外盒标签',
            '5'=>'其他标签', 
        ];
        
        $this->assign('info',$info);
        $this->assign('label',$label);
        $this->assign('pics',$pics);
        return $this->fetch();
    }
    /**
     * 产品标签编辑提交
     * @adminMenu(
     *     'name'   => '产品标签编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 71,
     *     'icon'   => '',
     *     'remark' => '产品标签编辑提交',
     *     'param'  => ''
     * )
     */
    public function type3_edit_do()
    {
        $m=$this->m;
        $data=$this->request->param();
        $id=intval($data['id']);
        $info=$m->where('id',$id)->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        $table=$this->table;
      
        $table0='type3';
        $flag='产品标签';
        $time=time();
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1){
            if(empty($info['shop']) || $info['shop']!=$admin['shop']){
                $this->error('不能编辑其他店铺的信息');
            }
        }
        
        $update=[
            'pid'=>$info['id'],
            'aid'=>$admin['id'],
            'atime'=>$time,
            'table'=>'goods',
            'url'=>url($table0.'_edit_info','',false,false),
            'rstatus'=>1,
            'rid'=>0,
            'rtime'=>0,
            'shop'=>$admin['shop'],
        ];
        $update['adsc']=(empty($data['adsc']))?'修改了'.$flag:$data['adsc'];
        $content=[];
        //原标签
        $label=Db::name('goods_label')->where('pid0',$info['id'])->find();
        if(empty($label)){
            $label=null;
         }
        if(empty($data['pid1'])){
            $this->error('没有添加关联产品');
        }
        $id1=intval($data['pid1']); 
        //关联产品比较
        if($id1 != $label['pid1']){
            $content['pid1']=$id1;
        }
        //比较编码 
        if($label['code']!=$data['code']){
            $content['code']=$data['code'];
        }
        //比较说明
        if($label['dsc']!=$data['dsc']){
            $content['dsc']=$data['dsc'];
        }
        //标签记录
        $pics=[
            '1'=>'正面标签',
            '2'=>'背面标签',
            '3'=>'侧面标签',
            '4'=>'外盒标签',
            '5'=>'其他标签',
        ];
        if(empty($label)){
            $label=null;
        }
        $path='upload/';
        $pathid='seller'.$info['shop'].'/goods'.$id.'/';
        //没有目录创建目录
        if(!is_dir($path.$pathid)){
            mkdir($path.$pathid);
        }
        //循环比较图片，数量
        $num=0;
        foreach($pics as $k=>$v){
            $tmp_pic=$data['pic'.$k];
            if($label['pic'.$k]!=$tmp_pic){
                if (!is_file($path.$tmp_pic))
                {
                    $this->error($pics[$k].'图片损坏，请注意');
                }
                //先比较是否需要额外保存,非指定位置的要复制粘贴
                if(strpos($tmp_pic, $pathid)!==0){
                    //获取后缀名,复制文件
                    $ext=substr($tmp_pic, strrpos($tmp_pic,'.'));
                    $new_file=$pathid.'label'.$k.'_'.date('Ymd-His').$ext;
                    $result =copy($path.$tmp_pic, $path.$new_file);
                    if ($result == false)
                    {
                        $this->error($pics[$k].'文件复制错误，请重试');
                    }else{ 
                        //删除原图片
                        unlink($path.$tmp_pic);
                        $tmp_pic=$new_file;
                    } 
                    //标签暂时不制作缩略图
                } 
                $content['pic'.$k]=$tmp_pic;
            }
            if($label['num'.$k]!=$data['num'.$k]){
                $content['num'.$k]=$data['num'.$k];
            }
            //总数量
            $num=$num+$data['num'.$k];
        }
        if($num!=$label['num']){
            $content['num']=$num;
        }
        //比较文档
        if(empty($data['files'])){
            $files1='';
        }else{
            $files1=[];
            foreach($data['files'] as $k=>$v){
                $tmp_file=$data['files'][$k];
                $tmp_name=$data['names'][$k];
                if (!is_file($path.$tmp_file))
                {
                    $this->error($tmp_name.'文件损坏，请注意');
                }
                //先比较是否需要额外保存,非指定位置的要复制粘贴
                if(strpos($tmp_file, $pathid)!==0){
                    //获取后缀名,复制文件
                    $ext=substr($tmp_file, strrpos($tmp_file,'.'));
                    $new_file=$pathid.'labelfiles'.$k.'_'.date('Ymd-His').$ext;
                    $result =copy($path.$tmp_file, $path.$new_file);
                    if ($result == false)
                    {
                        $this->error($tmp_name.'文件复制错误，请重试');
                    }else{
                        //删除原文件
                        unlink($path.$tmp_file);
                        $tmp_file=$new_file;
                    } 
                }
                $files1[]=['name'=>$tmp_name,'file'=>$tmp_file];
            }
            $files1=json_encode($files1);
        }
        if($label['files']!=$files1){
            $content['files']=$files1;
        }
        if(empty($content)){
            $this->error('未修改');
        }
        //保存更改
        $m_edit=Db::name('edit');
        $m_edit->startTrans();
        $eid=$m_edit->insertGetId($update);
        if($eid>0){
            $data_content=[
                'eid'=>$eid,
                'content'=>json_encode($content),
            ];
            Db::name('edit_info')->insert($data_content);
        }else{
            $m_edit->rollback();
            $this->error('保存数据错误，请重试');
        }
      
        //记录操作记录
       
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'编辑了'.$flag.$info['id'].'-'.$info['name'],
            'table'=>($this->table),
            'type'=>'edit',
            'pid'=>$info['id'],
            'link'=>url($table0.'_edit_info',['id'=>$eid]),
            'shop'=>$admin['shop'],
        ];
        zz_action($data_action,['department'=>$admin['department']]);
        $m_edit->commit();
        //添加收藏关联
        $this->goods_collect($info['id'],$admin['id'],2);
        
        //直接审核
        $rule='type3_edit_review';
        $res=$this->check_review($admin,$rule);
        if($res){
            $this->redirect($rule,['id'=>$eid,'rstatus'=>2,'rdsc'=>'直接审核']);
        }
        $this->success('已提交修改');
        
    }
    /**
     * 产品标签修改详情
     * @adminMenu(
     *     'name'   => '产品标签修改详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 72,
     *     'icon'   => '',
     *     'remark' => '产品标签修改详情',
     *     'param'  => ''
     * )
     */
    public function type3_edit_info()
    {
        $m=$this->m;
        $eid=$this->request->param('id',0,'intval');
        $info1=Db::name('edit')
        ->field('e.*,p.name as pname,p.status as pstatus,p.type as ptype')
        ->alias('e')
        ->join('cmf_goods p','p.id=e.pid')
        ->where('e.id',$eid)
        ->find();
        if(empty($info1)){
            $this->error('数据不存在');
        }
        
        //获取改变的信息
        $change=Db::name('edit_info')->where('eid',$eid)->value('content');
        $change=json_decode($change,true);
        
        if(!empty($change['files'])){
            $change['files']=json_decode($change['files'],true);
        }
        //关联产品
        if(isset($change['pid1'])){ 
            $change['pname1']=Db::name('goods')->where('id',$change['pid1'])->value('name'); 
        }
        //原标签 
        //获取标签
        $label=Db::name('goods_label')
        ->alias('gl')
        ->field('gl.*,p1.name as pname1')
        ->join('cmf_goods p1','p1.id=gl.pid1')
        ->where('gl.pid0',$info1['pid'])
        ->find();
        if(empty($label)){
            $label=null;
        }else{
            $label['files']=json_decode($label['files'],true);
        }
         
         
        //标签记录
        $pics=[
            '1'=>'正面标签',
            '2'=>'背面标签',
            '3'=>'侧面标签',
            '4'=>'外盒标签',
            '5'=>'其他标签',
        ];
         
        $this->assign('pics',$pics);
       
        $this->assign('label',$label);
        $this->assign('info1',$info1);
       
        $this->assign('change',$change);
        return $this->fetch();
    }
    /**
     * 产品标签审核
     * @adminMenu(
     *     'name'   => '产品标签审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 73,
     *     'icon'   => '',
     *     'remark' => '产品标签审核',
     *     'param'  => ''
     * )
     */
    public function type3_edit_review()
    {
        //审核编辑的信息
        $status=$this->request->param('rstatus',0,'intval');
        $id=$this->request->param('id',0,'intval');
        if(($status!=2 && $status!=3) || $id<=0){
            $this->error('信息错误');
        }
        $m=$this->m;
        $table=$this->table;
        $m_edit=Db::name('edit');
        $info=$m_edit
        ->field('e.*,p.name as pname,p.shop as pshop,p.type,a.user_nickname as aname')
        ->alias('e')
        ->join('cmf_'.$table.' p','p.id=e.pid')
        ->join('cmf_user a','a.id=e.aid')
        ->where('e.id',$id)
        ->find();
        if(empty($info)){
            $this->error('无效信息');
        }
        if($info['rstatus']!=1){
            $this->error('编辑信息已被审核！不能重复审核');
        }
        
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1){
            if(empty($info['shop']) || $info['shop']!=$admin['shop']){
                $this->error('不能审核其他店铺的信息');
            }
        }
        $time=time();
        
        $m->startTrans();
        
        $update=[
            'rid'=>$admin['id'],
            'rtime'=>$time,
            'rstatus'=>$status,
        ];
        $review_status=$this->review_status;
        $rdsc=$this->request->param('rdsc');
        $update['rdsc']=(empty($rdsc))?$review_status[$status]:$rdsc;
        //只有未审核的才能更新
        $where=[
            'id'=>$id,
            'rstatus'=>1,
        ];
        $row=$m_edit->where($where)->update($update);
        if($row!==1){
            $m->rollback();
            $this->error('审核失败，请刷新后重试');
        }
        //是否更新,2同意，3不同意
        if($status==2){
            //组装更新数据
            $update_info=[
                'time'=>$time,
                'goods_link'=>1,
            ];
            //得到修改的字段
            $change=Db::name('edit_info')->where('eid',$id)->value('content');
            $change=json_decode($change,true);
            //更新主要在goods_label表
            $m_label=Db::name('goods_label');
            $label1=[];
            foreach($change as $k=>$v){
                $label1[$k]=$v;
            }
            //得到原数据
            $label0=$m_label->where('pid0',$info['pid'])->find();
            //没加过就新增，加过就更新
            if(empty($label0)){
                $label1['pid0']=$info['pid'];
                $label1['shop']=$info['pshop'];
                $m_label->insert($label1); 
            }else{
                $m_label->where('pid0',$info['pid'])->update($label1); 
            }
            //和主产品数据同步
            $fields_link=$this->fields_link;
            $pid_link=(isset($label1['pid1']))?$label1['pid1']:$label0['pid1'];
            $info_link=$m->where('id',$pid_link)->find();
            foreach($fields_link as $v){
                $update_info[$v]=$info_link[$v];
            }
            $row=$m->where('id',$info['pid'])->update($update_info);
            if($row!==1){
                $m->rollback();
                $this->error('信息更新失败，请刷新后重试');
            }
            
        }
        
        //审核成功，记录操作记录,发送审核信息
        
        $flag='产品标签';
        $table0='type3';
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'审核'.$info['aid'].'-'.$info['aname'].'对'.($flag).$info['pid'].'-'.$info['pname'].'编辑为'.$review_status[$status],
            'table'=>$table,
            'type'=>'edit_review',
            'pid'=>$info['pid'],
            'link'=>url($table0.'_edit_info',['id'=>$info['id']]),
            'shop'=>$admin['shop'],
        ];
        zz_action($data_action,['aid'=>$info['aid']]); 
        
        $m->commit();
        //添加收藏关联
        $this->goods_collect($info['pid'],$admin['id'],3);
        $this->success('审核成功');
    }
    /**
     * 设备关联产品详情
     * @adminMenu(
     *     'name'   => '设备关联产品详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 80,
     *     'icon'   => '',
     *     'remark' => '设备关联产品详情',
     *     'param'  => ''
     * )
     */
    public function type5()
    {
        $this->link_type(5);
        
        return $this->fetch();
    }
    /**
     * 设备关联产品编辑提交
     * @adminMenu(
     *     'name'   => '设备关联产品编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 81,
     *     'icon'   => '',
     *     'remark' => '设备关联产品编辑提交',
     *     'param'  => ''
     * )
     */
    public function type5_edit_do()
    {
        $this->link_type_do(5);
       
    }
    /**
     * 设备关联产品修改详情
     * @adminMenu(
     *     'name'   => '设备关联产品修改详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 82,
     *     'icon'   => '',
     *     'remark' => '设备关联产品修改详情',
     *     'param'  => ''
     * )
     */
    public function type5_edit_info()
    {
        $this->link_type_info(5);
        
        return $this->fetch();
    }
    /**
     * 设备审核
     * @adminMenu(
     *     'name'   => '设备审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 83,
     *     'icon'   => '',
     *     'remark' => '设备审核',
     *     'param'  => ''
     * )
     */
    public function type5_edit_review()
    {
        $this->link_type_review(5);
        
    }
    //关联详情页
    public function link_type($type){
        $m=$this->m;
        $id=$this->request->param('id',0,'intval');
        $info=$m
        ->field('p.*,concat(cate1.name,"-",cate2.name) as cate_name')
        ->alias('p')
        ->join('cmf_cate cate2','cate2.id=p.cid','left')
        ->join('cmf_cate cate1','cate1.id=p.cid0','left')
        ->where('p.id',$id)
        ->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        //获取分类
        $this->cates();
        
        //关联产品
        
        $id_links=Db::name('goods_link')
        ->alias('gl')
        ->join('cmf_goods p','p.id=gl.pid1')
        ->where('gl.pid0',$id)
        ->column('gl.pid1,gl.num,gl.dsc,p.name');
        $this->assign('id_links',$id_links);
        $this->assign('info',$info);
         
    }
    //关联编辑
    public function link_type_do($type){
        $m=$this->m;
        $data=$this->request->param();
        $id=intval($data['id']);
        $info=$m->where('id',$id)->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        $table=$this->table;
        
        $table0='type'.$type;
     
        switch($type){
            case 2:
                $flag='产品组合';
                break;
            case 4:
                $flag='产品加工';
                break;
            case 5:
                $flag='设备';
                break;
            default:
               $this->error('类型错误');
        }
        $time=time();
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1){
            if(empty($info['shop']) || $info['shop']!=$admin['shop']){
                $this->error('不能编辑其他店铺的信息');
            }
        }
        $update=[
            'pid'=>$info['id'],
            'aid'=>$admin['id'],
            'atime'=>$time,
            'table'=>$table,
            'url'=>url($table0.'_edit_info','',false,false),
            'rstatus'=>1,
            'rid'=>0,
            'rtime'=>0,
            'shop'=>$admin['shop'],
        ];
        
        $update['adsc']=(empty($data['adsc']))?'修改了'.$flag.'信息':$data['adsc'];
        $content=[];
        //关联产品
        $links0=Db::name('goods_link')->where('pid0',$data['id'])->column('pid1,num,dsc');
        $links1=empty($data['id_links'])?[]:$data['id_links'];
        //配件数量格式
        foreach($links1 as $k=>$v){
            $links1[$k]=intval($v);
            if($links1[$k]<=0){
                $this->error('关联产品数量错误');
            }
            //存在
            if(isset($links0[$k])){
                if($links0[$k]['num'] != $v){
                    $content['edit'][$k]['num']=$v;
                }
                if($links0[$k]['dsc'] != $data['id_dscs'][$k]){
                    $content['edit'][$k]['dsc']=$data['id_dscs'][$k];
                }
            }else{
                //新增
                $content['add'][$k]=[
                    'dsc'=>$data['id_dscs'][$k],
                    'num'=>$v,
                    'pid1'=>$k,
                    'type'=>$type,
                    'pid0'=>$id,
                    'shop'=>$info['shop'],
                ];
            }
        }
        //检查是否要删除
        foreach($links0 as $k=>$v){
            if(empty($links1[$k])){
                $content['del'][$k]=$k;
            }
        }
        
        
        if(empty($content)){
            $this->error('未修改');
        }
        //保存更改
        $m_edit=Db::name('edit');
        $m_edit->startTrans();
        $eid=$m_edit->insertGetId($update);
        if($eid>0){
            $data_content=[
                'eid'=>$eid,
                'content'=>json_encode($content),
            ];
            Db::name('edit_info')->insert($data_content);
        }else{
            $m_edit->rollback();
            $this->error('保存数据错误，请重试');
        }
        
        //记录操作记录
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'编辑了'.$flag.$info['id'].'-'.$info['name'],
            'table'=>($this->table),
            'type'=>'edit',
            'pid'=>$info['id'],
            'link'=>url($table0.'_edit_info',['id'=>$eid]),
            'shop'=>$admin['shop'],
        ];
        
        zz_action($data_action,['department'=>$admin['department']]);
        $m_edit->commit();
        //添加收藏关联
        $this->goods_collect($info['id'],$admin['id'],2);
        //直接审核
        $rule=$table0.'_edit_review';
        $res=$this->check_review($admin,$rule);
        if($res){
            $this->redirect($rule,['id'=>$eid,'rstatus'=>2,'rdsc'=>'直接审核']);
        }
        $this->success('已提交修改');
        
    }
    //关联编辑审核
    public function link_type_review($type){
        //审核编辑的信息
        $status=$this->request->param('rstatus',0,'intval');
        $id=$this->request->param('id',0,'intval');
        if(($status!=2 && $status!=3) || $id<=0){
            $this->error('信息错误');
        }
        $m=$this->m;
        $table=$this->table;
        $m_edit=Db::name('edit');
        $info=$m_edit
        ->field('e.*,p.name as pname,p.type,p.shop as pshop,a.user_nickname as aname')
        ->alias('e')
        ->join('cmf_'.$table.' p','p.id=e.pid')
        ->join('cmf_user a','a.id=e.aid')
        ->where('e.id',$id)
        ->find();
        if(empty($info)){
            $this->error('无效信息');
        }
        if($info['rstatus']!=1){
            $this->error('编辑信息已被审核！不能重复审核');
        }
        
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1){
            if(empty($info['shop']) || $info['shop']!=$admin['shop']){
                $this->error('不能审核其他店铺的信息');
            }
        }
        $time=time();
        
        $m->startTrans();
        
        $update=[
            'rid'=>$admin['id'],
            'rtime'=>$time,
            'rstatus'=>$status,
        ];
        $review_status=$this->review_status;
        $rdsc=$this->request->param('rdsc');
        $update['rdsc']=(empty($rdsc))?$review_status[$status]:$rdsc;
        //只有未审核的才能更新
        $where=[
            'id'=>$id,
            'rstatus'=>1,
        ];
        $row=$m_edit->where($where)->update($update);
        if($row!==1){
            $m->rollback();
            $this->error('审核失败，请刷新后重试');
        }
        //是否更新,2同意，3不同意
        if($status==2){
            //组装更新数据
            $update_info=[
                'time'=>$time,
            ];
            //得到修改的字段
            $change=Db::name('edit_info')->where('eid',$id)->value('content');
            $change=json_decode($change,true);
            foreach($change as $k=>$v){
                $update_info[$k]=$v;
            }
            
            //得到原数据
            $info_tmp=$m->where('id',$info['pid'])->find();
            
            //处理关联产品
            $m_link=Db::name('goods_link');
            //先删除
            if(isset($update_info['del'])){
                $where_del=[
                    'pid0'=>$info['pid'],
                    'pid1'=>['in',$update_info['del']]
                ];
                $m_link->where($where_del)->delete();
                unset($update_info['del']);
            }
            //编辑
            if(isset($update_info['edit'])){
                $where_update=[
                    'pid0'=>$info['pid'],
                ];
                foreach($update_info['edit'] as $k=>$v){
                    $where_update['pid1']=$k;
                    $m_link->where($where_update)->update($v);
                }
                unset($update_info['edit']);
            }
            //新增,先删除再新增
            if(isset($update_info['add'])){
                $pid1s=array_keys($update_info['add']);
                $where_del=[
                    'pid0'=>$info['pid'],
                    'pid1'=>['in',$pid1s]
                ];
                $m_link->where($where_del)->delete();
                $m_link->insertAll($update_info['add']);
                unset($update_info['add']);
            }
            //统计关联设备总数量
            $where_link=[
                'pid0'=>$info['pid'],
            ];
            $update_info['goods_link']=$m_link->where($where_link)->sum('num');
            if(empty($update_info['goods_link'])){
                $update_info['goods_link']=0;
            }
            
            $row=$m->where('id',$info['pid'])->update($update_info);
            if($row!==1){
                $m->rollback();
                $this->error('信息更新失败，请刷新后重试');
            }
            
        }
        
        //审核成功，记录操作记录,发送审核信息 
        $table0='type'.$type; 
        switch($type){
            case 2:
                $flag='产品组合';
                break;
            case 4:
                $flag='产品加工';
                break;
            case 5:
                $flag='设备';
                break;
            default:
                $this->error('类型错误');
        }
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'审核'.$info['aid'].'-'.$info['aname'].'对'.($flag).$info['pid'].'-'.$info['pname'].'编辑为'.$review_status[$status],
            'table'=>$table,
            'type'=>'edit_review',
            'pid'=>$info['pid'],
            'link'=>url($table0.'_edit_info',['id'=>$info['id']]),
            'shop'=>$admin['shop'],
        ];
        zz_action($data_action,['aid'=>$info['aid']]);
        
        $m->commit();
        //添加收藏关联
        $this->goods_collect($info['pid'],$admin['id'],3);
        $this->success('审核成功');
    }
    //关联修改详情
    public function link_type_info(){
        $m=$this->m;
        $eid=$this->request->param('id',0,'intval');
        $info1=Db::name('edit')
        ->field('e.*,p.name as pname,p.status as pstatus,p.type as ptype')
        ->alias('e')
        ->join('cmf_goods p','p.id=e.pid')
        ->where('e.id',$eid)
        ->find();
        if(empty($info1)){
            $this->error('数据不存在');
        }
        
        //获取改变的信息
        $change=Db::name('edit_info')->where('eid',$eid)->value('content');
        $change=json_decode($change,true);
        
        $m_link=Db::name('goods_link');
        $links0=$m_link 
        ->where('pid0',$info1['pid'])
        ->column('pid1,num,dsc');
        $ids=array_keys($links0);
        if(isset($change['add'])){
            $ids1=array_keys($change['add']);
            $ids=array_merge($ids,$ids1);
        }
        $names=$m->where('id','in',$ids)->column('id,name');
        foreach($links0 as $k=>$v){
            $links0[$k]['name']=(empty($names[$k]))?$k:$names[$k];
        }
        if(isset($change['add'])){
            foreach($change['add'] as $k=>$v){
                $change['add'][$k]['name']=(empty($names[$k]))?$k:$names[$k];
            }
        }
        $this->assign('links0',$links0);
        
        $this->assign('info1',$info1);
        
        $this->assign('change',$change);
         
    }
    /**
     * 产品关联设备详情
     * @adminMenu(
     *     'name'   => '产品关联设备详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 80,
     *     'icon'   => '',
     *     'remark' => '产品关联设备详情',
     *     'param'  => ''
     * )
     */
    public function type0()
    {
        $m=$this->m;
        $id=$this->request->param('id',0,'intval');
        $info=$m
        ->field('p.*,concat(cate1.name,"-",cate2.name) as cate_name')
        ->alias('p')
        ->join('cmf_cate cate2','cate2.id=p.cid','left')
        ->join('cmf_cate cate1','cate1.id=p.cid0','left')
        ->where('p.id',$id)
        ->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        //获取分类
        $this->cates();
        //产品与设备的关联
        $where=[
            'gl.pid1'=>$id,
            'gl.type'=>5,
        ];
        //关联产品
        $id_links=Db::name('goods_link')
        ->alias('gl')
        ->join('cmf_goods p','p.id=gl.pid0')
        ->where($where)
        ->column('gl.pid0,gl.num,gl.dsc,p.name');
        
        $this->assign('id_links',$id_links);
        $this->assign('info',$info);
        $this->assign('ctype',2);
        $this->assign('gtype',5);
        return $this->fetch();
    }
    /**
     * 产品关联设备编辑提交
     * @adminMenu(
     *     'name'   => '产品关联设备编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 81,
     *     'icon'   => '',
     *     'remark' => '产品关联设备编辑提交',
     *     'param'  => ''
     * )
     */
    public function type0_edit_do()
    {
        $m=$this->m;
        $data=$this->request->param();
        $id=intval($data['id']);
        $info=$m->where('id',$id)->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        $table=$this->table;
        
        $table0='type0';
        $flag='产品';
        $time=time();
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1){
            if(empty($info['shop']) || $info['shop']!=$admin['shop']){
                $this->error('不能编辑其他店铺的信息');
            }
        }
        $update=[
            'pid'=>$info['id'],
            'aid'=>$admin['id'],
            'atime'=>$time,
            'table'=>'goods',
            'url'=>url($table0.'_edit_info','',false,false),
            'rstatus'=>1,
            'rid'=>0,
            'rtime'=>0,
            'shop'=>$admin['shop'],
        ];
        $update['adsc']=(empty($data['adsc']))?'修改了'.$flag:$data['adsc'];
        $content=[]; 
        //产品与设备的关联
        $where=[
            'pid1'=>$id,
            'type'=>5,
        ];
      
        $links0=Db::name('goods_link')->where('pid0',$data['id'])->column('pid0,num,dsc');
        $links1=empty($data['id_links'])?[]:$data['id_links'];
        //配件数量格式
        foreach($links1 as $k=>$v){
            $links1[$k]=intval($v);
            if($links1[$k]<=0){
                $this->error('关联产品数量错误');
            }
            //存在
            if(isset($links0[$k])){
                if($links0[$k]['num'] != $v){
                    $content['edit'][$k]['num']=$v;
                }
                if($links0[$k]['dsc'] != $data['id_dscs'][$k]){
                    $content['edit'][$k]['dsc']=$data['id_dscs'][$k];
                }
            }else{
                //新增
                $content['add'][$k]=[
                    'dsc'=>$data['id_dscs'][$k],
                    'num'=>$v,
                    'pid0'=>$k,
                    'type'=>5,
                    'pid1'=>$id,
                    'shop'=>$info['shop'],
                ];
            }
        }
        //检查是否要删除
        foreach($links0 as $k=>$v){
            if(empty($links1[$k])){
                $content['del'][$k]=$k;
            }
        }
        if(empty($content)){
            $this->error('未修改');
        }
        //保存更改
        $m_edit=Db::name('edit');
        $m_edit->startTrans();
        $eid=$m_edit->insertGetId($update);
        if($eid>0){
            $data_content=[
                'eid'=>$eid,
                'content'=>json_encode($content),
            ];
            Db::name('edit_info')->insert($data_content);
        }else{
            $m_edit->rollback();
            $this->error('保存数据错误，请重试');
        }
        
        //记录操作记录
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'编辑了'.$flag.$info['id'].'-'.$info['name'].'的对应设备',
            'table'=>($this->table),
            'type'=>'edit',
            'pid'=>$info['id'],
            'link'=>url($table0.'_edit_info',['id'=>$eid]),
            'shop'=>$admin['shop'],
        ];
        zz_action($data_action,['department'=>$admin['department']]);
        $m_edit->commit();
        //添加收藏关联
        $this->goods_collect($info['id'],$admin['id'],2);
        //直接审核
        $rule='type0_edit_review';
        $res=$this->check_review($admin,$rule);
        if($res){
            $this->redirect($rule,['id'=>$eid,'rstatus'=>2,'rdsc'=>'直接审核']);
        }
        $this->success('已提交修改');
        
    }
    /**
     * 产品关联设备修改详情
     * @adminMenu(
     *     'name'   => '产品关联设备修改详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 82,
     *     'icon'   => '',
     *     'remark' => '产品关联设备修改详情',
     *     'param'  => ''
     * )
     */
    public function type0_edit_info()
    {
        $m=$this->m;
        $eid=$this->request->param('id',0,'intval');
        $info1=Db::name('edit')
        ->field('e.*,p.name as pname,p.status as pstatus,p.type as ptype')
        ->alias('e')
        ->join('cmf_goods p','p.id=e.pid')
        ->where('e.id',$eid)
        ->find();
        if(empty($info1)){
            $this->error('数据不存在');
        }
        
        //获取改变的信息
        $change=Db::name('edit_info')->where('eid',$eid)->value('content');
        $change=json_decode($change,true);
        
        $m_link=Db::name('goods_link');
        //产品与设备的关联 
        $where=[
            'pid1'=>$info1['pid'],
            'type'=>5,
        ];
        $links0=$m_link
        ->where($where)
        ->column('pid0,num,dsc');
        $ids=array_keys($links0);
        if(isset($change['add'])){
            $ids1=array_keys($change['add']);
            $ids=array_merge($ids,$ids1);
        }
        $names=$m->where('id','in',$ids)->column('id,name');
        foreach($links0 as $k=>$v){
            $links0[$k]['name']=(empty($names[$k]))?$k:$names[$k];
        }
        if(isset($change['add'])){
            foreach($change['add'] as $k=>$v){
                $change['add'][$k]['name']=(empty($names[$k]))?$k:$names[$k];
            }
        }
        $this->assign('links0',$links0);
        
        $this->assign('info1',$info1);
        
        $this->assign('change',$change);
        return $this->fetch();
    }
    /**
     * 产品关联设备审核
     * @adminMenu(
     *     'name'   => '产品关联设备审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 83,
     *     'icon'   => '',
     *     'remark' => '产品关联设备审核',
     *     'param'  => ''
     * )
     */
    public function type0_edit_review()
    {
        //审核编辑的信息
        $status=$this->request->param('rstatus',0,'intval');
        $id=$this->request->param('id',0,'intval');
        if(($status!=2 && $status!=3) || $id<=0){
            $this->error('信息错误');
        }
        $m=$this->m;
        $table=$this->table;
        $type=5;
        $m_edit=Db::name('edit');
        $info=$m_edit
        ->field('e.*,p.name as pname,p.type,p.shop as pshop,a.user_nickname as aname')
        ->alias('e')
        ->join('cmf_'.$table.' p','p.id=e.pid')
        ->join('cmf_user a','a.id=e.aid')
        ->where('e.id',$id)
        ->find();
        if(empty($info)){
            $this->error('无效信息');
        }
        if($info['rstatus']!=1){
            $this->error('编辑信息已被审核！不能重复审核');
        }
        
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1){
            if(empty($info['shop']) || $info['shop']!=$admin['shop']){
                $this->error('不能审核其他店铺的信息');
            }
        }
        $time=time();
        
        $m->startTrans();
        
        $update=[
            'rid'=>$admin['id'],
            'rtime'=>$time,
            'rstatus'=>$status,
        ];
        $review_status=$this->review_status;
        $rdsc=$this->request->param('rdsc');
        $update['rdsc']=(empty($rdsc))?$review_status[$status]:$rdsc;
        //只有未审核的才能更新
        $where=[
            'id'=>$id,
            'rstatus'=>1,
        ];
        $row=$m_edit->where($where)->update($update);
        if($row!==1){
            $m->rollback();
            $this->error('审核失败，请刷新后重试');
        }
        //是否更新,2同意，3不同意
        if($status==2){
            //组装更新数据
            $update_info=[
                'time'=>$time,
            ];
            //得到修改的字段
            $change=Db::name('edit_info')->where('eid',$id)->value('content');
            $change=json_decode($change,true);
            
            foreach($change as $k=>$v){
                $update_info[$k]=$v;
            }
            $where=[
                'pid1'=>$info['pid'],
                'type'=>$type, 
            ];
            $m_link=Db::name('goods_link');
            //记录所有更改的pid
            $pids_all=[];
            //处理关联产品
            //先删除
            if(isset($update_info['del'])){
                $where['pid0']=['in',$update_info['del']];
                $pids_all=array_merge($pids_all,$update_info['del']);
                $m_link->where($where)->delete();
                unset($update_info['del']);
            }
            //编辑
            if(isset($update_info['edit'])){
                
                foreach($update_info['edit'] as $k=>$v){
                    $where['pid0']=$k;
                    $pids_all[]=$k;
                    $m_link->where($where)->update($v);
                }
                unset($update_info['edit']);
            }
            //新增,先删除再新增
            if(isset($update_info['add'])){
                $pids=array_keys($update_info['add']);
                $pids_all=array_merge($pids_all,$pids);
                $where['pid0']=['in',$pids];
                $m_link->where($where)->delete();
                $m_link->insertAll($update_info['add']);
                unset($update_info['add']);
            }
            //有关联更新要重新统计关联数
            if(!empty($pids_all)){
                foreach($pids_all as $k=>$v){
                    $where=[
                        'pid0'=>$v,
                        'type'=>5, 
                    ];
                    $num=$m_link->where($where)->sum('num');
                    if(empty($num)){
                        $num=0;
                    }
                    $m->where('id',$v)->setField('goods_link',$num);
                }
            } 
        }
        
        //审核成功，记录操作记录,发送审核信息
        
        $flag='产品';
        $table0='type0';
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'审核'.$info['aid'].'-'.$info['aname'].'对'.($flag).$info['pid'].'-'.$info['pname'].'的对应设备编辑为'.$review_status[$status],
            'table'=>$table,
            'type'=>'edit_review',
            'pid'=>$info['pid'],
            'link'=>url($table0.'_edit_info',['id'=>$info['id']]),
            'shop'=>$admin['shop'],
        ];
        zz_action($data_action,['aid'=>$info['aid']]);
        
        $m->commit();
        //添加收藏关联
        $this->goods_collect($info['pid'],$admin['id'],3);
        $this->success('审核成功');
    }
    /**
     * 产品状态批量同意
     * @adminMenu(
     *     'name'   => '产品状态批量同意',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品状态批量同意',
     *     'param'  => ''
     * )
     */
    public function review_all()
    {
        if(empty($_POST['ids'])){
            $this->error('未选中信息');
        }
        $ids=$_POST['ids'];
        $m=$this->m;
        $admin=$this->admin;
        $time=time();
        $where=[
            'id'=>['in',$ids],
            'status'=>['eq',1],
        ];
        //其他店铺检查,如果没有shop属性就只能是1号主站操作,有shop属性就带上查询条件
        if($admin['shop']!=1){
          
                $where['shop']=['eq',$admin['shop']];
           
        }
        
        $update=[
            'status'=>2,
            'time'=>$time,
            'rid'=>$admin['id'],
            'rtime'=>$time,
        ];
        //得到要更改的数据
        $list=$m->where($where)->column('id');
        if(empty($list)){
            $this->error('没有可以批量审核的数据');
        }
        
        $ids=implode(',',$list);
        
        //审核成功，记录操作记录,发送审核信息
        $flag=$this->flag;
        
        $table=$this->table;
        //记录操作记录
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'批量同意'.$flag.'('.$ids.')',
            'table'=>$table,
            'type'=>'review_all',
            'link'=>'',
            'shop'=>$admin['shop'],
        ];
        $m->startTrans();
        
        zz_action($data_action,['pids'=>$ids]);
        $rows=$m->where('id','in',$list)->update($update);
        if($rows<=0){
            $m->rollback();
            $this->error('没有数据审核成功，批量审核只能把未审核的数据审核为正常');
        }
        $m->commit();
        $this->success('审核成功'.$rows.'条数据');
    }
    /**
     * 产品禁用
     * @adminMenu(
     *     'name'   => '信息状态禁用',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '信息状态禁用',
     *     'param'  => ''
     * )
     */
    public function ban()
    {
        //区分是一个还是数组
        $id=$this->request->param('id',0,'intval');
        
        $where=['status'=>['eq',2]];
        if($id>0){
            $where['id']=['eq',$id];
        }elseif(empty($_POST['ids'])){
            $this->error('未选中信息');
        }else{
            $ids=$_POST['ids'];
            $where['id']=['in',$ids];
        }
        //其他店铺检查,如果没有shop属性就只能是1号主站操作,有shop属性就带上查询条件
        $admin=$this->admin;
        if($admin['shop']!=1){
        
                $where['shop']=['eq',$admin['shop']];
        }
        $m=$this->m;
        
        $update=['status'=>4];
        $rows=$m->where($where)->update($update);
        
        if($rows>=1){
            
            $this->success('已禁用'.$rows.'条数据');
        }else{
            $this->error('没有成功禁用数据，禁用是指将状态为正常改为禁用');
        }
    }
    /**
     * 产品信息状态恢复
     * @adminMenu(
     *     'name'   => '产品信息状态恢复',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品信息状态恢复',
     *     'param'  => ''
     * )
     */
    public function cancel_ban()
    {
        //区分是一个还是数组
        $id=$this->request->param('id',0,'intval');
        
        $where=['status'=>['eq',4]];
        if($id>0){
            $where['id']=['eq',$id];
        }elseif(empty($_POST['ids'])){
            $this->error('未选中信息');
        }else{
            $ids=$_POST['ids'];
            $where['id']=['in',$ids];
        }
        //其他店铺检查,如果没有shop属性就只能是1号主站操作,有shop属性就带上查询条件
        $admin=$this->admin;
        if($admin['shop']!=1){
            
                $where['shop']=['eq',$admin['shop']]; 
        }
        $m=$this->m;
        $update=['status'=>2];
        $rows=$m->where($where)->update($update);
        
        if($rows>=1){
            $this->success('已恢复'.$rows.'条数据');
        }else{
            $this->error('没有成功恢复数据,恢复是指将状态为禁用改为正常');
        }
    }
    /**
     * 产品编辑提交
     * @adminMenu(
     *     'name'   => '产品编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品编辑提交',
     *     'param'  => ''
     * )
     */
    public function edit_do()
    {
        $m=$this->m;
        $table=$this->table;
        $flag=$this->flag;
        $data0=$this->request->param();
        //检查不合法参数
        $data=$this->param_check($data0);
        if(!is_array($data)){
            $this->error($data);
        }
         
        $info=$m->where('id',$data['id'])->find();
        if(empty($info)){
            $this->error('数据不存在');
        }
        $time=time();
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1){
            if(empty($info['shop']) || $info['shop']!=$admin['shop']){
                $this->error('不能编辑其他店铺的信息');
            }
        }
       
        $update=[
            'pid'=>$info['id'],
            'aid'=>$admin['id'],
            'atime'=>$time,
            'table'=>'goods',
            'url'=>url('edit_info','',false,false),
            'rstatus'=>1,
            'rid'=>0,
            'rtime'=>0,
            'shop'=>$admin['shop'],
        ];
        $update['adsc']=(empty($data['adsc']))?'修改了'.$flag:$data['adsc'];
        //产品的字段 
        $fields=['cid','cid0','code_num','code_name','code','name','name2','name3',
            'type','sn_type','sn','brand','bchar','price','price_sale','price_in','price_cost',
            'price_min','price_range1','price_range2','price_range3','price_dealer1',
            'price_dealer2','price_dealer3','price_trade','price_factory','sort','dsc',
        ];
        //加标产品不能修改重量大小和技术参数，只能跟随主产品变动
        if($info['type']!=3){
            $fields_link=$this->fields_link;
            $fields=array_merge($fields,$fields_link);
        }
       
        $content=[];
       
        //检测改变了哪些字段
        foreach($fields as $k=>$v){
            //如果原信息和$data信息相同就未改变，不为相同就记录
            if(isset($data[$v]) && $info[$v]!=$data[$v]){
                $content[$v]=$data[$v];
            } 
        }
        
         //技术参数记录,新旧参数比较  .加标产品不修改参数
         if($info['type']!=3 && is_array($data['value'])){
             $params=[];
             if(!empty($data['value'])){ 
                 foreach($data['value'] as $k=>$v){
                     if(is_array($v)){
                         $v=implode(',', $v);
                     }
                     $params[$k]=$v;
                 }
             }
             //检测技术参数
             if(isset($content['template'])){
                 //模板改变直接变了
                 $content['param']=json_encode($params);
             }else{
                 //模板没变就需要比较
                 $params0= Db::name('goods_param')->where('pid',$info['id'])->column('param_id,value'); 
                 //计算新旧参数的差级，没有差级就是完全一样 
                 if(!empty(array_diff($params0,$params)) ||  !empty(array_diff($params,$params0))){
                     $content['param']=json_encode($params);
                     //参数有改变，要记住模板
                     $content['template']=$data['template'];
                 }
             }
         } 
          
        if(empty($content)){
            $this->error('未修改');
        }
        //保存更改
        $m_edit=Db::name('edit');
        $m_edit->startTrans();
        $eid=$m_edit->insertGetId($update);
        if($eid>0){
            $data_content=[
                'eid'=>$eid,
                'content'=>json_encode($content),
            ];
            Db::name('edit_info')->insert($data_content);
        }else{
            $m_edit->rollback();
            $this->error('保存数据错误，请重试');
        }
        //记录操作记录
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'编辑了'.$flag.$info['id'].'-'.$info['name'],
            'table'=>($this->table),
            'type'=>'edit',
            'pid'=>$info['id'],
            'link'=>url('edit_info',['id'=>$eid]),
            'shop'=>$admin['shop'],
        ];
        zz_action($data_action,['department'=>$admin['department']]);
         
        $m_edit->commit();
        //添加收藏关联
        $this->goods_collect($info['id'],$admin['id'],2);
        
        //判断是否直接审核
        $rule='edit_review';
        $res=$this->check_review($admin,$rule);
        if($res){
            $this->redirect($rule,['id'=>$eid,'rstatus'=>2,'rdsc'=>'直接审核']);
        }
        $this->success('已提交修改');
    }
    /**
     * 产品编辑列表
     * @adminMenu(
     *     'name'   => '产品编辑列表',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品编辑列表',
     *     'param'  => ''
     * )
     */
    public function edit_list(){
        $table=$this->table;
        $m_edit=Db::name('edit');
        $flag=$this->flag;
        $data=$this->request->param();
        //查找当前表的编辑
        $where=['e.table'=>['eq',$this->table]];
        //状态
        if(empty($data['status'])){
            $data['status']=0;
        }else{
            $where['e.rstatus']=['eq',$data['status']];
        }
        //编辑人
        if(empty($data['aid'])){
            $data['aid']=0;
        }else{
            $where['e.aid']=['eq',$data['aid']];
        }
        //审核人
        if(empty($data['rid'])){
            $data['rid']=0;
        }else{
            $where['e.rid']=['eq',$data['rid']];
        }
        //所属分类
        if(empty($data['cid'])){
            $data['cid']=0;
        }else{
            $where['p.cid']=['eq',$data['cid']];
        }
        //查询字段
        $types=config($table.'_search');
        if(empty($types)){
            $types=config('base_search');
        }
        //选择查询字段
        if(empty($data['type1'])){
            $data['type1']=key($types);
        }
        //搜索类型
        $search_types=config('search_types');
        if(empty($data['type2'])){
            $data['type2']=key($search_types);
        }
        //检查拼接搜索语句
        if(empty($data['name'])){
            $data['name']='';
        }else{
            $where['p.'.$data['type1']]=zz_search($data['type2'],$data['name']);
        }
        //时间类别
        $times=config('time2_search');
        if(empty($data['time'])){
            $data['time']=key($times);
            $data['datetime1']='';
            $data['datetime2']='';
        }else{
            //时间处理
            if(empty($data['datetime1'])){
                $data['datetime1']='';
                $time1=0;
                if(empty($data['datetime2'])){
                    $data['datetime2']='';
                    $time2=0;
                }else{
                    //只有结束时间
                    $time2=strtotime($data['datetime2']);
                    $where['e.'.$data['time']]=['elt',$time2];
                }
            }else{
                //有开始时间
                $time1=strtotime($data['datetime1']);
                if(empty($data['datetime2'])){
                    $data['datetime2']='';
                    $where['e.'.$data['time']]=['egt',$time1];
                }else{
                    //有结束时间有开始时间between
                    $time2=strtotime($data['datetime2']);
                    if($time2<=$time1){
                        $this->error('结束时间必须大于起始时间');
                    }
                    $where['e.'.$data['time']]=['between',[$time1,$time2]];
                }
            }
        }
        $field='e.*,p.name as pname';
        $join=[
            ['cmf_'.$table.' p','e.pid=p.id','left'],
            ['cmf_user a','e.aid=a.id','left'],
            ['cmf_user r','e.aid=r.id','left'],
        ];
         
        $list=$m_edit
        ->alias('e')
        ->field('e.*,p.name as pname,a.user_nickname as aname,r.user_nickname as rname')
        ->join($join)
        ->where($where)
        ->order('e.rstatus asc,e.atime desc')
        ->paginate();
        
        // 获取分页显示
        $page = $list->appends($data)->render();
        $m_user=Db::name('user');
        //创建人
        $where_aid=[
            'user_type'=>1,
            'shop'=>1,
        ];
        $aids=$m_user->where($where_aid)->column('id,user_nickname');
        //审核人
        $where_rid=[
            'user_type'=>1,
            'shop'=>1,
        ];
        $rids=$m_user->where($where_rid)->column('id,user_nickname');
        //分类信息
        $this->cates();
        
        $this->assign('page',$page);
        $this->assign('list',$list);
        $this->assign('aids',$aids);
        $this->assign('rids',$rids);
        $this->assign('data',$data);
        $this->assign('types',$types);
        $this->assign('times',$times);
        $this->assign("search_types", $search_types);
        return $this->fetch();
    }
    
    /**
     * 产品审核详情
     * @adminMenu(
     *     'name'   => '产品审核详情',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品审核详情',
     *     'param'  => ''
     * )
     */
    public function edit_info()
    {
        $m=$this->m;
        $id=$this->request->param('id',0,'intval');
        $table=$this->table;
        //获取编辑信息
        $m_edit=Db::name('edit');
        $info1=$m_edit->where('id',$id)->find();
        if(empty($info1)){
            $this->error('编辑信息不存在');
        }
        //权限检测
        $admin=$this->admin;
        $actions=$this->auth_get($admin['id']);
        $this->assign('actions',$actions);
        
        //获取改变的信息
        $change=Db::name('edit_info')->where('eid',$id)->value('content');
        $change=json_decode($change,true);
        //获取原信息 
        
        $info=$m
        ->field('p.*,c.name as cname,c0.name as cname0,b.name as bname,t.name as tname,price.name as pname')
        ->alias('p')
        ->join('cmf_cate c','c.id=p.cid','left')
        ->join('cmf_cate c0','c0.id=p.cid0','left')
        ->join('cmf_brand b','b.id=p.brand','left')
        ->join('cmf_template t','t.id=p.template','left')
        ->join('cmf_price price','price.id=p.price','left')
        ->where('p.id',$info1['pid'])
        ->find();
        if(empty($info)){
            $this->error('原信息数据不存在');
        }
        
        //获取分类
        if(isset($change['cid'])){
            $cate=Db::name('cate')
            ->field('c.name,c.fid,c0.name as fname')
            ->alias('c')
            ->join('cmf_cate c0','c0.id=c.fid')
            ->where('c.id',$change['cid'])
            ->find();
           if(empty($cate)){
               $change['cname0']='不存在';
               $change['cname']='分类'.$change['cid'].'不存在';
           }else{
               $change['cname0']=$cate['fname'];
               $change['cname']=$cate['name'];
               $change['cid0']=$cate['fid'];
           }
            
        }
        
        //品牌
        if(isset($change['brand'])){
            $change['bname']=Db::name('brand')->where('id',$change['brand'])->value('name');
        }
        //参数模板
        if(isset($change['template'])){
            $change['tname']=Db::name('template')->where('id',$change['template'])->value('name');
        } 
        //价格模板
        if(isset($change['price'])){
            $change['pname']=Db::name('price')->where('id',$change['price'])->value('name');
        }
        
        //参数 
        $params1=[];
        $params0=[];
        $params01=[];
        $params11=[];
              
        //获取模板所有参数
        if($info['template']>0){
            $where=[
                'tp.t_id'=>$info['template'], 
            ];
            $params0=Db::name('template_param')
            ->alias('tp')
            ->join('cmf_param p','tp.p_id=p.id')
            ->where($where)
            ->order('p.sort asc')
            ->column('p.id,p.name,p.type,p.dsc');
            
            if(!empty($params0)){ 
                //获取设置的参数
                $params1=Db::name('goods_param')
                ->where('pid',$info['id'])
                ->column('param_id,value');
                //处理技术参数,没有获取参数值的要设置默认
                foreach($params0 as $k=>$v){ 
                    //没有设置的参数值要给空值
                    if(!isset($params1[$k])){
                        $params1[$k]='--';
                    } 
                }
            }
            
        }
        //修改的模板参数
        if(isset($change['param'])){
            $where=[
                'tp.t_id'=>$change['template'], 
            ];
            $params01=Db::name('template_param')
            ->alias('tp')
            ->join('cmf_param p','tp.p_id=p.id')
            ->where($where)
            ->order('p.sort asc')
            ->column('p.id,p.name,p.type,p.dsc');
            
            if(!empty($params01)){ 
                //获取设置的参数
                $params11=json_decode($change['param'],true);
                //处理技术参数,没有获取参数值的要设置默认
                foreach($params01 as $k=>$v){ 
                    //没有设置的参数值要给空值
                    if(!isset($params11[$k])){
                        $params11[$k]='--';
                    } 
                }
            }
        }  
        
        
        $this->assign('params0',$params0);
        $this->assign('params1',$params1);
        $this->assign('params01',$params01);
        $this->assign('params11',$params11);
       
        $this->assign('info',$info);
        $this->assign('info1',$info1);
      
        $this->assign('change',$change);
        
        return $this->fetch();
    }
    /**
     * 产品信息编辑审核
     * @adminMenu(
     *     'name'   => '产品编辑审核',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品编辑审核',
     *     'param'  => ''
     * )
     */
    public function edit_review()
    {
        //审核编辑的信息
        $status=$this->request->param('rstatus',0,'intval');
        $id=$this->request->param('id',0,'intval');
        if(($status!=2 && $status!=3) || $id<=0){
            $this->error('信息错误');
        }
        $m=$this->m;
        $table=$this->table;
        $m_edit=Db::name('edit');
        $info=$m_edit
        ->field('e.*,p.name as pname,a.user_nickname as aname')
        ->alias('e')
        ->join('cmf_'.$table.' p','p.id=e.pid')
        ->join('cmf_user a','a.id=e.aid')
        ->where('e.id',$id)
        ->find();
        if(empty($info)){
            $this->error('无效信息');
        }
        if($info['rstatus']!=1){
            $this->error('编辑信息已被审核！不能重复审核');
        }
        
        $admin=$this->admin;
        //其他店铺的审核判断
        if($admin['shop']!=1){
            if(empty($info['shop']) || $info['shop']!=$admin['shop']){
                $this->error('不能审核其他店铺的信息');
            }
        }
        $time=time();
        
        $m->startTrans();
        
        $update=[
            'rid'=>$admin['id'],
            'rtime'=>$time,
            'rstatus'=>$status,
        ];
        $review_status=$this->review_status;
        $rdsc=$this->request->param('rdsc');
        $update['rdsc']=(empty($rdsc))?$review_status[$status]:$rdsc;
        //只有未审核的才能更新
        $where=[
            'id'=>$id,
            'rstatus'=>1,
        ];
        $row=$m_edit->where($where)->update($update);
        if($row!==1){
            $m->rollback();
            $this->error('审核失败，请刷新后重试');
        }
        //是否更新,2同意，3不同意
        if($status==2){
            //组装更新数据
            $update_info=[
                'time'=>$time,
            ];
            //得到修改的字段
            $change=Db::name('edit_info')->where('eid',$id)->value('content');
            $change=json_decode($change,true);
            
            foreach($change as $k=>$v){
                $update_info[$k]=$v;
            }
            //得到原数据
            $info_tmp=$m->where('id',$info['pid'])->find();
            //修改了分类或编码
            if( (isset($update_info['cid']) || isset($update_info['code_num']))){
                 
                $cid=isset($update_info['cid'])?$update_info['cid']:$info_tmp['cid'];
                $code_num=isset($update_info['code_num'])?$update_info['code_num']:$info_tmp['code_num'];
                //检查编码是否合法
                $where=[
                    'code_num'=>$code_num,
                    'cid'=>$cid,
                    'id'=>['neq',$info['pid']],
                ];
                $tmp=$m->where($where)->find();
                if(!empty($tmp)){
                    $this->error('该编码已存在');
                }
                //产品编码和分类最大编码更新
                $m_cate=Db::name('cate');
                $cate=$m_cate->where('id',$cid)->find();
                $update_info['code']=$cate['code'].'-'.str_pad($code_num, 2,'0',STR_PAD_LEFT);
                $update_info['cid0']=$cate['fid'];
                if($update_info['cid0']<=0){
                    $this->error('分类错误');
                }
                if($cate['max_num']<$code_num){
                    $m_cate->where('id',$cid)->update(['max_num'=>$code_num]);
                }
                
            }
            //处理关联产品?好像不需要了
           /*  if(isset($update_info['id_links'])){
                $links=json_decode($update_info['id_links'],true);
                $type=isset($update_info['type'])?$update_info['type']:$info_tmp['type'];
                unset($update_info['id_links']);
                $links_add=[];
                
                foreach($links as $k=>$v){
                    $links_add[]=[
                        'pid0'=>$info['pid'],
                        'pid1'=>$k,
                        'num'=>$v,
                        'type'=>$type,
                    ];
                }
                Db::name('goods_link')->where('pid0',$info['pid'])->delete();
                if(!empty($links_add)){
                    Db::name('goods_link')->insertAll($links_add);
                }
            } */
            //加标产品同步
            if($info_tmp['type']==1){
                $fields_link=$this->fields_link;
                $m_lable=Db::name('goods_label');
                $links=$m_lable->where('pid1',$info_tmp['id'])->column('pid0');
                //组装加标产品更新数据
                $link_info=[];
                //得到要修改的字段 
                foreach($fields_link as $v){
                    if(isset($change[$v])){
                        $link_info[$v]=$change[$v];
                    }
                }
                if(!empty($link_info) && !empty($links)){
                    $link_info['time']=$time;
                    foreach($links as $v){
                        $m->where('id',$v)->update($link_info);
                    }
                }
            }
           
            //处理技术参数
            if(isset($update_info['param'])){
                $params=json_decode($update_info['param'],true);
                unset($update_info['param']);
                $param_add=[];
                //关联的标签产品也要修改 
                if(empty($links)){
                    $links=[$info['pid']]; 
                }else{
                    $links[]=$info['pid']; 
                }
                
                Db::name('goods_param')->where('pid','in',$links)->delete();
                foreach($links as $kk=>$vv){
                    foreach($params as $k=>$v){
                        $param_add[]=[
                            'pid'=>$vv,
                            'param_id'=>$k,
                            'value'=>$v,
                        ];
                    }
                } 
                if(!empty($param_add)){
                    Db::name('goods_param')->insertAll($param_add);
                } 
               
            }
            $row=$m->where('id',$info['pid'])->update($update_info);
            if($row!==1){
                $m->rollback();
                $this->error('信息更新失败，请刷新后重试');
            }
            
        }
         
        //审核成功，记录操作记录,发送审核信息
        
        //记录操作记录
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'审核'.$info['aid'].'-'.$info['aname'].'对'.($this->flag).$info['pid'].'-'.$info['pname'].'的编辑为'.$review_status[$status],
            'table'=>$table,
            'type'=>'edit_review',
            'pid'=>$info['pid'],
            'link'=>url('edit_info',['id'=>$info['id']]),
            'shop'=>$admin['shop'],
        ];
        
        zz_action($data_action,['aid'=>$info['aid']]);
        $m->commit();
        //添加收藏关联
        $this->goods_collect($info['pid'],$admin['id'],3);
        $this->success('审核成功');
    }
    /**
     * 产品编辑记录批量删除
     * @adminMenu(
     *     'name'   => '产品编辑记录批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品编辑记录批量删除',
     *     'param'  => ''
     * )
     */
    public function edit_del_all()
    {
        
         parent::edit_del_all();
    }
    /**
     * 产品批量删除
     * @adminMenu(
     *     'name'   => '产品批量删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品批量删除',
     *     'param'  => ''
     * )
     */
    public function del_all()
    {
        if(empty($_POST['ids'])){
            $this->error('未选中信息');
        }
        $ids=$_POST['ids'];
        
        $m=$this->m;
        $flag=$this->flag;
        $table=$this->table;
        $admin=$this->admin;
        $time=time();
        //彻底删除
        $where=['id'=>['in',$ids]];
        //其他店铺检查,如果没有shop属性就只能是1号主站操作,有shop属性就带上查询条件
        if($admin['shop']!=1){
            $tmp=$m
            ->where($where)
            ->find();
            if(empty($tmp['shop']) || $tmp['shop']!=$admin['shop']){
                $this->error('只能操作自己店铺的信息');
            }else{
                $where['shop']=['eq',$admin['shop']];
            }
        }
        
        //产品关联删除
        $m_link=Db::name('goods_link');
        //被关联的不能删除
        $where_link=[
            'pid1'=>['in',$ids],
        ];
        $tmp=$m_link->where($where_link)->find();
        if(!empty($tmp)){
            $this->error('有产品'.$tmp['pid1'].'与产品'.$tmp['pid0'].'关联，不能删除');
        }
        $m_label=Db::name('goods_label');
        $tmp=$m_label->where($where_link)->find();
        if(!empty($tmp)){
            $this->error('有产品'.$tmp['pid1'].'与产品'.$tmp['pid0'].'标签关联，不能删除');
        }
        $count=count($ids);
        $m->startTrans();
        $tmp=$m->where($where)->delete();
        if($tmp!==$count){
            $m->rollback();
            $this->error('删除数据失败，请刷新重试');
        }
         
        //删除关联编辑记录
        $where_edit=[
            'table'=>['eq',$table],
            'pid'=>['in',$ids],
        ];
        //现获取编辑id来删除info
        $eids=Db::name('edit')->where($where_edit)->column('id');
        if(!empty($eids)){
            Db::name('edit_info')->where(['eid'=>['in',$eids]])->delete();
            Db::name('edit')->where(['id'=>['in',$eids]])->delete();
        }
        //参数参数对应
        Db::name('goods_param')->where(['pid'=>['in',$ids]])->delete();
        
        //关联的删除
        $where_link=[
            'pid0'=>['in',$ids],
        ];
        if(!empty($where['shop'])){
            $where_link['shop']=$where['shop'];
        }
        $m_link->where($where_link)->delete();
        //删除标签
        $m_label->where($where_link)->delete();
        //删除收藏
        Db::name('goods_collect')->where('pid','in',$ids)->delete();
        
       
        //记录操作记录
        $idss=implode(',',$ids);
        $data_action=[
            'aid'=>$admin['id'],
            'time'=>$time,
            'ip'=>get_client_ip(),
            'action'=>$admin['user_nickname'].'批量删除'.$flag.'('.$idss.')',
            'table'=>$table,
            'type'=>'del',
            'link'=>'',
            'pid'=>0,
            'shop'=>$admin['shop'],
        ];
        
        zz_action($data_action,['pids'=>$idss]);
        $m->commit();
        $this->success('成功删除数据'.$tmp.'条');
        
    }
    //获取分类信息
    public function cates($type=3){
       
        $admin=$this->admin;
        
        if($type<3){
            $shop=$this->where_shop;
            if(empty($shop)){
                $where_shop=['eq',1];
            }elseif($admin['shop']==1){
                $where_shop=['in',[1,$shop]];
            }else{
                $where_shop=['eq',$shop];
            }
            //显示编辑人和审核人
            $m_user=Db::name('user');
            //可以加权限判断，目前未加
            //创建人
            $where_aid=[
                'user_type'=>1,
                'shop'=>$where_shop,
            ];
            
            $aids=$m_user->where($where_aid)->column('id,user_nickname');
            //审核人
            $where_rid=[
                'user_type'=>1,
                'shop'=>$where_shop,
            ];
            $rids=$m_user->where($where_rid)->column('id,user_nickname');
            $this->assign('aids',$aids);
            $this->assign('rids',$rids);
            
            //如果分店铺又是列表页查找,显示所有店铺
            if($admin['shop']==1 ){
                $shops=Db::name('shop')->where('status',2)->column('id,name');
                //首页列表页去除总站 
                $this->assign('shops',$shops);
            }
            
        }
        //品牌分类
        $bcates=config('chars');
        $where_brand=[
            'status'=>['eq',2],
        ];
        $brands=Db::name('brand')->where($where_brand)->order('char asc,sort asc')->column('id,name,char');
        $this->assign('bcates',$bcates);
        $this->assign('brands',$brands);
       
         
    }
     
    
    //获取价格模板
    public function prices(){
        $where=[
            'status'=>2,
        ];
        $prices=Db::name('price')->where($where)->order('sort asc,name asc')->column('id,name');
        $this->assign('prices',$prices);
        
    }
    //获取权限信息
    public function auth_get($id,$str='goods/AdminGoodsauth/'){
        $actions=[];
        //检测是否超级管理员
        if($id==1){
            $actions['auth']=1;
            return $actions;
        }
        $roles=Db::name('role_user')->where('user_id',$id)->column('role_id');
        //检测是否超级管理员
        if(in_array(1,$roles)){
            $actions['auth']=1;
        } else{
            $where=[
                'role_id'=>['in',$roles],
                'rule_name'=>['like',$str.'%'],
            ];
            $len=strlen($str)+1;
            $actions=Db::name('auth_access')->where($where)->column("substring(rule_name,$len)");
            $actions=array_flip($actions);
        }
        return $actions;
    }
    //检查产品参数
    public function param_check($data0){
        
        $data=[
            'cid'=>$data0['cid'],
            'code_num'=>intval($data0['code_num']),
            'code_name'=>$data0['code_name'],
            'name'=>$data0['name'],
            'sort'=>intval($data0['sort']),
            'dsc'=>$data0['dsc'],
            'type'=>$data0['type'],
            'sn_type'=>$data0['sn_type'],
            'sn'=>$data0['sn'],
            'name2'=>$data0['name2'],
            'name3'=>$data0['name3'],
            'brand'=>$data0['brand'],
            'bchar'=>$data0['bchar'],
           
        ];
        if(empty($data['code_num'])){
            return ('未添加编码');
        }
        if(empty($data['name'])){
            return ('名称不能为空');
        }
        if(!empty($data0['id'])){
            $data['id']=intval($data0['id']);
        }
        if(empty($data['brand'])){
           $data['bchar']='';
        }
        //补充分类和编码
        $cate=Db::name('cate')
        ->field('c.*,f.name as fname')
        ->alias('c')
        ->join('cmf_cate f','f.id=c.fid')
        ->where('c.id',$data['cid'])
        ->find();
        
        if(empty($cate) || $cate['fid']==0){
            return ('分类选择不合法');
        } 
        $data['cid0']=$cate['fid'];
        $data['code']=$cate['code'].'-'.str_pad($data['code_num'], 2,'0',STR_PAD_LEFT);
        $m=$this->m;
        $where=[
           'cid'=>$data['cid'],
           'code_num'=>$data['code_num'], 
        ];
        if(!empty($data['id'])){
            $where['id']=['neq',$data['id']];
        }
        $tmp=$m->where($where)->find();
        if(!empty($tmp)){
            return '编码已被占用';
        }
         //转化基本参数
        $data['weight0']=round($data0['weight0'],2);
        $data['length0']=round($data0['length0'],2);
        $data['width0']=round($data0['width0'],2);
        $data['height0']=round($data0['height0'],2);
        $data['is_box']=intval($data0['is_box']);
        
        $data['weight1']=round($data0['weight1'],2);
        $data['length1']=round($data0['length1'],2);
        $data['width1']=round($data0['width1'],2);
        $data['height1']=round($data0['height1'],2);
        
        if($data['weight0'] <= 0){
            $data['weight0']=0;
        } 
        if($data['length0'] <= 0){
            $data['length0']=0;
        }
        if($data['width0'] <= 0){
            $data['length0']=0;
        }
        if($data['height0'] <= 0){
            $data['length0']=0;
        }
        $data['size0']=bcmul($data['length0']*$data['width0'],$data['height0']);
        
        if($data['is_box']==2){
            $data['weight1']=$data['weight0'];
            $data['length1']=$data['length0'];
            $data['width1']=$data['width0'];
            $data['height1']=$data['height0'];
            $data['size1']=$data['size0'];
        }else{
            $data['weight1']=round($data['weight1'],2);
            $data['length1']=round($data['length1'],2);
            $data['width1']=round($data['width1'],2);
            $data['height1']=round($data['height1'],2);
            if($data['weight1'] <= 0){
                $data['weight1']=0;
            }
            if($data['length1'] <= 0){
                $data['length1'] = 0;
            }
            if($data['width1'] <= 0){
                $data['width1'] = 0;
            }
            if($data['height1'] <= 0){
                $data['height1'] =0;
            }
            $data['size1']=bcmul($data['length1']*$data['width1'],$data['height1']);
        }
        //检测价格权限 
        $admin=$this->admin;
        $actions=$this->auth_get($admin['id']);
        //所有权限
        if(isset($actions['auth'])){
            $all=1;
        }else{
            $all=0;
        }
         
        //template技术参数
        if($all || isset($actions['template_set'])){
            $data['template']=intval($data0['template']); 
        } 
        if($all || isset($actions['param_set'])){ 
            if(empty($data0['value'])){
                $data['value']=[];
            }else{
                $data['value']=$data0['value'];
            }
        }else{
            $data['value']=0;
        }
        //价格模板
        $data['price_sale']=round($data0['price_sale'],2);
        if($all || isset($actions['price_set'])){
            $data['price']=intval($data0['price']);
        }
        if($all || isset($actions['price_in_set'])){
            $data['price_in']=round($data0['price_in'],2);
        }
        if($all || isset($actions['price_cost_set'])){
            $data['price_cost']=round($data0['price_cost'],2);
        }
        if($all || isset($actions['price_min_set'])){
            $data['price_min']=intval($data0['price_min']);
        }
        if($all || isset($actions['price_range_set'])){
            $data['price_range1']=round($data0['price_range1'],2);
            $data['price_range2']=round($data0['price_range2'],2);
            $data['price_range3']=round($data0['price_range3'],2);
        }
        if($all || isset($actions['price_dealer1_set'])){
            $data['price_dealer1']=round($data0['price_dealer1'],2);
        }
        if($all || isset($actions['price_dealer2_set'])){
            $data['price_dealer2']=round($data0['price_dealer2'],2);
        }
        if($all || isset($actions['price_dealer3_set'])){
            $data['price_dealer3']=round($data0['price_dealer3'],2);
        }
        if($all || isset($actions['price_trade_set'])){
            $data['price_trade']=round($data0['price_trade'],2);
        }
        if($all || isset($actions['price_factory_set'])){
            $data['price_factory']=round($data0['price_factory'],2);
        }
       
        return $data;
    }
     //收藏
     public function goods_collect($pid,$uid,$type=1){
         $time=time();
         $m_collect=Db::name('goods_collect');
         
         $where=[
             'pid'=>$pid,
             'uid'=>$uid,
         ];
         $tmp=$m_collect->where($where)->find();
         if(empty($tmp)){ 
             $data=[
                 'pid'=>$pid,
                 'uid'=>$uid,
                 'type'=>$type,
                 'ctime'=>$time,
                 'time'=>$time,
             ];
             $m_collect->insert($data);
         }else{
             $m_collect->where('id',$tmp['id'])->update(['time'=>$time]);
         }
     }
}
