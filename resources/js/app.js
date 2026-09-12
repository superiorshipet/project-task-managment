const liveSearchControllers = new WeakMap();
const prefetchedUrls = new Set();

function debounce(callback, delay = 220) {
    let timeout;

    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => callback(...args), delay);
    };
}

function liveSearch(form) {
    const target = document.querySelector(form.dataset.liveTarget);

    if (!target) {
        return;
    }

    const previousController = liveSearchControllers.get(form);
    previousController?.abort();

    const controller = new AbortController();
    liveSearchControllers.set(form, controller);

    const params = new URLSearchParams(new FormData(form));
    params.set('partial', form.dataset.livePartial || '1');

    const url = `${form.action || window.location.pathname}?${params.toString()}`;
    target.classList.add('opacity-60');

    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
        signal: controller.signal,
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error(`Live search failed with ${response.status}`);
            }

            return response.text();
        })
        .then((html) => {
            target.innerHTML = html;
            const displayParams = new URLSearchParams(params);
            displayParams.delete('partial');
            const displayQuery = displayParams.toString();
            window.history.replaceState({}, '', displayQuery ? `${window.location.pathname}?${displayQuery}` : window.location.pathname);
        })
        .catch((error) => {
            if (error.name !== 'AbortError') {
                console.error(error);
            }
        })
        .finally(() => target.classList.remove('opacity-60'));
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-live-search]').forEach((form) => {
        const run = debounce(() => liveSearch(form));

        form.addEventListener('input', run);
        form.addEventListener('change', () => liveSearch(form));
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            liveSearch(form);
        });
    });

    document.addEventListener('mouseover', (event) => {
        const link = event.target.closest('a[data-prefetch]');

        if (!link || prefetchedUrls.has(link.href)) {
            return;
        }

        prefetchedUrls.add(link.href);
        fetch(link.href, {
            headers: {
                'X-Purpose': 'prefetch',
            },
        }).catch(() => {
            prefetchedUrls.delete(link.href);
        });
    }, { passive: true });
});
