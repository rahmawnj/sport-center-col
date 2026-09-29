<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Sport Center" />

    <div class="min-h-screen bg-white text-slate-900">
        <main>
            <slot />
        </main>
    </div>
</template>
