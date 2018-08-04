<?php
// | Copyright (c) 2018-2019 http://zz.zheng11223.top All rights reserved.
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// | Author: infinitezheng<infinitezheng@qq.com>
use think\Config;
use think\Db;
use think\Url;
 
// 应用公共文件
 
/**
 * 获取字符串首字母
 * @param $name 字符串
 */
function zz_first_char($str)
{
    //$char为获取字符串首个字符
    $char=mb_substr($str, 0, 1,'utf8');
    //字母直接大写返回
    if(preg_match('/[a-zA-Z]/', $char)){
        return strtoupper($char);
    }
    
    //ord获取ASSIC码值
    
    $fchar = ord($char);
    //为了兼容gb2312和utf8
    $s1 = iconv("UTF-8","gb2312", $char);
    $s2 = iconv("gb2312","UTF-8", $s1);
    
    //如果是utf8编码，则$s2=char,是gb2312则s1=char
    if($s2 == $char){$s = $s1;}else{$s = $char;}
    
    $asc = ord($s[0]) * 256 + ord($s[1]) - 65536;
    //('A', 45217, 45252),gb2312编码以拼音A开头的汉字编码为45217---45252
    
    if($asc >= -20319 and $asc <= -20284) return "A";
    if($asc >= -20283 and $asc <= -19776) return "B";
    if($asc >= -19775 and $asc <= -19219) return "C";
    if($asc >= -19218 and $asc <= -18711) return "D";
    if($asc >= -18710 and $asc <= -18527) return "E";
    if($asc >= -18526 and $asc <= -18240) return "F";
    if($asc >= -18239 and $asc <= -17923) return "G";
    if($asc >= -17922 and $asc <= -17418) return "H";
    if($asc >= -17417 and $asc <= -16475) return "J";
    if($asc >= -16474 and $asc <= -16213) return "K";
    if($asc >= -16212 and $asc <= -15641) return "L";
    if($asc >= -15640 and $asc <= -15166) return "M";
    if($asc >= -15165 and $asc <= -14923) return "N";
    if($asc >= -14922 and $asc <= -14915) return "O";
    if($asc >= -14914 and $asc <= -14631) return "P";
    if($asc >= -14630 and $asc <= -14150) return "Q";
    if($asc >= -14149 and $asc <= -14091) return "R";
    if($asc >= -14090 and $asc <= -13319) return "S";
    if($asc >= -13318 and $asc <= -12839) return "T";
    if($asc >= -12838 and $asc <= -12557) return "W";
    if($asc >= -12556 and $asc <= -11848) return "X";
    if($asc >= -11847 and $asc <= -11056) return "Y";
    if($asc >= -11055 and $asc <= -10247) return "Z";
    return false;
}

/**
 * 文件日志
 * @param $content 要写入的内容
 * @param string $file 日志文件,在web 入口目录
 */
function zz_log($content, $file = "log.txt")
{
    file_put_contents('log/'.$file, date('Y-m-d H:i:s').' '.$content."\r\n",FILE_APPEND);
}
/**
 * 数字补0
 * @param $num 传入的数字
 * @param $limit 补足的位数
 */
function zz_num($num, $limit=2)
{
    $len=$limit-strlen($num);
    if($len>0){ 
        for($i=0;$i<=$len;$i++){
            $num='0'.$num;
        }
    }
    return $num;
}
/**
 * 根据选择的条件拼接搜索
 * @param $num 传入的数字
 * @param $limit 补足的位数
 */
function zz_search($type,$name)
{ 
    switch ($type){
        case 1:
            return ['eq',$name]; 
        case 2:
            return ['like',$name.'%']; 
        case 3:
            return ['like','%'.$name];  
        default:
            return ['like','%'.$name.'%']; 
    }
}
/**
 * 根据目录删除文件和目录,
 * @param $dir 传入的目录
 * @param $rate 目录深度，暂时1级
 */
 function zz_dirdel($dir,$rate=1){
     return 1;
     $files=scandir($dir,1); 
     foreach($files as $v){
         if($v[0]=='.'){
             break;
         }
         if(is_file($dir.$v)){
             unlink($dir.$v);
         }
     }
     //报错？没权限?
     unlink($dir);
     return 1; 
}
/**
 * 清除不规范分隔符输入
 * @param $dsc 输入内容
 * @param $delimiter 分隔符
 */
function zz_delimiter($dsc,$delimiter=','){
    //清除不规范输入导致的空格
    $tmp=explode($delimiter, $dsc);
    foreach($tmp as $k=>$v){ 
        $tmp[$k]=trim($v);
        if($tmp[$k]===''){
            unset($tmp[$k]);
        }
    }
    
    return implode($delimiter, $tmp);
}
/**
 * 判断密码输入
 * @param $uid 用户id
 * @param string $psw 密码
 */
function zz_psw($uid,$psw){
    $psw_count=config('psw_count');
    $m_user=Db::name('user');
    $user=$m_user->where('id',$uid)->find();
    if($user['user_pass']!=session('user.user_pass')){
        session('user',null);
        return [0,'密码已修改，请重新登录',url('user/login/login')];
    }
    //登录失败6次锁定
    if($user['psw_fail']>=$psw_count){
        return [0,'密码错误已达'.$psw_count.'次，请重新登录',url('user/login/login')];
    }
    
    if(cmf_compare_password($psw, $user['user_pass'])){
        $m_user->where('id',$uid)->update(['psw_fail'=>0]);
        return [1];
    }else{
        $m_user->where('id',$uid)->setInc('psw_fail');
        
        return [0,'密码错误'.($user['psw_fail']+1).',连续'.$psw_count.'次将退出登录!',''];
    }
    
}
/**
 * curl函数封装
 * @param $url 网址
 * @param $data 数据
 */
function zz_curl($url, $data = null)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    if (!empty($data)){
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}


/**
 *过滤HTML得到纯文本
 * @param $list 列表
 * @param $len 截取文本长度
 */
function zz_get_content($list,$len=100){
    //过滤富文本
    $tmp=[];
    foreach ($list as $k=>$v){
        
        $content_01 = $v["content"];//从数据库获取富文本content
        $content_02 = htmlspecialchars_decode($content_01); //把一些预定义的 HTML 实体转换为字符
        $content_03 = str_replace("&nbsp;","",$content_02);//将空格替换成空
        $contents = strip_tags($content_03);//函数剥去字符串中的 HTML、XML 以及 PHP 的标签,获取纯文本内容
        $con = mb_substr($contents, 0, $len,"utf-8");//返回字符串中的前100字符串长度的字符
        $v['content']=$con.'...';
        $tmp[]=$v;
    }
    return $tmp;
}

 
/**
 *制作缩略图
 * @param $pic 原图片
 * @param $pic_new 新图片名
 */
function zz_set_image($pic,$pic_new,$width,$height,$thump=6){
    /* 缩略图相关常量定义 */
    //     const THUMB_SCALING   = 1; //常量，标识缩略图等比例缩放类型
    //     const THUMB_FILLED    = 2; //常量，标识缩略图缩放后填充类型
    //     const THUMB_CENTER    = 3; //常量，标识缩略图居中裁剪类型
    //     const THUMB_NORTHWEST = 4; //常量，标识缩略图左上角裁剪类型
    //     const THUMB_SOUTHEAST = 5; //常量，标识缩略图右下角裁剪类型
    //     const THUMB_FIXED     = 6; //常量，标识缩略图固定尺寸缩放类型
    //         $water=INDEXIMG.'water.png';//水印图片
    //         $image->thumb(800, 800,1)->water($water,1,50)->save($imgSrc);//生成缩略图、删除原图以及添加水印
    // 1; //常量，标识缩略图等比例缩放类型
    //         6; //常量，标识缩略图固定尺寸缩放类型
    $path=getcwd().'/upload/';
    //判断文件来源，已上传和未上传
    $imgSrc=(is_file($pic))?$pic:($path.$pic);
    
    $imgSrc1=$path.$pic_new;
    if(is_file($imgSrc)){
        $image = \think\Image::open($imgSrc);
        $size=$image->size();
        if($size!=[$width,$height] || !is_file($imgSrc1)){
            $image->thumb($width, $height,$thump)->save($imgSrc1);
        }
    }
    return $pic_new;
}

/**
 *组装图片
 * @param $pic  
 * @param $pic_old  
 */
function zz_picid($pic,$pic_old,$type,$id){
    $path=getcwd().'/upload/';
    //logo处理
    if(!is_file($path.$pic)){
        return 0;
    }
    //文件未改变
    if($pic==$pic_old){
        return $pic;
    }
    $size=config('pic_'.$type);
    $pic_new=$type.'/'.$id.'-'.time().'.jpg';
    
    $image = \think\Image::open($path.$pic);
    $image->thumb($size['width'],  $size['height'],6)->save($path.$pic_new);
    
    unlink($path.$pic);
    if(is_file($path.$pic_old)){
        unlink($path.$pic_old);
    }
    
    return $pic_new;
    
}
/** 
 * 为网址补加http://
 * @param $link   网址
 */
function zz_link($link){
    //处理网址，补加http://
    $exp='/^(http|ftp|https):\/\//';
    if(preg_match($exp, $link)==0){
        $link='http://'.$link;
    }
    return $link;
}