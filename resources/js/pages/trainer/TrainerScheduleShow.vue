<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, CalendarDays, CheckCircle2, Clock3, Dumbbell, UserRound } from '@lucide/vue';
import TrainerDashboardLayout from '@/layouts/trainer/TrainerDashboardLayout.vue';

defineOptions({ layout: TrainerDashboardLayout });

type Schedule = {
  id: number; member: string; trainer: string; package: string; facility: string | null;
  date: string; start_time: string; end_time: string;
  member_attended: boolean; trainer_attended: boolean;
  member_confirmed_at: string | null; trainer_confirmed_at: string | null;
  status: string; viewer: 'member' | 'trainer'; can_confirm: boolean;
};
const props = defineProps<{ schedule: Schedule }>();
const dateLabel = (value: string) => new Date(value + 'T00:00:00').toLocaleDateString('id-ID', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
const attendanceLabel = (confirmed: string | null, attended: boolean) => !confirmed ? 'Belum dikonfirmasi' : attended ? 'Hadir' : 'Tidak hadir';
const attendanceClass = (confirmed: string | null, attended: boolean) => !confirmed ? 'bg-amber-50 text-amber-700' : attended ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700';
function confirm(attended: boolean) {
  const url = props.schedule.viewer === 'member'
    ? '/member/trainer-schedules/' + props.schedule.id + '/confirm'
    : '/trainer/schedules/' + props.schedule.id + '/confirm';
  router.post(url, { attended });
}
</script>
<template>
  <Head title="Detail Jadwal Sesi" />
  <div class="space-y-6">
    <header class="flex items-center gap-3">
      <Link href="/trainer/dashboard" class="flex size-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50" aria-label="Kembali ke dashboard"><ArrowLeft class="size-4" /></Link>
      <div><p class="text-xs font-bold uppercase tracking-[.18em] text-lime-700">Personal training</p><h1 class="mt-1 text-2xl font-bold text-slate-900">Detail Jadwal Sesi</h1></div>
    </header>
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="bg-[#001428] px-6 py-6 text-white sm:px-8"><p class="text-xs font-bold uppercase tracking-wider text-lime-300">{{ schedule.facility || 'Sport Center' }}</p><h2 class="mt-2 text-2xl font-bold">{{ schedule.package }}</h2><p class="mt-2 text-sm text-slate-300">Jadwal sesi #{{ schedule.id }}</p></div>
      <div class="grid gap-4 p-6 sm:grid-cols-2">
        <div class="rounded-xl bg-slate-50 p-4"><div class="flex items-center gap-2 text-xs text-slate-500"><CalendarDays class="size-4" /> Tanggal sesi</div><p class="mt-2 font-semibold text-slate-900">{{ dateLabel(schedule.date) }}</p></div>
        <div class="rounded-xl bg-slate-50 p-4"><div class="flex items-center gap-2 text-xs text-slate-500"><Clock3 class="size-4" /> Waktu</div><p class="mt-2 font-semibold text-slate-900">{{ schedule.start_time }} – {{ schedule.end_time }}</p></div>
        <div class="rounded-xl bg-slate-50 p-4"><div class="flex items-center gap-2 text-xs text-slate-500"><UserRound class="size-4" /> Member</div><p class="mt-2 font-semibold text-slate-900">{{ schedule.member }}</p></div>
        <div class="rounded-xl bg-slate-50 p-4"><div class="flex items-center gap-2 text-xs text-slate-500"><Dumbbell class="size-4" /> Trainer</div><p class="mt-2 font-semibold text-slate-900">{{ schedule.trainer }}</p></div>
      </div>
    </section>
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex items-center gap-2"><CheckCircle2 class="size-5 text-lime-700" /><h2 class="font-bold text-slate-900">Konfirmasi kehadiran</h2></div>
      <p class="mt-1 text-sm text-slate-500">Kedua pihak dapat mengonfirmasi sebelum hari latihan. Hasil sesi baru bisa ditetapkan admin setelah jadwal berakhir.</p>
      <div class="mt-5 grid gap-3 sm:grid-cols-2">
        <div class="rounded-xl border border-slate-100 p-4"><p class="text-sm font-semibold text-slate-700">Member</p><span class="mt-2 inline-flex rounded-full px-3 py-1.5 text-xs font-bold" :class="attendanceClass(schedule.member_confirmed_at, schedule.member_attended)">{{ attendanceLabel(schedule.member_confirmed_at, schedule.member_attended) }}</span><p v-if="schedule.member_confirmed_at" class="mt-2 text-xs text-slate-400">Sudah memberikan konfirmasi</p></div>
        <div class="rounded-xl border border-slate-100 p-4"><p class="text-sm font-semibold text-slate-700">Trainer</p><span class="mt-2 inline-flex rounded-full px-3 py-1.5 text-xs font-bold" :class="attendanceClass(schedule.trainer_confirmed_at, schedule.trainer_attended)">{{ attendanceLabel(schedule.trainer_confirmed_at, schedule.trainer_attended) }}</span><p v-if="schedule.trainer_confirmed_at" class="mt-2 text-xs text-slate-400">Sudah memberikan konfirmasi</p></div>
      </div>
      <div v-if="schedule.status === 'scheduled' && schedule.can_confirm" class="mt-6 rounded-xl bg-lime-50 p-4">
        <p class="text-sm font-semibold text-slate-900">Konfirmasi kehadiran kamu</p><p class="mt-1 text-xs text-slate-600">Pilih sesuai rencana kehadiranmu pada sesi ini.</p>
        <div class="mt-4 flex flex-wrap gap-2"><button type="button" @click="confirm(true)" class="rounded-xl bg-lime-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-lime-700">Hadir</button><button type="button" @click="confirm(false)" class="rounded-xl border border-rose-200 bg-white px-5 py-2.5 text-sm font-bold text-rose-700 transition hover:bg-rose-50">Tidak hadir</button></div>
      </div>
      <div v-else-if="schedule.status !== 'scheduled'" class="mt-6 rounded-xl bg-slate-50 p-4 text-sm font-semibold text-slate-600">Jadwal sudah ditutup. Status: {{ schedule.status === 'completed' ? 'Sesi berhasil' : 'Sesi tidak berhasil' }}.</div>
      <div v-else class="mt-6 rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-700">Konfirmasi kehadiran kamu sudah tersimpan.</div>
    </section>
  </div>
</template>