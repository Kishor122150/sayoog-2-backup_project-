<?php
/**
 * Patch script: replaces generate_donation_certificate function in config.php
 * with a TCPDF-compatible elegant certificate design.
 */

$file = __DIR__ . '/config.php';
$content = file_get_contents($file);

// The old function start marker (from the docblock to the closing brace before "?>")
$oldStart = "/**\n * Generate a PDF Certificate of Appreciation for a completed donation.";

// Find position of old function
$funcStart = strpos($content, $oldStart);
if ($funcStart === false) {
    echo "ERROR: Could not find old function start\n";
    exit(1);
}

// Find position of the next function or end of file after this function
// The function ends with "}\n"
$searchFrom = $funcStart;
$braceCount = 0;
$foundStart = false;
$funcEnd = $funcStart;

for ($i = $funcStart; $i < strlen($content); $i++) {
    $ch = $content[$i];
    if ($ch === '{' && !$foundStart) {
        // After "function generate_donation_certificate(...) {"
        $foundStart = true;
    }
    if ($foundStart) {
        if ($ch === '{') $braceCount++;
        if ($ch === '}') $braceCount--;
        if ($braceCount === 0 && $foundStart) {
            $funcEnd = $i + 1;
            break;
        }
    }
}

if ($funcEnd <= $funcStart) {
    echo "ERROR: Could not find end of function\n";
    exit(1);
}

$newFunction = <<<'FUNCTION'
/**
 * Generate a PDF Certificate of Appreciation for a completed donation.
 *
 * @param PDO    $pdo
 * @param int    $donation_id
 * @param string $donor_name
 * @param string $food_item
 * @param string $receiver_name (optional)
 * @return string|null Path to the generated certificate file, or null on failure.
 */
function generate_donation_certificate($pdo, $donation_id, $donor_name, $food_item, $receiver_name = 'Community') {
    try {
        $certDir = __DIR__ . '/uploads/certificates';
        if (!is_dir($certDir)) {
            mkdir($certDir, 0755, true);
        }

        $filename = 'certificate_' . $donation_id . '_' . uniqid() . '.pdf';
        $filepath = $certDir . '/' . $filename;

        // Include TCPDF
        require_once __DIR__ . '/tcpdf/tcpdf.php';

        // Create new PDF document (Landscape A4)
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Sayog Food Donation Platform');
        $pdf->SetAuthor('Sayog');
        $pdf->SetTitle('Certificate of Appreciation - ' . $food_item);
        $pdf->SetSubject('Donation Certificate');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Add a page
        $pdf->AddPage();

        // Certificate generation date
        $dateStr = date('F d, Y');
        $certNumber = 'SAYOG-CERT-' . str_pad($donation_id, 5, '0', STR_PAD_LEFT);

        // Escape variables for HTML
        $donor_escaped = htmlspecialchars($donor_name);
        $food_escaped = htmlspecialchars($food_item);
        $rec_escaped = htmlspecialchars($receiver_name);

        // Build elegant certificate HTML compatible with TCPDF's HTML renderer
        // NOTE: No position:absolute, no linear-gradient, no @page — TCPDF does not support these.
        $html = '
        <table border="0" cellpadding="0" cellspacing="0" style="width: 100%; height: 100%; border-collapse: collapse;">
            <tr>
                <td style="border: 6px solid #c9a84c; padding: 10px; background-color: #fdfcf5;">
                    <table border="0" cellpadding="0" cellspacing="0" style="width: 100%; height: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="border: 2px solid #b8943a; padding: 30px 28px 24px 28px; background-color: #fefcf5;">
                                <div style="text-align: center;">

                                    <!-- Corner decorative diamonds as text -->
                                    <div style="text-align: center; color: #c9a84c; font-size: 18px; letter-spacing: 2px; margin-bottom: 2px;">
                                        &#9670;                        &#9670;
                                    </div>

                                    <!-- Star seal / emblem -->
                                    <div style="font-size: 40px; color: #c9a84c; margin: 8px 0 4px 0;">&#9733;</div>

                                    <!-- Organization name -->
                                    <div style="color: #8b6d2c; font-size: 13px; font-weight: bold; letter-spacing: 4px; text-transform: uppercase; margin: 0 0 4px 0;">Sayog</div>

                                    <!-- Decorative flourish -->
                                    <div style="color: #c9a84c; font-size: 14px; letter-spacing: 6px; margin: 2px 0;">&#9679; &#9671; &#9679; &#9671; &#9679;</div>

                                    <!-- Main heading -->
                                    <div style="color: #2d5a3d; font-size: 30px; font-weight: bold; margin: 4px 0 4px 0; letter-spacing: -0.5px;">Certificate of Appreciation</div>

                                    <!-- Decorative flourish -->
                                    <div style="color: #c9a84c; font-size: 16px; letter-spacing: 6px; margin: 2px 0 8px 0;">&#9679; &#9670; &#9679; &#9670; &#9679;</div>

                                    <!-- Subtitle -->
                                    <div style="color: #6b7280; font-size: 12px; margin: 0 0 16px 0; letter-spacing: 1px;">In recognition of generous contribution to the community</div>

                                    <!-- Presented to -->
                                    <div style="color: #6b7280; font-size: 14px; margin: 16px 0 4px 0; font-style: italic;">This certificate is proudly presented to</div>

                                    <!-- Donor Name -->
                                    <div style="color: #1e3a2f; font-size: 36px; font-weight: bold; margin: 0 0 4px 0; letter-spacing: 1px;">' . $donor_escaped . '</div>

                                    <!-- Name underline -->
                                    <div style="border-bottom: 3px solid #c9a84c; width: 180px; margin: 0 auto 14px auto;"></div>

                                    <!-- For donating -->
                                    <div style="color: #6b7280; font-size: 14px; margin: 10px 0 4px 0;">For donating</div>
                                    <div style="color: #2d5a3d; font-size: 20px; font-weight: bold; margin: 2px 0 4px 0;">' . $food_escaped . '</div>
                                    <div style="color: #6b7280; font-size: 12px; margin: 2px 0 14px 0;">through the Sayog Food Donation Platform</div>

                                    <!-- Gold divider line -->
                                    <div style="border-bottom: 1px solid #c9a84c; width: 60%; margin: 10px auto;"></div>

                                    <!-- Organization name prominent -->
                                    <div style="color: #059669; font-size: 16px; font-weight: bold; margin: 14px 0 2px 0; letter-spacing: 3px; text-transform: uppercase;">Sayog</div>
                                    <div style="color: #9ca3af; font-size: 10px; margin: 0 0 14px 0;">Connecting surplus food with communities</div>

                                    <!-- Footer table: certificate details -->
                                    <table border="0" cellpadding="4" cellspacing="0" style="width: 80%; margin: 0 auto; border-collapse: collapse;">
                                        <tr>
                                            <td style="text-align: center; width: 33%;">
                                                <div style="font-size: 9px; color: #9ca3af; text-transform: uppercase; letter-spacing: 1px;">Certificate No.</div>
                                                <div style="font-size: 12px; color: #374151; font-weight: bold;">' . $certNumber . '</div>
                                            </td>
                                            <td style="text-align: center; width: 33%;">
                                                <div style="font-size: 9px; color: #9ca3af; text-transform: uppercase; letter-spacing: 1px;">Beneficiary</div>
                                                <div style="font-size: 12px; color: #374151; font-weight: bold;">' . $rec_escaped . '</div>
                                            </td>
                                            <td style="text-align: center; width: 33%;">
                                                <div style="font-size: 9px; color: #9ca3af; text-transform: uppercase; letter-spacing: 1px;">Date Issued</div>
                                                <div style="font-size: 12px; color: #374151; font-weight: bold;">' . $dateStr . '</div>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Signature area -->
                                    <div style="border-top: 2px solid #8b6d2c; width: 160px; margin: 28px auto 2px auto;"></div>
                                    <div style="font-size: 10px; color: #9ca3af; font-style: italic;">Authorized Signature &#8212; Sayog</div>

                                    <!-- Bottom footer note -->
                                    <div style="font-size: 7px; color: #d1d5db; margin-top: 16px; padding-top: 4px;">
                                        This certificate is auto-generated. Your contribution helps reduce food waste and supports communities in need.
                                    </div>

                                    <!-- Bottom corner decorative diamonds -->
                                    <div style="text-align: center; color: #c9a84c; font-size: 18px; letter-spacing: 2px; margin-top: 6px;">
                                        &#9670;                        &#9670;
                                    </div>

                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output($filepath, 'F');

        // Save path in database
        $relPath = 'uploads/certificates/' . $filename;
        $stmt = $pdo->prepare("UPDATE donations SET certificate_path = ? WHERE id = ?");
        $stmt->execute([$relPath, $donation_id]);

        return $relPath;
    } catch (Exception $e) {
        // Silently fail — certificate generation should not block the main flow
        return null;
    }
}
FUNCTION;

$newContent = substr($content, 0, $funcStart) . $newFunction . substr($content, $funcEnd);

// Verify PHP syntax
$tmpFile = tempnam(sys_get_temp_dir(), 'phpcheck_');
file_put_contents($tmpFile, '<?php ' . $newFunction . ' ?>');
exec('php -l ' . escapeshellarg($tmpFile) . ' 2>&1', $output, $exitCode);
unlink($tmpFile);

if ($exitCode !== 0) {
    echo "SYNTAX ERROR in new function:\n" . implode("\n", $output) . "\n";
    exit(1);
}

// Write the patched file
$bytes = file_put_contents($file, $newContent);
if ($bytes === false) {
    echo "ERROR: Failed to write config.php\n";
    exit(1);
}

// Verify the whole file syntax
exec('php -l ' . escapeshellarg($file) . ' 2>&1', $output2, $exitCode2);
if ($exitCode2 !== 0) {
    echo "SYNTAX ERROR in patched config.php:\n" . implode("\n", $output2) . "\n";
    exit(1);
}

echo "SUCCESS: Certificate function replaced. Syntax verified.\n";
