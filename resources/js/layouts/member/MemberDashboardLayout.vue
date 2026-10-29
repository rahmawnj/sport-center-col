<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    CalendarDays,
    ChevronDown,
    CircleUserRound,
    CreditCard,
    LayoutDashboard,
    LogOut,
    House,
    Menu,
    Trophy,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';

const page = usePage();
const sidebarOpen = ref(false);
const user = computed(() => (page.props.auth as { user?: { name?: string; email?: string } } | undefined)?.user);
const currentPath = computed(() => page.url.split('?')[0]);

const navigation = [
    { label: 'Dashboard', href: '/member/dashboard', icon: LayoutDashboard },
    { label: 'Booking Saya', href: '/member/bookings', icon: CalendarDays },
    { label: 'Membership Saya', href: '/member/membership', icon: Trophy },
    { label: 'Pembayaran', href: '/member/payments', icon: CreditCard },
    { label: 'Profil Saya', href: '/member/profile', icon: CircleUserRound },
];
</script>

<template>
    <div class="member-dashboard min-h-screen bg-[#f5f7fb] text-slate-800">
        <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-slate-950/40 lg:hidden" @click="sidebarOpen = false" />

        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-[264px] flex-col border-r border-slate-200 bg-white transition-transform duration-200 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        >
            <div class="flex h-[76px] items-center justify-between border-b border-slate-100 px-6">
                <Link href="/" class="flex items-center gap-3" aria-label="Sport Center home">
                    <span class="grid size-10 place-items-center rounded-xl bg-[#a4da01] text-sm font-black tracking-tight text-[#001428]">SC</span>
                    <span class="text-lg font-extrabold tracking-tight text-[#001428]">SPORT<span class="text-[#86b400]">CENTER</span></span>
                </Link>
                <button class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="Tutup menu" @click="sidebarOpen = false">
                    <X class="size-5" />
                </button>
            </div>

            <div class="border-b border-slate-100 px-5 py-5">
                <p class="text-[11px] font-bold uppercase tracking-[.16em] text-slate-400">Member area</p>
                <div class="mt-3 flex items-center gap-3">
                    <div class="grid size-11 shrink-0 place-items-center rounded-full bg-lime-100 font-bold text-lime-800">
                        {{ (user?.name || 'M').slice(0, 1).toUpperCase() }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold">{{ user?.name || 'Member' }}</p>
                        <p class="truncate text-xs text-slate-500">{{ user?.email || 'Akun member' }}</p>
                    </div>
                    <ChevronDown class="size-4 text-slate-400" />
                </div>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-5">
                <p class="mb-3 px-3 text-[10px] font-bold uppercase tracking-[.16em] text-slate-400">Menu utama</p>
                <Link
                    v-for="item in navigation"
                    :key="item.href"
                    :href="item.href"
                    class="group flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium transition-colors"
                    :class="currentPath === item.href ? 'bg-[#a4da01]/20 text-[#203000]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950'"
                    @click="sidebarOpen = false"
                >
                    <component :is="item.icon" class="size-[18px]" />
                    <span>{{ item.label }}</span>
                    <span v-if="currentPath === item.href" class="ml-auto size-1.5 rounded-full bg-[#719900]" />
                </Link>
            </nav>

            <div class="border-t border-slate-100 p-4">
                <Link href="/" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-950" @click="sidebarOpen = false">
                    <House class="size-[18px]" />
                    Kembali ke website
                </Link>
                <Link href="/logout" method="post" as="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left text-sm font-semibold text-rose-600 transition hover:bg-rose-50 hover:text-rose-700" @click="sidebarOpen = false">
                    <LogOut class="size-[18px]" />
                    Logout
                </Link>
                <p class="px-3 pt-2 text-[11px] text-slate-400">Sport Center · Member Portal</p>
            </div>
        </aside>

        <div class="min-h-screen lg:pl-[264px]">
            <header class="sticky top-0 z-30 flex h-[76px] items-center justify-between border-b border-slate-200/80 bg-white/90 px-4 backdrop-blur-xl sm:px-7">
                <div class="flex items-center gap-3">
                    <button class="rounded-xl border border-slate-200 p-2.5 text-slate-600 hover:bg-slate-50 lg:hidden" aria-label="Buka menu" @click="sidebarOpen = true">
                        <Menu class="size-5" />
                    </button>
                    <div>
                        <p class="text-xs text-slate-500">Member portal</p>
                        <p class="text-sm font-semibold text-slate-900">Area Member</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-semibold">{{ user?.name || 'Member' }}</p>
                        <p class="text-xs text-slate-500">Selamat datang kembali</p>
                    </div>
                    <div class="grid size-10 place-items-center rounded-full bg-[#a4da01]/25 font-bold text-[#304600]">
                        {{ (user?.name || 'M').slice(0, 1).toUpperCase() }}
                    </div>
                </div>
            </header>

            <main class="mx-auto w-full max-w-[1600px] p-4 sm:p-6 lg:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>

<style>
.member-dashboard {
    font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
}
</style>
