<?php
declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;
use PHPMailer\PHPMailer\PHPMailer;

const COMPANY_EMAIL = 'info@exacoreitsolutions.com';
const RATE_LIMIT_WINDOW_SECONDS = 900;
const RATE_LIMIT_MAX_REQUESTS = 5;

function respond(int $status, array $body): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}

function requiredEnvironmentValue(string $name): string
{
    $value = getenv($name);
    if ($value === false || trim($value) === '') {
        throw new RuntimeException("Missing required environment variable: {$name}");
    }

    return trim($value);
}

function cleanFormValue(string $key, int $maxLength): string
{
    $value = $_POST[$key] ?? null;
    if (!is_string($value)) {
        respond(422, ['ok' => false, 'message' => 'Please complete all required fields.']);
    }

    $value = trim($value);
    if ($value === '' || strlen($value) > $maxLength || preg_match('/[\r\n\x00]/', $value)) {
        respond(422, ['ok' => false, 'message' => 'Please check the form fields and try again.']);
    }

    return $value;
}

function allowSubmission(): bool
{
    $clientAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rateLimitFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR
        . 'exacore-case-study-' . hash('sha256', $clientAddress) . '.json';
    $handle = fopen($rateLimitFile, 'c+');
    if ($handle === false) {
        throw new RuntimeException('Could not access the submission rate-limit store.');
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            throw new RuntimeException('Could not lock the submission rate-limit store.');
        }

        rewind($handle);
        $stored = stream_get_contents($handle);
        $timestamps = $stored === '' ? [] : json_decode($stored, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($timestamps)) {
            throw new RuntimeException('The submission rate-limit store is invalid.');
        }

        $cutoff = time() - RATE_LIMIT_WINDOW_SECONDS;
        $timestamps = array_values(array_filter(
            $timestamps,
            static fn ($timestamp): bool => is_int($timestamp) && $timestamp > $cutoff
        ));
        if (count($timestamps) >= RATE_LIMIT_MAX_REQUESTS) {
            return false;
        }

        $timestamps[] = time();
        $encoded = json_encode($timestamps, JSON_THROW_ON_ERROR);
        rewind($handle);
        if (!ftruncate($handle, 0) || fwrite($handle, $encoded) === false || !fflush($handle)) {
            throw new RuntimeException('Could not update the submission rate-limit store.');
        }

        return true;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function createCaseStudyPdf(string $htmlPath, string $projectRoot): string
{
    if (!is_file($htmlPath) || !is_readable($htmlPath)) {
        throw new RuntimeException('The hospital case study page could not be read.');
    }

    $source = file_get_contents($htmlPath);
    if ($source === false) {
        throw new RuntimeException('The hospital case study page could not be read.');
    }

    $document = new DOMDocument('1.0', 'UTF-8');
    $previousLibxmlSetting = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML($source, LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($previousLibxmlSetting);
    if (!$loaded) {
        throw new RuntimeException('The hospital case study page could not be parsed.');
    }

    $xpath = new DOMXPath($document);
    $contentNodes = $xpath->query(
        '//*[contains(concat(" ", normalize-space(@class), " "), " detail-heading ")'
        . ' or contains(concat(" ", normalize-space(@class), " "), " case-meta ")'
        . ' or contains(concat(" ", normalize-space(@class), " "), " case-main-image ")'
        . ' or contains(concat(" ", normalize-space(@class), " "), " case-content ")]'
    );
    if ($contentNodes === false || $contentNodes->length === 0) {
        throw new RuntimeException('The hospital case study content was not found.');
    }

    $content = '';
    foreach ($contentNodes as $node) {
        $copy = $node->cloneNode(true);
        if (!$copy instanceof DOMElement) {
            continue;
        }

        $pdfOnlyRemovals = (new DOMXPath($document))->query(
            './/form | .//*[contains(concat(" ", normalize-space(@class), " "), " close-cta ")'
            . ' or contains(concat(" ", normalize-space(@class), " "), " back-button ")]',
            $copy
        );
        if ($pdfOnlyRemovals !== false) {
            for ($index = $pdfOnlyRemovals->length - 1; $index >= 0; $index--) {
                $remove = $pdfOnlyRemovals->item($index);
                if ($remove !== null && $remove->parentNode !== null) {
                    $remove->parentNode->removeChild($remove);
                }
            }
        }

        foreach ($copy->getElementsByTagName('img') as $image) {
            $relativePath = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $image->getAttribute('src')), DIRECTORY_SEPARATOR);
            $imagePath = realpath($projectRoot . DIRECTORY_SEPARATOR . $relativePath);
            if ($imagePath === false || !str_starts_with($imagePath, $projectRoot . DIRECTORY_SEPARATOR)) {
                throw new RuntimeException('A local case study image could not be resolved.');
            }

            $image->setAttribute('src', 'file://' . str_replace('\\', '/', $imagePath));
        }

        $content .= $document->saveHTML($copy);
    }

    $options = new Options();
    $options->set('isRemoteEnabled', false);
    $options->set('chroot', $projectRoot);
    $options->set('defaultFont', 'DejaVu Sans');

    $pdf = new Dompdf($options);
    $pdf->loadHtml(
        '<!doctype html><html lang="en"><head><meta charset="UTF-8"><style>'
        . '@page{margin:18mm 16mm}body{color:#171717;font:10pt "DejaVu Sans",sans-serif;line-height:1.55}'
        . 'h1,h2,h3{color:#171717;line-height:1.2}h1{font-size:23pt}h2{font-size:16pt;margin-top:22pt}'
        . 'p,li{orphans:3;widows:3}.case-meta{border-bottom:1px solid #ddd;padding:8px 0;margin:12px 0}'
        . '.oracle-logo{color:#c74634;font-weight:bold}.case-main-image img{width:100%;height:auto}'
        . '.solution-section,.results-section{page-break-inside:avoid}.results-section{background:#120a08;color:#fff;padding:16px}'
        . 'img{max-width:100%}strong{font-weight:bold}</style></head><body>'
        . $content
        . '</body></html>',
        'UTF-8'
    );
    $pdf->render();

    return $pdf->output();
}

function createSmtpMailer(): PHPMailer
{
    $encryption = strtolower(requiredEnvironmentValue('SMTP_ENCRYPTION'));
    if (!in_array($encryption, ['tls', 'ssl'], true)) {
        throw new RuntimeException('SMTP_ENCRYPTION must be either tls or ssl.');
    }

    $port = filter_var(requiredEnvironmentValue('SMTP_PORT'), FILTER_VALIDATE_INT);
    if ($port === false || $port < 1 || $port > 65535) {
        throw new RuntimeException('SMTP_PORT must be a valid TCP port.');
    }

    $mailer = new PHPMailer(true);
    $mailer->isSMTP();
    $mailer->Host = requiredEnvironmentValue('SMTP_HOST');
    $mailer->SMTPAuth = true;
    $mailer->Username = requiredEnvironmentValue('SMTP_USERNAME');
    $mailer->Password = requiredEnvironmentValue('SMTP_PASSWORD');
    $mailer->SMTPSecure = $encryption === 'ssl'
        ? PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer::ENCRYPTION_STARTTLS;
    $mailer->Port = $port;
    $mailer->CharSet = PHPMailer::CHARSET_UTF8;
    $mailer->setFrom(
        requiredEnvironmentValue('SMTP_FROM_EMAIL'),
        getenv('SMTP_FROM_NAME') ?: 'Exacore IT Solutions'
    );

    return $mailer;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    respond(405, ['ok' => false, 'message' => 'This endpoint accepts form submissions only.']);
}

try {
    $firstName = cleanFormValue('first_name', 100);
    $lastName = cleanFormValue('last_name', 100);
    $companyName = cleanFormValue('company_name', 150);
    $workEmail = cleanFormValue('work_email', 254);
    $phone = cleanFormValue('phone', 40);
    $jobTitle = cleanFormValue('job_title', 150);
    if (filter_var($workEmail, FILTER_VALIDATE_EMAIL) === false) {
        respond(422, ['ok' => false, 'message' => 'Please enter a valid work email address.']);
    }
    if (!allowSubmission()) {
        respond(429, ['ok' => false, 'message' => 'Too many submissions. Please try again later.']);
    }

    $projectRoot = dirname(__DIR__);
    $pdfContents = createCaseStudyPdf(
        $projectRoot . DIRECTORY_SEPARATOR . 'hospital-case-study-detail.html',
        $projectRoot
    );
    $fullName = $firstName . ' ' . $lastName;

    $leadMailer = createSmtpMailer();
    $leadMailer->addAddress(COMPANY_EMAIL);
    $leadMailer->addReplyTo($workEmail, $fullName);
    $leadMailer->Subject = 'Hospital Oracle EBS case study enquiry';
    $leadMailer->Body = "A visitor requested the Oracle EBS hospital case study PDF.\n\n"
        . "Name: {$fullName}\n"
        . "Company: {$companyName}\n"
        . "Work email: {$workEmail}\n"
        . "Phone: {$phone}\n"
        . "Title / role: {$jobTitle}\n";
    $leadMailer->send();

    $visitorMailer = createSmtpMailer();
    $visitorMailer->addAddress($workEmail, $fullName);
    $visitorMailer->Subject = 'Your Oracle EBS Month-End Close Case Study';
    $visitorMailer->Body = "Hello {$firstName},\n\n"
        . "Thank you for your interest. The Oracle EBS hospital month-end close case study is attached.\n\n"
        . "Exacore IT Solutions";
    $visitorMailer->addStringAttachment(
        $pdfContents,
        'oracle-ebs-hospital-month-end-close-case-study.pdf',
        PHPMailer::ENCODING_BASE64,
        'application/pdf'
    );
    $visitorMailer->send();

    respond(200, [
        'ok' => true,
        'message' => 'The case study PDF has been emailed to your work email. Our team has also received your enquiry.'
    ]);
} catch (Throwable $error) {
    error_log('Hospital case study email failed: ' . $error->getMessage());
    respond(500, [
        'ok' => false,
        'message' => 'We could not send the case study email right now. Please try again later or contact info@exacoreitsolutions.com.'
    ]);
}
