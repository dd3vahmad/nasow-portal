<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>New Ticket Assigned</title>
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
    .button {
      display: inline-block;
      padding: 10px 20px;
      background-color: #228B22;
      color: #ffffff;
      text-decoration: none;
      border-radius: 5px;
      margin-top: 20px;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1 class="greeting">Hello {{ $user->name }},</h1>

    <div class="content">
      <p>A new ticket has been assigned to you.</p>

      <p><strong>Title:</strong> {{ $ticket->subject }}</p>
      <p><strong>Submitted By:</strong> {{ $ticket->name }}</p>
    </div>

    <div class="footer">
      <p>If you believe this was assigned in error, please contact your administrator.</p>
      <p>Thanks,<br>{{ config('app.name') }}</p>
    </div>
  </div>
</body>
</html>
