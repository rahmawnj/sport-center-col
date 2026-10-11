<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const currentYear = new Date().getFullYear();
</script>

<template>
    <footer class="public-footer bg-[#001428] text-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-5 px-5 py-8 sm:flex-row sm:items-center sm:justify-between lg:px-8">
            <Link href="/" class="flex items-center gap-2.5">
                <span class="brand-mark" aria-hidden="true">SC</span>
                <span class="text-lg font-black">SPORT<span class="brand-accent">CENTER</span></span>
            </Link>
            <p class="text-sm text-slate-400">Temukan permainanmu. Bergerak lebih aktif.</p>
            <p class="text-xs text-slate-500">© {{ currentYear }} Sport Center. Hak cipta dilindungi.</p>
        </div>
    </footer>
</template>
