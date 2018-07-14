<?php
 
namespace app\portal\controller;

use cmf\controller\HomeBaseController;
use PHPExcel_IOFactory;
use PHPExcel;
use PHPExcel_Cell_DataType;
use PHPExcel_Style_Border;

use PHPExcel_Worksheet_Drawing;
use PHPExcel_Style_Alignment;

class IndexController extends HomeBaseController
{

     
    public function index()
    {
        exit('index');
        //PHP_EOL不行
        //$line=PHP_EOL;
        $line="\n";
        $filename='数据字典'.date('Y-m-d-H-i-s').'.xls';
        $phpexcel = new PHPExcel();
        
        //设置文本格式
       $str=PHPExcel_Cell_DataType::TYPE_STRING; 
        $dname='genele';
       
        $m= mysqli_init();
        $m->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);//设置超时时间
        $m->real_connect('localhost','root','root',$dname,'3306');
       // $m=mysqli_connect('localhost','root','root',$dname,'3306');
        //查询表
        $tables=$m->query("show tables");
        $j=0;
//         $data = $tables->fetch_all(MYSQLI_ASSOC);
        while($tmp=($tables->fetch_assoc())){
            $table=$tmp['Tables_in_'.$dname];
            //外键还是有问题
//             $table='sp_xunpan';
            $creat=$m->query('show create table '.$table);
           
            $tmp_sql=$creat->fetch_assoc();
            if(empty($tmp_sql)){
                zz_log('empty$table'.$table);
                continue;
            }
           
            //创建数据库表结构语句
            $sql=$tmp_sql['Create Table'];
           
           /*  //"CREATE TABLE `sheet1` ( `id` int(4) DEFAULT NULL, `pid` int(3) 
            NOT NULL DEFAULT '0' COMMENT 'sgf', `area_name` varchar(45) DEFAULT NULL, 
            `area_type` int(1) DEFAULT NULL, `area_code` int(4) DEFAULT NULL,
            `area_postcode` int(6) DEFAULT NULL, `sort` int(2) DEFAULT NULL )
            ENGINE=MyISAM DEFAULT CHARSET=utf8 */
            //设置sheet
           
            $phpexcel->createSheet();
            $phpexcel->setActiveSheetIndex($j);
            $sheet= $phpexcel->getActiveSheet();
            //设置sheet表名
            $sheet->setTitle($table);
            
            // 所有单元格默认高度
            $sheet->getDefaultRowDimension()->setRowHeight(20);
            $sheet->getDefaultColumnDimension()->setWidth(20);
            //单个宽度设置
            $sheet->getColumnDimension('C')->setWidth(30);
            $sheet->getColumnDimension('D')->setWidth(30);
           
           //截取需要的字符串
            $first=strpos($sql,'(')+1;
            $end=strrpos($sql,')'); 
            $sql_end=trim(substr($sql,$end));
             
            $i=1;
            $sheet
            ->setCellValue('A'.$i, $j.'表'.$table)
            ->setCellValue('B'.$i, $sql_end);
            $i++;
            $sheet
            ->setCellValue('A'.$i, '字段名')
            ->setCellValue('B'.$i, '类型')
            ->setCellValue('C'.$i, '是否为空和默认值')
            ->setCellValue('D'.$i, '备注说明');
           
            
            $sql=trim(substr($sql,$first,$end-$first));
            
            $fields=explode(",".$line,$sql);
            
            foreach($fields as $k=>$v){
                $v=trim($v);
                
                $field_end=strpos($v,'`',1);
                $field=trim(substr($v,1,$field_end-1));
                 
                $type_end=strpos($v,')');
                $type=trim(substr($v,$field_end+1,$type_end-$field_end));
                
                $comment_start=strpos($v,'COMMENT');
                if($comment_start=== false){
                    $default=trim(substr($v,$type_end+1));
                    $comment='';
                }else{
                    $default=trim(substr($v,$type_end+1,$comment_start-$type_end-1));
                    $comment=trim(substr($v,$comment_start+8));
                }
                $i++;
                $sheet
                ->setCellValue('A'.$i, $field)
                ->setCellValue('B'.$i, $type)
                ->setCellValue('C'.$i, $default)
                ->setCellValue('D'.$i, $comment); 
            } 
          $j++;
         
        }
        //$m->colse();
        //在浏览器输出
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=$filename");
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        
        $objwriter = PHPExcel_IOFactory::createWriter($phpexcel, 'Excel5');
        $objwriter->save('php://output');
        
         exit('index');
    }

}
