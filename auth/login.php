<?php
/**
 * -------------------------In the name of ALLAH-----------------------------
 * --------------------------------------------------------------------------
 * Programmer:  Amid Ahadi
 * Email:       Amid-ahadi@gmail.com
 * Website:     amid-ahadi.ir
 * Copyright:   All rights reserved for Amid Ahadi
 * --------------------------------------------------------------------------
 * Coded for Karaj Emam Hospital with love ❤️
 * Created:     2026-06-20
 */
session_start();
include("../config/database.php");

// تنظیم ست کاراکتر جهت اطمینان از صحت تبادل داده
$conn->set_charset("utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    // ۱. کوئری فقط با نام کاربری (بدون مقایسه پسورد در SQL)
    $sql = "SELECT users.*, roles.role_name
            FROM users
            JOIN roles ON roles.id = users.role_id
            WHERE users.username = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // ۲. تایید پسورد هش‌شده به روش Bcrypt
        if (password_verify($password, $user["password"])) {
            
            $_SESSION["user_id"]  = $user["id"];
            $_SESSION["name"]     = $user["name"];
            $_SESSION["role"]     = $user["role_name"];
            $_SESSION["ward_id"]  = $user["ward_id"];

            header("Location: ../dashboard/index.php");
            exit();
        } else {
            $error = "نام کاربری یا رمز اشتباه است";
        }
    } else {
        $error = "نام کاربری یا رمز اشتباه است";
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>RFS Login | سامانه تغذیه رادین</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Tahoma, Geneva, sans-serif;
        }
    </style>
</head>

<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header text-center bg-primary text-white py-3">
                    <h5 class="mb-0">سامانه تغذیه رادین (RFS)</h5>
                </div>
                <div class="card-body p-4">

                    <?php if (isset($error)) : ?>
                        <div class='alert alert-danger text-center small'><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="post" action="">
                        <div class="mb-3">
                            <label class="form-label text-secondary small">نام کاربری</label>
                            <input class="form-control" name="username" placeholder="Username" required autocomplete="username">
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary small">رمز عبور</label>
                            <input class="form-control" type="password" name="password" placeholder="Password" required autocomplete="current-password">
                        </div>

                        <button class="btn btn-primary w-100 py-2">ورود به سیستم</button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
