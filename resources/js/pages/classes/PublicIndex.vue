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
    <Head title="Kelas" />
    <PublicLayout>
        <div class="min-h-screen bg-slate-50 text-slate-900">
            <main class="mx-auto w-full max-w-5xl px-4 py-8">
                <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">Pendaftaran kelas</h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Pilih jadwal kelas yang tersedia, lalu isi data peserta untuk mendaftar.
                        </p>
                    </div>
                </div>

                <div v-if="props.success" class="mb-5 rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-sm text-emerald-800">
                    {{ props.success }}
                </div>

                <section class="rounded-2xl bg-white p-4 shadow-[0_8px_30px_rgba(15,23,42,0.06)] md:p-6">
                    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="font-semibold">Pilih sesi kelas</h2>
                            <p class="mt-1 text-xs text-slate-500">Jadwal rutin mingguan yang tersedia untuk pendaftaran.</p>
                        </div>
                        <span class="rounded-full bg-lime-50 px-3 py-1.5 text-xs font-semibold text-lime-800">
                            {{ schedules.length }} sesi tersedia
                        </span>
                    </div>

                    <div v-if="schedules.length" class="divide-y divide-slate-100">
                        <article
                            v-for="schedule in schedules"
                            :key="schedule.id + '-' + schedule.session_date"
                            class="flex flex-col gap-4 py-5 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-lime-50 px-2.5 py-1 text-[11px] font-bold text-lime-800">
                                        {{ schedule.facility || 'Sport Center' }}
                                    </span>
                                    <span class="text-xs text-slate-400">{{ dayLabel(schedule.day) }}</span>
                                </div>
                                <h3 class="mt-2 text-lg font-bold tracking-tight text-slate-900">{{ schedule.class_name }}</h3>
                                <p v-if="schedule.description" class="mt-1 max-w-2xl text-sm leading-6 text-slate-500">{{ schedule.description }}</p>
                                <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-600">
                                    <span>📅 {{ dateLabel(schedule.session_date) }}</span>
                                    <span>🕒 {{ schedule.start_time }}–{{ schedule.end_time }}</span>
                                    <span>🏋️ {{ schedule.trainer || 'Trainer akan diumumkan' }}</span>
                                    <span>👥 {{ schedule.available_count }} tempat tersedia</span>
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-row items-center justify-between gap-4 border-t border-slate-100 pt-4 sm:min-w-44 sm:flex-col sm:items-end sm:border-0 sm:pt-0">
                                <div class="sm:text-right">
                                    <p class="text-xs text-slate-400">Harga per peserta</p>
                                    <p class="text-lg font-bold text-slate-900">{{ money(schedule.price) }}</p>
                                </div>
                                <button
                                    type="button"
                                    :disabled="schedule.available_count < 1"
                                    class="rounded-xl bg-[#a4da01] px-4 py-2.5 text-sm font-bold text-[#001428] transition hover:bg-lime-300 disabled:cursor-not-allowed disabled:opacity-50"
                                    @click="openRegistration(schedule)"
                                >
                                    {{ schedule.available_count < 1 ? 'Kuota penuh' : 'Daftar' }}
                                </button>
                            </div>
                        </article>
                    </div>
                    <div v-else class="rounded-xl bg-slate-50 px-5 py-12 text-center">
                        <div class="text-3xl">🧘</div>
                        <h3 class="mt-3 font-semibold text-slate-800">Belum ada jadwal kelas</h3>
                        <p class="mt-1 text-sm text-slate-500">Jadwal kelas akan muncul di sini ketika sudah tersedia.</p>
                    </div>
                </section>
            </main>

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
        </div>
    </PublicLayout>
</template>
