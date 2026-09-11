<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';

const currentYear = new Date().getFullYear();
</script>

<template>
    <Head title="Sport Center" />

    <div class="min-h-screen bg-slate-950 text-white">
        <header class="mx-auto flex w-full max-w-7xl items-center justify-between px-6 py-6 lg:px-8">
            <div class="text-xl font-bold tracking-tight">Sport Center</div>

            <nav class="flex items-center gap-2 sm:gap-3">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-slate-100"
                >
                    Dasbor
                </Link>

                <template v-else>
                    <Link
                        :href="login()"
                        class="rounded-xl px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10"
                    >
                        Masuk
                    </Link>
                    <Link
                        :href="register()"
                        class="rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-slate-100"
                    >
                        Daftar
                    </Link>
                </template>
            </nav>
        </header>

        <main>
            <section class="mx-auto grid min-h-[70vh] w-full max-w-7xl items-center gap-12 px-6 py-16 lg:grid-cols-2 lg:px-8">
                <div>
                    <p class="mb-4 text-sm font-semibold uppercase tracking-[0.25em] text-cyan-300">
                        Pusat Olahraga
                    </p>
                    <h1 class="max-w-3xl text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                        Kelola aktivitas olahraga dengan lebih mudah.
                    </h1>
                    <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">
                        Temukan lapangan, kelola pemesanan, keanggotaan, fasilitas, dan layanan olahraga dalam satu platform.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="dashboard()"
                            class="rounded-xl bg-cyan-400 px-6 py-3 font-semibold text-slate-950 transition hover:bg-cyan-300"
                        >
                            Buka Dasbor
                        </Link>
                        <template v-else>
                            <Link
                                :href="register()"
                                class="rounded-xl bg-cyan-400 px-6 py-3 font-semibold text-slate-950 transition hover:bg-cyan-300"
                            >
                                Mulai Sekarang
                            </Link>
                            <Link
                                :href="login()"
                                class="rounded-xl border border-white/20 px-6 py-3 font-semibold text-white transition hover:bg-white/10"
                            >
                                Masuk
                            </Link>
                        </template>
                    </div>
                </div>

                <div class="rounded-3xl border border-white/10 bg-white/5 p-8 shadow-2xl backdrop-blur">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl bg-white/5 p-5">
                            <p class="text-sm text-slate-400">Pemesanan</p>
                            <p class="mt-2 text-xl font-semibold">Lebih praktis</p>
                        </div>
                        <div class="rounded-2xl bg-white/5 p-5">
                            <p class="text-sm text-slate-400">Keanggotaan</p>
                            <p class="mt-2 text-xl font-semibold">Terorganisir</p>
                        </div>
                        <div class="rounded-2xl bg-white/5 p-5">
                            <p class="text-sm text-slate-400">Fasilitas</p>
                            <p class="mt-2 text-xl font-semibold">Lengkap</p>
                        </div>
                        <div class="rounded-2xl bg-white/5 p-5">
                            <p class="text-sm text-slate-400">Operasional</p>
                            <p class="mt-2 text-xl font-semibold">Terpusat</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-t border-white/10 bg-slate-900/70">
                <div class="mx-auto grid w-full max-w-7xl gap-8 px-6 py-16 md:grid-cols-3 lg:px-8">
                    <div>
                        <h2 class="text-lg font-semibold">Pemesanan Lapangan</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-400">
                            Atur jadwal dan pemesanan lapangan dengan lebih mudah.
                        </p>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold">Manajemen Keanggotaan</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-400">
                            Kelola paket, langganan, dan status anggota dalam satu tempat.
                        </p>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold">Data Operasional</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-400">
                            Pantau data fasilitas, tarif, pelatih, dan layanan tambahan secara terpusat.
                        </p>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-white/10">
            <div class="mx-auto flex w-full max-w-7xl flex-col gap-2 px-6 py-6 text-sm text-slate-400 sm:flex-row sm:items-center sm:justify-between lg:px-8">
                <span>© {{ currentYear }} Sport Center</span>
                <span>Platform pengelolaan pusat olahraga</span>
            </div>
        </footer>
    </div>
</template>
