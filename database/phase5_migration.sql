USE artsphere;

CREATE TABLE IF NOT EXISTS customization_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    creator_id INT NOT NULL,
    listing_id INT,
    requirements TEXT NOT NULL,
    budget DECIMAL(10,2),
    preferred_date DATE,
    status ENUM('submitted', 'under_review', 'clarification_requested', 'quotation_sent', 'accepted', 'declined', 'cancelled') NOT NULL DEFAULT 'submitted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS quotations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    creator_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    work_description TEXT,
    base_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    customization_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    material_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    additional_charges DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estimated_days INT,
    proposed_completion_date DATE,
    revisions_included INT DEFAULT 0,
    terms TEXT,
    version INT DEFAULT 1,
    status ENUM('draft', 'sent', 'revision_requested', 'revised', 'accepted', 'declined', 'expired') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES customization_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
);
