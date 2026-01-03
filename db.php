<?php
// Database connection
$host = 'localhost';
$dbname = 'globetrotter';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Create tables if not exists
$sql = "
-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    profile_pic VARCHAR(255),
    bio TEXT,
    country VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Trips table
CREATE TABLE IF NOT EXISTS trips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    trip_name VARCHAR(100) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    travelers INT DEFAULT 1,
    total_budget DECIMAL(10,2),
    status ENUM('planning', 'ongoing', 'completed') DEFAULT 'planning',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Itinerary sections
CREATE TABLE IF NOT EXISTS itinerary_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    section_type ENUM('travel', 'accommodation', 'activity', 'food', 'other') NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    location VARCHAR(100),
    start_datetime DATETIME,
    end_datetime DATETIME,
    budget DECIMAL(10,2),
    notes TEXT,
    sequence INT,
    FOREIGN KEY (trip_id) REFERENCES trips(id)
);

-- Activities/Cities for search
CREATE TABLE IF NOT EXISTS activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('sightseeing', 'adventure', 'food', 'culture', 'shopping', 'relaxation') NOT NULL,
    city VARCHAR(100) NOT NULL,
    country VARCHAR(100) NOT NULL,
    description TEXT,
    avg_cost DECIMAL(10,2),
    duration_hours INT,
    popularity INT DEFAULT 0
);

-- Community messages
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id)
);

-- User saved/preferred regions
CREATE TABLE IF NOT EXISTS user_regions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    region VARCHAR(100) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
";

try {
    $pdo->exec($sql);
} catch(PDOException $e) {
    // Tables might already exist
}
?>