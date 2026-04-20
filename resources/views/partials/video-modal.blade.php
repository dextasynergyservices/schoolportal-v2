{{-- Reusable Video Modal — triggered by any element with [data-video-trigger]
     Usage: Include this partial once at the bottom of a page, then add
     data-video-trigger to any clickable element to open the video player.
     
     To change the video URL later, update the x-data videoUrl below. --}}

<div
    x-data="{
        isOpen: false,
        videoUrl: '{{ $videoUrl ?? "https://www.youtube.com/embed/dQw4w9WgXcQ" }}',
        playing: false,
        openModal() {
            this.isOpen = true;
            this.playing = true;
            document.body.style.overflow = 'hidden';
        },
        closeModal() {
            this.isOpen = false;
            this.playing = false;
            document.body.style.overflow = '';
        }
    }"
    x-on:open-video-modal.window="openModal()"
    x-on:keydown.escape.window="if (isOpen) closeModal()"
    x-cloak
    class="video-modal-root"
>
    {{-- Backdrop --}}
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        class="fixed inset-0 z-[9998] bg-black/80 backdrop-blur-sm"
        x-on:click="closeModal()"
        aria-hidden="true"
    ></div>

    {{-- Modal --}}
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-90"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-90"
        x-cloak
        class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6"
        role="dialog"
        aria-modal="true"
        aria-label="Video player"
    >
        <div class="relative w-full max-w-4xl" x-on:click.outside="closeModal()">
            {{-- Close button --}}
            <button
                x-on:click="closeModal()"
                class="absolute -top-12 right-0 sm:-top-14 sm:-right-2 flex items-center gap-2 text-white/70 hover:text-white transition-colors group"
                aria-label="Close video"
            >
                <span class="text-sm font-medium opacity-0 group-hover:opacity-100 transition-opacity">Close</span>
                <span class="flex items-center justify-center w-10 h-10 rounded-full bg-white/10 backdrop-blur-sm border border-white/20 group-hover:bg-white/20 transition-all">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </span>
            </button>

            {{-- Video container with glow effect --}}
            <div class="video-modal-container">
                <div class="video-modal-glow"></div>
                <div class="video-modal-frame">
                    <template x-if="playing">
                        <iframe
                            x-bind:src="videoUrl + (videoUrl.includes('?') ? '&' : '?') + 'autoplay=1&rel=0'"
                            class="absolute inset-0 w-full h-full"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                        ></iframe>
                    </template>
                </div>
            </div>

            {{-- Video title bar (optional) --}}
            <div class="mt-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[var(--sp-blue)] to-[var(--sp-navy)] flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    </div>
                    <div>
                        <p class="text-white font-semibold text-sm">DX-SchoolPortal Overview</p>
                        <p class="text-white/50 text-xs">See how it works</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .video-modal-container {
        position: relative;
        border-radius: 1rem;
        overflow: hidden;
    }
    .video-modal-glow {
        position: absolute;
        inset: -2px;
        background: linear-gradient(135deg, var(--sp-blue), var(--sp-navy), var(--sp-blue));
        border-radius: 1.1rem;
        opacity: 0.7;
        filter: blur(1px);
        animation: videoGlowPulse 3s ease-in-out infinite;
    }
    .video-modal-frame {
        position: relative;
        width: 100%;
        padding-bottom: 56.25%; /* 16:9 aspect ratio */
        background: #0a0a0f;
        border-radius: 1rem;
        overflow: hidden;
    }
    @keyframes videoGlowPulse {
        0%, 100% { opacity: 0.5; }
        50% { opacity: 0.8; }
    }

    [x-cloak] { display: none !important; }
</style>
