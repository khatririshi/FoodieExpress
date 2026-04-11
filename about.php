<?php
$pageTitle = 'About';
require_once 'includes/header.php';
?>

<section class="page-hero page-hero--sunset">
  <div class="container page-hero__inner">
    <div>
      <div class="page-hero__eyebrow">Project overview</div>
      <h1 class="page-hero__title">About FoodieExpress</h1>
      <p class="page-hero__desc">
        FoodieExpress is a college project focused on one admin-managed restaurant experience, blending food ordering, account flows, AI diet suggestions, and a cleaner storefront design.
      </p>
    </div>
  </div>
</section>

<div class="container section-pad">
  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card-fe p-4 h-100 interactive-surface" data-reveal>
        <div class="section-title__eyebrow">What makes it different</div>
        <h2 style="font-size:2rem">One restaurant, one clearer experience.</h2>
        <p style="color:var(--text-muted);line-height:1.9">
          The current version centers a single house kitchen instead of a crowded marketplace. That makes it a stronger demo of account flow, menu filtering, cart handling, checkout, order tracking, feedback, and AI-assisted recommendations inside one consistent visual system.
        </p>
        <div class="row g-3 mt-2">
          <?php foreach ([
              ['Editorial menu search', 'Find dishes and categories with less clutter and stronger hierarchy.'],
              ['Health-aware profiles', 'Store goals like calorie targets and conditions for AI meal suggestions.'],
              ['Order tracking', 'Follow recent orders through a cleaner customer dashboard.'],
              ['Admin dashboard', 'Manage the restaurant, foods, orders, users, and feedback in one place.'],
          ] as [$title, $text]): ?>
          <div class="col-md-6">
            <div class="card-fe p-3 h-100">
              <strong><?= $title ?></strong>
              <p class="mb-0 mt-2" style="color:var(--text-muted)"><?= $text ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card-fe p-4 mb-4 interactive-surface" data-reveal>
        <div class="section-title__eyebrow">Technology stack</div>
        <div class="d-flex flex-column gap-2 mt-3">
          <?php foreach (['PHP 8 + MySQL', 'XAMPP local development', 'Bootstrap 5 foundation', 'Shared CSS design system', 'Admin + customer session flows'] as $item): ?>
          <div class="card-fe p-3"><?= $item ?></div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card-fe p-4 interactive-surface" data-reveal>
        <div class="section-title__eyebrow">Current focus</div>
        <h2 style="font-size:1.8rem">Premium presentation with practical functionality.</h2>
        <p style="color:var(--text-muted);line-height:1.9">
          This pass prioritizes a luxury-editorial front end, menu usability, cleaner auth screens, and a more cohesive user-facing shell while keeping the existing backend logic intact.
        </p>
        <a href="<?= SITE_URL ?>/menu.php" class="btn-primary-fe">Explore The Menu</a>
      </div>
    </div>
  </div>

  <div class="row g-4 mt-1">
    <div class="col-12">
      <div class="card-fe p-4 interactive-surface about-college-card" data-reveal>
        <div class="about-college-card__media">
          <img src="<?= SITE_URL ?>/images/charusat-logo.png" alt="CHARUSAT logo">
        </div>
        <div class="about-college-card__content">
          <div class="section-title__eyebrow">College affiliation</div>
          <h2 style="font-size:2rem">Charotar University of Science and Technology (CHARUSAT)</h2>
          <p style="color:var(--text-muted);line-height:1.9;max-width:760px">
            This project now carries CHARUSAT branding so the presentation clearly shows its college identity alongside the FoodieExpress interface.
          </p>
          <div class="about-college-card__details">
            <span><i class="bi bi-geo-alt"></i> CHARUSAT Campus, Off. Nadiad-Petlad Highway, Changa - 388421, Gujarat</span>
            <span><i class="bi bi-telephone"></i> +91 2697 265011 / 21</span>
            <span><i class="bi bi-envelope"></i> info@charusat.ac.in</span>
            <span><i class="bi bi-globe2"></i> <a href="https://www.charusat.ac.in/" target="_blank" rel="noreferrer">www.charusat.ac.in</a></span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
