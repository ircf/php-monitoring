<?php
/**
 * PHP Monitoring index page : show services statuses
 */

require_once('functions.php');

header('content-type:text/plain');

$p = new PHPMonitoring();
try{
  $p->init();
  ob_start();
  foreach ($p->getServices() as $service){
    echo $p->printService($service);
    ob_flush();
    flush();
  }
}catch(Exception $e){
  $p->error($e->getMessage());
  echo $e->getMessage() . "\r\n";
}
