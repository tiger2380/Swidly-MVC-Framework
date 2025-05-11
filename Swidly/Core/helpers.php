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

function resizeImageWebP($image, $width, $height) {
    $filename = basename($image);
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $dir = dirname(realpath($image));

    if (filter_var($image, FILTER_VALIDATE_URL) == false) {
        $image = realpath($image);
        $newImage = $dir.'/'.$filename.'.webp';
    } else {
        $newImage = $image;
    }

    list($img_width, $img_height) = getimagesize($image);

    if (file_exists($dir.'/'.$newImage)) {
        list($w, $h) = getimagesize($newImage);
        if ($w == $width && $h == $height) {
            return $newImage;
        }
    }

    $ratio = $img_width / $img_height;

    if($width/$height > $ratio) {
        $width = $height*$ratio;
    } else {
        $height = $width/$ratio;
    }

    if($original = imagecreatefromstring(file_get_contents($image))) {
        $destination = imagecreatetruecolor($width, $height);
        imagealphablending($destination, false);

        if ($ext === 'png') {
            imagesavealpha($destination, true);
        }
        imagecopyresampled($destination, $original, 0, 0, 0, 0, $width, $height, $img_width, $img_height);

        try {
            imagewebp($destination, $newImage, 100);
            return $newImage;
        } catch(Exception $ex) {
            dd($ex->getMessage());
        } finally {
            imagedestroy($destination);
            imagedestroy($original);
        }
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

function findBlockById($blocks, $id) {
    foreach ($blocks as $block) {
        if ($block->id === $id) {
            return $block; // Return the block if the ID matches
        }
    }
    return null; // Return null if the block is not found
}

function JsonContentToHtml($jsonContent): void {
    $data = json_decode($jsonContent, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }

    $weights = [
        'hero' => 1,
        'about' => 2,
        'products' => 3,
        'gallery' => 4,
        'testimonials' => 5,
        'investors' => 6,
        'newsletter' => 7,
        'contact' => 8
    ];

    $blocks = $data['blocks'];
    foreach ($weights as $section => $weight) {
        if (isset($blocks[$section])) {
            $blocks[$section]['weight'] = $weight;
        }
    }

    uasort($blocks, function($a, $b) {
        $weightA = isset($a['weight']) ? $a['weight'] : PHP_INT_MAX;
        $weightB = isset($b['weight']) ? $b['weight'] : PHP_INT_MAX;
        return $weightA - $weightB;
    });

    $newBlocks = [];
    foreach ($blocks as $section => $value) {
        foreach ($value as $one_value) {
            $newBlocks[$section][] = json_decode(json_encode($one_value));
        }
    }

    renderArrayAsHtml($newBlocks, null, 0, 0);
}

function renderArrayAsHtml($data, $parent_item = null, $item_index = 0, $block_index = null): void {
    if ($item_index === 0) {
        echo '<div class"accordion" id="sectionsAccordion">';
    }
    foreach ($data as $section => $item) {
        if (isset($item) && is_array($item)) {
            if ($item_index > 0) {
                echo '
                                
                            </div>
                            </div>
                        </div>';
            }
            echo '
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="'. $section .'Header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#'.$section.'Content" aria-expanded="true" aria-controls="'.$section.'Content">
                                '. $section .'
                            </button>
                        </h2>
                        <div id="'. $section .'Content" class="accordion-collapse collapse" aria-labelledby="'. $section .'Header" data-bs-parent="#sectionsAccordion">
                            <div class="accordion-body">

            ';
            renderArrayAsHtml($item, $section, ++$item_index, ++$block_index);
            continue;
        }
        $type = $item->type ?? null;

        if ($type === 'image') {
            echo "<div class='block-wrapper mb-4'>";
            echo "<div class='block-label form-label'>{$item->name}</div>";
            echo "<div class='image-block d-flex justify-content-center align-items-center mb-2' style='width: 100%; height: 200px; background-repeat: no-repeat; background-image: url(" . $item->data->file->url . "); background-size: contain;'></div>";
            echo "<div class=''><input type='text' class='form-control' value='{$item->data->file->url}' /></div>";
            echo "<div class=''><input type='text' class='form-control' value='{$item->data->alt}' /></div>";
            echo "<button class='btn btn-primary border-radius'>replace</button>";
            echo "</div>";
        }

        if ($type === 'paragraph') {
            echo "<div class='block-wrapper mb-4'>";
            echo "<div class='block-label'>{$item->name}</div>";
            echo "<div class=''><textarea class='form-control' id='{$item->id}' rows='5'>{$item->data->text}</textarea></div>";
            echo "</div>";
        }

        if ($type === 'header') {
            $levels = ['1' => 'H1', '2' => 'H2', '3' => 'H3', '4' => 'H4', '5' => 'H5', '6' => 'H6'];
            echo "<div class='block-wrapper mb-4'>";
            echo "<div class='block-label'>{$item->name}</div>";
            echo "<div class=''><input class='form-control' id='{$item->id}' type='text' value='{$item->data->text}' /></div>";
            echo "<div class='block-label'>
                <select class='form-control' id='{$item->id}'>";
                    foreach ($levels as $level => $label) {
                        echo "<option value='$level' " . ($item->data->level === $level ? 'selected' : '') . ">$label</option>";
                    }
            echo "</select>
            </div>";
            echo "</div>";
        }

        if ($type === 'list') {
            echo "<div class='block-wrapper mb-4'>";
            echo "<div class='block-label'>{$item->name}</div>";
            echo "<div class='list-block'>";
            echo "<ul>";
            foreach ($item->data->items as $item) {
                echo "<li>{$item->text}</li>";
            }
            echo "<a href='#' class='btn btn-primary'>Add Item</a>";
            echo "</ul>";
            echo "</div>";
        }

        if ($type === 'button') {
            $controller = new \Swidly\Core\Controller();
            $model = $controller->getModel('PagesModel');
            $pages = $model->findAll(100, ['active' => 1]);

            echo "<div class='block-wrapper mb-4'>";
            echo "<div class='block-label'>{$item->name}</div>";
            echo "<div class='button-block'>";
            echo "<input type='text' class='form-control' value='{$item->data->text}' />";
            echo "<select class='form-control' id='{$item->id}'>";
            foreach ($pages as $page) {
                echo "<option value='{$page->id}' " . ($item->data->link === $page->slug ? 'selected' : '') . ">{$page->slug}</option>";
            }
            echo "</select>";
            echo "</div>";
            echo "</div>";
        }

        if ($type === 'boolean') {
            echo "<div class='block-wrapper mb-4'>";
            echo '<div class="form-check form-switch">';
            echo '<input class="form-check-input" type="checkbox" id="'.$item->id.'" '. ($item->data->value ? 'checked' : '') .'>';
            echo '<label class="form-check-label" for="'.$item->id.'">'. $item->name .'</label>';
            echo '</div>';
            echo "</div>";
        }


    }
    if ($item_index === 0)
        echo '</div>';
}

/**
 * Fetches content from a URL and generates blocks from specific selectors
 * @param string $url The URL to fetch content from
 * @param array $selectors CSS selectors to target specific elements
 * @return array Array of block objects ready for JSON encoding
 */
function generateBlocksFromURLWithSelectors($html, $selectors = []) {
    // Default selectors if none provided
    if (empty($selectors)) {
        $selectors = [
            'heading' => 'h1, h2, h3, h4, h5, h6',
            'paragraph' => 'p',
            'button' => 'a.btn, button',
            'link' => 'a:not(.btn)',
            'image' => 'img'
        ];
    }

    if (!$html) {
        return [
            'error' => true,
            'message' => 'Failed to fetch content from URL'
        ];
    }

    // Use DOMDocument and DOMXPath for more precise element selection
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new \DOMXPath($dom);
    $blocks = [];

    // Process headings
    if (isset($selectors['heading'])) {
        $headings = $xpath->query('//' . str_replace(', ', ' | //', $selectors['heading']));
        /** @var DOMElement $heading */
        foreach ($headings as $heading) {
            $tag = strtolower($heading->tagName);
            $level = (int)substr($tag, 1); // Extract number from h1-h6

            $blockId = $heading->getAttribute('id') ?: generateUniqueId();
            $blocks[] = [
                'id' => $blockId,
                'type' => 'header',
                'data' => [
                    'text' => $heading->textContent,
                    'level' => $level
                ]
            ];
        }
    }

    // Process paragraphs
    if (isset($selectors['paragraph'])) {
        $paragraphs = $xpath->query('//' . str_replace(', ', ' | //', $selectors['paragraph']));
        foreach ($paragraphs as $paragraph) {
            /** @var DOMElement $paragraph */
            $blockId = $paragraph->getAttribute('id') ?: generateUniqueId();
            $blocks[] = [
                'id' => $blockId,
                'type' => 'paragraph',
                'data' => [
                    'text' => $paragraph->textContent
                ]
            ];
        }
    }

    // Process buttons
    if (isset($selectors['button'])) {
        $buttons = $xpath->query('//' . str_replace(', ', ' | //', $selectors['button']));
        foreach ($buttons as $button) {
            /** @var DOMElement $button */
            $blockId = $button->getAttribute('id') ?: generateUniqueId();
            $blocks[] = [
                'id' => $blockId,
                'type' => 'button',
                'data' => [
                    'text' => $button->textContent,
                    'url' => $button->getAttribute('href'),
                    'style' => $button->getAttribute('class')
                ]
            ];
        }
    }

    // Process links
    if (isset($selectors['link'])) {
        $links = $xpath->query('//' . str_replace(', ', ' | //', $selectors['link']));
        foreach ($links as $link) {
            /** @var DOMElement $link */
            $blockId = $link->getAttribute('id') ?: generateUniqueId();
            $blocks[] = [
                'id' => $blockId,
                'type' => 'link',
                'data' => [
                    'text' => $link->textContent,
                    'url' => $link->getAttribute('href')
                ]
            ];
        }
    }

    // Process images
    if (isset($selectors['image'])) {
        $images = $xpath->query('//' . str_replace(', ', ' | //', $selectors['image']));
        foreach ($images as $image) {
            /** @var DOMElement $image */
            $blockId = $image->getAttribute('id') ?: generateUniqueId();
            $blocks[] = [
                'id' => $blockId,
                'type' => 'image',
                'data' => [
                    'src' => $image->getAttribute('src'),
                    'alt' => $image->getAttribute('alt'),
                    'width' => $image->getAttribute('width') ?: null,
                    'height' => $image->getAttribute('height') ?: null
                ]
            ];
        }
    }

    return $blocks;
}


/**
 * Generate a unique ID for blocks that don't have one
 * @return string Unique ID
 */
function generateUniqueId() {
    return substr(str_shuffle(MD5(microtime())), 0, 10);
}

/**
 * Fetches content from a directory
 * @param string $dir The directory to fetch
 * @return string|false The HTML content or false on failure
 */
function fetchFileContent($dir) {
    // Method 1: Using file_get_contents (if allow_url_fopen is enabled)
    if (ini_get('allow_url_fopen')) {
        return @file_get_contents($dir);
    }

    return false;
}

/**
 * Determines the block type based on the element
 * @param DOMElement $element The element to analyze
 * @return string The determined block type
 */
function determineBlockType($element) {
    // Check if element has a specified block type
    if ($element->hasAttribute('data-block-type')) {
        return $element->getAttribute('data-block-type');
    }

    if ($element->hasAttribute('data-block-toggle')) {
        return 'toggle';
    }

    // Determine type based on tag name
    switch (strtolower($element->tagName)) {
        case 'h1':
        case 'h2':
        case 'h3':
        case 'h4':
        case 'h5':
        case 'h6':
            return 'header';

        case 'p':
            return 'paragraph';

        case 'a':
            return strpos($element->getAttribute('class'), 'btn') !== false ? 'button' : 'link';

        case 'img':
            return 'image';

        default:
            return 'custom';
    }
}

/**
 * Fetches content from a URL and generates blocks with support for data attributes
 * @param string $url The URL to fetch content from
 * @return array Array of block objects ready for JSON encoding
 */
function generateBlocksFromURLWithDataAttributes($html, $filename) {
    if (!$html) {
        return [
            'error' => true,
            'message' => 'Failed to fetch content from URL'
        ];
    }

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $blocks[$filename] = [];

    // Example: Get elements with specific data attributes
    collectElementsWithDataAttribute($xpath, 'block', $blocks[$filename]);

    // Process standard elements as before
   // processStandardElements($xpath, $blocks);

    return $blocks;
}

/**
 * Collects elements with specific data attributes
 * @param DOMXPath $xpath The XPath object
 * @param string $dataAttribute The data attribute to search for (without the 'data-' prefix)
 * @param array &$blocks Reference to blocks array to populate
 * @param string|null $page The page name (optional)
 */
function collectElementsWithDataAttribute($xpath, $dataAttribute, &$blocks) {
    // Select all elements with the specified data attribute
    $elements = $xpath->query("//*[@data-$dataAttribute]");

    foreach ($elements as $element) {
        /** @var DOMElement $element */
        $blockType = determineBlockType($element);
        $blockId = $element->getAttribute('id') ?: $element->getAttribute("data-id") ?: generateUniqueId();
        $blockName = $element->getAttribute("data-block-name") ?: $element->getAttribute("data-name") ?: 'Block';
        $blockTag = $element->tagName;

        // Create a block based on the data-block-type attribute
        switch ($blockType) {
            case 'toggle':
                $blocks[] = [
                    'id' => $blockId,
                    'name' => 'Show/Hide '.$blockName,
                    'type' => 'boolean',
                    'data' => [
                        'value' => true
                    ]
                ];
                break;
            case 'heading':
                $level = convertHeaderTagToLevel($blockTag);
                $blocks[] = [
                    'id' => $blockId,
                    'type' => 'header',
                    'name' => $blockName,
                    'data' => [
                        'text' => $element->textContent,
                        'level' => $level
                    ]
                ];
                break;

            case 'paragraph':
                $blocks[] = [
                    'id' => $blockId,
                    'name' => $blockName,
                    'type' => 'paragraph',
                    'data' => [
                        'text' => $element->textContent
                    ]
                ];
                break;

            case 'button':
                // For buttons, check if it's an <a> tag
                $url = $element->tagName === 'a' ? $element->getAttribute('href') : $element->getAttribute('data-url');
                $blocks[] = [
                    'id' => $blockId,
                    'type' => 'button',
                    'name' => $blockName,
                    'data' => [
                        'text' => $element->textContent,
                        'url' => $url,
                        'style' => $element->getAttribute('class')
                    ]
                ];
                break;

            case 'link':
                $url = $element->tagName === 'a' ? $element->getAttribute('href') : $element->getAttribute('data-url');
                $blocks[] = [
                    'id' => $blockId,
                    'type' => 'link',
                    'name' => $blockName,
                    'data' => [
                        'text' => $element->textContent,
                        'url' => $url
                    ]
                ];
                break;

            case 'image':
                // For images
                if ($element->tagName === 'img') {
                    $src = $element->getAttribute('src');
                    $alt = $element->getAttribute('alt');
                } else {
                    // For containers with data-image attributes
                    $src = $element->getAttribute('data-src');
                    $alt = $element->getAttribute('data-alt');

                    // Try to find nested image
                    $nestedImages = $xpath->query('.//img', $element);
                    if ($nestedImages->length > 0) {
                        /** @var DOMElement $img */
                        $img = $nestedImages->item(0);
                        $src = $src ?: $img->getAttribute('src');
                        $alt = $alt ?: $img->getAttribute('alt');
                    }
                }

                $blocks[] = [
                    'id' => $blockId,
                    'type' => 'image',
                    'name' => $blockName,
                    'data' => [
                        'file' => [
                            'url' => $src,
                            'name' => basename($src),
                            'type' => pathinfo($src, PATHINFO_EXTENSION),
                            'size' => filesize($src) ?: 0 // Size in bytes
                        ],
                        'alt' => $alt,
                        'width' => $element->getAttribute('width') ?: $element->getAttribute('data-width') ?: null,
                        'height' => $element->getAttribute('height') ?: $element->getAttribute('data-height') ?: null
                    ]
                ];
                break;

            // Add more block types as needed
            default:
                // Generic block for custom types
                $blocks[] = [
                    'id' => $blockId,
                    'type' => $blockType,
                    'name' => $blockName,
                    'data' => collectDataAttributes($element)
                ];
                break;
        }
    }
}

/**
 * Collects all data attributes from an element
 * @param DOMElement $element The element to extract data attributes from
 * @return array Associative array of data attributes
 */
function collectDataAttributes($element) {
    $data = ['text' => $element->textContent];
    $attributes = $element->attributes;

    foreach ($attributes as $attribute) {
        $name = $attribute->name;
        if (strpos($name, 'data-') === 0) {
            // Convert data-attribute-name to attributeName (camelCase)
            $parts = explode('-', substr($name, 5));
            $camelCase = array_shift($parts);
            foreach ($parts as $part) {
                $camelCase .= ucfirst($part);
            }

            $data[$camelCase] = $attribute->value;
        }
    }

    return $data;
}

function checkPurchaseCode($code): bool
{
    $url = 'https://www.meebeestudio.com/api/verify-purchase/?code=' . $code;
    $response = \Swidly\Core\Response::get($url)['data'];

    if ($response['valid']) {
        return true;
    }

    return false;
}
function getPurchaseCode(): string
{
    $file = \Swidly\Core\File::readFile('config.php');
    preg_match('/\$purchase_code\s*=\s*\'(.*?)\'/', $file, $matches);
    return $matches[1] ?? '';
}

function runSqlFile($file, PDO $pdo): void
{
    $response = file_get_contents($file);
    if ($response === false) {
        throw new Exception("Failed to read the file: $file");
    }

    $response = json_decode($response, true);
    if ($response['success'] === false) {
        throw new Exception("Failed to encode the file content to JSON: $file");
    }
    $sql = $response['data']['sql'] ?? null;
    if (empty($sql)) {
        throw new Exception("No SQL content found in the file: $file");
    }

    $queries = array_filter(array_map('trim', explode(';', $sql)));
            
    foreach ($queries as $query) {
        if (!empty($query)) {
            try {
                $pdo->exec($query);
            } catch (\Exception $e) {
                error_log('SQL Error: ' . $e->getMessage());
                throw new \RuntimeException('Failed to execute query: ' . $query, 0, $e);
            }
        }
    }
}

function getCurrentUrl(): string
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];

    return $protocol . $host . $uri;
}

function convertHeaderTagToLevel($tag): int
{
    switch ($tag) {
        case 'h1':
            return 1;
        case 'h2':
            return 2;
        case 'h3':
            return 3;
        case 'h4':
            return 4;
        case 'h5':
            return 5;
        case 'h6':
            return 6;
        default:
            return 2; // Default to h2 if not recognized
    }
}

/**
 * Save data to cache file
 * @param string $key Cache key
 * @param mixed $data Data to cache
 * @param int $ttl Time to live in seconds (default 3600 = 1 hour)
 * @return bool Success/failure
 */
function saveToCache(string $key, mixed $data, int $ttl = 3600): bool {
    $cacheDir = __DIR__ . '/../../cache/';
    $filename = $cacheDir . md5($key) . '.cache';
    
    // Create cache directory if it doesn't exist
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0777, true);
    }
    
    $content = [
        'expires' => time() + $ttl,
        'data' => $data
    ];
    
    return file_put_contents($filename, serialize($content)) !== false;
}

/**
 * Get data from cache
 * @param string $key Cache key
 * @return mixed|null Cached data or null if expired/not found
 */
function getFromCache(string $key): mixed {
    $filename = __DIR__ . '/../../cache/' . md5($key) . '.cache';
    
    if (!file_exists($filename)) {
        return null;
    }
    
    $content = unserialize(file_get_contents($filename));
    
    if (time() > $content['expires']) {
        unlink($filename); // Delete expired cache
        return null;
    }
    
    return $content['data'];
}


/**
 * Compare cached data with new data
 * @param string $key Cache key
 * @param mixed $newData Data to compare with cached data
 * @return bool|array Returns false if no cache exists, otherwise returns [
 *    'changed' => bool,
 *    'additions' => array,
 *    'deletions' => array,
 *    'modifications' => array
 * ]
 */
function compareWithCache(string $key, mixed $newData): bool|array {
    $cachedData = getFromCache($key);
    
    if ($cachedData === null) {
        return false;
    }

    $changes = [
        'changed' => false,
        'additions' => [],
        'deletions' => [],
        'modifications' => []
    ];

    // Convert to arrays if objects
    $oldData = is_object($cachedData) ? (array)$cachedData : $cachedData;
    $newData = is_object($newData) ? (array)$newData : $newData;

    // If data types don't match, consider it changed
    if (gettype($oldData) !== gettype($newData)) {
        $changes['changed'] = true;
        return $changes;
    }

    // Compare arrays
    if (is_array($oldData)) {
        // Find additions (in new but not in old)
        $additions = array_diff_assoc($newData, $oldData);
        if (!empty($additions)) {
            $changes['changed'] = true;
            $changes['additions'] = $additions;
        }

        // Find deletions (in old but not in new)
        $deletions = array_diff_assoc($oldData, $newData);
        if (!empty($deletions)) {
            $changes['changed'] = true;
            $changes['deletions'] = $deletions;
        }

        // Find modifications (keys exist in both but values differ)
        foreach ($oldData as $key => $value) {
            if (isset($newData[$key]) && $newData[$key] !== $value) {
                $changes['changed'] = true;
                $changes['modifications'][$key] = [
                    'old' => $value,
                    'new' => $newData[$key]
                ];
            }
        }
    } else {
        // Simple comparison for non-arrays
        $changes['changed'] = ($oldData !== $newData);
        if ($changes['changed']) {
            $changes['modifications'] = [
                'old' => $oldData,
                'new' => $newData
            ];
        }
    }

    return $changes;
}