USE artsphere;

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50)
);

CREATE TABLE IF NOT EXISTS listings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    creator_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2),
    listing_type ENUM('ready_made', 'customizable', 'service') NOT NULL DEFAULT 'customizable',
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

ALTER TABLE creator_profiles ADD COLUMN specialties VARCHAR(255);
ALTER TABLE creator_profiles ADD COLUMN profile_image VARCHAR(255);

-- Seed Categories
INSERT IGNORE INTO categories (name, slug, description, icon) VALUES
('Interior Design', 'interior-design', 'Residential and commercial space planning.', 'ph-house'),
('Custom Furniture', 'custom-furniture', 'Woodwork, metalwork, and tailored pieces.', 'ph-armchair'),
('Painting & Digital Art', 'painting-digital-art', 'Canvas, watercolor, and digital illustrations.', 'ph-palette'),
('Handmade Crafts', 'handmade-crafts', 'Unique artisan goods and crafts.', 'ph-scissors'),
('Pottery & Ceramics', 'pottery-ceramics', 'Vases, dishware, and sculptural clay.', 'ph-coffee'),
('Jewellery Design', 'jewellery-design', 'Custom rings, necklaces, and accessories.', 'ph-diamond'),
('Photography', 'photography', 'Portraits, events, and fine art prints.', 'ph-camera'),
('Fashion & Clothing', 'fashion-clothing', 'Tailoring, alterations, and custom apparel.', 'ph-t-shirt'),
('Graphic Design', 'graphic-design', 'Logos, branding, and digital assets.', 'ph-bezier-curve'),
('Home Decor', 'home-decor', 'Textiles, lighting, and decorative items.', 'ph-lamp');

-- Seed Mock Data (Creators and Listings) for Testing
INSERT IGNORE INTO users (id, full_name, username, email, password_hash, role) VALUES 
(999, 'Sample Creator', 'samplecreator', 'creator@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'creator'),
(998, 'Jane Designer', 'janedesigner', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'creator');

INSERT IGNORE INTO creator_profiles (user_id, bio, location, specialties, profile_image) VALUES 
(999, 'I am a talented multi-disciplinary artist.', 'Local Studio', 'Furniture, Digital Art', 'face2'),
(998, 'Modern interior spaces and architecture.', 'Downtown Area', 'Interiors, Architecture', 'face1');

INSERT IGNORE INTO listings (id, creator_id, category_id, title, description, price, listing_type, image_url) VALUES
(1, 999, 2, 'Minimalist Walnut Desk', 'Custom made walnut desk with sleek modern legs.', 450.00, 'customizable', 'wood1'),
(2, 999, 3, 'Ocean Depths Canvas', 'Abstract painting inspired by the sea.', 220.00, 'ready_made', 'art1'),
(3, 998, 1, 'Living Space Concept', 'Full interior design consultation and moodboard.', 300.00, 'service', 'interior1'),
(4, 999, 5, 'Textured Clay Vase', 'Hand-thrown ceramic vase with unique texture.', 85.00, 'customizable', 'pottery1'),
(5, 998, 1, 'Minimalist Office Design', 'Complete office spatial planning and styling.', 500.00, 'service', 'office1');
