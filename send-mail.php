<?php
session_start();
require_once 'vendor/autoload.php';
$cfg = require_once 'cfg.php';
$errors = [];
$post = $_POST;
if (empty($post)) {
    die('no data');
}
$captcha = false;
if (isset($post['recaptcha-response']) && !empty($post['recaptcha-response'])) {
    $captchaResponse = $post['recaptcha-response'];
    $secret = $cfg['RECAPTCHA_SERVER'];
    $response = file_get_contents(
        "https://www.google.com/recaptcha/api/siteverify?secret=" . $secret . "&response=" . $captchaResponse . "&remoteip=" . $_SERVER['REMOTE_ADDR']
    );
    $response = json_decode($response);
    if ($response->success == true && $response->score >= 0.5) {
        $captcha = true;
    }
}

if (!$captcha) {
    $errors['captcha'] = 'captcha invalid';
}

if (!isset($post['csrf']) || empty($post['csrf']) || !isset($_SESSION['CSRF']) || ($_SESSION['CSRF'] !== $post['csrf'])) {
    $errors['csrf'] = 'csrf error';
}

if (!isset($post['name']) || empty($post['name'])) {
    $errors['name'] = 'name error';
}

if (!isset($post['email']) || empty($post['email'])) {
    $errors['email'] = 'email error';
}

if (!isset($post['message']) || empty($post['message'])) {
    $errors['message'] = 'message error';
}

if (!empty($errors)) {
    header("Content-type: application/json; charset=utf-8");
    http_response_code(406);
    echo json_encode(['errors' => $errors]);
    exit(0);
}

$subject = 'Email from website contact form';
$message = 'Name: ' . $post['name'] . '<br/>';
$message .= 'Email: ' . $post['email'] . '<br/>';
$message .= 'Message: ' . $post['message'] . '<br/>';

/* Create the Transport */
if ($cfg['ENV'] == 'PROD') {
    $transport = (new Swift_SmtpTransport($cfg['MAIL_SERVER_HOST'], $cfg['MAIL_SERVER_PORT']));
} else {
    $transport = (new Swift_SpoolTransport(new Swift_FileSpool('mails/')));
}

/* Create the Mailer using your created Transport */
$mailer = new Swift_Mailer($transport);
/* Create a message */
$mail = (new Swift_Message($subject))
    ->setFrom($cfg['MAIL_NO_REPLY'])
    ->setTo(explode(',', $cfg['MAIL_RECIPIENTS']))
    ->setBody($message, 'text/html');

/* Send the message */
$result = $mailer->send($mail);
if ($result < 1) {
    http_response_code(400);
    exit(1);
}
unset($_SESSION['CSRF']);
http_response_code(202);
exit(0);