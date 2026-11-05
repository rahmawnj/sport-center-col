<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import TrainerDashboardLayout from '@/layouts/trainer/TrainerDashboardLayout.vue';

defineOptions({ layout: TrainerDashboardLayout });

defineProps<{
    assignedPackages: {
        id: number;
        name: string;
        sports: string[];
        total_sessions: number;
        duration_days: number;
        price: number;
        is_active: boolean;
    }[];
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

const currency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
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
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Paket yang Di-assign ke Saya</h2>
                    <p class="mt-1 text-sm text-slate-500">Paket latihan yang bisa kamu ampu berdasarkan penugasan admin.</p>
                </div>
                <span class="rounded-full bg-lime-50 px-3 py-1.5 text-xs font-bold text-lime-700">{{ assignedPackages.length }} paket</span>
            </div>

            <div v-if="assignedPackages.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article v-for="item in assignedPackages" :key="item.id" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Paket latihan</p>
                            <h3 class="mt-1 text-base font-bold text-slate-900">{{ item.name }}</h3>
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-[11px] font-bold" :class="item.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                            {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <span v-for="sport in item.sports" :key="sport" class="rounded-lg bg-lime-50 px-2.5 py-1 text-xs font-semibold text-lime-800">{{ sport }}</span>
                        <span v-if="!item.sports.length" class="text-xs text-slate-400">Olahraga belum ditentukan</span>
                    </div>

                    <div class="mt-5 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4">
                        <div>
                            <p class="text-xs text-slate-400">Jumlah sesi</p>
                            <p class="mt-1 text-sm font-bold text-slate-800">{{ item.total_sessions }} sesi</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400">Durasi</p>
                            <p class="mt-1 text-sm font-bold text-slate-800">{{ item.duration_days }} hari</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-xs text-slate-400">Harga paket</p>
                            <p class="mt-1 text-base font-bold text-slate-900">{{ currency(item.price) }}</p>
                        </div>
                    </div>
                </article>
            </div>

            <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center">
                <h3 class="font-bold text-slate-800">Belum ada paket yang di-assign</h3>
                <p class="mt-1 text-sm text-slate-500">Paket yang ditugaskan admin kepadamu akan muncul di bagian ini.</p>
            </div>
        </section>

        <section class="space-y-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Member yang Mengambil Paketmu</h2>
                <p class="mt-1 text-sm text-slate-500">Daftar member yang sudah terdaftar pada paket latihanmu.</p>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div v-if="members.data.length" class="divide-y divide-slate-100">
                    <article v-for="item in members.data" :key="item.id" class="grid gap-4 p-5 md:grid-cols-[1.3fr_1fr_1fr_auto] md:items-center">
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
                        <span class="w-fit rounded-full px-3 py-1.5 text-xs font-bold capitalize" :class="{ 'bg-emerald-50 text-emerald-700': item.status === 'active', 'bg-amber-50 text-amber-700': item.status === 'pending', 'bg-slate-100 text-slate-600': !['active', 'pending'].includes(item.status) }">
                            {{ item.status }}
                        </span>
                    </article>
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
