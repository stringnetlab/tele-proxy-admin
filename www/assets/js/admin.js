// TeleProxy Admin - Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
	// Initialize Notiflix
	Notiflix.Notify.init({
		width: '320px',
		position: 'right-top',
		distance: '10px',
		opacity: 1,
		borderRadius: '4px',
		fontSize: '14px',
		timeout: 3000,
	});

	// Master token reveal toggle
	const revealButton = document.getElementById('reveal-master-token');
	const masterTokenForm = document.getElementById('master-token-form');

	if (revealButton && masterTokenForm) {
		revealButton.addEventListener('click', function() {
			masterTokenForm.classList.toggle('hidden');
		});
	}

	// Initialize Tabulator for clients table
	const clientsTable = document.getElementById('clients-table');
	if (clientsTable && typeof Tabulator !== 'undefined') {
		new Tabulator('#clients-table', {
			layout: 'fitColumns',
			responsiveLayout: 'hide',
			placeholder: 'No hay clientes disponibles',
			columns: [
				{ title: 'Crypt ID', field: 'crypt_id', width: 150 },
				{ title: 'Nombre', field: 'name', minWidth: 150 },
				{ title: 'Dominio', field: 'domain', minWidth: 150 },
				{ title: 'Rate Limit', field: 'rate_limit', minWidth: 120 },
				{ title: 'Acciones', field: 'actions', minWidth: 200, sortable: false },
			],
		});
	}

	// Initialize Tabulator for admins table
	const adminsTable = document.getElementById('admins-table');
	if (adminsTable && typeof Tabulator !== 'undefined') {
		new Tabulator('#admins-table', {
			layout: 'fitColumns',
			responsiveLayout: 'hide',
			placeholder: 'No hay administradores disponibles',
			columns: [
				{ title: 'Email', field: 'email', minWidth: 200 },
				{ title: 'Rol', field: 'role', width: 100 },
				{ title: 'Estado', field: 'active', width: 100 },
				{ title: 'Creado', field: 'created_at', width: 150 },
				{ title: 'Acciones', field: 'actions', minWidth: 200, sortable: false },
			],
		});
	}

	// Initialize Naja for AJAX
	if (typeof Naja !== 'undefined') {
		const naja = new Naja();
		naja.initialize();
	}

	// Confirmation dialogs for dangerous actions
	const deleteLinks = document.querySelectorAll('a[href*="delete"]');
	deleteLinks.forEach(function(link) {
		link.addEventListener('click', function(e) {
			if (!confirm('¿Estás seguro de que quieres eliminar este elemento?')) {
				e.preventDefault();
			}
		});
	});

	const rotateLinks = document.querySelectorAll('a[href*="rotate"]');
	rotateLinks.forEach(function(link) {
		link.addEventListener('click', function(e) {
			if (!confirm('¿Estás seguro de que quieres rotar este elemento? Esta acción no se puede deshacer.')) {
				e.preventDefault();
			}
		});
	});
});
