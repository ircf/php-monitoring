<?php
 /**
  * PHP monitoring functions
  */

class PHPMonitoring {

  const ALERT_FILE = 'alert.lock';
  const CONFIG_FILE = 'config.inc.php';
  const ERROR_LOG = 'error.log';
  const MAX_LOG_DATA = 10000;
  var $config;

  /**
   * Initialize PHP settings, load config file and reset results
   */
  function init(){
    ini_set('log_errors', 1);
    ini_set('error_log', dirname(__FILE__) . '/' . self::ERROR_LOG);
    global $config;
    if (!file_exists($this->getConfigFilePath())){
      throw new Exception("config file {$this->getConfigFilePath()} not found");
    }
    require_once $this->getConfigFilePath();
    $this->config = $config;
    $this->config['results'] = array();
    $this->checkInternet();
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
   * Check internet connection, throws an Exception if none
   */
  function checkInternet(){
    if (!isset($this->config['internet'])) throw new Exception('internet not configured');
    if (!$this->checkService($this->config['internet'], $this->config['try'])){
      throw new Exception("internet ({$this->config['internet']}) is down");
    }
  }

  /**
   * Check a service status
   */
  function checkService($service, $try = 1){
    list($ip, $port) = explode(':', $service);
    $this->config['results'][$service] = array();
    $fp = @fsockopen(
      $ip,
      $port,
      $this->config['results'][$service]['errno'],
      $this->config['results'][$service]['errstr'],
      $this->config['timeout']
    );
    if ($fp){
      @fclose($fp);
    }else{
      $this->error("$service is down ($try/{$this->config['try']})");
      if ($try < $this->config['try']){
        sleep($this->config['timeout']);
        return $this->checkService($service, $try + 1);
      }
    }
    return $this->config['results'][$service]['result'] = $fp;
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
    error_log('[INFO] ' . $msg);
  }
  function error($msg){
    error_log('[ERROR] ' . $msg);
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
  
  /**
   * Get log data
   */
  function getLogData(){
    date_default_timezone_set('UTC');
    $data = array();
    $file = new SplFileObject(self::ERROR_LOG);
    $file->seek(PHP_INT_MAX);
    $file->seek(max($file->key() - self::MAX_LOG_DATA, 0));
    while (!$file->eof()) {
      list($date, $time, $junk, $junk, $service) = $file->fgetcsv(' ');
      if (!isset($service)) continue;
      if (!isset($data[$service])) $data[$service] = array('name' => $service, 'visible' => false);
      $data[$service]['data'][] = array(strtotime(ltrim($date, '[') . ' ' . substr($time,0,5))*1000, 1);
    }
    ksort($data);
    return array_values($data);
  }
}
