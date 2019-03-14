<?php
set_time_limit(0);
error_reporting(E_ALL);
require 'binance-api.php';
require 'binance.key.php';
require 'api.controller.php';
$binanceApi = new BinanceApi($binanceKey['key'], $binanceKey['secret']);
$apiController = new ApiController($binanceApi);