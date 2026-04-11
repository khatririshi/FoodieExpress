<?php
$pageTitle = 'Surprise Me';
require_once 'includes/header.php';
?>

<section class="page-hero page-hero--sunset">
  <div class="container page-hero__inner">
    <div class="page-hero__eyebrow">Decision-free ordering</div>
    <h1 class="page-hero__title">Surprise Me</h1>
    <p class="page-hero__desc">Tell the platform your budget, spice mood, and food type, then let it cut through the noise and hand you a strong pick.</p>
  </div>
</section>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-6">
      <div class="card-fe p-4 interactive-surface">
        <h5 style="font-family:'Syne',sans-serif;margin-bottom:1.5rem;text-align:center">Set Your Preferences</h5>
        <div class="form-group mb-3">
          <label>Budget</label>
          <select id="budget" class="form-control-fe">
            <option value="100">Under Rs 100</option>
            <option value="200" selected>Under Rs 200</option>
            <option value="300">Under Rs 300</option>
            <option value="500">Under Rs 500</option>
            <option value="999">Any Budget</option>
          </select>
        </div>
        <div class="form-group mb-3">
          <label>Food Type</label>
          <div class="d-flex gap-3 mt-1 flex-wrap">
            <label style="cursor:pointer;display:flex;align-items:center;gap:0.5rem">
              <input type="radio" name="type" value="veg" checked> Veg
            </label>
            <label style="cursor:pointer;display:flex;align-items:center;gap:0.5rem">
              <input type="radio" name="type" value="nonveg"> Non-Veg
            </label>
            <label style="cursor:pointer;display:flex;align-items:center;gap:0.5rem">
              <input type="radio" name="type" value="any"> Any
            </label>
          </div>
        </div>
        <div class="form-group mb-4">
          <label>Spice Level</label>
          <select id="spice" class="form-control-fe">
            <option value="mild">Mild</option>
            <option value="medium" selected>Medium</option>
            <option value="spicy">Spicy</option>
            <option value="any">Any</option>
          </select>
        </div>
        <button id="surpriseBtn" class="surprise-btn w-100" onclick="doSurprise()">Surprise Me</button>
      </div>
      <div id="surpriseResults"></div>
    </div>
  </div>
</div>

<script>
function doSurprise() {
    const budget = document.getElementById('budget').value;
    const type = document.querySelector('input[name="type"]:checked').value;
    const spice = document.getElementById('spice').value;
    loadSurprise(budget, type, spice);
}
</script>

<?php require_once 'includes/footer.php'; ?>
