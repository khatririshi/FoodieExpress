<?php
require_once 'includes/config.php';
requireLogin();

$pageTitle = 'Checkout';
require_once 'includes/header.php';

$db  = getDB();
$uid = (int)$_SESSION['user_id'];

$cartItems = $db->query(
    "SELECT c.*, f.name, f.price, f.image, r.id AS restaurant_id, r.name AS restaurant_name, r.delivery_fee
     FROM cart c
     JOIN food_items f ON c.food_id = f.id
     JOIN restaurants r ON f.restaurant_id = r.id
     WHERE c.user_id = $uid"
)->fetch_all(MYSQLI_ASSOC);

if (empty($cartItems)) {
    $db->close();
    header('Location: ' . SITE_URL . '/menu.php');
    exit;
}

$subtotal = array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $cartItems));
$deliveryFee = $cartItems[0]['delivery_fee'] ?? 30;
$total = $subtotal + $deliveryFee;
$restaurantIds = array_unique(array_column($cartItems, 'restaurant_id'));

$userStmt = $db->prepare('SELECT * FROM users WHERE id = ?');
$userStmt->bind_param('i', $uid);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = sanitize($_POST['address'] ?? '');
    $special = sanitize($_POST['special_instructions'] ?? '');

    if (!$address) {
        $error = 'Please enter delivery address.';
    } elseif (count($restaurantIds) > 1) {
        $error = 'Your cart contains items from multiple restaurants. Please keep one restaurant per order.';
    } else {
        try {
            $db->begin_transaction();

            $payment = 'online'; // Placeholder until the user confirms a payment method
            $paymentStatus = 'pending';
            $orderStmt = $db->prepare(
                'INSERT INTO orders (user_id, total_amount, delivery_fee, final_amount, delivery_address, payment_method, payment_status, special_instructions)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $orderStmt->bind_param('idddssss', $uid, $subtotal, $deliveryFee, $total, $address, $payment, $paymentStatus, $special);
            $orderStmt->execute();
            $orderId = $db->insert_id;
            $orderStmt->close();

            foreach ($cartItems as $item) {
                $itemStmt = $db->prepare('INSERT INTO order_items (order_id, food_id, quantity, price) VALUES (?, ?, ?, ?)');
                $itemStmt->bind_param('iiid', $orderId, $item['food_id'], $item['quantity'], $item['price']);
                $itemStmt->execute();
                $itemStmt->close();
            }

            $addressStmt = $db->prepare('UPDATE users SET address = ? WHERE id = ?');
            $addressStmt->bind_param('si', $address, $uid);
            $addressStmt->execute();
            $addressStmt->close();

            clearUserCart($db, $uid);
            $db->commit();
            $db->close();

            header('Location: ' . SITE_URL . '/payment.php?order_id=' . $orderId);
            exit;
        } catch (Throwable $e) {
            $db->rollback();
            $error = 'We could not place your order right now. Please try again.';
        }
    }
}

$db->close();

$checkoutSteps = [
    ['icon' => 'bi-bag', 'label' => 'Cart', 'done' => true, 'active' => false],
    ['icon' => 'bi-geo-alt', 'label' => 'Checkout', 'done' => false, 'active' => true],
    ['icon' => 'bi-credit-card-2-front', 'label' => 'Payment', 'done' => false, 'active' => false],
    ['icon' => 'bi-check2-circle', 'label' => 'Done', 'done' => false, 'active' => false],
];
?>

<div style="background:var(--dark);padding:2rem 0 1.5rem;border-bottom:1px solid rgba(255,255,255,0.05)">
  <div class="container">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <a href="<?= SITE_URL ?>/cart.php" style="color:rgba(255,255,255,0.45);text-decoration:none;font-size:0.85rem;display:flex;align-items:center;gap:0.4rem" onmouseover="this.style.color='var(--accent-2)'" onmouseout="this.style.color='rgba(255,255,255,0.45)'">
        <i class="bi bi-arrow-left"></i> Back to Cart
      </a>
      <span style="color:rgba(255,255,255,0.15)">|</span>
      <h2 style="font-family:'Cormorant Garamond',serif;color:white;margin:0;font-size:1.5rem;display:flex;align-items:center;gap:0.55rem">
        <i class="bi bi-geo-alt"></i> Checkout
      </h2>
    </div>
    <div style="display:flex;align-items:center;gap:0;margin-top:1rem;flex-wrap:wrap">
      <?php foreach ($checkoutSteps as $i => $step): ?>
      <div style="display:flex;align-items:center;gap:0.4rem">
        <div style="width:28px;height:28px;border-radius:50%;<?= $step['active'] ? 'background:var(--grad)' : ($step['done'] ? 'background:rgba(159,95,56,0.4)' : 'background:rgba(255,255,255,0.1)') ?>;display:flex;align-items:center;justify-content:center;font-size:0.78rem;color:white">
          <i class="bi <?= $step['icon'] ?>"></i>
        </div>
        <span style="font-size:0.78rem;color:<?= $step['active'] ? 'white' : ($step['done'] ? 'rgba(255,255,255,0.55)' : 'rgba(255,255,255,0.3)') ?>;font-weight:<?= $step['active'] ? '600' : '400' ?>">
          <?= $step['label'] ?>
        </span>
      </div>
      <?php if ($i < count($checkoutSteps) - 1): ?><div style="width:40px;height:1px;background:<?= $step['done'] ? 'rgba(159,95,56,0.4)' : 'rgba(255,255,255,0.1)' ?>;margin:0 0.5rem"></div><?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="container py-5" style="max-width:900px">
  <?php if (isset($error)): ?>
  <div class="alert alert-danger rounded-3 mb-3"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="row g-4">
      <div class="col-lg-7">
        <div class="card-fe p-4 mb-3">
          <h5 style="font-family:'Cormorant Garamond',serif;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem">
            <i class="bi bi-geo-alt"></i> Delivery Address
          </h5>
          <div class="form-group mb-3">
            <label style="font-size:0.85rem;font-weight:600;margin-bottom:0.4rem;display:block">Full Address</label>
            <textarea name="address" class="form-control-fe" rows="3" placeholder="House/Flat no, Street, Area, City, Pincode" required><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label style="font-size:0.85rem;font-weight:600;margin-bottom:0.4rem;display:block">Special Instructions (Optional)</label>
            <input type="text" name="special_instructions" class="form-control-fe" value="<?= htmlspecialchars($_POST['special_instructions'] ?? '') ?>" placeholder="e.g. Ring the bell twice, Leave at door...">
          </div>
        </div>

        <div style="background:rgba(159,95,56,0.06);border:1px solid rgba(159,95,56,0.12);border-radius:var(--radius-sm);padding:0.9rem 1.1rem;display:flex;gap:0.7rem;align-items:flex-start">
          <span style="font-size:1.2rem;flex-shrink:0"><i class="bi bi-lightbulb"></i></span>
          <div style="font-size:0.82rem;color:var(--ink-soft);line-height:1.6">
            <strong>Next Step:</strong> After confirming your address, you will move to a simple payment screen with <strong>UPI, Card, or Cash on Delivery</strong>.
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="card-fe p-4" style="position:sticky;top:80px">
          <h5 style="font-family:'Cormorant Garamond',serif;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem">
            <i class="bi bi-receipt"></i> Order Summary
          </h5>

          <?php foreach ($cartItems as $item): ?>
          <div class="d-flex justify-content-between align-items-center mb-2 pb-2" style="border-bottom:1px solid var(--line)">
            <div>
              <div style="font-weight:500;font-size:0.9rem"><?= htmlspecialchars($item['name']) ?></div>
              <div style="font-size:0.78rem;color:var(--ink-muted)">x<?= (int)$item['quantity'] ?></div>
            </div>
            <div style="font-weight:600">Rs <?= number_format($item['price'] * $item['quantity'], 2) ?></div>
          </div>
          <?php endforeach; ?>

          <div class="mt-3">
            <div class="d-flex justify-content-between mb-2" style="font-size:0.9rem">
              <span class="text-muted">Subtotal</span>
              <span>Rs <?= number_format($subtotal, 2) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2" style="font-size:0.9rem">
              <span class="text-muted">Delivery Fee</span>
              <span>Rs <?= number_format($deliveryFee, 2) ?></span>
            </div>
            <div class="d-flex justify-content-between pt-2 mt-2" style="border-top:2px solid var(--line-strong);font-weight:800;font-size:1.1rem">
              <span>Total</span>
              <span style="color:var(--accent)">Rs <?= number_format($total, 2) ?></span>
            </div>
          </div>

          <div class="mt-3 p-3 rounded" style="background:rgba(47,122,91,0.06);border:1px solid rgba(47,122,91,0.12);font-size:0.82rem">
            <span style="color:var(--success);font-weight:700;display:flex;align-items:center;gap:0.45rem">
              <i class="bi bi-clock-history"></i> Estimated Delivery: 25-35 minutes
            </span>
          </div>

          <button type="submit" class="btn-primary-fe w-100 justify-content-center mt-4" style="padding:1rem;font-size:0.95rem">
            Continue To Payment Rs <?= number_format($total, 2) ?> <i class="bi bi-arrow-right"></i>
          </button>

          <div style="display:flex;justify-content:center;gap:1.2rem;margin-top:0.8rem;flex-wrap:wrap">
            <span style="font-size:0.72rem;color:var(--ink-muted)"><i class="bi bi-phone"></i> UPI</span>
            <span style="font-size:0.72rem;color:var(--ink-muted)"><i class="bi bi-credit-card"></i> Cards</span>
            <span style="font-size:0.72rem;color:var(--ink-muted)"><i class="bi bi-cash-stack"></i> COD</span>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<?php require_once 'includes/footer.php'; ?>
