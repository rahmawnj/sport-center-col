<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CalendarDays, Check, CheckCircle2, Clock3, Copy, MapPin, Share2 } from '@lucide/vue';
import { computed, ref } from 'vue';

interface Booking {
    id: number;
    booking_code: string;
    guest_name: string;
    booking_status: 'pending' | 'approved' | 'rejected' | 'cancelled';
    payment_status: 'unpaid' | 'dp_paid' | 'fully_paid' | string;
    payment_method: string | null;
    total_amount: number;
    created_at: string | null;
    zone: string | null;
    space: string | null;
    start_time: string | null;
    end_time: string | null;
    payment_option: string | null;
    amount_paid: number;
}

const props = defineProps<{ booking: Booking }>();
const copied = ref(false);
const shared = ref(false);

const ticketUrl = computed(() => window.location.href);

const formatPrice = (price: number) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
}).format(price);

const formatDate = (value: string | null) => value
    ? new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }).format(new Date(value))
    : '—';

const formatDateTime = (value: string | null) => value
    ? new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(value))
    : '—';

const statusLabel = computed(() => ({
    pending: 'Menunggu persetujuan',
    approved: 'Booking disetujui',
    rejected: 'Booking ditolak',
    cancelled: 'Booking dibatalkan',
}[props.booking.booking_status] ?? props.booking.booking_status));

const statusClass = computed(() => ({
    pending: 'border-amber-200 bg-amber-50 text-amber-700',
    approved: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    rejected: 'border-red-200 bg-red-50 text-red-700',
    cancelled: 'border-slate-200 bg-slate-100 text-slate-600',
}[props.booking.booking_status] ?? 'border-slate-200 bg-slate-100 text-slate-600'));

const paymentLabel = computed(() => ({
    unpaid: 'Belum dibayar',
    dp_paid: 'DP 50% dibayar',
    fully_paid: 'Lunas',
}[props.booking.payment_status] ?? props.booking.payment_status));

const copyLink = async () => {
    try {
        await navigator.clipboard.writeText(ticketUrl.value);
        copied.value = true;
        setTimeout(() => copied.value = false, 2000);
    } catch {
        copied.value = false;
    }
};

const shareTicket = async () => {
    if (navigator.share) {
        await navigator.share({
            title: `Tiket Booking ${props.booking.booking_code}`,
            text: `Tiket booking ${props.booking.booking_code} - ${props.booking.guest_name}`,
            url: ticketUrl.value,
        });
        shared.value = true;
        setTimeout(() => shared.value = false, 2000);
        return;
    }

    await copyLink();
};
</script>

<template>
    <Head :title="`Tiket ${booking.booking_code}`" />

    <div class="min-h-screen bg-[#f7f8f6] px-4 py-8 text-slate-900 sm:px-6 lg:py-12">
        <div class="mx-auto max-w-2xl">
            <div class="mb-6 flex items-center justify-between gap-4">
                <Link href="/" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 transition hover:text-slate-900">
                    <ArrowLeft class="size-4" />
                    Booking lagi
                </Link>
                <div class="flex gap-2">
                    <button type="button" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold shadow-sm transition hover:border-slate-900" @click="copyLink">
                        <Check v-if="copied" class="size-4" />
                        <Copy v-else class="size-4" />
                        {{ copied ? 'Tersalin' : 'Copy link' }}
                    </button>
                    <button type="button" class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-slate-800" @click="shareTicket">
                        <Check v-if="shared" class="size-4" />
                        <Share2 v-else class="size-4" />
                        {{ shared ? 'Tershared' : 'Share' }}
                    </button>
                </div>
            </div>

            <div class="overflow-hidden rounded-[2rem] bg-white shadow-xl shadow-slate-200/60">
                <div class="bg-slate-950 px-6 py-8 text-white sm:px-10 sm:py-10">
                    <div class="flex items-start justify-between gap-6">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.25em] text-slate-500">Sport Center</p>
                            <h1 class="mt-2 text-3xl font-black tracking-tight">Booking Ticket</h1>
                            <p class="mt-2 text-sm text-slate-400">Simpan halaman ini untuk melihat booking kamu lagi.</p>
                        </div>
                        <div class="hidden size-12 items-center justify-center rounded-2xl bg-white/10 text-xs font-black sm:flex">SC</div>
                    </div>

                    <div class="mt-8 rounded-2xl border border-white/10 bg-white/5 p-5">
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">Nomor tiket</p>
                        <p class="mt-2 break-all text-2xl font-black tracking-wider sm:text-3xl">{{ booking.booking_code }}</p>
                        <div class="mt-4 inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-bold" :class="statusClass">
                            <CheckCircle2 class="size-3.5" />
                            {{ statusLabel }}
                        </div>
                    </div>
                </div>

                <div class="p-6 sm:p-10">
                    <div class="mb-8 flex items-center justify-between border-b border-dashed border-slate-200 pb-7">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Pemesan</p>
                            <p class="mt-1 text-xl font-black">{{ booking.guest_name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-400">Dibuat</p>
                            <p class="mt-1 text-sm font-semibold">{{ formatDateTime(booking.created_at) }}</p>
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="rounded-2xl bg-slate-50 p-5">
                            <MapPin class="size-5 text-slate-400" />
                            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-slate-400">Area & Space</p>
                            <p class="mt-1 font-black">{{ booking.zone || '—' }}</p>
                            <p class="text-sm text-slate-500">{{ booking.space || '—' }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-5">
                            <CalendarDays class="size-5 text-slate-400" />
                            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-slate-400">Tanggal</p>
                            <p class="mt-1 font-black">{{ formatDate(booking.start_time) }}</p>
                            <p class="text-sm text-slate-500">{{ booking.start_time ? new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit' }).format(new Date(booking.start_time)) : '—' }} - {{ booking.end_time ? new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit' }).format(new Date(booking.end_time)) : '—' }}</p>
                        </div>
                    </div>

                    <div class="mt-8 border-t border-slate-100 pt-7">
                        <div class="flex items-center justify-between py-2 text-sm">
                            <span class="text-slate-500">Total booking</span>
                            <span class="font-bold">{{ formatPrice(booking.total_amount) }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 text-sm">
                            <span class="text-slate-500">Pembayaran</span>
                            <span class="font-bold">{{ paymentLabel }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 text-sm">
                            <span class="text-slate-500">Dibayar</span>
                            <span class="font-bold">{{ formatPrice(booking.amount_paid) }}</span>
                        </div>
                        <div class="mt-4 flex items-center justify-between border-t border-dashed border-slate-200 pt-5">
                            <span class="text-sm font-bold">Status booking</span>
                            <span class="rounded-full px-3 py-1.5 text-xs font-bold" :class="statusClass">{{ statusLabel }}</span>
                        </div>
                    </div>

                    <div class="mt-8 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <div class="flex gap-3">
                            <Clock3 class="mt-0.5 size-5 shrink-0 text-slate-400" />
                            <div>
                                <p class="text-sm font-bold">Jangan sampai kehilangan tiket</p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">Gunakan tombol Copy link atau Share di atas. Link ini bisa dibuka kembali kapan saja untuk melihat nomor tiket dan status booking.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <p class="mt-6 text-center text-xs text-slate-400">Booking ID: {{ booking.id }} • {{ booking.booking_code }}</p>
        </div>
    </div>
</template>
