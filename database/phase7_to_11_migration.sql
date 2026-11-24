USE artsphere;

-- Design versions table (Phase 7)
CREATE TABLE IF NOT EXISTS design_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    creator_id INT NOT NULL,
    version_number INT NOT NULL DEFAULT 1,
    file_path VARCHAR(255),
    preview_path VARCHAR(255),
    creator_notes TEXT,
    status ENUM('pending_review', 'revision_requested', 'approved', 'superseded') NOT NULL DEFAULT 'pending_review',
    approved_by INT DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES customization_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Revision requests (Phase 7)
CREATE TABLE IF NOT EXISTS revision_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    design_version_id INT NOT NULL,
    request_id INT NOT NULL,
    customer_id INT NOT NULL,
    description TEXT NOT NULL,
    status ENUM('requested', 'in_progress', 'resubmitted', 'accepted', 'closed') NOT NULL DEFAULT 'requested',
    creator_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (design_version_id) REFERENCES design_versions(id) ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES customization_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Projects / Orders (Phase 8 / 11)
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL UNIQUE,
    quotation_id INT NOT NULL,
    customer_id INT NOT NULL,
    creator_id INT NOT NULL,
    agreed_amount DECIMAL(10,2) NOT NULL,
    agreed_scope TEXT,
    status ENUM('agreement_confirmed','design_in_progress','design_review','design_approved','production','quality_check','ready_for_delivery','completed','cancelled') NOT NULL DEFAULT 'agreement_confirmed',
    expected_completion DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES customization_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (quotation_id) REFERENCES quotations(id),
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Reviews (Phase 10)
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    creator_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    feedback TEXT,
    creator_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Rewards (Phase 11)
CREATE TABLE IF NOT EXISTS reward_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    points INT NOT NULL,
    reason VARCHAR(150) NOT NULL,
    reference_id INT,
    reference_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY no_duplicate_reward (user_id, reference_type, reference_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add points column to users if not present
ALTER TABLE users ADD COLUMN IF NOT EXISTS reward_points INT NOT NULL DEFAULT 0;

-- Add listing_views to listings
ALTER TABLE listings ADD COLUMN IF NOT EXISTS views INT NOT NULL DEFAULT 0;

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_requests_creator ON customization_requests(creator_id);
CREATE INDEX IF NOT EXISTS idx_requests_customer ON customization_requests(customer_id);
CREATE INDEX IF NOT EXISTS idx_messages_request ON messages(request_id);
CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications(user_id, is_read);
CREATE INDEX IF NOT EXISTS idx_listings_category ON listings(category_id);
CREATE INDEX IF NOT EXISTS idx_design_versions_request ON design_versions(request_id);
