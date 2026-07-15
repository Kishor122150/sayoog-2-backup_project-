<?php
require_once 'config.php';

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

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?php echo $page ? htmlspecialchars($page['title']) : 'Contact | Sayog'; ?></title>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="js/app.js"></script>

<style>
  /* ===== MOBILE NAVIGATION — Hamburger Menu ===== */
  .mobile-nav-toggle {
    display: none;
    background: none;
    border: none;
    font-size: 24px;
    color: var(--text-primary, #0f172a);
    cursor: pointer;
    padding: 8px;
    line-height: 1;
    z-index: 1100;
    position: relative;
    transition: color 0.3s ease;
  }
  .mobile-nav-toggle:hover {
    color: #059669;
  }

  .mobile-nav-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.45);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 1050;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;
  }
  .mobile-nav-overlay.mobile-nav-open {
    opacity: 1;
    pointer-events: auto;
  }

  @media (max-width: 767px) {
    .site-nav {
      position: fixed;
      top: 0;
      left: 0;
      bottom: 0;
      width: 280px;
      max-width: 85vw;
      background: var(--surface, #ffffff);
      box-shadow: 0 0 40px rgba(0, 0, 0, 0.15);
      flex-direction: column;
      align-items: stretch;
      justify-content: flex-start;
      padding: 80px 20px 24px;
      gap: 4px;
      z-index: 1090;
      transform: translateX(-100%);
      transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
      overflow-y: auto;
      display: flex !important;
      flex-wrap: nowrap;
    }
    .site-nav.mobile-nav-open {
      transform: translateX(0);
    }
    .site-nav a {
      padding: 12px 16px;
      font-size: 15px;
      border-radius: 10px;
      width: 100%;
      justify-content: center;
    }
    .site-nav a[style*="background: #059669"] {
      padding: 12px 16px;
      font-size: 15px;
      width: 100%;
      justify-content: center;
    }
    .site-nav .theme-toggle,
    .site-nav .lang-toggle {
      width: 100%;
      justify-content: center;
      padding: 12px 16px;
      font-size: 14px;
      margin-left: 0 !important;
      margin-top: 4px;
    }
    .mobile-nav-toggle {
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .mobile-nav-overlay {
      display: block;
    }
    .mobile-nav-overlay.mobile-nav-open {
      display: block;
    }

    [data-theme="dark"] .site-nav {
      background: var(--surface, #1e293b);
      box-shadow: 0 0 40px rgba(0, 0, 0, 0.4);
    }
  }


.contact-section{

max-width:1150px;

margin:60px auto;

display:grid;

grid-template-columns:1fr 1fr;

gap:35px;

padding:20px;

}

.contact-info{

background:green;

color:white;

padding:40px;

border-radius:15px;

}

.contact-info h2{

margin-bottom:20px;

}

.contact-info p{

line-height:28px;

}

.info{

margin:20px 0;

font-size:16px;

}

.info i{

margin-right:12px;

width:25px;

}

.contact-form{

background:white;

padding:35px;

border-radius:15px;

box-shadow:0 10px 30px rgba(0,0,0,.1);

}

.contact-form h2{

margin-bottom:25px;

}

.contact-form input,

.contact-form textarea{

width:100%;

padding:14px;

margin-bottom:18px;

border:1px solid #ddd;

border-radius:8px;

font-size:15px;

}

.contact-form textarea{

height:150px;

resize:none;

}

.contact-form button{

width:100%;

background:#059669;

color:white;

padding:15px;

border:none;

border-radius:8px;

font-size:16px;

cursor:pointer;

transition:.3s;

}

.contact-form button:hover{

background:#1d4ed8;

}

.success{

background:#dcfce7;

color:#166534;

padding:15px;

margin-bottom:15px;

border-radius:8px;

}

.error{

background:#fee2e2;

color:#b91c1c;

padding:15px;

margin-bottom:15px;

border-radius:8px;

}

@media(max-width:900px){

.contact-section{

grid-template-columns:1fr;
}

}

@media(max-width:768px){
  .contact-section{
    margin:30px auto;
    padding:12px;
    gap:20px;
  }
  .contact-info{
    padding:24px 20px;
  }
  .contact-info h2{
    font-size:1.3rem;
  }
  .contact-info p{
    font-size:14px;
    line-height:24px;
  }
  .info{
    font-size:14px;
    margin:16px 0;
  }
  .contact-form{
    padding:24px 20px;
  }
  .contact-form h2{
    font-size:1.2rem;
    margin-bottom:16px;
  }
  .contact-form input,
  .contact-form textarea{
    padding:12px;
    font-size:14px;
  }
  .contact-form textarea{
    height:120px;
  }
  .contact-form button{
    padding:12px;
    font-size:15px;
  }
}

@media(max-width:480px){
  .contact-section{
    margin:20px auto;
    padding:8px;
    gap:16px;
  }
  .contact-info{
    padding:20px 16px;
    border-radius:12px;
  }
  .contact-info h2{
    font-size:1.2rem;
    margin-bottom:14px;
  }
  .contact-info p{
    font-size:13px;
  }
  .info{
    font-size:13px;
    margin:12px 0;
  }
  .contact-form{
    padding:20px 16px;
    border-radius:12px;
  }
  .contact-form h2{
    font-size:1.1rem;
  }
  .contact-form input,
  .contact-form textarea{
    padding:10px;
    font-size:14px;
    margin-bottom:14px;
  }
  .contact-form button{
    padding:12px;
    font-size:14px;
  }
  .success,
  .error{
    padding:12px;
    font-size:13px;
  }
}

@media(max-width:375px){
  .contact-section{
    padding:6px;
    gap:12px;
  }
  .contact-info{
    padding:16px 12px;
  }
  .contact-info h2{
    font-size:1.1rem;
  }
  .contact-info p{
    font-size:12px;
  }
  .info{
    font-size:12px;
  }
  .contact-form{
    padding:16px 12px;
  }
  .contact-form h2{
    font-size:1rem;
  }
  .contact-form input,
  .contact-form textarea{
    padding:8px 10px;
    font-size:13px;
  }
  .contact-form button{
    padding:10px;
    font-size:13px;
  }
}

</style>

</head>

<body>

<!-- Mobile Nav Overlay -->
<div class="mobile-nav-overlay" id="mobileNavOverlay"></div>

<header class="site-header">
<button class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Toggle navigation menu">
    <i class="fa-solid fa-bars"></i>
</button>
<div class="site-branding">
<a href="index.php" class="site-logo">
<i class="fa-solid fa-hand-holding-heart"></i> Sayog
</a>
</div>

<nav class="site-nav" id="mobileNav">
<a href="index.php" data-i18n="nav.home">Home</a>
<a href="donations.php" data-i18n="nav.food_listings">Food Listings</a>
<a href="about.php" data-i18n="nav.about">About</a>
<a href="contact.php" class="active" style="color: #059669;" data-i18n="nav.contact">Contact</a>
<a href="login.php" data-i18n="nav.login">Login</a>
<!-- <a href="register.php">Get Started</a> -->
            <a href="register.php" style="background: #059669; color:#fff" data-i18n="nav.get_started">Get Started</a>
            <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme" style="margin-left:4px;">
                <i class="fa-solid fa-moon"></i>
            </button>
            <button class="lang-toggle" onclick="toggleLanguage()" style="background:rgba(59,130,246,0.1);">
                <span>नेपाली</span>
            </button>
</nav>

</header>

<section class="contact-section">

<div class="contact-info">

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

<h2 data-i18n="contact.send_message">Send Message</h2>

<?php if($success!=""){ ?>

<div class="success"><?php echo $success; ?></div>

<?php } ?>

<?php if($error!=""){ ?>

<div class="error"><?php echo $error; ?></div>

<?php } ?>

<form method="POST">

<input
type="text"
name="name"
placeholder="Your Name"
required
data-i18n="form.name">

<input
type="email"
name="email"
placeholder="Your Email"
required
data-i18n="form.email">

<input
type="text"
name="phone"
placeholder="Phone Number"
data-i18n="form.phone">

<input
type="text"
name="subject"
placeholder="Subject"
required
data-i18n="form.subject">

<textarea
name="message"
placeholder="Write your message..."
required
data-i18n="form.message"></textarea>

<button type="submit" name="send" data-i18n="contact.send_message">

<i class="fa-solid fa-paper-plane"></i>

Send Message

</button>

</form>

</div>

</section>

<footer class="site-footer">
<p>&copy; <?php echo date('Y'); ?> Sayog. Connecting surplus food with communities.</p>
</footer>

<script>
(function() {
  var toggle = document.getElementById('mobileNavToggle');
  var nav = document.getElementById('mobileNav');
  var overlay = document.getElementById('mobileNavOverlay');
  var icon = toggle ? toggle.querySelector('i') : null;

  if (!toggle || !nav || !overlay) return;

  function openMenu() {
    nav.classList.add('mobile-nav-open');
    overlay.classList.add('mobile-nav-open');
    if (icon) {
      icon.className = 'fa-solid fa-xmark';
    }
    toggle.setAttribute('aria-label', 'Close navigation menu');
    document.body.style.overflow = 'hidden';
  }

  function closeMenu() {
    nav.classList.remove('mobile-nav-open');
    overlay.classList.remove('mobile-nav-open');
    if (icon) {
      icon.className = 'fa-solid fa-bars';
    }
    toggle.setAttribute('aria-label', 'Toggle navigation menu');
    document.body.style.overflow = '';
  }

  toggle.addEventListener('click', function(e) {
    e.stopPropagation();
    if (nav.classList.contains('mobile-nav-open')) {
      closeMenu();
    } else {
      openMenu();
    }
  });

  overlay.addEventListener('click', closeMenu);

  nav.querySelectorAll('a').forEach(function(link) {
    link.addEventListener('click', closeMenu);
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && nav.classList.contains('mobile-nav-open')) {
      closeMenu();
    }
  });
})();
</script>

</body>
</html>