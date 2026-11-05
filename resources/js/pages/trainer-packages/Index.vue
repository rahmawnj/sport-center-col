<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type TrainerPackage = { id:number; name:string; total_sessions:number; duration_days:number; price:string|number; description:string|null; is_active:boolean };
type MemberPackage = { id:number; member_name:string|null; trainer_name:string|null; package_name:string|null; start_date:string; expires_at:string; total_sessions:number; used_sessions:number; remaining_sessions:number; status:string };
type Option = { id:number; name:string; specialty?:string|null };
const props=defineProps<{packages:TrainerPackage[];members:Option[];trainers:Option[];memberPackages:{data:MemberPackage[];links:{url:string|null;label:string;active:boolean}[];total:number};stats:{packages:number;active:number;expiring:number}}>();
const editing=ref<TrainerPackage|null>(null);
const packageForm=useForm({name:'',total_sessions:5,duration_days:30,price:'',description:'',is_active:true});
const purchaseForm=useForm({user_id:'',trainer_id:'',trainer_package_id:'',start_date:new Date().toISOString().slice(0,10),payment_method:'cash',payment_status:'paid'});
const packageTitle=computed(()=>editing.value?'Edit paket trainer':'Tambah paket trainer');
function resetPackage(){editing.value=null;packageForm.reset();packageForm.clearErrors();packageForm.is_active=true;packageForm.total_sessions=5;packageForm.duration_days=30;}
function editPackage(item:TrainerPackage){editing.value=item;packageForm.name=item.name;packageForm.total_sessions=item.total_sessions;packageForm.duration_days=item.duration_days;packageForm.price=String(item.price);packageForm.description=item.description??'';packageForm.is_active=item.is_active;window.scrollTo({top:0,behavior:'smooth'});}
function savePackage(){if(editing.value){packageForm.put('/trainer-packages/'+editing.value.id,{preserveScroll:true,onSuccess:resetPackage});}else{packageForm.post('/trainer-packages',{preserveScroll:true,onSuccess:resetPackage});}}
function removePackage(item:TrainerPackage){if(window.confirm('Hapus paket '+item.name+'?'))router.delete('/trainer-packages/'+item.id,{preserveScroll:true});}
function purchase(){purchaseForm.post('/trainer-packages/purchase',{preserveScroll:true,onSuccess:()=>purchaseForm.reset('user_id','trainer_id','trainer_package_id')});}
function rupiah(v:string|number){return new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(Number(v));}
function date(v:string){return new Intl.DateTimeFormat('id-ID',{day:'2-digit',month:'short',year:'numeric'}).format(new Date(v+'T00:00:00'));}
function statusLabel(v:string){return ({active:'Aktif',pending:'Menunggu pembayaran',completed:'Sesi habis',expired:'Expired',cancelled:'Dibatalkan'} as Record<string,string>)[v]??v;}
</script>

<template>
<Head title="Paket Personal Trainer"/>
<div class="space-y-6 p-4 md:p-6">
<PageHeader eyebrow="Master Data" title="Paket Personal Trainer" description="Atur paket sesi PT, masa berlaku, harga, dan pembelian member."/>
<div class="grid gap-3 sm:grid-cols-3">
<div class="rounded-2xl border bg-card p-4"><p class="text-sm text-muted-foreground">Master paket</p><p class="mt-2 text-2xl font-semibold">{{stats.packages}}</p></div>
<div class="rounded-2xl border bg-card p-4"><p class="text-sm text-muted-foreground">Paket member aktif</p><p class="mt-2 text-2xl font-semibold">{{stats.active}}</p></div>
<div class="rounded-2xl border bg-card p-4"><p class="text-sm text-muted-foreground">Akan expired (7 hari)</p><p class="mt-2 text-2xl font-semibold">{{stats.expiring}}</p></div>
</div>

<div class="grid gap-6 xl:grid-cols-2">
<section class="rounded-2xl border bg-card p-5 shadow-sm">
<div class="mb-4"><h2 class="font-semibold">{{packageTitle}}</h2><p class="text-sm text-muted-foreground">Contoh: PT 10 sesi, berlaku 30 hari.</p></div>
<form class="grid gap-4 sm:grid-cols-2" @submit.prevent="savePackage">
<div class="grid gap-2 sm:col-span-2"><Label>Nama paket</Label><Input v-model="packageForm.name" placeholder="Personal Trainer 10 Sesi"/><p v-if="packageForm.errors.name" class="text-xs text-destructive">{{packageForm.errors.name}}</p></div>
<div class="grid gap-2"><Label>Jumlah sesi</Label><Input v-model.number="packageForm.total_sessions" type="number" min="1"/><p v-if="packageForm.errors.total_sessions" class="text-xs text-destructive">{{packageForm.errors.total_sessions}}</p></div>
<div class="grid gap-2"><Label>Masa berlaku (hari)</Label><Input v-model.number="packageForm.duration_days" type="number" min="1"/><p v-if="packageForm.errors.duration_days" class="text-xs text-destructive">{{packageForm.errors.duration_days}}</p></div>
<div class="grid gap-2 sm:col-span-2"><Label>Harga (Rp)</Label><Input v-model="packageForm.price" type="number" min="0" placeholder="900000"/><p v-if="packageForm.errors.price" class="text-xs text-destructive">{{packageForm.errors.price}}</p></div>
<div class="grid gap-2 sm:col-span-2"><Label>Deskripsi (opsional)</Label><textarea v-model="packageForm.description" rows="2" class="w-full rounded-md border bg-background px-3 py-2 text-sm" placeholder="Deskripsi paket..."/></div>
<label v-if="editing" class="flex items-center gap-2 text-sm sm:col-span-2"><input v-model="packageForm.is_active" type="checkbox"/> Paket aktif dan bisa dijual</label>
<div class="flex gap-2 sm:col-span-2"><Button type="submit" :disabled="packageForm.processing">{{packageForm.processing?'Menyimpan...':editing?'Simpan perubahan':'Tambah paket'}}</Button><Button v-if="editing" type="button" variant="outline" @click="resetPackage">Batal edit</Button></div>
</form>
</section>

<section class="rounded-2xl border bg-card p-5 shadow-sm">
<div class="mb-4"><h2 class="font-semibold">Catat pembelian paket</h2><p class="text-sm text-muted-foreground">Paket member akan memiliki tanggal expired dan kuota sesi sendiri.</p></div>
<form class="grid gap-4" @submit.prevent="purchase">
<div class="grid gap-2"><Label>Member</Label><select v-model="purchaseForm.user_id" required class="h-10 rounded-md border bg-background px-3 text-sm"><option value="">Pilih member</option><option v-for="m in members" :key="m.id" :value="String(m.id)">{{m.name}}</option></select><p v-if="purchaseForm.errors.user_id" class="text-xs text-destructive">{{purchaseForm.errors.user_id}}</p></div>
<div class="grid gap-2"><Label>Trainer</Label><select v-model="purchaseForm.trainer_id" required class="h-10 rounded-md border bg-background px-3 text-sm"><option value="">Pilih trainer</option><option v-for="t in trainers" :key="t.id" :value="String(t.id)">{{t.name}} {{t.specialty?'· '+t.specialty:''}}</option></select><p v-if="purchaseForm.errors.trainer_id" class="text-xs text-destructive">{{purchaseForm.errors.trainer_id}}</p></div>
<div class="grid gap-2"><Label>Paket</Label><select v-model="purchaseForm.trainer_package_id" required class="h-10 rounded-md border bg-background px-3 text-sm"><option value="">Pilih paket</option><option v-for="p in packages.filter(x=>x.is_active)" :key="p.id" :value="String(p.id)">{{p.name}} · {{p.total_sessions}} sesi · {{p.duration_days}} hari · {{rupiah(p.price)}}</option></select><p v-if="purchaseForm.errors.trainer_package_id" class="text-xs text-destructive">{{purchaseForm.errors.trainer_package_id}}</p></div>
<div class="grid gap-2"><Label>Tanggal mulai</Label><Input v-model="purchaseForm.start_date" type="date" required/><p v-if="purchaseForm.errors.start_date" class="text-xs text-destructive">{{purchaseForm.errors.start_date}}</p></div>
<div class="grid gap-2 sm:grid-cols-2">
<div class="grid gap-2"><Label>Metode bayar</Label><select v-model="purchaseForm.payment_method" class="h-10 rounded-md border bg-background px-3 text-sm"><option value="cash">Cash</option><option value="bank_transfer">Transfer bank</option><option value="qris">QRIS</option></select></div>
<div class="grid gap-2"><Label>Status bayar</Label><select v-model="purchaseForm.payment_status" class="h-10 rounded-md border bg-background px-3 text-sm"><option value="paid">Lunas</option><option value="pending">Belum bayar</option></select></div>
</div>
<p class="text-xs text-muted-foreground">Status paket otomatis aktif jika pembayaran lunas. Jika belum bayar, paket berstatus menunggu pembayaran.</p>
<Button type="submit" :disabled="purchaseForm.processing || !members.length || !trainers.length || !packages.some(p=>p.is_active)">{{purchaseForm.processing?'Menyimpan...':'Simpan pembelian'}}</Button>
</form>
</section>
</div>

<section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
<div class="border-b p-5"><h2 class="font-semibold">Daftar paket trainer</h2><p class="text-sm text-muted-foreground">Paket master yang tersedia untuk dijual.</p></div>
<div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-muted/40 text-left text-muted-foreground"><tr><th class="p-3">Nama paket</th><th class="p-3">Sesi</th><th class="p-3">Masa berlaku</th><th class="p-3">Harga</th><th class="p-3">Status</th><th class="p-3 text-right">Aksi</th></tr></thead><tbody>
<tr v-for="p in packages" :key="p.id" class="border-t"><td class="p-3 font-medium">{{p.name}}<p class="text-xs font-normal text-muted-foreground">{{p.description}}</p></td><td class="p-3">{{p.total_sessions}} sesi</td><td class="p-3">{{p.duration_days}} hari</td><td class="p-3">{{rupiah(p.price)}}</td><td class="p-3"><span :class="p.is_active?'text-emerald-600':'text-muted-foreground'">{{p.is_active?'Aktif':'Nonaktif'}}</span></td><td class="p-3"><div class="flex justify-end gap-2"><Button size="sm" variant="outline" @click="editPackage(p)">Edit</Button><Button size="sm" variant="ghost" class="text-destructive" @click="removePackage(p)">Hapus</Button></div></td></tr>
<tr v-if="!packages.length"><td colspan="6" class="p-8 text-center text-muted-foreground">Belum ada paket trainer.</td></tr>
</tbody></table></div>
</section>

<section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
<div class="border-b p-5"><h2 class="font-semibold">Paket yang dimiliki member</h2><p class="text-sm text-muted-foreground">Sisa sesi dihitung dari total sesi dikurangi sesi yang digunakan.</p></div>
<div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-muted/40 text-left text-muted-foreground"><tr><th class="p-3">Member</th><th class="p-3">Paket / Trainer</th><th class="p-3">Sesi</th><th class="p-3">Mulai</th><th class="p-3">Expired</th><th class="p-3">Status</th></tr></thead><tbody>
<tr v-for="item in memberPackages.data" :key="item.id" class="border-t"><td class="p-3 font-medium">{{item.member_name||'—'}}</td><td class="p-3">{{item.package_name}}<p class="text-xs text-muted-foreground">{{item.trainer_name}}</p></td><td class="p-3 tabular-nums">{{item.remaining_sessions}} / {{item.total_sessions}} tersisa<p class="text-xs text-muted-foreground">{{item.used_sessions}} digunakan</p></td><td class="p-3">{{date(item.start_date)}}</td><td class="p-3">{{date(item.expires_at)}}</td><td class="p-3">{{statusLabel(item.status)}}</td></tr>
<tr v-if="!memberPackages.data.length"><td colspan="6" class="p-8 text-center text-muted-foreground">Belum ada pembelian paket trainer.</td></tr>
</tbody></table></div>
<div v-if="memberPackages.links.length>3" class="flex flex-wrap gap-1 border-t p-3"><template v-for="link in memberPackages.links" :key="link.label"><button v-if="link.url" class="rounded-md border px-3 py-1 text-xs" :class="link.active?'bg-primary text-primary-foreground':''" @click="router.get(link.url,{}, {preserveState:true, preserveScroll:true})" v-html="link.label"/><span v-else class="px-3 py-1 text-xs text-muted-foreground" v-html="link.label"/></template></div>
</section>
</div>
</template>
