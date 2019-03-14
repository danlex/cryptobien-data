<?php

$file = 'error_log';
if(file_exists($file)) {
  echo('<pre>');
  echo(file_get_contents($file));
}
    

