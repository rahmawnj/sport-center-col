<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarDays, Check, ChevronRight, Clock3, MapPin, Upload, Users, X } from '@lucide/vue';
import { computed, ref } from 'vue';

interface Rate { id: number; rental_type: string; price: string | number; }
interface Facility { id: number; name: string; }
interface Space { id: number; name: string; capacity: number | null; status: string; facilities: Facility[]; pricing_rates: Rate[]; }
interface Zone { id: number; name: string; pricing_model: string; is_online_bookable: boolean; zone_spaces: Space[]; }

const props = defineProps<{ zones: Zone[]; initial: { date?: string; zone_id?: string } }>();
const selectedZoneId = ref(props.initial.zone_id ? Number(props.initial.zone_id) : props.zones[0]?.id ?? null);
const selectedSpaceId = ref<number | null>(null);
const bookingDate = ref(props.initial.date ?? '');
const selectedTime = ref('');
const guestName = ref('');
const paymentOption = ref<'full_payment' | 'half_payment' | 'pay_later'>('full_payment');
const paymentProof = ref<File | null>(null);
const paymentProofPreview = ref('');
const submitting = ref(false);
const submitError = ref('');

const selectedZone = computed(() => props.zones.find((zone) => zone.id === selectedZoneId.value));
const selectedSpace = computed(() => selectedZone.value?.zone_spaces.find((space) => space.id === selectedSpaceId.value));
const selectedRate = computed(() => selectedSpace.value?.pricing_rates?.[0]);
const totalAmount = computed(() => Number(selectedRate.value?.price ?? 0));
const amountToPay = computed(() => paymentOption.value === 'full_payment' ? totalAmount.value : paymentOption.value === 'half_payment' ? totalAmount.value / 2 : 0);
const canSubmit = computed(() => Boolean(selectedSpace.value && guestName.value.trim() && bookingDate.value && selectedTime.value && (paymentOption.value === 'pay_later' || paymentProof.value)));
const today = new Date().toISOString().slice(0, 10);

const formatPrice = (price: string | number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(price));
const selectZone = (zone: Zone) => { selectedZoneId.value = zone.id; selectedSpaceId.value = null; };

const handleProofUpload = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    if (!file || !file.type.match(/^image\/(jpeg|png|webp)$/i)) return;
    paymentProof.value = file;
    paymentProofPreview.value = URL.createObjectURL(file);
    submitError.value = '';
};

const removeProof = () => {
    if (paymentProofPreview.value) URL.revokeObjectURL(paymentProofPreview.value);
    paymentProof.value = null;
    paymentProofPreview.value = '';
};

const submitBooking = () => {
    if (!canSubmit.value || submitting.value) return;
    submitting.value = true;
    submitError.value = '';

    router.post('/booking', {
        zone_space_id: selectedSpaceId.value,
        guest_name: guestName.value.trim(),
        booking_date: bookingDate.value,
        start_time: selectedTime.value,
        payment_option: paymentOption.value,
        payment_proof: paymentProof.value,
    }, {
        forceFormData: true,
        preserveScroll: true,
        onError: (errors) => {
            submitError.value = Object.values(errors)[0] ?? 'Booking gagal dikirim. Silakan cek kembali data booking.';
        },
        onSuccess: () => {
            alert('Booking berhasil dikirim. Booking akan menunggu persetujuan admin.');
        },
        onFinish: () => { submitting.value = false; },
    });
};
</script>

<template>
    <Head title="Online Booking" />
    <div class="min-h-screen bg-[#f7f8f6] text-slate-900">
        <header class="sticky top-0 z-30 border-b border-white/60 bg-[#f7f8f6]/90 backdrop-blur-xl">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-8">
                <Link href="/" class="flex items-center gap-3"><div class="flex size-10 items-center justify-center rounded-2xl bg-slate-900 text-sm font-black text-white">SC</div><div><p class="text-sm font-bold">Sport Center</p><p class="text-[10px] font-medium uppercase tracking-[0.2em] text-slate-400">Play. Move. Connect.</p></div></Link>
                <Link href="/" class="hidden rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold sm:inline-flex">Back to Home</Link>
            </div>
        </header>

        <main>
            <section class="bg-slate-950 text-white"><div class="mx-auto max-w-7xl px-5 py-16 sm:px-8 lg:py-20"><div class="mb-5 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-semibold"><span class="size-1.5 rounded-full bg-emerald-400"></span> Online booking is open</div><h1 class="max-w-3xl text-4xl font-black leading-[1.05] tracking-[-0.04em] sm:text-6xl">Your game.<br /><span class="text-slate-400">Your time.</span> Your space.</h1><p class="mt-6 max-w-xl text-base leading-7 text-slate-300 sm:text-lg">Pilih fasilitas, jadwal, pembayaran, lalu langsung kirim booking. Booking baru aktif setelah disetujui admin.</p></div></section>

            <section class="mx-auto max-w-7xl px-5 py-12 sm:px-8 lg:py-16">
                <form v-if="zones.length" @submit.prevent="submitBooking" class="grid gap-10 lg:grid-cols-[1fr_380px]">
                    <div class="space-y-10">
                        <div><div class="mb-5"><p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Step 01</p><h2 class="mt-1 text-2xl font-black">Choose your area</h2></div><div class="grid gap-4 sm:grid-cols-3"><button v-for="zone in zones" :key="zone.id" type="button" class="rounded-3xl border bg-white p-5 text-left transition hover:-translate-y-1 hover:shadow-lg" :class="selectedZoneId === zone.id ? 'border-slate-900 ring-2 ring-slate-900/10' : 'border-slate-200'" @click="selectZone(zone)"><div class="mb-6 flex items-center justify-between"><div class="flex size-11 items-center justify-center rounded-2xl bg-slate-100"><MapPin class="size-5" /></div><Check v-if="selectedZoneId === zone.id" class="size-5" /></div><p class="font-bold">{{ zone.name }}</p><p class="mt-1 text-xs text-slate-400">{{ zone.zone_spaces.length }} space tersedia</p></button></div></div>

                        <div v-if="selectedZone"><div class="mb-5"><p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Step 02</p><h2 class="mt-1 text-2xl font-black">Choose your space</h2></div><div class="grid gap-4 sm:grid-cols-2"><button v-for="space in selectedZone.zone_spaces" :key="space.id" type="button" class="rounded-3xl border bg-white p-6 text-left transition hover:shadow-lg" :class="selectedSpaceId === space.id ? 'border-slate-900 ring-2 ring-slate-900/10' : 'border-slate-200'" @click="selectedSpaceId = space.id"><div class="flex items-start justify-between"><div><p class="font-bold">{{ space.name }}</p><div class="mt-3 flex gap-3 text-xs text-slate-400"><span v-if="space.capacity" class="inline-flex items-center gap-1"><Users class="size-3.5" /> {{ space.capacity }} orang</span><span class="inline-flex items-center gap-1"><Clock3 class="size-3.5" /> Available</span></div></div><div class="flex size-7 items-center justify-center rounded-full border" :class="selectedSpaceId === space.id ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200'"><Check v-if="selectedSpaceId === space.id" class="size-4" /></div></div><div v-if="space.facilities.length" class="mt-5 flex flex-wrap gap-1.5"><span v-for="facility in space.facilities" :key="facility.id" class="rounded-full bg-slate-100 px-3 py-1.5 text-[11px] text-slate-500">{{ facility.name }}</span></div></button></div></div>

                        <div><div class="mb-5"><p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Step 03</p><h2 class="mt-1 text-2xl font-black">Your schedule</h2></div><div class="grid gap-4 rounded-3xl border border-slate-200 bg-white p-5 sm:grid-cols-3"><label><span class="mb-2 block text-sm font-bold">Nama pemesan</span><input v-model="guestName" type="text" placeholder="Nama lengkap" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm outline-none focus:border-slate-900" /></label><label><span class="mb-2 block text-sm font-bold">Tanggal</span><input v-model="bookingDate" :min="today" type="date" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm outline-none focus:border-slate-900" /></label><label><span class="mb-2 block text-sm font-bold">Jam</span><input v-model="selectedTime" type="time" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm outline-none focus:border-slate-900" /></label></div></div>

                        <div><div class="mb-5"><p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Step 04</p><h2 class="mt-1 text-2xl font-black">Payment</h2><p class="mt-2 text-sm text-slate-500">Bukti pembayaran berupa gambar. Admin akan mengecek booking dan pembayaran sebelum menyetujui.</p></div><div class="rounded-3xl border border-slate-200 bg-white p-5 sm:p-6"><div class="grid gap-3 sm:grid-cols-3"><button type="button" class="rounded-2xl border p-4 text-left" :class="paymentOption === 'full_payment' ? 'border-slate-900 bg-slate-50 ring-2 ring-slate-900/10' : 'border-slate-200'" @click="paymentOption = 'full_payment'"><p class="font-bold">Full payment</p><p class="mt-1 text-xs text-slate-500">Bayar 100%</p></button><button type="button" class="rounded-2xl border p-4 text-left" :class="paymentOption === 'half_payment' ? 'border-slate-900 bg-slate-50 ring-2 ring-slate-900/10' : 'border-slate-200'" @click="paymentOption = 'half_payment'"><p class="font-bold">Half payment</p><p class="mt-1 text-xs text-slate-500">Bayar 50% / DP</p></button><button type="button" class="rounded-2xl border p-4 text-left" :class="paymentOption === 'pay_later' ? 'border-slate-900 bg-slate-50 ring-2 ring-slate-900/10' : 'border-slate-200'" @click="paymentOption = 'pay_later'"><p class="font-bold">Bayar nanti</p><p class="mt-1 text-xs text-slate-500">Belum bayar</p></button></div><div v-if="paymentOption !== 'pay_later'" class="mt-5"><input id="payment-proof" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="handleProofUpload" /><label for="payment-proof" class="flex cursor-pointer flex-col items-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-7 text-center hover:border-slate-900"><Upload class="size-7 text-slate-400" /><p class="mt-3 text-sm font-bold">Upload bukti pembayaran</p><p class="mt-1 text-xs text-slate-400">JPG, PNG, WebP • maksimal 5 MB</p></label><div v-if="paymentProofPreview" class="relative mt-4 overflow-hidden rounded-2xl border"><img :src="paymentProofPreview" alt="Bukti pembayaran" class="max-h-72 w-full object-contain" /><button type="button" class="absolute right-3 top-3 flex size-9 items-center justify-center rounded-full bg-white shadow" @click="removeProof"><X class="size-4" /></button></div></div><div class="mt-5 flex justify-between border-t border-slate-100 pt-5 text-sm"><span class="text-slate-500">Bayar sekarang</span><b>{{ formatPrice(amountToPay) }}</b></div></div></div>
                    </div>

                    <aside class="lg:sticky lg:top-24 lg:self-start"><div class="rounded-[2rem] bg-slate-900 text-white shadow-2xl"><div class="p-7 sm:p-8"><p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Booking summary</p><h2 class="mt-2 text-2xl font-black">{{ selectedSpace?.name ?? 'Choose a space' }}</h2><div class="my-7 space-y-4 border-y border-white/10 py-6 text-sm"><div class="flex justify-between gap-4"><span class="text-slate-500">Nama</span><span class="font-semibold">{{ guestName || '—' }}</span></div><div class="flex justify-between gap-4"><span class="text-slate-500">Area</span><span class="font-semibold">{{ selectedZone?.name ?? '—' }}</span></div><div class="flex justify-between gap-4"><span class="text-slate-500">Tanggal</span><span class="font-semibold">{{ bookingDate || '—' }}</span></div><div class="flex justify-between gap-4"><span class="text-slate-500">Jam</span><span class="font-semibold">{{ selectedTime || '—' }}</span></div></div><p class="text-xs uppercase tracking-widest text-slate-500">Total</p><p class="mt-1 text-3xl font-black">{{ formatPrice(totalAmount) }}</p><p class="mt-1 text-xs text-slate-500">{{ selectedRate?.rental_type || '—' }}</p><button type="submit" :disabled="!canSubmit || submitting" class="mt-7 flex w-full items-center justify-center gap-2 rounded-2xl bg-white px-5 py-4 text-sm font-bold text-slate-900" :class="!canSubmit || submitting ? 'cursor-not-allowed opacity-40' : 'hover:bg-slate-100'">{{ submitting ? 'Mengirim...' : 'Kirim Booking' }} <ChevronRight class="size-4" /></button><p class="mt-4 text-center text-[11px] leading-5 text-slate-500">Status awal: <b class="text-slate-300">Pending</b>. Admin harus approve terlebih dahulu.</p><p v-if="submitError" class="mt-4 rounded-xl bg-red-500/10 p-3 text-xs text-red-300">{{ submitError }}</p></div></div></aside>
                </form>
                <div v-else class="rounded-[2rem] border border-dashed border-slate-300 bg-white p-14 text-center"><h2 class="text-xl font-black">Booking online belum tersedia</h2><p class="mt-2 text-sm text-slate-500">Belum ada area yang diaktifkan untuk online booking.</p></div>
            </section>
        </main>

        <footer class="border-t border-slate-200 bg-white"><div class="mx-auto flex max-w-7xl justify-between px-5 py-8 text-xs text-slate-400 sm:px-8"><p>© {{ new Date().getFullYear() }} Sport Center</p><p>Play. Move. Connect.</p></div></footer>
    </div>
</template>
