SET @database_name = DATABASE();

SET @sql = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='daa' AND COLUMN_NAME='created_by'),
    'DO 0', 'ALTER TABLE daa ADD COLUMN created_by BIGINT UNSIGNED NULL AFTER id_dak_standar_keamanan'
);
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @sql = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='daa' AND COLUMN_NAME='updated_by'),
    'DO 0', 'ALTER TABLE daa ADD COLUMN updated_by BIGINT UNSIGNED NULL AFTER created_by'
);
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @sql = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='daa' AND COLUMN_NAME='created_at'),
    'DO 0', 'ALTER TABLE daa ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER updated_by'
);
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;

SET @sql = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@database_name AND TABLE_NAME='daa' AND COLUMN_NAME='updated_at'),
    'DO 0', 'ALTER TABLE daa ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at'
);
PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;
