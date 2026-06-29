<?php
echo "<h1>Admin Folder Contents</h1>";
$files = scandir(dirname(__FILE__));
echo "<pre>";
foreach ($files as $f) {
    if ($f !== '.' && $f !== '..') {
        echo $f . " - " . filesize(dirname(__FILE__) . '/' . $f) . " bytes\n";
    }
}
echo "</pre>";
?>
