<?php
$pageTitle = 'Menu';
require_once 'includes/header.php';

$db = getDB();
$search = sanitize($_GET['search'] ?? '');
$filter = sanitize($_GET['filter'] ?? 'all');
$requestedRestaurant = (int)($_GET['restaurant'] ?? 0);
$sortBy = sanitize($_GET['sort'] ?? 'popular');
$publicRestaurantId = getPublicRestaurantId($db);
$restaurant = $publicRestaurantId > 0 ? normalizePublicRestaurantId($db, $requestedRestaurant) : 0;
$currentRestaurant = getPublicRestaurant($db);

$where = ['f.is_available = 1'];
$params = [];
$types = '';

if ($search !== '') {
    $keyword = "%$search%";
    $where[] = '(f.name LIKE ? OR f.description LIKE ? OR r.name LIKE ?)';
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
    $types .= 'sss';
}

if ($filter === 'veg') {
    $where[] = 'f.is_veg = 1';
}
if ($filter === 'nonveg') {
    $where[] = 'f.is_veg = 0';
}
if ($filter === 'healthy') {
    $where[] = 'f.is_healthy = 1';
}
if ($filter === 'under200') {
    $where[] = 'f.price <= 200';
}
if ($restaurant > 0) {
    $where[] = 'f.restaurant_id = ?';
    $params[] = $restaurant;
    $types .= 'i';
}

$orderClause = match ($sortBy) {
    'price_low' => 'f.price ASC',
    'price_high' => 'f.price DESC',
    'rating' => 'f.rating DESC',
    default => 'f.rating DESC'
};

$sql = 'SELECT f.*, r.name AS restaurant_name, r.delivery_time, r.is_open
        FROM food_items f
        JOIN restaurants r ON f.restaurant_id = r.id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY ' . $orderClause;

if ($params) {
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $foods = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $foods = $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

$db->close();
?>

<section class="page-hero page-hero--discover page-hero--compact">
  <div class="container page-hero__inner">
    <div>
      <div class="page-hero__eyebrow">Menu atelier</div>
      <h1 class="page-hero__title"><?= htmlspecialchars($currentRestaurant['name'] ?? 'House Kitchen Menu') ?></h1>
      <p class="page-hero__desc">
        The same filters and sorting still work, but the browsing experience is now calmer, clearer, and anchored to one admin-managed restaurant.
      </p>
    </div>
    <div class="page-hero__metrics">
      <div class="page-hero__metric">
        <strong><?= count($foods) ?></strong>
        <span>Visible dishes</span>
      </div>
      <div class="page-hero__metric">
        <strong><?= (int)($currentRestaurant['delivery_time'] ?? 25) ?> min</strong>
        <span>Delivery</span>
      </div>
      <div class="page-hero__metric">
        <strong><?= !empty($currentRestaurant['is_open']) ? 'Open' : 'Closed' ?></strong>
        <span>Kitchen status</span>
      </div>
    </div>
  </div>
</section>

<div class="container section-pad menu-layout">
  <div class="row g-4">
    <aside class="col-xl-3 col-lg-4">
      <div class="menu-sidebar-shell">
        <div class="menu-sidebar card-fe interactive-surface">
          <div class="menu-sidebar__search">
            <div class="search-wrap search-wrap--sidebar">
              <i class="bi bi-search search-icon"></i>
              <input type="text" id="globalSearch" value="<?= htmlspecialchars($search) ?>" placeholder="Search the menu...">
              <button class="btn-search" onclick="performSearch()">Go</button>
            </div>
          </div>

          <div class="menu-sidebar__scroll">
            <div class="menu-sidebar__section">
              <div class="menu-sidebar__label">Filter</div>
              <?php
              $filters = [
                  'all' => ['bi-grid', 'All Dishes'],
                  'veg' => ['bi-flower1', 'Veg Only'],
                  'nonveg' => ['bi-fire', 'Non-Veg'],
                  'healthy' => ['bi-heart-pulse', 'Healthy'],
                  'under200' => ['bi-cash-stack', 'Under Rs 200'],
              ];
              foreach ($filters as $key => [$icon, $label]):
              ?>
              <a
                href="?filter=<?= $key ?>&sort=<?= urlencode($sortBy) ?>&restaurant=<?= $restaurant ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                class="menu-filter-link <?= $filter === $key ? 'is-active' : '' ?>"
              >
                <span><i class="bi <?= $icon ?>"></i><?= $label ?></span>
                <?php if ($filter === $key): ?><i class="bi bi-check2"></i><?php endif; ?>
              </a>
              <?php endforeach; ?>
            </div>

            <div class="menu-sidebar__section">
              <div class="menu-sidebar__label">Sort</div>
              <?php foreach ([
                  'popular' => 'Most Popular',
                  'price_low' => 'Price: Low To High',
                  'price_high' => 'Price: High To Low',
                  'rating' => 'Top Rated',
              ] as $value => $label): ?>
              <a
                href="?sort=<?= $value ?>&filter=<?= urlencode($filter) ?>&restaurant=<?= $restaurant ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                class="menu-sort-link <?= $sortBy === $value ? 'is-active' : '' ?>"
              >
                <?= $label ?>
              </a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </aside>

    <section class="col-xl-9 col-lg-8">
      <?php if ($currentRestaurant): ?>
      <div class="menu-restaurant-banner card-fe interactive-surface" data-reveal>
        <div class="menu-restaurant-banner__media">
          <img src="<?= resolveRestaurantImage($currentRestaurant) ?>" alt="<?= htmlspecialchars($currentRestaurant['name']) ?>" class="restaurant-summary-image">
        </div>
        <div class="menu-restaurant-banner__body">
          <div class="menu-restaurant-banner__eyebrow">House kitchen</div>
          <h2><?= htmlspecialchars($currentRestaurant['name']) ?></h2>
          <p>
            <?= htmlspecialchars($currentRestaurant['cuisine_type']) ?>
            &middot; Rated <?= htmlspecialchars((string)$currentRestaurant['rating']) ?>
            &middot; <?= (int)$currentRestaurant['delivery_time'] ?> minute delivery
          </p>
        </div>
        <div class="menu-restaurant-banner__status <?= !empty($currentRestaurant['is_open']) ? 'is-open' : 'is-closed' ?>">
          <?= !empty($currentRestaurant['is_open']) ? 'Open Now' : 'Closed' ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="menu-content__header" data-reveal>
        <div>
          <div class="section-title__eyebrow">Menu results</div>
          <h2>
            <?= $search !== '' ? 'Results For "' . htmlspecialchars($search) . '"' : ($filter !== 'all' ? ucwords(str_replace('_', ' ', $filter)) . ' Dishes' : 'All Dishes') ?>
          </h2>
          <p>Scroll the menu naturally while the desktop filter rail stays anchored and internally scrollable.</p>
        </div>
        <div class="menu-content__count"><?= count($foods) ?> items</div>
      </div>

      <?php if (empty($foods)): ?>
      <div class="empty-state card-fe" data-reveal>
        <h3>No dishes matched this combination</h3>
        <p>Try another filter or clear the search to see the full house menu again.</p>
        <a href="<?= SITE_URL ?>/menu.php?restaurant=<?= $restaurant ?>" class="btn-primary-fe">Reset Menu</a>
      </div>
      <?php else: ?>
      <div class="row g-4" id="foodGrid">
        <?php foreach ($foods as $food): ?>
        <div class="col-md-6" data-reveal>
          <article class="food-card food-card--menu interactive-surface h-100">
            <div class="food-image-box food-image-box--menu">
              <img src="<?= resolveFoodImage($food) ?>" alt="<?= htmlspecialchars($food['name']) ?>" class="food-thumb-image">
            </div>
            <div class="food-info">
              <div class="food-info__meta">
                <span class="<?= $food['is_veg'] ? 'veg-dot' : 'nonveg-dot' ?>"></span>
                <span><?= $food['is_healthy'] ? 'Healthy' : 'Kitchen favourite' ?></span>
              </div>
              <div class="food-card__topline">
                <h3 class="food-name"><?= htmlspecialchars($food['name']) ?></h3>
                <span class="food-rating">&#9733; <?= htmlspecialchars((string)$food['rating']) ?></span>
              </div>
              <p class="food-desc"><?= htmlspecialchars(substr($food['description'], 0, 110)) ?>...</p>
              <div class="food-card__meta-row">
                <span><?= htmlspecialchars($food['category'] ?: 'House signature') ?></span>
                <?php if (!empty($food['calories'])): ?>
                <span><?= (int)$food['calories'] ?> cal</span>
                <?php endif; ?>
              </div>
              <div class="food-card__footer">
                <div>
                  <span class="food-price">Rs <?= number_format((float)$food['price'], 0) ?></span>
                </div>
                <button class="btn-add-cart" onclick='addToCart(<?= (int)$food["id"] ?>, <?= json_encode($food["name"]) ?>, <?= (float)$food["price"] ?>)'>
                  Add To Cart
                </button>
              </div>
            </div>
          </article>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </section>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
