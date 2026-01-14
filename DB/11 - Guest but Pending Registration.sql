/* ===== Case 2: Guest user WITH PENDING registration (non-client) ===== */
START TRANSACTION;

-- Company = 1
SET @company_id = 1;

-- Resolve Guest role id for company 1
SET @role_guest = (
  SELECT id FROM roles
  WHERE company_id=@company_id AND name='Guest' AND deleted_at IS NULL
  LIMIT 1
);

-- Create/ensure user
SET @user_email = 'pending.officer@example.com';
SET @user_name  = 'Pending Officer';

INSERT INTO users (company_id, role_id, name, email, password, status, created_by, updated_by, created_at, updated_at)
SELECT @company_id, @role_guest, @user_name, @user_email,
       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- "password"
       1, 1, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email=@user_email);

-- Get user id
SET @user_id = (SELECT id FROM users WHERE email=@user_email LIMIT 1);

-- Locations: Dhaka (if present) else first; then first district/upazila under it
SET @div_id = COALESCE(
  (SELECT id FROM divisions WHERE name LIKE 'Dhaka%' ORDER BY id LIMIT 1),
  (SELECT id FROM divisions ORDER BY id LIMIT 1)
);

SET @dist_id = COALESCE(
  (SELECT id FROM districts WHERE division_id=@div_id ORDER BY id LIMIT 1),
  (SELECT id FROM districts ORDER BY id LIMIT 1)
);

SET @upa_id = COALESCE(
  (SELECT id FROM upazilas WHERE district_id=@dist_id ORDER BY id LIMIT 1),
  (SELECT id FROM upazilas ORDER BY id LIMIT 1)
);

-- Contact for registration
SET @reg_phone = '+8801700000002';
SET @reg_email = @user_email;

-- Insert pending registration if not exists
INSERT INTO registration_master (
  company_id, user_id, registration_type,
  full_name, gender, date_of_birth,
  phone, email,
  division_id, district_id, upazila_id,
  person_type, present_address, notes,
  approval_status, approved_by, approved_at,
  status,
  created_by, updated_by, created_at, updated_at, deleted_at
)
SELECT
  @company_id, @user_id, 'company_officer',
  'Pending Officer', 'male', NULL,
  @reg_phone, @reg_email,
  @div_id, @dist_id, @upa_id,
  'P', 'Test pending address', NULL,
  'pending', NULL, NULL,
  0,                      -- status: 0 = Pending (not active)
  1, 1, NOW(), NOW(), NULL
WHERE NOT EXISTS (SELECT 1 FROM registration_master WHERE user_id=@user_id);

COMMIT;
