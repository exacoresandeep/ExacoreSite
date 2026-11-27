
<?php

error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once __DIR__ . '/phpmailer/class.phpmailer.php';
require_once __DIR__ . '/phpmailer/class.smtp.php';

function jsonResponse($success, $message, $statusCode = 200)
{
    http_response_code($statusCode);

    echo json_encode(array(
        'success' => $success,
        'message' => $message
    ));

    exit;
}

// Accept POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.', 405);
}

// Get form values
$firstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
$lastName  = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
$company   = isset($_POST['company_name']) ? trim($_POST['company_name']) : '';
$workEmail = isset($_POST['work_email']) ? trim($_POST['work_email']) : '';
$phone     = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$jobTitle  = isset($_POST['job_title']) ? trim($_POST['job_title']) : '';
$pdfKey    = isset($_POST['pdf_key']) ? trim($_POST['pdf_key']) : '';

// Validate required fields
if (
    !$firstName ||
    !$lastName ||
    !$company ||
    !$workEmail ||
    !$phone ||
    !$jobTitle
) {
    jsonResponse(false, 'All fields are required.', 400);
}

// Validate email
if (!filter_var($workEmail, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Invalid email address.', 400);
}

// PDF mapping
$pdfFiles = array(
    'hospital' => 'EXC_CaseStudy_SlowMonthEndClose_1.pdf',
    'healthcare' => 'healthcare-case-study.pdf',
    'manufacturing' => 'manufacturing-case-study.pdf'
);

// Validate PDF selection
if (!isset($pdfFiles[$pdfKey])) {
    jsonResponse(false, 'Invalid case study selection.', 400);
}

// Attachment path
$pdfPath = __DIR__ . '/pdf/' . $pdfFiles[$pdfKey];

if (!file_exists($pdfPath) || !is_readable($pdfPath)) {
    error_log('Case Study PDF not found: ' . $pdfPath);

    jsonResponse(false, 'Case study PDF is currently unavailable.', 500);
}

try {

    /*
    |--------------------------------------------------------------------------
    | 1. Send Case Study PDF to Customer
    |--------------------------------------------------------------------------
    */

    $customerMail = new PHPMailer(true);

    $customerMail->IsSMTP();
    $customerMail->SMTPDebug = 3;
    $customerMail->Debugoutput = 'error_log';

    // Same SMTP configuration as working contact form
    $customerMail->Host = 'mail.exacoreitsolutions.com';
    $customerMail->SMTPAuth = true;
    $customerMail->Username = 'admin@exacoreitsolutions.com';
    $customerMail->Password = 'ikKexV);wc)D$FIa';
    $customerMail->SMTPSecure = 'tls';
    $customerMail->Port = 587;
    $customerMail->Timeout = 30;

    $customerMail->From = 'admin@exacoreitsolutions.com';
    $customerMail->FromName = 'Exacore IT Solutions';

    $customerMail->AddAddress(
        $workEmail,
        $firstName . ' ' . $lastName
    );

    $customerMail->AddReplyTo(
        'sales@exacoreitsolutions.com',
        'Exacore Team'
    );

    $customerMail->IsHTML(true);

    $customerMail->Subject = 'Your Requested Case Study - Exacore IT Solutions';

    $customerMail->Body = '
        <div style="font-family:Arial,sans-serif;line-height:1.6;color:#333;">

            <p>Dear ' . htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') . ',</p>

            <p>Thank you for your interest in Exacore IT Solutions.</p>

            <p>
                Please find your requested case study attached
                to this email.
            </p>

            <p>
                If you have any questions, feel free to contact our team.
            </p>

            <p>
                Best regards,<br>
                <strong>Exacore IT Solutions</strong>
            </p>

        </div>
    ';

    $customerMail->AltBody =
        "Dear $firstName,\n\n" .
        "Thank you for your interest in Exacore IT Solutions.\n" .
        "Please find your requested case study attached.\n\n" .
        "Best regards,\nExacore IT Solutions";

    $customerMail->AddAttachment(
        $pdfPath,
        $pdfFiles[$pdfKey]
    );

    $customerMail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    $customerMail->Send();


    /*
    |--------------------------------------------------------------------------
    | 2. Send Enquiry Details to Sales Team
    |--------------------------------------------------------------------------
    */

    $teamMail = new PHPMailer(true);

    $teamMail->IsSMTP();
    $teamMail->SMTPDebug = 3;
    $teamMail->Debugoutput = 'error_log';

    // Same SMTP configuration as working contact form
    $teamMail->Host = 'mail.exacoreitsolutions.com';
    $teamMail->SMTPAuth = true;
    $teamMail->Username = 'admin@exacoreitsolutions.com';
    $teamMail->Password = 'ikKexV);wc)D$FIa';
    $teamMail->SMTPSecure = 'tls';
    $teamMail->Port = 587;
    $teamMail->Timeout = 30;

    $teamMail->From = 'admin@exacoreitsolutions.com';
    $teamMail->FromName = 'Website Case Study Enquiry';

    $teamMail->AddAddress('sandeep.ps@exacoreitsolutions.com');

    $teamMail->AddReplyTo(
        $workEmail,
        $firstName . ' ' . $lastName
    );

    $teamMail->IsHTML(true);

    $teamMail->Subject = 'New Case Study Enquiry - ' . $company;

    $safe = function ($value) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    };

    $teamMail->Body = '
        <h3>New Case Study Enquiry</h3>

        <table cellpadding="8" cellspacing="0" border="1"
            style="border-collapse:collapse;width:100%;">

            <tr>
                <td><strong>First Name</strong></td>
                <td>' . $safe($firstName) . '</td>
            </tr>

            <tr>
                <td><strong>Last Name</strong></td>
                <td>' . $safe($lastName) . '</td>
            </tr>

            <tr>
                <td><strong>Company</strong></td>
                <td>' . $safe($company) . '</td>
            </tr>

            <tr>
                <td><strong>Work Email</strong></td>
                <td>' . $safe($workEmail) . '</td>
            </tr>

            <tr>
                <td><strong>Phone</strong></td>
                <td>' . $safe($phone) . '</td>
            </tr>

            <tr>
                <td><strong>Job Title / Role</strong></td>
                <td>' . $safe($jobTitle) . '</td>
            </tr>

            <tr>
                <td><strong>Requested Case Study</strong></td>
                <td>' . $safe($pdfFiles[$pdfKey]) . '</td>
            </tr>

        </table>
    ';

    $teamMail->AltBody =
        "New Case Study Enquiry\n\n" .
        "Name: $firstName $lastName\n" .
        "Company: $company\n" .
        "Email: $workEmail\n" .
        "Phone: $phone\n" .
        "Job Title: $jobTitle\n" .
        "Case Study: " . $pdfFiles[$pdfKey];

    $teamMail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    $teamMail->Send();

    jsonResponse(
        true,
        'Thank you! Your case study has been sent to your email successfully.'
    );

} catch (Exception $e) {

    error_log('Case Study PHPMailer Error: ' . $e->getMessage());

    jsonResponse(
        false,
        'Unable to send email. Please try again later.',
        500
    );

} catch (Throwable $e) {

    error_log('Case Study PHP Error: ' . $e->getMessage());

    jsonResponse(
        false,
        'Something went wrong. Please try again later.',
        500
    );

}
