<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import MemberDashboardLayout from '@/layouts/member/MemberDashboardLayout.vue';

defineOptions({ layout: MemberDashboardLayout });
defineProps<{profile:{name:string;email:string;phone:string|null;member_code:string|null}}>();
</script>
<template>
  <Head title="Profil Saya" />
  <div class="space-y-6">
    <header><p class="text-xs font-bold uppercase tracking-[.18em] text-emerald-700">Akun member</p><h1 class="mt-2 text-2xl font-bold text-slate-900">Profil Saya</h1><p class="mt-1 text-sm text-slate-500">Informasi akun yang terdaftar di Sport Center.</p></header>
    <section class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 sm:p-8">
      <div class="flex items-center gap-4 border-b border-slate-100 pb-6"><div class="grid size-16 place-items-center rounded-2xl bg-lime-100 text-2xl font-black text-lime-800">{{ profile.name.slice(0,1).toUpperCase() }}</div><div><h2 class="text-lg font-bold text-slate-900">{{ profile.name }}</h2><p class="text-sm text-slate-500">Member Sport Center</p></div></div>
      <dl class="mt-6 grid gap-5 sm:grid-cols-2"><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Nama lengkap</dt><dd class="mt-2 font-semibold text-slate-800">{{ profile.name }}</dd></div><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Email</dt><dd class="mt-2 font-semibold text-slate-800">{{ profile.email }}</dd></div><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Nomor telepon</dt><dd class="mt-2 font-semibold text-slate-800">{{ profile.phone || 'Belum diisi' }}</dd></div><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Kode member</dt><dd class="mt-2 font-semibold text-slate-800">{{ profile.member_code || 'Belum tersedia' }}</dd></div></dl>
      <div class="mt-7 rounded-xl bg-slate-50 p-4 text-sm leading-6 text-slate-600">Untuk keamanan, perubahan email dan kata sandi dilakukan melalui pengaturan akun.</div>
      <Link href="/settings/profile" class="mt-5 inline-flex rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Pengaturan akun</Link>
    </section>
  </div>
</template>