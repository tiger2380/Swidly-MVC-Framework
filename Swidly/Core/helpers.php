<?php
use \Swidly\Core;

function dump($input, $stop = false): void
{
    // add line number from where this is called
    $trace = debug_backtrace();
    $line = $trace[0]['line'];
    $file = $trace[0]['file'];
    echo '<pre style="display: inline-block; background: rgba(0,0,0,0.8); color: white; padding: 1.4rem;">';
    echo '<span style="color: #FF0000;">'.$file.':'.$line.'</span><br/>';
    print_r($input);
    echo '</pre><br/>';
    
    if($stop) {
        exit();
    }
}

function dd($input): void {
    dump($input, true);
}

function stripQuestionMarks($value): array|string
{
    return str_replace('?', '', $value);
}

function hex2rgb($value = '#000000'): array
{
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

function rgb2hex($rgb): string
{
    $hex = "#";
    $hex .= str_pad(dechex($rgb[0]), 2, "0", STR_PAD_LEFT);
    $hex .= str_pad(dechex($rgb[1]), 2, "0", STR_PAD_LEFT);
    $hex .= str_pad(dechex($rgb[2]), 2, "0", STR_PAD_LEFT);

    return $hex;
}

function getTheme(): array
{
    $theme = Core\Store::get('theme');

    if($theme) {
        return $theme;
    } else {
        return load_default_theme();
    }
}

/**
 * @param string $image
 * @param string $watermark
 * @param string $position
 * @return bool|string
 */
function watermark_image($image, $watermark, $position = 'bottom-right') {
    $filename = basename($image);
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if($original = imagecreatefromstring(file_get_contents($image))) {
        $watermark = imagecreatefromstring(file_get_contents($watermark));
        $watermark_width = imagesx($watermark);
        $watermark_height = imagesy($watermark);

        switch($position) {
            case 'top-left':
                $dest_x = 0;
                $dest_y = 0;
                break;
            case 'top-right':
                $dest_x = imagesx($original) - $watermark_width;
                $dest_y = 0;
                break;
            case 'bottom-left':
                $dest_x = 0;
                $dest_y = imagesy($original) - $watermark_height;
                break;
            case 'bottom-right':
                $dest_x = imagesx($original) - $watermark_width;
                $dest_y = imagesy($original) - $watermark_height;
                break;
            default:
                $dest_x = imagesx($original) - $watermark_width;
                $dest_y = imagesy($original) - $watermark_height;
                break;
        }

        imagealphablending($original, true);
        imagealphablending($watermark, true);
        imagecopy($original, $watermark, $dest_x, $dest_y, 0, 0, $watermark_width, $watermark_height);

        switch($ext) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($original, null, 100);
            case 'png':
                imagesavealpha($original, true);
                imagepng($original, null);
                break;
            case 'gif':
                imagegif($original, null);
                break;
            default:
                return $filename;
                break;
        }

        imagedestroy($original);
        imagedestroy($watermark);
    } else {
        return false;
    }
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

function load_default_theme(): array
{
    return [
        'color' => hex2rgb('#EEEEEE'),
        'layout' => 'default',
        'logo' => 'https://cdn.pixabay.com/photo/2016/11/07/13/04/yoga-1805784_1280.png',
    ];
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