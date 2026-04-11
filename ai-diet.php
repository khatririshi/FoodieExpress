<?php
require_once 'includes/config.php';
requireLogin();

$pageTitle = 'AI Diet Assistant';
require_once 'includes/header.php';

$recommendations = [];
$analysisData = null;
$publicRestaurant = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['weight_goal'])) {
    $goal = sanitize($_POST['weight_goal'] ?? $_GET['weight_goal'] ?? 'maintain');
    $condition = sanitize($_POST['health_condition'] ?? $_GET['condition'] ?? 'none');
    $calories = (int)($_POST['daily_calories'] ?? $_GET['calories'] ?? 2000);

    $db = getDB();
    $publicRestaurant = getPublicRestaurant($db);
    $publicRestaurantId = getPublicRestaurantId($db);

    $conditions = ['f.is_available = 1'];
    $params = [];
    $types = '';

    if ($goal === 'lose') {
        $conditions[] = 'f.calories < 450';
    } elseif ($goal === 'gain') {
        $conditions[] = '(f.calories > 400 OR f.protein > 20)';
    } else {
        $conditions[] = 'f.calories < 600';
    }

    if ($condition === 'pcos') {
        $conditions[] = "f.health_tags LIKE '%pcos-friendly%'";
    } elseif ($condition === 'diabetes') {
        $conditions[] = "(f.health_tags LIKE '%diabetic-friendly%' OR f.health_tags LIKE '%low-sugar%')";
    } elseif ($condition === 'fitness') {
        $conditions[] = 'f.protein > 15';
    } elseif ($condition === 'low_carb') {
        $conditions[] = 'f.carbs < 30';
    }

    if ($publicRestaurantId > 0) {
        $conditions[] = 'f.restaurant_id = ?';
        $params[] = $publicRestaurantId;
        $types .= 'i';
    }

    $sql = 'SELECT f.*, r.name AS restaurant_name, r.delivery_time
            FROM food_items f
            JOIN restaurants r ON f.restaurant_id = r.id
            WHERE ' . implode(' AND ', $conditions) . '
            ORDER BY f.rating DESC, f.protein DESC
            LIMIT 12';

    if ($params) {
        $recommendationStmt = $db->prepare($sql);
        $recommendationStmt->bind_param($types, ...$params);
        $recommendationStmt->execute();
        $recommendations = $recommendationStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $recommendationStmt->close();
    } else {
        $recommendations = $db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    $uid = (int)$_SESSION['user_id'];
    $profileStmt = $db->prepare('UPDATE users SET weight_goal = ?, health_condition = ?, daily_calories = ? WHERE id = ?');
    $profileStmt->bind_param('ssii', $goal, $condition, $calories, $uid);
    $profileStmt->execute();
    $profileStmt->close();

    $analysisData = ['goal' => $goal, 'condition' => $condition, 'calories' => $calories];
    $db->close();
}

$userProfile = null;
if (!$analysisData) {
    $db = getDB();
    $uid = (int)$_SESSION['user_id'];
    $publicRestaurant = getPublicRestaurant($db);
    $profileStmt = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $profileStmt->bind_param('i', $uid);
    $profileStmt->execute();
    $userProfile = $profileStmt->get_result()->fetch_assoc();
    $profileStmt->close();
    $db->close();
}
?>

<section class="page-hero page-hero--wellness">
  <div class="container page-hero__inner">
    <div>
      <div class="page-hero__eyebrow">AI-assisted wellness</div>
      <h1 class="page-hero__title">AI Diet &amp; Health Assistant</h1>
      <p class="page-hero__desc">
        Tune recommendations around your goals and health preferences while staying inside the same single-kitchen experience used across FoodieExpress.
      </p>
    </div>
    <div class="page-hero__metrics">
      <div class="page-hero__metric">
        <strong><?= htmlspecialchars($publicRestaurant['name'] ?? 'House Kitchen') ?></strong>
        <span>Menu source</span>
      </div>
      <div class="page-hero__metric">
        <strong><?= (int)($publicRestaurant['delivery_time'] ?? 25) ?> min</strong>
        <span>Delivery pace</span>
      </div>
    </div>
  </div>
</section>

<div class="container section-pad">
  <div class="row g-4">
    <div class="col-lg-4">
      <div class="card-fe p-4 interactive-surface" style="position:sticky;top:104px" data-reveal>
        <div class="section-title__eyebrow">Tell us about you</div>
        <h2 class="mb-4" style="font-size:2rem">Build your meal profile</h2>

        <form method="POST">
          <div class="form-group">
            <label>Weight Goal</label>
            <select name="weight_goal" class="form-control-fe">
              <option value="lose" <?= ($analysisData['goal'] ?? $userProfile['weight_goal'] ?? '') === 'lose' ? 'selected' : '' ?>>Lose Weight</option>
              <option value="maintain" <?= ($analysisData['goal'] ?? $userProfile['weight_goal'] ?? 'maintain') === 'maintain' ? 'selected' : '' ?>>Maintain Weight</option>
              <option value="gain" <?= ($analysisData['goal'] ?? $userProfile['weight_goal'] ?? '') === 'gain' ? 'selected' : '' ?>>Gain Weight / Muscle</option>
            </select>
          </div>

          <div class="form-group mt-3">
            <label>Health Condition</label>
            <select name="health_condition" class="form-control-fe">
              <option value="none" <?= ($analysisData['condition'] ?? $userProfile['health_condition'] ?? 'none') === 'none' ? 'selected' : '' ?>>None / General Health</option>
              <option value="pcos" <?= ($analysisData['condition'] ?? '') === 'pcos' ? 'selected' : '' ?>>PCOS</option>
              <option value="diabetes" <?= ($analysisData['condition'] ?? '') === 'diabetes' ? 'selected' : '' ?>>Diabetes / Blood Sugar</option>
              <option value="fitness" <?= ($analysisData['condition'] ?? '') === 'fitness' ? 'selected' : '' ?>>Fitness / Bodybuilding</option>
              <option value="low_carb" <?= ($analysisData['condition'] ?? '') === 'low_carb' ? 'selected' : '' ?>>Low Carb Diet</option>
            </select>
          </div>

          <div class="form-group mt-3">
            <label>Daily Calorie Goal (kcal)</label>
            <input
              type="number"
              name="daily_calories"
              class="form-control-fe"
              value="<?= (int)($analysisData['calories'] ?? $userProfile['daily_calories'] ?? 2000) ?>"
              placeholder="e.g. 1800"
              min="800"
              max="5000"
            >
            <small class="text-muted">Most adults land between 1800 and 2500 kcal per day.</small>
          </div>

          <button type="submit" class="btn-primary-fe w-100 justify-content-center mt-4">Get My Meal Plan</button>
        </form>

        <div class="card-fe mt-4 p-3">
          <div class="section-title__eyebrow mb-2">Good to remember</div>
          <ul class="mb-0 ps-3" style="color:var(--text-muted);line-height:1.8;font-size:0.9rem">
            <li>PCOS plans lean lower-GI and anti-inflammatory.</li>
            <li>Diabetes plans prefer lower sugar and more fiber.</li>
            <li>Fitness plans bias toward higher protein plates.</li>
            <li>Hydration still matters more than any single meal.</li>
          </ul>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <?php if ($analysisData): ?>
      <div class="card-fe p-4 mb-4 interactive-surface" data-reveal>
        <div class="section-title__eyebrow">Analysis complete</div>
        <h2 class="mb-3" style="font-size:2rem">Your personalized meal direction</h2>
        <div class="row g-3">
          <div class="col-md-4">
            <div class="dashboard-stat-card h-100">
              <span>Goal</span>
              <strong><?= ucfirst($analysisData['goal']) ?></strong>
            </div>
          </div>
          <div class="col-md-4">
            <div class="dashboard-stat-card h-100">
              <span>Condition</span>
              <strong><?= ucfirst(str_replace('_', ' ', $analysisData['condition'])) ?></strong>
            </div>
          </div>
          <div class="col-md-4">
            <div class="dashboard-stat-card h-100">
              <span>Daily target</span>
              <strong><?= (int)$analysisData['calories'] ?> kcal</strong>
            </div>
          </div>
        </div>
      </div>

      <div class="section-title mb-4" data-reveal>
        <span class="section-title__eyebrow">Recommended from the kitchen</span>
        <h2>Meal options aligned to your profile</h2>
        <p><?= count($recommendations) ?> suggestions based on your current goals.</p>
      </div>

      <?php if (empty($recommendations)): ?>
      <div class="empty-state card-fe" data-reveal>
        <h3>No perfect match yet</h3>
        <p>Try adjusting your criteria to see more options from the house kitchen.</p>
      </div>
      <?php else: ?>
      <div class="row g-4">
        <?php foreach ($recommendations as $food): ?>
        <div class="col-md-6" data-reveal>
          <article class="food-card food-card--menu interactive-surface h-100">
            <div class="food-image-box food-image-box--menu">
              <img src="<?= resolveFoodImage($food) ?>" alt="<?= htmlspecialchars($food['name']) ?>" class="food-thumb-image">
            </div>
            <div class="food-info">
              <div class="food-info__meta">
                <span class="<?= $food['is_veg'] ? 'veg-dot' : 'nonveg-dot' ?>"></span>
                <span><?= !empty($food['health_tags']) ? 'Health-aligned pick' : 'Kitchen recommendation' ?></span>
              </div>
              <div class="food-card__topline">
                <h3 class="food-name"><?= htmlspecialchars($food['name']) ?></h3>
                <span class="food-rating">&#9733; <?= htmlspecialchars((string)$food['rating']) ?></span>
              </div>
              <p class="food-desc">
                <?= !empty($food['description']) ? htmlspecialchars(substr($food['description'], 0, 110)) . '...' : 'A strong fit for your current health and calorie preferences.' ?>
              </p>
              <div class="food-card__meta-row">
                <?php if (!empty($food['calories'])): ?><span><?= (int)$food['calories'] ?> cal</span><?php endif; ?>
                <?php if (!empty($food['protein'])): ?><span><?= (int)$food['protein'] ?>g protein</span><?php endif; ?>
              </div>
              <div class="food-card__footer">
                <div>
                  <span class="food-price">Rs <?= number_format((float)$food['price'], 0) ?></span>
                  <?php if (!empty($food['health_tags'])): ?>
                  <div class="food-card__subline"><?= htmlspecialchars(implode(', ', array_slice(explode(',', $food['health_tags']), 0, 2))) ?></div>
                  <?php endif; ?>
                </div>
                <button class="btn-add-cart" onclick='addToCart(<?= (int)$food["id"] ?>, <?= json_encode($food["name"]) ?>, <?= (float)$food["price"] ?>)'>Add To Cart</button>
              </div>
            </div>
          </article>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php else: ?>
      <div class="card-fe p-5 text-center interactive-surface" data-reveal>
        <div class="section-title__eyebrow">Personalized guidance</div>
        <h2 class="mb-3" style="font-size:2.2rem">Your personal nutrition assistant</h2>
        <p class="mx-auto" style="max-width:540px;color:var(--text-muted)">
          Fill in your health profile and FoodieExpress will suggest the best dishes from the house kitchen for your current goals.
        </p>
        <div class="row g-3 mt-4 text-start">
          <?php
          $examples = [
              ['PCOS-Friendly', 'Lower-GI and anti-inflammatory leaning suggestions.'],
              ['Diabetic-Safe', 'Lower sugar and higher-fiber recommendation patterns.'],
              ['High Protein', 'Stronger protein bias for training and recovery goals.'],
              ['Weight Loss', 'More filling plates that stay within lighter calorie ranges.'],
          ];
          foreach ($examples as [$title, $desc]):
          ?>
          <div class="col-md-6">
            <div class="card-fe p-3 h-100">
              <strong><?= $title ?></strong>
              <p class="mb-0 mt-2" style="color:var(--text-muted)"><?= $desc ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
