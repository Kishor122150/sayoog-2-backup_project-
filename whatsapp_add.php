<?php
// Script to add WhatsApp chat buttons to dashboard.php

$file = 'dashboard.php';
$content = file_get_contents($file);
if ($content === false) {
    die("Failed to read $file\n");
}

$original = $content;

// 1. Manage Donation - Add WhatsApp after consumer phone
$search1 = '                                                <div><strong><?php echo htmlspecialchars($req[\'consumer_name\']); ?></strong></div>
                                                <div style="font-size: 11.5px; color: var(--text-secondary); margin-top: 2px;">
                                                    <i class="fa-solid fa-phone" style="font-size: 10px; margin-right: 3px;"></i> <?php echo htmlspecialchars($req[\'consumer_phone\']); ?>
                                                </div>';

$replace1 = '                                                <div><strong><?php echo htmlspecialchars($req[\'consumer_name\']); ?></strong></div>
                                                <div style="font-size: 11.5px; color: var(--text-secondary); margin-top: 2px;">
                                                    <i class="fa-solid fa-phone" style="font-size: 10px; margin-right: 3px;"></i> <?php echo htmlspecialchars($req[\'consumer_phone\']); ?>
                                                </div>
                                                <?php if (!empty($req[\'consumer_phone\'])): ?>
                                                    <div style="margin-top: 4px;">
                                                        <a href="<?php echo get_whatsapp_link($req[\'consumer_phone\'], \'Hello \' . $req[\'consumer_name\'] . \', I am messaging you regarding a food donation request on Sayog.\'); ?>" target="_blank" class="whatsapp-chip">
                                                            <i class="fa-brands fa-whatsapp"></i> Chat on WhatsApp
                                                        </a>
                                                    </div>
                                                <?php endif; ?>';

$content = str_replace($search1, $replace1, $content);
if ($content === $original) {
    echo "WARNING: Replacement 1 (manage donation) did not match!\n";
} else {
    echo "OK: Replacement 1 (manage donation) succeeded.\n";
    $original = $content;
}

// 2. Manage Request - Add WhatsApp after donor phone (when approved/completed)
$search2 = '                                                <div><strong><?php echo htmlspecialchars($req[\'donor_name\']); ?></strong></div>
                                                <?php if ($req[\'status\'] === \'approved\' || $req[\'status\'] === \'completed\'): ?>
                                                    <div style="font-size: 11.5px; color: var(--text-secondary); margin-top: 2px;">
                                                        <i class="fa-solid fa-phone" style="font-size: 10px; margin-right: 3px;"></i> <?php echo htmlspecialchars($req[\'donor_phone\']); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div style="font-size: 11px; color: var(--text-muted); font-style: italic; margin-top: 2px;">Phone unlocked upon approval</div>
                                                <?php endif; ?>';

$replace2 = '                                                <div><strong><?php echo htmlspecialchars($req[\'donor_name\']); ?></strong></div>
                                                <?php if ($req[\'status\'] === \'approved\' || $req[\'status\'] === \'completed\'): ?>
                                                    <div style="font-size: 11.5px; color: var(--text-secondary); margin-top: 2px;">
                                                        <i class="fa-solid fa-phone" style="font-size: 10px; margin-right: 3px;"></i> <?php echo htmlspecialchars($req[\'donor_phone\']); ?>
                                                    </div>
                                                    <?php if (!empty($req[\'donor_phone\'])): ?>
                                                        <div style="margin-top: 4px;">
                                                            <a href="<?php echo get_whatsapp_link($req[\'donor_phone\'], \'Hello \' . $req[\'donor_name\'] . \', I am following up on my food donation request on Sayog.\'); ?>" target="_blank" class="whatsapp-chip">
                                                                <i class="fa-brands fa-whatsapp"></i> Chat on WhatsApp
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <div style="font-size: 11px; color: var(--text-muted); font-style: italic; margin-top: 2px;">Phone unlocked upon approval</div>
                                                <?php endif; ?>';

$content = str_replace($search2, $replace2, $content);
if ($content === $original) {
    echo "WARNING: Replacement 2 (manage request) did not match!\n";
} else {
    echo "OK: Replacement 2 (manage request) succeeded.\n";
    $original = $content;
}

// 3. Track Request - Add WhatsApp button after Contact phone in approved status
$search3 = '                                                <i class="fa-solid fa-phone-volume" style="color: var(--primary);"></i> <strong>Approved!</strong> Please coordinate pickup at: <strong><?php echo htmlspecialchars($req[\'pickup_address\']); ?></strong>. Contact: <strong><?php echo htmlspecialchars($req[\'donor_phone\']); ?></strong>.';

$replace3 = '                                                <i class="fa-solid fa-phone-volume" style="color: var(--primary);"></i> <strong>Approved!</strong> Please coordinate pickup at: <strong><?php echo htmlspecialchars($req[\'pickup_address\']); ?></strong>. Contact: <strong><?php echo htmlspecialchars($req[\'donor_phone\']); ?></strong>.
                                                <?php if (!empty($req[\'donor_phone\'])): ?>
                                                    <div style="margin-top: 8px;">
                                                        <a href="<?php echo get_whatsapp_link($req[\'donor_phone\'], \'Hello \' . $req[\'donor_name\'] . \', I am contacting you regarding the approved food donation pickup on Sayog.\'); ?>" target="_blank" class="btn btn-whatsapp btn-whatsapp-sm">
                                                            <i class="fa-brands fa-whatsapp"></i> Chat on WhatsApp
                                                        </a>
                                                    </div>
                                                <?php endif; ?>';

$content = str_replace($search3, $replace3, $content);
if ($content === $original) {
    echo "WARNING: Replacement 3 (track request) did not match!\n";
} else {
    echo "OK: Replacement 3 (track request) succeeded.\n";
    $original = $content;
}

// Write back
if (file_put_contents($file, $content) === false) {
    die("Failed to write $file\n");
}

echo "\nAll replacements completed successfully.\n";
