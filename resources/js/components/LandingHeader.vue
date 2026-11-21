<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { login } from '@/routes';

const page = usePage();
const authUser = computed(() => (page.props as any).auth?.user ?? null);
const dashboardUrl = computed(() => {
    const role = authUser.value?.role?.name;

    if (role === 'Member') return '/member/dashboard';
    if (role === 'Trainer') return '/trainer/dashboard';

    return '/dashboard';
});

const isMenuOpen = ref(false);
const closeMenu = () => {
    isMenuOpen.value = false;
};
const initials = computed(() => {
    const name = String(authUser.value?.name ?? 'User').trim();
    return name.charAt(0).toUpperCase() || 'U';
});
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
                <details v-if="authUser" class="profile-dropdown relative hidden sm:block">
                    <summary class="flex cursor-pointer list-none items-center gap-3 rounded-2xl px-2 py-1.5 transition hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#a4da01]">
                        <img
                            v-if="authUser.image_profile_url"
                            :src="authUser.image_profile_url"
                            :alt="'Foto profil ' + authUser.name"
                            class="size-10 rounded-full border border-slate-200 object-cover"
                        />
                        <span v-else class="grid size-10 place-items-center rounded-full bg-[#a4da01] text-sm font-black text-[#001428]">{{ initials }}</span>
                        <span class="hidden min-w-0 text-left md:block">
                            <span class="block max-w-40 truncate text-sm font-extrabold text-[#001428]">{{ authUser.name }}</span>
                            <span class="block max-w-40 truncate text-xs text-slate-500">{{ authUser.email }}</span>
                        </span>
                        <svg class="size-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m7 10 5 5 5-5" /></svg>
                    </summary>
                    <div class="absolute right-0 top-full z-50 mt-3 w-64 overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-900/10">
                        <div class="border-b border-slate-100 px-3 py-3">
                            <p class="truncate text-sm font-bold text-[#001428]">{{ authUser.name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ authUser.email }}</p>
                        </div>
                        <Link :href="dashboardUrl" class="mt-2 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-lime-50 hover:text-[#001428]">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="5" rx="1.5"/><rect x="13" y="10" width="8" height="11" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/></svg>
                            Dashboard
                        </Link>
                        <Link href="/logout" method="post" as="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-rose-600 transition hover:bg-rose-50">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg>
                            Logout
                        </Link>
                    </div>
                </details>

                <Link v-else :href="login()" class="public-primary-button inline-flex items-center rounded-[15px] px-5 py-2.5 text-sm font-extrabold">Login</Link>

                <button
                    type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-[15px] text-[#001428] transition hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#a4da01] lg:hidden"
                    :aria-expanded="isMenuOpen"
                    aria-controls="mobile-public-navigation"
                    :aria-label="isMenuOpen ? 'Tutup menu navigasi' : 'Buka menu navigasi'"
                    @click="isMenuOpen = !isMenuOpen"
                >
                    <svg v-if="!isMenuOpen" xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" /></svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m18 6-12 12M6 6l12 12" /></svg>
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
            <nav v-if="isMenuOpen" id="mobile-public-navigation" class="border-t border-slate-100 bg-white px-5 py-3 shadow-lg shadow-slate-900/5 lg:hidden" aria-label="Navigasi mobile">
                <div class="mx-auto flex max-w-7xl flex-col">
                    <Link href="/#olahraga" class="mobile-nav-link" @click="closeMenu">Olahraga</Link>
                    <Link href="/#pelatih" class="mobile-nav-link" @click="closeMenu">Pelatih</Link>
                    <Link href="/#membership" class="mobile-nav-link" @click="closeMenu">Paket</Link>
                    <Link href="/book" class="mobile-nav-link" @click="closeMenu">Booking</Link>
                    <Link href="/kelas" class="mobile-nav-link" @click="closeMenu">Kelas</Link>
                    <template v-if="authUser">
                        <div class="mt-3 flex items-center gap-3 rounded-2xl bg-slate-50 p-3">
                            <img v-if="authUser.image_profile_url" :src="authUser.image_profile_url" :alt="'Foto profil ' + authUser.name" class="size-11 rounded-full object-cover" />
                            <span v-else class="grid size-11 place-items-center rounded-full bg-[#a4da01] font-black text-[#001428]">{{ initials }}</span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-bold text-[#001428]">{{ authUser.name }}</span>
                                <span class="block truncate text-xs text-slate-500">{{ authUser.email }}</span>
                            </span>
                        </div>
                        <Link :href="dashboardUrl" class="mobile-nav-link mt-2 rounded-xl border-b-0 bg-[#a4da01] px-4 text-center font-extrabold" @click="closeMenu">Dashboard</Link>
                        <Link href="/logout" method="post" as="button" class="mobile-nav-link text-center font-extrabold text-rose-600" @click="closeMenu">Logout</Link>
                    </template>
                    <Link v-else :href="login()" class="mobile-nav-link mt-2 rounded-[15px] border-b-0 bg-[#a4da01] px-4 text-center font-extrabold" @click="closeMenu">Login</Link>
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
.profile-dropdown summary::-webkit-details-marker {
    display: none;
}
</style>
