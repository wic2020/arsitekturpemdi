SET @database_name = DATABASE();

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @database_name AND TABLE_NAME = 'indikator' AND COLUMN_NAME = 'bobot'
    ),
    'ALTER TABLE indikator MODIFY COLUMN bobot DECIMAL(5,2) NOT NULL DEFAULT 0',
    'ALTER TABLE indikator ADD COLUMN bobot DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER koordinator'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;
