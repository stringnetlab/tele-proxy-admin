// TeleProxy Admin - Main JavaScript

if (window.netteForms) {
	window.netteForms.initOnLoad();
}

document.addEventListener('DOMContentLoaded', function () {
	document.addEventListener('click', function (event) {
		const target = event.target.closest('[data-confirm]');
		if (target && !window.confirm(target.dataset.confirm)) {
			event.preventDefault();
		}
	});

	document.addEventListener('click', function (event) {
		const button = event.target.closest('[data-copy-target]');
		if (!button) {
			return;
		}

		const source = document.getElementById(button.dataset.copyTarget);
		if (!source) {
			return;
		}

		const token = source.textContent.trim();
		const done = function () {
			button.textContent = 'Copiado';
		};

		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(token).then(done, function () {
				fallbackCopy(token, done);
			});
			return;
		}

		fallbackCopy(token, done);
	});

	const tokenConfirm = document.getElementById('token-confirm');
	const tokenContinue = document.getElementById('token-continue');
	if (tokenConfirm && tokenContinue) {
		const update = function () {
			tokenContinue.classList.toggle('disabled', !tokenConfirm.checked);
			tokenContinue.setAttribute('aria-disabled', String(!tokenConfirm.checked));
		};

		tokenConfirm.addEventListener('change', update);
		tokenContinue.addEventListener('click', function (event) {
			if (!tokenConfirm.checked) {
				event.preventDefault();
			}
		});
		update();
	}
});

function fallbackCopy(text, done) {
	const area = document.createElement('textarea');
	area.value = text;
	area.setAttribute('readonly', '');
	area.style.position = 'absolute';
	area.style.left = '-9999px';
	document.body.appendChild(area);
	area.select();
	document.execCommand('copy');
	document.body.removeChild(area);
	done();
}
