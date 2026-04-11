<?php
require_once 'includes/config.php';
requireLogin();

$pageTitle = 'Payment';
require_once 'includes/header.php';

$db  = getDB();
$uid = (int)$_SESSION['user_id'];
$orderId = (int)($_GET['order_id'] ?? 0);

if (!$orderId) {
    $db->close();
    header('Location: ' . SITE_URL . '/cart.php');
    exit;
}

$order = $db->query(
    "SELECT o.*, GROUP_CONCAT(CONCAT(COALESCE(f.name, 'Item'), ' x', oi.quantity) SEPARATOR '||') AS items_list
     FROM orders o
     JOIN order_items oi ON o.id = oi.order_id
     LEFT JOIN food_items f ON oi.food_id = f.id
     WHERE o.id = $orderId AND o.user_id = $uid
     GROUP BY o.id"
)->fetch_assoc();

if (!$order) {
    $db->close();
    setFlash('error', 'Order not found.');
    header('Location: ' . SITE_URL . '/cart.php');
    exit;
}

if ($order['order_status'] === 'cancelled') {
    $db->close();
    setFlash('error', 'This order has already been cancelled.');
    header('Location: ' . SITE_URL . '/orders.php?track=' . $orderId);
    exit;
}

if ($order['payment_status'] === 'paid') {
    $db->close();
    header('Location: ' . SITE_URL . '/order-success.php?order_id=' . $orderId);
    exit;
}

$items = array_values(array_filter(array_map('trim', explode('||', $order['items_list'] ?? ''))));
$db->close();

$paymentSteps = [
    ['icon' => 'bi-bag', 'label' => 'Cart', 'done' => true, 'active' => false],
    ['icon' => 'bi-geo-alt', 'label' => 'Checkout', 'done' => true, 'active' => false],
    ['icon' => 'bi-credit-card-2-front', 'label' => 'Payment', 'done' => false, 'active' => true],
    ['icon' => 'bi-check2-circle', 'label' => 'Done', 'done' => false, 'active' => false],
];
?>

<div style="background:var(--dark);padding:2rem 0 1.5rem;border-bottom:1px solid rgba(255,255,255,0.05)">
  <div class="container">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <a href="<?= SITE_URL ?>/checkout.php" style="color:rgba(255,255,255,0.45);text-decoration:none;font-size:0.85rem;display:flex;align-items:center;gap:0.4rem" onmouseover="this.style.color='var(--accent-2)'" onmouseout="this.style.color='rgba(255,255,255,0.45)'">
        <i class="bi bi-arrow-left"></i> Back to Checkout
      </a>
      <span style="color:rgba(255,255,255,0.15)">|</span>
      <h2 style="font-family:'Cormorant Garamond',serif;color:white;margin:0;font-size:1.5rem;display:flex;align-items:center;gap:0.55rem">
        <i class="bi bi-credit-card-2-front"></i> Simple Payment
      </h2>
    </div>
    <div style="display:flex;align-items:center;gap:0;margin-top:1rem;flex-wrap:wrap">
      <?php foreach ($paymentSteps as $i => $step): ?>
      <div style="display:flex;align-items:center;gap:0.4rem">
        <div style="width:28px;height:28px;border-radius:50%;<?= $step['active'] ? 'background:var(--grad)' : ($step['done'] ? 'background:rgba(159,95,56,0.4)' : 'background:rgba(255,255,255,0.1)') ?>;display:flex;align-items:center;justify-content:center;font-size:0.78rem;color:white">
          <i class="bi <?= $step['icon'] ?>"></i>
        </div>
        <span style="font-size:0.78rem;color:<?= $step['active'] ? 'white' : ($step['done'] ? 'rgba(255,255,255,0.55)' : 'rgba(255,255,255,0.3)') ?>;font-weight:<?= $step['active'] ? '600' : '400' ?>">
          <?= $step['label'] ?>
        </span>
      </div>
      <?php if ($i < count($paymentSteps) - 1): ?><div style="width:40px;height:1px;background:<?= $step['done'] ? 'rgba(159,95,56,0.4)' : 'rgba(255,255,255,0.1)' ?>;margin:0 0.5rem"></div><?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="container py-5 payment-page" style="max-width:1100px">
  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card-fe payment-page__panel payment-page__panel--intro">
        <span class="section-title__eyebrow">Easy checkout</span>
        <h3>Choose the fastest way to complete this order.</h3>
        <p>We reduced the payment step to the options most users understand immediately: UPI, card, and cash on delivery.</p>
      </div>

      <div id="paymentFeedback" class="payment-inline-alert" hidden></div>

      <div class="payment-methods-simple">
        <section class="payment-option is-active" data-payment-option="upi">
          <label class="payment-option__header" for="paymentMethodUpi">
            <input class="payment-option__input" type="radio" id="paymentMethodUpi" name="payment_method" value="upi" checked>
            <span class="payment-option__control" aria-hidden="true"></span>
            <span class="payment-option__icon"><i class="bi bi-phone"></i></span>
            <span class="payment-option__copy">
              <strong>UPI</strong>
              <small>Use GPay, PhonePe, Paytm, or any UPI app with one ID.</small>
            </span>
            <span class="payment-option__tag">Recommended</span>
          </label>
          <div class="payment-option__body" data-payment-panel="upi">
            <label class="pay-label" for="upiIdInput">UPI ID</label>
            <input type="text" id="upiIdInput" class="form-control-fe" placeholder="name@upi" autocomplete="off">
            <div class="payment-quick-list">
              <span class="payment-pill">GPay</span>
              <span class="payment-pill">PhonePe</span>
              <span class="payment-pill">Paytm</span>
              <span class="payment-pill">BHIM</span>
            </div>
            <p class="payment-help-text">Enter the same UPI ID you use in your payment app.</p>
          </div>
        </section>

        <section class="payment-option" data-payment-option="card">
          <label class="payment-option__header" for="paymentMethodCard">
            <input class="payment-option__input" type="radio" id="paymentMethodCard" name="payment_method" value="card">
            <span class="payment-option__control" aria-hidden="true"></span>
            <span class="payment-option__icon"><i class="bi bi-credit-card"></i></span>
            <span class="payment-option__copy">
              <strong>Credit or Debit Card</strong>
              <small>Enter your card details once and continue securely.</small>
            </span>
          </label>
          <div class="payment-option__body" data-payment-panel="card" hidden>
            <div class="payment-form-grid">
              <div class="payment-form-grid__full">
                <label class="pay-label" for="cardNumber">Card Number</label>
                <input type="text" id="cardNumber" class="form-control-fe" placeholder="1234 5678 9012 3456" inputmode="numeric" autocomplete="cc-number">
              </div>
              <div>
                <label class="pay-label" for="cardExpiry">Expiry Date</label>
                <input type="text" id="cardExpiry" class="form-control-fe" placeholder="MM / YY" inputmode="numeric" autocomplete="cc-exp">
              </div>
              <div>
                <label class="pay-label" for="cardCvv">CVV</label>
                <input type="password" id="cardCvv" class="form-control-fe" placeholder="123" inputmode="numeric" maxlength="4" autocomplete="cc-csc">
              </div>
              <div class="payment-form-grid__full">
                <label class="pay-label" for="cardName">Name on Card</label>
                <input type="text" id="cardName" class="form-control-fe" placeholder="Cardholder name" autocomplete="cc-name">
              </div>
            </div>
            <p class="payment-help-text">Only the last four digits are stored after payment is completed.</p>
          </div>
        </section>

        <section class="payment-option" data-payment-option="cod">
          <label class="payment-option__header" for="paymentMethodCod">
            <input class="payment-option__input" type="radio" id="paymentMethodCod" name="payment_method" value="cod">
            <span class="payment-option__control" aria-hidden="true"></span>
            <span class="payment-option__icon"><i class="bi bi-cash-stack"></i></span>
            <span class="payment-option__copy">
              <strong>Cash on Delivery</strong>
              <small>Place the order now and pay when the food reaches you.</small>
            </span>
          </label>
          <div class="payment-option__body" data-payment-panel="cod" hidden>
            <div class="payment-note-card">
              <strong>Cash on delivery is ready.</strong>
              <p>Keep the amount ready for the delivery partner, or complete the payment by UPI at the doorstep if needed.</p>
            </div>
          </div>
        </section>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="payment-summary-stack">
        <div class="card-fe p-4">
          <h5 style="font-family:'Cormorant Garamond',serif;font-size:1.1rem;margin-bottom:1.2rem;display:flex;align-items:center;gap:0.5rem">
            <i class="bi bi-receipt"></i> Order #<?= $orderId ?>
          </h5>

          <div class="payment-summary-list">
            <?php foreach ($items as $item): ?>
            <div class="payment-summary-list__item">
              <span><?= htmlspecialchars($item) ?></span>
            </div>
            <?php endforeach; ?>
          </div>

          <div class="payment-summary-totals">
            <div class="payment-summary-totals__row">
              <span>Subtotal</span>
              <strong>Rs <?= number_format($order['total_amount'], 2) ?></strong>
            </div>
            <div class="payment-summary-totals__row">
              <span>Delivery Fee</span>
              <strong>Rs <?= number_format($order['delivery_fee'], 2) ?></strong>
            </div>
            <?php if ((float)$order['discount'] > 0): ?>
            <div class="payment-summary-totals__row payment-summary-totals__row--discount">
              <span>Discount</span>
              <strong>-Rs <?= number_format($order['discount'], 2) ?></strong>
            </div>
            <?php endif; ?>
          </div>

          <div class="payment-summary-total">
            <span>Total</span>
            <strong>Rs <?= number_format($order['final_amount'], 2) ?></strong>
          </div>
        </div>

        <div class="card-fe p-3 payment-address-card">
          <div class="payment-address-card__icon"><i class="bi bi-geo-alt"></i></div>
          <div>
            <strong>Delivery Address</strong>
            <p><?= htmlspecialchars($order['delivery_address']) ?></p>
          </div>
        </div>

        <div class="card-fe p-4">
          <button id="payBtn" type="button" onclick="processPayment()" class="btn-primary-fe w-100 justify-content-center payment-submit">
            <span id="payBtnText">Pay Rs <?= number_format($order['final_amount'], 2) ?></span>
            <span id="payBtnSpinner" class="payment-submit__spinner" hidden>
              <span class="pay-spinner"></span> Processing...
            </span>
          </button>

          <div class="payment-trust-list">
            <span><i class="bi bi-shield-lock"></i> Secure payment request</span>
            <span><i class="bi bi-clock-history"></i> Delivery in 25-35 minutes</span>
            <span><i class="bi bi-check2-circle"></i> Order confirmation after payment</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="paymentOverlay" class="payment-overlay" hidden>
  <div class="payment-overlay-content">
    <div class="payment-overlay__badge"><i class="bi bi-shield-lock"></i></div>
    <h3 id="processTitle">Processing payment</h3>
    <p id="processSubtitle">Please wait while we confirm this order.</p>
    <div class="payment-overlay__status">
      <span class="pay-spinner"></span>
      <span id="processStatus">Connecting securely...</span>
    </div>
  </div>
</div>

<script>
const ORDER_ID = <?= $orderId ?>;
const TOTAL = <?= (float)$order['final_amount'] ?>;
const FALLBACK_SITE_URL = <?= json_encode(SITE_URL) ?>;

function paymentSiteUrl() {
  return typeof SITE_URL === 'string' && SITE_URL ? SITE_URL : FALLBACK_SITE_URL;
}

function selectedPaymentMethod() {
  return document.querySelector('input[name="payment_method"]:checked')?.value || 'upi';
}

function setPaymentFeedback(message) {
  const feedback = document.getElementById('paymentFeedback');
  if (!feedback) {
    return;
  }

  feedback.textContent = message;
  feedback.hidden = !message;
}

function syncPaymentPanels() {
  const method = selectedPaymentMethod();

  document.querySelectorAll('[data-payment-option]').forEach(option => {
    option.classList.toggle('is-active', option.dataset.paymentOption === method);
  });

  document.querySelectorAll('[data-payment-panel]').forEach(panel => {
    panel.hidden = panel.dataset.paymentPanel !== method;
  });

  const buttonText = document.getElementById('payBtnText');
  if (buttonText) {
    buttonText.textContent = method === 'cod'
      ? `Place Order - Rs ${TOTAL.toFixed(2)}`
      : `Pay Rs ${TOTAL.toFixed(2)}`;
  }
}

function formatCardNumberInput(event) {
  const digits = event.target.value.replace(/\D/g, '').substring(0, 19);
  event.target.value = digits.replace(/(.{4})/g, '$1 ').trim();
}

function formatExpiryInput(event) {
  const digits = event.target.value.replace(/\D/g, '').substring(0, 4);
  event.target.value = digits.length > 2
    ? `${digits.substring(0, 2)} / ${digits.substring(2)}`
    : digits;
}

function showPaymentLoading(isLoading, title, subtitle, status) {
  const button = document.getElementById('payBtn');
  const buttonText = document.getElementById('payBtnText');
  const buttonSpinner = document.getElementById('payBtnSpinner');
  const overlay = document.getElementById('paymentOverlay');

  if (button) {
    button.disabled = isLoading;
  }
  if (buttonText) {
    buttonText.hidden = isLoading;
  }
  if (buttonSpinner) {
    buttonSpinner.hidden = !isLoading;
  }
  if (overlay) {
    overlay.hidden = !isLoading;
  }

  document.body.style.overflow = isLoading ? 'hidden' : '';

  if (title) {
    document.getElementById('processTitle').textContent = title;
  }
  if (subtitle) {
    document.getElementById('processSubtitle').textContent = subtitle;
  }
  if (status) {
    document.getElementById('processStatus').textContent = status;
  }
}

function processPayment() {
  const method = selectedPaymentMethod();
  const formData = new URLSearchParams();

  setPaymentFeedback('');
  formData.append('order_id', ORDER_ID);
  formData.append('payment_method', method);

  if (method === 'upi') {
    const upiId = document.getElementById('upiIdInput').value.trim().toLowerCase();
    if (!/^[a-z0-9._-]{2,}@[a-z]{2,}$/i.test(upiId)) {
      setPaymentFeedback('Please enter a valid UPI ID like name@upi.');
      document.getElementById('upiIdInput').focus();
      return;
    }
    formData.append('upi_id', upiId);
  }

  if (method === 'card') {
    const cardNumber = document.getElementById('cardNumber').value.replace(/\D/g, '');
    const cardExpiry = document.getElementById('cardExpiry').value.trim();
    const cardCvv = document.getElementById('cardCvv').value.trim();
    const cardName = document.getElementById('cardName').value.trim();

    if (cardNumber.length < 12 || cardNumber.length > 19) {
      setPaymentFeedback('Please enter a valid card number.');
      document.getElementById('cardNumber').focus();
      return;
    }
    if (!/^(0[1-9]|1[0-2]) \/ \d{2}$/.test(cardExpiry)) {
      setPaymentFeedback('Please enter the card expiry in MM / YY format.');
      document.getElementById('cardExpiry').focus();
      return;
    }
    if (!/^\d{3,4}$/.test(cardCvv)) {
      setPaymentFeedback('Please enter a valid card CVV.');
      document.getElementById('cardCvv').focus();
      return;
    }
    if (cardName.length < 3) {
      setPaymentFeedback('Please enter the name shown on the card.');
      document.getElementById('cardName').focus();
      return;
    }

    formData.append('card_last_four', cardNumber.slice(-4));
  }

  showPaymentLoading(
    true,
    method === 'cod' ? 'Placing your order' : 'Processing payment',
    method === 'cod'
      ? 'We are confirming your cash on delivery request.'
      : 'Please wait while we contact the payment service.',
    method === 'cod' ? 'Saving payment choice...' : 'Connecting securely...'
  );

  fetch(`${paymentSiteUrl()}/php/process-payment.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: formData.toString()
  })
    .then(response => response.json())
    .then(data => {
      if (!data.success) {
        if (data.redirect) {
          showPaymentLoading(false);
          setPaymentFeedback(data.message || 'This order is no longer available for payment.');
          window.setTimeout(() => {
            window.location.href = data.redirect;
          }, 900);
          return;
        }
        throw new Error(data.message || 'Payment could not be completed.');
      }

      showPaymentLoading(
        true,
        method === 'cod' ? 'Order placed' : 'Payment successful',
        'Redirecting to your order confirmation page.',
        'Finalizing your order...'
      );

      window.setTimeout(() => {
        window.location.href = data.redirect;
      }, 900);
    })
    .catch(error => {
      showPaymentLoading(false);
      setPaymentFeedback(error.message || 'Something went wrong while processing payment.');
      if (typeof showToast === 'function') {
        showToast('error', error.message || 'Something went wrong while processing payment.');
      }
    });
}

document.querySelectorAll('input[name="payment_method"]').forEach(input => {
  input.addEventListener('change', syncPaymentPanels);
});

document.getElementById('cardNumber')?.addEventListener('input', formatCardNumberInput);
document.getElementById('cardExpiry')?.addEventListener('input', formatExpiryInput);

syncPaymentPanels();
</script>

<?php require_once 'includes/footer.php'; ?>
