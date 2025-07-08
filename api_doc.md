# Silent Connect API Documentation

## Base URL
```
/api/v1
```

## Authentication

All endpoints except for authentication endpoints require a valid Bearer token in the Authorization header:
```
Authorization: Bearer <token>
```

### Authentication Endpoints

#### Login
- **URL**: `/auth/login`
- **Method**: `POST`
- **Request Body**:
  ```json
  {
    "email": "string (required)",
    "password": "string (required)",
    "remember": "boolean (optional, default: false)"
  }
  ```
- **Response**:
  ```json
  {
    "success": true,
    "message": "Login successful",
    "data": {
      "token": "string",
      "user": {
        "id": "number",
        "name": "string",
        "email": "string",
        "role": "string",
        "status": "string"
      },
      "expires_at": "datetime"
    }
  }
  ```

#### Register
- **URL**: `/auth/register`
- **Method**: `POST`
- **Request Body**:
  ```json
  {
    "name": "string (required)",
    "email": "string (required)",
    "password": "string (required, min: 8 chars)",
    "role": "string (optional, default: patient)",
    "phone": "string (optional)"
  }
  ```
- **Response**:
  ```json
  {
    "success": true,
    "message": "Registration successful",
    "data": {
      "user": {
        "id": "number",
        "name": "string",
        "email": "string",
        "role": "string"
      }
    }
  }
  ```

## Users

### Get Users List
- **URL**: `/users`
- **Method**: `GET`
- **Required Role**: `admin`, `secretary`
- **Query Parameters**:
  - `role`: string (optional) - Filter by role
  - `status`: string (optional) - Filter by status
  - `page`: number (optional, default: 1)
  - `limit`: number (optional, default: 10, max: 50)
- **Response**:
  ```json
  {
    "success": true,
    "data": {
      "users": [
        {
          "id": "number",
          "name": "string",
          "email": "string",
          "role": "string",
          "phone": "string",
          "status": "string",
          "created_at": "datetime",
          "last_login": "datetime"
        }
      ],
      "pagination": {
        "page": "number",
        "limit": "number",
        "total": "number",
        "pages": "number"
      }
    }
  }
  ```

### Get Single User
- **URL**: `/users/{id}`
- **Method**: `GET`
- **Required Role**: `admin`, `secretary`, or self
- **Response**:
  ```json
  {
    "success": true,
    "data": {
      "id": "number",
      "name": "string",
      "email": "string",
      "role": "string",
      "phone": "string",
      "status": "string",
      "created_at": "datetime",
      "last_login": "datetime",
      "profile": { } // Role-specific profile data
    }
  }
  ```

## Appointments

### Get Appointments List
- **URL**: `/appointments`
- **Method**: `GET`
- **Required Authentication**: Yes
- **Query Parameters**:
  - `status`: string (optional) - Filter by status
  - `date`: string (optional) - Filter by date (YYYY-MM-DD)
  - `clinic_id`: number (optional) - Filter by clinic
  - `page`: number (optional, default: 1)
  - `limit`: number (optional, default: 10, max: 50)
- **Response**:
  ```json
  {
    "success": true,
    "data": {
      "appointments": [
        {
          "id": "number",
          "clinic_name": "string",
          "clinic_location": "string",
          "doctor_name": "string",
          "doctor_phone": "string",
          "patient_name": "string",
          "patient_phone": "string",
          "appointment_date": "date",
          "appointment_time": "time",
          "status": "string",
          "notes": "string"
        }
      ],
      "pagination": {
        "page": "number",
        "limit": "number",
        "total": "number",
        "pages": "number"
      }
    }
  }
  ```

### Create Appointment
- **URL**: `/appointments`
- **Method**: `POST`
- **Required Authentication**: Yes
- **Request Body**:
  ```json
  {
    "clinic_id": "number (required)",
    "patient_id": "number (optional, defaults to authenticated user)",
    "appointment_date": "string (required, YYYY-MM-DD)",
    "appointment_time": "string (required, HH:MM)",
    "reason": "string (optional)",
    "notes": "string (optional)"
  }
  ```

## Clinics

### Get Clinics List
- **URL**: `/clinics`
- **Method**: `GET`
- **Required Authentication**: Yes
- **Query Parameters**:
  - `status`: string (optional)
  - `page`: number (optional, default: 1)
  - `limit`: number (optional, default: 10, max: 50)
- **Response**:
  ```json
  {
    "success": true,
    "data": {
      "clinics": [
        {
          "id": "number",
          "name": "string",
          "description": "string",
          "location": "string",
          "status": "string",
          "created_at": "datetime",
          "creator_name": "string"
        }
      ],
      "pagination": {
        "page": "number",
        "limit": "number",
        "total": "number",
        "pages": "number"
      }
    }
  }
  ```

### Get Single Clinic
- **URL**: `/clinics/{id}`
- **Method**: `GET`
- **Required Authentication**: Yes
- **Response**:
  ```json
  {
    "success": true,
    "data": {
      "id": "number",
      "name": "string",
      "description": "string",
      "location": "string",
      "status": "string",
      "doctors": [
        {
          "id": "number",
          "name": "string",
          "specialization": "string",
          "schedules": [
            {
              "day_of_week": "number",
              "start_time": "time",
              "end_time": "time"
            }
          ]
        }
      ],
      "recent_appointments": [] // Only for admin/doctor/secretary
    }
  }
  ```

## Pharmacy (Prescriptions)

### Get Prescriptions List
- **URL**: `/pharmacy`
- **Method**: `GET`
- **Required Role**: `admin`, `pharmacy`, `doctor`
- **Response**:
  ```json
  {
    "success": true,
    "data": [
      {
        "id": "number",
        "patient_name": "string",
        "doctor_name": "string",
        "prescription": "string",
        "notes": "string",
        "created_at": "datetime"
      }
    ]
  }
  ```

### Get Single Prescription
- **URL**: `/pharmacy/{id}`
- **Method**: `GET`
- **Required Role**: `admin`, `pharmacy`, `doctor`
- **Response**:
  ```json
  {
    "success": true,
    "data": {
      "id": "number",
      "patient_name": "string",
      "doctor_name": "string",
      "prescription": "string",
      "notes": "string",
      "created_at": "datetime"
    }
  }
  ```

### Create Prescription
- **URL**: `/pharmacy`
- **Method**: `POST`
- **Required Role**: `doctor`
- **Request Body**:
  ```json
  {
    "patient_id": "number (required)",
    "description": "string (required)",
    "notes": "string (optional)"
  }
  ```

## Videos

### Get Videos List
- **URL**: `/videos`
- **Method**: `GET`
- **Required Authentication**: Yes
- **Query Parameters**:
  - `category`: string (optional)
  - `target_audience`: string (optional)
  - `page`: number (optional, default: 1)
  - `limit`: number (optional, default: 10, max: 50)
- **Response**:
  ```json
  {
    "success": true,
    "data": {
      "videos": [
        {
          "id": "number",
          "title": "string",
          "description": "string",
          "video_url": "string",
          "category": "string",
          "target_audience": "string",
          "status": "string",
          "created_at": "datetime"
        }
      ],
      "pagination": {
        "page": "number",
        "limit": "number",
        "total": "number",
        "pages": "number"
      }
    }
  }
  ```

## Common Response Formats

### Success Response
```json
{
  "success": true,
  "message": "string (optional)",
  "data": "mixed",
  "timestamp": "datetime"
}
```

### Error Response
```json
{
  "success": false,
  "message": "string",
  "details": "mixed (optional)",
  "timestamp": "datetime"
}
```

## HTTP Status Codes

- `200`: Success
- `201`: Created
- `400`: Bad Request
- `401`: Unauthorized
- `403`: Forbidden
- `404`: Not Found
- `405`: Method Not Allowed
- `500`: Internal Server Error

## Roles and Permissions

Available roles:
- `admin`: Full system access
- `doctor`: Manage patients, appointments, prescriptions
- `patient`: View own appointments, medical records
- `secretary`: Manage appointments, videos
- `pharmacy`: Manage prescriptions
- `reception`: Manage patient reception

Each role has specific permissions defined in the RBAC system.
