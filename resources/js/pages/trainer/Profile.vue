<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Camera, Check, LoaderCircle, UserRound, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import TrainerDashboardLayout from '@/layouts/trainer/TrainerDashboardLayout.vue';

defineOptions({ layout: TrainerDashboardLayout });

const props = defineProps<{
    profile: {
        name: string;
        email: string | null;
        specialty: string | null;
        photo_url: string | null;
        facilities: string[];
    };
}>();

const editing = ref(false);
const photoInput = ref<HTMLInputElement | null>(null);
const previewUrl = ref<string | null>(null);
const form = useForm({
    name: props.profile.name,
    email: props.profile.email ?? '',
    specialty: props.profile.specialty ?? '',
    image_profile: null as File | null,
});

const avatarUrl = computed(() => previewUrl.value || props.profile.photo_url);
const initials = computed(() => props.profile.name.trim().split(/\s+/).slice(0, 2).map(part => part[0]).join('').toUpperCase());

function selectPhoto(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.image_profile = file;
    previewUrl.value = file ? URL.createObjectURL(file) : null;
}

function startEditing() {
    form.name = props.profile.name;
    form.email = props.profile.email ?? '';
    form.specialty = props.profile.specialty ?? '';
    form.image_profile = null;
    previewUrl.value = null;
    editing.value = true;
}

function cancelEditing() {
    editing.value = false;
    form.clearErrors();
    form.reset();
    form.image_profile = null;
    previewUrl.value = null;
    if (photoInput.value) photoInput.value.value = '';
}

function save() {
    form.transform(data => ({ ...data, _method: 'put' })).post('/trainer/profile', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            editing.value = false;
            previewUrl.value = null;
            form.image_profile = null;
        },
    });
}
</script>

<template>
    <Head title="Profil Trainer" />

    <div class="w-full space-y-6">
        <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-xs font-bold uppercase tracking-[.18em] text-lime-700">Akun trainer</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">Profil Saya</h1>
                <p class="mt-1 text-sm text-slate-500">Kelola informasi pribadi dan foto profil akun trainer.</p>
            </div>
            <button v-if="!editing" type="button" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-lime-600 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-lime-700" @click="startEditing">
                <UserRound class="size-4" /> Edit profil
            </button>
        </header>

        <section class="w-full overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="relative h-36 bg-gradient-to-r from-slate-950 via-slate-800 to-lime-800 sm:h-44">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 22px 22px;"></div>
                <div class="absolute bottom-5 left-6 text-white sm:left-8">
                    <p class="text-xs font-semibold uppercase tracking-[.2em] text-lime-200">Sport Center · Personal Trainer</p>
                    <p class="mt-1 text-lg font-bold sm:text-xl">Informasi akun</p>
                </div>
            </div>

            <div class="px-5 pb-7 sm:px-8">
                <div class="-mt-12 flex flex-col gap-4 border-b border-slate-100 pb-6 sm:-mt-14 sm:flex-row sm:items-end sm:justify-between">
                    <div class="flex items-end gap-4">
                        <div class="relative grid size-24 shrink-0 place-items-center overflow-hidden rounded-3xl border-4 border-white bg-lime-100 text-2xl font-black text-lime-800 shadow-md sm:size-28">
                            <img v-if="avatarUrl" :src="avatarUrl" :alt="form.name || profile.name" class="absolute inset-0 size-full object-cover" />
                            <span v-else>{{ initials }}</span>
                        </div>
                        <div class="min-w-0 pb-1">
                            <h2 class="truncate text-xl font-bold text-slate-950 sm:text-2xl">{{ profile.name }}</h2>
                            <p class="mt-1 truncate text-sm text-slate-500">{{ profile.email || 'Email belum tersedia' }}</p>
                            <span class="mt-2 inline-flex rounded-full bg-lime-50 px-2.5 py-1 text-xs font-bold text-lime-800">Trainer</span>
                        </div>
                    </div>
                    <div v-if="editing" class="flex gap-2 sm:pb-1">
                        <button type="button" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50" :disabled="form.processing" @click="cancelEditing">
                            <X class="size-4" /> Batal
                        </button>
                        <button type="button" class="inline-flex h-10 items-center gap-2 rounded-xl bg-lime-600 px-4 text-sm font-bold text-white hover:bg-lime-700 disabled:opacity-60" :disabled="form.processing" @click="save">
                            <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                            <Check v-else class="size-4" /> Simpan
                        </button>
                    </div>
                </div>

                <form v-if="editing" class="mt-7 space-y-6" @submit.prevent="save">
                    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                        <div class="space-y-2">
                            <label for="trainer-profile-name" class="text-sm font-semibold text-slate-700">Nama lengkap</label>
                            <input id="trainer-profile-name" v-model="form.name" type="text" required maxlength="255" class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none transition focus:border-lime-500 focus:ring-2 focus:ring-lime-100" />
                            <p v-if="form.errors.name" class="text-xs text-rose-600">{{ form.errors.name }}</p>
                        </div>
                        <div class="space-y-2">
                            <label for="trainer-profile-email" class="text-sm font-semibold text-slate-700">Email login</label>
                            <input id="trainer-profile-email" v-model="form.email" type="email" required maxlength="255" class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none transition focus:border-lime-500 focus:ring-2 focus:ring-lime-100" />
                            <p v-if="form.errors.email" class="text-xs text-rose-600">{{ form.errors.email }}</p>
                        </div>
                        <div class="space-y-2 lg:col-span-2">
                            <label for="trainer-profile-specialty" class="text-sm font-semibold text-slate-700">Spesialisasi</label>
                            <input id="trainer-profile-specialty" v-model="form.specialty" type="text" maxlength="255" placeholder="Contoh: Strength Training, Yoga, Fitness" class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none transition focus:border-lime-500 focus:ring-2 focus:ring-lime-100" />
                            <p v-if="form.errors.specialty" class="text-xs text-rose-600">{{ form.errors.specialty }}</p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                            <div class="grid size-16 shrink-0 place-items-center overflow-hidden rounded-2xl bg-white text-lime-700 ring-1 ring-slate-200">
                                <img v-if="avatarUrl" :src="avatarUrl" alt="Preview foto profil" class="size-full object-cover" />
                                <Camera v-else class="size-7" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <label for="trainer-profile-image" class="text-sm font-bold text-slate-800">Foto profil</label>
                                <p class="mt-1 text-xs text-slate-500">JPG, PNG, atau WEBP. Maksimal 2 MB.</p>
                                <input id="trainer-profile-image" ref="photoInput" type="file" accept="image/jpeg,image/png,image/webp" class="mt-3 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-lime-100 file:px-3 file:py-2 file:text-xs file:font-bold file:text-lime-800 hover:file:bg-lime-200" @change="selectPhoto" />
                                <p v-if="form.errors.image_profile" class="mt-1 text-xs text-rose-600">{{ form.errors.image_profile }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Cabang / fasilitas</p>
                        <p class="mt-2 text-sm font-semibold text-slate-700">{{ profile.facilities.join(', ') || 'Belum ditentukan' }}</p>
                        <p class="mt-1 text-xs text-slate-500">Penugasan cabang dikelola oleh admin.</p>
                    </div>
                </form>

                <div v-else class="mt-7 grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Nama lengkap</p>
                        <p class="mt-2 break-words text-sm font-semibold text-slate-900">{{ profile.name }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Email login</p>
                        <p class="mt-2 break-words text-sm font-semibold text-slate-900">{{ profile.email || 'Belum tersedia' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Spesialisasi</p>
                        <p class="mt-2 break-words text-sm font-semibold text-slate-900">{{ profile.specialty || 'Belum diisi' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Cabang / olahraga</p>
                        <p class="mt-2 break-words text-sm font-semibold text-slate-900">{{ profile.facilities.join(', ') || 'Belum ditentukan' }}</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
