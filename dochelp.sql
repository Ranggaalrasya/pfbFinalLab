-- Database: dochelp_db
-- Assignment: COSC6050 Programming for Business (Lab Final Project)
-- System: DocHelp - Doctor Appointment Booking & Management System

CREATE DATABASE IF NOT EXISTS `dochelp_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `dochelp_db`;

-- --------------------------------------------------------
-- Table: specialties
-- --------------------------------------------------------
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `appointments`;
DROP TABLE IF EXISTS `schedules`;
DROP TABLE IF EXISTS `doctors`;
DROP TABLE IF EXISTS `specialties`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT(10) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `phone_number` VARCHAR(20) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'Member',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `specialties` (
  `id` INT(10) NOT NULL AUTO_INCREMENT,
  `specialty_name` VARCHAR(80) NOT NULL UNIQUE,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `doctors` (
  `id` INT(10) NOT NULL AUTO_INCREMENT,
  `doctor_name` VARCHAR(100) NOT NULL UNIQUE,
  `specialty_id` INT(10) NOT NULL,
  `bio` VARCHAR(200) DEFAULT NULL,
  `price_rate` FLOAT(10) NOT NULL,
  `rating` FLOAT(10) NOT NULL DEFAULT 5.0,
  `clinic_location` VARCHAR(150) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_doctor_specialty` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `schedules` (
  `id` INT(10) NOT NULL AUTO_INCREMENT,
  `doctor_id` INT(10) NOT NULL,
  `day_of_week` VARCHAR(10) NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_schedule_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `appointments` (
  `id` INT(10) NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) NOT NULL,
  `doctor_id` INT(10) NOT NULL,
  `appointment_date` DATE NOT NULL,
  `time_slot` TIME NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'Waiting',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_appointment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appointment_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `payments` (
  `id` INT(10) NOT NULL AUTO_INCREMENT,
  `appointment_id` INT(10) NOT NULL UNIQUE,
  `amount` FLOAT(10) NOT NULL,
  `proof_image` VARCHAR(255) DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_payment_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Sample / Seed Data
-- --------------------------------------------------------

-- Users: Default Admin and Member ('devon')
-- Password for both is: password123
INSERT INTO `users` (`id`, `name`, `email`, `phone_number`, `password`, `role`) VALUES
(1, 'Admin', 'admin@gmail.com', '081234567890', '$2y$10$FGS2LPKCJ3cGdsLNpSB2t.6G.oVgpQewiaT9mZIOMjV/dmgyWpmG6', 'Admin'),
(2, 'devon', 'devon@gmail.com', '081298765432', '$2y$10$FGS2LPKCJ3cGdsLNpSB2t.6G.oVgpQewiaT9mZIOMjV/dmgyWpmG6', 'Member');

-- Specialties
INSERT INTO `specialties` (`id`, `specialty_name`) VALUES
(1, 'General'),
(2, 'Dental'),
(3, 'Pediatrics'),
(4, 'Cardiology'),
(5, 'Dermatology');

-- Doctors
INSERT INTO `doctors` (`id`, `doctor_name`, `specialty_id`, `bio`, `price_rate`, `rating`, `clinic_location`) VALUES
(1, 'Alex', 1, 'I can take care of anything!', 50000, 5.0, 'Tangerang'),
(2, 'Theola', 2, 'I can fix teeth!', 100000, 4.5, 'Sunib'),
(3, 'Howard', 3, 'Experienced pediatrician caring for children of all ages.', 150000, 4.0, 'Batam');

-- Schedules
-- Alex: Monday & Wednesday (09:00:00 to 17:00:00)
INSERT INTO `schedules` (`doctor_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(1, 'Monday', '09:00:00', '17:00:00'),
(1, 'Wednesday', '09:00:00', '17:00:00'),
(1, 'Friday', '09:00:00', '15:00:00');

-- Theola: Tuesday, Thursday, Saturday
INSERT INTO `schedules` (`doctor_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(2, 'Tuesday', '08:00:00', '14:00:00'),
(2, 'Thursday', '08:00:00', '14:00:00'),
(2, 'Saturday', '09:00:00', '13:00:00');

-- Howard: Monday, Tuesday, Thursday
INSERT INTO `schedules` (`doctor_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(3, 'Monday', '10:00:00', '16:00:00'),
(3, 'Tuesday', '10:00:00', '16:00:00'),
(3, 'Thursday', '10:00:00', '16:00:00');

-- Sample Appointments (matching case illustration for patient devon)
INSERT INTO `appointments` (`id`, `user_id`, `doctor_id`, `appointment_date`, `time_slot`, `status`) VALUES
(2, 2, 3, '2026-04-27', '12:00:00', 'Waiting'),
(3, 2, 1, '2026-04-27', '12:00:00', 'Waiting');

-- Sample Payments
INSERT INTO `payments` (`id`, `appointment_id`, `amount`, `proof_image`, `status`) VALUES
(2, 2, 10000, 'sample_receipt.jpg', 'pending');
