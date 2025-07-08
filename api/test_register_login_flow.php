<?php
// Test complete registration and login flow
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Database.php';

echo "Testing Complete Registration + Login Flow\n";
echo "==========================================\n\n";

$uniqueId = time();
$testData = [
    'name' => 'Test User ' . $uniqueId,
    'email' => 'testflow_' . $uniqueId . '@example.com',
    'password' => 'password123',
    'confirm_password' => 'password123',
    'phone' => '01234567890',
    'national_id' => '1234567890' . sprintf('%04d', $uniqueId % 10000),
    'gender' => 'male',
    'hearing_status' => 'deaf',
    'governorate' => 'cairo',
    'age' => 25,
    'terms' => true
];

echo "1. Testing Registration...\n";
echo "Test Data: " . json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

// Test Registration
$_POST = $testData;
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';
$_SERVER['REQUEST_URI'] = '/api/v1/auth/register';

ob_start();
$path = '/auth/register';
include __DIR__ . '/v1/auth.php';
$registerOutput = ob_get_clean();

echo "Registration Response:\n";
echo $registerOutput . "\n\n";

$registerResponse = json_decode($registerOutput, true);
if ($registerResponse && isset($registerResponse['success']) && $registerResponse['success']) {
    echo "✅ Registration Successful!\n";
    echo "User Status: " . $registerResponse['data']['user']['status'] . "\n\n";
    
    // Now test login with the same credentials
    echo "2. Testing Login with same credentials...\n";
    
    $loginData = [
        'email' => $testData['email'],
        'password' => $testData['password'],
        'remember' => false
    ];
    
    $_POST = $loginData;
    $_SERVER['REQUEST_URI'] = '/api/v1/auth/login';
    
    ob_start();
    $path = '/auth/login';
    include __DIR__ . '/v1/auth.php';
    $loginOutput = ob_get_clean();
    
    echo "Login Response:\n";
    echo $loginOutput . "\n\n";
    
    $loginResponse = json_decode($loginOutput, true);
    if ($loginResponse && isset($loginResponse['success']) && $loginResponse['success']) {
        echo "✅ Login Successful!\n";
        echo "Token received: " . substr($loginResponse['data']['token'], 0, 16) . "...\n";
        echo "User can now access protected endpoints.\n\n";
        echo "🎉 COMPLETE FLOW TEST PASSED! 🎉\n";
        echo "Users can register and login immediately without verification.\n";
    } else {
        echo "❌ Login Failed: " . ($loginResponse['message'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "❌ Registration Failed: " . ($registerResponse['message'] ?? 'Unknown error') . "\n";
}
?>
