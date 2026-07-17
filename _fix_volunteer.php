<?php
$file = __DIR__ . '/dashboard.php';
$c = file_get_contents($file);

// The broken line has "rgba('.(===" - fix it by finding and replacing the broken section
// Find the unique marker that identifies the broken button HTML
$marker = "update_volunteer_status";
$pos = strpos($c, $marker);
if ($pos === false) {
    echo "CRITICAL: Could not find volunteer status marker\n";
    exit(1);
}

// Find the broken online status buttons section
// We need to find and replace the entire button generation code
$start_marker = '<?php
                                $statuses';
$end_marker = '<?php endforeach; ?>';

$start = strpos($c, $start_marker);
$end = strpos($c, $end_marker, $start);

if ($start === false || $end === false) {
    echo "CRITICAL: Could not find status section\n";
    exit(1);
}

// Get the full length including end marker
$end += strlen($end_marker);
$old_section = substr($c, $start, $end - $start);

// The correct replacement code
$new_section = '<?php
                                $statuses_list = [\'available\' => [\'#10b981\', \'fa-circle-check\', \'Available\'], \'busy\' => [\'#f59e0b\', \'fa-clock\', \'Busy\'], \'offline\' => [\'#94a3b8\', \'fa-circle\', \'Offline\']];
                                foreach ($statuses_list as $st_val => [$st_color, $st_icon, $st_label]):
                                    $st_active = $vol[\'online_status\'] === $st_val;
                                ?>
                                <form action="dashboard.php?page=volunteer" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="update_volunteer_status">
                                    <input type="hidden" name="online_status" value="<?php echo $st_val; ?>">
                                    <button type="submit" style="display:flex; align-items:center; gap:6px; padding:8px 16px; border:2px solid <?php echo $st_active ? $st_color : \'var(--border)\'; ?>; border-radius:10px; background:<?php echo $st_active ? \'rgba(\'.($st_val===\'available\'?\'16,185,129\':($st_val===\'busy\'?\'245,158,11\':\'148,163,184\')).\',0.1)\' : \'transparent\'; ?>; cursor:pointer; font-size:13px; font-weight:600; color:<?php echo $st_active ? $st_color : \'var(--text-secondary)\'; ?>; transition:all 0.2s;">
                                        <i class="fa-solid <?php echo $st_icon; ?>" style="color:<?php echo $st_color; ?>;"></i> <?php echo $st_label; ?>
                                    </button>
                                </form>
                                <?php endforeach; ?>';

$c = str_replace($old_section, $new_section, $c);
echo "Replaced broken online status buttons section\n";

file_put_contents($file, $c);
echo "Done\n";
