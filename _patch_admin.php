<?php
/**
 * Patch admin.php to add Volunteer Management sections
 * Run: php _patch_admin.php
 */

// ─── PATCH admin.php ───
$file = __DIR__ . '/admin/admin.php';
$content = file_get_contents($file);
$modified = false;

if ($content === false) die("Failed to read admin/admin.php\n");

// 1. Add 'volunteers' to valid_sections
$old = "\$valid_sections = ['dashboard', 'users', 'products', 'cms', 'listing_requests', 'contact_messages', 'donations'];";
$new = "\$valid_sections = ['dashboard', 'users', 'products', 'cms', 'listing_requests', 'contact_messages', 'donations', 'volunteers'];";
if (strpos($content, $old) !== false) {
    $content = str_replace($old, $new, $content);
    echo "✅ Added volunteers to valid_sections\n";
    $modified = true;
}

// 2. Add volunteer counts query after existing queries
$search_query = "\$recent_donations = \$pdo->query(\"SELECT d.*, u.name AS donor_name FROM donations d JOIN users u ON d.donor_id = u.id ORDER BY d.created_at DESC LIMIT 6\")->fetchAll();";
$new_queries = "\$recent_donations = \$pdo->query(\"SELECT d.*, u.name AS donor_name FROM donations d JOIN users u ON d.donor_id = u.id ORDER BY d.created_at DESC LIMIT 6\")->fetchAll();\n\$volunteer_counts = get_volunteer_counts(\$pdo);\n\$volunteers_pending = get_volunteers_by_status(\$pdo, 'pending');\n\$volunteers_approved = get_volunteers_by_status(\$pdo, 'approved');\n\$volunteers_rejected = get_volunteers_by_status(\$pdo, 'rejected');\n\$volunteers_suspended = get_volunteers_by_status(\$pdo, 'suspended');";
if (strpos($content, $search_query) !== false) {
    $content = str_replace($search_query, $new_queries, $content);
    echo "✅ Added volunteer queries\n";
    $modified = true;
}

// 3. Add volunteer sidebar navigation after Donations nav item
$search_nav = "<a href=\"admin.php?section=donations\" class=\\\"<?php echo \$section === 'donations' ? 'active' : ''; ?>\\\">\n                <i class=\"fa-solid fa-hand-holding-heart\"></i> Donations\n                <?php if (count(\$pending_donations) > 0): ?>\n                    <span class=\"nav-badge\"><?php echo count(\$pending_donations); ?></span>\n                <?php endif; ?>\n            </a>\n\n            <div class=\"nav-section-label\">Management</div>";
$new_nav = "<a href=\"admin.php?section=donations\" class=\"<?php echo \$section === 'donations' ? 'active' : ''; ?>\">\n                <i class=\"fa-solid fa-hand-holding-heart\"></i> Donations\n                <?php if (count(\$pending_donations) > 0): ?>\n                    <span class=\"nav-badge\"><?php echo count(\$pending_donations); ?></span>\n                <?php endif; ?>\n            </a>\n\n            <a href=\"admin.php?section=volunteers\" class=\"<?php echo \$section === 'volunteers' ? 'active' : ''; ?>\">\n                <i class=\"fa-solid fa-people-carry-box\"></i> Volunteers\n                <?php if (\$volunteer_counts['pending'] > 0): ?>\n                    <span class=\"nav-badge\"><?php echo \$volunteer_counts['pending']; ?></span>\n                <?php endif; ?>\n            </a>\n\n            <div class=\"nav-section-label\">Management</div>";
if (strpos($content, $search_nav) !== false) {
    $content = str_replace($search_nav, $new_nav, $content);
    echo "✅ Added volunteer nav item\n";
    $modified = true;
} else {
    echo "⚠️  Could not find nav section\n";
}

// 4. Add volunteer detail view page before </html>
$search_end = "</html>";
$volunteer_view = '
<!-- ============================== -->
            <!-- VOLUNTEER MANAGEMENT SECTION -->
            <!-- ============================== -->
            <?php } elseif ($section === \'volunteers\'): 
                $vol_tab = sanitize($_GET[\'vol_tab\'] ?? \'pending\');
                $valid_vol_tabs = [\'pending\', \'approved\', \'rejected\', \'suspended\', \'detail\'];
                if (!in_array($vol_tab, $valid_vol_tabs)) $vol_tab = \'pending\';
                $vol_detail_id = intval($_GET[\'vol_id\'] ?? 0);

                // Handle approve/reject/suspend actions
                if ($_SERVER[\'REQUEST_METHOD\'] === \'POST\') {
                    $action = $_POST[\'action\'] ?? \'\';
                    
                    if ($action === \'approve_volunteer\') {
                        $vid = intval($_POST[\'volunteer_id\'] ?? 0);
                        if ($vid > 0) {
                            approve_volunteer($pdo, $vid, $_SESSION[\'user_id\'] ?? 0);
                            set_flash_message(\'success\', \'Volunteer approved successfully.\');
                            redirect(\'admin.php?section=volunteers&vol_tab=approved\');
                        }
                    }
                    
                    if ($action === \'reject_volunteer\') {
                        $vid = intval($_POST[\'volunteer_id\'] ?? 0);
                        $reason = sanitize($_POST[\'reject_reason\'] ?? \'Other\');
                        if ($vid > 0) {
                            reject_volunteer($pdo, $vid, $reason);
                            set_flash_message(\'warning\', \'Volunteer application rejected.\');
                            redirect(\'admin.php?section=volunteers&vol_tab=rejected\');
                        }
                    }
                    
                    if ($action === \'suspend_volunteer\') {
                        $vid = intval($_POST[\'volunteer_id\'] ?? 0);
                        $reason = sanitize($_POST[\'suspend_reason\'] ?? \'Policy Violation\');
                        if ($vid > 0) {
                            suspend_volunteer($pdo, $vid, $reason);
                            set_flash_message(\'warning\', \'Volunteer suspended.\');
                            redirect(\'admin.php?section=volunteers&vol_tab=suspended\');
                        }
                    }
                    
                    if ($action === \'delete_volunteer\') {
                        $vid = intval($_POST[\'volunteer_id\'] ?? 0);
                        if ($vid > 0) {
                            $stmt = $pdo->prepare("DELETE FROM volunteers WHERE id = ?");
                            $stmt->execute([$vid]);
                            set_flash_message(\'success\', \'Volunteer record deleted.\');
                            redirect(\'admin.php?section=volunteers\');
                        }
                    }
                }

                $flash = get_flash_message();
                ?>

                <?php if ($flash): ?>
                    <div class="admin-alert admin-alert-<?php echo $flash[\'type\']; ?>">
                        <i class="fa-solid <?php echo $flash[\'type\'] === \'success\' ? \'fa-circle-check\' : ($flash[\'type\'] === \'warning\' ? \'fa-triangle-exclamation\' : \'fa-circle-xmark\'); ?>"></i>
                        <span><?php echo htmlspecialchars($flash[\'message\']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($vol_tab === \'detail\' && $vol_detail_id > 0):
                    $vol = get_volunteer_by_id($pdo, $vol_detail_id);
                    if (!$vol): ?>
                        <div class="admin-alert admin-alert-danger">Volunteer not found.</div>
                    <?php else: ?>
                    <div class="section-header">
                        <div>
                            <h1>Volunteer Details</h1>
                            <p>Review full application details for <?php echo htmlspecialchars($vol[\'full_name\']); ?></p>
                        </div>
                        <a href="admin.php?section=volunteers" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
                    </div>

                    <div class="admin-card" style="margin-bottom:24px;">
                        <div class="admin-card-header">
                            <h3><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($vol[\'full_name\']); ?></h3>
                            <span class="badge badge-<?php echo $vol[\'status\'] === \'approved\' ? \'success\' : ($vol[\'status\'] === \'rejected\' ? \'danger\' : ($vol[\'status\'] === \'suspended\' ? \'warning\' : \'info\')); ?>">
                                <?php echo ucfirst($vol[\'status\']); ?>
                            </span>
                        </div>
                        <div class="admin-card-body">
                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;">
                                <div>
                                    <h4 style="margin-bottom:12px;font-size:14px;font-weight:700;color:var(--admin-text);">Personal Information</h4>
                                    <table class="detail-table">
                                        <tr><td><strong>Full Name</strong></td><td><?php echo htmlspecialchars($vol[\'full_name\']); ?></td></tr>
                                        <tr><td><strong>Email</strong></td><td><?php echo htmlspecialchars($vol[\'email\']); ?></td></tr>
                                        <tr><td><strong>Phone</strong></td><td><?php echo htmlspecialchars($vol[\'phone\']); ?></td></tr>
                                        <tr><td><strong>DOB</strong></td><td><?php echo htmlspecialchars($vol[\'date_of_birth\']); ?></td></tr>
                                        <tr><td><strong>Gender</strong></td><td><?php echo ucfirst($vol[\'gender\']); ?></td></tr>
                                        <tr><td><strong>Occupation</strong></td><td><?php echo htmlspecialchars($vol[\'occupation\'] ?? \'N/A\'); ?></td></tr>
                                        <tr><td><strong>Emergency Contact</strong></td><td><?php echo htmlspecialchars($vol[\'emergency_contact\']); ?></td></tr>
                                    </table>
                                </div>
                                <div>
                                    <h4 style="margin-bottom:12px;font-size:14px;font-weight:700;color:var(--admin-text);">Address</h4>
                                    <table class="detail-table">
                                        <tr><td><strong>Address</strong></td><td><?php echo htmlspecialchars($vol[\'address\'] ?? \'N/A\'); ?></td></tr>
                                        <tr><td><strong>Municipality</strong></td><td><?php echo htmlspecialchars($vol[\'municipality\'] ?? \'N/A\'); ?></td></tr>
                                        <tr><td><strong>Ward</strong></td><td><?php echo htmlspecialchars($vol[\'ward_number\'] ?? \'N/A\'); ?></td></tr>
                                        <tr><td><strong>District</strong></td><td><?php echo htmlspecialchars($vol[\'district\'] ?? \'N/A\'); ?></td></tr>
                                        <tr><td><strong>Province</strong></td><td><?php echo htmlspecialchars($vol[\'province\'] ?? \'N/A\'); ?></td></tr>
                                    </table>
                                </div>
                                <div>
                                    <h4 style="margin-bottom:12px;font-size:14px;font-weight:700;color:var(--admin-text);">Vehicle & Delivery</h4>
                                    <table class="detail-table">
                                        <tr><td><strong>Vehicle Type</strong></td><td><?php echo ucfirst($vol[\'vehicle_type\']); ?></td></tr>
                                        <tr><td><strong>Vehicle No.</strong></td><td><?php echo htmlspecialchars($vol[\'vehicle_number\'] ?? \'N/A\'); ?></td></tr>
                                        <tr><td><strong>License No.</strong></td><td><?php echo htmlspecialchars($vol[\'license_number\'] ?? \'N/A\'); ?></td></tr>
                                        <tr><td><strong>Delivery Radius</strong></td><td><?php echo $vol[\'delivery_radius\']; ?> km</td></tr>
                                        <tr><td><strong>Availability</strong></td><td><?php echo str_replace(\',\', \', \', ucfirst($vol[\'availability\'])); ?></td></tr>
                                        <tr><td><strong>Languages</strong></td><td><?php echo htmlspecialchars($vol[\'languages\'] ?? \'N/A\'); ?></td></tr>
                                    </table>
                                </div>
                            </div>

                            <?php if (!empty($vol[\'motivation\'])): ?>
                            <div style="margin-top:20px;padding:16px;background:var(--admin-bg-light);border-radius:12px;">
                                <h4 style="font-size:14px;font-weight:700;margin-bottom:8px;">Motivation</h4>
                                <p style="color:var(--admin-text-secondary);"><?php echo nl2br(htmlspecialchars($vol[\'motivation\'])); ?></p>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($vol[\'previous_experience\'])): ?>
                            <div style="margin-top:16px;">
                                <h4 style="font-size:14px;font-weight:700;margin-bottom:8px;">Previous Experience</h4>
                                <p style="color:var(--admin-text-secondary);"><?php echo nl2br(htmlspecialchars($vol[\'previous_experience\'])); ?></p>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($vol[\'medical_training\'])): ?>
                            <div style="margin-top:16px;">
                                <h4 style="font-size:14px;font-weight:700;margin-bottom:8px;">Medical Training</h4>
                                <p style="color:var(--admin-text-secondary);"><?php echo htmlspecialchars($vol[\'medical_training\']); ?></p>
                            </div>
                            <?php endif; ?>

                            <!-- Identity Documents -->
                            <div style="margin-top:24px;">
                                <h4 style="font-size:14px;font-weight:700;margin-bottom:12px;">Identity Documents</h4>
                                <div style="display:flex;flex-wrap:wrap;gap:12px;">
                                    <?php foreach ([
                                        \'citizenship_front\' => \'Citizenship Front\',
                                        \'citizenship_back\' => \'Citizenship Back\',
                                        \'national_id\' => \'National ID\',
                                        \'college_id\' => \'College ID\',
                                        \'driving_license\' => \'Driving License\',
                                        \'profile_photo\' => \'Profile Photo\'
                                    ] as $key => $label): 
                                        if (!empty($vol[$key])): ?>
                                        <div style="text-align:center;">
                                            <div style="font-size:11px;font-weight:600;color:var(--admin-text-muted);margin-bottom:4px;"><?php echo $label; ?></div>
                                            <?php $ext = strtolower(pathinfo($vol[$key], PATHINFO_EXTENSION)); ?>
                                            <?php if (in_array($ext, [\'jpg\', \'jpeg\', \'png\', \'webp\'])): ?>
                                                <a href="../<?php echo htmlspecialchars($vol[$key]); ?>" target="_blank">
                                                    <img src="../<?php echo htmlspecialchars($vol[$key]); ?>" alt="<?php echo $label; ?>" style="width:120px;height:90px;object-fit:cover;border-radius:8px;border:1px solid var(--admin-border);">
                                                </a>
                                            <?php else: ?>
                                                <a href="../<?php echo htmlspecialchars($vol[$key]); ?>" target="_blank" class="btn btn-sm btn-outline"><i class="fa-solid fa-file"></i> View PDF</a>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; 
                                    endforeach; ?>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--admin-border);display:flex;flex-wrap:wrap;gap:12px;">
                                <?php if ($vol[\'status\'] === \'pending\'): ?>
                                <form action="admin.php?section=volunteers&vol_tab=detail&vol_id=<?php echo $vol[\'id\']; ?>" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="approve_volunteer">
                                    <input type="hidden" name="volunteer_id" value="<?php echo $vol[\'id\']; ?>">
                                    <button type="submit" class="btn btn-success"><i class="fa-solid fa-check"></i> Approve Volunteer</button>
                                </form>
                                <form action="admin.php?section=volunteers&vol_tab=detail&vol_id=<?php echo $vol[\'id\']; ?>" method="POST" style="display:inline;" onsubmit="return confirm(\'Reject this application?\');">
                                    <input type="hidden" name="action" value="reject_volunteer">
                                    <input type="hidden" name="volunteer_id" value="<?php echo $vol[\'id\']; ?>">
                                    <select name="reject_reason" class="form-control" style="width:auto;display:inline-block;min-width:160px;" required>
                                        <option value="">Select reason...</option>
                                        <option value="Incomplete Information">Incomplete Information</option>
                                        <option value="Invalid Documents">Invalid Documents</option>
                                        <option value="Duplicate Account">Duplicate Account</option>
                                        <option value="Fake Information">Fake Information</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-xmark"></i> Reject</button>
                                </form>
                                <?php elseif ($vol[\'status\'] === \'approved\'): ?>
                                <form action="admin.php?section=volunteers&vol_tab=detail&vol_id=<?php echo $vol[\'id\']; ?>" method="POST" style="display:inline;" onsubmit="return confirm(\'Suspend this volunteer?\');">
                                    <input type="hidden" name="action" value="suspend_volunteer">
                                    <input type="hidden" name="volunteer_id" value="<?php echo $vol[\'id\']; ?>">
                                    <select name="suspend_reason" class="form-control" style="width:auto;display:inline-block;min-width:160px;" required>
                                        <option value="">Select reason...</option>
                                        <option value="Misconduct">Misconduct</option>
                                        <option value="Fake Delivery">Fake Delivery</option>
                                        <option value="Poor Rating">Poor Rating</option>
                                        <option value="Policy Violation">Policy Violation</option>
                                    </select>
                                    <button type="submit" class="btn btn-warning"><i class="fa-solid fa-pause"></i> Suspend</button>
                                </form>
                                <?php endif; ?>
                                <form action="admin.php?section=volunteers&vol_tab=detail&vol_id=<?php echo $vol[\'id\']; ?>" method="POST" style="display:inline;" onsubmit="return confirm(\'Delete this volunteer record permanently?\');">
                                    <input type="hidden" name="action" value="delete_volunteer">
                                    <input type="hidden" name="volunteer_id" value="<?php echo $vol[\'id\']; ?>">
                                    <button type="submit" class="btn btn-danger" style="color:red"><i class="fa-solid fa-trash"></i> Delete Record</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                <?php else: ?>
                <div class="section-header">
                    <div>
                        <h1>Volunteer Management</h1>
                        <p>Manage volunteer applications, approvals, rejections, and suspensions.</p>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid" style="margin-bottom:24px;">
                    <div class="stat-card">
                        <div class="stat-card-icon green"><i class="fa-solid fa-users"></i></div>
                        <div class="stat-card-body">
                            <div class="stat-card-value"><?php echo $volunteer_counts[\'total\']; ?></div>
                            <div class="stat-card-label">Total Applications</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon amber"><i class="fa-solid fa-clock"></i></div>
                        <div class="stat-card-body">
                            <div class="stat-card-value"><?php echo $volunteer_counts[\'pending\']; ?></div>
                            <div class="stat-card-label">Pending</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon green"><i class="fa-solid fa-circle-check"></i></div>
                        <div class="stat-card-body">
                            <div class="stat-card-value"><?php echo $volunteer_counts[\'approved\']; ?></div>
                            <div class="stat-card-label">Approved</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon red"><i class="fa-solid fa-ban"></i></div>
                        <div class="stat-card-body">
                            <div class="stat-card-value"><?php echo $volunteer_counts[\'rejected\']; ?></div>
                            <div class="stat-card-label">Rejected</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon purple"><i class="fa-solid fa-pause"></i></div>
                        <div class="stat-card-body">
                            <div class="stat-card-value"><?php echo $volunteer_counts[\'suspended\']; ?></div>
                            <div class="stat-card-label">Suspended</div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="cms-tabs" style="margin-bottom:24px;">
                    <a href="admin.php?section=volunteers&vol_tab=pending" class="cms-tab <?php echo $vol_tab === \'pending\' ? \'active\' : \'\'; ?>">
                        <i class="fa-solid fa-clock"></i> Pending <span class="badge badge-warning" style="margin-left:6px;"><?php echo $volunteer_counts[\'pending\']; ?></span>
                    </a>
                    <a href="admin.php?section=volunteers&vol_tab=approved" class="cms-tab <?php echo $vol_tab === \'approved\' ? \'active\' : \'\'; ?>">
                        <i class="fa-solid fa-circle-check"></i> Approved <span class="badge badge-success" style="margin-left:6px;"><?php echo $volunteer_counts[\'approved\']; ?></span>
                    </a>
                    <a href="admin.php?section=volunteers&vol_tab=rejected" class="cms-tab <?php echo $vol_tab === \'rejected\' ? \'active\' : \'\'; ?>">
                        <i class="fa-solid fa-ban"></i> Rejected <span class="badge badge-danger" style="margin-left:6px;"><?php echo $volunteer_counts[\'rejected\']; ?></span>
                    </a>
                    <a href="admin.php?section=volunteers&vol_tab=suspended" class="cms-tab <?php echo $vol_tab === \'suspended\' ? \'active\' : \'\'; ?>">
                        <i class="fa-solid fa-pause"></i> Suspended <span class="badge badge-warning" style="margin-left:6px;"><?php echo $volunteer_counts[\'suspended\']; ?></span>
                    </a>
                </div>

                <!-- Volunteer Table -->
                <?php
                $vol_list = [];
                $list_title = \'\';
                if ($vol_tab === \'pending\') { $vol_list = $volunteers_pending; $list_title = \'Pending Applications\'; }
                elseif ($vol_tab === \'approved\') { $vol_list = $volunteers_approved; $list_title = \'Approved Volunteers\'; }
                elseif ($vol_tab === \'rejected\') { $vol_list = $volunteers_rejected; $list_title = \'Rejected Applications\'; }
                elseif ($vol_tab === \'suspended\') { $vol_list = $volunteers_suspended; $list_title = \'Suspended Volunteers\'; }
                ?>
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3><i class="fa-solid fa-list"></i> <?php echo $list_title; ?></h3>
                        <span class="badge badge-info"><?php echo count($vol_list); ?> records</span>
                    </div>
                    <div class="admin-card-body" style="padding:0;">
                        <?php if (empty($vol_list)): ?>
                            <div class="empty-state" style="padding:40px 20px;">
                                <i class="fa-solid fa-inbox"></i>
                                <h3>No <?php echo strtolower($list_title); ?></h3>
                                <p>There are no volunteer applications in this category.</p>
                            </div>
                        <?php else: ?>
                        <div class="table-wrapper">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th style="width:50px;">#</th>
                                        <th>Volunteer</th>
                                        <th>Phone</th>
                                        <th>Vehicle</th>
                                        <th>Radius</th>
                                        <th>Availability</th>
                                        <th>Applied</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $sn = 1; foreach ($vol_list as $v): ?>
                                    <tr>
                                        <td><span class="badge badge-neutral"><?php echo $sn++; ?></span></td>
                                        <td>
                                            <div class="user-cell">
                                                <div class="user-avatar"><?php echo strtoupper(substr($v[\'full_name\'], 0, 1)); ?></div>
                                                <div>
                                                    <div class="user-name"><?php echo htmlspecialchars($v[\'full_name\']); ?></div>
                                                    <div class="user-email"><?php echo htmlspecialchars($v[\'email\']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($v[\'phone\']); ?></td>
                                        <td><span class="badge badge-neutral"><?php echo ucfirst($v[\'vehicle_type\']); ?></span></td>
                                        <td><?php echo $v[\'delivery_radius\']; ?> km</td>
                                        <td><span class="badge badge-info"><?php echo str_replace(\',\', \' \', ucfirst($v[\'availability\'])); ?></span></td>
                                        <td style="white-space:nowrap;font-size:13px;"><?php echo date(\'d M Y\', strtotime($v[\'created_at\'])); ?></td>
                                        <td>
                                            <div class="table-actions">
                                                <a href="admin.php?section=volunteers&vol_tab=detail&vol_id=<?php echo $v[\'id\']; ?>" class="btn btn-sm btn-outline"><i class="fa-solid fa-eye"></i> View</a>
                                                <?php if ($v[\'status\'] === \'pending\'): ?>
                                                <form action="admin.php?section=volunteers&vol_tab=<?php echo $vol_tab; ?>" method="POST" class="inline-form">
                                                    <input type="hidden" name="action" value="approve_volunteer">
                                                    <input type="hidden" name="volunteer_id" value="<?php echo $v[\'id\']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success"><i class="fa-solid fa-check"></i> Approve</button>
                                                </form>
                                                <form action="admin.php?section=volunteers&vol_tab=<?php echo $vol_tab; ?>" method="POST" class="inline-form">
                                                    <input type="hidden" name="action" value="reject_volunteer">
                                                    <input type="hidden" name="volunteer_id" value="<?php echo $v[\'id\']; ?>">
                                                    <input type="hidden" name="reject_reason" value="Other">
                                                    <button type="submit" class="btn btn-sm btn-secondary" onclick="return confirm(\'Reject this application?\');"><i class="fa-solid fa-xmark"></i> Reject</button>
                                                </form>
                                                <?php elseif ($v[\'status\'] === \'approved\'): ?>
                                                <form action="admin.php?section=volunteers&vol_tab=<?php echo $vol_tab; ?>" method="POST" class="inline-form" onsubmit="return confirm(\'Suspend this volunteer?\');">
                                                    <input type="hidden" name="action" value="suspend_volunteer">
                                                    <input type="hidden" name="volunteer_id" value="<?php echo $v[\'id\']; ?>">
                                                    <input type="hidden" name="suspend_reason" value="Policy Violation">
                                                    <button type="submit" class="btn btn-sm btn-secondary"><i class="fa-solid fa-pause"></i> Suspend</button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- END VOLUNTEER MANAGEMENT -->';

if (strpos($content, $search_end) !== false) {
    $content = str_replace($search_end, $volunteer_view . "\n        </html>", $content);
    echo "✅ Added volunteer management views\n";
    $modified = true;
}

if ($modified) {
    file_put_contents($file, $content);
    echo "\n✅ admin.php patched successfully\n";
} else {
    echo "\n⚠️  No modifications made to admin.php\n";
}

// ─── PATCH admin.css ───
$css_file = __DIR__ . '/admin/admin.css';
$css_content = file_get_contents($css_file);

$css_additions = "\n
/* ============================================================
   VOLUNTEER MANAGEMENT STYLES
   ============================================================ */
.detail-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.detail-table tr {
    border-bottom: 1px solid var(--admin-border);
}
.detail-table td {
    padding: 8px 12px;
    vertical-align: top;
}
.detail-table td:first-child {
    font-weight: 600;
    color: var(--admin-text-muted);
    white-space: nowrap;
    width: 140px;
}
.btn-warning {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff;
}
.btn-warning:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}
";

if (strpos($css_content, 'VOLUNTEER MANAGEMENT STYLES') === false) {
    file_put_contents($css_file, $css_content . $css_additions);
    echo "✅ Added volunteer styles to admin.css\n";
} else {
    echo "⚠️  Volunteer styles already exist in admin.css\n";
}

// ─── PATCH style.css ───
$public_css_file = __DIR__ . '/style.css';
$public_css = file_get_contents($public_css_file);

$public_css_additions = "\n
/* ============================================================
   VOLUNTEER MODULE STYLES
   ============================================================ */
.volunteer-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    background: rgba(5, 150, 105, 0.1);
    color: #059669;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid rgba(5, 150, 105, 0.15);
}
.volunteer-badge i {
    font-size: 11px;
}
.vol-detail-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.vol-detail-table td {
    padding: 8px 12px;
    border-bottom: 1px solid var(--border);
}
.vol-detail-table td:first-child {
    font-weight: 600;
    color: var(--text-muted);
    width: 140px;
}
";

if (strpos($public_css, 'VOLUNTEER MODULE STYLES') === false) {
    file_put_contents($public_css_file, $public_css . $public_css_additions);
    echo "✅ Added volunteer styles to style.css\n";
} else {
    echo "⚠️  Volunteer styles already exist in style.css\n";
}

echo "\n✅ All patching complete!\n";
