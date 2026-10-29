<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import PublicLayout from '@/layouts/PublicLayout.vue';

type MembershipPackage = {
    id: number;
    name: string;
    duration_days: number;
    facility: string | null;
    facility_slug: string | null;
    prices: number[];
    price: number | null;
    description: string;
};

type CurrentUser = { name: string; email: string } | null;

const props = defineProps<{
    packages: MembershipPackage[];
    currentUser: CurrentUser;
}>();

const selectedPackage = ref<MembershipPackage | null>(null);
const checkoutOpen = ref(false);
const form = useForm({
    package_id: 0,
    terms_accepted: false,
    payment_method: 'bank_transfer',
});

const readyPackages = computed(() => props.packages.filter((item) => item.price !== null));
const featuredId = computed(() => readyPackages.value.length > 1
    ? readyPackages.value[Math.floor(readyPackages.value.length / 2)]?.id
    : readyPackages.value[0]?.id);

watch(selectedPackage, (item) => {
    if (item) {
form.package_id = item.id;
}
});

const formatCurrency = (value: number | null) => value === null
    ? 'Harga belum tersedia'
    : new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const openCheckout = (item: MembershipPackage) => {
    selectedPackage.value = item;
    form.clearErrors();
    checkoutOpen.value = true;
};

const closeCheckout = () => {
    if (form.processing) {
return;
}

    checkoutOpen.value = false;
};

const submitPurchase = () => {
    if (!selectedPackage.value || !props.currentUser) {
return;
}

    form.package_id = selectedPackage.value.id;
    form.post('/book', {
        preserveScroll: true,
        onSuccess: () => {
            checkoutOpen.value = false;
        },
    });
};

const durationLabel = (days: number) => {
    if (days >= 365 && days % 365 === 0) {
return days / 365 + ' tahun';
}

    if (days >= 30 && days % 30 === 0) {
return days / 30 + ' bulan';
}

    return days + ' hari';
};
</script>

<template>
    <Head title="Membership — DreamSports" />
    <PublicLayout>
        <main class="membership-page">
            <section class="membership-hero relative overflow-hidden">
                <div class="hero-orbit hero-orbit-one"></div>
                <div class="hero-orbit hero-orbit-two"></div>
                <div class="relative z-10 mx-auto grid max-w-7xl items-center gap-12 px-5 py-16 sm:py-20 lg:grid-cols-[1.05fr_.95fr] lg:px-8 lg:py-24">
                    <div>
                        <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/[.06] px-4 py-2 text-xs font-bold tracking-wide text-white/80">
                            <span class="h-2 w-2 rounded-full bg-[#a4da01]"></span>
                            MEMBERSHIP DREAMSPORTS
                        </div>
                        <h1 class="max-w-2xl text-4xl font-black leading-[1.08] tracking-[-.045em] text-white sm:text-5xl lg:text-6xl">
                            Latihan lebih rutin.<br />
                            <span class="text-[#b7e934]">Progress lebih nyata.</span>
                        </h1>
                        <p class="mt-6 max-w-xl text-base leading-7 text-slate-300 sm:text-lg">
                            Pilih paket yang sesuai dengan targetmu. Semua pilihan ditampilkan dengan harga dan masa berlaku yang jelas sebelum kamu melanjutkan.
                        </p>
                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <a href="#pilih-paket" class="inline-flex items-center justify-center gap-3 rounded-full bg-[#a4da01] px-6 py-3.5 font-extrabold text-[#001428] transition hover:-translate-y-0.5 hover:bg-[#b8ec24]">
                                Pilih paket membership <i class="fa-solid fa-arrow-down"></i>
                            </a>
                            <a href="#cara-kerja" class="inline-flex items-center justify-center rounded-full border border-white/20 px-6 py-3.5 font-bold text-white transition hover:bg-white/10">
                                Cara pembelian
                            </a>
                        </div>
                        <div class="mt-10 flex flex-wrap gap-x-6 gap-y-3 text-sm font-medium text-slate-300">
                            <span class="inline-flex items-center gap-2"><i class="fa-solid fa-circle-check text-[#b7e934]"></i> Harga transparan</span>
                            <span class="inline-flex items-center gap-2"><i class="fa-solid fa-circle-check text-[#b7e934]"></i> Pilihan durasi fleksibel</span>
                            <span class="inline-flex items-center gap-2"><i class="fa-solid fa-circle-check text-[#b7e934]"></i> Konfirmasi sebelum lanjut</span>
                        </div>
                    </div>

                    <div class="hero-visual relative mx-auto w-full max-w-lg">
                        <div class="hero-image overflow-hidden rounded-[2rem] border border-white/15 bg-white/5 p-3 shadow-2xl shadow-black/25">
                            <img
                                src="https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=1100&q=85"
                                alt="Area latihan gym modern"
                                class="h-[300px] w-full rounded-[1.5rem] object-cover sm:h-[370px]"
                            />
                            <div class="flex items-center justify-between gap-4 px-2 pb-1 pt-5">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[.18em] text-[#b7e934]">Your next chapter</p>
                                    <p class="mt-1 text-lg font-extrabold text-white">Mulai dari satu komitmen.</p>
                                </div>
                                <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-[#a4da01] text-xl text-[#001428]">
                                    <i class="fa-solid fa-bolt"></i>
                                </div>
                            </div>
                        </div>
                        <div class="absolute -bottom-5 -left-3 rounded-2xl border border-white/60 bg-white/95 px-4 py-3 shadow-xl sm:-left-8 sm:px-5">
                            <p class="text-xs font-bold text-slate-500">Investasi untuk diri sendiri</p>
                            <p class="mt-1 text-sm font-black text-[#001428]">Small steps. Stronger you.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section id="pilih-paket" class="mx-auto max-w-7xl scroll-mt-24 px-5 py-16 sm:py-20 lg:px-8 lg:py-24">
                <div class="mx-auto mb-12 max-w-2xl text-center">
                    <p class="section-kicker">Temukan ritmemu</p>
                    <h2 class="section-title">Pilih paket yang <span>pas untukmu</span></h2>
                    <p class="mt-4 leading-7 text-slate-500">Bandingkan harga, masa aktif, dan olahraga yang terkait. Kamu bisa meninjau ulang pilihan sebelum mengonfirmasi.</p>
                </div>

                <div v-if="readyPackages.length" class="grid items-stretch gap-6 md:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="(item, index) in readyPackages"
                        :key="item.id"
                        class="package-card relative flex flex-col rounded-3xl border bg-white p-6 sm:p-7"
                        :class="item.id === featuredId ? 'package-card-featured' : 'border-slate-200'"
                    >
                        <div v-if="item.id === featuredId" class="popular-ribbon">
                            <i class="fa-solid fa-star mr-1.5"></i> Paling direkomendasikan
                        </div>
                        <div class="mb-6 flex items-start justify-between gap-4">
                            <div class="grid h-12 w-12 place-items-center rounded-2xl" :class="item.id === featuredId ? 'bg-[#a4da01] text-[#001428]' : 'bg-slate-100 text-slate-700'">
                                <i :class="item.name.toLowerCase().includes('gym') ? 'fa-solid fa-dumbbell' : 'fa-solid fa-medal'" class="text-lg"></i>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600">{{ durationLabel(item.duration_days) }}</span>
                        </div>
                        <p class="text-xs font-extrabold uppercase tracking-[.16em] text-[#789900]">{{ item.facility || 'DreamSports' }}</p>
                        <h3 class="mt-2 text-2xl font-black tracking-tight text-[#001428]">{{ item.name }}</h3>
                        <p class="mt-3 min-h-[48px] text-sm leading-6 text-slate-500">{{ item.description }}</p>

                        <div class="my-7 border-y border-slate-100 py-6">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Harga paket</p>
                            <p class="mt-2 break-words text-3xl font-black tracking-tight text-[#001428]">{{ formatCurrency(item.price) }}</p>
                            <p class="mt-2 text-sm text-slate-500">Berlaku {{ item.duration_days }} hari sejak aktivasi</p>
                        </div>

                        <ul class="mb-8 flex-1 space-y-3 text-sm text-slate-600">
                            <li class="flex gap-3"><i class="fa-solid fa-check mt-0.5 text-[#789900]"></i><span>Masa berlaku ditampilkan dengan jelas</span></li>
                            <li class="flex gap-3"><i class="fa-solid fa-check mt-0.5 text-[#789900]"></i><span>Detail paket bisa ditinjau sebelum konfirmasi</span></li>
                            <li class="flex gap-3"><i class="fa-solid fa-check mt-0.5 text-[#789900]"></i><span>Dikelola melalui akun member</span></li>
                        </ul>

                        <button
                            type="button"
                            class="flex w-full items-center justify-center gap-3 rounded-full px-5 py-3.5 font-extrabold transition hover:-translate-y-0.5"
                            :class="item.id === featuredId ? 'bg-[#a4da01] text-[#001428] hover:bg-[#b8ec24]' : 'border border-slate-200 bg-white text-[#001428] hover:border-[#a4da01] hover:bg-[#f8fbeF]'"
                            :disabled="item.price === null"
                            @click="openCheckout(item)"
                        >
                            Pilih paket <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </article>
                </div>

                <div v-else class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-16 text-center">
                    <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-white text-slate-400 shadow-sm"><i class="fa-solid fa-box-open text-xl"></i></div>
                    <h3 class="mt-5 text-xl font-extrabold text-[#001428]">Paket membership belum tersedia</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Saat ini belum ada paket dengan harga aktif. Silakan kembali lagi nanti atau hubungi pengelola.</p>
                    <Link href="/" class="mt-6 inline-flex rounded-full bg-[#001428] px-5 py-3 text-sm font-bold text-white">Kembali ke beranda</Link>
                </div>

                <p class="mt-6 text-center text-xs leading-5 text-slate-400">Harga mengikuti paket dan tarif yang tersedia di sistem. Jika paket belum memiliki tarif aktif, paket tidak ditampilkan sebagai pilihan pembelian.</p>
            </section>

            <section id="cara-kerja" class="bg-[#f6f8f2]">
                <div class="mx-auto max-w-7xl px-5 py-16 sm:py-20 lg:px-8">
                    <div class="mb-10 max-w-2xl">
                        <p class="section-kicker">Tanpa langkah membingungkan</p>
                        <h2 class="section-title">Pembelian yang <span>jelas dari awal</span></h2>
                        <p class="mt-3 leading-7 text-slate-500">Kamu tetap tahu paket apa yang dipilih dan apa yang perlu dilakukan berikutnya.</p>
                    </div>
                    <div class="grid gap-5 md:grid-cols-3">
                        <div class="step-card">
                            <span class="step-number">01</span>
                            <h3>Pilih paket</h3>
                            <p>Bandingkan harga dan durasi, lalu pilih membership yang sesuai kebutuhanmu.</p>
                        </div>
                        <div class="step-card">
                            <span class="step-number">02</span>
                            <h3>Periksa detail</h3>
                            <p>Pastikan nama paket dan harga sudah sesuai sebelum mengirim pesanan.</p>
                        </div>
                        <div class="step-card">
                            <span class="step-number">03</span>
                            <h3>Lanjutkan pesanan</h3>
                            <p>Pesanan dicatat ke sistem. Ikuti instruksi pembayaran dan konfirmasi dari pengelola.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-5xl px-5 py-16 sm:py-20">
                <div class="rounded-3xl bg-[#001428] px-6 py-9 text-center sm:px-12 sm:py-12">
                    <p class="text-xs font-extrabold uppercase tracking-[.2em] text-[#b7e934]">Siap mulai?</p>
                    <h2 class="mx-auto mt-3 max-w-2xl text-2xl font-black leading-tight text-white sm:text-3xl">Pilih membership yang mendukung targetmu berikutnya.</h2>
                    <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-slate-300">Mulai dengan paket yang realistis untuk rutinitasmu. Konsistensi lebih penting daripada terburu-buru.</p>
                    <a href="#pilih-paket" class="mt-6 inline-flex items-center gap-3 rounded-full bg-[#a4da01] px-6 py-3.5 font-extrabold text-[#001428] transition hover:bg-[#b8ec24]">Lihat paket <i class="fa-solid fa-arrow-up"></i></a>
                </div>
            </section>
        </main>

        <div v-if="checkoutOpen && selectedPackage" class="checkout-overlay" @click.self="closeCheckout" @keydown.esc="closeCheckout">
            <section class="checkout-dialog" role="dialog" aria-modal="true" aria-labelledby="checkout-title">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5 sm:px-7">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-[.16em] text-[#789900]">Konfirmasi pilihan</p>
                        <h2 id="checkout-title" class="mt-1 text-xl font-black text-[#001428]">Ringkasan membership</h2>
                    </div>
                    <button type="button" aria-label="Tutup konfirmasi" class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-slate-100 text-slate-600 transition hover:bg-slate-200" @click="closeCheckout">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="space-y-5 px-6 py-6 sm:px-7">
                    <div class="rounded-2xl bg-[#f6f8f2] p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-bold text-slate-500">{{ selectedPackage.facility || 'DreamSports' }}</p>
                                <h3 class="mt-1 font-extrabold text-[#001428]">{{ selectedPackage.name }}</h3>
                                <p class="mt-1 text-sm text-slate-500">Masa berlaku {{ durationLabel(selectedPackage.duration_days) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-slate-500">Total</p>
                                <p class="mt-1 font-black text-[#001428]">{{ formatCurrency(selectedPackage.price) }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="!currentUser" class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                        <p class="font-bold text-amber-900">Masuk untuk melanjutkan</p>
                        <p class="mt-1 text-sm leading-6 text-amber-800">Membership akan terhubung ke akunmu agar lebih mudah dikelola.</p>
                        <Link href="/login" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-full bg-[#001428] px-5 py-3 font-bold text-white">Masuk ke akun <i class="fa-solid fa-arrow-right"></i></Link>
                    </div>

                    <form v-else class="space-y-4" @submit.prevent="submitPurchase">
                        <div class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-[#eaf5c8] text-[#4f6900]">
                                <i class="fa-solid fa-user-check text-lg"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-extrabold uppercase tracking-wider text-slate-500">Akun member</p>
                                <p class="mt-1 truncate font-extrabold text-[#001428]">{{ currentUser?.name }}</p>
                                <p class="mt-0.5 break-all text-sm text-slate-500">{{ currentUser?.email }}</p>
                                <p class="mt-2 text-xs leading-5 text-slate-500">Identitas diambil otomatis dari akun yang sedang login. Tidak perlu mengisi data kontak lagi.</p>
                            </div>
                        </div>
                        <div>
                            <label for="payment-method" class="mb-1.5 block text-sm font-bold text-slate-700">Metode pembayaran</label>
                            <select id="payment-method" v-model="form.payment_method" required class="checkout-input">
                                <option value="bank_transfer">Transfer bank</option>
                                <option value="cash">Bayar tunai di tempat</option>
                            </select>
                            <p v-if="form.errors.payment_method" class="mt-1 text-xs text-rose-600">{{ form.errors.payment_method }}</p>
                        </div>
                        <label class="flex cursor-pointer items-start gap-3 text-sm leading-5 text-slate-600">
                            <input v-model="form.terms_accepted" type="checkbox" required class="mt-1 h-4 w-4 accent-[#789900]" />
                            <span>Saya memastikan detail paket sudah benar dan setuju mengikuti ketentuan membership.</span>
                        </label>
                        <p v-if="form.errors.package_id" class="text-sm text-rose-600">{{ form.errors.package_id }}</p>
                        <p v-if="form.errors.terms_accepted" class="text-sm text-rose-600">{{ form.errors.terms_accepted }}</p>
                        <div class="rounded-xl border border-slate-200 p-3 text-xs leading-5 text-slate-500">
                            <i class="fa-solid fa-shield-halved mr-1 text-[#789900]"></i>
                            Data akun member akan digunakan otomatis. Periksa kembali paket dan metode pembayaran sebelum melanjutkan.
                        </div>
                        <button type="submit" :disabled="form.processing" class="flex w-full items-center justify-center gap-2 rounded-full bg-[#a4da01] px-5 py-3.5 font-extrabold text-[#001428] transition hover:bg-[#b8ec24] disabled:cursor-not-allowed disabled:opacity-60">
                            <i v-if="form.processing" class="fa-solid fa-spinner fa-spin"></i>
                            {{ form.processing ? 'Memproses pesanan…' : 'Konfirmasi pembelian' }}
                            <i v-if="!form.processing" class="fa-solid fa-arrow-right"></i>
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </PublicLayout>
</template>

<style scoped>
.membership-page { --lime: #a4da01; --navy: #001428; font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
.membership-hero { background: var(--navy); }
.hero-orbit { position: absolute; border: 1px solid rgba(164,218,1,.15); border-radius: 50%; pointer-events: none; }
.hero-orbit-one { top: -340px; right: -150px; width: 760px; height: 760px; box-shadow: 0 0 0 60px rgba(164,218,1,.025), 0 0 0 120px rgba(164,218,1,.02); }
.hero-orbit-two { right: 16%; bottom: -400px; width: 600px; height: 600px; }
.hero-image { background: rgba(255,255,255,.04); }
.section-kicker { color: #789900; font-size: 11px; font-weight: 900; letter-spacing: .2em; text-transform: uppercase; }
.section-title { margin-top: 10px; color: var(--navy); font-size: clamp(28px, 3vw, 40px); font-weight: 900; line-height: 1.16; letter-spacing: -.04em; }
.section-title span { color: #7da500; }
.package-card { box-shadow: 0 12px 35px rgba(0,20,40,.045); transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease; }
.package-card:hover { transform: translateY(-5px); box-shadow: 0 24px 50px rgba(0,20,40,.1); }
.package-card-featured { border-color: #a4da01; box-shadow: 0 20px 45px rgba(91,125,0,.1); }
.popular-ribbon { position: absolute; top: -12px; left: 50%; transform: translateX(-50%); white-space: nowrap; border-radius: 999px; background: #a4da01; padding: 6px 12px; color: #001428; font-size: 10px; font-weight: 900; box-shadow: 0 5px 14px rgba(0,20,40,.12); }
.step-card { border: 1px solid #e5ebd9; border-radius: 22px; background: white; padding: 26px; }
.step-number { display: grid; width: 44px; height: 44px; place-items: center; border-radius: 14px; background: #eaf5c8; color: #4f6900; font-size: 13px; font-weight: 900; }
.step-card h3 { margin-top: 22px; color: #001428; font-size: 18px; font-weight: 900; }
.step-card p { margin-top: 8px; color: #64748b; font-size: 14px; line-height: 1.7; }
.checkout-overlay { position: fixed; z-index: 100; inset: 0; display: flex; align-items: center; justify-content: center; overflow-y: auto; background: rgba(0,12,25,.68); padding: 20px; backdrop-filter: blur(8px); }
.checkout-dialog { width: 100%; max-width: 520px; max-height: min(90vh, 850px); overflow-y: auto; border-radius: 26px; background: white; box-shadow: 0 30px 100px rgba(0,0,0,.3); animation: checkout-in .2s ease-out; }
.checkout-input { width: 100%; border: 1px solid #dbe2ea; border-radius: 12px; background: white; padding: 11px 13px; color: #001428; outline: none; transition: border-color .15s ease, box-shadow .15s ease; }
.checkout-input:focus { border-color: #8dbb00; box-shadow: 0 0 0 3px rgba(164,218,1,.18); }
@keyframes checkout-in { from { opacity: 0; transform: translateY(10px) scale(.99); } to { opacity: 1; transform: translateY(0) scale(1); } }
@media (prefers-reduced-motion: reduce) { .package-card, .checkout-dialog { animation: none; transition: none; } }
@media (max-width: 480px) { .popular-ribbon { font-size: 9px; } }
</style>
