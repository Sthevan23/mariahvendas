(() => {
  const header = document.querySelector('[data-header]');
  const menuBtn = document.querySelector('[data-menu-toggle]');
  const mobileNav = document.querySelector('[data-mobile-nav]');
  const searchBtn = document.querySelector('[data-search-toggle]');
  const searchPanel = document.querySelector('[data-search-panel]');
  const bagIcon = document.querySelector('[data-bag-icon]');

  const onScroll = () => {
    if (!header) return;
    header.classList.toggle('is-scrolled', window.scrollY > 8);
  };
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });

  menuBtn?.addEventListener('click', () => {
    mobileNav?.classList.toggle('open');
    document.body.style.overflow = mobileNav?.classList.contains('open') ? 'hidden' : '';
  });

  searchBtn?.addEventListener('click', () => {
    searchPanel?.classList.toggle('open');
    searchPanel?.querySelector('input')?.focus();
  });

  document.querySelectorAll('.reveal').forEach((el) => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      el.classList.add('visible');
      return;
    }
    const io = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.05, rootMargin: '40px 0px' });
    io.observe(el);
    // Se já está na viewport no load, marca na hora
    requestAnimationFrame(() => {
      const rect = el.getBoundingClientRect();
      if (rect.top < window.innerHeight && rect.bottom > 0) {
        el.classList.add('visible');
      }
    });
  });

  const flash = document.querySelector('[data-flash]');
  if (flash) {
    setTimeout(() => flash.remove(), 4200);
  }

  document.querySelectorAll('[data-ajax-cart]').forEach((form) => {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const buyNowInput = form.querySelector('[data-buy-now]');
      const isBuyNow = e.submitter?.dataset?.action === 'buy';
      if (buyNowInput) buyNowInput.value = isBuyNow ? '1' : '0';

      const variantId = form.querySelector('[data-variant-id]')?.value;
      if (!variantId) {
        showToast('Selecione tamanho e cor disponíveis.', 'error');
        return;
      }

      const fd = new FormData(form);
      const res = await fetch(form.action, {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const data = await res.json();
      showToast(data.message || 'Atualizado', data.ok ? 'success' : 'error');
      if (data.ok) {
        const badge = document.querySelector('[data-cart-count]');
        if (badge && typeof data.count !== 'undefined') {
          badge.textContent = data.count;
          badge.hidden = data.count < 1;
        }
        bagIcon?.classList.remove('bag-pulse');
        void bagIcon?.offsetWidth;
        bagIcon?.classList.add('bag-pulse');
        if (data.redirect) {
          window.location.href = data.redirect;
        }
      }
    });
  });

  const galleryMain = document.querySelector('[data-gallery-main]');
  galleryMain?.addEventListener('click', () => galleryMain.classList.toggle('zoomed'));
  document.querySelectorAll('[data-thumb]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const src = btn.getAttribute('data-src');
      const img = galleryMain?.querySelector('img');
      if (img && src) img.src = src;
      document.querySelectorAll('[data-thumb]').forEach((b) => b.classList.remove('active'));
      btn.classList.add('active');
    });
  });

  const variantMap = window.MANDALLA_VARIANTS || [];
  const sizeInputs = document.querySelectorAll('[data-size]');
  const colorInputs = document.querySelectorAll('[data-color]');
  const variantInput = document.querySelector('[data-variant-id]');
  const stockLabel = document.querySelector('[data-stock-label]');
  const addBtns = document.querySelectorAll('[data-add-btn]');

  const syncVariant = () => {
    const size = document.querySelector('[data-size].active')?.dataset.size;
    const color = document.querySelector('[data-color].active')?.dataset.color;
    const match = variantMap.find((v) => v.size === size && v.color === color);
    if (variantInput) variantInput.value = match && match.stock > 0 ? match.id : '';
    if (stockLabel) {
      stockLabel.textContent = match
        ? (match.stock > 0 ? `Disponível (${match.stock})` : 'Indisponível nesta combinação')
        : 'Selecione tamanho e cor';
    }
    const enabled = !!(match && match.stock > 0);
    addBtns.forEach((btn) => { btn.disabled = !enabled; });
  };

  sizeInputs.forEach((el) => el.addEventListener('click', () => {
    sizeInputs.forEach((s) => s.classList.remove('active'));
    el.classList.add('active');
    syncVariant();
  }));
  colorInputs.forEach((el) => el.addEventListener('click', () => {
    colorInputs.forEach((c) => c.classList.remove('active'));
    el.classList.add('active');
    syncVariant();
  }));
  syncVariant();

  function showToast(message, type) {
    document.querySelector('[data-flash]')?.remove();
    const el = document.createElement('div');
    el.className = `flash ${type}`;
    el.setAttribute('data-flash', '');
    el.textContent = message;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 4000);
  }
})();
