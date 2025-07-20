<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Your Application Has Been Approved</title>
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
    .footer {
      color: #666666;
      font-size: 14px;
      line-height: 1.6;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1 class="greeting">Congratulations {{ $user->name }},</h1>

    <div class="content">
      <p>Your NASOW application has been approved!</p>
      <p>You can now log in to the NASOW portal and explore the available member features.</p>
    </div>

    <div class="footer">
      <p>If you didn’t expect this, please contact support immediately.</p>
      <p>Thanks,<br>{{ config('app.name') }}</p>
    </div>
  </div>
</body>
</html>
