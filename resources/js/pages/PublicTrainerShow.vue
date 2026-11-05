<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';

type Trainer = {
    id: number;
    name: string;
    specialty: string | null;
    sports: string[];
};

type TrainerPackage = {
    id: number;
    name: string;
    total_sessions: number;
    duration_days: number;
    price: number;
    description: string | null;
    sports: string[];
};

defineProps<{
    trainer: Trainer;
    packages: TrainerPackage[];
}>();

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(amount);
</script>

<template>
    <Head :title="`${trainer.name} — Detail Pelatih`" />
    <PublicLayout>
        <main class="min-h-screen bg-[#f7f9f3] text-[#001428]">
            <section class="bg-[#001428] px-5 py-14 text-white sm:py-20">
                <div class="mx-auto max-w-5xl">
                    <Link href="/#pelatih" class="inline-flex items-center gap-2 text-sm font-bold text-slate-300 transition hover:text-[#a4da01]">
                        <i class="fa-solid fa-arrow-left"></i> Kembali ke daftar pelatih
                    </Link>
                    <div class="mt-9 flex flex-col gap-7 sm:flex-row sm:items-center">
                        <div class="grid h-28 w-28 shrink-0 place-items-center rounded-3xl bg-white/10 text-5xl text-[#a4da01]"><i class="fa-solid fa-user-tie"></i></div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-[.2em] text-[#a4da01]">Profil pelatih</p>
                            <h1 class="mt-2 text-3xl font-black sm:text-5xl">{{ trainer.name }}</h1>
                            <p class="mt-3 text-lg text-slate-300">{{ trainer.specialty || 'Pelatih olahraga' }}</p>
                            <div v-if="trainer.sports.length" class="mt-5 flex flex-wrap gap-2">
                                <span v-for="sport in trainer.sports" :key="sport" class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-bold text-white">{{ sport }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <section class="mx-auto max-w-5xl px-5 py-14 sm:py-20">
                <div class="mb-8">
                    <p class="text-xs font-black uppercase tracking-[.2em] text-[#789900]">Paket latihan</p>
                    <h2 class="mt-2 text-3xl font-black">Paket yang tersedia bersama {{ trainer.name }}</h2>
                    <p class="mt-3 text-slate-500">Berikut paket yang memang di-assign kepada pelatih ini.</p>
                </div>
                <div v-if="packages.length" class="grid gap-5 md:grid-cols-2">
                    <article v-for="item in packages" :key="item.id" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-xl font-black">{{ item.name }}</h3>
                                <p v-if="item.sports.length" class="mt-2 text-sm font-semibold text-[#789900]">{{ item.sports.join(' · ') }}</p>
                            </div>
                            <span class="rounded-full bg-[#f0f7d9] px-3 py-1 text-xs font-black text-[#617d00]">{{ item.total_sessions }} sesi</span>
                        </div>
                        <p v-if="item.description" class="mt-4 text-sm leading-6 text-slate-500">{{ item.description }}</p>
                        <div class="mt-6 flex items-end justify-between border-t border-slate-100 pt-5">
                            <div>
                                <p class="text-xs font-semibold text-slate-400">Durasi {{ item.duration_days }} hari</p>
                                <p class="mt-1 text-2xl font-black">{{ formatCurrency(item.price) }}</p>
                            </div>
                            <a :href="'/trainer-packages/' + item.id" class="inline-flex items-center gap-2 rounded-full bg-[#a4da01] px-5 py-3 text-sm font-black text-[#001428] transition hover:bg-[#b8ec24]">Detail & Booking <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                    </article>
                </div>
                <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
                    <i class="fa-solid fa-box-open text-3xl text-slate-300"></i>
                    <p class="mt-4 font-bold">Belum ada paket yang di-assign ke pelatih ini.</p>
                    <p class="mt-1 text-sm text-slate-500">Silakan cek kembali nanti.</p>
                </div>
            </section>
        </main>
    </PublicLayout>
</template>
