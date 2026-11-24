<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';

type ExampleKey = 'member' | 'qrcode' | 'face';

const examples: Record<ExampleKey, { label: string; description: string; payload: Record<string, unknown> }> = {
    member: {
        label: 'Scan member code',
        description: 'Memeriksa membership aktif milik member pada fasilitas perangkat.',
        payload: { device_id: 'DEVICE_CODE', device_type: 'member', member_code: 'MEMBER_CODE' },
    },
    qrcode: {
        label: 'Scan QR / booking',
        description: 'Memeriksa kode QR transaksi atau booking. Format booking publik: BK-000001.',
        payload: { device_id: 'DEVICE_CODE', device_type: 'qrcode', scan_code: 'BK-000001' },
    },
    face: {
        label: 'Face device callback',
        description: 'Simulasi callback VerifyPush dari perangkat face recognition.',
        payload: {
            operator: 'VerifyPush',
            info: { DeviceID: 'DEVICE_CODE', IdCard: 'MEMBER_CODE', VerifyStatus: '1', Similarity1: '95.5' },
        },
    },
};

const selected = ref<ExampleKey>('member');
const payload = ref(JSON.stringify(examples.member.payload, null, 2));
const loading = ref(false);
const httpStatus = ref<number | null>(null);
const responseBody = ref('');
const responseHeaders = ref('');
const elapsed = ref<number | null>(null);
const errorMessage = ref('');
const copied = ref(false);

const parsedPayload = computed(() => {
    try {
        return { valid: true, value: JSON.parse(payload.value) as Record<string, unknown>, message: '' };
    } catch (error) {
        return { valid: false, value: null, message: error instanceof Error ? error.message : 'JSON tidak valid.' };
    }
});

const curlCommand = computed(() => [
    "curl -X POST '" + window.location.origin + "/api/device/access' \\",
    "  -H 'Accept: application/json' \\",
    "  -H 'Content-Type: application/json' \\",
    "  -d '" + payload.value.replace(/'/g, "'\\''") + "'",
].join('\n'));

function chooseExample(key: ExampleKey) {
    selected.value = key;
    payload.value = JSON.stringify(examples[key].payload, null, 2);
    responseBody.value = '';
    responseHeaders.value = '';
    httpStatus.value = null;
    elapsed.value = null;
    errorMessage.value = '';
}

async function sendRequest() {
    errorMessage.value = '';
    responseBody.value = '';
    responseHeaders.value = '';
    httpStatus.value = null;
    elapsed.value = null;

    if (!parsedPayload.value.valid) {
        errorMessage.value = 'JSON tidak valid: ' + parsedPayload.value.message;
        return;
    }

    loading.value = true;
    const startedAt = performance.now();

    try {
        const response = await fetch('/api/device/access', {
            method: 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify(parsedPayload.value.value),
        });

        elapsed.value = Math.round(performance.now() - startedAt);
        httpStatus.value = response.status;
        responseHeaders.value = [
            'content-type: ' + (response.headers.get('content-type') ?? 'unknown'),
            'retry-after: ' + (response.headers.get('retry-after') ?? '—'),
        ].join('\n');

        const raw = await response.text();
        try {
            responseBody.value = JSON.stringify(JSON.parse(raw), null, 2);
        } catch {
            responseBody.value = raw || '(response kosong)';
        }
    } catch (error) {
        elapsed.value = Math.round(performance.now() - startedAt);
        errorMessage.value = error instanceof Error ? error.message : 'Request gagal dikirim.';
    } finally {
        loading.value = false;
    }
}

async function copyCurl() {
    try {
        await navigator.clipboard.writeText(curlCommand.value);
        copied.value = true;
        window.setTimeout(() => (copied.value = false), 1800);
    } catch {
        copied.value = false;
        errorMessage.value = 'Browser tidak mengizinkan clipboard. Salin perintah cURL secara manual.';
    }
}
</script>

<template>
    <Head title="API Docs & Playground" />
    <main class="min-h-screen bg-slate-950 text-slate-100">
        <div class="mx-auto max-w-7xl space-y-7 px-4 py-8 sm:px-6 lg:px-8">
            <header class="flex flex-col justify-between gap-4 border-b border-white/10 pb-6 sm:flex-row sm:items-end">
                <div>
                    <a href="/dashboard" class="mb-4 inline-flex text-sm text-slate-400 transition hover:text-white">← Kembali ke dashboard</a>
                    <div class="mb-2 flex items-center gap-2">
                        <span class="rounded-full border border-cyan-400/30 bg-cyan-400/10 px-2.5 py-1 text-xs font-semibold tracking-wide text-cyan-300">DEVELOPER TOOLS</span>
                        <span class="text-xs text-slate-500">Sport Center API</span>
                    </div>
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">API Docs <span class="text-cyan-400">& Playground</span></h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-400">Dokumentasi dan tempat mencoba endpoint gate secara langsung. Kirim payload, lihat HTTP status, waktu respons, dan JSON response dari server.</p>
                </div>
                <div class="rounded-xl border border-white/10 bg-white/[0.04] px-4 py-3">
                    <p class="text-xs text-slate-500">Endpoint utama</p>
                    <p class="mt-1 font-mono text-sm text-cyan-300">POST /api/device/access</p>
                </div>
            </header>

            <section class="grid gap-4 md:grid-cols-3">
                <article class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Method</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-300">POST</p>
                    <p class="mt-1 text-sm text-slate-400">JSON request body</p>
                </article>
                <article class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Rate limit</p>
                    <p class="mt-2 text-2xl font-bold">60 <span class="text-base font-medium text-slate-400">requests/min</span></p>
                    <p class="mt-1 text-sm text-slate-400">Sesuai throttle endpoint</p>
                </article>
                <article class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Response format</p>
                    <p class="mt-2 text-2xl font-bold">JSON</p>
                    <p class="mt-1 text-sm text-slate-400">status, allowed, message, data</p>
                </article>
            </section>

            <section class="grid items-start gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(380px,0.9fr)]">
                <div class="space-y-5">
                    <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-5 sm:p-6">
                        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold">Request playground</h2>
                                <p class="mt-1 text-sm text-slate-400">Pilih skenario, sesuaikan JSON, lalu kirim.</p>
                            </div>
                            <span class="rounded-lg bg-emerald-400/10 px-3 py-1.5 text-xs font-semibold text-emerald-300">LIVE REQUEST</span>
                        </div>

                        <div class="mb-5 grid gap-2 sm:grid-cols-3">
                            <button v-for="(item, key) in examples" :key="key" type="button" class="rounded-xl border p-3 text-left transition" :class="selected === key ? 'border-cyan-400/60 bg-cyan-400/10 text-cyan-200' : 'border-white/10 bg-slate-900/70 text-slate-300 hover:border-white/25'" @click="chooseExample(key as ExampleKey)">
                                <span class="block text-sm font-semibold">{{ item.label }}</span>
                                <span class="mt-1 block text-xs text-slate-500">{{ key === 'member' ? 'Member code' : key === 'qrcode' ? 'QR booking/tiket' : 'Face callback' }}</span>
                            </button>
                        </div>

                        <p class="mb-3 text-sm leading-6 text-slate-400">{{ examples[selected].description }}</p>
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <label for="request-json" class="text-sm font-semibold">Request body</label>
                            <span class="text-xs" :class="parsedPayload.valid ? 'text-emerald-300' : 'text-rose-300'">{{ parsedPayload.valid ? 'JSON valid' : 'JSON tidak valid' }}</span>
                        </div>
                        <textarea id="request-json" v-model="payload" spellcheck="false" class="min-h-64 w-full resize-y rounded-xl border border-white/10 bg-slate-950 p-4 font-mono text-sm leading-6 text-cyan-100 outline-none transition focus:border-cyan-400/60 focus:ring-2 focus:ring-cyan-400/10"></textarea>
                        <p v-if="!parsedPayload.valid" class="mt-2 text-xs text-rose-300">{{ parsedPayload.message }}</p>

                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <button type="button" :disabled="loading || !parsedPayload.valid" class="inline-flex items-center justify-center rounded-xl bg-cyan-400 px-5 py-3 text-sm font-bold text-slate-950 transition hover:bg-cyan-300 disabled:cursor-not-allowed disabled:opacity-50" @click="sendRequest">
                                <span v-if="loading">Mengirim request…</span>
                                <span v-else>▶ Kirim request</span>
                            </button>
                            <button type="button" class="rounded-xl border border-white/15 px-4 py-3 text-sm font-semibold text-slate-300 transition hover:bg-white/5" @click="chooseExample(selected)">Reset contoh</button>
                        </div>
                        <p class="mt-3 text-xs leading-5 text-amber-200/80">Request dikirim ke server aplikasi yang sedang dibuka. Gunakan data uji; jangan gunakan QR/member code pelanggan sungguhan tanpa izin.</p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-5 sm:p-6">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h2 class="text-lg font-semibold">cURL</h2>
                            <button type="button" class="rounded-lg border border-white/10 px-3 py-1.5 text-xs font-semibold text-slate-300 hover:bg-white/5" @click="copyCurl">{{ copied ? 'Tersalin ✓' : 'Salin cURL' }}</button>
                        </div>
                        <pre class="overflow-x-auto rounded-xl bg-slate-950 p-4 text-xs leading-6 text-emerald-200"><code>{{ curlCommand }}</code></pre>
                    </div>
                </div>

                <div class="space-y-5">
                    <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.04]">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 px-5 py-4">
                            <div>
                                <h2 class="font-semibold">Response</h2>
                                <p class="mt-1 text-xs text-slate-500">Hasil aktual dari endpoint</p>
                            </div>
                            <span v-if="httpStatus !== null" class="rounded-lg px-3 py-1.5 font-mono text-xs font-bold" :class="httpStatus >= 200 && httpStatus < 300 ? 'bg-emerald-400/10 text-emerald-300' : 'bg-rose-400/10 text-rose-300'">HTTP {{ httpStatus }}</span>
                            <span v-else class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs text-slate-400">Belum dikirim</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3 border-b border-white/10 p-5">
                            <div class="rounded-xl bg-slate-950/80 p-3">
                                <p class="text-xs text-slate-500">Response time</p>
                                <p class="mt-1 text-xl font-bold">{{ elapsed === null ? '—' : elapsed + ' ms' }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-950/80 p-3">
                                <p class="text-xs text-slate-500">Decision</p>
                                <p class="mt-1 text-xl font-bold" :class="responseBody.includes('&quot;allowed&quot;: true') ? 'text-emerald-300' : responseBody.includes('&quot;allowed&quot;: false') ? 'text-rose-300' : 'text-slate-300'">
                                    {{ responseBody.includes('"allowed": true') ? 'ALLOWED' : responseBody.includes('"allowed": false') ? 'DENIED' : '—' }}
                                </p>
                            </div>
                        </div>
                        <div class="p-5">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Response body</p>
                            <pre class="min-h-64 overflow-auto rounded-xl bg-slate-950 p-4 text-xs leading-6 text-slate-200"><code>{{ responseBody || 'Belum ada response. Kirim request untuk melihat hasilnya.' }}</code></pre>
                            <div v-if="errorMessage" class="mt-3 rounded-xl border border-rose-400/20 bg-rose-400/10 p-3 text-sm text-rose-200">{{ errorMessage }}</div>
                            <div v-if="responseHeaders" class="mt-4">
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Headers penting</p>
                                <pre class="rounded-xl bg-slate-950 p-3 text-xs leading-5 text-slate-400">{{ responseHeaders }}</pre>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-cyan-400/20 bg-cyan-400/[0.06] p-5">
                        <h3 class="font-semibold text-cyan-200">Cara membaca response</h3>
                        <ul class="mt-3 space-y-2 text-sm leading-6 text-slate-300">
                            <li><span class="font-mono text-cyan-200">HTTP 200</span> + <span class="font-mono">allowed: true</span> berarti akses diberikan.</li>
                            <li><span class="font-mono text-cyan-200">HTTP 200</span> + <span class="font-mono">allowed: false</span> berarti request diproses tetapi akses ditolak oleh aturan gate.</li>
                            <li><span class="font-mono text-amber-200">HTTP 422</span> berarti payload tidak lolos validasi.</li>
                            <li><span class="font-mono text-amber-200">HTTP 429</span> berarti terkena rate limit.</li>
                            <li>Network error berarti browser tidak berhasil menjangkau endpoint.</li>
                        </ul>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-white/10 bg-white/[0.04] p-5 sm:p-6">
                <h2 class="text-lg font-semibold">Dokumentasi endpoint</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[620px] text-left text-sm">
                        <thead class="text-xs uppercase tracking-wider text-slate-500">
                            <tr class="border-b border-white/10"><th class="pb-3 pr-4">Field</th><th class="pb-3 pr-4">Tipe</th><th class="pb-3">Keterangan</th></tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-slate-300">
                            <tr><td class="py-3 pr-4 font-mono text-cyan-200">device_id</td><td class="py-3 pr-4">string</td><td class="py-3">Kode perangkat dari data Devices (device_code). ID numerik juga diterima.</td></tr>
                            <tr><td class="py-3 pr-4 font-mono text-cyan-200">device_type</td><td class="py-3 pr-4">member | qrcode</td><td class="py-3">Jenis identifikasi untuk request API biasa.</td></tr>
                            <tr><td class="py-3 pr-4 font-mono text-cyan-200">member_code</td><td class="py-3 pr-4">string</td><td class="py-3">Wajib jika device_type = member.</td></tr>
                            <tr><td class="py-3 pr-4 font-mono text-cyan-200">scan_code</td><td class="py-3 pr-4">string</td><td class="py-3">Wajib jika device_type = qrcode. Contoh booking publik: BK-000001.</td></tr>
                            <tr><td class="py-3 pr-4 font-mono text-cyan-200">operator</td><td class="py-3 pr-4">string</td><td class="py-3">Gunakan VerifyPush untuk callback face device.</td></tr>
                            <tr><td class="py-3 pr-4 font-mono text-cyan-200">info</td><td class="py-3 pr-4">object</td><td class="py-3">Berisi DeviceID, IdCard, VerifyStatus, Similarity1 untuk face callback.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-5 grid gap-3 md:grid-cols-2">
                    <div class="rounded-xl border border-white/10 bg-slate-950/60 p-4">
                        <h3 class="text-sm font-semibold text-emerald-300">Contoh akses diterima</h3>
                        <pre class="mt-2 overflow-x-auto text-xs leading-5 text-slate-300"><code>{
  "status": "success",
  "allowed": true,
  "message": "Akses diberikan.",
  "data": { "type": "member" }
}</code></pre>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-slate-950/60 p-4">
                        <h3 class="text-sm font-semibold text-rose-300">Contoh akses ditolak</h3>
                        <pre class="mt-2 overflow-x-auto text-xs leading-5 text-slate-300"><code>{
  "status": "success",
  "allowed": false,
  "message": "Membership tidak aktif...",
  "data": { "type": "member" }
}</code></pre>
                    </div>
                </div>
                <p class="mt-5 rounded-xl border border-amber-400/20 bg-amber-400/[0.06] p-4 text-sm leading-6 text-amber-100/90">
                    <strong>Status implementasi saat ini:</strong> endpoint yang ada menerima member code, QR booking/tiket, dan callback face device. Pengujian paket trainer dan pendaftaran class belum dapat dianggap didukung sampai logic aksesnya ditambahkan ke backend. Playground ini mengirim request nyata ke endpoint saat ini, bukan membuat simulasi hasil.
                </p>
            </section>

            <footer class="border-t border-white/10 pt-5 text-xs text-slate-500">
                Sport Center · Internal API documentation · Endpoint: /api/device/access
            </footer>
        </div>
    </main>
</template>
