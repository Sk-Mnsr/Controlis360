<template>
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold">Référentiels</h2>
            <p class="mt-1 text-sm text-slate-500">Données issues du fichier Excel MESO VF</p>
        </div>

        <p v-if="loading" class="text-sm text-slate-500">Chargement...</p>

        <div v-else class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold">Échelle de gravité (G)</h3>
                <ul class="mt-4 space-y-2 text-sm">
                    <li v-for="item in scales.gravity" :key="item.id" class="flex gap-3">
                        <span class="font-semibold text-emerald-700">{{ item.level }}</span>
                        <span>{{ item.label }}</span>
                    </li>
                </ul>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold">Échelle de probabilité (P)</h3>
                <ul class="mt-4 space-y-2 text-sm">
                    <li v-for="item in scales.probability" :key="item.id" class="flex gap-3">
                        <span class="font-semibold text-emerald-700">{{ item.level }}</span>
                        <span>{{ item.label }}</span>
                    </li>
                </ul>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold">Échelle de contrôle</h3>
                <ul class="mt-4 space-y-2 text-sm">
                    <li v-for="item in scales.control" :key="item.id" class="flex gap-3">
                        <span class="font-semibold text-emerald-700">{{ item.level }}</span>
                        <span>{{ item.qualification }} — {{ item.maturity_label }}</span>
                    </li>
                </ul>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold">Départements MESO</h3>
                <ul class="mt-4 space-y-1 text-sm">
                    <li v-for="dept in departments" :key="dept.id">{{ dept.name }}</li>
                </ul>
            </section>

            <section class="col-span-full rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold">Familles de risques</h3>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div v-for="category in riskFamilies" :key="category.id" class="rounded-xl bg-slate-50 p-4">
                        <h4 class="font-medium">{{ category.number }}. {{ category.name }}</h4>
                        <ul class="mt-2 space-y-1 text-sm text-slate-600">
                            <li v-for="family in category.families" :key="family.id">• {{ family.name }}</li>
                        </ul>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '../api/client';

const loading = ref(true);
const scales = ref({ gravity: [], probability: [], control: [] });
const departments = ref([]);
const riskFamilies = ref([]);

onMounted(async () => {
    try {
        const [scalesRes, deptRes, familiesRes] = await Promise.all([
            api.get('/referentials/scales'),
            api.get('/referentials/departments'),
            api.get('/referentials/risk-families'),
        ]);

        scales.value = scalesRes.data.data ?? scalesRes.data;
        departments.value = deptRes.data.data ?? deptRes.data;
        riskFamilies.value = familiesRes.data.data ?? familiesRes.data;
    } finally {
        loading.value = false;
    }
});
</script>
