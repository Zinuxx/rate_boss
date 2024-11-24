<?php
session_start();

$thisfile = "index.php";

$reg_form = <<<EOREGFORM
<p>We must ask your name and email address to ensure that no one votes more than once, but we do not associate your personal information with your rating.</p>
<form method="POST" action="$thisfile">
    Name: <input type="text" size=25 name="name" required><br><br>
    Email: <input type="email" size=25 name="email" required>
    <input type="hidden" name="stage" value="register">
    <input type="submit" name="submit" value="Submit">
</form>
EOREGFORM;

$rate_form = <<<EORATEFORM
<p>My boss is: </p>
<form method="POST" action="$thisfile">
    <input type="radio" name="rating" value=1 required>Driving me to look for a new job. <br>
    <input type="radio" name="rating" value=2>Not the worst, but pretty bad. <br>
    <input type="radio" name="rating" value=3>Just so-so. <br>
    <input type="radio" name="rating" value=4>Pretty good. <br>
    <input type="radio" name="rating" value=5>A pleasure to work with. <br><br>
    Boss's name: <input type="text" size=25 name="boss" required><br>
    <input type="hidden" name="stage" value="rate"><br><br>
    <input type="submit" name="submit" value="Submit">
</form>
EORATEFORM;

$message = "";

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "rate_boss";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_POST['submit'])) {
    // Show registration form for first-time users
    $message = $reg_form;
} elseif ($_POST['submit'] === 'Submit' && $_POST['stage'] === 'register') {
    // Handle registration
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    if (empty($name) || empty($email)) {
        $message = "<p>Please enter both name and email.</p>" . $reg_form;
    } elseif (strlen($name) > 30 || strlen($email) > 30) {
        $message = "<p>Name or email is too long. Please try again.</p>" . $reg_form;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<p>Invalid email format. Please try again.</p>" . $reg_form;
    } else {
        // Check for duplicate registration
        $stmt = $conn->prepare("SELECT sub_id FROM raters WHERE Name = ? AND Email = ?");
        $stmt->bind_param("ss", $name, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $message = "<p>A user with this name and email already exists.</p>" . $reg_form;
        } else {
            // Insert into `raters` table
            $stmt = $conn->prepare("INSERT INTO raters (Name, Email) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $email);

            if ($stmt->execute()) {
                $_SESSION['rater_id'] = $stmt->insert_id; // Store rater ID for session
                $message = $rate_form;
            } else {
                $message = "<p>Something went wrong with your registration attempt.</p>" . $reg_form;
            }
        }
        $stmt->close();
    }
} elseif ($_POST['submit'] === 'Submit' && $_POST['stage'] === 'rate') {
    // Handle rating submission
    if (!isset($_SESSION['rater_id'])) {
        $message = "<p>Your session has expired. Please register again.</p>" . $reg_form;
    } else {
        $boss = trim($_POST['boss']);
        $rating = intval($_POST['rating']);

        if (empty($boss) || $rating < 1 || $rating > 5) {
            $message = "<p>Please provide a valid boss name and select a rating.</p>" . $rate_form;
        } else {
            // Insert into `ratings` table
            $stmt = $conn->prepare("INSERT INTO ratings (Rating, Boss) VALUES (?, ?)");
            $stmt->bind_param("is", $rating, $boss);

            if ($stmt->execute()) {
                $message = "<p>Your rating has been submitted. Thank you!</p>";
            } else {
                $message = "<p>Something went wrong with your rating attempt.</p>" . $rate_form;
            }
            $stmt->close();
        }
    }
}
$conn->close();
?>

<html>
<head>
    <title>Rate your boss</title>
    <style type="text/css">
        body, p {
            color: black;
            font-family: verdana;
            font-size: 10pt;
        }
        h1 {
            color: black;
            font-family: arial;
            font-size: 12pt;
        }
    </style>
</head>
<body>
    <table border=0 cellpadding=10 width=100%>
        <tr>
            <td bgcolor="#F0F8FF" align="center" valign="top" width="17%"></td>
            <td bgcolor="#FFFFFF" align="center" valign="top" width="83%">
                <h1>Rate your boss anonymously</h1>
                <?php echo $message; ?>
            </td>
        </tr>
    </table>
</body>
</html>



<!-- sql query -->
<!-- CREATE TABLE raters (
    sub_id INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(30) NOT NULL,
    Email VARCHAR(30) NOT NULL UNIQUE
);

CREATE TABLE ratings (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Rating INT NOT NULL,
    Boss VARCHAR(30) NOT NULL
); -->