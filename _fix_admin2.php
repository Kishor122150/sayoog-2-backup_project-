<?php
$file = "C:\\xampp\\htdocs\\sayog\\admin\\admin.php";
$c = file_get_contents($file);

// The volunteer section is after </html> and starts with:
// <?php } elseif ($section === 'volunteers'):
// This needs to be changed to:
// <?php if ($section === 'volunteers'):
// Since it's outside the main if/else chain

$old = '<?php } elseif ($section === \'volunteers\'):';
$new = '<?php if ($section === \'volunteers\'):';

$pos = strpos($c, $old);
if ($pos !== false) {
    $c = str_replace($old, $new, $c);
    echo "Fixed } elseif to if\n";
} else {
    echo "Pattern not found. Trying exact hex match...\n";
    // Let's find what's actually there
    $marker = "VOLUNTEER MANAGEMENT SECTION";
    $pos = strpos($c, $marker);
    if ($pos !== false) {
        $segment = substr($c, $pos, 200);
        echo "Context: " . bin2hex($segment) . "\n";
    }
}

file_put_contents($file, $c);

// Check syntax
$output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
echo $output;
