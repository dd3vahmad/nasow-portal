<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Reset Your Password</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
    }
    .container {
      background-color: #ffffff;
      width: 600px;
      margin: 40px auto;
      padding: 40px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .greeting {
      text-align: center;
      color: #228B22;
      margin-bottom: 20px;
    }
    .content {
      color: #333333;
      font-size: 16px;
      line-height: 1.6;
      margin-bottom: 20px;
    }
    .button {
      display: inline-block;
      padding: 10px 20px;
      background-color: #228B22;
      color: #ffffff;
      text-decoration: none;
      border-radius: 5px;
      margin: 20px 0;
    }
    .footer {
      color: #666666;
      font-size: 14px;
      line-height: 1.6;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1 class="greeting">Hello {{ $user->name }},</h1>

    <div class="content">
      <p>We received a request to reset your password for your {{ config('app.name') }} account.</p>
      <p>Please click the button below to reset your password:</p>
      <a href="{{ $url }}" class="button">Reset Password</a>
      <p>If you did not request a password reset, please ignore this email or contact support if you have concerns.</p>
    </div>

    <div class="footer">
      <p>Thanks,<br>{{ config('app.name') }}</p>
    </div>
  </div>
</body>
</html>
