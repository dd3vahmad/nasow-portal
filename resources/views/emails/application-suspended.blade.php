<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Your Application Has Been Suspended</title>
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
      color: #d9534f;
      margin-bottom: 20px;
    }
    .content {
      color: #333333;
      font-size: 16px;
      line-height: 1.6;
      margin-bottom: 20px;
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
      <p>We regret to inform you that your NASOW application has been suspended.</p>
      <p>This could be due to incomplete information or other eligibility concerns. For clarification or to resolve this, please contact the NASOW support team.</p>
    </div>

    <div class="footer">
      <p>We’re here to help if you need further assistance.</p>
      <p>Thanks,<br>{{ config('app.name') }}</p>
    </div>
  </div>
</body>
</html>
