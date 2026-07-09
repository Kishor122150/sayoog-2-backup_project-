<?php
$file = 'dashboard.php';
$content = file_get_contents($file);
$count = 0;

// Replacement 1: Feed card Consume Before
$search1 = '<span><strong>Consume Before:</strong> <?php echo date(\'M d, Y h:i A\', strtotime($post[\'expiry_time\'])); ?></span>';
$replace1 = '<span><strong>Consume Before:</strong> <span class="countdown-badge" data-expiry="<?php echo $post[\'expiry_time\']; ?>">⏳ Loading...</span></span>';
$content = str_replace($search1, $replace1, $content, $c1);
$count += $c1;
echo "Replacement 1 (feed card Consume Before): $c1 occurrences\n";

// Replacement 2: Catalog card Consume Before
$search2 = '<div class="detail-item"><i class="fa-solid fa-hourglass-half"></i><span><strong>Consume Before:</strong> <?php echo date(\'M d, Y h:i A\', strtotime($donation[\'expiry_time\'])); ?></span></div>';
$replace2 = '<div class="detail-item"><i class="fa-solid fa-hourglass-half"></i><span><strong>Consume Before:</strong> <span class="countdown-badge" data-expiry="<?php echo $donation[\'expiry_time\']; ?>">⏳ Loading...</span></span></div>';
$content = str_replace($search2, $replace2, $content, $c2);
$count += $c2;
echo "Replacement 2 (catalog Consume Before): $c2 occurrences\n";

// Replacement 3: Catalog card Expires
$search3 = '<div class="detail-item"><i class="fa-solid fa-hourglass-half"></i><span><strong>Expires:</strong> <?php echo date(\'M d, Y h:i A\', strtotime($donation[\'expiry_time\'])); ?></span></div>';
$replace3 = '<div class="detail-item"><i class="fa-solid fa-hourglass-half"></i><span><strong>Expires:</strong> <span class="countdown-badge" data-expiry="<?php echo $donation[\'expiry_time\']; ?>">⏳ Loading...</span></span></div>';
$content = str_replace($search3, $replace3, $content, $c3);
$count += $c3;
echo "Replacement 3 (catalog Expires): $c3 occurrences\n";

// Replacement 4: Track request Exp
$search4 = '<span>Exp: <?php echo date(\'M d, Y h:i A\', strtotime($card[\'expiry_time\'])); ?></span>';
$replace4 = '<span>Exp: <span class="countdown-badge" data-expiry="<?php echo $card[\'expiry_time\']; ?>">⏳ Loading...</span></span>';
$content = str_replace($search4, $replace4, $content, $c4);
$count += $c4;
echo "Replacement 4 (track Exp): $c4 occurrences\n";

if ($count > 0) {
    file_put_contents($file, $content);
    echo "Total replacements: $count\n";
    echo "SUCCESS: dashboard.php updated.\n";
} else {
    echo "WARNING: No replacements were made. Strings may not match exactly.\n";
}
?>
