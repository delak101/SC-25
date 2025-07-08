<?php
/**
 * Simple API Test Script
 * Test the standardized API endpoints
 */

// Base URL for the API
$baseUrl = 'http://localhost:5000/finalfinalfinal/api/v1';

// Test function to make HTTP requests
function testRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    $defaultHeaders = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    $allHeaders = array_merge($defaultHeaders, $headers);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'response' => json_decode($response, true),
        'raw' => $response
    ];
}

echo "Testing Silent Connect API v1.0\n";
echo "================================\n\n";

// Test 1: API Info
echo "1. Testing API Info endpoint...\n";
$result = testRequest($baseUrl);
echo "Status: " . $result['code'] . "\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

// Test 2: Health Check
echo "2. Testing Health Check endpoint...\n";
$result = testRequest($baseUrl . '/health');
echo "Status: " . $result['code'] . "\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

// Test 3: Documentation
echo "3. Testing Documentation endpoint...\n";
$result = testRequest($baseUrl . '/docs');
echo "Status: " . $result['code'] . "\n";
if ($result['response']) {
    echo "Documentation available: " . (isset($result['response']['data']) ? 'Yes' : 'No') . "\n";
} else {
    echo "Response: " . $result['raw'] . "\n";
}
echo "\n";

// Test 4: Invalid endpoint
echo "4. Testing invalid endpoint...\n";
$result = testRequest($baseUrl . '/invalid');
echo "Status: " . $result['code'] . "\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

// Test 5: Auth endpoint without credentials
echo "5. Testing protected endpoint without auth...\n";
$result = testRequest($baseUrl . '/users');
echo "Status: " . $result['code'] . "\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

// Test 6: Pharmacy endpoint without auth
echo "6. Testing pharmacy endpoint without auth...\n";
$result = testRequest($baseUrl . '/pharmacy');
echo "Status: " . $result['code'] . "\n";
echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n\n";

echo "API Testing Complete!\n";
echo "=====================\n";
echo "All endpoints should return proper JSON responses with consistent format.\n";
?>
