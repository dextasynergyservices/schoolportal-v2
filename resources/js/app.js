/**
 * Global form loading state enhancement.
 *
 * Automatically shows a spinner and disables submit buttons when
 * POST/PUT/PATCH/DELETE forms are submitted. Prevents double-submission.
 *
 * Skips: GET forms, Livewire forms (wire:submit), forms with [data-no-loading].
 */
document.addEventListener('submit', (e) => {
    const form = e.target;
    const method = (form.getAttribute('method') || 'get').toUpperCase();

    // Skip search/filter forms and Livewire-managed forms
    if (method === 'GET') return;
    if (form.hasAttribute('wire:submit') || form.hasAttribute('wire:submit.prevent')) return;
    if ('noLoading' in form.dataset) return;

    const buttons = form.querySelectorAll('[type="submit"]');

    buttons.forEach((btn) => {
        if (btn.disabled) return;

        btn.disabled = true;
        btn.style.opacity = '0.7';
        btn.style.cursor = 'wait';

        // Replace text content with spinner — preserves outer <button> styling (Flux classes)
        const hasText = btn.textContent.trim().length > 0;
        if (hasText) {
            btn.dataset.originalHtml = btn.innerHTML;
            btn.innerHTML =
                '<svg class="animate-spin h-4 w-4 shrink-0 -ml-0.5 mr-1.5 inline-block" viewBox="0 0 24 24" fill="none" aria-hidden="true">' +
                '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>' +
                '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>' +
                '</svg> Processing\u2026';
        }
    });

    // Safety: re-enable after 15s if page didn't navigate (e.g. validation error redirect)
    setTimeout(() => {
        buttons.forEach((btn) => {
            btn.disabled = false;
            btn.style.opacity = '';
            btn.style.cursor = '';
            if (btn.dataset.originalHtml) {
                btn.innerHTML = btn.dataset.originalHtml;
                delete btn.dataset.originalHtml;
            }
        });
    }, 15000);
});
