<?php
 /**
  * PHP monitoring functions
  */

class PHPMonitoring {

  const CONFIG_FILE = 'config.inc.php';
  const ERROR_LOG = '/var/log/php-monitoring.log';
  var $config;

  /**
   * Initialize PHP settings, load config file and reset results
   */
  function init(){
    ini_set('log_errors', 1);
    ini_set('error_log', self::ERROR_LOG);
    global $config;
    if (!file_exists(self::CONFIG_FILE)){
      throw new Exception('config file not found');
    }
    require_once self::CONFIG_FILE;
    $this->config = $config;
    $this->config['results'] = array();
  }

  /**
   * Check all services statuses
   */
  function run(){
    if (
      !isset($this->config['services'])
      || !is_array($this->config['services'])
      || empty($this->config['services'])
    ){
      throw new Exception('services not configured');
    }
    $alert = false;
    foreach ($this->config['services'] as $service){
      $alert = $alert || !$this->checkService($service);
    }
    if ($alert){
      $this->alert();
    }
  }

  /**
   * Check a service status
   */
  function checkService($service){
    list($ip, $port) = explode(':', $service);
    $this->config['results'][$service] = array();
    $this->config['results'][$service]['result'] = @fsockopen(
      $ip,
      $port,
      $this->config['results'][$service]['errno'],
      $this->config['results'][$service]['errstr'],
      $this->config['timeout']
    );
    if (!$this->config['results'][$service]['result']){
      $this->error("$service is down");
    }
    return $this->config['results'][$service]['result'];
  }

  /**
   * Check and print a service status
   */
  function printService($service){
    if (!isset($this->config['results'][$service])){
      $this->checkService($service);
    }
    $result = "{$service} -> ";
    if ($this->config['results'][$service]['result']){
      $result .= "OK";
    }else{
      $result .= "ERROR {$this->config['results'][$service]['errno']} ({$this->config['results'][$service]['errstr']})";
    }
    $result .= "\r\n";
    return $result;
  }

  /**
   * Message logging
   */
  function info($msg){
    error_log(date('Y-m-d H:i:s') . ' [INFO] ' . $msg);
  }
  function error($msg){
    error_log(date('Y-m-d H:i:s') . ' [ERROR] ' . $msg);
  }
  
  /**
   * Send a mail alert
   */
  function alert(){
    if (
      !isset($this->config['alert'])
      || !is_array($this->config['alert'])
      || empty($this->config['alert'])
    ){
      throw new Exception('alert not configured');
    }
    foreach ($p->getServices() as $service){
      $body .= $p->printService($service);
    }
    $result = mail(
      $this->config['alert']['to'],
      $this->config['alert']['subject'],
      $body
    );
    if (!$result){
      $this->error('could not send alert');
    }
    return $result;
  }
  
  /**
   * Get services from config
   */
  function getServices(){
    return $this->config['services'];
  }
}
