<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?= BASE_URL ?>index.php">
            Art<span>Sphere</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-4">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'explore.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>explore.php">Explore</a>
                </li>
                <?php if(is_logged_in()): 
                    $nav_user = get_logged_in_user();
                ?>
                    <?php if($nav_user['role'] === 'creator'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Creator Tools</a>
                            <ul class="dropdown-menu dropdown-menu-dark bg-dark border-secondary">
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>profile.php?username=<?= urlencode($nav_user['username']) ?>"><i class="ph ph-user me-2"></i>My Public Profile</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>edit_profile.php"><i class="ph ph-pencil me-2"></i>Edit Profile</a></li>
                                <li><hr class="dropdown-divider border-secondary"></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>manage_listings.php">My Portfolio</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>add_listing.php">Create Listing</a></li>
                                <li><hr class="dropdown-divider border-secondary"></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>incoming_requests.php">Incoming Requests</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>manage_quotations.php">Quotations</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>design_workspace.php">Design Workspace</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>creator_projects.php">My Projects</a></li>
                                <li><hr class="dropdown-divider border-secondary"></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>messages.php">Messages</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>analytics.php">Analytics</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">My Hub</a>
                            <ul class="dropdown-menu dropdown-menu-dark bg-dark border-secondary">
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>profile.php?username=<?= urlencode($nav_user['username']) ?>"><i class="ph ph-user me-2"></i>My Profile</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>edit_profile.php"><i class="ph ph-pencil me-2"></i>Edit Profile</a></li>
                                <li><hr class="dropdown-divider border-secondary"></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>my_requests.php">My Customization Requests</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>my_quotations.php">My Quotations</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>customer_projects.php">My Projects</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>messages.php">Messages</a></li>
                                <li><hr class="dropdown-divider border-secondary"></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>wishlist.php"><i class="ph ph-heart me-2" style="color:#F20A68;"></i>My Wishlist</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'categories.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>categories.php">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'creators.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>creators.php">Creators</a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <form class="d-flex me-4" role="search" action="<?= BASE_URL ?>explore.php" method="GET">
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-secondary" id="search-icon"><i class="ph ph-magnifying-glass"></i></span>
                    <input type="search" name="q" class="form-control bg-dark border-secondary text-light shadow-none" placeholder="Search creations..." aria-label="Search" aria-describedby="search-icon">
                </div>
            </form>
            
            <div class="d-flex align-items-center gap-3">
                <?php if(is_logged_in()): ?>
                    <a href="<?= BASE_URL ?>dashboard.php" class="nav-link text-white"><i class="ph ph-user-circle me-1"></i> <?= htmlspecialchars($_SESSION['username']) ?></a>
                    <a href="<?= BASE_URL ?>logout.php" class="btn btn-outline-custom btn-sm">Logout</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>login.php" class="nav-link">Sign In</a>
                    <a href="<?= BASE_URL ?>register.php" class="btn btn-primary-custom">Join as Creator</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
