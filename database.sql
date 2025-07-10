-- Create the database
CREATE DATABASE IF NOT EXISTS chama_app;
USE chama_app;

-- Users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    avatar VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Chamas table
CREATE TABLE chamas (
    chama_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    goal_amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'Ksh',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    contribution_period ENUM('daily', 'weekly', 'monthly', 'custom') DEFAULT 'monthly',
    contribution_amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Chama members table
CREATE TABLE chama_members (
    member_id INT AUTO_INCREMENT PRIMARY KEY,
    chama_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('admin', 'member') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (chama_id) REFERENCES chamas(chama_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    UNIQUE KEY (chama_id, user_id)
);

-- Contributions table
CREATE TABLE contributions (
    contribution_id INT AUTO_INCREMENT PRIMARY KEY,
    chama_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50),
    proof_url VARCHAR(255),
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    contributed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_by INT,
    verified_at TIMESTAMP NULL,
    FOREIGN KEY (chama_id) REFERENCES chamas(chama_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (verified_by) REFERENCES users(user_id)
);

-- Activities table
CREATE TABLE activities (
    activity_id INT AUTO_INCREMENT PRIMARY KEY,
    chama_id INT NOT NULL,
    user_id INT,
    activity_type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chama_id) REFERENCES chamas(chama_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Notifications table
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    link VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Badges table (for gamification)
CREATE TABLE badges (
    badge_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    icon VARCHAR(255)
);

CREATE TABLE user_badges (
    user_badge_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    badge_id INT NOT NULL,
    chama_id INT,
    awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (badge_id) REFERENCES badges(badge_id),
    FOREIGN KEY (chama_id) REFERENCES chamas(chama_id)
);

-- Insert some sample badges
INSERT INTO badges (name, description, icon) VALUES 
('Early Bird', 'For making contributions early', 'early-bird.png'),
('Streak Saver', 'For consistent contributions', 'streak.png'),
('Champion', 'Top contributor in a period', 'champion.png');