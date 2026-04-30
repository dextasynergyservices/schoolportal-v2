/**
 * Rich text editor — global Alpine component.
 *
 * Features: undo/redo, bold/italic/underline/strikethrough, headings,
 * blockquote, code, text alignment, bullet/numbered lists, indent/outdent,
 * link insert/remove, horizontal rule, text color, highlight color,
 * remove formatting, word + char count.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('richEditor', () => ({
        showLinkDialog: false,
        showTextColorPicker: false,
        showHighlightPicker: false,
        linkUrl: 'https://',
        savedSelection: null,
        charCount: 0,
        wordCount: 0,

        textColors: [
            '#000000',
            '#374151',
            '#6b7280',
            '#ef4444',
            '#f97316',
            '#eab308',
            '#22c55e',
            '#3b82f6',
            '#6366f1',
            '#a855f7',
            '#ec4899',
            '#ffffff',
        ],

        highlightColors: ['#fef08a', '#bbf7d0', '#a5f3fc', '#fecaca', '#fed7aa', '#e9d5ff', '#f9a8d4'],

        init() {
            const hidden = this.$refs.hiddenBody ?? this.$refs.hiddenContent;
            if (hidden?.value) {
                this.$refs.editor.innerHTML = hidden.value;
            }
            this._updateCounts();
            document.addEventListener('selectionchange', () => this.$nextTick(() => {}));
        },

        exec(command, value = null) {
            this.$refs.editor.focus();
            if (command === 'formatBlock') {
                document.execCommand('formatBlock', false, `<${value}>`);
            } else {
                document.execCommand(command, false, value);
            }
            this.updateHidden();
        },

        execColor(type, color) {
            this.restoreSelection();
            this.$refs.editor.focus();
            if (type === 'text') {
                document.execCommand('foreColor', false, color);
                this.showTextColorPicker = false;
            } else {
                document.execCommand('hiliteColor', false, color);
                this.showHighlightPicker = false;
            }
            this.updateHidden();
        },

        insertHR() {
            this.$refs.editor.focus();
            document.execCommand('insertHorizontalRule', false, null);
            this.updateHidden();
        },

        removeLink() {
            this.$refs.editor.focus();
            document.execCommand('unlink', false, null);
            this.updateHidden();
        },

        isActive(command) {
            try {
                return document.queryCommandState(command);
            } catch {
                return false;
            }
        },

        isInLink() {
            try {
                const sel = window.getSelection();
                if (!sel || sel.rangeCount === 0) return false;
                let node = sel.getRangeAt(0).commonAncestorContainer;
                while (node && node !== this.$refs.editor) {
                    if (node.nodeName === 'A') return true;
                    node = node.parentNode;
                }
                return false;
            } catch {
                return false;
            }
        },

        saveSelection() {
            const sel = window.getSelection();
            if (sel && sel.rangeCount > 0) {
                this.savedSelection = sel.getRangeAt(0).cloneRange();
            }
        },

        restoreSelection() {
            if (this.savedSelection) {
                const sel = window.getSelection();
                if (sel) {
                    sel.removeAllRanges();
                    sel.addRange(this.savedSelection);
                }
            }
        },

        openLinkDialog() {
            this.saveSelection();
            // Pre-fill URL if cursor is already inside a link
            let existingUrl = 'https://';
            const sel = window.getSelection();
            if (sel && sel.rangeCount > 0) {
                let node = sel.getRangeAt(0).commonAncestorContainer;
                while (node && node !== this.$refs.editor) {
                    if (node.nodeName === 'A') {
                        existingUrl = node.href || 'https://';
                        break;
                    }
                    node = node.parentNode;
                }
            }
            this.linkUrl = existingUrl;
            this.showLinkDialog = true;
            this.showTextColorPicker = false;
            this.showHighlightPicker = false;
            this.$nextTick(() => {
                this.$refs.linkUrlInput?.focus();
                this.$refs.linkUrlInput?.select();
            });
        },

        applyLink() {
            const url = this.linkUrl.trim();
            if (url && url !== 'https://') {
                this.restoreSelection();
                this.$refs.editor.focus();
                document.execCommand('createLink', false, url);
                this.updateHidden();
            }
            this.closeLinkDialog();
        },

        closeLinkDialog() {
            this.showLinkDialog = false;
            this.linkUrl = 'https://';
            this.savedSelection = null;
        },

        toggleColorPicker(type) {
            this.saveSelection();
            this.showLinkDialog = false;
            if (type === 'text') {
                this.showHighlightPicker = false;
                this.showTextColorPicker = !this.showTextColorPicker;
            } else {
                this.showTextColorPicker = false;
                this.showHighlightPicker = !this.showHighlightPicker;
            }
        },

        updateHidden() {
            const hidden = this.$refs.hiddenBody ?? this.$refs.hiddenContent;
            if (hidden) hidden.value = this.$refs.editor.innerHTML;
            this._updateCounts();
        },

        _updateCounts() {
            const text = this.$refs.editor?.innerText ?? '';
            this.charCount = text.replace(/\n/g, '').length;
            this.wordCount = text.trim() ? text.trim().split(/\s+/).length : 0;
        },
    }));
});

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
