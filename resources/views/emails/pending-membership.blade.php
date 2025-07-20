<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Yes, we received your application</title>
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
    <h1 class="greeting">Hello {{ $user->name }},</h1>

    <div class="content">
      <p>Your NASOW application is currently under review and awaiting approval. You will get notified once it has been approved.</p>
      <p>If the application takes more than 7 days, please feel free to contact the support team on the NASOW portal.</p>
    </div>

    <div class="footer">
      <p>If you didn’t request this, you can safely ignore it.</p>
      <p>Thanks,<br>{{ config('app.name') }}</p>
    </div>
  </div>
</body>
</html>
