<?php
require_once 'includes/config.php';
requireLogin();

$pageTitle = 'Dashboard';
require_once 'includes/header.php';

$db = getDB();
$uid = (int)$_SESSION['user_id'];
$publicRestaurant = getPublicRestaurant($db);

$userStmt = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$userStmt->bind_param('i', $uid);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

$orders = (int)$db->query("SELECT COUNT(*) FROM orders WHERE user_id = $uid")->fetch_row()[0];
$spent = (float)$db->query("SELECT COALESCE(SUM(final_amount), 0) FROM orders WHERE user_id = $uid AND order_status = 'delivered'")->fetch_row()[0];
$recentOrders = $db->query(
    "SELECT o.*, COUNT(oi.id) AS items
     FROM orders o
     LEFT JOIN order_items oi ON o.id = oi.order_id
     WHERE o.user_id = $uid
     GROUP BY o.id
     ORDER BY o.created_at DESC
     LIMIT 4"
)->fetch_all(MYSQLI_ASSOC);
$db->close();

$firstName = htmlspecialchars(explode(' ', trim((string)($user['name'] ?? 'Guest')))[0]);
$healthSummary = ucfirst(str_replace('_', ' ', (string)($user['health_condition'] ?? 'none')));
?>

<div class="container section-pad dashboard-shell">
  <section class="dashboard-hero interactive-surface depth-scene" data-reveal>
    <div class="dashboard-hero__copy" data-depth="10">
      <div class="section-title__eyebrow">Customer dashboard</div>
      <h1>Welcome back, <?= $firstName ?>.</h1>
      <p>
        Your account is now framed around one cleaner restaurant experience, with easier re-entry into the menu,
        profile, and recent order flow.
      </p>
      <div class="dashboard-hero__actions">
        <a href="<?= SITE_URL ?>/menu.php" class="btn-primary-fe">Browse Menu <i class="bi bi-arrow-right"></i></a>
        <a href="<?= SITE_URL ?>/orders.php" class="btn-outline-fe">View Orders</a>
      </div>
    </div>

    <div class="dashboard-hero__stats" data-depth="18">
      <div class="dashboard-stat-card">
        <span>Orders placed</span>
        <strong><?= $orders ?></strong>
      </div>
      <div class="dashboard-stat-card">
        <span>Total spent</span>
        <strong>Rs <?= number_format($spent, 0) ?></strong>
      </div>
      <div class="dashboard-stat-card">
        <span>Kitchen delivery</span>
        <strong><?= (int)($publicRestaurant['delivery_time'] ?? 25) ?> min</strong>
      </div>
    </div>
  </section>

  <div class="row g-4 mt-1">
    <div class="col-lg-4">
      <div class="card-fe dashboard-profile-card interactive-surface" data-reveal>
        <div class="dashboard-profile-card__avatar"><?= strtoupper(substr((string)($user['name'] ?? 'F'), 0, 1)) ?></div>
        <div class="dashboard-profile-card__eyebrow">Profile summary</div>
        <h2><?= htmlspecialchars($user['name'] ?? 'FoodieExpress User') ?></h2>
        <p><?= htmlspecialchars($user['email'] ?? '') ?></p>
        <div class="dashboard-profile-card__meta">
          <span><i class="bi bi-heart-pulse"></i> <?= $healthSummary ?></span>
          <span><i class="bi bi-lightning-charge"></i> <?= (int)($user['daily_calories'] ?? 2000) ?> kcal target</span>
        </div>
        <a href="<?= SITE_URL ?>/profile.php" class="btn-outline-fe w-100 justify-content-center">Edit Profile</a>
      </div>

      <div class="card-fe dashboard-link-panel interactive-surface mt-4" data-reveal>
        <div class="dashboard-link-panel__eyebrow">Quick access</div>
        <div class="dashboard-link-list">
          <?php foreach ([
              ['orders.php', 'bi-bag-check', 'My Orders'],
              ['ai-diet.php', 'bi-heart-pulse', 'AI Diet'],
              ['surprise.php', 'bi-stars', 'Surprise Me'],
              ['emergency.php', 'bi-lightning-charge', 'Emergency'],
              ['feedback.php', 'bi-chat-square-text', 'Feedback'],
          ] as [$url, $icon, $label]): ?>
          <a href="<?= SITE_URL ?>/<?= $url ?>" class="sidebar-link">
            <span><i class="bi <?= $icon ?>"></i><?= $label ?></span>
            <i class="bi bi-arrow-right"></i>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="dashboard-action-grid" data-reveal>
        <?php foreach ([
            ['menu.php', 'bi-grid-1x2', 'Menu', 'Return to the full house menu with refined filters and cleaner browsing.'],
            ['ai-diet.php', 'bi-heart-pulse', 'AI Diet', 'Use your saved profile to get better meal guidance faster.'],
            ['surprise.php', 'bi-stars', 'Surprise Me', 'Let the system hand you one strong recommendation in seconds.'],
            ['emergency.php', 'bi-lightning-charge', 'Emergency', 'Jump to the fastest current options when speed matters.'],
        ] as [$url, $icon, $title, $desc]): ?>
        <a href="<?= SITE_URL ?>/<?= $url ?>" class="dashboard-action-card interactive-surface">
          <div class="dashboard-action-card__icon"><i class="bi <?= $icon ?>"></i></div>
          <div>
            <h3><?= $title ?></h3>
            <p><?= $desc ?></p>
          </div>
          <span class="dashboard-action-card__cta">Open <i class="bi bi-arrow-right"></i></span>
        </a>
        <?php endforeach; ?>
      </div>

      <div class="card-fe dashboard-orders-panel interactive-surface mt-4" data-reveal>
        <div class="dashboard-orders-panel__header">
          <div>
            <div class="dashboard-link-panel__eyebrow">Recent activity</div>
            <h2>Recent orders</h2>
          </div>
          <a href="<?= SITE_URL ?>/orders.php" class="btn-outline-fe">See All</a>
        </div>

        <?php if (empty($recentOrders)): ?>
        <div class="empty-state empty-state--compact">
          <h3>No orders yet</h3>
          <p>Start with the menu and the refreshed checkout flow will take it from there.</p>
          <a href="<?= SITE_URL ?>/menu.php" class="btn-primary-fe">Order Now</a>
        </div>
        <?php else: ?>
        <div class="dashboard-order-list">
          <?php foreach ($recentOrders as $order): ?>
          <article class="dashboard-order-card">
            <div>
              <div class="dashboard-order-card__kicker">Order #<?= (int)$order['id'] ?></div>
              <h3><?= (int)$order['items'] ?> item<?= (int)$order['items'] === 1 ? '' : 's' ?></h3>
              <p><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></p>
            </div>
            <div class="dashboard-order-card__meta">
              <span class="status-pill status-pill--<?= htmlspecialchars($order['order_status']) ?>">
                <?= ucwords(str_replace('_', ' ', $order['order_status'])) ?>
              </span>
              <strong>Rs <?= number_format((float)$order['final_amount'], 2) ?></strong>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
