SET @database_name = DATABASE();

DROP PROCEDURE IF EXISTS update_dai_main_table_fields;
DELIMITER //
CREATE PROCEDURE update_dai_main_table_fields()
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @database_name
          AND TABLE_NAME = 'dai_hardware_server'
          AND COLUMN_NAME = 'jenis_prosesor'
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @database_name
          AND TABLE_NAME = 'dai_hardware_server'
          AND COLUMN_NAME = 'kapasitas_prosesor'
    ) THEN
        ALTER TABLE dai_hardware_server
            CHANGE COLUMN jenis_prosesor kapasitas_prosesor VARCHAR(255) NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @database_name
          AND TABLE_NAME = 'dai_hardware_server'
          AND COLUMN_NAME = 'jenis_server'
    ) THEN
        ALTER TABLE dai_hardware_server
            ADD COLUMN jenis_server VARCHAR(20) NULL AFTER deskripsi;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @database_name
          AND TABLE_NAME = 'dai_hardware_storage'
          AND COLUMN_NAME = 'deskripsi'
    ) THEN
        ALTER TABLE dai_hardware_storage
            ADD COLUMN deskripsi TEXT NULL AFTER nama_storage;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @database_name
          AND TABLE_NAME = 'dai_hardware_storage'
          AND COLUMN_NAME = 'tipe'
    ) THEN
        ALTER TABLE dai_hardware_storage
            ADD COLUMN tipe VARCHAR(255) NULL AFTER deskripsi;
    END IF;
END//
DELIMITER ;

CALL update_dai_main_table_fields();
DROP PROCEDURE update_dai_main_table_fields;
