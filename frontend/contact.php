<?php
require_once '../config.php';

// Auto-expire past donations
$pdo->exec("UPDATE donations SET status = 'cancelled' WHERE status IN ('available', 'requested', 'accepted') AND expiry_time < NOW()");

$page = get_cms_page_by_slug($pdo, 'contact');
if (!$page) {
    http_response_code(404);
}

$success = "";
$error = "";

if(isset($_POST['send'])){

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if($name && $email && $subject && $message){

        $stmt = $pdo->prepare("INSERT INTO contact_messages(name,email,phone,subject,message)
                               VALUES(?,?,?,?,?)");

        if($stmt->execute([$name,$email,$phone,$subject,$message])){

            $success = "Thank you! Your message has been sent successfully.";

        }else{

            $error = "Something went wrong.";

        }

    }else{

        $error = "Please fill all required fields.";

    }

}
?>

<?php
$page_title = 'Contact | Sayog';
$active_page = 'contact';
require_once '../header.php';
?>
    <style>
/* ==========================================================
   SAYOG CONTACT PAGE — Premium Animated Design
   Preserves all PHP/form functionality. CSS-only redesign.
   ========================================================== */

@keyframes contactFadeUp {
  0% { opacity: 0; transform: translateY(30px) scale(0.97); }
  100% { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes contactFadeLeft {
  0% { opacity: 0; transform: translateX(-30px); }
  100% { opacity: 1; transform: translateX(0); }
}
@keyframes contactFadeRight {
  0% { opacity: 0; transform: translateX(30px); }
  100% { opacity: 1; transform: translateX(0); }
}
@keyframes contactBgDrift {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}
@keyframes contactFloat {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-12px); }
}
@keyframes contactIconPop {
  0% { transform: scale(0); opacity: 0; }
  60% { transform: scale(1.15); }
  100% { transform: scale(1); opacity: 1; }
}
@keyframes contactShimmer {
  0% { transform: translateX(-100%) skewX(-15deg); }
  100% { transform: translateX(200%) skewX(-15deg); }
}
@keyframes contactSubmitPulse {
  0%, 100% { box-shadow: 0 4px 14px rgba(5,150,105,0.2); }
  50% { box-shadow: 0 4px 24px rgba(5,150,105,0.4); }
}

/* ── Section Wrapper ── */
.contact-section-wrapper {
  padding: 60px 24px 80px;
  position: relative;
  overflow: hidden;
  background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #f4fbf7 100%);
  background-size: 300% 300%;
  animation: contactBgDrift 14s ease-in-out infinite;
  isolation: isolate;
  min-height: 80vh;
}
[data-theme="dark"] .contact-section-wrapper {
  background: linear-gradient(135deg, #0a1f1a 0%, #0d2b22 50%, #0f1f1a 100%);
  background-size: 300% 300%;
}

/* ── Decorative Blobs ── */
.contact-blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  z-index: 0;
  pointer-events: none;
  opacity: 0.35;
}
.contact-blob--1 {
  width: 300px; height: 300px;
  background: rgba(5, 150, 105, 0.07);
  top: -80px; right: -50px;
  animation: contactFloat 9s ease-in-out infinite;
}
.contact-blob--2 {
  width: 250px; height: 250px;
  background: rgba(16, 185, 129, 0.05);
  bottom: -60px; left: -40px;
  animation: contactFloat 11s ease-in-out infinite reverse;
}
.contact-blob--3 {
  width: 180px; height: 180px;
  background: rgba(5, 150, 105, 0.04);
  top: 40%; left: 60%;
  animation: contactFloat 7s ease-in-out infinite 2s;
}

/* ── Inner Container ── */
.contact-inner {
  max-width: 1100px;
  margin: 0 auto;
  position: relative;
  z-index: 1;
}

/* ── Section Header ── */
.contact-header {
  text-align: center;
  margin-bottom: 48px;
  animation: contactFadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.contact-header h1 {
  font-size: 2.2rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 12px;
  letter-spacing: -0.02em;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
}
[data-theme="dark"] .contact-header h1 { color: #f1f5f9; }
.contact-header p {
  font-size: 1.05rem;
  color: #4b5563;
  margin: 0;
  line-height: 1.6;
  max-width: 520px;
  margin-left: auto;
  margin-right: auto;
}
[data-theme="dark"] .contact-header p { color: #94a3b8; }

/* ── Grid Layout ── */
.contact-grid {
  display: grid;
  grid-template-columns: 1fr 1.1fr;
  gap: 32px;
  align-items: start;
}
@media (max-width: 900px) {
  .contact-grid { grid-template-columns: 1fr; }
}

/* ── Contact Info Card (Glassmorphism) ── */
.contact-info {
  background: rgba(255, 255, 255, 0.72) !important;
  backdrop-filter: blur(16px) !important;
  -webkit-backdrop-filter: blur(16px) !important;
  border: 1px solid rgba(5, 150, 105, 0.08);
  border-radius: 24px;
  padding: 40px 36px;
  box-shadow:
    0 4px 12px rgba(0, 0, 0, 0.02),
    0 12px 40px rgba(5, 150, 105, 0.03);
  animation: contactFadeLeft 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  position: relative;
  overflow: hidden;
  color: #0f172a !important;
}
[data-theme="dark"] .contact-info {
  background: rgba(15, 23, 42, 0.55) !important;
  backdrop-filter: blur(16px) !important;
  border-color: rgba(5, 150, 105, 0.06);
}

/* Decorative gradient accent bar */
.contact-info::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 4px;
  background: linear-gradient(90deg, #059669, #10b981, #34d399);
}

/* Header icon in info card */
.contact-info-icon {
  width: 56px;
  height: 56px;
  border-radius: 16px;
  background: linear-gradient(135deg, #059669, #047857);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: #fff;
  margin-bottom: 20px;
  box-shadow: 0 8px 16px rgba(5, 150, 105, 0.2);
}

.contact-info h2 {
  font-size: 1.4rem !important;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 10px !important;
}
[data-theme="dark"] .contact-info h2 { color: #f1f5f9; }

.contact-info p {
  font-size: 14.5px;
  color: #4b5563;
  line-height: 1.7;
  margin: 0 0 28px !important;
}
[data-theme="dark"] .contact-info p { color: #94a3b8; }

/* ── Info Items ── */
.info {
  display: flex;
  align-items: center;
  gap: 16px;
  margin: 18px 0 !important;
  padding: 14px 18px;
  border-radius: 14px;
  background: rgba(5, 150, 105, 0.04);
  border: 1px solid rgba(5, 150, 105, 0.06);
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
  animation: contactFadeUp 0.6s forwards;
  opacity: 0;
  font-size: 14.5px !important;
  color: #374151 !important;
}
.info:nth-child(2) { animation-delay: 0.15s; }
.info:nth-child(3) { animation-delay: 0.25s; }
.info:nth-child(4) { animation-delay: 0.35s; }
.info:nth-child(5) { animation-delay: 0.45s; }

.info:hover {
  transform: translateX(6px);
  background: rgba(5, 150, 105, 0.08);
  border-color: rgba(5, 150, 105, 0.15);
}

.info i {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: linear-gradient(135deg, #059669, #10b981);
  color: #fff !important;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px !important;
  flex-shrink: 0;
  margin-right: 0 !important;
  box-shadow: 0 4px 8px rgba(5, 150, 105, 0.15);
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.info:hover i {
  transform: scale(1.08) rotate(-4deg);
  box-shadow: 0 6px 16px rgba(5, 150, 105, 0.25);
}

[data-theme="dark"] .info {
  background: rgba(74, 222, 128, 0.04);
  border-color: rgba(74, 222, 128, 0.05);
  color: #e2e8f0 !important;
}
[data-theme="dark"] .info:hover {
  background: rgba(74, 222, 128, 0.08);
}

/* ── Contact Form Card ── */
.contact-form {
  background: rgba(255, 255, 255, 0.82) !important;
  backdrop-filter: blur(16px) !important;
  -webkit-backdrop-filter: blur(16px) !important;
  border: 1px solid rgba(5, 150, 105, 0.06);
  border-radius: 24px;
  padding: 40px 36px !important;
  box-shadow:
    0 4px 16px rgba(0, 0, 0, 0.04),
    0 16px 48px rgba(5, 150, 105, 0.04);
  animation: contactFadeRight 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  position: relative;
  overflow: hidden;
}
[data-theme="dark"] .contact-form {
  background: rgba(15, 23, 42, 0.6) !important;
  border-color: rgba(74, 222, 128, 0.04);
}

.contact-form h2 {
  font-size: 1.4rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 24px !important;
  display: flex;
  align-items: center;
  gap: 10px;
}
[data-theme="dark"] .contact-form h2 { color: #f1f5f9; }
.contact-form h2 i { color: #059669; font-size: 1.3rem; }

/* ── Form Inputs ── */
.contact-form input,
.contact-form textarea {
  width: 100% !important;
  padding: 14px 18px !important;
  margin-bottom: 16px !important;
  border: 1.5px solid rgba(0, 0, 0, 0.08) !important;
  border-radius: 12px !important;
  font-size: 14.5px !important;
  font-family: inherit;
  background: rgba(255, 255, 255, 0.8) !important;
  color: #0f172a !important;
  transition: all 0.25s ease !important;
  box-sizing: border-box !important;
  outline: none !important;
}

.contact-form input:focus,
.contact-form textarea:focus {
  border-color: #059669 !important;
  box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1) !important;
  background: #fff !important;
}

.contact-form textarea {
  height: 140px !important;
  resize: vertical !important;
  min-height: 100px;
}

[data-theme="dark"] .contact-form input,
[data-theme="dark"] .contact-form textarea {
  background: rgba(30, 41, 59, 0.8) !important;
  border-color: rgba(148, 163, 184, 0.12) !important;
  color: #f1f5f9 !important;
}
[data-theme="dark"] .contact-form input:focus,
[data-theme="dark"] .contact-form textarea:focus {
  border-color: #34d399 !important;
  box-shadow: 0 0 0 4px rgba(52, 211, 153, 0.08) !important;
}

/* ── Submit Button ── */
.contact-form button[type="submit"] {
  width: 100% !important;
  padding: 16px 24px !important;
  background: linear-gradient(135deg, #059669, #047857) !important;
  color: #fff !important;
  border: none !important;
  border-radius: 12px !important;
  font-size: 15px !important;
  font-weight: 600 !important;
  cursor: pointer !important;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25) !important;
  position: relative;
  overflow: hidden;
}

.contact-form button[type="submit"]:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(5, 150, 105, 0.35) !important;
  background: linear-gradient(135deg, #047857, #065f46) !important;
}

.contact-form button[type="submit"]:active {
  transform: translateY(0);
}

.contact-form button[type="submit"] i {
  transition: transform 0.3s ease;
}
.contact-form button[type="submit"]:hover i {
  transform: translateX(4px);
}

/* Shimmer effect on button */
.contact-form button[type="submit"]::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.08), transparent);
  transform: translateX(-100%) skewX(-15deg);
  pointer-events: none;
}
.contact-form button[type="submit"]:hover::after {
  animation: contactShimmer 0.8s ease-in-out;
}

/* ── Alert Messages ── */
.success {
  background: rgba(5, 150, 105, 0.08) !important;
  color: #065f46 !important;
  padding: 16px 20px !important;
  margin-bottom: 20px !important;
  border-radius: 12px !important;
  border: 1px solid rgba(5, 150, 105, 0.15) !important;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 10px;
  animation: contactFadeUp 0.4s ease;
}
.success::before {
  content: '\f00c';
  font-family: 'Font Awesome 6 Free';
  font-weight: 900;
  color: #059669;
  font-size: 16px;
}

.error {
  background: rgba(239, 68, 68, 0.08) !important;
  color: #991b1b !important;
  padding: 16px 20px !important;
  margin-bottom: 20px !important;
  border-radius: 12px !important;
  border: 1px solid rgba(239, 68, 68, 0.15) !important;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 10px;
  animation: contactFadeUp 0.4s ease;
}
.error::before {
  content: '\f071';
  font-family: 'Font Awesome 6 Free';
  font-weight: 900;
  color: #dc2626;
  font-size: 16px;
}

[data-theme="dark"] .success {
  background: rgba(74, 222, 128, 0.08) !important;
  border-color: rgba(74, 222, 128, 0.12) !important;
  color: #4ade80 !important;
}
[data-theme="dark"] .error {
  background: rgba(248, 113, 113, 0.08) !important;
  border-color: rgba(248, 113, 113, 0.12) !important;
  color: #fca5a5 !important;
}

/* ── Responsive ── */
@media (max-width: 900px) {
  .contact-section-wrapper { padding: 40px 16px 60px; }
  .contact-info { padding: 32px 24px !important; }
  .contact-form { padding: 32px 24px !important; }
  .contact-header h1 { font-size: 1.6rem; }
}
@media (max-width: 640px) {
  .contact-section-wrapper { padding: 24px 12px 48px; }
  .contact-info { padding: 24px 18px !important; }
  .contact-form { padding: 24px 18px !important; }
  .info { padding: 12px 14px; }
  .info i { width: 36px; height: 36px; font-size: 14px !important; }
  .contact-form button[type="submit"] { padding: 14px 18px !important; font-size: 14px !important; }
}
</style>


<section class="contact-section-wrapper">
  <div class="contact-blob contact-blob--1"></div>
  <div class="contact-blob contact-blob--2"></div>
  <div class="contact-blob contact-blob--3"></div>

  <div class="contact-inner">
    <div class="contact-header">
      <h1 data-i18n="contact.page_title"><i class="fa-solid fa-paper-plane" style="font-size:1.6rem;background:linear-gradient(135deg,#059669,#10b981);-webkit-background-clip:text;-webkit-text-fill-color:transparent;"></i> Get In Touch</h1>
      <p>Have questions about food donation? Need help using the platform? We'd love to hear from you.</p>
    </div>

    <div class="contact-grid">
      <div class="contact-info">
        <div class="contact-info-icon"><i class="fa-solid fa-envelope-open-text"></i></div>
        <h2 data-i18n="contact.heading">Contact Sayoog</h2>
        <p data-i18n="contact.description">
          Have questions about food donation?
          Need help using the platform?
          Feel free to contact us anytime.
        </p>

        <div class="info">
          <i class="fa-solid fa-location-dot"></i>
          Kathmandu, Nepal
        </div>

        <div class="info">
          <i class="fa-solid fa-phone"></i>
          +977-9800000000
        </div>

        <div class="info">
          <i class="fa-solid fa-envelope"></i>
          support@sayoog.com
        </div>

        <div class="info">
          <i class="fa-brands fa-whatsapp"></i>
          WhatsApp Support Available
        </div>
      </div>

      <div class="contact-form">
        <h2><i class="fa-solid fa-comment-dots"></i> <span data-i18n="contact.send_message">Send Message</span></h2>

        <?php if($success!=""){ ?>
          <div class="success"><?php echo $success; ?></div>
        <?php } ?>

        <?php if($error!=""){ ?>
          <div class="error"><?php echo $error; ?></div>
        <?php } ?>

        <form method="POST">
          <input type="text" name="name" placeholder="Your Name" required data-i18n="form.name">
          <input type="email" name="email" placeholder="Your Email" required data-i18n="form.email">
          <input type="text" name="phone" placeholder="Phone Number" data-i18n="form.phone">
          <input type="text" name="subject" placeholder="Subject" required data-i18n="form.subject">
          <textarea name="message" placeholder="Write your message..." required data-i18n="form.message"></textarea>
          <button type="submit" name="send" data-i18n="contact.send_message">
            <i class="fa-solid fa-paper-plane"></i> Send Message
          </button>
        </form>
      </div>
    </div>
  </div>
</section>

<?php require_once '../footer.php'; ?>
