<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Yay your issue has been resolved</title>
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
    <h1 class="greeting">Hey {{ $ticket->user->name }},</h1>

    <div class="content">
      <p>Your issue "{{ $ticket->subject }}" has been resolved.</p>
      <p>Log back into you NASOW portal to confirm or create a new issue if you still have any.</p>

      <p><strong>Title:</strong> {{ $ticket->subject }}</p>
      <p><strong>Resolved By:</strong> {{ $user->name }}</p>
    </div>

    <div class="footer">
      <p>If you believe this was error, please contact your administrator.</p>
      <p>Thanks,<br>{{ config('app.name') }}</p>
    </div>
  </div>
</body>
</html>
