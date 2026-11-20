(function () {
	var box = document.querySelector('.gt-booking');
	if (!box) return;
	var total = box.querySelector('.gt-booking__total');
	var precio = parseFloat(total ? total.getAttribute('data-precio') : 0) || 0;
	var fmt = function (n) { return '$' + Math.round(n).toLocaleString('es-CO') + ' COP'; };
	var dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

	if (box.getAttribute('data-tipo') === 'alojamiento') {
		var inp = box.querySelector('[name=gt_llegada]');
		var out = box.querySelector('[name=gt_salida]');
		var update = function () {
			if (inp.value) {
				var d = new Date(inp.value + 'T12:00:00');
				d.setDate(d.getDate() + 1);
				var min = d.toISOString().slice(0, 10);
				out.min = min;
				if (out.value && out.value < min) out.value = min;
			}
			if (inp.value && out.value) {
				var n = Math.round((new Date(out.value + 'T12:00:00') - new Date(inp.value + 'T12:00:00')) / 86400000);
				if (n > 0) {
					total.textContent = n + (n === 1 ? ' noche' : ' noches') + ' · Total: ' + fmt(n * precio);
					return;
				}
			}
			total.textContent = '';
		};
		inp.addEventListener('change', update);
		out.addEventListener('change', update);
	} else {
		var f = box.querySelector('[name=gt_fecha]');
		var q = document.querySelector('form.cart input.qty');
		var permitidos = (box.getAttribute('data-dias') || '').split(',').filter(function (x) { return x !== ''; }).map(Number);
		var update2 = function () {
			if (f.value && permitidos.length) {
				var dow = new Date(f.value + 'T12:00:00').getDay();
				if (permitidos.indexOf(dow) === -1) {
					f.setCustomValidity('Este tour no sale los ' + dias[dow] + '. Elige otra fecha.');
					f.reportValidity();
					total.textContent = '';
					return;
				}
			}
			f.setCustomValidity('');
			var p = q ? parseInt(q.value, 10) || 1 : 1;
			total.textContent = f.value ? p + (p === 1 ? ' persona' : ' personas') + ' · Total: ' + fmt(p * precio) : '';
		};
		f.addEventListener('change', update2);
		if (q) q.addEventListener('input', update2);
	}
})();
