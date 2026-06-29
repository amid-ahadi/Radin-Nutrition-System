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
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION["user_id"])) { header("Location: ../auth/login.php"); exit(); }

$role = $_SESSION["role"];
$name = $_SESSION["name"];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پنل کاربری - سامانه تغذيه رادین</title>

    <link rel="stylesheet" href="../assets/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="../assets/css/all.min.css">

    <style>
        :root {
            --sidebar-width: 275px;
            --sidebar-bg: #182433;
            --sidebar-bg-2: #111a26;
            --sidebar-hover: #24364a;
            --primary: #0d6efd;
            --primary-soft: rgba(13, 110, 253, 0.14);
            --text-muted: #8ea0b7;
            --page-bg: #f3f6fa;
            --border-soft: #e7ecf2;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Tahoma, Arial, sans-serif;
            background: var(--page-bg);
            color: #263238;
        }

        .wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
            align-items: stretch;
        }

        #sidebar {
            min-width: var(--sidebar-width);
            max-width: var(--sidebar-width);
            min-height: 100vh;
            background:
                linear-gradient(180deg, rgba(13,110,253,0.10), transparent 180px),
                var(--sidebar-bg);
            color: #fff;
            transition: all 0.3s ease;
            box-shadow: -8px 0 30px rgba(0,0,0,0.10);
            position: sticky;
            top: 0;
            overflow-y: auto;
        }

        #sidebar::-webkit-scrollbar {
            width: 6px;
        }

        #sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.16);
            border-radius: 10px;
        }

        #sidebar .sidebar-header {
            padding: 24px 20px 20px;
            background: var(--sidebar-bg-2);
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .brand-icon {
            width: 58px;
            height: 58px;
            margin: 0 auto 12px;
            border-radius: 18px;
            background: linear-gradient(135deg, #0d6efd, #20c997);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 12px 28px rgba(13,110,253,0.28);
        }

        .brand-icon i {
            font-size: 26px;
            color: #fff;
        }

        #sidebar .sidebar-header h4 {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
            color: #fff;
            letter-spacing: 0;
        }

        .user-chip {
            margin-top: 12px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            border-radius: 999px;
            background: rgba(255,255,255,0.08);
            color: #d7e5f5;
            font-size: 13px;
            max-width: 100%;
        }

        .user-chip span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        #sidebar ul.components {
            padding: 16px 12px 22px;
            margin: 0;
        }

        #sidebar ul li {
            list-style: none;
        }

        #sidebar ul li a {
            min-height: 44px;
            padding: 11px 14px;
            margin: 4px 0;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #c5d0df;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.2s ease;
            position: relative;
        }

        #sidebar ul li a i {
            width: 22px;
            text-align: center;
            font-size: 15px;
            color: #8ea0b7;
            transition: all 0.2s ease;
        }

        #sidebar ul li a:hover {
            color: #fff;
            background: var(--sidebar-hover);
            transform: translateX(-3px);
        }

        #sidebar ul li a:hover i {
            color: #fff;
        }

        #sidebar ul li.active > a {
            color: #fff;
            background: linear-gradient(135deg, #0d6efd, #0b5ed7);
            box-shadow: 0 10px 24px rgba(13,110,253,0.24);
        }

        #sidebar ul li.active > a i {
            color: #fff;
        }

        .menu-section {
            margin: 16px 4px 8px;
            padding: 8px 12px;
            color: #7f93aa;
            font-size: 12px;
            font-weight: 700;
            border-radius: 10px;
            background: rgba(255,255,255,0.04);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .menu-section::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #0d6efd;
            display: inline-block;
        }

        .logout-link {
            margin-top: 18px !important;
            color: #ffb3b3 !important;
            background: rgba(220,53,69,0.08);
        }

        .logout-link i {
            color: #ffb3b3 !important;
        }

        .logout-link:hover {
            background: rgba(220,53,69,0.18) !important;
            color: #fff !important;
        }

        #content {
            width: 100%;
            min-width: 0;
            padding: 22px;
        }

        .top-navbar {
            border: 1px solid var(--border-soft);
            border-radius: 18px;
            background: rgba(255,255,255,0.94);
            box-shadow: 0 10px 30px rgba(23, 35, 50, 0.06);
            padding: 14px 18px;
            margin-bottom: 24px;
        }

        .welcome-box {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .welcome-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: var(--primary-soft);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }

        .welcome-title {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 3px;
        }

        .welcome-name {
            font-size: 15px;
            color: #243244;
            font-weight: 800;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-right: 8px;
            padding: 4px 9px;
            border-radius: 999px;
            background: #eef5ff;
            color: #0d6efd;
            font-size: 12px;
            font-weight: 700;
            vertical-align: middle;
        }

        .date-box {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 9px 13px;
            border-radius: 14px;
            background: #f8fafc;
            border: 1px solid #edf1f5;
            color: #5d6b7b;
            font-size: 13px;
            font-weight: 700;
        }

        .date-box i {
            color: #20c997;
        }

        .info-card {
            border: none;
            border-radius: 14px;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .info-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 35px rgba(0,0,0,0.08);
        }

        @media (max-width: 992px) {
            :root {
                --sidebar-width: 235px;
            }

            #content {
                padding: 16px;
            }

            #sidebar ul li a {
                font-size: 13px;
                padding: 10px 12px;
            }

            .top-navbar {
                border-radius: 14px;
            }
        }

        @media (max-width: 768px) {
            .wrapper {
                display: block;
            }

            #sidebar {
                position: relative;
                min-width: 100%;
                max-width: 100%;
                min-height: auto;
                box-shadow: none;
            }

            #sidebar ul.components {
                padding-bottom: 14px;
            }

            #content {
                padding: 14px;
            }

            .top-navbar .container-fluid {
                gap: 12px;
            }

            .date-box {
                width: 100%;
                justify-content: center;
            }

            .welcome-box {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">

        <nav id="sidebar">
            <div class="sidebar-header">
                <div class="brand-icon">
                    <i class="fas fa-hospital-user"></i>
                </div>

                <h4>سامانه تغذيه رادین</h4>

                <div class="user-chip">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($name); ?></span>
                </div>
            </div>

            <ul class="list-unstyled components">
                <li class="active">
                    <a href="../dashboard/index.php">
                        <i class="fas fa-home"></i>
                        <span>داشبورد</span>
                    </a>
                </li>

                <?php if($role == 'admin'): ?>
                    <li class="menu-section">مدیریت سامانه</li>

                    <li>
                        <a href="../admin/users.php">
                            <i class="fas fa-users"></i>
                            <span>مدیریت کاربران</span>
                        </a>
                    </li>

                    <li>
                        <a href="../admin/wards.php">
                            <i class="fas fa-hospital"></i>
                            <span>مدیریت بخش‌ها</span>
                        </a>
                    </li>

                    <li>
                        <a href="../admin/import_excel.php">
                            <i class="fas fa-file-import"></i>
                            <span>بارگذاری اطلاعات</span>
                        </a>
                    </li>

                <?php endif; ?>
				
				 <?php if($role == 'finance' || $role == 'admin' ): ?>
                    <li class="menu-section">مدیریت مالي</li>

                    <li>
                        <a href="../nutrition/financial_reports.php">
                            <i class="fas fa-chart-line"></i>
                            <span>گزارش مالی کلی</span>
                        </a>
                    </li>

                    <li>
                        <a href="../admin/doctor_meal_report.php">
                            <i class="fas fa-user-doctor"></i>
                            <span>گزارش مالی پزشکان</span>
                        </a>
                    </li>

                    <li>
                        <a href="../admin/financial_report_table.php">
                            <i class="fas fa-table"></i>
                            <span>گزارش مالی خروجی</span>
                        </a>
                    </li>
                <?php endif; ?>


                <?php if($role == 'nutrition_manager' || $role == 'admin'): ?>
                    <li class="menu-section">مدیریت تغذیه</li>

                    <li>
                        <a href="../nutrition/daily_stats.php">
                            <i class="fas fa-calendar-day"></i>
                            <span>آمار روزانه</span>
                        </a>
                    </li>

                    <li>
                        <a href="../nutrition/doctor_meal_entry.php">
                            <i class="fas fa-utensils"></i>
                            <span>آمار روزانه پزشکان</span>
                        </a>
                    </li>

                    <li>
                        <a href="../nutrition/meal_pricing.php">
                            <i class="fas fa-tags"></i>
                            <span>مصرف‌کنندگان و قیمت‌ها</span>
                        </a>
                    </li>

                    <li>
                        <a href="../nutrition/manage_doctors.php">
                            <i class="fas fa-user-md"></i>
                            <span>مدیریت پزشکان</span>
                        </a>
                    </li>

                    <li>
                        <a href="../nutrition/meal_settings.php">
                            <i class="fas fa-sliders-h"></i>
                            <span>تنظیم وعده‌ها</span>
                        </a>
                    </li>
                <?php endif; ?>


                <?php if($role == 'ward_secretary' || $role == 'admin'): ?>
                    <li class="menu-section">منشي بخش</li>

                    <li>
                        <a href="../ward/doctor_meal_request_entry.php">
                            <i class="fas fa-plus-circle"></i>
                            <span>ثبت درخواست جدید</span>
                        </a>
                    </li>
                <?php endif; ?>


                <?php if($role == 'matron' || $role == 'admin'): ?>
                    <li class="menu-section">تاییدات</li>

                    <li>
                        <a href="../ward/doctor_meal_requests_matron.php">
                            <i class="fas fa-check-double"></i>
                            <span>تاییدات مترون</span>
                        </a>
                    </li>
                <?php endif; ?>

                <li>
                    <a href="../auth/logout.php" class="logout-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>خروج</span>
                    </a>
                </li>
            </ul>
        </nav>


        <div id="content">
            <nav class="navbar navbar-expand-lg top-navbar">
                <div class="container-fluid px-0 d-flex justify-content-between align-items-center">

                    <div class="welcome-box">
                        <div class="welcome-icon">
                            <i class="fas fa-user"></i>
                        </div>

                        <div class="min-w-0">
                            <div class="welcome-title">خوش آمدید</div>

                            <div class="welcome-name">
                                <?php echo htmlspecialchars($name); ?>

                                <span class="role-badge">
                                    <i class="fas fa-id-badge"></i>
                                    <?php echo htmlspecialchars($role); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="date-box">
                        <i class="fas fa-calendar-alt"></i>
                        <span>
                            <?php
                            if (!function_exists('jdate')) {
                                include_once("../config/jdf.php");
                            }
                            echo jdate("Y/m/d");
                            ?>
                        </span>
                    </div>

                </div>
            </nav>
