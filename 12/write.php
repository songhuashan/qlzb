<?php
header("content-type:text/html;charset=utf-8");
	 
function write($cont){

   //时间
   $data=date("Y-m-d h:i:s",time());
   $condition=$cont."------".$data;
   $path = "./toinlog/";
   if (!is_dir($path)){
    mkdir($path,0777);  // 创建文件夹test,并给777的权限（所有权限）
     }
   $content = $condition."\r\n";  // 写入的内容
   $file = $path."log.txt";    // 写入的文件
   file_put_contents($file,$content,FILE_APPEND);  // 最简单的快速的以追加的方式写入写入方法，

   }
?>