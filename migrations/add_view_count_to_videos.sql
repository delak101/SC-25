-- Add view_count column to videos table
ALTER TABLE videos ADD COLUMN view_count INT DEFAULT 0 NOT NULL;

-- Update existing videos to have 0 view count
UPDATE videos SET view_count = 0 WHERE view_count IS NULL;
