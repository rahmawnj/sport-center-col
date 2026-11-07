<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import TrainerDashboardLayout from '@/layouts/trainer/TrainerDashboardLayout.vue';

defineOptions({ layout: TrainerDashboardLayout });

defineProps<{
    members: {
        data: {
            id: number;
            member: string;
            email: string | null;
            package: string;
            facility: string | null;
            start_date: string | null;
            expires_at: string | null;
            remaining_sessions: number;
            total_sessions: number;
            status: string;
        }[];
        links: { url: string | null; label: string; active: boolean }[];
    };
}>();

const date = (value: string | null) =>
    value
        ? new Date(value + 'T00:00:00').toLocaleDateString('id-ID', {
              day: '2-digit',
              month: 'short',
              year: 'numeric',
          })
        : '—';

</script>

<template>
    <Head title="Member Binaan" />

    <div class="space-y-8">
        <header>
            <p class="text-xs font-bold uppercase tracking-[.18em] text-lime-700">Personal training</p>
            <h1 class="mt-2 text-2xl font-bold">Member Binaan</h1>
            <p class="mt-1 text-sm text-slate-500">Lihat paket yang di-assign kepadamu dan member yang sedang kamu latih.</p>
        </header>

        <section class="space-y-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Member yang Mengambil Paketmu</h2>
                <p class="mt-1 text-sm text-slate-500">Daftar member yang sudah terdaftar pada paket latihanmu.</p>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div v-if="members.data.length" class="divide-y divide-slate-100">
                    <Link v-for="item in members.data" :key="item.id" :href="`/trainer/members/${item.id}`" class="group grid gap-4 p-5 transition hover:bg-lime-50/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-lime-600 md:grid-cols-[1.3fr_1fr_1fr_auto] md:items-center">
                        <div>
                            <p class="font-semibold">{{ item.member }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ item.email || 'Email tidak tersedia' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400">Paket latihan</p>
                            <p class="mt-1 text-sm font-semibold">{{ item.package }}</p>
                            <p class="text-xs text-slate-500">{{ item.facility || 'Cabang tidak ditentukan' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400">Periode</p>
                            <p class="mt-1 text-sm">{{ date(item.start_date) }} – {{ date(item.expires_at) }}</p>
                            <p class="mt-1 text-xs text-slate-500">Sisa {{ item.remaining_sessions }} dari {{ item.total_sessions }} sesi</p>
                        </div>
                        <span class="w-fit rounded-full px-3 py-1.5 text-xs font-bold capitalize" :class="{ 'bg-emerald-50 text-emerald-700': item.status === 'active', 'bg-sky-50 text-sky-700': item.status === 'scheduled', 'bg-amber-50 text-amber-700': item.status === 'pending', 'bg-slate-100 text-slate-600': !['active', 'scheduled', 'pending'].includes(item.status) }">
                            {{ item.status === 'scheduled' ? 'Menunggu giliran' : item.status }}
                        </span>
                        <span class="flex items-center gap-1 text-sm font-semibold text-lime-700 md:justify-self-end">Detail <span aria-hidden="true" class="transition-transform group-hover:translate-x-1">→</span></span>
                    </Link>
                </div>
                <div v-else class="p-12 text-center">
                    <h3 class="font-bold">Belum ada member binaan</h3>
                    <p class="mt-1 text-sm text-slate-500">Member yang mengambil paket latihanmu akan ditampilkan di sini.</p>
                </div>
            </div>

            <nav v-if="members.links.length > 3" class="flex flex-wrap gap-2">
                <template v-for="link in members.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="rounded-lg border px-3 py-2 text-sm" :class="link.active ? 'border-lime-300 bg-lime-50 font-bold' : 'border-slate-200 bg-white text-slate-600'" v-html="link.label" />
                    <span v-else class="rounded-lg border border-slate-100 px-3 py-2 text-sm text-slate-300" v-html="link.label" />
                </template>
            </nav>
        </section>
    </div>
</template>
