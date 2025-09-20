# Gmail SMTP Setup for EzShip Email Confirmation

## Step 1: Enable 2-Factor Authentication on Gmail

1. Go to your Google Account settings: https://myaccount.google.com/
2. Click on "Security" in the left sidebar
3. Under "Signing in to Google", enable "2-Step Verification"
4. Follow the prompts to set it up

## Step 2: Generate App Password

1. After enabling 2FA, go to: https://myaccount.google.com/apppasswords
2. Select "Mail" from the "Select app" dropdown
3. Select "Other (Custom name)" from the "Select device" dropdown
4. Enter "EzShip Backend" as the name
5. Click "Generate"
6. **COPY THE 16-CHARACTER PASSWORD** (looks like: xxxx xxxx xxxx xxxx)
7. Save this password securely - you won't be able to see it again!

## Step 3: Update Environment Configuration

Update your `.env.production` file with these settings:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-16-char-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="EzShip"
```

**Important:** 
- Replace `your-email@gmail.com` with your actual Gmail address
- Replace `your-16-char-app-password` with the app password from Step 2 (remove spaces)
- The `MAIL_FROM_ADDRESS` should be the same as `MAIL_USERNAME` for Gmail

## Step 4: Alternative Gmail Settings (if 587 doesn't work)

Try these alternative settings:

```env
# Option 2: SSL on port 465
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-16-char-app-password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="EzShip"
```

## Step 5: Update GitHub Secrets (for CI/CD)

Add these secrets to your GitHub repository:

1. Go to: https://github.com/shahzodjonweb/ezship-backend/settings/secrets/actions
2. Add/Update these secrets:
   - `MAIL_USERNAME`: Your Gmail address
   - `MAIL_PASSWORD`: Your 16-character app password (no spaces)

## Step 6: Test Email Configuration

After deployment, test the email configuration:

1. Visit: https://api.ezship.app/test-email.php (we'll create this)
2. Or run: `docker exec ezship-app-prod php artisan tinker` and then:
   ```php
   Mail::raw('Test email from EzShip', function($message) {
       $message->to('test@example.com')->subject('Test Email');
   });
   ```

## Common Issues and Solutions

### Error: "Username and Password not accepted"
- Make sure you're using the App Password, not your regular Gmail password
- Ensure 2-Factor Authentication is enabled
- Check that the app password doesn't have spaces

### Error: "Connection could not be established"
- Try switching between port 587 (TLS) and port 465 (SSL)
- Check if your server's IP is not blocked by Google
- May need to enable "Less secure app access" (not recommended)

### Error: "Failed to authenticate on SMTP server"
- Double-check the email address and app password
- Regenerate the app password if needed
- Clear Laravel cache: `php artisan config:clear`

## Security Notes

1. **Never commit passwords to Git** - use environment variables
2. **Use App Passwords** - never use your main Gmail password
3. **Consider using a dedicated email account** for sending emails
4. **Monitor your Gmail account** for unusual activity

## Gmail Sending Limits

- **Daily limit**: 500 emails per day (for regular Gmail)
- **Recipients per email**: Maximum 500 recipients
- **Rate limit**: ~20 emails per second

For higher volumes, consider:
- Google Workspace (2,000 emails/day)
- SendGrid, Mailgun, or Amazon SES for production

## Testing Locally

For local development, you can use these settings in `.env`:

```env
# Option 1: Log emails (doesn't send, just logs)
MAIL_MAILER=log

# Option 2: Use Mailtrap for testing
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=tls
```