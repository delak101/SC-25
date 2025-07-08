-- Add clinic_id column to videos table
-- This migration adds the missing clinic_id field for video-clinic relationship

-- For MySQL/MariaDB
ALTER TABLE videos ADD COLUMN clinic_id INT;

-- Add foreign key constraint
ALTER TABLE videos ADD CONSTRAINT fk_videos_clinic_id FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE SET NULL;

-- Create index for better performance
CREATE INDEX idx_videos_clinic_id ON videos(clinic_id);
