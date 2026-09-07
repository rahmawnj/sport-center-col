<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, Check, ChevronRight, Clock3, MapPin, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Rate {
    id: number;
    rental_type: string;
    price: string | number;
}

interface Facility {
    id: number;
    name: string;
}

interface Space {
    id: number;
    name: string;
    capacity: number | null;
    status: string;
    facilities: Facility[];
    pricing_rates: Rate[];
}

interface Zone {
    id: number;
    name: string;
    pricing_model: string;
    is_online_bookable: boolean;
    zone_spaces: Space[];
}

const props = defineProps<{ zones: Zone[]; initial: { date?: string; zone_id?: string } }>();

const selectedZoneId = ref(props.initial.zone_id ? Number(props.initial.zone_id) : props.zones[0]?.id ?? null);
const selectedSpaceId = ref<number | null>(null);
const bookingDate = ref(props.initial.date ?? '');
const selectedTime = ref('');

const selectedZone = computed(() => props.zones.find((zone) => zone.id === selectedZoneId.value));
const selectedSpace = computed(() => selectedZone.value?.zone_spaces.find((space) => space.id === selectedSpaceId.value));

const selectZone = (zone: Zone) => {
    selectedZoneId.value = zone.id;
    selectedSpaceId.value = null;
};

const formatPrice = (price: string | number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(price));

const today = new Date().toISOString().slice(0, 10);
</script>

<template>
    <Head title="Booking" />

    <div class="min-h-screen bg-slate-50 text-slate-900">
        <header class="border-b bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5 lg:px-8">
                <Link href="/" class="text-xl font-bold tracking-tight">Sport Center</Link>
                <div class="flex items-center gap-3">
                    <Link href="/login" class="rounded-xl px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Login</Link>
                    <Link href="/register" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Register</Link>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
            <section class="mb-8">
                <p class="mb-2 text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Online Booking</p>
                <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">Book your favorite space</h1>
                <p class="mt-2 max-w-2xl text-slate-500">Pilih area olahraga, space, tanggal, dan waktu yang kamu inginkan.</p>
            </section>

            <div v-if="zones.length" class="grid gap-8 lg:grid-cols-[1fr_360px]">
                <section class="space-y-7">
                    <div>
                        <div class="mb-3 flex items-center gap-2 text-sm font-semibold"><span class="flex size-7 items-center justify-center rounded-full bg-slate-900 text-xs text-white">1</span> Pilih area</div>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <button
                                v-for="zone in zones"
                                :key="zone.id"
                                type="button"
                                class="rounded-2xl border bg-white p-5 text-left transition hover:-translate-y-0.5 hover:border-slate-400 hover:shadow-sm"
                                :class="selectedZoneId === zone.id ? 'border-slate-900 ring-2 ring-slate-900/10' : 'border-slate-200'"
                                @click="selectZone(zone)"
                            >
                                <div class="mb-4 flex items-center justify-between">
                                    <div class="flex size-10 items-center justify-center rounded-xl bg-slate-100"><MapPin class="size-5" /></div>
                                    <Check v-if="selectedZoneId === zone.id" class="size-5" />
                                </div>
                                <p class="font-semibold">{{ zone.name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ zone.zone_spaces.length }} space tersedia</p>
                            </button>
                        </div>
                    </div>

                    <div v-if="selectedZone">
                        <div class="mb-3 flex items-center gap-2 text-sm font-semibold"><span class="flex size-7 items-center justify-center rounded-full bg-slate-900 text-xs text-white">2</span> Pilih space</div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <button
                                v-for="space in selectedZone.zone_spaces"
                                :key="space.id"
                                type="button"
                                class="rounded-2xl border bg-white p-5 text-left transition hover:border-slate-400 hover:shadow-sm"
                                :class="selectedSpaceId === space.id ? 'border-slate-900 ring-2 ring-slate-900/10' : 'border-slate-200'"
                                @click="selectedSpaceId = space.id"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="font-semibold">{{ space.name }}</p>
                                        <div class="mt-2 flex flex-wrap gap-3 text-xs text-slate-500">
                                            <span v-if="space.capacity" class="inline-flex items-center gap-1"><Users class="size-3.5" /> {{ space.capacity }} orang</span>
                                            <span class="inline-flex items-center gap-1"><Clock3 class="size-3.5" /> Available</span>
                                        </div>
                                    </div>
                                    <Check v-if="selectedSpaceId === space.id" class="size-5 shrink-0" />
                                </div>
                                <div v-if="space.facilities.length" class="mt-4 flex flex-wrap gap-1.5">
                                    <span v-for="facility in space.facilities" :key="facility.id" class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] text-slate-600">{{ facility.name }}</span>
                                </div>
                            </button>
                        </div>
                    </div>

                    <div>
                        <div class="mb-3 flex items-center gap-2 text-sm font-semibold"><span class="flex size-7 items-center justify-center rounded-full bg-slate-900 text-xs text-white">3</span> Pilih jadwal</div>
                        <div class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 sm:grid-cols-2">
                            <label class="block">
                                <span class="mb-2 block text-sm font-medium">Tanggal</span>
                                <div class="relative">
                                    <CalendarDays class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                                    <input v-model="bookingDate" :min="today" type="date" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10" />
                                </div>
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-sm font-medium">Waktu</span>
                                <input v-model="selectedTime" type="time" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10" />
                            </label>
                        </div>
                    </div>
                </section>

                <aside class="lg:sticky lg:top-6 lg:self-start">
                    <div class="rounded-3xl bg-slate-900 p-6 text-white shadow-xl">
                        <p class="text-sm font-medium text-slate-400">Booking summary</p>
                        <h2 class="mt-2 text-xl font-semibold">{{ selectedZone?.name ?? 'Pilih area' }}</h2>
                        <div class="my-6 space-y-4 border-y border-white/10 py-5 text-sm">
                            <div class="flex justify-between gap-4"><span class="text-slate-400">Space</span><span class="text-right font-medium">{{ selectedSpace?.name ?? '—' }}</span></div>
                            <div class="flex justify-between gap-4"><span class="text-slate-400">Tanggal</span><span class="text-right font-medium">{{ bookingDate || '—' }}</span></div>
                            <div class="flex justify-between gap-4"><span class="text-slate-400">Waktu</span><span class="text-right font-medium">{{ selectedTime || '—' }}</span></div>
                        </div>
                        <div v-if="selectedSpace?.pricing_rates?.length" class="mb-5 space-y-2">
                            <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Harga</p>
                            <div v-for="rate in selectedSpace.pricing_rates" :key="rate.id" class="flex justify-between text-sm">
                                <span class="text-slate-300">{{ rate.rental_type }}</span><span>{{ formatPrice(rate.price) }}</span>
                            </div>
                        </div>
                        <button type="button" class="flex w-full items-center justify-center gap-2 rounded-xl bg-white px-4 py-3 font-semibold text-slate-900 transition hover:bg-slate-100" :disabled="!selectedSpace || !bookingDate || !selectedTime" :class="(!selectedSpace || !bookingDate || !selectedTime) ? 'cursor-not-allowed opacity-50' : ''">
                            Lanjutkan Booking <ChevronRight class="size-4" />
                        </button>
                        <p class="mt-3 text-center text-xs text-slate-500">Login tidak diperlukan untuk melihat dan memilih jadwal.</p>
                    </div>
                </aside>
            </div>

            <div v-else class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">
                <h2 class="text-lg font-semibold">Booking online belum tersedia</h2>
                <p class="mt-2 text-sm text-slate-500">Belum ada area yang diaktifkan untuk online booking.</p>
            </div>
        </main>
    </div>
</template>
