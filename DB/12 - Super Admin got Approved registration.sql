/* ===== Case 3: Super Admin — approved registration (company_officer) ===== */
START TRANSACTION;

-- Tenant
SET @company_id = 1;

-- Resolve role ids
SET @role_super_admin = (
  SELECT id FROM roles
  WHERE company_id=@company_id AND name='Super Admin' AND deleted_at IS NULL
  LIMIT 1
);

-- Pick the Super Admin user (must already exist per your note)
SET @user_id = (
  SELECT id FROM users
  WHERE company_id=@company_id AND role_id=@role_super_admin
  ORDER BY id
  LIMIT 1
);

-- Pull convenient fields from users
SET @user_email = (SELECT email FROM users WHERE id=@user_id);
SET @user_name  = (SELECT name  FROM users WHERE id=@user_id);

-- Location fallbacks: Dhaka if present, else first; then first district/upazila under it
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

-- Phone unique per company; make it deterministic from user id
SET @reg_phone = CONCAT('+8801700', LPAD(@user_id, 6, '0'));
SET @reg_email = @user_email;

-- Insert APPROVED registration if not exists
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
  COALESCE(@user_name, 'Super Admin'), 'male', NULL,
  @reg_phone, @reg_email,
  @div_id, @dist_id, @upa_id,
  'J', 'Head Office', 'Bootstrap approved record for Super Admin',
  'approved', @user_id, NOW(),
  1,                  -- status: 1 = Approved/Active
  1, 1, NOW(), NOW(), NULL
WHERE NOT EXISTS (SELECT 1 FROM registration_master WHERE user_id=@user_id);

COMMIT;
