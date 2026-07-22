<?php
// simple_email_sender.php - 간단한 이메일 발송 클래스

// PHPMailer 라이브러리 로드
require_once 'lib/PHPMailer/src/Exception.php';
require_once 'lib/PHPMailer/src/PHPMailer.php';
require_once 'lib/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class SimpleEmailSender {
    private $mailjetApiKey;
    private $mailjetSecretKey;
    private $fromEmail;
    private $fromName;
    
    public function __construct($apiKey, $secretKey, $fromEmail = 'noreply@tourhellousa.com', $fromName = 'Tour Hello USA') {
        $this->mailjetApiKey = $apiKey;
        $this->mailjetSecretKey = $secretKey;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }
    
    /**
     * 이메일 발송 함수
     */
    public function sendEmail($toEmail, $toName, $subject, $message, $attachmentPath = null) {
        $mail = new PHPMailer(true);
        
        try {
            // Mailjet SMTP 설정
            $mail->isSMTP();
            $mail->Host = 'in-v3.mailjet.com';
            $mail->SMTPAuth = true;
            $mail->Username = $this->mailjetApiKey;
            $mail->Password = $this->mailjetSecretKey;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';
            
            // 발신자 설정
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addReplyTo($this->fromEmail, $this->fromName);
            
            // 수신자 설정
            $mail->addAddress($toEmail, $toName);
            
            // 이메일 내용 설정
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $this->generateHTMLEmail($message);
            $mail->AltBody = strip_tags($message);
            
            // 첨부파일 추가
            if (!empty($attachmentPath) && file_exists($attachmentPath)) {
                $fileName = basename($attachmentPath);
                $mail->addAttachment($attachmentPath, $fileName);
            }
            
            // 이메일 발송
            $result = $mail->send();
            
            return [
                'success' => true,
                'message' => '이메일이 성공적으로 발송되었습니다.',
                'to' => $toEmail,
                'subject' => $subject
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => '이메일 발송 실패: ' . $mail->ErrorInfo,
                'error' => $e->getMessage(),
                'to' => $toEmail
            ];
        }
    }
    
    /**
     * HTML 이메일 템플릿 생성
     */
    private function generateHTMLEmail($message) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body {
                    font-family: 'Arial', sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 0;
                    background-color: #f4f4f4;
                }
                .container {
                    background: white;
                    margin: 20px;
                    border-radius: 10px;
                    overflow: hidden;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                }
                .header {
                    background: linear-gradient(135deg, #2E86AB 0%, #A23B72 100%);
                    color: white;
                    padding: 30px 20px;
                    text-align: center;
                }
                .header h1 {
                    margin: 0;
                    font-size: 24px;
                    font-weight: bold;
                }
                .content {
                    padding: 30px;
                    background: white;
                }
                .content p {
                    margin-bottom: 15px;
                }
                .footer {
                    background: #333;
                    color: white;
                    padding: 20px;
                    text-align: center;
                    font-size: 12px;
                }
                .footer p {
                    margin: 5px 0;
                }
                .highlight {
                    background: #f8f9fa;
                    padding: 15px;
                    border-left: 4px solid #2E86AB;
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>" . $this->fromName . "</h1>
                </div>
                <div class='content'>
                    " . nl2br($message) . "
                </div>
                <div class='footer'>
                    <p><strong>" . $this->fromName . "</strong></p>
                    <p>이 이메일은 자동으로 발송된 메일입니다.</p>
                    <p>문의사항이 있으시면 회신해 주세요.</p>
                </div>
            </div>
        </body>
        </html>";
    }
}
?>