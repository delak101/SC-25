-- Silent Connect Medical Management System
-- SQLite Database Schema

-- Enable foreign key constraints
PRAGMA foreign_keys = ON;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    phone TEXT,
    role TEXT CHECK(role IN ('admin', 'doctor', 'patient', 'secretary', 'pharmacy', 'reception')) DEFAULT 'patient',
    status TEXT CHECK(status IN ('active', 'inactive', 'deleted')) DEFAULT 'active',
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_users_status ON users(status);

-- Trigger to update updated_at for users
CREATE TRIGGER IF NOT EXISTS update_users_timestamp 
    AFTER UPDATE ON users
    BEGIN
        UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
    END;

-- Roles table for RBAC
CREATE TABLE IF NOT EXISTS roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE NOT NULL,
    display_name TEXT NOT NULL,
    description TEXT,
    parent_role_id INTEGER,
    status TEXT CHECK(status IN ('active', 'inactive')) DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_role_id) REFERENCES roles(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_roles_name ON roles(name);
CREATE INDEX IF NOT EXISTS idx_roles_status ON roles(status);

-- Trigger to update updated_at for roles
CREATE TRIGGER IF NOT EXISTS update_roles_timestamp 
    AFTER UPDATE ON roles
    BEGIN
        UPDATE roles SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
    END;

-- Permissions table for RBAC
CREATE TABLE IF NOT EXISTS permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    feature TEXT NOT NULL,
    action TEXT NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(feature, action)
);

CREATE INDEX IF NOT EXISTS idx_permissions_feature ON permissions(feature);
CREATE INDEX IF NOT EXISTS idx_permissions_action ON permissions(action);

-- Role permissions mapping
CREATE TABLE IF NOT EXISTS role_permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    role_id INTEGER NOT NULL,
    permission_id INTEGER NOT NULL,
    granted INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE(role_id, permission_id)
);

CREATE INDEX IF NOT EXISTS idx_role_permissions_role ON role_permissions(role_id);
CREATE INDEX IF NOT EXISTS idx_role_permissions_permission ON role_permissions(permission_id);

-- Trigger to update updated_at for role_permissions
CREATE TRIGGER IF NOT EXISTS update_role_permissions_timestamp 
    AFTER UPDATE ON role_permissions
    BEGIN
        UPDATE role_permissions SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
    END;

-- User roles mapping
CREATE TABLE IF NOT EXISTS user_roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    role_id INTEGER NOT NULL,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    UNIQUE(user_id, role_id)
);

CREATE INDEX IF NOT EXISTS idx_user_roles_user ON user_roles(user_id);
CREATE INDEX IF NOT EXISTS idx_user_roles_role ON user_roles(role_id);

-- Patients table
CREATE TABLE IF NOT EXISTS patients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER UNIQUE NOT NULL,
    medical_history TEXT,
    allergies TEXT,
    emergency_contact TEXT,
    emergency_phone TEXT,
    blood_type TEXT CHECK(blood_type IN ('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_patients_blood_type ON patients(blood_type);

-- Trigger to update updated_at for patients
CREATE TRIGGER IF NOT EXISTS update_patients_timestamp 
    AFTER UPDATE ON patients
    BEGIN
        UPDATE patients SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
    END;

-- Doctors table
CREATE TABLE IF NOT EXISTS doctors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER UNIQUE NOT NULL,
    specialization TEXT,
    license_number TEXT,
    experience_years INTEGER DEFAULT 0,
    education TEXT,
    certifications TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_doctors_specialization ON doctors(specialization);
CREATE INDEX IF NOT EXISTS idx_doctors_license ON doctors(license_number);

-- Trigger to update updated_at for doctors
CREATE TRIGGER IF NOT EXISTS update_doctors_timestamp 
    AFTER UPDATE ON doctors
    BEGIN
        UPDATE doctors SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
    END;

-- Clinics table
CREATE TABLE IF NOT EXISTS clinics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    video_url TEXT,
    video_path TEXT,
    status TEXT CHECK(status IN ('active', 'inactive', 'deleted')) DEFAULT 'active',
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_clinics_name ON clinics(name);
CREATE INDEX IF NOT EXISTS idx_clinics_status ON clinics(status);
CREATE INDEX IF NOT EXISTS idx_clinics_created_by ON clinics(created_by);

-- Trigger to update updated_at for clinics
CREATE TRIGGER IF NOT EXISTS update_clinics_timestamp 
    AFTER UPDATE ON clinics
    BEGIN
        UPDATE clinics SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
    END;

-- Clinic doctors mapping
CREATE TABLE IF NOT EXISTS clinic_doctors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    clinic_id INTEGER NOT NULL,
    doctor_id INTEGER NOT NULL,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(clinic_id, doctor_id)
);

CREATE INDEX IF NOT EXISTS idx_clinic_doctors_clinic ON clinic_doctors(clinic_id);
CREATE INDEX IF NOT EXISTS idx_clinic_doctors_doctor ON clinic_doctors(doctor_id);

-- Appointments table
CREATE TABLE IF NOT EXISTS appointments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    patient_id INTEGER NOT NULL,
    doctor_id INTEGER NOT NULL,
    clinic_id INTEGER NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status TEXT CHECK(status IN ('scheduled', 'confirmed', 'completed', 'cancelled', 'no_show')) DEFAULT 'scheduled',
    notes TEXT,
    cancellation_reason TEXT,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_appointments_patient ON appointments(patient_id);
CREATE INDEX IF NOT EXISTS idx_appointments_doctor ON appointments(doctor_id);
CREATE INDEX IF NOT EXISTS idx_appointments_clinic ON appointments(clinic_id);
CREATE INDEX IF NOT EXISTS idx_appointments_date ON appointments(appointment_date);
CREATE INDEX IF NOT EXISTS idx_appointments_status ON appointments(status);
CREATE INDEX IF NOT EXISTS idx_appointments_datetime ON appointments(appointment_date, appointment_time);

-- Trigger to update updated_at for appointments
CREATE TRIGGER IF NOT EXISTS update_appointments_timestamp 
    AFTER UPDATE ON appointments
    BEGIN
        UPDATE appointments SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
    END;

-- Doctor schedules table
CREATE TABLE IF NOT EXISTS doctor_schedules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    doctor_id INTEGER NOT NULL,
    day_of_week INTEGER, -- 0=Sunday, 1=Monday, ..., 6=Saturday
    specific_date DATE,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    clinic_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_doctor_schedules_doctor ON doctor_schedules(doctor_id);
CREATE INDEX IF NOT EXISTS idx_doctor_schedules_day ON doctor_schedules(day_of_week);
CREATE INDEX IF NOT EXISTS idx_doctor_schedules_date ON doctor_schedules(specific_date);

-- Medical records table
CREATE TABLE IF NOT EXISTS medical_records (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    patient_id INTEGER NOT NULL,
    doctor_id INTEGER NOT NULL,
    clinic_id INTEGER,
    diagnosis TEXT,
    treatment TEXT,
    prescription TEXT,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_medical_records_patient ON medical_records(patient_id);
CREATE INDEX IF NOT EXISTS idx_medical_records_doctor ON medical_records(doctor_id);
CREATE INDEX IF NOT EXISTS idx_medical_records_date ON medical_records(created_at);

-- Trigger to update updated_at for medical_records
CREATE TRIGGER IF NOT EXISTS update_medical_records_timestamp 
    AFTER UPDATE ON medical_records
    BEGIN
        UPDATE medical_records SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
    END;

-- Videos table
CREATE TABLE IF NOT EXISTS videos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    video_url TEXT,
    video_path TEXT,
    category TEXT DEFAULT 'general',
    target_audience TEXT CHECK(target_audience IN ('all', 'admin', 'doctor', 'patient', 'secretary', 'pharmacy', 'reception')) DEFAULT 'all',
    status TEXT CHECK(status IN ('active', 'inactive', 'deleted')) DEFAULT 'active',
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_videos_title ON videos(title);
CREATE INDEX IF NOT EXISTS idx_videos_category ON videos(category);
CREATE INDEX IF NOT EXISTS idx_videos_audience ON videos(target_audience);
CREATE INDEX IF NOT EXISTS idx_videos_status ON videos(status);
CREATE INDEX IF NOT EXISTS idx_videos_created_by ON videos(created_by);

-- Trigger to update updated_at for videos
CREATE TRIGGER IF NOT EXISTS update_videos_timestamp 
    AFTER UPDATE ON videos
    BEGIN
        UPDATE videos SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
    END;

-- Login attempts table for security
CREATE TABLE IF NOT EXISTS login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL,
    ip_address TEXT NOT NULL,
    attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_login_attempts_email ON login_attempts(email);
CREATE INDEX IF NOT EXISTS idx_login_attempts_ip ON login_attempts(ip_address);
CREATE INDEX IF NOT EXISTS idx_login_attempts_time ON login_attempts(attempted_at);

-- Remember tokens table
CREATE TABLE IF NOT EXISTS remember_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token TEXT UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_remember_tokens_user ON remember_tokens(user_id);
CREATE INDEX IF NOT EXISTS idx_remember_tokens_token ON remember_tokens(token);
CREATE INDEX IF NOT EXISTS idx_remember_tokens_expires ON remember_tokens(expires_at);

-- Password resets table
CREATE TABLE IF NOT EXISTS password_resets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL,
    token TEXT NOT NULL,
    expires_at DATETIME NOT NULL,
    used INTEGER DEFAULT 0,
    used_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_password_resets_email ON password_resets(email);
CREATE INDEX IF NOT EXISTS idx_password_resets_token ON password_resets(token);
CREATE INDEX IF NOT EXISTS idx_password_resets_expires ON password_resets(expires_at);

-- Activity logs table for audit trail
CREATE TABLE IF NOT EXISTS activity_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action TEXT NOT NULL,
    details TEXT,
    table_name TEXT,
    record_id INTEGER,
    ip_address TEXT,
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_activity_logs_user ON activity_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_activity_logs_action ON activity_logs(action);
CREATE INDEX IF NOT EXISTS idx_activity_logs_table ON activity_logs(table_name);
CREATE INDEX IF NOT EXISTS idx_activity_logs_date ON activity_logs(created_at);

-- Insert default admin user (password: Admin123!)
INSERT OR IGNORE INTO users (name, email, password, role, status) 
VALUES ('مدير النظام', 'admin@silentconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Insert default roles
INSERT OR IGNORE INTO roles (name, display_name, description) VALUES
('admin', 'مدير النظام', 'صلاحية كاملة على النظام'),
('doctor', 'دكتور', 'إدارة المرضى والمواعيد'),
('patient', 'مريض', 'عرض المواعيد والملف الطبي'),
('secretary', 'سكرتارية', 'إدارة المواعيد والفيديوهات'),
('pharmacy', 'صيدلي', 'إدارة الأدوية والوصفات'),
('reception', 'استقبال', 'إدارة استقبال المرضى');

-- Insert default permissions
INSERT OR IGNORE INTO permissions (feature, action, description) VALUES
-- Users management
('users', 'create', 'إنشاء مستخدمين جدد'),
('users', 'read', 'عرض قائمة المستخدمين'),
('users', 'update', 'تعديل بيانات المستخدمين'),
('users', 'delete', 'حذف المستخدمين'),
('users', 'manage', 'إدارة شاملة للمستخدمين'),

-- Clinics management
('clinics', 'create', 'إنشاء عيادات جديدة'),
('clinics', 'read', 'عرض قائمة العيادات'),
('clinics', 'update', 'تعديل بيانات العيادات'),
('clinics', 'delete', 'حذف العيادات'),
('clinics', 'manage', 'إدارة شاملة للعيادات'),

-- Appointments management
('appointments', 'create', 'إنشاء مواعيد جديدة'),
('appointments', 'read', 'عرض قائمة المواعيد'),
('appointments', 'update', 'تعديل المواعيد'),
('appointments', 'delete', 'حذف المواعيد'),
('appointments', 'manage', 'إدارة شاملة للمواعيد'),

-- Videos management
('videos', 'create', 'إنشاء فيديوهات جديدة'),
('videos', 'read', 'عرض قائمة الفيديوهات'),
('videos', 'update', 'تعديل الفيديوهات'),
('videos', 'delete', 'حذف الفيديوهات'),
('videos', 'manage', 'إدارة شاملة للفيديوهات'),

-- Patients management
('patients', 'create', 'إنشاء ملفات مرضى جديدة'),
('patients', 'read', 'عرض قائمة المرضى'),
('patients', 'update', 'تعديل ملفات المرضى'),
('patients', 'delete', 'حذف ملفات المرضى'),
('patients', 'manage', 'إدارة شاملة للمرضى'),

-- Doctors management
('doctors', 'create', 'إنشاء ملفات أطباء جديدة'),
('doctors', 'read', 'عرض قائمة الأطباء'),
('doctors', 'update', 'تعديل ملفات الأطباء'),
('doctors', 'delete', 'حذف ملفات الأطباء'),
('doctors', 'manage', 'إدارة شاملة للأطباء'),

-- Pharmacy management
('pharmacy', 'create', 'إضافة أدوية ووصفات'),
('pharmacy', 'read', 'عرض قائمة الأدوية والوصفات'),
('pharmacy', 'update', 'تعديل الأدوية والوصفات'),
('pharmacy', 'delete', 'حذف الأدوية والوصفات'),
('pharmacy', 'manage', 'إدارة شاملة للصيدلية'),

-- Reception management
('reception', 'create', 'إضافة إجراءات الاستقبال'),
('reception', 'read', 'عرض إجراءات الاستقبال'),
('reception', 'update', 'تعديل إجراءات الاستقبال'),
('reception', 'delete', 'حذف إجراءات الاستقبال'),
('reception', 'manage', 'إدارة شاملة للاستقبال'),

-- Medical terms
('medical_terms', 'create', 'إضافة مصطلحات طبية'),
('medical_terms', 'read', 'عرض المصطلحات الطبية'),
('medical_terms', 'update', 'تعديل المصطلحات الطبية'),
('medical_terms', 'delete', 'حذف المصطلحات الطبية'),
('medical_terms', 'manage', 'إدارة شاملة للمصطلحات الطبية'),

-- Reports
('reports', 'create', 'إنشاء تقارير'),
('reports', 'read', 'عرض التقارير'),
('reports', 'update', 'تعديل التقارير'),
('reports', 'delete', 'حذف التقارير'),
('reports', 'manage', 'إدارة شاملة للتقارير'),

-- Settings
('settings', 'read', 'عرض إعدادات النظام'),
('settings', 'update', 'تعديل إعدادات النظام'),
('settings', 'manage', 'إدارة شاملة للإعدادات'),

-- RBAC management
('rbac', 'create', 'إنشاء أدوار وصلاحيات'),
('rbac', 'read', 'عرض الأدوار والصلاحيات'),
('rbac', 'update', 'تعديل الأدوار والصلاحيات'),
('rbac', 'delete', 'حذف الأدوار والصلاحيات'),
('rbac', 'manage', 'إدارة شاملة للأدوار والصلاحيات');

-- Assign all permissions to admin role
INSERT OR IGNORE INTO role_permissions (role_id, permission_id, granted)
SELECT r.id, p.id, 1
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'admin';

-- Assign basic permissions to other roles
-- Doctor permissions
INSERT OR IGNORE INTO role_permissions (role_id, permission_id, granted)
SELECT r.id, p.id, 1
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'doctor' 
AND p.feature IN ('appointments', 'patients', 'videos', 'medical_terms')
AND p.action IN ('read', 'create', 'update');

-- Patient permissions
INSERT OR IGNORE INTO role_permissions (role_id, permission_id, granted)
SELECT r.id, p.id, 1
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'patient' 
AND p.feature IN ('appointments', 'videos', 'medical_terms')
AND p.action = 'read';

-- Secretary permissions
INSERT OR IGNORE INTO role_permissions (role_id, permission_id, granted)
SELECT r.id, p.id, 1
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'secretary' 
AND p.feature IN ('appointments', 'videos', 'patients', 'clinics')
AND p.action IN ('read', 'create', 'update');

-- Pharmacy permissions
INSERT OR IGNORE INTO role_permissions (role_id, permission_id, granted)
SELECT r.id, p.id, 1
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'pharmacy' 
AND p.feature IN ('pharmacy', 'videos', 'medical_terms')
AND p.action IN ('read', 'create', 'update');

-- Reception permissions
INSERT OR IGNORE INTO role_permissions (role_id, permission_id, granted)
SELECT r.id, p.id, 1
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'reception' 
AND p.feature IN ('reception', 'appointments', 'patients', 'videos')
AND p.action IN ('read', 'create', 'update');

-- Assign admin user to admin role
INSERT OR IGNORE INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
CROSS JOIN roles r
WHERE u.email = 'admin@silentconnect.com' 
AND r.name = 'admin';
