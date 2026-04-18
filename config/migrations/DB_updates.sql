-- Migration: private-session approvals and subscriber moderation
-- Run this once before deploying the private-session feature.

START TRANSACTION;

ALTER TABLE sessions
ADD COLUMN sub_count INT NOT NULL DEFAULT 0;

CREATE TABLE subscribers (
  Subscriber_ID INT NOT NULL,
  Session_ID INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (Subscriber_ID, Session_ID),
  CONSTRAINT fk_subscribers_user
    FOREIGN KEY (Subscriber_ID)
    REFERENCES users(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_subscribers_session
    FOREIGN KEY (Session_ID)
    REFERENCES sessions(id)
    ON DELETE CASCADE
);

ALTER TABLE sessions
  MODIFY COLUMN audience ENUM('my_university', 'all_universities', 'private') NOT NULL;

ALTER TABLE subscribers
  ADD COLUMN IF NOT EXISTS status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved' AFTER Session_ID,
  ADD COLUMN IF NOT EXISTS requested_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP AFTER status,
  ADD COLUMN IF NOT EXISTS approved_at DATETIME NULL AFTER requested_at,
  ADD COLUMN IF NOT EXISTS rejected_at DATETIME NULL AFTER approved_at;

UPDATE subscribers
SET status = 'approved'
WHERE status IS NULL OR status = '';

UPDATE subscribers
SET approved_at = COALESCE(approved_at, requested_at, NOW())
WHERE status = 'approved';

UPDATE sessions s
SET s.sub_count = (
  SELECT COUNT(*)
  FROM subscribers sub
  WHERE sub.Session_ID = s.id
    AND sub.status <> 'rejected'
);

COMMIT;
