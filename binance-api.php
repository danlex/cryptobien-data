<?php
     
class BinanceApi {
  protected $base = "https://www.binance.com/api/";
  protected $apiKey = "apiKey";
  protected $apiSecret = "apiSecret";
  protected $cacheInterval = 120;

  public function __construct( $apiKey, $apiSecret) {
    $this->apiKey = $apiKey;
    $this->apiSecret = $apiSecret;
  }

  public function getTickersCache() {
    $file = './cache/tickers/tickers';
    if (file_exists($file) && time() - filemtime($file) < $this->cacheInterval) {
      $tickers = json_decode(file_get_contents($file), true);
      if(!is_array($tickers)) {
        return [];
      }
    }

    $tickers = $this->computeTickers();
    return $this->setTickersCache($tickers);
  }

  private function setTickersCache($tickers) {
    $file = './cache/tickers/tickers';
    file_put_contents($file, json_encode($tickers));
    return $tickers;
  }

  private function computeTickers() {
    $writeTickers = [];
    $tickers = $this->ticker24hr();
    if(is_array($tickers)) {
      foreach($tickers as $key => &$ticker) {
        $ticker['coin'] = $this->getCoin($ticker);
        if($this->isBTCPair($ticker) || $this->isUSDT($ticker)) {
          $writeTickers[$key] = $ticker;
        }
      }
    }
    return $writeTickers;
  }

  private function ticker24hr() {
    return $this->request("v1/ticker/24hr");
  }

  public function getCandlesCache($symbol, $interval, $limit) {
    $file = "./cache/candles/candle-{$symbol}-{$interval}-{$limit}";
    if (file_exists($file) && time() - filemtime($file) < $this->cacheInterval) {
      $candles = json_decode(file_get_contents($file), true);
      if(!is_array($candles)) {
        return [];
      } else {
        return $candles;
      }
    } else {
      $candles = $this->computeCandles($symbol, $interval, $limit);
      $this->setCandlesCache($candles, $symbol, $interval, $limit);
      return $candles;
    };
  }

  private function setCandlesCache($candles, $symbol, $interval, $limit) {
    $writeTickers = [];
    $file = "./cache/candles/candle-{$symbol}-{$interval}-{$limit}";
    file_put_contents($file, json_encode($candles));
  }

  public function getCoin($ticker) {
    return str_replace('BTC', '', $ticker['symbol']);
  }

  private function isBTCPair($ticker) {
    return (strpos($ticker['symbol'], 'BTC') > 0);
  }

  public function isUSDT($ticker) {
    return $ticker['coin'] === 'USDT';
  }

  private function computeCandles($symbol, $interval = "5m", $limit = 10) {
    return $this->candlesticks($symbol, $interval, $limit);
  }

  public function candlesticks($symbol, $interval = "5m", $limit = 10)
  {
    $writeCandles = [];
    $candles =  $this->request("v1/klines", ["symbol" => $symbol, "interval" => $interval, "limit" => $limit]);
    if(!isset($candles[0][2])) {
      return [];
    }
    $writeCandles[0]['isGreen'] = true;
    $writeCandles[0]['median'] = ($candles[0][2] + $candles[0][3] + $candles[0][4])/3;
    $writeCandles[0]['volume'] = $candles[0][5];
    $writeCandles[0]['high'] = $candles[0][2];
    $writeCandles[0]['low'] = $candles[0][3];
    for($i = 1; $i < count($candles); $i ++) {
      $writeCandles[$i]['changePercent'] = 0;
      $writeCandles[$i]['volumePercent'] = 0;
      $writeCandles[$i]['volume'] = $candles[$i][5];
      $writeCandles[$i]['high'] = $candles[$i][2];
      $writeCandles[$i]['low'] = $candles[$i][3];

      $writeCandles[$i]['median'] = ($candles[$i][2] + $candles[$i][3] + $candles[$i][4])/3;
      if($writeCandles[$i]['median'] > $writeCandles[$i-1]['median']) {
        $writeCandles[$i]['isGreen'] = true;
      } else {
        $writeCandles[$i]['isGreen'] = false;
      }
      if(isset($writeCandles[$i]['median']) && $writeCandles[$i]['median'] > 0) {
        $writeCandles[$i]['changePercent'] = round(($writeCandles[$i]['median'] - $writeCandles[$i-1]['median']) / $writeCandles[$i]['median'] * 10000 , 0);
      }
      if(isset($candles[$i][5]) && $candles[$i][5] > 0) {
        $writeCandles[$i]['volumePercent'] = round(($candles[$i][5] - $candles[$i-1][5]) / $candles[$i][5] * 100 , 0);
      }
    }
    return $writeCandles;
  }

  public function httpRequest($url, $headers, $data = array(), $method = 'GET') {
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_USERAGENT, "User-Agent: Mozilla/4.0 (compatible; PHP Binance API)"); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    if ($method === 'POST') {
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    if($method === 'DELETE') {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_ENCODING, "");
    $content = curl_exec($ch);
    if (curl_errno($ch)) {
      $content = false;
    }
    curl_close($ch);
    
    return $content;
  }

  private function request($url, $params = []) {
    $headers = array("X-MBX-APIKEY: {$this->apiKey}");
    $query = http_build_query($params, '', '&');
    return json_decode($this->httpRequest($this->base . $url . '?' . $query, $headers), true);
  }
}
