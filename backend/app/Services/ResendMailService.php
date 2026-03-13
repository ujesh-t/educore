<?php

namespace App\Services;

use Resend;
use Illuminate\Support\Facades\Log;

class ResendMailService
{
    protected $resend;
    protected $fromAddress;
    protected $fromName;

    public function __construct()
    {
        $this->resend = new Resend(env('RESEND_API_KEY'));
        $this->fromAddress = config('mail.from.address', 'onboarding@resend.dev');
        $this->fromName = config('mail.from.name', config('app.name', 'EduCore SMS'));
    }

    /**
     * Send a simple email
     */
    public function sendSimple(
        string $to,
        string $subject,
        string $text,
        ?string $html = null,
        array $attachments = [],
        array $tags = []
    ): array {
        try {
            $response = $this->resend->emails->send([
                'from' => "{$this->fromName} <{$this->fromAddress}>",
                'to' => [$to],
                'subject' => $subject,
                'text' => $text,
                'html' => $html,
                'attachments' => $attachments,
                'tags' => $tags,
            ]);

            Log::info('Email sent via Resend', [
                'to' => $to,
                'subject' => $subject,
                'id' => $response->id,
            ]);

            return [
                'success' => true,
                'id' => $response->id,
                'message' => 'Email sent successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send email via Resend', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send email with template
     */
    public function sendWithTemplate(
        string $to,
        string $subject,
        string $templateId,
        array $data = []
    ): array {
        try {
            $response = $this->resend->emails->send([
                'from' => "{$this->fromName} <{$this->fromAddress}>",
                'to' => [$to],
                'subject' => $subject,
                'template_id' => $templateId,
            ]);

            Log::info('Template email sent via Resend', [
                'to' => $to,
                'subject' => $subject,
                'template_id' => $templateId,
                'id' => $response->id,
            ]);

            return [
                'success' => true,
                'id' => $response->id,
                'message' => 'Email sent successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send template email via Resend', [
                'to' => $to,
                'subject' => $subject,
                'template_id' => $templateId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send welcome email
     */
    public function sendWelcomeEmail(string $to, string $name, array $credentials = []): array
    {
        $subject = 'Welcome to ' . config('app.name');
        $text = "Hello {$name},\n\nWelcome to " . config('app.name') . "!\n\n";
        
        if (!empty($credentials['email']) && !empty($credentials['password'])) {
            $text .= "Your login credentials:\n";
            $text .= "Email: {$credentials['email']}\n";
            $text .= "Password: {$credentials['password']}\n\n";
        }

        $text .= "Get started by logging in to your account.\n\nBest regards,\n" . config('app.name') . " Team";

        $html = view('emails.welcome', [
            'name' => $name,
            'appName' => config('app.name'),
            'credentials' => $credentials,
        ])->render();

        return $this->sendSimple($to, $subject, $text, $html);
    }

    /**
     * Send password reset email
     */
    public function sendPasswordReset(string $to, string $name, string $resetUrl): array
    {
        $subject = 'Password Reset Request - ' . config('app.name');
        $text = "Hello {$name},\n\nYou have requested to reset your password.\n\n";
        $text .= "Click the link below to reset your password:\n{$resetUrl}\n\n";
        $text .= "This link will expire in 60 minutes.\n\n";
        $text .= "If you did not request a password reset, please ignore this email.\n\nBest regards,\n" . config('app.name') . " Team";

        $html = view('emails.password-reset', [
            'name' => $name,
            'resetUrl' => $resetUrl,
            'appName' => config('app.name'),
        ])->render();

        return $this->sendSimple($to, $subject, $text, $html, [], [['key' => 'type', 'value' => 'password-reset']]);
    }

    /**
     * Send invoice email
     */
    public function sendInvoice(
        string $to,
        string $name,
        string $invoiceNumber,
        float $amount,
        string $dueDate,
        ?string $pdfPath = null
    ): array {
        $subject = "Invoice {$invoiceNumber} from " . config('app.name');
        
        $attachments = [];
        if ($pdfPath && file_exists($pdfPath)) {
            $attachments = [
                [
                    'filename' => basename($pdfPath),
                    'content' => base64_encode(file_get_contents($pdfPath)),
                ],
            ];
        }

        $text = "Hello {$name},\n\nPlease find attached your invoice {$invoiceNumber}.\n\n";
        $text .= "Amount Due: ₹" . number_format($amount, 2) . "\n";
        $text .= "Due Date: {$dueDate}\n\n";
        $text .= "Please make the payment before the due date to avoid any late fees.\n\n";
        $text .= "Thank you for your business!\n\nBest regards,\n" . config('app.name') . " Team";

        $html = view('emails.invoice', [
            'name' => $name,
            'invoiceNumber' => $invoiceNumber,
            'amount' => $amount,
            'dueDate' => $dueDate,
            'appName' => config('app.name'),
        ])->render();

        return $this->sendSimple($to, $subject, $text, $html, $attachments, [['key' => 'type', 'value' => 'invoice']]);
    }

    /**
     * Send payment confirmation email
     */
    public function sendPaymentConfirmation(
        string $to,
        string $name,
        string $invoiceNumber,
        float $amount,
        string $paymentMethod,
        string $transactionId
    ): array {
        $subject = "Payment Confirmation - Invoice {$invoiceNumber}";
        
        $text = "Hello {$name},\n\nYour payment has been received successfully!\n\n";
        $text .= "Payment Details:\n";
        $text .= "Invoice: {$invoiceNumber}\n";
        $text .= "Amount Paid: ₹" . number_format($amount, 2) . "\n";
        $text .= "Payment Method: " . ucfirst($paymentMethod) . "\n";
        $text .= "Transaction ID: {$transactionId}\n\n";
        $text .= "Thank you for your payment!\n\nBest regards,\n" . config('app.name') . " Team";

        $html = view('emails.payment-confirmation', [
            'name' => $name,
            'invoiceNumber' => $invoiceNumber,
            'amount' => $amount,
            'paymentMethod' => $paymentMethod,
            'transactionId' => $transactionId,
            'appName' => config('app.name'),
        ])->render();

        return $this->sendSimple($to, $subject, $text, $html, [], [['key' => 'type', 'value' => 'payment']]);
    }

    /**
     * Send subscription reminder email
     */
    public function sendSubscriptionReminder(
        string $to,
        string $name,
        string $planName,
        string $expiryDate,
        int $daysUntilExpiry
    ): array {
        $subject = "Subscription Expiry Reminder - " . config('app.name');
        
        $text = "Hello {$name},\n\nThis is a friendly reminder that your {$planName} subscription will expire in {$daysUntilExpiry} days.\n\n";
        $text .= "Expiry Date: {$expiryDate}\n\n";
        $text .= "Please renew your subscription to continue enjoying our services.\n\n";
        $text .= "Best regards,\n" . config('app.name') . " Team";

        $html = view('emails.subscription-reminder', [
            'name' => $name,
            'planName' => $planName,
            'expiryDate' => $expiryDate,
            'daysUntilExpiry' => $daysUntilExpiry,
            'appName' => config('app.name'),
        ])->render();

        return $this->sendSimple($to, $subject, $text, $html, [], [['key' => 'type', 'value' => 'subscription-reminder']]);
    }

    /**
     * Send announcement email
     */
    public function sendAnnouncement(
        string $to,
        string $name,
        string $subject,
        string $title,
        string $content
    ): array {
        $text = "Hello {$name},\n\n{$title}\n\n{$content}\n\nBest regards,\n" . config('app.name') . " Team";

        $html = view('emails.announcement', [
            'name' => $name,
            'title' => $title,
            'content' => $content,
            'appName' => config('app.name'),
        ])->render();

        return $this->sendSimple($to, $subject, $text, $html, [], [['key' => 'type', 'value' => 'announcement']]);
    }
}
