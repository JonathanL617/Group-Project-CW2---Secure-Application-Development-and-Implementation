
<?php
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>403 - Access Denied</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet" crossorigin="anonymous">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: #f0f7f6;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .error-card {
            background: #ffffff;
            width: 100%;
            max-width: 520px;
            padding: 55px 40px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 77, 64, 0.08);
        }

        .icon-container {
            width: 105px;
            height: 105px;
            background: #e0f2f1;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
        }

        .icon-container svg {
            width: 55px;
            height: 55px;
            stroke: #00796b;
        }

        .error-code {
            font-size: 68px;
            font-weight: 800;
            color: #004d40;
            letter-spacing: 2px;
            line-height: 1.2;
        }

        h2 {
            color: #263b38;
            font-size: 24px;
            font-weight: 700;
            margin: 12px 0;
        }

        .description {
            color: #71817e;
            font-size: 14px;
            line-height: 1.8;
            margin: 15px auto 25px;
            max-width: 380px;
        }

        .status {
            display: inline-block;
            background: #fff3e0;
            color: #a65c00;
            padding: 9px 17px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 30px;
        }

        .return-button {
            display: inline-block;
            background: #00796b;
            color: white;
            text-decoration: none;
            padding: 14px 30px;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 600;
            transition: 0.25s ease;
        }

        .return-button:hover {
            background: #004d40;
            color: white;
            transform: translateY(-2px);
        }

        .footer {
            margin-top: 35px;
            padding-top: 20px;
            border-top: 1px solid #edf1f0;
            color: #a0aaa8;
            font-size: 11px;
        }

        @media (max-width: 480px) {
            .error-card {
                padding: 40px 22px;
            }

            .error-code {
                font-size: 55px;
            }

            h2 {
                font-size: 21px;
            }
        }
    </style>
</head>

<body>

    <div class="error-card">

        <div class="icon-container">
            <svg viewBox="0 0 24 24"
                 fill="none"
                 stroke-width="1.7"
                 stroke-linecap="round"
                 stroke-linejoin="round">

                <path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11z"/>
                <path d="M12 8v4"/>
                <path d="M12 16h.01"/>
            </svg>
        </div>

        <div class="error-code">403</div>

        <h2>Access Denied</h2>

        <p class="description">
            Sorry, you don't have permission to access this page.
            Your current account role does not have the required
            access privileges.
        </p>

        <div class="status">
            RESTRICTED ACCESS
        </div>

        <br>

        <a href="dashboard.php" class="return-button">
            &#8592; Return to Dashboard
        </a>

        <div class="footer">
            Pharmacy Management System
            <br><br>
            Security & Access Control
        </div>

    </div>

</body>
</html>
