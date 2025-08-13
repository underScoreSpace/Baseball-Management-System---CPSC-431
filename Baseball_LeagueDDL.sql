-- ===================================
-- Drop and recreate the database
-- ===================================
DROP DATABASE IF EXISTS cpsc431_final;
CREATE DATABASE cpsc431_final;
USE cpsc431_final;

-- ===================================
-- Create tables
-- ===================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone_number VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('User', 'Player', 'Coach', 'Manager', 'Admin') DEFAULT 'User',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    jersey_number INT,
    position VARCHAR(50),
    team_id INT,
    games_played INT DEFAULT 0,
    at_bats INT DEFAULT 0,
    hits INT DEFAULT 0,
    home_runs INT DEFAULT 0,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL
);

CREATE TABLE team_coaches (
    user_id INT,
    team_id INT,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    PRIMARY KEY (user_id, team_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
);

CREATE TABLE team_managers (
    user_id INT NOT NULL,
    team_id INT NOT NULL,
    status ENUM('Pending', 'Active', 'Inactive') DEFAULT 'Pending',
    PRIMARY KEY (user_id, team_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
);

CREATE TABLE player_registration_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT,
    code VARCHAR(50) UNIQUE,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
);

CREATE TABLE coach_registration_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT,
    code VARCHAR(50) UNIQUE,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
);

CREATE TABLE manager_registration_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(100) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);




-- TEST DATA SECTION -- 
--1 admin, 1 manager per team, 1 coach per team, 3players per team, (3 teams: used for testing) 
-- ===================================
-- Insert initial Admin User
-- ===================================
INSERT INTO users (username, first_name, last_name, email, password_hash, role)
VALUES ('admin1', 'Super', 'Admin', 'admin@example.com', '$2y$10$jJI4LU2PrCPCCgEeq04xi.VVQiUcYXKeKmvldYEv0D4rjklkbqDjG', 'Admin');

-- ===================================
-- Insert Teams
-- ===================================
INSERT INTO teams (team_name) VALUES
('Titans'),
('Sharks'),
('Eagles');

-- ===================================
-- Insert Managers, Coaches, Players
-- ===================================
-- Managers
INSERT INTO users (username, first_name, last_name, email, phone_number, password_hash, role) VALUES
('manager1', 'Manager', 'One', 'manager1@example.com', '555-111-1111', '$2y$10$jJI4LU2PrCPCCgEeq04xi.VVQiUcYXKeKmvldYEv0D4rjklkbqDjG', 'Manager'),
('manager2', 'Manager', 'Two', 'manager2@example.com', '555-222-2222', '$2y$10$jJI4LU2PrCPCCgEeq04xi.VVQiUcYXKeKmvldYEv0D4rjklkbqDjG', 'Manager'),
('manager3', 'Manager', 'Three', 'manager3@example.com', '555-333-3333', '$2y$10$jJI4LU2PrCPCCgEeq04xi.VVQiUcYXKeKmvldYEv0D4rjklkbqDjG', 'Manager');

-- Coaches
INSERT INTO users (username, first_name, last_name, email, phone_number, password_hash, role) VALUES
('coach1', 'Coach', 'One', 'coach1@example.com', '555-444-4444', '$2y$10$jJI4LU2PrCPCCgEeq04xi.VVQiUcYXKeKmvldYEv0D4rjklkbqDjG', 'Coach'),
('coach2', 'Coach', 'Two', 'coach2@example.com', '555-555-5555', '$2y$10$jJI4LU2PrCPCCgEeq04xi.VVQiUcYXKeKmvldYEv0D4rjklkbqDjG', 'Coach'),
('coach3', 'Coach', 'Three', 'coach3@example.com', '555-666-6666', '$2y$10$jJI4LU2PrCPCCgEeq04xi.VVQiUcYXKeKmvldYEv0D4rjklkbqDjG', 'Coach');

-- Players
INSERT INTO users (username, first_name, last_name, email, phone_number, password_hash, role) VALUES
-- Team 1 (Titans)
('player1a', 'Player', 'OneA', 'player1a@example.com', '555-701-0001', '$2y$10$jJI4LU2PrCPCCgEeq04xi.VVQiUcYXKeKmvldYEv0D4rjklkbqDjG', 'Player'),
('player1b', 'Player', 'OneB', 'player1b@example.com', '555-701-0002', '$2y$10$jJI4LU2PrCPCCgEeq04xi.VVQiUcYXKeKmvldYEv0D4rjklkbqDjG', 'Player'),
('player1c', 'Player', 'OneC', 'player1c@example.com', '555-701-0003', '$2y$10$jJI4LU2PrCPCCgEeq04xi.VVQiUcYXKeKmvldYEv0D4rjklkbqDjG', 'Player'),

-- Team 2 (Sharks)
('player2a', 'Player', 'TwoA', 'player2a@example.com', '555-702-0001', '$2y$10$jJI4LU2PrCPCCgEeq04xi.VVQiUcYXKeKmvldYEv0D4rjklkbqDjG', 'Player'),
('player2b', 'Player', 'TwoB', 'player2b@example.com', '555-702-0002', '$2y$10$jJI4LU2PrCPCCgEeq04xi.VVQiUcYXKeKmvldYEv0D4rjklkbqDjG', 'Player'),
('player2c', 'Player', 'TwoC', 'player2c@example.com', '555-702-0003', '$2y$10$jJI4LU2PrCPCCgEeq04xi.VVQiUcYXKeKmvldYEv0D4rjklkbqDjG', 'Player'),

-- Team 3 (Eagles)
('player3a', 'Player', 'ThreeA', 'player3a@example.com', '555-703-0001', '$2y$10$jJI4LU2PrCPCCgEeq04xi.VVQiUcYXKeKmvldYEv0D4rjklkbqDjG', 'Player'),
('player3b', 'Player', 'ThreeB', 'player3b@example.com', '555-703-0002', '$2y$10$jJI4LU2PrCPCCgEeq04xi.VVQiUcYXKeKmvldYEv0D4rjklkbqDjG', 'Player'),
('player3c', 'Player', 'ThreeC', 'player3c@example.com', '555-703-0003', '$2y$10$jJI4LU2PrCPCCgEeq04xi.VVQiUcYXKeKmvldYEv0D4rjklkbqDjG', 'Player');

-- ===================================
-- Assign Managers to Teams
-- ===================================
INSERT INTO team_managers (user_id, team_id, status)
SELECT id, 1, 'Active' FROM users WHERE username = 'manager1';
INSERT INTO team_managers (user_id, team_id, status)
SELECT id, 2, 'Active' FROM users WHERE username = 'manager2';
INSERT INTO team_managers (user_id, team_id, status)
SELECT id, 3, 'Active' FROM users WHERE username = 'manager3';

-- ===================================
-- Assign Coaches to Teams
-- ===================================
INSERT INTO team_coaches (user_id, team_id, status)
SELECT id, 1, 'Active' FROM users WHERE username = 'coach1';
INSERT INTO team_coaches (user_id, team_id, status)
SELECT id, 2, 'Active' FROM users WHERE username = 'coach2';
INSERT INTO team_coaches (user_id, team_id, status)
SELECT id, 3, 'Active' FROM users WHERE username = 'coach3';

-- ===================================
-- Assign Players to Teams
-- ===================================
-- Team 1 (Titans)
INSERT INTO players (user_id, jersey_number, position, team_id, games_played, at_bats, hits, home_runs)
SELECT id, 10, 'Pitcher', 1, 12, 40, 15, 3 FROM users WHERE username = 'player1a';

INSERT INTO players (user_id, jersey_number, position, team_id, games_played, at_bats, hits, home_runs)
SELECT id, 11, 'Catcher', 1, 14, 38, 16, 4 FROM users WHERE username = 'player1b';

INSERT INTO players (user_id, jersey_number, position, team_id, games_played, at_bats, hits, home_runs)
SELECT id, 12, 'Outfielder', 1, 13, 35, 13, 2 FROM users WHERE username = 'player1c';

-- Team 2 (Sharks)
INSERT INTO players (user_id, jersey_number, position, team_id, games_played, at_bats, hits, home_runs)
SELECT id, 13, 'Pitcher', 2, 11, 32, 10, 1 FROM users WHERE username = 'player2a';

INSERT INTO players (user_id, jersey_number, position, team_id, games_played, at_bats, hits, home_runs)
SELECT id, 14, 'Catcher', 2, 15, 36, 17, 5 FROM users WHERE username = 'player2b';

INSERT INTO players (user_id, jersey_number, position, team_id, games_played, at_bats, hits, home_runs)
SELECT id, 15, 'Outfielder', 2, 14, 33, 12, 2 FROM users WHERE username = 'player2c';

-- Team 3 (Eagles)
INSERT INTO players (user_id, jersey_number, position, team_id, games_played, at_bats, hits, home_runs)
SELECT id, 16, 'Pitcher', 3, 10, 28, 11, 2 FROM users WHERE username = 'player3a';

INSERT INTO players (user_id, jersey_number, position, team_id, games_played, at_bats, hits, home_runs)
SELECT id, 17, 'Catcher', 3, 12, 31, 14, 3 FROM users WHERE username = 'player3b';

INSERT INTO players (user_id, jersey_number, position, team_id, games_played, at_bats, hits, home_runs)
SELECT id, 18, 'Outfielder', 3, 13, 34, 13, 3 FROM users WHERE username = 'player3c';