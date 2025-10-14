<?php
// thank-you.php

// (Optional) hide notices in prod:
ini_set('display_errors', '0');

// Read the id from the redirect: thank-you.php?id=...
$id = isset($_GET['id']) && $_GET['id'] !== ''
    ? htmlspecialchars($_GET['id'], ENT_QUOTES, 'UTF-8')
    : null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Thank You</title>
    <link rel="stylesheet" href="./css/index.css">
    <style>
        .thankyou-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px
        }

        .thankyou-card {
            max-width: 880px;
            background: rgb(4 4 4 / 55%);
            backdrop-filter: blur(8px);
            border-radius: 16px;
            padding: 48px 36px;
            box-shadow: 0 6px 24px rgba(0, 0, 0, .25);
        }

        .thankyou-title {
            font-family: Orbitron, sans-serif;
            letter-spacing: 4px;
            font-size: 48px;
            margin-bottom: 16px
        }

        .thankyou-text {
            opacity: .9;
            line-height: 1.6;
            font-size: 18px;
            margin-top: 8px
        }

        .back-btn {
            margin-top: 28px;
            display: inline-block
        }
    </style>
</head>

<body>
    <div class="thankyou-wrap">
        <div class="thankyou-card">
            <img src="assets/images/Neurobot-Logo.svg" alt="Neurobot" width="260" style="margin-bottom:18px" />
            <div class="thankyou-title">Thank you for your submission</div>

            <?php if ($id): ?>
                <p class="thankyou-text">Reference ID: <strong><?php echo $id; ?></strong></p>
            <?php endif; ?>

            <a href="/" class="cross-cut-button back-btn">GO TO HOME <div class="arrows"></div></a>
        </div>
    </div>
</body>

</html>