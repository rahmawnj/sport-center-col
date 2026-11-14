<script setup lang="ts">
import { Form, Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Building2,
    Clock,
    Cpu,
    EllipsisVertical,
    LayoutGrid,
    Package as PackageIcon,
    Pencil,
    Plus,
    Trash2,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import FormSelect from '@/components/FormSelect.vue';
import FacilityClasses from '@/components/FacilityClasses.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type FacilityStatus = 'active' | 'maintenance' | 'inactive';
type CourtStatus = 'available' | 'maintenance' | 'inactive';
type DeviceStatus = 'active' | 'maintenance' | 'inactive';
type Device = {
    id: number;
    name: string;
    type: string | null;
    device_code: string | null;
    mqtt_topic: string | null;
    court_id: number | null;
    court_name: string | null;
    status: DeviceStatus;
};
type PackageType = 'session' | 'membership' | 'entry';
type DayType = 'weekday' | 'weekend' | 'all';
type Court = {
    id: number;
    name: string;
    amenities: string[];
    price_adjustment: number;
    status: CourtStatus;
};
type PricingRule = {
    id: number;
    day_type: DayType;
    date_start: string | null;
    date_end: string | null;
    start_time: string | null;
    end_time: string | null;
    price: number;
    priority: number;
};
type PricingRuleInput = {
    _key: number;
    day_type: DayType;
    date_start: string;
    date_end: string;
    start_time: string;
    end_time: string;
    price: string;
    priority: string;
};
type Trainer = { id: number; name: string; specialty: string | null };
type TrainerPackage = { id: number; name: string; total_sessions: number; duration_days: number; price: number; trainer_share_percentage: number; description: string | null; is_active: boolean };
type TrainerAddon = { trainer_id: number; trainer_name: string; price: number };
type Package = {
    id: number;
    name: string;
    type: PackageType;
    duration_minutes: number;
    duration_days: number | null;
    pricing_rules: PricingRule[];
    trainer_addons: TrainerAddon[];
};
type BookingDay = 'monday' | 'tuesday' | 'wednesday' | 'thursday' | 'friday' | 'saturday' | 'sunday';
type BookingTimeSlot = { id: number; day: BookingDay; start_time: string; end_time: string; price: number; is_active: boolean };
type Props = {
    facility: {
        id: number;
        name: string;
        slug: string;
        is_bookable_online: boolean;
        has_booking: boolean;
        has_session: boolean;
        has_membership: boolean;
        trainer_packages: TrainerPackage[];
        turnaround_minutes: number;
        status: FacilityStatus;
        created_at: string | null;
        courts: Court[];
        packages: Package[];
        trainers: Trainer[];
        classes: { id:number; facility_id:number; name:string; description:string|null; duration_minutes:number; default_capacity:number; is_active:boolean; schedules:{ id:number; trainer_id:number|null; trainer:string|null; date:string; start_time:string; end_time:string; capacity:number|null; effective_capacity:number; registered_count:number; price:number; status:string; participants:{id:number;name:string;status:string}[] }[] }[];
        devices: Device[];
        booking_time_slots: BookingTimeSlot[];
    };
};

const props = defineProps<Props>();

const activeTab = ref('general');
const facilityTabs = computed(() => [
    ...(props.facility.has_booking ? [{ value: 'booking', label: 'Booking' }] : []),
    ...(props.facility.has_membership ? [{ value: 'membership', label: 'Membership' }] : []),
    ...(props.facility.has_session ? [{ value: 'session', label: 'Sesi Kelas' }] : []),
    { value: 'general', label: 'Pengaturan General' },
]);

const showBookingSlotForm = ref(false);
const bookingSlotEditing = ref<BookingTimeSlot | null>(null);
const bookingSlotTitle = computed(() => bookingSlotEditing.value ? 'Edit jam booking' : 'Tambah jam booking');
const bookingSlotForm = useForm({
    day: 'monday' as BookingDay,
    start_time: '07:00',
    end_time: '12:00',
    price: '50000',
    is_active: true,
});
const bookingDays: { value: BookingDay; label: string }[] = [
    { value: 'monday', label: 'Senin' }, { value: 'tuesday', label: 'Selasa' },
    { value: 'wednesday', label: 'Rabu' }, { value: 'thursday', label: 'Kamis' },
    { value: 'friday', label: 'Jumat' }, { value: 'saturday', label: 'Sabtu' },
    { value: 'sunday', label: 'Minggu' },
];
const bookingSlotsByDay = computed(() => bookingDays.map((day) => ({
    ...day,
    slots: props.facility.booking_time_slots
        .filter((slot) => slot.day === day.value)
        .sort((a, b) => a.start_time.localeCompare(b.start_time)),
})));
function getBookingSlotSuggestion(day: BookingDay): { startTime: string | null; previousEnd: string | null } {
    const latestSlot = props.facility.booking_time_slots.filter((slot) => slot.is_active && slot.day === day).sort((a, b) => b.end_time.localeCompare(a.end_time))[0];
    if (!latestSlot) return { startTime: '07:00', previousEnd: null };
    const [hours, minutes] = latestSlot.end_time.split(':').map(Number);
    const total = hours * 60 + minutes + Math.max(0, props.facility.turnaround_minutes);
    if (total >= 1440) return { startTime: null, previousEnd: latestSlot.end_time.slice(0, 5) };
    return { startTime: String(Math.floor(total / 60)).padStart(2, '0') + ':' + String(total % 60).padStart(2, '0'), previousEnd: latestSlot.end_time.slice(0, 5) };
}
function applyBookingSlotSuggestion() { bookingSlotForm.start_time = getBookingSlotSuggestion(bookingSlotForm.day).startTime ?? ''; }
function openBookingSlotCreate(day: BookingDay = 'monday') {
    bookingSlotEditing.value = null; bookingSlotForm.day = day;
    bookingSlotForm.start_time = getBookingSlotSuggestion(day).startTime ?? '';
    bookingSlotForm.end_time = '12:00'; bookingSlotForm.price = '50000';
    bookingSlotForm.is_active = true; bookingSlotForm.clearErrors(); showBookingSlotForm.value = true;
}
function editBookingSlot(slot: BookingTimeSlot) {
    bookingSlotEditing.value = slot; showBookingSlotForm.value = true;
    bookingSlotForm.day = slot.day; bookingSlotForm.start_time = slot.start_time;
    bookingSlotForm.end_time = slot.end_time; bookingSlotForm.price = String(slot.price);
    bookingSlotForm.is_active = slot.is_active; bookingSlotForm.clearErrors();
}
function closeBookingSlotForm() {
    showBookingSlotForm.value = false;
    bookingSlotEditing.value = null;
    bookingSlotForm.clearErrors();
}
function resetBookingSlotForm() {
    showBookingSlotForm.value = false;
    bookingSlotEditing.value = null;
    bookingSlotForm.reset();
    bookingSlotForm.clearErrors();
    bookingSlotForm.day = 'monday';
    bookingSlotForm.start_time = '07:00';
    bookingSlotForm.end_time = '12:00';
    bookingSlotForm.price = '50000';
    bookingSlotForm.is_active = true;
}
function submitBookingSlot() {
    const url = `/facilities/${props.facility.id}/booking-time-slots`;
    const options = { preserveScroll: true, onSuccess: resetBookingSlotForm };
    if (bookingSlotEditing.value) {
        bookingSlotForm.put(`${url}/${bookingSlotEditing.value.id}`, options);
    } else {
        bookingSlotForm.post(url, options);
    }
}
function removeBookingSlot(slot: BookingTimeSlot) {
    if (!window.confirm(`Hapus rentang jam ${slot.start_time}–${slot.end_time}?`)) return;
    router.delete(`/facilities/${props.facility.id}/booking-time-slots/${slot.id}`, { preserveScroll: true });
}

const statusMeta: Record<
    FacilityStatus,
    { label: string; badge: string; dot: string }
> = {
    active: {
        label: 'Aktif',
        badge: 'bg-emerald-500/10 text-emerald-600',
        dot: 'bg-emerald-500',
    },
    maintenance: {
        label: 'Perawatan',
        badge: 'bg-amber-500/10 text-amber-600',
        dot: 'bg-amber-500',
    },
    inactive: {
        label: 'Tidak aktif',
        badge: 'bg-muted text-muted-foreground',
        dot: 'bg-muted-foreground/60',
    },
};

const courtStatusMeta: Record<CourtStatus, { label: string; badge: string }> = {
    available: {
        label: 'Tersedia',
        badge: 'bg-emerald-500/10 text-emerald-600',
    },
    maintenance: {
        label: 'Perawatan',
        badge: 'bg-amber-500/10 text-amber-600',
    },
    inactive: { label: 'Tidak aktif', badge: 'bg-muted text-muted-foreground' },
};

const packageTypeLabel: Record<PackageType, string> = {
    session: 'Sesi',
    membership: 'Keanggotaan',
    entry: 'Harian',
};
const typeOptions = (Object.keys(packageTypeLabel) as PackageType[]).map(
    (value) => ({ value, label: packageTypeLabel[value] }),
);

const dayTypeLabel: Record<DayType, string> = {
    all: 'Semua hari',
    weekday: 'Weekday',
    weekend: 'Weekend',
};
const dayTypeOptions = (Object.keys(dayTypeLabel) as DayType[]).map(
    (value) => ({ value, label: dayTypeLabel[value] }),
);

let ruleUid = 0;
function emptyRule(): PricingRuleInput {
    return {
        _key: ++ruleUid,
        day_type: 'all',
        date_start: '',
        date_end: '',
        start_time: '',
        end_time: '',
        price: '',
        priority: '0',
    };
}

const showForm = ref(false);
const editing = ref<Package | null>(null);
const formKey = ref(0);
const form = ref<{
    type: PackageType;
    duration_minutes: string;
    duration_days: string;
    pricing_rules: PricingRuleInput[];
    trainer_addons: { trainer_id: number; price: string }[];
}>({
    type: 'session',
    duration_minutes: '60',
    duration_days: '30',
    pricing_rules: [emptyRule()],
    trainer_addons: [],
});
const formTitle = computed(() =>
    editing.value ? 'Edit paket' : 'Tambah paket',
);
const formAction = computed(() =>
    editing.value
        ? `/packages/${editing.value.id}`
        : `/facilities/${props.facility.id}/packages`,
);
const formMethod = computed(() => (editing.value ? 'put' : 'post'));

const info = computed(() => [
    {
        key: 'has_booking',
        label: 'Booking',
        enabled: props.facility.has_booking,
        value: !props.facility.has_booking
            ? 'Tidak tersedia'
            : props.facility.is_bookable_online
              ? 'Tersedia · Online'
              : 'Tersedia · Kasir',
        disabled: false,
    },
    {
        key: 'has_membership',
        label: 'Membership',
        enabled: props.facility.has_membership,
        value: props.facility.has_membership ? 'Tersedia' : 'Tidak tersedia',
        disabled: false,
    },
    {
        key: 'has_session',
        label: 'Kelas',
        enabled: props.facility.has_session,
        value: props.facility.has_session ? 'Tersedia' : 'Tidak tersedia',
        disabled: false,
    },
    {
        key: 'status',
        label: 'Status Olahraga',
        enabled: props.facility.status === 'active',
        value: props.facility.status === 'active'
            ? 'Aktif'
            : props.facility.status === 'maintenance'
              ? 'Maintenance'
              : 'Nonaktif',
        disabled: false,
    },
]);

const updatingCapability = ref<string | null>(null);

function toggleCapability(item: (typeof info.value)[number]) {
    if (item.disabled || updatingCapability.value) return;

    updatingCapability.value = item.key;
    router.patch(
        `/facilities/${props.facility.id}/capabilities`,
        { capability: item.key, enabled: !item.enabled },
        {
            preserveScroll: true,
            onFinish: () => {
                updatingCapability.value = null;
            },
        },
    );
}

function openCreate() {
    editing.value = null;
    form.value = {
        type: 'session',
        duration_minutes: '60',
        duration_days: '30',
        pricing_rules: [emptyRule()],
        trainer_addons: [],
    };
    formKey.value++;
    showForm.value = true;
}

function openEdit(pkg: Package) {
    editing.value = pkg;
    form.value = {
        type: pkg.type,
        duration_minutes: String(pkg.duration_minutes ?? 60),
        duration_days: pkg.duration_days ? String(pkg.duration_days) : '',
        trainer_addons: pkg.trainer_addons.map((addon) => ({ trainer_id: addon.trainer_id, price: String(addon.price) })),
        pricing_rules: pkg.pricing_rules.length
            ? pkg.pricing_rules.map((rule) => ({
                  _key: ++ruleUid,
                  day_type: rule.day_type,
                  date_start: rule.date_start ?? '',
                  date_end: rule.date_end ?? '',
                  start_time: rule.start_time ?? '',
                  end_time: rule.end_time ?? '',
                  price: String(rule.price),
                  priority: String(rule.priority ?? 0),
              }))
            : [emptyRule()],
    };
    formKey.value++;
    showForm.value = true;
}

function handleTrainerAddonToggle(trainerId: number, event: Event) {
    toggleTrainerAddon(trainerId, (event.target as HTMLInputElement).checked);
}

function updateTrainerAddonPrice(trainerId: number, event: Event) {
    const addon = form.value.trainer_addons.find((item) => item.trainer_id === trainerId);
    if (addon) {
        addon.price = (event.target as HTMLInputElement).value;
    }
}

function toggleTrainerAddon(trainerId: number, checked: boolean) {
    if (checked) {
        if (!form.value.trainer_addons.some((addon) => addon.trainer_id === trainerId)) {
            form.value.trainer_addons.push({ trainer_id: trainerId, price: '0' });
        }
        return;
    }

    form.value.trainer_addons = form.value.trainer_addons.filter((addon) => addon.trainer_id !== trainerId);
}

function addRule() {
    form.value.pricing_rules.push(emptyRule());
}

function removeRule(index: number) {
    form.value.pricing_rules.splice(index, 1);
}

function closeForm() {
    showForm.value = false;
    editing.value = null;
}

function removePackage(pkg: Package) {
    if (!window.confirm(`Hapus paket ${pkg.name}?`)) {
        return;
    }

    router.delete(`/packages/${pkg.id}`, { preserveScroll: true });
}


const showTrainerPackageForm = ref(false);
const editingTrainerPackage = ref<TrainerPackage | null>(null);
const trainerPackageForm = useForm({
    name: '',
    facility_id: String(props.facility.id),
    total_sessions: 5,
    duration_days: 30,
    price: '',
    trainer_share_percentage: 0,
    description: '',
    is_active: true,
});
const trainerPackageTitle = computed(() => editingTrainerPackage.value ? 'Edit paket trainer' : 'Tambah paket trainer');

function openTrainerPackageCreate() {
    editingTrainerPackage.value = null;
    trainerPackageForm.reset();
    trainerPackageForm.clearErrors();
    trainerPackageForm.name = '';
    trainerPackageForm.facility_id = String(props.facility.id);
    trainerPackageForm.total_sessions = 5;
    trainerPackageForm.duration_days = 30;
    trainerPackageForm.price = '';
    trainerPackageForm.trainer_share_percentage = 0;
    trainerPackageForm.description = '';
    trainerPackageForm.is_active = true;
    showTrainerPackageForm.value = true;
}
function openTrainerPackageEdit(item: TrainerPackage) {
    editingTrainerPackage.value = item;
    trainerPackageForm.clearErrors();
    trainerPackageForm.name = item.name;
    trainerPackageForm.facility_id = String(props.facility.id);
    trainerPackageForm.total_sessions = item.total_sessions;
    trainerPackageForm.duration_days = item.duration_days;
    trainerPackageForm.price = String(item.price);
    trainerPackageForm.trainer_share_percentage = item.trainer_share_percentage;
    trainerPackageForm.description = item.description ?? '';
    trainerPackageForm.is_active = item.is_active;
    showTrainerPackageForm.value = true;
}
function closeTrainerPackageForm() {
    showTrainerPackageForm.value = false;
    editingTrainerPackage.value = null;
    trainerPackageForm.clearErrors();
}
function saveTrainerPackage() {
    const options = { preserveScroll: true, onSuccess: closeTrainerPackageForm };
    if (editingTrainerPackage.value) {
        trainerPackageForm.put('/trainer-packages/' + editingTrainerPackage.value.id, options);
    } else {
        trainerPackageForm.post('/trainer-packages', options);
    }
}
function removeTrainerPackage(item: TrainerPackage) {
    if (window.confirm('Hapus paket trainer ' + item.name + '?')) {
        router.delete('/trainer-packages/' + item.id, { preserveScroll: true });
    }
}
function formatTrainerPackagePrice(value: number) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
}

const showCourtForm = ref(false);
const editingCourt = ref<Court | null>(null);
const courtFormKey = ref(0);
const courtForm = ref({ status: 'available' });
const courtStatusOptions = [
    { value: 'available', label: 'Tersedia' },
    { value: 'maintenance', label: 'Perawatan' },
    { value: 'inactive', label: 'Tidak aktif' },
];
const courtFormTitle = computed(() =>
    editingCourt.value ? 'Edit lapangan' : 'Tambah lapangan',
);
const courtFormAction = computed(() =>
    editingCourt.value
        ? `/courts/${editingCourt.value.id}`
        : `/facilities/${props.facility.id}/courts`,
);
const courtFormMethod = computed(() => (editingCourt.value ? 'put' : 'post'));

function openCourtCreate() {
    editingCourt.value = null;
    courtForm.value = { status: 'available' };
    courtFormKey.value++;
    showCourtForm.value = true;
}

function openCourtEdit(court: Court) {
    editingCourt.value = court;
    courtForm.value = { status: court.status };
    courtFormKey.value++;
    showCourtForm.value = true;
}

function closeCourtForm() {
    showCourtForm.value = false;
    editingCourt.value = null;
}

function removeCourt(court: Court) {
    if (!window.confirm(`Hapus lapangan ${court.name}?`)) {
        return;
    }

    router.delete(`/courts/${court.id}`, { preserveScroll: true });
}

const showDeviceForm = ref(false);
const editingDevice = ref<Device | null>(null);
const deviceFormKey = ref(0);
const deviceForm = ref({ status: 'active', court_id: 'none' });
const deviceCourtOptions = computed(() => [
    { value: 'none', label: 'Seluruh olahraga (tanpa lapangan)' },
    ...props.facility.courts.map((court) => ({
        value: String(court.id),
        label: court.name,
    })),
]);
const deviceStatusOptions = [
    { value: 'active', label: 'Aktif' },
    { value: 'maintenance', label: 'Perawatan' },
    { value: 'inactive', label: 'Tidak aktif' },
];
const deviceStatusMeta: Record<
    DeviceStatus,
    { label: string; badge: string; dot: string }
> = {
    active: {
        label: 'Aktif',
        badge: 'bg-emerald-500/10 text-emerald-600',
        dot: 'bg-emerald-500',
    },
    maintenance: {
        label: 'Perawatan',
        badge: 'bg-amber-500/10 text-amber-600',
        dot: 'bg-amber-500',
    },
    inactive: {
        label: 'Tidak aktif',
        badge: 'bg-muted text-muted-foreground',
        dot: 'bg-muted-foreground/60',
    },
};
const deviceFormTitle = computed(() =>
    editingDevice.value ? 'Edit perangkat' : 'Tambah perangkat',
);
const deviceFormAction = computed(() =>
    editingDevice.value
        ? `/devices/${editingDevice.value.id}`
        : `/facilities/${props.facility.id}/devices`,
);
const deviceFormMethod = computed(() => (editingDevice.value ? 'put' : 'post'));

function openDeviceCreate() {
    editingDevice.value = null;
    deviceForm.value = { status: 'active', court_id: 'none' };
    deviceFormKey.value++;
    showDeviceForm.value = true;
}

function openDeviceEdit(device: Device) {
    editingDevice.value = device;
    deviceForm.value = {
        status: device.status,
        court_id: device.court_id ? String(device.court_id) : 'none',
    };
    deviceFormKey.value++;
    showDeviceForm.value = true;
}

function closeDeviceForm() {
    showDeviceForm.value = false;
    editingDevice.value = null;
}

function removeDevice(device: Device) {
    if (!window.confirm(`Hapus perangkat ${device.name}?`)) {
        return;
    }

    router.delete(`/devices/${device.id}`, { preserveScroll: true });
}

function formatDuration(minutes: number) {
    return minutes % 60 === 0 ? `${minutes / 60} jam` : `${minutes} menit`;
}

function formatDate(value: string | null) {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(new Date(value));
}

function formatIDR(value: number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}

function ruleTiming(rule: PricingRule) {
    const time =
        rule.start_time && rule.end_time
            ? `${rule.start_time}–${rule.end_time}`
            : '';

    return [dayTypeLabel[rule.day_type], time].filter(Boolean).join(' · ');
}

function promoRange(rule: PricingRule) {
    if (!rule.date_start && !rule.date_end) {
        return '';
    }

    return `Promo: ${rule.date_start ?? '…'} s/d ${rule.date_end ?? '…'}`;
}
</script>

<template>
    <Head :title="`Olahraga · ${props.facility.name}`" />
    <div class="space-y-6 p-4 md:p-6">
        <Link
            href="/facilities"
            class="inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
        >
            <ArrowLeft class="size-4" />
            Kembali ke daftar olahraga
        </Link>

        <PageHeader
            eyebrow="Detail olahraga"
            :title="props.facility.name"
            :description="`/${props.facility.slug}`"
        >
            <span
                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                :class="statusMeta[props.facility.status].badge"
            >
                <span
                    class="size-1.5 rounded-full"
                    :class="statusMeta[props.facility.status].dot"
                />
                {{ statusMeta[props.facility.status].label }}
            </span>
        </PageHeader>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div
                v-for="item in info"
                :key="item.key"
                class="flex flex-col justify-between gap-4 rounded-2xl border bg-card p-4 shadow-sm"
            >
                <div>
                    <div class="text-sm text-muted-foreground">{{ item.label }}</div>
                    <div class="mt-2 flex items-center gap-2">
                        <span
                            class="size-2 rounded-full"
                            :class="item.enabled ? 'bg-emerald-500' : 'bg-muted-foreground/40'"
                        />
                        <span class="font-medium">{{ item.value }}</span>
                    </div>
                </div>
                <Button
                    size="sm"
                    variant="outline"
                    class="w-full"
                    :disabled="item.disabled || updatingCapability !== null"
                    @click="toggleCapability(item)"
                >
                    {{ updatingCapability === item.key ? 'Menyimpan...' : item.enabled ? 'Nonaktifkan' : 'Aktifkan' }}
                </Button>
            </div>
        </div>




        <div class="flex flex-wrap gap-2 border-b pb-3" role="tablist" aria-label="Bagian pengaturan olahraga">
            <button
                v-for="tab in facilityTabs"
                :key="tab.value"
                type="button"
                role="tab"
                :aria-selected="activeTab === tab.value"
                @click="activeTab = tab.value"
                class="rounded-lg px-4 py-2 text-sm font-medium transition-colors"
                :class="activeTab === tab.value
                    ? 'bg-primary text-primary-foreground shadow-sm'
                    : 'bg-muted text-muted-foreground hover:bg-muted/80 hover:text-foreground'"
            >
                {{ tab.label }}
            </button>
        </div>

        <section v-if="props.facility.has_booking && activeTab === 'booking'" class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <Clock class="size-4 text-primary" />
                    <h2 class="font-semibold">Master Jam Booking</h2>
                    <span class="text-sm text-muted-foreground">({{ props.facility.booking_time_slots.length }})</span>
                </div>
                <Button size="sm" @click="openBookingSlotCreate">
                    <Plus class="mr-2 size-4" />
                    Tambah jam
                </Button>
            </div>
            <p class="text-sm text-muted-foreground">
                Jam operasional untuk booking online dan kasir. Jeda antar-slot mengikuti pengaturan fasilitas ({{ props.facility.turnaround_minutes }} menit).
            </p>
            <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <div v-for="(day, index) in bookingSlotsByDay" :key="day.value" class="border-b last:border-b-0">
                    <div class="flex items-center justify-between gap-3 bg-muted/30 px-4 py-3">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold">{{ day.label }}</span>
                            <span class="rounded-full bg-background px-2 py-0.5 text-xs text-muted-foreground">{{ day.slots.length }} rentang</span>
                        </div>
                        <Button size="sm" variant="outline" @click="openBookingSlotCreate(day.value)">
                            <Plus class="mr-1 size-3.5" />Tambah
                        </Button>
                    </div>
                    <div v-if="day.slots.length" class="divide-y">
                        <div v-for="slot in day.slots" :key="slot.id" class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                            <div class="flex min-w-0 flex-1 flex-wrap items-center gap-x-6 gap-y-2">
                                <div class="min-w-[130px]">
                                    <div class="text-xs text-muted-foreground">Jam operasional</div>
                                    <div class="font-medium tabular-nums">{{ slot.start_time }} – {{ slot.end_time }}</div>
                                </div>
                                <div class="min-w-[130px]">
                                    <div class="text-xs text-muted-foreground">Harga per jam</div>
                                    <div class="font-semibold tabular-nums">{{ formatIDR(slot.price) }}</div>
                                </div>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="slot.is_active ? 'bg-emerald-500/10 text-emerald-600' : 'bg-muted text-muted-foreground'">
                                    {{ slot.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="icon"><EllipsisVertical class="size-4" /></Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem @click="editBookingSlot(slot)"><Pencil class="mr-2 size-4" />Edit</DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem class="text-destructive focus:text-destructive" @click="removeBookingSlot(slot)"><Trash2 class="mr-2 size-4" />Hapus</DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                    <div v-else class="px-4 py-4 text-sm text-muted-foreground">
                        Belum ada jam operasional untuk hari {{ day.label }}.
                    </div>
                </div>
            </div>
        </section>

        



        <section v-if="props.facility.has_membership && activeTab === 'membership'" class="space-y-5">
            <div class="border-b pb-3">
                <h2 class="text-lg font-semibold">Membership</h2>
                <p class="text-sm text-muted-foreground">Pengaturan paket keanggotaan dan paket personal trainer untuk olahraga ini.</p>
            </div>
        <section class="space-y-3">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <PackageIcon class="size-4 text-primary" />
                    <h2 class="font-semibold">Paket</h2>
                    <span class="text-sm text-muted-foreground"
                        >({{ props.facility.packages.length }})</span
                    >
                </div>
                <Button size="sm" @click="openCreate">
                    <Plus class="mr-2 size-4" />
                    Tambah paket
                </Button>
            </div>

            <div
                v-if="props.facility.packages.length"
                class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3"
            >
                <div
                    v-for="pkg in props.facility.packages"
                    :key="pkg.id"
                    class="flex flex-col gap-3 rounded-2xl border bg-card p-4 shadow-sm transition-shadow hover:shadow-md"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                            >
                                <PackageIcon class="size-5" />
                            </div>
                            <div class="min-w-0">
                                <div class="truncate font-medium">
                                    {{ pkg.name }}
                                </div>
                                <div
                                    class="mt-0.5 flex items-center gap-1.5 text-xs text-muted-foreground"
                                >
                                    <span>{{
                                        packageTypeLabel[pkg.type]
                                    }}</span>
                                    <template v-if="pkg.duration_minutes > 0">
                                        <span class="text-muted-foreground/40"
                                            >·</span
                                        >
                                        <Clock class="size-3" />
                                        <span>{{
                                            formatDuration(pkg.duration_minutes)
                                        }}</span>
                                    </template>

                                    <template
                                        v-if="
                                            pkg.type === 'membership' &&
                                            pkg.duration_days
                                        "
                                    >
                                        <span class="text-muted-foreground/40"
                                            >·</span
                                        >
                                        <Clock class="size-3" />
                                        <span
                                            >{{ pkg.duration_days }} hari</span
                                        >
                                    </template>
                                </div>
                            </div>
                        </div>
                        <DropdownMenu>
                            <DropdownMenuTrigger :as-child="true">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    title="Aksi"
                                    class="hover:bg-primary/10 hover:text-primary"
                                >
                                    <EllipsisVertical class="size-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-40">
                                <DropdownMenuItem @click="openEdit(pkg)">
                                    <Pencil class="size-4" />
                                    Edit
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    variant="destructive"
                                    @click="removePackage(pkg)"
                                >
                                    <Trash2 class="size-4" />
                                    Hapus
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <div v-if="pkg.trainer_addons.length" class="space-y-1.5 rounded-xl bg-primary/5 p-3">
                        <div class="text-xs font-semibold text-primary">Trainer Add-on</div>
                        <div v-for="addon in pkg.trainer_addons" :key="addon.trainer_id" class="flex items-center justify-between gap-2 text-sm">
                            <span>{{ addon.trainer_name }}</span>
                            <span class="font-medium">{{ formatIDR(addon.price) }}</span>
                        </div>
                    </div>
                    <div class="mt-auto space-y-1.5 border-t pt-3">
                        <div class="text-xs text-muted-foreground">
                            Aturan harga
                        </div>
                        <template v-if="pkg.pricing_rules.length">
                            <div
                                v-for="rule in pkg.pricing_rules"
                                :key="rule.id"
                                class="flex items-center justify-between gap-2 text-sm"
                            >
                                <span
                                    class="flex items-center gap-1.5 text-muted-foreground"
                                    :title="promoRange(rule)"
                                >
                                    {{ ruleTiming(rule) }}
                                    <span
                                        v-if="rule.date_start || rule.date_end"
                                        class="inline-flex items-center rounded-full bg-amber-500/10 px-2 py-0.5 text-[10px] font-semibold text-amber-600"
                                    >
                                        Promo
                                    </span>
                                </span>
                                <span class="font-medium">
                                    {{ formatIDR(rule.price) }}
                                </span>
                            </div>
                        </template>
                        <p v-else class="text-xs text-muted-foreground">
                            Belum ada harga.
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="rounded-2xl border bg-card p-8 text-center text-sm text-muted-foreground shadow-sm"
            >
                Belum ada paket pada olahraga ini.
            </div>
        </section>
        <section v-if="props.facility.has_membership" class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <PackageIcon class="size-4 text-primary" />
                    <div>
                        <h2 class="font-semibold">Paket Personal Trainer</h2>
                        <p class="text-xs text-muted-foreground">Paket latihan khusus untuk olahraga {{ props.facility.name }}.</p>
                    </div>
                    <span class="text-sm text-muted-foreground">({{ props.facility.trainer_packages.length }})</span>
                </div>
                <Button size="sm" @click="openTrainerPackageCreate"><Plus class="mr-2 size-4" /> Tambah paket trainer</Button>
            </div>
            <div v-if="props.facility.trainer_packages.length" class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <article v-for="item in props.facility.trainer_packages" :key="item.id" class="rounded-2xl border bg-card p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="font-semibold">{{ item.name }}</h3>
                            <p class="mt-1 text-xs text-muted-foreground">{{ item.total_sessions }} sesi · {{ item.duration_days }} hari</p>
                        </div>
                        <DropdownMenu>
                            <DropdownMenuTrigger :as-child="true"><Button variant="ghost" size="icon" title="Aksi"><EllipsisVertical class="size-4" /></Button></DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem @click="openTrainerPackageEdit(item)"><Pencil class="size-4" /> Edit</DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem variant="destructive" @click="removeTrainerPackage(item)"><Trash2 class="size-4" /> Hapus</DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                    <p class="mt-3 text-xl font-bold">{{ formatTrainerPackagePrice(item.price) }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">Bagian trainer: {{ Number(item.trainer_share_percentage).toLocaleString('id-ID') }}%</p>
                    <p v-if="item.description" class="mt-3 text-sm text-muted-foreground">{{ item.description }}</p>
                    <span class="mt-3 inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="item.is_active ? 'bg-emerald-500/10 text-emerald-600' : 'bg-muted text-muted-foreground'">{{ item.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </article>
            </div>
            <div v-else class="rounded-2xl border border-dashed p-8 text-center text-sm text-muted-foreground">Belum ada paket personal trainer untuk olahraga ini.</div>
        </section>
        </section>

        <section v-if="props.facility.has_session && activeTab === 'session'" class="space-y-3">
            <div class="border-b pb-3">
                <h2 class="text-lg font-semibold">Sesi Kelas</h2>
                <p class="text-sm text-muted-foreground">Pengaturan kelas dan jadwal sesi olahraga.</p>
            </div>
            <FacilityClasses :facility-id="props.facility.id" :facility-name="props.facility.name" :classes="props.facility.classes" :trainers="props.facility.trainers" />
        </section>

        <section v-if="activeTab === 'general'" class="space-y-6">
        <section class="space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <Cpu class="size-4 text-primary" />
                            <h2 class="font-semibold">Perangkat</h2>
                            <span class="text-sm text-muted-foreground"
                                >({{ props.facility.devices.length }})</span
                            >
                        </div>
                        <Button size="sm" @click="openDeviceCreate">
                            <Plus class="mr-2 size-4" />
                            Tambah perangkat
                        </Button>
                    </div>
        
                    <div
                        v-if="props.facility.devices.length"
                        class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3"
                    >
                        <div
                            v-for="device in props.facility.devices"
                            :key="device.id"
                            class="flex items-center justify-between gap-3 rounded-2xl border bg-card p-4 shadow-sm"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <div
                                    class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                                >
                                    <Cpu class="size-4" />
                                </div>
                                <div class="min-w-0">
                                    <div class="truncate font-medium">
                                        {{ device.name }}
                                    </div>
                                    <div
                                        class="truncate font-mono text-xs text-muted-foreground"
                                    >
                                        {{ device.device_code || device.type || '—' }}
                                    </div>
                                    <div class="truncate text-xs text-muted-foreground">
                                        {{ device.court_name || 'Semua lapangan' }}
                                    </div>
                                    <div
                                        v-if="device.mqtt_topic"
                                        class="truncate font-mono text-xs text-sky-600"
                                    >
                                        {{ device.mqtt_topic }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="deviceStatusMeta[device.status].badge"
                                >
                                    <span
                                        class="size-1.5 rounded-full"
                                        :class="deviceStatusMeta[device.status].dot"
                                    />
                                    {{ deviceStatusMeta[device.status].label }}
                                </span>
                                <DropdownMenu>
                                    <DropdownMenuTrigger :as-child="true">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            title="Aksi"
                                            class="hover:bg-primary/10 hover:text-primary"
                                        >
                                            <EllipsisVertical class="size-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="w-40">
                                        <DropdownMenuItem
                                            @click="openDeviceEdit(device)"
                                        >
                                            <Pencil class="size-4" />
                                            Edit
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            variant="destructive"
                                            @click="removeDevice(device)"
                                        >
                                            <Trash2 class="size-4" />
                                            Hapus
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    </div>
        
                    <div
                        v-else
                        class="rounded-2xl border bg-card p-8 text-center text-sm text-muted-foreground shadow-sm"
                    >
                        Belum ada perangkat pada olahraga ini.
                    </div>
                </section>

        <section class="space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <LayoutGrid class="size-4 text-primary" />
                            <h2 class="font-semibold">Lapangan</h2>
                            <span class="text-sm text-muted-foreground"
                                >({{ props.facility.courts.length }})</span
                            >
                        </div>
                        <Button size="sm" @click="openCourtCreate">
                            <Plus class="mr-2 size-4" />
                            Tambah lapangan
                        </Button>
                    </div>
        
                    <div
                        v-if="props.facility.courts.length"
                        class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3"
                    >
                        <div
                            v-for="court in props.facility.courts"
                            :key="court.id"
                            class="flex items-center justify-between gap-3 rounded-2xl border bg-card p-4 shadow-sm"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <div
                                    class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-xs font-semibold text-primary"
                                >
                                    <Building2 class="size-4" />
                                </div>
                                <div class="min-w-0">
                                    <span class="block truncate font-medium">{{
                                        court.name
                                    }}</span>
                                    <span
                                        v-if="court.amenities?.length"
                                        class="mt-0.5 block truncate text-xs text-muted-foreground"
                                        >{{ court.amenities.join(' · ') }}</span
                                    >
                                    <span
                                        v-else
                                        class="mt-0.5 block text-xs text-muted-foreground"
                                        >Tanpa fasilitas</span
                                    >
                                    <span
                                        v-if="court.price_adjustment"
                                        class="mt-0.5 block text-xs font-medium text-emerald-700"
                                        >+{{ formatIDR(court.price_adjustment) }}</span
                                    >
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="courtStatusMeta[court.status].badge"
                                >
                                    {{ courtStatusMeta[court.status].label }}
                                </span>
                                <DropdownMenu>
                                    <DropdownMenuTrigger :as-child="true">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            title="Aksi"
                                            class="hover:bg-primary/10 hover:text-primary"
                                        >
                                            <EllipsisVertical class="size-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="w-40">
                                        <DropdownMenuItem @click="openCourtEdit(court)">
                                            <Pencil class="size-4" />
                                            Edit
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            variant="destructive"
                                            @click="removeCourt(court)"
                                        >
                                            <Trash2 class="size-4" />
                                            Hapus
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    </div>
        
                    <div
                        v-else
                        class="rounded-2xl border bg-card p-8 text-center text-sm text-muted-foreground shadow-sm"
                    >
                        Belum ada lapangan pada olahraga ini.
                    </div>
                </section>
        </section>
    </div>

    <div
        v-if="showForm"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closeForm"
    >
        <div
            class="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-2xl border bg-background shadow-2xl"
        >
            <div class="flex items-start justify-between border-b p-5">
                <div>
                    <p class="text-sm font-medium text-primary">
                        Manajemen paket
                    </p>
                    <h2 class="mt-1 text-xl font-semibold">{{ formTitle }}</h2>
                </div>
                <Button variant="ghost" size="icon" @click="closeForm"
                    ><X class="size-4"
                /></Button>
            </div>
            <Form
                :key="formKey"
                :action="formAction"
                :method="formMethod"
                class="space-y-5 p-5"
                @success="closeForm"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="package-name">Nama paket</Label>
                        <Input
                            id="package-name"
                            name="name"
                            :default-value="editing?.name"
                            required
                            placeholder="e.g. Paket Bulanan"
                        />
                        <p v-if="errors.name" class="text-xs text-destructive">
                            {{ errors.name }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="package-type">Tipe</Label>
                        <FormSelect
                            id="package-type"
                            v-model="form.type"
                            name="type"
                            :options="typeOptions"
                        />
                        <p v-if="errors.type" class="text-xs text-destructive">
                            {{ errors.type }}
                        </p>
                    </div>
                    <div v-if="form.type === 'session'" class="grid gap-2">
                        <Label for="package-duration">Durasi (menit)</Label>
                        <Input
                            id="package-duration"
                            type="number"
                            name="duration_minutes"
                            :default-value="editing?.duration_minutes ?? 60"
                            required
                            placeholder="60"
                        />
                        <p
                            v-if="errors.duration_minutes"
                            class="text-xs text-destructive"
                        >
                            {{ errors.duration_minutes }}
                        </p>
                    </div>
                    <div v-if="form.type === 'membership'" class="grid gap-2">
                        <Label for="package-duration-days"
                            >Masa aktif (hari)</Label
                        >
                        <Input
                            id="package-duration-days"
                            type="number"
                            min="1"
                            name="duration_days"
                            :default-value="editing?.duration_days ?? 30"
                            required
                            placeholder="30"
                        />
                        <p
                            v-if="errors.duration_days"
                            class="text-xs text-destructive"
                        >
                            {{ errors.duration_days }}
                        </p>
                    </div>
                    <div v-if="form.type === 'session'" class="grid gap-3 sm:col-span-2 rounded-xl border p-4">
                        <div>
                            <Label>Trainer Add-on (opsional)</Label>
                            <p class="mt-1 text-xs text-muted-foreground">Pilih pelatih yang dapat ditambahkan saat booking. Harga berlaku satu kali per booking, bukan per jam.</p>
                        </div>
                        <div v-if="props.facility.trainers.length" class="grid gap-3 sm:grid-cols-2">
                            <div v-for="trainer in props.facility.trainers" :key="trainer.id" class="rounded-lg border p-3">
                                <label class="flex items-center gap-2 text-sm font-medium">
                                    <input
                                        type="checkbox"
                                        :checked="form.trainer_addons.some((addon) => addon.trainer_id === trainer.id)"
                                        @change="handleTrainerAddonToggle(trainer.id, $event)"
                                    />
                                    {{ trainer.name }}
                                    <span v-if="trainer.specialty" class="text-xs font-normal text-muted-foreground">· {{ trainer.specialty }}</span>
                                </label>
                                <div v-if="form.trainer_addons.some((addon) => addon.trainer_id === trainer.id)" class="mt-2 grid gap-1">
                                    <Label :for="`trainer-addon-price-${trainer.id}`" class="text-xs">Biaya add-on per booking</Label>
                                    <input
                                        :id="`trainer-addon-price-${trainer.id}`"
                                        type="number"
                                        min="0"
                                        step="1000"
                                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                        :name="`trainer_addons[${form.trainer_addons.findIndex((addon) => addon.trainer_id === trainer.id)}][price]`"
                                        :value="form.trainer_addons.find((addon) => addon.trainer_id === trainer.id)?.price ?? '0'"
                                        @input="updateTrainerAddonPrice(trainer.id, $event)"
                                    />
                                    <input
                                        type="hidden"
                                        :name="`trainer_addons[${form.trainer_addons.findIndex((addon) => addon.trainer_id === trainer.id)}][trainer_id]`"
                                        :value="trainer.id"
                                    />
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-xs text-muted-foreground">Belum ada trainer yang terhubung dengan olahraga ini. Hubungkan trainer melalui menu Trainer terlebih dahulu.</p>
                        <template v-for="(msg, key) in errors" :key="key">
                            <p v-if="String(key).startsWith('trainer_addons')" class="text-xs text-destructive">{{ msg }}</p>
                        </template>
                    </div>
                    <div class="grid gap-2 sm:col-span-2">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <Label>Aturan harga</Label>
                                <p class="text-[11px] text-muted-foreground">
                                    Isi rentang tanggal + prioritas untuk promo
                                    (menimpa harga normal saat aktif).
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="addRule"
                            >
                                <Plus class="mr-1.5 size-3.5" />
                                Tambah harga
                            </Button>
                        </div>
                        <div class="space-y-2">
                            <div
                                v-for="(rule, index) in form.pricing_rules"
                                :key="rule._key"
                                class="space-y-2 rounded-lg border p-2.5"
                            >
                                <div class="grid grid-cols-12 items-end gap-2">
                                    <div
                                        class="col-span-12 grid gap-1 sm:col-span-3"
                                    >
                                        <Label class="text-xs">Hari</Label>
                                        <FormSelect
                                            :id="`rule-day-${index}`"
                                            v-model="rule.day_type"
                                            :name="`pricing_rules[${index}][day_type]`"
                                            :options="dayTypeOptions"
                                        />
                                    </div>
                                    <div
                                        class="col-span-6 grid gap-1 sm:col-span-3"
                                    >
                                        <Label class="text-xs">Mulai</Label>
                                        <Input
                                            type="time"
                                            v-model="rule.start_time"
                                            :name="`pricing_rules[${index}][start_time]`"
                                        />
                                    </div>
                                    <div
                                        class="col-span-6 grid gap-1 sm:col-span-3"
                                    >
                                        <Label class="text-xs">Selesai</Label>
                                        <Input
                                            type="time"
                                            v-model="rule.end_time"
                                            :name="`pricing_rules[${index}][end_time]`"
                                        />
                                    </div>
                                    <div
                                        class="col-span-10 grid gap-1 sm:col-span-2"
                                    >
                                        <Label class="text-xs">Harga</Label>
                                        <Input
                                            type="number"
                                            min="0"
                                            step="1000"
                                            v-model="rule.price"
                                            :name="`pricing_rules[${index}][price]`"
                                            placeholder="0"
                                        />
                                    </div>
                                    <div
                                        class="col-span-2 flex justify-end sm:col-span-1"
                                    >
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="text-destructive hover:bg-destructive/10"
                                            :disabled="
                                                form.pricing_rules.length === 1
                                            "
                                            @click="removeRule(index)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </div>
                                <div
                                    class="grid grid-cols-12 items-end gap-2 border-t pt-2"
                                >
                                    <div
                                        class="col-span-6 grid gap-1 sm:col-span-4"
                                    >
                                        <Label class="text-xs">
                                            Promo mulai (opsional)
                                        </Label>
                                        <Input
                                            type="date"
                                            v-model="rule.date_start"
                                            :name="`pricing_rules[${index}][date_start]`"
                                        />
                                    </div>
                                    <div
                                        class="col-span-6 grid gap-1 sm:col-span-4"
                                    >
                                        <Label class="text-xs">
                                            Promo sampai (opsional)
                                        </Label>
                                        <Input
                                            type="date"
                                            v-model="rule.date_end"
                                            :name="`pricing_rules[${index}][date_end]`"
                                        />
                                    </div>
                                    <div
                                        class="col-span-6 grid gap-1 sm:col-span-2"
                                    >
                                        <Label class="text-xs">Prioritas</Label>
                                        <Input
                                            type="number"
                                            v-model="rule.priority"
                                            :name="`pricing_rules[${index}][priority]`"
                                            placeholder="0"
                                        />
                                    </div>
                                    <p
                                        class="col-span-6 text-[11px] leading-tight text-muted-foreground sm:col-span-2"
                                    >
                                        Prioritas lebih tinggi akan dipakai saat
                                        jam &amp; tanggalnya sama.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <template v-for="(msg, key) in errors" :key="key">
                            <p
                                v-if="String(key).startsWith('pricing_rules')"
                                class="text-xs text-destructive"
                            >
                                {{ msg }}
                            </p>
                        </template>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t pt-4">
                    <Button type="button" variant="ghost" @click="closeForm"
                        >Batal</Button
                    >
                    <Button type="submit" :disabled="processing">
                        {{
                            processing
                                ? 'Menyimpan...'
                                : editing
                                  ? 'Simpan perubahan'
                                  : 'Tambah paket'
                        }}
                    </Button>
                </div>
            </Form>
        </div>
    </div>

    <div
        v-if="showBookingSlotForm"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closeBookingSlotForm"
    >
        <div class="w-full max-w-md overflow-hidden rounded-2xl border bg-background shadow-2xl">
            <div class="flex items-start justify-between border-b p-5">
                <div>
                    <p class="text-sm font-medium text-primary">Master jam booking</p>
                    <h2 class="mt-1 text-xl font-semibold">{{ bookingSlotTitle }}</h2>
                </div>
                <Button variant="ghost" size="icon" @click="closeBookingSlotForm"><X class="size-4" /></Button>
            </div>
            <form class="space-y-5 p-5" @submit.prevent="submitBookingSlot">
                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="booking-day">Hari</Label>
                        <select id="booking-day" v-model="bookingSlotForm.day" @change="applyBookingSlotSuggestion" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                            <option v-for="day in bookingDays" :key="day.value" :value="day.value">{{ day.label }}</option>
                        </select>
                        <p v-if="bookingSlotForm.errors.day" class="text-xs text-destructive">{{ bookingSlotForm.errors.day }}</p>
                        <p class="text-xs text-muted-foreground">
                            <template v-if="getBookingSlotSuggestion(bookingSlotForm.day).previousEnd">
                                Saran otomatis: {{ getBookingSlotSuggestion(bookingSlotForm.day).startTime ?? 'Tidak tersedia' }} ({{ getBookingSlotSuggestion(bookingSlotForm.day).previousEnd }} + jeda {{ props.facility.turnaround_minutes }} menit).
                            </template>
                            <template v-else>Belum ada rentang aktif untuk hari ini. Saran awal: 07:00.</template>
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="booking-start">Jam mulai</Label>
                        <Input
                            id="booking-start"
                            v-model="bookingSlotForm.start_time"
                            type="text"
                            inputmode="numeric"
                            maxlength="5"
                            pattern="([01][0-9]|2[0-3]):[0-5][0-9]"
                            placeholder="07:00"
                            autocomplete="off"
                            required
                        />
                        <p v-if="bookingSlotForm.errors.start_time" class="text-xs text-destructive">{{ bookingSlotForm.errors.start_time }}</p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="booking-price">Harga per jam (Rp)</Label>
                        <Input id="booking-price" v-model="bookingSlotForm.price" type="number" min="0" step="1000" placeholder="50000" required />
                        <p v-if="bookingSlotForm.errors.price" class="text-xs text-destructive">{{ bookingSlotForm.errors.price }}</p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="booking-end">Jam selesai</Label>
                        <Input
                            id="booking-end"
                            v-model="bookingSlotForm.end_time"
                            type="text"
                            inputmode="numeric"
                            maxlength="5"
                            pattern="([01][0-9]|2[0-3]):[0-5][0-9]"
                            placeholder="12:00"
                            autocomplete="off"
                            required
                        />
                        <p v-if="bookingSlotForm.errors.end_time" class="text-xs text-destructive">{{ bookingSlotForm.errors.end_time }}</p>
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="bookingSlotForm.is_active" type="checkbox" class="size-4 rounded border" />
                        Aktif
                    </label>
                    <p class="text-xs text-muted-foreground">
                        Slot dibuat otomatis berdasarkan durasi paket. Jeda minimal antar-rentang mengikuti pengaturan fasilitas: {{ props.facility.turnaround_minutes }} menit.
                    </p>
                </div>
                <div class="flex justify-end gap-2 border-t pt-4">
                    <Button type="button" variant="ghost" @click="closeBookingSlotForm">Batal</Button>
                    <Button type="submit" :disabled="bookingSlotForm.processing">
                        {{ bookingSlotForm.processing ? 'Menyimpan...' : bookingSlotEditing ? 'Simpan perubahan' : 'Tambah jam' }}
                    </Button>
                </div>
            </form>
        </div>
    </div>

    <div
        v-if="showCourtForm"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closeCourtForm"
    >
        <div
            class="w-full max-w-md overflow-hidden rounded-2xl border bg-background shadow-2xl"
        >
            <div class="flex items-start justify-between border-b p-5">
                <div>
                    <p class="text-sm font-medium text-primary">
                        Manajemen lapangan
                    </p>
                    <h2 class="mt-1 text-xl font-semibold">
                        {{ courtFormTitle }}
                    </h2>
                </div>
                <Button variant="ghost" size="icon" @click="closeCourtForm"
                    ><X class="size-4"
                /></Button>
            </div>
            <Form
                :key="courtFormKey"
                :action="courtFormAction"
                :method="courtFormMethod"
                class="space-y-5 p-5"
                @success="closeCourtForm"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="court-name">Nama lapangan</Label>
                        <Input
                            id="court-name"
                            name="name"
                            :default-value="editingCourt?.name"
                            required
                            placeholder="e.g. Lapangan 1"
                        />
                        <p v-if="errors.name" class="text-xs text-destructive">
                            {{ errors.name }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="court-amenities">Fasilitas ruangan</Label>
                        <Input
                            id="court-amenities"
                            name="amenities"
                            :default-value="editingCourt?.amenities?.join(', ')"
                            placeholder="AC, Free WiFi, Loker"
                        />
                        <p class="text-xs text-muted-foreground">
                            Pisahkan dengan koma.
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="court-price-adjustment">Tambahan harga</Label>
                        <Input
                            id="court-price-adjustment"
                            name="price_adjustment"
                            type="number"
                            min="0"
                            step="1000"
                            :default-value="
                                Number(editingCourt?.price_adjustment ?? 0)
                            "
                            placeholder="0"
                        />
                        <p class="text-xs text-muted-foreground">
                            Surcharge per booking untuk lapangan ini (0 jika
                            tidak ada).
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="court-status">Status</Label>
                        <FormSelect
                            id="court-status"
                            v-model="courtForm.status"
                            name="status"
                            :options="courtStatusOptions"
                        />
                        <p
                            v-if="errors.status"
                            class="text-xs text-destructive"
                        >
                            {{ errors.status }}
                        </p>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t pt-4">
                    <Button
                        type="button"
                        variant="ghost"
                        @click="closeCourtForm"
                        >Batal</Button
                    >
                    <Button type="submit" :disabled="processing">
                        {{
                            processing
                                ? 'Menyimpan...'
                                : editingCourt
                                  ? 'Simpan perubahan'
                                  : 'Tambah lapangan'
                        }}
                    </Button>
                </div>
            </Form>
        </div>
    </div>

    <div
        v-if="showDeviceForm"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closeDeviceForm"
    >
        <div
            class="w-full max-w-md overflow-hidden rounded-2xl border bg-background shadow-2xl"
        >
            <div class="flex items-start justify-between border-b p-5">
                <div>
                    <p class="text-sm font-medium text-primary">
                        Manajemen perangkat
                    </p>
                    <h2 class="mt-1 text-xl font-semibold">
                        {{ deviceFormTitle }}
                    </h2>
                </div>
                <Button variant="ghost" size="icon" @click="closeDeviceForm"
                    ><X class="size-4"
                /></Button>
            </div>
            <Form
                :key="deviceFormKey"
                :action="deviceFormAction"
                :method="deviceFormMethod"
                class="space-y-5 p-5"
                @success="closeDeviceForm"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="device-name">Nama perangkat</Label>
                        <Input
                            id="device-name"
                            name="name"
                            :default-value="editingDevice?.name"
                            required
                            placeholder="e.g. Sensor Pintu"
                        />
                        <p v-if="errors.name" class="text-xs text-destructive">
                            {{ errors.name }}
                        </p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="device-type">Tipe</Label>
                            <Input
                                id="device-type"
                                name="type"
                                :default-value="editingDevice?.type ?? ''"
                                placeholder="e.g. RFID Reader"
                            />
                            <p
                                v-if="errors.type"
                                class="text-xs text-destructive"
                            >
                                {{ errors.type }}
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="device-status">Status</Label>
                            <FormSelect
                                id="device-status"
                                v-model="deviceForm.status"
                                name="status"
                                :options="deviceStatusOptions"
                            />
                            <p
                                v-if="errors.status"
                                class="text-xs text-destructive"
                            >
                                {{ errors.status }}
                            </p>
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="device-court">Lapangan</Label>
                        <FormSelect
                            id="device-court"
                            v-model="deviceForm.court_id"
                            name="court_id"
                            :options="deviceCourtOptions"
                        />
                        <p
                            v-if="errors.court_id"
                            class="text-xs text-destructive"
                        >
                            {{ errors.court_id }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Pilih lapangan untuk perangkat per-ruangan, atau
                            biarkan kosong untuk satu perangkat per olahraga.
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="device-code">Kode perangkat</Label>
                        <Input
                            id="device-code"
                            name="device_code"
                            :default-value="editingDevice?.device_code ?? ''"
                            placeholder="e.g. DEV-0001"
                        />
                        <p
                            v-if="errors.device_code"
                            class="text-xs text-destructive"
                        >
                            {{ errors.device_code }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="device-mqtt-topic">MQTT topic</Label>
                        <Input
                            id="device-mqtt-topic"
                            name="mqtt_topic"
                            :default-value="editingDevice?.mqtt_topic ?? ''"
                            placeholder="sportcenter/PADEL-01/command"
                        />
                        <p
                            v-if="errors.mqtt_topic"
                            class="text-xs text-destructive"
                        >
                            {{ errors.mqtt_topic }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Topic yang di-subscribe controller untuk perangkat
                            ini.
                        </p>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t pt-4">
                    <Button
                        type="button"
                        variant="ghost"
                        @click="closeDeviceForm"
                        >Batal</Button
                    >
                    <Button type="submit" :disabled="processing">
                        {{
                            processing
                                ? 'Menyimpan...'
                                : editingDevice
                                  ? 'Simpan perubahan'
                                  : 'Tambah perangkat'
                        }}
                    </Button>
                </div>
            </Form>
        </div>
    </div>
    <div v-if="showTrainerPackageForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 p-4" @click.self="closeTrainerPackageForm">
        <div class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl border bg-background shadow-2xl">
            <div class="flex items-start justify-between border-b p-5">
                <div><p class="text-sm font-medium text-primary">{{ props.facility.name }}</p><h2 class="mt-1 text-xl font-semibold">{{ trainerPackageTitle }}</h2></div>
                <Button variant="ghost" size="icon" @click="closeTrainerPackageForm"><X class="size-4" /></Button>
            </div>
            <form class="space-y-4 p-5" @submit.prevent="saveTrainerPackage">
                <input v-model="trainerPackageForm.facility_id" type="hidden" />
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2 sm:col-span-2"><Label>Nama paket</Label><Input v-model="trainerPackageForm.name" placeholder="Personal Trainer 10 Sesi" /><p v-if="trainerPackageForm.errors.name" class="text-xs text-destructive">{{ trainerPackageForm.errors.name }}</p></div>
                    <div class="grid gap-2"><Label>Jumlah sesi</Label><Input v-model.number="trainerPackageForm.total_sessions" type="number" min="1" /><p v-if="trainerPackageForm.errors.total_sessions" class="text-xs text-destructive">{{ trainerPackageForm.errors.total_sessions }}</p></div>
                    <div class="grid gap-2"><Label>Masa berlaku (hari)</Label><Input v-model.number="trainerPackageForm.duration_days" type="number" min="1" /><p v-if="trainerPackageForm.errors.duration_days" class="text-xs text-destructive">{{ trainerPackageForm.errors.duration_days }}</p></div>
                    <div class="grid gap-2"><Label>Harga (Rp)</Label><Input v-model="trainerPackageForm.price" type="number" min="0" placeholder="900000" /><p v-if="trainerPackageForm.errors.price" class="text-xs text-destructive">{{ trainerPackageForm.errors.price }}</p></div>
                    <div class="grid gap-2"><Label>Bagian trainer (%)</Label><Input v-model.number="trainerPackageForm.trainer_share_percentage" type="number" min="0" max="100" step="0.01" /><p v-if="trainerPackageForm.errors.trainer_share_percentage" class="text-xs text-destructive">{{ trainerPackageForm.errors.trainer_share_percentage }}</p><p class="text-xs text-muted-foreground">Persentase dari harga paket yang menjadi hak trainer.</p></div>
                    <div class="grid gap-2 sm:col-span-2"><Label>Deskripsi</Label><textarea v-model="trainerPackageForm.description" rows="3" class="w-full rounded-md border bg-background px-3 py-2 text-sm" placeholder="Deskripsi paket..." /><p v-if="trainerPackageForm.errors.description" class="text-xs text-destructive">{{ trainerPackageForm.errors.description }}</p></div>
                    <label v-if="editingTrainerPackage" class="flex items-center gap-2 text-sm sm:col-span-2"><input v-model="trainerPackageForm.is_active" type="checkbox" class="size-4" /> Paket aktif dan bisa dijual</label>
                </div>
                <div class="flex justify-end gap-2 border-t pt-4"><Button type="button" variant="ghost" @click="closeTrainerPackageForm">Batal</Button><Button type="submit" :disabled="trainerPackageForm.processing">{{ trainerPackageForm.processing ? 'Menyimpan...' : 'Simpan paket' }}</Button></div>
            </form>
        </div>
    </div>

</template>
