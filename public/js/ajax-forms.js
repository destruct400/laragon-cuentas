(() => {
    const replaceMain = (html) => {
        const documentResponse = new DOMParser().parseFromString(
            html,
            'text/html',
        );
        const nextMain = documentResponse.querySelector('main');
        const currentMain = document.querySelector('main');

        if (!nextMain || !currentMain) {
            window.location.reload();
            return;
        }

        currentMain.replaceWith(nextMain);
    };

    document.addEventListener('submit', async (event) => {
        const form = event.target;

        if (
            !(form instanceof HTMLFormElement) ||
            !form.matches('[data-ajax]')
        ) {
            return;
        }

        event.preventDefault();

        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
        }

        try {
            const method = (form.method || 'POST').toUpperCase();
            const formData = new FormData(form);
            const options = {
                method,
                headers: {
                    Accept: 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            };

            let url = form.action;
            if (method === 'GET') {
                const query = new URLSearchParams(formData).toString();
                url = query ? `${url}?${query}` : url;
            } else {
                options.body = formData;
            }

            const response = await fetch(url, {
                ...options,
            });

            replaceMain(await response.text());
        } catch {
            form.submit();
        }
    });
})();
