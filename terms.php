<?php
/**
 * terms.php - Privacy Policy and Terms & Conditions
 */
require_once 'frontend/partials/_page_header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy & Terms - Santa Fe Beach Club</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="frontend/assets/css/style.css">
    <style>
        .terms-container {
            max-width: 900px;
            margin: 60px auto;
            padding: 40px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            font-family: 'Outfit', sans-serif;
            color: #334155;
            line-height: 1.6;
        }
        .terms-container h1 { font-size: 28px; color: #0F172A; margin-bottom: 24px; text-align: center; }
        .terms-container h2 { font-size: 20px; color: #1E293B; margin-top: 32px; margin-bottom: 16px; border-bottom: 1px solid #E2E8F0; padding-bottom: 8px; }
        .terms-container p { margin-bottom: 16px; font-size: 15px; }
        .terms-container ul { margin-bottom: 16px; padding-left: 20px; }
        .terms-container li { margin-bottom: 8px; font-size: 15px; }
    </style>
</head>
<body>
    <?php render_header("Privacy & Terms", "Santa Fe Beach Club"); ?>

    <div class="terms-container">
        <h1>Privacy Policy & Terms of Service</h1>
        <p>Last Updated: <?php echo date('F j, Y'); ?></p>

        <h2>1. Data Collection & Privacy (Data Minimization)</h2>
        <p>We only collect the minimum personal information required to process and manage your reservation:</p>
        <ul>
            <li>Full Name</li>
            <li>Email Address (for booking confirmation and OTP login)</li>
            <li>Phone Number (for emergency contact and reservation updates)</li>
            <li>Country of Origin (for demographic analytics)</li>
            <li>Payment Proof (e.g., GCash/Bank transfer receipts)</li>
        </ul>
        <p>We do <strong>not</strong> collect or store sensitive financial data like credit card numbers. All payments are processed manually via GCash or Bank Transfer, or handled in person.</p>

        <h2>2. Data Retention & Deletion</h2>
        <p>Your personal data is retained only for as long as necessary to fulfill the purposes outlined in this policy:</p>
        <ul>
            <li><strong>Booking Data:</strong> Retained for accounting and legal compliance for a period of up to 2 years after your stay.</li>
            <li><strong>Payment Receipts:</strong> Securely deleted 90 days after your successful checkout.</li>
            <li><strong>OTP Codes:</strong> Cryptographically hashed and automatically invalidated after 10 minutes or upon successful use.</li>
        </ul>

        <h2>3. Access Control & Security</h2>
        <p>Your personal data is strictly protected. Access to guest profiles, contact information, and payment receipts is restricted exclusively to authorized Management and Reception staff using Role-Based Access Control (RBAC). Your data is never sold to third parties.</p>

        <h2>4. Cancellation & Refund Policy</h2>
        <p>You may cancel your booking using the unique secure link provided in your confirmation email. Cancellations must be made prior to the payment deadline. Refunds for paid reservations are subject to management approval.</p>
        
        <h2>5. Your Rights</h2>
        <p>You have the right to request access to the personal data we hold about you, or request its deletion. For any privacy concerns, please contact our support team.</p>
    </div>
</body>
</html>
