<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Participant = { id:number; name:string; status:string; session_date:string };
type Schedule = { id:number; trainer_id:number|null; trainer:string|null; day:string; start_time:string; end_time:string; capacity:number|null; effective_capacity:number; registered_count:number; price:number; is_active:boolean; participants:Participant[] };
type SportClass = { id:number; facility_id:number; name:string; description:string|null; duration_minutes:number; default_capacity:number; is_active:boolean; schedules:Schedule[] };
const props=defineProps<{facilityId:number; facilityName:string; classes:SportClass[]; trainers:{id:number;name:string}[]}>();
const days=[{value:'monday',label:'Senin'},{value:'tuesday',label:'Selasa'},{value:'wednesday',label:'Rabu'},{value:'thursday',label:'Kamis'},{value:'friday',label:'Jumat'},{value:'saturday',label:'Sabtu'},{value:'sunday',label:'Minggu'}];
const dayLabel=(day:string)=>days.find(d=>d.value===day)?.label??day;
const timeSlots=(item:SportClass)=>[...new Set(item.schedules.map(s=>s.start_time.slice(0,5)))].sort((a,b)=>a.localeCompare(b));
const schedulesAt=(item:SportClass,day:string,time:string)=>item.schedules.filter(s=>s.day===day&&s.start_time.slice(0,5)===time).sort((a,b)=>a.start_time.localeCompare(b.start_time));
const classDialog=ref(false); const editingClass=ref<number|null>(null);
const classForm=useForm({name:'',description:'',duration_minutes:'60',default_capacity:'10',is_active:true});
const scheduleDialog=ref(false); const editingSchedule=ref<number|null>(null); const scheduleClassId=ref<number|null>(null);
const scheduleForm=useForm({trainer_id:'',day:'monday',start_time:'08:00',end_time:'09:00',capacity:'',price:'0',is_active:true});
const money=(v:number)=>new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(v);
const groupedParticipants = (participants: Participant[]) => {
  const groups = new Map<string, Participant[]>();
  for (const participant of [...participants].sort((a,b) => a.session_date.localeCompare(b.session_date) || a.name.localeCompare(b.name))) {
    const key = participant.session_date;
    groups.set(key, [...(groups.get(key) ?? []), participant]);
  }
  return [...groups.entries()].map(([date, people]) => ({ date, people }));
};
const dateLabel=(v:string)=>new Date(v+'T00:00:00').toLocaleDateString('id-ID',{weekday:'long',day:'2-digit',month:'short',year:'numeric'});
function endTime(start:string,duration:number){const [h,m]=start.split(':').map(Number);const t=h*60+m+duration;return String(Math.floor(t/60)%24).padStart(2,'0')+':'+String(t%60).padStart(2,'0');}
function openClass(item?:SportClass){editingClass.value=item?.id??null;classForm.clearErrors();classForm.name=item?.name??'';classForm.description=item?.description??'';classForm.duration_minutes=String(item?.duration_minutes??60);classForm.default_capacity=String(item?.default_capacity??10);classForm.is_active=item?.is_active??true;classDialog.value=true;}
function saveClass(){const opts={preserveScroll:true,onSuccess:()=>classDialog.value=false};if(editingClass.value)classForm.put('/classes/'+editingClass.value,opts);else classForm.post('/facilities/'+props.facilityId+'/classes',opts);}
function removeClass(item:SportClass){if(confirm('Hapus master kelas '+item.name+'?'))router.delete('/classes/'+item.id,{preserveScroll:true});}
function openSchedule(item:SportClass,s?:Schedule){scheduleClassId.value=item.id;editingSchedule.value=s?.id??null;scheduleForm.clearErrors();scheduleForm.trainer_id=String(s?.trainer_id??'');scheduleForm.day=s?.day??'monday';scheduleForm.start_time=s?.start_time??'08:00';scheduleForm.end_time=s?.end_time??endTime('08:00',item.duration_minutes);scheduleForm.capacity=s?.capacity==null?'':String(s.capacity);scheduleForm.price=String(s?.price??0);scheduleForm.is_active=s?.is_active??true;scheduleDialog.value=true;}
function saveSchedule(){const item=props.classes.find(c=>c.id===scheduleClassId.value);if(!item)return;const data={...scheduleForm.data(),trainer_id:scheduleForm.trainer_id||null,capacity:scheduleForm.capacity||null};const opts={preserveScroll:true,onSuccess:()=>scheduleDialog.value=false};if(editingSchedule.value)router.put('/class-schedules/'+editingSchedule.value,data,opts);else router.post('/classes/'+item.id+'/schedules',data,opts);}
function updateEnd(){const item=props.classes.find(c=>c.id===scheduleClassId.value);if(item)scheduleForm.end_time=endTime(scheduleForm.start_time,item.duration_minutes);}
function removeSchedule(s:Schedule){if(confirm('Hapus jadwal ini?'))router.delete('/class-schedules/'+s.id,{preserveScroll:true});}
function setActive(s:Schedule,is_active:boolean){router.put('/class-schedules/'+s.id+'/status',{is_active},{preserveScroll:true});}
function setAttendance(id:number,status:'attended'|'no_show'){router.put('/session-participants/'+id+'/attendance',{status},{preserveScroll:true});}
</script>

<template>
<section class="space-y-4">
  <div class="flex flex-wrap items-center justify-between gap-2">
    <div><h2 class="font-semibold">Kelas & Jadwal</h2><p class="text-sm text-muted-foreground">Kelola kelas dan jadwal rutin mingguan untuk {{props.facilityName}}.</p></div>
    <Button size="sm" @click="openClass()"><span class="mr-1">+</span>Tambah kelas</Button>
  </div>
  <div v-if="!classes.length" class="rounded-2xl border border-dashed bg-card p-8 text-center"><p class="font-medium">Belum ada kelas</p><p class="mt-1 text-sm text-muted-foreground">Tambahkan kelas seperti Yoga, Zumba, atau Swimming.</p></div>
  <div v-for="item in classes" :key="item.id" class="rounded-xl border bg-card">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b px-3 py-2.5">
      <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
          <h3 class="font-semibold">{{item.name}}</h3>
          <span class="rounded-full px-2 py-0.5 text-[11px] font-medium" :class="item.is_active?'bg-emerald-500/10 text-emerald-600':'bg-muted text-muted-foreground'">{{item.is_active?'Aktif':'Nonaktif'}}</span>
          <span class="text-xs text-muted-foreground">{{item.duration_minutes}} menit · Kapasitas {{item.default_capacity}}</span>
        </div>
        <p v-if="item.description" class="mt-0.5 text-xs text-muted-foreground">{{item.description}}</p>
      </div>
      <div class="flex shrink-0 gap-1">
        <Button size="sm" variant="outline" @click="openClass(item)">Edit</Button>
        <Button size="sm" variant="ghost" class="text-destructive" @click="removeClass(item)">Hapus</Button>
      </div>
    </div>
    <div class="p-3">
      <div class="mb-2 flex items-center justify-between gap-2">
        <h4 class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Jadwal rutin · {{item.schedules.length}}</h4>
        <Button size="sm" variant="outline" @click="openSchedule(item)">+ Jadwal</Button>
      </div>
      <p v-if="!item.schedules.length" class="rounded-lg bg-muted/40 px-3 py-2 text-xs text-muted-foreground">Belum ada jadwal untuk kelas ini.</p>
      <div v-else class="overflow-x-auto rounded-lg border">
        <table class="w-full min-w-[980px] border-collapse text-left">
          <thead>
            <tr class="bg-muted/60">
              <th class="sticky left-0 z-10 w-24 border-b border-r bg-muted/90 px-3 py-2 text-xs font-semibold text-muted-foreground">Jam</th>
              <th v-for="d in days" :key="d.value" class="border-b border-r px-3 py-2 text-center text-xs font-semibold last:border-r-0">{{d.label}}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="time in timeSlots(item)" :key="time" class="align-top">
              <th class="sticky left-0 z-10 border-b border-r bg-muted/40 px-3 py-3 text-xs font-semibold tabular-nums">{{time}}</th>
              <td v-for="d in days" :key="d.value" class="w-[12.5%] border-b border-r p-1.5 last:border-r-0">
                <div v-if="schedulesAt(item,d.value,time).length" class="space-y-2">
                  <div v-for="s in schedulesAt(item,d.value,time)" :key="s.id" class="rounded-md border bg-background p-2">
                    <div class="flex items-center justify-between gap-1">
                      <span class="text-[11px] font-semibold tabular-nums">{{s.start_time.slice(0,5)}}–{{s.end_time.slice(0,5)}}</span>
                      <span class="rounded-full px-1.5 py-0.5 text-[9px] font-medium" :class="s.is_active?'bg-emerald-500/10 text-emerald-600':'bg-muted text-muted-foreground'">{{s.is_active?'Aktif':'Off'}}</span>
                    </div>
                    <p class="mt-1 text-[11px] font-medium leading-tight">{{s.trainer||'Trainer belum ditentukan'}}</p>
                    <p class="mt-1 text-[10px] leading-relaxed text-muted-foreground">{{s.registered_count}} peserta · {{money(s.price)}}</p>
                    <div class="mt-2 flex flex-wrap gap-1 border-t pt-1.5">
                      <Button size="sm" variant="outline" class="h-6 px-1.5 text-[10px]" @click="openSchedule(item,s)">Edit</Button>
                      <Button size="sm" variant="outline" class="h-6 px-1.5 text-[10px]" @click="setActive(s,!s.is_active)">{{s.is_active?'Off':'Aktifkan'}}</Button>
                      <Button size="sm" variant="ghost" class="h-6 px-1.5 text-[10px] text-destructive" @click="removeSchedule(s)">Hapus</Button>
                    </div>
                    <details v-if="s.participants.length" class="mt-2 border-t pt-1.5">
                      <summary class="cursor-pointer text-[10px] font-medium text-muted-foreground">Lihat {{s.participants.length}} peserta</summary>
                      <div class="mt-2 space-y-2">
                        <div v-for="group in groupedParticipants(s.participants)" :key="group.date" class="rounded bg-muted/40 p-1.5">
                          <p class="mb-1 text-[10px] font-semibold">{{dateLabel(group.date)}}</p>
                          <div v-for="p in group.people" :key="p.id" class="flex flex-wrap items-center justify-between gap-1 border-t border-border/60 py-1 first:border-0">
                            <span class="text-[10px]">{{p.name}} · {{p.status}}</span>
                            <div v-if="['confirmed','attended','no_show'].includes(p.status)" class="flex gap-1">
                              <Button size="sm" variant="outline" class="h-6 px-1.5 text-[9px]" @click="setAttendance(p.id,'attended')">Hadir</Button>
                              <Button size="sm" variant="outline" class="h-6 px-1.5 text-[9px]" @click="setAttendance(p.id,'no_show')">Absen</Button>
                            </div>
                          </div>
                        </div>
                      </div>
                    </details>
                  </div>
                </div>
                <div v-else class="min-h-12 rounded-md bg-muted/15"></div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<div v-if="classDialog" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" @click.self="classDialog=false">
  <form class="w-full max-w-lg space-y-4 rounded-2xl border bg-background p-6 shadow-xl" @submit.prevent="saveClass">
    <div><h3 class="text-lg font-semibold">{{editingClass?'Edit master kelas':'Tambah master kelas'}}</h3><p class="text-sm text-muted-foreground">Fasilitas: {{props.facilityName}}</p></div>
    <div class="space-y-1"><Label>Nama kelas</Label><Input v-model="classForm.name" required/><p v-if="classForm.errors.name" class="text-xs text-destructive">{{classForm.errors.name}}</p></div>
    <div class="space-y-1"><Label>Deskripsi</Label><textarea v-model="classForm.description" rows="3" class="w-full rounded-md border bg-background p-3 text-sm"/></div>
    <div class="grid grid-cols-2 gap-3"><div class="space-y-1"><Label>Durasi (menit)</Label><Input v-model="classForm.duration_minutes" type="number" min="1" max="600" required/></div><div class="space-y-1"><Label>Kapasitas default</Label><Input v-model="classForm.default_capacity" type="number" min="1" required/></div></div>
    <label class="flex items-center gap-2 text-sm"><input v-model="classForm.is_active" type="checkbox"/> Kelas aktif dan bisa dijadwalkan</label>
    <div class="flex justify-end gap-2"><Button type="button" variant="outline" @click="classDialog=false">Batal</Button><Button type="submit" :disabled="classForm.processing">Simpan</Button></div>
  </form>
</div>

<div v-if="scheduleDialog" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" @click.self="scheduleDialog=false">
  <form class="w-full max-w-lg space-y-4 rounded-2xl border bg-background p-6 shadow-xl" @submit.prevent="saveSchedule">
    <div><h3 class="text-lg font-semibold">{{editingSchedule?'Edit jadwal rutin':'Tambah jadwal rutin'}}</h3><p class="text-sm text-muted-foreground">Jadwal ini akan berulang setiap minggu. Jam selesai otomatis mengikuti durasi master kelas.</p></div>
    <div class="space-y-1"><Label>Hari</Label><select v-model="scheduleForm.day" class="h-10 w-full rounded-md border bg-background px-3 text-sm"><option v-for="d in days" :key="d.value" :value="d.value">{{d.label}}</option></select><p v-if="scheduleForm.errors.day" class="text-xs text-destructive">{{scheduleForm.errors.day}}</p></div>
    <div class="grid grid-cols-2 gap-2"><div class="space-y-1"><Label>Mulai</Label><Input v-model="scheduleForm.start_time" type="time" required @update:model-value="updateEnd"/></div><div class="space-y-1"><Label>Selesai</Label><Input v-model="scheduleForm.end_time" type="time" readonly required/></div></div>
    <div class="space-y-1"><Label>Trainer</Label><select v-model="scheduleForm.trainer_id" class="h-10 w-full rounded-md border bg-background px-3 text-sm"><option value="">Belum ditentukan</option><option v-for="t in trainers" :key="t.id" :value="String(t.id)">{{t.name}}</option></select><p v-if="scheduleForm.errors.trainer_id" class="text-xs text-destructive">{{scheduleForm.errors.trainer_id}}</p></div>
    <div class="grid grid-cols-2 gap-2"><div class="space-y-1"><Label>Kapasitas</Label><Input v-model="scheduleForm.capacity" type="number" min="1" placeholder="Default"/></div><div class="space-y-1"><Label>Harga (Rp)</Label><Input v-model="scheduleForm.price" type="number" min="0" required/></div></div>
    <label class="flex items-center gap-2 text-sm"><input v-model="scheduleForm.is_active" type="checkbox"/> Jadwal aktif setiap minggu</label>
    <div class="flex justify-end gap-2"><Button type="button" variant="outline" @click="scheduleDialog=false">Batal</Button><Button type="submit" :disabled="scheduleForm.processing">Simpan jadwal</Button></div>
  </form>
</div>
</template>