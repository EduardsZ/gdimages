<?php
/**
 * This is a status API for getting status of picture creator
 * @version 0.1
 * @author Varius
 * @project test
 * @link 
 * 
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/jobtests/gdimages/generatetectangles/funcs.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/jobtests/gdimages/generatetectangles/db.php';

$get = $_GET;
$res = [];

if(!empty($get['id'])){
    $image = R::findOne('images', 'imageid = ?', [$get['id']]);
    if($image){
        $res['status'] = $image->status;
    }
    else{
        $res['status'] = 'failed';
        $res['reason'] = 'not_found';
    }
}

ddd($res, 1, 1);


echo '200 OK';
