<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';

const currentYear = new Date().getFullYear();
</script>

<template>
    <div class="public-site min-h-screen bg-white text-[#001428]">
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
                    <Link href="/book" class="public-book-link rounded-full px-3 py-2.5 text-xs font-extrabold sm:px-4 sm:text-sm">Booking</Link>
                    <Link v-if="$page.props.auth.user" :href="dashboard()" class="public-primary-button rounded-full px-4 py-2.5 text-xs font-extrabold sm:px-5 sm:text-sm">Dasbor</Link>
                    <template v-else>
                        <Link :href="login()" class="hidden rounded-full px-3 py-2.5 text-sm font-bold transition hover:bg-slate-100 sm:inline-flex">Masuk</Link>
                        <Link :href="register()" class="public-primary-button rounded-full px-4 py-2.5 text-xs font-extrabold sm:px-5 sm:text-sm">Daftar</Link>
                    </template>
                </div>
            </div>
        </header>

        <slot />

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
    </div>
</template>

<style>
.public-site {
    --sport-lime: #a4da01;
    --sport-navy: #001428;
    font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
}
.public-site .brand-mark {
    display: grid;
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    place-items: center;
    border-radius: 12px;
    background: var(--sport-lime);
    color: var(--sport-navy);
    font-size: 13px;
    font-weight: 950;
    letter-spacing: -.06em;
}
.public-site .brand-accent { color: #8dbb00; }
.public-site .public-primary-button {
    background: var(--sport-lime);
    color: var(--sport-navy);
    transition: transform .2s ease, background .2s ease;
}
.public-site .public-primary-button:hover {
    transform: translateY(-1px);
    background: #b8ec24;
}
.public-site .public-book-link { color: var(--sport-navy); }
.public-site .public-book-link:hover { background: #f1f5f9; }
.public-site .public-nav-link {
    position: relative;
    padding: 10px 0;
    transition: color .2s ease;
}
.public-site .public-nav-link:hover { color: #729700; }
.public-site .public-nav-link::after {
    position: absolute;
    right: 0;
    bottom: 2px;
    left: 0;
    height: 2px;
    background: var(--sport-lime);
    content: '';
    transform: scaleX(0);
    transform-origin: left;
    transition: transform .2s ease;
}
.public-site .public-nav-link:hover::after { transform: scaleX(1); }
@media (max-width: 420px) {
    .public-site .brand-mark { width: 32px; height: 32px; border-radius: 10px; }
    .public-site .public-book-link { padding-right: .65rem; padding-left: .65rem; }
}
</style>
