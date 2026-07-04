<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Request</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #d3d3d3; /* Changed to greyish background */
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff; /* Maintained white background */
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            padding: 20px 0;
            background: #00459e; /* Maintained original header background */
            color: #ffffff;
            border-radius: 8px 8px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 20px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #00459e; /* Maintained original button background */
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #003580; /* Maintained original hover color */
        }
        .footer {
            text-align: center;
            padding: 10px;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #eee;
        }
        p {
            margin: 0 0 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Netacube Password Reset</h1>
        </div>
        <div class="content">
            
            <p>You have requested to reset your password for your Netacube account. Click the button below to set a new password:</p>
          
              <?php 
              $token = $data["token"]; 
              $link = Request::root(); 
              ?>

              {{-- TODO: route('master.reset.password.view', ...) removed — wire up the
                   correct tenant reset-password route here once it exists. Until then
                   this link is a non-functional placeholder. --}}
              <p style="text-align: center;">
                 <a href="#" class="button">Reset Your Password</a>
               </p>
          
           
            
            
            <p>This link will expire within 24 hours. If you did not request a password reset, please ignore this email.</p>
            <p>Thank you</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Netamind Technology. All rights reserved.</p>
            <p><a href="https://netamind.com">Visit our website</a> | <a href="mailto:info@netamind.com">Contact Support</a></p>
        </div>
    </div>
</body>
</html>