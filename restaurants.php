<?php
$pageTitle = 'Kitchen';
require_once 'includes/header.php';

$db = getDB();
$publicRestaurantId = getPublicRestaurantId($db);
$restaurant = null;
$featuredFoods = [];

if ($publicRestaurantId > 0) {
    $sql = 'SELECT r.*, COUNT(f.id) AS food_count
            FROM restaurants r
            LEFT JOIN food_items f ON r.id = f.restaurant_id AND f.is_available = 1
            WHERE r.id = ?
            GROUP BY r.id
            LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $publicRestaurantId);
    $stmt->execute();
    $restaurant = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    $foodsStmt = $db->prepare(
        'SELECT f.*, r.name AS restaurant_name
         FROM food_items f
         JOIN restaurants r ON f.restaurant_id = r.id
         WHERE f.is_available = 1 AND f.restaurant_id = ?
         ORDER BY f.rating DESC, f.id DESC
         LIMIT 4'
    );
    $foodsStmt->bind_param('i', $publicRestaurantId);
    $foodsStmt->execute();
    $featuredFoods = $foodsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $foodsStmt->close();
}

$db->close();
?>

<section class="page-hero page-hero--discover">
  <div class="container page-hero__inner">
    <div>
      <div class="page-hero__eyebrow">Single restaurant experience</div>
      <h1 class="page-hero__title"><?= htmlspecialchars($restaurant['name'] ?? 'Our House Kitchen') ?></h1>
      <p class="page-hero__desc">
        One admin-managed restaurant, clearer food presentation, and a simpler page for users who just want to see the kitchen and order.
      </p>
    </div>
    <div class="page-hero__metrics">
      <div class="page-hero__metric">
        <strong><?= $restaurant ? '01' : '00' ?></strong>
        <span>Kitchen</span>
      </div>
      <div class="page-hero__metric">
        <strong><?= $restaurant ? (int)($restaurant['food_count'] ?? 0) : 0 ?></strong>
        <span>Dishes</span>
      </div>
      <div class="page-hero__metric">
        <strong><?= $restaurant ? (int)($restaurant['delivery_time'] ?? 25) . ' min' : 'Fast' ?></strong>
        <span>Delivery pace</span>
      </div>
    </div>
  </div>
</section>

<section class="section-pad">
  <div class="container">
    <?php if (!$restaurant): ?>
    <div class="empty-state card-fe" data-reveal>
      <h3>No kitchen matched this view</h3>
      <p>The house kitchen profile is not available right now. Check back after the admin publishes it.</p>
      <a href="<?= SITE_URL ?>/restaurants.php" class="btn-primary-fe">Reset View</a>
    </div>
    <?php else: ?>
    <div class="editorial-panel editorial-panel--kitchen" data-reveal>
      <div class="editorial-panel__media">
        <img src="<?= resolveRestaurantImage($restaurant) ?>" alt="<?= htmlspecialchars($restaurant['name']) ?>">
      </div>
      <div class="editorial-panel__content">
        <span class="section-title__eyebrow">Kitchen profile</span>
        <h2><?= htmlspecialchars($restaurant['name']) ?></h2>
        <p><?= htmlspecialchars($restaurant['description'] ?: 'An admin-managed kitchen focused on clear presentation, dependable delivery details, and a simpler ordering experience.') ?></p>
        <div class="editorial-panel__facts">
          <span><i class="bi bi-stars"></i> <?= htmlspecialchars($restaurant['cuisine_type']) ?></span>
          <span><i class="bi bi-clock-history"></i> <?= (int)$restaurant['delivery_time'] ?> minutes</span>
          <span><i class="bi bi-bag-check"></i> Rs <?= number_format((float)$restaurant['min_order'], 0) ?> minimum order</span>
          <span><i class="bi bi-telephone-forward"></i> Rs <?= number_format((float)$restaurant['delivery_fee'], 0) ?> delivery fee</span>
        </div>
        <div class="editorial-panel__actions">
          <a href="<?= SITE_URL ?>/menu.php?restaurant=<?= (int)$restaurant['id'] ?>" class="btn-primary-fe">Open Menu <i class="bi bi-arrow-right"></i></a>
          <a href="<?= SITE_URL ?>/dashboard.php" class="btn-outline-fe">Go To Dashboard</a>
        </div>
      </div>
    </div>

    <div class="section-title mt-5" data-reveal>
      <span class="section-title__eyebrow">Featured plates</span>
      <h2>Popular dishes from this kitchen.</h2>
      <p>Better food photos, shorter descriptions, and pricing that is easier to scan.</p>
    </div>

    <div class="row g-4">
      <?php foreach ($featuredFoods as $food): ?>
      <div class="col-md-6 col-xl-3" data-reveal>
        <article class="food-card food-card--stacked interactive-surface h-100">
          <div class="food-image-box">
            <img src="<?= resolveFoodImage($food) ?>" alt="<?= htmlspecialchars($food['name']) ?>" class="food-thumb-image">
          </div>
          <div class="food-info">
            <div class="food-info__meta">
              <span class="<?= $food['is_veg'] ? 'veg-dot' : 'nonveg-dot' ?>"></span>
              <span><?= $food['is_healthy'] ? 'Healthy-leaning' : 'Signature plate' ?></span>
            </div>
            <h3 class="food-name"><?= htmlspecialchars($food['name']) ?></h3>
            <p class="food-desc"><?= htmlspecialchars(substr($food['description'], 0, 90)) ?>...</p>
            <div class="food-card__footer">
              <div>
                <span class="food-price">Rs <?= number_format((float)$food['price'], 0) ?></span>
                <div class="food-card__subline"><?= htmlspecialchars($food['category'] ?: 'Signature plate') ?></div>
              </div>
              <button class="btn-add-cart" onclick='addToCart(<?= (int)$food["id"] ?>, <?= json_encode($food["name"]) ?>, <?= (float)$food["price"] ?>)'>Add</button>
            </div>
          </div>
        </article>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
