<?php
    $documentRoot = $_SERVER['DOCUMENT_ROOT'];
    $themesPath = $documentRoot.'/Swidly/themes';
    $themeInfos = [];

    $themes = array_diff(scandir($themesPath), ['..', '.']);
    foreach ($themes as $theme) {
        $realPath = $themesPath.'/'.$theme;
        if(is_dir($realPath) && $theme !== 'sw_admin') {
            $themeFile = glob($realPath.'/theme.*')[0];
           $themeInfos[] = \Swidly\Core\File::readArray($themeFile);
        }
    }
?>
<div>
    <?php foreach ($themeInfos as $info): ?>
        <div style="width: 500px; height: auto; border: 1px solid gray; margin-bottom: 2rem;">
            <img src="<?=$info['screenshot'] ?>" style="width: 100%; height: 250px; object-fit: cover;"/>
            <h5 style="padding: 0.9rem;"><?= $info['name'] ?></h5>
        </div>
    <?php endforeach; ?>
</div>