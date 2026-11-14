<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { dashboard, login } from '@/routes';

const isMenuOpen = ref(false);

const closeMenu = () => {
    isMenuOpen.value = false;
};
</script>

<template>
    <header class="public-header sticky top-0 z-50 border-b border-slate-100/80 bg-white/90 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-3.5 lg:px-8">
            <Link href="/" class="flex shrink-0 items-center gap-2.5" aria-label="Sport Center beranda" @click="closeMenu">
                <span class="brand-mark" aria-hidden="true">SC</span>
                <span class="text-lg font-black tracking-tight sm:text-xl">SPORT<span class="brand-accent">CENTER</span></span>
            </Link>

            <nav class="hidden flex-1 items-center justify-center gap-6 text-sm font-semibold lg:flex xl:gap-8" aria-label="Navigasi utama">
                <Link href="/#olahraga" class="public-nav-link">Olahraga</Link>
                <Link href="/#pelatih" class="public-nav-link">Pelatih</Link>
                <Link href="/#membership" class="public-nav-link">Paket</Link>
                <Link href="/book" class="public-nav-link">Booking</Link>
                <Link href="/kelas" class="public-nav-link">Kelas</Link>
            </nav>

            <div class="flex shrink-0 items-center gap-3">
                <Link v-if="$page.props.auth.user" :href="dashboard()" class="public-primary-button inline-flex items-center rounded-[15px] px-5 py-2.5 text-sm font-extrabold">Dasbor</Link>
                <Link v-else :href="login()" class="public-primary-button inline-flex items-center rounded-[15px] px-5 py-2.5 text-sm font-extrabold">Login</Link>

                <button
                    type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-[15px] text-[#001428] transition hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#a4da01] lg:hidden"
                    :aria-expanded="isMenuOpen"
                    aria-controls="mobile-public-navigation"
                    :aria-label="isMenuOpen ? 'Tutup menu navigasi' : 'Buka menu navigasi'"
                    @click="isMenuOpen = !isMenuOpen"
                >
                    <svg v-if="!isMenuOpen" xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m18 6-12 12M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-[-6px] opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-[-6px] opacity-0"
        >
            <nav
                v-if="isMenuOpen"
                id="mobile-public-navigation"
                class="border-t border-slate-100 bg-white px-5 py-3 shadow-lg shadow-slate-900/5 lg:hidden"
                aria-label="Navigasi mobile"
            >
                <div class="mx-auto flex max-w-7xl flex-col">
                    <Link href="/#olahraga" class="mobile-nav-link" @click="closeMenu">Olahraga</Link>
                    <Link href="/#pelatih" class="mobile-nav-link" @click="closeMenu">Pelatih</Link>
                    <Link href="/#membership" class="mobile-nav-link" @click="closeMenu">Paket</Link>
                    <Link href="/book" class="mobile-nav-link" @click="closeMenu">Booking</Link>
                    <Link href="/kelas" class="mobile-nav-link" @click="closeMenu">Kelas</Link>
                    <Link v-if="$page.props.auth.user" :href="dashboard()" class="mobile-nav-link mt-2 rounded-[15px] border-b-0 bg-[#a4da01] px-4 text-center font-extrabold" @click="closeMenu">Dasbor</Link>
                </div>
            </nav>
        </Transition>
    </header>
</template>

<style scoped>
.mobile-nav-link {
    border-bottom: 1px solid #f1f5f9;
    padding: 14px 2px;
    color: #001428;
    font-size: 14px;
    font-weight: 600;
    transition: color .18s ease, padding-left .18s ease;
}
.mobile-nav-link:hover {
    padding-left: 7px;
    color: #729700;
}
</style>
