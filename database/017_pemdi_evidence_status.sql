UPDATE pemdi_evidence
SET status_upload = CASE
    WHEN file_upload IS NULL OR TRIM(file_upload) = '' THEN 'belum_diunggah'
    ELSE 'sudah_diunggah'
END;

ALTER TABLE pemdi_evidence
    MODIFY COLUMN skor DECIMAL(5,2) NULL,
    MODIFY COLUMN status_upload VARCHAR(50) NOT NULL DEFAULT 'belum_diunggah';
