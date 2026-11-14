# Pharmacy Management System

## 1. Project Description

The Pharmacy Management System (PMS) is a web-based application designed to support pharmacy operations and manage patient-related information.

This project focuses on improving the security of an existing Pharmacy Management System. Several security improvements were implemented to strengthen user authentication, access control, data protection, input validation, secure communication, security monitoring and security testing.

The system supports four main user roles: Superadmin, Admin, Pharmacist and Doctor.

## 2. Main Features

The system includes the following features:

- User authentication and role-based access control.
- Pharmacy and medicine inventory management.
- Patient information management.
- Prescription and order management.
- Staff and account management.
- Security improvements to protect application data and user information.

### Security Improvements

The project covers six main security areas:

**1. Level of Access**

- Upgraded password hashing from Argon2i to Argon2id for new and updated passwords.
- Implemented backend Role-Based Access Control (RBAC) in the relevant controllers.
- Centralised session configuration with HttpOnly and SameSite cookie attributes.
- Implemented a 30-minute session inactivity timeout.
- Updated the existing database session tracking functionality to record login time, last activity, logout time and session status.
- Added mandatory first-login password changes for newly approved pharmacists and doctors.

**2. Input Validation, Output Encoding and Error Handling**

- Added server-side input validation for important functions.
- Improved validation for medicine quantities, medicine prices and email addresses.
- Replaced unsafe DOM output using `innerHTML` with `textContent` in the tested functions to prevent XSS.
- Added safer error handling to prevent sensitive database and PHP error information from being exposed to users.
- Recorded detailed error information in server-side logs.

**3. Secure Communication**

- Configured SSL/TLS for HTTPS communication.
- Generated a self-signed certificate for local testing.
- Implemented HTTP-to-HTTPS redirection and HSTS in `login_page.php`.
- Added HTTP security headers, including Content Security Policy (CSP), X-Frame-Options and X-Content-Type-Options.
- Removed the `X-Powered-By` response header to reduce server information exposure.
- Implemented CSRF token protection across application forms.

**4. Data Protection and User Education**

- Implemented AES-256-GCM encryption for sensitive patient information, including phone numbers and addresses.
- Added data masking to reduce unnecessary exposure of personal information.
- Added a Privacy Policy page.
- Added a Security Tips page to educate users about safer system usage.

**5. Security Logging, Analytics and Advanced Security Features**

- Developed a reusable audit logging function to record important system activities.
- Added login security monitoring for failed authentication attempts using keyed HMAC-SHA256 identifiers.
- Implemented temporary account and IP restrictions after five failed login attempts within ten minutes.
- Configured the temporary restrictions to last for 15 minutes.
- Developed an analytics dashboard using PHP, MySQL and Chart.js, with role-based access permissions.

*Note: The audit logging, login monitoring and related database components were developed and tested separately using a local Q5 test database. The dashboard and role-based permission mechanism were also developed, but separate Super Admin and Admin dashboard views and full integration with the original PMS authentication workflow are still required.*

**6. Security and Vulnerability Testing**

- Conducted automated security testing using OWASP ZAP, including baseline and post-implementation scans.
- Performed manual penetration testing using Burp Suite.
- Tested security improvements such as access control, input validation, XSS prevention, error handling, temporary login restrictions, data encryption and audit logging.

## 3. User Roles

The system consists of four user roles:

- **Superadmin:** Manages administrators and oversees the overall system.
- **Admin:** Manages pharmacy operations, staff and patient information.
- **Pharmacist:** Handles prescriptions, medicine inventory and dispensing.
- **Doctor:** Manages patient prescriptions and medical-related information.

Each role has different access permissions depending on its responsibilities.

## 4. Technologies Used

The project uses the following technologies:

- **Frontend:** HTML, CSS, JavaScript and Bootstrap
- **Backend:** PHP
- **Database:** MySQL
- **Local Development Environment:** XAMPP
- **Charts and Visualisation:** Chart.js
- **Security Testing:** OWASP ZAP and Burp Suite
- **Encryption:** AES-256-GCM
- **Password Hashing:** Argon2id
- **Version Control:** Git and GitHub

## 5. Installation and Setup Guide

Follow the steps below to run the Pharmacy Management System locally.

### 5.1 Install XAMPP

1. Download XAMPP from the official website:

   https://www.apachefriends.org/

2. Install XAMPP on the computer.
3. Open the XAMPP Control Panel.
4. Start the following services:
   - Apache
   - MySQL

### 5.2 Download the Project

1. Download or clone this GitHub repository.
2. If downloaded as a ZIP file, extract the project folder.
3. Copy the project folder into the XAMPP `htdocs` directory.

Example project location:

```text
C:\xampp\htdocs\WebDev-Pharmacist-Management-System
```

### 5.3 Create and Import the Database

1. Make sure MySQL is running in the XAMPP Control Panel.
2. Open a web browser and access phpMyAdmin:

   http://localhost/phpmyadmin

3. Create a new database using the database name specified in the project's database connection configuration.
4. Select the newly created database.
5. Open the **Import** tab.
6. Click **Choose File** and select the latest SQL file provided in the project repository.
7. Click **Import** or **Go** to import the database.
8. Wait for the import process to complete.

The database should now contain the tables and data required by the application.

### 5.4 Configure the Database Connection

1. Locate the database connection file:

```text
app/config/conn.php
```

2. Open the file using a code editor such as Visual Studio Code.
3. Check the database connection settings, including:
   - Database host
   - Database username
   - Database password
   - Database name
4. Update the settings according to the local XAMPP MySQL configuration.

For example, a standard local XAMPP configuration may use:

```php
$host = "localhost";
$username = "root";
$password = "";
$database = "your_database_name";
```

*Note: The example above is only a reference. The actual variable names and database name must match the project's configuration.*

5. Save the changes.

### 5.5 Configure PHP and Required Extensions

1. Open the XAMPP Control Panel.
2. Click **Config** beside Apache and open `php.ini`.
3. Check that the PHP extensions required by the project are enabled, especially:
   - `mysqli`
   - `openssl`
4. If an extension is disabled, remove the semicolon (`;`) at the beginning of its corresponding extension line.
5. Save the file and restart Apache.

The project also uses PHP password hashing and verification functions. Ensure the installed PHP version supports Argon2id password hashing.

### 5.6 Configure HTTPS for Local Testing

The application uses HTTPS redirection and security headers. Therefore, HTTPS needs to be configured in the local Apache environment.

1. Open the XAMPP Control Panel.
2. Click **Config** beside Apache.
3. Open the Apache configuration file (`httpd.conf`).
4. Check that the SSL module is enabled.
5. Check that the SSL configuration file is included.
6. Open the Apache SSL configuration file (`httpd-ssl.conf`).
7. Ensure that the SSL virtual host and certificate paths are correctly configured.
8. Generate or configure a self-signed SSL certificate for local testing if one is not already provided.
9. Save the configuration files.
10. Restart Apache.

After HTTPS is configured, access the application using the HTTPS address.

*Note: The exact SSL certificate paths and Apache settings depend on the local XAMPP installation. A browser security warning may appear because the certificate is self-signed. This certificate is intended for local testing only.*

### 5.7 Run the Application

1. Ensure that Apache and MySQL are running in the XAMPP Control Panel.
2. Open a web browser.
3. Enter the following address:

```text
https://localhost/WebDev-Pharmacist-Management-System/
```

4. The application should display its login page.
5. Log in using an account that has already been configured in the database.

*Note: The URL may need to be adjusted according to the actual project folder name and application entry point. If the application uses a different login page, access the appropriate entry point.*

### 5.8 Troubleshooting

The following are some common problems that may occur during local setup.

**Apache cannot start**
- Check whether another application is already using ports 80 or 443.
- Close the application using the conflicting port or configure Apache to use another available port.

**MySQL cannot start**
- Check whether another MySQL service is using the database port.
- Ensure that the MySQL service is not already running in another application.

**Database connection failed**
- Check the database name, username, password and host in `conn.php`.
- Ensure that MySQL is running.

**Database tables are missing**
- Make sure the latest SQL file has been imported successfully.
- Check that the correct database was selected during import.

**Page cannot be found**
- Check that the project folder is inside `htdocs`.
- Make sure the URL uses the correct project folder name and application entry point.

**HTTPS connection failed**
- Check the Apache SSL module and SSL configuration.
- Verify that the certificate and key paths are correct.

**PHP errors appear**
- Check the PHP configuration.
- Review the Apache error log for more information.

## 6. Security Implementation Notes

- Passwords are stored using secure password hashing rather than plaintext storage.
- Argon2id is used for new and updated passwords.
- Backend role checks are applied to the relevant controllers.
- Session security settings are centralised in the session configuration.
- Sensitive patient information is protected using AES-256-GCM encryption.
- CSRF tokens and HTTP security headers provide additional application protection.


## 7. Security Testing

The application was tested using the following security tools:

- **OWASP ZAP:** Used to conduct automated baseline and post-implementation security scans.
- **Burp Suite:** Used for manual security testing and examination of application requests and responses.

The testing covered access control, input validation, XSS prevention, error handling, login restrictions, data encryption and audit logging.



## 8. Project Notes

- This project was developed for educational purposes.
- XAMPP is used as the local development environment.
- The latest SQL file should be imported before running the application.
- The self-signed SSL certificate is intended for local testing and should not be used as a production certificate.
- Database credentials, encryption keys and sensitive patient information should not be uploaded to public repositories.


## 9. License

This project is developed for academic purposes as part of the Software Security assignment.