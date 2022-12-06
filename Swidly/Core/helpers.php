<?php
use \Swidly\Swidly;

function dump($input, $stop = false) {
    echo '<pre style="display: inline-block; background: rgba(0,0,0,0.8); color: white; padding: 1.4rem;">';
    print_r($input);
    echo '</pre><br/>';
    
    if($stop) {
        exit();
    }
}

function stripQuestionMarks($value) {
    return str_replace('?', '', $value);
}

function hex2rgb($value = '#000000') {
    $cleanHex = ($value[0] === '#' ? substr($value, 1, strlen($value)) : $value);

    $r = intval(substr($cleanHex, 0, 2), 16);
    $g = intval(substr($cleanHex, 2, 4), 16);
    $b = intval(substr($cleanHex, 4, 6), 16);

    return [
        $r,
        $g,
        $b
    ];
}

function resize_image($image, $width, $height) {
    $filename = basename($image);
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    list($img_width, $img_height) = getimagesize($image);
    $ratio = $img_width / $img_height;

    if($width/$height > $ratio) {
        $width = $height*$ratio;
    } else {
        $height = $width/$ratio;
    }

    if($original = imagecreatefromstring(file_get_contents($image))) {
        $destination = imagecreatetruecolor($width, $height);
        imagealphablending($destination, false);
        imagecopyresampled($destination, $original, 0, 0, 0, 0, $width, $height, $img_width, $img_height);

        switch($ext) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($destination, null, 100);
            case 'png':
                imagesavealpha($destination, true);
                imagepng($destination, null);
                break;
            case 'gif':
                imagegif($destination, null);
                break;
            default:
                return $filename;
                break;
        }

        imagedestroy($destination);
        imagedestroy($original);
    } else {
        return false;
    }
}

function load_default_theme() {
    $default_theme = [
        'color' => hex2rgb('#EEEEEE'),
        'layout' => 'default',
        'logo' => 'https://cdn.pixabay.com/photo/2016/11/07/13/04/yoga-1805784_1280.png',
    ];

    return $default_theme;
}

function parseArray(array $original_array, $prefix = null): array {
    $parsedArray = [];

    foreach ($original_array as $key => $value) {
        if (is_array($value)) {
            $_prefix = isset($prefix) ? $prefix.'::' : '';
            $parsedArray = array_merge($parsedArray, parseArray($value, $_prefix.$key));
        } else {
            if(isset($prefix)) {
                $parsedArray[$prefix.'::'.$key] = $value;
            } else {
                $parsedArray[$key] = $value;
            }
        }
    }

    return $parsedArray;
}