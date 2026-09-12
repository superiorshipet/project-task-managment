const liveSearchControllers = new WeakMap();
const prefetchedUrls = new Set();

function debounce(callback, delay = 110) {
    let timeout;

    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => callback(...args), delay);
    };
}

function liveSearchUrl(form) {
    const url = new URL(form.action || window.location.pathname, window.location.origin);
    const params = new URLSearchParams();

    new FormData(form).forEach((value, key) => {
        const normalized = String(value).trim();

        if (normalized !== '') {
            params.set(key, normalized);
        }
    });

    const displayParams = new URLSearchParams(params);
    params.set('partial', form.dataset.livePartial || '1');
    url.search = params.toString();

    return {
        requestUrl: url.toString(),
        displayUrl: displayParams.toString() ? `${url.pathname}?${displayParams.toString()}` : url.pathname,
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

    const { requestUrl, displayUrl } = liveSearchUrl(form);
    target.classList.add('opacity-60');

    fetch(requestUrl, {
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
            window.history.replaceState({}, '', displayUrl);
        })
        .catch((error) => {
            if (error.name !== 'AbortError') {
                console.error(error);
            }
        })
        .finally(() => target.classList.remove('opacity-60'));
}

function updateColumnCount(column, delta) {
    const count = column?.querySelector('[data-column-count]');

    if (!count) {
        return;
    }

    count.textContent = Math.max(0, Number.parseInt(count.textContent || '0', 10) + delta);
}

function applyStatusButtonState(card, status) {
    card.querySelectorAll('[data-status-form]').forEach((form) => {
        const button = form.querySelector('button');
        const formStatus = form.querySelector('input[name="status"]')?.value;

        if (!button || !formStatus) {
            return;
        }

        button.className = formStatus === status
            ? 'w-full rounded-lg border px-2 py-1 text-[11px] font-semibold transition border-slate-950 bg-slate-950 text-white'
            : 'w-full rounded-lg border px-2 py-1 text-[11px] font-semibold transition border-gray-200 text-gray-500 hover:border-gray-300';
    });
}

function moveTaskCard(card, nextStatus) {
    const previousColumn = card.closest('[data-status-column]');
    const nextColumn = document.querySelector(`[data-status-column="${nextStatus}"]`);
    const nextCards = nextColumn?.querySelector('[data-column-cards]');

    if (!nextColumn || !nextCards || previousColumn === nextColumn) {
        applyStatusButtonState(card, nextStatus);
        card.dataset.taskStatus = nextStatus;
        return { previousColumn, nextColumn: previousColumn, previousSibling: card.nextElementSibling };
    }

    const previousSibling = card.nextElementSibling;
    updateColumnCount(previousColumn, -1);
    updateColumnCount(nextColumn, 1);
    card.dataset.taskStatus = nextStatus;
    applyStatusButtonState(card, nextStatus);
    nextCards.prepend(card);

    return { previousColumn, nextColumn, previousSibling };
}

function rollbackTaskCard(card, snapshot) {
    const currentColumn = card.closest('[data-status-column]');
    const previousCards = snapshot.previousColumn?.querySelector('[data-column-cards]');

    if (!previousCards || !currentColumn || currentColumn === snapshot.previousColumn) {
        return;
    }

    updateColumnCount(currentColumn, -1);
    updateColumnCount(snapshot.previousColumn, 1);

    if (snapshot.previousSibling?.parentElement === previousCards) {
        previousCards.insertBefore(card, snapshot.previousSibling);
    } else {
        previousCards.append(card);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-live-search]').forEach((form) => {
        const run = debounce(() => liveSearch(form));

        form.addEventListener('input', (event) => {
            if (event.target.name === 'q' && event.target.value.trim() === '') {
                liveSearch(form);
                return;
            }

            run();
        });
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

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('[data-status-form]');

        if (!form) {
            return;
        }

        event.preventDefault();

        const card = form.closest('[data-task-card]');
        const nextStatus = form.querySelector('input[name="status"]')?.value;

        if (!card || !nextStatus || form.dataset.pending === '1') {
            return;
        }

        const previousStatus = card.dataset.taskStatus;
        const snapshot = moveTaskCard(card, nextStatus);
        form.dataset.pending = '1';

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`Status update failed with ${response.status}`);
                }

                return response.json();
            })
            .catch((error) => {
                console.error(error);
                rollbackTaskCard(card, snapshot);
                card.dataset.taskStatus = previousStatus;
                applyStatusButtonState(card, previousStatus);
                alert('Task status could not be updated. Please try again.');
            })
            .finally(() => {
                delete form.dataset.pending;
            });
    });
});
