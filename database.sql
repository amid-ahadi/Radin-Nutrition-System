-- ساخت دیتابیس
CREATE DATABASE IF NOT EXISTS hospital_food_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE hospital_food_system;

-- جدول کاربران
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('admin','nutrition','ward','finance') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول بخش‌ها
CREATE TABLE wards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ward_name VARCHAR(100) NOT NULL
);

-- جدول پزشکان
CREATE TABLE doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_name VARCHAR(100) NOT NULL,
    ward_id INT,
    FOREIGN KEY (ward_id) REFERENCES wards(id)
);

-- جدول انواع غذا
CREATE TABLE meal_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meal_name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL
);

-- جدول ثبت غذای پزشکان
CREATE TABLE doctor_meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    meal_type_id INT NOT NULL,
    meal_date DATE NOT NULL,
    quantity INT DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(12,2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
    confirmed TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (doctor_id) REFERENCES doctors(id),
    FOREIGN KEY (meal_type_id) REFERENCES meal_types(id)
);

-- جدول آمار روزانه
CREATE TABLE daily_statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stat_date DATE NOT NULL UNIQUE,
    breakfast_count INT DEFAULT 0,
    lunch_count INT DEFAULT 0,
    dinner_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- داده اولیه غذاها
INSERT INTO meal_types (meal_name, price) VALUES
('صبحانه', 50000),
('ناهار', 150000),
('شام', 120000);

-- کاربر ادمین پیش‌فرض
INSERT INTO users (username, password, full_name, role)
VALUES ('admin', '$2y$10$examplehash', 'System Admin', 'admin');
