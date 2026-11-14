<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Tips - Pharmacy Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 40px 20px;
        }
        .tips-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .header-section {
            text-align: center;
            margin-bottom: 40px;
        }
        .header-section h1 {
            color: #2c3e50;
            font-weight: 700;
        }
        .header-section p {
            color: #666;
            font-size: 1.1rem;
        }
        .tip-card {
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 20px;
            border-left: 5px solid #3498db;
            transition: transform 0.2s;
        }
        .tip-card:hover {
            transform: translateX(5px);
        }
        .tip-card h4 {
            color: #2980b9;
            font-size: 1.2rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .tip-card p {
            color: #555;
            margin: 0;
            line-height: 1.6;
        }
        .back-btn {
            margin-top: 40px;
            text-align: center;
        }
        .back-btn a {
            text-decoration: none;
            background-color: #3498db;
            color: white;
            padding: 10px 25px;
            border-radius: 5px;
            font-weight: 600;
            transition: 0.3s;
        }
        .back-btn a:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="tips-container">
        <div class="header-section">
            <h1>🛡️ User Security Best Practices</h1>
            <p>Help us keep your account and patient data secure by following these guidelines.</p>
        </div>

        <div class="tip-card">
            <h4>🔑 1. Use Strong Passwords</h4>
            <p>Create complex passwords with at least 8 characters, mixing uppercase, lowercase, numbers, and symbols. Never reuse passwords across different systems.</p>
        </div>

        <div class="tip-card">
            <h4>🎣 2. Beware of Phishing Attempts</h4>
            <p>The system will never ask for your password via email. Always verify the URL in your address bar before entering your login credentials.</p>
        </div>

        <div class="tip-card">
            <h4>📶 3. Avoid Public Wi-Fi</h4>
            <p>Do not access sensitive patient records or login to the system over unsecured public Wi-Fi networks. Use a trusted network or a VPN.</p>
        </div>

        <div class="tip-card">
            <h4>🚪 4. Always Log Out</h4>
            <p>When you finish your session, especially on a shared computer, always click the "Log Out" button to destroy your session and prevent unauthorized access.</p>
        </div>

        <div class="back-btn">
            <a href="login_page.php">← Return to Login</a>
        </div>
    </div>
</body>
</html>