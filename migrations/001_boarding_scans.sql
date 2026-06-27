-- Migrasi: tabel boarding_scans untuk scan Tiket A (verifikasi) & Tiket B (checkout)
-- Jalankan di database tiketkapal

CREATE TABLE IF NOT EXISTS `boarding_scans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `pax_no` int(11) NOT NULL COMMENT 'Nomor urut penumpang (1, 2, 3, ...)',
  `lane` enum('A','B') NOT NULL COMMENT 'A = Verifikasi, B = Checkout',
  `scanned_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_scan` (`ticket_id`,`pax_no`,`lane`),
  KEY `fk_boarding_ticket` (`ticket_id`),
  CONSTRAINT `fk_boarding_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id_ticket`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
