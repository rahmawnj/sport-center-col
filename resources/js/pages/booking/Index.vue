<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, Check, ChevronRight, Clock3, MapPin, Users } from '@lucide/vue';
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
const selectedZone = computed(() => props.zones.find((zone) => zone.id === selectedZoneId.value));
const selectedSpace = computed(() => selectedZone.value?.zone_spaces.find((space) => space.id === selectedSpaceId.value));
const selectZone = (zone: Zone) => { selectedZoneId.value = zone.id; selectedSpaceId.value = null; };
const formatPrice = (price: string | number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(price));
const today = new Date().toISOString().slice(0, 10);
</script>

<template>
    <Head title="Online Booking" />
    <div class="min-h-screen bg-[#f7f8f6] text-slate-900">
        <header class="sticky top-0 z-30 border-b border-white/60 bg-[#f7f8f6]/90 backdrop-blur-xl">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-8">
                <Link href="/" class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-2xl bg-slate-900 text-sm font-black text-white shadow-lg shadow-slate-900/10">SC</div>
                    <div>
                        <p class="text-sm font-bold tracking-tight">Sport Center</p>
                        <p class="text-[10px] font-medium uppercase tracking-[0.2em] text-slate-400">Play. Move. Connect.</p>
                    </div>
                </Link>
                <Link href="/" class="hidden rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold transition hover:border-slate-300 hover:shadow-sm sm:inline-flex">Back to Home</Link>
            </div>
        </header>

        <main>
            <section class="relative overflow-hidden bg-slate-950 text-white">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_20%,rgba(255,255,255,0.12),transparent_32%),radial-gradient(circle_at_15%_90%,rgba(148,163,184,0.16),transparent_28%)]"></div>
                <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-5 py-16 sm:px-8 lg:grid-cols-[1.15fr_.85fr] lg:py-24">
                    <div>
                        <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-semibold tracking-wide text-slate-200 backdrop-blur"><span class="size-1.5 rounded-full bg-emerald-400"></span> Online booking is open</div>
                        <h1 class="max-w-3xl text-4xl font-black leading-[1.05] tracking-[-0.04em] sm:text-6xl">Your game.<br /><span class="text-slate-400">Your time.</span> Your space.</h1>
                        <p class="mt-6 max-w-xl text-base leading-7 text-slate-300 sm:text-lg">Pesan fasilitas olahraga favoritmu dengan mudah. Pilih area, space, tanggal, dan waktu — tanpa perlu masuk ke dashboard.</p>
                        <div class="mt-8 flex flex-wrap gap-3 text-sm text-slate-300"><span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2"><Check class="size-4" /> Public booking</span><span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2"><Clock3 class="size-4" /> Pilih waktu sendiri</span></div>
                    </div>
                    <div class="hidden lg:block">
                        <div class="relative ml-auto max-w-md rounded-[2rem] border border-white/10 bg-white/[0.07] p-3 shadow-2xl backdrop-blur">
                            <div class="rounded-[1.5rem] bg-white p-7 text-slate-900">
                                <div class="flex items-center justify-between"><div><p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Quick booking</p><p class="mt-1 text-lg font-bold">Find your court</p></div><div class="flex size-11 items-center justify-center rounded-2xl bg-slate-100"><CalendarDays class="size-5" /></div></div>
                                <div class="mt-7 space-y-3"><div class="h-12 rounded-xl bg-slate-100"></div><div class="grid grid-cols-2 gap-3"><div class="h-12 rounded-xl bg-slate-100"></div><div class="h-12 rounded-xl bg-slate-100"></div></div><div class="h-12 rounded-xl bg-slate-900"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-7xl px-5 py-12 sm:px-8 lg:py-16">
                <div v-if="zones.length" class="grid gap-10 lg:grid-cols-[1fr_380px]">
                    <div class="space-y-10">
                        <div>
                            <div class="mb-5 flex items-end justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Step 01</p><h2 class="mt-1 text-2xl font-black tracking-tight sm:text-3xl">Choose your area</h2></div><span class="text-sm text-slate-400">{{ zones.length }} area</span></div>
                            <div class="grid gap-4 sm:grid-cols-3">
                                <button v-for="zone in zones" :key="zone.id" type="button" class="group rounded-3xl border bg-white p-5 text-left transition duration-200 hover:-translate-y-1 hover:border-slate-300 hover:shadow-xl hover:shadow-slate-200/50" :class="selectedZoneId === zone.id ? 'border-slate-900 ring-2 ring-slate-900/10' : 'border-slate-200'" @click="selectZone(zone)">
                                    <div class="mb-7 flex items-center justify-between"><div class="flex size-11 items-center justify-center rounded-2xl bg-slate-100 transition group-hover:bg-slate-900 group-hover:text-white"><MapPin class="size-5" /></div><Check v-if="selectedZoneId === zone.id" class="size-5" /></div>
                                    <p class="font-bold">{{ zone.name }}</p><p class="mt-1 text-xs leading-5 text-slate-400">{{ zone.zone_spaces.length }} space tersedia untuk booking</p>
                                </button>
                            </div>
                        </div>

                        <div v-if="selectedZone">
                            <div class="mb-5 flex items-end justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Step 02</p><h2 class="mt-1 text-2xl font-black tracking-tight sm:text-3xl">Choose your space</h2></div><span class="text-sm text-slate-400">{{ selectedZone.zone_spaces.length }} space</span></div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <button v-for="space in selectedZone.zone_spaces" :key="space.id" type="button" class="rounded-3xl border bg-white p-6 text-left transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-lg" :class="selectedSpaceId === space.id ? 'border-slate-900 ring-2 ring-slate-900/10' : 'border-slate-200'" @click="selectedSpaceId = space.id">
                                    <div class="flex items-start justify-between gap-4"><div><p class="font-bold">{{ space.name }}</p><div class="mt-3 flex flex-wrap gap-3 text-xs text-slate-400"><span v-if="space.capacity" class="inline-flex items-center gap-1.5"><Users class="size-3.5" /> {{ space.capacity }} orang</span><span class="inline-flex items-center gap-1.5"><Clock3 class="size-3.5" /> Available</span></div></div><div class="flex size-7 shrink-0 items-center justify-center rounded-full border" :class="selectedSpaceId === space.id ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200'"><Check v-if="selectedSpaceId === space.id" class="size-4" /></div></div>
                                    <div v-if="space.facilities.length" class="mt-5 flex flex-wrap gap-1.5"><span v-for="facility in space.facilities" :key="facility.id" class="rounded-full bg-slate-100 px-3 py-1.5 text-[11px] font-medium text-slate-500">{{ facility.name }}</span></div>
                                </button>
                            </div>
                        </div>

                        <div>
                            <div class="mb-5"><p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Step 03</p><h2 class="mt-1 text-2xl font-black tracking-tight sm:text-3xl">Pick your schedule</h2></div>
                            <div class="grid gap-4 rounded-3xl border border-slate-200 bg-white p-5 sm:grid-cols-2 sm:p-6">
                                <label class="block"><span class="mb-2 block text-sm font-bold">Date</span><div class="relative"><CalendarDays class="pointer-events-none absolute left-4 top-1/2 size-4 -translate-y-1/2 text-slate-400" /><input v-model="bookingDate" :min="today" type="date" class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3.5 pl-11 pr-3 text-sm outline-none transition focus:border-slate-900 focus:bg-white focus:ring-4 focus:ring-slate-900/5" /></div></label>
                                <label class="block"><span class="mb-2 block text-sm font-bold">Time</span><input v-model="selectedTime" type="time" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm outline-none transition focus:border-slate-900 focus:bg-white focus:ring-4 focus:ring-slate-900/5" /></label>
                            </div>
                        </div>
                    </div>

                    <aside class="lg:sticky lg:top-24 lg:self-start">
                        <div class="overflow-hidden rounded-[2rem] bg-slate-900 text-white shadow-2xl shadow-slate-300/40">
                            <div class="p-7 sm:p-8">
                                <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Your booking</p>
                                <h2 class="mt-2 text-2xl font-black tracking-tight">{{ selectedSpace?.name ?? 'Choose a space' }}</h2>
                                <div class="my-7 space-y-4 border-y border-white/10 py-6 text-sm"><div class="flex justify-between gap-4"><span class="text-slate-500">Area</span><span class="font-semibold">{{ selectedZone?.name ?? '—' }}</span></div><div class="flex justify-between gap-4"><span class="text-slate-500">Date</span><span class="font-semibold">{{ bookingDate || '—' }}</span></div><div class="flex justify-between gap-4"><span class="text-slate-500">Time</span><span class="font-semibold">{{ selectedTime || '—' }}</span></div></div>
                                <div v-if="selectedSpace?.pricing_rates?.length" class="mb-7"><p class="mb-3 text-xs font-bold uppercase tracking-widest text-slate-500">Pricing</p><div class="space-y-2.5"> <div v-for="rate in selectedSpace.pricing_rates" :key="rate.id" class="flex justify-between text-sm"><span class="text-slate-300">{{ rate.rental_type }}</span><span class="font-semibold">{{ formatPrice(rate.price) }}</span></div></div></div>
                                <button type="button" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-white px-5 py-4 text-sm font-bold text-slate-900 transition hover:bg-slate-100" :disabled="!selectedSpace || !bookingDate || !selectedTime" :class="(!selectedSpace || !bookingDate || !selectedTime) ? 'cursor-not-allowed opacity-40' : ''">Continue Booking <ChevronRight class="size-4" /></button>
                                <p class="mt-4 text-center text-[11px] leading-5 text-slate-500">Kamu bisa memilih jadwal tanpa login.</p>
                            </div>
                        </div>
                    </aside>
                </div>
                <div v-else class="rounded-[2rem] border border-dashed border-slate-300 bg-white p-14 text-center"><div class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-100"><CalendarDays class="size-6 text-slate-400" /></div><h2 class="mt-5 text-xl font-black">Booking online belum tersedia</h2><p class="mt-2 text-sm text-slate-500">Belum ada area yang diaktifkan untuk online booking.</p></div>
            </section>
        </main>

        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl flex-col gap-2 px-5 py-8 text-xs text-slate-400 sm:px-8 sm:flex-row sm:items-center sm:justify-between"><p>© {{ new Date().getFullYear() }} Sport Center</p><p>Play. Move. Connect.</p></div>
        </footer>
    </div>
</template>
