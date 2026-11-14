<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Pharmacy Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 40px 20px;
        }
        .policy-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
        }
        h1 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 15px;
        }
        h3 {
            color: #2980b9;
            margin-top: 30px;
            font-size: 1.3rem;
        }
        p, li {
            color: #555;
            line-height: 1.7;
        }
        .last-updated {
            font-size: 0.9rem;
            color: #888;
            margin-bottom: 30px;
        }
        .back-btn {
            margin-top: 30px;
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
    <div class="policy-card">
        <h1>Privacy Policy</h1>
        <p class="last-updated">Last Updated: November 2026</p>
        
        <p>At Pharmacist Management System, we take the privacy and security of your personal and medical data extremely seriously. This policy outlines how we collect, protect, and handle your information in compliance with the <strong>Personal Data Protection Act (PDPA)</strong> and <strong>GDPR</strong>.</p>
        
        <h3>1. Data We Collect</h3>
        <p>We may collect personal information necessary for pharmaceutical services, including but not limited to:</p>
        <ul>
            <li>Names, email addresses, and phone numbers.</li>
            <li>Medical and prescription records.</li>
            <li>Authentication credentials (securely hashed).</li>
        </ul>

        <h3>2. How We Protect Your Data</h3>
        <p>We implement state-of-the-art security measures to protect your data from unauthorized access:</p>
        <ul>
            <li><strong>Encryption in Transit:</strong> All communication between your browser and our servers is encrypted using TLS (HTTPS), preventing eavesdropping.</li>
            <li><strong>Encryption at Rest:</strong> Sensitive personal data (e.g., phone numbers and addresses) is encrypted in our database using the <strong>AES-256-GCM</strong> algorithm.</li>
            <li><strong>Access Control:</strong> Role-Based Access Control (RBAC) ensures that only authorized personnel can view specific medical records.</li>
        </ul>

        <h3>3. Your Rights</h3>
        <p>You have the right to request access to, correction of, or deletion of your personal data. We do not sell or share your data with third-party advertisers.</p>

        <div class="back-btn">
            <a href="login_page.php">← Return to Login</a>
        </div>
    </div>
</body>
</html>