<?php
header('Content-Type: text/css', true);
require_once '../../../../bootstrap.php';
$db = \Swidly\Core\DB::create();

$appearance = $db->Query('
    SELECT * FROM appearance a
    INNER JOIN colors c ON a.colorId = c.id
    INNER JOIN fonts f ON a.fontId = f.id
')->fetch(PDO::FETCH_OBJ);
?>

:root {
    --background-color: <?= $appearance->background ?>;
    --font-color: <?= $appearance->accent ?>;
    --font-family: <?= $appearance->family ?>;
}