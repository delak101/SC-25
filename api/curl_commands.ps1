# Silent Connect API v1.0 - cURL Commands
# =========================================
# Copy and paste these commands into your terminal to test the API
# Replace "YOUR_TOKEN_HERE" with actual authentication token

# Base URL
$BASE_URL = "http://localhost:5000/finalfinalfinal/api/v1"

# ===========================================
# 1. BASIC API ENDPOINTS
# ===========================================

# API Info
curl -X GET "$BASE_URL" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json"

# Health Check
curl -X GET "$BASE_URL/health" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json"

# API Documentation
curl -X GET "$BASE_URL/docs" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json"

# ===========================================
# 2. AUTHENTICATION
# ===========================================

# Login
curl -X POST "$BASE_URL/auth/login" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -d '{
    "email": "admin@example.com",
    "password": "password123",
    "remember": false
  }'

# Register (without file uploads - uses default logo.png, users can login immediately)
curl -X POST "$BASE_URL/auth/register" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "confirm_password": "password123",
    "phone": "01234567890",
    "national_id": "12345678901234",
    "gender": "male",
    "hearing_status": "deaf",
    "governorate": "cairo",
    "age": 25,
    "terms": true
  }'

# Register with file uploads (example - requires actual image files)
# curl -X POST "$BASE_URL/auth/register" `
#   -H "Accept: application/json" `
#   -F "name=Test User 2" `
#   -F "email=test2@example.com" `
#   -F "password=password123" `
#   -F "confirm_password=password123" `
#   -F "phone=01234567891" `
#   -F "national_id=12345678901235" `
#   -F "gender=female" `
#   -F "hearing_status=hard_of_hearing" `
#   -F "governorate=alexandria" `
#   -F "age=30" `
#   -F "terms=true" `
#   -F "national_id_image=@path/to/national_id.jpg" `
#   -F "service_card_image=@path/to/service_card.jpg"

# Get Profile (requires token)
curl -X GET "$BASE_URL/auth/profile" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Verify Token
curl -X GET "$BASE_URL/auth/verify" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Logout
curl -X POST "$BASE_URL/auth/logout" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# ===========================================
# 3. PHARMACY ENDPOINTS
# ===========================================

# Get All Prescriptions
curl -X GET "$BASE_URL/pharmacy" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Get Prescriptions with Pagination
curl -X GET "$BASE_URL/pharmacy?page=1&limit=10" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Get Prescription by ID
curl -X GET "$BASE_URL/pharmacy/1" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Create Prescription
curl -X POST "$BASE_URL/pharmacy" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -H "Authorization: Bearer YOUR_TOKEN_HERE" `
  -d '{
    "doctor_id": 1,
    "patient_id": 2,
    "description": "Take 1 tablet twice daily after meals",
    "notes": "Monitor for side effects"
  }'

# Update Prescription
curl -X PUT "$BASE_URL/pharmacy/1" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -H "Authorization: Bearer YOUR_TOKEN_HERE" `
  -d '{
    "description": "Updated prescription: Take 2 tablets three times daily",
    "notes": "Updated notes"
  }'

# Search Prescriptions
curl -X POST "$BASE_URL/pharmacy/search" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -H "Authorization: Bearer YOUR_TOKEN_HERE" `
  -d '{
    "doctor_id": 1,
    "date_from": "2025-01-01",
    "date_to": "2025-12-31"
  }'

# Delete Prescription
curl -X DELETE "$BASE_URL/pharmacy/1" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# ===========================================
# 4. USERS ENDPOINTS
# ===========================================

# Get All Users
curl -X GET "$BASE_URL/users" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Get Users with Filters
curl -X GET "$BASE_URL/users?role=doctor&status=active&page=1&limit=5" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Get User by ID
curl -X GET "$BASE_URL/users/1" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Search Users
curl -X POST "$BASE_URL/users/search" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -H "Authorization: Bearer YOUR_TOKEN_HERE" `
  -d '{
    "query": "admin",
    "role": "admin",
    "status": "active"
  }'

# ===========================================
# 5. VIDEOS ENDPOINTS
# ===========================================

# Get All Videos
curl -X GET "$BASE_URL/videos" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Get Videos with Filters
curl -X GET "$BASE_URL/videos?category=medical&target_audience=patient&page=1&limit=5" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Get Video by ID
curl -X GET "$BASE_URL/videos/1" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Create Video
curl -X POST "$BASE_URL/videos" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -H "Authorization: Bearer YOUR_TOKEN_HERE" `
  -d '{
    "title": "Medical Sign Language Tutorial",
    "description": "Learn basic medical terminology in sign language",
    "video_url": "https://example.com/video.mp4",
    "category": "education",
    "target_audience": "all"
  }'

# Search Videos
curl -X POST "$BASE_URL/videos/search" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -H "Authorization: Bearer YOUR_TOKEN_HERE" `
  -d '{
    "query": "sign language",
    "category": "education"
  }'

# ===========================================
# 6. CLINICS ENDPOINTS
# ===========================================

# Get All Clinics
curl -X GET "$BASE_URL/clinics" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Get Clinics with Pagination
curl -X GET "$BASE_URL/clinics?page=1&limit=10" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Get Clinic by ID
curl -X GET "$BASE_URL/clinics/1" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Create Clinic
curl -X POST "$BASE_URL/clinics" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -H "Authorization: Bearer YOUR_TOKEN_HERE" `
  -d '{
    "name": "Cardiology Clinic",
    "description": "Specialized heart care clinic",
    "status": "active"
  }'

# Search Clinics
curl -X POST "$BASE_URL/clinics/search" `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -H "Authorization: Bearer YOUR_TOKEN_HERE" `
  -d '{
    "query": "cardiology",
    "specialty": "cardiology"
  }'
