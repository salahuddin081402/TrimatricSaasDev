<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Drop-if-exists guards (idempotent)
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cluster_masters_bi');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cluster_masters_bu');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cluster_upazila_mappings_bi');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cluster_upazila_mappings_bu');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cluster_members_bi');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cluster_members_bu');

        // --- cluster_masters: BEFORE INSERT ---
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_cluster_masters_bi
BEFORE INSERT ON cluster_masters
FOR EACH ROW
BEGIN
  DECLARE v_dist_code CHAR(5);
  DECLARE v_next TINYINT UNSIGNED;
  DECLARE v_user_co BIGINT UNSIGNED;

  SELECT short_code INTO v_dist_code
  FROM districts
  WHERE id = NEW.district_id
  LIMIT 1;

  IF v_dist_code IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid district_id for cluster';
  END IF;

  IF NEW.cluster_supervisor_id IS NOT NULL THEN
    SELECT company_id INTO v_user_co FROM users WHERE id = NEW.cluster_supervisor_id LIMIT 1;
    IF v_user_co IS NULL OR v_user_co <> NEW.company_id THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'cluster_supervisor must belong to the same company';
    END IF;
  END IF;

  IF NEW.cluster_no IS NULL OR NEW.cluster_no = 0 THEN
    SELECT COALESCE(MAX(cluster_no),0)+1 INTO v_next
    FROM cluster_masters
    WHERE company_id = NEW.company_id
      AND district_id = NEW.district_id;
    SET NEW.cluster_no = v_next;
  END IF;

  IF NEW.cluster_no < 1 OR NEW.cluster_no > 99 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'cluster_no must be between 1 and 99';
  END IF;

  SET NEW.short_code = CONCAT(v_dist_code, LPAD(NEW.cluster_no, 2, '0'));
END
SQL);

        // --- cluster_masters: BEFORE UPDATE ---
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_cluster_masters_bu
BEFORE UPDATE ON cluster_masters
FOR EACH ROW
BEGIN
  DECLARE v_dist_code CHAR(5);
  DECLARE v_user_co BIGINT UNSIGNED;

  IF NEW.cluster_supervisor_id IS NOT NULL THEN
    SELECT company_id INTO v_user_co FROM users WHERE id = NEW.cluster_supervisor_id LIMIT 1;
    IF v_user_co IS NULL OR v_user_co <> NEW.company_id THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'cluster_supervisor must belong to the same company';
    END IF;
  END IF;

  IF NEW.cluster_no < 1 OR NEW.cluster_no > 99 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'cluster_no must be between 1 and 99';
  END IF;

  IF NEW.district_id <> OLD.district_id OR NEW.cluster_no <> OLD.cluster_no THEN
    SELECT short_code INTO v_dist_code
    FROM districts
    WHERE id = NEW.district_id
    LIMIT 1;

    IF v_dist_code IS NULL THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid district_id for cluster (update)';
    END IF;

    SET NEW.short_code = CONCAT(v_dist_code, LPAD(NEW.cluster_no, 2, '0'));
  END IF;
END
SQL);

        // --- cluster_upazila_mappings: BEFORE INSERT ---
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_cluster_upazila_mappings_bi
BEFORE INSERT ON cluster_upazila_mappings
FOR EACH ROW
BEGIN
  DECLARE v_cluster_co BIGINT UNSIGNED;
  DECLARE v_cluster_dist BIGINT UNSIGNED;
  DECLARE v_upa_dist BIGINT UNSIGNED;

  SELECT company_id, district_id INTO v_cluster_co, v_cluster_dist
  FROM cluster_masters
  WHERE id = NEW.cluster_id
  LIMIT 1;

  IF v_cluster_co IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid cluster_id in mapping';
  END IF;

  IF v_cluster_co <> NEW.company_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Mapping company_id must equal cluster company_id';
  END IF;

  SELECT district_id INTO v_upa_dist
  FROM upazilas
  WHERE id = NEW.upazila_id
  LIMIT 1;

  IF v_upa_dist IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid upazila_id in mapping';
  END IF;

  IF v_upa_dist <> v_cluster_dist THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Upazila must belong to the same district as the cluster';
  END IF;
END
SQL);

        // --- cluster_upazila_mappings: BEFORE UPDATE ---
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_cluster_upazila_mappings_bu
BEFORE UPDATE ON cluster_upazila_mappings
FOR EACH ROW
BEGIN
  DECLARE v_cluster_co BIGINT UNSIGNED;
  DECLARE v_cluster_dist BIGINT UNSIGNED;
  DECLARE v_upa_dist BIGINT UNSIGNED;

  SELECT company_id, district_id INTO v_cluster_co, v_cluster_dist
  FROM cluster_masters
  WHERE id = NEW.cluster_id
  LIMIT 1;

  IF v_cluster_co IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid cluster_id in mapping (update)';
  END IF;

  IF v_cluster_co <> NEW.company_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Mapping company_id must equal cluster company_id (update)';
  END IF;

  SELECT district_id INTO v_upa_dist
  FROM upazilas
  WHERE id = NEW.upazila_id
  LIMIT 1;

  IF v_upa_dist IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid upazila_id in mapping (update)';
  END IF;

  IF v_upa_dist <> v_cluster_dist THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Upazila must belong to the same district as the cluster (update)';
  END IF;
END
SQL);

        // --- cluster_members: BEFORE INSERT ---
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_cluster_members_bi
BEFORE INSERT ON cluster_members
FOR EACH ROW
BEGIN
  DECLARE v_cluster_co BIGINT UNSIGNED;
  DECLARE v_user_co BIGINT UNSIGNED;

  SELECT company_id INTO v_cluster_co
  FROM cluster_masters
  WHERE id = NEW.cluster_id
  LIMIT 1;

  IF v_cluster_co IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid cluster_id for member';
  END IF;

  IF v_cluster_co <> NEW.company_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Member company_id must equal cluster company_id';
  END IF;

  SELECT company_id INTO v_user_co
  FROM users
  WHERE id = NEW.user_id
  LIMIT 1;

  IF v_user_co IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid user_id for member';
  END IF;

  IF v_user_co <> NEW.company_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Member user must belong to the same company as the cluster';
  END IF;
END
SQL);

        // --- cluster_members: BEFORE UPDATE ---
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_cluster_members_bu
BEFORE UPDATE ON cluster_members
FOR EACH ROW
BEGIN
  DECLARE v_cluster_co BIGINT UNSIGNED;
  DECLARE v_user_co BIGINT UNSIGNED;

  SELECT company_id INTO v_cluster_co
  FROM cluster_masters
  WHERE id = NEW.cluster_id
  LIMIT 1;

  IF v_cluster_co IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid cluster_id for member (update)';
  END IF;

  IF v_cluster_co <> NEW.company_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Member company_id must equal cluster company_id (update)';
  END IF;

  SELECT company_id INTO v_user_co
  FROM users
  WHERE id = NEW.user_id
  LIMIT 1;

  IF v_user_co IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid user_id for member (update)';
  END IF;

  IF v_user_co <> NEW.company_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Member user must belong to the same company as the cluster (update)';
  END IF;
END
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cluster_members_bu');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cluster_members_bi');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cluster_upazila_mappings_bu');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cluster_upazila_mappings_bi');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cluster_masters_bu');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cluster_masters_bi');
    }
};
