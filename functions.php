<?php
 /**
  * PHP monitoring functions
  */

class PHPMonitoring {

  const ALERT_FILE = 'alert.lock';
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
    if (!file_exists($this->getConfigFilePath())){
      throw new Exception("config file {$this->getConfigFilePath()} not found");
    }
    require_once $this->getConfigFilePath();
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
  function checkService($service, $try = 1){
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
      $this->error("$service is down ($try/{$this->opts['try']})");
      if ($try < $this->opts['try']){
        sleep($this->opts['timeout']);
        return $this->checkService($service, $try + 1);
      }
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
   * Send a mail alert once per day
   */
  function alert(){
    if (
      !isset($this->config['alert'])
      || !is_array($this->config['alert'])
      || empty($this->config['alert'])
    ){
      throw new Exception('alert not configured');
    }
    if (file_exists($this->getAlertFilePath())){
      if (date('H', filemtime($this->getAlertFilePath())) != date('H')){
        unlink($this->getAlertFilePath());
      }else{
        return;
      }
    }
    $this->config['alert']['body'] = '';
    foreach ($this->getServices() as $service){
      $this->config['alert']['body'] .= $this->printService($service);
    }
    $this->mail($this->config['alert']);
    touch($this->getAlertFilePath());
  }
  
  /**
   * Send a mail using PHP PEAR library
   */
  function mail($opts = array()){
    if (!isset($opts['factory'])) throw new Exception('mail factory not set');
    if (!isset($opts['parameters'])) throw new Exception('mail parameters not set');
    if (!isset($opts['headers'])) throw new Exception('mail headers not set');
    if (!isset($opts['body'])) throw new Exception('mail body not set');
    require_once('Mail.php');
    $mail =& Mail::factory($opts['factory'], $opts['parameters']);
    if (isset($opts['localhost'])) $mail->localhost = $opts['localhost'];
    $mail->send($opts['headers']['To'], $opts['headers'], $opts['body']);
    if (PEAR::isError($mail)) {
      $this->error('could not sent mail : ' . $mail->getMessage());
    }
  }
  
  /**
   * Get config file path
   */
  function getConfigFilePath(){
    return dirname(__FILE__) . '/' . self::CONFIG_FILE;
  }
  
  /**
   * Get alert file path
   */
  function getAlertFilePath(){
    return dirname(__FILE__) . '/' . self::ALERT_FILE;
  }
  
  /**
   * Get services from config
   */
  function getServices(){
    return $this->config['services'];
  }
}
