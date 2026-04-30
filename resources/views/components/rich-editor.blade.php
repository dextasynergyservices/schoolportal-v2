@props([
    'name'        => 'body',
    'label'       => null,
    'value'       => '',
    'placeholder' => '',
    'required'    => false,
    'minHeight'   => 'min-h-48',
])

@php
    $refName = $name === 'body' ? 'hiddenBody' : 'hiddenContent';
@endphp

<div>
    @if ($label)
        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5 block">
            {{ $label }}
            @if ($required) <span class="text-red-500" aria-hidden="true">*</span> @endif
        </label>
    @endif

    <div x-data="richEditor"
         class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden focus-within:ring-2 focus-within:ring-indigo-500/40 focus-within:border-indigo-500 dark:focus-within:border-indigo-400 transition-all shadow-sm">

        {{-- ─── Toolbar ─────────────────────────────────────────────────────────── --}}
        <div class="flex flex-wrap items-center gap-0.5 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/80 px-2 py-1.5"
             role="toolbar" aria-label="{{ __('Rich text editor toolbar') }}">

            {{-- History --}}
            <button type="button" @click="exec('undo')"
                    class="rounded p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors text-zinc-600 dark:text-zinc-300"
                    title="{{ __('Undo (Ctrl+Z)') }}" aria-label="{{ __('Undo') }}">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                </svg>
            </button>
            <button type="button" @click="exec('redo')"
                    class="rounded p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors text-zinc-600 dark:text-zinc-300"
                    title="{{ __('Redo (Ctrl+Y)') }}" aria-label="{{ __('Redo') }}">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10H11a8 8 0 00-8 8v2m18-10l-6 6m6-6l-6-6"/>
                </svg>
            </button>

            <div class="w-px h-5 bg-zinc-300 dark:bg-zinc-600 mx-1" aria-hidden="true"></div>

            {{-- Text style --}}
            <button type="button" @click="exec('bold')"
                    x-bind:class="isActive('bold') ? 'bg-zinc-200 dark:bg-zinc-600 text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-300'"
                    class="rounded p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors"
                    title="{{ __('Bold (Ctrl+B)') }}" aria-label="{{ __('Bold') }}">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z"/>
                </svg>
            </button>
            <button type="button" @click="exec('italic')"
                    x-bind:class="isActive('italic') ? 'bg-zinc-200 dark:bg-zinc-600 text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-300'"
                    class="rounded p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors"
                    title="{{ __('Italic (Ctrl+I)') }}" aria-label="{{ __('Italic') }}">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 4h4m-2 0l-4 16m0 0h4"/>
                </svg>
            </button>
            <button type="button" @click="exec('underline')"
                    x-bind:class="isActive('underline') ? 'bg-zinc-200 dark:bg-zinc-600 text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-300'"
                    class="rounded p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors"
                    title="{{ __('Underline (Ctrl+U)') }}" aria-label="{{ __('Underline') }}">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v7a5 5 0 0010 0V4M5 20h14"/>
                </svg>
            </button>
            <button type="button" @click="exec('strikeThrough')"
                    x-bind:class="isActive('strikeThrough') ? 'bg-zinc-200 dark:bg-zinc-600 text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-300'"
                    class="rounded p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors"
                    title="{{ __('Strikethrough') }}" aria-label="{{ __('Strikethrough') }}">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-width="2" d="M4 12h16"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7c0-1.1 1.79-2 4-2s4 .9 4 2-1.79 2-4 2"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 17c0 1.1 1.79 2 4 2s4-.9 4-2"/>
                </svg>
            </button>

            <div class="w-px h-5 bg-zinc-300 dark:bg-zinc-600 mx-1" aria-hidden="true"></div>

            {{-- Format block --}}
            <select @change="exec('formatBlock', $event.target.value); $event.target.value = ''"
                    class="text-xs rounded border-0 bg-transparent text-zinc-700 dark:text-zinc-300 px-2 py-1 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors cursor-pointer focus:ring-2 focus:ring-indigo-500/40 focus:outline-none"
                    aria-label="{{ __('Text format') }}">
                <option value="">{{ __('Format') }}</option>
                <option value="p">{{ __('Normal') }}</option>
                <option value="h1">{{ __('Heading 1') }}</option>
                <option value="h2">{{ __('Heading 2') }}</option>
                <option value="h3">{{ __('Heading 3') }}</option>
                <option value="blockquote">{{ __('Blockquote') }}</option>
                <option value="pre">{{ __('Code') }}</option>
            </select>

            <div class="w-px h-5 bg-zinc-300 dark:bg-zinc-600 mx-1" aria-hidden="true"></div>

            {{-- Alignment --}}
            <button type="button" @click="exec('justifyLeft')"
                    x-bind:class="isActive('justifyLeft') ? 'bg-zinc-200 dark:bg-zinc-600 text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-300'"
                    class="rounded p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors"
                    title="{{ __('Align left') }}" aria-label="{{ __('Align left') }}">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-width="2" d="M3 6h18M3 11h12M3 16h15"/>
                </svg>
            </button>
            <button type="button" @click="exec('justifyCenter')"
                    x-bind:class="isActive('justifyCenter') ? 'bg-zinc-200 dark:bg-zinc-600 text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-300'"
                    class="rounded p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors"
                    title="{{ __('Align center') }}" aria-label="{{ __('Align center') }}">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-width="2" d="M3 6h18M6 11h12M4.5 16h15"/>
                </svg>
            </button>
            <button type="button" @click="exec('justifyRight')"
                    x-bind:class="isActive('justifyRight') ? 'bg-zinc-200 dark:bg-zinc-600 text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-300'"
                    class="rounded p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors"
                    title="{{ __('Align right') }}" aria-label="{{ __('Align right') }}">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-width="2" d="M3 6h18M9 11h12M6 16h15"/>
                </svg>
            </button>

            <div class="w-px h-5 bg-zinc-300 dark:bg-zinc-600 mx-1" aria-hidden="true"></div>

            {{-- Lists + indent --}}
            <button type="button" @click="exec('insertUnorderedList')"
                    x-bind:class="isActive('insertUnorderedList') ? 'bg-zinc-200 dark:bg-zinc-600 text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-300'"
                    class="rounded p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors"
                    title="{{ __('Bullet list') }}" aria-label="{{ __('Bullet list') }}">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>
                </svg>
            </button>
            <button type="button" @click="exec('insertOrderedList')"
                    x-bind:class="isActive('insertOrderedList') ? 'bg-zinc-200 dark:bg-zinc-600 text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-300'"
                    class="rounded p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors"
                    title="{{ __('Numbered list') }}" aria-label="{{ __('Numbered list') }}">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h13M8 12h13M8 18h13M3 6v1m0 4v1m0 4v1"/>
                </svg>
            </button>
            <button type="button" @click="exec('indent')"
                    class="rounded p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors text-zinc-600 dark:text-zinc-300"
                    title="{{ __('Indent') }}" aria-label="{{ __('Indent') }}">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-width="2" d="M3 5h18M9 12h12M9 18h12"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9l3 3-3 3"/>
                </svg>
            </button>
            <button type="button" @click="exec('outdent')"
                    class="rounded p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors text-zinc-600 dark:text-zinc-300"
                    title="{{ __('Outdent') }}" aria-label="{{ __('Outdent') }}">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-width="2" d="M3 5h18M9 12h12M9 18h12"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9l-3 3 3 3"/>
                </svg>
            </button>

            <div class="w-px h-5 bg-zinc-300 dark:bg-zinc-600 mx-1" aria-hidden="true"></div>

            {{-- Link --}}
            <button type="button" @click="openLinkDialog()"
                    x-bind:class="isInLink() ? 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300' : 'text-zinc-600 dark:text-zinc-300'"
                    class="rounded p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors"
                    title="{{ __('Insert / edit link') }}" aria-label="{{ __('Link') }}">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
            </button>
            <button type="button" @click="removeLink()"
                    x-show="isInLink()"
                    class="rounded p-1.5 hover:bg-red-100 dark:hover:bg-red-900/20 transition-colors text-zinc-500 dark:text-zinc-400 hover:text-red-600 dark:hover:text-red-400"
                    title="{{ __('Remove link') }}" aria-label="{{ __('Remove link') }}"
                    x-cloak>
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    <path stroke-linecap="round" stroke-width="2" d="M3 21L21 3"/>
                </svg>
            </button>
            <button type="button" @click="insertHR()"
                    class="rounded p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors text-zinc-600 dark:text-zinc-300"
                    title="{{ __('Horizontal line') }}" aria-label="{{ __('Horizontal line') }}">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-width="2.5" d="M4 12h16"/>
                    <path stroke-linecap="round" stroke-width="1" d="M4 7h16M4 17h16" opacity="0.3"/>
                </svg>
            </button>

            <div class="w-px h-5 bg-zinc-300 dark:bg-zinc-600 mx-1" aria-hidden="true"></div>

            {{-- Text color --}}
            <button type="button" @click="toggleColorPicker('text')"
                    x-bind:class="showTextColorPicker && 'bg-zinc-200 dark:bg-zinc-600'"
                    class="rounded p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors text-zinc-600 dark:text-zinc-300"
                    title="{{ __('Text color') }}" aria-label="{{ __('Text color') }}">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 20L12 6l4 14M9.5 14h5"/>
                    <rect x="4" y="20.5" width="16" height="2" rx="1" fill="currentColor" stroke="none" opacity="0.5"/>
                </svg>
            </button>

            {{-- Highlight color --}}
            <button type="button" @click="toggleColorPicker('highlight')"
                    x-bind:class="showHighlightPicker && 'bg-zinc-200 dark:bg-zinc-600'"
                    class="rounded p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors text-zinc-600 dark:text-zinc-300"
                    title="{{ __('Highlight color') }}" aria-label="{{ __('Highlight') }}">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
            </button>

            <div class="w-px h-5 bg-zinc-300 dark:bg-zinc-600 mx-1" aria-hidden="true"></div>

            {{-- Remove formatting --}}
            <button type="button" @click="exec('removeFormat')"
                    class="rounded p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors text-zinc-600 dark:text-zinc-300"
                    title="{{ __('Remove formatting') }}" aria-label="{{ __('Remove formatting') }}">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 20H7L3 16l10-10 7 7-3 4zM6.5 17.5l3.5-3.5"/>
                    <path stroke-linecap="round" stroke-width="1.5" d="M3 21h7"/>
                </svg>
            </button>
        </div>

        {{-- ─── Link dialog ──────────────────────────────────────────────────────── --}}
        <div x-show="showLinkDialog" x-transition.opacity
             class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/80 px-4 py-3"
             x-cloak role="region" aria-label="{{ __('Insert link') }}">
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label class="text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1 block" for="link-url-{{ $name }}">
                        {{ __('URL') }}
                    </label>
                    <input id="link-url-{{ $name }}" type="url"
                           x-ref="linkUrlInput" x-model="linkUrl"
                           @keydown.enter.prevent="applyLink()"
                           @keydown.escape.prevent="closeLinkDialog()"
                           placeholder="https://"
                           class="w-full text-sm rounded-md border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white px-3 py-1.5 focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500" />
                </div>
                <button type="button" @click="applyLink()"
                        class="inline-flex items-center gap-1 rounded-md bg-neutral-800 dark:bg-neutral-200 px-3 py-1.5 text-sm font-medium text-white dark:text-neutral-900 hover:bg-neutral-700 dark:hover:bg-neutral-300 transition-colors">
                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ __('Apply') }}
                </button>
                <button type="button" @click="closeLinkDialog()"
                        class="rounded-md px-3 py-1.5 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors">
                    {{ __('Cancel') }}
                </button>
            </div>
        </div>

        {{-- ─── Color panels ─────────────────────────────────────────────────────── --}}
        <div x-show="showTextColorPicker || showHighlightPicker" x-transition.opacity
             class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/80 px-4 py-3"
             x-cloak role="region">

            {{-- Text color swatches --}}
            <div x-show="showTextColorPicker" class="space-y-2">
                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Text Color') }}</p>
                <div class="flex flex-wrap gap-2 items-center">
                    <template x-for="color in textColors" :key="color">
                        <button type="button" @click="execColor('text', color)"
                                class="size-6 rounded-full border-2 border-white dark:border-zinc-800 shadow hover:scale-110 transition-transform focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
                                x-bind:style="`background:${color};${color==='#ffffff'?'border-color:#d1d5db':''}`"
                                x-bind:title="color">
                        </button>
                    </template>
                    <button type="button" @click="exec('removeFormat'); showTextColorPicker = false"
                            class="size-6 rounded border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 flex items-center justify-center text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-600 transition-colors"
                            title="{{ __('Remove color') }}" aria-label="{{ __('Remove text color') }}">
                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- Highlight swatches --}}
            <div x-show="showHighlightPicker" class="space-y-2">
                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Highlight Color') }}</p>
                <div class="flex flex-wrap gap-2 items-center">
                    <template x-for="color in highlightColors" :key="color">
                        <button type="button" @click="execColor('highlight', color)"
                                class="size-6 rounded-full border-2 border-white dark:border-zinc-800 shadow hover:scale-110 transition-transform focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
                                x-bind:style="`background:${color}`"
                                x-bind:title="color">
                        </button>
                    </template>
                    <button type="button" @click="execColor('highlight', 'rgba(0,0,0,0)')"
                            class="size-6 rounded border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 flex items-center justify-center text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-600 transition-colors"
                            title="{{ __('Remove highlight') }}" aria-label="{{ __('Remove highlight') }}">
                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- ─── Editable content area ────────────────────────────────────────────── --}}
        <div x-ref="editor"
             contenteditable="true"
             role="textbox"
             aria-multiline="true"
             @input="updateHidden()"
             @keydown.ctrl.b.prevent="exec('bold')"
             @keydown.ctrl.i.prevent="exec('italic')"
             @keydown.ctrl.u.prevent="exec('underline')"
             class="{{ $minHeight }} max-h-[32rem] overflow-y-auto p-4 text-sm text-zinc-900 dark:text-white prose dark:prose-invert max-w-none focus:outline-none"
             data-placeholder="{{ $placeholder }}"
             style="word-break: break-word;">{!! $value !!}</div>

        {{-- ─── Footer: word / char count ───────────────────────────────────────── --}}
        <div class="flex items-center justify-end border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/60 px-4 py-1.5">
            <span class="text-xs tabular-nums text-zinc-400 dark:text-zinc-500">
                <span x-text="wordCount"></span> {{ __('words') }} &middot; <span x-text="charCount"></span> {{ __('chars') }}
            </span>
        </div>

        {{-- ─── Hidden input for form submission ────────────────────────────────── --}}
        <input type="hidden" name="{{ $name }}" x-ref="{{ $refName }}" value="{{ $value }}" />
    </div>

    @error($name)
        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
    @enderror
</div>

@once
    @push('styles')
    <style>
        [contenteditable]:empty:before {
            content: attr(data-placeholder);
            color: #a1a1aa;
            pointer-events: none;
        }
        .dark [contenteditable]:empty:before { color: #52525b; }

        [contenteditable] h1 { font-size: 1.5rem; font-weight: 700; line-height: 1.3; margin-top: 1.25rem; margin-bottom: 0.5rem; }
        [contenteditable] h2 { font-size: 1.25rem; font-weight: 700; line-height: 1.35; margin-top: 1rem; margin-bottom: 0.4rem; }
        [contenteditable] h3 { font-size: 1.1rem; font-weight: 600; line-height: 1.4; margin-top: 0.75rem; margin-bottom: 0.3rem; }
        [contenteditable] p  { margin: 0.25rem 0; }
        [contenteditable] ul { list-style-type: disc; padding-left: 1.5rem; margin: 0.4rem 0; }
        [contenteditable] ol { list-style-type: decimal; padding-left: 1.5rem; margin: 0.4rem 0; }
        [contenteditable] li { margin: 0.15rem 0; }
        [contenteditable] a  { color: #6366f1; text-decoration: underline; cursor: pointer; }
        [contenteditable] blockquote { border-left: 3px solid #e5e7eb; padding-left: 1rem; margin: 0.5rem 0; color: #6b7280; font-style: italic; }
        .dark [contenteditable] blockquote { border-color: #3f3f46; color: #a1a1aa; }
        [contenteditable] pre { background: #f4f4f5; padding: 0.75rem 1rem; border-radius: 0.375rem; font-family: monospace; font-size: 0.8rem; overflow-x: auto; white-space: pre; }
        .dark [contenteditable] pre { background: #27272a; }
        [contenteditable] hr { border: none; border-top: 2px solid #e4e4e7; margin: 1rem 0; }
        .dark [contenteditable] hr { border-color: #3f3f46; }
    </style>
    @endpush
@endonce
