<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Pencil, Trash2, Clock } from '@lucide/vue';
import { ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';

type Facility = { id: number; name: string; turnaround_minutes: number };
type Slot = {
    id: number;
    facility_id: number;
    facility_name: string;
    start_time: string;
    end_time: string;
    is_active: boolean;
};

const props = defineProps<{ facilities: Facility[]; slots: Slot[] }>();
const editing = ref<Slot | null>(null);
const form = useForm({
    facility_id: props.facilities[0]?.id ?? '',
    start_time: '07:00',
    end_time: '22:00',
    is_active: true,
});

function resetForm() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.facility_id = props.facilities[0]?.id ?? '';
    form.start_time = '07:00';
    form.end_time = '22:00';
    form.is_active = true;
}

function edit(slot: Slot) {
    editing.value = slot;
    form.facility_id = slot.facility_id;
    form.start_time = slot.start_time;
    form.end_time = slot.end_time;
    form.is_active = slot.is_active;
    form.clearErrors();
}

function submit() {
    if (editing.value) {
        form.put(`/booking-time-slots/${editing.value.id}`, { onSuccess: resetForm });
    } else {
        form.post('/booking-time-slots', { onSuccess: resetForm });
    }
}

function remove(slot: Slot) {
    if (window.confirm(`Hapus jam ${slot.start_time}–${slot.end_time} untuk ${slot.facility_name}?`)) {
        form.delete(`/booking-time-slots/${slot.id}`);
    }
}
</script>

<template>
    <Head title="Master Jam Booking" />
    <div class="space-y-6 p-4 md:p-6">
        <Link href="/facilities" class="text-sm text-muted-foreground hover:text-foreground">Kembali ke olahraga</Link>
        <PageHeader
            eyebrow="Pengaturan booking"
            title="Master Jam Booking"
            description="Atur rentang jam operasional per fasilitas. Booking online dan kasir menggunakan jadwal yang sama."
        />

        <div class="grid gap-6 lg:grid-cols-[360px_1fr]">
            <form class="h-fit space-y-4 rounded-xl border bg-card p-5" @submit.prevent="submit">
                <div class="flex items-center gap-2 font-semibold">
                    <Clock class="size-4 text-primary" />
                    {{ editing ? 'Ubah rentang jam' : 'Tambah rentang jam' }}
                </div>

                <div class="grid gap-2">
                    <label for="facility" class="text-sm font-medium">Fasilitas</label>
                    <select id="facility" v-model="form.facility_id" class="h-10 rounded-md border bg-background px-3 text-sm" required>
                        <option v-for="facility in facilities" :key="facility.id" :value="facility.id">
                            {{ facility.name }} (jeda {{ facility.turnaround_minutes }} menit)
                        </option>
                    </select>
                    <p v-if="form.errors.facility_id" class="text-xs text-destructive">{{ form.errors.facility_id }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="grid gap-2">
                        <label for="start" class="text-sm font-medium">Mulai</label>
                        <input id="start" v-model="form.start_time" type="time" required class="h-10 rounded-md border bg-background px-3 text-sm" />
                        <p v-if="form.errors.start_time" class="text-xs text-destructive">{{ form.errors.start_time }}</p>
                    </div>
                    <div class="grid gap-2">
                        <label for="end" class="text-sm font-medium">Selesai</label>
                        <input id="end" v-model="form.end_time" type="time" required class="h-10 rounded-md border bg-background px-3 text-sm" />
                        <p v-if="form.errors.end_time" class="text-xs text-destructive">{{ form.errors.end_time }}</p>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.is_active" type="checkbox" class="size-4 rounded border" />
                    Aktif dan bisa digunakan untuk booking
                </label>

                <p class="text-xs text-muted-foreground">
                    Slot per paket dibuat otomatis di dalam rentang ini. Jeda antar-slot mengikuti turnaround fasilitas. Rentang aktif tidak boleh bertabrakan atau terlalu mepet.
                </p>

                <div class="flex gap-2">
                    <Button type="submit" :disabled="form.processing || !facilities.length" class="flex-1">
                        {{ form.processing ? 'Menyimpan...' : editing ? 'Simpan perubahan' : 'Tambah jam' }}
                    </Button>
                    <Button v-if="editing" type="button" variant="outline" @click="resetForm">Batal</Button>
                </div>
            </form>

            <div class="overflow-hidden rounded-xl border bg-card">
                <div class="border-b px-5 py-4">
                    <h2 class="font-semibold">Daftar jam booking</h2>
                    <p class="mt-1 text-sm text-muted-foreground">{{ slots.length }} rentang jam terdaftar</p>
                </div>
                <div v-if="slots.length" class="divide-y">
                    <div v-for="slot in slots" :key="slot.id" class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                        <div class="min-w-0">
                            <div class="font-medium">{{ slot.facility_name }}</div>
                            <div class="mt-1 text-sm text-muted-foreground">{{ slot.start_time }} – {{ slot.end_time }}</div>
                            <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-xs" :class="slot.is_active ? 'bg-emerald-500/10 text-emerald-700' : 'bg-muted text-muted-foreground'">
                                {{ slot.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <Button type="button" size="sm" variant="outline" @click="edit(slot)">
                                <Pencil class="mr-1.5 size-3.5" /> Edit
                            </Button>
                            <Button type="button" size="sm" variant="ghost" class="text-destructive" @click="remove(slot)">
                                <Trash2 class="mr-1.5 size-3.5" /> Hapus
                            </Button>
                        </div>
                    </div>
                </div>
                <div v-else class="px-5 py-12 text-center text-sm text-muted-foreground">
                    Belum ada master jam. Tambahkan rentang jam untuk mulai menerima booking.
                </div>
            </div>
        </div>
    </div>
</template>
