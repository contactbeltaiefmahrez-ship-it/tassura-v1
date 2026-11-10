'use strict';

(() => {
  document.documentElement.classList.add('has-js');
  const one = (selector, root = document) => root.querySelector(selector);
  const all = (selector, root = document) => Array.from(root.querySelectorAll(selector));
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const mobile = window.matchMedia('(max-width: 800px)');

  // A real navigation menu, usable without mouse or hover.
  const menuToggle = one('[data-menu-toggle]');
  const menu = one('[data-menu]');
  if (menu && menuToggle) {
    const setMenu = (open) => {
      menu.classList.toggle('is-open', open);
      menuToggle.setAttribute('aria-expanded', String(open));
      menuToggle.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu');
    };
    menuToggle.addEventListener('click', () => setMenu(!menu.classList.contains('is-open')));
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape') setMenu(false);
    });
    document.addEventListener('click', event => {
      if (!menu.contains(event.target) && !menuToggle.contains(event.target)) setMenu(false);
    });
    all('a', menu).forEach(link => link.addEventListener('click', () => setMenu(false)));
    mobile.addEventListener('change', () => setMenu(false));
  }

  // One entrance, six real campaign films. A collection choice changes footage,
  // never the product inventory or the destination returned by PHP.
  const films = all('[data-home-film]');
  const choices = all('[data-home-choice]');
  const enter = one('[data-home-enter]');
  const videoToggle = one('[data-video-toggle]');
  let currentFilm = null;
  let manuallyPaused = reducedMotion.matches;
  const filmKeys = new Map();
  films.forEach(film => {
    const key = film.dataset.homeFilm;
    if (!filmKeys.has(key)) filmKeys.set(key, []);
    filmKeys.get(key).push(film);
    film.muted = true;
    film.playsInline = true;
    film.preload = 'none';
    film.loop = false;
    if (film.dataset.objectPosition) film.style.objectPosition = film.dataset.objectPosition;
  });
  const updateVideoControl = () => {
    if (!videoToggle) return;
    const paused = manuallyPaused || !currentFilm || currentFilm.paused;
    videoToggle.setAttribute('aria-pressed', String(paused));
    videoToggle.setAttribute('aria-label', paused ? 'Lire le film' : 'Mettre le film en pause');
    const label = one('[data-video-toggle-label]', videoToggle);
    if (label) label.textContent = paused ? 'Lire le film' : 'Pause';
  };
  const playFilm = (film) => {
    if (!film || manuallyPaused || document.hidden) return;
    film.preload = 'auto';
    const pending = film.play();
    if (pending && typeof pending.then === 'function') {
      pending.then(updateVideoControl).catch(updateVideoControl);
    }
  };
  const switchFilm = (film) => {
    if (!film) return;
    films.forEach(item => {
      item.classList.toggle('is-active', item === film);
      if (item !== film) item.pause();
    });
    currentFilm = film;
    playFilm(film);
    updateVideoControl();
  };
  const choose = (choice) => {
    const key = choice.dataset.homeChoice;
    choices.forEach(item => {
      const selected = item === choice;
      item.classList.toggle('is-active', selected);
      item.setAttribute('aria-pressed', String(selected));
    });
    if (enter && choice.dataset.homeUrl) {
      enter.href = choice.dataset.homeUrl;
      const text = one('[data-home-enter-label]', enter);
      if (text && choice.dataset.homeLabel) text.textContent = choice.dataset.homeLabel;
    }
    const group = filmKeys.get(key);
    if (group && group.length && (!currentFilm || currentFilm.dataset.homeFilm !== key)) {
      switchFilm(group[0]);
    }
  };
  choices.forEach((choice, index) => {
    choice.addEventListener('click', () => choose(choice));
    choice.addEventListener('keydown', event => {
      if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
      event.preventDefault();
      let next = index;
      if (event.key === 'ArrowLeft') next = (index - 1 + choices.length) % choices.length;
      if (event.key === 'ArrowRight') next = (index + 1) % choices.length;
      if (event.key === 'Home') next = 0;
      if (event.key === 'End') next = choices.length - 1;
      choices[next].focus();
      choose(choices[next]);
    });
  });
  films.forEach(film => {
    film.addEventListener('ended', () => {
      if (film !== currentFilm || manuallyPaused) return;
      const group = filmKeys.get(film.dataset.homeFilm) || [film];
      const next = group[(group.indexOf(film) + 1) % group.length];
      next.currentTime = 0;
      switchFilm(next);
    });
    film.addEventListener('pause', updateVideoControl);
    film.addEventListener('playing', updateVideoControl);
  });
  if (choices.length) choose(choices.find(item => item.classList.contains('is-active')) || choices[0]);
  else if (films.length) switchFilm(films[0]);
  if (videoToggle) videoToggle.addEventListener('click', () => {
    if (!currentFilm) return;
    manuallyPaused = !currentFilm.paused;
    if (manuallyPaused) currentFilm.pause();
    else playFilm(currentFilm);
    updateVideoControl();
  });
  document.addEventListener('visibilitychange', () => {
    if (!currentFilm) return;
    if (document.hidden) currentFilm.pause();
    else playFilm(currentFilm);
  });
  reducedMotion.addEventListener('change', event => {
    if (event.matches && currentFilm) {
      manuallyPaused = true;
      currentFilm.pause();
      updateVideoControl();
    }
  });

  // Native scroll-snap preserves touch scrolling, keyboard access and full image
  // links. Controls move the gallery, rather than swapping a decorative overlay.
  all('[data-card-gallery]').forEach(gallery => {
    const visual = gallery.closest('.card-visual') || gallery.parentElement;
    const slides = all('.card-slide', gallery);
    const previous = one('[data-card-prev]', visual);
    const next = one('[data-card-next]', visual);
    const dots = all('.card-dots i,.card-dots span,[data-card-dot]', visual);
    const progress = one('[data-card-progress]', visual);
    const move = direction => {
      const count = slides.length;
      if (!count) return;
      const current = Math.round(gallery.scrollLeft / Math.max(1, gallery.clientWidth));
      const index = (current + direction + count) % count;
      gallery.scrollTo({left: index * gallery.clientWidth, behavior: reducedMotion.matches ? 'instant' : 'smooth'});
    };
    if (previous) previous.addEventListener('click', event => { event.preventDefault(); move(-1); });
    if (next) next.addEventListener('click', event => { event.preventDefault(); move(1); });
    let frame = 0;
    const syncDots = () => {
      const index = Math.round(gallery.scrollLeft / Math.max(1, gallery.clientWidth));
      dots.forEach((dot, number) => dot.classList.toggle('is-active', number === index));
      if (progress && slides.length) {
        progress.style.width = `${100 / slides.length}%`;
        progress.style.transform = `translateX(${index * 100}%)`;
      }
      frame = 0;
    };
    gallery.addEventListener('scroll', () => {
      if (!frame) frame = requestAnimationFrame(syncDots);
    }, {passive: true});
    gallery.addEventListener('keydown', event => {
      if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
        event.preventDefault();
        move(event.key === 'ArrowRight' ? 1 : -1);
      }
    });
    let startX = 0;
    let startY = 0;
    let dragged = false;
    gallery.addEventListener('pointerdown', event => {
      startX = event.clientX;
      startY = event.clientY;
      dragged = false;
    }, {passive: true});
    gallery.addEventListener('pointermove', event => {
      if (Math.abs(event.clientX - startX) > 12 && Math.abs(event.clientX - startX) > Math.abs(event.clientY - startY)) dragged = true;
    }, {passive: true});
    gallery.addEventListener('click', event => {
      if (dragged) {
        event.preventDefault();
        dragged = false;
      }
    }, true);
    syncDots();
  });

  // The filter form is submitted normally to PHP: results, prices, categories,
  // sizes and counts are authoritative server output, not browser simulations.
  const filterDialog = one('.filter-dialog');
  if (filterDialog) {
    all('[data-filter-open]').forEach(button => button.addEventListener('click', () => {
      if (typeof filterDialog.showModal === 'function') filterDialog.showModal();
      else filterDialog.setAttribute('open', '');
    }));
    all('[data-filter-close]', filterDialog).forEach(button => button.addEventListener('click', () => {
      if (typeof filterDialog.close === 'function') filterDialog.close();
      else filterDialog.removeAttribute('open');
    }));
    filterDialog.addEventListener('click', event => {
      if (event.target !== filterDialog) return;
      const rect = filterDialog.getBoundingClientRect();
      if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) filterDialog.close();
    });
  }
  all('[data-auto-submit]').forEach(field => field.addEventListener('change', () => {
    if (field.form) field.form.requestSubmit();
  }));

  // Only an explicit color selection changes the selected product photo.
  // Quantity, size, page scroll and gallery scroll never trigger this operation.
  const selectedImage = one('[data-variant-image]');
  const selectedLabel = one('[data-variant-label]');
  const colorLabel = one('[data-selected-color]');
  all('input[type="radio"][data-variant-src]').forEach(radio => {
    radio.addEventListener('change', () => {
      if (!radio.checked) return;
      const source = radio.dataset.variantSrc;
      const color = radio.dataset.colorName || '';
      if (selectedImage && source) {
        selectedImage.src = source;
        if (selectedImage.srcset) selectedImage.removeAttribute('srcset');
        const product = selectedImage.dataset.productName || '';
        selectedImage.alt = [product, color].filter(Boolean).join(' — ');
      }
      if (selectedImage) selectedImage.classList.add('is-color-preview');
      if (selectedLabel) selectedLabel.textContent = `Aperçu du coloris sélectionné · ${color}`;
      if (colorLabel) colorLabel.textContent = color;
    });
  });

  // Variant availability is supplied by PHP. These controls mirror that stock;
  // the server still validates stock and price transactionally on every order.
  const purchaseForm = one('.purchase-form');
  if (purchaseForm) {
    const colorRadios = all('input[name="color_id"]', purchaseForm);
    const sizeRadios = all('input[name="size_id"]', purchaseForm);
    const quantity = one('input[name="quantity"]', purchaseForm);
    const maximumQuantity = quantity ? Math.min(99, Math.max(1, Number(quantity.max) || 99)) : 99;
    const addButton = one('[data-add-to-cart]', purchaseForm);
    const availability = one('[data-availability]', purchaseForm);
    const stockNodes = all('[data-stock-map] [data-color][data-size][data-stock],span[data-stock-map][data-color][data-size][data-stock]');
    const stocks = new Map(stockNodes.map(node => [`${node.dataset.color}:${node.dataset.size}`, Math.max(0, Number(node.dataset.stock) || 0)]));
    const syncStock = (selectionChanged = false) => {
      if (!stocks.size) return;
      const color = colorRadios.find(radio => radio.checked);
      sizeRadios.forEach(radio => {
        const stock = color ? stocks.get(`${color.value}:${radio.value}`) || 0 : 0;
        radio.disabled = stock < 1;
        if (radio.disabled) radio.checked = false;
        const label = radio.closest('.size-option');
        if (label) label.title = stock > 0 ? `Disponible : ${stock}` : 'Indisponible dans ce coloris';
      });
      const size = sizeRadios.find(radio => radio.checked);
      const stock = color && size ? stocks.get(`${color.value}:${size.value}`) || 0 : 0;
      if (quantity) {
        quantity.max = String(stock > 0 ? Math.min(stock, maximumQuantity) : maximumQuantity);
        if (selectionChanged && stock > 0 && Number(quantity.value) > stock) quantity.value = String(Math.min(stock, maximumQuantity));
      }
      const count = quantity ? Number(quantity.value) : 1;
      const priceReady = !addButton || addButton.dataset.priceReady === '1';
      const valid = priceReady && color && size && stock > 0 && Number.isInteger(count) && count >= 1 && count <= stock && count <= maximumQuantity;
      if (addButton) addButton.disabled = !valid;
      if (availability) {
        if (!priceReady) availability.textContent = 'Le prix de cette pièce sera annoncé prochainement.';
        else if (!color) availability.textContent = 'Choisissez un coloris.';
        else if (!size && sizeRadios.some(radio => !radio.disabled)) availability.textContent = 'Choisissez votre taille.';
        else if (!size) availability.textContent = 'Ce coloris est actuellement indisponible.';
        else if (count > stock) availability.textContent = `Quantité disponible dans cette taille : ${stock}.`;
        else if (!Number.isInteger(count) || count < 1) availability.textContent = 'Indiquez une quantité entière valide.';
        else availability.textContent = stock < 4 ? `Dernières pièces : ${stock} disponible${stock > 1 ? 's' : ''}.` : 'Disponible dans votre sélection.';
      }
    };
    colorRadios.forEach(radio => radio.addEventListener('change', () => syncStock(true)));
    sizeRadios.forEach(radio => radio.addEventListener('change', () => syncStock(true)));
    if (quantity) quantity.addEventListener('input', () => syncStock(false));
    syncStock(false);
  }

  // Accessible native quantity input remains editable when JavaScript is off.
  all('.quantity-control').forEach(control => {
    const input = one('input[type="number"]', control);
    if (!input) return;
    const adjust = direction => {
      if (input.disabled) return;
      const minimum = Number(input.min || 1);
      const maximum = Number(input.max || 99);
      const step = Number(input.step || 1);
      const value = Number(input.value || minimum);
      input.value = String(Math.min(maximum, Math.max(minimum, value + direction * step)));
      input.dispatchEvent(new Event('input', {bubbles: true}));
      input.dispatchEvent(new Event('change', {bubbles: true}));
    };
    const minus = one('[data-qty-minus]', control);
    const plus = one('[data-qty-plus]', control);
    if (minus) minus.addEventListener('click', () => adjust(-1));
    if (plus) plus.addEventListener('click', () => adjust(1));
  });

  // The same purchase form lives in the desktop column and mobile bottom panel.
  // It is not duplicated, so color, size, CSRF field and quantity cannot drift.
  const purchasePanel = one('[data-purchase-panel]');
  if (purchasePanel) {
    purchasePanel.classList.add('is-enhanced');
    const toggle = one('[data-purchase-toggle]', purchasePanel);
    const updatePurchaseHeight = () => {
      const height = mobile.matches ? Math.ceil(purchasePanel.getBoundingClientRect().height) : 0;
      document.documentElement.style.setProperty('--purchase-height', `${height}px`);
    };
    purchasePanel.classList.add('is-expanded');
    if (toggle) {
      toggle.setAttribute('aria-expanded', 'true');
      toggle.setAttribute('aria-label', 'Réduire les options de commande');
      toggle.addEventListener('click', () => {
        const expanded = purchasePanel.classList.toggle('is-expanded');
        toggle.setAttribute('aria-expanded', String(expanded));
        toggle.setAttribute('aria-label', expanded ? 'Réduire les options de commande' : 'Afficher les options de commande');
        updatePurchaseHeight();
      });
    }
    if ('ResizeObserver' in window) new ResizeObserver(updatePurchaseHeight).observe(purchasePanel);
    window.addEventListener('resize', updatePurchaseHeight, {passive: true});
    updatePurchaseHeight();
  }

  // Native <dialog> for guides and admin confirmations, without UI libraries.
  all('[data-dialog-open]').forEach(button => button.addEventListener('click', () => {
    const id = button.dataset.dialogOpen;
    const dialog = id ? document.getElementById(id) : null;
    if (dialog && typeof dialog.showModal === 'function') dialog.showModal();
  }));
  all('[data-dialog-close]').forEach(button => button.addEventListener('click', () => {
    const dialog = button.closest('dialog');
    if (dialog) dialog.close();
  }));
  all('form[data-confirm]').forEach(form => form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm || 'Confirmer cette action ?')) event.preventDefault();
  }));

  // Plain password visibility toggles do not modify authentication behavior.
  all('[data-password-toggle]').forEach(button => button.addEventListener('click', () => {
    const target = document.getElementById(button.dataset.passwordToggle || '');
    if (!target) return;
    const visible = target.type === 'password';
    target.type = visible ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(visible));
    button.textContent = visible ? 'Masquer' : 'Afficher';
  }));
})();
