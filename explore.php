<?php
$page_title = 'Explore | ArtSphere';
require_once 'config/database.php';
require_once 'includes/auth.php';

// Capture Filter Parameters
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$category_slug = isset($_GET['category']) ? $_GET['category'] : '';
$type_filter = isset($_GET['type']) ? (array)$_GET['type'] : [];
$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : null;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build Query
$sql = "SELECT l.*, c.name as cat_name, c.slug as cat_slug, u.full_name as creator_name, u.username as creator_username
        FROM listings l 
        JOIN categories c ON l.category_id = c.id
        JOIN users u ON l.creator_id = u.id 
        WHERE 1=1";
$params = [];

if ($search_query !== '') {
    $sql .= " AND (l.title LIKE ? OR l.description LIKE ? OR u.full_name LIKE ? OR u.username LIKE ?)";
    $like = '%' . $search_query . '%';
    array_push($params, $like, $like, $like, $like);
}

if ($category_slug !== '') {
    $sql .= " AND c.slug = ?";
    $params[] = $category_slug;
}

if (!empty($type_filter)) {
    $placeholders = implode(',', array_fill(0, count($type_filter), '?'));
    $sql .= " AND l.listing_type IN ($placeholders)";
    $params = array_merge($params, $type_filter);
}

if ($min_price !== null) {
    $sql .= " AND l.price >= ?";
    $params[] = $min_price;
}
if ($max_price !== null) {
    $sql .= " AND l.price <= ?";
    $params[] = $max_price;
}

// Sorting
if ($sort === 'price_asc') {
    $sql .= " ORDER BY l.price ASC";
} elseif ($sort === 'price_desc') {
    $sql .= " ORDER BY l.price DESC";
} elseif ($sort === 'alpha') {
    $sql .= " ORDER BY l.title ASC";
} else {
    $sql .= " ORDER BY l.created_at DESC";
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $listings = $stmt->fetchAll();
} catch (PDOException $e) {
    $listings = [];
    error_log("Explore Query Error: " . $e->getMessage());
}

// Get all categories for sidebar
$cat_stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$all_categories = $cat_stmt->fetchAll();

// Use shared 300-image pool (30 per category)
require_once 'includes/image_pools.php';

function get_listing_image($image_url, $cat_slug, $listing_type, $listing_id = 0) {
    return get_category_image($image_url, $cat_slug, $listing_type, $listing_id);
}

require_once 'includes/header.php';
require_once 'includes/navigation.php';
?>

<style>
/* ===== Explore Page Specific Fixes ===== */

/* Sidebar Panel */
.explore-sidebar .card {
    background-color: #141414 !important;
    border: 1px solid #2a2a2a !important;
    color: #fff !important;
}

.explore-sidebar .card-body {
    background-color: #141414 !important;
}

.explore-sidebar h5.filter-title {
    color: #ffffff !important;
    font-weight: 600;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 8px;
    padding-bottom: 12px;
    border-bottom: 1px solid #2a2a2a;
    margin-bottom: 16px;
}

.explore-sidebar h6 {
    color: #e0e0e0 !important;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 12px !important;
}

.explore-sidebar .form-check-label {
    color: #a0a0a0 !important;
    cursor: pointer;
    transition: color 0.2s ease;
    font-size: 0.9rem;
}

.explore-sidebar .form-check-input:checked + .form-check-label {
    color: #F20A68 !important;
    font-weight: 500;
}

.explore-sidebar .form-check-input {
    background-color: #1f1f1f !important;
    border-color: #3a3a3a !important;
    cursor: pointer;
}

.explore-sidebar .form-check-input:checked {
    background-color: #F20A68 !important;
    border-color: #F20A68 !important;
}

.explore-sidebar .form-control,
.explore-sidebar .form-select {
    background-color: #1f1f1f !important;
    border-color: #2a2a2a !important;
    color: #fff !important;
}

.explore-sidebar .form-control::placeholder {
    color: #555 !important;
}

.explore-sidebar .form-control:focus,
.explore-sidebar .form-select:focus {
    border-color: #F20A68 !important;
    box-shadow: 0 0 0 2px rgba(242, 10, 104, 0.15) !important;
}

.explore-sidebar .form-select option {
    background-color: #1f1f1f;
    color: #fff;
}

/* Active category highlight in sidebar */
.explore-sidebar .form-check-input[type="radio"]:checked {
    background-color: #F20A68 !important;
    border-color: #F20A68 !important;
}

/* Category count badge */
.cat-count {
    margin-left: auto;
    font-size: 0.7rem;
    background: rgba(242,10,104,0.12);
    color: #F20A68;
    padding: 2px 7px;
    border-radius: 20px;
    font-weight: 600;
}

/* Card image fallback */
.card-img-wrapper {
    background: #1a1a1a;
    position: relative;
    overflow: hidden;
    aspect-ratio: 4/3;
}

.card-img-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
    display: block;
}

/* Filter divider */
.filter-divider {
    border-color: #2a2a2a !important;
    margin: 16px 0;
}

/* Sidebar sticky top adjusted for navbar */
.sidebar-sticky {
    position: sticky;
    top: 85px;
    max-height: calc(100vh - 110px);
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #2a2a2a transparent;
}

.sidebar-sticky::-webkit-scrollbar { width: 4px; }
.sidebar-sticky::-webkit-scrollbar-track { background: transparent; }
.sidebar-sticky::-webkit-scrollbar-thumb { background: #2a2a2a; border-radius: 4px; }

/* Active category label style */
.active-category-label {
    color: #F20A68 !important;
    font-weight: 600 !important;
}
</style>

<div class="container py-5">
    <!-- Header & Breadcrumbs -->
    <div class="row mb-4 align-items-end">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="index.php" class="text-secondary text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Explore</li>
                <?php if($category_slug): ?>
                <li class="breadcrumb-item active text-white" aria-current="page"><?= htmlspecialchars(ucwords(str_replace('-', ' ', $category_slug))) ?></li>
                <?php endif; ?>
              </ol>
            </nav>
            <h1 class="display-5 fw-bold mb-2">Explore Creations</h1>
            <p class="text-secondary mb-0">Discover customizable products, artwork, and creative services.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- ===== LEFT SIDEBAR FILTERS ===== -->
        <div class="col-lg-3 explore-sidebar">
            <div class="sidebar-sticky">
                <div class="card border-0 rounded-3 p-3" style="background-color:#141414; border:1px solid #2a2a2a!important;">
                    
                    <h5 class="filter-title">
                        <i class="ph ph-faders-horizontal"></i> Filters
                    </h5>

                    <form method="GET" action="explore.php" id="filterForm">
                        <?php if($search_query): ?>
                            <input type="hidden" name="q" value="<?= htmlspecialchars($search_query) ?>">
                        <?php endif; ?>

                        <!-- ── Category ── -->
                        <h6>Category</h6>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="category" value="" id="cat_all"
                                <?= empty($category_slug) ? 'checked' : '' ?>
                                onchange="document.getElementById('filterForm').submit()">
                            <label class="form-check-label <?= empty($category_slug) ? 'active-category-label' : '' ?>" for="cat_all">All Categories</label>
                        </div>
                        <?php foreach($all_categories as $cat): ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio"
                                name="category"
                                value="<?= htmlspecialchars($cat['slug']) ?>"
                                id="cat_<?= $cat['id'] ?>"
                                <?= $category_slug === $cat['slug'] ? 'checked' : '' ?>
                                onchange="document.getElementById('filterForm').submit()">
                            <label class="form-check-label <?= $category_slug === $cat['slug'] ? 'active-category-label' : '' ?>" for="cat_<?= $cat['id'] ?>">
                                <?= htmlspecialchars($cat['name']) ?>
                            </label>
                        </div>
                        <?php endforeach; ?>

                        <hr class="filter-divider">

                        <!-- ── Listing Type ── -->
                        <h6>Listing Type</h6>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="type[]" value="ready_made" id="type1" <?= in_array('ready_made', $type_filter) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="type1">Ready-made</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="type[]" value="customizable" id="type2" <?= in_array('customizable', $type_filter) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="type2">Customizable</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="type[]" value="service" id="type3" <?= in_array('service', $type_filter) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="type3">Services / Consultation</label>
                        </div>

                        <hr class="filter-divider">

                        <!-- ── Price Range ── -->
                        <h6>Price Range</h6>
                        <div class="d-flex gap-2 mb-1">
                            <input type="number" name="min_price" class="form-control form-control-sm shadow-none"
                                placeholder="Min ₹"
                                value="<?= htmlspecialchars($_GET['min_price'] ?? '') ?>">
                            <span class="text-secondary align-self-center">–</span>
                            <input type="number" name="max_price" class="form-control form-control-sm shadow-none"
                                placeholder="Max ₹"
                                value="<?= htmlspecialchars($_GET['max_price'] ?? '') ?>">
                        </div>

                        <hr class="filter-divider">

                        <!-- ── Sort By ── -->
                        <h6>Sort By</h6>
                        <select name="sort" class="form-select form-select-sm shadow-none mb-3">
                            <option value="newest"    <?= $sort === 'newest'     ? 'selected' : '' ?>>Newest</option>
                            <option value="price_asc" <?= $sort === 'price_asc'  ? 'selected' : '' ?>>Price: Low → High</option>
                            <option value="price_desc"<?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High → Low</option>
                            <option value="alpha"     <?= $sort === 'alpha'      ? 'selected' : '' ?>>Alphabetical</option>
                        </select>

                        <button type="submit" class="btn btn-primary-custom w-100 mb-2" style="border-radius:8px;">
                            <i class="ph ph-magnifying-glass me-1"></i> Apply Filters
                        </button>
                        <a href="explore.php" class="btn btn-outline-custom w-100" style="border-radius:8px;">
                            <i class="ph ph-x me-1"></i> Clear Filters
                        </a>
                    </form>
                </div>
            </div>
        </div>

        <!-- ===== MAIN CONTENT GRID ===== -->
        <div class="col-lg-9">
            <?php if(!empty($search_query)): ?>
                <div class="mb-4 d-flex justify-content-between align-items-center">
                    <p class="text-light mb-0">Results for: <strong class="text-white">"<?= htmlspecialchars($search_query) ?>"</strong></p>
                    <a href="explore.php" class="btn btn-sm btn-outline-secondary">Clear Search</a>
                </div>
            <?php endif; ?>

            <?php if ($category_slug): ?>
            <div class="mb-3 d-flex align-items-center gap-2">
                <span class="text-secondary small">Filtering by:</span>
                <span class="badge rounded-pill px-3 py-2" style="background:rgba(242,10,104,0.15); color:#F20A68; border:1px solid rgba(242,10,104,0.3);">
                    <i class="ph ph-tag me-1"></i><?= htmlspecialchars(ucwords(str_replace('-', ' ', $category_slug))) ?>
                    <a href="explore.php" class="ms-2 text-decoration-none" style="color:#F20A68;" title="Remove filter">×</a>
                </span>
                <span class="text-secondary small">(<?= count($listings) ?> result<?= count($listings) !== 1 ? 's' : '' ?>)</span>
            </div>
            <?php endif; ?>

            <div class="row g-4">
                <?php if(empty($listings)): ?>
                    <div class="col-12 text-center py-5">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-4"
                            style="width:80px;height:80px;background:rgba(255,255,255,0.05);color:var(--text-secondary);font-size:2.5rem;">
                            <i class="ph ph-magnifying-glass"></i>
                        </div>
                        <h4 class="text-white mb-2">No creations found</h4>
                        <p class="text-secondary mb-4">Try adjusting your filters or search terms.</p>
                        <a href="explore.php" class="btn btn-primary-custom">View All</a>
                    </div>
                <?php else: ?>
                    <?php foreach($listings as $index => $item):
                        $badge = 'Customizable';
                        $icon  = 'ph-faders';
                        if ($item['listing_type'] === 'ready_made') {
                            $badge = 'Ready-made';
                            $icon  = 'ph-check-circle';
                        } elseif ($item['listing_type'] === 'service') {
                            $badge = 'Service';
                            $icon  = 'ph-paint-brush';
                        }

                        // Resolve image URL properly (pass ID for pool rotation)
                        $image_url = get_listing_image(
                            $item['image_url'] ?? '',
                            $item['cat_slug']  ?? '',
                            $item['listing_type'] ?? '',
                            (int)($item['id'] ?? 0)
                        );
                    ?>
                    <div class="col-md-6 col-xl-4 fade-in-up" style="animation-delay:<?= ($index % 4) * 0.08 ?>s;">
                        <a href="listing.php?id=<?= $item['id'] ?>" class="text-decoration-none">
                            <div class="card custom-card h-100">
                                <div class="card-img-wrapper">
                                    <img src="<?= htmlspecialchars($image_url) ?>"
                                         alt="<?= htmlspecialchars($item['title']) ?>"
                                         loading="lazy"
                                         onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?w=700&q=82&auto=format&fit=crop';"
                                         style="width:100%;height:100%;object-fit:cover;">
                                    <div class="position-absolute top-0 end-0 p-2">
                                        <span class="badge px-3 py-2 rounded-pill"
                                              style="background:rgba(0,0,0,0.65);backdrop-filter:blur(6px);border:1px solid rgba(255,255,255,0.1);color:#fff;font-size:0.72rem;">
                                            <i class="ph <?= $icon ?> me-1"></i><?= $badge ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body-custom">
                                    <p class="text-secondary small mb-1" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.06em;"><?= htmlspecialchars($item['cat_name']) ?></p>
                                    <h5 class="card-title-custom text-truncate mb-1"><?= htmlspecialchars($item['title']) ?></h5>
                                    <p class="text-secondary small mb-0 text-truncate">By <?= htmlspecialchars($item['creator_name']) ?></p>
                                    <div class="d-flex justify-content-between align-items-center mt-3 pt-3" style="border-top:1px solid #2a2a2a;">
                                        <span class="text-white fw-bold">
                                            <?= $item['price'] ? '₹' . number_format($item['price'], 2) : '<span class="text-secondary" style="font-size:0.85rem;">Request Quote</span>' ?>
                                        </span>
                                        <span class="text-secondary" style="font-size:0.8rem;">
                                            <i class="ph ph-arrow-right"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if(!empty($listings)): ?>
            <!-- Pagination -->
            <nav class="mt-5 d-flex justify-content-center">
                <ul class="pagination">
                    <li class="page-item disabled"><a class="page-link" href="#" style="background:#141414;border-color:#2a2a2a;color:#666;">Previous</a></li>
                    <li class="page-item active"><a class="page-link" href="#" style="background:#F20A68;border-color:#F20A68;color:#fff;">1</a></li>
                    <li class="page-item"><a class="page-link" href="#" style="background:#141414;border-color:#2a2a2a;color:#fff;">Next</a></li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
