// ============================================================
// Interlinked Marketplace — Main JS
// Student Project — ITECA-12 Web Development
// ============================================================

console.log('main.js loaded');

document.addEventListener('DOMContentLoaded', function () {

  // --- debugging ---
  console.log('page loaded');
  console.log('rootPath:', typeof rootPath !== 'undefined' ? rootPath : 'not set');

  // ============================================================
  // WISHLIST TOGGLE
  // Uses fetch API to send product ID to api/wishlist.php
  // ============================================================
  document.querySelectorAll('.wishlist-btn').forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      const productId = this.dataset.productId;
      if (!productId) return;

      const rp = (typeof rootPath !== 'undefined') ? rootPath : '';
      const token = (typeof csrfToken !== 'undefined') ? csrfToken : '';

      fetch(rp + 'api/wishlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId, csrf_token: token })
      })
      .then(r => r.json())
      .then(data => {
        console.log('wishlist response:', data);
        if (data.status === 'added') {
          this.classList.add('active');
          this.style.setProperty('border-color', '#f43f5e', 'important');
          this.style.setProperty('color', '#f43f5e', 'important');
          this.innerHTML = '<i data-feather="heart" style="width:18px"></i> In Wishlist';
          if (typeof feather !== 'undefined') feather.replace();
          showWishlistToast('Added to wishlist ♥', 'success');
        } else if (data.status === 'removed') {
          this.classList.remove('active');
          this.style.removeProperty('border-color');
          this.style.removeProperty('color');
          this.innerHTML = '<i data-feather="heart" style="width:18px"></i> Add to Wishlist';
          if (typeof feather !== 'undefined') feather.replace();
          showWishlistToast('Removed from wishlist', 'info');
        } else if (data.status === 'login_required') {
          window.location.href = rp + 'auth/login.php';
        }
      })
      .catch(err => console.log('wishlist error:', err));
    });
  });

  // ============================================================
  // AUTO-DISMISS ALERTS
  // Fades out alert messages after 4 seconds
  // ============================================================
  document.querySelectorAll('.auto-dismiss').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity .5s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 500);
    }, 4000);
  });

  // ============================================================
  // STATUS BADGE COLOURING
  // Adds Bootstrap badge colour classes based on text content
  // ============================================================
  document.querySelectorAll('.status-badge').forEach(el => {
    const s = el.textContent.trim().toLowerCase();
    const map = {
      pending: 'warning', approved: 'success', rejected: 'danger',
      sold: 'secondary', paid: 'success', shipped: 'info',
      delivered: 'success', cancelled: 'danger', active: 'success',
      inactive: 'secondary', new: 'primary', unpaid: 'warning', refunded: 'info'
    };
    el.classList.add('badge', 'bg-' + (map[s] || 'secondary'));
  });

  // ===========================================================
  // WISHLIST TOAST NOTIFICATION
  // ===========================================================
  function showWishlistToast(message, type) {
    var toast = document.createElement('div');
    toast.textContent = message;
    toast.style.cssText = [
      'position:fixed',
      'bottom:30px',
      'left:50%',
      'transform:translateX(-50%)',
      'padding:14px 28px',
      'border-radius:12px',
      'font-weight:700',
      'font-size:.9rem',
      'z-index:9999',
      'pointer-events:none',
      'transition:all .3s ease',
      'opacity:0',
      'box-shadow:0 10px 40px rgba(0,0,0,0.4)',
      type === 'success' ? 'background:#10b981;color:#fff' : 'background:#334155;color:#fff'
    ].join(';');
    document.body.appendChild(toast);
    // animate in
    requestAnimationFrame(function () {
      toast.style.opacity = '1';
      toast.style.transform = 'translateX(-50%) translateY(0)';
    });
    // remove after 2.5s
    setTimeout(function () {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(-50%) translateY(20px)';
      setTimeout(function () { toast.remove(); }, 300);
    }, 2500);
  }

}); // end DOMContentLoaded


// ============================================================
// 3D HERO VIEWER (Three.js)
// Renders a rotating 3D torus knot in the hero section
// ============================================================
function init3DProductViewer(containerId, color) {
  const container = document.getElementById(containerId);
  if (!container || typeof THREE === 'undefined') {
    console.log('3D viewer: container or THREE.js not found');
    return;
  }

  container.innerHTML = '';

  const w = container.clientWidth || 400;
  const h = container.clientHeight || 320;

  const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
  renderer.setSize(w, h);
  renderer.setPixelRatio(window.devicePixelRatio);
  container.appendChild(renderer.domElement);

  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(45, w / h, 0.1, 100);
  camera.position.set(0, 0.5, 5);

  scene.add(new THREE.AmbientLight(0xffffff, 1));

  const pointLight = new THREE.PointLight(0x39ff88, 3, 100);
  pointLight.position.set(2, 3, 4);
  scene.add(pointLight);

  const geometry = new THREE.TorusKnotGeometry(1.1, 0.35, 120, 16);
  const material = new THREE.MeshPhysicalMaterial({
    color: color || 0x39ff88,
    metalness: 0.9,
    roughness: 0.15,
    transmission: 0.2,
    clearcoat: 1
  });

  const mesh = new THREE.Mesh(geometry, material);
  scene.add(mesh);

  const ring = new THREE.Mesh(
    new THREE.TorusGeometry(2.2, 0.03, 16, 100),
    new THREE.MeshBasicMaterial({ color: 0x39ff88 })
  );
  ring.rotation.x = Math.PI / 2;
  scene.add(ring);

  function animate() {
    requestAnimationFrame(animate);
    mesh.rotation.x += 0.004;
    mesh.rotation.y += 0.01;
    ring.rotation.z += 0.003;
    renderer.render(scene, camera);
  }

  animate();

  window.addEventListener('resize', () => {
    const width = container.clientWidth || 400;
    const height = container.clientHeight || 320;
    renderer.setSize(width, height);
    camera.aspect = width / height;
    camera.updateProjectionMatrix();
  });
}


// ============================================================
// QR CODE PAYMENT RENDERER
// Generates QR code for payment page
// ============================================================
function initQR3D(opts, containerId) {
  const container = document.getElementById(containerId);
  if (!container || typeof QRCode === 'undefined') return;

  const payload = 'Interlinked|' + opts.ref + '|R' + parseFloat(opts.amount).toFixed(2);

  new QRCode(container, {
    text: payload,
    width: 220,
    height: 220,
    colorDark: '#1a1a2e',
    colorLight: '#ffffff',
    correctLevel: QRCode.CorrectLevel.H
  });
}


// ============================================================
// CUSTOM DROPDOWN (Chrome on Windows fix)
// Replaces native <select> with a custom styled dropdown
// because Chrome renders dark select options unreadably
// ============================================================
(function () {

  function buildCustomSelect(originalSelect) {
    if (originalSelect.dataset.customized === 'true') return;
    originalSelect.dataset.customized = 'true';

    var wrapper = document.createElement('div');
    wrapper.className = 'custom-select-wrapper';

    var selectedIndex = originalSelect.selectedIndex || 0;
    var selectedOption = originalSelect.options[selectedIndex] || originalSelect.options[0];
    var selectedValue = selectedOption ? selectedOption.value : '';
    var selectedText = selectedOption ? selectedOption.text : '';

    // hidden input to store the actual value for form submission
    var hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = originalSelect.name || '';
    hiddenInput.value = selectedValue;

    // keep the onchange handler from the original select
    var onchangeHandler = originalSelect.getAttribute('onchange');
    if (onchangeHandler) {
      hiddenInput.dataset.onchange = onchangeHandler;
    }

    // the clickable display button
    var display = document.createElement('div');
    display.className = 'custom-select-display';
    display.setAttribute('tabindex', '0');

    var labelSpan = document.createElement('span');
    labelSpan.className = 'custom-select-label';
    labelSpan.textContent = selectedText;

    var arrow = document.createElement('span');
    arrow.className = 'custom-select-arrow';
    arrow.innerHTML = '&#9662;';

    display.appendChild(labelSpan);
    display.appendChild(arrow);

    // dropdown menu
    var menu = document.createElement('div');
    menu.className = 'custom-select-menu';

    for (var i = 0; i < originalSelect.options.length; i++) {
      var opt = originalSelect.options[i];
      var item = document.createElement('div');
      item.className = 'custom-select-option';
      item.setAttribute('data-value', opt.value);
      item.textContent = opt.text;

      if (i === selectedIndex) {
        item.classList.add('is-selected');
      }

      (function (optionEl, optionValue, optionText) {
        item.addEventListener('click', function (e) {
          e.stopPropagation();
          hiddenInput.value = optionValue;
          labelSpan.textContent = optionText;
          var allOpts = menu.querySelectorAll('.custom-select-option');
          for (var j = 0; j < allOpts.length; j++) {
            allOpts[j].classList.remove('is-selected');
          }
          optionEl.classList.add('is-selected');
          closeMenu();
          // fire onchange if there was one
          if (hiddenInput.dataset.onchange) {
            try {
              (new Function(hiddenInput.dataset.onchange)).call(hiddenInput);
            } catch (err) {
              console.warn('custom select onchange error:', err);
            }
          }
          hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        });
      })(item, opt.value, opt.text);

      menu.appendChild(item);
    }

    function openMenu() {
      menu.style.display = 'block';
      wrapper.classList.add('is-open');
    }
    function closeMenu() {
      menu.style.display = 'none';
      wrapper.classList.remove('is-open');
    }

    display.addEventListener('click', function (e) {
      e.stopPropagation();
      if (menu.style.display === 'block') closeMenu(); else openMenu();
    });

    display.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); display.click(); }
      if (e.key === 'Escape') closeMenu();
    });

    document.addEventListener('click', function (e) {
      if (!wrapper.contains(e.target)) closeMenu();
    });

    // put it all together — hide original, insert custom
    originalSelect.style.display = 'none';
    originalSelect.removeAttribute('name');
    originalSelect.parentNode.insertBefore(wrapper, originalSelect);
    wrapper.appendChild(hiddenInput);
    wrapper.appendChild(display);
    wrapper.appendChild(menu);
    wrapper.appendChild(originalSelect);
  }

  var selects = document.querySelectorAll('select.form-select');
  console.log('[CustomSelect] Found ' + selects.length + ' select elements to convert');
  for (var s = 0; s < selects.length; s++) {
    buildCustomSelect(selects[s]);
  }

})();
