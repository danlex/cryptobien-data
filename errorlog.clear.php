<?php
    $file = 'error_log';
    if(file_exists($file)) {
        unlink($file);
    }
    