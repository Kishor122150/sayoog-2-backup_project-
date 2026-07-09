-- Create Database if not exists
CREATE DATABASE IF NOT EXISTS `sayog_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sayog_db`;

-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `address` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Donations Table
CREATE TABLE IF NOT EXISTS `donations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `donor_id` INT NOT NULL,
  `food_item` VARCHAR(100) NOT NULL,
  `quantity` VARCHAR(50) NOT NULL,
  `expiry_time` DATETIME NOT NULL,
  `pickup_address` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `description` TEXT,
  `status` ENUM('available', 'requested', 'accepted', 'completed', 'cancelled') DEFAULT 'available',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`donor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Requests Table
CREATE TABLE IF NOT EXISTS `requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `donation_id` INT NOT NULL,
  `consumer_id` INT NOT NULL,
  `quantity_requested` VARCHAR(50) NOT NULL,
  `message` TEXT,
  `status` ENUM('pending', 'approved', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`donation_id`) REFERENCES `donations`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`consumer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Unread','Read','Replied') DEFAULT 'Unread'
);

CREATE TABLE cms_homepage (
    id INT AUTO_INCREMENT PRIMARY KEY,

    /* HERO SECTION */
    hero_logo VARCHAR(255),
    hero_heading VARCHAR(255),
    hero_subheading TEXT,
    hero_button1_text VARCHAR(100),
    hero_button1_link VARCHAR(255),
    hero_button2_text VARCHAR(100),
    hero_button2_link VARCHAR(255),

    /* MAIN SECTION */
    main_heading VARCHAR(255),
    main_description TEXT,

    /* HOW SAYOOG WORKS */
    works_title VARCHAR(255),
    works_description TEXT,

    work1_icon VARCHAR(100),
    work1_heading VARCHAR(100),
    work1_description TEXT,

    work2_icon VARCHAR(100),
    work2_heading VARCHAR(100),
    work2_description TEXT,

    work3_icon VARCHAR(100),
    work3_heading VARCHAR(100),
    work3_description TEXT,

    work4_icon VARCHAR(100),
    work4_heading VARCHAR(100),
    work4_description TEXT,

    /* QUICK ACTION */
    quick_title VARCHAR(255),
    quick_description TEXT,

    quick1_icon VARCHAR(100),
    quick1_title VARCHAR(100),
    quick1_description TEXT,
    quick1_button VARCHAR(100),
    quick1_link VARCHAR(255),

    quick2_icon VARCHAR(100),
    quick2_title VARCHAR(100),
    quick2_description TEXT,
    quick2_button VARCHAR(100),
    quick2_link VARCHAR(255),

    quick3_icon VARCHAR(100),
    quick3_title VARCHAR(100),
    quick3_description TEXT,
    quick3_button VARCHAR(100),
    quick3_link VARCHAR(255),

    quick4_icon VARCHAR(100),
    quick4_title VARCHAR(100),
    quick4_description TEXT,
    quick4_button VARCHAR(100),
    quick4_link VARCHAR(255),

    /* FOOTER */
    footer_logo VARCHAR(255),
    footer_description TEXT,
    footer_address VARCHAR(255),
    footer_phone VARCHAR(30),
    footer_email VARCHAR(100),

    facebook VARCHAR(255),
    instagram VARCHAR(255),
    whatsapp VARCHAR(255),
    linkedin VARCHAR(255),

    copyright TEXT,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);