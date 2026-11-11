<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import MemberDashboardLayout from '@/layouts/member/MemberDashboardLayout.vue';

defineOptions({ layout: MemberDashboardLayout });

type PurchasedClass = {
    id: number;
    session_date: string;
    class_name: string;
    description: string | null;
    facility: string | null;
    trainer: string | null;
    start_time: string;
    end_time: string;
    price: number;
    status: string;
    payment_method: string | null;
    invoice_number: string | null;
};

defineProps<{ schedules: PurchasedClass[] }>();

const money = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const dateLabel = (value: string) =>
    new Date(value + 'T00:00:00').toLocaleDateString('id-ID', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });

const statusLabel = (status: string) => ({
    confirmed: 'Terkonfirmasi',
    attended: 'Sudah diikuti',
    no_show: 'Tidak hadir',
    pending: 'Menunggu konfirmasi',
}[status] ?? status);

const paymentMethodLabel = (method: string | null) => ({
    cash: 'Tunai',
    bank_transfer: 'Transfer bank',
}[method ?? ''] ?? 'Lunas');
</script>

<template>
    <Head title="Kelas Saya" />

    <div class="space-y-6">
        <header>
            <p class="text-xs font-bold uppercase tracking-[.18em] text-lime-700">Aktivitas olahraga</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">Kelas Saya</h1>
            <p class="mt-1 text-sm text-slate-500">Daftar kelas yang sudah kamu beli dan lunasi.</p>
        </header>

        <div v-if="schedules.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="item in schedules"
                :key="item.id"
                class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-lime-700">{{ item.facility || 'Sport Center' }}</p>
                        <h2 class="mt-1 text-xl font-bold text-slate-900">{{ item.class_name }}</h2>
                    </div>
                    <span class="shrink-0 rounded-full bg-lime-50 px-3 py-1 text-xs font-bold text-lime-800">
                        {{ statusLabel(item.status) }}
                    </span>
                </div>

                <p v-if="item.description" class="mt-3 line-clamp-2 text-sm text-slate-500">{{ item.description }}</p>

                <div class="mt-4 space-y-2 text-sm text-slate-600">
                    <p>📅 {{ dateLabel(item.session_date) }}</p>
                    <p>🕒 {{ item.start_time }}–{{ item.end_time }}</p>
                    <p>🏋️ {{ item.trainer || 'Trainer akan diumumkan' }}</p>
                    <p>💳 {{ paymentMethodLabel(item.payment_method) }}</p>
                </div>

                <div class="mt-auto border-t border-slate-100 pt-4">
                    <p class="text-lg font-extrabold text-slate-900">{{ money(item.price) }}</p>
                    <p v-if="item.invoice_number" class="mt-1 text-xs text-slate-400">Invoice {{ item.invoice_number }}</p>
                </div>
            </article>
        </div>

        <div v-else class="rounded-2xl border border-slate-200 bg-white px-6 py-16 text-center">
            <div class="text-3xl">🧘</div>
            <h2 class="mt-3 font-bold text-slate-900">Belum ada kelas yang dibeli</h2>
            <p class="mt-1 text-sm text-slate-500">Kelas yang sudah dibeli dan lunas akan muncul di halaman ini.</p>
            <a href="/kelas" class="mt-5 inline-flex rounded-full bg-[#a4da01] px-5 py-3 text-sm font-bold text-[#001428]">
                Cari kelas
            </a>
        </div>
    </div>
</template>
