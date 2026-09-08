<script setup lang="ts">
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';

const year = computed(() => new Date().getFullYear());
</script>

<template>
    <Head title="Booking" />

    <div class="min-h-screen bg-white text-slate-900">
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
