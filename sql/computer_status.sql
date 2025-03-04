CREATE TABLE `computer_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `laboratory` varchar(10) NOT NULL,
  `pc_number` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'available',
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `lab_pc` (`laboratory`, `pc_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
