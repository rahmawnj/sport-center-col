<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user as any);
const imagePreview = ref<string | null>(null);

function handleImageChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    imagePreview.value = file ? URL.createObjectURL(file) : null;
}
</script>

<template>
    <Head title="Pengaturan Profil" />

    <div class="w-full space-y-8">
        <div class="flex flex-col gap-2">
            <Heading
                variant="small"
                title="Profil Saya"
                description="Kelola foto, nama, dan alamat email akun administrator."
            />
        </div>

        <section class="w-full max-w-3xl overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
            <div class="flex items-center gap-4 border-b border-border bg-muted/30 p-6">
                <img
                    v-if="imagePreview || user.image_profile_url"
                    :src="imagePreview || user.image_profile_url"
                    :alt="'Foto profil ' + user.name"
                    class="size-20 rounded-2xl border border-border object-cover"
                />
                <div v-else class="grid size-20 place-items-center rounded-2xl bg-primary/10 text-2xl font-bold text-primary">
                    {{ String(user.name || 'A').slice(0, 1).toUpperCase() }}
                </div>
                <div class="min-w-0">
                    <h2 class="truncate text-lg font-bold text-foreground">{{ user.name }}</h2>
                    <p class="truncate text-sm text-muted-foreground">{{ user.email }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">Administrator Sport Center</p>
                </div>
            </div>

            <div class="p-6">
                <Form
                    action="/settings/profile"
                    method="post"
                    :options="{ forceFormData: true, preserveScroll: true }"
                    class="space-y-5"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="name">Nama lengkap</Label>
                        <Input
                            id="name"
                            name="name"
                            :default-value="user.name"
                            required
                            autocomplete="name"
                            placeholder="Nama lengkap"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Alamat email</Label>
                        <Input
                            id="email"
                            name="email"
                            type="email"
                            :default-value="user.email"
                            required
                            autocomplete="username"
                            placeholder="Email"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="image_profile">Foto profil</Label>
                        <Input
                            id="image_profile"
                            name="image_profile"
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            @change="handleImageChange"
                        />
                        <p class="text-xs text-muted-foreground">Format JPG, PNG, atau WEBP. Maksimal 2 MB.</p>
                        <InputError :message="errors.image_profile" />
                    </div>

                    <div class="flex items-center gap-3">
                        <Button :disabled="processing">
                            {{ processing ? 'Menyimpan...' : 'Simpan perubahan' }}
                        </Button>
                    </div>
                </Form>

                <div v-if="page.props.mustVerifyEmail && !user.email_verified_at" class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    Email belum diverifikasi.
                    <Link :href="send()" as="button" class="font-semibold underline underline-offset-4">
                        Kirim ulang email verifikasi
                    </Link>
                    <p v-if="page.props.status === 'verification-link-sent'" class="mt-2 font-medium text-green-700">
                        Link verifikasi baru sudah dikirim.
                    </p>
                </div>
            </div>
        </section>

        <DeleteUser />
    </div>
</template>
