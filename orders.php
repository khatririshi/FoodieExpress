<?php
require_once 'includes/config.php';
requireLogin();

$pageTitle = 'My Orders';
require_once 'includes/header.php';

$db = getDB();
$uid = (int)$_SESSION['user_id'];
$statusSteps = ['placed' => 0, 'confirmed' => 1, 'preparing' => 2, 'out_for_delivery' => 3, 'delivered' => 4];
$cancelableStatuses = ['placed', 'confirmed', 'preparing'];

if (isset($_POST['cancel_order'])) {
    $orderId = (int)($_POST['order_id'] ?? 0);

    $orderStmt = $db->prepare('SELECT id, order_status FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
    $orderStmt->bind_param('ii', $orderId, $uid);
    $orderStmt->execute();
    $orderToCancel = $orderStmt->get_result()->fetch_assoc();
    $orderStmt->close();

    if (!$orderToCancel) {
        setFlash('error', 'Order not found.');
    } elseif (!in_array((string)$orderToCancel['order_status'], $cancelableStatuses, true)) {
        setFlash('error', 'This order can no longer be cancelled.');
    } else {
        $cancelStmt = $db->prepare('UPDATE orders SET order_status = ?, is_acknowledged = 1 WHERE id = ? AND user_id = ?');
        $cancelledStatus = 'cancelled';
        $cancelStmt->bind_param('sii', $cancelledStatus, $orderId, $uid);
        $cancelStmt->execute();
        $cancelStmt->close();

        setFlash('success', 'Your order has been cancelled.');
    }

    header('Location: ' . SITE_URL . '/orders.php?track=' . $orderId);
    exit;
}

if (isset($_POST['acknowledge_order'])) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $db->query("UPDATE orders SET is_acknowledged = 1 WHERE id = $orderId AND user_id = $uid");
    header('Location: ' . SITE_URL . '/orders.php');
    exit;
}

if (isset($_POST['reorder'])) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $items = $db->query("SELECT food_id, quantity FROM order_items WHERE order_id = $orderId")->fetch_all(MYSQLI_ASSOC);
    clearUserCart($db, $uid);
    foreach ($items as $item) {
        $foodId = (int)$item['food_id'];
        $quantity = (int)$item['quantity'];
        if ($foodId > 0) {
            upsertCartItem($db, $uid, $foodId, $quantity, true);
        }
    }
    header('Location: ' . SITE_URL . '/checkout.php');
    exit;
}

if (isset($_POST['submit_rating'])) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $review = sanitize($_POST['review'] ?? '');
    $items = $db->query("SELECT food_id FROM order_items WHERE order_id = $orderId")->fetch_all(MYSQLI_ASSOC);

    foreach ($items as $item) {
        $foodId = (int)$item['food_id'];
        if ($foodId <= 0) {
            continue;
        }

        $stmt = $db->prepare(
            'INSERT INTO ratings (user_id, food_id, rating, review)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE rating = VALUES(rating), review = VALUES(review)'
        );
        $stmt->bind_param('iiis', $uid, $foodId, $rating, $review);
        $stmt->execute();
        $stmt->close();

        $db->query("UPDATE food_items SET rating = ROUND((SELECT AVG(rating) FROM ratings WHERE food_id = $foodId), 1) WHERE id = $foodId");
    }

    $db->query("UPDATE orders SET is_rated = 1, is_acknowledged = 1 WHERE id = $orderId AND user_id = $uid");
    header('Location: ' . SITE_URL . '/orders.php?rated=1');
    exit;
}

$orders = $db->query(
    "SELECT o.*, COUNT(oi.id) AS item_count
     FROM orders o
     LEFT JOIN order_items oi ON o.id = oi.order_id
     WHERE o.user_id = $uid
     GROUP BY o.id
     ORDER BY o.created_at DESC"
)->fetch_all(MYSQLI_ASSOC);

$trackOrder = null;
if (isset($_GET['track']) || isset($_GET['new'])) {
    $orderId = (int)($_GET['track'] ?? $_GET['new']);
    $trackOrder = $db->query(
        "SELECT o.*,
                GROUP_CONCAT(CONCAT(COALESCE(f.name, 'Removed item'), ' x', oi.quantity) SEPARATOR ', ') AS items_list
         FROM orders o
         JOIN order_items oi ON o.id = oi.order_id
         LEFT JOIN food_items f ON oi.food_id = f.id
         WHERE o.id = $orderId AND o.user_id = $uid
         GROUP BY o.id"
    )->fetch_assoc();
}

$db->close();
?>

<?php if (isset($_GET['rated'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => showToast('success', 'Thanks for your rating.'));</script>
<?php endif; ?>

<div style="background:var(--dark);padding:2rem 0 1.5rem;border-bottom:1px solid rgba(255,255,255,0.05)">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
      <h2 style="font-family:'Syne',sans-serif;color:white;margin:0;font-size:1.6rem">My Orders</h2>
      <a href="<?= SITE_URL ?>/menu.php" class="btn-primary-fe" style="font-size:0.85rem;padding:0.55rem 1.3rem">+ New Order</a>
    </div>
  </div>
</div>

<div class="container py-4" style="max-width:960px">
  <?php if ($trackOrder): ?>
  <?php
    $currentStep = $statusSteps[$trackOrder['order_status']] ?? 0;
    $progress = ($currentStep / 4) * 100;
    $trackingSteps = [
        ['placed', 'Placed'],
        ['confirmed', 'Confirmed'],
        ['preparing', 'Preparing'],
        ['out_for_delivery', 'On the Way'],
        ['delivered', 'Delivered']
    ];
  ?>
  <div class="card-fe p-4 mb-4" id="liveTracker" data-order-id="<?= $trackOrder['id'] ?>" data-current-status="<?= $trackOrder['order_status'] ?>">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
      <div>
        <div style="font-size:0.78rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:0.35rem">
          <?= ucfirst(str_replace('_', ' ', $trackOrder['order_status'])) ?>
        </div>
        <h4 style="font-family:'Syne',sans-serif;margin:0 0 0.3rem">Order #<?= $trackOrder['id'] ?></h4>
        <div style="font-size:0.85rem;color:var(--text-muted)"><?= htmlspecialchars($trackOrder['items_list'] ?? '') ?></div>
      </div>
      <div class="text-end">
        <div style="font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;color:var(--dark)">Rs <?= number_format($trackOrder['final_amount'], 2) ?></div>
        <div style="font-size:0.78rem;color:var(--text-muted)"><?= date('d M Y, h:i A', strtotime($trackOrder['created_at'])) ?></div>
      </div>
    </div>

    <?php if ($trackOrder['order_status'] === 'cancelled'): ?>
    <div class="alert alert-danger rounded-3 mb-3">This order was cancelled.</div>
    <?php elseif ($trackOrder['order_status'] === 'delivered'): ?>
    <div class="alert alert-success rounded-3 mb-3">Your order has been delivered.</div>
    <?php else: ?>
    <div style="background:rgba(0,0,0,0.05);border-radius:50px;height:8px;overflow:hidden;margin-bottom:1.5rem">
      <div id="progressBar" style="background:var(--grad);height:100%;width:<?= $progress ?>%"></div>
    </div>
    <div class="tracking-steps mb-4" id="trackingSteps">
      <?php foreach ($trackingSteps as $index => [$key, $label]): ?>
      <?php
        $class = '';
        if ($index < $currentStep) $class = 'done';
        if ($index === $currentStep) $class = 'active';
      ?>
      <div class="track-step <?= $class ?>" data-step="<?= $index ?>">
        <div class="track-step-icon"><?= $index + 1 ?></div>
        <p><?= $label ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <div style="font-size:0.9rem;color:var(--text-muted)">
      Estimated delivery in <strong><?= (int)$trackOrder['estimated_delivery'] ?> minutes</strong>
    </div>
    <?php endif; ?>

    <div class="d-flex gap-2 flex-wrap mt-4">
      <?php if (in_array($trackOrder['order_status'], $cancelableStatuses, true)): ?>
      <form method="POST" onsubmit="return confirm('Cancel this order? This cannot be undone.');">
        <input type="hidden" name="order_id" value="<?= $trackOrder['id'] ?>">
        <button type="submit" name="cancel_order" class="btn-outline-fe" style="padding:0.7rem 1.2rem;color:#9c3b2c;border-color:rgba(156,59,44,0.18)">
          Cancel Order
        </button>
      </form>
      <div style="display:flex;align-items:center;font-size:0.8rem;color:var(--text-muted)">
        You can cancel this order until it goes out for delivery.
      </div>
      <?php endif; ?>

      <?php if ($trackOrder['order_status'] === 'delivered' || $trackOrder['order_status'] === 'cancelled'): ?>
      <form method="POST">
        <input type="hidden" name="order_id" value="<?= $trackOrder['id'] ?>">
        <button type="submit" name="reorder" class="btn-primary-fe" style="font-size:0.85rem">Reorder</button>
      </form>
      <?php endif; ?>

      <?php if (!$trackOrder['is_acknowledged'] && !in_array($trackOrder['order_status'], ['delivered', 'cancelled'], true)): ?>
      <form method="POST">
        <input type="hidden" name="order_id" value="<?= $trackOrder['id'] ?>">
        <button type="submit" name="acknowledge_order" class="btn-add-cart" style="padding:0.7rem 1.2rem">Acknowledge</button>
      </form>
      <?php endif; ?>
    </div>

    <?php if ($trackOrder['order_status'] === 'delivered' && !$trackOrder['is_rated']): ?>
    <div class="card-fe p-4 mt-4" style="background:var(--silver-bg)">
      <h5 style="font-family:'Syne',sans-serif;margin-bottom:1rem">Rate this order</h5>
      <form method="POST">
        <input type="hidden" name="order_id" value="<?= $trackOrder['id'] ?>">
        <input type="hidden" name="rating" id="ratingVal" value="5">
        <div id="starRow" style="display:flex;gap:0.4rem;margin-bottom:0.75rem">
          <?php for ($i = 1; $i <= 5; $i++): ?>
          <span style="font-size:2rem;cursor:pointer;user-select:none" onmouseover="hoverStars(<?= $i ?>)" onmouseout="resetStars()" onclick="selectStar(<?= $i ?>)">*</span>
          <?php endfor; ?>
        </div>
        <div id="ratingLabel" style="font-size:0.82rem;color:var(--text-muted);margin-bottom:0.8rem">Excellent</div>
        <textarea name="review" rows="3" class="form-control-fe" placeholder="Write a short review (optional)"></textarea>
        <button type="submit" name="submit_rating" class="btn-primary-fe mt-3">Submit Rating</button>
      </form>
    </div>
    <?php elseif ($trackOrder['order_status'] === 'delivered' && $trackOrder['is_rated']): ?>
    <div class="alert alert-info rounded-3 mt-4 mb-0">You have already rated this order.</div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <h5 style="font-family:'Syne',sans-serif;margin-bottom:1.2rem;font-size:1rem;color:var(--dark)">
    <?= empty($orders) ? '' : 'All Orders (' . count($orders) . ')' ?>
  </h5>

  <?php if (empty($orders)): ?>
  <div class="text-center py-5">
    <div style="font-size:4rem;margin-bottom:1rem">Orders</div>
    <h4 style="font-family:'Syne',sans-serif;margin-bottom:0.75rem">No orders yet</h4>
    <p style="color:var(--text-muted);margin-bottom:1.5rem">You have not placed any orders yet.</p>
    <a href="<?= SITE_URL ?>/menu.php" class="btn-primary-fe">Browse Menu</a>
  </div>
  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:0.85rem">
    <?php foreach ($orders as $order): ?>
    <?php
      $statusLabel = ucwords(str_replace('_', ' ', $order['order_status']));
      $canCancel = in_array($order['order_status'], $cancelableStatuses, true);
      $statusColor = match ($order['order_status']) {
          'placed' => '#1565c0',
          'confirmed' => '#6a1b9a',
          'preparing' => '#e65100',
          'out_for_delivery' => '#2e7d32',
          'delivered' => '#2e7d32',
          'cancelled' => '#c62828',
          default => '#555555'
      };
      $statusBg = match ($order['order_status']) {
          'placed' => '#e3f2fd',
          'confirmed' => '#f3e5f5',
          'preparing' => '#fff3e0',
          'out_for_delivery' => '#e8f5e9',
          'delivered' => '#e8f5e9',
          'cancelled' => '#fce4ec',
          default => '#f5f5f5'
      };
    ?>
    <div class="card-fe p-4">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
          <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
            <strong>Order #<?= $order['id'] ?></strong>
            <span style="background:<?= $statusBg ?>;color:<?= $statusColor ?>;padding:0.15rem 0.65rem;border-radius:50px;font-size:0.72rem;font-weight:700">
              <?= $statusLabel ?>
            </span>
          </div>
          <div style="font-size:0.82rem;color:var(--text-muted)">
            <?= $order['item_count'] ?> item(s) | <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?>
          </div>
          <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.25rem">
            <?= htmlspecialchars(substr($order['delivery_address'], 0, 70)) ?>...
          </div>
        </div>
        <div class="text-end">
          <div style="font-family:'Syne',sans-serif;font-size:1.2rem;font-weight:800;color:var(--dark)">Rs <?= number_format($order['final_amount'], 2) ?></div>
          <div style="font-size:0.75rem;color:var(--text-muted);margin-bottom:0.6rem"><?= htmlspecialchars(['cod' => 'Cash on Delivery', 'upi' => 'UPI', 'online' => 'Online Payment'][$order['payment_method']] ?? ucfirst((string)$order['payment_method'])) ?></div>
          <div style="display:flex;gap:0.5rem;justify-content:flex-end;flex-wrap:wrap">
            <a href="?track=<?= $order['id'] ?>" class="btn-add-cart" style="padding:0.35rem 0.9rem">
              <?= $order['order_status'] === 'delivered' ? 'View' : 'Track' ?>
            </a>
            <?php if ($canCancel): ?>
            <form method="POST" onsubmit="return confirm('Cancel this order? This cannot be undone.');">
              <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
              <button type="submit" name="cancel_order" class="btn-outline-fe" style="padding:0.35rem 0.9rem;font-size:0.8rem;color:#9c3b2c;border-color:rgba(156,59,44,0.18)">
                Cancel
              </button>
            </form>
            <?php endif; ?>
            <?php if ($order['order_status'] === 'delivered' || $order['order_status'] === 'cancelled'): ?>
            <form method="POST">
              <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
              <button type="submit" name="reorder" class="btn-primary-fe" style="padding:0.35rem 0.9rem;font-size:0.8rem">Reorder</button>
            </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<script>
let selectedRating = 5;
const ratingLabels = {1: 'Poor', 2: 'Fair', 3: 'Good', 4: 'Great', 5: 'Excellent'};

function hoverStars(n) {
  document.querySelectorAll('#starRow span').forEach((star, index) => {
    star.style.transform = index < n ? 'scale(1.2)' : 'scale(1)';
    star.style.opacity = index < n ? '1' : '0.3';
  });
  const label = document.getElementById('ratingLabel');
  if (label) label.textContent = ratingLabels[n] || '';
}

function resetStars() {
  document.querySelectorAll('#starRow span').forEach((star, index) => {
    star.style.transform = index < selectedRating ? 'scale(1.15)' : 'scale(1)';
    star.style.opacity = index < selectedRating ? '1' : '0.3';
  });
  const label = document.getElementById('ratingLabel');
  if (label) label.textContent = ratingLabels[selectedRating] || '';
}

function selectStar(n) {
  selectedRating = n;
  const input = document.getElementById('ratingVal');
  if (input) input.value = n;
  resetStars();
}

document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('starRow')) {
    resetStars();
  }
});

const tracker = document.getElementById('liveTracker');
if (tracker) {
  const trackerSteps = ['placed', 'confirmed', 'preparing', 'out_for_delivery', 'delivered'];
  setInterval(() => {
    const orderId = tracker.dataset.orderId;
    fetch(`<?= SITE_URL ?>/php/order-status.php?order_id=${orderId}`)
      .then(response => response.json())
      .then(data => {
        if (!data.success) return;

        const currentStatus = tracker.dataset.currentStatus;
        if (data.order_status !== currentStatus) {
          if (data.order_status === 'delivered' || data.order_status === 'cancelled') {
            window.location.reload();
            return;
          }
          tracker.dataset.currentStatus = data.order_status;
        }

        const stepIndex = trackerSteps.indexOf(data.order_status);
        const progress = stepIndex >= 0 ? (stepIndex / 4) * 100 : 0;
        const progressBar = document.getElementById('progressBar');
        if (progressBar) {
          progressBar.style.width = `${progress}%`;
        }

        document.querySelectorAll('#trackingSteps .track-step').forEach(step => {
          const stepNumber = parseInt(step.dataset.step, 10);
          step.classList.remove('done', 'active');
          if (stepNumber < stepIndex) step.classList.add('done');
          if (stepNumber === stepIndex) step.classList.add('active');
        });

        const etaStrong = tracker.querySelector('strong');
        if (etaStrong && data.estimated_delivery) {
          etaStrong.textContent = `${data.estimated_delivery} minutes`;
        }
      })
      .catch(() => {});
  }, 30000);
}
</script>

<?php require_once 'includes/footer.php'; ?>

