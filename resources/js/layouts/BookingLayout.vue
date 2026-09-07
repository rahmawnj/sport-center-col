<script setup lang="ts">
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';

const year = computed(() => new Date().getFullYear());
</script>

<template>
    <Head title="Booking" />

    <div class="min-h-screen bg-white text-slate-900">
        <header class="border-b border-slate-100 bg-white/95 backdrop-blur">
            <div class="mx-auto flex h-18 max-w-7xl items-center justify-between px-5 sm:px-8">
                <a href="/booking" class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-900 text-sm font-bold text-white">SC</div>
                    <div>
                        <p class="text-sm font-bold tracking-tight">Sport Center</p>
                        <p class="text-[11px] text-slate-500">Book your activity</p>
                    </div>
                </a>
                <div class="flex items-center gap-3">
                    <a href="/login" class="rounded-xl px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">Login</a>
                </div>
            </div>
        </header>

        <main>
            <slot />
        </main>

        <footer class="border-t border-slate-100 bg-slate-50">
            <div class="mx-auto flex max-w-7xl flex-col gap-2 px-5 py-7 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                <span>© {{ year }} Sport Center. All rights reserved.</span>
                <span>Book your space, enjoy your game.</span>
            </div>
        </footer>
    </div>
</template>
