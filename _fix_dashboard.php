<?php
/**
 * Fix syntax issues in dashboard.php caused by the patch script
 */
$file = __DIR__ . '/dashboard.php';
$c = file_get_contents($file);
$fixed = false;

// Fix 1: HTML comment <!-- inside PHP block -> change to PHP comment //
$search1 = '                <!-- =============================================================
                // TAB: VOLUNTEER DASHBOARD
                // =============================================================';
$replace1 = '                // =============================================================
                // TAB: VOLUNTEER DASHBOARD
                // =============================================================';
if (strpos($c, $search1) !== false) {
    $c = str_replace($search1, $replace1, $c);
    echo "✅ Fixed HTML comment inside PHP block\n";
    $fixed = true;
} else {
    echo "⚠️  Could not find HTML comment pattern\n";
    // Let's search for the pattern with different spacing
    $alt1 = '<!-- =============================================================';
    if (strpos($c, $alt1) !== false) {
        echo "   Found alternative pattern with '<!--'\n";
        $c = str_replace($alt1, '// =============================================================', $c);
        $fixed = true;
    }
}

// Fix 2: Add volunteer case to header title switch
$header_search = "case 'profile': echo \"Edit Profile\"; break;\n                            }";
$header_replace = "case 'profile': echo \"Edit Profile\"; break;\n                                case 'volunteer': echo \"Volunteer Dashboard\"; break;\n                            }";
if (strpos($c, $header_search) !== false) {
    $c = str_replace($header_search, $header_replace, $c);
    echo "✅ Added volunteer case to header title\n";
    $fixed = true;
} else {
    echo "⚠️  Could not find profile case in header\n";
    // Check if it was already added
    if (strpos($c, "case 'volunteer':") !== false) {
        echo "   Volunteer case already exists\n";
    }
}

if ($fixed) {
    file_put_contents($file, $c);
    echo "\n✅ Dashboard.php fixed successfully\n";
} else {
    echo "\n⚠️  No changes made\n";
}
