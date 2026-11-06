<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';

type Selection = {
    package: { id: number; name: string; facility_id: number; facility: string; duration_days: number; price: number };
    trainer_package: { id: number; name: string; total_sessions: number; duration_days: number; price: number } | null;
    trainer: { id: number; name: string; specialty: string | null } | null;
    total: number;
    payment_method: 'bank_transfer' | 'cash';
};
type CurrentUser = { name: string; email: string } | null;

const props = defineProps<{ selection: Selection; currentUser: CurrentUser }>();
const form = useForm({
    package_id: props.selection.package.id,
    trainer_package_id: props.selection.trainer_package?.id ?? null as number | null,
    trainer_id: props.selection.trainer?.id ?? null as number | null,
    payment_method: props.selection.payment_method,
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    terms_accepted: false,
});

const currency = (amount: number) => new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
}).format(amount);
const duration = (days: number) => days >= 30 && days % 30 === 0 ? `${days / 30} bulan` : `${days} hari`;

const switchToMemberLogin = () => {
    router.post('/membership/register/switch-account');
};

const submit = () => {
    form.post('/membership/register', {
        preserveScroll: true,
        onSuccess: () => localStorage.removeItem('sport-center-membership-selection-v1'),
    });
};
</script>

<template>
    <Head title="Pendaftaran Membership — DreamSports" />
    <PublicLayout>
        <main class="min-h-screen bg-[#f7f9f3] text-[#001428]">
            <section class="bg-[#001428] px-5 py-12 text-white sm:py-16">
                <div class="mx-auto max-w-5xl">
                    <Link href="/membership#pilih-paket" class="inline-flex items-center gap-2 text-sm font-bold text-slate-300 transition hover:text-[#b7e934]"><i class="fa-solid fa-arrow-left"></i> Kembali ke pilihan paket</Link>
                    <p class="mt-8 text-xs font-black uppercase tracking-[.2em] text-[#b7e934]">Langkah terakhir</p>
                    <h1 class="mt-2 text-3xl font-black sm:text-5xl">Lengkapi pendaftaranmu</h1>
                    <p class="mt-4 max-w-2xl leading-7 text-slate-300">Pilihan membership dan trainer yang kamu tentukan sebelumnya sudah dibawa ke halaman ini. Periksa ringkasannya, lalu lengkapi data akun.</p>
                </div>
            </section>

            <section class="mx-auto grid max-w-5xl gap-8 px-5 py-10 lg:grid-cols-[1fr_.9fr] lg:py-14">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="mb-7">
                        <p class="text-xs font-black uppercase tracking-[.18em] text-[#789900]">Data pendaftaran</p>
                        <h2 class="mt-2 text-2xl font-black">Informasi member</h2>
                    </div>

                    <div v-if="currentUser" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                        <div class="flex items-center gap-3"><span class="grid size-11 place-items-center rounded-xl bg-white text-emerald-700"><i class="fa-solid fa-user-check"></i></span><div><p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Login sebagai member</p><p class="mt-1 font-extrabold text-slate-900">{{ currentUser.name }}</p><p class="text-sm text-slate-600">{{ currentUser.email }}</p></div></div>
                        <p class="mt-4 text-sm leading-6 text-slate-600">Paket akan langsung ditambahkan ke akun yang sedang aktif.</p>
                    </div>

                    <form class="mt-6 space-y-5" @submit.prevent="submit">
                        <template v-if="!currentUser">
                            <div><label for="name" class="mb-2 block text-sm font-bold text-slate-700">Nama lengkap</label><input id="name" v-model="form.name" type="text" autocomplete="name" required class="form-input" placeholder="Nama sesuai identitas" /><p v-if="form.errors.name" class="mt-1 text-sm text-rose-600">{{ form.errors.name }}</p></div>
                            <div><label for="email" class="mb-2 block text-sm font-bold text-slate-700">Alamat email</label><input id="email" v-model="form.email" type="email" autocomplete="email" required class="form-input" placeholder="nama@email.com" /><p v-if="form.errors.email" class="mt-1 text-sm text-rose-600">{{ form.errors.email }}</p></div>
                            <div><label for="phone" class="mb-2 block text-sm font-bold text-slate-700">Nomor WhatsApp / HP</label><input id="phone" v-model="form.phone" type="tel" autocomplete="tel" required class="form-input" placeholder="08xxxxxxxxxx" /><p v-if="form.errors.phone" class="mt-1 text-sm text-rose-600">{{ form.errors.phone }}</p></div>
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div><label for="password" class="mb-2 block text-sm font-bold text-slate-700">Password</label><input id="password" v-model="form.password" type="password" autocomplete="new-password" required minlength="8" class="form-input" placeholder="Minimal 8 karakter" /><p v-if="form.errors.password" class="mt-1 text-sm text-rose-600">{{ form.errors.password }}</p></div>
                                <div><label for="password_confirmation" class="mb-2 block text-sm font-bold text-slate-700">Ulangi password</label><input id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" required minlength="8" class="form-input" placeholder="Ulangi password" /></div>
                            </div>
                        </template>

                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl bg-slate-50 p-4 text-sm leading-6 text-slate-600">
                            <input v-model="form.terms_accepted" type="checkbox" required class="mt-1 size-4 accent-[#789900]" />
                            <span>Saya menyetujui ketentuan membership dan memastikan pilihan paket di atas sudah benar.</span>
                        </label>
                        <p v-if="form.errors.terms_accepted" class="text-sm text-rose-600">{{ form.errors.terms_accepted }}</p>
                        <p v-if="form.errors.package_id || form.errors.trainer_package_id || form.errors.trainer_id" class="text-sm text-rose-600">{{ form.errors.package_id || form.errors.trainer_package_id || form.errors.trainer_id }}</p>

                        <button type="submit" :disabled="form.processing" class="flex w-full items-center justify-center gap-3 rounded-full bg-[#a4da01] px-6 py-4 font-black text-[#001428] transition hover:bg-[#b8ec24] disabled:cursor-not-allowed disabled:opacity-50">
                            <i v-if="form.processing" class="fa-solid fa-spinner fa-spin"></i>
                            {{ form.processing ? 'Menyimpan pendaftaran…' : currentUser ? 'Daftarkan paket ke akun saya' : 'Buat akun & daftar membership' }}
                            <i v-if="!form.processing" class="fa-solid fa-arrow-right"></i>
                        </button>
                    </form>

                    <div v-if="!currentUser" class="mt-6 rounded-2xl bg-slate-50 p-4 text-center text-sm text-slate-600">
                        <p>Sudah punya akun member?</p>
                        <button type="button" @click="switchToMemberLogin" class="mt-2 font-bold text-[#668500] underline underline-offset-4">Keluar dari akun ini & masuk dengan akun member</button>
                    </div>
                </div>

                <aside class="h-fit rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:sticky lg:top-8">
                    <p class="text-xs font-black uppercase tracking-[.18em] text-[#789900]">Review pilihan</p>
                    <h2 class="mt-2 text-xl font-black">Ringkasan pesanan</h2>
                    <div class="mt-6 rounded-2xl bg-[#f6f8f2] p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Membership · {{ selection.package.facility }}</p>
                        <h3 class="mt-2 text-lg font-black">{{ selection.package.name }}</h3>
                        <p class="mt-1 text-sm text-slate-500">Masa berlaku {{ duration(selection.package.duration_days) }}</p>
                        <p class="mt-4 text-lg font-black">{{ currency(selection.package.price) }}</p>
                    </div>
                    <div v-if="selection.trainer_package" class="mt-4 rounded-2xl border border-slate-200 p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-[#789900]">Add-on personal trainer</p>
                        <h3 class="mt-2 font-black">{{ selection.trainer_package.name }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ selection.trainer_package.total_sessions }} sesi · {{ duration(selection.trainer_package.duration_days) }}</p>
                        <div class="mt-3 flex items-center justify-between gap-3 text-sm"><span class="text-slate-500">Trainer pilihan</span><strong>{{ selection.trainer?.name }}</strong></div>
                        <p class="mt-4 text-lg font-black">{{ currency(selection.trainer_package.price) }}</p>
                    </div>
                    <div v-else class="mt-4 rounded-2xl border border-dashed border-slate-300 p-5 text-sm text-slate-500">Kamu tidak memilih add-on trainer.</div>
                    <div class="mt-6 rounded-2xl border border-slate-200 p-4"><p class="text-xs font-bold uppercase tracking-wider text-slate-500">Metode pembayaran</p><div class="mt-2 flex items-center gap-3"><span class="grid size-10 place-items-center rounded-xl bg-[#f6f8f2] text-[#668500]"><i :class="selection.payment_method === 'cash' ? 'fa-solid fa-cash-register' : 'fa-solid fa-building-columns'"></i></span><strong class="text-sm text-[#001428]">{{ selection.payment_method === 'cash' ? 'Bayar di kasir' : 'Transfer bank' }}</strong></div></div>
                    <div class="mt-6 flex items-center justify-between border-t border-slate-100 pt-5"><span class="font-bold text-slate-600">Total pilihan</span><strong class="text-2xl font-black text-[#001428]">{{ currency(selection.total) }}</strong></div>
                    <p class="mt-4 text-xs leading-5 text-slate-400">Membership dan add-on trainer digabung menjadi satu invoice dan satu transaksi. Status pembayaran dapat dipantau melalui menu Pembayaran.</p>
                </aside>
            </section>
        </main>
    </PublicLayout>
</template>

<style scoped>
.form-input { width: 100%; border: 1px solid #dbe2ea; border-radius: 13px; background: white; padding: 12px 14px; color: #001428; outline: none; transition: border-color .15s ease, box-shadow .15s ease; }
.form-input:focus { border-color: #8dbb00; box-shadow: 0 0 0 3px rgba(164,218,1,.18); }
</style>
