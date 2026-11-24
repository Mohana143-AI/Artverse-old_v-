-- ============================================================
-- ArtSphere — MASTER MIGRATION (Run this ONE file only)
-- Safe to run in any order. Uses IF NOT EXISTS throughout.
-- Run in phpMyAdmin: select 'artsphere' database, then Import this file.
-- ============================================================

CREATE DATABASE IF NOT EXISTS artsphere CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE artsphere;

-- ============================================================
-- 1. USERS (base table — everything else references it)
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer','creator','admin') NOT NULL DEFAULT 'customer',
    account_status ENUM('active','suspended','pending') NOT NULL DEFAULT 'active',
    reward_points INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- 2. CATEGORIES
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50),
    image_url VARCHAR(255) NULL
);

-- Add image URLs for demo categories (if not already present)
ALTER TABLE categories ADD COLUMN IF NOT EXISTS image_url VARCHAR(255) NULL;

-- ============================================================
-- 3. CREATOR PROFILES (references users)
-- ============================================================
CREATE TABLE IF NOT EXISTS creator_profiles (
    user_id INT PRIMARY KEY,
    bio TEXT,
    location VARCHAR(100),
    specialties VARCHAR(255),
    profile_image VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- 4. LISTINGS (references users + categories)
-- ============================================================
CREATE TABLE IF NOT EXISTS listings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    creator_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2),
    listing_type ENUM('ready_made','customizable','service') NOT NULL DEFAULT 'customizable',
    image_url VARCHAR(255),
    views INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- ============================================================
-- 5. CUSTOMIZATION REQUESTS (references users + listings)
-- ============================================================
CREATE TABLE IF NOT EXISTS customization_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    creator_id INT NOT NULL,
    listing_id INT,
    requirements TEXT NOT NULL,
    budget DECIMAL(10,2),
    preferred_date DATE,
    status ENUM('submitted','under_review','clarification_requested','quotation_sent','accepted','declined','cancelled') NOT NULL DEFAULT 'submitted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE SET NULL
);

-- ============================================================
-- 6. QUOTATIONS (references customization_requests + users)
-- ============================================================
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
    status ENUM('draft','sent','revision_requested','revised','accepted','declined','expired') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES customization_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- 7. MESSAGES (references customization_requests + users)
-- ============================================================
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    sender_id INT NOT NULL,
    message_text TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES customization_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- 8. NOTIFICATIONS (references users)
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- 9. PROJECT EVENTS (references customization_requests + users)
-- ============================================================
CREATE TABLE IF NOT EXISTS project_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    user_id INT NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    event_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES customization_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- 10. DESIGN VERSIONS (references customization_requests + users)
-- ============================================================
CREATE TABLE IF NOT EXISTS design_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    creator_id INT NOT NULL,
    version_number INT NOT NULL DEFAULT 1,
    file_path VARCHAR(255),
    preview_path VARCHAR(255),
    creator_notes TEXT,
    status ENUM('pending_review','revision_requested','approved','superseded') NOT NULL DEFAULT 'pending_review',
    approved_by INT DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES customization_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- 11. REVISION REQUESTS (references design_versions + customization_requests + users)
-- ============================================================
CREATE TABLE IF NOT EXISTS revision_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    design_version_id INT NOT NULL,
    request_id INT NOT NULL,
    customer_id INT NOT NULL,
    description TEXT NOT NULL,
    status ENUM('requested','in_progress','resubmitted','accepted','closed') NOT NULL DEFAULT 'requested',
    creator_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (design_version_id) REFERENCES design_versions(id) ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES customization_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- 12. PROJECTS / ORDERS (references customization_requests + quotations + users)
-- ============================================================
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

-- ============================================================
-- 13. REVIEWS (references projects + users)
-- ============================================================
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

-- ============================================================
-- 14. REWARD TRANSACTIONS (references users)
-- ============================================================
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

-- ============================================================
-- 15. PERFORMANCE INDEXES
-- ============================================================
CREATE INDEX IF NOT EXISTS idx_requests_creator  ON customization_requests(creator_id);
CREATE INDEX IF NOT EXISTS idx_requests_customer ON customization_requests(customer_id);
CREATE INDEX IF NOT EXISTS idx_messages_request  ON messages(request_id);
CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications(user_id, is_read);
CREATE INDEX IF NOT EXISTS idx_listings_category ON listings(category_id);
CREATE INDEX IF NOT EXISTS idx_design_versions_request ON design_versions(request_id);

-- ============================================================
-- 16. SEED DATA (demo categories — safe to re-run with IGNORE)
-- ============================================================
INSERT IGNORE INTO categories (name, slug, description, icon) VALUES
('Interior Design',      'interior-design',     'Residential and commercial space planning.',        'ph-house'),
('Custom Furniture',     'custom-furniture',    'Woodwork, metalwork, and tailored pieces.',         'ph-armchair'),
('Painting & Digital Art','painting-digital-art','Canvas, watercolor, and digital illustrations.',  'ph-palette'),
('Handmade Crafts',      'handmade-crafts',     'Unique artisan goods and crafts.',                 'ph-scissors'),
('Pottery & Ceramics',   'pottery-ceramics',    'Vases, dishware, and sculptural clay.',            'ph-coffee'),
('Jewellery Design',     'jewellery-design',    'Custom rings, necklaces, and accessories.',        'ph-diamond'),
('Photography',          'photography',         'Portraits, events, and fine art prints.',          'ph-camera'),
('Fashion & Clothing',   'fashion-clothing',    'Tailoring, alterations, and custom apparel.',      'ph-t-shirt'),
('Graphic Design',       'graphic-design',      'Logos, branding, and digital assets.',             'ph-bezier-curve'),
('Home Decor',           'home-decor',          'Textiles, lighting, and decorative items.',        'ph-lamp');
-- Set image URLs for categories
UPDATE categories SET image_url = 'https://picsum.photos/seed/interior/800/600' WHERE slug = 'interior-design';
UPDATE categories SET image_url = 'https://picsum.photos/seed/furniture/800/600' WHERE slug = 'custom-furniture';
UPDATE categories SET image_url = 'https://picsum.photos/seed/art/800/600' WHERE slug = 'painting-digital-art';
UPDATE categories SET image_url = 'https://picsum.photos/seed/crafts/800/600' WHERE slug = 'handmade-crafts';
UPDATE categories SET image_url = 'https://picsum.photos/seed/pottery/800/600' WHERE slug = 'pottery-ceramics';
UPDATE categories SET image_url = 'https://picsum.photos/seed/jewellery/800/600' WHERE slug = 'jewellery-design';
UPDATE categories SET image_url = 'https://picsum.photos/seed/photo/800/600' WHERE slug = 'photography';
UPDATE categories SET image_url = 'https://picsum.photos/seed/fashion/800/600' WHERE slug = 'fashion-clothing';
UPDATE categories SET image_url = 'https://picsum.photos/seed/graphic/800/600' WHERE slug = 'graphic-design';
UPDATE categories SET image_url = 'https://picsum.photos/seed/decor/800/600' WHERE slug = 'home-decor';

-- ============================================================
-- 17. DEMO CREATOR ACCOUNTS (password = "password" for testing only)
-- ============================================================
INSERT IGNORE INTO users (id, full_name, username, email, password_hash, role) VALUES
(998, 'Jane Designer',   'janedesigner',  'jane@example.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'creator'),
(999, 'Sample Creator',  'samplecreator', 'creator@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'creator');

INSERT IGNORE INTO creator_profiles (user_id, bio, location, specialties, profile_image) VALUES
(998, 'Modern interior spaces and architecture design.', 'Downtown Area',  'Interiors, Architecture',   'face1'),
(999, 'Multi-disciplinary artist and maker.',             'Local Studio',   'Furniture, Digital Art',    'face2');

INSERT IGNORE INTO listings (id, creator_id, category_id, title, description, price, listing_type, image_url) VALUES
(1, 999, 2, 'Minimalist Walnut Desk',       'Custom made walnut desk with sleek modern legs.',             450.00, 'customizable', 'wood1'),
(2, 999, 3, 'Ocean Depths Canvas',          'Abstract painting inspired by the deep sea.',                220.00, 'ready_made',   'art1'),
(3, 998, 1, 'Living Space Concept',         'Full interior design consultation and moodboard.',           300.00, 'service',      'interior1'),
(4, 999, 5, 'Textured Clay Vase',           'Hand-thrown ceramic vase with unique natural texture.',       85.00, 'customizable', 'pottery1'),
(5, 998, 1, 'Minimalist Office Design',     'Complete office spatial planning and styling service.',      500.00, 'service',      'office1'),
(6, 999, 6, 'Sterling Silver Ring',         'Handcrafted sterling silver band, custom sizing available.', 150.00, 'customizable', 'jewel1'),
(7, 998, 7, 'Portrait Photography Session', 'Professional 1-hour portrait session with edited images.',   200.00, 'service',      'photo1'),
(8, 999, 4, 'Hand-poured Soy Candle',       'Artisan soy wax candle with essential oils.',                 25.00, 'ready_made',   'craft1');

-- ============================================================
-- END OF MASTER MIGRATION
-- ============================================================
