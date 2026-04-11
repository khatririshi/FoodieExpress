<?php
require_once 'includes/config.php';
requireLogin();

$pageTitle = 'Order Confirmed';
require_once 'includes/header.php';

$db = getDB();
$uid = (int)$_SESSION['user_id'];
$orderId = (int)($_GET['order_id'] ?? 0);

if (!$orderId) {
    $db->close();
    header('Location: ' . SITE_URL . '/orders.php');
    exit;
}

$orderStmt = $db->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
$orderStmt->bind_param('ii', $orderId, $uid);
$orderStmt->execute();
$order = $orderStmt->get_result()->fetch_assoc();
$orderStmt->close();

if (!$order) {
    $db->close();
    setFlash('error', 'Order not found.');
    header('Location: ' . SITE_URL . '/orders.php');
    exit;
}

$paymentStmt = $db->prepare('SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1');
$paymentStmt->bind_param('i', $orderId);
$paymentStmt->execute();
$payment = $paymentStmt->get_result()->fetch_assoc();
$paymentStmt->close();
$db->close();

$cancelableStatuses = ['placed', 'confirmed', 'preparing'];
$canCancelOrder = in_array((string)$order['order_status'], $cancelableStatuses, true);

$methodLabels = [
    'cod' => ['bi-cash-stack', 'Cash on Delivery'],
    'upi' => ['bi-phone', 'UPI Payment'],
    'card' => ['bi-credit-card', 'Credit or Debit Card'],
    'netbanking' => ['bi-bank', 'Net Banking'],
    'wallet' => ['bi-wallet2', 'Wallet Payment'],
    'online' => ['bi-credit-card-2-front', 'Online Payment'],
];
$paymentMethodKey = (string)($payment['payment_method'] ?? $order['payment_method']);
$methodInfo = $methodLabels[$paymentMethodKey] ?? ['bi-credit-card-2-front', 'Payment'];
$transId = trim((string)($payment['transaction_id'] ?? ''));

$isCancelled = $order['order_status'] === 'cancelled';
$isCod = $order['payment_method'] === 'cod';
$headline = $isCancelled
    ? 'Order Cancelled'
    : ($isCod ? 'Order Placed!' : 'Payment Successful!');
$subheadline = $isCancelled
    ? 'This order has been cancelled and will not be prepared or delivered.'
    : ($isCod
        ? 'Your order has been placed. Pay when the food arrives.'
        : 'Your payment has been processed and your order is confirmed.');
$statusTone = $isCancelled ? '#c62828' : 'var(--success)';
$statusBackground = $isCancelled ? 'rgba(198,40,40,0.1)' : 'rgba(47,122,91,0.1)';
?>

<style>
.success-container { max-width: 680px; margin: 0 auto; padding: 2rem 1rem 4rem; text-align: center; }
.success-checkmark { width: 110px; height: 110px; margin: 0 auto 1.5rem; position: relative; }
.success-circle {
  width: 110px;
  height: 110px;
  border-radius: 50%;
  background: linear-gradient(135deg, <?= $isCancelled ? '#d95a4e, #b7342c' : 'var(--success), #3db37a' ?>);
  display: flex;
  align-items: center;
  justify-content: center;
  animation: successPop 0.6s cubic-bezier(0.17, 0.89, 0.32, 1.28);
  box-shadow: 0 20px 50px <?= $isCancelled ? 'rgba(198,40,40,0.26)' : 'rgba(47,122,91,0.3)' ?>;
}
.success-circle i { font-size: 3.2rem; color: white; animation: checkDraw 0.4s 0.3s ease-out both; }
@keyframes successPop { 0% { transform: scale(0); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
@keyframes checkDraw { 0% { transform: scale(0) rotate(-45deg); opacity: 0; } 100% { transform: scale(1) rotate(0); opacity: 1; } }
.confetti-container { position: fixed; inset: 0; pointer-events: none; z-index: 9999; overflow: hidden; }
.confetti { position: absolute; width: 10px; height: 10px; top: -10px; animation: confettiFall var(--dur) var(--delay) ease-in forwards; }
@keyframes confettiFall {
  0% { transform: translateY(0) rotate(0deg); opacity: 1; }
  100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
}
.order-detail-row { display: flex; justify-content: space-between; gap: 1rem; padding: 0.65rem 0; font-size: 0.9rem; border-bottom: 1px solid var(--line); }
.order-detail-row:last-child { border-bottom: none; }
.success-actions { display: flex; gap: 1rem; justify-content: center; margin-top: 2rem; flex-wrap: wrap; }
.success-actions form { margin: 0; }
</style>

<?php if (!$isCancelled): ?>
<div class="confetti-container" id="confetti"></div>
<?php endif; ?>

<div class="success-container">
  <div class="success-checkmark">
    <div class="success-circle">
      <i class="bi <?= $isCancelled ? 'bi-x-lg' : 'bi-check-lg' ?>"></i>
    </div>
  </div>

  <h2 style="font-family:'Cormorant Garamond',serif;font-size:2.2rem;margin-bottom:0.4rem;color:var(--ink)">
    <?= $headline ?>
  </h2>
  <p style="color:var(--ink-muted);font-size:1rem;margin-bottom:2rem;line-height:1.6">
    <?= $subheadline ?>
  </p>

  <?php if ($canCancelOrder): ?>
  <div class="alert rounded-3 mb-3" style="background:rgba(156,59,44,0.08);border:1px solid rgba(156,59,44,0.15);color:#7b3528">
    You can still cancel this order until it goes out for delivery.
  </div>
  <?php elseif ($isCancelled): ?>
  <div class="alert alert-danger rounded-3 mb-3">This order is already cancelled.</div>
  <?php endif; ?>

  <div class="card-fe p-4" style="text-align:left;margin-bottom:1.5rem">
    <div style="display:flex;align-items:center;gap:0.8rem;margin-bottom:1.2rem;padding-bottom:1rem;border-bottom:2px solid var(--line)">
      <div style="width:48px;height:48px;border-radius:14px;background:<?= $statusBackground ?>;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;color:<?= $statusTone ?>">
        <i class="bi <?= $isCancelled ? 'bi-x-circle' : 'bi-bag-check' ?>"></i>
      </div>
      <div>
        <div style="font-weight:800;font-size:1.05rem">Order #<?= $orderId ?></div>
        <div style="font-size:0.78rem;color:var(--ink-muted)"><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></div>
      </div>
    </div>

    <div class="order-detail-row">
      <span style="color:var(--ink-muted)">Amount</span>
      <span style="font-weight:800;color:var(--accent);font-size:1.05rem">Rs <?= number_format((float)$order['final_amount'], 2) ?></span>
    </div>
    <div class="order-detail-row">
      <span style="color:var(--ink-muted)">Order Status</span>
      <span style="font-weight:700;color:<?= $statusTone ?>"><?= ucwords(str_replace('_', ' ', (string)$order['order_status'])) ?></span>
    </div>
    <div class="order-detail-row">
      <span style="color:var(--ink-muted)">Payment Method</span>
      <span style="font-weight:600;display:inline-flex;align-items:center;gap:0.4rem">
        <i class="bi <?= htmlspecialchars($methodInfo[0]) ?>"></i> <?= htmlspecialchars($methodInfo[1]) ?>
      </span>
    </div>
    <?php if ($payment && $transId !== ''): ?>
    <div class="order-detail-row">
      <span style="color:var(--ink-muted)">Transaction ID</span>
      <span style="font-weight:600;font-family:monospace;font-size:0.82rem;letter-spacing:0.5px"><?= htmlspecialchars($transId) ?></span>
    </div>
    <?php endif; ?>
    <?php if ($payment && !empty($payment['upi_id'])): ?>
    <div class="order-detail-row">
      <span style="color:var(--ink-muted)">UPI ID</span>
      <span style="font-weight:600"><?= htmlspecialchars((string)$payment['upi_id']) ?></span>
    </div>
    <?php endif; ?>
    <?php if ($payment && !empty($payment['card_last_four'])): ?>
    <div class="order-detail-row">
      <span style="color:var(--ink-muted)">Card</span>
      <span style="font-weight:600">**** **** **** <?= htmlspecialchars((string)$payment['card_last_four']) ?></span>
    </div>
    <?php endif; ?>
    <?php if ($payment && !empty($payment['bank_name'])): ?>
    <div class="order-detail-row">
      <span style="color:var(--ink-muted)">Bank</span>
      <span style="font-weight:600"><?= htmlspecialchars((string)$payment['bank_name']) ?></span>
    </div>
    <?php endif; ?>
    <?php if ($payment && !empty($payment['wallet_name'])): ?>
    <div class="order-detail-row">
      <span style="color:var(--ink-muted)">Wallet</span>
      <span style="font-weight:600"><?= htmlspecialchars((string)$payment['wallet_name']) ?></span>
    </div>
    <?php endif; ?>
    <div class="order-detail-row">
      <span style="color:var(--ink-muted)">Payment Status</span>
      <span style="font-weight:700;color:<?= $isCancelled ? '#c62828' : 'var(--success)' ?>">
        <?= $isCancelled ? 'Cancelled' : ($isCod ? 'Pay on Delivery' : 'Paid') ?>
      </span>
    </div>
    <div class="order-detail-row">
      <span style="color:var(--ink-muted)">Delivery Address</span>
      <span style="font-weight:500;font-size:0.82rem;text-align:right;max-width:55%"><?= htmlspecialchars(substr((string)$order['delivery_address'], 0, 100)) ?></span>
    </div>
  </div>

  <?php if (!$isCancelled): ?>
  <div class="card-fe p-3 mb-3" style="background:rgba(47,122,91,0.05);border-color:rgba(47,122,91,0.12)">
    <div style="display:flex;align-items:center;gap:0.7rem;justify-content:center">
      <span style="font-size:1.5rem"><i class="bi bi-clock-history"></i></span>
      <div>
        <div style="font-weight:700;color:var(--success);font-size:0.95rem">Estimated Delivery: 25-35 minutes</div>
        <div style="font-size:0.78rem;color:var(--ink-muted)">Track your order in real time</div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="success-actions">
    <a href="<?= SITE_URL ?>/orders.php?track=<?= $orderId ?>" class="btn-primary-fe" style="padding:0.9rem 2rem">
      <?= $isCancelled ? 'View Order' : 'Track Order' ?>
    </a>

    <?php if ($canCancelOrder): ?>
    <form method="POST" action="<?= SITE_URL ?>/orders.php" onsubmit="return confirm('Cancel this order? This cannot be undone.');">
      <input type="hidden" name="order_id" value="<?= $orderId ?>">
      <button type="submit" name="cancel_order" class="btn-outline-fe" style="padding:0.9rem 2rem;color:#9c3b2c;border-color:rgba(156,59,44,0.18)">
        Cancel Order
      </button>
    </form>
    <?php endif; ?>

    <a href="<?= SITE_URL ?>/menu.php" class="btn-outline-fe" style="padding:0.9rem 2rem;text-decoration:none">
      Order More
    </a>
  </div>
</div>

<?php if (!$isCancelled): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('confetti');
  if (!container) {
    return;
  }

  const colors = ['#ff6b6b', '#ffd93d', '#6bcb77', '#4d96ff', '#ff9ff3', '#feca57', '#ff6348', '#2ed573', '#1e90ff', '#ff4757'];
  for (let i = 0; i < 80; i++) {
    const el = document.createElement('div');
    el.className = 'confetti';
    el.style.left = Math.random() * 100 + '%';
    el.style.setProperty('--dur', (Math.random() * 2 + 1.5) + 's');
    el.style.setProperty('--delay', (Math.random() * 0.8) + 's');
    el.style.background = colors[Math.floor(Math.random() * colors.length)];
    el.style.borderRadius = Math.random() > 0.5 ? '50%' : '2px';
    el.style.width = (Math.random() * 8 + 5) + 'px';
    el.style.height = (Math.random() * 8 + 5) + 'px';
    container.appendChild(el);
  }

  setTimeout(() => container.remove(), 4000);
});
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
