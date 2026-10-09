<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

type Rate = {
    id: number;
    rental_type: string;
    price: number;
    unit_type: 'per_hour' | 'per_visit' | 'per_session';
    min_booking_duration: number;
};
type Space = {
    id: number;
    name: string;
    capacity: number;
    status: string;
    facilities: string[];
    rates: Rate[];
};
type Zone = { id: number; name: string; pricing_model: string; spaces: Space[] };
type AddOn = { id: number; name: string; price: number; stock: number };
type Slot = { start_time: string; end_time: string; price: number };

const props = defineProps<{
    zones: Zone[];
    addOns: AddOn[];
    initialZoneId: number | null;
    initialSpaceId: number | null;
    successMessage?: string | null;
    bookingReference?: string | null;
}>();

const step = ref(1);
const zoneId = ref<number | null>(props.initialZoneId);
const spaceId = ref<number | null>(null);
const rateId = ref<number | null>(null);
const date = ref('');
const duration = ref(1);
const slots = ref<Slot[]>([]);
const selectedSlot = ref<Slot | null>(null);
const loadingSlots = ref(false);
const slotError = ref('');
const addOnQuantities = ref<Record<number, number>>({});
const today = (() => {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
})();

const form = useForm({
    space_id: null as number | null,
    rate_id: null as number | null,
    date: '',
    start_time: '',
    duration: 1,
    guest_name: '',
    guest_email: '',
    guest_phone: '',
    add_ons: [] as { id: number; quantity: number }[],
});

const selectedZone = computed(() => props.zones.find((zone) => zone.id === zoneId.value) ?? null);
const availableSpaces = computed(() => selectedZone.value?.spaces.filter((space) => space.status === 'available' && space.rates.length) ?? []);
const selectedSpace = computed(() => selectedZone.value?.spaces.find((space) => space.id === spaceId.value) ?? null);
const selectedRate = computed(() => selectedSpace.value?.rates.find((rate) => rate.id === rateId.value) ?? null);
const isHourly = computed(() => selectedRate.value?.unit_type === 'per_hour');
const currentPrice = computed(() => selectedSlot.value?.price ?? (selectedRate.value ? selectedRate.value.price * (isHourly.value ? duration.value : 1) : 0));
const addOnTotal = computed(() => props.addOns.reduce((sum, item) => sum + item.price * (addOnQuantities.value[item.id] ?? 0), 0));
const total = computed(() => currentPrice.value + addOnTotal.value);
const selectedAddOns = computed(() => props.addOns
    .filter((item) => (addOnQuantities.value[item.id] ?? 0) > 0)
    .map((item) => ({ id: item.id, quantity: addOnQuantities.value[item.id] })));

const steps = [
    { id: 1, label: 'Zona' },
    { id: 2, label: 'Ruang & tarif' },
    { id: 3, label: 'Jadwal' },
    { id: 4, label: 'Tambahan' },
    { id: 5, label: 'Data pemesan' },
];

onMounted(() => {
    if (props.initialSpaceId && selectedZone.value) {
        const space = selectedZone.value.spaces.find((item) => item.id === props.initialSpaceId);
        if (space) {
            chooseSpace(space);
            step.value = 3;
            return;
        }
    }
    if (zoneId.value && selectedZone.value) step.value = 2;
});

watch([date, spaceId, rateId, duration], () => {
    selectedSlot.value = null;
    slots.value = [];
    slotError.value = '';
    if (!date.value || !spaceId.value || !rateId.value) return;

    loadingSlots.value = true;
    const params = new URLSearchParams({
        space_id: String(spaceId.value),
        rate_id: String(rateId.value),
        date: date.value,
        duration: String(duration.value),
    });

    fetch(`/book/availability?${params.toString()}`, {
        headers: { Accept: 'application/json' },
    })
        .then(async (response) => {
            const data = await response.json();
            if (!response.ok) throw new Error(data.message ?? data.errors?.date?.[0] ?? 'Gagal memuat jadwal.');
            slots.value = data.slots ?? [];
        })
        .catch((error: Error) => {
            slotError.value = error.message || 'Jadwal tidak dapat dimuat.';
            slots.value = [];
        })
        .finally(() => { loadingSlots.value = false; });
});

function chooseZone(zone: Zone) {
    zoneId.value = zone.id;
    spaceId.value = null;
    rateId.value = null;
    date.value = '';
    selectedSlot.value = null;
    step.value = 2;
}
function chooseSpace(space: Space) {
    spaceId.value = space.id;
    rateId.value = space.rates[0]?.id ?? null;
    duration.value = Math.max(1, space.rates[0]?.min_booking_duration ?? 1);
    selectedSlot.value = null;
}
function chooseRate(rate: Rate) {
    rateId.value = rate.id;
    duration.value = Math.max(1, rate.min_booking_duration || 1);
    selectedSlot.value = null;
}
function chooseDuration(value: number) {
    duration.value = Math.max(selectedRate.value?.min_booking_duration ?? 1, value);
}
function next() {
    if (step.value === 2 && selectedRate.value && selectedSpace.value) {
        step.value = 3;
        return;
    }
    if (step.value === 3 && date.value && selectedSlot.value) {
        step.value = 4;
        return;
    }
    if (step.value === 4) step.value = 5;
}
function back() {
    step.value = Math.max(1, step.value - 1);
}
function submit() {
    if (!selectedSpace.value || !selectedRate.value || !selectedSlot.value) return;
    form.space_id = selectedSpace.value.id;
    form.rate_id = selectedRate.value.id;
    form.date = date.value;
    form.start_time = selectedSlot.value.start_time;
    form.duration = duration.value;
    form.add_ons = selectedAddOns.value;
    form.post('/book', { preserveScroll: true });
}
function formatIDR(value: number) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
}
function unitLabel(unit: Rate['unit_type']) {
    return ({ per_hour: 'per jam', per_visit: 'per kunjungan', per_session: 'per sesi' })[unit];
}
</script>

<template>
    <Head>
        <title>Booking Fasilitas Olahraga | Sport Center</title>
        <meta head-key="description" name="description" content="Pilih zona, ruang, tanggal, jam, dan add-on. Lihat total harga sebelum konfirmasi booking olahraga tanpa akun member." />
        <meta head-key="robots" name="robots" content="noindex, follow" />
    </Head>
    <div class="min-h-screen bg-[#f6f7f2] text-[#15251f]">
        <header class="border-b border-black/5 bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-5 py-4 sm:px-8">
                <Link href="/" class="flex items-center gap-3">
                    <img src="/logo.png" alt="Sport Center" class="h-10 w-10 rounded-xl object-contain" />
                    <span class="text-lg font-black tracking-tight">sport<span class="text-[#6b8e23]">center.</span></span>
                </Link>
                <Link href="/" class="rounded-full border border-[#d7ddcf] px-4 py-2.5 text-sm font-bold hover:bg-[#f6f7f2]">← Beranda</Link>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-5 py-8 sm:px-8 sm:py-12">
            <div v-if="props.successMessage" class="mb-6 rounded-3xl border border-emerald-200 bg-emerald-50 p-6">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">Booking tercatat</p>
                <h1 class="mt-2 text-2xl font-black text-emerald-950">{{ props.successMessage }}</h1>
                <p v-if="props.bookingReference" class="mt-3 inline-flex rounded-xl bg-white px-4 py-3 font-mono text-lg font-black text-emerald-900">Kode: {{ props.bookingReference }}</p>
                <p class="mt-3 text-sm text-emerald-800">Status awal: menunggu pembayaran. Pengunjung tidak perlu membuat akun member.</p>
                <Link href="/" class="mt-5 inline-flex rounded-full bg-[#172720] px-5 py-3 text-sm font-bold text-white">Kembali ke beranda</Link>
            </div>

            <template v-else>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-[#819b42]">Book your session</p>
                <div class="mt-2 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <h1 class="text-3xl font-black tracking-tight sm:text-4xl">Booking olahraga</h1>
                        <p class="mt-3 max-w-xl text-sm leading-6 text-slate-500">Pilih zona, ruang, jadwal, dan layanan tambahan. Harga dihitung otomatis dari tarif yang tersimpan.</p>
                    </div>
                    <span class="w-fit rounded-full bg-[#e7efda] px-4 py-2 text-xs font-extrabold text-[#53663a]">Tanpa daftar member</span>
                </div>

                <div class="mt-7 grid grid-cols-5 gap-2">
                    <div v-for="item in steps" :key="item.id" class="min-w-0">
                        <div class="h-1.5 rounded-full" :class="step >= item.id ? 'bg-[#8ba83d]' : 'bg-[#e0e5d9]'"></div>
                        <p class="mt-2 truncate text-[10px] font-bold sm:text-xs" :class="step === item.id ? 'text-[#26351c]' : 'text-slate-400'">{{ item.label }}</p>
                    </div>
                </div>

                <div class="mt-6 grid gap-5 lg:grid-cols-[1fr_300px]">
                    <section class="min-w-0 rounded-3xl border border-[#e4e8df] bg-white p-5 shadow-sm sm:p-7">
                        <div v-if="step === 1">
                            <h2 class="text-xl font-black">Pilih zona olahraga</h2>
                            <p class="mt-1 text-sm text-slate-500">Pilih jenis olahraga yang ingin kamu mainkan.</p>
                            <div v-if="props.zones.length" class="mt-5 grid gap-3 sm:grid-cols-2">
                                <button v-for="zone in props.zones" :key="zone.id" type="button" class="rounded-2xl border p-4 text-left transition hover:border-[#9db55f] hover:bg-[#f8faef]" :class="zoneId === zone.id ? 'border-[#8ba83d] bg-[#f3f8e7] ring-1 ring-[#8ba83d]' : 'border-[#e4e8df]'" @click="chooseZone(zone)">
                                    <span class="flex items-start justify-between gap-3">
                                        <span class="text-lg font-black">{{ zone.name }}</span>
                                        <span class="text-lg">↗</span>
                                    </span>
                                    <span class="mt-2 block text-xs text-slate-500">{{ zone.spaces.length }} ruang · {{ zone.spaces.filter(s => s.status === 'available' && s.rates.length).length }} siap dipesan</span>
                                </button>
                            </div>
                            <p v-else class="mt-5 rounded-xl bg-[#f6f7f2] p-4 text-sm text-slate-500">Belum ada zona yang diaktifkan untuk booking online.</p>
                        </div>

                        <div v-else-if="step === 2">
                            <h2 class="text-xl font-black">Pilih ruang dan tarif</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ selectedZone?.name }} · pilih ruang yang tersedia.</p>
                            <div class="mt-5 space-y-3">
                                <button v-for="space in availableSpaces" :key="space.id" type="button" class="w-full rounded-2xl border p-4 text-left transition hover:border-[#9db55f]" :class="spaceId === space.id ? 'border-[#8ba83d] bg-[#f8faef] ring-1 ring-[#8ba83d]' : 'border-[#e4e8df]'" @click="chooseSpace(space)">
                                    <span class="flex items-start justify-between gap-3">
                                        <span>
                                            <span class="block font-black">{{ space.name }}</span>
                                            <span class="mt-1 block text-xs text-slate-500">Kapasitas {{ space.capacity }} orang</span>
                                        </span>
                                        <span v-if="spaceId === space.id" class="rounded-full bg-[#d8ff62] px-3 py-1 text-xs font-black">Dipilih</span>
                                    </span>
                                    <span v-if="space.facilities.length" class="mt-3 flex flex-wrap gap-1.5">
                                        <span v-for="facility in space.facilities" :key="facility" class="rounded-lg bg-[#f0f2eb] px-2 py-1 text-xs text-slate-600">{{ facility }}</span>
                                    </span>
                                </button>
                            </div>
                            <p v-if="selectedSpace" class="mt-6 text-sm font-bold">Pilih jenis tarif</p>
                            <div v-if="selectedSpace" class="mt-3 grid gap-3 sm:grid-cols-2">
                                <button v-for="rate in selectedSpace.rates" :key="rate.id" type="button" class="rounded-2xl border p-4 text-left" :class="rateId === rate.id ? 'border-[#8ba83d] bg-[#f8faef]' : 'border-[#e4e8df]'" @click="chooseRate(rate)">
                                    <span class="block font-bold">{{ rate.rental_type }}</span>
                                    <span class="mt-1 block text-xs text-slate-500">{{ unitLabel(rate.unit_type) }}<span v-if="rate.min_booking_duration > 1"> · minimum {{ rate.min_booking_duration }} unit</span></span>
                                    <span class="mt-3 block text-lg font-black">{{ formatIDR(rate.price) }} <span class="text-xs font-semibold text-slate-400">/ {{ unitLabel(rate.unit_type) }}</span></span>
                                </button>
                            </div>
                            <p v-if="selectedZone && !availableSpaces.length" class="mt-5 rounded-xl bg-amber-50 p-4 text-sm text-amber-800">Belum ada ruang tersedia dengan tarif aktif di zona ini.</p>
                        </div>

                        <div v-else-if="step === 3">
                            <h2 class="text-xl font-black">Pilih tanggal dan jam</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ selectedZone?.name }} · {{ selectedSpace?.name }}</p>
                            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                                <label class="grid gap-2 text-sm font-bold">Tanggal
                                    <input v-model="date" type="date" :min="today" class="rounded-xl border border-[#dce1d5] bg-white px-4 py-3 font-medium outline-none focus:border-[#8ba83d]" />
                                </label>
                                <label class="grid gap-2 text-sm font-bold">Durasi
                                    <select :value="duration" :disabled="!isHourly" class="rounded-xl border border-[#dce1d5] bg-white px-4 py-3 font-medium outline-none focus:border-[#8ba83d] disabled:bg-slate-100" @change="chooseDuration(Number(($event.target as HTMLSelectElement).value))">
                                        <option v-for="hour in [1,2,3,4,5,6,7,8,9,10,11,12].filter(h => h >= (selectedRate?.min_booking_duration ?? 1))" :key="hour" :value="hour">{{ hour }} jam</option>
                                    </select>
                                </label>
                            </div>
                            <div class="mt-5 rounded-xl bg-[#f6f7f2] p-4 text-sm">
                                <div class="flex justify-between gap-3"><span class="text-slate-500">Tarif</span><span class="font-bold">{{ selectedRate ? formatIDR(selectedRate.price) + ' / ' + unitLabel(selectedRate.unit_type) : '—' }}</span></div>
                                <div class="mt-2 flex justify-between gap-3"><span class="text-slate-500">Durasi</span><span class="font-bold">{{ isHourly ? duration + ' jam' : unitLabel(selectedRate?.unit_type ?? 'per_session') }}</span></div>
                            </div>
                            <div v-if="loadingSlots" class="mt-5 text-sm font-semibold text-slate-500">Memeriksa jadwal yang tersedia...</div>
                            <p v-else-if="slotError" class="mt-5 rounded-xl bg-amber-50 p-4 text-sm text-amber-800">{{ slotError }}</p>
                            <div v-else-if="date" class="mt-5">
                                <p class="mb-3 text-sm font-bold">Jam yang tersedia</p>
                                <div v-if="slots.length" class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                    <button v-for="slot in slots" :key="slot.start_time" type="button" class="rounded-xl border p-3 text-left transition" :class="selectedSlot?.start_time === slot.start_time ? 'border-[#8ba83d] bg-[#f3f8e7] ring-1 ring-[#8ba83d]' : 'border-[#e4e8df] hover:border-[#9db55f]'" @click="selectedSlot = slot">
                                        <span class="block font-black">{{ slot.start_time }}–{{ slot.end_time }}</span>
                                        <span class="mt-1 block text-xs font-bold text-[#607a2f]">{{ formatIDR(slot.price) }}</span>
                                    </button>
                                </div>
                                <p v-else class="rounded-xl border border-dashed border-[#d5dccb] p-5 text-sm text-slate-500">Tidak ada slot tersedia pada tanggal ini. Coba tanggal atau durasi lain.</p>
                            </div>
                            <p v-if="form.errors.start_time || form.errors.date" class="mt-3 text-sm font-semibold text-red-600">{{ form.errors.start_time || form.errors.date }}</p>
                        </div>

                        <div v-else-if="step === 4">
                            <h2 class="text-xl font-black">Tambahan untuk booking</h2>
                            <p class="mt-1 text-sm text-slate-500">Opsional. Pilih add-on yang kamu perlukan.</p>
                            <div v-if="props.addOns.length" class="mt-5 space-y-3">
                                <div v-for="item in props.addOns" :key="item.id" class="flex items-center justify-between gap-4 rounded-2xl border border-[#e4e8df] p-4">
                                    <div class="min-w-0">
                                        <p class="font-black">{{ item.name }}</p>
                                        <p class="mt-1 text-sm font-bold text-[#607a2f]">{{ formatIDR(item.price) }}</p>
                                        <p class="mt-1 text-xs text-slate-400">Stok {{ item.stock }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" class="grid size-9 place-items-center rounded-full border disabled:opacity-30" :disabled="!(addOnQuantities[item.id] ?? 0)" @click="addOnQuantities[item.id] = Math.max(0, (addOnQuantities[item.id] ?? 0) - 1)">−</button>
                                        <span class="w-5 text-center text-sm font-black">{{ addOnQuantities[item.id] ?? 0 }}</span>
                                        <button type="button" class="grid size-9 place-items-center rounded-full border disabled:opacity-30" :disabled="(addOnQuantities[item.id] ?? 0) >= Math.min(item.stock, 20)" @click="addOnQuantities[item.id] = (addOnQuantities[item.id] ?? 0) + 1">+</button>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="mt-5 rounded-xl bg-[#f6f7f2] p-4 text-sm text-slate-500">Tidak ada add-on yang tersedia saat ini. Kamu bisa lanjut tanpa tambahan.</div>
                        </div>

                        <div v-else>
                            <h2 class="text-xl font-black">Data pemesan</h2>
                            <p class="mt-1 text-sm text-slate-500">Tidak perlu login atau membuat akun member.</p>
                            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                                <label class="grid gap-2 text-sm font-bold sm:col-span-2">Nama lengkap
                                    <input v-model="form.guest_name" autocomplete="name" placeholder="Nama pemesan" class="rounded-xl border border-[#dce1d5] px-4 py-3 font-medium outline-none focus:border-[#8ba83d]" />
                                    <span v-if="form.errors.guest_name" class="text-xs text-red-600">{{ form.errors.guest_name }}</span>
                                </label>
                                <label class="grid gap-2 text-sm font-bold">Email
                                    <input v-model="form.guest_email" type="email" autocomplete="email" placeholder="nama@email.com" class="rounded-xl border border-[#dce1d5] px-4 py-3 font-medium outline-none focus:border-[#8ba83d]" />
                                    <span v-if="form.errors.guest_email" class="text-xs text-red-600">{{ form.errors.guest_email }}</span>
                                </label>
                                <label class="grid gap-2 text-sm font-bold">Nomor HP / WhatsApp
                                    <input v-model="form.guest_phone" type="tel" autocomplete="tel" placeholder="08xxxxxxxxxx" class="rounded-xl border border-[#dce1d5] px-4 py-3 font-medium outline-none focus:border-[#8ba83d]" />
                                    <span v-if="form.errors.guest_phone" class="text-xs text-red-600">{{ form.errors.guest_phone }}</span>
                                </label>
                            </div>
                            <p v-if="form.errors.add_ons" class="mt-3 text-sm text-red-600">{{ form.errors.add_ons }}</p>
                            <div class="mt-5 rounded-2xl bg-[#172720] p-5 text-white">
                                <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#d8ff62]">Ringkasan booking</p>
                                <div class="mt-3 flex justify-between gap-3 text-sm"><span class="text-white/65">Zona / ruang</span><span class="text-right font-bold">{{ selectedZone?.name }} · {{ selectedSpace?.name }}</span></div>
                                <div class="mt-2 flex justify-between gap-3 text-sm"><span class="text-white/65">Tanggal</span><span class="font-bold">{{ date }}</span></div>
                                <div class="mt-2 flex justify-between gap-3 text-sm"><span class="text-white/65">Jam</span><span class="font-bold">{{ selectedSlot?.start_time }}–{{ selectedSlot?.end_time }}</span></div>
                                <div class="mt-2 flex justify-between gap-3 text-sm"><span class="text-white/65">Tarif ruang</span><span class="font-bold">{{ formatIDR(currentPrice) }}</span></div>
                                <div class="mt-2 flex justify-between gap-3 text-sm"><span class="text-white/65">Add-on</span><span class="font-bold">{{ formatIDR(addOnTotal) }}</span></div>
                                <div class="mt-4 flex items-end justify-between border-t border-white/15 pt-4"><span class="font-bold">Total</span><span class="text-2xl font-black text-[#d8ff62]">{{ formatIDR(total) }}</span></div>
                            </div>
                            <p v-if="form.errors.space_id || form.errors.rate_id" class="mt-3 text-sm text-red-600">{{ form.errors.space_id || form.errors.rate_id }}</p>
                        </div>

                        <div class="mt-7 flex items-center justify-between gap-3 border-t border-[#edf0e9] pt-5">
                            <button type="button" class="rounded-full border border-[#d7ddcf] px-5 py-3 text-sm font-bold disabled:opacity-30" :disabled="step === 1 || form.processing" @click="back">← Kembali</button>
                            <button v-if="step < 5" type="button" class="rounded-full bg-[#172720] px-6 py-3 text-sm font-black text-white transition hover:bg-[#2d4436] disabled:cursor-not-allowed disabled:opacity-40" :disabled="(step === 1 && !selectedZone) || (step === 2 && (!selectedSpace || !selectedRate)) || (step === 3 && (!date || !selectedSlot || loadingSlots))" @click="next">Lanjutkan →</button>
                            <button v-else type="button" class="rounded-full bg-[#d8ff62] px-6 py-3 text-sm font-black text-[#172720] transition hover:bg-white disabled:cursor-not-allowed disabled:opacity-40" :disabled="form.processing || !form.guest_name || !form.guest_email || !form.guest_phone" @click="submit">{{ form.processing ? 'Memproses...' : 'Konfirmasi booking · ' + formatIDR(total) }}</button>
                        </div>
                    </section>

                    <aside class="h-fit rounded-3xl bg-[#172720] p-5 text-white sm:p-6">
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-[#d8ff62]">Ringkasan</p>
                        <h2 class="mt-2 text-xl font-black">Booking kamu</h2>
                        <div class="mt-5 space-y-3 border-b border-white/15 pb-5 text-sm">
                            <div class="flex justify-between gap-3"><span class="text-white/60">Zona</span><span class="text-right font-bold">{{ selectedZone?.name ?? 'Belum dipilih' }}</span></div>
                            <div class="flex justify-between gap-3"><span class="text-white/60">Ruang</span><span class="text-right font-bold">{{ selectedSpace?.name ?? 'Belum dipilih' }}</span></div>
                            <div class="flex justify-between gap-3"><span class="text-white/60">Tanggal</span><span class="text-right font-bold">{{ date || 'Belum dipilih' }}</span></div>
                            <div class="flex justify-between gap-3"><span class="text-white/60">Jam</span><span class="text-right font-bold">{{ selectedSlot ? selectedSlot.start_time + '–' + selectedSlot.end_time : 'Belum dipilih' }}</span></div>
                        </div>
                        <div class="mt-5 flex items-end justify-between gap-3">
                            <span class="text-sm text-white/65">Estimasi total</span>
                            <span class="text-xl font-black text-[#d8ff62]">{{ formatIDR(total) }}</span>
                        </div>
                        <p class="mt-4 text-xs leading-5 text-white/55">Harga diambil dari tarif yang diatur pengelola. Booking akan berstatus menunggu pembayaran setelah dikonfirmasi.</p>
                    </aside>
                </div>
            </template>
        </main>
    </div>
</template>
