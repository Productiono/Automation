(function () {
        const form = document.getElementById('fblaManualConnectionForm');
        const notice = document.getElementById('fblaManualConnectionNotice');

        if (!form) {
                return;
        }

        const setNotice = (message, isError = false) => {
                if (!notice) {
                        return;
                }

                notice.textContent = message;
                notice.classList.remove('uap-settings-panel-alert--error', 'uap-settings-panel-alert--success');
                notice.classList.add('uap-settings-panel-alert', isError ? 'uap-settings-panel-alert--error' : 'uap-settings-panel-alert--success');
        };

        form.addEventListener('submit', async (event) => {
                event.preventDefault();

                if (notice) {
                        notice.textContent = '';
                        notice.className = '';
                        notice.setAttribute('aria-live', 'polite');
                }

                const formData = new FormData(form);
                formData.set('action', 'automator_fbla_manual_save');

                const ajaxUrl = (window.UncannyAutomatorBackend?.ajax?.url) || form.getAttribute('action');

                try {
                        const response = await fetch(ajaxUrl, {
                                method: 'POST',
                                credentials: 'same-origin',
                                body: formData,
                        });

                        const result = await response.json();

                        if (!response.ok || !result?.success) {
                                throw new Error(result?.data?.message || 'Unable to save connection details.');
                        }

                        setNotice(result.data.message || 'Connection verified successfully.');
                } catch (error) {
                        setNotice(error.message, true);
                }
        });
})();
