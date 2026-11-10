<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Participant = { id:number; name:string; status:string };
type Schedule = { id:number; trainer_id:number|null; trainer:string|null; date:string; start_time:string; end_time:string; capacity:number|null; effective_capacity:number; registered_count:number; price:number; status:string; participants:Participant[] };
type SportClass = { id:number; facility_id:number; name:string; description:string|null; duration_minutes:number; default_capacity:number; is_active:boolean; schedules:Schedule[] };
const props=defineProps<{facilityId:number; classes:SportClass[]; trainers:{id:number;name:string}[]}>();
const classDialog=ref(false); const editingClass=ref<number|null>(null);
const classForm=useForm({facility_id:String(props.facilityId),name:'',description:'',duration_minutes:'60',default_capacity:'10',is_active:true});
const scheduleDialog=ref(false); const editingSchedule=ref<number|null>(null); const scheduleClassId=ref<number|null>(null);
const scheduleForm=useForm({trainer_id:'',session_date:'',start_time:'08:00',end_time:'09:00',capacity:'',price:'0',status:'scheduled'});
const money=(v:number)=>new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(v);
const dateLabel=(v:string)=>new Date(v+'T00:00:00').toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'});
function endTime(start:string,duration:number){const [h,m]=start.split(':').map(Number);const t=h*60+m+duration;return String(Math.floor(t/60)%24).padStart(2,'0')+':'+String(t%60).padStart(2,'0');}
function openClass(item?:SportClass){editingClass.value=item?.id??null;classForm.clearErrors();classForm.facility_id=String(props.facilityId);classForm.name=item?.name??'';classForm.description=item?.description??'';classForm.duration_minutes=String(item?.duration_minutes??60);classForm.default_capacity=String(item?.default_capacity??10);classForm.is_active=item?.is_active??true;classDialog.value=true;}
function saveClass(){const opts={preserveScroll:true,onSuccess:()=>classDialog.value=false};if(editingClass.value)classForm.put('/classes/'+editingClass.value,opts);else classForm.post('/classes',opts);}
function removeClass(item:SportClass){if(confirm('Hapus master kelas '+item.name+'?'))router.delete('/classes/'+item.id,{preserveScroll:true});}
function openSchedule(item:SportClass,s?:Schedule){scheduleClassId.value=item.id;editingSchedule.value=s?.id??null;scheduleForm.clearErrors();scheduleForm.trainer_id=String(s?.trainer_id??'');scheduleForm.session_date=s?.date??'';scheduleForm.start_time=s?.start_time??'08:00';scheduleForm.end_time=s?.end_time??endTime('08:00',item.duration_minutes);scheduleForm.capacity=s?.capacity==null?'':String(s.capacity);scheduleForm.price=String(s?.price??0);scheduleForm.status=s?.status??'scheduled';scheduleDialog.value=true;}
function saveSchedule(){const item=props.classes.find(c=>c.id===scheduleClassId.value);if(!item)return;const data={...scheduleForm.data(),trainer_id:scheduleForm.trainer_id||null,capacity:scheduleForm.capacity||null};const opts={preserveScroll:true,onSuccess:()=>scheduleDialog.value=false};if(editingSchedule.value)scheduleForm.transform(()=>data).put('/class-schedules/'+editingSchedule.value,opts);else router.post('/classes/'+item.id+'/schedules',data,opts);}
function updateEnd(){const item=props.classes.find(c=>c.id===scheduleClassId.value);if(item)scheduleForm.end_time=endTime(scheduleForm.start_time,item.duration_minutes);}
function removeSchedule(s:Schedule){if(confirm('Hapus jadwal ini?'))router.delete('/class-schedules/'+s.id,{preserveScroll:true});}
function setStatus(s:Schedule,status:string){router.put('/class-schedules/'+s.id+'/status',{status},{preserveScroll:true});}
function setAttendance(id:number,status:'attended'|'no_show'){router.put('/session-participants/'+id+'/attendance',{status},{preserveScroll:true});}
</script>

<template>
<section class="space-y-4">
  <div class="flex flex-wrap items-center justify-between gap-2">
    <div><h2 class="font-semibold">Kelas & Jadwal</h2><p class="text-sm text-muted-foreground">Kelola kelas dan sesi terjadwal untuk {{ props.classes.length ? 'fasilitas ini' : 'fasilitas ini' }}.</p></div>
    <Button size="sm" @click="openClass()"><span class="mr-1">+</span>Tambah kelas</Button>
  </div>
  <div v-if="!classes.length" class="rounded-2xl border border-dashed bg-card p-8 text-center"><p class="font-medium">Belum ada kelas</p><p class="mt-1 text-sm text-muted-foreground">Tambahkan kelas seperti Yoga, Zumba, atau Swimming.</p></div>
  <div v-for="item in classes" :key="item.id" class="overflow-hidden rounded-2xl border bg-card shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-3 border-b p-4">
      <div><div class="flex flex-wrap items-center gap-2"><h3 class="font-semibold">{{ item.name }}</h3><span class="rounded-full px-2 py-1 text-xs" :class="item.is_active?'bg-emerald-500/10 text-emerald-600':'bg-muted text-muted-foreground'">{{item.is_active?'Aktif':'Nonaktif'}}</span></div><p class="mt-1 text-sm text-muted-foreground">{{item.duration_minutes}} menit · Kapasitas default {{item.default_capacity}}</p><p v-if="item.description" class="mt-1 text-sm text-muted-foreground">{{item.description}}</p></div>
      <div class="flex gap-2"><Button size="sm" variant="outline" @click="openClass(item)">Edit kelas</Button><Button size="sm" variant="ghost" class="text-destructive" @click="removeClass(item)">Hapus</Button></div>
    </div>
    <div class="space-y-3 p-4">
      <div class="flex items-center justify-between gap-2"><h4 class="text-sm font-semibold">Jadwal ({{item.schedules.length}})</h4><Button size="sm" variant="outline" @click="openSchedule(item)">Tambah jadwal</Button></div>
      <div v-if="!item.schedules.length" class="rounded-lg bg-muted/40 p-4 text-sm text-muted-foreground">Belum ada jadwal untuk kelas ini.</div>
      <div v-for="s in item.schedules" :key="s.id" class="rounded-xl border p-3">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div><p class="font-medium">{{dateLabel(s.date)}} · {{s.start_time}}–{{s.end_time}}</p><p class="mt-1 text-sm text-muted-foreground">{{s.trainer||'Trainer belum ditentukan'}} · {{s.registered_count}}/{{s.effective_capacity}} peserta · {{money(s.price)}}</p><span class="mt-2 inline-flex rounded-full px-2 py-1 text-xs" :class="s.status==='scheduled'?'bg-sky-500/10 text-sky-600':s.status==='completed'?'bg-emerald-500/10 text-emerald-600':'bg-rose-500/10 text-rose-600'">{{s.status}}</span></div>
          <div class="flex flex-wrap gap-1"><Button size="sm" variant="outline" @click="openSchedule(item,s)">Edit</Button><Button v-if="s.status==='scheduled'" size="sm" variant="outline" @click="setStatus(s,'completed')">Selesai</Button><Button v-if="s.status==='scheduled'" size="sm" variant="outline" @click="setStatus(s,'cancelled')">Batalkan</Button><Button size="sm" variant="ghost" class="text-destructive" @click="removeSchedule(s)">Hapus</Button></div>
        </div>
        <div v-if="s.participants.length" class="mt-3 border-t pt-3"><p class="mb-2 text-xs font-semibold text-muted-foreground">Peserta</p><div v-for="p in s.participants" :key="p.id" class="flex flex-wrap items-center justify-between gap-2 py-1 text-sm"><span>{{p.name}} <span class="text-xs text-muted-foreground">· {{p.status}}</span></span><div v-if="s.status==='completed' && ['confirmed','attended','no_show'].includes(p.status)" class="flex gap-1"><Button size="sm" variant="outline" @click="setAttendance(p.id,'attended')">Hadir</Button><Button size="sm" variant="outline" @click="setAttendance(p.id,'no_show')">Tidak hadir</Button></div></div></div>
      </div>
    </div>
  </div>
</section>

<div v-if="classDialog" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" @click.self="classDialog=false">
  <form class="w-full max-w-lg space-y-4 rounded-2xl border bg-background p-6 shadow-xl" @submit.prevent="saveClass">
    <div><h3 class="text-lg font-semibold">{{editingClass?'Edit master kelas':'Tambah master kelas'}}</h3><p class="text-sm text-muted-foreground">Fasilitas: ID {{props.facilityId}}</p></div>
    <div class="space-y-1"><Label>Nama kelas</Label><Input v-model="classForm.name" required/><p v-if="classForm.errors.name" class="text-xs text-destructive">{{classForm.errors.name}}</p></div>
    <div class="space-y-1"><Label>Deskripsi</Label><textarea v-model="classForm.description" rows="3" class="w-full rounded-md border bg-background p-3 text-sm"/></div>
    <div class="grid grid-cols-2 gap-3"><div class="space-y-1"><Label>Durasi (menit)</Label><Input v-model="classForm.duration_minutes" type="number" min="1" max="600" required/></div><div class="space-y-1"><Label>Kapasitas default</Label><Input v-model="classForm.default_capacity" type="number" min="1" required/></div></div>
    <label class="flex items-center gap-2 text-sm"><input v-model="classForm.is_active" type="checkbox"/> Kelas aktif dan bisa dijadwalkan</label>
    <div class="flex justify-end gap-2"><Button type="button" variant="outline" @click="classDialog=false">Batal</Button><Button type="submit" :disabled="classForm.processing">Simpan</Button></div>
  </form>
</div>

<div v-if="scheduleDialog" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" @click.self="scheduleDialog=false">
  <form class="w-full max-w-lg space-y-4 rounded-2xl border bg-background p-6 shadow-xl" @submit.prevent="saveSchedule">
    <div><h3 class="text-lg font-semibold">{{editingSchedule?'Edit jadwal kelas':'Tambah jadwal kelas'}}</h3><p class="text-sm text-muted-foreground">Jam selesai otomatis mengikuti durasi master kelas.</p></div>
    <div class="space-y-1"><Label>Tanggal</Label><Input v-model="scheduleForm.session_date" type="date" required/><p v-if="scheduleForm.errors.session_date" class="text-xs text-destructive">{{scheduleForm.errors.session_date}}</p></div>
    <div class="grid grid-cols-2 gap-2"><div class="space-y-1"><Label>Mulai</Label><Input v-model="scheduleForm.start_time" type="time" required @update:model-value="updateEnd"/></div><div class="space-y-1"><Label>Selesai</Label><Input v-model="scheduleForm.end_time" type="time" readonly required/></div></div>
    <div class="space-y-1"><Label>Trainer</Label><select v-model="scheduleForm.trainer_id" class="h-10 w-full rounded-md border bg-background px-3 text-sm"><option value="">Belum ditentukan</option><option v-for="t in trainers" :key="t.id" :value="String(t.id)">{{t.name}}</option></select><p v-if="scheduleForm.errors.trainer_id" class="text-xs text-destructive">{{scheduleForm.errors.trainer_id}}</p></div>
    <div class="grid grid-cols-2 gap-2"><div class="space-y-1"><Label>Kapasitas</Label><Input v-model="scheduleForm.capacity" type="number" min="1" placeholder="Default"/></div><div class="space-y-1"><Label>Harga (Rp)</Label><Input v-model="scheduleForm.price" type="number" min="0" required/></div></div>
    <div v-if="editingSchedule" class="space-y-1"><Label>Status</Label><select v-model="scheduleForm.status" class="h-10 w-full rounded-md border bg-background px-3 text-sm"><option value="scheduled">Dijadwalkan</option><option value="completed">Selesai</option><option value="cancelled">Dibatalkan</option></select></div>
    <div class="flex justify-end gap-2"><Button type="button" variant="outline" @click="scheduleDialog=false">Batal</Button><Button type="submit" :disabled="scheduleForm.processing">Simpan jadwal</Button></div>
  </form>
</div>
</template>
