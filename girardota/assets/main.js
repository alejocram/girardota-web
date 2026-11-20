(function () {
	// Menú móvil
	var burger = document.querySelector('.gt-burger');
	var nav = document.getElementById('gt-nav');
	if (burger && nav) {
		burger.addEventListener('click', function () {
			var open = nav.classList.toggle('is-open');
			burger.setAttribute('aria-expanded', open ? 'true' : 'false');
			burger.setAttribute('aria-label', open ? 'Cerrar menú' : 'Abrir menú');
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && nav.classList.contains('is-open')) {
				nav.classList.remove('is-open');
				burger.setAttribute('aria-expanded', 'false');
				burger.focus();
			}
		});
	}

	// Sombra del encabezado al hacer scroll
	var header = document.getElementById('gt-header');
	if (header) {
		var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 10); };
		window.addEventListener('scroll', onScroll, { passive: true });
		onScroll();
	}

	// Filtros y búsqueda de alojamientos/tours
	document.querySelectorAll('[data-gt-filter]').forEach(function (wrap) {
		var cards = wrap.querySelectorAll('.gt-card');
		var search = wrap.querySelector('.gt-search');
		var empty = wrap.querySelector('.gt-empty--filter');
		var cat = '';
		var norm = function (s) { return (s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); };
		var apply = function () {
			var q = norm(search ? search.value : '');
			var shown = 0;
			cards.forEach(function (c) {
				var okCat = !cat || (' ' + c.getAttribute('data-cats') + ' ').indexOf(' ' + cat + ' ') !== -1;
				var okQ = !q || norm(c.textContent).indexOf(q) !== -1;
				c.hidden = !(okCat && okQ);
				if (!c.hidden) shown++;
			});
			if (empty) empty.hidden = shown > 0;
		};
		wrap.querySelectorAll('.gt-filter').forEach(function (b) {
			b.addEventListener('click', function () {
				wrap.querySelectorAll('.gt-filter').forEach(function (x) { x.classList.remove('is-active'); });
				b.classList.add('is-active');
				cat = b.getAttribute('data-cat');
				apply();
			});
		});
		if (search) search.addEventListener('input', apply);
	});
})();
