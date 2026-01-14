/* ============================================================
   Client Requisition – Interior Design Parameter Schema
   (Lookup + Master Tables Only, WITH IMAGE FIELDS, WITH company_id)
   Target: MySQL 8+, InnoDB, utf8mb4
   ============================================================ */

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

/* ---------- Drop existing (if any) in FK-safe order ---------- */

DROP TABLE IF EXISTS cr_products;
DROP TABLE IF EXISTS cr_item_subcategories;
DROP TABLE IF EXISTS cr_item_categories;
DROP TABLE IF EXISTS cr_spaces;
DROP TABLE IF EXISTS cr_project_subtypes;
DROP TABLE IF EXISTS cr_project_types;
DROP TABLE IF EXISTS  cr_space_category_mappings;
SET FOREIGN_KEY_CHECKS = 1;

/* ============================================================
   1) Project Types (Residential / Commercial / etc.)
   ============================================================ */

CREATE TABLE cr_project_types (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id      BIGINT UNSIGNED NULL,
    code            VARCHAR(30)     NOT NULL UNIQUE,
    name            VARCHAR(150)    NOT NULL,
    description     TEXT            NULL,

    -- Image used for project-type cards (Step-1)
    card_image_path VARCHAR(255)    NULL,

    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    sort_order      INT UNSIGNED    NOT NULL DEFAULT 0,
    created_at      DATETIME        NULL,
    updated_at      DATETIME        NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_cr_project_types_company
        FOREIGN KEY (company_id)
        REFERENCES companies (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

INSERT INTO cr_project_types (company_id, code, name, description, card_image_path, sort_order) VALUES
(NULL, 'RES', 'Residential',
 'Apartments, duplexes, villas and other residential interiors.',
 'assets/images/cr/project_types/residential.jpg', 1),
(NULL, 'COM', 'Commercial',
 'Retail, showrooms, offices and general commercial spaces.',
 'assets/images/cr/project_types/commercial.jpg', 2),
(NULL, 'HOS', 'Hospitality',
 'Hotels, resorts, serviced apartments, guest rooms.',
 'assets/images/cr/project_types/hospitality.jpg', 3),
(NULL, 'INS', 'Institutional',
 'Banks, schools, hospitals and institutional interiors.',
 'assets/images/cr/project_types/institutional.jpg', 4);

/* ============================================================
   2) Project Sub-Types (Duplex, Apartment, Office, etc.)
   ============================================================ */

CREATE TABLE cr_project_subtypes (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id       BIGINT UNSIGNED NULL,
    project_type_id  BIGINT UNSIGNED NOT NULL,
    code             VARCHAR(50)     NOT NULL,
    name             VARCHAR(150)    NOT NULL,
    description      TEXT            NULL,

    -- Image for sub-type cards (Step-1)
    card_image_path  VARCHAR(255)    NULL,

    is_active        TINYINT(1)      NOT NULL DEFAULT 1,
    sort_order       INT UNSIGNED    NOT NULL DEFAULT 0,
    created_at       DATETIME        NULL,
    updated_at       DATETIME        NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_project_subtype_code (code),
    UNIQUE KEY uq_project_subtype_type_name (project_type_id, name),
    CONSTRAINT fk_cr_project_subtypes_company
        FOREIGN KEY (company_id)
        REFERENCES companies (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_cr_project_subtypes_project_type
        FOREIGN KEY (project_type_id)
        REFERENCES cr_project_types (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

/* Seed common Residential sub-types */
INSERT INTO cr_project_subtypes (company_id, project_type_id, code, name, description, card_image_path, sort_order)
SELECT NULL, id, 'RES_APT', 'Apartment',
       'Standard residential apartment unit.',
       'assets/images/cr/project_subtypes/res-apartment.jpg',
       1
FROM cr_project_types WHERE code = 'RES';

INSERT INTO cr_project_subtypes (company_id, project_type_id, code, name, description, card_image_path, sort_order)
SELECT NULL, id, 'RES_DPX', 'Duplex',
       'Duplex / triplex type residential unit.',
       'assets/images/cr/project_subtypes/res-duplex.jpg',
       2
FROM cr_project_types WHERE code = 'RES';

INSERT INTO cr_project_subtypes (company_id, project_type_id, code, name, description, card_image_path, sort_order)
SELECT NULL, id, 'RES_VIL', 'Villa / Bungalow',
       'Independent villa or bungalow.',
       'assets/images/cr/project_subtypes/res-villa.jpg',
       3
FROM cr_project_types WHERE code = 'RES';

/* Sample Commercial / Hospitality sub-types */
INSERT INTO cr_project_subtypes (company_id, project_type_id, code, name, description, card_image_path, sort_order)
SELECT NULL, id, 'COM_OFF', 'Office / Corporate',
       'Corporate office interior.',
       'assets/images/cr/project_subtypes/com-office.jpg',
       1
FROM cr_project_types WHERE code = 'COM';

INSERT INTO cr_project_subtypes (company_id, project_type_id, code, name, description, card_image_path, sort_order)
SELECT NULL, id, 'COM_RET', 'Retail Showroom',
       'Retail / showroom interior.',
       'assets/images/cr/project_subtypes/com-retail.jpg',
       2
FROM cr_project_types WHERE code = 'COM';

INSERT INTO cr_project_subtypes (company_id, project_type_id, code, name, description, card_image_path, sort_order)
SELECT NULL, id, 'HOS_GUEST', 'Hotel Guest Room',
       'Standard hotel guest room.',
       'assets/images/cr/project_subtypes/hos-guest-room.jpg',
       1
FROM cr_project_types WHERE code = 'HOS';

/* ============================================================
   3) Spaces (Master Bed Room, Living, Dining, etc.)
   ============================================================ */

CREATE TABLE cr_spaces (
    id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id         BIGINT UNSIGNED NULL,
    project_subtype_id BIGINT UNSIGNED NOT NULL,
    code               VARCHAR(50)     NOT NULL,
    name               VARCHAR(150)    NOT NULL,
    description        TEXT            NULL,

    -- Image used in Step-1 / Step-2 "Spaces" cards
    card_image_path    VARCHAR(255)    NULL,

    default_quantity   INT UNSIGNED    NULL,
    default_area_sqft  DECIMAL(10,2)   NULL,
    is_active          TINYINT(1)      NOT NULL DEFAULT 1,
    sort_order         INT UNSIGNED    NOT NULL DEFAULT 0,
    created_at         DATETIME        NULL,
    updated_at         DATETIME        NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_cr_spaces_subtype_code (project_subtype_id, code),
    UNIQUE KEY uq_cr_spaces_subtype_name (project_subtype_id, name),

    CONSTRAINT fk_cr_spaces_company
        FOREIGN KEY (company_id)
        REFERENCES companies (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_cr_spaces_project_subtype
        FOREIGN KEY (project_subtype_id)
        REFERENCES cr_project_subtypes (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

/* Seed default spaces for Residential → Duplex (RES_DPX) */

SET @res_duplex_subtype_id := (
    SELECT id
    FROM cr_project_subtypes
    WHERE code = 'RES_DPX'
    LIMIT 1
);

INSERT INTO cr_spaces (
    company_id,
    project_subtype_id,
    code,
    name,
    description,
    card_image_path,
    default_quantity,
    default_area_sqft,
    is_active,
    sort_order
) VALUES
(NULL, @res_duplex_subtype_id, 'MASTER_BED',  'Master Bed Room',
 'Primary bedroom of the unit.',
 'assets/images/cr/spaces/master-bed-room.jpg',
 1, 220.00, 1, 1),

(NULL, @res_duplex_subtype_id, 'CHILD_BED',   'Child Bed Room',
 'Bedroom for child/teen.',
 'assets/images/cr/spaces/child-bed-room.jpg',
 1, 180.00, 1, 2),

(NULL, @res_duplex_subtype_id, 'LIVING',      'Living / Lounge',
 'Main living / lounge area.',
 'assets/images/cr/spaces/living-lounge.jpg',
 1, 260.00, 1, 3),

(NULL, @res_duplex_subtype_id, 'DINING',      'Dining',
 'Dedicated dining space.',
 'assets/images/cr/spaces/dining.jpg',
 1, 160.00, 1, 4),

(NULL, @res_duplex_subtype_id, 'KITCHEN',     'Kitchen',
 'Main kitchen space.',
 'assets/images/cr/spaces/kitchen.jpg',
 1, 140.00, 1, 5),

(NULL, @res_duplex_subtype_id, 'FAMILY_LIV',  'Family Living',
 'Informal family living / TV lounge.',
 'assets/images/cr/spaces/family-living.jpg',
 1, 200.00, 1, 6),

(NULL, @res_duplex_subtype_id, 'LOBBY',       'Entrance Lobby',
 'Entrance foyer / lobby area.',
 'assets/images/cr/spaces/entrance-lobby.jpg',
 1,  80.00, 1, 7),

(NULL, @res_duplex_subtype_id, 'TOILET_ATT',  'Attached Toilet',
 'Toilet attached to bedroom.',
 'assets/images/cr/spaces/attached-toilet.jpg',
 1,  60.00, 1, 8),

(NULL, @res_duplex_subtype_id, 'BALCONY',     'Balcony',
 'Balcony / terrace space.',
 'assets/images/cr/spaces/balcony.jpg',
 1,  50.00, 1, 9);

/* ============================================================
   4) Item Categories (Furniture, Lighting, etc.)
   ============================================================ */

CREATE TABLE cr_item_categories (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id      BIGINT UNSIGNED NULL,
    code            VARCHAR(50)     NOT NULL UNIQUE,
    name            VARCHAR(150)    NOT NULL,
    description     TEXT            NULL,

    -- Image used in Step-2 "Item Category" cards
    card_image_path VARCHAR(255)    NULL,

    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    sort_order      INT UNSIGNED    NOT NULL DEFAULT 0,
    created_at      DATETIME        NULL,
    updated_at      DATETIME        NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_cr_item_categories_company
        FOREIGN KEY (company_id)
        REFERENCES companies (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

INSERT INTO cr_item_categories (company_id, code, name, description, card_image_path, sort_order) VALUES
(NULL, 'FURN',        'Furniture',
 'All loose and built-in furniture items.',
 'assets/images/cr/categories/furniture.jpg', 1),
(NULL, 'LIGHT',       'Lighting',
 'Ceiling, wall, pendant and task lights.',
 'assets/images/cr/categories/lighting.jpg', 2),
(NULL, 'CURTAIN',     'Curtains & Fabrics',
 'Curtains, blinds, bed runners, cushions.',
 'assets/images/cr/categories/curtains-fabrics.jpg', 3),
(NULL, 'WALL_TREAT',  'Wall Treatment',
 'Paint, wallpaper and wall panels.',
 'assets/images/cr/categories/wall-treatment.jpg', 4),
(NULL, 'FLOOR_FIN',   'Floor Finish',
 'Tiles, laminate, wood flooring, rugs.',
 'assets/images/cr/categories/floor-finish.jpg', 5),
(NULL, 'CEILING',     'Ceiling Design',
 'False ceiling, coves, feature ceilings.',
 'assets/images/cr/categories/ceiling-design.jpg', 6),
(NULL, 'DECOR',       'Decor & Accessories',
 'Art, mirrors, decor accessories.',
 'assets/images/cr/categories/decor-accessories.jpg', 7);

/* ============================================================
   5) Item Sub-Categories (Double Bed, Wardrobe, Study Desk, etc.)
   ============================================================ */

CREATE TABLE cr_item_subcategories (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id      BIGINT UNSIGNED NULL,
    category_id     BIGINT UNSIGNED NOT NULL,
    code            VARCHAR(80)     NOT NULL,
    name            VARCHAR(180)    NOT NULL,
    description     TEXT            NULL,

    -- Image used in Step-2 "Sub-Category" cards
    card_image_path VARCHAR(255)    NULL,

    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    sort_order      INT UNSIGNED    NOT NULL DEFAULT 0,
    created_at      DATETIME        NULL,
    updated_at      DATETIME        NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_item_subcat_code (code),
    UNIQUE KEY uq_item_subcat_cat_name (category_id, name),
    CONSTRAINT fk_cr_item_subcategories_company
        FOREIGN KEY (company_id)
        REFERENCES companies (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_cr_item_subcategories_category
        FOREIGN KEY (category_id)
        REFERENCES cr_item_categories (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

/* Furniture sub-categories (aligned with current Step-2 HTML) */
INSERT INTO cr_item_subcategories (company_id, category_id, code, name, description, card_image_path, sort_order)
SELECT NULL, id,
       'FURN_DBED',
       'Double Person Bed',
       'King/Queen size double bed options.',
       'assets/images/cr/subcategories/double-bed.jpg',
       1
FROM cr_item_categories WHERE code = 'FURN';

INSERT INTO cr_item_subcategories (company_id, category_id, code, name, description, card_image_path, sort_order)
SELECT NULL, id,
       'FURN_SBEB',
       'Single Bed',
       'Single bed for child/guest rooms.',
       'assets/images/cr/subcategories/single-bed.jpg',
       2
FROM cr_item_categories WHERE code = 'FURN';

INSERT INTO cr_item_subcategories (company_id, category_id, code, name, description, card_image_path, sort_order)
SELECT NULL, id,
       'FURN_WARD',
       'Wardrobe',
       'Sliding, hinged and walk-in wardrobe modules.',
       'assets/images/cr/subcategories/wardrobe.jpg',
       3
FROM cr_item_categories WHERE code = 'FURN';

INSERT INTO cr_item_subcategories (company_id, category_id, code, name, description, card_image_path, sort_order)
SELECT NULL, id,
       'FURN_BSTBL',
       'Bedside Table',
       'Bedside tables with drawers/shelves.',
       'assets/images/cr/subcategories/bedside-table.jpg',
       4
FROM cr_item_categories WHERE code = 'FURN';

INSERT INTO cr_item_subcategories (company_id, category_id, code, name, description, card_image_path, sort_order)
SELECT NULL, id,
       'FURN_STDSK',
       'Study Desk',
       'Compact desks for reading and work.',
       'assets/images/cr/subcategories/study-desk.jpg',
       5
FROM cr_item_categories WHERE code = 'FURN';

/* Lighting sub-categories */
INSERT INTO cr_item_subcategories (company_id, category_id, code, name, description, card_image_path, sort_order)
SELECT NULL, id,
       'LIGHT_AMB_CEIL',
       'Ambient Ceiling Light',
       'Primary ceiling lights for general illumination.',
       'assets/images/cr/subcategories/ambient-ceiling-light.jpg',
       1
FROM cr_item_categories WHERE code = 'LIGHT';

INSERT INTO cr_item_subcategories (company_id, category_id, code, name, description, card_image_path, sort_order)
SELECT NULL, id,
       'LIGHT_WALL',
       'Wall Sconce',
       'Decorative wall-mounted lighting.',
       'assets/images/cr/subcategories/wall-sconce.jpg',
       2
FROM cr_item_categories WHERE code = 'LIGHT';

INSERT INTO cr_item_subcategories (company_id, category_id, code, name, description, card_image_path, sort_order)
SELECT NULL, id,
       'LIGHT_PEND',
       'Pendant Light',
       'Pendant fixtures over bed, dining or island.',
       'assets/images/cr/subcategories/pendant-light.jpg',
       3
FROM cr_item_categories WHERE code = 'LIGHT';

/* Curtains & Fabrics sub-categories */
INSERT INTO cr_item_subcategories (company_id, category_id, code, name, description, card_image_path, sort_order)
SELECT NULL, id,
       'CURT_WIN',
       'Window Curtain',
       'Full height curtains for windows/doors.',
       'assets/images/cr/subcategories/window-curtain.jpg',
       1
FROM cr_item_categories WHERE code = 'CURTAIN';

INSERT INTO cr_item_subcategories (company_id, category_id, code, name, description, card_image_path, sort_order)
SELECT NULL, id,
       'CURT_BLIND',
       'Roller Blind',
       'Roller / zebra blinds.',
       'assets/images/cr/subcategories/roller-blind.jpg',
       2
FROM cr_item_categories WHERE code = 'CURTAIN';

/* Wall Treatment sub-categories */
INSERT INTO cr_item_subcategories (company_id, category_id, code, name, description, card_image_path, sort_order)
SELECT NULL, id,
       'WALL_PAINT',
       'Paint Finish',
       'Standard and special paint finishes.',
       'assets/images/cr/subcategories/paint-finish.jpg',
       1
FROM cr_item_categories WHERE code = 'WALL_TREAT';

INSERT INTO cr_item_subcategories (company_id, category_id, code, name, description, card_image_path, sort_order)
SELECT NULL, id,
       'WALL_WPAPER',
       'Wallpaper',
       'Imported/local wallpaper.',
       'assets/images/cr/subcategories/wallpaper.jpg',
       2
FROM cr_item_categories WHERE code = 'WALL_TREAT';

INSERT INTO cr_item_subcategories (company_id, category_id, code, name, description, card_image_path, sort_order)
SELECT NULL, id,
       'WALL_PANEL',
       'Wall Paneling',
       'Wood/MDF/PU panel systems.',
       'assets/images/cr/subcategories/wall-paneling.jpg',
       3
FROM cr_item_categories WHERE code = 'WALL_TREAT';

/* ============================================================
   6) Product Library (Master – to be selected in requisitions)
   ============================================================ */

CREATE TABLE cr_products (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id        BIGINT UNSIGNED NULL,
    subcategory_id    BIGINT UNSIGNED NOT NULL,
    sku               VARCHAR(80)     NOT NULL,
    name              VARCHAR(255)    NOT NULL,
    short_description VARCHAR(500)    NULL,
    specification     TEXT            NULL,
    origin_country    VARCHAR(120)    NULL,
    default_tag       ENUM('preferred','standard','value','premium') NULL,
    default_qty       INT UNSIGNED    NOT NULL DEFAULT 1,

    -- Image used for product cards (Step-2)
    main_image_url    VARCHAR(500)    NULL,

    is_active         TINYINT(1)      NOT NULL DEFAULT 1,
    sort_order        INT UNSIGNED    NOT NULL DEFAULT 0,
    created_at        DATETIME        NULL,
    updated_at        DATETIME        NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cr_products_sku (sku),
    CONSTRAINT fk_cr_products_company
        FOREIGN KEY (company_id)
        REFERENCES companies (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_cr_products_subcategory
        FOREIGN KEY (subcategory_id)
        REFERENCES cr_item_subcategories (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

/* Sample products aligned with current Step-2 HTML (Double Person Bed) */

INSERT INTO cr_products (
    company_id,
    subcategory_id,
    sku,
    name,
    short_description,
    specification,
    origin_country,
    default_tag,
    default_qty,
    main_image_url,
    sort_order
)
SELECT
    NULL,
    s.id,
    'DB-AVN-180x200',
    'Avenue Solid Wood Double Bed',
    'Solid wood double bed with upholstered headboard.',
    'Solid oak + veneer; upholstered headboard; 180x200 cm bed size; recommended for master bed rooms.',
    'Malaysia',
    'preferred',
    1,
    'https://images.unsplash.com/photo-1545239351-1141bd82e8a6?auto=format&fit=crop&w=900&q=80',
    1
FROM cr_item_subcategories s
JOIN cr_item_categories c ON c.id = s.category_id
WHERE c.code = 'FURN' AND s.code = 'FURN_DBED'
LIMIT 1;

INSERT INTO cr_products (
    company_id,
    subcategory_id,
    sku,
    name,
    short_description,
    specification,
    origin_country,
    default_tag,
    default_qty,
    main_image_url,
    sort_order
)
SELECT
    NULL,
    s.id,
    'DB-LIN-160x200',
    'Linea Minimal Bed with Fabric Headboard',
    'Minimal bed frame with soft fabric headboard.',
    'MDF + veneer frame; fabric panel headboard; 160x200 cm; recommended for master/child bed rooms.',
    'Turkey',
    'standard',
    1,
    'https://images.unsplash.com/photo-1545239351-1141bd82e8a6?auto=format&fit=crop&w=900&q=80',
    2
FROM cr_item_subcategories s
JOIN cr_item_categories c ON c.id = s.category_id
WHERE c.code = 'FURN' AND s.code = 'FURN_DBED'
LIMIT 1;

INSERT INTO cr_products (
    company_id,
    subcategory_id,
    sku,
    name,
    short_description,
    specification,
    origin_country,
    default_tag,
    default_qty,
    main_image_url,
    sort_order
)
SELECT
    NULL,
    s.id,
    'DB-NOV-160x200',
    'Nova Storage Bed with Drawers',
    'Storage bed with four under-bed drawers.',
    'Engineered wood structure; 4 pull-out storage drawers; 160x200 cm; soft-close hardware.',
    'Bangladesh',
    'value',
    1,
    'https://images.unsplash.com/photo-1545239351-1141bd82e8a6?auto=format&fit=crop&w=900&q=80',
    3
FROM cr_item_subcategories s
JOIN cr_item_categories c ON c.id = s.category_id
WHERE c.code = 'FURN' AND s.code = 'FURN_DBED'
LIMIT 1;

/* ============================================================
   END OF PARAMETER / LOOKUP SCHEMA (ALL TABLES HAVE IMAGE FIELD + company_id)
   ============================================================ */

/* ============================================================
   Space → Category Mapping (SaaS / Company-scoped)
   Purpose: restrict which Item Categories are allowed for a Space
   Depends on existing:
     - companies(id)
     - cr_spaces(id)
     - cr_item_categories(id)
   ============================================================ */

CREATE TABLE cr_space_category_mappings (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id   BIGINT UNSIGNED NOT NULL,
    space_id     BIGINT UNSIGNED NOT NULL,
    category_id  BIGINT UNSIGNED NOT NULL,

    is_active    TINYINT(1)      NOT NULL DEFAULT 1,
    sort_order   INT UNSIGNED    NOT NULL DEFAULT 0,

    created_at   DATETIME        NULL,
    updated_at   DATETIME        NULL,

    PRIMARY KEY (id),

    -- Prevent duplicates per tenant
    UNIQUE KEY uq_space_category (company_id, space_id, category_id),

    -- Performance indexes for filtering UI
    KEY idx_company_space (company_id, space_id),
    KEY idx_company_cat   (company_id, category_id),
    KEY idx_space_cat     (space_id, category_id),

    CONSTRAINT fk_scmap_company
        FOREIGN KEY (company_id)
        REFERENCES companies (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_scmap_space
        FOREIGN KEY (space_id)
        REFERENCES cr_spaces (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_scmap_category
        FOREIGN KEY (category_id)
        REFERENCES cr_item_categories (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;


/* ============================================================
   INSERTs for cr_space_category_mappings
   Source: your uploaded trimatricsaas (27).sql
   Assumption rules (you can tweak later):
   - Most residential spaces: FURN, LIGHT, CURTAIN, WALL_TREAT, FLOOR_FIN, CEILING, DECOR
   - KITCHEN: no CURTAIN
   - TOILET_ATT: no FURN, no CURTAIN
   - BALCONY: no CURTAIN
   - Cabin space: uses category "CAB FUR" + common finishes (no CURTAIN)
   Company used: company_id = 1 (matches your dump)
   ============================================================ */

DELETE FROM cr_space_category_mappings
WHERE company_id = 1;

INSERT INTO cr_space_category_mappings
(company_id, space_id, category_id, is_active, sort_order, created_at, updated_at)
VALUES
-- MASTER_BED (space_id=1)
(1, 1, 1, 1, 1, NULL, NULL),
(1, 1, 2, 1, 2, NULL, NULL),
(1, 1, 3, 1, 3, NULL, NULL),
(1, 1, 4, 1, 4, NULL, NULL),
(1, 1, 5, 1, 5, NULL, NULL),
(1, 1, 6, 1, 6, NULL, NULL),
(1, 1, 7, 1, 7, NULL, NULL),

-- CHILD_BED (space_id=2)
(1, 2, 1, 1, 1, NULL, NULL),
(1, 2, 2, 1, 2, NULL, NULL),
(1, 2, 3, 1, 3, NULL, NULL),
(1, 2, 4, 1, 4, NULL, NULL),
(1, 2, 5, 1, 5, NULL, NULL),
(1, 2, 6, 1, 6, NULL, NULL),
(1, 2, 7, 1, 7, NULL, NULL),

-- LIVING (space_id=3)
(1, 3, 1, 1, 1, NULL, NULL),
(1, 3, 2, 1, 2, NULL, NULL),
(1, 3, 3, 1, 3, NULL, NULL),
(1, 3, 4, 1, 4, NULL, NULL),
(1, 3, 5, 1, 5, NULL, NULL),
(1, 3, 6, 1, 6, NULL, NULL),
(1, 3, 7, 1, 7, NULL, NULL),

-- DINING (space_id=4)
(1, 4, 1, 1, 1, NULL, NULL),
(1, 4, 2, 1, 2, NULL, NULL),
(1, 4, 3, 1, 3, NULL, NULL),
(1, 4, 4, 1, 4, NULL, NULL),
(1, 4, 5, 1, 5, NULL, NULL),
(1, 4, 6, 1, 6, NULL, NULL),
(1, 4, 7, 1, 7, NULL, NULL),

-- KITCHEN (space_id=5) [no CURTAIN]
(1, 5, 1, 1, 1, NULL, NULL),
(1, 5, 2, 1, 2, NULL, NULL),
(1, 5, 4, 1, 3, NULL, NULL),
(1, 5, 5, 1, 4, NULL, NULL),
(1, 5, 6, 1, 5, NULL, NULL),
(1, 5, 7, 1, 6, NULL, NULL),

-- FAMILY_LIV (space_id=6)
(1, 6, 1, 1, 1, NULL, NULL),
(1, 6, 2, 1, 2, NULL, NULL),
(1, 6, 3, 1, 3, NULL, NULL),
(1, 6, 4, 1, 4, NULL, NULL),
(1, 6, 5, 1, 5, NULL, NULL),
(1, 6, 6, 1, 6, NULL, NULL),
(1, 6, 7, 1, 7, NULL, NULL),

-- LOBBY (space_id=7)
(1, 7, 1, 1, 1, NULL, NULL),
(1, 7, 2, 1, 2, NULL, NULL),
(1, 7, 3, 1, 3, NULL, NULL),
(1, 7, 4, 1, 4, NULL, NULL),
(1, 7, 5, 1, 5, NULL, NULL),
(1, 7, 6, 1, 6, NULL, NULL),
(1, 7, 7, 1, 7, NULL, NULL),

-- TOILET_ATT (space_id=8) [no FURN, no CURTAIN]
(1, 8, 2, 1, 1, NULL, NULL),
(1, 8, 4, 1, 2, NULL, NULL),
(1, 8, 5, 1, 3, NULL, NULL),
(1, 8, 6, 1, 4, NULL, NULL),
(1, 8, 7, 1, 5, NULL, NULL),

-- BALCONY (space_id=9) [no CURTAIN]
(1, 9, 1, 1, 1, NULL, NULL),
(1, 9, 2, 1, 2, NULL, NULL),
(1, 9, 4, 1, 3, NULL, NULL),
(1, 9, 5, 1, 4, NULL, NULL),
(1, 9, 6, 1, 5, NULL, NULL),
(1, 9, 7, 1, 6, NULL, NULL),

-- Cabin (space_id=10) [uses category "CAB FUR" (category_id=8), no CURTAIN]
(1, 10, 8, 1, 1, NULL, NULL),
(1, 10, 2, 1, 2, NULL, NULL),
(1, 10, 4, 1, 3, NULL, NULL),
(1, 10, 5, 1, 4, NULL, NULL),
(1, 10, 6, 1, 5, NULL, NULL),
(1, 10, 7, 1, 6, NULL, NULL);
