<?php
require_once '../config.php';

// Admin authentication — protect this page
if (!is_admin_logged_in()) {
    redirect('admin-login.php');
}

$section = sanitize($_GET['section'] ?? 'pending');
$valid_sections = ['pending', 'approved', 'rejected', 'suspended', 'detail'];
if (!in_array($section, $valid_sections)) $section = 'pending';
$detail_id = intval($_GET['detail_id'] ?? 0);
$flash = get_flash_message();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'approve_volunteer') {
        $vid = intval($_POST['volunteer_id'] ?? 0);
        if ($vid > 0) {
            approve_volunteer($pdo, $vid, $_SESSION['user_id'] ?? 0);
            set_flash_message('success', 'Volunteer approved successfully.');
            redirect('admin-volunteers.php?section=approved');
        }
    }
    if ($action === 'reject_volunteer') {
        $vid = intval($_POST['volunteer_id'] ?? 0);
        $reason = sanitize($_POST['reject_reason'] ?? 'Other');
        if ($vid > 0) {
            reject_volunteer($pdo, $vid, $reason);
            set_flash_message('warning', 'Volunteer rejected.');
            redirect('admin-volunteers.php?section=rejected');
        }
    }
    if ($action === 'suspend_volunteer') {
        $vid = intval($_POST['volunteer_id'] ?? 0);
        $reason = sanitize($_POST['suspend_reason'] ?? 'Policy Violation');
        if ($vid > 0) {
            suspend_volunteer($pdo, $vid, $reason);
            set_flash_message('warning', 'Volunteer suspended.');
            redirect('admin-volunteers.php?section=suspended');
        }
    }
    if ($action === 'delete_volunteer') {
        $vid = intval($_POST['volunteer_id'] ?? 0);
        if ($vid > 0) {
            $pdo->prepare("DELETE FROM volunteers WHERE id = ?")->execute([$vid]);
            set_flash_message('success', 'Volunteer record deleted.');
            redirect('admin-volunteers.php');
        }
    }
}

// Fetch data
$counts = get_volunteer_counts($pdo);
$pending = get_volunteers_by_status($pdo, 'pending');
$approved = get_volunteers_by_status($pdo, 'approved');
$rejected = get_volunteers_by_status($pdo, 'rejected');
$suspended = get_volunteers_by_status($pdo, 'suspended');

$list = [];
$list_title = '';
switch ($section) {
    case 'pending': $list = $pending; $list_title = 'Pending Applications'; break;
    case 'approved': $list = $approved; $list_title = 'Approved Volunteers'; break;
    case 'rejected': $list = $rejected; $list_title = 'Rejected Applications'; break;
    case 'suspended': $list = $suspended; $list_title = 'Suspended Volunteers'; break;
}

// For detail view
$detail = null;
if ($section === 'detail' && $detail_id > 0) {
    $detail = get_volunteer_by_id($pdo, $detail_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Management | Sayog Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        body { background: #f1f5f9; font-family: 'Inter', sans-serif; margin: 0; padding: 24px; }
        .vol-container { max-width: 1400px; margin: 0 auto; }
        .vol-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
        .vol-header h1 { margin:0; font-size:24px; color:#0f172a; }
        .vol-header p { margin:4px 0 0; color:#64748b; font-size:14px; }
        .stats-bar { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:24px; }
        @media (max-width:768px) { .stats-bar { grid-template-columns:repeat(2,1fr); } }
        .stat-card { background:#fff; border-radius:14px; padding:20px; border:1px solid #e2e8f0; display:flex; align-items:center; gap:16px; transition:all 0.2s; }
        .stat-card:hover { box-shadow:0 4px 12px rgba(0,0,0,0.06); }
        .stat-icon { width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0; }
        .stat-icon.green { background:#dcfce7; color:#059669; }
        .stat-icon.blue { background:#dbeafe; color:#3b82f6; }
        .stat-icon.amber { background:#fef3c7; color:#d97706; }
        .stat-icon.purple { background:#f3e8ff; color:#7c3aed; }
        .stat-num { font-size:28px; font-weight:800; color:#0f172a; line-height:1; }
        .stat-label { font-size:12px; color:#64748b; margin-top:4px; text-transform:uppercase; letter-spacing:0.5px; }
        .vol-tabs { display:flex; gap:0; margin-bottom:24px; background:#fff; border-radius:12px; overflow:hidden; border:1px solid #e2e8f0; }
        .vol-tab { padding:12px 24px; font-size:14px; font-weight:600; color:#64748b; text-decoration:none; display:flex; align-items:center; gap:8px; border-bottom:2px solid transparent; transition:all 0.2s; }
        .vol-tab:hover { color:#059669; background:#f8fafc; }
        .vol-tab.active { color:#059669; border-bottom-color:#059669; background:#f0fdf4; }
        .card { background:#fff; border-radius:14px; border:1px solid #e2e8f0; overflow:hidden; }
        .card-header { display:flex; justify-content:space-between; align-items:center; padding:16px 20px; border-bottom:1px solid #e2e8f0; }
        .card-header h3 { margin:0; font-size:16px; color:#0f172a; display:flex; align-items:center; gap:8px; }
        .card-body { padding:0; overflow-x:auto; }
        table { width:100%; border-collapse:collapse; font-size:13px; }
        th { text-align:left; padding:12px 14px; font-weight:600; color:#64748b; background:#f8fafc; border-bottom:1px solid #e2e8f0; white-space:nowrap; }
        td { padding:10px 14px; border-bottom:1px solid #f1f5f9; color:#334155; }
        tr:hover td { background:#f8fafc; }
        .user-cell { display:flex; align-items:center; gap:10px; }
        .user-avatar { width:34px;height:34px;border-radius:50%;background:#059669;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0; }
        .user-name { font-weight:600; color:#0f172a; }
        .user-email { font-size:11px; color:#94a3b8; }
        .badge { display:inline-flex;align-items:center;padding:2px 10px;border-radius:999px;font-size:11px;font-weight:600; }
        .badge-green { background:#dcfce7;color:#059669; }
        .badge-red { background:#fef2f2;color:#dc2626; }
        .badge-yellow { background:#fef3c7;color:#d97706; }
        .badge-blue { background:#dbeafe;color:#3b82f6; }
        .badge-gray { background:#f1f5f9;color:#64748b; }
        .btn { display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;border:none;cursor:pointer;transition:all 0.15s; }
        .btn-sm { padding:4px 10px; font-size:11px; }
        .btn-green { background:#059669;color:#fff; }
        .btn-green:hover { background:#047857; }
        .btn-red { background:#ef4444;color:#fff; }
        .btn-red:hover { background:#dc2626; }
        .btn-gray { background:#f1f5f9;color:#334155; }
        .btn-gray:hover { background:#e2e8f0; }
        .btn-outline { background:transparent;border:1px solid #e2e8f0;color:#334155; }
        .btn-outline:hover { background:#f8fafc; }
        .btn-warning { background:#f59e0b;color:#fff; }
        .btn-warning:hover { background:#d97706; }
        .table-actions { display:flex;gap:4px;flex-wrap:wrap; }
        .empty-state { text-align:center;padding:48px 20px;color:#94a3b8; }
        .empty-state i { font-size:40px;margin-bottom:12px; }
        .empty-state h3 { margin:0 0 4px;color:#64748b; }
        .empty-state p { margin:0;font-size:14px; }
        .alert { padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:14px;display:flex;align-items:center;gap:10px; }
        .alert-success { background:#f0fdf4;color:#059669;border:1px solid #bbf7d0; }
        .alert-danger { background:#fef2f2;color:#dc2626;border:1px solid #fecaca; }
        .alert-warning { background:#fffbeb;color:#d97706;border:1px solid #fde68a; }
        .alert-info { background:#eff6ff;color:#3b82f6;border:1px solid #bfdbfe; }
        .doc-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:16px;margin-top:16px; }
        .doc-card { background:#f8fafc;border-radius:10px;padding:14px;border:1px solid #e2e8f0; }
        .doc-card h4 { margin:0 0 6px;font-size:13px;color:#374151; }
        .detail-grid { display:grid;grid-template-columns:1fr 1fr;gap:20px; }
        @media (max-width:900px) { .detail-grid { grid-template-columns:1fr; } }
        .info-card { background:#fff;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden; }
        .info-card h3 { margin:0;padding:14px 18px;font-size:15px;border-bottom:1px solid #e2e8f0;background:#fafafa; }
        .info-body { padding:14px 18px; }
        .info-table { width:100%; font-size:13px; }
        .info-table td { padding:7px 8px; border:none; }
        .info-table td:first-child { font-weight:600; color:#374151; width:140px; }
        .profile-header { display:flex;align-items:center;gap:24px;flex-wrap:wrap;padding:24px; }
        .profile-pic { width:90px;height:90px;border-radius:50%;overflow:hidden;border:3px solid #e2e8f0;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:#f1f5f9; }
        .profile-pic img { width:100%;height:100%;object-fit:cover; }
        .profile-pic i { font-size:32px;color:#94a3a8; }
        .back-link { display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#fff;border:1px solid #e2e8f0;border-radius:8px;text-decoration:none;color:#334155;font-size:13px;font-weight:600; }
        .back-link:hover { background:#f8fafc; }
        .action-bar { display:flex;gap:12px;margin-top:20px;flex-wrap:wrap;padding:20px;background:#fff;border-radius:14px;border:1px solid #e2e8f0; }
    </style>
</head>
<body>
<div class="vol-container">

    <!-- Header -->
    <div class="vol-header">
        <div>
            <h1><i class="fa-solid fa-hand-holding-heart" style="color:#059669;"></i> Volunteer Management</h1>
            <p>Review, verify, and manage all volunteer applications from one place</p>
        </div>
        <a href="admin.php" class="btn btn-gray"><i class="fa-solid fa-arrow-left"></i> Back to Admin</a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'warning' ? 'warning' : 'danger'); ?>">
            <i class="fa-solid <?php echo $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-clock"></i></div>
            <div><div class="stat-num"><?php echo (int)$counts['pending']; ?></div><div class="stat-label">Pending</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fa-solid fa-circle-check"></i></div>
            <div><div class="stat-num"><?php echo (int)$counts['approved']; ?></div><div class="stat-label">Approved</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber"><i class="fa-solid fa-ban"></i></div>
            <div><div class="stat-num"><?php echo (int)$counts['rejected']; ?></div><div class="stat-label">Rejected</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fa-solid fa-pause"></i></div>
            <div><div class="stat-num"><?php echo (int)$counts['suspended']; ?></div><div class="stat-label">Suspended</div></div>
        </div>
    </div>

    <?php if ($section === 'detail' && $detail): 
        $v = $detail;
        $docFields = ['profile_photo'=>'Profile Photo','citizenship_front'=>'Citizenship Front','citizenship_back'=>'Citizenship Back','national_id'=>'National ID','college_id'=>'College ID','driving_license'=>'Driving License'];
    ?>
        <!-- Back link -->
        <a href="admin-volunteers.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to All Volunteers</a>

        <!-- Profile Header -->
        <div class="card" style="margin-top:16px;">
            <div class="profile-header">
                <div class="profile-pic">
                    <?php if (!empty($v['profile_photo'])): ?>
                        <img src="../<?php echo htmlspecialchars($v['profile_photo']); ?>" alt="Profile">
                    <?php else: ?>
                        <i class="fa-solid fa-user"></i>
                    <?php endif; ?>
                </div>
                <div style="flex:1;">
                    <h2 style="margin:0 0 4px;font-size:22px;"><?php echo htmlspecialchars($v['full_name']); ?></h2>
                    <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:13px;color:#64748b;">
                        <span><i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($v['email']); ?></span>
                        <span><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($v['phone']); ?></span>
                        <?php if (!empty($v['volunteer_id'])): ?>
                            <span><i class="fa-solid fa-id-card"></i> ID: <?php echo htmlspecialchars($v['volunteer_id']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="badge badge-<?php echo $v['status']==='approved'?'green':($v['status']==='rejected'?'red':($v['status']==='suspended'?'yellow':'blue')); ?>" style="font-size:14px;padding:8px 20px;">
                    <?php echo strtoupper($v['status']); ?>
                </span>
            </div>
        </div>

        <!-- Two-column detail grid -->
        <div class="detail-grid" style="margin-top:20px;">
            <div class="info-card">
                <h3><i class="fa-solid fa-user"></i> Personal Info</h3>
                <div class="info-body">
                    <table class="info-table">
                        <tr><td>Full Name</td><td><?php echo htmlspecialchars($v['full_name']); ?></td></tr>
                        <tr><td>Email</td><td><?php echo htmlspecialchars($v['email']); ?></td></tr>
                        <tr><td>Phone</td><td><?php echo htmlspecialchars($v['phone']); ?></td></tr>
                        <tr><td>Date of Birth</td><td><?php echo htmlspecialchars($v['date_of_birth'] ?? 'N/A'); ?></td></tr>
                        <tr><td>Gender</td><td><?php echo ucfirst($v['gender'] ?? 'N/A'); ?></td></tr>
                        <tr><td>Occupation</td><td><?php echo htmlspecialchars($v['occupation'] ?? 'N/A'); ?></td></tr>
                        <tr><td>Emergency Contact</td><td><?php echo htmlspecialchars($v['emergency_contact'] ?? 'N/A'); ?></td></tr>
                    </table>
                </div>
            </div>
            <div class="info-card">
                <h3><i class="fa-solid fa-location-dot"></i> Address</h3>
                <div class="info-body">
                    <table class="info-table">
                        <tr><td>Address</td><td><?php echo htmlspecialchars($v['address'] ?? 'N/A'); ?></td></tr>
                        <tr><td>Municipality</td><td><?php echo htmlspecialchars($v['municipality'] ?? 'N/A'); ?></td></tr>
                        <tr><td>Ward No.</td><td><?php echo htmlspecialchars($v['ward_number'] ?? 'N/A'); ?></td></tr>
                        <tr><td>District</td><td><?php echo htmlspecialchars($v['district'] ?? 'N/A'); ?></td></tr>
                        <tr><td>Province</td><td><?php echo htmlspecialchars($v['province'] ?? 'N/A'); ?></td></tr>
                    </table>
                </div>
            </div>
            <div class="info-card">
                <h3><i class="fa-solid fa-truck"></i> Vehicle</h3>
                <div class="info-body">
                    <table class="info-table">
                        <tr><td>Type</td><td><span class="badge badge-gray"><?php echo ucfirst($v['vehicle_type']); ?></span></td></tr>
                        <tr><td>Vehicle No.</td><td><?php echo htmlspecialchars($v['vehicle_number'] ?? 'N/A'); ?></td></tr>
                        <tr><td>License No.</td><td><?php echo htmlspecialchars($v['license_number'] ?? 'N/A'); ?></td></tr>
                        <tr><td>Radius</td><td><?php echo $v['delivery_radius']; ?> km</td></tr>
                        <tr><td>Availability</td><td><span class="badge badge-blue"><?php echo str_replace(',',', ',ucfirst($v['availability'])); ?></span></td></tr>
                        <tr><td>Languages</td><td><?php echo htmlspecialchars($v['languages'] ?? 'N/A'); ?></td></tr>
                    </table>
                </div>
            </div>
            <div class="info-card">
                <h3><i class="fa-solid fa-calendar"></i> Application Info</h3>
                <div class="info-body">
                    <table class="info-table">
                        <tr><td>Applied</td><td><?php echo date('d M Y h:i A',strtotime($v['created_at'])); ?></td></tr>
                        <tr><td>Approved At</td><td><?php echo $v['approved_at'] ? date('d M Y h:i A',strtotime($v['approved_at'])) : '-'; ?></td></tr>
                        <tr><td>Volunteer ID</td><td><?php echo htmlspecialchars($v['volunteer_id'] ?? '-'); ?></td></tr>
                        <?php if (!empty($v['rejected_reason'])): ?>
                            <tr><td>Rejection</td><td style="color:#dc2626;"><?php echo htmlspecialchars($v['rejected_reason']); ?></td></tr>
                        <?php endif; ?>
                        <tr><td>Rating</td><td><?php echo $v['rating'] > 0 ? $v['rating'].'/5' : 'Not rated'; ?></td></tr>
                        <tr><td>Deliveries</td><td><?php echo (int)$v['completed_deliveries']; ?></td></tr>
                        <tr><td>Points</td><td><?php echo (int)$v['community_points']; ?></td></tr>
                    </table>
                </div>
            </div>
            <div class="info-card">
                <h3><i class="fa-solid fa-star"></i> Experience & Motivation</h3>
                <div class="info-body">
                    <?php if (!empty($v['previous_experience'])): ?>
                        <p style="margin:0 0 12px;font-size:13px;"><strong>Previous Experience:</strong><br><?php echo nl2br(htmlspecialchars($v['previous_experience'])); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($v['medical_training'])): ?>
                        <p style="margin:0 0 12px;font-size:13px;"><strong>Medical Training:</strong><br><?php echo htmlspecialchars($v['medical_training']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($v['first_aid'])): ?>
                        <p style="margin:0 0 12px;font-size:13px;color:#059669;"><i class="fa-solid fa-check-circle"></i> Has First Aid Knowledge</p>
                    <?php endif; ?>
                    <?php if (!empty($v['motivation'])): ?>
                        <p style="margin:0;font-size:13px;"><strong>Why Volunteer:</strong><br><?php echo nl2br(htmlspecialchars($v['motivation'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="info-card">
                <h3><i class="fa-solid fa-file-shield"></i> Identity Documents</h3>
                <div class="info-body">
                    <table class="info-table">
                        <?php foreach ($docFields as $dk => $dl): ?>
                            <tr>
                                <td><?php echo $dl; ?></td>
                                <td>
                                    <?php if (!empty($v[$dk])): ?>
                                        <a href="../<?php echo htmlspecialchars($v[$dk]); ?>" target="_blank" class="btn btn-outline btn-sm"><i class="fa-solid fa-eye"></i> View</a>
                                        <a href="../<?php echo htmlspecialchars($v[$dk]); ?>" download class="btn btn-outline btn-sm"><i class="fa-solid fa-download"></i> DL</a>
                                    <?php else: ?>
                                        <span style="color:#94a3a8;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-bar">
            <?php if ($v['status'] === 'pending'): ?>
                <form method="POST" style="display:inline-flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <input type="hidden" name="action" value="approve_volunteer">
                    <input type="hidden" name="volunteer_id" value="<?php echo $v['id']; ?>">
                    <button type="submit" class="btn btn-green" style="padding:10px 24px;"><i class="fa-solid fa-check"></i> Approve Volunteer</button>
                </form>
                <form method="POST" style="display:inline-flex;align-items:center;gap:8px;flex-wrap:wrap;" onsubmit="return confirm('Reject this applicant?');">
                    <input type="hidden" name="action" value="reject_volunteer">
                    <input type="hidden" name="volunteer_id" value="<?php echo $v['id']; ?>">
                    <select name="reject_reason" style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;min-width:160px;" required>
                        <option value="">Select reason...</option>
                        <option value="Incomplete Information">Incomplete Information</option>
                        <option value="Invalid Documents">Invalid Documents</option>
                        <option value="Duplicate Account">Duplicate Account</option>
                        <option value="Fake Information">Fake Information</option>
                        <option value="Other">Other</option>
                    </select>
                    <button type="submit" class="btn btn-red" style="padding:10px 24px;"><i class="fa-solid fa-xmark"></i> Reject</button>
                </form>
            <?php elseif ($v['status'] === 'approved'): ?>
                <form method="POST" style="display:inline-flex;align-items:center;gap:8px;flex-wrap:wrap;" onsubmit="return confirm('Suspend this volunteer?');">
                    <input type="hidden" name="action" value="suspend_volunteer">
                    <input type="hidden" name="volunteer_id" value="<?php echo $v['id']; ?>">
                    <select name="suspend_reason" style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;min-width:160px;" required>
                        <option value="">Select reason...</option>
                        <option value="Misconduct">Misconduct</option>
                        <option value="Fake Delivery">Fake Delivery</option>
                        <option value="Poor Rating">Poor Rating</option>
                        <option value="Policy Violation">Policy Violation</option>
                    </select>
                    <button type="submit" class="btn btn-warning" style="padding:10px 24px;"><i class="fa-solid fa-pause"></i> Suspend</button>
                </form>
            <?php endif; ?>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently delete this record?');">
                <input type="hidden" name="action" value="delete_volunteer">
                <input type="hidden" name="volunteer_id" value="<?php echo $v['id']; ?>">
                <button type="submit" class="btn btn-red" style="padding:10px 24px;background:#dc2626;"><i class="fa-solid fa-trash"></i> Delete Record</button>
            </form>
        </div>

    <?php else: ?>

        <!-- Tabs -->
        <div class="vol-tabs">
            <a href="admin-volunteers.php?section=pending" class="vol-tab <?php echo $section==='pending'?'active':''; ?>">
                <i class="fa-solid fa-clock"></i> Pending <span class="badge badge-yellow"><?php echo (int)$counts['pending']; ?></span>
            </a>
            <a href="admin-volunteers.php?section=approved" class="vol-tab <?php echo $section==='approved'?'active':''; ?>">
                <i class="fa-solid fa-circle-check"></i> Approved <span class="badge badge-green"><?php echo (int)$counts['approved']; ?></span>
            </a>
            <a href="admin-volunteers.php?section=rejected" class="vol-tab <?php echo $section==='rejected'?'active':''; ?>">
                <i class="fa-solid fa-ban"></i> Rejected <span class="badge badge-red"><?php echo (int)$counts['rejected']; ?></span>
            </a>
            <a href="admin-volunteers.php?section=suspended" class="vol-tab <?php echo $section==='suspended'?'active':''; ?>">
                <i class="fa-solid fa-pause"></i> Suspended <span class="badge badge-yellow"><?php echo (int)$counts['suspended']; ?></span>
            </a>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-list"></i> <?php echo $list_title; ?></h3>
                <span class="badge badge-blue"><?php echo count($list); ?> records</span>
            </div>
            <div class="card-body">
                <?php if (empty($list)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-inbox"></i>
                        <h3>No <?php echo strtolower($list_title); ?></h3>
                        <p>There are no volunteer applications in this category.</p>
                    </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th style="width:30px;">#</th>
                            <th>Volunteer</th>
                            <th>Contact</th>
                            <th>Location</th>
                            <th>Vehicle</th>
                            <th>Documents</th>
                            <th>Applied</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sn = 1; foreach ($list as $v): ?>
                        <tr>
                            <td style="color:#94a3a8;"><?php echo $sn++; ?></td>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar"><?php echo strtoupper(substr($v['full_name'],0,1)); ?></div>
                                    <div>
                                        <div class="user-name"><?php echo htmlspecialchars($v['full_name']); ?></div>
                                        <div class="user-email"><?php echo htmlspecialchars($v['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="font-size:12px;">
                                <?php echo htmlspecialchars($v['phone']); ?>
                                <?php if (!empty($v['emergency_contact'])): ?>
                                    <div style="color:#94a3a8;font-size:10px;">Emer: <?php echo htmlspecialchars($v['emergency_contact']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:12px;">
                                <?php echo htmlspecialchars($v['address'] ?? ''); ?>
                                <div style="color:#94a3a8;font-size:10px;">
                                    <?php echo htmlspecialchars($v['municipality'] ?? ''); ?>
                                    <?php echo !empty($v['ward_number']) ? 'Ward-'.$v['ward_number'] : ''; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-gray"><?php echo ucfirst($v['vehicle_type']); ?></span>
                                <div style="font-size:11px;color:#64748b;margin-top:2px;"><?php echo $v['delivery_radius']; ?>km</div>
                            </td>
                            <td>
                                <div style="display:flex;gap:3px;">
                                    <?php $hasDoc = false; ?>
                                    <?php foreach (['citizenship_front','citizenship_back','national_id','college_id','driving_license'] as $dk): ?>
                                        <?php if (!empty($v[$dk])): $hasDoc = true; ?>
                                            <a href="../<?php echo htmlspecialchars($v[$dk]); ?>" target="_blank" title="<?php echo $dk; ?>" style="padding:2px 5px;background:#f1f5f9;border-radius:4px;font-size:10px;color:#374151;text-decoration:none;white-space:nowrap;"><i class="fa-solid fa-file"></i></a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <?php if (!$hasDoc): ?><span style="font-size:10px;color:#94a3a8;">None</span><?php endif; ?>
                                </div>
                            </td>
                            <td style="white-space:nowrap;font-size:12px;"><?php echo date('d M Y',strtotime($v['created_at'])); ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="admin-volunteers.php?section=detail&detail_id=<?php echo $v['id']; ?>" class="btn btn-outline btn-sm" title="View Details"><i class="fa-solid fa-eye"></i></a>
                                    <?php if ($v['status'] === 'pending'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="approve_volunteer">
                                            <input type="hidden" name="volunteer_id" value="<?php echo $v['id']; ?>">
                                            <button type="submit" class="btn btn-green btn-sm" title="Approve"><i class="fa-solid fa-check"></i></button>
                                        </form>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Reject?');">
                                            <input type="hidden" name="action" value="reject_volunteer">
                                            <input type="hidden" name="volunteer_id" value="<?php echo $v['id']; ?>">
                                            <input type="hidden" name="reject_reason" value="Other">
                                            <button type="submit" class="btn btn-red btn-sm" title="Reject"><i class="fa-solid fa-xmark"></i></button>
                                        </form>
                                    <?php elseif ($v['status'] === 'approved'): ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Suspend?');">
                                            <input type="hidden" name="action" value="suspend_volunteer">
                                            <input type="hidden" name="volunteer_id" value="<?php echo $v['id']; ?>">
                                            <input type="hidden" name="suspend_reason" value="Policy Violation">
                                            <button type="submit" class="btn btn-warning btn-sm" title="Suspend"><i class="fa-solid fa-pause"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
