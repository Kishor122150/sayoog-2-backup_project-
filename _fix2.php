<?php
$file = __DIR__ . '/dashboard.php';
$c = file_get_contents($file);

// Fix the missing $val in the online status button
// Find the broken line pattern
$broken = "(===";
$idx = strpos($c, $broken);
if ($idx !== false && strpos(substr($c, $idx, 60), "available") !== false) {
    echo "Found broken pattern at position: $idx\n";
    // Replace the exact broken segment
    $old = "(=== 'available'";
    $new = "(\$val==='available'";
    $c = str_replace($old, $new, $c, $count);
    echo "Fixed $count occurrences of missing \$val in status button\n";
}

// Also fix the second occurrence
$old2 = "(=== 'busy'";
$new2 = "(\$val==='busy'";
$c = str_replace($old2, $new2, $c, $count2);
echo "Fixed $count2 occurrences of missing \$val for busy\n";

file_put_contents($file, $c);
echo "Done\n";
