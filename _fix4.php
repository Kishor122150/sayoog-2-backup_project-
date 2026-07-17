<?php
$file = __DIR__ . '/dashboard.php';
$c = file_get_contents($file);
$count = 0;

// The exact broken pattern from od output: 'rgba('.(==='available'
$broken = "'rgba('.(=== ";
$replacement = "'rgba('.(\$val=== ";
$c = str_replace($broken, $replacement, $c, $count);
echo "Fixed $count broken patterns\n";

file_put_contents($file, $c);
echo "Done\n";
