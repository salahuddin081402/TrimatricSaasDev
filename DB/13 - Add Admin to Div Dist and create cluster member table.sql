ALTER TABLE divisions
  ADD COLUMN division_admin_user_id BIGINT UNSIGNED NULL AFTER status,
  ADD CONSTRAINT fk_divisions_div_admin
    FOREIGN KEY (division_admin_user_id) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE districts
  ADD COLUMN district_admin_user_id BIGINT UNSIGNED NULL AFTER status,
  ADD CONSTRAINT fk_districts_dist_admin
    FOREIGN KEY (district_admin_user_id) REFERENCES users(id) ON DELETE SET NULL;

