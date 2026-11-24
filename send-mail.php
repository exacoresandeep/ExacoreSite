
<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

require 'vendor/autoload.php';
require 'config.php';

try {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request.');
    }

    $firstName = trim($_POST['firstName'] ?? '');
    $lastName  = trim($_POST['lastName'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $message   = trim($_POST['message'] ?? '');

    if (!$firstName || !$lastName || !$email || !$message) {
        throw new Exception('Please fill all required fields.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Please enter a valid email address.');
    }

    if (empty($_POST['terms'])) {
        throw new Exception('Please accept the Terms & Conditions.');
    }

    // PDF attachment
    $pdfPath = __DIR__ . '/pdf/company-profile.pdf';

    if (!file_exists($pdfPath)) {
        throw new Exception('PDF attachment not found.');
    }

    $mail = new PHPMailer(true);

    // SMTP Configuration
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;

    // Sender and recipient
    $mail->setFrom(FROM_EMAIL, FROM_NAME);
    $mail->addAddress($email, $firstName . ' ' . $lastName);

    // Email content
    $mail->isHTML(true);
    $mail->Subject = 'Thank you for contacting ' . FROM_NAME;

    $mail->Body = "
        <h3>Hello " . htmlspecialchars($firstName) . ",</h3>

        <p>Thank you for contacting us.</p>

        <p>Please find our company profile attached to this email.</p>

        <p>We appreciate your interest and will get back to you soon.</p>

        <br>
        <p>Regards,<br>" . htmlspecialchars(FROM_NAME) . "</p>
    ";

    $mail->AltBody = "Hello $firstName,\n\nThank you for contacting us. Please find our company profile attached.";

    // Attach PDF
    $mail->addAttachment($pdfPath, 'Company-Profile.pdf');

    // Send email
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

