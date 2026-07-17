<?php
$file = __DIR__ . '/dashboard.php';
$c = file_get_contents($file);

// Look for the exact broken pattern
$search = ".(=== 'available'";
if (strpos($c, $search) !== false) {
    $c = str_replace($search, ".(\$val==='available'", $c);
    echo "Fixed available\n";
}

$search2 = ".(=== 'busy'";
if (strpos($c, $search2) !== false) {
    $c = str_replace($search2, ".(\$val==='busy'", $c);
    echo "Fixed busy\n";
}

// Also try without the leading dot
$search3 = "(=== 'available'";
if (strpos($c, $search3) !== false) {
    $c = str_replace($search3, "(\$val==='available'", $c);
    echo "Fixed available (no dot)\n";
}

$search4 = "(=== 'busy'";
if (strpos($c, $search4) !== false) {
    $c = str_replace($search4, "(\$val==='busy'", $c);
    echo "Fixed busy (no dot)\n";
}

file_put_contents($file, $c);
echo "Done\n";
