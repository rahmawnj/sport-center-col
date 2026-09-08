<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Check, ChevronRight, Clock3, MapPin, Users } from '@lucide/vue';
import { computed, onMounted, watch, ref } from 'vue';

interface Rate { id: number; rental_type: string; price: string | number; }
interface Facility { id: number; name: string; }
interface Space { id: number; name: string; capacity: number | null; status: string; facilities: Facility[]; pricing_rates: Rate[]; }
interface Zone { id: number; name: string; pricing_model: string; is_online_bookable: boolean; zone_spaces: Space[]; }

const props = defineProps<{ zones: Zone[]; initial: { date?: string; zone_id?: string } }>();
const storageKey = 'sport-center-booking-draft';

const selectedZoneId = ref<number | null>(props.zones[0]?.id ?? null);
const selectedSpaceId = ref<number | null>(null);
const bookingDate = ref('');
const selectedTime = ref('');
const guestName = ref('');
const error = ref('');

const selectedZone = computed(() => props.zones.find((zone) => zone.id === selectedZoneId.value));
const selectedSpace = computed(() => selectedZone.value?.zone_spaces.find((space) => space.id === selectedSpaceId.value));
const selectedRate = computed(() => selectedSpace.value?.pricing_rates?.[0]);
const totalAmount = computed(() => Number(selectedRate.value?.price ?? 0));
const today = new Date().toISOString().slice(0, 10);
const canContinue = computed(() => Boolean(selectedSpace.value && guestName.value.trim() && bookingDate.value && selectedTime.value));

const formatPrice = (price: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(price);

const saveDraft = () => {
    localStorage.setItem(storageKey, JSON.stringify({
        zone_space_id: selectedSpaceId.value,
        zone_id: selectedZoneId.value,
        guest_name: guestName.value,
        booking_date: bookingDate.value,
        start_time: selectedTime.value,
        zone_name: selectedZone.value?.name ?? '',
        space_name: selectedSpace.value?.name ?? '',
        rental_type: selectedRate.value?.rental_type ?? '',
        total_amount: totalAmount.value,
    }));
};

onMounted(() => {
    try {
        const saved = JSON.parse(localStorage.getItem(storageKey) ?? 'null');
        if (!saved) return;
        selectedZoneId.value = saved.zone_id ?? selectedZoneId.value;
        selectedSpaceId.value = saved.zone_space_id ?? null;
        guestName.value = saved.guest_name ?? '';
        bookingDate.value = saved.booking_date ?? '';
        selectedTime.value = saved.start_time ?? '';
    } catch {
        localStorage.removeItem(storageKey);
    }
});

watch([selectedZoneId, selectedSpaceId, bookingDate, selectedTime, guestName], saveDraft, { deep: true });

const selectZone = (zone: Zone) => {
    selectedZoneId.value = zone.id;
    selectedSpaceId.value = null;
};

const continueToPayment = () => {
    if (!canContinue.value) {
        error.value = 'Lengkapi nama, area, space, tanggal, dan jam terlebih dahulu.';
        return;
    }
    saveDraft();
    router.visit('/booking/payment');
};
</script>

<template>
    <Head title="Online Booking" />
    <div class="min-h-screen bg-[#f7f8f6] text-slate-900">
        <header class="sticky top-0 z-30 border-b border-white/60 bg-[#f7f8f6]/90 backdrop-blur-xl">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-8">
                <Link href="/" class="flex items-center gap-3"><div class="flex size-10 items-center justify-center rounded-2xl bg-slate-900 text-sm font-black text-white">SC</div><div><p class="text-sm font-bold">Sport Center</p><p class="text-[10px] font-medium uppercase tracking-[0.2em] text-slate-400">Play. Move. Connect.</p></div></Link>
                <span class="rounded-full bg-slate-100 px-4 py-2 text-xs font-bold text-slate-500">Step 1 of 2</span>
            </div>
        </header>

        <main>
            <section class="bg-slate-950 text-white"><div class="mx-auto max-w-7xl px-5 py-14 sm:px-8 lg:py-18"><div class="mb-5 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-semibold"><span class="size-1.5 rounded-full bg-emerald-400"></span> Online booking is open</div><h1 class="max-w-3xl text-4xl font-black leading-[1.05] tracking-[-0.04em] sm:text-6xl">Book your<br /><span class="text-slate-400">game time.</span></h1><p class="mt-6 max-w-xl text-base leading-7 text-slate-300 sm:text-lg">Isi data booking terlebih dahulu. Setelah itu kamu akan diarahkan ke halaman pembayaran.</p></div></section>

            <section class="mx-auto max-w-7xl px-5 py-12 sm:px-8 lg:py-16">
                <div v-if="zones.length" class="grid gap-10 lg:grid-cols-[1fr_380px]">
                    <div class="space-y-10">
                        <div><div class="mb-5"><p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Step 01</p><h2 class="mt-1 text-2xl font-black">Choose your area</h2></div><div class="grid gap-4 sm:grid-cols-3"><button v-for="zone in zones" :key="zone.id" type="button" class="rounded-3xl border bg-white p-5 text-left transition hover:-translate-y-1 hover:shadow-lg" :class="selectedZoneId === zone.id ? 'border-slate-900 ring-2 ring-slate-900/10' : 'border-slate-200'" @click="selectZone(zone)"><div class="mb-6 flex items-center justify-between"><div class="flex size-11 items-center justify-center rounded-2xl bg-slate-100"><MapPin class="size-5" /></div><Check v-if="selectedZoneId === zone.id" class="size-5" /></div><p class="font-bold">{{ zone.name }}</p><p class="mt-1 text-xs text-slate-400">{{ zone.zone_spaces.length }} space tersedia</p></button></div></div>

                        <div v-if="selectedZone"><div class="mb-5"><p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Step 02</p><h2 class="mt-1 text-2xl font-black">Choose your space</h2></div><div class="grid gap-4 sm:grid-cols-2"><button v-for="space in selectedZone.zone_spaces" :key="space.id" type="button" class="rounded-3xl border bg-white p-6 text-left transition hover:shadow-lg" :class="selectedSpaceId === space.id ? 'border-slate-900 ring-2 ring-slate-900/10' : 'border-slate-200'" @click="selectedSpaceId = space.id"><div class="flex items-start justify-between"><div><p class="font-bold">{{ space.name }}</p><div class="mt-3 flex gap-3 text-xs text-slate-400"><span v-if="space.capacity" class="inline-flex items-center gap-1"><Users class="size-3.5" /> {{ space.capacity }} orang</span><span class="inline-flex items-center gap-1"><Clock3 class="size-3.5" /> Available</span></div></div><div class="flex size-7 items-center justify-center rounded-full border" :class="selectedSpaceId === space.id ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200'"><Check v-if="selectedSpaceId === space.id" class="size-4" /></div></div><div v-if="space.facilities.length" class="mt-5 flex flex-wrap gap-1.5"><span v-for="facility in space.facilities" :key="facility.id" class="rounded-full bg-slate-100 px-3 py-1.5 text-[11px] text-slate-500">{{ facility.name }}</span></div></button></div></div>

                        <div><div class="mb-5"><p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Step 03</p><h2 class="mt-1 text-2xl font-black">Your details</h2></div><div class="grid gap-4 rounded-3xl border border-slate-200 bg-white p-5 sm:grid-cols-3"><label><span class="mb-2 block text-sm font-bold">Nama pemesan</span><input v-model="guestName" type="text" placeholder="Nama lengkap" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm outline-none focus:border-slate-900" /></label><label><span class="mb-2 block text-sm font-bold">Tanggal</span><input v-model="bookingDate" :min="today" type="date" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm outline-none focus:border-slate-900" /></label><label><span class="mb-2 block text-sm font-bold">Jam</span><input v-model="selectedTime" type="time" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm outline-none focus:border-slate-900" /></label></div></div>
                        <p v-if="error" class="rounded-2xl bg-red-50 p-4 text-sm font-semibold text-red-600">{{ error }}</p>
                    </div>

                    <aside class="lg:sticky lg:top-24 lg:self-start"><div class="rounded-[2rem] bg-slate-900 text-white shadow-2xl"><div class="p-7 sm:p-8"><p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Booking summary</p><h2 class="mt-2 text-2xl font-black">{{ selectedSpace?.name ?? 'Choose a space' }}</h2><div class="my-7 space-y-4 border-y border-white/10 py-6 text-sm"><div class="flex justify-between gap-4"><span class="text-slate-500">Nama</span><span class="font-semibold">{{ guestName || '—' }}</span></div><div class="flex justify-between gap-4"><span class="text-slate-500">Area</span><span class="font-semibold">{{ selectedZone?.name ?? '—' }}</span></div><div class="flex justify-between gap-4"><span class="text-slate-500">Tanggal</span><span class="font-semibold">{{ bookingDate || '—' }}</span></div><div class="flex justify-between gap-4"><span class="text-slate-500">Jam</span><span class="font-semibold">{{ selectedTime || '—' }}</span></div></div><p class="text-xs uppercase tracking-widest text-slate-500">Total booking</p><p class="mt-1 text-3xl font-black">{{ formatPrice(totalAmount) }}</p><p class="mt-1 text-xs text-slate-500">{{ selectedRate?.rental_type || '—' }}</p><button type="button" :disabled="!canContinue" class="mt-7 flex w-full items-center justify-center gap-2 rounded-2xl bg-white px-5 py-4 text-sm font-bold text-slate-900 transition" :class="!canContinue ? 'cursor-not-allowed opacity-40' : 'hover:bg-slate-100'" @click="continueToPayment">Lanjut ke Pembayaran <ChevronRight class="size-4" /></button><p class="mt-4 text-center text-[11px] leading-5 text-slate-500">Data booking otomatis tersimpan di browser agar tidak hilang saat refresh.</p></div></div></aside>
                </div>
                <div v-else class="rounded-[2rem] border border-dashed border-slate-300 bg-white p-14 text-center"><h2 class="text-xl font-black">Booking online belum tersedia</h2><p class="mt-2 text-sm text-slate-500">Belum ada area yang diaktifkan untuk online booking.</p></div>
            </section>
        </main>
    </div>
</template>
