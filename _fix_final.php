<?php
$file = __DIR__ . '/dashboard.php';
$c = file_get_contents($file);

// Find WHERE the '===' broken pattern is exactly by searching byte by byte
// The pattern we need: 'rgba('.(==='available'  should be  'rgba('.($val==='available'
// Search for the literal characters: 'rgba(' .

$pos = strpos($c, "'rgba('.");
if ($pos === false) {
    // Try without dot
    $pos = strpos($c, "'rgba('.(");
}
echo "Found 'rgba(' at position: $pos\n";

if ($pos !== false) {
    // Show what's around it
    $segment = substr($c, $pos, 120);
    echo "Before: " . bin2hex($segment) . "\n";
    
    // Replace all occurrences of $val=== with $val=== is fine
    // The issue is that some places have ==='available' without $val
    // Let me check if $val exists somewhere nearby
    
    // Fix all instances: (=== 'available' -> ($val==='available'
    // and (=== 'busy' -> ($val==='busy'
    $patterns = [
        "(=== 'available'" => "(\$val==='available'",
        "(=== 'busy'" => "(\$val==='busy'",
        ".=== 'available'" => ".\$val==='available'",
        ".=== 'busy'" => ".\$val==='busy'",
    ];
    
    $count = 0;
    foreach ($patterns as $search => $replace) {
        $c = str_replace($search, $replace, $c, $c2);
        $count += $c2;
    }
    echo "Fixed $count instances\n";
}

file_put_contents($file, $c);
echo "Done\n";
