<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/dashboard.php');
    exit;
}

$error = '';
$form = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'weight_goal' => 'maintain',
    'health_condition' => 'none',
    'daily_calories' => 2000
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['name'] = sanitize($_POST['name'] ?? '');
    $form['email'] = sanitize($_POST['email'] ?? '');
    $form['phone'] = sanitize($_POST['phone'] ?? '');
    $form['weight_goal'] = sanitize($_POST['weight_goal'] ?? 'maintain');
    $form['health_condition'] = sanitize($_POST['health_condition'] ?? 'none');
    $form['daily_calories'] = max(800, min(5000, (int)($_POST['daily_calories'] ?? 2000)));
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $allowedGoals = ['lose', 'gain', 'maintain'];
    $allowedHealth = ['none', 'pcos', 'diabetes', 'fitness', 'low_carb'];

    if (!in_array($form['weight_goal'], $allowedGoals, true)) {
        $form['weight_goal'] = 'maintain';
    }

    if (!in_array($form['health_condition'], $allowedHealth, true)) {
        $form['health_condition'] = 'none';
    }

    if (!$form['name'] || !$form['email'] || !$password) {
        $error = 'Please fill all required fields.';
    } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $db = getDB();
        $check = $db->prepare('SELECT id FROM users WHERE email = ?');
        $check->bind_param('s', $form['email']);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = 'Email already registered. Please login.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare(
                'INSERT INTO users (name, email, phone, password, weight_goal, health_condition, daily_calories)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param(
                'ssssssi',
                $form['name'],
                $form['email'],
                $form['phone'],
                $hash,
                $form['weight_goal'],
                $form['health_condition'],
                $form['daily_calories']
            );

            if ($stmt->execute()) {
                $_SESSION['user_id'] = $db->insert_id;
                $_SESSION['user_name'] = $form['name'];
                $_SESSION['user_email'] = $form['email'];
                $stmt->close();
                $check->close();
                $db->close();

                setFlash('success', 'Welcome to FoodieExpress!');
                header('Location: ' . SITE_URL . '/dashboard.php');
                exit;
            }

            $error = 'Registration failed. Please try again.';
            $stmt->close();
        }

        $check->close();
        $db->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="site-url" content="<?= SITE_URL ?>">
  <title>Register | FoodieExpress</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="<?= SITE_URL ?>/css/style.css" rel="stylesheet">
</head>
<body class="auth-screen auth-screen--register">
  <div class="auth-shell auth-shell--register">
    <section class="auth-panel auth-panel--wide interactive-surface">
      <div class="auth-panel__header">
        <a href="<?= SITE_URL ?>/index.php" class="auth-brand-lockup">
          <span class="auth-brand-lockup__mark"><i class="bi bi-fire"></i></span>
          <span>
            <strong>FoodieExpress</strong>
            <small>Customer registration</small>
          </span>
        </a>
        <a href="<?= SITE_URL ?>/login.php" class="auth-secondary-link">Already registered?</a>
      </div>

      <div class="auth-panel__intro">
        <h2>Create your account</h2>
        <p>Create one customer profile and use it across the menu, dashboard, checkout, and AI diet experience.</p>
      </div>

      <div class="auth-role-note auth-role-note--soft">
        <span>Customer signup only</span>
        <span>No extra steps. No restaurant choices.</span>
      </div>

      <?php if ($error): ?>
      <div class="alert alert-danger auth-alert" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
      <?php endif; ?>

      <form method="POST" class="auth-form">
        <div class="auth-grid auth-grid--two">
          <label class="auth-field auth-field--full">
            <span class="auth-field__label">Full Name</span>
            <div class="auth-input-wrap">
              <i class="bi bi-person"></i>
              <input type="text" name="name" class="form-control-fe" placeholder="Your full name" value="<?= htmlspecialchars($form['name']) ?>" required>
            </div>
          </label>

          <label class="auth-field">
            <span class="auth-field__label">Email Address</span>
            <div class="auth-input-wrap">
              <i class="bi bi-envelope"></i>
              <input type="email" name="email" class="form-control-fe" placeholder="you@example.com" value="<?= htmlspecialchars($form['email']) ?>" required>
            </div>
          </label>

          <label class="auth-field">
            <span class="auth-field__label">Phone Number</span>
            <div class="auth-input-wrap">
              <i class="bi bi-telephone"></i>
              <input type="tel" name="phone" class="form-control-fe" placeholder="10-digit number" value="<?= htmlspecialchars($form['phone']) ?>">
            </div>
          </label>

          <label class="auth-field">
            <span class="auth-field__label">Password</span>
            <div class="auth-input-wrap auth-input-wrap--password">
              <i class="bi bi-shield-lock"></i>
              <input type="password" name="password" id="registerPass" class="form-control-fe" placeholder="Min 6 characters" required>
              <button type="button" class="auth-toggle" onclick="togglePass('registerPass', 'registerEyeIcon')">
                <i class="bi bi-eye" id="registerEyeIcon"></i>
              </button>
            </div>
          </label>

          <label class="auth-field">
            <span class="auth-field__label">Confirm Password</span>
            <div class="auth-input-wrap auth-input-wrap--password">
              <i class="bi bi-check-circle"></i>
              <input type="password" name="confirm_password" id="confirmPass" class="form-control-fe" placeholder="Repeat password" required>
              <button type="button" class="auth-toggle" onclick="togglePass('confirmPass', 'confirmEyeIcon')">
                <i class="bi bi-eye" id="confirmEyeIcon"></i>
              </button>
            </div>
          </label>
        </div>

        <details class="auth-details-box auth-details-box--premium">
          <summary>
            <span>
              Health Profile
              <small>Optional, but useful for the AI diet assistant</small>
            </span>
            <span class="auth-details-box__badge">Personalize</span>
          </summary>

          <div class="auth-details-content auth-grid auth-grid--two">
            <label class="auth-field">
              <span class="auth-field__label">Weight Goal</span>
              <div class="auth-input-wrap auth-input-wrap--select">
                <i class="bi bi-bullseye"></i>
                <select name="weight_goal" class="form-control-fe">
                  <option value="maintain" <?= $form['weight_goal'] === 'maintain' ? 'selected' : '' ?>>Maintain Weight</option>
                  <option value="lose" <?= $form['weight_goal'] === 'lose' ? 'selected' : '' ?>>Lose Weight</option>
                  <option value="gain" <?= $form['weight_goal'] === 'gain' ? 'selected' : '' ?>>Gain Weight</option>
                </select>
              </div>
            </label>

            <label class="auth-field">
              <span class="auth-field__label">Health Condition</span>
              <div class="auth-input-wrap auth-input-wrap--select">
                <i class="bi bi-heart-pulse"></i>
                <select name="health_condition" class="form-control-fe">
                  <option value="none" <?= $form['health_condition'] === 'none' ? 'selected' : '' ?>>None</option>
                  <option value="pcos" <?= $form['health_condition'] === 'pcos' ? 'selected' : '' ?>>PCOS</option>
                  <option value="diabetes" <?= $form['health_condition'] === 'diabetes' ? 'selected' : '' ?>>Diabetes</option>
                  <option value="fitness" <?= $form['health_condition'] === 'fitness' ? 'selected' : '' ?>>Fitness Goal</option>
                  <option value="low_carb" <?= $form['health_condition'] === 'low_carb' ? 'selected' : '' ?>>Low Carb Diet</option>
                </select>
              </div>
            </label>

            <label class="auth-field auth-field--full">
              <span class="auth-field__label">Daily Calorie Goal</span>
              <div class="auth-input-wrap">
                <i class="bi bi-lightning"></i>
                <input type="number" name="daily_calories" class="form-control-fe" placeholder="e.g. 1800" value="<?= (int)$form['daily_calories'] ?>" min="800" max="5000">
              </div>
            </label>
          </div>
        </details>

        <div class="auth-inline-banner">
          <div>
            <strong>Customer accounts only</strong>
            <span>Admin access stays on the login page. Registration is only for customer profiles and saved preferences.</span>
          </div>
          <span class="auth-inline-banner__pill">Clearer</span>
        </div>

        <button type="submit" class="btn-primary-fe auth-submit auth-submit--wide">
          Create My Account <i class="bi bi-arrow-right"></i>
        </button>
      </form>

      <div class="auth-panel__footer">
        <p>Already have an account? <a href="<?= SITE_URL ?>/login.php">Sign in now</a></p>
      </div>
    </section>

    <section class="auth-visual auth-visual--register">
      <a href="<?= SITE_URL ?>/index.php" class="auth-return-link">
        <i class="bi bi-arrow-left"></i> Back to home
      </a>

      <div class="auth-visual__content">
        <div class="auth-kicker">Clean signup, better first impression</div>
        <h1 class="auth-visual__title">Create your account in a cleaner, easier layout.</h1>
        <p class="auth-visual__desc">
          Better imagery, tighter spacing, and simpler form framing make signup feel more polished from the start.
        </p>

        <div class="auth-app-scene depth-scene">
          <figure class="auth-device auth-device--warm" data-depth="18">
            <img src="<?= assetUrl('images/foods/butter-chicken.jpg') ?>" alt="Signature butter chicken">
            <figcaption>
              <span>Signature visual</span>
              <strong>Food photography that matches the product instead of generic decoration.</strong>
            </figcaption>
          </figure>

          <div class="auth-mini-card auth-mini-card--delivery" data-depth="8">
            <span>After signup</span>
            <strong>Dashboard access, saved profile, and a smoother return journey.</strong>
          </div>

          <div class="auth-mini-card auth-mini-card--rating" data-depth="12">
            <span>Why it feels better</span>
            <strong>Cleaner form framing with subtle motion and less clutter.</strong>
          </div>

          <div class="auth-trust-strip">
            <span><i class="bi bi-camera"></i> Better food discovery</span>
            <span><i class="bi bi-heart-pulse"></i> Health-aware suggestions</span>
            <span><i class="bi bi-person-check"></i> Stronger account clarity</span>
          </div>
        </div>
      </div>
    </section>
  </div>

  <script>
  function togglePass(id, iconId) {
      const input = document.getElementById(id);
      const icon = document.getElementById(iconId);

      if (input.type === 'password') {
          input.type = 'text';
          icon.className = 'bi bi-eye-slash';
      } else {
          input.type = 'password';
          icon.className = 'bi bi-eye';
      }
  }
  </script>
  <?php require_once __DIR__ . '/includes/chatbot-widget.php'; ?>
  <script src="<?= SITE_URL ?>/js/chatbot.js"></script>
</body>
</html>
