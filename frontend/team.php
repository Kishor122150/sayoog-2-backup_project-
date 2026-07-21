<?php
require_once '../config.php';

// Auto-expire past donations
$pdo->exec("UPDATE donations SET status = 'cancelled' WHERE status IN ('available', 'requested', 'accepted') AND expiry_time < NOW()");

// Fetch all active team members
$team_members = $pdo->query("SELECT * FROM team_members WHERE status = 'active' ORDER BY display_order ASC, created_at DESC")->fetchAll();
?>
<?php
$page_title = 'Our Team | Sayog';
$active_page = 'team';
require_once '../header.php';
?>
    <style>
      @keyframes teamFadeUp { 0%{opacity:0;transform:translateY(30px)} 100%{opacity:1;transform:translateY(0)} }
      @keyframes teamBgDrift { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
      @keyframes teamFloat { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-10px)} }
      .team-section { padding:80px 24px 100px; position:relative; overflow:hidden; background:linear-gradient(135deg,#f0fdf4 0%,#ecfdf5 30%,#f4fbf7 70%,#f0fdf4 100%); background-size:300% 300%; animation:teamBgDrift 14s ease-in-out infinite; isolation:isolate; min-height:60vh; }
      [data-theme="dark"] .team-section { background:linear-gradient(135deg,#0a1f1a 0%,#0d2b22 30%,#0f1f1a 70%,#0a1f1a 100%); }
      .team-blob { position:absolute; border-radius:50%; filter:blur(80px); z-index:0; pointer-events:none; opacity:0.35; }
      .team-blob--1 { width:300px;height:300px;background:rgba(5,150,105,0.07);top:-80px;right:-50px;animation:teamFloat 9s ease-in-out infinite; }
      .team-blob--2 { width:250px;height:250px;background:rgba(16,185,129,0.05);bottom:-60px;left:-40px;animation:teamFloat 11s ease-in-out infinite reverse; }
      .team-inner { max-width:1100px; margin:0 auto; position:relative; z-index:1; }
      .team-header { text-align:center; margin-bottom:56px; }
      .team-header h2 { font-size:2.2rem; font-weight:800; color:#0f172a; margin:0 0 12px; letter-spacing:-0.02em; }
      .team-header p { font-size:1.05rem; color:#4b5563; margin:0; line-height:1.6; max-width:600px; margin-left:auto; margin-right:auto; }
      .team-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:28px; }
      .team-card { background:rgba(255,255,255,0.75); backdrop-filter:blur(12px); border:1px solid rgba(5,150,105,0.08); border-radius:20px; padding:32px 20px 24px; text-align:center; transition:all 0.4s cubic-bezier(0.34,1.56,0.64,1); animation:teamFadeUp 0.8s forwards; opacity:0; }
      .team-card:nth-child(1) { animation-delay:0.05s; } .team-card:nth-child(2) { animation-delay:0.1s; } .team-card:nth-child(3) { animation-delay:0.15s; }
      .team-card:hover { transform:translateY(-8px) scale(1.02); border-color:rgba(5,150,105,0.2); }
      .team-avatar { width:96px; height:96px; border-radius:50%; overflow:hidden; margin:0 auto 16px; border:3px solid rgba(5,150,105,0.15); display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,rgba(5,150,105,0.08),rgba(16,185,129,0.08)); }
      .team-card:hover .team-avatar { border-color:#059669; transform:scale(1.05); }
      .team-avatar img { width:100%;height:100%;object-fit:cover; }
      .team-avatar i { font-size:36px; color:#9ca3af; }
      .team-card h3 { font-size:1.15rem; font-weight:700; color:#0f172a; margin:0 0 4px; }
      .team-role { display:inline-block; padding:4px 14px; border-radius:999px; font-size:12px; font-weight:600; background:rgba(5,150,105,0.1); color:#059669; margin-bottom:12px; }
      .team-bio { font-size:13px; color:#4b5563; line-height:1.6; margin:0 0 16px; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
      .team-social { display:flex; justify-content:center; gap:10px; padding-top:14px; border-top:1px solid #e5e7eb; }
      .team-social a { width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:16px; color:#6b7280; background:#f3f4f6; transition:all 0.3s; text-decoration:none; }
      .team-social a:hover { transform:translateY(-3px) scale(1.1); }
      .team-social a.team-linkedin:hover { background:#0a66c2; }
      .team-social a.team-github:hover { background:#24292e; }
      .team-detail-btn { display:inline-block; margin-top:12px; padding:8px 20px; font-size:13px; font-weight:600; border:1.5px solid rgba(5,150,105,0.25); border-radius:8px; background:transparent; color:#059669; cursor:pointer; }
      .team-card:hover .team-detail-btn { background:#059669; color:#fff; }
      .team-modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px); z-index:2000; align-items:center; justify-content:center; padding:24px; transition:opacity 0.25s ease; }
      .team-modal-overlay.open { display:flex; }
      .team-modal { background:#fff; border-radius:24px; max-width:600px; width:100%; max-height:85vh; overflow-y:auto; box-shadow:0 30px 80px rgba(0,0,0,0.18), 0 10px 24px rgba(0,0,0,0.06); position:relative; transform:translateY(30px) scale(0.96); transition:all 0.4s cubic-bezier(0.34,1.56,0.64,1); }
      .team-modal-overlay.open .team-modal { transform:translateY(0) scale(1); }
      .team-modal-close { position:absolute; top:16px; right:16px; width:40px; height:40px; border-radius:50%; border:none; background:rgba(0,0,0,0.06); color:#6b7280; font-size:20px; cursor:pointer; z-index:10; display:flex; align-items:center; justify-content:center; transition:all 0.3s cubic-bezier(0.34,1.56,0.64,1); box-shadow:0 2px 8px rgba(0,0,0,0.04); }
      .team-modal-close:hover { background:#ef4444; color:#fff; transform:rotate(90deg) scale(1.1); box-shadow:0 4px 12px rgba(239,68,68,0.3); }
      .team-modal-header { display:flex; align-items:center; gap:20px; padding:32px 32px 0; background:linear-gradient(to right,rgba(5,150,105,0.04),transparent); border-radius:24px 24px 0 0; }
      .team-modal-avatar { width:80px; height:80px; border-radius:50%; overflow:hidden; flex-shrink:0; border:3px solid rgba(5,150,105,0.15); display:flex; align-items:center; justify-content:center; }
      .team-modal-title h3 { font-size:1.3rem; font-weight:700; color:#0f172a; margin:0 0 4px; }
      .team-modal-body { padding:28px 32px 32px; }
      .team-modal-body::-webkit-scrollbar { width:5px; }
      .team-modal-body::-webkit-scrollbar-track { background:transparent; }
      .team-modal-body::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:10px; }
      [data-theme="dark"] .team-modal-body::-webkit-scrollbar-thumb { background:#475569; }
      .team-modal-bio { font-size:14.5px; line-height:1.8; color:#374151; margin:0 0 24px; }
      .team-modal-social { display:flex; flex-wrap:wrap; gap:12px; padding-top:20px; border-top:1px solid #e5e7eb; }
      .team-modal-social a { display:inline-flex; align-items:center; gap:8px; padding:10px 18px; border-radius:10px; font-size:13px; font-weight:600; color:#374151; background:#f3f4f6; text-decoration:none; }
      .team-modal-social a.modal-linkedin:hover { background:#0a66c2; color:#fff; transform:translateY(-2px); }
      .team-modal-social a.modal-email:hover { background:#059669; color:#fff; transform:translateY(-2px); }
      .team-modal-social a.modal-github:hover { background:#24292e; color:#fff; transform:translateY(-2px); }
      .team-modal-social a.modal-globe:hover { background:#3b82f6; color:#fff; transform:translateY(-2px); }
      @media (max-width:600px) { .team-section { padding:48px 16px 60px; } .team-grid { gap:16px; grid-template-columns:1fr; } }
    </style>


    <main class="site-main">
        <section class="team-section">
            <div class="team-blob team-blob--1"></div>
            <div class="team-blob team-blob--2"></div>
            <div class="team-inner">
                <div class="team-header">
                    <h2><i class="fa-solid fa-people-group" style="font-size:1.6rem;background:linear-gradient(135deg,#059669,#10b981);-webkit-background-clip:text;-webkit-text-fill-color:transparent;"></i> Our Team</h2>
                    <p>Meet the talented developers and designers who built and maintain the Sayog platform.</p>
                </div>

                <?php if (empty($team_members)): ?>
                    <div class="empty-state" style="text-align:center;padding:60px 24px;">
                        <i class="fa-solid fa-people-group" style="font-size:48px;color:#9ca3af;margin-bottom:16px;"></i>
                        <h3 style="color:#0f172a;margin:0 0 8px;">Team information coming soon</h3>
                        <p style="color:#6b7280;margin:0;">Our amazing team members will be listed here shortly.</p>
                    </div>
                <?php else: ?>
                    <div class="team-grid">
                        <?php foreach ($team_members as $m): ?>
                            <div class="team-card">
                                <div class="team-avatar">
                                    <?php if (!empty($m['photo'])): ?>
                                        <img src="<?php echo htmlspecialchars(asset_url($m['photo'])); ?>" alt="<?php echo htmlspecialchars($m['name']); ?>">
                                    <?php else: ?>
                                        <i class="fa-solid fa-user"></i>
                                    <?php endif; ?>
                                </div>
                                <h3><?php echo htmlspecialchars($m['name']); ?></h3>
                                <div class="team-role"><?php echo htmlspecialchars($m['role']); ?></div>
                                <?php if (!empty($m['bio'])): ?>
                                    <p class="team-bio"><?php echo htmlspecialchars($m['bio']); ?></p>
                                <?php endif; ?>
                                <div class="team-social">
                                    <?php if (!empty($m['email'])): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($m['email']); ?>" class="team-email" title="Email"><i class="fa-solid fa-envelope"></i></a>
                                    <?php endif; ?>
                                    <?php if (!empty($m['github'])): ?>
                                        <a href="<?php echo htmlspecialchars($m['github']); ?>" target="_blank" class="team-github" title="GitHub"><i class="fa-brands fa-github"></i></a>
                                    <?php endif; ?>
                                    <?php if (!empty($m['linkedin'])): ?>
                                        <a href="<?php echo htmlspecialchars($m['linkedin']); ?>" target="_blank" class="team-linkedin" title="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                                    <?php endif; ?>
                                    <?php if (!empty($m['website'])): ?>
                                        <a href="<?php echo htmlspecialchars($m['website']); ?>" target="_blank" class="team-globe" title="Website"><i class="fa-solid fa-globe"></i></a>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($m['bio'])): ?>
                                    <button class="team-detail-btn" onclick="openTeamModal(<?php echo (int)$m['id']; ?>)">
                                        <i class="fa-solid fa-expand"></i> View Full Bio
                                    </button>
                                <?php endif; ?>
                            </div>

                            <!-- ── Team Member Detail Modal ── -->
                            <div class="team-modal-overlay" id="teamModalOverlay_<?php echo (int)$m['id']; ?>">
                                <div class="team-modal" onclick="event.stopPropagation()">
                                    <button class="team-modal-close" onclick="closeTeamModal(<?php echo (int)$m['id']; ?>)" title="Close">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                    <div class="team-modal-header">
                                        <div class="team-modal-avatar">
                                            <?php if (!empty($m['photo'])): ?>
                                                <img src="<?php echo htmlspecialchars(asset_url($m['photo'])); ?>" alt="<?php echo htmlspecialchars($m['name']); ?>">
                                            <?php else: ?>
                                                <i class="fa-solid fa-user"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="team-modal-title">
                                            <h3><?php echo htmlspecialchars($m['name']); ?></h3>
                                            <span class="role-badge"><?php echo htmlspecialchars($m['role']); ?></span>
                                        </div>
                                    </div>
                                    <div class="team-modal-body">
                                        <?php if (!empty($m['bio'])): ?>
                                            <div class="team-modal-bio"><?php echo nl2br(htmlspecialchars($m['bio'])); ?></div>
                                        <?php endif; ?>
                                        <div class="team-modal-social">
                                            <?php if (!empty($m['email'])): ?>
                                                <a href="mailto:<?php echo htmlspecialchars($m['email']); ?>" class="modal-email" title="Email"><i class="fa-solid fa-envelope"></i> Email</a>
                                            <?php endif; ?>
                                            <?php if (!empty($m['github'])): ?>
                                                <a href="<?php echo htmlspecialchars($m['github']); ?>" target="_blank" class="modal-github" title="GitHub"><i class="fa-brands fa-github"></i> GitHub</a>
                                            <?php endif; ?>
                                            <?php if (!empty($m['linkedin'])): ?>
                                                <a href="<?php echo htmlspecialchars($m['linkedin']); ?>" target="_blank" class="modal-linkedin" title="LinkedIn"><i class="fa-brands fa-linkedin-in"></i> LinkedIn</a>
                                            <?php endif; ?>
                                            <?php if (!empty($m['website'])): ?>
                                                <a href="<?php echo htmlspecialchars($m['website']); ?>" target="_blank" class="modal-globe" title="Website"><i class="fa-solid fa-globe"></i> Website</a>
                                            <?php endif; ?>
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
    /* ── Team Member Detail Modal ── */
    function openTeamModal(id) {
      var overlay = document.getElementById('teamModalOverlay_' + id);
      if (overlay) {
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
      }
    }

    function closeTeamModal(id) {
      var overlay = document.getElementById('teamModalOverlay_' + id);
      if (overlay) {
        overlay.classList.remove('open');
        document.body.style.overflow = '';
      }
    }

    /* Close any open modal on Escape key */
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        var openOverlays = document.querySelectorAll('.team-modal-overlay.open');
        openOverlays.forEach(function(ov) {
          ov.classList.remove('open');
        });
        document.body.style.overflow = '';
      }
    });

    /* Close modal when clicking the overlay background */
    document.addEventListener('click', function(e) {
      if (e.target.classList.contains('team-modal-overlay')) {
        e.target.classList.remove('open');
        document.body.style.overflow = '';
      }
    });
  </script>
    <?php require_once '../footer.php'; ?>
