<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{ passwordRules: string }>();

defineOptions({
    layout: {
        title: 'Buat akun',
        description: 'Masukkan data Anda di bawah untuk membuat akun',
    },
});

const continuePendingMembershipCheckout = () => {
    const key = 'sport-center-membership-selection-v1';
    const raw = localStorage.getItem(key);
    if (!raw) return;

    try {
        const selection = JSON.parse(raw) as {
            package_id?: number;
            trainer_package_id?: number | null;
            trainer_id?: number | null;
        };
        if (!selection.package_id) return;

        const params = new URLSearchParams({ package_id: String(selection.package_id) });
        if (selection.trainer_package_id) params.set('trainer_package_id', String(selection.trainer_package_id));
        if (selection.trainer_id) params.set('trainer_id', String(selection.trainer_id));
        router.visit(`/membership/register?${params.toString()}`);
    } catch {
        // Keep malformed temporary data untouched for manual recovery.
    }
};
</script>

<template>
    <Head title="Daftar" />
    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        @success="continuePendingMembershipCheckout"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">Nama</Label>
                <Input id="name" type="text" required autofocus :tabindex="1" autocomplete="name" name="name" placeholder="Nama lengkap" />
                <InputError :message="errors.name" />
            </div>
            <div class="grid gap-2">
                <Label for="email">Alamat email</Label>
                <Input id="email" type="email" required :tabindex="2" autocomplete="email" name="email" placeholder="email@contoh.com" />
                <InputError :message="errors.email" />
            </div>
            <div class="grid gap-2">
                <Label for="password">Kata sandi</Label>
                <PasswordInput id="password" required :tabindex="3" autocomplete="new-password" name="password" placeholder="Kata sandi" :passwordrules="passwordRules" />
                <InputError :message="errors.password" />
            </div>
            <div class="grid gap-2">
                <Label for="password_confirmation">Konfirmasi kata sandi</Label>
                <PasswordInput id="password_confirmation" required :tabindex="4" autocomplete="new-password" name="password_confirmation" placeholder="Konfirmasi kata sandi" :passwordrules="passwordRules" />
                <InputError :message="errors.password_confirmation" />
            </div>
            <Button type="submit" class="mt-2 w-full" tabindex="5" :disabled="processing" data-test="register-user-button">
                <Spinner v-if="processing" /> Buat akun
            </Button>
        </div>
        <div class="text-center text-sm text-muted-foreground">
            Sudah punya akun? <TextLink :href="login()" class="underline underline-offset-4" :tabindex="6">Masuk</TextLink>
        </div>
    </Form>
</template>
