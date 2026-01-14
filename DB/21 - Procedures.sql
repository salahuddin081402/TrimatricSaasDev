DELIMITER $$

CREATE OR REPLACE PROCEDURE sp_promote_user_role(
  IN  p_company_id     INT,
  IN  p_reg_id         INT,
  IN  p_user_id        INT,
  IN  p_target_role_id INT,   -- allowed targets: 2..8 (1 is NOT allowed)
  IN  p_division_id    INT,   -- required for 5
  IN  p_district_id    INT,   -- required for 6,7,8
  IN  p_cluster_id     INT,   -- required for 7,8
  IN  p_actor_user_id  INT
)
BEGIN
  DECLARE v_reg_type   VARCHAR(64);
  DECLARE v_approval   VARCHAR(32);
  DECLARE v_cur_role   INT;

  DECLARE v_occ_id     INT;
  DECLARE v_occ_name   VARCHAR(255);
  DECLARE v_div_name   VARCHAR(255);
  DECLARE v_dst_name   VARCHAR(255);
  DECLARE v_cluster_nm VARCHAR(255);

  DECLARE v_msg        VARCHAR(1000);

  /* ====== EXIT handler: rollback on any SQL exception ====== */
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  START TRANSACTION;

  /* ===== 0) Context & base checks (locked rows) ===== */
  SELECT rm.registration_type, rm.approval_status
    INTO v_reg_type, v_approval
  FROM registration_master rm
  WHERE rm.id = p_reg_id
    AND rm.company_id = p_company_id
    AND rm.user_id = p_user_id
    AND rm.deleted_at IS NULL
  FOR UPDATE;

  IF v_reg_type IS NULL THEN
    SET v_msg='Not found: registration context for this user/company.';
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT=v_msg;
  END IF;

  IF v_reg_type <> 'company_officer' THEN
    SET v_msg='Promotion blocked: only "company_officer" registrations are promotable.';
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT=v_msg;
  END IF;

  IF v_approval <> 'approved' THEN
    SET v_msg='Promotion blocked: registration must be Approved.';
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT=v_msg;
  END IF;

  SELECT role_id INTO v_cur_role
  FROM users
  WHERE id = p_user_id
  FOR UPDATE;

  /* Only current roles 3..8 can be promoted */
  IF v_cur_role NOT BETWEEN 3 AND 8 THEN
    SET v_msg='Ineligible: current role must be in 3..8 for promotion.';
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT=v_msg;
  END IF;

  /* ===== One-step promotions only ===== */
  IF NOT (
    (v_cur_role = 8 AND p_target_role_id = 7) OR
    (v_cur_role = 7 AND p_target_role_id = 6) OR
    (v_cur_role = 6 AND p_target_role_id = 5) OR
    (v_cur_role = 5 AND p_target_role_id = 4) OR
    (v_cur_role = 4 AND p_target_role_id = 3) OR
    (v_cur_role = 3 AND p_target_role_id = 2)
  ) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Only one-step promotions are allowed.';
  END IF;

  /* Target role: 2..8 (1 is forbidden) */
  IF p_target_role_id = 1 THEN
    SET v_msg='Forbidden: nobody can be promoted to Super Admin (role 1).';
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT=v_msg;
  END IF;

  IF p_target_role_id NOT BETWEEN 2 AND 8 THEN
    SET v_msg='Invalid target role: only 2..8 allowed.';
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT=v_msg;
  END IF;

  /* Must be strictly higher (numerically smaller) than current */
  IF p_target_role_id >= v_cur_role THEN
    SET v_msg='Invalid target: target role must be higher than current (numerically smaller).';
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT=v_msg;
  END IF;

  /* ===== 1) Target-specific input checks ===== */
  IF p_target_role_id = 5 AND (p_division_id IS NULL OR p_division_id < 1) THEN
    SET v_msg='Input missing: division_id is required for promotion to Division Admin (role 5).';
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT=v_msg;
  END IF;

  IF p_target_role_id = 6 AND (p_district_id IS NULL OR p_district_id < 1) THEN
    SET v_msg='Input missing: district_id is required for promotion to District Admin (role 6).';
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT=v_msg;
  END IF;

  IF p_target_role_id IN (7,8) AND
     (p_district_id IS NULL OR p_district_id < 1 OR p_cluster_id IS NULL OR p_cluster_id < 1) THEN
    SET v_msg='Input missing: district_id and cluster_id are both required for roles 7/8.';
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT=v_msg;
  END IF;

  /* ===== 2) Single-occupant HQ rule for CEO (role 2) ===== */
  IF p_target_role_id = 2 THEN
    /* CEO is single-occupant per company (using rm.company_id scope) */
    IF EXISTS (
      SELECT 1
        FROM users u
        JOIN registration_master rm2
          ON rm2.user_id = u.id
         AND rm2.company_id = p_company_id
         AND rm2.deleted_at IS NULL
       WHERE u.role_id = 2
         AND u.id <> p_user_id
       LIMIT 1
    ) THEN
      SELECT u.id, u.name
        INTO v_occ_id, v_occ_name
        FROM users u
        JOIN registration_master rm2
          ON rm2.user_id = u.id
         AND rm2.company_id = p_company_id
         AND rm2.deleted_at IS NULL
       WHERE u.role_id = 2
         AND u.id <> p_user_id
       LIMIT 1;

      SET v_msg = CONCAT(
        'CEO already exists for this Company. Holder: ',
        COALESCE(v_occ_name,'Unknown'), ' (User ID: ', COALESCE(v_occ_id,0),
        '). De-assign first, then retry.'
      );
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT=v_msg;
    END IF;
    /* (No geo-footprint cleanup mandated for CEO.) */
  END IF;

  /* ===== 3) Target-specific pre-conditions & single-occupant checks ===== */

  /* 3A) Promote to Division Admin (role 5) */
  IF p_target_role_id = 5 THEN
    /* Single-occupant per Division check */
    SELECT name INTO v_div_name FROM divisions WHERE id = p_division_id LIMIT 1;

    IF EXISTS (
      SELECT 1 FROM company_division_admins
       WHERE company_id=p_company_id AND division_id=p_division_id AND status=1 AND user_id<>p_user_id
    ) THEN
      SELECT u.id, u.name INTO v_occ_id, v_occ_name
        FROM company_division_admins cda
        LEFT JOIN users u ON u.id=cda.user_id
       WHERE cda.company_id=p_company_id AND cda.division_id=p_division_id AND cda.status=1
       LIMIT 1;

      SET v_msg = CONCAT(
        'Already Admin exists for Division "', COALESCE(v_div_name,'(Unknown)'),
        '". Holder: ', COALESCE(v_occ_name,'Unknown'),
        ' (User ID: ', COALESCE(v_occ_id,0), '). De-assign first, then retry.'
      );
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT=v_msg;
    END IF;

    /* Deactivate ALL District Admin rows for this user (your rule) */
    UPDATE company_district_admins
       SET status=0, updated_by=p_actor_user_id, updated_at=NOW()
     WHERE company_id=p_company_id AND user_id=p_user_id AND status=1;

    /* Restore-or-insert Division Admin row for the target division */
    IF EXISTS (
      SELECT 1 FROM company_division_admins
       WHERE company_id=p_company_id AND division_id=p_division_id AND user_id=p_user_id AND status=0
    ) THEN
      UPDATE company_division_admins
         SET status=1, updated_by=p_actor_user_id, updated_at=NOW()
       WHERE company_id=p_company_id AND division_id=p_division_id AND user_id=p_user_id AND status=0;
    ELSE
      INSERT INTO company_division_admins
        (company_id,division_id,user_id,status,created_by,updated_by,created_at,updated_at)
      VALUES (p_company_id,p_division_id,p_user_id,1,p_actor_user_id,p_actor_user_id,NOW(),NOW());
    END IF;
  END IF;

  /* 3B) Promote to District Admin (role 6) */
  IF p_target_role_id = 6 THEN
    /* Single-occupant per District check */
    SELECT name INTO v_dst_name FROM districts WHERE id = p_district_id LIMIT 1;

    IF EXISTS (
      SELECT 1 FROM company_district_admins
       WHERE company_id=p_company_id AND district_id=p_district_id AND status=1 AND user_id<>p_user_id
    ) THEN
      SELECT u.id, u.name INTO v_occ_id, v_occ_name
        FROM company_district_admins cda
        LEFT JOIN users u ON u.id=cda.user_id
       WHERE cda.company_id=p_company_id AND cda.district_id=p_district_id AND cda.status=1
       LIMIT 1;

      SET v_msg = CONCAT(
        'Already Admin exists for District "', COALESCE(v_dst_name,'(Unknown)'),
        '". Holder: ', COALESCE(v_occ_name,'Unknown'),
        ' (User ID: ', COALESCE(v_occ_id,0), '). De-assign first, then retry.'
      );
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT=v_msg;
    END IF;

    /* Drop ALL cluster supervisions for this user (keep memberships intact) */
    UPDATE cluster_masters
       SET cluster_supervisor_id = NULL, updated_by=p_actor_user_id, updated_at=NOW()
     WHERE company_id = p_company_id
       AND cluster_supervisor_id = p_user_id;

    /* Restore-or-insert District Admin row for the target district */
    IF EXISTS (
      SELECT 1 FROM company_district_admins
       WHERE company_id=p_company_id AND district_id=p_district_id AND user_id=p_user_id AND status=0
    ) THEN
      UPDATE company_district_admins
         SET status=1, updated_by=p_actor_user_id, updated_at=NOW()
       WHERE company_id=p_company_id AND district_id=p_district_id AND user_id=p_user_id AND status=0;
    ELSE
      INSERT INTO company_district_admins
        (company_id,district_id,user_id,status,created_by,updated_by,created_at,updated_at)
      VALUES (p_company_id,p_district_id,p_user_id,1,p_actor_user_id,p_actor_user_id,NOW(),NOW());
    END IF;
  END IF;

  /* 3C) Promote to Cluster Admin / Supervisor (role 7) */
  IF p_target_role_id = 7 THEN
    /* Must be active member of the target cluster */
    IF NOT EXISTS (
      SELECT 1 FROM cluster_members
       WHERE company_id=p_company_id AND cluster_id=p_cluster_id AND user_id=p_user_id AND status=1
    ) THEN
      SELECT cm.cluster_name INTO v_cluster_nm FROM cluster_masters cm
       WHERE cm.company_id=p_company_id AND cm.id=p_cluster_id LIMIT 1;

      SET v_msg = CONCAT(
        'Ineligible: user is not an active member of cluster "',
        COALESCE(v_cluster_nm,'(Unknown)'), '".'
      );
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT=v_msg;
    END IF;

    /* Single-occupant per cluster (supervisor) */
    SELECT cm.cluster_name INTO v_cluster_nm
      FROM cluster_masters cm
     WHERE cm.company_id=p_company_id AND cm.id=p_cluster_id
     LIMIT 1;

    IF EXISTS (
      SELECT 1 FROM cluster_masters
       WHERE company_id=p_company_id
         AND id=p_cluster_id
         AND cluster_supervisor_id IS NOT NULL
         AND cluster_supervisor_id <> p_user_id
    ) THEN
      SELECT u.id, u.name
        INTO v_occ_id, v_occ_name
      FROM cluster_masters cm
      LEFT JOIN users u ON u.id = cm.cluster_supervisor_id
      WHERE cm.company_id=p_company_id AND cm.id=p_cluster_id
      LIMIT 1;

      SET v_msg = CONCAT(
        'Cluster "', COALESCE(v_cluster_nm,'(Unknown)'),
        '" already has an Admin: ', COALESCE(v_occ_name,'Unknown'),
        ' (User ID: ', COALESCE(v_occ_id,0), '). De-assign first, then retry.'
      );
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT=v_msg;
    END IF;

    /* Assign as supervisor; DO NOT remove any other cluster supervisions or memberships */
    UPDATE cluster_masters
       SET cluster_supervisor_id=p_user_id, updated_by=p_actor_user_id, updated_at=NOW()
     WHERE company_id=p_company_id AND id=p_cluster_id;
  END IF;

  /* 3D) Promote to Cluster Member (role 8) */
  IF p_target_role_id = 8 THEN
    /* Upsert membership for the target cluster; DO NOT touch other memberships */
    IF EXISTS (
      SELECT 1 FROM cluster_members
       WHERE company_id=p_company_id AND cluster_id=p_cluster_id AND user_id=p_user_id
    ) THEN
      UPDATE cluster_members
         SET status=1, updated_by=p_actor_user_id, updated_at=NOW()
       WHERE company_id=p_company_id AND cluster_id=p_cluster_id AND user_id=p_user_id;
    ELSE
      INSERT INTO cluster_members
        (company_id,cluster_id,user_id,status,created_by,updated_by,created_at,updated_at)
      VALUES (p_company_id,p_cluster_id,p_user_id,1,p_actor_user_id,p_actor_user_id,NOW(),NOW());
    END IF;
  END IF;

  /* 3E) Head-office ladder nuance: 5 -> 4 should drop division-admin footprints (your rule) */
  IF p_target_role_id = 4 THEN
    UPDATE company_division_admins
       SET status=0, updated_by=p_actor_user_id, updated_at=NOW()
     WHERE company_id=p_company_id AND user_id=p_user_id AND status=1;
    /* No district/cluster cleanup requested for 4 */
  END IF;
  /* Roles 3 and 2: no geo-footprint changes by design */

  /* ===== 4) Persist role and keep RM approved ===== */
  UPDATE users
     SET role_id=p_target_role_id, updated_at=NOW()
   WHERE id=p_user_id;

  UPDATE registration_master
     SET status=1, approval_status='approved', updated_by=p_actor_user_id, updated_at=NOW()
   WHERE id=p_reg_id;

  /* ===== 5) Audit log ===== */
  INSERT INTO activity_logs(
    company_id,user_id,action,table_name,row_id,details,ip_address,
    time_local,time_dhaka,created_by,updated_by,created_at,updated_at
  )
  VALUES (
    p_company_id,p_actor_user_id,'promotion','registration_master',p_reg_id,
    JSON_OBJECT('user_id',p_user_id,'target_role_id',p_target_role_id,
                'division_id',p_division_id,'district_id',p_district_id,'cluster_id',p_cluster_id),
    NULL,NOW(),NOW(),p_actor_user_id,p_actor_user_id,NOW(),NOW()
  );

  COMMIT;
END$$

DELIMITER ;
