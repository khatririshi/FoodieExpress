<?php
$pageTitle = 'Home';
require_once 'includes/header.php';

$db = getDB();
$publicRestaurant = getPublicRestaurant($db);
$publicRestaurantId = (int)($publicRestaurant['id'] ?? 0);
$topFoods = [];
$availableDishCount = 0;

if ($publicRestaurantId > 0) {
    $foodStmt = $db->prepare(
        'SELECT f.*, r.name AS restaurant_name
         FROM food_items f
         JOIN restaurants r ON f.restaurant_id = r.id
         WHERE f.is_available = 1 AND f.restaurant_id = ?
         ORDER BY f.rating DESC, f.id DESC
         LIMIT 6'
    );
    $foodStmt->bind_param('i', $publicRestaurantId);
    $foodStmt->execute();
    $topFoods = $foodStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $foodStmt->close();

    $countStmt = $db->prepare('SELECT COUNT(*) AS total FROM food_items WHERE is_available = 1 AND restaurant_id = ?');
    $countStmt->bind_param('i', $publicRestaurantId);
    $countStmt->execute();
    $availableDishCount = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
    $countStmt->close();
}

$db->close();

$heroDishes = array_slice($topFoods, 0, 3);
$heroPrimary = $topFoods[0] ?? [
    'name' => 'Chicken Biryani',
    'description' => 'Aromatic basmati rice with tender chicken and spice.',
    'price' => 320,
    'rating' => 4.8,
    'is_veg' => 0,
    'category' => 'Main Course',
];
$heroSecondary = array_slice($topFoods, 1, 2);
$menuPreview = array_slice($topFoods, 0, 4);
$signatureLine = $heroDishes
    ? implode(' | ', array_map(fn($food) => htmlspecialchars($food['name']), $heroDishes))
    : 'Curated comfort plates, calmer discovery, and a more polished ordering flow.';
$restaurantDescription = trim((string)($publicRestaurant['description'] ?? ''));

if ($restaurantDescription === '') {
    $restaurantDescription = 'A single admin-managed kitchen with a premium storefront, quicker decision-making, and presentation that feels intentional from landing to checkout.';
}
?>

<section class="hero hero-home">
  <div class="hero-ambience"></div>
  <div class="hero-grain"></div>
  <div class="container">
    <div class="hero-home__layout">
      <div class="hero-home__content hero-copy" data-reveal>
        <div class="hero-eyebrow">
          <span class="eyebrow-dot"></span>
          One trusted kitchen, shown more clearly
        </div>
        <h1><?= htmlspecialchars($publicRestaurant['name'] ?? 'Our house kitchen') ?> serves food users can understand at a glance.</h1>
        <p class="hero-desc">
          Better food photography, clearer prices, and simpler information make the first screen feel more useful and more attractive.
        </p>

        <div class="hero-home__service-row">
          <span><i class="bi bi-stars"></i> <?= htmlspecialchars($publicRestaurant['cuisine_type'] ?? 'Curated comfort menu') ?></span>
          <span><i class="bi bi-clock-history"></i> <?= (int)($publicRestaurant['delivery_time'] ?? 25) ?> min delivery</span>
          <span><i class="bi bi-bag-check"></i> Rs <?= number_format((float)($publicRestaurant['min_order'] ?? 150), 0) ?> minimum order</span>
        </div>

        <div class="hero-actions">
          <a href="<?= SITE_URL ?>/menu.php" class="btn-primary-fe">
            Order Now <i class="bi bi-arrow-right"></i>
          </a>
          <a href="<?= SITE_URL ?>/restaurants.php" class="btn-outline-fe">
            View Kitchen Details
          </a>
        </div>

        <div class="hero-stats">
          <div class="hero-stat-card">
            <strong><?= $publicRestaurantId > 0 ? '01' : '00' ?></strong>
            <span>Restaurant</span>
          </div>
          <div class="hero-stat-card">
            <strong><?= $availableDishCount ?></strong>
            <span>Live dishes</span>
          </div>
          <div class="hero-stat-card">
            <strong><?= htmlspecialchars((string)($publicRestaurant['rating'] ?? '4.8')) ?></strong>
            <span>Average rating</span>
          </div>
        </div>
      </div>

      <div class="hero-home__spotlight" data-reveal>
        <article class="hero-feature-card interactive-surface">
          <div class="hero-feature-card__media">
            <img src="<?= resolveFoodImage($heroPrimary) ?>" alt="<?= htmlspecialchars($heroPrimary['name']) ?>">
          </div>
          <div class="hero-feature-card__body">
            <span class="hero-feature-card__eyebrow">Featured dish</span>
            <div class="hero-feature-card__topline">
              <h2><?= htmlspecialchars($heroPrimary['name']) ?></h2>
              <span class="food-rating">&#9733; <?= htmlspecialchars((string)($heroPrimary['rating'] ?? '4.8')) ?></span>
            </div>
            <p><?= htmlspecialchars(substr((string)($heroPrimary['description'] ?? 'A signature dish from the house kitchen.'), 0, 130)) ?></p>
            <div class="hero-feature-card__footer">
              <div>
                <strong>Rs <?= number_format((float)($heroPrimary['price'] ?? 320), 0) ?></strong>
                <span><?= htmlspecialchars($heroPrimary['category'] ?? 'Signature plate') ?></span>
              </div>
              <a href="<?= SITE_URL ?>/menu.php" class="btn-outline-fe">Open Menu</a>
            </div>
          </div>
        </article>

        <div class="hero-home__aux">
          <div class="hero-home__snapshot interactive-surface">
            <span class="hero-home__snapshot-label">Kitchen snapshot</span>
            <strong><?= htmlspecialchars($publicRestaurant['name'] ?? 'FoodieExpress Kitchen') ?></strong>
            <p><?= htmlspecialchars($restaurantDescription) ?></p>
            <a href="<?= SITE_URL ?>/restaurants.php">Learn more <i class="bi bi-arrow-right"></i></a>
          </div>

          <div class="hero-home__mini-list">
            <?php foreach ($heroSecondary as $food): ?>
            <article class="hero-home__mini-card interactive-surface">
              <img src="<?= resolveFoodImage($food) ?>" alt="<?= htmlspecialchars($food['name']) ?>">
              <div class="hero-home__mini-card-body">
                <strong><?= htmlspecialchars($food['name']) ?></strong>
                <span><?= htmlspecialchars($food['category'] ?: 'House special') ?></span>
              </div>
              <div class="hero-home__mini-card-price">Rs <?= number_format((float)$food['price'], 0) ?></div>
            </article>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="search-section search-section--home">
  <div class="container">
    <div class="search-wrap search-wrap--home">
      <div class="search-wrap__field">
        <i class="bi bi-search search-icon"></i>
        <input type="text" id="globalSearch" placeholder="Search dishes, ingredients, or categories..." aria-label="Search dishes, ingredients, or categories">
      </div>
      <button class="btn-search" onclick="performSearch()">Search</button>
    </div>
    <div class="filter-chips">
      <a href="<?= SITE_URL ?>/menu.php" class="chip active">All Dishes</a>
      <a href="<?= SITE_URL ?>/menu.php?filter=veg" class="chip">Veg</a>
      <a href="<?= SITE_URL ?>/menu.php?filter=nonveg" class="chip">Non-Veg</a>
      <a href="<?= SITE_URL ?>/menu.php?filter=healthy" class="chip">Healthy</a>
      <a href="<?= SITE_URL ?>/menu.php?filter=under200" class="chip">Under Rs 200</a>
    </div>
  </div>
</section>

<section class="section-pad">
  <div class="container">
    <div class="section-title" data-reveal>
      <span class="section-title__eyebrow">Best sellers today</span>
      <h2>Popular dishes from the current menu.</h2>
      <p>Simple cards, better photos, and clear pricing so users can decide quickly.</p>
    </div>

    <?php if (empty($topFoods)): ?>
    <div class="empty-state card-fe" data-reveal>
      <h3>Menu updates are on the way</h3>
      <p>The house kitchen does not have visible dishes yet. Check back once the admin publishes the menu.</p>
      <a href="<?= SITE_URL ?>/restaurants.php" class="btn-primary-fe">View The Kitchen</a>
    </div>
    <?php else: ?>
    <div class="row g-4">
      <?php foreach ($topFoods as $food): ?>
      <div class="col-md-6 col-xl-4" data-reveal>
        <article class="food-card food-card--stacked interactive-surface h-100">
          <div class="food-image-box">
            <img src="<?= resolveFoodImage($food) ?>" alt="<?= htmlspecialchars($food['name']) ?>" class="food-thumb-image">
          </div>
          <div class="food-info">
            <div class="food-info__meta">
              <span class="<?= $food['is_veg'] ? 'veg-dot' : 'nonveg-dot' ?>"></span>
              <span><?= $food['is_healthy'] ? 'Balanced pick' : 'House favourite' ?></span>
            </div>
            <h3 class="food-name"><?= htmlspecialchars($food['name']) ?></h3>
            <p class="food-desc"><?= htmlspecialchars(substr($food['description'], 0, 110)) ?>...</p>
            <div class="food-card__footer">
              <div>
                <span class="food-price">Rs <?= number_format($food['price'], 0) ?></span>
                <div class="food-card__subline"><?= htmlspecialchars($food['category'] ?: 'Signature house plate') ?></div>
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
  </div>
</section>

<section class="section-pad section-pad--warm">
  <div class="container">
    <div class="home-story-grid">
      <div class="editorial-panel editorial-panel--kitchen" data-reveal>
        <div class="editorial-panel__media">
          <img src="<?= resolveRestaurantImage($publicRestaurant ?? []) ?>" alt="<?= htmlspecialchars($publicRestaurant['name'] ?? 'FoodieExpress Kitchen') ?>">
        </div>
        <div class="editorial-panel__content">
          <span class="section-title__eyebrow">About the kitchen</span>
          <h2><?= htmlspecialchars($publicRestaurant['name'] ?? 'FoodieExpress Signature Kitchen') ?></h2>
          <p><?= htmlspecialchars($restaurantDescription) ?></p>
          <div class="editorial-panel__facts">
            <span><i class="bi bi-check2-circle"></i> <?= htmlspecialchars($publicRestaurant['cuisine_type'] ?? 'Curated comfort menu') ?></span>
            <span><i class="bi bi-clock-history"></i> <?= (int)($publicRestaurant['delivery_time'] ?? 25) ?> minute delivery window</span>
            <span><i class="bi bi-star-fill"></i> Rated <?= htmlspecialchars((string)($publicRestaurant['rating'] ?? '4.8')) ?></span>
          </div>
          <div class="editorial-panel__actions">
            <a href="<?= SITE_URL ?>/restaurants.php" class="btn-primary-fe">View Kitchen Details <i class="bi bi-arrow-right"></i></a>
            <a href="<?= SITE_URL ?>/menu.php?restaurant=<?= $publicRestaurantId ?>" class="btn-outline-fe">Open Full Menu</a>
          </div>
        </div>
      </div>

      <div class="home-story-panel card-fe interactive-surface" data-reveal>
        <span class="section-title__eyebrow">Quick menu guide</span>
        <h3>What users usually look for first</h3>
        <div class="home-story-panel__list">
          <?php foreach ($menuPreview as $food): ?>
          <article class="home-story-panel__item">
            <img src="<?= resolveFoodImage($food) ?>" alt="<?= htmlspecialchars($food['name']) ?>">
            <div>
              <strong><?= htmlspecialchars($food['name']) ?></strong>
              <span><?= htmlspecialchars($food['category'] ?: 'House special') ?></span>
            </div>
            <em>Rs <?= number_format((float)$food['price'], 0) ?></em>
          </article>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section-pad">
  <div class="container">
    <div class="section-title" data-reveal>
      <span class="section-title__eyebrow">Helpful tools</span>
      <h2>Extra features, but presented more clearly.</h2>
      <p>Everything important now uses the same cleaner card system and easier calls to action.</p>
    </div>

    <div class="row g-4">
      <?php foreach ([
          ['AI Diet Assistant', 'Get meal suggestions based on your health goals and saved profile.', 'ai-diet.php', 'bi-heart-pulse'],
          ['Surprise Me', 'Let FoodieExpress recommend one strong option when you do not want to think too much.', 'surprise.php', 'bi-stars'],
          ['Emergency Mode', 'Jump straight to quick and dependable picks when you need food fast.', 'emergency.php', 'bi-lightning-charge'],
      ] as [$title, $desc, $url, $icon]): ?>
      <div class="col-md-6 col-xl-4" data-reveal>
        <div class="feature-card interactive-surface h-100">
          <div class="feature-icon"><i class="bi <?= $icon ?>"></i></div>
          <h3><?= $title ?></h3>
          <p><?= $desc ?></p>
          <a href="<?= SITE_URL ?>/<?= $url ?>" class="feature-link">Open feature <i class="bi bi-arrow-right"></i></a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
