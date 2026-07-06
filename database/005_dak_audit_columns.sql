DROP PROCEDURE IF EXISTS ensure_dak_audit_columns;
DELIMITER //
CREATE PROCEDURE ensure_dak_audit_columns(IN target_table VARCHAR(64))
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = target_table AND COLUMN_NAME = 'created_by'
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', target_table, '` ADD COLUMN created_by BIGINT UNSIGNED NULL');
        PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = target_table AND COLUMN_NAME = 'updated_by'
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', target_table, '` ADD COLUMN updated_by BIGINT UNSIGNED NULL');
        PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = target_table AND COLUMN_NAME = 'created_at'
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', target_table, '` ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = target_table AND COLUMN_NAME = 'updated_at'
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', target_table, '` ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;
    END IF;
END//
DELIMITER ;

CALL ensure_dak_audit_columns('dak_audit_keamanan');
CALL ensure_dak_audit_columns('dak_edukasi_kesadaran');
CALL ensure_dak_audit_columns('dak_identifikasi_kerentanan');
CALL ensure_dak_audit_columns('dak_kelaikan_keamanan');
CALL ensure_dak_audit_columns('dak_penanganan_insiden');
CALL ensure_dak_audit_columns('dak_peningkatan_keamanan');
CALL ensure_dak_audit_columns('dak_standar_keamanan');

DROP PROCEDURE ensure_dak_audit_columns;
