CREATE TABLE daa_dad (
    id INT NOT NULL AUTO_INCREMENT,
    id_daa INT NOT NULL,
    id_dad INT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_daa_dad (id_daa, id_dad),
    KEY idx_daa_dad_daa (id_daa),
    KEY idx_daa_dad_dad (id_dad),
    CONSTRAINT fk_daa_dad_daa
        FOREIGN KEY (id_daa) REFERENCES daa (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_daa_dad_dad
        FOREIGN KEY (id_dad) REFERENCES dad (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE daa_dal (
    id INT NOT NULL AUTO_INCREMENT,
    id_daa INT NOT NULL,
    id_dal INT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_daa_dal (id_daa, id_dal),
    KEY idx_daa_dal_daa (id_daa),
    KEY idx_daa_dal_dal (id_dal),
    CONSTRAINT fk_daa_dal_daa
        FOREIGN KEY (id_daa) REFERENCES daa (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_daa_dal_dal
        FOREIGN KEY (id_dal) REFERENCES dal (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE daa_dai_splp (
    id INT NOT NULL AUTO_INCREMENT,
    id_daa INT NOT NULL,
    id_dai_splp INT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_daa_dai_splp (id_daa, id_dai_splp),
    KEY idx_daa_dai_splp_daa (id_daa),
    KEY idx_daa_dai_splp_splp (id_dai_splp),
    CONSTRAINT fk_daa_dai_splp_daa
        FOREIGN KEY (id_daa) REFERENCES daa (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_daa_dai_splp_splp
        FOREIGN KEY (id_dai_splp) REFERENCES dai_splp (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE daa
    DROP FOREIGN KEY fk_daa_dad,
    DROP FOREIGN KEY fk_daa_dal,
    DROP FOREIGN KEY fk_daa_supplier_data,
    DROP FOREIGN KEY fk_daa_luaran_data,
    DROP FOREIGN KEY fk_daa_customer_data,
    DROP COLUMN id_dad,
    DROP COLUMN id_dal,
    DROP COLUMN id_dai_splp,
    DROP COLUMN id_supplier_data,
    DROP COLUMN id_luaran_data,
    DROP COLUMN id_customer_data;

DROP TABLE daa_inputan_data;
DROP TABLE daa_unit_kerja_pengembang;
