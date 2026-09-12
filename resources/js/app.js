import './echo';

const liveSearchControllers = new WeakMap();
const liveSearchSignatures = new WeakMap();
function debounce(callback, delay = 80) {
    let timeout;

    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => callback(...args), delay);
    };
}

function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    })[char]);
}

function renderNotificationItem(notification) {
    const wrapperClass = notification.read
        ? 'rounded-xl p-3 transition hover:bg-gray-50'
        : 'rounded-xl p-3 transition bg-indigo-50/60 hover:bg-indigo-50';
    const dotClass = notification.read ? 'bg-gray-300' : 'bg-indigo-500';

    return `
        <div class="${wrapperClass}">
            <div class="flex items-start justify-between gap-3">
                <a href="${escapeHtml(notification.open_url)}" class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="size-2 shrink-0 rounded-full ${dotClass}"></span>
                        <p class="truncate text-sm font-semibold text-gray-950">${escapeHtml(notification.title)}</p>
                    </div>
                    <p class="mt-1 line-clamp-2 text-xs leading-5 text-gray-500">${escapeHtml(notification.body)}</p>
                    <p class="mt-2 text-[11px] font-semibold text-gray-400">${escapeHtml(notification.created_at)}</p>
                </a>
            </div>
        </div>
    `;
}

function prependNotificationItem(root, notification) {
    const list = root.querySelector('[data-notifications-list]');

    if (!list) {
        return;
    }

    list.querySelector('[data-notifications-empty]')?.remove();
    list.insertAdjacentHTML('afterbegin', renderNotificationItem(notification));

    const items = list.children;
    while (items.length > 5) {
        items[items.length - 1].remove();
    }
}

function setNotificationCount(count) {
    document.querySelectorAll('[data-notification-count]').forEach((counter) => {
        counter.textContent = count;
        counter.classList.toggle('bg-rose-500', count > 0);
        counter.classList.toggle('text-white', count > 0);
        counter.classList.toggle('text-slate-500', count === 0);
    });

    const badge = document.querySelector('[data-notification-badge]');
    const unreadLabel = document.querySelector('[data-notification-unread-label]');

    if (badge) {
        badge.textContent = count;
        badge.classList.toggle('grid', count > 0);
        badge.classList.toggle('hidden', count === 0);
    }

    if (unreadLabel) {
        unreadLabel.textContent = `${count} unread`;
    }
}

async function refreshNotifications(root) {
    if (!root?.dataset.feedUrl) {
        return;
    }

    const response = await fetch(root.dataset.feedUrl, {
        headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
        return;
    }

    const payload = await response.json();
    const count = Number(payload.unread_count || 0);
    const list = root.querySelector('[data-notifications-list]');

    setNotificationCount(count);

    if (!list) {
        return;
    }

    if (!payload.notifications?.length) {
        list.innerHTML = '<p data-notifications-empty class="rounded-xl border border-dashed border-gray-200 p-6 text-center text-sm text-gray-500">No notifications yet.</p>';
        return;
    }

    list.innerHTML = payload.notifications.map(renderNotificationItem).join('');
}

function normalizedWords(value) {
    return String(value || '')
        .trim()
        .toLowerCase()
        .split(/\s+/)
        .filter(Boolean);
}

function taskMatchesSearch(card, terms) {
    if (terms.length === 0) {
        return true;
    }

    const searchableWords = normalizedWords(card.dataset.taskSearch);

    return terms.every((term) => searchableWords.some((word) => word.startsWith(term)));
}

function todayIsoDate() {
    const date = new Date();
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());

    return date.toISOString().slice(0, 10);
}

function endOfWeekIsoDate() {
    const date = new Date();
    const day = date.getDay();
    const daysUntilSunday = day === 0 ? 0 : 7 - day;
    date.setDate(date.getDate() + daysUntilSunday);
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());

    return date.toISOString().slice(0, 10);
}

function taskMatchesDueDate(card, dueDate, dueRange) {
    const cardDueDate = String(card.dataset.taskDueDate || '');

    if (dueDate) {
        return cardDueDate === dueDate;
    }

    if (!dueRange) {
        return true;
    }

    if (!cardDueDate) {
        return false;
    }

    const today = todayIsoDate();

    if (dueRange === 'today') {
        return cardDueDate === today;
    }

    if (dueRange === 'week') {
        return cardDueDate >= today && cardDueDate <= endOfWeekIsoDate();
    }

    if (dueRange === 'overdue') {
        return cardDueDate < today && card.dataset.taskStatus !== 'completed';
    }

    return true;
}

function syncClientEmptyState(column, visibleCount) {
    let empty = column.querySelector('[data-client-empty]');

    if (visibleCount > 0) {
        empty?.remove();
        return;
    }

    if (!empty) {
        empty = document.createElement('div');
        empty.dataset.clientEmpty = '1';
        empty.className = 'rounded-2xl border border-dashed border-gray-300 bg-white/70 p-6 text-center text-sm text-gray-500';
        empty.textContent = 'No tasks here.';
        column.querySelector('[data-column-cards]')?.append(empty);
    }
}

function applyInstantBoardFilter(form) {
    const target = document.querySelector(form.dataset.liveTarget);

    if (!target) {
        return;
    }

    const data = new FormData(form);
    const status = String(data.get('status') || '');
    const assignedTo = String(data.get('assigned_to') || '');
    const projectId = String(data.get('project_id') || '');
    const dueDate = String(data.get('due_date') || '');
    const dueRange = String(data.get('due_range') || '');
    const terms = normalizedWords(data.get('q'));

    target.querySelectorAll('[data-client-empty]').forEach((empty) => empty.remove());

    target.querySelectorAll('[data-task-card]').forEach((card) => {
        const matches = (!status || card.dataset.taskStatus === status)
            && (!assignedTo || String(card.dataset.taskAssignedTo || '').split(',').includes(assignedTo))
            && (!projectId || card.dataset.taskProjectId === projectId)
            && taskMatchesDueDate(card, dueDate, dueRange)
            && taskMatchesSearch(card, terms);

        card.hidden = !matches;
    });

    target.querySelectorAll('[data-status-column]').forEach((column) => {
        const visibleCount = column.querySelectorAll('[data-task-card]:not([hidden])').length;
        const count = column.querySelector('[data-column-count]');

        if (count) {
            count.textContent = visibleCount;
        }

        syncClientEmptyState(column, visibleCount);
    });
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

    const signature = params.toString();
    const displayParams = new URLSearchParams(params);
    params.set('partial', form.dataset.livePartial || '1');
    url.search = params.toString();

    return {
        requestUrl: url.toString(),
        displayUrl: displayParams.toString() ? `${url.pathname}?${displayParams.toString()}` : url.pathname,
        signature,
    };
}

function markLiveSearchIntent(form) {
    liveSearchSignatures.set(form, liveSearchUrl(form).signature);
}

function replaceHistoryFromForm(form) {
    window.history.replaceState({}, '', liveSearchUrl(form).displayUrl);
}

function shouldUseClientOnlySearch(form) {
    return form.dataset.liveMode === 'client';
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

    const { requestUrl, displayUrl, signature } = liveSearchUrl(form);
    liveSearchSignatures.set(form, signature);
    target.setAttribute('aria-busy', 'true');

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
            if (liveSearchSignatures.get(form) !== signature) {
                return;
            }

            target.innerHTML = html;
            applyInstantBoardFilter(form);
            window.history.replaceState({}, '', displayUrl);
        })
        .catch((error) => {
            if (error.name !== 'AbortError') {
                console.error(error);
            }
        })
        .finally(() => {
            if (liveSearchSignatures.get(form) === signature) {
                target.removeAttribute('aria-busy');
            }
        });
}

function updateColumnCount(column, delta) {
    const count = column?.querySelector('[data-column-count]');

    if (!count) {
        return;
    }

    count.textContent = Math.max(0, Number.parseInt(count.textContent || '0', 10) + delta);
}

function updateProjectProgress(previousStatus, nextStatus) {
    const progress = document.querySelector('[data-project-progress]');

    if (!progress || previousStatus === nextStatus) {
        return;
    }

    const total = Number.parseInt(progress.dataset.total || '0', 10);
    let completed = Number.parseInt(progress.dataset.completed || '0', 10);

    if (previousStatus === 'completed') {
        completed -= 1;
    }

    if (nextStatus === 'completed') {
        completed += 1;
    }

    completed = Math.max(0, Math.min(total, completed));
    progress.dataset.completed = String(completed);

    const percentage = total > 0 ? Math.round((completed / total) * 100) : 0;
    const label = progress.querySelector('[data-project-progress-label]');
    const bar = progress.querySelector('[data-project-progress-bar]');

    if (label) {
        label.textContent = `${percentage}%`;
    }

    if (bar) {
        bar.style.width = `${percentage}%`;
    }
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

function submitTaskStatusForm(form, card, nextStatus) {
    if (!card || !nextStatus || form.dataset.busy === '1') {
        return;
    }

    const previousStatus = card.dataset.taskStatus;
    const snapshot = moveTaskCard(card, nextStatus);
    updateProjectProgress(previousStatus, nextStatus);
    form.dataset.busy = '1';

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
            updateProjectProgress(nextStatus, previousStatus);
            alert('Task status could not be updated. Please try again.');
        })
        .finally(() => {
            delete form.dataset.busy;
        });
}

document.addEventListener('DOMContentLoaded', () => {
    const notificationsRoot = document.querySelector('[data-notifications-root]');

    if (notificationsRoot) {
        refreshNotifications(notificationsRoot).catch(console.error);

        if (window.Echo && notificationsRoot.dataset.userId) {
            window.Echo.private(`users.${notificationsRoot.dataset.userId}`)
                .listen('.workspace.notification.created', (event) => {
                    setNotificationCount(Number(event.unread_count || 0));
                    prependNotificationItem(notificationsRoot, event.notification);
                });
        }

        setInterval(() => refreshNotifications(notificationsRoot).catch(console.error), 2500);
    }

    document.querySelectorAll('[data-live-search]').forEach((form) => {
        const run = debounce(() => liveSearch(form));

        form.addEventListener('input', (event) => {
            markLiveSearchIntent(form);
            applyInstantBoardFilter(form);
            replaceHistoryFromForm(form);

            if (shouldUseClientOnlySearch(form)) {
                return;
            }

            if (event.target.name === 'q' && event.target.value.trim() === '') {
                liveSearch(form);
                return;
            }

            run();
        });
        form.addEventListener('change', () => {
            markLiveSearchIntent(form);
            applyInstantBoardFilter(form);
            replaceHistoryFromForm(form);

            if (shouldUseClientOnlySearch(form)) {
                return;
            }

            liveSearch(form);
        });
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            markLiveSearchIntent(form);
            applyInstantBoardFilter(form);
            replaceHistoryFromForm(form);

            if (shouldUseClientOnlySearch(form)) {
                return;
            }

            liveSearch(form);
        });
    });

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('[data-status-form]');

        if (!form) {
            return;
        }

        event.preventDefault();

        const card = form.closest('[data-task-card]');
        const nextStatus = form.querySelector('input[name="status"]')?.value;

        submitTaskStatusForm(form, card, nextStatus);
    });

    document.addEventListener('dragstart', (event) => {
        const card = event.target.closest('[data-task-card]');

        if (!card) {
            return;
        }

        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', card.dataset.taskId);
        card.classList.add('opacity-50', 'ring-2', 'ring-indigo-300');
    });

    document.addEventListener('dragend', (event) => {
        event.target.closest('[data-task-card]')?.classList.remove('opacity-50', 'ring-2', 'ring-indigo-300');
        document.querySelectorAll('[data-status-column]').forEach((column) => column.classList.remove('border-indigo-300', 'bg-indigo-50/40'));
    });

    document.addEventListener('dragover', (event) => {
        const column = event.target.closest('[data-status-column]');

        if (!column) {
            return;
        }

        event.preventDefault();
        column.classList.add('border-indigo-300', 'bg-indigo-50/40');
    });

    document.addEventListener('dragleave', (event) => {
        const column = event.target.closest('[data-status-column]');

        if (column && !column.contains(event.relatedTarget)) {
            column.classList.remove('border-indigo-300', 'bg-indigo-50/40');
        }
    });

    document.addEventListener('drop', (event) => {
        const column = event.target.closest('[data-status-column]');

        if (!column) {
            return;
        }

        event.preventDefault();
        column.classList.remove('border-indigo-300', 'bg-indigo-50/40');

        const taskId = event.dataTransfer.getData('text/plain');
        const card = document.querySelector(`[data-task-card][data-task-id="${taskId}"]`);
        const nextStatus = column.dataset.statusColumn;

        if (!card || !nextStatus || card.dataset.taskStatus === nextStatus) {
            return;
        }

        const form = Array.from(card.querySelectorAll('[data-status-form]'))
            .find((candidate) => candidate.querySelector('input[name="status"]')?.value === nextStatus);

        if (form) {
            submitTaskStatusForm(form, card, nextStatus);
        }
    });
});
