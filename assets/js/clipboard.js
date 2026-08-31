/**
 * Core Blueprint Profiles clipboard integration.
 *
 * Clipboard behaviour belongs to Core Blueprint Base. Profiles owns only the
 * exact code string and the local copy-button placement.
 */
(() => {
	'use strict';

	const init = () => {
		const clipboardApi = window.cbCore?.clipboard;
		if (!clipboardApi || typeof clipboardApi.enhance !== 'function') return;

		document.querySelectorAll('[data-cb-profiles-copy]').forEach((button) => {
			const text = button.dataset.cbProfilesCopyText || '';
			const label = button.dataset.cbProfilesCopyLabel || '';
			const successMessage = button.dataset.cbProfilesCopySuccess || '';
			if (!text || !label || !successMessage) return;

			clipboardApi.enhance(button, {
				text: () => text,
				label,
				successMessage,
			});
		});
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init, { once: true });
	} else {
		init();
	}
})();
