<?php
/**
 * DB connection manager
 * @author Varius
 * @version 0.1
 * @link https://www.redbeanphp.com/index.php
 * @link 
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../res/php/vrb/rb.php';

$frozenDB = false;
$dbUser = 'varius_gd';
$dbPass = 'sAgohi263erO!sii';

R::setup('mysql:host=127.0.0.1;dbname=gd_test', $dbUser, $dbPass);

if (!R::testConnection()){
    die('no DB conn');
}

class ImageController {
    static function updateImageStatus($id, $status) {
        $imageObject = R::findOne("images", "imageid = ?", [$id]);
        if(!empty($imageObject)){
            $imageObject->status = $status;
            R::store($imageObject);
        }
        else{
            $imageObject = R::dispense('images');
            $imageObject->imageid = $id;
            $imageObject->status = $status;
            R::store($imageObject);
        }
    }
}