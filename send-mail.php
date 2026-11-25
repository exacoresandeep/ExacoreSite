
<?php

header('Content-Type: application/json; charset=UTF-8');

try {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    $firstName = trim($_POST['firstName'] ?? '');
    $lastName  = trim($_POST['lastName'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $message   = trim($_POST['message'] ?? '');

    // Validate required fields
    $missing = [];

    foreach ([
        'firstName' => $firstName,
        'lastName'  => $lastName,
        'email'     => $email,
        'message'   => $message
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

    // Email configuration
    $fromEmail = 'admin@exacoreitsolutions.com';
    $fromName  = 'Exacore IT Solutions';

    $to = $email;

    $subject = 'Thank you for contacting ' . $fromName;

    // Prevent header injection
    $safeFromName = str_replace(["\r", "\n"], '', $fromName);

    // HTML email body
    $htmlMessage = '
    <!DOCTYPE html>
    <html>
    <body>
        <h3>Hello ' . htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') . ',</h3>

        <p>Thank you for contacting us.</p>

        <p>We appreciate your interest and will get back to you soon.</p>

        <p>
            Regards,<br>
            ' . htmlspecialchars($fromName, ENT_QUOTES, 'UTF-8') . '
        </p>
    </body>
    </html>';

    // Email headers
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . $safeFromName . " <" . $fromEmail . ">\r\n";
    $headers .= "Reply-To: " . $fromEmail . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    // Send email
    $mailSent = mail(
        $to,
        '=?UTF-8?B?' . base64_encode($subject) . '?=',
        $htmlMessage,
        $headers
    );

    if (!$mailSent) {
        throw new Exception('Server failed to submit the email.');
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Mail submitted successfully. Please check your email.'
    ]);

} catch (Throwable $e) {

    error_log('MAIL ERROR: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
