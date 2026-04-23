<x-layouts::app :title="__('Compose Email')">
    <div class="space-y-6">
        <x-admin-header :title="__('Compose Email to Schools')" />

        @if (session('error'))
            <flux:callout variant="danger" icon="exclamation-triangle">{{ session('error') }}</flux:callout>
        @endif

        <div class="max-w-3xl rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6" x-data="{ showSendConfirm: false, sending: false }">
            <form method="POST" action="{{ route('super-admin.emails.store') }}" class="space-y-6"
                  x-ref="emailForm">
                @csrf

                {{-- Select schools --}}
                <fieldset x-data="{
                    selected: {{ json_encode(old('school_ids', [])) }},
                    allIds: {{ $schools->pluck('id')->toJson() }},
                    get allSelected() { return this.allIds.length > 0 && this.selected.length === this.allIds.length },
                    get someSelected() { return this.selected.length > 0 && this.selected.length < this.allIds.length },
                    toggleAll() { this.selected = this.allSelected ? [] : [...this.allIds] }
                }">
                    <legend class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        {{ __('Recipient Schools') }} <span class="text-red-500">*</span>
                    </legend>

                    <div class="mb-2 flex items-center justify-between">
                        <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400 cursor-pointer">
                            <input type="checkbox" :checked="allSelected" :indeterminate="someSelected" @change="toggleAll()"
                                   class="rounded border-zinc-300 dark:border-zinc-600 text-indigo-600 focus:ring-indigo-500" />
                            {{ __('Select all') }}
                        </label>
                        <span class="text-xs text-zinc-400 dark:text-zinc-500" x-text="selected.length + ' / {{ $schools->count() }} {{ __('selected') }}'"></span>
                    </div>

                    <div class="max-h-60 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700 p-3 space-y-0.5">
                        @foreach ($schools as $school)
                            <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700/50 rounded px-2 py-1.5 transition-colors"
                                   :class="selected.includes({{ $school->id }}) && 'bg-indigo-50 dark:bg-indigo-900/20'">
                                <input type="checkbox" name="school_ids[]" value="{{ $school->id }}"
                                       x-model.number="selected"
                                       class="rounded border-zinc-300 dark:border-zinc-600 text-indigo-600 focus:ring-indigo-500" />
                                <span class="text-zinc-900 dark:text-white">{{ $school->name }}</span>
                                @if ($school->email)
                                    <span class="text-xs text-zinc-400 dark:text-zinc-500 ml-auto truncate max-w-[200px]">{{ $school->email }}</span>
                                @else
                                    <span class="text-xs text-amber-500 ml-auto">{{ __('No email') }}</span>
                                @endif
                            </label>
                        @endforeach
                    </div>

                    @error('school_ids')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </fieldset>

                <flux:input name="subject" :label="__('Subject')" :value="old('subject')" required />

                {{-- Rich text editor --}}
                <div>
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1 block">
                        {{ __('Email Body') }} <span class="text-red-500">*</span>
                    </label>
                    <div x-data="richEditor()" x-init="init()" class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden focus-within:ring-2 focus-within:ring-indigo-500/40 focus-within:border-indigo-500 dark:focus-within:border-indigo-400 transition-shadow">
                        {{-- Toolbar --}}
                        <div class="flex flex-wrap items-center gap-0.5 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 px-2 py-1.5">
                            <button type="button" @click="exec('bold')" :class="isActive('bold') && 'bg-zinc-200 dark:bg-zinc-600'" class="rounded p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors" title="{{ __('Bold (Ctrl+B)') }}">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z"/></svg>
                            </button>
                            <button type="button" @click="exec('italic')" :class="isActive('italic') && 'bg-zinc-200 dark:bg-zinc-600'" class="rounded p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors" title="{{ __('Italic (Ctrl+I)') }}">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 4h4m-2 0l-4 16m0 0h4"/></svg>
                            </button>
                            <button type="button" @click="exec('underline')" :class="isActive('underline') && 'bg-zinc-200 dark:bg-zinc-600'" class="rounded p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors" title="{{ __('Underline (Ctrl+U)') }}">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v7a5 5 0 0010 0V4M5 20h14"/></svg>
                            </button>
                            <div class="w-px h-5 bg-zinc-300 dark:bg-zinc-600 mx-1"></div>
                            <button type="button" @click="exec('insertUnorderedList')" :class="isActive('insertUnorderedList') && 'bg-zinc-200 dark:bg-zinc-600'" class="rounded p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors" title="{{ __('Bullet list') }}">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                            </button>
                            <button type="button" @click="exec('insertOrderedList')" :class="isActive('insertOrderedList') && 'bg-zinc-200 dark:bg-zinc-600'" class="rounded p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors" title="{{ __('Numbered list') }}">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h13M8 12h13M8 18h13M3 6v1m0 4v1m0 4v1"/></svg>
                            </button>
                            <div class="w-px h-5 bg-zinc-300 dark:bg-zinc-600 mx-1"></div>
                            <button type="button" @click="openLinkDialog()" class="rounded p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors" title="{{ __('Insert link') }}">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            </button>
                            <div class="w-px h-5 bg-zinc-300 dark:bg-zinc-600 mx-1"></div>
                            <select @change="exec('formatBlock', $event.target.value); $event.target.value = ''" class="text-xs rounded border-zinc-300 dark:border-zinc-600 bg-transparent dark:text-zinc-300 px-2 py-1 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors cursor-pointer">
                                <option value="">{{ __('Format') }}</option>
                                <option value="h2">{{ __('Heading') }}</option>
                                <option value="h3">{{ __('Subheading') }}</option>
                                <option value="p">{{ __('Paragraph') }}</option>
                            </select>
                            <div class="ml-auto hidden sm:flex items-center gap-1.5 text-xs text-zinc-400 dark:text-zinc-500">
                                <kbd class="px-1.5 py-0.5 rounded bg-zinc-200 dark:bg-zinc-700 font-mono text-[10px]">Ctrl+B</kbd>
                                <kbd class="px-1.5 py-0.5 rounded bg-zinc-200 dark:bg-zinc-700 font-mono text-[10px]">Ctrl+I</kbd>
                                <kbd class="px-1.5 py-0.5 rounded bg-zinc-200 dark:bg-zinc-700 font-mono text-[10px]">Ctrl+U</kbd>
                            </div>
                        </div>

                        {{-- Link dialog --}}
                        <div x-show="showLinkDialog" x-transition.opacity class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/80 px-4 py-3" x-cloak>
                            <div class="flex items-end gap-2">
                                <div class="flex-1">
                                    <label class="text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1 block">{{ __('URL') }}</label>
                                    <input type="url" x-ref="linkUrlInput" x-model="linkUrl" @keydown.enter.prevent="applyLink()" @keydown.escape.prevent="closeLinkDialog()"
                                           placeholder="https://" class="w-full text-sm rounded-md border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white px-3 py-1.5 focus:ring-2 focus:ring-neutral-500/40 focus:border-neutral-500" />
                                </div>
                                <button type="button" @click="applyLink()" class="inline-flex items-center gap-1 rounded-md bg-neutral-800 dark:bg-neutral-200 px-3 py-1.5 text-sm font-medium text-white dark:text-neutral-900 hover:bg-neutral-700 dark:hover:bg-neutral-300 transition-colors">
                                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    {{ __('Apply') }}
                                </button>
                                <button type="button" @click="closeLinkDialog()" class="rounded-md px-3 py-1.5 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors">
                                    {{ __('Cancel') }}
                                </button>
                            </div>
                        </div>

                        {{-- Editable area --}}
                        <div x-ref="editor" contenteditable="true"
                             @input="updateHidden()"
                             @keydown.ctrl.b.prevent="exec('bold')"
                             @keydown.ctrl.i.prevent="exec('italic')"
                             @keydown.ctrl.u.prevent="exec('underline')"
                             class="min-h-48 max-h-96 overflow-y-auto p-4 text-sm text-zinc-900 dark:text-white prose dark:prose-invert max-w-none focus:outline-none"
                             data-placeholder="{{ __('Write your email content here...') }}"
                             style="word-break: break-word;">
                            {!! old('body', '') !!}
                        </div>

                        {{-- Hidden field for form submission --}}
                        <input type="hidden" name="body" x-ref="hiddenBody" value="{{ old('body', '') }}">
                    </div>
                    @error('body')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-3">
                    <flux:button variant="primary" type="button" icon="paper-airplane" @click="showSendConfirm = true">{{ __('Send Email') }}</flux:button>
                    <flux:button variant="ghost" href="{{ route('super-admin.emails.index') }}" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>

            {{-- Send confirmation dialog --}}
            <div x-show="showSendConfirm" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @keydown.escape.window="showSendConfirm = false" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" x-cloak>
                <div class="fixed inset-0 bg-black/50 dark:bg-black/70" @click="showSendConfirm = false" aria-hidden="true"></div>
                <div x-show="showSendConfirm" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="relative w-full max-w-md rounded-xl bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 p-6 shadow-xl">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-700 mb-4">
                        <flux:icon name="paper-airplane" class="size-6 text-zinc-700 dark:text-zinc-300" />
                    </div>
                    <div class="text-center">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Send Email?') }}</h3>
                        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('This will send an email to all selected schools. This action cannot be undone.') }}</p>
                    </div>
                    <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-center">
                        <flux:button variant="ghost" @click="showSendConfirm = false" x-bind:disabled="sending">{{ __('Cancel') }}</flux:button>
                        <flux:button variant="primary" icon="paper-airplane" @click="sending = true; $nextTick(() => { $refs.emailForm.submit(); })" x-bind:disabled="sending">
                            <span x-show="!sending">{{ __('Yes, Send') }}</span>
                            <span x-show="sending" x-cloak class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                {{ __('Sending...') }}
                            </span>
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <style>
        [contenteditable]:empty:before {
            content: attr(data-placeholder);
            color: #a1a1aa;
            pointer-events: none;
        }
        .dark [contenteditable]:empty:before {
            color: #71717a;
        }
    </style>
    <script>
        function richEditor() {
            return {
                showLinkDialog: false,
                linkUrl: 'https://',
                savedSelection: null,
                init() {
                    const initial = this.$refs.hiddenBody.value;
                    if (initial) {
                        this.$refs.editor.innerHTML = initial;
                    }
                    // Track selection changes for active state indicators
                    document.addEventListener('selectionchange', () => this.$nextTick(() => {}));
                },
                exec(command, value = null) {
                    this.$refs.editor.focus();
                    if (command === 'formatBlock') {
                        document.execCommand('formatBlock', false, '<' + value + '>');
                    } else {
                        document.execCommand(command, false, value);
                    }
                    this.updateHidden();
                },
                isActive(command) {
                    try { return document.queryCommandState(command); } catch { return false; }
                },
                saveSelection() {
                    const sel = window.getSelection();
                    if (sel.rangeCount > 0) {
                        this.savedSelection = sel.getRangeAt(0).cloneRange();
                    }
                },
                restoreSelection() {
                    if (this.savedSelection) {
                        const sel = window.getSelection();
                        sel.removeAllRanges();
                        sel.addRange(this.savedSelection);
                    }
                },
                openLinkDialog() {
                    this.saveSelection();
                    this.linkUrl = 'https://';
                    this.showLinkDialog = true;
                    this.$nextTick(() => {
                        this.$refs.linkUrlInput.focus();
                        this.$refs.linkUrlInput.select();
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
                updateHidden() {
                    this.$refs.hiddenBody.value = this.$refs.editor.innerHTML;
                }
            };
        }
    </script>
    @endpush
</x-layouts::app>
