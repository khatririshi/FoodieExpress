<?php
$pageTitle = 'Emergency Food Mode';
require_once 'includes/header.php';

$db = getDB();
$hour = (int)date('H');
$lateNight = ($hour >= 22 || $hour < 6);
$publicRestaurantId = getPublicRestaurantId($db);
$fastKitchen = [];

if ($publicRestaurantId > 0) {
    $stmt = $db->prepare(
        'SELECT r.*, COUNT(f.id) AS item_count
         FROM restaurants r
         LEFT JOIN food_items f ON r.id = f.restaurant_id AND f.is_available = 1
         WHERE r.id = ? AND r.is_open = 1
         GROUP BY r.id
         ORDER BY r.delivery_time ASC, r.rating DESC
         LIMIT 1'
    );
    $stmt->bind_param('i', $publicRestaurantId);
    $stmt->execute();
    $fastKitchen = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();
}

$db->close();
?>

<section class="page-hero page-hero--night">
  <div class="container page-hero__inner">
    <div>
      <div class="page-hero__eyebrow">Emergency mode</div>
      <h1 class="page-hero__title"><?= $lateNight ? 'Late Night Hunger?' : 'Need Food Fast?' ?></h1>
      <p class="page-hero__desc">
        <?= $lateNight ? 'It is ' . date('h:i A') . '. The house kitchen is surfaced first so you can make a quick decision without hunting through clutter.' : 'Jump straight to the fastest current path from craving to checkout.' ?>
      </p>
    </div>
    <div class="page-hero__metrics">
      <div class="page-hero__metric">
        <strong><?= !empty($fastKitchen) ? '01' : '00' ?></strong>
        <span>Kitchen ready</span>
      </div>
      <div class="page-hero__metric">
        <strong><?= !empty($fastKitchen) ? (int)$fastKitchen['delivery_time'] . ' min' : '--' ?></strong>
        <span>Fastest ETA</span>
      </div>
    </div>
  </div>
</section>

<div class="container section-pad">
  <?php if (empty($fastKitchen)): ?>
  <div class="empty-state card-fe">
    <h3>The kitchen is currently closed</h3>
    <p>Please check back during operating hours. Emergency mode will resume once the house kitchen is active again.</p>
  </div>
  <?php else: ?>
  <div class="editorial-panel editorial-panel--kitchen" data-reveal>
    <div class="editorial-panel__media">
      <img src="<?= resolveRestaurantImage($fastKitchen) ?>" alt="<?= htmlspecialchars($fastKitchen['name']) ?>">
    </div>
    <div class="editorial-panel__content">
      <span class="section-title__eyebrow">Fastest route to food</span>
      <h2><?= htmlspecialchars($fastKitchen['name']) ?></h2>
      <p><?= htmlspecialchars($fastKitchen['description'] ?: 'The house kitchen is open and ready to move you from urgency to checkout with fewer steps.') ?></p>
      <div class="editorial-panel__facts">
        <span><i class="bi bi-clock-history"></i> <?= (int)$fastKitchen['delivery_time'] ?> minute delivery</span>
        <span><i class="bi bi-bag-check"></i> <?= (int)$fastKitchen['item_count'] ?> available dishes</span>
        <span><i class="bi bi-star-fill"></i> Rated <?= htmlspecialchars((string)$fastKitchen['rating']) ?></span>
      </div>
      <div class="editorial-panel__actions">
        <a href="<?= SITE_URL ?>/menu.php?restaurant=<?= (int)$fastKitchen['id'] ?>" class="btn-primary-fe">Order Now <i class="bi bi-arrow-right"></i></a>
        <a href="<?= SITE_URL ?>/menu.php?filter=under200&restaurant=<?= (int)$fastKitchen['id'] ?>" class="btn-outline-fe">See Quick Picks</a>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
