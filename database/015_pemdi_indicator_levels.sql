SET @database_name = DATABASE();

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @database_name AND TABLE_NAME = 'indikator' AND COLUMN_NAME = 'id_skpd'
    ),
    'DO 0',
    'ALTER TABLE indikator ADD COLUMN id_skpd INT NULL AFTER id_aspek'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @database_name AND TABLE_NAME = 'indikator' AND COLUMN_NAME = 'deskripsi_indikator'
    ),
    'DO 0',
    'ALTER TABLE indikator ADD COLUMN deskripsi_indikator TEXT NULL AFTER nama_indikator'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @database_name AND TABLE_NAME = 'indikator' AND COLUMN_NAME = 'koordinator'
    ),
    'DO 0',
    'ALTER TABLE indikator ADD COLUMN koordinator VARCHAR(255) NULL AFTER deskripsi_indikator'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = @database_name AND TABLE_NAME = 'indikator' AND INDEX_NAME = 'idx_indikator_skpd'
    ),
    'DO 0',
    'ALTER TABLE indikator ADD KEY idx_indikator_skpd (id_skpd)'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = @database_name AND TABLE_NAME = 'indikator' AND CONSTRAINT_NAME = 'fk_indikator_skpd'
    ),
    'DO 0',
    'ALTER TABLE indikator ADD CONSTRAINT fk_indikator_skpd FOREIGN KEY (id_skpd) REFERENCES skpd (id) ON DELETE RESTRICT ON UPDATE CASCADE'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

CREATE TABLE IF NOT EXISTS pemdi_level (
    id INT NOT NULL AUTO_INCREMENT,
    id_indikator INT NOT NULL,
    level INT NOT NULL,
    deskripsi TEXT NULL,
    kriteria TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pemdi_level_indikator_level (id_indikator, level),
    KEY idx_pemdi_level_indikator (id_indikator),
    CONSTRAINT fk_pemdi_level_indikator FOREIGN KEY (id_indikator) REFERENCES indikator (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pemdi_level_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_pemdi_level_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pemdi_evidence (
    id INT NOT NULL AUTO_INCREMENT,
    id_pemdi_level INT NOT NULL,
    nama_dokumen VARCHAR(255) NOT NULL,
    skor DECIMAL(8,2) NULL,
    file_upload VARCHAR(500) NULL,
    status_upload VARCHAR(50) NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pemdi_evidence_level (id_pemdi_level),
    CONSTRAINT fk_pemdi_evidence_level FOREIGN KEY (id_pemdi_level) REFERENCES pemdi_level (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pemdi_evidence_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_pemdi_evidence_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
