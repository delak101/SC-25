<?php
/**
 * Hospital Pharmacy Page
 * Pharmacy management interface
 */

$page_title = 'إدارة الصيدلية';
require_once __DIR__ . '/includes/header.php';

// Require authentication and pharmacy role
requireAuth();
$current_user = getCurrentUser();

?>
