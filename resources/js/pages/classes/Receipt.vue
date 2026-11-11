<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ArrowLeft, Check, Clock, Copy, Share2 } from '@lucide/vue';
import { ref } from 'vue';
import PublicLayout from '@/layouts/PublicLayout.vue';

type ReceiptData = {
    code: string;
    customerName: string | null;
    customerEmail: string | null;
    customerPhone: string | null;
    facility: string;
    className: string;
    description: string | null;
    trainer: string | null;
    date: string | null;
    day: string;
    startTime: string;
    endTime: string;
    duration: number;
    participantStatus: string;
    paymentMethod: string;
    paymentStatus: string;
    total: number;
};

const props = defineProps<{ receipt: ReceiptData; receiptUrl: string }>();
const feedback = ref('');

function formatIDR(value: number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}

function label(value: string) {
    const labels: Record<string, string> = {
        pending: 'Menunggu pembayaran',
        confirmed: 'Terkonfirmasi',
        attended: 'Sudah hadir',
        no_show: 'Tidak hadir',
        cancelled: 'Dibatalkan',
        unpaid: 'Belum dibayar',
        fully_paid: 'Lunas',
        cash: 'Tunai di tempat',
        bank_transfer: 'Transfer bank',
    };

    return labels[value] ?? value.replaceAll('_', ' ');
}

async function copyLink() {
    try {
        await navigator.clipboard.writeText(props.receiptUrl);
        feedback.value = 'Tautan bukti pendaftaran berhasil disalin.';
    } catch {
        feedback.value = 'Salin URL dari bilah alamat browser untuk membagikan bukti pendaftaran.';
    }
}

async function shareLink() {
    if (typeof navigator.share === 'function') {
        try {
            await navigator.share({
                title: `Bukti pendaftaran kelas ${props.receipt.code}`,
                text: `Pendaftaran ${props.receipt.className}`,
                url: props.receiptUrl,
            });
            feedback.value = 'Bukti pendaftaran siap dibagikan.';
        } catch (error) {
            if (error instanceof Error && error.name !== 'AbortError') await copyLink();
        }
        return;
    }

    await copyLink();
}
</script>

<template>
    <Head :title="`Bukti Pendaftaran ${receipt.code}`" />
    <PublicLayout>
        <main class="min-h-screen bg-slate-50 px-4 py-8 text-slate-900 sm:py-12">
            <div class="mx-auto max-w-3xl">
                <div class="mb-6 text-center">
                    <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-lime-100 text-lime-800">
                        <Check class="size-8" :stroke-width="3" />
                    </div>
                    <p class="mt-4 text-sm font-bold uppercase tracking-[0.18em] text-lime-700">Pendaftaran berhasil dikirim</p>
                    <h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">Bukti pendaftaran kelas</h1>
                    <p class="mt-3 text-sm leading-6 text-slate-500">Simpan atau bagikan tautan ini untuk membuka bukti pendaftaran kembali tanpa perlu login.</p>
                </div>

                <article class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_70px_rgba(15,23,42,0.10)]">
                    <header class="border-b border-lime-100 bg-white px-6 py-6 text-slate-900 sm:px-8">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-lime-700">Registration reference</p>
                                <h2 class="mt-2 text-2xl font-bold">{{ receipt.code }}</h2>
                                <p class="mt-2 text-sm text-slate-500">Status peserta:
                                    <span class="font-semibold text-lime-700">{{ label(receipt.participantStatus) }}</span>
                                </p>
                            </div>
                            <div class="rounded-xl border border-lime-100 bg-lime-50 px-4 py-3">
                                <p class="text-xs text-slate-500">Total pendaftaran</p>
                                <p class="mt-1 text-xl font-bold text-lime-800">{{ formatIDR(receipt.total) }}</p>
                            </div>
                        </div>
                    </header>

                    <div class="space-y-7 p-6 sm:p-8">
                        <section>
                            <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">Detail kelas</h3>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <p class="text-sm text-slate-500">Olahraga / fasilitas</p>
                                    <p class="mt-1 font-semibold">{{ receipt.facility }}</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <p class="text-sm text-slate-500">Nama kelas</p>
                                    <p class="mt-1 font-semibold">{{ receipt.className }}</p>
                                    <p v-if="receipt.description" class="mt-1 text-sm leading-5 text-slate-500">{{ receipt.description }}</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <p class="text-sm text-slate-500">Tanggal sesi</p>
                                    <p class="mt-1 font-semibold">{{ receipt.date || '—' }}</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <p class="text-sm text-slate-500">Waktu kelas</p>
                                    <p class="mt-1 font-semibold">{{ receipt.startTime }}–{{ receipt.endTime }}</p>
                                    <p v-if="receipt.duration" class="mt-1 text-sm text-slate-500">{{ receipt.duration }} menit</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-4 sm:col-span-2">
                                    <p class="text-sm text-slate-500">Instruktur</p>
                                    <p class="mt-1 font-semibold">{{ receipt.trainer || 'Akan diumumkan' }}</p>
                                </div>
                            </div>
                        </section>

                        <section class="border-t border-dashed border-slate-200 pt-6">
                            <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">Informasi peserta</h3>
                            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div><dt class="text-sm text-slate-500">Nama</dt><dd class="mt-1 font-semibold">{{ receipt.customerName || '—' }}</dd></div>
                                <div><dt class="text-sm text-slate-500">Email</dt><dd class="mt-1 break-all font-semibold">{{ receipt.customerEmail || '—' }}</dd></div>
                                <div><dt class="text-sm text-slate-500">Nomor HP</dt><dd class="mt-1 font-semibold">{{ receipt.customerPhone || '—' }}</dd></div>
                            </dl>
                        </section>

                        <section class="rounded-2xl border border-lime-200 bg-lime-50 p-4 sm:p-5">
                            <div class="flex items-start gap-3">
                                <Clock class="mt-0.5 size-5 shrink-0 text-lime-800" />
                                <div>
                                    <p class="font-semibold text-slate-900">Informasi pembayaran</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-600">Metode: {{ label(receipt.paymentMethod) }}. Status pembayaran: <strong>{{ label(receipt.paymentStatus) }}</strong>. Pendaftaran belum dianggap lunas sebelum pembayaran dikonfirmasi oleh pengelola.</p>
                                </div>
                            </div>
                        </section>

                        <section class="border-t border-slate-100 pt-6">
                            <p class="text-sm font-semibold text-slate-800">Tautan bukti pendaftaran</p>
                            <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                                <input readonly :value="receiptUrl" aria-label="URL bukti pendaftaran kelas" class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-600" />
                                <button type="button" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-[#a4da01] px-5 py-3 text-sm font-bold text-[#001428] transition hover:bg-lime-400" @click="copyLink">
                                    <Copy class="size-4" /> Salin tautan
                                </button>
                                <button type="button" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50" @click="shareLink">
                                    <Share2 class="size-4" /> Bagikan
                                </button>
                            </div>
                            <p v-if="feedback" role="status" class="mt-3 text-sm font-medium text-lime-800">{{ feedback }}</p>
                            <p class="mt-2 text-xs leading-5 text-slate-500">Siapa pun yang memiliki tautan ini dapat melihat bukti pendaftaran dan informasi peserta. Bagikan hanya kepada orang yang kamu percaya.</p>
                        </section>
                    </div>
                </article>

                <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                    <a href="/" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100"><ArrowLeft class="mr-2 size-4" /> Kembali ke beranda</a>
                    <a href="/kelas" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-[#a4da01] px-6 py-3 text-sm font-bold text-[#001428] hover:bg-lime-400">Daftar kelas lain</a>
                </div>
            </div>
        </main>
    </PublicLayout>
</template>
