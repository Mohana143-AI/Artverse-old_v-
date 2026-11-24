USE artsphere;
-- Add delivery tracking to messages (safe to run multiple times)
ALTER TABLE messages ADD COLUMN IF NOT EXISTS is_delivered TINYINT(1) NOT NULL DEFAULT 0;
-- Mark all existing messages as delivered
UPDATE messages SET is_delivered = 1 WHERE id > 0;
