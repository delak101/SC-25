-- Add specialization column to clinics table
-- This migration adds the missing specialization field

-- For SQLite
ALTER TABLE clinics ADD COLUMN specialization TEXT;

-- Create index for better performance
CREATE INDEX IF NOT EXISTS idx_clinics_specialization ON clinics(specialization);
