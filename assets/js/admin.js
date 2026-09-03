(() => {
	'use strict';
	const tabs = document.querySelector('[data-cb-profiles-tabs]');
	if (tabs) {
		const buttons = [...tabs.querySelectorAll('[data-cb-profiles-tab]')];
		const panels = [...document.querySelectorAll('[data-cb-profiles-panel]')];
		const activate = (name) => {
			buttons.forEach((button) => {
				const active = button.dataset.cbProfilesTab === name;
				button.classList.toggle('nav-tab-active', active);
				button.setAttribute('aria-selected', active ? 'true' : 'false');
			});
			panels.forEach((panel) => { panel.hidden = panel.dataset.cbProfilesPanel !== name; });
			try { sessionStorage.setItem('cbProfilesAdminTab', name); } catch (_) {}
		};
		buttons.forEach((button) => button.addEventListener('click', () => activate(button.dataset.cbProfilesTab)));
		try {
			const stored = sessionStorage.getItem('cbProfilesAdminTab');
			if (stored && buttons.some((button) => button.dataset.cbProfilesTab === stored)) activate(stored);
		} catch (_) {}
	}

	const behavior = document.querySelector('[data-cb-profiles-denied-behavior]');
	const source = document.querySelector('[data-cb-profiles-template-source]');
	const syncBehavior = () => {
		if (!behavior) return;
		document.querySelectorAll('[data-cb-profiles-behavior]').forEach((panel) => { panel.hidden = panel.dataset.cbProfilesBehavior !== behavior.value; });
	};
	const syncSource = () => {
		if (!source) return;
		document.querySelectorAll('[data-cb-profiles-template]').forEach((panel) => { panel.hidden = panel.dataset.cbProfilesTemplate !== source.value; });
	};
	behavior?.addEventListener('change', syncBehavior);
	source?.addEventListener('change', syncSource);
	syncBehavior();
	syncSource();
})();

(() => {
	'use strict';
	const enabled = document.querySelector('[data-cb-profiles-enabled-control] input[type="checkbox"]');
	const visibility = document.querySelector('[data-cb-profiles-visibility]');
	const contexts = [...document.querySelectorAll('[data-cb-profiles-context]')];
	const restrictedSettings = document.querySelector('[data-cb-profiles-restricted-settings]');

	const setInactive = (element, inactive) => {
		if (!element) return;
		element.classList.toggle('is-context-disabled', inactive);
		element.setAttribute('aria-disabled', inactive ? 'true' : 'false');
		if (inactive) element.setAttribute('inert', '');
		else element.removeAttribute('inert');
	};

	const syncProfileState = () => {
		const isEnabled = enabled ? enabled.checked : true;
		const visibilityMode = visibility ? visibility.value : 'public';
		document.querySelectorAll('[data-cb-profiles-requires-enabled], [data-cb-profiles-enabled-setting]').forEach((element) => setInactive(element, !isEnabled));

		const context = !isEnabled ? 'disabled' : visibilityMode;
		contexts.forEach((element) => { element.hidden = element.dataset.cbProfilesContext !== context; });
		setInactive(restrictedSettings, !isEnabled || visibilityMode !== 'logged_in');
	};

	enabled?.addEventListener('change', syncProfileState);
	visibility?.addEventListener('change', syncProfileState);
	syncProfileState();

	const roleDisclosure = document.querySelector('[data-cb-profiles-role-disclosure]');
	const roleCount = roleDisclosure?.querySelector('[data-cb-profiles-role-count]');
	const roleCheckboxes = [...(roleDisclosure?.querySelectorAll('[data-cb-profiles-role-checkbox]') || [])];
	const syncRoleCount = () => {
		if (roleCount) roleCount.textContent = String(roleCheckboxes.filter((checkbox) => checkbox.checked).length);
	};

	roleCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', syncRoleCount));
	syncRoleCount();
})();
