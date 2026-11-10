<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ArrowLeft, CalendarDays, CheckCircle2, Clock3, Pencil, X, Save, Check, Ban } from '@lucide/vue';
import { Button } from '@/components/ui/button';
type Item = { id:number; member_name:string|null; member_email:string|null; trainer_name:string|null; package_name:string|null; facility_name:string|null; start_date:string|null; expires_at:string|null; total_sessions:number; used_sessions:number; remaining_sessions:number; status:string; membership_active:boolean; membership_package:string|null; membership_end:string|null; membership_status:string };
type Schedule = { id:number; date:string; start_time:string; end_time:string; member_attended:boolean; trainer_attended:boolean; member_confirmed_at:string|null; trainer_confirmed_at:string|null; status:string };
const props = defineProps<{ item:Item; schedules:Schedule[] }>();
const form = useForm({ session_date:'', start_time:'09:00', end_time:'10:00' });
const editingId = ref<number|null>(null);
const editForm = useForm({ session_date:'', start_time:'09:00', end_time:'10:00' });
function submit(){ form.post('/membership-trainers/'+props.item.id+'/schedules',{onSuccess:()=>form.reset('session_date')}); }
function beginEdit(schedule:Schedule){ editingId.value=schedule.id; editForm.clearErrors(); editForm.session_date=schedule.date; editForm.start_time=schedule.start_time; editForm.end_time=schedule.end_time; }
function cancelEdit(){ editingId.value=null; editForm.reset(); editForm.clearErrors(); }
function saveEdit(id:number){ editForm.put('/trainer-session-schedules/'+id,{onSuccess:cancelEdit}); }
function setResult(id:number,result:'completed'|'failed'){ const label=result==='completed'?'BERHASIL':'TIDAK BERHASIL'; if(window.confirm('Tandai sesi ini '+label+'? '+(result==='completed'?'Sisa sesi akan berkurang satu.':'Sisa sesi tidak akan berkurang.'))) router.post('/trainer-session-schedules/'+id+'/result',{result}); }
function attendanceLabel(confirmedAt:string|null,attended:boolean){ return !confirmedAt ? 'Belum dikonfirmasi' : (attended ? 'Hadir' : 'Tidak hadir'); }
function statusLabel(status:string){ return ({scheduled:'Menunggu hasil',completed:'Berhasil',failed:'Tidak berhasil'} as Record<string,string>)[status] || status; }
function formatDate(value:string|null){ return value ? new Intl.DateTimeFormat('id-ID',{day:'2-digit',month:'long',year:'numeric'}).format(new Date(value)) : '—'; }
</script>
<template>
<Head :title="'Detail Paket Trainer · '+(item.member_name || 'Member')" />
<div class="space-y-6 p-4 md:p-6">
<div class="flex items-center gap-3"><Button variant="ghost" size="icon" as-child><Link href="/membership-trainers"><ArrowLeft class="size-4"/></Link></Button><div><p class="text-sm text-muted-foreground">Membership Trainer / Detail</p><h1 class="text-xl font-semibold">Detail Paket Trainer</h1></div></div>
<section class="rounded-2xl border bg-card p-5 shadow-sm md:p-6">
<div class="flex flex-col justify-between gap-4 md:flex-row md:items-start"><div><p class="text-sm text-muted-foreground">Member</p><h2 class="mt-1 text-2xl font-semibold">{{item.member_name || '—'}}</h2><p class="mt-1 text-sm text-muted-foreground">{{item.member_email || '—'}}</p></div><span class="w-fit rounded-full bg-emerald-500/10 px-3 py-1.5 text-xs font-semibold text-emerald-700">{{item.status}}</span></div>
<div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4"><div><p class="text-xs text-muted-foreground">Trainer</p><p class="mt-1 font-medium">{{item.trainer_name || '—'}}</p></div><div><p class="text-xs text-muted-foreground">Paket</p><p class="mt-1 font-medium">{{item.package_name || '—'}}</p></div><div><p class="text-xs text-muted-foreground">Olahraga</p><p class="mt-1 font-medium">{{item.facility_name || '—'}}</p></div><div><p class="text-xs text-muted-foreground">Periode</p><p class="mt-1 font-medium">{{formatDate(item.start_date)}} – {{formatDate(item.expires_at)}}</p></div></div>
<div class="mt-6 grid gap-3 sm:grid-cols-3"><div class="rounded-xl bg-muted/50 p-4"><p class="text-xs text-muted-foreground">Total sesi</p><p class="mt-1 text-2xl font-semibold">{{item.total_sessions}}</p></div><div class="rounded-xl bg-muted/50 p-4"><p class="text-xs text-muted-foreground">Sesi terpakai</p><p class="mt-1 text-2xl font-semibold">{{item.used_sessions}}</p></div><div class="rounded-xl bg-primary/5 p-4"><p class="text-xs text-muted-foreground">Sisa sesi</p><p class="mt-1 text-2xl font-semibold text-primary">{{item.remaining_sessions}}</p></div></div>
</section>
<div class="grid gap-6 xl:grid-cols-[.8fr_1.2fr]">
<section class="h-fit rounded-2xl border bg-card p-5 shadow-sm"><div class="flex items-center gap-2"><CalendarDays class="size-5 text-primary"/><h2 class="font-semibold">Tambah jadwal sesi</h2></div><p class="mt-1 text-sm text-muted-foreground">Jadwal otomatis terhubung ke paket {{item.package_name}} milik {{item.member_name}}.</p>
<div v-if="!item.membership_active" class="mt-5 rounded-xl border border-amber-500/30 bg-amber-500/5 p-4"><p class="text-sm font-semibold text-amber-800">Membership belum aktif</p><p class="mt-1 text-sm text-muted-foreground">Paket personal trainer belum bisa digunakan karena member tidak memiliki membership aktif pada fasilitas {{item.facility_name || 'ini'}}.</p><p class="mt-2 text-xs text-muted-foreground">{{item.membership_status === 'none' ? 'Member belum memiliki membership untuk fasilitas ini.' : item.membership_status === 'upcoming' ? 'Membership member belum memasuki tanggal aktif.' : 'Membership member sudah berakhir atau tidak aktif.'}}</p></div>
<form class="mt-5 space-y-4" @submit.prevent="submit"><div class="space-y-1.5"><label class="text-sm font-medium">Tanggal sesi</label><input v-model="form.session_date" type="date" required :min="item.start_date || undefined" :max="item.membership_active ? (item.membership_end && item.expires_at ? (item.membership_end < item.expires_at ? item.membership_end : item.expires_at) : (item.membership_end || item.expires_at || undefined)) : undefined" :disabled="!item.membership_active || item.remaining_sessions < 1" class="w-full rounded-md border bg-background px-3 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-50"/><p v-if="form.errors.session_date" class="text-xs text-destructive">{{form.errors.session_date}}</p></div>
<div class="grid grid-cols-2 gap-3"><div class="space-y-1.5"><label class="text-sm font-medium">Mulai</label><input v-model="form.start_time" type="time" required :disabled="!item.membership_active || item.remaining_sessions < 1" class="w-full rounded-md border bg-background px-3 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-50"/></div><div class="space-y-1.5"><label class="text-sm font-medium">Selesai</label><input v-model="form.end_time" type="time" required :disabled="!item.membership_active || item.remaining_sessions < 1" class="w-full rounded-md border bg-background px-3 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-50"/></div></div><p v-if="form.errors.start_time || form.errors.end_time" class="text-xs text-destructive">{{form.errors.start_time || form.errors.end_time}}</p>
<Button type="submit" class="w-full" :disabled="form.processing || item.remaining_sessions < 1 || !item.membership_active"><Clock3 class="mr-2 size-4"/>Simpan jadwal</Button><p v-if="item.remaining_sessions < 1" class="text-xs text-destructive">Kuota sesi paket ini sudah habis.</p><p v-else-if="!item.membership_active" class="text-xs text-destructive">Aktifkan membership pada fasilitas yang sama terlebih dahulu.</p></form></section>
<section class="overflow-hidden rounded-2xl border bg-card shadow-sm"><div class="border-b px-5 py-4"><h2 class="font-semibold">Riwayat jadwal sesi</h2><p class="mt-1 text-xs text-muted-foreground">Admin dapat mengedit jadwal yang belum ditutup. Setelah sesi berakhir, tentukan hasilnya: berhasil mengurangi satu sesi, tidak berhasil tidak mengurangi kuota.</p></div>
<div v-if="schedules.length" class="divide-y">
<article v-for="schedule in schedules" :key="schedule.id" class="space-y-3 px-5 py-4">
<div v-if="editingId===schedule.id" class="grid gap-3 sm:grid-cols-3">
<div class="space-y-1"><label class="text-xs font-medium">Tanggal</label><input v-model="editForm.session_date" type="date" required :min="item.start_date || undefined" :max="item.expires_at || undefined" class="w-full rounded-md border bg-background px-3 py-2 text-sm"/></div>
<div class="space-y-1"><label class="text-xs font-medium">Mulai</label><input v-model="editForm.start_time" type="time" required class="w-full rounded-md border bg-background px-3 py-2 text-sm"/></div>
<div class="space-y-1"><label class="text-xs font-medium">Selesai</label><input v-model="editForm.end_time" type="time" required class="w-full rounded-md border bg-background px-3 py-2 text-sm"/></div>
<div class="flex flex-wrap gap-2 sm:col-span-3"><Button size="sm" :disabled="editForm.processing" @click="saveEdit(schedule.id)"><Save class="mr-1 size-3.5"/>Simpan</Button><Button size="sm" variant="outline" @click="cancelEdit"><X class="mr-1 size-3.5"/>Batal</Button><p v-if="editForm.errors.session_date || editForm.errors.start_time || editForm.errors.end_time" class="w-full text-xs text-destructive">{{editForm.errors.session_date || editForm.errors.start_time || editForm.errors.end_time}}</p></div>
</div>
<template v-else>
<div class="flex flex-wrap items-start justify-between gap-3"><div><p class="font-medium">{{formatDate(schedule.date)}}</p><p class="mt-1 text-sm text-muted-foreground">{{schedule.start_time}}–{{schedule.end_time}}</p></div><span class="rounded-full px-3 py-1 text-xs font-semibold" :class="schedule.status==='completed'?'bg-emerald-500/10 text-emerald-700':schedule.status==='failed'?'bg-rose-500/10 text-rose-700':'bg-muted text-muted-foreground'">{{statusLabel(schedule.status)}}</span></div>
<div class="grid gap-2 rounded-xl bg-muted/40 p-3 sm:grid-cols-2">
<div><p class="text-xs text-muted-foreground">Konfirmasi member</p><p class="mt-1 text-sm font-semibold" :class="schedule.member_confirmed_at ? (schedule.member_attended?'text-emerald-700':'text-rose-700') : 'text-amber-700'">{{attendanceLabel(schedule.member_confirmed_at,schedule.member_attended)}}</p></div>
<div><p class="text-xs text-muted-foreground">Konfirmasi trainer</p><p class="mt-1 text-sm font-semibold" :class="schedule.trainer_confirmed_at ? (schedule.trainer_attended?'text-emerald-700':'text-rose-700') : 'text-amber-700'">{{attendanceLabel(schedule.trainer_confirmed_at,schedule.trainer_attended)}}</p></div>
</div>
<div v-if="schedule.status==='scheduled'" class="flex flex-wrap gap-2">
<Button size="sm" variant="outline" @click="beginEdit(schedule)"><Pencil class="mr-1 size-3.5"/>Edit jadwal</Button>
<Button size="sm" :disabled="new Date(schedule.date+'T'+schedule.end_time) > new Date() || !schedule.member_confirmed_at || !schedule.trainer_confirmed_at || !schedule.member_attended || !schedule.trainer_attended" @click="setResult(schedule.id,'completed')"><Check class="mr-1 size-3.5"/>Berhasil</Button>
<Button size="sm" variant="destructive" :disabled="new Date(schedule.date+'T'+schedule.end_time) > new Date()" @click="setResult(schedule.id,'failed')"><Ban class="mr-1 size-3.5"/>Tidak berhasil</Button>
<p v-if="!schedule.member_confirmed_at || !schedule.trainer_confirmed_at" class="w-full text-xs text-amber-700">Menunggu konfirmasi dari {{!schedule.member_confirmed_at && !schedule.trainer_confirmed_at ? 'member dan trainer' : (!schedule.member_confirmed_at ? 'member' : 'trainer')}}.</p>
<p v-else-if="!schedule.member_attended || !schedule.trainer_attended" class="w-full text-xs text-rose-700">Sesi tidak dapat ditandai berhasil karena salah satu pihak mengonfirmasi tidak hadir.</p>
<p v-if="new Date(schedule.date+'T'+schedule.end_time) > new Date()" class="w-full text-xs text-muted-foreground">Konfirmasi dan hasil sesi baru bisa dilakukan setelah jam sesi berakhir.</p>
</div>
<p v-else class="text-xs text-muted-foreground">{{schedule.status==='completed'?'Kuota sudah dikurangi satu sesi.':'Sesi ditutup tanpa mengurangi kuota.'}}</p>
</template>
</article>
</div>
<div v-else class="p-10 text-center text-sm text-muted-foreground"><CheckCircle2 class="mx-auto mb-2 size-8 opacity-40"/>Belum ada jadwal sesi untuk paket ini.</div></section>
</div></div>
</template>