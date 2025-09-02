
-- =====================
-- Country 
-- =====================


CREATE TABLE countries (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Country ID',
    name            VARCHAR(150) NOT NULL COMMENT 'Country name',
    short_code      VARCHAR(10)  NULL COMMENT 'Short Code. Say, BD for Bangladesh',
    created_by      BIGINT UNSIGNED NULL COMMENT 'User who created this Country',
    updated_by      BIGINT UNSIGNED NULL COMMENT 'User who last updated this Country',
    created_at      TIMESTAMP NULL DEFAULT NULL,
    updated_at      TIMESTAMP NULL DEFAULT NULL,
    deleted_at      TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_country_name (name),
    INDEX idx_country_deleted_at (deleted_at),
    INDEX idx_country_created_by (created_by),
    INDEX idx_country_updated_by (updated_by)
);


-- =====================
-- COMPANY (Tenant) Table
-- =====================
-- companies_create_with_slug_status.sql
CREATE TABLE companies (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Company/Tenant ID',
    country_id      BIGINT UNSIGNED NOT NULL COMMENT 'FK to countries.id',
    name            VARCHAR(150) NOT NULL COMMENT 'Company or tenant name',
    slug            VARCHAR(190) NOT NULL COMMENT 'URL-safe unique slug for company',
    description     VARCHAR(255) NULL COMMENT 'Details about the company',
    address         VARCHAR(255) NULL COMMENT 'Company address',
    contact_no      VARCHAR(50)  NULL COMMENT 'Primary contact no',
    logo            VARCHAR(255) NULL COMMENT 'Public path to company logo',
    status          TINYINT NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
    created_by      BIGINT UNSIGNED NULL COMMENT 'User who created this company',
    updated_by      BIGINT UNSIGNED NULL COMMENT 'User who last updated this company',
    created_at      TIMESTAMP NULL DEFAULT NULL,
    updated_at      TIMESTAMP NULL DEFAULT NULL,
    deleted_at      TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_companies_name (name),
    UNIQUE KEY uq_companies_slug (slug),
    INDEX idx_companies_status (status),
    INDEX idx_companies_deleted_at (deleted_at),
    INDEX idx_companies_created_by (created_by),
    INDEX idx_companies_updated_by (updated_by),
    CONSTRAINT fk_companies_country
      FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE
) ;

-- =====================
-- ROLE TYPES (Dashboard categories)
-- =====================
CREATE TABLE role_types (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL COMMENT 'Role type/category name (e.g., Super Admin/Head Office, Vendor, Client, etc.)',
    description     VARCHAR(255) NULL,
    created_by      BIGINT UNSIGNED NULL,
    updated_by      BIGINT UNSIGNED NULL,
    created_at      TIMESTAMP NULL DEFAULT NULL,
    updated_at      TIMESTAMP NULL DEFAULT NULL,
    deleted_at      TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_role_type_name (name),
    INDEX idx_role_type_deleted_at (deleted_at)
);

-- =====================
-- ROLES (Custom, per company, under role_type)
-- =====================
CREATE TABLE roles (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id      BIGINT UNSIGNED NOT NULL COMMENT 'FK to companies',
    role_type_id    BIGINT UNSIGNED NOT NULL COMMENT 'FK to role_types',
    name            VARCHAR(100) NOT NULL COMMENT 'Role name (e.g., Zonal Admin)',
    description     VARCHAR(255) NULL,
    created_by      BIGINT UNSIGNED NULL,
    updated_by      BIGINT UNSIGNED NULL,
    created_at      TIMESTAMP NULL DEFAULT NULL,
    updated_at      TIMESTAMP NULL DEFAULT NULL,
    deleted_at      TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_role_company_name (company_id, name),
    INDEX idx_roles_company_id (company_id),
    INDEX idx_roles_role_type_id (role_type_id),
    INDEX idx_roles_deleted_at (deleted_at),
    INDEX idx_roles_created_by (created_by),
    INDEX idx_roles_updated_by (updated_by),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (role_type_id) REFERENCES role_types(id)
);

-- =====================
-- MENUS (Global, fixed)
-- =====================
CREATE TABLE menus (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id       BIGINT UNSIGNED NULL COMMENT 'For menu hierarchy',
    name            VARCHAR(100) NOT NULL COMMENT 'Menu name',
    uri             VARCHAR(200) NULL COMMENT 'Route/URL',
    icon            VARCHAR(100) NULL COMMENT 'Fontawesome or similar icon',
    menu_order      INT UNSIGNED NULL,
    description     VARCHAR(255) NULL,
    created_by      BIGINT UNSIGNED NULL,
    updated_by      BIGINT UNSIGNED NULL,
    created_at      TIMESTAMP NULL DEFAULT NULL,
    updated_at      TIMESTAMP NULL DEFAULT NULL,
    deleted_at      TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_menu_name (name),
    INDEX idx_menus_parent_id (parent_id),
    FOREIGN KEY (parent_id) REFERENCES menus(id) ON DELETE SET NULL
);

-- =====================
-- ROLE-MENU MAPPING (Many-to-Many: Role ↔ Menu)
-- =====================
CREATE TABLE role_menu_mappings (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id         BIGINT UNSIGNED NOT NULL,
    menu_id         BIGINT UNSIGNED NOT NULL,
    access_type     VARCHAR(30) NULL COMMENT 'view/edit/delete, etc.',
    created_by      BIGINT UNSIGNED NULL,
    updated_by      BIGINT UNSIGNED NULL,
    created_at      TIMESTAMP NULL DEFAULT NULL,
    updated_at      TIMESTAMP NULL DEFAULT NULL,
    deleted_at      TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_role_menu (role_id, menu_id),
    INDEX idx_role_menu_role_id (role_id),
    INDEX idx_role_menu_menu_id (menu_id),
    INDEX idx_role_menu_deleted_at (deleted_at),
    INDEX idx_role_menu_created_by (created_by),
    INDEX idx_role_menu_updated_by (updated_by),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_id) REFERENCES menus(id)
);

-- ====== Actions Table ======
CREATE TABLE actions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(50) NOT NULL COMMENT 'Action key (e.g., view, edit, approve, delete, add, return)',
    display_name    VARCHAR(100) NULL COMMENT 'Button label/UI',
    description     VARCHAR(255) NULL,
    created_by      BIGINT UNSIGNED NULL,
    updated_by      BIGINT UNSIGNED NULL,
    created_at      TIMESTAMP NULL DEFAULT NULL,
    updated_at      TIMESTAMP NULL DEFAULT NULL,
    deleted_at      TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_action_name (name)
);

-- ====== Menu-Actions Table ======
CREATE TABLE menu_actions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    menu_id         BIGINT UNSIGNED NOT NULL,
    action_id       BIGINT UNSIGNED NOT NULL,
    button_label    VARCHAR(100) NULL COMMENT 'Optional: custom label for this menu-action button',
    button_icon     VARCHAR(100) NULL COMMENT 'Optional: icon for the button',
    button_order    INT UNSIGNED NULL COMMENT 'Display order for actions in this menu',
    created_by      BIGINT UNSIGNED NULL,
    updated_by      BIGINT UNSIGNED NULL,
    created_at      TIMESTAMP NULL DEFAULT NULL,
    updated_at      TIMESTAMP NULL DEFAULT NULL,
    deleted_at      TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_menu_action (menu_id, action_id),
    FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE,
    FOREIGN KEY (action_id) REFERENCES actions(id)
);

-- ====== Role-Menu-Action Permissions Table ======
CREATE TABLE role_menu_action_permissions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id         BIGINT UNSIGNED NOT NULL,
    menu_id         BIGINT UNSIGNED NOT NULL,
    action_id       BIGINT UNSIGNED NOT NULL,
    allowed         TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=allowed, 0=not allowed',
    created_by      BIGINT UNSIGNED NULL,
    updated_by      BIGINT UNSIGNED NULL,
    created_at      TIMESTAMP NULL DEFAULT NULL,
    updated_at      TIMESTAMP NULL DEFAULT NULL,
    deleted_at      TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_role_menu_action (role_id, menu_id, action_id),
    INDEX idx_rma_role (role_id),
    INDEX idx_rma_menu (menu_id),
    INDEX idx_rma_action (action_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE,
    FOREIGN KEY (action_id) REFERENCES actions(id) ON DELETE CASCADE
);


-- =====================
-- USERS
-- =====================
CREATE TABLE users (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id      BIGINT UNSIGNED NOT NULL,
    role_id         BIGINT UNSIGNED NOT NULL,
    name            VARCHAR(150) NOT NULL,
    email           VARCHAR(150) NOT NULL,
    password        VARCHAR(255) NOT NULL,
    remember_token  VARCHAR(100) NULL COLLATE utf8mb4_unicode_ci COMMENT 'Token for Laravel remember me authentication',
    status          TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=active, 0=inactive',
    created_by      BIGINT UNSIGNED NULL,
    updated_by      BIGINT UNSIGNED NULL,
    created_at      TIMESTAMP NULL DEFAULT NULL,
    updated_at      TIMESTAMP NULL DEFAULT NULL,
    deleted_at      TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_user_email (company_id, email),
    INDEX idx_users_company_id (company_id),
    INDEX idx_users_role_id (role_id),
    INDEX idx_users_deleted_at (deleted_at),
    INDEX idx_users_status (status),
    INDEX idx_users_created_by (created_by),
    INDEX idx_users_updated_by (updated_by),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);


-- =====================
-- ACTIVITY LOG
-- =====================
CREATE TABLE activity_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id      BIGINT UNSIGNED NOT NULL COMMENT 'Tenant/Company',
    user_id         BIGINT UNSIGNED NULL,
    action          VARCHAR(20) NOT NULL COMMENT 'add/edit/delete/login/logout/other',
    table_name      VARCHAR(50) NULL COMMENT 'Affected table/entity',
    row_id          BIGINT UNSIGNED NULL COMMENT 'Affected row id',
    details         TEXT NULL COMMENT 'Optional details or JSON snapshot',
    ip_address      VARCHAR(45) NULL,
    time_local      TIMESTAMP NULL DEFAULT NULL COMMENT 'User’s local time (from browser/device)',
    time_dhaka      TIMESTAMP NULL DEFAULT NULL COMMENT 'Asia/Dhaka time (set by server)',
    created_by      BIGINT UNSIGNED NULL,
    updated_by      BIGINT UNSIGNED NULL,
    created_at      TIMESTAMP NULL DEFAULT NULL,
    updated_at      TIMESTAMP NULL DEFAULT NULL,
    deleted_at      TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_log_company_id (company_id),
    INDEX idx_log_user_id (user_id),
    INDEX idx_log_action (action),
    INDEX idx_log_time_dhaka (time_dhaka),
    INDEX idx_log_table_row (table_name, row_id),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);



-- =====================
-- ADDITIONAL: Foreign Keys for Audit Columns (Optional, for full traceability)
-- (Add these if you want to enforce user existance on created_by/updated_by fields)
-- =====================
-- ALTER TABLE companies ADD CONSTRAINT fk_companies_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;
-- ALTER TABLE companies ADD CONSTRAINT fk_companies_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL;
-- (Do similar for all tables with audit columns, if desired)


CREATE TABLE design_images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NULL,
    file_path VARCHAR(255) NOT NULL COMMENT 'S3 or local relative path',
    file_url VARCHAR(255) NULL COMMENT 'CDN or direct URL',
    file_size BIGINT UNSIGNED NULL,
    file_type VARCHAR(50) NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    -- ...FKs and audit columns...
    INDEX idx_design_images_company_id (company_id),
    INDEX idx_design_images_project_id (project_id)
);

