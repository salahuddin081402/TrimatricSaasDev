START TRANSACTION;

/* ======================================================================
   A) Tasks_Param  (unique on: Task_Param_Name + Module)
   ====================================================================== */
INSERT INTO Tasks_Param
(Task_Param_Name, Module, Type, Is_Client_Approval_Required, status, created_by, updated_by, created_at, updated_at)
VALUES
('Estimate Design Cost - Input',         'Estimate Design Cost', 'Inputter',  'N', 1, 1, 1, NOW(), NOW()),
('Estimate Design Cost - Approve',       'Estimate Design Cost', 'Approver',  'Y', 1, 1, 1, NOW(), NOW()),
('2D - Input',                          '2D',                  'Inputter',  'N', 1, 1, 1, NOW(), NOW()),
('2D - Approve',                        '2D',                  'Approver',  'Y', 1, 1, 1, NOW(), NOW()),
('3D - Input',                          '3D',                  'Inputter',  'N', 1, 1, 1, NOW(), NOW()),
('3D - Approve',                        '3D',                  'Approver',  'Y', 1, 1, 1, NOW(), NOW()),
('BOQ - Input',                         'BOQ',                 'Inputter',  'N', 1, 1, 1, NOW(), NOW()),
('BOQ - Approve',                       'BOQ',                  'Approver',  'Y', 1, 1, 1, NOW(), NOW()),
('Payment Schedule - Input',            'Payment Schedule',    'Inputter',  'N', 1, 1, 1, NOW(), NOW()),
('Payment Schedule - Approve',          'Payment Schedule',    'Approver',  'Y', 1, 1, 1, NOW(), NOW()),
('Vendor PO Issue - Input',             'Vendor PO Issue',     'Inputter',  'N', 1, 1, 1, NOW(), NOW()),
('Vendor PO Issue - Approve',            'Vendor PO Issue',    'Approver',  'N', 1, 1, 1, NOW(), NOW()),
('Vendor Payment',                       'Vendor Payment',     'Inputter',  'N', 1, 1, 1, NOW(), NOW()),
('Cross Check Client Payment',           'Client Payment History',     'Inputter',  'N', 1, 1, 1, NOW(), NOW()),
('Client Handover Package Review',      'Project',             'Inputter',  'Y', 1, 1, 1, NOW(), NOW());

/* ======================================================================
   B) skills  (unique on: skill)
   - Blend of architecture/interior/project/IT competencies
   ====================================================================== */
INSERT INTO skills
(skill, status, created_by, updated_by, created_at, updated_at)
VALUES
('Architectural Drafting', 1, 1, 1, NOW(), NOW()),
('BIM Coordination',       1, 1, 1, NOW(), NOW()),
('Interior Material Specs',1, 1, 1, NOW(), NOW()),
('Quantity Surveying (BOQ)',1,1,1,NOW(),NOW()),
('2D',1,1,1,NOW(),NOW()),
('3D',1,1,1,NOW(),NOW()),
('Cost Estimation',        1, 1, 1, NOW(), NOW()),
('Site Supervision',       1, 1, 1, NOW(), NOW()),
('Project Scheduling',     1, 1, 1, NOW(), NOW()),
('Client Communication',   1, 1, 1, NOW(), NOW()),
('Graphic Visualization',  1, 1, 1, NOW(), NOW()),
('Data Reporting (Power BI)',1,1,1,NOW(),NOW()),
('SQL Basics',             1, 1, 1, NOW(), NOW()),
('JavaScript Basics',      1, 1, 1, NOW(), NOW()),
('QA/QC Procedures',       1, 1, 1, NOW(), NOW()),
('MEP Coordination',       1, 1, 1, NOW(), NOW()),
('HSE Awareness',          1, 1, 1, NOW(), NOW());

/* ======================================================================
   C) Professions  (unique on: profession)
   ====================================================================== */
INSERT INTO Professions
(profession, status, created_by, updated_by, created_at, updated_at)
VALUES
('Architect',           1, 1, 1, NOW(), NOW()),
('Interior Designer',   1, 1, 1, NOW(), NOW()),
('BIM Modeler',         1, 1, 1, NOW(), NOW()),
('3D Visualizer',       1, 1, 1, NOW(), NOW()),
('Project Manager',     1, 1, 1, NOW(), NOW()),
('Site Engineer',       1, 1, 1, NOW(), NOW()),
('Quantity Surveyor',   1, 1, 1, NOW(), NOW()),
('MEP Coordinator',     1, 1, 1, NOW(), NOW()),
('Landscape Architect', 1, 1, 1, NOW(), NOW()),
('Urban Planner',       1, 1, 1, NOW(), NOW()),
('CAD Technician',      1, 1, 1, NOW(), NOW()),
('Procurement Officer', 1, 1, 1, NOW(), NOW()),
('QA/QC Engineer',      1, 1, 1, NOW(), NOW()),
('IT Support Engineer', 1, 1, 1, NOW(), NOW());

/* ======================================================================
   D) Software_List  (unique on: software_name)
   - Architecture/Interior + PM + Visualization + Analytics
   ====================================================================== */
INSERT INTO Software_List
(software_name, status, created_by, updated_by, created_at, updated_at)
VALUES
('AutoCAD',                1, 1, 1, NOW(), NOW()),
('Revit',                  1, 1, 1, NOW(), NOW()),
('SketchUp',               1, 1, 1, NOW(), NOW()),
('3ds Max',                1, 1, 1, NOW(), NOW()),
('Lumion',                 1, 1, 1, NOW(), NOW()),
('Enscape',                1, 1, 1, NOW(), NOW()),
('Rhino',                  1, 1, 1, NOW(), NOW()),
('Grasshopper',            1, 1, 1, NOW(), NOW()),
('Navisworks',             1, 1, 1, NOW(), NOW()),
('Autodesk BIM 360',       1, 1, 1, NOW(), NOW()),
('Bluebeam Revu',          1, 1, 1, NOW(), NOW()),
('Primavera P6',           1, 1, 1, NOW(), NOW()),
('Microsoft Project',      1, 1, 1, NOW(), NOW()),
('Power BI',               1, 1, 1, NOW(), NOW()),
('Tableau',                1, 1, 1, NOW(), NOW()),
('Adobe Photoshop',        1, 1, 1, NOW(), NOW()),
('Adobe Illustrator',      1, 1, 1, NOW(), NOW()),
('Adobe InDesign',         1, 1, 1, NOW(), NOW());

/* ======================================================================
   E) Company_Reg_Keys  (PK: Company_id + reg_key; must be unique)
   - Realistic “digital keys” (not passwords), status=1
   ====================================================================== */
INSERT INTO Company_Reg_Keys
(Company_id, reg_key, status, created_by, updated_by, created_at, updated_at)
VALUES
(1, 'CO1-7b3dacf4c2e941d8a9f13c87b1f0c2ab', 1, 1, 1, NOW(), NOW()),
(1, 'CO1-b8e4a197c0f24f1e8e2c4b36a5de9f01', 1, 1, 1, NOW(), NOW()),
(1, 'CO1-4fd2e9c3a7b54d2c9e13fab8d9170c5e', 1, 1, 1, NOW(), NOW()),
(1, 'CO1-fc01a2b3c4d596877889aabbccddeeff', 1, 1, 1, NOW(), NOW()),
(1, 'CO1-98f1a6e2d7c349f8b1a0ce34a9d2bb70', 1, 1, 1, NOW(), NOW()),
(1, 'CO1-1a2b3c4d5e6f77889900aabbccddeeff', 1, 1, 1, NOW(), NOW()),
(1, 'CO1-3e7a9c5b12de40a6b8f2c1d3e4f5a6b7', 1, 1, 1, NOW(), NOW()),
(1, 'CO1-6f5e4d3c2b1a00998877665544332211', 1, 1, 1, NOW(), NOW());

/* ======================================================================
   F) Training_Category (Company-scoped; unique on: Company_id + Training_Category_Name)
   - IDs are company-local; pick small positive integers
   ====================================================================== */
INSERT INTO Training_Category
(Company_id, Training_Category_Id, Training_Category_Name, status, created_by, updated_by, created_at, updated_at)
VALUES
(1, 1, 'Architecture Fundamentals',  1, 1, 1, NOW(), NOW()),
(1, 2, 'Interior Design',            1, 1, 1, NOW(), NOW()),
(1, 3, 'BIM & Coordination',         1, 1, 1, NOW(), NOW()),
(1, 4, 'Project Management',         1, 1, 1, NOW(), NOW()),
(1, 5, 'HSE & Site Safety',          1, 1, 1, NOW(), NOW()),
(1, 6, 'Software Tools',             1, 1, 1, NOW(), NOW()),
(1, 7, 'Quality & Compliance',       1, 1, 1, NOW(), NOW()),
(1, 8, 'Soft Skills',                1, 1, 1, NOW(), NOW());

/* ======================================================================
   G) Training_list (PK: Company_id + Training_Category_Id + Training_ID)
   - UNIQUE (Company_id, Training_Name) → ensure Training_Name is globally unique per company
   - Keep Training_IDs unique within their category
   ====================================================================== */
INSERT INTO Training_list
(Company_id, Training_Category_Id, Training_ID, Training_Name, Description, status, created_by, updated_by, created_at, updated_at)
VALUES
-- Architecture Fundamentals (cat=1)
(1, 1, 1, 'Architectural Drawing Standards', 'Line weights, scales, and documentation best practices', 1, 1, 1, NOW(), NOW()),
(1, 1, 2, 'Design Principles & Codes',       'Local code awareness and design principles overview',   1, 1, 1, NOW(), NOW()),

-- Interior Design (cat=2)
(1, 2, 1, 'Material & Finish Specifications', 'Interior materials, finishes, sustainability basics', 1, 1, 1, NOW(), NOW()),
(1, 2, 2, 'Lighting Design Basics',           'Lumens, color temperature, and fixtures selection',   1, 1, 1, NOW(), NOW()),

-- BIM & Coordination (cat=3)
(1, 3, 1, 'BIM Execution Planning',          'BEP essentials, model setup, and collaboration',     1, 1, 1, NOW(), NOW()),
(1, 3, 2, 'Clash Detection & Navisworks',    'Set detection rules, review, and coordination flow', 1, 1, 1, NOW(), NOW()),

-- Project Management (cat=4)
(1, 4, 1, 'Scheduling with MS Project',      'WBS, dependencies, resource basics',                 1, 1, 1, NOW(), NOW()),
(1, 4, 2, 'Cost Control & BOQ Basics',       'Budget tracking, variations, and reporting',         1, 1, 1, NOW(), NOW()),

-- HSE & Site Safety (cat=5)
(1, 5, 1, 'Site Safety Induction',           'PPE, toolbox talks, hazard identification',          1, 1, 1, NOW(), NOW()),
(1, 5, 2, 'Quality & Safety Audits',         'Checklists, NCRs, and corrective actions',           1, 1, 1, NOW(), NOW()),

-- Software Tools (cat=6)
(1, 6, 1, 'Revit for Architects',            'Families, views, sheets, and annotations',           1, 1, 1, NOW(), NOW()),
(1, 6, 2, 'AutoCAD Productivity',            'Templates, blocks, xrefs, and best practices',       1, 1, 1, NOW(), NOW()),
(1, 6, 3, 'Power BI for Reporting',          'Data modeling, visuals, and sharing dashboards',     1, 1, 1, NOW(), NOW()),

-- Quality & Compliance (cat=7)
(1, 7, 1, 'QA/QC for Architecture',          'Inspection plans, ITPs, and documentation control',  1, 1, 1, NOW(), NOW()),
(1, 7, 2, 'Documentation & Submittals',      'Submittal logs, RFIs, and revision management',      1, 1, 1, NOW(), NOW()),

-- Soft Skills (cat=8)
(1, 8, 1, 'Client Communication Essentials', 'Stakeholder mapping, meeting notes, approvals',      1, 1, 1, NOW(), NOW()),
(1, 8, 2, 'Presentation & Visualization',    'Storyboarding, visual hierarchy, and delivery',      1, 1, 1, NOW(), NOW());

COMMIT;
