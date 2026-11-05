<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';

type Trainer = { id: number; name: string; specialty: string | null };
type Facility = { id: number; name: string };
type TrainerPackage = {
    id: number;
    name: string;
    description: string | null;
    total_sessions: number;
    duration_days: number;
    price: number;
    trainers: Trainer[];
    facilities: Facility[];
};

const props = defineProps<{ package: TrainerPackage; canPurchase: boolean }>();
const form = useForm({
    trainer_id: props.package.trainers[0]?.id ?? '',
    facility_id: props.package.facilities[0]?.id ?? '',
});
const formatCurrency = (amount: number) => new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
}).format(amount);
const submit = () => form.post(`/trainer-packages/${props.package.id}/purchase`);
</script>

<template>
    <Head :title="`${package.name} — Detail Paket Trainer`" />
    <PublicLayout>
        <main class="min-h-screen bg-[#f7f9f3] text-[#001428]">
            <section class="bg-[#001428] px-5 py-14 text-white sm:py-20">
                <div class="mx-auto max-w-5xl">
                    <Link href="/#paket-trainer" class="inline-flex items-center gap-2 text-sm font-bold text-slate-300 transition hover:text-[#a4da01]">
                        <i class="fa-solid fa-arrow-left"></i> Kembali ke paket trainer
                    </Link>
                    <p class="mt-10 text-xs font-black uppercase tracking-[.2em] text-[#a4da01]">Detail paket personal trainer</p>
                    <h1 class="mt-3 max-w-3xl text-3xl font-black sm:text-5xl">{{ package.name }}</h1>
                    <p class="mt-5 max-w-2xl leading-7 text-slate-300">{{ package.description || 'Program latihan personal bersama trainer untuk membantu mencapai target kebugaranmu.' }}</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <span class="rounded-full bg-white/10 px-4 py-2 text-sm font-bold">{{ package.total_sessions }} sesi latihan</span>
                        <span class="rounded-full bg-white/10 px-4 py-2 text-sm font-bold">Berlaku {{ package.duration_days }} hari</span>
                    </div>
                </div>
            </section>

            <section class="mx-auto grid max-w-5xl gap-8 px-5 py-12 lg:grid-cols-[1.1fr_.9fr] lg:py-16">
                <div class="space-y-6">
                    <div class="rounded-2xl border border-slate-200 bg-white p-6">
                        <h2 class="text-xl font-black">Yang kamu dapatkan</h2>
                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-xl bg-[#f5f8eb] p-4">
                                <i class="fa-solid fa-dumbbell text-xl text-[#789900]"></i>
                                <p class="mt-3 text-2xl font-black">{{ package.total_sessions }} sesi</p>
                                <p class="mt-1 text-sm text-slate-500">Total sesi latihan</p>
                            </div>
                            <div class="rounded-xl bg-[#f5f8eb] p-4">
                                <i class="fa-regular fa-calendar-check text-xl text-[#789900]"></i>
                                <p class="mt-3 text-2xl font-black">{{ package.duration_days }} hari</p>
                                <p class="mt-1 text-sm text-slate-500">Masa berlaku paket</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-6">
                        <h2 class="text-xl font-black">Trainer yang tersedia</h2>
                        <div v-if="package.trainers.length" class="mt-4 space-y-3">
                            <div v-for="trainer in package.trainers" :key="trainer.id" class="flex items-center gap-3 rounded-xl bg-slate-50 p-4">
                                <span class="grid h-11 w-11 place-items-center rounded-full bg-[#eaf3cd] text-[#6f9000]"><i class="fa-solid fa-user-tie"></i></span>
                                <div><p class="font-extrabold">{{ trainer.name }}</p><p class="text-sm text-slate-500">{{ trainer.specialty || 'Personal trainer' }}</p></div>
                            </div>
                        </div>
                        <p v-else class="mt-3 text-sm text-slate-500">Trainer belum ditentukan.</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-6">
                        <h2 class="text-xl font-black">Olahraga / fasilitas</h2>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span v-for="facility in package.facilities" :key="facility.id" class="rounded-full bg-[#f0f7d9] px-4 py-2 text-sm font-bold text-[#617d00]">{{ facility.name }}</span>
                        </div>
                    </div>
                </div>

                <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:sticky lg:top-8">
                    <p class="text-xs font-black uppercase tracking-[.16em] text-[#789900]">Harga paket</p>
                    <p class="mt-2 text-3xl font-black">{{ formatCurrency(package.price) }}</p>
                    <p class="mt-2 text-sm text-slate-500">Pembelian paket akan dicatat ke akun member dan masuk ke daftar transaksi.</p>

                    <form v-if="canPurchase" class="mt-7 space-y-5" @submit.prevent="submit">
                        <div v-if="package.trainers.length">
                            <label for="trainer_id" class="mb-2 block text-sm font-bold">Pilih trainer</label>
                            <select id="trainer_id" v-model="form.trainer_id" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-[#8eb900] focus:outline-none">
                                <option v-for="trainer in package.trainers" :key="trainer.id" :value="trainer.id">{{ trainer.name }}</option>
                            </select>
                            <p v-if="form.errors.trainer_id" class="mt-1 text-sm text-red-600">{{ form.errors.trainer_id }}</p>
                        </div>
                        <div v-if="package.facilities.length">
                            <label for="facility_id" class="mb-2 block text-sm font-bold">Pilih olahraga</label>
                            <select id="facility_id" v-model="form.facility_id" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-[#8eb900] focus:outline-none">
                                <option v-for="facility in package.facilities" :key="facility.id" :value="facility.id">{{ facility.name }}</option>
                            </select>
                            <p v-if="form.errors.facility_id" class="mt-1 text-sm text-red-600">{{ form.errors.facility_id }}</p>
                        </div>
                        <button type="submit" :disabled="form.processing || !package.trainers.length || !package.facilities.length" class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-[#a4da01] px-6 py-4 font-black text-[#001428] transition hover:bg-[#b8ec24] disabled:cursor-not-allowed disabled:opacity-50">
                            {{ form.processing ? 'Memproses...' : 'Beli Paket Trainer' }} <i class="fa-solid fa-arrow-right"></i>
                        </button>
                        <p class="text-xs leading-5 text-slate-500">Setelah dipesan, status paket menunggu pembayaran. Tanggal aktif dihitung otomatis setelah paket sebelumnya selesai.</p>
                    </form>
                    <div v-else class="mt-7 rounded-xl bg-[#f5f8eb] p-4">
                        <p class="text-sm font-bold">Masuk sebagai member untuk membeli paket ini.</p>
                        <Link href="/login" class="mt-4 inline-flex w-full items-center justify-center rounded-full bg-[#a4da01] px-5 py-3 font-black text-[#001428]">Login Member</Link>
                    </div>
                    <p v-if="form.errors" class="mt-3 text-sm text-red-600">{{ form.errors }}</p>
                </aside>
            </section>
        </main>
    </PublicLayout>
</template>
