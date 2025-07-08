-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 28, 2025 at 05:30 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `silent_connect`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `table_name`, `record_id`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 2, 'login', 'تسجيل دخول ناجح', '', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-27 18:33:30'),
(2, 2, 'logout', 'تسجيل خروج من النظام', '', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-27 18:34:15'),
(3, 2, 'login', 'تسجيل دخول ناجح', '', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-27 18:34:22'),
(4, 2, 'permission_assigned', 'تعيين صلاحية للدور', 'role_permissions', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-27 18:41:54'),
(5, 2, 'video_category_created', 'إنشاء فئة فيديو: test', 'video_categories', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-27 20:21:14'),
(6, 2, 'video_created', 'إنشاء فيديو: as', 'videos', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-27 20:23:06'),
(7, 2, 'login', 'تسجيل دخول ناجح', '', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', '2025-06-27 20:34:49'),
(8, 2, 'video_category_created', 'إنشاء فئة فيديو: khaled jaheen', 'video_categories', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-27 20:39:09'),
(9, 2, 'login', 'تسجيل دخول ناجح', '', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-27 21:37:28'),
(10, 2, 'video_category_updated', 'تحديث فئة فيديو', 'video_categories', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-27 21:59:49'),
(11, 2, 'video_category_deleted', 'حذف فئة فيديو', 'video_categories', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-27 22:00:03'),
(12, 2, 'video_updated', 'تحديث فيديو', 'videos', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-27 22:00:50');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `clinic_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('scheduled','confirmed','completed','cancelled','no_show') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinics`
--

CREATE TABLE `clinics` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `video_path` varchar(500) DEFAULT NULL,
  `status` enum('active','inactive','deleted') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinic_doctors`
--

CREATE TABLE `clinic_doctors` (
  `id` int(11) NOT NULL,
  `clinic_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `experience_years` int(11) DEFAULT 0,
  `education` text DEFAULT NULL,
  `certifications` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_schedules`
--

CREATE TABLE `doctor_schedules` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `day_of_week` tinyint(4) DEFAULT NULL,
  `specific_date` date DEFAULT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `clinic_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `email`, `ip_address`, `attempted_at`) VALUES
(2, 'khaledhesham007@gmail.com', '::1', '2025-06-27 21:36:20'),
(3, 'admin@silentconnect.com', '::1', '2025-06-27 21:36:51'),
(4, 'khaledhesham007@gmail.com', '::1', '2025-06-27 21:36:52'),
(5, 'khaledhesham007@gmail.com', '::1', '2025-06-27 21:36:53'),
(6, 'khaledhesham007@gmail.com', '::1', '2025-06-27 21:36:53'),
(7, 'khaledhesham007@gmail.com', '::1', '2025-06-27 21:36:54');

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `clinic_id` int(11) DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `prescription` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `medical_history` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `emergency_contact` varchar(255) DEFAULT NULL,
  `emergency_phone` varchar(20) DEFAULT NULL,
  `blood_type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `feature` varchar(100) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `feature`, `action`, `description`, `created_at`) VALUES
(1, 'users', 'create', 'إنشاء مستخدمين جدد', '2025-06-27 18:31:33'),
(2, 'users', 'read', 'عرض قائمة المستخدمين', '2025-06-27 18:31:33'),
(3, 'users', 'update', 'تعديل بيانات المستخدمين', '2025-06-27 18:31:33'),
(4, 'users', 'delete', 'حذف المستخدمين', '2025-06-27 18:31:33'),
(5, 'users', 'manage', 'إدارة شاملة للمستخدمين', '2025-06-27 18:31:33'),
(6, 'clinics', 'create', 'إنشاء عيادات جديدة', '2025-06-27 18:31:33'),
(7, 'clinics', 'read', 'عرض قائمة العيادات', '2025-06-27 18:31:33'),
(8, 'clinics', 'update', 'تعديل بيانات العيادات', '2025-06-27 18:31:33'),
(9, 'clinics', 'delete', 'حذف العيادات', '2025-06-27 18:31:33'),
(10, 'clinics', 'manage', 'إدارة شاملة للعيادات', '2025-06-27 18:31:33'),
(11, 'appointments', 'create', 'إنشاء مواعيد جديدة', '2025-06-27 18:31:33'),
(12, 'appointments', 'read', 'عرض قائمة المواعيد', '2025-06-27 18:31:33'),
(13, 'appointments', 'update', 'تعديل المواعيد', '2025-06-27 18:31:33'),
(14, 'appointments', 'delete', 'حذف المواعيد', '2025-06-27 18:31:33'),
(15, 'appointments', 'manage', 'إدارة شاملة للمواعيد', '2025-06-27 18:31:33'),
(16, 'videos', 'create', 'إنشاء فيديوهات جديدة', '2025-06-27 18:31:33'),
(17, 'videos', 'read', 'عرض قائمة الفيديوهات', '2025-06-27 18:31:33'),
(18, 'videos', 'update', 'تعديل الفيديوهات', '2025-06-27 18:31:33'),
(19, 'videos', 'delete', 'حذف الفيديوهات', '2025-06-27 18:31:33'),
(20, 'videos', 'manage', 'إدارة شاملة للفيديوهات', '2025-06-27 18:31:33'),
(21, 'patients', 'create', 'إنشاء ملفات مرضى جديدة', '2025-06-27 18:31:33'),
(22, 'patients', 'read', 'عرض قائمة المرضى', '2025-06-27 18:31:33'),
(23, 'patients', 'update', 'تعديل ملفات المرضى', '2025-06-27 18:31:33'),
(24, 'patients', 'delete', 'حذف ملفات المرضى', '2025-06-27 18:31:33'),
(25, 'patients', 'manage', 'إدارة شاملة للمرضى', '2025-06-27 18:31:33'),
(26, 'doctors', 'create', 'إنشاء ملفات أطباء جديدة', '2025-06-27 18:31:33'),
(27, 'doctors', 'read', 'عرض قائمة الأطباء', '2025-06-27 18:31:33'),
(28, 'doctors', 'update', 'تعديل ملفات الأطباء', '2025-06-27 18:31:33'),
(29, 'doctors', 'delete', 'حذف ملفات الأطباء', '2025-06-27 18:31:33'),
(30, 'doctors', 'manage', 'إدارة شاملة للأطباء', '2025-06-27 18:31:33'),
(31, 'pharmacy', 'create', 'إضافة أدوية ووصفات', '2025-06-27 18:31:33'),
(32, 'pharmacy', 'read', 'عرض قائمة الأدوية والوصفات', '2025-06-27 18:31:33'),
(33, 'pharmacy', 'update', 'تعديل الأدوية والوصفات', '2025-06-27 18:31:33'),
(34, 'pharmacy', 'delete', 'حذف الأدوية والوصفات', '2025-06-27 18:31:33'),
(35, 'pharmacy', 'manage', 'إدارة شاملة للصيدلية', '2025-06-27 18:31:33'),
(36, 'reception', 'create', 'إضافة إجراءات الاستقبال', '2025-06-27 18:31:33'),
(37, 'reception', 'read', 'عرض إجراءات الاستقبال', '2025-06-27 18:31:33'),
(38, 'reception', 'update', 'تعديل إجراءات الاستقبال', '2025-06-27 18:31:33'),
(39, 'reception', 'delete', 'حذف إجراءات الاستقبال', '2025-06-27 18:31:33'),
(40, 'reception', 'manage', 'إدارة شاملة للاستقبال', '2025-06-27 18:31:33'),
(41, 'medical_terms', 'create', 'إضافة مصطلحات طبية', '2025-06-27 18:31:33'),
(42, 'medical_terms', 'read', 'عرض المصطلحات الطبية', '2025-06-27 18:31:33'),
(43, 'medical_terms', 'update', 'تعديل المصطلحات الطبية', '2025-06-27 18:31:33'),
(44, 'medical_terms', 'delete', 'حذف المصطلحات الطبية', '2025-06-27 18:31:33'),
(45, 'medical_terms', 'manage', 'إدارة شاملة للمصطلحات الطبية', '2025-06-27 18:31:33'),
(46, 'reports', 'create', 'إنشاء تقارير', '2025-06-27 18:31:33'),
(47, 'reports', 'read', 'عرض التقارير', '2025-06-27 18:31:33'),
(48, 'reports', 'update', 'تعديل التقارير', '2025-06-27 18:31:33'),
(49, 'reports', 'delete', 'حذف التقارير', '2025-06-27 18:31:33'),
(50, 'reports', 'manage', 'إدارة شاملة للتقارير', '2025-06-27 18:31:33'),
(51, 'settings', 'read', 'عرض إعدادات النظام', '2025-06-27 18:31:33'),
(52, 'settings', 'update', 'تعديل إعدادات النظام', '2025-06-27 18:31:33'),
(53, 'settings', 'manage', 'إدارة شاملة للإعدادات', '2025-06-27 18:31:33'),
(54, 'rbac', 'create', 'إنشاء أدوار وصلاحيات', '2025-06-27 18:31:33'),
(55, 'rbac', 'read', 'عرض الأدوار والصلاحيات', '2025-06-27 18:31:33'),
(56, 'rbac', 'update', 'تعديل الأدوار والصلاحيات', '2025-06-27 18:31:33'),
(57, 'rbac', 'delete', 'حذف الأدوار والصلاحيات', '2025-06-27 18:31:33'),
(58, 'rbac', 'manage', 'إدارة شاملة للأدوار والصلاحيات', '2025-06-27 18:31:33');

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_role_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `display_name`, `description`, `parent_role_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'مدير النظام', 'صلاحية كاملة على النظام', NULL, 'active', '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(2, 'doctor', 'دكتور', 'إدارة المرضى والمواعيد', NULL, 'active', '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(3, 'patient', 'مريض', 'عرض المواعيد والملف الطبي', NULL, 'active', '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(4, 'secretary', 'سكرتارية', 'إدارة المواعيد والفيديوهات', NULL, 'active', '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(5, 'pharmacy', 'صيدلي', 'إدارة الأدوية والوصفات', NULL, 'active', '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(6, 'reception', 'استقبال', 'إدارة استقبال المرضى', NULL, 'active', '2025-06-27 18:31:33', '2025-06-27 18:31:33');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `granted` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`, `granted`, `created_at`, `updated_at`) VALUES
(1, 1, 11, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(2, 1, 12, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(3, 1, 13, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(4, 1, 14, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(5, 1, 15, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(6, 1, 6, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(7, 1, 7, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(8, 1, 8, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(9, 1, 9, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(10, 1, 10, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(11, 1, 26, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(12, 1, 27, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(13, 1, 28, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(14, 1, 29, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(15, 1, 30, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(16, 1, 41, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(17, 1, 42, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(18, 1, 43, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(19, 1, 44, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(20, 1, 45, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(21, 1, 21, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(22, 1, 22, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(23, 1, 23, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(24, 1, 24, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(25, 1, 25, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(26, 1, 31, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(27, 1, 32, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(28, 1, 33, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(29, 1, 34, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(30, 1, 35, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(31, 1, 54, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(32, 1, 55, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(33, 1, 56, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(34, 1, 57, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(35, 1, 58, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(36, 1, 36, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(37, 1, 37, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(38, 1, 38, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(39, 1, 39, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(40, 1, 40, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(41, 1, 46, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(42, 1, 47, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(43, 1, 48, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(44, 1, 49, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(45, 1, 50, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(46, 1, 51, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(47, 1, 52, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(48, 1, 53, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(49, 1, 1, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(50, 1, 2, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(51, 1, 3, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(52, 1, 4, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(53, 1, 5, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(54, 1, 16, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(55, 1, 17, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(56, 1, 18, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(57, 1, 19, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(58, 1, 20, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(64, 2, 11, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(65, 2, 12, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(66, 2, 13, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(67, 2, 41, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(68, 2, 42, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(69, 2, 43, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(70, 2, 21, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(71, 2, 22, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(72, 2, 23, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(73, 2, 16, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(74, 2, 17, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(75, 2, 18, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(79, 3, 12, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(80, 3, 42, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(81, 3, 17, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(82, 4, 11, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(83, 4, 12, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(84, 4, 13, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(85, 4, 6, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(86, 4, 7, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(87, 4, 8, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(88, 4, 21, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(89, 4, 22, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(90, 4, 23, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(91, 4, 16, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(92, 4, 17, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(93, 4, 18, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(97, 5, 41, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(98, 5, 42, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(99, 5, 43, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(100, 5, 31, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(101, 5, 32, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(102, 5, 33, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(103, 5, 16, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(104, 5, 17, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(105, 5, 18, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(112, 6, 11, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(113, 6, 12, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(114, 6, 13, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(115, 6, 21, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(116, 6, 22, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(117, 6, 23, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(118, 6, 36, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(119, 6, 37, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(120, 6, 38, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(121, 6, 16, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(122, 6, 17, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(123, 6, 18, 1, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(127, 2, 26, 1, '2025-06-27 18:41:54', '2025-06-27 18:41:54');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `national_id` varchar(20) DEFAULT NULL,
  `national_id_image` varchar(255) DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `hearing_status` enum('deaf','hard_of_hearing','hearing') NOT NULL,
  `marital_status` enum('single','married','divorced','widowed') DEFAULT NULL,
  `sign_language_level` enum('beginner','intermediate','advanced','none') DEFAULT NULL,
  `governorate` varchar(100) NOT NULL,
  `age` int(11) NOT NULL,
  `job` varchar(100) DEFAULT NULL,
  `service_card_image` varchar(255) DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `emergency_contact` varchar(255) DEFAULT NULL,
  `emergency_phone` varchar(20) DEFAULT NULL,
  `blood_type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL,
  `role` enum('admin','doctor','patient','secretary','pharmacy','reception') DEFAULT 'patient',
  `status` enum('active','inactive','deleted') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `national_id`, `national_id_image`, `gender`, `hearing_status`, `marital_status`, `sign_language_level`, `governorate`, `age`, `job`, `service_card_image`, `medical_history`, `allergies`, `emergency_contact`, `emergency_phone`, `blood_type`, `role`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'مدير النظام', 'admin@silentconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '', NULL, NULL, NULL, 'deaf', NULL, NULL, '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'admin', 'active', NULL, '2025-06-27 18:31:33', '2025-06-27 18:31:33'),
(2, 'test', 'Test12@gmail.com', '$2y$10$6cnj/GX0g6zzXMTf4Hl6AejKukhLZYjw4/CSTCizdP7582oc9awNm', '01030111111', '3030423120134', 'national_ids/685ee3e66d323.png', 'male', 'deaf', 'single', 'beginner', 'cairo', 10, '', 'service_cards/685ee3e66db04.jpg', '', '', '', '', '', 'admin', 'active', '2025-06-27 21:37:28', '2025-06-27 18:33:10', '2025-06-27 21:37:28');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`id`, `user_id`, `role_id`, `assigned_at`) VALUES
(1, 2, 3, '2025-06-27 18:33:10');

-- --------------------------------------------------------

--
-- Table structure for table `videos`
--

CREATE TABLE `videos` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `video_path` varchar(500) DEFAULT NULL,
  `category` int(11) DEFAULT NULL,
  `target_audience` enum('all','admin','doctor','patient','secretary','pharmacy','reception') DEFAULT 'all',
  `status` enum('active','inactive','deleted') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `videos`
--

INSERT INTO `videos` (`id`, `title`, `description`, `video_url`, `video_path`, `category`, `target_audience`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'as2', 'as', '', '/uploads/videos/as_1751055786.mp4', 1, 'all', 'active', 2, '2025-06-27 20:23:06', '2025-06-27 22:00:50');

-- --------------------------------------------------------

--
-- Table structure for table `video_categories`
--

CREATE TABLE `video_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'video',
  `color` varchar(30) DEFAULT 'primary',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `video_categories`
--

INSERT INTO `video_categories` (`id`, `name`, `slug`, `description`, `icon`, `color`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'test', 'test', 'test', 'heart', 'primary', 2, '2025-06-27 20:21:14', '2025-06-27 20:21:14');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_logs_user` (`user_id`),
  ADD KEY `idx_activity_logs_action` (`action`),
  ADD KEY `idx_activity_logs_table` (`table_name`),
  ADD KEY `idx_activity_logs_date` (`created_at`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_appointments_patient` (`patient_id`),
  ADD KEY `idx_appointments_doctor` (`doctor_id`),
  ADD KEY `idx_appointments_clinic` (`clinic_id`),
  ADD KEY `idx_appointments_date` (`appointment_date`),
  ADD KEY `idx_appointments_status` (`status`),
  ADD KEY `idx_appointments_datetime` (`appointment_date`,`appointment_time`);

--
-- Indexes for table `clinics`
--
ALTER TABLE `clinics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_clinics_name` (`name`),
  ADD KEY `idx_clinics_status` (`status`),
  ADD KEY `idx_clinics_created_by` (`created_by`);

--
-- Indexes for table `clinic_doctors`
--
ALTER TABLE `clinic_doctors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_clinic_doctor` (`clinic_id`,`doctor_id`),
  ADD KEY `idx_clinic_doctors_clinic` (`clinic_id`),
  ADD KEY `idx_clinic_doctors_doctor` (`doctor_id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_doctors_specialization` (`specialization`),
  ADD KEY `idx_doctors_license` (`license_number`);

--
-- Indexes for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `clinic_id` (`clinic_id`),
  ADD KEY `idx_doctor_schedules_doctor` (`doctor_id`),
  ADD KEY `idx_doctor_schedules_day` (`day_of_week`),
  ADD KEY `idx_doctor_schedules_date` (`specific_date`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_login_attempts_email` (`email`),
  ADD KEY `idx_login_attempts_ip` (`ip_address`),
  ADD KEY `idx_login_attempts_time` (`attempted_at`);

--
-- Indexes for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `clinic_id` (`clinic_id`),
  ADD KEY `idx_medical_records_patient` (`patient_id`),
  ADD KEY `idx_medical_records_doctor` (`doctor_id`),
  ADD KEY `idx_medical_records_date` (`created_at`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_password_resets_email` (`email`),
  ADD KEY `idx_password_resets_token` (`token`),
  ADD KEY `idx_password_resets_expires` (`expires_at`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_patients_blood_type` (`blood_type`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_permission` (`feature`,`action`),
  ADD KEY `idx_permissions_feature` (`feature`),
  ADD KEY `idx_permissions_action` (`action`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_remember_tokens_user` (`user_id`),
  ADD KEY `idx_remember_tokens_token` (`token`),
  ADD KEY `idx_remember_tokens_expires` (`expires_at`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `parent_role_id` (`parent_role_id`),
  ADD KEY `idx_roles_name` (`name`),
  ADD KEY `idx_roles_status` (`status`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_role_permission` (`role_id`,`permission_id`),
  ADD KEY `idx_role_permissions_role` (`role_id`),
  ADD KEY `idx_role_permissions_permission` (`permission_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `national_id` (`national_id`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_status` (`status`),
  ADD KEY `idx_users_hearing_status` (`hearing_status`),
  ADD KEY `idx_users_governorate` (`governorate`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_role` (`user_id`,`role_id`),
  ADD KEY `idx_user_roles_user` (`user_id`),
  ADD KEY `idx_user_roles_role` (`role_id`);

--
-- Indexes for table `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_videos_title` (`title`),
  ADD KEY `idx_videos_category` (`category`),
  ADD KEY `idx_videos_audience` (`target_audience`),
  ADD KEY `idx_videos_status` (`status`),
  ADD KEY `idx_videos_created_by` (`created_by`);

--
-- Indexes for table `video_categories`
--
ALTER TABLE `video_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_video_categories_name` (`name`),
  ADD KEY `idx_video_categories_slug` (`slug`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clinics`
--
ALTER TABLE `clinics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clinic_doctors`
--
ALTER TABLE `clinic_doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=128;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `video_categories`
--
ALTER TABLE `video_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `clinics`
--
ALTER TABLE `clinics`
  ADD CONSTRAINT `clinics_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `clinic_doctors`
--
ALTER TABLE `clinic_doctors`
  ADD CONSTRAINT `clinic_doctors_ibfk_1` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `clinic_doctors_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD CONSTRAINT `doctor_schedules_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctor_schedules_ibfk_2` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD CONSTRAINT `medical_records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medical_records_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medical_records_ibfk_3` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `roles`
--
ALTER TABLE `roles`
  ADD CONSTRAINT `roles_ibfk_1` FOREIGN KEY (`parent_role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `videos`
--
ALTER TABLE `videos`
  ADD CONSTRAINT `fk_videos_category` FOREIGN KEY (`category`) REFERENCES `video_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `videos_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `video_categories`
--
ALTER TABLE `video_categories`
  ADD CONSTRAINT `video_categories_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
