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
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard/dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>سامانه مدیریت تغذیه بیمارستان</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap RTL -->
    <link href="assets/css/bootstrap.rtl.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="assets/css/all.min.css" rel="stylesheet">

    <style>
        @font-face {
            font-family: 'Vazir';
            src: url('assets/webfonts/Vazir-Bold.woff2') format('woff2');
            font-weight: normal;
            font-style: normal;
        }

        * {
            font-family: Vazir, Tahoma, Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background:
                radial-gradient(circle at top right, rgba(255,255,255,0.20), transparent 30%),
                linear-gradient(135deg, #0d6efd 0%, #0b5ed7 45%, #052c65 100%);
            overflow-x: hidden;
        }

        .main-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
            position: relative;
        }

        .bg-shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.10);
            filter: blur(2px);
        }

        .shape-1 {
            width: 220px;
            height: 220px;
            top: 8%;
            right: 8%;
        }

        .shape-2 {
            width: 160px;
            height: 160px;
            bottom: 12%;
            left: 10%;
        }

        .shape-3 {
            width: 90px;
            height: 90px;
            bottom: 25%;
            right: 18%;
            background: rgba(255,255,255,0.08);
        }

        .landing-card {
            width: 100%;
            max-width: 1000px;
            border: 0;
            border-radius: 28px;
            overflow: hidden;
            background: rgba(255,255,255,0.96);
            box-shadow: 0 25px 70px rgba(0,0,0,0.25);
            position: relative;
            z-index: 2;
        }

        .hero-section {
            padding: 55px 45px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #e7f1ff;
            color: #0d6efd;
            border-radius: 50px;
            padding: 8px 16px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .hero-title {
            font-size: 34px;
            font-weight: 800;
            color: #172033;
            line-height: 1.7;
            margin-bottom: 18px;
        }

        .hero-text {
            color: #5f6b7a;
            font-size: 16px;
            line-height: 2;
            margin-bottom: 30px;
        }

        .login-btn {
            border-radius: 16px;
            padding: 13px 34px;
            font-size: 16px;
            font-weight: 700;
            box-shadow: 0 12px 28px rgba(13,110,253,0.35);
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #455468;
            margin-top: 14px;
            font-size: 15px;
        }

        .feature-item i {
            color: #198754;
            font-size: 18px;
        }

        .side-panel {
            height: 100%;
            min-height: 460px;
            padding: 45px 35px;
            background: linear-gradient(160deg, #0d6efd, #084298);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .side-panel::before {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: rgba(255,255,255,0.13);
            top: -80px;
            left: -80px;
        }

        .side-panel::after {
            content: "";
            position: absolute;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: rgba(255,255,255,0.10);
            bottom: -60px;
            right: -60px;
        }

        .system-icon {
            width: 95px;
            height: 95px;
            border-radius: 28px;
            background: rgba(255,255,255,0.16);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
            margin-bottom: 28px;
            position: relative;
            z-index: 1;
        }

        .side-title {
            font-size: 25px;
            font-weight: 800;
            line-height: 1.8;
            position: relative;
            z-index: 1;
        }

        .side-desc {
            color: rgba(255,255,255,0.82);
            line-height: 2;
            margin-top: 15px;
            position: relative;
            z-index: 1;
        }

        .info-box {
            background: rgba(255,255,255,0.14);
            border: 1px solid rgba(255,255,255,0.20);
            border-radius: 20px;
            padding: 18px;
            margin-top: 30px;
            position: relative;
            z-index: 1;
        }

        .info-box div {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
            color: rgba(255,255,255,0.92);
        }

        .footer-text {
            position: fixed;
            bottom: 12px;
            right: 0;
            left: 0;
            text-align: center;
            color: rgba(255,255,255,0.75);
            font-size: 13px;
            z-index: 1;
        }

        .footer-text a {
            color: #fff;
            text-decoration: none;
            font-weight: 700;
        }

        @media (max-width: 768px) {
            .hero-section {
                padding: 35px 25px;
                text-align: center;
            }

            .hero-title {
                font-size: 25px;
            }

            .side-panel {
                min-height: auto;
                padding: 35px 25px;
                text-align: center;
                align-items: center;
            }

            .feature-item {
                justify-content: center;
            }

            .footer-text {
                position: static;
                margin-top: -20px;
                padding-bottom: 15px;
            }
        }
    </style>
</head>

<body>

<div class="main-wrapper">

    <div class="bg-shape shape-1"></div>
    <div class="bg-shape shape-2"></div>
    <div class="bg-shape shape-3"></div>

    <div class="landing-card">
        <div class="row g-0">

            <div class="col-lg-7">
                <div class="hero-section">

                    <div class="hero-badge">
                        <i class="fa-solid fa-shield-halved"></i>
                        سامانه داخلی مدیریت تغذیه
                    </div>

                    <h1 class="hero-title">
                        به سامانه مدیریت تغذیه بیمارستان خوش آمدید
                    </h1>

                    <p class="hero-text">
                        این سامانه برای ثبت، بررسی، تایید و مدیریت اطلاعات تغذیه، آمار روزانه،
                        وعده‌های غذایی، درخواست پزشکان و گزارش‌های مدیریتی طراحی شده است.
                    </p>

                    <a href="auth/login.php" class="btn btn-primary login-btn">
                        <i class="fa-solid fa-right-to-bracket ms-2"></i>
                        ورود به سامانه
                    </a>

                    <div class="mt-4">
                        <div class="feature-item">
                            <i class="fa-solid fa-circle-check"></i>
                            ثبت و مدیریت آمار روزانه تغذیه
                        </div>

                        <div class="feature-item">
                            <i class="fa-solid fa-circle-check"></i>
                            بررسی درخواست‌های غذا و تایید توسط واحدهای مربوطه
                        </div>

                        <div class="feature-item">
                            <i class="fa-solid fa-circle-check"></i>
                            گزارش‌گیری دقیق و قابل پیگیری
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-lg-5">
                <div class="side-panel">

                    <div class="system-icon">
                        <i class="fa-solid fa-utensils"></i>
                    </div>

                    <div class="side-title">
                        مدیریت هوشمند و منظم فرآیندهای تغذیه
                    </div>

                    <p class="side-desc">
                        با ورود به سامانه، بر اساس سطح دسترسی خود می‌توانید اطلاعات مربوط به
                        تغذیه، درخواست‌ها، آمار و گزارش‌ها را مدیریت کنید.
                    </p>

                    <div class="info-box">
                        <div>
                            <i class="fa-solid fa-user-lock"></i>
                            ورود فقط برای کاربران مجاز
                        </div>

                        <div>
                            <i class="fa-solid fa-calendar-days"></i>
                            پشتیبانی از تاریخ شمسی
                        </div>

                        <div>
                            <i class="fa-solid fa-database"></i>
                            اتصال مستقیم به اطلاعات ثبت‌شده
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

</div>

<div class="footer-text">
    طراحی و کدنویسی توسط <a href="https://amid-ahadi.ir">واحد فناوری اطلاعات</a>
</div>

</body>
</html>
