<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - RenderShelf</title>
    <link rel="stylesheet" href="style.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        .loading {
            display: none;
            text-align: center;
            margin: 20px 0;
        }
        .loading.active {
            display: block;
        }
        .spinner {
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 3px solid #667eea;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="center-screen">
        <div class="container">
            <div style="text-align: left; margin-bottom: 20px;">
                <a href="login.php" style="color: white; text-decoration: none; font-size: 24px;"><ion-icon name="arrow-back-outline"></ion-icon></a>
            </div>

            <div class="auth-card">
                <div id="message-container"></div>

                <div class="logo-area">
                    <h1>Reset Password</h1>
                    <p class="subtitle">Enter your email to receive an OTP.</p>
                </div>

                <form id="forgot-password-form">
                    <div class="form-group">
                        <label>Email</label>
                        <div class="input-wrapper">
                            <ion-icon name="mail" class="input-icon"></ion-icon>
                            <input type="email" id="email" name="email" placeholder="editor@rendershelf.com" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" id="submit-btn">Send OTP</button>
                    
                    <div class="loading" id="loading">
                        <div class="spinner"></div>
                        <p style="color: #999; margin-top: 10px;">Sending OTP...</p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('forgot-password-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const submitBtn = document.getElementById('submit-btn');
            const loading = document.getElementById('loading');
            const messageContainer = document.getElementById('message-container');
            
            // Clear previous messages
            messageContainer.innerHTML = '';
            
            // Show loading
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending...';
            loading.classList.add('active');
            
            try {
                const formData = new FormData();
                formData.append('email', email);
                
                const response = await fetch('send_otp.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                // Hide loading
                loading.classList.remove('active');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Send OTP';
                
                if (data.success) {
                    messageContainer.innerHTML = `<div class="success-message">${data.message}</div>`;
                    // Redirect to OTP verification page after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'verify_otp.php?email=' + encodeURIComponent(email);
                    }, 2000);
                } else {
                    messageContainer.innerHTML = `<div class="error-message">${data.message}</div>`;
                }
            } catch (error) {
                loading.classList.remove('active');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Send OTP';
                messageContainer.innerHTML = `<div class="error-message">An error occurred. Please try again.</div>`;
                console.error('Error:', error);
            }
        });
    </script>
</body>
</html>
