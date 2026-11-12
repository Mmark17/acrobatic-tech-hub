-- Add SolidWorks and KiCad courses to existing installations.
-- A NULL fee is displayed as "Fee to be confirmed".
ALTER TABLE trainings
    MODIFY fee DECIMAL(10,2) NULL DEFAULT NULL;

INSERT INTO trainings (title, description, duration, fee, start_date)
SELECT 'SolidWorks for Mechanical Engineers',
       'Learn parametric sketching, 3D part modelling, assemblies and engineering drawings through a practical mechanical design project.',
       '6 weeks', NULL, NULL
WHERE NOT EXISTS (
    SELECT 1 FROM trainings WHERE title = 'SolidWorks for Mechanical Engineers'
);

INSERT INTO trainings (title, description, duration, fee, start_date)
SELECT 'KiCad PCB Design',
       'Create circuit schematics, assign footprints, lay out a printed circuit board, run design-rule checks and prepare manufacturing files.',
       '4 weeks', NULL, NULL
WHERE NOT EXISTS (
    SELECT 1 FROM trainings WHERE title = 'KiCad PCB Design'
);
