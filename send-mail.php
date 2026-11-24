<?php

use PHPMailer\PHPMailer\PHPMailer;

header('Content-Type: application/json');

try {

    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/config.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    error_log('CONTACT POST DATA: ' . print_r($_POST, true));

    $firstName = trim($_POST['firstName'] ?? '');
    $lastName  = trim($_POST['lastName'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $message   = trim($_POST['message'] ?? '');

    $missing = [];

    foreach ([
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $email,
        'message' => $message
    ] as $field => $value) {
        if ($value === '') {
            $missing[] = $field;
        }
    }

    if (!empty($missing)) {
        throw new Exception('Missing fields: ' . implode(', ', $missing));
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address.');
    }

    if (empty($_POST['terms'])) {
        throw new Exception('Please accept Terms & Conditions.');
    }

    // $pdfPath = __DIR__ . '/pdf/company-profile.pdf';

    // if (!file_exists($pdfPath)) {
    //     throw new Exception('PDF attachment not found.');
    // }

    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = SMTP_PORT;

    $mail->setFrom(FROM_EMAIL, FROM_NAME);
    $mail->addAddress($email, $firstName . ' ' . $lastName);

    $mail->isHTML(true);
    $mail->Subject = 'Thank you for contacting ' . FROM_NAME;

    $mail->Body = '
        <h3>Hello ' . htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') . ',</h3>
        <p>Thank you for contacting us.</p>
        <p>Please find our company profile attached.</p>
        <p>We appreciate your interest and will get back to you soon.</p>
        <p>Regards,<br>' . htmlspecialchars(FROM_NAME, ENT_QUOTES, 'UTF-8') . '</p>
    ';

    $mail->AltBody = "Hello $firstName,\n\nThank you for contacting us.";

    //$mail->addAttachment($pdfPath, 'Company-Profile.pdf');

    $mail->send();

    echo json_encode([
        'status' => 'success',
        'message' => 'Mail sent successfully. Please check your email for PDF.'
    ]);

} catch (\Throwable $e) {

    error_log('SMTP MAIL ERROR: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}