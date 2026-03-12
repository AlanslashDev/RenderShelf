<?php
// Prevent any output before JSON
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Please enter your email']);
        exit;
    }

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Generate 6-digit OTP
        $otp = sprintf("%06d", mt_rand(100000, 999999));
        $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        // Delete old OTPs for this email
        $deleteStmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $deleteStmt->bind_param("s", $email);
        $deleteStmt->execute();
        $deleteStmt->close();

        // Store OTP in database
        $insertStmt = $conn->prepare("INSERT INTO password_resets (email, token, expiry) VALUES (?, ?, ?)");
        $insertStmt->bind_param("sss", $email, $otp, $expiry);

        if ($insertStmt->execute()) {
            // Send email using PHPMailer
            try {
                $mail = new PHPMailer(true);

                // Server settings
                $mail->SMTPDebug = 0; // Disable verbose debug output for production
                $mail->isSMTP();
                $mail->Host = getenv('SMTP_HOST');
                $mail->SMTPAuth = true;
                $mail->Username = getenv('SMTP_USER');
                $mail->Password = getenv('SMTP_PASS');
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = getenv('SMTP_PORT');
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );

                // Recipients
                $mail->setFrom(getenv('SMTP_FROM_EMAIL'), getenv('SMTP_FROM_NAME'));
                $mail->addAddress($email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset OTP - RenderShelf';
                $mail->Body = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 50px auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .content { padding: 40px 30px; color: #333; }
        .otp-box { background: #f8f9fa; border: 2px dashed #667eea; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
        .otp { font-size: 32px; font-weight: bold; color: #667eea; letter-spacing: 8px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1 style='margin: 0;'>RenderShelf</h1>
            <p style='margin: 10px 0 0 0;'>Password Reset Request</p>
        </div>
        <div class='content'>
            <p>Hi there,</p>
            <p>We received a request to reset your password. Use the OTP below to verify your identity:</p>
            <div class='otp-box'>
                <div class='otp'>$otp</div>
            </div>
            <p><strong>This OTP is valid for 15 minutes.</strong></p>
            <p>If you didn't request this, please ignore this email.</p>
            <p>Best regards,<br>The RenderShelf Team</p>
        </div>
        <div class='footer'>
            <p>&copy; 2026 RenderShelf. All rights reserved.</p>
        </div>
    </div>
</body>
</html>";
                $mail->AltBody = "Your RenderShelf password reset OTP is: $otp\n\nThis OTP is valid for 15 minutes.\n\nIf you didn't request this, please ignore this email.\n\nBest regards,\nThe RenderShelf Team";

                $mail->send();
                echo json_encode(['success' => true, 'message' => 'OTP sent successfully to your email']);
            } catch (Exception $e) {
                // FALLBACK: Log OTP to file for local development/testing if email fails
                $log_msg = "[" . date('Y-m-d H:i:s') . "] OTP for $email: $otp\n";
                file_put_contents('otp_log.txt', $log_msg, FILE_APPEND);

                echo json_encode([
                    'success' => true,
                    'message' => 'Dev Mode: Email failed, but OTP was saved to otp_log.txt. Your OTP is: ' . $otp
                ]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error generating OTP']);
        }
        $insertStmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Email not found']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
