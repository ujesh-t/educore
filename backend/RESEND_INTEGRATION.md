# Resend Email Integration

This project uses [Resend](https://resend.com) for sending email notifications.

## Setup

### 1. Get Your API Key

1. Sign up at [resend.com](https://resend.com)
2. Go to **API Keys** section
3. Create a new API key
4. Copy your API key (starts with `re_`)

### 2. Configure Environment

Update your `.env` file:

```env
MAIL_MAILER=resend
MAIL_HOST=smtp.resend.com
MAIL_PORT=587
MAIL_USERNAME=smtp
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="your-verified-domain@resend.dev"
MAIL_FROM_NAME="${APP_NAME}"

RESEND_API_KEY=re_your_actual_api_key_here
```

### 3. Add Your Domain (Production)

1. In Resend dashboard, go to **Domains**
2. Add your domain (e.g., `yourdomain.com`)
3. Add the DNS records to your domain provider
4. Wait for verification (usually 5-10 minutes)

### 4. Update From Address

Once your domain is verified, update `.env`:

```env
MAIL_FROM_ADDRESS="notifications@yourdomain.com"
```

## Usage

### In Controllers

Use the `SendsNotifications` trait:

```php
use App\Traits\SendsNotifications;

class YourController extends Controller
{
    use SendsNotifications;

    public function store(Request $request)
    {
        // Your logic here...

        // Send welcome email
        $this->sendWelcomeNotification(
            $user->email,
            $user->name,
            ['email' => $user->email, 'password' => $password]
        );

        // Send password reset
        $this->sendPasswordResetNotification(
            $user->email,
            $user->name,
            $resetToken
        );

        // Send invoice
        $this->sendInvoiceNotification(
            $school->email,
            $school->name,
            $invoiceNumber,
            $amount,
            $dueDate,
            $pdfPath
        );

        // Send payment confirmation
        $this->sendPaymentConfirmationNotification(
            $school->email,
            $school->name,
            $invoiceNumber,
            $amount,
            $paymentMethod,
            $transactionId
        );

        // Send subscription reminder
        $this->sendSubscriptionReminderNotification(
            $user->email,
            $user->name,
            $planName,
            $expiryDate,
            $daysUntilExpiry
        );

        // Send announcement
        $this->sendAnnouncementNotification(
            $user->email,
            $user->name,
            $subject,
            $title,
            $content
        );
    }
}
```

### Direct Service Usage

```php
use App\Services\ResendMailService;

$mailService = app(ResendMailService::class);

// Send simple email
$result = $mailService->sendSimple(
    to: 'user@example.com',
    subject: 'Hello',
    text: 'Hello from EduCore!',
    html: '<h1>Hello from EduCore!</h1>',
    tags: [['key' => 'type', 'value' => 'greeting']]
);

// Send with template
$result = $mailService->sendWithTemplate(
    to: 'user@example.com',
    subject: 'Welcome',
    templateId: 'your-template-id',
    data: ['name' => 'John']
);
```

## Available Email Templates

All templates are located in `resources/views/emails/`:

- `welcome.blade.php` - Welcome email for new users
- `password-reset.blade.php` - Password reset link
- `invoice.blade.php` - Invoice notification
- `payment-confirmation.blade.php` - Payment receipt
- `subscription-reminder.blade.php` - Subscription expiry reminder
- `announcement.blade.php` - General announcements

## Email Types

| Type | Method | Trigger |
|------|--------|---------|
| Welcome | `sendWelcomeNotification()` | New user registration |
| Password Reset | `sendPasswordResetNotification()` | Password reset request |
| Invoice | `sendInvoiceNotification()` | New invoice created |
| Payment Confirmation | `sendPaymentConfirmationNotification()` | Payment received |
| Subscription Reminder | `sendSubscriptionReminderNotification()` | Before subscription expires |
| Announcement | `sendAnnouncementNotification()` | System announcements |

## Testing

### Development

In development, emails are sent to your Resend test domain. Check your Resend dashboard to view sent emails.

### Local Testing

1. Use Resend's test mode (emails won't be delivered but will appear in dashboard)
2. Or use a real verified domain for full testing

## Troubleshooting

### Emails Not Sending

1. Check your API key is correct in `.env`
2. Verify your domain in Resend dashboard
3. Check logs: `storage/logs/laravel.log`

### API Key Issues

```bash
php artisan config:clear
php artisan cache:clear
```

### View Logs

```bash
tail -f storage/logs/laravel.log
```

## Rate Limits

Resend free tier includes:
- 100 emails/day
- 3,000 emails/month

For higher limits, upgrade your Resend plan.

## Security

- Never commit your API key to version control
- Use environment variables only
- Rotate API keys periodically
- Monitor email usage in Resend dashboard

## Links

- [Resend Documentation](https://resend.com/docs)
- [Resend PHP SDK](https://github.com/resend/resend-php)
- [Resend Dashboard](https://resend.com/dashboard)
