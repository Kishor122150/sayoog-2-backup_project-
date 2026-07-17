<?php
$file = __DIR__ . '/dashboard.php';
$c = file_get_contents($file);

// Fix: Remove the extra closing `}` at the end of the volunteer view section
// Line with `<?php endif; }` should be just `<?php endif;` without the extra }
// so that the `} elseif ($page === 'notifications') {` can close the volunteer block

$search = "<?php endif; ?>\n\n                } elseif (\$page === 'notifications') {";
$pos = strpos($c, $search);

if ($pos !== false) {
    echo "Found pattern with extra closing\n";
    // Check what's before endif
    $segment = substr($c, $pos - 50, 100);
    echo "Context: ..." . bin2hex(substr($segment, 0, 30)) . "...\n";
} 

// Try different patterns - the endif is followed by } then newlines
$patterns = [
    "<?php endif; }" => "<?php endif;",
    "endif; }\n\n                } elseif" => "endif;\n\n                } elseif",
];

foreach ($patterns as $search => $replace) {
    $c = str_replace($search, $replace, $c, $count);
    if ($count > 0) {
        echo "Fixed pattern: " . bin2hex($search) . " -> " . bin2hex($replace) . " ($count occurrences)\n";
    }
}

file_put_contents($file, $c);
echo "Done\n";
