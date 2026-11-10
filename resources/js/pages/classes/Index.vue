<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Schedule = { id:number; trainer_id:number|null; trainer:string|null; date:string; start_time:string; end_time:string; capacity:number|null; effective_capacity:number; registered_count:number; price:number; status:string };
type SportClass = { id:number; facility_id:number; facility:string; name:string; description:string|null; duration_minutes:number; default_capacity:number; is_active:boolean; schedules:Schedule[] };
const props=defineProps<{classes:SportClass[];facilities:{id:number;name:string}[];trainers:{id:number;name:string}[]}>();
const classDialog=ref(false); const editingClass=ref<number|null>(null);
const scheduleDialog=ref(false); const editingSchedule=ref<number|null>(null);
const scheduleForm=useForm({trainer_id:'',session_date:'',start_time:'08:00',end_time:'09:00',capacity:'',price:'0',status:'scheduled'});
const classForm=useForm({facility_id:'',name:'',description:'',duration_minutes:'60',default_capacity:'10',is_active:true});
const scheduleForms=ref<Record<number,{trainer_id:string;session_date:string;start_time:string;end_time:string;capacity:string;price:string}>>({});
function openClass(item?:SportClass){editingClass.value=item?.id??null;classForm.reset();classForm.clearErrors();classForm.facility_id=String(item?.facility_id??props.facilities[0]?.id??'');classForm.name=item?.name??'';classForm.description=item?.description??'';classForm.duration_minutes=String(item?.duration_minutes??60);classForm.default_capacity=String(item?.default_capacity??10);classForm.is_active=item?.is_active??true;classDialog.value=true;}
function saveClass(){const options={preserveScroll:true,onSuccess:()=>classDialog.value=false};if(editingClass.value)classForm.put('/classes/'+editingClass.value,options);else classForm.post('/classes',options);}
function formFor(id:number){return scheduleForms.value[id]??(scheduleForms.value[id]={trainer_id:'',session_date:'',start_time:'08:00',end_time:'09:00',capacity:'',price:'0'});}
function addSchedule(item:SportClass){const f=formFor(item.id);router.post('/classes/'+item.id+'/schedules',{...f,trainer_id:f.trainer_id||null,capacity:f.capacity||null},{preserveScroll:true,onSuccess:()=>{scheduleForms.value[item.id]={trainer_id:'',session_date:'',start_time:'08:00',end_time:'09:00',capacity:'',price:'0'};}});}
function removeClass(item:SportClass){if(confirm('Hapus master kelas '+item.name+'?'))router.delete('/classes/'+item.id,{preserveScroll:true});}
function setStatus(s:Schedule,status:string){router.put('/class-schedules/'+s.id+'/status',{status},{preserveScroll:true});}
function editSchedule(s:Schedule){editingSchedule.value=s.id;scheduleForm.clearErrors();scheduleForm.trainer_id=String(s.trainer_id??'');scheduleForm.session_date=s.date;scheduleForm.start_time=s.start_time;scheduleForm.end_time=s.end_time;scheduleForm.capacity=s.capacity===null?'':String(s.capacity);scheduleForm.price=String(s.price);scheduleForm.status=s.status;scheduleDialog.value=true;}
function saveSchedule(){if(!editingSchedule.value)return;scheduleForm.put('/class-schedules/'+editingSchedule.value,{preserveScroll:true,onSuccess:()=>scheduleDialog.value=false});}
function removeSchedule(s:Schedule){if(confirm('Hapus jadwal ini?'))router.delete('/class-schedules/'+s.id,{preserveScroll:true});}
const money=(v:number)=>new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(v);
const dateLabel=(v:string)=>new Date(v+'T00:00:00').toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'});
</script>

<template>
<Head title="Kelas & Jadwal"/>
<div class="space-y-6 p-4 md:p-6">
<PageHeader eyebrow="Manajemen Aktivitas" title="Kelas & Jadwal" description="Kelola master kelas dan jadwal pelaksanaannya." >
<Button @click="openClass()">Tambah kelas</Button>
</PageHeader>
<div v-if="!classes.length" class="rounded-2xl border bg-card p-12 text-center"><h2 class="font-semibold">Belum ada kelas</h2><p class="mt-1 text-sm text-muted-foreground">Buat master kelas seperti Yoga, Zumba, atau Swimming.</p></div>
<div v-for="item in classes" :key="item.id" class="overflow-hidden rounded-2xl border bg-card shadow-sm">
<div class="flex flex-wrap items-start justify-between gap-4 border-b p-5">
<div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h2 class="text-lg font-bold">{{item.name}}</h2><span class="rounded-full px-2 py-1 text-xs" :class="item.is_active?'bg-emerald-100 text-emerald-700':'bg-muted text-muted-foreground'">{{item.is_active?'Aktif':'Nonaktif'}}</span></div><p class="mt-1 text-sm text-muted-foreground">{{item.facility}} · {{item.duration_minutes}} menit · Kapasitas default {{item.default_capacity}}</p><p v-if="item.description" class="mt-2 text-sm text-muted-foreground">{{item.description}}</p></div>
<div class="flex gap-2"><Button variant="outline" size="sm" @click="openClass(item)">Edit</Button><Button variant="destructive" size="sm" @click="removeClass(item)">Hapus</Button></div>
</div>
<div class="grid gap-4 p-5 xl:grid-cols-[minmax(0,1fr)_320px]">
<div class="space-y-3">
<h3 class="text-sm font-semibold">Jadwal kelas ({{item.schedules.length}})</h3>
<div v-if="!item.schedules.length" class="rounded-xl border border-dashed p-5 text-sm text-muted-foreground">Belum ada jadwal untuk kelas ini.</div>
<div v-for="s in item.schedules" :key="s.id" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border p-4">
<div><p class="font-semibold">{{dateLabel(s.date)}} · {{s.start_time}}–{{s.end_time}}</p><p class="mt-1 text-xs text-muted-foreground">{{s.trainer||'Trainer belum ditentukan'}} · {{s.registered_count}}/{{s.effective_capacity}} peserta · {{money(s.price)}}</p><span class="mt-2 inline-flex rounded-full px-2 py-1 text-xs font-semibold" :class="s.status==='scheduled'?'bg-sky-100 text-sky-700':s.status==='completed'?'bg-emerald-100 text-emerald-700':'bg-rose-100 text-rose-700'">{{s.status}}</span></div>
<div class="flex flex-wrap gap-2"><Button size="sm" variant="outline" @click="editSchedule(s)">Edit</Button><Button v-if="s.status==='scheduled'" size="sm" variant="outline" @click="setStatus(s,'completed')">Selesai</Button><Button v-if="s.status==='scheduled'" size="sm" variant="outline" @click="setStatus(s,'cancelled')">Batalkan</Button><Button size="sm" variant="ghost" class="text-destructive" @click="removeSchedule(s)">Hapus</Button></div>
</div>
</div>
<form class="h-fit space-y-3 rounded-xl bg-muted/40 p-4" @submit.prevent="addSchedule(item)">
<h3 class="font-semibold">Tambah jadwal</h3>
<div class="space-y-1"><Label>Tanggal</Label><Input v-model="formFor(item.id).session_date" type="date" required/></div>
<div class="grid grid-cols-2 gap-2"><div class="space-y-1"><Label>Mulai</Label><Input v-model="formFor(item.id).start_time" type="time" required/></div><div class="space-y-1"><Label>Selesai</Label><Input v-model="formFor(item.id).end_time" type="time" required/></div></div>
<div class="space-y-1"><Label>Trainer</Label><select v-model="formFor(item.id).trainer_id" class="h-10 w-full rounded-md border bg-background px-3 text-sm"><option value="">Belum ditentukan</option><option v-for="t in trainers" :key="t.id" :value="String(t.id)">{{t.name}}</option></select></div>
<div class="grid grid-cols-2 gap-2"><div class="space-y-1"><Label>Kapasitas</Label><Input v-model="formFor(item.id).capacity" type="number" min="1" placeholder="Default"/></div><div class="space-y-1"><Label>Harga (Rp)</Label><Input v-model="formFor(item.id).price" type="number" min="0" required/></div></div>
<Button type="submit" class="w-full">Simpan jadwal</Button>
</form>
</div>
</div>
<div v-if="scheduleDialog" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" @click.self="scheduleDialog=false">
<form class="w-full max-w-lg space-y-4 rounded-2xl bg-background p-6 shadow-xl" @submit.prevent="saveSchedule">
<div><h2 class="text-lg font-bold">Edit jadwal kelas</h2><p class="text-sm text-muted-foreground">Perubahan kapasitas tidak boleh mengurangi slot peserta yang sudah terdaftar.</p></div>
<div class="space-y-1"><Label>Tanggal</Label><Input v-model="scheduleForm.session_date" type="date" required/></div>
<div class="grid grid-cols-2 gap-2"><div class="space-y-1"><Label>Mulai</Label><Input v-model="scheduleForm.start_time" type="time" required/></div><div class="space-y-1"><Label>Selesai</Label><Input v-model="scheduleForm.end_time" type="time" required/></div></div>
<div class="space-y-1"><Label>Trainer</Label><select v-model="scheduleForm.trainer_id" class="h-10 w-full rounded-md border bg-background px-3 text-sm"><option value="">Belum ditentukan</option><option v-for="t in trainers" :key="t.id" :value="String(t.id)">{{t.name}}</option></select></div>
<div class="grid grid-cols-2 gap-2"><div class="space-y-1"><Label>Kapasitas</Label><Input v-model="scheduleForm.capacity" type="number" min="1" placeholder="Default"/></div><div class="space-y-1"><Label>Harga (Rp)</Label><Input v-model="scheduleForm.price" type="number" min="0" required/></div></div>
<div class="space-y-1"><Label>Status</Label><select v-model="scheduleForm.status" class="h-10 w-full rounded-md border bg-background px-3 text-sm"><option value="scheduled">Dijadwalkan</option><option value="completed">Selesai</option><option value="cancelled">Dibatalkan</option></select></div>
<div class="flex justify-end gap-2"><Button type="button" variant="outline" @click="scheduleDialog=false">Tutup</Button><Button type="submit" :disabled="scheduleForm.processing">Simpan perubahan</Button></div>
</form></div>
<div v-if="classDialog" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" @click.self="classDialog=false">
<form class="w-full max-w-lg space-y-4 rounded-2xl bg-background p-6 shadow-xl" @submit.prevent="saveClass">
<div><h2 class="text-lg font-bold">{{editingClass?'Edit master kelas':'Tambah master kelas'}}</h2><p class="text-sm text-muted-foreground">Harga dan kapasitas khusus disimpan pada masing-masing jadwal.</p></div>
<div class="space-y-1"><Label>Fasilitas</Label><select v-model="classForm.facility_id" required class="h-10 w-full rounded-md border bg-background px-3"><option value="" disabled>Pilih fasilitas</option><option v-for="f in facilities" :key="f.id" :value="String(f.id)">{{f.name}}</option></select><p v-if="classForm.errors.facility_id" class="text-xs text-destructive">{{classForm.errors.facility_id}}</p></div>
<div class="space-y-1"><Label>Nama kelas</Label><Input v-model="classForm.name" required/><p v-if="classForm.errors.name" class="text-xs text-destructive">{{classForm.errors.name}}</p></div>
<div class="space-y-1"><Label>Deskripsi</Label><textarea v-model="classForm.description" rows="3" class="w-full rounded-md border bg-background p-3 text-sm"/></div>
<div class="grid grid-cols-2 gap-3"><div class="space-y-1"><Label>Durasi (menit)</Label><Input v-model="classForm.duration_minutes" type="number" min="1" required/></div><div class="space-y-1"><Label>Kapasitas default</Label><Input v-model="classForm.default_capacity" type="number" min="1" required/></div></div>
<label class="flex items-center gap-2 text-sm"><input v-model="classForm.is_active" type="checkbox"/> Kelas aktif dan bisa dijadwalkan</label>
<div class="flex justify-end gap-2"><Button type="button" variant="outline" @click="classDialog=false">Tutup</Button><Button type="submit" :disabled="classForm.processing">Simpan</Button></div>
</form>
</div>
</div>
</template>