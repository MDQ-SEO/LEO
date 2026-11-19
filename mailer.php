<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

/**
 * Configure and return a PHPMailer instance with Gmail SMTP credentials
 */
function createMailer() {
    $mail = new PHPMailer(true);

    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'leosurfacecoatings17@gmail.com';
    $mail->Password   = 'unuhvwlmkrwpxtqq';
    //     $mail->Username   = 'mdq.seo@gmail.com';
    // $mail->Password   = 'yxdyofnxdyuywsoh';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';
    $mail->Timeout    = 20;

    // Fix FQDN: Prevent Message-ID and HELO from using local Windows hostname (LAPTOP-ISADU3QA)
    // Non-resolvable Message-ID hostnames are heavily penalized by Gmail spam filter
    $mail->Hostname  = 'gmail.com';
    $mail->MessageID = sprintf('<%s.%s@gmail.com>', time(), bin2hex(random_bytes(8)));

    // SSL options for local / server compatibility
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true
        ]
    ];

    // From and Recipients
    $mail->setFrom('mdq.seo@gmail.com', 'Leo Surface Coatings');
    // $mail->addAddress('celshiyanancy@gmail.com');
    // $mail->addCC('nancycelshiya.mdq@gmail.com');
        $mail->addAddress('hr@leosurfacecoatings.com');
    $mail->addCC('manikandan@mdqualityapps.com');
    $mail->addCC('kumudha.mdq@gmail.com');
    // $mail->addCC('celshiyanancy@gmail.com');



    $mail->isHTML(true);

    // Anti-Spam Optimizations
    $mail->XMailer  = ' '; // Suppress default PHPMailer signature header
    $mail->Encoding = 'base64';

    return $mail;
}

/**
 * Send Contact Inquiry Email
 */
function sendContactEmail(array $data): bool {
    try {
        $mail = createMailer();

        $fullName = trim($data['first_name'] . ' ' . ($data['last_name'] ?? ''));
        $email    = $data['email'] ?? '';
        $phone    = $data['phone'] ?? '';
        $subject  = $data['subject'] ?? 'Website Inquiry';
        $message  = $data['message'] ?? '';
        $date     = date('d M Y, h:i A');

        $mail->Subject = 'New Contact Inquiry: ' . $subject . ' - ' . $fullName;

        $body = '
        <div style="font-family: Arial, sans-serif; max-width: 650px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; background: #ffffff;">
            <div style="background: #1e293b; color: #ffffff; padding: 20px; text-align: center;">
                <h2 style="margin: 0; font-size: 20px; letter-spacing: 0.5px;">Leo Surface Coatings</h2>
                <p style="margin: 5px 0 0; font-size: 13px; color: #94a3b8;">New Contact Form Inquiry Received</p>
            </div>
            <div style="padding: 24px;">
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                    <tr>
                        <td style="padding: 10px 12px; background: #f8fafc; font-weight: bold; width: 30%; color: #475569; border-bottom: 1px solid #e2e8f0;">Name:</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; color: #0f172a; font-weight: 600;">' . htmlspecialchars($fullName) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 12px; background: #f8fafc; font-weight: bold; color: #475569; border-bottom: 1px solid #e2e8f0;">Email:</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; color: #0f172a;"><a href="mailto:' . htmlspecialchars($email) . '" style="color: #2563eb; font-weight: 600;">' . htmlspecialchars($email) . '</a></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 12px; background: #f8fafc; font-weight: bold; color: #475569; border-bottom: 1px solid #e2e8f0;">Phone:</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; color: #0f172a;">' . htmlspecialchars($phone) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 12px; background: #f8fafc; font-weight: bold; color: #475569; border-bottom: 1px solid #e2e8f0;">Subject:</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; color: #0f172a; font-weight: 600;">' . htmlspecialchars($subject) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 12px; background: #f8fafc; font-weight: bold; color: #475569; border-bottom: 1px solid #e2e8f0;">Date & Time:</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; color: #64748b;">' . htmlspecialchars($date) . '</td>
                    </tr>
                </table>

                <div style="margin-top: 20px;">
                    <div style="font-size: 13px; font-weight: bold; color: #475569; text-transform: uppercase; margin-bottom: 8px;">Message:</div>
                    <div style="background: #f8fafc; padding: 16px; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 14px; line-height: 1.6; color: #1e293b; white-space: pre-wrap;">' . htmlspecialchars($message) . '</div>
                </div>

                <div style="margin-top: 24px; text-align: center;">
                    <a href="mailto:' . htmlspecialchars($email) . '?subject=Re: ' . rawurlencode($subject) . '" style="display: inline-block; background: #2563eb; color: #ffffff; padding: 10px 22px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 13px;">Reply to Customer (' . htmlspecialchars($email) . ')</a>
                </div>
            </div>
            <div style="background: #f1f5f9; padding: 12px 20px; text-align: center; font-size: 12px; color: #64748b;">
                This inquiry was submitted from the Leo Surface Coatings Contact Form.
            </div>
        </div>';

        $mail->Body    = $body;
        $mail->AltBody = "New Contact Inquiry\n\nName: $fullName\nEmail: $email\nPhone: $phone\nSubject: $subject\nDate: $date\n\nMessage:\n$message";

        // Attachment
        if (!empty($data['attachment_path']) && file_exists($data['attachment_path'])) {
            $mail->addAttachment($data['attachment_path'], $data['attachment_name'] ?? 'attachment');
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Contact Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send Career Application Email
 */
function sendCareerEmail(array $data): bool {
    try {
        $mail = createMailer();

        $fullName       = trim($data['first_name'] . ' ' . ($data['last_name'] ?? ''));
        $email          = $data['email'] ?? '';
        $phone          = $data['phone'] ?? '';
        $roleApplied    = $data['role_applied'] ?? 'Not specified';
        $currentCompany = $data['current_company'] ?? 'Fresher / Not specified';
        $date           = date('d M Y, h:i A');

        $mail->Subject = 'New Job Application: ' . $roleApplied . ' - ' . $fullName;

        $body = '
        <div style="font-family: Arial, sans-serif; max-width: 650px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; background: #ffffff;">
            <div style="background: #1e293b; color: #ffffff; padding: 20px; text-align: center;">
                <h2 style="margin: 0; font-size: 20px; letter-spacing: 0.5px;">Leo Surface Coatings</h2>
                <p style="margin: 5px 0 0; font-size: 13px; color: #94a3b8;">New Job Application Received</p>
            </div>
            <div style="padding: 24px;">
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                    <tr>
                        <td style="padding: 10px 12px; background: #f8fafc; font-weight: bold; width: 35%; color: #475569; border-bottom: 1px solid #e2e8f0;">Candidate Name:</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; color: #0f172a; font-weight: 600;">' . htmlspecialchars($fullName) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 12px; background: #f8fafc; font-weight: bold; color: #475569; border-bottom: 1px solid #e2e8f0;">Role Applied For:</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; color: #1d4ed8; font-weight: 600;">' . htmlspecialchars($roleApplied) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 12px; background: #f8fafc; font-weight: bold; color: #475569; border-bottom: 1px solid #e2e8f0;">Email:</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; color: #0f172a;"><a href="mailto:' . htmlspecialchars($email) . '" style="color: #2563eb; font-weight: 600;">' . htmlspecialchars($email) . '</a></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 12px; background: #f8fafc; font-weight: bold; color: #475569; border-bottom: 1px solid #e2e8f0;">Phone:</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; color: #0f172a;">' . htmlspecialchars($phone) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 12px; background: #f8fafc; font-weight: bold; color: #475569; border-bottom: 1px solid #e2e8f0;">Current Company:</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; color: #0f172a;">' . htmlspecialchars($currentCompany) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 12px; background: #f8fafc; font-weight: bold; color: #475569; border-bottom: 1px solid #e2e8f0;">Date & Time:</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; color: #64748b;">' . htmlspecialchars($date) . '</td>
                    </tr>
                </table>

                <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 12px 16px; margin-top: 15px; font-size: 13px; color: #1e40af;">
                    <strong>Note:</strong> The candidate\'s resume / CV has been attached to this email.
                </div>

                <div style="margin-top: 24px; text-align: center;">
                    <a href="mailto:' . htmlspecialchars($email) . '?subject=Re: Application for ' . rawurlencode($roleApplied) . '" style="display: inline-block; background: #2563eb; color: #ffffff; padding: 10px 22px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 13px;">Reply to Candidate (' . htmlspecialchars($email) . ')</a>
                </div>
            </div>
            <div style="background: #f1f5f9; padding: 12px 20px; text-align: center; font-size: 12px; color: #64748b;">
                This application was submitted via the Leo Surface Coatings Careers Portal.
            </div>
        </div>';

        $mail->Body    = $body;
        $mail->AltBody = "New Job Application\n\nCandidate: $fullName\nRole Applied: $roleApplied\nEmail: $email\nPhone: $phone\nCurrent Company: $currentCompany\nDate: $date";

        // Attach CV (Required)
        if (!empty($data['cv_path']) && file_exists($data['cv_path'])) {
            $mail->addAttachment($data['cv_path'], $data['cv_name'] ?? 'Resume.pdf');
        }

        // Attach Cover Letter (Optional)
        if (!empty($data['cover_path']) && file_exists($data['cover_path'])) {
            $mail->addAttachment($data['cover_path'], $data['cover_name'] ?? 'Cover_Letter.pdf');
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Career Error: " . $e->getMessage());
        return false;
    }
}
