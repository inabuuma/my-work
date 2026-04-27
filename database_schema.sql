-- Database schema for Lost & Found system

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    room_number VARCHAR(50),
    hostel_id INT DEFAULT 1,
    role ENUM('admin', 'resident', 'hostel_manager', 'security') DEFAULT 'resident',
    is_active TINYINT(1) DEFAULT 1,
    points INT DEFAULT 0,
    reward_level VARCHAR(50) DEFAULT 'novice',
    is_emergency_verified TINYINT(1) DEFAULT 0,
    offline_sync_queue JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE lost_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    location VARCHAR(255),
    location_latitude DECIMAL(10, 8),
    location_longitude DECIMAL(11, 8),
    hostel_id INT DEFAULT 1,
    date_lost DATE,
    status ENUM('lost', 'recovered', 'claimed', 'unclaimed', 'auctioned', 'donated', 'disposed') DEFAULT 'lost',
    is_high_value TINYINT(1) DEFAULT 0,
    reward_amount DECIMAL(10, 2) DEFAULT 0,
    item_condition VARCHAR(100),
    image_paths JSON,
    proof_documents JSON,
    color_markings TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expiry_date TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE found_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    location VARCHAR(255),
    location_latitude DECIMAL(10, 8),
    location_longitude DECIMAL(11, 8),
    hostel_id INT DEFAULT 1,
    date_found DATE,
    status ENUM('found', 'returned', 'claimed', 'unclaimed', 'auctioned', 'donated', 'disposed') DEFAULT 'found',
    item_condition VARCHAR(100),
    image_paths JSON,
    proof_documents JSON,
    color_markings TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expiry_date TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE matches (
    match_id INT AUTO_INCREMENT PRIMARY KEY,
    lost_item_id INT NOT NULL,
    found_item_id INT NOT NULL,
    match_score DECIMAL(3,2),
    match_status ENUM('pending', 'confirmed', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lost_item_id) REFERENCES lost_items(item_id) ON DELETE CASCADE,
    FOREIGN KEY (found_item_id) REFERENCES found_items(item_id) ON DELETE CASCADE
);

CREATE TABLE claims (
    claim_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    user_id INT NOT NULL,
    claim_type ENUM('lost', 'found') NOT NULL,
    description TEXT,
    proof_images JSON,
    question_1 VARCHAR(255),
    answer_1 TEXT,
    question_2 VARCHAR(255),
    answer_2 TEXT,
    question_3 VARCHAR(255),
    answer_3 TEXT,
    verified_by INT,
    claim_status ENUM('pending', 'approved', 'rejected', 'verified_pending_approval') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(user_id) ON DELETE SET NULL
);

CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    notification_type ENUM('match', 'claim', 'reward', 'emergency', 'message', 'system') DEFAULT 'system',
    related_item_id INT,
    is_read TINYINT(1) DEFAULT 0,
    is_sms_sent TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- NEW TABLES FOR ADDITIONAL FEATURES

CREATE TABLE hostels (
    hostel_id INT AUTO_INCREMENT PRIMARY KEY,
    hostel_name VARCHAR(255) NOT NULL,
    location VARCHAR(255),
    manager_id INT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES users(user_id) ON DELETE SET NULL
);

CREATE TABLE messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    item_id INT,
    message_text TEXT NOT NULL,
    attachment_path VARCHAR(255),
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES found_items(item_id) ON DELETE SET NULL
);

CREATE TABLE location_heatmap (
    heatmap_id INT AUTO_INCREMENT PRIMARY KEY,
    hostel_id INT DEFAULT 1,
    location VARCHAR(255) NOT NULL,
    lost_count INT DEFAULT 0,
    found_count INT DEFAULT 0,
    total_incidents INT DEFAULT 0,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE rewards_leaderboard (
    leaderboard_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    total_returns INT DEFAULT 0,
    total_points INT DEFAULT 0,
    reward_rank ENUM('novice', 'helper', 'good_samaritan', 'legend') DEFAULT 'novice',
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    action_type ENUM('report_lost', 'report_found', 'claim', 'approve', 'reject', 'message', 'view_item', 'award_points', 'update_status') NOT NULL,
    related_item_id INT,
    related_user_id INT,
    details JSON,
    ip_address VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

CREATE TABLE auction_items (
    auction_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    auction_type ENUM('donate', 'auction_internal', 'dispose') DEFAULT 'donate',
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_date TIMESTAMP NULL,
    reserve_price DECIMAL(10, 2),
    current_bid DECIMAL(10, 2) DEFAULT 0,
    highest_bidder_id INT,
    status ENUM('pending', 'active', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES found_items(item_id) ON DELETE CASCADE,
    FOREIGN KEY (highest_bidder_id) REFERENCES users(user_id) ON DELETE SET NULL
);

CREATE TABLE emergency_alerts (
    alert_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    user_id INT NOT NULL,
    alert_title VARCHAR(255) NOT NULL,
    alert_description TEXT,
    urgency_level ENUM('high', 'critical') DEFAULT 'high',
    is_resolved TINYINT(1) DEFAULT 0,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES lost_items(item_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE offline_sync_queue (
    sync_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type VARCHAR(100),
    action_data JSON NOT NULL,
    device_id VARCHAR(255),
    local_timestamp TIMESTAMP,
    synced TINYINT(1) DEFAULT 0,
    synced_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

ALTER TABLE notifications DROP FOREIGN KEY notifications_ibfk_1;