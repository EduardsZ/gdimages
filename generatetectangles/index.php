<?php
/**
 * @version 0.1
 * @author Varius
 * @project test
 * @link https://stackoverflow.com/questions/13390333/two-rectangles-intersection
 * 
 */

require_once __DIR__ . '/vars.php';
require_once __DIR__ . '/funcs.php';
// require_once __DIR__ . '/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/jobtests/gdimages/generatetectangles/db.php';


$decoded_params = "";
$params_errors = [];
$rectangle_names = [];
$response = [];
$imageWidth = 0;
$imageHeight = 0;
$processStatus = 'pending';

ImageController::updateImageStatus($imageName, $processStatus);

$json_params = file_get_contents("php://input");

if (strlen($json_params) > 0 && isValidJSON($json_params)){
  $decoded_params = json_decode($json_params);
} else {
  $params_errors['malformatted_json'][] = 'No valid JSON data';
}

if(isset($decoded_params->width)) {

  if(checkImageDimension($decoded_params->width, $imgMinWidth, $imgMaxWidth)){
    $imageWidth = $decoded_params->width;
  } else {
    $params_errors['image_doesnt_fit_constraints']['width'] = $imageWidth;
  }
} else {
  $params_errors['malformatted_json'][] = 'No width provided in input data';
}

if(isset($decoded_params->height)) {
  if(checkImageDimension($decoded_params->height, $imgMinHeight, $imgMaxHeight)){
    $imageHeight = $decoded_params->height;
  } else {
    $params_errors['image_doesnt_fit_constraints']['height'] = $imageHeight;
  }
} else {
  $params_errors['malformatted_json'][] = 'No height provided in input data';
}

if(!isset($decoded_params->color)) {
  $params_errors['malformatted_json'][] = 'No color provided in input data';
}

if(isset($decoded_params->rectangles)) {
  $rectangles = $decoded_params->rectangles;

  // IF RECTANGLES ARE HERE...
  if($rectangles){
    $rCount = count($rectangles);
    if($rCount > 0){
      foreach($rectangles as $rectangle){
        if(isThereArectangleInside($rectangle, $imageWidth, $imageHeight)) {
          // RECTANGLE IS INSIDE IMAGE :)
          
        } else {
          $params_errors['rectangles_out_of_bounds'][] = $rectangle->id;
        }
      }
    }
    
    if ($rCount > 1){
      for($recIter = 0; $recIter < $rCount - 1; $recIter++){
        
        for($curRect = $recIter+1; $curRect < $rCount; $curRect++){
          $firstX = $rectangles[$recIter]->x;
          $firstY = $rectangles[$recIter]->y;
          $firstW = $rectangles[$recIter]->width;
          $firstH = $rectangles[$recIter]->width;
          $secondX = $rectangles[$curRect]->x;
          $SecondY = $rectangles[$curRect]->y;
          $secondW = $rectangles[$curRect]->width;
          $SecondH = $rectangles[$curRect]->height;

          if(rectangle_collision($firstX,$firstY,$firstW,$firstH,$secondX,$SecondY,$secondW,$SecondH)){
            $params_errors['rectangles_overlap'][] = [$rectangles[$recIter]->id, $rectangles[$curRect]->id];
          }
        }
      }
    }
  }
}
else {
  // THIS NOT VALID?
  // $params_errors[] = 'No any rectangle provided in input data.';
}

// CHECKING FOR ANY ERROR
if(empty($params_errors)) {
  $processStatus = 'in_progress';
  ImageController::updateImageStatus($imageName, $processStatus);
  if(drawImage($decoded_params, $imageName)){
    $processStatus = 'done';
    ImageController::updateImageStatus($imageName, $processStatus);
    $response['success'] = true;
    $response['id'] = $imageName;
  }else{
    $processStatus = 'failed';
    ImageController::updateImageStatus($imageName, $processStatus);
    $response['success'] = false;
    $params_errors['Image creation failure'] = 'failed to create image';
  };
} else {
  $processStatus = 'failed';
  ImageController::updateImageStatus($imageName, $processStatus);
  $response['success'] = false;
  $response['errors'] = $params_errors;
}
ddd($response, 1, 1);


//  F U N C T I O N S

/**
 * This function detects two rectangles collision using their coordinates and dimensions
 * @link https://silentmatt.com/rectangle-intersection/
 */
function rectangle_collision($x_1, $y_1, $width_1, $height_1, $x_2, $y_2, $width_2, $height_2)
{
  return ($x_1 < ($x_2 + $width_2) && ($x_1 + $width_1) > $x_2 && $y_1 < ($y_2 + $height_2) && ($y_1 + $height_1) > $y_2);
}

/**
 * This function detects rectangles collision with image borders
 */
function isThereArectangleInside($rectangle, $width, $height){
  try {
    $rPosX   = $rectangle->x;
    $rPosY   = $rectangle->y;
    $rWidth  = $rectangle->width;
    $rHeight = $rectangle->height;
    return (intval($rPosX) > 0 and intval($rPosY) > 0 and (intval($rPosX) + intval($rWidth)) < intval($width) and (intval($rPosY) + intval($rHeight)) < intval($height));
  }
  catch (Exception $e) {
    return false;
  }
}

/**
 * This function checks image dimensions
 */
function checkImageDimension($size, $min = 0, $max = 3840) {
  return $size > $min && $size < $max;
}



/**
 * this function draws image
 */
function drawImage($image, $imgName){
  try{
    $IMG = @imagecreate( $image->width, $image->height ) or die();
    $background = imagecolorallocate($IMG, 0,0,255);
    if(isset($image->rectangles)){
        // ddd(hexToRgb($image->rectangles[2]->color), 0);
        foreach($image->rectangles as $rect){
          // ddd(['colors: r, g, b',hexToRgb($rect->color)['r'],hexToRgb($rect->color)['g'],hexToRgb($rect->color)['b']], 0);
          $curRectX1 = $rect->x;
          $curRectY1 = $rect->y;
          $curRectX2 = $rect->x + $rect->width;
          $curRectY2 = $rect->y + $rect->height;
          // ddd([$rect->id, $curRectX1,$curRectY1,$curRectX2,$curRectY2], 0);
          $colors = hexToRgb($rect->color);
          $color = imagecolorallocate($IMG,$colors['r'],$colors['g'],$colors['b']);
          imagefilledrectangle ($IMG,   $curRectX1,  $curRectY1, $curRectX2, $curRectY2, $color);
        }
      }
      imagepng($IMG,$imgName.time().".png");
      imagecolordeallocate($IMG, $color);
      imagecolordeallocate($IMG, $background );
      imagedestroy($IMG);
      return true;
  }
  catch(Exception $e){
    return false;
  }
}

/**
 * This function parses input json data
 */
function isValidJSON($str) {
  json_decode($str);
  return json_last_error() == JSON_ERROR_NONE;
}

/**
 * This function parses hex color to RGB
 */
function hexToRgb($hex, $alpha = false) {
  $hex      = str_replace('#', '', $hex);
  $length   = strlen($hex);
  $rgb['r'] = hexdec($length == 6 ? substr($hex, 0, 2) : ($length == 3 ? str_repeat(substr($hex, 0, 1), 2) : 0));
  $rgb['g'] = hexdec($length == 6 ? substr($hex, 2, 2) : ($length == 3 ? str_repeat(substr($hex, 1, 1), 2) : 0));
  $rgb['b'] = hexdec($length == 6 ? substr($hex, 4, 2) : ($length == 3 ? str_repeat(substr($hex, 2, 1), 2) : 0));
  if ( $alpha ) {
     $rgb['a'] = $alpha;
  }
  return $rgb;
}
