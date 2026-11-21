<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, Copy, Download, ShieldCheck } from '@lucide/vue';
import { computed, ref } from 'vue';
import QrcodeVue from 'qrcode.vue';
import MemberDashboardLayout from '@/layouts/member/MemberDashboardLayout.vue';

defineOptions({ layout: MemberDashboardLayout });

const props = defineProps<{
    member: { name: string; member_code: string | null; qr_value: string | null };
    membership: {
        package: string;
        facility: string | null;
        start_date: string | null;
        end_date: string | null;
    } | null;
}>();

const copied = ref(false);
const qrValue = computed(() => props.member.qr_value || '');
const active = computed(() => Boolean(props.membership && qrValue.value));

function dateLabel(value: string | null) {
    if (!value) return '—';
    return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }).format(new Date(value + 'T00:00:00'));
}

async function copyCode() {
    if (!props.member.member_code) return;
    try {
        await navigator.clipboard.writeText(props.member.member_code);
        copied.value = true;
        window.setTimeout(() => (copied.value = false), 1800);
    } catch {
        copied.value = false;
    }
}

function downloadQr() {
    const svg = document.querySelector('#member-access-qr svg') as SVGElement | null;
    if (!svg) return;
    const source = new XMLSerializer().serializeToString(svg);
    const blob = new Blob([source], { type: 'image/svg+xml;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'sport-center-member-qr.svg';
    link.click();
    URL.revokeObjectURL(url);
}
</script>

<template>
    <Head title="QR Access Member" />
    <div class="mx-auto max-w-4xl space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[.18em] text-lime-700">Akses masuk</p>
                <h1 class="mt-2 text-2xl font-bold text-slate-900">QR Access</h1>
                <p class="mt-1 text-sm text-slate-500">Tunjukkan kode ini ke kamera tripod untuk check-in.</p>
            </div>
            <Link href="/member/dashboard" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <ArrowLeft class="size-4" /> Dashboard
            </Link>
        </header>

        <section class="grid gap-5 lg:grid-cols-[1fr_1.1fr]">
            <div class="relative overflow-hidden rounded-3xl bg-[#001428] p-6 text-white shadow-xl sm:p-8">
                <div class="pointer-events-none absolute -right-16 -top-16 size-56 rounded-full bg-lime-400/15 blur-3xl" />
                <div class="relative flex items-center justify-between gap-3">
                    <div><p class="text-xs font-bold uppercase tracking-[.18em] text-lime-300">Sport Center</p><h2 class="mt-2 text-xl font-bold">Digital Member Pass</h2></div>
                    <ShieldCheck class="size-8 text-lime-300" />
                </div>
                <div class="relative mt-7 rounded-3xl bg-white p-4 text-center shadow-2xl sm:p-6">
                    <div v-if="active" id="member-access-qr" class="mx-auto flex aspect-square w-full max-w-[300px] items-center justify-center">
                        <QrcodeVue :value="qrValue" :size="300" level="H" render-as="svg" />
                    </div>
                    <div v-else class="flex aspect-square w-full max-w-[300px] flex-col items-center justify-center gap-3 text-slate-500">
                        <ShieldCheck class="size-12 text-slate-300" /><p class="font-semibold">QR belum tersedia</p>
                        <p class="max-w-xs text-xs leading-5">QR Access hanya bisa digunakan ketika akun memiliki membership aktif.</p>
                    </div>
                    <p class="mt-3 text-[11px] font-bold uppercase tracking-[.2em] text-slate-500">Scan untuk akses member</p>
                </div>
                <div class="relative mt-6 flex flex-wrap items-center justify-between gap-3">
                    <div><p class="text-xs text-slate-300">Nama member</p><p class="mt-1 text-lg font-bold">{{ member.name }}</p></div>
                    <div class="text-right"><p class="text-xs text-slate-300">Status akses</p>
                        <span v-if="active" class="mt-1 inline-flex items-center gap-1.5 rounded-full bg-lime-300/15 px-3 py-1.5 text-xs font-bold text-lime-200"><CheckCircle2 class="size-3.5" /> AKTIF</span>
                        <span v-else class="mt-1 inline-flex rounded-full bg-white/10 px-3 py-1.5 text-xs font-bold text-slate-200">TIDAK AKTIF</span>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <h3 class="font-bold text-slate-900">Informasi kartu</h3>
                    <p class="mt-1 text-sm text-slate-500">Gunakan QR ini saat melakukan scan di perangkat pintu masuk.</p>
                    <div class="mt-5 rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-medium text-slate-500">Kode member</p>
                        <p class="mt-2 break-all font-mono text-base font-bold tracking-wide text-slate-900">{{ member.member_code || 'Belum tersedia' }}</p>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" :disabled="!member.member_code" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40" @click="copyCode"><Copy class="size-4" /> {{ copied ? 'Tersalin' : 'Salin kode' }}</button>
                        <button type="button" :disabled="!active" class="inline-flex items-center gap-2 rounded-xl bg-[#a4da01] px-4 py-2.5 text-sm font-bold text-[#001428] hover:bg-[#b8ec24] disabled:cursor-not-allowed disabled:opacity-40" @click="downloadQr"><Download class="size-4" /> Unduh QR</button>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex items-center justify-between gap-3"><h3 class="font-bold text-slate-900">Membership</h3><span class="rounded-full px-3 py-1 text-xs font-bold" :class="active ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">{{ active ? 'Aktif' : 'Tidak aktif' }}</span></div>
                    <template v-if="membership">
                        <p class="mt-3 text-lg font-bold text-slate-900">{{ membership.package }}</p><p class="mt-1 text-sm text-slate-500">{{ membership.facility || 'Sport Center' }}</p>
                        <div class="mt-4 grid grid-cols-2 gap-3"><div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Mulai</p><p class="mt-1 text-sm font-semibold text-slate-800">{{ dateLabel(membership.start_date) }}</p></div><div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Berakhir</p><p class="mt-1 text-sm font-semibold text-slate-800">{{ dateLabel(membership.end_date) }}</p></div></div>
                    </template>
                    <p v-else class="mt-3 text-sm leading-6 text-slate-500">Belum ada membership yang aktif hari ini. QR tidak dapat digunakan untuk membuka akses sampai membership aktif.</p>
                </div>
                <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm leading-6 text-sky-900"><strong>Tips scan tripod:</strong> naikkan kecerahan layar, tampilkan QR secara penuh, dan arahkan layar ke kamera dari jarak yang disarankan petugas.</div>
            </div>
        </section>
    </div>
</template>
