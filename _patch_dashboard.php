<?php
/**
 * Helper script to add volunteer module features to dashboard.php
 * Run: php _patch_dashboard.php
 */

$file = __DIR__ . '/dashboard.php';
$content = file_get_contents($file);

if ($content === false) {
    die("Failed to read dashboard.php\n");
}

$modified = false;

// 1. Add 'volunteer' to valid_pages
$old = "\$valid_pages = ['home', 'create-donation', 'donation_approval', 'request-donation', 'manage-donation', 'manage-request', 'track-donation', 'track-request', 'profile', 'notifications'];";
$new = "\$valid_pages = ['home', 'create-donation', 'donation_approval', 'request-donation', 'manage-donation', 'manage-request', 'track-donation', 'track-request', 'profile', 'notifications', 'volunteer'];";
if (strpos($content, $old) !== false) {
    $content = str_replace($old, $new, $content);
    echo "✅ Added 'volunteer' to valid_pages\n";
    $modified = true;
} else {
    echo "⚠️  Could not find valid_pages line\n";
}

// 2. Add 'volunteer' case in header title switch
$old = "case 'profile': echo \"Edit Profile\"; break;\n                            }";
$new = "case 'profile': echo \"Edit Profile\"; break;\n                                case 'volunteer': echo \"Volunteer Dashboard\"; break;\n                            }";
if (strpos($content, $old) !== false) {
    $content = str_replace($old, $new, $content);
    echo "✅ Added volunteer case to header title\n";
    $modified = true;
} else {
    echo "⚠️  Could not find profile case in header\n";
}

// 3. Add volunteer badge in sidebar profile section
// Find the member badge and add volunteer status after it
$search_badge = '<span class="profile-role-badge role-donor" style="background-color: rgba(16, 185, 129, 0.1); color: var(--primary);">
                        <span data-i18n="sidebar.member">Member</span>
                    </span>';
$volunteer_badge = '<span class="profile-role-badge role-donor" style="background-color: rgba(16, 185, 129, 0.1); color: var(--primary);">
                        <span data-i18n="sidebar.member">Member</span>
                    </span>
                    <?php
                    \$vol_status = get_volunteer_status(\$pdo, \$user_id);
                    if (\$vol_status && \$vol_status[\'status\'] === \'approved\'): ?>
                        <span class="profile-role-badge" style="background-color: rgba(5, 150, 105, 0.15); color: #059669; border: 1px solid rgba(5, 150, 105, 0.2); margin-left: 4px;">
                            <i class="fa-solid fa-circle-check" style="font-size: 9px;"></i> Verified Volunteer
                        </span>
                    <?php endif; ?>';
if (strpos($content, $search_badge) !== false) {
    $content = str_replace($search_badge, $volunteer_badge, $content);
    echo "✅ Added volunteer badge to sidebar profile\n";
    $modified = true;
} else {
    echo "⚠️  Could not find member badge in sidebar\n";
}

// 4. Add Volunteer nav link before logout
$search_logout = '<a href="logout.php" class="nav-item nav-item-logout">';
$volunteer_nav = '<?php
                    \$vol_status = get_volunteer_status(\$pdo, \$user_id);
                    if (\$vol_status && \$vol_status[\'status\'] === \'approved\'): ?>
                    <a href="dashboard.php?page=volunteer" class="nav-item <?php echo \$page === \'volunteer\' ? \'active\' : \'\'; ?>" style="border-top: 1px solid var(--border); margin-top: 8px; padding-top: 12px;">
                        <i class="fa-solid fa-hand-holding-heart" style="color: #059669;"></i>
                        <span style="font-weight: 700; color: #059669;">🧭 Volunteer Hub</span>
                    </a>
                    <?php elseif (!\$vol_status): ?>
                    <a href="become-volunteer.php" class="nav-item" style="background: rgba(5, 150, 105, 0.06); border: 1px dashed rgba(5, 150, 105, 0.2); border-radius: 10px; margin-top: 4px;">
                        <i class="fa-solid fa-user-plus" style="color: #059669;"></i>
                        <span style="font-weight: 600; color: #059669;">Become a Volunteer</span>
                    </a>
                    <?php elseif (\$vol_status[\'status\'] === \'pending\'): ?>
                    <a href="become-volunteer.php" class="nav-item" style="opacity: 0.7;">
                        <i class="fa-solid fa-clock" style="color: #f59e0b;"></i>
                        <span>⏳ Application Pending</span>
                    </a>
                    <?php elseif (\$vol_status[\'status\'] === \'rejected\'): ?>
                    <a href="become-volunteer.php" class="nav-item" style="opacity: 0.7;">
                        <i class="fa-solid fa-circle-exclamation" style="color: #ef4444;"></i>
                        <span>Reapply as Volunteer</span>
                    </a>
                    <?php endif; ?>
                <a href="logout.php" class="nav-item nav-item-logout">';
if (strpos($content, $search_logout) !== false) {
    $content = str_replace($search_logout, $volunteer_nav, $content);
    echo "✅ Added volunteer navigation links\n";
    $modified = true;
} else {
    echo "⚠️  Could not find logout link\n";
}

// 5. Add volunteer page content before the closing PHP tag of the view rendering
// Find the ending of the profile tab and add volunteer tab after it
$end_of_profile = "<!-- =============================================================\n                // TAB: NOTIFICATIONS\n                // =============================================================\n                }";
$volunteer_view = "<!-- =============================================================\n                // TAB: VOLUNTEER DASHBOARD\n                // =============================================================\n                } elseif (\$page === 'volunteer') {\n                    \$vol = get_volunteer_details(\$pdo, \$user_id);\n                    if (!\$vol || \$vol['status'] !== 'approved'):\n                        echo '<div class=\"alert alert-warning\"><i class=\"fa-solid fa-shield-halved\"></i> Access denied. Your volunteer status is not approved.</div>';\n                    else:\n                    ?>\n                    <div style=\"max-width: 1000px; margin: 0 auto;\">\n                        <!-- Volunteer Hero Profile Card -->\n                        <div style=\"background: linear-gradient(135deg, #059669 0%, #047857 100%); border-radius: 20px; padding: 32px; color: #fff; margin-bottom: 28px; position: relative; overflow: hidden;\">\n                            <div style=\"position: absolute; top: -30px; right: -30px; width: 200px; height: 200px; background: rgba(255,255,255,0.05); border-radius: 50%;\"></div>\n                            <div style=\"position: absolute; bottom: -50px; left: -20px; width: 150px; height: 150px; background: rgba(255,255,255,0.04); border-radius: 50%;\"></div>\n                            <div style=\"display: flex; align-items: center; gap: 24px; flex-wrap: wrap; position: relative; z-index: 1;\">\n                                <div style=\"width: 80px; height: 80px; border-radius: 50%; background: rgba(255,255,255,0.2); border: 3px solid rgba(255,255,255,0.3); display: flex; align-items: center; justify-content: center; font-size: 32px; overflow: hidden; flex-shrink: 0;\">\n                                    <?php if (!empty(\$vol['profile_photo'])): ?>\n                                        <img src=\"<?php echo htmlspecialchars(\$vol['profile_photo']); ?>\" style=\"width:100%;height:100%;object-fit:cover;\">\n                                    <?php else: ?>\n                                        <i class=\"fa-solid fa-user\"></i>\n                                    <?php endif; ?>\n                                </div>\n                                <div style=\"flex:1; min-width: 200px;\">\n                                    <div style=\"display: flex; align-items: center; gap: 10px; flex-wrap: wrap;\">\n                                        <h2 style=\"font-size: 26px; font-weight: 800; margin: 0; color: #fff;\"><?php echo htmlspecialchars(\$vol['full_name'] ?? \$vol['user_name']); ?></h2>\n                                        <span style=\"display: inline-flex; align-items: center; gap: 4px; padding: 4px 12px; background: rgba(255,255,255,0.2); border-radius: 999px; font-size: 12px; font-weight: 700;\">\n                                            <i class=\"fa-solid fa-circle-check\"></i> Verified Volunteer\n                                        </span>\n                                    </div>\n                                    <div style=\"display: flex; flex-wrap: wrap; gap: 16px; margin-top: 8px; font-size: 14px; opacity: 0.9;\">\n                                        <span><i class=\"fa-solid fa-id-card\"></i> ID: <?php echo htmlspecialchars(\$vol['volunteer_id']); ?></span>\n                                        <span><i class=\"fa-solid fa-calendar\"></i> Joined: <?php echo date('M Y', strtotime(\$vol['approved_at'])); ?></span>\n                                        <span><i class=\"fa-solid fa-truck\"></i> <?php echo ucfirst(\$vol['vehicle_type']); ?> | <?php echo \$vol['delivery_radius']; ?> km radius</span>\n                                    </div>\n                                </div>\n                                <div style=\"display: flex; gap: 16px; flex-wrap: wrap;\">\n                                    <div style=\"text-align: center; padding: 12px 20px; background: rgba(255,255,255,0.12); border-radius: 14px;\">\n                                        <div style=\"font-size: 28px; font-weight: 800;\"><?php echo (int)\$vol['completed_deliveries']; ?></div>\n                                        <div style=\"font-size: 11px; opacity: 0.8;\">Deliveries</div>\n                                    </div>\n                                    <div style=\"text-align: center; padding: 12px 20px; background: rgba(255,255,255,0.12); border-radius: 14px;\">\n                                        <div style=\"font-size: 28px; font-weight: 800;\"><?php echo number_format((float)\$vol['rating'], 1); ?></div>\n                                        <div style=\"font-size: 11px; opacity: 0.8;\">Rating</div>\n                                    </div>\n                                    <div style=\"text-align: center; padding: 12px 20px; background: rgba(255,255,255,0.12); border-radius: 14px;\">\n                                        <div style=\"font-size: 28px; font-weight: 800;\"><?php echo (int)\$vol['community_points']; ?></div>\n                                        <div style=\"font-size: 11px; opacity: 0.8;\">Points</div>\n                                    </div>\n                                </div>\n                            </div>\n                        </div>\n\n                        <!-- Online Status Toggle -->\n                        <div style=\"background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 20px 24px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;\">\n                            <div>\n                                <h3 style=\"font-size: 16px; font-weight: 700; margin: 0 0 4px;\">Online Status</h3>\n                                <p style=\"margin: 0; font-size: 13px; color: var(--text-secondary);\">Set your availability to receive delivery requests.</p>\n                            </div>\n                            <div style=\"display: flex; gap: 8px;\">\n                                <?php\n                                \$statuses = ['available' => ['#10b981', 'fa-circle-check', 'Available'], 'busy' => ['#f59e0b', 'fa-clock', 'Busy'], 'offline' => ['#94a3b8', 'fa-circle', 'Offline']];\n                                foreach (\$statuses as \$val => [\$color, \$icon, \$label]):\n                                    \$active = \$vol['online_status'] === \$val;\n                                ?>\n                                <form action=\"dashboard.php?page=volunteer\" method=\"POST\" style=\"display:inline;\">\n                                    <input type=\"hidden\" name=\"action\" value=\"update_volunteer_status\">\n                                    <input type=\"hidden\" name=\"online_status\" value=\"<?php echo \$val; ?>\">\n                                    <button type=\"submit\" style=\"display:flex; align-items:center; gap:6px; padding:8px 16px; border:2px solid <?php echo \$active ? \$color : 'var(--border)'; ?>; border-radius:10px; background:<?php echo \$active ? 'rgba('.($val==='available'?'16,185,129':($val==='busy'?'245,158,11':'148,163,184')).',0.1)' : 'transparent'; ?>; cursor:pointer; font-size:13px; font-weight:600; color:<?php echo \$active ? \$color : 'var(--text-secondary)'; ?>; transition:all 0.2s;\">\n                                        <i class=\"fa-solid <?php echo \$icon; ?>\" style=\"color:<?php echo \$color; ?>;\"></i> <?php echo \$label; ?>\n                                    </button>\n                                </form>\n                                <?php endforeach; ?>\n                            </div>\n                        </div>\n\n                        <!-- Quick Stats & Info Cards -->\n                        <div style=\"display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 28px;\">\n                            <div style=\"background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 24px;\">\n                                <div style=\"display: flex; align-items: center; gap: 12px; margin-bottom: 16px;\">\n                                    <div style=\"width: 44px; height: 44px; background: rgba(5,150,105,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: #059669;\">\n                                        <i class=\"fa-solid fa-user\"></i>\n                                    </div>\n                                    <div>\n                                        <div style=\"font-size: 13px; font-weight: 600; color: var(--text-secondary);\">Personal Info</div>\n                                    </div>\n                                </div>\n                                <div style=\"display: grid; gap: 8px; font-size: 13px;\">\n                                    <div><strong>Phone:</strong> <?php echo htmlspecialchars(\$vol['phone']); ?></div>\n                                    <div><strong>Email:</strong> <?php echo htmlspecialchars(\$vol['email']); ?></div>\n                                    <div><strong>Address:</strong> <?php echo htmlspecialchars(\$vol['address'] ?: 'N/A'); ?></div>\n                                    <div><strong>Vehicle:</strong> <?php echo ucfirst(\$vol['vehicle_type']); ?> <?php echo \$vol['vehicle_number'] ? '(' . htmlspecialchars(\$vol['vehicle_number']) . ')' : ''; ?></div>\n                                    <div><strong>Languages:</strong> <?php echo htmlspecialchars(\$vol['languages'] ?: 'N/A'); ?></div>\n                                </div>\n                            </div>\n\n                            <div style=\"background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 24px;\">\n                                <div style=\"display: flex; align-items: center; gap: 12px; margin-bottom: 16px;\">\n                                    <div style=\"width: 44px; height: 44px; background: rgba(245,158,11,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: #f59e0b;\">\n                                        <i class=\"fa-solid fa-clock\"></i>\n                                    </div>\n                                    <div>\n                                        <div style=\"font-size: 13px; font-weight: 600; color: var(--text-secondary);\">Availability</div>\n                                    </div>\n                                </div>\n                                <div style=\"display: flex; flex-wrap: wrap; gap: 6px;\">\n                                    <?php foreach (explode(',', \$vol['availability']) as \$a): \n                                        \$labels = ['morning'=>'🌅 Morning', 'afternoon'=>'☀️ Afternoon', 'evening'=>'🌆 Evening', 'weekend'=>'📅 Weekend', 'always'=>'🔄 Always'];\n                                        \$label = \$labels[trim(\$a)] ?? ucfirst(trim(\$a));\n                                    ?>\n                                        <span style=\"padding: 6px 12px; background: rgba(5,150,105,0.08); border-radius: 8px; font-size: 12px; font-weight: 600; color: #059669;\"><?php echo \$label; ?></span>\n                                    <?php endforeach; ?>\n                                </div>\n                                <div style=\"margin-top: 12px; font-size: 13px;\">\n                                    <strong>Delivery Radius:</strong> <?php echo \$vol['delivery_radius']; ?> km\n                                </div>\n                            </div>\n\n                            <div style=\"background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 24px;\">\n                                <div style=\"display: flex; align-items: center; gap: 12px; margin-bottom: 16px;\">\n                                    <div style=\"width: 44px; height: 44px; background: rgba(139,92,246,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: #8b5cf6;\">\n                                        <i class=\"fa-solid fa-medal\"></i>\n                                    </div>\n                                    <div>\n                                        <div style=\"font-size: 13px; font-weight: 600; color: var(--text-secondary);\">Stats & Certificates</div>\n                                    </div>\n                                </div>\n                                <div style=\"display: grid; gap: 10px;\">\n                                    <?php if (\$vol['first_aid']): ?>\n                                        <span style=\"display:flex;align-items:center;gap:6px;font-size:13px;color:#059669;\"><i class=\"fa-solid fa-circle-check\"></i> First Aid Certified</span>\n                                    <?php endif; ?>\n                                    <?php if (!empty(\$vol['medical_training'])): ?>\n                                        <span style=\"display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text-secondary);\"><i class=\"fa-solid fa-stethoscope\"></i> <?php echo htmlspecialchars(\$vol['medical_training']); ?></span>\n                                    <?php endif; ?>\n                                    <?php if (!empty(\$vol['previous_experience'])): ?>\n                                        <span style=\"display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text-secondary);\"><i class=\"fa-solid fa-briefcase\"></i> Has prior experience</span>\n                                    <?php endif; ?>\n                                    <span style=\"display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text-secondary);\"><i class=\"fa-solid fa-trophy\"></i> <?php echo (int)\$vol['community_points']; ?> Community Points</span>\n                                </div>\n                                <?php if (!empty(\$vol['certificate_path'])): ?>\n                                    <a href=\"<?php echo htmlspecialchars(\$vol['certificate_path']); ?>\" target=\"_blank\" class=\"btn btn-sm btn-outline\" style=\"margin-top:12px;\"><i class=\"fa-solid fa-file-pdf\"></i> View Certificate</a>\n                                <?php endif; ?>\n                            </div>\n                        </div>\n\n                        <!-- Recent Activity / Deliveries -->\n                        <div style=\"background: var(--surface); border: 1px solid var(--border); border-radius: 16px; overflow: hidden;\">\n                            <div style=\"padding: 18px 24px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 10px;\">\n                                <i class=\"fa-solid fa-clock-rotate-left\" style=\"color: #059669;\"></i>\n                                <h3 style=\"font-size: 16px; font-weight: 700; margin: 0;\">Recent Activity</h3>\n                            </div>\n                            <div style=\"padding: 24px; text-align: center; color: var(--text-muted);\">\n                                <i class=\"fa-solid fa-box-open\" style=\"font-size: 36px; margin-bottom: 12px; display: block;\"></i>\n                                <p style=\"font-size: 14px; margin: 0;\">No delivery requests yet. Your activity will appear here once you start delivering.</p>\n                            </div>\n                        </div>\n                    </div>\n                    <?php endif; }";

// Insert volunteer view after profile, before notifications
// We need to find the pattern that ends the profile tab section
$end_of_profile_pattern = "// =============================================================\n                // TAB: NOTIFICATIONS\n                // =============================================================\n                } elseif (\$page === 'notifications') {";
if (strpos($content, $end_of_profile_pattern) !== false) {
    $content = str_replace($end_of_profile_pattern, $volunteer_view . "\n\n                " . $end_of_profile_pattern, $content);
    echo "✅ Added volunteer dashboard view\n";
    $modified = true;
} else {
    echo "⚠️  Could not find notifications tab start\n";
    // Try alternative pattern
    $alt_pattern = "} elseif (\$page === 'notifications') {";
    $alt_full = strpos($content, $alt_pattern);
    if ($alt_full !== false) {
        // Check if the pattern before it includes profile ending
        $content = substr_replace($content, $volunteer_view . "\n\n                " . $alt_pattern, $alt_full, strlen($alt_pattern));
        echo "✅ Added volunteer dashboard view (alt)\n";
        $modified = true;
    } else {
        echo "⚠️  Could not find notifications tab start (alt)\n";
    }
}

// 6. Add POST handler for volunteer status update
$search_profile_action = "// Action: Update Profile";
$volunteer_status_action = "// Action: Update Volunteer Online Status\n    if (\$action === 'update_volunteer_status') {\n        \$online_status = sanitize(\$_POST['online_status'] ?? '');\n        if (in_array(\$online_status, ['available', 'busy', 'offline'])) {\n            \$stmt = \$pdo->prepare(\"UPDATE volunteers SET online_status = ? WHERE user_id = ?\");\n            \$stmt->execute([\$online_status, \$user_id]);\n            set_flash_message('success', 'Status updated to ' . ucfirst(\$online_status) . '.');\n            redirect('dashboard.php?page=volunteer');\n        }\n    }\n\n    // Action: Update Profile";
if (strpos($content, $search_profile_action) !== false) {
    $content = str_replace($search_profile_action, $volunteer_status_action, $content);
    echo "✅ Added volunteer status update action handler\n";
    $modified = true;
} else {
    echo "⚠️  Could not find profile action start\n";
}

if ($modified) {
    file_put_contents($file, $content);
    echo "\n✅ All patches applied successfully to dashboard.php\n";
} else {
    echo "\n⚠️  No modifications were made\n";
}
