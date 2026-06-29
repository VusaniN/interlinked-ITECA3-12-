<!-- FOOTER -->
<footer class="interlinked-footer mt-5">
  <div class="container">
    <div class="row gy-4">
      <div class="col-lg-4">
        <div class="fw-800 h4 mb-3" style="font-family:'Sora',sans-serif">Interlinked<span class="brand-accent">.</span></div>
        <p class="text-muted small pe-lg-5">South Africa's up and comnig marketplace. Together we are connected.</p>
        <div class="d-flex gap-2 mt-4">
          <a href="#" class="social-btn"><i data-feather="facebook" style="width:16px"></i></a>
          <a href="#" class="social-btn"><i data-feather="twitter" style="width:16px"></i></a>
          <a href="#" class="social-btn"><i data-feather="instagram" style="width:16px"></i></a>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <h6 class="footer-heading">Trading</h6>
        <ul class="footer-links">
          <li><a href="<?= url('products.php') ?>">Browse Collection</a></li>
          <li><a href="<?= url('products.php?featured=1') ?>">Listed Assets</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <h6 class="footer-heading">Sell</h6>
        <ul class="footer-links">
          <li><a href="<?= url('create_product.php') ?>">List Assets</a></li>
          <li><a href="<?= url('dashboard.php') ?>">Seller Portal</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <h6 class="footer-heading">Membership</h6>
        <ul class="footer-links">
          <li><a href="<?= url('auth/login.php') ?>">Sign In</a></li>
          <li><a href="<?= url('auth/register.php') ?>">Register</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <h6 class="footer-heading">Legal</h6>
        <ul class="footer-links">
          <li><a href="#">Privacy Policy</a></li>
          <li><a href="#">Terms of Service</a></li>
        </ul>
      </div>
    </div>
    <hr class="footer-divider">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 pb-4">
      <div class="small text-muted">&copy; <?= date('Y') ?> Interlinked Marketplace. All rights reserved.</div>
      <div class="d-flex gap-4">
        <span class="text-muted small">Connecting South Africa</span>
      </div>
    </div>
  </div>
</footer>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/feather-icons"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
<script>if (typeof feather !== 'undefined') feather.replace();</script>

<!-- CHATBOT -->
<?php require_once dirname(__FILE__) . '/chatbot.php'; ?>
<?= $extraFooter ?? '' ?>
</body>
</html>

