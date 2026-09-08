<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, ChevronRight, Upload, X } from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';

interface Draft { zone_space_id: number; zone_id: number; booking_date: string; start_time: string; zone_name: string; space_name: string; rental_type: string; total_amount: number; guest_name?: string; payment_option?: 'full_payment' | 'half_payment' | 'pay_later'; }

const storageKey = 'sport-center-booking-draft';
const draft = ref<Draft | null>(null);
const guestName = ref('');
const paymentOption = ref<'full_payment' | 'half_payment' | 'pay_later'>('full_payment');
const paymentProof = ref<File | null>(null);
const paymentProofPreview = ref('');
const submitting = ref(false);
const error = ref('');
const amountToPay = computed(() => { const total = Number(draft.value?.total_amount ?? 0); return paymentOption.value === 'full_payment' ? total : paymentOption.value === 'half_payment' ? total / 2 : 0; });
const formatPrice = (price: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(price);

onMounted(() => {
    try {
        const saved = JSON.parse(localStorage.getItem(storageKey) ?? 'null');
        if (!saved?.zone_space_id || !saved?.booking_date || !saved?.start_time) { router.visit('/'); return; }
        draft.value = saved;
        guestName.value = saved.guest_name ?? '';
        paymentOption.value = saved.payment_option ?? 'full_payment';
    } catch { router.visit('/'); }
});

watch([guestName, paymentOption], () => {
    if (!draft.value) return;
    draft.value.guest_name = guestName.value;
    draft.value.payment_option = paymentOption.value;
    localStorage.setItem(storageKey, JSON.stringify(draft.value));
});

const handleProofUpload = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    if (!file || !file.type.match(/^image\/(jpeg|png|webp)$/i)) { error.value = 'Bukti pembayaran harus berupa JPG, PNG, atau WebP.'; return; }
    if (file.size > 5 * 1024 * 1024) { error.value = 'Ukuran bukti pembayaran maksimal 5 MB.'; return; }
    paymentProof.value = file; paymentProofPreview.value = URL.createObjectURL(file); error.value = '';
};
const removeProof = () => { if (paymentProofPreview.value) URL.revokeObjectURL(paymentProofPreview.value); paymentProof.value = null; paymentProofPreview.value = ''; };
const submitBooking = () => {
    if (!draft.value || submitting.value) return;
    if (!guestName.value.trim()) { error.value = 'Nama lengkap wajib diisi.'; return; }
    if (paymentOption.value !== 'pay_later' && !paymentProof.value) { error.value = 'Upload bukti pembayaran terlebih dahulu.'; return; }
    submitting.value = true; error.value = '';
    router.post('/booking', { zone_space_id: draft.value.zone_space_id, guest_name: guestName.value.trim(), booking_date: draft.value.booking_date, start_time: draft.value.start_time, payment_option: paymentOption.value, payment_proof: paymentProof.value }, {
        forceFormData: true,
        onError: (errors) => { error.value = Object.values(errors)[0] ?? 'Booking gagal dikirim. Silakan coba lagi.'; },
        onSuccess: () => { localStorage.removeItem(storageKey); },
        onFinish: () => { submitting.value = false; },
    });
};
</script>

<template>
    <Head title="Pembayaran Booking" />
    <div class="min-h-screen bg-[#f7f8f6] text-slate-900">
        <header class="sticky top-0 z-30 border-b border-white/60 bg-[#f7f8f6]/90 backdrop-blur-xl"><div class="mx-auto flex max-w-5xl items-center justify-between px-5 py-4 sm:px-8"><Link href="/" class="flex items-center gap-3"><div class="flex size-10 items-center justify-center rounded-2xl bg-slate-900 text-sm font-black text-white">SC</div><div><p class="text-sm font-bold">Sport Center</p><p class="text-[10px] font-medium uppercase tracking-[0.2em] text-slate-400">Booking</p></div></Link><span class="rounded-full bg-slate-900 px-4 py-2 text-xs font-bold text-white">Step 2 of 2</span></div></header>
        <main class="mx-auto max-w-5xl px-5 py-10 sm:px-8 lg:py-14">
            <div v-if="draft" class="grid gap-8 lg:grid-cols-[1fr_360px]">
                <div><Link href="/" class="mb-6 inline-flex items-center gap-2 text-sm font-bold text-slate-500 hover:text-slate-900"><ArrowLeft class="size-4" /> Kembali ke booking</Link><div class="mb-7"><p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Step 2</p><h1 class="mt-1 text-3xl font-black tracking-tight sm:text-4xl">Data pemesan & pembayaran</h1><p class="mt-3 text-sm leading-6 text-slate-500">Masukkan nama lengkap pemesan, lalu pilih pembayaran.</p></div>
                    <div class="space-y-5">
                        <div class="rounded-3xl border border-slate-200 bg-white p-5 sm:p-7"><label><span class="mb-2 block text-sm font-bold">Nama lengkap</span><input v-model="guestName" type="text" autocomplete="name" placeholder="Masukkan nama lengkap" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm outline-none focus:border-slate-900" /></label></div>
                        <div class="rounded-3xl border border-slate-200 bg-white p-5 sm:p-7"><p class="text-sm font-bold">Metode pembayaran</p><div class="mt-4 grid gap-3 sm:grid-cols-3"><button type="button" class="rounded-2xl border p-4 text-left" :class="paymentOption === 'full_payment' ? 'border-slate-900 bg-slate-50 ring-2 ring-slate-900/10' : 'border-slate-200'" @click="paymentOption = 'full_payment'"><p class="font-bold">Full payment</p><p class="mt-1 text-xs text-slate-500">Bayar 100%</p></button><button type="button" class="rounded-2xl border p-4 text-left" :class="paymentOption === 'half_payment' ? 'border-slate-900 bg-slate-50 ring-2 ring-slate-900/10' : 'border-slate-200'" @click="paymentOption = 'half_payment'"><p class="font-bold">Half payment</p><p class="mt-1 text-xs text-slate-500">Bayar 50% / DP</p></button><button type="button" class="rounded-2xl border p-4 text-left" :class="paymentOption === 'pay_later' ? 'border-slate-900 bg-slate-50 ring-2 ring-slate-900/10' : 'border-slate-200'" @click="paymentOption = 'pay_later'"><p class="font-bold">Bayar nanti</p><p class="mt-1 text-xs text-slate-500">Belum bayar</p></button></div>
                            <div v-if="paymentOption !== 'pay_later'" class="mt-6"><input id="payment-proof" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="handleProofUpload" /><label for="payment-proof" class="flex cursor-pointer flex-col items-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-8 text-center hover:border-slate-900"><Upload class="size-7 text-slate-400" /><p class="mt-3 text-sm font-bold">Upload bukti pembayaran</p><p class="mt-1 text-xs text-slate-400">JPG, PNG, WebP • maksimal 5 MB</p></label><div v-if="paymentProofPreview" class="relative mt-4 overflow-hidden rounded-2xl border"><img :src="paymentProofPreview" alt="Bukti pembayaran" class="max-h-80 w-full object-contain" /><button type="button" class="absolute right-3 top-3 flex size-9 items-center justify-center rounded-full bg-white shadow" @click="removeProof"><X class="size-4" /></button></div></div><p v-if="error" class="mt-5 rounded-xl bg-red-50 p-3 text-xs font-semibold text-red-600">{{ error }}</p></div>
                    </div>
                </div>
                <aside class="lg:sticky lg:top-24 lg:self-start"><div class="rounded-[2rem] bg-slate-900 text-white shadow-2xl"><div class="p-7 sm:p-8"><p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Booking summary</p><h2 class="mt-2 text-2xl font-black">{{ draft.space_name }}</h2><div class="my-7 space-y-4 border-y border-white/10 py-6 text-sm"><div class="flex justify-between gap-4"><span class="text-slate-500">Nama</span><span class="font-semibold text-right">{{ guestName || '—' }}</span></div><div class="flex justify-between gap-4"><span class="text-slate-500">Area</span><span class="font-semibold">{{ draft.zone_name }}</span></div><div class="flex justify-between gap-4"><span class="text-slate-500">Tanggal</span><span class="font-semibold">{{ draft.booking_date }}</span></div><div class="flex justify-between gap-4"><span class="text-slate-500">Jam</span><span class="font-semibold">{{ draft.start_time }}</span></div></div><div class="flex justify-between text-sm"><span class="text-slate-500">Total</span><b>{{ formatPrice(draft.total_amount) }}</b></div><div class="mt-2 flex justify-between text-sm"><span class="text-slate-500">Bayar sekarang</span><b>{{ formatPrice(amountToPay) }}</b></div><button type="button" :disabled="submitting" class="mt-7 flex w-full items-center justify-center gap-2 rounded-2xl bg-white px-5 py-4 text-sm font-bold text-slate-900 transition" :class="submitting ? 'cursor-not-allowed opacity-40' : 'hover:bg-slate-100'" @click="submitBooking">{{ submitting ? 'Mengirim...' : 'Konfirmasi Booking' }} <ChevronRight class="size-4" /></button><p class="mt-4 text-center text-[11px] leading-5 text-slate-500">Data tersimpan di browser agar tidak hilang saat refresh.</p></div></div></aside>
            </div>
        </main>
    </div>
</template>
