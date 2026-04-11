<?php
require_once 'includes/config.php';
requireLogin();

$pageTitle = 'Profile';
require_once 'includes/header.php';

$db = getDB();
$uid = (int)$_SESSION['user_id'];

$stmt = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $weightGoal = sanitize($_POST['weight_goal'] ?? 'maintain');
    $healthCondition = sanitize($_POST['health_condition'] ?? 'none');
    $dailyCalories = max(800, min(5000, (int)($_POST['daily_calories'] ?? 2000)));
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $allowedGoals = ['lose', 'gain', 'maintain'];
    $allowedHealth = ['none', 'pcos', 'diabetes', 'fitness', 'low_carb'];

    if (!$name || !$email) {
        $error = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!in_array($weightGoal, $allowedGoals, true)) {
        $error = 'Invalid weight goal selected.';
    } elseif (!in_array($healthCondition, $allowedHealth, true)) {
        $error = 'Invalid health condition selected.';
    } elseif ($newPassword && strlen($newPassword) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($newPassword && $newPassword !== $confirmPassword) {
        $error = 'New password and confirm password do not match.';
    } else {
        $emailStmt = $db->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
        $emailStmt->bind_param('si', $email, $uid);
        $emailStmt->execute();
        $existingUser = $emailStmt->get_result()->fetch_assoc();
        $emailStmt->close();

        if ($existingUser) {
            $error = 'That email address is already in use.';
        } else {
            if ($newPassword) {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $update = $db->prepare(
                    'UPDATE users
                     SET name = ?, email = ?, phone = ?, address = ?, weight_goal = ?, health_condition = ?, daily_calories = ?, password = ?
                     WHERE id = ?'
                );
                $update->bind_param('ssssssisi', $name, $email, $phone, $address, $weightGoal, $healthCondition, $dailyCalories, $hash, $uid);
            } else {
                $update = $db->prepare(
                    'UPDATE users
                     SET name = ?, email = ?, phone = ?, address = ?, weight_goal = ?, health_condition = ?, daily_calories = ?
                     WHERE id = ?'
                );
                $update->bind_param('ssssssii', $name, $email, $phone, $address, $weightGoal, $healthCondition, $dailyCalories, $uid);
            }

            if ($update->execute()) {
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                setFlash('success', 'Your profile has been updated.');
                $update->close();
                $db->close();
                header('Location: ' . SITE_URL . '/profile.php');
                exit;
            }

            $error = 'We could not save your profile right now. Please try again.';
            $update->close();
        }
    }

    $user = array_merge($user, [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'address' => $address,
        'weight_goal' => $weightGoal,
        'health_condition' => $healthCondition,
        'daily_calories' => $dailyCalories,
    ]);
}

$db->close();
?>

<section class="page-hero page-hero--profile">
  <div class="container page-hero__inner">
    <div class="page-hero__eyebrow">Account hub</div>
    <h1 class="page-hero__title">My Profile</h1>
    <p class="page-hero__desc">Keep your account details, delivery address, and health preferences in sync so the platform stays useful every time you return.</p>
  </div>
</section>

<div class="container py-5" style="max-width:960px">
  <?php if ($error): ?>
  <div class="alert alert-danger rounded-3 mb-4"><?= $error ?></div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-4">
      <div class="card-fe p-4 text-center interactive-surface">
        <div style="width:90px;height:90px;border-radius:50%;background:var(--gradient);display:flex;align-items:center;justify-content:center;color:white;font-size:2.1rem;font-weight:800;margin:0 auto 1rem">
          <?= strtoupper(substr($user['name'], 0, 1)) ?>
        </div>
        <h4 style="font-family:'Syne',sans-serif;margin-bottom:0.3rem"><?= htmlspecialchars($user['name']) ?></h4>
        <div style="font-size:0.88rem;color:var(--text-muted)"><?= htmlspecialchars($user['email']) ?></div>
        <div style="font-size:0.82rem;color:var(--text-muted);margin-top:1rem">
          Member since <?= date('d M Y', strtotime($user['created_at'])) ?>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card-fe p-4 interactive-surface">
        <h5 style="font-family:'Syne',sans-serif;margin-bottom:1.25rem">Account Details</h5>
        <form method="POST">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" class="form-control-fe" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control-fe" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" class="form-control-fe" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Daily Calories</label>
                <input type="number" name="daily_calories" class="form-control-fe" min="800" max="5000" value="<?= (int)($user['daily_calories'] ?? 2000) ?>">
              </div>
            </div>
            <div class="col-12">
              <div class="form-group">
                <label>Address</label>
                <textarea name="address" class="form-control-fe" rows="3" placeholder="House/Flat no, Street, Area, City, Pincode"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Weight Goal</label>
                <select name="weight_goal" class="form-control-fe">
                  <?php foreach (['maintain' => 'Maintain Weight', 'lose' => 'Lose Weight', 'gain' => 'Gain Weight'] as $value => $label): ?>
                  <option value="<?= $value ?>" <?= ($user['weight_goal'] ?? 'maintain') === $value ? 'selected' : '' ?>><?= $label ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Health Condition</label>
                <select name="health_condition" class="form-control-fe">
                  <?php foreach (['none' => 'None', 'pcos' => 'PCOS', 'diabetes' => 'Diabetes', 'fitness' => 'Fitness Goal', 'low_carb' => 'Low Carb Diet'] as $value => $label): ?>
                  <option value="<?= $value ?>" <?= ($user['health_condition'] ?? 'none') === $value ? 'selected' : '' ?>><?= $label ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" class="form-control-fe" placeholder="Leave blank to keep current password">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control-fe" placeholder="Repeat new password">
              </div>
            </div>
          </div>
          <button type="submit" class="btn-primary-fe mt-4">Save Profile</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
