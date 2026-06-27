-- Kolom untuk nama file PDF tiket & jejak pengiriman WhatsApp
ALTER TABLE tickets
    ADD COLUMN IF NOT EXISTS file_tiket VARCHAR(255) DEFAULT NULL AFTER paid_at,
    ADD COLUMN IF NOT EXISTS wa_sent_at DATETIME DEFAULT NULL AFTER file_tiket;
