SET @database_name = DATABASE();

DROP PROCEDURE IF EXISTS drop_peripheral_server_storage;
DELIMITER //
CREATE PROCEDURE drop_peripheral_server_storage()
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE BINARY TABLE_SCHEMA = BINARY @database_name
          AND TABLE_NAME = 'dai_hardware_periferal'
          AND COLUMN_NAME = 'id_dai_hardware_storage'
    ) THEN
        ALTER TABLE dai_hardware_periferal
            DROP COLUMN id_dai_hardware_storage;
    END IF;

    IF EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE BINARY TABLE_SCHEMA = BINARY @database_name
          AND TABLE_NAME = 'dai_hardware_periferal'
          AND COLUMN_NAME = 'id_dai_hardware_server'
    ) THEN
        ALTER TABLE dai_hardware_periferal
            DROP COLUMN id_dai_hardware_server;
    END IF;
END//
DELIMITER ;

CALL drop_peripheral_server_storage();
DROP PROCEDURE drop_peripheral_server_storage;
