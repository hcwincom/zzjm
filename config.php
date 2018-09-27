<?php	return array (
  'zzsite' => 
  array (
    'name' => '极敏',
    'keywords' => '极敏,genele',
    'desc' => '极敏是精密可靠仪器制造商',
    'admin' => 'adminadmin',
  ),
    'zztarget'=>[
        'list'=>'_self',
        'edit'=>'_self',
        'other'=>'_blank',
    ],
  'zzajax' => 'bb',
    'units'=>[
        1=>['克','毫米','立方毫米'],
        2=>['千克','厘米米','立方厘米'],
        3=>['千克','米','立方米'],
    ],
  'info_status' => 
  array (
    1 => '待审核',
    2 => '正常',
    3 => '不通过',
    4 => '禁用',
  ),
  'review_status' => 
  array (
    1 => '待审核',
    2 => '通过',
    3 => '不通过',
  ),
  'msg_status' => 
  array (
    1 => '未接收',
    2 => '未读',
    3 => '已读', 
  ),
  'msg_types' => 
  array (
    'msg' => '通知',
    'notice' => '公告',
      'add' => '创建',
      'edit' => '编辑',
      'review' => '状态审核',
      'review_all' => '批量同意',
      'edit_review' => '编辑审核',
      'del' => '删除',
      'edit_del' => '编辑记录删除',
  ),
    'action_types' =>
    array (
        'add' => '创建',
        'edit' => '编辑',
        'review' => '状态审核',
        'review_all' => '批量同意',
        'edit_review' => '编辑审核',
        'del' => '删除',
        'edit_del' => '编辑记录删除',
        
    ),
  'tables' => 
  array (
      
      
    'cate' => '产品分类',
    'goods' => '产品',
    'msg' => '消息',
    'brand' => '品牌',
    'param' => '技术参数',
    'template' => '参数模板',
    'fee' => '价格参数',
    'feecate' => '价格参数分类',
    'price' => '价格模板',
    'compare'=>'产品对比',
  'bank'=>'转账银行',
  'express'=>'快递种类',
      'freight'=>'配送公司',
  'shop'=>'加盟店铺',
  'area'=>'地区', 
  'store'=>'我的仓库',
  'company'=>'子公司',
  'custom'=>'客户',
  'customcate'=>'客户分类',
  ),
  
  'search_types' => 
  array (
    4 => '模糊搜索',
    1 => '精准搜索',
    2 => '头部搜索',
    3 => '尾部搜索',
  ),
  'time1_search' => 
  array (
    'atime' => '创建时间',
    'rtime' => '审核时间',
    'time' => '更新时间',
  ),
  'time2_search' => 
  array (
    'atime' => '创建时间',
    'rtime' => '审核时间',
  ),
  'file_type' => 
  array (
    1 => '极敏商城图片',
    2 => '产品实物图片',
    3 => '极敏logo图片',
    4 => '产品规格图',
    5 => '产品原理图',
    6 => '其他参考图片',
    7 => '产品说明书',
    8 => '其他文档',
  ),
  'image_type' => 
  array (
    0 => 1,
    1 => 2,
    2 => 3,
  ),
  'pic_size' => 
  array (
    1 => 
    array (
      0 => 70,
      1 => 70,
    ),
    2 => 
    array (
      0 => 330,
      1 => 330,
    ),
    3 => 
    array (
      0 => 660,
      1 => 660,
    ),
    4 => 
    array (
      0 => 150,
      1 => 150,
    ),
  ),
  'pic_brand' => 
  array (
    0 => 150,
    1 => 150,
    2 => 1,
  ),
  'cate_max' => '1000',
  'prices' => 
  array (
    1 => '入库价',
    2 => '出库价',
    3 => '发货价',
    4 => '零售价',
  ),
  'reg' => 
  array (
    'mobile' => 
    array (
      0 => '/(^(13\\d|15[^4\\D]|17[013678]|18\\d)\\d{8})$/',
      1 => '手机号码格式错误',
    ),
    'user_login' => 
    array (
      0 => '/^[0-9a-zA-Z]{2,20}$/',
      1 => '用户名只能是2-20位数字或英文字母组成',
    ),
  ),
  
  'user_search' => 
  array (
    'user_nickname' => '姓名',
    'user_login' => '用户名',
    'user_email' => '邮箱',
    'mobile' => '手机',
    'id' => '用户id',
  ),
    
  'goods_search' => 
  array (
    'name' => '全名',
    'code' => '编码',
    'code_name' => '同级名称',
    'name2' => '商城名称',
    'name3' => '打印名称',
    'id' => '产品id',
    'aid' => '创建人id',
    'rid' => '审核人id',
  ),
  'goods_type' => 
  array (
    1 => '基本产品',
    2 => '产品组合',
    3 => '加标产品',
    4 => '加工产品',
    5 => '设备',
  ),
  'sn_type' => 
  array (
    1 => '无条码',
    3 => '一货一码',
    2 => '有条码',
  ),
  'is_box' => 
  array (
    2 => '无内盒',
    1 => '有内盒',
  ),
  'chars' => 
  array (
    0 => 'A',
    1 => 'B',
    2 => 'C',
    3 => 'D',
    4 => 'E',
    5 => 'F',
    6 => 'G',
    7 => 'H',
    8 => 'I',
    9 => 'J',
    10 => 'K',
    11 => 'L',
    12 => 'M',
    13 => 'N',
    14 => 'O',
    15 => 'P',
    16 => 'Q',
    17 => 'R',
    18 => 'S',
    19 => 'T',
    20 => 'U',
    21 => 'V',
    22 => 'W',
    23 => 'X',
    24 => 'Y',
    25 => 'Z',
  ),
    'invoice_type'=>[
        0=>'不开票',
        1=>'增值税票',
        2=>'普通税票',
    ],
    'pay_type'=>[
        1=>'先付款后发货',
        2=>'先发货后付款',
        3=>'定期结算',
    ],
    'shelf_size'=>[
        'length'=>2,
        'width'=>0.5,
        'height'=>2,
        'floor'=>5,
    ],
  //数据库配置1
    'db_old' => [
        // 数据库类型
        'type'        => 'mysql',
        // 服务器地址
        'hostname'    => 'localhost',
        // 数据库名
        'database'    => 'genele',
        // 数据库用户名
        'username'    => 'root',
        // 数据库密码
        'password'    => 'root',
        // 数据库编码默认采用utf8
        'charset'     => 'utf8',
        // 数据库表前缀
        'prefix'      => 'sp_',
    ],
);