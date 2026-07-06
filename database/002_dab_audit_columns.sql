SET @database_name = DATABASE();

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @database_name AND TABLE_NAME = 'dab' AND COLUMN_NAME = 'created_by'
    ),
    'DO 0',
    'ALTER TABLE dab ADD COLUMN created_by BIGINT UNSIGNED NULL AFTER realisasi'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @database_name AND TABLE_NAME = 'dab' AND COLUMN_NAME = 'updated_by'
    ),
    'DO 0',
    'ALTER TABLE dab ADD COLUMN updated_by BIGINT UNSIGNED NULL AFTER created_by'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @database_name AND TABLE_NAME = 'dab' AND COLUMN_NAME = 'created_at'
    ),
    'DO 0',
    'ALTER TABLE dab ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER updated_by'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @database_name AND TABLE_NAME = 'dab' AND COLUMN_NAME = 'updated_at'
    ),
    'DO 0',
    'ALTER TABLE dab ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

UPDATE dab d
JOIN audit_logs audit_create ON audit_create.id = (
    SELECT candidate.id
    FROM audit_logs candidate
    WHERE candidate.table_name = 'dab'
      AND candidate.record_id = d.id
      AND candidate.action = 'create'
    ORDER BY candidate.created_at ASC, candidate.id ASC
    LIMIT 1
)
SET
    d.created_at = IF(d.created_by IS NULL, audit_create.created_at, d.created_at),
    d.created_by = COALESCE(d.created_by, audit_create.user_id);

UPDATE dab d
JOIN audit_logs audit_latest ON audit_latest.id = (
    SELECT candidate.id
    FROM audit_logs candidate
    WHERE candidate.table_name = 'dab'
      AND candidate.record_id = d.id
      AND candidate.action IN ('create', 'update')
    ORDER BY candidate.created_at DESC, candidate.id DESC
    LIMIT 1
)
SET
    d.updated_at = IF(d.updated_by IS NULL, audit_latest.created_at, d.updated_at),
    d.updated_by = COALESCE(d.updated_by, audit_latest.user_id);
