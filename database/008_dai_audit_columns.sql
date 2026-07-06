SET @database_name = DATABASE();

SET @tables = 'dai_fasilitas_komputasi,dai_komputasi_awan,dai_jaringan_intra,dai_software';

DROP PROCEDURE IF EXISTS add_dai_audit_columns;
DELIMITER //
CREATE PROCEDURE add_dai_audit_columns()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE table_name_value VARCHAR(64);
    DECLARE table_cursor CURSOR FOR
        SELECT TABLE_NAME
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = @database_name
          AND FIND_IN_SET(TABLE_NAME, @tables);
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN table_cursor;
    table_loop: LOOP
        FETCH table_cursor INTO table_name_value;
        IF done = 1 THEN LEAVE table_loop; END IF;

        IF NOT EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @database_name AND TABLE_NAME = table_name_value AND COLUMN_NAME = 'created_by'
        ) THEN
            SET @sql = CONCAT('ALTER TABLE `', table_name_value, '` ADD COLUMN created_by BIGINT UNSIGNED NULL');
            PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;
        END IF;
        IF NOT EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @database_name AND TABLE_NAME = table_name_value AND COLUMN_NAME = 'updated_by'
        ) THEN
            SET @sql = CONCAT('ALTER TABLE `', table_name_value, '` ADD COLUMN updated_by BIGINT UNSIGNED NULL AFTER created_by');
            PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;
        END IF;
        IF NOT EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @database_name AND TABLE_NAME = table_name_value AND COLUMN_NAME = 'created_at'
        ) THEN
            SET @sql = CONCAT('ALTER TABLE `', table_name_value, '` ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER updated_by');
            PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;
        END IF;
        IF NOT EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @database_name AND TABLE_NAME = table_name_value AND COLUMN_NAME = 'updated_at'
        ) THEN
            SET @sql = CONCAT('ALTER TABLE `', table_name_value, '` ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at');
            PREPARE statement FROM @sql; EXECUTE statement; DEALLOCATE PREPARE statement;
        END IF;
    END LOOP;
    CLOSE table_cursor;
END//
DELIMITER ;

CALL add_dai_audit_columns();
DROP PROCEDURE add_dai_audit_columns;
