-- Normalisasi status pembayaran ke format standar
ALTER TABLE `tickets`
  MODIFY `payment_status` VARCHAR(20) NOT NULL DEFAULT 'paid';

UPDATE `tickets` SET `payment_status` = 'paid'    WHERE `payment_status` IN ('LUNAS', '', 'paid') OR `payment_status` IS NULL;
UPDATE `tickets` SET `payment_status` = 'pending'  WHERE `payment_status` = 'MENUNGGU PEMBAYARAN';
UPDATE `tickets` SET `payment_status` = 'failed'  WHERE `payment_status` = 'GAGAL';
UPDATE `tickets` SET `payment_status` = 'expire'  WHERE `payment_status` = 'KADALUARSA';
