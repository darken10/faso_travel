<div
    x-data="{
        toasts: [],

        add(message, type) {
            type = type || 'success';
            const id = Date.now() + Math.random();
            const duration = type === 'error' ? 5000 : 4000;
            this.toasts.push({ id, message, type, visible: false, progress: 100 });
            this.$nextTick(() => {
                const t = this.toasts.find(t => t.id === id);
                if (t) t.visible = true;
                this.startTimer(id, duration);
            });
            if (this.toasts.length > 4) this.dismiss(this.toasts[0].id);
        },

        startTimer(id, duration) {
            const tick = 40;
            const steps = duration / tick;
            let step = 0;
            const iv = setInterval(() => {
                step++;
                const t = this.toasts.find(t => t.id === id);
                if (!t) { clearInterval(iv); return; }
                t.progress = Math.max(0, 100 - (step / steps) * 100);
                if (step >= steps) { clearInterval(iv); this.dismiss(id); }
            }, tick);
        },

        dismiss(id) {
            const t = this.toasts.find(t => t.id === id);
            if (!t) return;
            t.visible = false;
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 350);
        }
    }"
    x-on:toast.window="add($event.detail.message, $event.detail.type)"
    class="fixed top-5 right-5 z-[9999] flex flex-col gap-2.5 w-80 pointer-events-none"
    role="region"
    aria-label="Notifications"
    aria-live="polite"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="toast.visible"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-full scale-95"
            x-transition:enter-end="opacity-100 translate-x-0 scale-100"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 translate-x-0 scale-100"
            x-transition:leave-end="opacity-0 translate-x-full scale-95"
            class="pointer-events-auto relative overflow-hidden rounded-2xl bg-white shadow-[0_8px_30px_rgb(0,0,0,0.12)] ring-1"
            :class="{
                'ring-emerald-200': toast.type === 'success',
                'ring-red-200':     toast.type === 'error',
                'ring-amber-200':   toast.type === 'warning',
                'ring-indigo-200':  toast.type === 'info'
            }"
            role="alert"
        >
            {{-- Accent bar --}}
            <div class="absolute left-0 top-0 bottom-0 w-1 rounded-l-2xl"
                 :class="{
                     'bg-emerald-500': toast.type === 'success',
                     'bg-red-500':     toast.type === 'error',
                     'bg-amber-500':   toast.type === 'warning',
                     'bg-indigo-500':  toast.type === 'info'
                 }">
            </div>

            {{-- Body --}}
            <div class="flex items-start gap-3 px-4 py-3.5 pl-5">

                {{-- Icon --}}
                <div class="mt-0.5 flex-shrink-0 w-8 h-8 rounded-xl flex items-center justify-center ring-1"
                     :class="{
                         'bg-emerald-100 ring-emerald-200': toast.type === 'success',
                         'bg-red-100 ring-red-200':         toast.type === 'error',
                         'bg-amber-100 ring-amber-200':     toast.type === 'warning',
                         'bg-indigo-100 ring-indigo-200':   toast.type === 'info'
                     }">

                    {{-- Success --}}
                    <svg x-show="toast.type === 'success'" class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{-- Error --}}
                    <svg x-show="toast.type === 'error'" class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    {{-- Warning --}}
                    <svg x-show="toast.type === 'warning'" class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    {{-- Info --}}
                    <svg x-show="toast.type === 'info'" class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>

                {{-- Message --}}
                <p class="flex-1 min-w-0 pt-1 text-sm font-semibold text-gray-800 leading-snug" x-text="toast.message"></p>

                {{-- Close --}}
                <button
                    @click="dismiss(toast.id)"
                    class="flex-shrink-0 mt-0.5 p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors duration-150 cursor-pointer"
                    aria-label="Fermer"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Progress bar --}}
            <div class="h-0.5 bg-gray-100">
                <div
                    class="h-full rounded-full"
                    :class="{
                        'bg-emerald-400': toast.type === 'success',
                        'bg-red-400':     toast.type === 'error',
                        'bg-amber-400':   toast.type === 'warning',
                        'bg-indigo-400':  toast.type === 'info'
                    }"
                    :style="'width: ' + toast.progress + '%'"
                ></div>
            </div>
        </div>
    </template>
</div>
