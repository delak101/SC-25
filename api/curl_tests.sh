#!/bin/bash

# Silent Connect API v1.0 - cURL Testing Script
# =============================================

# Base URL - Update this to match your server
BASE_URL="http://localhost:5000/finalfinalfinal/api/v1"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_header() {
    echo -e "${BLUE}===============================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}===============================================${NC}"
}

print_test() {
    echo -e "${YELLOW}$1${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

# Function to execute curl command and show response
test_curl() {
    local description="$1"
    local curl_command="$2"
    
    print_test "$description"
    echo "Command: $curl_command"
    echo "Response:"
    eval "$curl_command"
    echo -e "\n"
}

print_header "Silent Connect API v1.0 - cURL Tests"

# ===========================================
# 1. BASIC API ENDPOINTS
# ===========================================

print_header "1. BASIC API ENDPOINTS"

test_curl "API Info" \
"curl -X GET '$BASE_URL' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "Health Check" \
"curl -X GET '$BASE_URL/health' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "API Documentation" \
"curl -X GET '$BASE_URL/docs' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "Invalid Endpoint (Should return 404)" \
"curl -X GET '$BASE_URL/invalid' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -w '\nHTTP Status: %{http_code}\n'"

# ===========================================
# 2. AUTHENTICATION ENDPOINTS
# ===========================================

print_header "2. AUTHENTICATION ENDPOINTS"

test_curl "Login - Valid Credentials Test" \
"curl -X POST '$BASE_URL/auth/login' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{
    \"email\": \"admin@example.com\",
    \"password\": \"password123\",
    \"remember\": false
  }' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "Login - Missing Credentials (Should return 400)" \
"curl -X POST '$BASE_URL/auth/login' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{}' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "Login - Invalid JSON (Should return 400)" \
"curl -X POST '$BASE_URL/auth/login' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d 'invalid json' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "Register - Sample Registration (No file uploads, users can login immediately)" \
"curl -X POST '$BASE_URL/auth/register' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{
    \"name\": \"Test User\",
    \"email\": \"test@example.com\",
    \"password\": \"password123\",
    \"confirm_password\": \"password123\",
    \"phone\": \"01234567890\",
    \"national_id\": \"12345678901234\",
    \"gender\": \"male\",
    \"hearing_status\": \"deaf\",
    \"governorate\": \"cairo\",
    \"age\": 25,
    \"terms\": true
  }' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "Register - Missing Fields (Should return 422)" \
"curl -X POST '$BASE_URL/auth/register' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{
    \"name\": \"Test User\",
    \"email\": \"test@example.com\"
  }' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "Get Profile - Without Token (Should return 401)" \
"curl -X GET '$BASE_URL/auth/profile' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "Verify Token - Without Token (Should return 401)" \
"curl -X GET '$BASE_URL/auth/verify' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -w '\nHTTP Status: %{http_code}\n'"

# ===========================================
# 3. USERS ENDPOINTS
# ===========================================

print_header "3. USERS ENDPOINTS"

test_curl "Get Users - Without Token (Should return 401)" \
"curl -X GET '$BASE_URL/users' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "Get Users - With Pagination" \
"curl -X GET '$BASE_URL/users?page=1&limit=5' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "Get User by ID - Without Token (Should return 401)" \
"curl -X GET '$BASE_URL/users/1' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "Search Users - Sample Search" \
"curl -X POST '$BASE_URL/users/search' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE' \
  -d '{
    \"query\": \"admin\",
    \"role\": \"admin\",
    \"status\": \"active\"
  }' \
  -w '\nHTTP Status: %{http_code}\n'"

# ===========================================
# 4. PHARMACY ENDPOINTS
# ===========================================

print_header "4. PHARMACY ENDPOINTS"

test_curl "Get Prescriptions - Without Token (Should return 401)" \
"curl -X GET '$BASE_URL/pharmacy' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "Get Prescriptions - With Pagination" \
"curl -X GET '$BASE_URL/pharmacy?page=1&limit=10' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "Get Prescription by ID" \
"curl -X GET '$BASE_URL/pharmacy/1' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "Create Prescription" \
"curl -X POST '$BASE_URL/pharmacy' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE' \
  -d '{
    \"doctor_id\": 1,
    \"patient_id\": 2,
    \"description\": \"Take 1 tablet twice daily after meals\",
    \"notes\": \"Monitor for side effects\"
  }' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "Create Prescription - Missing Fields (Should return 422)" \
"curl -X POST '$BASE_URL/pharmacy' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE' \
  -d '{
    \"doctor_id\": 1
  }' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "Update Prescription" \
"curl -X PUT '$BASE_URL/pharmacy/1' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE' \
  -d '{
    \"description\": \"Updated prescription: Take 2 tablets three times daily\",
    \"notes\": \"Updated notes\"
  }' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "Search Prescriptions" \
"curl -X POST '$BASE_URL/pharmacy/search' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE' \
  -d '{
    \"doctor_id\": 1,
    \"date_from\": \"2025-01-01\",
    \"date_to\": \"2025-12-31\"
  }' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "Delete Prescription" \
"curl -X DELETE '$BASE_URL/pharmacy/1' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE' \
  -w '\nHTTP Status: %{http_code}\n'"

# ===========================================
# 5. VIDEOS ENDPOINTS
# ===========================================

print_header "5. VIDEOS ENDPOINTS"

test_curl "Get Videos - Without Token (Should return 401)" \
"curl -X GET '$BASE_URL/videos' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "Get Videos - With Filters" \
"curl -X GET '$BASE_URL/videos?category=medical&target_audience=patient&page=1&limit=5' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "Get Video by ID" \
"curl -X GET '$BASE_URL/videos/1' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "Create Video" \
"curl -X POST '$BASE_URL/videos' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE' \
  -d '{
    \"title\": \"Medical Sign Language Tutorial\",
    \"description\": \"Learn basic medical terminology in sign language\",
    \"video_url\": \"https://example.com/video.mp4\",
    \"category\": \"education\",
    \"target_audience\": \"all\"
  }' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "Search Videos" \
"curl -X POST '$BASE_URL/videos/search' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE' \
  -d '{
    \"query\": \"sign language\",
    \"category\": \"education\"
  }' \
  -w '\nHTTP Status: %{http_code}\n'"

# ===========================================
# 6. CLINICS ENDPOINTS
# ===========================================

print_header "6. CLINICS ENDPOINTS"

test_curl "Get Clinics - Without Token (Should return 401)" \
"curl -X GET '$BASE_URL/clinics' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "Get Clinics - With Pagination" \
"curl -X GET '$BASE_URL/clinics?page=1&limit=10' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "Get Clinic by ID" \
"curl -X GET '$BASE_URL/clinics/1' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "Create Clinic" \
"curl -X POST '$BASE_URL/clinics' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE' \
  -d '{
    \"name\": \"Cardiology Clinic\",
    \"description\": \"Specialized heart care clinic\",
    \"status\": \"active\"
  }' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "Search Clinics" \
"curl -X POST '$BASE_URL/clinics/search' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE' \
  -d '{
    \"query\": \"cardiology\",
    \"specialty\": \"cardiology\"
  }' \
  -w '\nHTTP Status: %{http_code}\n'"

# ===========================================
# 7. ERROR TESTING
# ===========================================

print_header "7. ERROR TESTING"

test_curl "Method Not Allowed (Should return 405)" \
"curl -X PATCH '$BASE_URL/auth/login' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "Invalid JSON Content-Type" \
"curl -X POST '$BASE_URL/auth/login' \
  -H 'Accept: application/json' \
  -H 'Content-Type: text/plain' \
  -d 'invalid data' \
  -w '\nHTTP Status: %{http_code}\n'"

test_curl "OPTIONS Request (CORS Preflight)" \
"curl -X OPTIONS '$BASE_URL/auth/login' \
  -H 'Access-Control-Request-Method: POST' \
  -H 'Access-Control-Request-Headers: Content-Type,Authorization' \
  -w '\nHTTP Status: %{http_code}\n'"

print_header "cURL Testing Complete!"
echo "Note: Replace 'YOUR_TOKEN_HERE' with an actual authentication token"
echo "obtained from the login endpoint for protected endpoints to work."
