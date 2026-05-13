CREATE DATABASE IF NOT EXISTS sportoviste CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sportoviste;

-- Tabulka uživatelů
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'teacher', 'admin') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabulka sportovišť
CREATE TABLE IF NOT EXISTS facilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('tělocvična', 'posilovna', 'ovál', 'hřiště') NOT NULL,
    capacity INT DEFAULT 20,
    location VARCHAR(100),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabulka rezervací
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    facility_id INT NOT NULL,
    date DATE NOT NULL,
    time_from TIME NOT NULL,
    time_to TIME NOT NULL,
    people_count INT NOT NULL DEFAULT 1,
    status ENUM('active', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE,
    INDEX idx_facility_date (facility_id, date),
    INDEX idx_user_status (user_id, status)
);

-- Testovací data
INSERT INTO facilities (name, type, capacity, location, description) VALUES
('Hlavní tělocvična', 'tělocvična', 30, 'Přízemí', 'Velká tělocvična pro kolektivní sporty'),
('Posilovna A', 'posilovna', 15, '1. patro', 'Moderní posilovna s kardio zónou'),
('Atletický ovál', 'ovál', 50, 'Venkovní areál', '400m atletická dráha'),
('Fotbalové hřiště', 'hřiště', 22, 'Venkovní areál', 'Travnaté hřiště s umělým osvětlením'),
('Malá tělocvična', 'tělocvična', 20, 'Přízemí', 'Tělocvična pro menší skupiny'),
('Posilovna B', 'posilovna', 12, 'Suterén', 'Specializovaná posilovna pro pokročilé');

-- ⚠️ Admin účet — heslo vygeneruj přes POST /api/auth/register nebo seedovací skript
-- INSERT INTO users (name, email, password, role) VALUES ('Administrátor', 'admin@sportoviste.cz', '<bcrypt_hash>', 'admin');

-- Migrace: přidání people_count (bezpečné i na existující DB)
ALTER TABLE reservations ADD COLUMN IF NOT EXISTS people_count INT NOT NULL DEFAULT 1;
