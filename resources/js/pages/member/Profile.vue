<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import MemberDashboardLayout from '@/layouts/member/MemberDashboardLayout.vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineOptions({ layout: MemberDashboardLayout });

type Profile = {
    name: string;
    email: string;
    phone: string | null;
    member_code: string | null;
    face_photo_url: string | null;
    image_profile_url: string | null;
};

const props = defineProps<{ profile: Profile }>();
const page = usePage();
const user = computed(() => page.props.auth.user);
const imagePreview = ref<string | null>(null);

function handleImageChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    imagePreview.value = file ? URL.createObjectURL(file) : null;
}
</script>

<template>
    <Head title="Profil Saya" />

    <div class="space-y-6">
        <header>
            <p class="text-xs font-bold uppercase tracking-[.18em] text-emerald-700">Akun member</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">Profil Saya</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola informasi profil dan keamanan akun Sport Center dari halaman ini.</p>
        </header>

        <section class="w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center gap-4 border-b border-slate-100 bg-slate-50/70 p-6 sm:px-8">
                <img v-if="imagePreview || profile.image_profile_url" :src="imagePreview || profile.image_profile_url || undefined" :alt="'Foto profil ' + profile.name" class="size-16 rounded-2xl object-cover" />
                <div v-else class="grid size-16 place-items-center rounded-2xl bg-lime-100 text-2xl font-black text-lime-800">{{ profile.name.slice(0, 1).toUpperCase() }}</div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">{{ profile.name }}</h2>
                    <p class="text-sm text-slate-500">Member Sport Center</p>
                </div>
            </div>

            <div class="p-6 sm:p-8">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Informasi member</h2>
                    <p class="mt-1 text-sm text-slate-500">Data identitas akun yang terdaftar.</p>
                    <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Nama lengkap</dt><dd class="mt-2 font-semibold text-slate-800">{{ profile.name }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Email</dt><dd class="mt-2 font-semibold text-slate-800">{{ profile.email }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Nomor telepon</dt><dd class="mt-2 font-semibold text-slate-800">{{ profile.phone || 'Belum diisi' }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Kode member</dt><dd class="mt-2 font-semibold text-slate-800">{{ profile.member_code || 'Belum tersedia' }}</dd></div>
                    </dl>
                </div>

                <div class="my-8 border-t border-slate-100"></div>

                <div>
                    <h2 class="text-lg font-bold text-slate-900">Ubah informasi akun</h2>
                    <p class="mt-1 text-sm text-slate-500">Perbarui nama atau alamat email yang digunakan untuk masuk.</p>
                    <Form v-bind="ProfileController.update.form()" :options="{ forceFormData: true, preserveScroll: true }" class="mt-5 space-y-5" v-slot="{ errors, processing }">
                        <div class="grid gap-2">
                            <Label for="name">Nama lengkap</Label>
                            <Input id="name" name="name" :default-value="user.name" required autocomplete="name" />
                            <InputError :message="errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="email">Alamat email</Label>
                            <Input id="email" name="email" type="email" :default-value="user.email" required autocomplete="username" />
                            <InputError :message="errors.email" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="image_profile">Foto profil</Label>
                            <Input id="image_profile" name="image_profile" type="file" accept="image/png,image/jpeg,image/webp" @change="handleImageChange" />
                            <p class="text-xs text-slate-500">Format JPG, PNG, atau WEBP. Maksimal 2 MB.</p>
                            <InputError :message="errors.image_profile" />
                        </div>
                        <Button :disabled="processing">Simpan perubahan</Button>
                    </Form>
                </div>

                <div class="my-8 border-t border-slate-100"></div>

                <div>
                    <h2 class="text-lg font-bold text-slate-900">Ubah kata sandi</h2>
                    <p class="mt-1 text-sm text-slate-500">Gunakan kata sandi yang kuat dan jangan bagikan kepada orang lain.</p>
                    <Form
                        v-bind="SecurityController.update.form()"
                        :options="{ preserveScroll: true }"
                        reset-on-success
                        :reset-on-error="['password', 'password_confirmation', 'current_password']"
                        class="mt-5 space-y-5"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="current_password">Kata sandi saat ini</Label>
                            <PasswordInput id="current_password" name="current_password" autocomplete="current-password" placeholder="Masukkan kata sandi saat ini" />
                            <InputError :message="errors.current_password" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="password">Kata sandi baru</Label>
                            <PasswordInput id="password" name="password" autocomplete="new-password" placeholder="Masukkan kata sandi baru" />
                            <InputError :message="errors.password" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="password_confirmation">Konfirmasi kata sandi baru</Label>
                            <PasswordInput id="password_confirmation" name="password_confirmation" autocomplete="new-password" placeholder="Ulangi kata sandi baru" />
                            <InputError :message="errors.password_confirmation" />
                        </div>
                        <Button :disabled="processing">Perbarui kata sandi</Button>
                    </Form>
                </div>
            </div>
        </section>
    </div>
</template>
