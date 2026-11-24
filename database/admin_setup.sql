-- Admin setup: run this ONCE manually from phpMyAdmin or MySQL CLI.
-- Never expose this file publicly. Delete it after running.

-- Promote an existing user to admin (replace 'yourusername' with your actual username):
UPDATE artsphere.users SET role = 'admin' WHERE username = 'yourusername' LIMIT 1;

-- Verify:
SELECT id, username, role FROM artsphere.users WHERE role = 'admin';
