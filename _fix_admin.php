<?php
$file = __DIR__ . '/admin/admin.php';
$c = file_get_contents($file);

// The volunteer section is appended after </html>, so it's outside the main if/elseif chain.
// Change `<?php } elseif ($section === 'volunteers'):` to `<?php if ($section === 'volunteers'):`
// because there's no outer if block to close at this point.

$search = '<?php } elseif ($section === \'volunteers\'):';
$replace = '<?php if ($section === \'volunteers\'):';

$c = str_replace($search, $replace, $c, $count);
echo "Fixed $count occurrence(s): changed '} elseif' to 'if'\n";

// Also find and fix the ending - the volunteer section should end with <?php endif; ?>
// Check what the closing looks like - it should be <?php endif; ?> not <?php } ?>
// The section probably ends the main else-if chain
// Let me find the ending pattern

// The section ends with: <?php endif; ?> then maybe closing tags etc.
// Let me check the last lines

file_put_contents($file, $c);
echo "Done\n";
