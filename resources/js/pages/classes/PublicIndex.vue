<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import PublicLayout from '@/layouts/PublicLayout.vue';

type Schedule = {
    id: number;
    session_date: string;
    class_name: string;
    description: string | null;
    facility: string | null;
    trainer: string | null;
    trainer_specialty: string | null;
    day: string;
    start_time: string;
    end_time: string;
    capacity: number;
    registered_count: number;
    available_count: number;
    price: number;
};

const props = defineProps<{
    schedules: Schedule[];
    currentUser: { name: string; email: string } | null;
    success?: string | null;
    selectedScheduleId?: number | null;
}>();

const selected = ref<Schedule | null>(null);
const form = useForm({
    session_date: '',
    payment_method: 'bank_transfer' as 'bank_transfer' | 'cash',
    guest_name: props.currentUser?.name ?? '',
    guest_email: props.currentUser?.email ?? '',
    guest_phone: '',
});
const isGuest = computed(() => !props.currentUser);
const money = (value: number) => new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
}).format(value);
const dateLabel = (value: string) => new Date(value + 'T00:00:00').toLocaleDateString('id-ID', {
    weekday: 'long', day: '2-digit', month: 'long', year: 'numeric',
});
const dayLabel = (value: string) => ({
    monday: 'Senin', tuesday: 'Selasa', wednesday: 'Rabu', thursday: 'Kamis',
    friday: 'Jumat', saturday: 'Sabtu', sunday: 'Minggu',
}[value.toLowerCase()] ?? value);

onMounted(() => {
    if (!props.selectedScheduleId) return;
    const target = props.schedules.find((schedule) => schedule.id === props.selectedScheduleId);
    if (target) openRegistration(target);
});

function openRegistration(schedule: Schedule) {
    if (schedule.available_count < 1) return;
    selected.value = schedule;
    form.session_date = schedule.session_date;
    form.clearErrors();
}

function closeRegistration() {
    selected.value = null;
    form.reset('guest_phone');
}

function submit() {
    if (!selected.value) return;
    form.post('/kelas/' + selected.value.id + '/register', {
        preserveScroll: true,
        onSuccess: () => { selected.value = null; form.reset('guest_phone'); },
    });
}
</script>

<template>
    <Head title="Pendaftaran Kelas" />
    <PublicLayout>
        <main class="min-h-screen bg-slate-50 px-4 py-10 text-slate-900">
            <div class="mx-auto max-w-6xl">
                <header class="mb-8">
                    <p class="text-xs font-bold uppercase tracking-[.18em] text-lime-700">Sport Center · Kelas</p>
                    <h1 class="mt-2 text-3xl font-black tracking-tight">Pilih sesi kelasmu</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Pilih tanggal yang tersedia, isi data peserta, lalu konfirmasi pendaftaran. Tidak perlu login untuk mendaftar sebagai guest.</p>
                </header>

                <div v-if="props.success" class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">{{ props.success }}</div>

                <div v-if="schedules.length" class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    <article v-for="schedule in schedules" :key="schedule.id + '-' + schedule.session_date" class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-lime-700">{{ schedule.facility || 'Sport Center' }}</p>
                                <h2 class="mt-1 text-xl font-extrabold">{{ schedule.class_name }}</h2>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ dayLabel(schedule.day) }}</span>
                        </div>
                        <p v-if="schedule.description" class="mt-3 line-clamp-2 text-sm text-slate-500">{{ schedule.description }}</p>
                        <div class="mt-4 space-y-2 text-sm text-slate-600">
                            <p>📅 {{ dateLabel(schedule.session_date) }}</p>
                            <p>🕒 {{ schedule.start_time }}–{{ schedule.end_time }}</p>
                            <p>🏋️ {{ schedule.trainer || 'Trainer akan diumumkan' }}</p>
                            <p>👥 {{ schedule.available_count }} dari {{ schedule.capacity }} tempat tersedia</p>
                        </div>
                        <div class="mt-auto border-t border-slate-100 pt-4">
                            <p class="text-lg font-black">{{ money(schedule.price) }}</p>
                            <p class="mb-3 text-xs text-slate-400">Harga per peserta</p>
                            <button type="button" :disabled="schedule.available_count < 1" class="w-full rounded-xl bg-[#a4da01] px-4 py-3 text-sm font-extrabold text-[#001428] transition hover:bg-lime-300 disabled:cursor-not-allowed disabled:opacity-50" @click="openRegistration(schedule)">
                                {{ schedule.available_count < 1 ? 'Kuota penuh' : 'Daftar sesi ini' }}
                            </button>
                        </div>
                    </article>
                </div>
                <div v-else class="rounded-2xl border border-slate-200 bg-white px-6 py-16 text-center">
                    <div class="text-4xl">🧘</div>
                    <h2 class="mt-3 text-lg font-bold">Belum ada jadwal kelas</h2>
                    <p class="mt-1 text-sm text-slate-500">Jadwal kelas akan muncul di sini ketika sudah tersedia.</p>
                </div>
            </div>

            <div v-if="selected" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/60 p-4" @click.self="closeRegistration">
                <section class="my-auto w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-lime-700">Konfirmasi pendaftaran</p>
                            <h2 class="mt-1 text-2xl font-black">{{ selected.class_name }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ dateLabel(selected.session_date) }} · {{ selected.start_time }}–{{ selected.end_time }}</p>
                        </div>
                        <button type="button" class="rounded-lg px-3 py-1 text-xl text-slate-400 hover:bg-slate-100" @click="closeRegistration">×</button>
                    </div>

                    <form class="mt-6 space-y-4" @submit.prevent="submit">
                        <div v-if="isGuest" class="rounded-xl bg-slate-50 p-3 text-xs leading-5 text-slate-600">Kamu mendaftar sebagai guest. Tidak perlu membuat akun.</div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold">Nama lengkap</label>
                            <input v-model="form.guest_name" type="text" maxlength="255" required :readonly="!isGuest" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 outline-none focus:border-lime-500" />
                            <p v-if="form.errors.guest_name" class="mt-1 text-xs text-red-600">{{ form.errors.guest_name }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold">Email</label>
                            <input v-model="form.guest_email" type="email" maxlength="255" required :readonly="!isGuest" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 outline-none focus:border-lime-500" />
                            <p v-if="form.errors.guest_email" class="mt-1 text-xs text-red-600">{{ form.errors.guest_email }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold">Nomor HP</label>
                            <input v-model="form.guest_phone" type="tel" maxlength="50" :required="isGuest" placeholder="08xxxxxxxxxx" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 outline-none focus:border-lime-500" />
                            <p v-if="form.errors.guest_phone" class="mt-1 text-xs text-red-600">{{ form.errors.guest_phone }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold">Metode pembayaran</label>
                            <select v-model="form.payment_method" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 outline-none focus:border-lime-500">
                                <option value="bank_transfer">Transfer bank</option>
                                <option value="cash">Tunai di tempat</option>
                            </select>
                        </div>
                        <div class="flex items-center justify-between rounded-xl bg-slate-50 p-4">
                            <span class="text-sm font-semibold text-slate-600">Total pembayaran</span>
                            <strong class="text-lg">{{ money(selected.price) }}</strong>
                        </div>
                        <p v-if="form.errors.schedule" class="text-sm text-red-600">{{ form.errors.schedule }}</p>
                        <div class="flex gap-3 pt-2">
                            <button type="button" class="flex-1 rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold" @click="closeRegistration">Batal</button>
                            <button type="submit" :disabled="form.processing" class="flex-1 rounded-xl bg-[#a4da01] px-4 py-3 text-sm font-extrabold text-[#001428] disabled:opacity-50">{{ form.processing ? 'Memproses...' : 'Konfirmasi daftar' }}</button>
                        </div>
                    </form>
                </section>
            </div>
        </main>
    </PublicLayout>
</template>
