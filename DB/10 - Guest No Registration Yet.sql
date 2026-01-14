START TRANSACTION;

-- -------- Params (change if you want) --------
SET @company_id := 1;

SET @user_name  := 'Guest NoReg';
SET @user_email := 'guest_noreg@example.com';
-- bcrypt('Password@123') — change if you like
SET @user_pass  := '$2y$12$wCqYz0x3rQvQqk3rIysb5uQe2T4S7WQJd0y6L9p9iZy1mM0QxJrme';
-- --------------------------------------------

/* Resolve Guest role (Role-Type: Client) */
SELECT r.id INTO @guest_role_id
FROM roles r
JOIN role_types rt ON rt.id = r.role_type_id
WHERE r.company_id = @company_id AND r.name = 'Guest'
LIMIT 1;

/* Create user if not exists */
INSERT INTO `users` (company_id, role_id, name, email, password, status, created_by, updated_by, created_at, updated_at)
SELECT @company_id, @guest_role_id, @user_name, @user_email, @user_pass, 1, 1, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE email=@user_email AND company_id=@company_id);

SELECT id AS user_id, role_id AS user_role_id
INTO @user_id, @user_role_id
FROM `users`
WHERE email=@user_email AND company_id=@company_id
LIMIT 1;

/* Ensure NO registration row for this user (just in case someone added one) */
DELETE FROM registration_master WHERE user_id=@user_id;

COMMIT;

/* Quick check */
SELECT @user_id AS user_id, @user_role_id AS role_id;
