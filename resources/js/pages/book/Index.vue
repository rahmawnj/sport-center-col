<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, Building2, Check, Loader2, MapPin } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import PublicLayout from '@/layouts/PublicLayout.vue';

type Facility = { id: number; name: string; slug: string };
type Court = {
    id: number;
    name: string;
    amenities?: string[];
    price_adjustment?: number;
    is_available: boolean;
};
type Slot = {
    window_id: number;
    start_time: string;
    end_time: string;
    price: number;
    courts: Court[];
};

const props = defineProps<{
    facilities: Facility[];
    initialFacilityId: number | null;
    currentUser: { id: number; name: string; email: string } | null;
    success?: string | null;
}>();

const now = new Date();
const toDateInputValue = (value: Date) => `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}`;
const today = toDateInputValue(now);
const maxBookingDate = toDateInputValue(new Date(now.getFullYear(), now.getMonth(), now.getDate() + 7));
const initialFacility = props.facilities.find((item) => item.id === props.initialFacilityId) ?? null;
const step = ref(initialFacility ? 2 : 1);
const facility = ref<Facility | null>(initialFacility);
const date = ref('');
const slots = ref<Slot[]>([]);
const loadingSlots = ref(false);
const slot = ref<Slot | null>(null);
const selectedSlots = ref<string[]>([]);
const slotSelectionError = ref('');
const courtId = ref<number | null>(null);
const termsAccepted = ref(false);

const form = useForm({
    facility_id: (facility.value?.id ?? null) as number | null,
    court_id: null as number | null,
    date: '',
    start_time: '',
    duration_hours: 1,
    guest_name: props.currentUser?.name ?? '',
    guest_email: props.currentUser?.email ?? '',
    guest_phone: '',
    terms_accepted: false,
});

const steps = [
    { id: 1, label: 'Olahraga' },
    { id: 2, label: 'Jadwal & lapangan' },
    { id: 3, label: 'Data pemesan' },
    { id: 4, label: 'Konfirmasi' },
    { id: 5, label: 'Pembayaran' },
];

const selectedDurationHours = computed(() => selectedSlots.value.length);
const selectedDurationLabel = computed(() => selectedDurationHours.value === 1 ? '1 jam' : `${selectedDurationHours.value} jam`);
const selectedCourt = computed(() => slot.value?.courts.find((court) => court.id === courtId.value) ?? null);
const displayPrice = computed(() => slot.value
    ? slot.value.price + (selectedCourt.value?.price_adjustment ?? 0)
    : null);

const canNext = computed(() => {
    switch (step.value) {
        case 1: return !!facility.value;
        case 2: return !!date.value && !!slot.value && selectedSlots.value.length > 0 && !!courtId.value;
        case 3:
            return !!form.guest_phone.trim() && (props.currentUser !== null || (
                !!form.guest_name.trim()
                && !!form.guest_email.trim()
                && form.guest_email.includes('@')
                && form.guest_email.includes('.')
            ));
        case 4: return true;
        case 5: return termsAccepted.value;
        default: return false;
    }
});

watch(date, () => {
    slot.value = null;
    selectedSlots.value = [];
    slotSelectionError.value = '';
    courtId.value = null;
    slots.value = [];

    if (!facility.value || !date.value) return;

    loadingSlots.value = true;
    fetch(`/book/availability?facility_id=${facility.value.id}&date=${date.value}`, {
        headers: { Accept: 'application/json' },
    })
        .then((response) => {
            if (!response.ok) throw new Error('Gagal memuat jadwal booking.');
            return response.json();
        })
        .then((data) => { slots.value = data.slots ?? []; })
        .catch(() => { slots.value = []; })
        .finally(() => { loadingSlots.value = false; });
});

function chooseFacility(item: Facility) {
    facility.value = item;
    form.facility_id = item.id;
    date.value = '';
    slots.value = [];
    slot.value = null;
    selectedSlots.value = [];
    courtId.value = null;
    step.value = 2;
}

function chooseSlot(item: Slot) {
    slotSelectionError.value = '';
    const selected = new Set(selectedSlots.value);
    const clickedIndex = slots.value.findIndex((entry) => entry.start_time === item.start_time);

    if (selected.has(item.start_time)) {
        const indices = [...selected]
            .map((time) => slots.value.findIndex((entry) => entry.start_time === time))
            .filter((index) => index >= 0)
            .sort((a, b) => a - b);
        const first = indices[0];
        const last = indices[indices.length - 1];

        if (clickedIndex === first || clickedIndex === last) {
            selected.delete(item.start_time);
        } else {
            indices.filter((index) => index >= clickedIndex).forEach((index) => selected.delete(slots.value[index].start_time));
        }
    } else {
        const indices = [...selected]
            .map((time) => slots.value.findIndex((entry) => entry.start_time === time))
            .filter((index) => index >= 0);
        const first = indices.length ? Math.min(...indices, clickedIndex) : clickedIndex;
        const last = indices.length ? Math.max(...indices, clickedIndex) : clickedIndex;

        for (let index = first; index <= last; index++) selected.add(slots.value[index].start_time);
    }

    const chosen = [...selected]
        .map((time) => slots.value.find((entry) => entry.start_time === time))
        .filter((entry): entry is Slot => !!entry)
        .sort((a, b) => a.start_time.localeCompare(b.start_time));

    if (new Set(chosen.map((entry) => entry.window_id)).size > 1) {
        slotSelectionError.value = 'Booking tidak bisa melewati pergantian rentang harga. Pilih jam dalam satu rentang harga.';
        return;
    }

    selectedSlots.value = chosen.map((entry) => entry.start_time);
    courtId.value = null;

    if (!chosen.length) {
        slot.value = null;
        return;
    }

    const commonCourts = chosen[0].courts.map((court) => ({
        ...court,
        is_available: chosen.every((entry) => entry.courts.some((option) => option.id === court.id && option.is_available)),
    }));
    const [hour, minute] = chosen[0].start_time.split(':').map(Number);
    const endMinutes = hour * 60 + minute + chosen.length * 60;

    slot.value = {
        window_id: chosen[0].window_id,
        start_time: chosen[0].start_time,
        end_time: `${String(Math.floor(endMinutes / 60)).padStart(2, '0')}:${String(endMinutes % 60).padStart(2, '0')}`,
        price: chosen.reduce((total, entry) => total + entry.price, 0),
        courts: commonCourts,
    };
}

function chooseCourt(id: number) {
    courtId.value = id;
}

function next() {
    if (step.value === 2) {
        form.facility_id = facility.value?.id ?? null;
        form.date = date.value;
        form.start_time = slot.value?.start_time ?? '';
        form.duration_hours = selectedSlots.value.length;
        form.court_id = courtId.value;
    }
    step.value = Math.min(5, step.value + 1);
}

function back() {
    step.value = Math.max(1, step.value - 1);
}

function submit() {
    form.terms_accepted = termsAccepted.value;
    form.facility_id = facility.value?.id ?? null;
    form.date = date.value;
    form.start_time = slot.value?.start_time ?? '';
    form.duration_hours = selectedSlots.value.length;
    form.court_id = courtId.value;
    form.post('/book', { preserveScroll: true });
}

function formatIDR(value: number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}
</script>

<template>
    <Head title="Booking" />
    <PublicLayout>
        <div class="min-h-screen bg-slate-50 text-slate-900">
            <main class="mx-auto w-full max-w-5xl px-4 py-8">
                <section v-if="props.success" class="flex min-h-[75vh] items-center justify-center py-10">
                    <div class="w-full max-w-3xl rounded-3xl bg-white p-8 text-center shadow-xl sm:p-12">
                        <div class="mx-auto flex size-20 items-center justify-center rounded-full bg-lime-100 text-lime-700"><Check class="size-10" /></div>
                        <p class="mt-6 text-sm font-semibold uppercase tracking-widest text-lime-700">Konfirmasi berhasil</p>
                        <h1 class="mt-2 text-3xl font-bold">Booking berhasil!</h1>
                        <p class="mt-3 text-slate-600">{{ props.success }}</p>
                        <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                            <a href="/" class="rounded-xl border px-6 py-3 text-sm font-semibold">Kembali ke beranda</a>
                            <a href="/book" class="rounded-xl bg-[#a4da01] px-6 py-3 text-sm font-bold text-[#001428]">Booking olahraga lain</a>
                        </div>
                    </div>
                </section>

                <template v-else>
                    <div class="mb-5">
                        <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">Booking olahraga</h1>
                        <p class="mt-1 text-sm text-muted-foreground">Pilih olahraga, tentukan jadwal, lalu konfirmasi dengan data kontakmu.</p>
                    </div>

                    <div class="mt-6 flex flex-wrap items-center gap-2">
                        <template v-for="(item, index) in steps" :key="item.id">
                            <div class="flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-semibold"
                                :class="step === item.id ? 'bg-[#a4da01] text-[#001428]' : step > item.id ? 'bg-slate-200 text-slate-700' : 'bg-white text-slate-400'">
                                <span class="flex size-5 items-center justify-center rounded-full text-xs"
                                    :class="step >= item.id ? 'bg-white/70' : 'bg-slate-100'">
                                    <Check v-if="step > item.id" class="size-3" />
                                    <template v-else>{{ index + 1 }}</template>
                                </span>
                                {{ item.label }}
                            </div>
                            <ArrowRight v-if="index < steps.length - 1" class="size-4 text-muted-foreground/50" />
                        </template>
                    </div>

                    <div class="mt-6 rounded-2xl bg-white p-4 shadow-[0_8px_30px_rgba(15,23,42,0.06)] md:p-6">
                        <section v-if="step === 1" class="space-y-4">
                            <h2 class="font-semibold">Pilih olahraga</h2>
                            <div v-if="props.facilities.length" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                <button v-for="item in props.facilities" :key="item.id" type="button"
                                    class="flex items-center gap-3 rounded-xl bg-slate-50 p-4 text-left transition-all hover:bg-lime-50 hover:shadow-sm"
                                    :class="facility?.id === item.id ? 'bg-lime-100 ring-2 ring-inset ring-[#a4da01]' : ''"
                                    @click="chooseFacility(item)">
                                    <div class="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary"><Building2 class="size-5" /></div>
                                    <div class="min-w-0"><div class="truncate font-medium">{{ item.name }}</div><div class="text-xs text-muted-foreground">Booking tersedia</div></div>
                                </button>
                            </div>
                            <p v-else class="text-sm text-muted-foreground">Belum ada olahraga yang bisa dibooking online.</p>
                        </section>

                        <section v-else-if="step === 2" class="grid gap-6 lg:grid-cols-[minmax(0,1.6fr)_minmax(280px,0.9fr)]">
                            <div class="space-y-5">
                                <div>
                                    <h2 class="text-lg font-semibold">Pilih tanggal, jam & lapangan</h2>
                                    <p class="mt-1 text-sm text-slate-500">Olahraga: <strong>{{ facility?.name }}</strong>. Pilih satu atau beberapa jam berurutan, lalu pilih lapangan.</p>
                                </div>
                                <div class="grid max-w-sm gap-2">
                                    <Label for="booking-date" class="text-sm font-semibold text-slate-700">Tanggal booking</Label>
                                    <div class="rounded-2xl bg-slate-50 p-3">
                                        <Input id="booking-date" v-model="date" type="date" :min="today" class="h-14 rounded-2xl border-0 bg-slate-50 px-4 text-base font-semibold text-slate-800 shadow-none" />
                                        <p class="mt-2 text-xs text-slate-500">Pilih tanggal untuk melihat jam dan lapangan yang tersedia.</p>
                                    </div>
                                </div>
                                <div v-if="loadingSlots" class="flex items-center gap-2 text-sm text-muted-foreground"><Loader2 class="size-4 animate-spin" /> Memuat jadwal...</div>
                                <template v-else-if="date">
                                    <div v-if="slots.length" class="space-y-3">
                                        <p class="text-sm text-slate-600">Pilih jam. Jam di antaranya otomatis ikut dipilih.</p>
                                        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                            <label v-for="item in slots" :key="item.start_time"
                                                class="flex cursor-pointer items-start gap-3 rounded-xl bg-slate-50 p-3 transition-colors has-[:disabled]:cursor-not-allowed has-[:checked]:bg-lime-100 hover:bg-lime-50"
                                                :class="selectedSlots.includes(item.start_time) ? 'ring-2 ring-inset ring-[#a4da01]' : ''">
                                                <input type="checkbox" :checked="selectedSlots.includes(item.start_time)" :disabled="!item.courts.some((court) => court.is_available)"
                                                    class="mt-1 size-4 accent-[#8dbb00] disabled:cursor-not-allowed" @change="chooseSlot(item)" />
                                                <span class="min-w-0">
                                                    <span class="block font-semibold text-slate-800">{{ item.start_time }}–{{ item.end_time }}</span>
                                                    <span class="block text-sm font-medium text-slate-700">{{ formatIDR(item.price) }}</span>
                                                    <span class="block text-xs text-slate-500">{{ item.courts.filter((court) => court.is_available).length }} lapangan tersedia</span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    <p v-else class="text-sm text-muted-foreground">Tidak ada jadwal tersedia pada tanggal ini.</p>
                                </template>
                                <p v-if="slotSelectionError" class="text-sm text-destructive">{{ slotSelectionError }}</p>

                                <div class="space-y-3 border-t border-slate-100 pt-5">
                                    <div><h3 class="font-semibold">Pilih lapangan</h3><p class="mt-1 text-sm text-slate-500">{{ slot ? slot.start_time + '–' + slot.end_time : 'Pilih jam terlebih dahulu.' }}</p></div>
                                    <div v-if="slot?.courts.length" class="grid gap-3 sm:grid-cols-2">
                                        <button v-for="item in slot.courts" :key="item.id" type="button" :disabled="!item.is_available"
                                            class="flex items-center gap-3 rounded-xl border p-4 text-left transition-colors"
                                            :class="courtId === item.id ? 'border-[#a4da01] bg-lime-100 ring-2 ring-inset ring-[#a4da01]' : item.is_available ? 'border-slate-100 bg-slate-50 hover:bg-lime-50' : 'cursor-not-allowed border-slate-200 bg-slate-100 opacity-60'"
                                            @click="chooseCourt(item.id)">
                                            <MapPin class="size-4 shrink-0" :class="item.is_available ? 'text-primary' : 'text-slate-400'" />
                                            <div class="min-w-0 flex-1">
                                                <span class="block font-medium" :class="item.is_available ? 'text-slate-800' : 'text-slate-500'">{{ item.name }}</span>
                                                <span v-if="item.amenities?.length" class="mt-0.5 block text-xs text-slate-500">{{ item.amenities.join(' · ') }}</span>
                                                <span v-if="item.price_adjustment" class="mt-0.5 block text-xs font-medium text-emerald-700">+{{ formatIDR(item.price_adjustment) }}</span>
                                                <span v-if="!item.is_available" class="mt-1 block text-xs font-semibold text-rose-600">Sudah dibooking pada jam ini</span>
                                                <span v-else class="mt-1 block text-xs font-semibold text-emerald-700">Tersedia</span>
                                            </div>
                                            <Check v-if="courtId === item.id" class="ml-auto size-4 shrink-0 text-primary" />
                                        </button>
                                    </div>
                                    <p v-else class="text-sm text-muted-foreground">Pilih tanggal dan jam untuk melihat lapangan yang tersedia.</p>
                                </div>
                            </div>
                            <aside class="h-fit rounded-2xl border border-slate-200 bg-slate-50 p-5 lg:sticky lg:top-24">
                                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Booking detail</p>
                                <h3 class="mt-2 text-lg font-bold text-slate-900">Ringkasan pesanan</h3>
                                <div class="mt-5 space-y-4 text-sm">
                                    <div><p class="text-slate-500">Olahraga</p><p class="mt-1 font-semibold">{{ facility?.name || 'Belum dipilih' }}</p></div>
                                    <div><p class="text-slate-500">Tanggal</p><p class="mt-1 font-semibold">{{ date || 'Belum dipilih' }}</p></div>
                                    <div><p class="text-slate-500">Jam mulai – selesai</p><p class="mt-1 font-semibold">{{ slot ? slot.start_time + '–' + slot.end_time : 'Belum dipilih' }}</p></div>
                                    <div><p class="text-slate-500">Durasi</p><p class="mt-1 font-semibold">{{ slot && selectedDurationHours ? selectedDurationLabel : 'Belum dipilih' }}</p></div>
                                    <div><p class="text-slate-500">Lapangan</p><p class="mt-1 font-semibold">{{ selectedCourt?.name || 'Belum dipilih' }}</p></div>
                                    <div class="border-t border-slate-200 pt-4"><p class="text-slate-500">Total</p><p class="mt-1 text-2xl font-bold text-slate-900">{{ displayPrice !== null ? formatIDR(displayPrice) : '—' }}</p></div>
                                </div>
                            </aside>
                        </section>

                        <section v-else-if="step === 3" class="space-y-4">
                            <h2 class="text-lg font-semibold">Data pemesan</h2>
                            <p class="text-sm text-slate-500">Masukkan detail kontak untuk konfirmasi booking.</p>
                            <div class="rounded-xl bg-slate-50 p-4 text-sm">
                                <div class="flex justify-between gap-2"><span class="text-muted-foreground">Olahraga</span><span class="font-medium">{{ facility?.name }}</span></div>
                                <div class="mt-1 flex justify-between gap-2"><span class="text-muted-foreground">Jadwal</span><span class="font-medium">{{ date }} · {{ slot?.start_time }}–{{ slot?.end_time }}</span></div>
                                <div class="mt-1 flex justify-between gap-2"><span class="text-muted-foreground">Lapangan</span><span class="font-medium">{{ selectedCourt?.name }}</span></div>
                                <div class="mt-2 flex justify-between gap-2 border-t pt-2"><span class="text-muted-foreground">Total</span><span class="font-semibold text-primary">{{ displayPrice !== null ? formatIDR(displayPrice) : '—' }}</span></div>
                            </div>
                            <div v-if="props.currentUser" class="rounded-2xl border border-lime-200 bg-lime-50/70 p-5">
                                <div class="mb-4 flex items-center gap-3"><div class="flex size-11 items-center justify-center rounded-xl bg-[#a4da01] text-[#001428]"><Check class="size-5" /></div><div><p class="font-semibold text-slate-900">Data member terisi otomatis</p><p class="mt-1 text-sm text-slate-600">Booking akan ditautkan ke akun yang sedang login.</p></div></div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="grid gap-2"><Label for="guest-name">Nama</Label><Input id="guest-name" v-model="form.guest_name" readonly class="h-14 rounded-2xl border-0 bg-white px-4 text-base shadow-none" /></div>
                                    <div class="grid gap-2"><Label for="guest-email">Email</Label><Input id="guest-email" v-model="form.guest_email" type="email" readonly class="h-14 rounded-2xl border-0 bg-white px-4 text-base shadow-none" /></div>
                                    <div class="grid gap-2 sm:col-span-2"><Label for="guest-phone">No. HP</Label><Input id="guest-phone" v-model="form.guest_phone" type="tel" placeholder="08xxxxxxxxxx" class="h-14 rounded-2xl border-0 bg-white px-4 text-base shadow-none" /><p v-if="form.errors.guest_phone" class="text-xs text-destructive">{{ form.errors.guest_phone }}</p></div>
                                </div>
                            </div>
                            <div v-else class="grid gap-4 sm:grid-cols-2">
                                <div class="grid gap-2 sm:col-span-2"><Label for="guest-name">Nama</Label><Input id="guest-name" v-model="form.guest_name" placeholder="Nama lengkap" class="h-14 rounded-2xl border-0 bg-slate-50 px-4 text-base shadow-none" /><p v-if="form.errors.guest_name" class="text-xs text-destructive">{{ form.errors.guest_name }}</p></div>
                                <div class="grid gap-2"><Label for="guest-email">Email</Label><Input id="guest-email" v-model="form.guest_email" type="email" placeholder="email@contoh.com" class="h-14 rounded-2xl border-0 bg-slate-50 px-4 text-base shadow-none" /><p v-if="form.errors.guest_email" class="text-xs text-destructive">{{ form.errors.guest_email }}</p></div>
                                <div class="grid gap-2"><Label for="guest-phone">No. HP</Label><Input id="guest-phone" v-model="form.guest_phone" type="tel" placeholder="08xxxxxxxxxx" class="h-14 rounded-2xl border-0 bg-slate-50 px-4 text-base shadow-none" /><p v-if="form.errors.guest_phone" class="text-xs text-destructive">{{ form.errors.guest_phone }}</p></div>
                            </div>
                        </section>

                        <section v-else-if="step === 4" class="space-y-5">
                            <div><h2 class="text-xl font-bold">Konfirmasi pesanan</h2><p class="mt-1 text-sm text-slate-500">Periksa kembali detail booking sebelum dilanjutkan.</p></div>
                            <div class="rounded-2xl border border-slate-200 p-5">
                                <div class="border-b border-slate-100 pb-4"><p class="font-semibold">{{ facility?.name }}</p><p class="mt-1 text-sm text-slate-500">{{ date }} · {{ slot?.start_time }}–{{ slot?.end_time }}</p></div>
                                <div class="grid gap-4 py-4 sm:grid-cols-2">
                                    <div><p class="text-sm text-slate-500">Nama pemesan</p><p class="mt-1 font-semibold">{{ props.currentUser?.name || form.guest_name }}</p></div>
                                    <div><p class="text-sm text-slate-500">Email</p><p class="mt-1 break-all font-semibold">{{ props.currentUser?.email || form.guest_email }}</p></div>
                                    <div><p class="text-sm text-slate-500">Nomor HP</p><p class="mt-1 font-semibold">{{ form.guest_phone || '—' }}</p></div>
                                    <div><p class="text-sm text-slate-500">Lapangan</p><p class="mt-1 font-semibold">{{ selectedCourt?.name }}</p></div>
                                    <div><p class="text-sm text-slate-500">Durasi</p><p class="mt-1 font-semibold">{{ selectedDurationLabel }}</p></div>
                                </div>
                                <div class="flex items-center justify-between border-t border-slate-200 pt-4"><div><p class="font-semibold">Total pesanan</p><p class="mt-1 text-xs text-slate-500">Berdasarkan Master Jam Booking dan biaya lapangan</p></div><p class="text-2xl font-bold">{{ displayPrice !== null ? formatIDR(displayPrice) : '—' }}</p></div>
                            </div>
                        </section>

                        <section v-else class="space-y-5">
                            <div><h2 class="text-xl font-bold">Pembayaran & persetujuan</h2><p class="mt-1 text-sm text-slate-500">Konfirmasi booking dan persetujuan syarat dan ketentuan.</p></div>
                            <div class="rounded-2xl bg-[#001428] p-6 text-white">
                                <div class="flex items-center gap-3"><div class="flex size-12 items-center justify-center rounded-full bg-[#a4da01] text-[#001428]"><Check class="size-6" :stroke-width="3" /></div><div><p class="text-lg font-bold">Konfirmasi booking</p><p class="mt-1 text-sm text-slate-300">Total pesanan</p></div></div>
                                <p class="mt-5 text-3xl font-bold text-[#c9f36a]">{{ displayPrice !== null ? formatIDR(displayPrice) : '—' }}</p>
                                <p class="mt-2 text-sm leading-6 text-slate-300">Konfirmasi ini menyimpan booking dan belum memproses pembayaran online. Detail pembayaran dapat dikonfirmasi dengan pengelola.</p>
                            </div>
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 transition hover:bg-slate-50" :class="termsAccepted ? 'border-[#a4da01] bg-lime-50' : ''">
                                <input v-model="termsAccepted" type="checkbox" class="mt-1 size-4 accent-[#8dbb00]" />
                                <span class="text-sm leading-6 text-slate-700">Saya telah memeriksa detail pesanan dan menyetujui syarat serta ketentuan booking olahraga.</span>
                            </label>
                            <p v-if="!termsAccepted" class="text-xs text-slate-500">Centang persetujuan untuk mengaktifkan tombol konfirmasi.</p>
                            <p v-if="form.errors.start_time || form.errors.court_id || form.errors.facility_id" class="text-sm text-destructive">{{ form.errors.start_time || form.errors.court_id || form.errors.facility_id }}</p>
                        </section>

                        <div class="mt-6 flex items-center justify-between border-t border-slate-100 pt-4">
                            <Button type="button" variant="ghost" :disabled="step === 1" @click="back"><ArrowLeft class="mr-2 size-4" /> Kembali</Button>
                            <Button v-if="step < 5" type="button" :disabled="!canNext" @click="next">
                                {{ step === 3 ? 'Lanjut ke konfirmasi' : step === 4 ? 'Lanjut ke pembayaran' : 'Lanjut' }} <ArrowRight class="ml-2 size-4" />
                            </Button>
                            <Button v-else type="button" :disabled="!canNext || form.processing" @click="submit">
                                <Loader2 v-if="form.processing" class="mr-2 size-4 animate-spin" />
                                {{ form.processing ? 'Mengonfirmasi booking...' : 'Setujui & konfirmasi booking' }} <Check v-if="!form.processing" class="ml-2 size-4" />
                            </Button>
                        </div>
                    </div>
                </template>
            </main>
        </div>
    </PublicLayout>
</template>
