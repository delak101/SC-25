# API JSON Response Test

This file demonstrates the correct JSON response format for all API endpoints.

## Authentication API (`/api/v1/auth`)

### Login
```bash
curl -X POST http://localhost/finalfinal/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123",
    "remember": true
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "Ahmed Mohamed",
      "email": "user@example.com",
      "role": "patient"
    },
    "token": "abc123...",
    "expires_at": "2025-07-05 12:00:00"
  },
  "timestamp": "2025-07-04 12:00:00"
}
```

### Register
```bash
curl -X POST http://localhost/finalfinal/api/v1/auth/register \
  -F "name=أحمد محمد" \
  -F "email=ahmed@example.com" \
  -F "password=MyPassword123" \
  -F "confirm_password=MyPassword123" \
  -F "phone=01012345678" \
  -F "national_id=12345678901234" \
  -F "gender=male" \
  -F "hearing_status=deaf" \
  -F "governorate=cairo" \
  -F "age=25" \
  -F "terms=true" \
  -F "national_id_image=@/path/to/id.jpg"
```

**Response:**
```json
{
  "success": true,
  "message": "Registration successful. Account pending approval.",
  "data": {
    "user": {
      "id": 123,
      "name": "أحمد محمد",
      "email": "ahmed@example.com",
      "role": "patient",
      "status": "pending"
    },
    "verification_required": true,
    "verification_token": "xyz789..."
  },
  "timestamp": "2025-07-04 12:00:00"
}
```

## Users API (`/api/v1/users`)

### Get All Users
```bash
curl -X GET http://localhost/finalfinal/api/v1/users \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "users": [
      {
        "id": 1,
        "name": "Ahmed Mohamed",
        "email": "ahmed@example.com",
        "role": "patient",
        "status": "active"
      }
    ],
    "total": 1,
    "page": 1,
    "limit": 10
  },
  "timestamp": "2025-07-04 12:00:00"
}
```

## Clinics API (`/api/v1/clinics`)

### Get All Clinics
```bash
curl -X GET http://localhost/finalfinal/api/v1/clinics \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "message": "Success",
  "data": [
    {
      "id": 1,
      "name": "عيادة القلب",
      "description": "عيادة متخصصة في أمراض القلب",
      "specialization": "cardiology",
      "location": "الطابق الأول",
      "created_at": "2025-07-04 12:00:00"
    }
  ],
  "timestamp": "2025-07-04 12:00:00"
}
```

## Videos API (`/api/v1/videos`)

### Get All Videos
```bash
curl -X GET http://localhost/finalfinal/api/v1/videos \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "message": "Success",
  "data": [
    {
      "id": 1,
      "title": "فيديو تعليمي",
      "description": "فيديو لتعليم لغة الإشارة",
      "file_path": "uploads/videos/video1.mp4",
      "clinic_id": 1,
      "view_count": 150,
      "created_at": "2025-07-04 12:00:00"
    }
  ],
  "timestamp": "2025-07-04 12:00:00"
}
```

## Appointments API (`/api/v1/appointments`)

### Get All Appointments
```bash
curl -X GET http://localhost/finalfinal/api/v1/appointments \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "message": "Success",
  "data": [
    {
      "id": 1,
      "doctor_name": "د. أحمد محمد",
      "patient_name": "محمد علي",
      "clinic_name": "عيادة القلب",
      "appointment_date": "2025-07-05",
      "appointment_time": "10:00:00",
      "status": "confirmed"
    }
  ],
  "timestamp": "2025-07-04 12:00:00"
}
```

## Pharmacy API (`/api/v1/pharmacy`)

### Get All Prescriptions
```bash
curl -X GET http://localhost/finalfinal/api/v1/pharmacy \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "success": true,
  "message": "Success",
  "data": [
    {
      "id": 1,
      "doctor_name": "د. أحمد محمد",
      "patient_name": "محمد علي",
      "prescription": "دواء للضغط مرتين يومياً",
      "notes": "مع الطعام",
      "created_at": "2025-07-04 12:00:00"
    }
  ],
  "timestamp": "2025-07-04 12:00:00"
}
```

## Error Responses

All APIs return consistent error responses:

```json
{
  "success": false,
  "message": "Error description",
  "details": null,
  "timestamp": "2025-07-04 12:00:00"
}
```

### Common HTTP Status Codes:
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `405` - Method Not Allowed
- `409` - Conflict
- `500` - Internal Server Error

## Headers

All API responses include:
- `Content-Type: application/json`
- `Access-Control-Allow-Origin: *`
- `Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS`
- `Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With`
