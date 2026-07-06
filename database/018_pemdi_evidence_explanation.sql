SET @database_name = DATABASE();

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @database_name
          AND TABLE_NAME = 'pemdi_evidence'
          AND COLUMN_NAME = 'penjelasan'
    ),
    'DO 0',
    'ALTER TABLE pemdi_evidence ADD COLUMN penjelasan TEXT NULL AFTER skor'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;
