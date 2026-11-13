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

  const setMenu = (open) => {
    if (!mobileNav) return;
    mobileNav.classList.toggle('open', open);
    document.body.classList.toggle('menu-open', open);
    menuBtn?.setAttribute('aria-expanded', open ? 'true' : 'false');
    menuBtn?.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu');
    document.body.style.overflow = open ? 'hidden' : '';
  };
  menuBtn?.addEventListener('click', () => setMenu(!mobileNav?.classList.contains('open')));
  mobileNav?.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setMenu(false)));
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && mobileNav?.classList.contains('open')) setMenu(false);
  });

  searchBtn?.addEventListener('click', () => {
    searchPanel?.classList.toggle('open');
    searchPanel?.querySelector('input')?.focus();
  });

  const motionOk = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  document.querySelectorAll('.product-grid, .instagram-grid, .benefits').forEach((grid) => {
    grid.querySelectorAll('.reveal').forEach((el, i) => {
      el.style.setProperty('--reveal-delay', `${Math.min(i * 70, 420)}ms`);
    });
  });

  document.querySelectorAll('.reveal').forEach((el) => {
    if (!motionOk) {
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
    requestAnimationFrame(() => {
      const rect = el.getBoundingClientRect();
      if (rect.top < window.innerHeight && rect.bottom > 0) {
        el.classList.add('visible');
      }
    });
  });

  const heroMedia = document.querySelector('.hero-media');
  if (heroMedia && motionOk) {
    let ticking = false;
    const parallax = () => {
      ticking = false;
      const y = window.scrollY;
      const limit = window.innerHeight * 1.1;
      if (y > limit) return;
      const shift = y * 0.28;
      heroMedia.style.transform = `scale(1.04) translate3d(0, ${shift}px, 0)`;
    };
    window.addEventListener('scroll', () => {
      if (!ticking) {
        ticking = true;
        requestAnimationFrame(parallax);
      }
    }, { passive: true });
  }

  const flash = document.querySelector('[data-flash]');
  if (flash) {
    setTimeout(() => flash.remove(), 4200);
  }

  document.querySelectorAll('.fav-form').forEach((form) => {
    form.addEventListener('click', (e) => e.stopPropagation());
    form.addEventListener('submit', (e) => e.stopPropagation());
  });

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
      const submitBtns = Array.from(form.querySelectorAll('button[type="submit"], button:not([type])'));
      const prevDisabled = submitBtns.map((b) => b.disabled);
      submitBtns.forEach((b) => { b.disabled = true; b.setAttribute('aria-busy', 'true'); });

      let data;
      try {
        const res = await fetch(form.action, {
          method: 'POST',
          body: fd,
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        data = await res.json();
      } catch (err) {
        data = { ok: false, message: 'Não foi possível atualizar a sacola. Verifique sua conexão e tente novamente.' };
      } finally {
        submitBtns.forEach((b, i) => { b.disabled = prevDisabled[i]; b.removeAttribute('aria-busy'); });
      }
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
        ? (match.stock > 0 ? `Disponível (${match.stock})` : 'Esgotado nesta combinação')
        : 'Selecione tamanho e cor';
    }
    const enabled = !!(match && match.stock > 0);
    addBtns.forEach((btn) => { btn.disabled = !enabled; });
  };

  // Fotos por cor: ao escolher a bolinha, mostra as fotos daquela cor (mais as fotos gerais, sem cor).
  const thumbs = document.querySelectorAll('[data-thumb]');
  const showColorPhotos = (color) => {
    const key = (color || '').toLowerCase();
    const tagged = [...thumbs].filter((t) => t.dataset.photoColor === key);
    if (!tagged.length) return; // nenhuma foto marcada para esta cor: mantém a galeria como está
    thumbs.forEach((t) => {
      const pc = t.dataset.photoColor;
      t.hidden = !(pc === key || pc === '');
    });
    tagged[0].click();
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
    showColorPhotos(el.dataset.color);
  }));
  syncVariant();
  showColorPhotos(document.querySelector('[data-color].active')?.dataset.color);

  function showToast(message, type) {
    document.querySelector('[data-flash]')?.remove();
    const el = document.createElement('div');
    el.className = `flash ${type}`;
    el.setAttribute('data-flash', '');
    el.textContent = message;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 4000);
  }

  const filtersDisclosure = document.querySelector('[data-filters]');
  if (filtersDisclosure) {
    const desktopQuery = window.matchMedia('(min-width: 768px)');
    const openOnDesktop = () => {
      if (desktopQuery.matches) filtersDisclosure.open = true;
    };
    openOnDesktop();
    desktopQuery.addEventListener('change', openOnDesktop);
  }

  const adminSidebar = document.getElementById('admin-sidebar');
  const adminBackdrop = document.getElementById('admin-sidebar-backdrop');
  const adminToggle = document.getElementById('admin-nav-toggle');
  const closeAdminNav = () => {
    adminSidebar?.classList.remove('is-open');
    adminBackdrop?.classList.remove('is-open');
    adminToggle?.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  };
  adminToggle?.addEventListener('click', () => {
    const open = !adminSidebar?.classList.contains('is-open');
    adminSidebar?.classList.toggle('is-open', open);
    adminBackdrop?.classList.toggle('is-open', open);
    adminToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.style.overflow = open ? 'hidden' : '';
  });
  adminBackdrop?.addEventListener('click', closeAdminNav);
  adminSidebar?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
      if (window.matchMedia('(max-width: 959px)').matches) closeAdminNav();
    });
  });
})();
