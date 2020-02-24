<?php

/**
 * Debug function for showing variables
 */
function ddd($arr, $die = true, $json = false){
    if ($json) {
        echo json_encode($arr);
    }
    else {
        echo '<pre>' . print_r($arr, true) . '</pre>';
    }
    if($die) die();
}