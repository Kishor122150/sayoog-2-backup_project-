<?php
require_once '../config.php';

// Auto-expire past donations
$pdo->exec("UPDATE donations SET status = 'cancelled' WHERE status IN ('available', 'requested', 'accepted') AND expiry_time < NOW()");

// Fetch all approved volunteers
$approved_volunteers = get_volunteers_by_status($pdo, 'approved');
$volunteer_counts = get_volunteer_counts($pdo);
?>
<?php
$page_title = 'Our Volunteers | Sayog';
$active_page = 'volunteers';
require_once '../header.php';
?>
    <style>
      /* ────────────────────────────────────────────────────
         PREMIUM VOLUNTEER CARDS — Redesigned UI/UX
         Inspired by Airbnb, Stripe, Vercel, Linear
         ──────────────────────────────────────────────────── */
      @keyframes volFadeUp { 0%{opacity:0;transform:translateY(24px)} 100%{opacity:1;transform:translateY(0)} }
      @keyframes volBgDrift { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
      @keyframes volFloat { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-10px)} }
      @keyframes volShimmer { 0%,100%{background-position:0% 50%} 50%{background-position:100% 50%} }
      @keyframes volPulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.7;transform:scale(1.15)} }
      @keyframes volBadgePop { 0%{transform:scale(0)} 60%{transform:scale(1.2)} 100%{transform:scale(1)} }
      @keyframes volRipple { to{transform:scale(4);opacity:0} }
      @keyframes volGradientShift { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
      @keyframes volSkeletonShimmer { 0%{background-position:-200% 0} 100%{background-position:200% 0} }
      @keyframes volAvatarFloat { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-5px)} }

      .volunteers-section { padding:80px 24px; position:relative; overflow:hidden; background:linear-gradient(135deg,#f0fdf4 0%,#ecfdf5 30%,#f4fbf7 70%,#f0fdf4 100%); background-size:300% 300%; animation:volBgDrift 14s ease-in-out infinite; isolation:isolate; min-height:60vh; }
      [data-theme="dark"] .volunteers-section { background:linear-gradient(135deg,#0a1f1a 0%,#0d2b22 30%,#0f1f1a 70%,#0a1f1a 100%); }
      .vol-blob { position:absolute; border-radius:50%; filter:blur(80px); z-index:0; pointer-events:none; opacity:0.35; }
      .vol-blob--1 { width:280px;height:280px;background:rgba(5,150,105,0.07);top:-60px;left:-40px;animation:volFloat 9s ease-in-out infinite; }
      .vol-blob--2 { width:220px;height:220px;background:rgba(16,185,129,0.05);bottom:-50px;right:-30px;animation:volFloat 11s ease-in-out infinite reverse; }
      .vol-inner { max-width:1100px; margin:0 auto; position:relative; z-index:1; }
      .vol-header { text-align:center; margin-bottom:48px; }
      .vol-header h2 { font-size:2rem; font-weight:800; color:#0f172a; margin:0 0 12px; letter-spacing:-0.02em; }
      .vol-header p { font-size:1.05rem; color:#4b5563; margin:0; line-height:1.6; max-width:580px; margin-left:auto; margin-right:auto; }
      [data-theme="dark"] .vol-header h2 { color:#f1f5f9; }
      [data-theme="dark"] .vol-header p { color:#94a3b8; }
      .vol-stats { display:flex; justify-content:center; gap:24px; flex-wrap:wrap; margin-bottom:36px; }
      .vol-stat { text-align:center; padding:12px 24px; background:rgba(255,255,255,0.6); backdrop-filter:blur(8px); border-radius:16px; border:1px solid rgba(5,150,105,0.08); min-width:120px; }
      .vol-stat .num { font-size:28px; font-weight:800; color:#059669; display:block; }
      .vol-stat .lbl { font-size:11px; color:#6b7280; text-transform:uppercase; letter-spacing:0.04em; font-weight:600; }
      [data-theme="dark"] .vol-stat { background:rgba(30,41,59,0.6); border-color:rgba(52,211,153,0.08); }
      [data-theme="dark"] .vol-stat .num { color:#34d399; }
      [data-theme="dark"] .vol-stat .lbl { color:#94a3b8; }

      /* ── Premium Card Grid ── */
      .vol-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:28px; }

      /* ── Premium Card Container ── */
      .vol-card {
        background:#ffffff;
        border-radius:22px;
        overflow:hidden;
        box-shadow:0 2px 12px rgba(0,0,0,0.04), 0 1px 3px rgba(0,0,0,0.03);
        transition:all 0.45s cubic-bezier(0.34,1.56,0.64,1);
        position:relative;
        display:flex;
        flex-direction:column;
        animation:volFadeUp 0.6s ease forwards;
        opacity:0;
        border:1px solid rgba(5,150,105,0.06);
      }
      .vol-card:nth-child(1) { animation-delay:0.05s; }
      .vol-card:nth-child(2) { animation-delay:0.10s; }
      .vol-card:nth-child(3) { animation-delay:0.15s; }
      .vol-card:nth-child(4) { animation-delay:0.20s; }
      .vol-card:nth-child(5) { animation-delay:0.25s; }
      .vol-card:nth-child(6) { animation-delay:0.30s; }
      .vol-card:nth-child(7) { animation-delay:0.35s; }
      .vol-card:nth-child(8) { animation-delay:0.40s; }
      .vol-card:nth-child(9) { animation-delay:0.45s; }
      .vol-card:nth-child(10) { animation-delay:0.50s; }
      .vol-card:nth-child(11) { animation-delay:0.55s; }
      .vol-card:nth-child(12) { animation-delay:0.60s; }

      .vol-card:hover {
        transform:translateY(-8px);
        box-shadow:0 20px 60px rgba(5,150,105,0.12), 0 8px 24px rgba(0,0,0,0.06);
        border-color:rgba(5,150,105,0.15);
      }

      [data-theme="dark"] .vol-card {
        background:#1e293b;
        border-color:rgba(52,211,153,0.06);
        box-shadow:0 2px 12px rgba(0,0,0,0.2);
      }
      [data-theme="dark"] .vol-card:hover {
        box-shadow:0 20px 60px rgba(52,211,153,0.08), 0 8px 24px rgba(0,0,0,0.3);
        border-color:rgba(52,211,153,0.15);
      }

      /* ── Card Gradient Header ── */
      .vol-card-header {
        position:relative;
        height:130px;
        background:linear-gradient(135deg,#059669 0%,#047857 35%,#065f46 70%,#064e3b 100%);
        background-size:200% 200%;
        animation:volGradientShift 8s ease-in-out infinite;
        overflow:hidden;
        flex-shrink:0;
      }

      /* Decorative background pattern */
      .vol-card-header::before {
        content:'';
        position:absolute;
        inset:0;
        background-image:radial-gradient(circle at 25% 45%, rgba(255,255,255,0.06) 0%, transparent 50%),
                        radial-gradient(circle at 75% 30%, rgba(255,255,255,0.04) 0%, transparent 40%),
                        radial-gradient(circle at 50% 80%, rgba(255,255,255,0.05) 0%, transparent 45%);
        pointer-events:none;
      }

      /* Dot pattern overlay */
      .vol-card-header::after {
        content:'';
        position:absolute;
        inset:0;
        background-image:radial-gradient(circle, rgba(255,255,255,0.07) 1px, transparent 1px);
        background-size:16px 16px;
        pointer-events:none;
        opacity:0.5;
      }

      /* Decorative circle blobs */
      .vol-header-blob {
        position:absolute;
        border-radius:50%;
        pointer-events:none;
      }
      .vol-header-blob--1 {
        width:160px;height:160px;
        background:rgba(255,255,255,0.04);
        top:-50px;right:-40px;
      }
      .vol-header-blob--2 {
        width:100px;height:100px;
        background:rgba(255,255,255,0.03);
        bottom:-30px;left:-20px;
      }
      .vol-header-blob--3 {
        width:60px;height:60px;
        background:rgba(255,255,255,0.05);
        top:20px;right:30px;
        animation:volAvatarFloat 5s ease-in-out infinite;
      }

      /* Online/offline indicator */
      .vol-online-dot {
        position:absolute;
        top:14px;
        right:16px;
        display:flex;
        align-items:center;
        gap:5px;
        padding:4px 12px 4px 8px;
        border-radius:999px;
        background:rgba(0,0,0,0.25);
        backdrop-filter:blur(8px);
        font-size:10px;
        font-weight:700;
        color:#fff;
        letter-spacing:0.3px;
        text-transform:uppercase;
        z-index:2;
        border:1px solid rgba(255,255,255,0.08);
      }
      .vol-online-dot .dot {
        width:7px;
        height:7px;
        border-radius:50%;
        background:#10b981;
        box-shadow:0 0 0 2px rgba(16,185,129,0.3);
        animation:volPulse 2s ease-in-out infinite;
      }
      .vol-online-dot.offline .dot {
        background:#94a3b8;
        box-shadow:none;
        animation:none;
      }

      /* ── Avatar Section (floating over header) ── */
      .vol-avatar-section {
        position:relative;
        display:flex;
        justify-content:center;
        margin-top:-48px;
        z-index:3;
        flex-shrink:0;
      }
      .vol-avatar-wrapper {
        position:relative;
        display:inline-flex;
      }
      .vol-avatar {
        width:92px;
        height:92px;
        border-radius:50%;
        overflow:hidden;
        border:4px solid #ffffff;
        box-shadow:0 4px 16px rgba(0,0,0,0.08), 0 2px 8px rgba(5,150,105,0.1);
        display:flex;
        align-items:center;
        justify-content:center;
        background:linear-gradient(135deg,#e0e7ff,#c7d2fe);
        transition:all 0.45s cubic-bezier(0.34,1.56,0.64,1);
      }
      .vol-card:hover .vol-avatar {
        transform:scale(1.05);
        box-shadow:0 6px 24px rgba(0,0,0,0.12), 0 4px 16px rgba(5,150,105,0.15);
      }
      [data-theme="dark"] .vol-avatar {
        border-color:#1e293b;
      }
      .vol-avatar img {
        width:100%;height:100%;object-fit:cover;
      }
      .vol-avatar-placeholder {
        width:100%;height:100%;
        display:flex;
        align-items:center;
        justify-content:center;
        background:linear-gradient(135deg,#6366f1,#8b5cf6);
        font-size:28px;
        font-weight:700;
        color:#fff;
        text-shadow:0 2px 4px rgba(0,0,0,0.1);
      }

      /* Verified badge on avatar */
      .vol-verify-badge {
        position:absolute;
        bottom:-2px;
        right:-4px;
        width:26px;
        height:26px;
        background:linear-gradient(135deg,#059669,#34d399);
        border-radius:50%;
        display:flex;
        align-items:center;
        justify-content:center;
        color:#fff;
        font-size:12px;
        border:3px solid #ffffff;
        box-shadow:0 2px 8px rgba(5,150,105,0.25);
        animation:volBadgePop 0.6s cubic-bezier(0.34,1.56,0.64,1);
        transition:all 0.3s ease;
      }
      .vol-card:hover .vol-verify-badge {
        transform:scale(1.1) rotate(-8deg);
        box-shadow:0 4px 14px rgba(5,150,105,0.35);
      }
      [data-theme="dark"] .vol-verify-badge {
        border-color:#1e293b;
      }

      /* ── Card Body ── */
      .vol-card-body {
        padding:14px 22px 20px;
        display:flex;
        flex-direction:column;
        flex:1;
      }

      .vol-card-body h3 {
        font-size:1.15rem;
        font-weight:700;
        color:#0f172a;
        margin:0 0 2px;
        text-align:center;
        letter-spacing:-0.02em;
      }
      [data-theme="dark"] .vol-card-body h3 { color:#f1f5f9; }

      .vol-tagline {
        font-size:12px;
        color:#6b7280;
        text-align:center;
        margin-bottom:14px;
        font-weight:500;
      }
      [data-theme="dark"] .vol-tagline { color:#94a3b8; }

      /* ── Info Rows ── */
      .vol-info-rows {
        display:flex;
        flex-direction:column;
        gap:7px;
        padding:14px 0;
        border-top:1px solid #f1f5f9;
        border-bottom:1px solid #f1f5f9;
        margin-bottom:14px;
      }
      [data-theme="dark"] .vol-info-rows {
        border-color:#334155;
        border-top-color:#1e293b;
      }

      .vol-info-row {
        display:flex;
        align-items:center;
        gap:10px;
        font-size:13px;
        color:#475569;
        line-height:1.5;
      }
      [data-theme="dark"] .vol-info-row { color:#94a3b8; }

      .vol-info-row .vol-info-icon {
        width:20px;
        height:20px;
        display:flex;
        align-items:center;
        justify-content:center;
        flex-shrink:0;
        font-size:12px;
        color:#059669;
        background:rgba(5,150,105,0.08);
        border-radius:6px;
      }
      [data-theme="dark"] .vol-info-row .vol-info-icon {
        color:#34d399;
        background:rgba(52,211,153,0.08);
      }

      .vol-info-row .vol-info-label {
        color:#9ca3af;
        font-size:11px;
        font-weight:600;
        text-transform:uppercase;
        letter-spacing:0.04em;
        min-width:60px;
      }

      .vol-info-row .vol-info-value {
        font-weight:600;
        color:#1e293b;
        flex:1;
      }
      [data-theme="dark"] .vol-info-row .vol-info-value { color:#e2e8f0; }

      /* ── Stats Boxes ── */
      .vol-stats-boxes {
        display:grid;
        grid-template-columns:1fr 1fr 1fr;
        gap:8px;
        margin-bottom:16px;
      }

      .vol-stat-box {
        text-align:center;
        padding:10px 6px;
        border-radius:12px;
        background:rgba(5,150,105,0.04);
        border:1px solid rgba(5,150,105,0.06);
        transition:all 0.3s ease;
      }
      .vol-card:hover .vol-stat-box {
        background:rgba(5,150,105,0.06);
        border-color:rgba(5,150,105,0.1);
      }
      [data-theme="dark"] .vol-stat-box {
        background:rgba(52,211,153,0.04);
        border-color:rgba(52,211,153,0.06);
      }
      [data-theme="dark"] .vol-card:hover .vol-stat-box {
        background:rgba(52,211,153,0.06);
        border-color:rgba(52,211,153,0.1);
      }

      .vol-stat-box .num {
        font-size:16px;
        font-weight:800;
        color:#059669;
        display:block;
        letter-spacing:-0.02em;
      }
      [data-theme="dark"] .vol-stat-box .num { color:#34d399; }

      .vol-stat-box .icon {
        font-size:14px;
        display:block;
        margin-bottom:2px;
      }

      .vol-stat-box .lbl {
        font-size:9px;
        color:#6b7280;
        text-transform:uppercase;
        letter-spacing:0.04em;
        font-weight:600;
        display:block;
        margin-top:2px;
      }
      [data-theme="dark"] .vol-stat-box .lbl { color:#94a3b8; }

      /* ── Action Buttons ── */
      .vol-actions {
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:8px;
        margin-top:auto;
      }

      .vol-btn {
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:6px;
        padding:10px 14px;
        font-size:12.5px;
        font-weight:600;
        font-family:inherit;
        border-radius:10px;
        cursor:pointer;
        transition:all 0.3s cubic-bezier(0.34,1.56,0.64,1);
        text-decoration:none;
        border:none;
        position:relative;
        overflow:hidden;
      }

      .vol-btn-primary {
        background:linear-gradient(135deg,#059669,#10b981);
        color:#fff;
        box-shadow:0 3px 10px rgba(5,150,105,0.2);
      }
      .vol-btn-primary:hover {
        transform:translateY(-2px);
        box-shadow:0 6px 20px rgba(5,150,105,0.3);
      }
      .vol-btn-primary:active {
        transform:translateY(0);
      }

      .vol-btn-secondary {
        background:rgba(5,150,105,0.06);
        color:#059669;
        border:1px solid rgba(5,150,105,0.12);
      }
      .vol-btn-secondary:hover {
        background:rgba(5,150,105,0.1);
        border-color:rgba(5,150,105,0.2);
        transform:translateY(-2px);
      }
      .vol-btn-secondary:active {
        transform:translateY(0);
      }

      [data-theme="dark"] .vol-btn-secondary {
        background:rgba(52,211,153,0.06);
        color:#34d399;
        border-color:rgba(52,211,153,0.1);
      }
      [data-theme="dark"] .vol-btn-secondary:hover {
        background:rgba(52,211,153,0.1);
        border-color:rgba(52,211,153,0.2);
      }

      /* Button Ripple Effect */
      .vol-btn .ripple {
        position:absolute;
        border-radius:50%;
        background:rgba(255,255,255,0.3);
        transform:scale(0);
        animation:volRipple 0.6s linear;
        pointer-events:none;
      }

      /* ── Joined date chip ── */
      .vol-jdate-chip {
        display:inline-flex;
        align-items:center;
        gap:4px;
        font-size:10px;
        color:#9ca3af;
        font-weight:500;
        padding:2px 10px;
        background:rgba(0,0,0,0.02);
        border-radius:999px;
        margin:0 auto 10px;
      }
      [data-theme="dark"] .vol-jdate-chip {
        background:rgba(255,255,255,0.03);
        color:#64748b;
      }

      /* ── Premium Skeleton Loading ── */
      .vol-skeleton {
        background:#ffffff;
        border-radius:22px;
        overflow:hidden;
        border:1px solid rgba(5,150,105,0.06);
      }
      [data-theme="dark"] .vol-skeleton {
        background:#1e293b;
        border-color:rgba(52,211,153,0.06);
      }
      .vol-skeleton-header {
        height:130px;
        background:linear-gradient(135deg,#e2e8f0,#f1f5f9);
        background-size:200% 200%;
        animation:volSkeletonShimmer 1.5s ease-in-out infinite;
      }
      [data-theme="dark"] .vol-skeleton-header {
        background:linear-gradient(135deg,#334155,#475569);
      }
      .vol-skeleton-body {
        padding:50px 22px 20px;
        display:flex;
        flex-direction:column;
        gap:10px;
        align-items:center;
      }
      .vol-skeleton-line {
        height:12px;
        background:#f1f5f9;
        border-radius:6px;
        width:60%;
      }
      [data-theme="dark"] .vol-skeleton-line { background:#334155; }
      .vol-skeleton-line.short { width:40%; }
      .vol-skeleton-line.long { width:80%; }
      .vol-skeleton-rows {
        width:100%;
        display:flex;
        flex-direction:column;
        gap:8px;
        padding:14px 0;
      }
      .vol-skeleton-rows .vol-skeleton-line { width:100%; height:10px; }
      .vol-skeleton-stats {
        display:grid;
        grid-template-columns:1fr 1fr 1fr;
        gap:8px;
        width:100%;
      }
      .vol-skeleton-stat {
        height:50px;
        background:#f1f5f9;
        border-radius:12px;
      }
      [data-theme="dark"] .vol-skeleton-stat { background:#334155; }
      .vol-skeleton-actions {
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:8px;
        width:100%;
        margin-top:6px;
      }
      .vol-skeleton-btn {
        height:38px;
        background:#f1f5f9;
        border-radius:10px;
      }
      [data-theme="dark"] .vol-skeleton-btn { background:#334155; }

      /* ── Modal Badge (restored — used in modal footer) ── */
      .vol-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 12px; border-radius:999px; font-size:11px; font-weight:600; background:rgba(5,150,105,0.1); color:#059669; margin-bottom:0; }
      [data-theme="dark"] .vol-badge { background:rgba(52,211,153,0.1); color:#34d399; }

      /* ── Modal Styles (unchanged from original) ── */
      .vol-modal-overlay { display:none; position:fixed; inset:0; z-index: 9999; background:rgba(0,0,0,0.6); backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px); align-items:center; justify-content:center; padding:24px; animation:volModalFadeIn 0.2s ease; }
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
      .vol-modal-section { margin-bottom:24px; padding-bottom:24px; border-bottom:1px solid #e5e7eb; }
      .vol-modal-section:last-child { border-bottom:none; margin-bottom:0; padding-bottom:0; }
      .vol-modal-section h3 { font-size:14px; font-weight:700; color:#0f172a; margin:0 0 14px; display:flex; align-items:center; gap:8px; }
      .vol-modal-section h3 i { color:#059669; font-size:16px; }
      .vol-detail-item { display:flex; flex-direction:column; gap:2px; padding:6px 0; }
      .vol-detail-item label { font-size:11px; font-weight:600; color:#9ca3af; text-transform:uppercase; letter-spacing:0.04em; }
      .vol-detail-item span { font-size:14px; color:#374151; line-height:1.5; }
      .vol-detail-text { font-size:13.5px; color:#4b5563; line-height:1.7; margin:6px 0 0; }
      .vol-modal-volid { display:inline-flex; align-items:center; gap:6px; padding:5px 14px; border-radius:999px; font-size:13px; font-weight:600; background:rgba(255,255,255,0.12); color:rgba(255,255,255,0.85); margin-top:4px; backdrop-filter:blur(4px); }
      .vol-modal-volid i { font-size:14px; }
      [data-theme="dark"] .vol-modal-section { border-bottom-color:#334155; }
      [data-theme="dark"] .vol-modal-section h3 { color:#f1f5f9; }
      [data-theme="dark"] .vol-detail-item span { color:#e2e8f0; }
      [data-theme="dark"] .vol-detail-text { color:#94a3b8; }
      [data-theme="dark"] .vol-modal-stat { background:rgba(74,222,128,0.05); border-color:rgba(74,222,128,0.06); }
      [data-theme="dark"] .vol-modal-stat .num { color:#4ade80; }
      [data-theme="dark"] .vol-modal-stat .lbl { color:#94a3b8; }

      /* ── Responsive ── */
      @media (max-width: 960px) {
        .vol-grid { grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:22px; }
      }
      @media (max-width: 640px) {
        .vol-grid { grid-template-columns:1fr; gap:20px; }
      }
      @media (max-width:480px) {
        .vol-detail-grid { grid-template-columns:1fr; }
        .vol-modal-body { padding:20px; }
        .vol-modal-header { padding:24px 20px 18px; }
        .vol-card-body { padding:12px 16px 16px; }
        .vol-stats-boxes { gap:6px; }
        .vol-stat-box { padding:8px 4px; }
        .vol-actions { gap:6px; }
        .vol-btn { font-size:11.5px; padding:8px 10px; }
      }
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
                                <!-- ── Gradient Header ── -->
                                <div class="vol-card-header">
                                    <div class="vol-header-blob vol-header-blob--1"></div>
                                    <div class="vol-header-blob vol-header-blob--2"></div>
                                    <div class="vol-header-blob vol-header-blob--3"></div>
                                    <div class="vol-online-dot <?php echo (!empty($v['online_status']) && $v['online_status'] === 'online') ? '' : 'offline'; ?>">
                                        <span class="dot"></span>
                                        <?php echo (!empty($v['online_status']) && $v['online_status'] === 'online') ? 'Available' : 'Offline'; ?>
                                    </div>
                                </div>

                                <!-- ── Avatar Section ── -->
                                <div class="vol-avatar-section">
                                    <div class="vol-avatar-wrapper">
                                        <div class="vol-avatar">
                                            <?php if (!empty($v['profile_photo'])): ?>
                                                <img src="<?php echo htmlspecialchars(asset_url($v['profile_photo'])); ?>" alt="<?php echo htmlspecialchars($v['full_name']); ?>">
                                            <?php else: ?>
                                                <div class="vol-avatar-placeholder">
                                                    <?php echo strtoupper(substr($v['full_name'], 0, 1)); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="vol-verify-badge" title="Verified Volunteer">
                                            <i class="fa-solid fa-check"></i>
                                        </div>
                                    </div>
                                </div>

                                <!-- ── Card Body ── -->
                                <div class="vol-card-body">
                                    <h3><?php echo htmlspecialchars($v['full_name']); ?></h3>
                                    <div class="vol-tagline">
                                        <i class="fa-solid fa-leaf" style="font-size:9px;opacity:0.5;"></i>
                                        Food Rescue Volunteer
                                        <i class="fa-solid fa-leaf" style="font-size:9px;opacity:0.5;"></i>
                                    </div>

                                    <div class="vol-jdate-chip">
                                        <i class="fa-regular fa-calendar"></i>
                                        Joined <?php echo date('M Y', strtotime($v['created_at'])); ?>
                                    </div>

                                    <!-- Info Rows -->
                                    <div class="vol-info-rows">
                                        <div class="vol-info-row">
                                            <span class="vol-info-icon"><i class="fa-solid fa-truck"></i></span>
                                            <span class="vol-info-label">Vehicle</span>
                                            <span class="vol-info-value"><?php echo ucfirst($v['vehicle_type'] ?? 'Walking'); ?></span>
                                        </div>
                                        <div class="vol-info-row">
                                            <span class="vol-info-icon"><i class="fa-solid fa-location-dot"></i></span>
                                            <span class="vol-info-label">Radius</span>
                                            <span class="vol-info-value"><?php echo (int)$v['delivery_radius']; ?> km</span>
                                        </div>
                                        <div class="vol-info-row">
                                            <span class="vol-info-icon"><i class="fa-regular fa-clock"></i></span>
                                            <span class="vol-info-label">Available</span>
                                            <span class="vol-info-value"><?php echo str_replace(',', ', ', ucfirst($v['availability'] ?? 'Always')); ?></span>
                                        </div>
                                        <?php if (!empty($v['languages'])): ?>
                                        <div class="vol-info-row">
                                            <span class="vol-info-icon"><i class="fa-solid fa-language"></i></span>
                                            <span class="vol-info-label">Languages</span>
                                            <span class="vol-info-value"><?php echo htmlspecialchars($v['languages']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Stats Boxes -->
                                    <div class="vol-stats-boxes">
                                        <div class="vol-stat-box">
                                            <span class="icon">⭐</span>
                                            <span class="num"><?php echo number_format((float)$v['rating'], 1); ?></span>
                                            <span class="lbl">Rating</span>
                                        </div>
                                        <div class="vol-stat-box">
                                            <span class="icon">🚚</span>
                                            <span class="num"><?php echo (int)$v['completed_deliveries']; ?></span>
                                            <span class="lbl">Deliveries</span>
                                        </div>
                                        <div class="vol-stat-box">
                                            <span class="icon">❤️</span>
                                            <span class="num"><?php echo (int)$v['community_points']; ?>%</span>
                                            <span class="lbl">Impact</span>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="vol-actions">
                                        <button class="vol-btn vol-btn-primary" onclick="openVolModal(<?php echo $v['id']; ?>)">
                                            <i class="fa-solid fa-user"></i> View Profile
                                        </button>
                                        <a href="contact.php?volunteer=<?php echo $v['user_id']; ?>" class="vol-btn vol-btn-secondary">
                                            <i class="fa-solid fa-message"></i> Contact
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Volunteer Details Modal -->
                            <div class="vol-modal-overlay" id="volModalOverlay_<?php echo $v['id']; ?>" onclick="closeVolModal(<?php echo $v['id']; ?>)">
                                <div class="vol-modal" onclick="event.stopPropagation()">
                                    <button class="vol-modal-close" onclick="closeVolModal(<?php echo $v['id']; ?>)"><i class="fa-solid fa-xmark"></i></button>

                                    <!-- Header -->
                                    <div class="vol-modal-header">
                                        <div class="vol-modal-avatar">
                                            <?php if (!empty($v['profile_photo'])): ?>
                                                <img src="<?php echo htmlspecialchars(asset_url($v['profile_photo'])); ?>" alt="<?php echo htmlspecialchars($v['full_name']); ?>">
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
        /* Move overlay to body to escape parent stacking contexts (isolation:isolate, overflow:hidden) */
        document.body.appendChild(overlay);
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
    <?php require_once '../footer.php'; ?>
