/**
 * rumi.js - Master Token Encryption for TeleProxy Admin
 *
 * Uses IndexedDB for secure storage and AES-256-GCM for encryption.
 * The master token is encrypted before being stored and decrypted only when needed.
 */

const Rumi = (function() {
	const DB_NAME = 'teleproxy-admin';
	const DB_VERSION = 1;
	const STORE_NAME = 'tokens';
	const ALGORITHM = 'AES-GCM';
	const KEY_LENGTH = 256;

	let db = null;

	// Initialize IndexedDB
	function initDB() {
		return new Promise((resolve, reject) => {
			if (db) {
				resolve(db);
				return;
			}

			const request = indexedDB.open(DB_NAME, DB_VERSION);

			request.onerror = () => reject(request.error);
			request.onsuccess = () => {
				db = request.result;
				resolve(db);
			};

			request.onupgradeneeded = (event) => {
				const database = event.target.result;
				if (!database.objectStoreNames.contains(STORE_NAME)) {
					database.createObjectStore(STORE_NAME, { keyPath: 'id' });
				}
			};
		});
	}

	// Generate a random encryption key
	function generateKey() {
		return window.crypto.subtle.generateKey(
			{
				name: ALGORITHM,
				length: KEY_LENGTH,
			},
			true,
			['encrypt', 'decrypt']
		);
	}

	// Export key to raw format for storage
	async function exportKey(key) {
		return await window.crypto.subtle.exportKey('raw', key);
	}

	// Import key from raw format
	async function importKey(keyData) {
		return await window.crypto.subtle.importKey(
			'raw',
			keyData,
			{
				name: ALGORITHM,
				length: KEY_LENGTH,
			},
			true,
			['encrypt', 'decrypt']
		);
	}

	// Encrypt data
	async function encrypt(data, key) {
		const iv = window.crypto.getRandomValues(new Uint8Array(12));
		const encoded = new TextEncoder().encode(data);

		const encrypted = await window.crypto.subtle.encrypt(
			{
				name: ALGORITHM,
				iv: iv,
			},
			key,
			encoded
		);

		return {
			iv: Array.from(iv),
			data: Array.from(new Uint8Array(encrypted)),
		};
	}

	// Decrypt data
	async function decrypt(encryptedData, key) {
		const iv = new Uint8Array(encryptedData.iv);
		const data = new Uint8Array(encryptedData.data);

		const decrypted = await window.crypto.subtle.decrypt(
			{
				name: ALGORITHM,
				iv: iv,
			},
			key,
			data
		);

		return new TextDecoder().decode(decrypted);
	}

	// Store encrypted token
	async function storeToken(token) {
		try {
			const database = await initDB();
			let keyData = await getKeyFromStorage();

			if (!keyData) {
				const key = await generateKey();
				keyData = await exportKey(key);
				await saveKeyToStorage(keyData);
			}

			const key = await importKey(keyData);
			const encrypted = await encrypt(token, key);

			const transaction = database.transaction([STORE_NAME], 'readwrite');
			const store = transaction.objectStore(STORE_NAME);

			await store.put({
				id: 'master_token',
				encrypted: encrypted,
				createdAt: Date.now(),
			});

			return true;
		} catch (error) {
			console.error('Error storing token:', error);
			return false;
		}
	}

	// Retrieve and decrypt token
	async function getToken() {
		try {
			const database = await initDB();
			const keyData = await getKeyFromStorage();

			if (!keyData) {
				return null;
			}

			const transaction = database.transaction([STORE_NAME], 'readonly');
			const store = transaction.objectStore(STORE_NAME);
			const request = store.get('master_token');

			return new Promise((resolve, reject) => {
				request.onsuccess = async () => {
					if (!request.result) {
						resolve(null);
						return;
					}

					try {
						const key = await importKey(keyData);
						const token = await decrypt(request.result.encrypted, key);
						resolve(token);
					} catch (error) {
						console.error('Error decrypting token:', error);
						resolve(null);
					}
				};
				request.onerror = () => reject(request.error);
			});
		} catch (error) {
			console.error('Error retrieving token:', error);
			return null;
		}
	}

	// Delete token
	async function deleteToken() {
		try {
			const database = await initDB();
			const transaction = database.transaction([STORE_NAME], 'readwrite');
			const store = transaction.objectStore(STORE_NAME);

			await store.delete('master_token');
			return true;
		} catch (error) {
			console.error('Error deleting token:', error);
			return false;
		}
	}

	// Helper: Get key from localStorage
	async function getKeyFromStorage() {
		const keyData = localStorage.getItem('teleproxy_admin_key');
		if (!keyData) {
			return null;
		}
		return new Uint8Array(JSON.parse(keyData)).buffer;
	}

	// Helper: Save key to localStorage
	async function saveKeyToStorage(keyData) {
		const keyArray = Array.from(new Uint8Array(keyData));
		localStorage.setItem('teleproxy_admin_key', JSON.stringify(keyArray));
	}

	// Public API
	return {
		storeToken,
		getToken,
		deleteToken,
	};
})();

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
	module.exports = Rumi;
}
