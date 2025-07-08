<?php
// Test registration without file uploads
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Database.php';

// Test data
$testData = [
    'name' => 'Test User Registration',
    'email' => 'test_registration_' . time() . '@example.com',
    'password' => 'password123',
    'confirm_password' => 'password123',
    'phone' => '01234567890',
    'national_id' => '1234567890123' . rand(0, 9),
    'gender' => 'male',
    'hearing_status' => 'deaf',
    'governorate' => 'cairo',
    'age' => 25,
    'terms' => true
];

// Simulate POST request
$_POST = $testData;
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Capture output
ob_start();
include __DIR__ . '/v1/auth.php';
$output = ob_get_clean();

echo "Registration Test Results:\n";
echo "==========================\n";
echo "Test Data: " . json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";
echo "API Response:\n";
echo $output . "\n";

// Parse response to check if successful
$response = json_decode($output, true);
if ($response && isset($response['success'])) {
    if ($response['success']) {
        echo "\n✅ SUCCESS: Registration completed without file uploads!\n";
        echo "Default images used: logo.png\n";
    } else {
        echo "\n❌ FAILED: " . ($response['message'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "\n❌ FAILED: Invalid response format\n";
}
?>
