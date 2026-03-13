<?php

namespace App\Traits;

use App\Services\ResendMailService;

trait SendsNotifications
{
    protected ResendMailService $mailService;

    /**
     * Send welcome email to new user
     */
    protected function sendWelcomeNotification(string $email, string $name, array $credentials = []): array
    {
        return $this->getMailService()->sendWelcomeEmail($email, $name, $credentials);
    }

    /**
     * Send password reset email
     */
    protected function sendPasswordResetNotification(string $email, string $name, string $resetToken): array
    {
        $resetUrl = url('/reset-password?token=' . $resetToken . '&email=' . urlencode($email));
        return $this->getMailService()->sendPasswordReset($email, $name, $resetUrl);
    }

    /**
     * Send invoice email
     */
    protected function sendInvoiceNotification(
        string $email,
        string $name,
        string $invoiceNumber,
        float $amount,
        string $dueDate,
        ?string $pdfPath = null
    ): array {
        return $this->getMailService()->sendInvoice($email, $name, $invoiceNumber, $amount, $dueDate, $pdfPath);
    }

    /**
     * Send payment confirmation email
     */
    protected function sendPaymentConfirmationNotification(
        string $email,
        string $name,
        string $invoiceNumber,
        float $amount,
        string $paymentMethod,
        string $transactionId
    ): array {
        return $this->getMailService()->sendPaymentConfirmation(
            $email,
            $name,
            $invoiceNumber,
            $amount,
            $paymentMethod,
            $transactionId
        );
    }

    /**
     * Send subscription expiry reminder
     */
    protected function sendSubscriptionReminderNotification(
        string $email,
        string $name,
        string $planName,
        string $expiryDate,
        int $daysUntilExpiry
    ): array {
        return $this->getMailService()->sendSubscriptionReminder(
            $email,
            $name,
            $planName,
            $expiryDate,
            $daysUntilExpiry
        );
    }

    /**
     * Send announcement email
     */
    protected function sendAnnouncementNotification(
        string $email,
        string $name,
        string $subject,
        string $title,
        string $content
    ): array {
        return $this->getMailService()->sendAnnouncement($email, $name, $subject, $title, $content);
    }

    /**
     * Get the mail service instance
     */
    protected function getMailService(): ResendMailService
    {
        if (!isset($this->mailService)) {
            $this->mailService = app(ResendMailService::class);
        }
        return $this->mailService;
    }
}
