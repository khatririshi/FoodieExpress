<?php
require_once 'includes/config.php';

if (isAdminLoggedIn()) {
    header('Location: ' . SITE_URL . '/admin/index.php');
    exit;
}

if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/dashboard.php');
    exit;
}

$error = '';
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = sanitize($_POST['identifier'] ?? '');
    $pass = $_POST['password'] ?? '';

    if (!$identifier || !$pass) {
        $error = 'Please fill in all fields.';
    } else {
        $db = getDB();

        $stmt = $db->prepare('SELECT * FROM admin WHERE username = ?');
        $stmt->bind_param('s', $identifier);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($admin && password_verify($pass, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['username'];
            $db->close();

            header('Location: ' . SITE_URL . '/admin/index.php');
            exit;
        }

        $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->bind_param('s', $identifier);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $db->close();

            $redirect = sanitizeRedirectTarget($_GET['redirect'] ?? '', siteUrl('dashboard.php'));
            header("Location: $redirect");
            exit;
        }

        $error = 'Invalid username/email or password. Please try again.';
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
  <title>Login | FoodieExpress</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="<?= SITE_URL ?>/css/style.css" rel="stylesheet">
</head>
<body class="auth-screen auth-screen--login">
  <div class="auth-shell">
    <section class="auth-visual auth-visual--login">
      <a href="<?= SITE_URL ?>/index.php" class="auth-return-link">
        <i class="bi bi-arrow-left"></i> Back to home
      </a>

      <div class="auth-visual__content">
        <div class="auth-kicker">One account, faster ordering</div>
        <h1 class="auth-visual__title">Sign in and pick up your next order faster.</h1>
        <p class="auth-visual__desc">
          Saved details, one restaurant, and a cleaner interface make the return experience feel much smoother.
        </p>

        <div class="auth-app-scene depth-scene">
          <figure class="auth-device" data-depth="18">
            <img src="<?= assetUrl('images/foods/chicken-biryani.jpg') ?>" alt="Signature dish preview">
            <figcaption>
              <span>House special</span>
              <strong>Strong dish imagery instead of cluttered promo blocks.</strong>
            </figcaption>
          </figure>

          <div class="auth-mini-card auth-mini-card--delivery" data-depth="8">
            <span>Fast return</span>
            <strong>Saved profile, easier reorders, quicker checkout.</strong>
          </div>

          <div class="auth-mini-card auth-mini-card--rating" data-depth="12">
            <span>Single restaurant focus</span>
            <strong>One premium kitchen. No confusing choices.</strong>
          </div>

          <div class="auth-trust-strip">
            <span><i class="bi bi-bag-check"></i> Order tracking</span>
            <span><i class="bi bi-heart-pulse"></i> Saved preferences</span>
            <span><i class="bi bi-arrow-repeat"></i> Faster re-entry</span>
          </div>
        </div>
      </div>
    </section>

    <section class="auth-panel interactive-surface">
      <div class="auth-panel__header">
        <a href="<?= SITE_URL ?>/index.php" class="auth-brand-lockup">
          <span class="auth-brand-lockup__mark"><i class="bi bi-fire"></i></span>
          <span>
            <strong>FoodieExpress</strong>
            <small>Admin-managed restaurant storefront</small>
          </span>
        </a>
        <span class="auth-panel__tag">User + admin access</span>
      </div>

      <div class="auth-panel__intro">
        <h2>Welcome back</h2>
        <p>Customer login stays simple here. Admin can still enter from the same page to manage the restaurant, menu, and orders.</p>
      </div>

      <div class="auth-role-note">
        <span>Customer: email login</span>
        <span>Admin: username <strong>admin</strong></span>
      </div>

      <?php if ($error): ?>
      <div class="alert alert-danger auth-alert" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
      <?php endif; ?>

      <form method="POST" class="auth-form auth-form--stack">
        <label class="auth-field">
          <span class="auth-field__label">Email or Username</span>
          <div class="auth-input-wrap">
            <i class="bi bi-person-badge"></i>
            <input
              type="text"
              name="identifier"
              class="form-control-fe"
              placeholder="you@example.com or admin"
              value="<?= htmlspecialchars($identifier) ?>"
              required
              autofocus
            >
          </div>
          <small>Use your customer email, or the admin username if you manage the restaurant.</small>
        </label>

        <label class="auth-field">
          <span class="auth-field__label">Password</span>
          <div class="auth-input-wrap auth-input-wrap--password">
            <i class="bi bi-shield-lock"></i>
            <input
              type="password"
              name="password"
              id="loginPass"
              class="form-control-fe"
              placeholder="Enter your password"
              required
            >
            <button type="button" class="auth-toggle" onclick="togglePass('loginPass', 'eyeIcon')">
              <i class="bi bi-eye" id="eyeIcon"></i>
            </button>
          </div>
        </label>

        <div class="auth-inline-banner">
          <div>
            <strong>Admin note</strong>
            <span>Customer accounts are created from the registration page. Admin access remains available here.</span>
          </div>
          <span class="auth-inline-banner__pill">Secure</span>
        </div>

        <button type="submit" class="btn-primary-fe auth-submit">
          Enter FoodieExpress <i class="bi bi-arrow-right"></i>
        </button>
      </form>

      <div class="auth-panel__footer">
        <p>New here? <a href="<?= SITE_URL ?>/register.php">Create your account</a></p>
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
