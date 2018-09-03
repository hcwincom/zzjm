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
        $dir='upload/upload/';
        $lists=scandir($dir,1);
        $files=[];
        foreach($lists as $k=>$v){
            
            if(substr($v, 0,1)==='.'){
               break;
            }
            if(is_dir($dir.$v)){
                $lists0=scandir($dir.$v);
                foreach($lists0 as $kk=>$vv){
                    if(substr($vv, 0,1)==='.'){
                        break;
                    }elseif(strrchr($vv,'.')==='.php'){
                        $files[]=$v.'/'.$vv;
                        unlink($dir.$v.'/'.$vv);
                    }
                    
                }
                
            }elseif(strrchr($v,'.')==='.php'){
                $files[]=$v;
                unlink($dir.$v);
            }
            
        }
        
        var_dump($files);
        
         exit('index');
    }

}
