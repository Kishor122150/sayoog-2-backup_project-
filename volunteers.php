<?php
require_once 'config.php';

// Auto-expire past donations
$pdo->exec("UPDATE donations SET status = 'cancelled' WHERE status IN ('available', 'requested', 'accepted') AND expiry_time < NOW()");

// Fetch all approved volunteers
$approved_volunteers = get_volunteers_by_status($pdo, 'approved');
$volunteer_counts = get_volunteer_counts($pdo);
?>
<?php
$page_title = 'Our Volunteers | Sayog';
$active_page = 'volunteers';
require_once 'header.php';
?>
    <style>
      @keyframes volFadeUp { 0%{opacity:0;transform:translateY(24px)} 100%{opacity:1;transform:translateY(0)} }
      @keyframes volBgDrift { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
      @keyframes volFloat { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-10px)} }
      .volunteers-section { padding:80px 24px; position:relative; overflow:hidden; background:linear-gradient(135deg,#f0fdf4 0%,#ecfdf5 30%,#f4fbf7 70%,#f0fdf4 100%); background-size:300% 300%; animation:volBgDrift 14s ease-in-out infinite; isolation:isolate; min-height:60vh; }
      [data-theme="dark"] .volunteers-section { background:linear-gradient(135deg,#0a1f1a 0%,#0d2b22 30%,#0f1f1a 70%,#0a1f1a 100%); }
      .vol-blob { position:absolute; border-radius:50%; filter:blur(80px); z-index:0; pointer-events:none; opacity:0.35; }
      .vol-blob--1 { width:280px;height:280px;background:rgba(5,150,105,0.07);top:-60px;left:-40px;animation:volFloat 9s ease-in-out infinite; }
      .vol-blob--2 { width:220px;height:220px;background:rgba(16,185,129,0.05);bottom:-50px;right:-30px;animation:volFloat 11s ease-in-out infinite reverse; }
      .vol-inner { max-width:1100px; margin:0 auto; position:relative; z-index:1; }
      .vol-header { text-align:center; margin-bottom:48px; }
      .vol-header h2 { font-size:2rem; font-weight:800; color:#0f172a; margin:0 0 12px; letter-spacing:-0.02em; }
      .vol-header p { font-size:1.05rem; color:#4b5563; margin:0; line-height:1.6; max-width:580px; margin-left:auto; margin-right:auto; }
      .vol-stats { display:flex; justify-content:center; gap:24px; flex-wrap:wrap; margin-bottom:36px; }
      .vol-stat { text-align:center; padding:12px 24px; background:rgba(255,255,255,0.6); backdrop-filter:blur(8px); border-radius:16px; border:1px solid rgba(5,150,105,0.08); min-width:120px; }
      .vol-stat .num { font-size:28px; font-weight:800; color:#059669; display:block; }
      .vol-stat .lbl { font-size:11px; color:#6b7280; text-transform:uppercase; letter-spacing:0.04em; font-weight:600; }
      .vol-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:24px; }
      .vol-card { background:rgba(255,255,255,0.75); backdrop-filter:blur(12px); border:1px solid rgba(5,150,105,0.08); border-radius:20px; padding:28px 20px 22px; text-align:center; transition:all 0.4s cubic-bezier(0.34,1.56,0.64,1); }
      .vol-card:hover { transform:translateY(-6px) scale(1.02); border-color:rgba(5,150,105,0.2); }
      .vol-avatar { width:80px; height:80px; border-radius:50%; overflow:hidden; margin:0 auto 14px; border:3px solid #e5e7eb; display:flex; align-items:center; justify-content:center; background:#f3f4f6; }
      .vol-avatar img { width:100%;height:100%;object-fit:cover; }
      .vol-avatar i { font-size:28px;color:#9ca3af; }
      .vol-card h3 { font-size:1.05rem; font-weight:700; color:#0f172a; margin:0 0 4px; }
      .vol-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 12px; border-radius:999px; font-size:11px; font-weight:600; background:rgba(5,150,105,0.1); color:#059669; margin-bottom:10px; }
      .vol-details { font-size:13px; color:#4b5563; display:flex; flex-direction:column; gap:6px; margin-top:8px; padding-top:12px; border-top:1px solid #e5e7eb; }
      .vol-details span { display:flex; align-items:center; justify-content:center; gap:6px; }
      .vol-details i { width:16px; color:#059669; }
      .vol-jdate { font-size:11px; color:#9ca3af; margin-top:10px; }
      .vol-details-btn { display:inline-flex; align-items:center; gap:6px; margin-top:12px; padding:7px 18px; border:1px solid rgba(5,150,105,0.2); border-radius:8px; background:rgba(5,150,105,0.06); color:#059669; font-size:13px; font-weight:600; cursor:pointer; transition:all 0.3s cubic-bezier(0.34,1.56,0.64,1); }
      .vol-details-btn:hover { background:#059669; color:#fff; border-color:#059669; }
      .vol-modal-overlay { display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.6); backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px); align-items:center; justify-content:center; padding:24px; animation:volModalFadeIn 0.2s ease; }
      .vol-modal-overlay.active { display:flex; animation:volModalFadeIn 0.25s ease; }
      @keyframes volModalSlideUp { 0% { opacity:0;transform:translateY(40px) scale(0.92); } 60% { transform:translateY(-5px) scale(1.01); } 100% { opacity:1;transform:translateY(0) scale(1); } }
      .vol-modal { background:#fff; border-radius:24px; max-width:680px; width:100%; max-height:90vh; overflow-y:auto; padding:0; box-shadow:0 30px 80px rgba(0,0,0,0.18), 0 10px 24px rgba(0,0,0,0.06); animation:volModalSlideUp 0.45s cubic-bezier(0.34,1.56,0.64,1); position:relative; }
      [data-theme="dark"] .vol-modal { background:#1e293b; }
      .vol-modal-close { position:absolute; top:16px; right:16px; width:40px; height:40px; border-radius:50%; border:none; background:rgba(0,0,0,0.08); color:#6b7280; font-size:22px; cursor:pointer; display:flex; align-items:center; justify-content:center; z-index:2; transition:all 0.3s cubic-bezier(0.34,1.56,0.64,1); box-shadow:0 2px 8px rgba(0,0,0,0.06); }
      .vol-modal-close:hover { background:#ef4444; color:#fff; transform:rotate(90deg) scale(1.1); box-shadow:0 4px 12px rgba(239,68,68,0.3); }
      .vol-modal-close:active { transform:rotate(90deg) scale(0.95); }
      .vol-modal-header { background:linear-gradient(135deg,#059669 0%,#047857 40%,#065f46 100%); padding:36px 32px 28px; text-align:center; color:#fff; border-radius:24px 24px 0 0; position:relative; overflow:hidden; }
      .vol-modal-header::before { content:''; position:absolute; width:220px; height:220px; background:rgba(255,255,255,0.06); border-radius:50%; top:-70px; right:-50px; pointer-events:none; }
      .vol-modal-header::after { content:''; position:absolute; width:160px; height:160px; background:rgba(255,255,255,0.04); border-radius:50%; bottom:-40px; left:-30px; pointer-events:none; }
      @keyframes volModalFadeIn { from{opacity:0} to{opacity:1} }
      .vol-modal-avatar { width:88px; height:88px; border-radius:50%; border:4px solid rgba(255,255,255,0.3); overflow:hidden; margin:0 auto 12px; display:flex; align-items:center; justify-content:center; background:rgba(255,255,255,0.15); }
      .vol-modal-header h2 { font-size:22px;font-weight:700;margin:0 0 4px; }
      .vol-modal-body { padding:28px 32px 32px; }
      .vol-detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px 20px; }
      .vol-detail-full { grid-column:1/-1; }
      .vol-modal-stats { display:flex; gap:16px; flex-wrap:wrap; margin-top:16px; }
      .vol-modal-stat { flex:1; min-width:100px; text-align:center; padding:12px 8px; border-radius:12px; background:rgba(5,150,105,0.05); border:1px solid rgba(5,150,105,0.08); }
      .vol-modal-stat .num { font-size:22px; font-weight:800; color:#059669; display:block; }
      .vol-modal-stat .lbl { font-size:10px; color:#6b7280; text-transform:uppercase; font-weight:600; }
      @media (max-width:480px) { .vol-detail-grid { grid-template-columns:1fr; } .vol-modal-body { padding:20px; } .vol-modal-header { padding:24px 20px 18px; } }
    </style>


    <main class="site-main">
        <section class="volunteers-section">
            <div class="vol-blob vol-blob--1"></div>
            <div class="vol-blob vol-blob--2"></div>
            <div class="vol-inner">
                <div class="vol-header">
                    <h2><i class="fa-solid fa-hand-holding-heart" style="font-size:1.5rem;background:linear-gradient(135deg,#059669,#10b981);-webkit-background-clip:text;-webkit-text-fill-color:transparent;"></i> Our Volunteers</h2>
                    <p>Meet the dedicated volunteers who help deliver food to communities in need. All volunteers are verified and committed to serving.</p>
                </div>

                <?php if (!empty($volunteer_counts)): ?>
                <div class="vol-stats">
                    <div class="vol-stat">
                        <span class="num"><?php echo (int)($volunteer_counts['approved'] ?? 0); ?></span>
                        <span class="lbl">Active Volunteers</span>
                    </div>
                    <div class="vol-stat">
                        <span class="num"><?php echo (int)($volunteer_counts['total'] ?? 0); ?></span>
                        <span class="lbl">Total Applicants</span>
                    </div>
                    <div class="vol-stat">
                        <span class="num"><?php echo (int)($volunteer_counts['pending'] ?? 0); ?></span>
                        <span class="lbl">Pending Review</span>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (empty($approved_volunteers)): ?>
                    <div class="empty-state" style="text-align:center;padding:60px 24px;">
                        <i class="fa-solid fa-user-group" style="font-size:48px;color:#9ca3af;margin-bottom:16px;"></i>
                        <h3 style="color:#0f172a;margin:0 0 8px;">No volunteers yet</h3>
                        <p style="color:#6b7280;margin:0;">Volunteers will appear here once they are approved. Interested in joining? <a href="become-volunteer.php" style="color:#059669;font-weight:600;">Apply now</a>.</p>
                    </div>
                <?php else: ?>
                    <div class="vol-grid">
                        <?php foreach ($approved_volunteers as $v): ?>
                            <div class="vol-card">
                                <div class="vol-avatar">
                                    <?php if (!empty($v['profile_photo'])): ?>
                                        <img src="<?php echo htmlspecialchars($v['profile_photo']); ?>" alt="<?php echo htmlspecialchars($v['full_name']); ?>">
                                    <?php else: ?>
                                        <i class="fa-solid fa-user"></i>
                                    <?php endif; ?>
                                </div>
                                <h3><?php echo htmlspecialchars($v['full_name']); ?></h3>
                                <div class="vol-badge"><i class="fa-solid fa-circle-check"></i> Verified Volunteer</div>
                                <div class="vol-details">
                                    <span><i class="fa-solid fa-truck"></i> <?php echo ucfirst($v['vehicle_type']); ?></span>
                                    <span><i class="fa-solid fa-location-dot"></i> <?php echo $v['delivery_radius']; ?> km radius</span>
                                    <span><i class="fa-solid fa-clock"></i> <?php echo str_replace(',', ', ', ucfirst($v['availability'])); ?></span>
                                    <?php if (!empty($v['languages'])): ?>
                                        <span><i class="fa-solid fa-language"></i> <?php echo htmlspecialchars($v['languages']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="vol-jdate">Joined <?php echo date('M Y', strtotime($v['created_at'])); ?></div>
                                <button class="vol-details-btn" onclick="openVolModal(<?php echo $v['id']; ?>)">
                                    <i class="fa-solid fa-eye"></i> Show Details
                                </button>
                            </div>

                            <!-- Volunteer Details Modal -->
                            <div class="vol-modal-overlay" id="volModalOverlay_<?php echo $v['id']; ?>" onclick="closeVolModal(<?php echo $v['id']; ?>)">
                                <div class="vol-modal" onclick="event.stopPropagation()">
                                    <button class="vol-modal-close" onclick="closeVolModal(<?php echo $v['id']; ?>)"><i class="fa-solid fa-xmark"></i></button>

                                    <!-- Header -->
                                    <div class="vol-modal-header">
                                        <div class="vol-modal-avatar">
                                            <?php if (!empty($v['profile_photo'])): ?>
                                                <img src="<?php echo htmlspecialchars($v['profile_photo']); ?>" alt="<?php echo htmlspecialchars($v['full_name']); ?>">
                                            <?php else: ?>
                                                <i class="fa-solid fa-user"></i>
                                            <?php endif; ?>
                                        </div>
                                        <h2><?php echo htmlspecialchars($v['full_name']); ?></h2>
                                        <?php if (!empty($v['volunteer_id'])): ?>
                                            <div class="vol-modal-volid"><i class="fa-solid fa-id-card"></i> <?php echo htmlspecialchars($v['volunteer_id']); ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Body -->
                                    <div class="vol-modal-body">
                                        <!-- Stats Row -->
                                        <div class="vol-modal-stats">
                                            <div class="vol-modal-stat">
                                                <span class="num"><?php echo (int)$v['completed_deliveries']; ?></span>
                                                <span class="lbl">Deliveries</span>
                                            </div>
                                            <div class="vol-modal-stat">
                                                <span class="num"><?php echo number_format((float)$v['rating'], 1); ?></span>
                                                <span class="lbl">Rating</span>
                                            </div>
                                            <div class="vol-modal-stat">
                                                <span class="num"><?php echo (int)$v['community_points']; ?></span>
                                                <span class="lbl">Points</span>
                                            </div>
                                        </div>

                                        <!-- Personal Information -->
                                        <div class="vol-modal-section">
                                            <h3><i class="fa-solid fa-user-circle"></i> Personal Information</h3>
                                            <div class="vol-detail-grid">
                                                <div class="vol-detail-item">
                                                    <label>Email</label>
                                                    <span><?php echo htmlspecialchars($v['email'] ?? '—'); ?></span>
                                                </div>
                                                <div class="vol-detail-item">
                                                    <label>Phone</label>
                                                    <span><?php echo htmlspecialchars($v['phone'] ?? '—'); ?></span>
                                                </div>
                                                <?php if (!empty($v['date_of_birth'])): ?>
                                                <div class="vol-detail-item">
                                                    <label>Date of Birth</label>
                                                    <span><?php echo date('M d, Y', strtotime($v['date_of_birth'])); ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <?php if (!empty($v['gender'])): ?>
                                                <div class="vol-detail-item">
                                                    <label>Gender</label>
                                                    <span><?php echo ucfirst($v['gender']); ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <?php if (!empty($v['occupation'])): ?>
                                                <div class="vol-detail-item">
                                                    <label>Occupation</label>
                                                    <span><?php echo htmlspecialchars($v['occupation']); ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <?php if (!empty($v['emergency_contact'])): ?>
                                                <div class="vol-detail-item">
                                                    <label>Emergency Contact</label>
                                                    <span><?php echo htmlspecialchars($v['emergency_contact']); ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <div class="vol-detail-item vol-detail-full">
                                                    <label>Address</label>
                                                    <span><?php
                                                        $addr_parts = array_filter([$v['address'] ?? '', $v['municipality'] ?? '', $v['ward_number'] ? 'Ward ' . $v['ward_number'] : '', $v['district'] ?? '', $v['province'] ?? '']);
                                                        echo !empty($addr_parts) ? htmlspecialchars(implode(', ', $addr_parts)) : '—';
                                                    ?></span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Vehicle & Availability -->
                                        <div class="vol-modal-section">
                                            <h3><i class="fa-solid fa-truck"></i> Vehicle &amp; Availability</h3>
                                            <div class="vol-detail-grid">
                                                <div class="vol-detail-item">
                                                    <label>Vehicle Type</label>
                                                    <span><?php echo ucfirst($v['vehicle_type'] ?? 'Walking'); ?></span>
                                                </div>
                                                <div class="vol-detail-item">
                                                    <label>Delivery Radius</label>
                                                    <span><?php echo (int)$v['delivery_radius']; ?> km</span>
                                                </div>
                                                <?php if (!empty($v['vehicle_number'])): ?>
                                                <div class="vol-detail-item">
                                                    <label>Vehicle Number</label>
                                                    <span><?php echo htmlspecialchars($v['vehicle_number']); ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <?php if (!empty($v['license_number'])): ?>
                                                <div class="vol-detail-item">
                                                    <label>License Number</label>
                                                    <span><?php echo htmlspecialchars($v['license_number']); ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <div class="vol-detail-item">
                                                    <label>Availability</label>
                                                    <span><?php echo str_replace(',', ', ', ucfirst($v['availability'] ?? 'Always')); ?></span>
                                                </div>
                                                <?php if (!empty($v['online_status'])): ?>
                                                <div class="vol-detail-item">
                                                    <label>Online Status</label>
                                                    <span><?php echo ucfirst($v['online_status']); ?></span>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Experience & Motivation -->
                                        <?php if (!empty($v['previous_experience']) || !empty($v['motivation']) || !empty($v['languages']) || !empty($v['medical_training']) || !empty($v['first_aid'])): ?>
                                        <div class="vol-modal-section">
                                            <h3><i class="fa-solid fa-star"></i> Experience &amp; Background</h3>
                                            <div class="vol-detail-grid">
                                                <?php if (!empty($v['languages'])): ?>
                                                <div class="vol-detail-item">
                                                    <label>Languages</label>
                                                    <span><?php echo htmlspecialchars($v['languages']); ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <?php if ($v['first_aid']): ?>
                                                <div class="vol-detail-item">
                                                    <label>First Aid</label>
                                                    <span><i class="fa-solid fa-check-circle" style="color:#059669;"></i> Certified</span>
                                                </div>
                                                <?php endif; ?>
                                                <?php if (!empty($v['previous_experience'])): ?>
                                                <div class="vol-detail-item vol-detail-full">
                                                    <label>Previous Experience</label>
                                                    <p class="vol-detail-text"><?php echo nl2br(htmlspecialchars($v['previous_experience'])); ?></p>
                                                </div>
                                                <?php endif; ?>
                                                <?php if (!empty($v['medical_training'])): ?>
                                                <div class="vol-detail-item vol-detail-full">
                                                    <label>Medical Training</label>
                                                    <p class="vol-detail-text"><?php echo nl2br(htmlspecialchars($v['medical_training'])); ?></p>
                                                </div>
                                                <?php endif; ?>
                                                <?php if (!empty($v['motivation'])): ?>
                                                <div class="vol-detail-item vol-detail-full">
                                                    <label>Why I Want to Volunteer</label>
                                                    <p class="vol-detail-text" style="font-style:italic;color:#059669;">
                                                        "<?php echo nl2br(htmlspecialchars($v['motivation'])); ?>"
                                                    </p>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <!-- Footer -->
                                        <div class="vol-modal-section" style="border-bottom:none;margin-bottom:0;padding-bottom:0;">
                                            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
                                                <span class="vol-badge"><i class="fa-solid fa-circle-check"></i> Verified Volunteer</span>
                                                <span style="font-size:12px;color:#9ca3af;">Joined <?php echo date('F Y', strtotime($v['created_at'])); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

  <script>
    /* ── Volunteer Modal Open/Close ── */
    function openVolModal(id) {
      var overlay = document.getElementById('volModalOverlay_' + id);
      if (overlay) {
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
      }
    }
    function closeVolModal(id) {
      var overlay = document.getElementById('volModalOverlay_' + id);
      if (overlay) {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
      }
    }
    /* Close all volunteer modals on Escape key */
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        var overlays = document.querySelectorAll('.vol-modal-overlay.active');
        overlays.forEach(function(o) { o.classList.remove('active'); });
        document.body.style.overflow = '';
      }
    });
  </script>
    <?php require_once 'footer.php'; ?>
