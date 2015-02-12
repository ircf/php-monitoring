<?php
 /**
  * PHP monitoring cron script
  */

require_once('functions.php');

$p = new PHPMonitoring();

try{
  $p->init();
  $p->run();
}catch(Exception $e){
  $p->error($e->getMessage());
}
