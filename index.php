<?php
session_start();

// Carregar PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Generate CAPTCHA in session
if (!isset($_SESSION['captcha_num1']) || !isset($_SESSION['captcha_num2'])) {
    $_SESSION['captcha_num1'] = rand(1, 10);
    $_SESSION['captcha_num2'] = rand(1, 10);
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $company = trim(filter_input(INPUT_POST, 'company', FILTER_SANITIZE_SPECIAL_CHARS));
    $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS));
    $captcha = trim(filter_input(INPUT_POST, 'captcha', FILTER_SANITIZE_NUMBER_INT));
    
    // Validations
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (empty($company)) {
        $errors[] = "Company is required";
    }
    
    if (empty($phone) || !preg_match('/^[\d\s\-\(\)\+]{8,20}$/', $phone)) {
        $errors[] = "Valid phone number is required";
    }
    
    // Validate CAPTCHA
    $correct_answer = $_SESSION['captcha_num1'] + $_SESSION['captcha_num2'];
    if (empty($captcha) || (int)$captcha !== $correct_answer) {
        $errors[] = "Incorrect CAPTCHA answer";
    }
    
    // If no errors, process
    if (empty($errors)) {
        $mail = new PHPMailer(true);
        
        try {
            // Configuração SMTP
            $mail->isSMTP();
            $mail->Host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = getenv('SMTP_USER') ?: 'seu-email@gmail.com';
            $mail->Password = getenv('SMTP_PASS') ?: 'sua-senha-app';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = getenv('SMTP_PORT') ?: 587;
            
            // Remetente
            $mail->setFrom(getenv('SMTP_FROM') ?: 'noreply@yoursite.com', 'Your Site');
            
            // Destinatário
            $mail->addAddress($email, $name);
            
            // Conteúdo do email
            $mail->isHTML(true);
            $mail->Subject = 'Form Submission Confirmation';
            $mail->Body = "
                <h2>Hello " . htmlspecialchars($name) . ",</h2>
                <p>We have received your form submission!</p>
                <h3>Submitted data:</h3>
                <ul>
                    <li><strong>Name:</strong> " . htmlspecialchars($name) . "</li>
                    <li><strong>Email:</strong> " . htmlspecialchars($email) . "</li>
                    <li><strong>Company:</strong> " . htmlspecialchars($company) . "</li>
                    <li><strong>Phone:</strong> " . htmlspecialchars($phone) . "</li>
                </ul>
                <p>We will contact you soon.</p>
                <p>Best regards,<br>Support Team</p>
            ";
            $mail->AltBody = "Hello " . htmlspecialchars($name) . ",\n\n" .
                           "We have received your form submission!\n\n" .
                           "Submitted data:\n" .
                           "Name: " . htmlspecialchars($name) . "\n" .
                           "Email: " . htmlspecialchars($email) . "\n" .
                           "Company: " . htmlspecialchars($company) . "\n" .
                           "Phone: " . htmlspecialchars($phone) . "\n\n" .
                           "We will contact you soon.\n\n" .
                           "Best regards,\nSupport Team";
            
            $mail->send();
            
            // Regenerate CAPTCHA
            $_SESSION['captcha_num1'] = rand(1, 10);
            $_SESSION['captcha_num2'] = rand(1, 10);
            
            // Redirect to thank you page
            header('Location: ?success=1');
            exit;
            
        } catch (Exception $e) {
            $errors[] = "Error sending confirmation email: {$mail->ErrorInfo}";
        }
    }
    
    // Regenerate CAPTCHA on error
    $_SESSION['captcha_num1'] = rand(1, 10);
    $_SESSION['captcha_num2'] = rand(1, 10);
}

// Check if success page
if (isset($_GET['success']) && $_GET['success'] == '1') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Thank You</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
            h1 { color: #333; }
            p { color: #666; line-height: 1.6; }
            a { color: #0066cc; text-decoration: none; }
        </style>
    </head>
    <body>
        <h1>Thank You!</h1>
        <p>Your form has been submitted successfully. We have sent a confirmation email to you.</p>
        <p>We will contact you soon.</p>
        <p><a href="?">← Back to form</a></p>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .error {
            background: #fee;
            border: 1px solid #fcc;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .error ul {
            margin: 0;
            padding-left: 20px;
        }
        .error li {
            color: #c00;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
        input {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .captcha {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .captcha-question {
            background: #f5f5f5;
            padding: 8px 12px;
            border-radius: 4px;
            font-weight: bold;
        }
        .captcha input {
            width: 80px;
            margin-bottom: 0;
        }
        button {
            background: #0066cc;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        button:hover {
            background: #0052a3;
        }
    </style>
</head>
<body>
    <h1>Contact Form</h1>
    
    <?php if (!empty($errors)): ?>
    <div class="error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <label>Name *</label>
        <input type="text" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
        
        <label>Email *</label>
        <input type="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
        
        <label>Company *</label>
        <input type="text" name="company" value="<?php echo isset($_POST['company']) ? htmlspecialchars($_POST['company']) : ''; ?>" required>
        
        <label>Phone *</label>
        <input type="tel" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
        
        <label>Security Check *</label>
        <div class="captcha">
            <span class="captcha-question">
                <?php echo $_SESSION['captcha_num1']; ?> + <?php echo $_SESSION['captcha_num2']; ?> = ?
            </span>
            <input type="number" name="captcha" required>
        </div>
        
        <button type="submit">Submit</button>
    </form>
</body>
</html>