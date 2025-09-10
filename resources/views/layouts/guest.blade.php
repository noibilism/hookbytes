<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Hookbytes') }} - Login</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <style>
            body {
                font-family: 'JetBrains Mono', monospace;
                margin: 0;
                padding: 0;
                height: 100vh;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .login-container {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                padding: 40px;
                width: 100%;
                max-width: 400px;
                text-align: center;
            }
            
            .login-title {
                font-size: 32px;
                font-weight: 600;
                color: #333;
                margin-bottom: 30px;
            }
            
            .form-group {
                margin-bottom: 20px;
                text-align: left;
            }
            
            .form-label {
                display: block;
                font-size: 14px;
                font-weight: 500;
                color: #666;
                margin-bottom: 8px;
            }
            
            .form-input {
                width: 100%;
                padding: 12px 16px;
                border: 1px solid #e1e5e9;
                border-radius: 8px;
                font-size: 14px;
                transition: all 0.3s ease;
                box-sizing: border-box;
            }
            
            .form-input:focus {
                outline: none;
                border-color: #667eea;
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            }
            
            .forgot-password {
                text-align: right;
                margin-top: 8px;
            }
            
            .forgot-password a {
                color: #999;
                text-decoration: none;
                font-size: 12px;
            }
            
            .forgot-password a:hover {
                color: #667eea;
            }
            
            .login-btn {
                width: 100%;
                padding: 12px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 25px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                margin: 20px 0;
            }
            
            .login-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            }
            
            .social-login {
                margin: 20px 0;
            }
            
            .social-login p {
                color: #999;
                font-size: 14px;
                margin-bottom: 15px;
            }
            
            .social-buttons {
                display: flex;
                justify-content: center;
                gap: 10px;
                margin-bottom: 20px;
            }
            
            .social-btn {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                border: none;
                color: white;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
            }
            
            .social-btn.facebook {
                background: #3b5998;
            }
            
            .social-btn.twitter {
                background: #1da1f2;
            }
            
            .social-btn.google {
                background: #dd4b39;
            }
            
            .social-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            }
            
            .signup-link {
                color: #999;
                font-size: 14px;
            }
            
            .signup-btn {
                background: none;
                border: 1px solid #e1e5e9;
                color: #666;
                padding: 10px 20px;
                border-radius: 20px;
                cursor: pointer;
                font-size: 14px;
                margin-top: 10px;
                transition: all 0.3s ease;
            }
            
            .signup-btn:hover {
                border-color: #667eea;
                color: #667eea;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            {{ $slot }}
        </div>
    </body>
</html>
