<?php
/**
 * Common Header
 * Includes navigation and user information
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';

$current_user = getCurrentUser();
$page_title = $page_title ?? 'Silent Connect';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitizeInput($page_title); ?> - <?php echo APP_NAME; ?></title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="/css/normalize.css">
    <link rel="stylesheet" href="/css/style.css">
    
    <style>
        .navbar-brand {
            font-weight: bold;
            color: #007bff !important;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
        }
        
        .alert {
            margin-bottom: 0;
            border-radius: 0;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            right: 0;
            height: 100vh;
            width: 250px;
            background: #fff;
            box-shadow: -2px 0 5px rgba(0,0,0,0.1);
            z-index: 1000;
            overflow-y: auto;
        }
        
        .main-content {
            margin-right: 250px;
            min-height: 100vh;
            background: #f8f9fa;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard.php">
                <i class="fas fa-stethoscope me-2"></i>
                <?php echo APP_NAME; ?>
            </a>
            
            <button class="navbar-toggler d-lg-none" type="button" onclick="toggleSidebar()">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <?php if ($current_user): ?>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle user-info" href="#" id="userDropdown" 
                       role="button" data-bs-toggle="dropdown">
                        <span><?php echo sanitizeInput($current_user['name']); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="/profile.php">
                            <i class="fas fa-user me-2"></i>الملف الشخصي
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>تسجيل الخروج
                        </a></li>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Alert Messages -->
    <?php 
    $success_message = getSuccessMessage();
    $error_message = getErrorMessage();
    ?>
    
    <?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo sanitizeInput($success_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?php echo sanitizeInput($error_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="d-flex">
        <!-- Sidebar -->
        <?php if ($current_user): ?>
        <div class="sidebar" id="sidebar">
            <div class="logo p-3 text-center border-bottom">
                <h5 id="silent"><?php echo APP_NAME; ?></h5>
                <img id="photo" src="/images/logo.png" alt="الشعار" class="img-fluid mt-2 rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
            </div>
            
            <div class="list p-3">
                <ul class="nav nav-pills flex-column">
                    <!-- Dashboard -->
                    <li class="nav-item mb-2">
                        <a href="/dashboard.php" class="nav-link">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            لوحة التحكم
                        </a>
                    </li>
                    
                    <!-- Users Management -->
                    <?php if (hasPermission('users', 'read')): ?>
                    <li class="nav-item mb-2">
                        <a class="nav-link d-flex justify-content-between align-items-center" 
                           data-bs-toggle="collapse" href="#usersMenu" role="button">
                            <span>
                                <i class="fas fa-users me-2"></i>
                                المستخدمون
                            </span>
                            <i class="fas fa-chevron-down"></i>
                        </a>
                        <div class="collapse" id="usersMenu">
                            <ul class="nav nav-pills flex-column ms-3">
                                <?php if (hasPermission('users', 'create')): ?>
                                <li class="nav-item">
                                    <a href="/register.php" class="nav-link">
                                        <i class="fas fa-user-plus me-2"></i>إضافة مستخدم
                                    </a>
                                </li>
                                <?php endif; ?>
                                <li class="nav-item">
                                    <a href="/admin/user_management.php" class="nav-link">
                                        <i class="fas fa-users me-2"></i>إظهار الكل
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Medical Services -->
                    <li class="nav-item mb-2">
                        <a class="nav-link d-flex justify-content-between align-items-center" 
                           data-bs-toggle="collapse" href="#medicalMenu" role="button">
                            <span>
                                <i class="fas fa-hospital me-2"></i>
                                الخدمات الطبية
                            </span>
                            <i class="fas fa-chevron-down"></i>
                        </a>
                        <div class="collapse" id="medicalMenu">
                            <ul class="nav nav-pills flex-column ms-3">
                                <!-- Clinics -->
                                <?php if (hasPermission('clinics', 'read')): ?>
                                <li class="nav-item">
                                    <a class="nav-link d-flex justify-content-between align-items-center" 
                                       data-bs-toggle="collapse" href="#clinicsMenu" role="button">
                                        <span>
                                            <i class="fas fa-clinic-medical me-2"></i>
                                            المستشفيات
                                        </span>
                                        <i class="fas fa-chevron-down"></i>
                                    </a>
                                    <div class="collapse" id="clinicsMenu">
                                        <ul class="nav nav-pills flex-column ms-3">
                                            <li class="nav-item">
                                                <a href="/clinics/doc.php" class="nav-link">دكتور</a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="/clinics/pat.php" class="nav-link">مريض</a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="/clinics/secr.php" class="nav-link">الإستقبال</a>
                                            </li>
                                        </ul>
                                    </div>
                                </li>
                                <?php endif; ?>
                                
                                <!-- Pharmacy -->
                                <?php if (hasPermission('pharmacy', 'read')): ?>
                                <li class="nav-item">
                                    <a href="/hospital/pharmacy.php" class="nav-link">
                                        <i class="fas fa-pills me-2"></i>الصيدلية
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>
                    
                    <!-- Language Keys -->
                    <li class="nav-item mb-2">
                        <a href="/keys.php" class="nav-link">
                            <i class="fas fa-language me-2"></i>
                            مفاتيح لغوية
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Main Content -->
        <div class="main-content flex-grow-1">
