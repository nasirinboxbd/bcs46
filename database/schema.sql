CREATE DATABASE IF NOT EXISTS bcs_joining CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bcs_joining;

CREATE TABLE IF NOT EXISTS site_settings (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  bcs_number VARCHAR(20) NOT NULL DEFAULT '45',
  exam_year YEAR NOT NULL DEFAULT 2023,
  joining_date DATE NULL,
  notification_date DATE NULL,
  commission_date DATE NULL,
  show_cadre_id TINYINT(1) NOT NULL DEFAULT 1,
  developer_name VARCHAR(160) NOT NULL DEFAULT 'Md. Nasir Uddin',
  developer_subtitle VARCHAR(200) NOT NULL DEFAULT 'ওয়েব অ্যাপ ডেভেলপার',
  developer_workplace VARCHAR(255) NOT NULL DEFAULT 'Assistant Authorized Officer, RAJUK',
  developer_bcs46 VARCHAR(255) NOT NULL DEFAULT 'BCS Roads and Highways Cadre — সুপারিশপ্রাপ্ত',
  developer_bcs47 VARCHAR(255) NOT NULL DEFAULT 'BCS Administration Cadre — সুপারিশপ্রাপ্ত',
  developer_whatsapp VARCHAR(255) NOT NULL DEFAULT 'https://wa.me/8801581051987',
  developer_facebook VARCHAR(255) NOT NULL DEFAULT 'https://www.facebook.com/mdnasiruddin.ce.buet',
  developer_image VARCHAR(255) NOT NULL DEFAULT 'public/assets/developer.jpeg',
  branding TEXT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO site_settings (id, bcs_number, exam_year, joining_date, notification_date, commission_date, show_cadre_id)
VALUES (1, '45', 2023, NULL, '2026-10-14', '2025-12-14', 1)
ON DUPLICATE KEY UPDATE id = id;

CREATE TABLE IF NOT EXISTS admin_users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
