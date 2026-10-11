<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { dashboard, login } from '@/routes';
</script>

<template>
    <header class="public-header sticky top-0 z-50 border-b border-slate-100 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-3.5 lg:px-8">
            <Link href="/" class="flex shrink-0 items-center gap-2.5" aria-label="Sport Center beranda">
                <span class="brand-mark" aria-hidden="true">SC</span>
                <span class="text-lg font-black tracking-tight sm:text-xl">SPORT<span class="brand-accent">CENTER</span></span>
            </Link>

            <nav class="hidden items-center gap-7 text-sm font-bold md:flex">
                <Link href="/#olahraga" class="public-nav-link">Olahraga</Link>
                <Link href="/#pelatih" class="public-nav-link">Pelatih</Link>
                <Link href="/#membership" class="public-nav-link">Keanggotaan</Link>
            </nav>

            <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                <Link href="/book" class="public-primary-button rounded-full px-4 py-2.5 text-xs font-extrabold sm:px-5 sm:text-sm">Booking</Link>
                <Link v-if="$page.props.auth.user" :href="dashboard()" class="rounded-full px-3 py-2.5 text-xs font-bold transition hover:bg-slate-100 sm:px-4 sm:text-sm">Dasbor</Link>
                <Link v-else :href="login()" class="hidden rounded-full px-3 py-2.5 text-sm font-bold transition hover:bg-slate-100 sm:inline-flex">Masuk</Link>
            </div>
        </div>
    </header>
</template>
