<?php
require_once 'includes/config.php';
requireLogin();

$pageTitle = 'My Cart';
require_once 'includes/header.php';

$db  = getDB();
$uid = $_SESSION['user_id'];

$cartItems = $db->query("
    SELECT c.id as cart_id, c.food_id, c.quantity,
           f.name, f.price, f.description, f.category, f.is_veg, f.calories,
           r.name as restaurant_name, r.id as restaurant_id,
           r.delivery_fee, r.delivery_time, r.is_open
    FROM cart c
    JOIN food_items f ON c.food_id = f.id
    JOIN restaurants r ON f.restaurant_id = r.id
    WHERE c.user_id = $uid
    ORDER BY c.added_at DESC
")->fetch_all(MYSQLI_ASSOC);

$subtotal    = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cartItems));
$deliveryFee = !empty($cartItems) ? ($cartItems[0]['delivery_fee'] ?? 30) : 0;
$total       = $subtotal + $deliveryFee;
$totalItems  = array_sum(array_column($cartItems, 'quantity'));
$db->close();
?>
<!-- PAGE HEADER -->
<div style="background:var(--dark);padding:2rem 0 1.5rem;border-bottom:1px solid rgba(255,255,255,0.05)">
  <div class="container">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <a href="<?= SITE_URL ?>/menu.php" style="color:rgba(255,255,255,0.45);text-decoration:none;font-size:0.85rem;display:flex;align-items:center;gap:0.4rem" onmouseover="this.style.color='#ff6b35'" onmouseout="this.style.color='rgba(255,255,255,0.45)'">
        <i class="bi bi-arrow-left"></i> Continue Shopping
      </a>
      <span style="color:rgba(255,255,255,0.15)">|</span>
      <h2 style="font-family:'Syne',sans-serif;color:white;margin:0;font-size:1.5rem">
        🛒 My Cart
        <?php if ($totalItems > 0): ?>
        <span style="background:linear-gradient(135deg,#ff4d00,#ff8c00);color:white;font-size:0.7rem;padding:0.2rem 0.6rem;border-radius:50px;font-family:'Outfit',sans-serif;font-weight:700;margin-left:0.5rem;vertical-align:middle"><?= $totalItems ?> item<?= $totalItems>1?'s':'' ?></span>
        <?php endif; ?>
      </h2>
    </div>
    <?php if (!empty($cartItems)): ?>
    <div style="display:flex;align-items:center;gap:0;margin-top:1rem">
      <?php foreach ([['🛒','Cart',true],['📋','Checkout',false],['✅','Done',false]] as $i => [$e,$l,$active]): ?>
      <div style="display:flex;align-items:center;gap:0.4rem">
        <div style="width:28px;height:28px;border-radius:50%;<?= $active?'background:linear-gradient(135deg,#ff4d00,#ff8c00)':'background:rgba(255,255,255,0.1)' ?>;display:flex;align-items:center;justify-content:center;font-size:0.7rem"><?= $e ?></div>
        <span style="font-size:0.78rem;color:<?= $active?'white':'rgba(255,255,255,0.3)' ?>;font-weight:<?= $active?'600':'400' ?>"><?= $l ?></span>
      </div>
      <?php if ($i<2): ?><div style="width:40px;height:1px;background:rgba(255,255,255,0.1);margin:0 0.5rem"></div><?php endif; ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<div class="container py-4" style="max-width:1100px">

<?php if (empty($cartItems)): ?>
<!-- EMPTY CART -->
<div style="text-align:center;padding:5rem 1rem">
  <div style="font-size:6rem;display:inline-block;animation:floatAnim 3s ease-in-out infinite;margin-bottom:1.5rem">🛒</div>
  <h3 style="font-family:'Syne',sans-serif;margin-bottom:0.75rem">Your cart is empty!</h3>
  <p style="color:var(--text-muted);margin-bottom:2rem;line-height:1.7">Looks like you haven't added anything yet.<br>Browse our delicious menu and order food!</p>
  <div style="display:flex;justify-content:center;gap:1rem;flex-wrap:wrap;margin-bottom:3rem">
    <a href="<?= SITE_URL ?>/menu.php" class="btn-primary-fe">🍔 Browse Menu</a>
    <a href="<?= SITE_URL ?>/surprise.php" class="btn-add-cart" style="padding:0.8rem 1.5rem;font-size:0.9rem">🎲 Surprise Me</a>
  </div>
  <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:1rem">Popular categories</p>
  <div style="display:flex;justify-content:center;gap:0.6rem;flex-wrap:wrap">
    <?php foreach([['🍕','Pizza'],['🍔','Burgers'],['🥗','Healthy'],['🍛','Indian'],['🍜','Chinese'],['🍰','Desserts']] as [$e,$l]): ?>
    <a href="<?= SITE_URL ?>/menu.php?search=<?= $l ?>" class="chip"><?= $e ?> <?= $l ?></a>
    <?php endforeach; ?>
  </div>
</div>

<?php else: ?>
<!-- CART WITH ITEMS -->
<div class="row g-4">

  <!-- LEFT: Cart Items -->
  <div class="col-lg-7">

    <!-- Restaurant bar -->
    <div style="background:var(--dark);border-radius:14px;padding:0.9rem 1.2rem;margin-bottom:1.2rem;display:flex;align-items:center;gap:0.9rem">
      <div style="width:42px;height:42px;border-radius:10px;background:rgba(255,77,0,0.15);display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0">🍽️</div>
      <div style="flex:1;min-width:0">
        <div style="color:white;font-weight:700;font-size:0.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($cartItems[0]['restaurant_name']) ?></div>
        <div style="color:rgba(255,255,255,0.4);font-size:0.75rem;margin-top:0.1rem">
          🕐 <?= $cartItems[0]['delivery_time'] ?> mins &nbsp;·&nbsp;
          <?= $cartItems[0]['is_open'] ? '<span style="color:#00c896">● Open</span>' : '<span style="color:#ff6b6b">● Closed</span>' ?>
        </div>
      </div>
      <a href="<?= SITE_URL ?>/menu.php?restaurant=<?= $cartItems[0]['restaurant_id'] ?>" style="color:var(--primary);font-size:0.82rem;font-weight:700;text-decoration:none;flex-shrink:0">+ Add More</a>
    </div>

    <!-- Items -->
    <div id="cartPageItems">
    <?php foreach ($cartItems as $item): ?>
    <div id="page-item-<?= $item['food_id'] ?>"
         style="background:white;border-radius:14px;border:1px solid rgba(0,0,0,0.05);padding:0.9rem 1rem;margin-bottom:0.65rem;display:flex;gap:0.85rem;align-items:center;box-shadow:0 2px 8px rgba(0,0,0,0.04);transition:all 0.3s">
      <div style="width:64px;height:60px;border-radius:10px;background:<?= $item['is_veg']?'linear-gradient(135deg,#e8faf4,#b8f0dc)':'linear-gradient(135deg,#fff0ef,#ffd4d0)' ?>;display:flex;align-items:center;justify-content:center;font-size:1.9rem;flex-shrink:0">
        <?= match($item['category']){'Pizza'=>'🍕','Burgers'=>'🍔','Bowls'=>'🥗','Salads'=>'🥙','Snacks'=>'🥪','Chinese'=>'🍜','Desserts'=>'🍰',default=>'🍛'} ?>
      </div>
      <div style="flex:1;min-width:0">
        <div style="display:flex;align-items:center;gap:0.4rem;margin-bottom:0.2rem">
          <div class="<?= $item['is_veg']?'veg-dot':'nonveg-dot' ?>"></div>
          <div style="font-weight:600;font-size:0.88rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($item['name']) ?></div>
        </div>
        <?php if ($item['calories']): ?>
        <div style="font-size:0.72rem;color:var(--text-muted);margin-bottom:0.25rem">🔥 <?= $item['calories'] ?> kcal</div>
        <?php endif; ?>
        <div style="font-weight:700;color:var(--primary);font-size:0.9rem">₹<?= number_format($item['price'],2) ?></div>
      </div>
      <div style="display:flex;align-items:center;gap:0.4rem;flex-shrink:0">
        <button class="qty-btn" onclick="cpUpdate(<?= $item['food_id'] ?>,'decrease',<?= $item['price'] ?>)">−</button>
        <span id="pqty-<?= $item['food_id'] ?>" style="font-weight:700;min-width:20px;text-align:center"><?= $item['quantity'] ?></span>
        <button class="qty-btn" onclick="cpUpdate(<?= $item['food_id'] ?>,'increase',<?= $item['price'] ?>)">+</button>
      </div>
      <div style="text-align:right;flex-shrink:0;min-width:70px">
        <div id="ptotal-<?= $item['food_id'] ?>" style="font-weight:700;color:var(--dark);font-size:0.9rem">₹<?= number_format($item['price']*$item['quantity'],2) ?></div>
        <button onclick="cpRemove(<?= $item['food_id'] ?>)"
                style="background:none;border:none;color:#ccc;cursor:pointer;font-size:0.7rem;margin-top:0.2rem;padding:0;font-family:'Outfit',sans-serif;transition:color 0.2s"
                onmouseover="this.style.color='#e63946'" onmouseout="this.style.color='#ccc'">🗑 Remove</button>
      </div>
    </div>
    <?php endforeach; ?>
    </div>

    <div style="text-align:right;margin-top:0.2rem">
      <button onclick="cpClear()" style="background:none;border:none;color:var(--text-muted);font-size:0.8rem;cursor:pointer;text-decoration:underline;font-family:'Outfit',sans-serif">Clear entire cart</button>
    </div>

    <!-- Coupon -->
    <div style="background:white;border-radius:14px;border:2px dashed #e8e8e8;padding:0.9rem 1.1rem;margin-top:1.2rem;display:flex;gap:0.6rem;align-items:center">
      <span style="font-size:1.1rem">🏷️</span>
      <input type="text" id="couponInput" placeholder="Coupon code? (Try: SAVE50, WELCOME100, BCA2025)"
             style="flex:1;border:none;outline:none;font-family:'Outfit',sans-serif;font-size:0.85rem;color:var(--text);background:transparent;min-width:0"
             onkeyup="if(event.key==='Enter')applyCoupon()">
      <button onclick="applyCoupon()" style="background:var(--grad);color:white;border:none;padding:0.4rem 1rem;border-radius:50px;font-weight:700;font-size:0.78rem;cursor:pointer;flex-shrink:0">Apply</button>
    </div>
    <div id="couponMsg" style="font-size:0.78rem;margin-top:0.35rem;padding-left:0.4rem;min-height:1rem"></div>
  </div>

  <!-- RIGHT: Summary -->
  <div class="col-lg-5">
    <div style="position:sticky;top:76px">
      <!-- Bill Details -->
      <div class="card-fe p-4 mb-3">
        <h5 style="font-family:'Syne',sans-serif;font-size:0.95rem;margin-bottom:1.2rem">📋 Bill Details</h5>
        <div style="display:flex;justify-content:space-between;margin-bottom:0.7rem;font-size:0.88rem">
          <span style="color:var(--text-muted)">Item total (<span id="itemCount"><?= $totalItems ?></span> items)</span>
          <span id="summarySubtotal" style="font-weight:600">₹<?= number_format($subtotal,2) ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:0.7rem;font-size:0.88rem">
          <span style="color:var(--text-muted)">Delivery fee</span>
          <span style="font-weight:600">₹<?= number_format($deliveryFee,2) ?></span>
        </div>
        <div id="discountRow" style="display:none;justify-content:space-between;margin-bottom:0.7rem;font-size:0.88rem">
          <span style="color:#00875a">🏷️ Coupon discount</span>
          <span id="discountDisplay" style="color:#00875a;font-weight:700">-₹0</span>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:0.7rem;font-size:0.88rem">
          <span style="color:var(--text-muted)">Platform fee</span>
          <span style="font-weight:600;color:#00875a">FREE 🎉</span>
        </div>
        <div style="border-top:2px dashed #f0f0f0;margin:0.8rem 0"></div>
        <div style="display:flex;justify-content:space-between;align-items:center">
          <span style="font-weight:700">Grand Total</span>
          <span id="summaryTotal" style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.2rem;color:var(--primary)">₹<?= number_format($total,2) ?></span>
        </div>
        <?php if ($subtotal < 500): ?>
        <div style="background:rgba(255,140,0,0.07);border:1px solid rgba(255,140,0,0.2);border-radius:8px;padding:0.6rem 0.8rem;margin-top:0.8rem;font-size:0.76rem;color:#8c5c00">
          ⚡ Add ₹<?= number_format(500-$subtotal,2) ?> more to get FREE delivery!
        </div>
        <?php else: ?>
        <div style="background:rgba(0,200,150,0.07);border:1px solid rgba(0,200,150,0.15);border-radius:8px;padding:0.6rem 0.8rem;margin-top:0.8rem;font-size:0.76rem;color:#00875a">
          ✅ You've unlocked free delivery!
        </div>
        <?php endif; ?>
      </div>

      <!-- Delivery info -->
      <div class="card-fe p-3 mb-3">
        <div style="display:flex;gap:0.7rem;align-items:center;padding-bottom:0.7rem;border-bottom:1px solid #f5f5f5;margin-bottom:0.7rem">
          <div style="width:34px;height:34px;border-radius:9px;background:rgba(255,77,0,0.08);display:flex;align-items:center;justify-content:center;font-size:1.1rem">⏱️</div>
          <div>
            <div style="font-weight:600;font-size:0.85rem">Est. delivery: <?= $cartItems[0]['delivery_time'] ?>–<?= $cartItems[0]['delivery_time']+10 ?> mins</div>
            <div style="color:var(--text-muted);font-size:0.75rem">After order confirmation</div>
          </div>
        </div>
        <div style="display:flex;gap:0.7rem;align-items:center">
          <div style="width:34px;height:34px;border-radius:9px;background:rgba(0,200,150,0.08);display:flex;align-items:center;justify-content:center;font-size:1.1rem">🔒</div>
          <div>
            <div style="font-weight:600;font-size:0.85rem">Safe & secure checkout</div>
            <div style="color:var(--text-muted);font-size:0.75rem">100% encrypted payment</div>
          </div>
        </div>
      </div>

      <!-- Checkout CTA -->
      <a href="<?= SITE_URL ?>/checkout.php" class="btn-primary-fe w-100 justify-content-center" style="font-size:0.95rem;padding:0.95rem 1.5rem;border-radius:14px;text-decoration:none;letter-spacing:0.2px">
        Checkout &nbsp;·&nbsp; <span id="btnTotal">₹<?= number_format($total,2) ?></span> &nbsp;→
      </a>
      <div style="display:flex;justify-content:center;gap:1.5rem;margin-top:0.9rem">
        <span style="font-size:0.72rem;color:var(--text-muted)">🔒 Secure</span>
        <span style="font-size:0.72rem;color:var(--text-muted)">🚀 Fast</span>
        <span style="font-size:0.72rem;color:var(--text-muted)">💯 Fresh</span>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
</div>

<script>
let cpQtys   = {<?php foreach($cartItems as $i): echo $i['food_id'].':'.$i['quantity'].','; endforeach; ?>};
let cpPrices = {<?php foreach($cartItems as $i): echo $i['food_id'].':'.$i['price'].','; endforeach; ?>};
let deliveryFee = <?= $deliveryFee ?>;
let discountAmt = 0;

function cpRecalc() {
  let sub = 0, count = 0;
  for (let fid in cpQtys) { sub += cpQtys[fid] * (cpPrices[fid]||0); count += parseInt(cpQtys[fid]); }
  const total = sub + deliveryFee - discountAmt;
  const fmt   = n => '₹' + n.toFixed(2);
  ['summarySubtotal','summaryTotal','btnTotal'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.textContent = id==='summarySubtotal' ? fmt(sub) : fmt(total);
  });
  const ic = document.getElementById('itemCount');
  if (ic) ic.textContent = count;
}

function cpUpdate(foodId, action, price) {
  fetch(`${SITE_URL}/php/cart.php`,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`action=${action}&food_id=${foodId}`})
  .then(r=>r.json()).then(data=>{
    if (!data.success) return;
    updateCartBadge(data.cart_count);
    if (action==='increase') cpQtys[foodId]=(cpQtys[foodId]||0)+1;
    else { cpQtys[foodId]=(cpQtys[foodId]||1)-1; if(cpQtys[foodId]<=0){cpRemoveAnim(foodId);return;} }
    const q=document.getElementById('pqty-'+foodId), t=document.getElementById('ptotal-'+foodId);
    if(q) q.textContent=cpQtys[foodId];
    if(t) t.textContent='₹'+(cpQtys[foodId]*cpPrices[foodId]).toFixed(2);
    cpRecalc();
  });
}

function cpRemove(foodId) {
  fetch(`${SITE_URL}/php/cart.php`,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`action=remove&food_id=${foodId}`})
  .then(r=>r.json()).then(data=>{
    if(!data.success) return;
    updateCartBadge(data.cart_count);
    delete cpQtys[foodId];
    cpRemoveAnim(foodId);
    showToast('info','Item removed from cart');
  });
}

function cpRemoveAnim(foodId) {
  delete cpQtys[foodId];
  const el = document.getElementById('page-item-'+foodId);
  if (el) {
    el.style.transition='all 0.3s'; el.style.opacity='0'; el.style.transform='translateX(40px)';
    setTimeout(()=>{
      el.style.maxHeight=el.offsetHeight+'px'; el.style.overflow='hidden';
      el.style.maxHeight='0'; el.style.padding='0'; el.style.marginBottom='0';
    },300);
    setTimeout(()=>{ el.remove(); cpRecalc(); if(Object.keys(cpQtys).length===0) location.reload(); },600);
  }
  cpRecalc();
}

function cpClear() {
  if(!confirm('Remove all items from cart?')) return;
  fetch(`${SITE_URL}/php/cart.php`,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=clear'})
  .then(r=>r.json()).then(data=>{ if(data.success){updateCartBadge(0);location.reload();} });
}

function applyCoupon() {
  const code    = document.getElementById('couponInput').value.trim().toUpperCase();
  const msgEl   = document.getElementById('couponMsg');
  const coupons = {'SAVE50':50,'WELCOME100':100,'FOODIE20':20,'BCA2025':75};
  if (coupons[code]) {
    discountAmt = coupons[code];
    msgEl.innerHTML = `<span style="color:#00875a;font-weight:600">✅ Coupon applied! You saved ₹${discountAmt}</span>`;
    document.getElementById('discountRow').style.display='flex';
    document.getElementById('discountDisplay').textContent='-₹'+discountAmt;
    cpRecalc();
    showToast('success',`Coupon ${code} applied! Saved ₹${discountAmt}`);
  } else {
    msgEl.innerHTML = code ? `<span style="color:#e63946">❌ Invalid code. Try: SAVE50, WELCOME100, BCA2025</span>` : '';
  }
}
</script>

<?php require_once 'includes/footer.php'; ?>
