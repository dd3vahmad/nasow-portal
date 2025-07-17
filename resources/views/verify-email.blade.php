<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Verify Email Address</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #ffffff; padding: 40px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
        <tr>
            <td align="center" style="padding-bottom: 20px;">
                <h1 style="color: #228B22;">Hello {{ $user->name }},</h1>
            </td>
        </tr>
        <tr>
            <td style="padding-bottom: 20px; color: #333333; font-size: 16px;">
                <p>Thanks for signing up! Please confirm your email address by clicking the button below.</p>
            </td>
        </tr>
        <tr>
            <td align="center" style="padding-bottom: 20px;">
                <a href="{{ $url }}" style="background-color: #228B22; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">Confirm Email</a>
            </td>
        </tr>
        <tr>
            <td style="color: #666666; font-size: 14px;">
                <p>If you didn’t request this, you can safely ignore it.</p>
                <p>Thanks,<br>{{ config('app.name') }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
