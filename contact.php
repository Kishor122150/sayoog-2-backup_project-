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

<?php
$page_title = 'Contact | Sayog';
$active_page = 'contact';
require_once 'header.php';
?>
    <style>
.contact-section{ max-width:1150px; margin:60px auto; display:grid; grid-template-columns:1fr 1fr; gap:35px; padding:20px; }
.contact-info{ background:green; color:white; padding:40px; border-radius:15px; }
.contact-info h2{ margin-bottom:20px; }
.contact-info p{ line-height:28px; }
.info{ margin:20px 0; font-size:16px; }
.info i{ margin-right:12px; width:25px; }
.contact-form{ background:white; padding:35px; border-radius:15px; box-shadow:0 10px 30px rgba(0,0,0,.1); }
.contact-form h2{ margin-bottom:25px; }
.contact-form input, .contact-form textarea{ width:100%; padding:14px; margin-bottom:18px; border:1px solid #ddd; border-radius:8px; font-size:15px; box-sizing:border-box; }
.contact-form textarea{ height:150px; resize:none; }
.contact-form button{ width:100%; background:#059669; color:white; padding:15px; border:none; border-radius:8px; font-size:16px; cursor:pointer; transition:.3s; }
.contact-form button:hover{ background:#1d4ed8; }
.success{ background:#dcfce7; color:#166534; padding:15px; margin-bottom:15px; border-radius:8px; }
.error{ background:#fee2e2; color:#b91c1c; padding:15px; margin-bottom:15px; border-radius:8px; }
@media(max-width:900px){ .contact-section{ grid-template-columns:1fr; } }
@media(max-width:768px){
  .contact-section{ margin:30px auto; padding:12px; gap:20px; }
  .contact-info{ padding:24px 20px; } .contact-info h2{ font-size:1.3rem; }
  .contact-form{ padding:24px 20px; } .contact-form h2{ font-size:1.2rem; }
  .contact-form input, .contact-form textarea{ padding:12px; font-size:14px; }
  .contact-form button{ padding:12px; font-size:15px; }
}
@media(max-width:480px){
  .contact-section{ margin:20px auto; padding:8px; gap:16px; }
  .contact-info{ padding:20px 16px; } .contact-info h2{ font-size:1.2rem; }
  .contact-form{ padding:20px 16px; } .contact-form h2{ font-size:1.1rem; }
  .contact-form input, .contact-form textarea{ padding:10px; font-size:14px; }
  .contact-form button{ padding:12px; font-size:14px; }
}
</style>


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

<?php require_once 'footer.php'; ?>
