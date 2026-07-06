SET @database_name = DATABASE();

DROP PROCEDURE IF EXISTS drop_daa_program;
DELIMITER //
CREATE PROCEDURE drop_daa_program()
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.REFERENTIAL_CONSTRAINTS
        WHERE BINARY CONSTRAINT_SCHEMA = BINARY @database_name
          AND TABLE_NAME = 'daa'
          AND CONSTRAINT_NAME = 'fk_daa_program'
    ) THEN
        ALTER TABLE daa DROP FOREIGN KEY fk_daa_program;
    END IF;

    IF EXISTS (
        SELECT 1
        FROM information_schema.STATISTICS
        WHERE BINARY TABLE_SCHEMA = BINARY @database_name
          AND TABLE_NAME = 'daa'
          AND INDEX_NAME = 'idx_daa_program'
    ) THEN
        ALTER TABLE daa DROP INDEX idx_daa_program;
    END IF;

    IF EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE BINARY TABLE_SCHEMA = BINARY @database_name
          AND TABLE_NAME = 'daa'
          AND COLUMN_NAME = 'id_program'
    ) THEN
        ALTER TABLE daa DROP COLUMN id_program;
    END IF;
END//
DELIMITER ;

CALL drop_daa_program();
DROP PROCEDURE drop_daa_program;
