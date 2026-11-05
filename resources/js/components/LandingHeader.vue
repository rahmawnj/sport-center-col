<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { dashboard, login } from '@/routes';
</script>

<template>
    <header class="public-header sticky top-0 z-50 border-b border-slate-100 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-5 px-5 py-3.5 lg:px-8">
            <Link href="/" class="flex shrink-0 items-center gap-2.5" aria-label="Sport Center beranda">
                <span class="brand-mark" aria-hidden="true">SC</span>
                <span class="text-lg font-black tracking-tight sm:text-xl">SPORT<span class="brand-accent">CENTER</span></span>
            </Link>

            <nav class="hidden flex-1 items-center justify-center gap-6 text-sm font-bold lg:flex xl:gap-8">
                <Link href="/#olahraga" class="public-nav-link">Olahraga</Link>
                <Link href="/#pelatih" class="public-nav-link">Pelatih</Link>
                <Link href="/membership" class="public-nav-link">Membership</Link>
                <Link href="/membership" class="public-nav-link">Paket</Link>
                <Link href="/book" class="public-nav-link">Booking</Link>
            </nav>

            <div class="flex shrink-0 items-center gap-3 sm:gap-5">
                <Link v-if="$page.props.auth.user" :href="dashboard()" class="text-sm font-bold transition hover:text-[#729700]">Dasbor</Link>
                <Link v-else :href="login()" class="public-primary-button rounded-full px-5 py-2.5 text-sm font-extrabold">Login</Link>
            </div>
        </div>
    </header>
</template>
