-- =========================================
-- Insert City Corporations into `upazilas`
-- kind='CITY_CORPORATION', names include "City Corporation"
-- Uses your existing triggers to fill upa_no/short_code
-- All-or-nothing transaction
-- =========================================
START TRANSACTION;

-- Dhaka District (id=18)
INSERT INTO upazilas (district_id, name, kind, status)
SELECT 18, 'Dhaka North City Corporation', 'CITY_CORPORATION', 1
WHERE NOT EXISTS (
  SELECT 1 FROM upazilas u WHERE u.district_id=18 AND u.name='Dhaka North City Corporation'
);

INSERT INTO upazilas (district_id, name, kind, status)
SELECT 18, 'Dhaka South City Corporation', 'CITY_CORPORATION', 1
WHERE NOT EXISTS (
  SELECT 1 FROM upazilas u WHERE u.district_id=18 AND u.name='Dhaka South City Corporation'
);

-- Chattogram District (id=7)
INSERT INTO upazilas (district_id, name, kind, status)
SELECT 7, 'Chattogram City Corporation', 'CITY_CORPORATION', 1
WHERE NOT EXISTS (
  SELECT 1 FROM upazilas u WHERE u.district_id=7 AND u.name='Chattogram City Corporation'
);

-- Cumilla District (id=9)
INSERT INTO upazilas (district_id, name, kind, status)
SELECT 9, 'Cumilla City Corporation', 'CITY_CORPORATION', 1
WHERE NOT EXISTS (
  SELECT 1 FROM upazilas u WHERE u.district_id=9 AND u.name='Cumilla City Corporation'
);

-- Gazipur District (id=20)
INSERT INTO upazilas (district_id, name, kind, status)
SELECT 20, 'Gazipur City Corporation', 'CITY_CORPORATION', 1
WHERE NOT EXISTS (
  SELECT 1 FROM upazilas u WHERE u.district_id=20 AND u.name='Gazipur City Corporation'
);

-- Narayanganj District (id=26)
INSERT INTO upazilas (district_id, name, kind, status)
SELECT 26, 'Narayanganj City Corporation', 'CITY_CORPORATION', 1
WHERE NOT EXISTS (
  SELECT 1 FROM upazilas u WHERE u.district_id=26 AND u.name='Narayanganj City Corporation'
);

-- Khulna District (id=31)
INSERT INTO upazilas (district_id, name, kind, status)
SELECT 31, 'Khulna City Corporation', 'CITY_CORPORATION', 1
WHERE NOT EXISTS (
  SELECT 1 FROM upazilas u WHERE u.district_id=31 AND u.name='Khulna City Corporation'
);

-- Rajshahi District (id=45)
INSERT INTO upazilas (district_id, name, kind, status)
SELECT 45, 'Rajshahi City Corporation', 'CITY_CORPORATION', 1
WHERE NOT EXISTS (
  SELECT 1 FROM upazilas u WHERE u.district_id=45 AND u.name='Rajshahi City Corporation'
);

-- Sylhet District (id=61)
INSERT INTO upazilas (district_id, name, kind, status)
SELECT 61, 'Sylhet City Corporation', 'CITY_CORPORATION', 1
WHERE NOT EXISTS (
  SELECT 1 FROM upazilas u WHERE u.district_id=61 AND u.name='Sylhet City Corporation'
);

-- Barishal District (id=1)
INSERT INTO upazilas (district_id, name, kind, status)
SELECT 1, 'Barishal City Corporation', 'CITY_CORPORATION', 1
WHERE NOT EXISTS (
  SELECT 1 FROM upazilas u WHERE u.district_id=1 AND u.name='Barishal City Corporation'
);

-- Rangpur District (id=53)
INSERT INTO upazilas (district_id, name, kind, status)
SELECT 53, 'Rangpur City Corporation', 'CITY_CORPORATION', 1
WHERE NOT EXISTS (
  SELECT 1 FROM upazilas u WHERE u.district_id=53 AND u.name='Rangpur City Corporation'
);

-- Mymensingh District (id=41)
INSERT INTO upazilas (district_id, name, kind, status)
SELECT 41, 'Mymensingh City Corporation', 'CITY_CORPORATION', 1
WHERE NOT EXISTS (
  SELECT 1 FROM upazilas u WHERE u.district_id=41 AND u.name='Mymensingh City Corporation'
);

COMMIT;
