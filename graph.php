<?php
/**
 * PHP Monitoring graph page : show services graphics
 */
 
require_once('functions.php');
$p = new PHPMonitoring();
?>
<!DOCTYPE html>
<html style="height: 100%;">
  <head>
    <title>PHP monitoring graph</title>
    <script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
    <script src="http://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
  </head>
  <body style="height: 100%;">
    <div id="container" style="width: 100%; height: 100%;"></div>
    <script>
      $(document).ready(function(){
        $('#container').highcharts({
          chart: {
            type: 'column',
            zoomType: 'x'
          },
          title: {
            text: 'PHP monitoring graph'
          },
          xAxis: {
            type: 'datetime',
            startOnTick: true,
            endOnTick: true,
            minTickInterval: 60000
          },
          yAxis: {
            title: {
              text: 'down'
            },
            min: 0,
            max: 1
          },
          plotOptions: {
            series: {
              pointPadding: 0,
              groupPadding: 0,
              borderWidth: 0,
              shadow: false
            }
          },
          legend: {
            layout: 'vertical',
            align: 'right',
            verticalAlign: 'middle',
            borderWidth: 0
          },
          series: <?= json_encode($tmp = $p->getLogData()) ?>
        });
      });
    </script>
  </body>
</html>
