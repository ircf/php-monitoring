<?php
 /**
  * PHP monitoring cron script
  */

$p = new PHPMonitoring();
try{
  $p->init();
  $p->run();
}catch(Exception $e){
  $p->log($e);
}
