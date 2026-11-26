<?php

error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once __DIR__ . '/phpmailer/class.phpmailer.php';
require_once __DIR__ . '/phpmailer/class.smtp.php';

$firstName = isset($_POST['firstName']) ? $_POST['firstName'] : '';
$lastName  = isset($_POST['lastName']) ? $_POST['lastName'] : '';
$email     = isset($_POST['email']) ? $_POST['email'] : '';
$phone     = isset($_POST['phone']) ? $_POST['phone'] : '';
$message   = isset($_POST['message']) ? $_POST['message'] : '';

if (!$firstName || !$lastName || !$email || !$phone || !$message) {
    http_response_code(400);
    echo json_encode(array(
        'success' => false,
        'message' => 'All fields are required.'
    ));
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(array(
        'success' => false,
        'message' => 'Invalid email address.'
    ));
    exit;
}

$mail = new PHPMailer(true);

try {
    $mail->IsSMTP();

    $mail->SMTPDebug = 3;
    $mail->Debugoutput = 'error_log';

    $mail->Host = 'mail.exacoreitsolutions.com';
    $mail->SMTPAuth = true;

    $mail->Username = 'admin@exacoreitsolutions.com';
    $mail->Password = 'ikKexV);wc)D$FIa';

    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->Timeout = 30;


    
    $mail->From = 'admin@exacoreitsolutions.com';
    $mail->FromName = 'Exacore Website';

    $mail->AddAddress('sandeep.ps@exacoreitsolutions.com');

    $mail->AddReplyTo($email, $firstName . ' ' . $lastName);

    $mail->IsHTML(true);
    $mail->Subject = 'New Contact Form Submission';

    $mail->Body = '
        <h3>New Contact Form Submission</h3>
        <p><b>Name:</b> ' . htmlspecialchars($firstName . ' ' . $lastName, ENT_QUOTES, 'UTF-8') . '</p>
        <p><b>Email:</b> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</p>
        <p><b>Phone:</b> ' . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . '</p>
        <p><b>Message:</b><br>' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</p>
    ';

    $mail->Send();

    echo json_encode(array(
        'success' => true,
        'message' => 'Email sent successfully.'
    ));

} catch (Exception $e) {

    error_log('PHPMailer Error: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode(array(
        'success' => false,
        'message' => 'Unable to send email.'
    ));
}