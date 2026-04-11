<?php
require_once 'includes/config.php';
requireLogin();

$pageTitle = 'Feedback';
require_once 'includes/header.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $uid = (int)$_SESSION['user_id'];

    if (!$name || !$email || !$message) {
        $error = 'Please fill in your name, email, and message.';
    } else {
        $db = getDB();
        $stmt = $db->prepare('INSERT INTO feedback (user_id, name, email, subject, message, rating) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('issssi', $uid, $name, $email, $subject, $message, $rating);
        $success = $stmt->execute();
        if (!$success) {
            $error = 'We could not save your feedback right now.';
        }
        $stmt->close();
        $db->close();
    }
}
?>

<div class="container py-5" style="max-width:720px">
  <div class="section-title">
    <h2>Your Feedback</h2>
    <p>Tell us what worked well and what we should improve.</p>
    <div class="title-line"></div>
  </div>

  <?php if ($success): ?>
  <div class="card-fe p-5 text-center mt-4">
    <h4 class="mt-2">Thank You</h4>
    <p class="text-muted">Your feedback has been submitted successfully.</p>
    <a href="<?= SITE_URL ?>/index.php" class="btn-primary-fe mt-3">Back to Home</a>
  </div>
  <?php else: ?>
  <div class="card-fe p-4 mt-4">
    <?php if ($error): ?>
    <div class="alert alert-danger rounded-3 mb-3"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="row g-3">
        <div class="col-md-6">
          <div class="form-group">
            <label>Your Name</label>
            <input type="text" name="name" class="form-control-fe" value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>" required>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" class="form-control-fe" value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>" required>
          </div>
        </div>
        <div class="col-12">
          <div class="form-group">
            <label>Subject</label>
            <input type="text" name="subject" class="form-control-fe" placeholder="App suggestion, delivery issue, feature request...">
          </div>
        </div>
        <div class="col-12">
          <label>Overall Rating</label>
          <input type="hidden" name="rating" id="feedbackRating" value="5">
          <div id="feedbackStars" style="display:flex;gap:0.35rem;margin-top:0.5rem">
            <?php for ($i = 1; $i <= 5; $i++): ?>
            <button type="button" class="feedback-star" data-value="<?= $i ?>" style="background:none;border:none;font-size:2rem;line-height:1;cursor:pointer;color:#d0d0d0;padding:0">★</button>
            <?php endfor; ?>
          </div>
          <div id="feedbackRatingLabel" style="font-size:0.82rem;color:var(--text-muted);margin-top:0.35rem">Excellent</div>
        </div>
        <div class="col-12">
          <div class="form-group">
            <label>Your Message</label>
            <textarea name="message" class="form-control-fe" rows="5" placeholder="Tell us about your experience, suggestions, or any issues..." required></textarea>
          </div>
        </div>
      </div>
      <button type="submit" class="btn-primary-fe w-100 justify-content-center mt-4">Send Feedback</button>
    </form>
  </div>
  <?php endif; ?>
</div>

<?php if (!$success): ?>
<script>
const feedbackLabels = {1: 'Poor', 2: 'Fair', 3: 'Good', 4: 'Great', 5: 'Excellent'};
const feedbackStars = document.querySelectorAll('.feedback-star');
const feedbackInput = document.getElementById('feedbackRating');
const feedbackLabel = document.getElementById('feedbackRatingLabel');

function renderFeedbackStars(value) {
  feedbackStars.forEach(star => {
    const starValue = parseInt(star.dataset.value, 10);
    star.style.color = starValue <= value ? '#ff8c00' : '#d0d0d0';
    star.style.transform = starValue <= value ? 'scale(1.05)' : 'scale(1)';
  });
  if (feedbackLabel) feedbackLabel.textContent = feedbackLabels[value] || '';
}

feedbackStars.forEach(star => {
  star.addEventListener('mouseover', () => renderFeedbackStars(parseInt(star.dataset.value, 10)));
  star.addEventListener('focus', () => renderFeedbackStars(parseInt(star.dataset.value, 10)));
  star.addEventListener('click', () => {
    feedbackInput.value = star.dataset.value;
    renderFeedbackStars(parseInt(star.dataset.value, 10));
  });
});

document.getElementById('feedbackStars')?.addEventListener('mouseleave', () => {
  renderFeedbackStars(parseInt(feedbackInput.value, 10));
});

renderFeedbackStars(parseInt(feedbackInput.value, 10));
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
