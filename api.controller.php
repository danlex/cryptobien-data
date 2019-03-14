<?php

class ApiController {
  public function __construct($apiBinance) {
      $this->apiBinance = $apiBinance;
    }

    public function serve($params) {
      header('Content-Type: application/json');
      $data = array(
        'data' => $this->getServeData($params),
        'errorMessage'=> '',
        'error'=> false,
        'time'=> time(),
        'timeFormat'=> date('Y-m-d H:i:s')
      );
      echo(json_encode($data));
    }

    private function getServeData($params) {
     switch($params['method']) {
        case 'getTickersCache':
          return $this->getTickersCache();
        case 'getCandlesCache':
          return $this->getCandlesCache($params['symbol'], $params['interval'], $params['limit']);
        default:
          return 'no path';
      }
    }

    private function getTickersCache() {
      return $this->apiBinance->getTickersCache();
    }

    private function getCandlesCache($symbol, $interval, $limit) {
      return $this->apiBinance->getCandlesCache($symbol, $interval, $limit);
    }
}