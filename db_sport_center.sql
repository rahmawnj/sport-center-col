-- =================================================================================
-- 1. DDL: CREATE TABLES (Struktur Database)
-- =================================================================================

-- ---------------------------------------------------------
-- MODUL PENGGUNA & KEANGGOTAAN
-- ---------------------------------------------------------
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

CREATE TABLE membership_packages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(15, 2) NOT NULL,
    duration_days INT NOT NULL COMMENT 'Masa aktif dalam hari',
    session_quota INT NULL COMMENT 'Batas kedatangan/sesi. Isi NULL jika Unlimited',
    description TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL
);

CREATE TABLE user_subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    membership_package_id BIGINT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    used_sessions INT NOT NULL DEFAULT 0 COMMENT 'Jumlah sesi yang sudah dipakai',
    status ENUM('active', 'expired', 'cancelled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (membership_package_id) REFERENCES membership_packages(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- MODUL MASTER DATA (ZONA, FASILITAS, JAM OPERASIONAL)
-- ---------------------------------------------------------
CREATE TABLE zones (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    pricing_model ENUM('per_person', 'per_space', 'per_trainer_session', 'per_table') NOT NULL,
    is_online_bookable BOOLEAN NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE operational_hours (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    zone_id BIGINT UNSIGNED NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '1=Senin, 2=Selasa, ... 7=Minggu',
    open_time TIME NOT NULL,
    close_time TIME NOT NULL,
    is_closed BOOLEAN NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE CASCADE
);

CREATE TABLE zone_spaces (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    zone_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    capacity INT NOT NULL DEFAULT 1,
    status ENUM('available', 'maintenance') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE CASCADE
);

CREATE TABLE trainers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    specialty VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL
);

CREATE TABLE facilities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    icon VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE facility_zone_space (
    zone_space_id BIGINT UNSIGNED NOT NULL,
    facility_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (zone_space_id, facility_id),
    FOREIGN KEY (zone_space_id) REFERENCES zone_spaces(id) ON DELETE CASCADE,
    FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE
);

CREATE TABLE pricing_rates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    zone_space_id BIGINT UNSIGNED NOT NULL,
    trainer_id BIGINT UNSIGNED NULL COMMENT 'Untuk harga per instruktur',
    rental_type VARCHAR(255) NOT NULL COMMENT 'Contoh: Reguler, Promo Happy Hour',
    price DECIMAL(15, 2) NOT NULL,
    unit_type ENUM('per_hour', 'per_visit', 'per_session') NOT NULL,
    min_booking_duration INT NOT NULL DEFAULT 1,
    day_of_week VARCHAR(50) NULL COMMENT 'Format CSV: "1,2,3,4,5" untuk Weekday. NULL = Tiap hari',
    start_time TIME NULL COMMENT 'Jam awal promo berlaku',
    end_time TIME NULL COMMENT 'Jam akhir promo berlaku',
    is_active BOOLEAN NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (zone_space_id) REFERENCES zone_spaces(id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE CASCADE
);

CREATE TABLE add_ons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(15, 2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL
);

-- ---------------------------------------------------------
-- MODUL TRANSAKSI (POS & BOOKING)
-- ---------------------------------------------------------
CREATE TABLE transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_code VARCHAR(50) NOT NULL UNIQUE,
    customer_type ENUM('member', 'general') NOT NULL DEFAULT 'general',
    user_id BIGINT UNSIGNED NULL,
    guest_name VARCHAR(255) NULL,
    payment_method ENUM('cash', 'bank_transfer') NOT NULL,
    total_amount DECIMAL(15, 2) NOT NULL,
    payment_status ENUM('unpaid', 'dp_paid', 'fully_paid') NOT NULL DEFAULT 'unpaid',
    handled_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (handled_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE transaction_details (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id BIGINT UNSIGNED NOT NULL,
    zone_space_id BIGINT UNSIGNED NOT NULL,
    trainer_id BIGINT UNSIGNED NULL,
    qty INT NOT NULL DEFAULT 1,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    price_rate DECIMAL(15, 2) NOT NULL,
    subtotal DECIMAL(15, 2) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (zone_space_id) REFERENCES zone_spaces(id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE SET NULL
);

CREATE TABLE transaction_add_ons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id BIGINT UNSIGNED NOT NULL,
    add_on_id BIGINT UNSIGNED NOT NULL,
    qty INT NOT NULL DEFAULT 1,
    price_rate DECIMAL(15, 2) NOT NULL,
    subtotal DECIMAL(15, 2) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (add_on_id) REFERENCES add_ons(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- MODUL KAS & KEUANGAN (CASH MANAGEMENT)
-- ---------------------------------------------------------
CREATE TABLE cash_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type ENUM('cash_in', 'cash_out', 'petty_cash_update', 'transfer_to_bank') NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    description TEXT NULL,
    handled_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (handled_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE daily_closings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    closing_date DATE NOT NULL,
    total_system_cash DECIMAL(15, 2) NOT NULL,
    total_actual_cash DECIMAL(15, 2) NOT NULL,
    total_bank_transfer DECIMAL(15, 2) NOT NULL,
    difference DECIMAL(15, 2) NOT NULL,
    closed_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (closed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ---------------------------------------------------------
-- MODUL KONTROL AKSES & IOT
-- ---------------------------------------------------------
CREATE TABLE gate_access_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    zone_id BIGINT UNSIGNED NOT NULL COMMENT 'Zona apa yang diakses (misal Gym)',
    action ENUM('check_in', 'check_out') NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE CASCADE
);

CREATE TABLE iot_devices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    zone_space_id BIGINT UNSIGNED NOT NULL,
    device_code VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('online', 'offline') NOT NULL DEFAULT 'offline',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (zone_space_id) REFERENCES zone_spaces(id) ON DELETE CASCADE
);

CREATE TABLE iot_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    iot_device_id BIGINT UNSIGNED NOT NULL,
    action ENUM('turn_on', 'turn_off') NOT NULL,
    triggered_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (iot_device_id) REFERENCES iot_devices(id) ON DELETE CASCADE,
    FOREIGN KEY (triggered_by) REFERENCES users(id) ON DELETE SET NULL
);


-- =================================================================================
-- 2. DML: INSERT DUMMY DATA (Simulasi Data Agustus 2026)
-- =================================================================================

-- Insert Roles & Users
INSERT INTO roles (name) VALUES ('Superadmin'), ('Admin'), ('Member');
INSERT INTO users (role_id, name, email, password, phone) VALUES
(1, 'Super Admin', 'super@admin.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '08111111111'),
(2, 'Kasir Satu', 'kasir@admin.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '08222222222'),
(3, 'Budi Member', 'budi@member.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '08333333333');

-- Insert Membership & Subscriptions
-- Gym Unlimited (session_quota = NULL) & Yoga Paket 10x Sesi
INSERT INTO membership_packages (name, price, duration_days, session_quota) VALUES
('Gym Iuran Bulanan', 250000.00, 30, NULL),
('Yoga Paket 10 Sesi', 400000.00, 30, 10);

INSERT INTO user_subscriptions (user_id, membership_package_id, start_date, end_date, used_sessions) VALUES
(3, 1, '2026-08-01', '2026-08-31', 0), -- Budi langganan Gym
(3, 2, '2026-08-01', '2026-08-31', 2); -- Budi langganan Yoga, sudah terpakai 2 sesi

-- Insert Zones
INSERT INTO zones (name, pricing_model, is_online_bookable) VALUES
('Gym', 'per_person', 0),
('Padel', 'per_space', 1),
('Billiard', 'per_table', 0),
('Studio Yoga', 'per_person', 1);

-- Insert Operational Hours (Contoh Padel & Billiard)
INSERT INTO operational_hours (zone_id, day_of_week, open_time, close_time) VALUES
(2, 1, '08:00:00', '23:00:00'), (2, 2, '08:00:00', '23:00:00'), -- Padel Sen-Sel
(3, 1, '10:00:00', '24:00:00'), (3, 2, '10:00:00', '24:00:00'); -- Billiard Sen-Sel

-- Insert Zone Spaces
INSERT INTO zone_spaces (zone_id, name, capacity) VALUES
(1, 'Area Gym Utama', 100), -- ID: 1
(2, 'Padel A (Indoor)', 4),   -- ID: 2
(3, 'Meja Billiard 1', 4),    -- ID: 3
(4, 'Studio Yoga VVIP', 20);  -- ID: 4

-- Insert Trainers
INSERT INTO trainers (name, specialty) VALUES
('Coach Agung', 'Personal Trainer Gym'), -- ID: 1
('Coach Sarah', 'Yoga Master');          -- ID: 2

-- Insert Dynamic Pricing Rates
-- 1. Gym Iuran Harian (Tiap hari, jam bebas)
INSERT INTO pricing_rates (zone_space_id, trainer_id, rental_type, price, unit_type, day_of_week, start_time, end_time)
VALUES (1, NULL, 'Gym Iuran Harian', 50000.00, 'per_visit', NULL, NULL, NULL);

-- 2. Padel (Weekday vs Weekend)
INSERT INTO pricing_rates (zone_space_id, trainer_id, rental_type, price, unit_type, day_of_week, start_time, end_time)
VALUES (2, NULL, 'Padel Weekday', 150000.00, 'per_hour', '1,2,3,4,5', NULL, NULL),
       (2, NULL, 'Padel Weekend', 200000.00, 'per_hour', '6,7', NULL, NULL);

-- 3. Billiard (Reguler vs Happy Hour Jam 10-14 khusus Weekday)
INSERT INTO pricing_rates (zone_space_id, trainer_id, rental_type, price, unit_type, day_of_week, start_time, end_time)
VALUES (3, NULL, 'Billiard Reguler', 40000.00, 'per_hour', NULL, NULL, NULL),
       (3, NULL, 'Billiard Promo Siang', 20000.00, 'per_hour', '1,2,3,4,5', '10:00:00', '14:00:00');

-- 4. Yoga (Harga bergantung Trainer)
INSERT INTO pricing_rates (zone_space_id, trainer_id, rental_type, price, unit_type, day_of_week, start_time, end_time)
VALUES (4, 2, 'Yoga Master Class', 100000.00, 'per_session', NULL, NULL, NULL);

-- Insert Access Log (Contoh Budi masuk Gym jam 7 pagi dan keluar jam 9 pagi)
INSERT INTO gate_access_logs (user_id, zone_id, action, created_at) VALUES
(3, 1, 'check_in', '2026-08-20 07:00:00'),
(3, 1, 'check_out', '2026-08-20 09:00:00');

-- Insert Transaksi
INSERT INTO transactions (booking_code, customer_type, guest_name, payment_method, total_amount, payment_status, handled_by)
VALUES ('BK-260820-001', 'general', 'Tamu Walkin Padel', 'cash', 150000.00, 'fully_paid', 2);

INSERT INTO transaction_details (transaction_id, zone_space_id, start_time, end_time, price_rate, subtotal)
VALUES (1, 2, '2026-08-20 15:00:00', '2026-08-20 16:00:00', 150000.00, 150000.00);

-- Insert IoT
INSERT INTO iot_devices (zone_space_id, device_code, status) VALUES
(2, 'PADEL-A-RELAY', 'online');

INSERT INTO iot_logs (iot_device_id, action, triggered_by, created_at) VALUES
(1, 'turn_on', 2, '2026-08-20 14:58:00');
