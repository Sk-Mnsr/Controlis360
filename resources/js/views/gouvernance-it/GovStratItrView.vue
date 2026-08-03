<template>
    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Gouvernance IT</p>
                    <h2 class="mt-1 text-xl font-semibold text-slate-900">GovStrat IT-R</h2>
                    <p class="mt-2 max-w-2xl text-slate-600">
                        Sélectionnez un module selon votre rôle : le formulaire s’affiche directement en dessous.
                    </p>
                </div>
                <RouterLink
                    :to="{ name: 'gouvernance-it.home' }"
                    class="text-sm font-medium text-slate-500 hover:text-slate-800"
                >
                    ← Retour à l’accueil
                </RouterLink>
            </div>

            <div class="govit-gears-wrap mt-10">
                <div
                    class="govit-gears"
                    :class="`govit-gears--${visibleModules.length}`"
                >
                    <button
                        v-for="(mod, index) in visibleModules"
                        :key="mod.key"
                        type="button"
                        class="govit-gear"
                        :class="[
                            `govit-gear--${mod.tone}`,
                            `govit-gear--pos-${index}`,
                            { 'govit-gear--active': activeModule === mod.key },
                        ]"
                        :style="{ '--gear-size': mod.size }"
                        :title="mod.label"
                        @click="selectModule(mod.key)"
                    >
                        <svg class="govit-gear-svg" viewBox="0 0 120 120" aria-hidden="true">
                            <path :d="gearPath" class="govit-gear-body" />
                            <circle cx="60" cy="60" r="18" class="govit-gear-hub" />
                            <circle cx="60" cy="60" r="8" class="govit-gear-hole" />
                        </svg>
                        <span
                            class="govit-gear-label"
                            :class="{ 'govit-gear-label--accent': mod.accent || activeModule === mod.key }"
                        >
                            {{ mod.label }}
                        </span>
                    </button>
                </div>
            </div>

            <div v-if="activeModule" class="mt-8 border-t border-slate-200 pt-6">
                <TaskActivityItView
                    v-if="activeModule === 'task-activity'"
                    :key="'task-activity'"
                    embedded
                />
                <GovItOperationsSectionView
                    v-else-if="activeModule === 'centre-support'"
                    :key="'centre-support'"
                    embedded
                    title="CENTRE SUPPORT"
                    module-slug="centre_support"
                />
                <GovItOperationsSectionView
                    v-else-if="activeModule === 'systemes-reseaux'"
                    :key="'systemes-reseaux'"
                    embedded
                    title="SYSTEMES ET RESEAUX"
                    module-slug="systemes_reseaux"
                />
                <GovItOperationsSectionView
                    v-else-if="activeModule === 'base-donnees'"
                    :key="'base-donnees'"
                    embedded
                    title="BASE DE DONNEES"
                    module-slug="base_donnees"
                />
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useAuthStore } from '../../stores/auth';
import TaskActivityItView from './TaskActivityItView.vue';
import GovItOperationsSectionView from './GovItOperationsSectionView.vue';

const auth = useAuthStore();
const profile = computed(() => auth.user?.profile);
const activeModule = ref(null);

const isAdminViewer = computed(() =>
    ['super_admin', 'admin'].includes(profile.value),
);

const canSeeTaskActivity = computed(() =>
    isAdminViewer.value || profile.value === 'responsable_regional',
);

const canSeeOperations = computed(() =>
    isAdminViewer.value
    || profile.value === 'agent_it'
    || profile.value === 'responsable_it',
);

const visibleModules = computed(() => {
    const items = [];
    if (canSeeTaskActivity.value) {
        items.push({
            key: 'task-activity',
            label: 'Task ACTIVITY IT',
            tone: 'dark',
            accent: false,
            size: '11.5rem',
        });
    }
    if (canSeeOperations.value) {
        items.push({
            key: 'centre-support',
            label: 'CENTRE SUPPORT',
            tone: 'light',
            accent: true,
            size: '12.5rem',
        });
        items.push({
            key: 'systemes-reseaux',
            label: 'SYSTEMES ET RESEAUX',
            tone: 'mid',
            accent: false,
            size: '12rem',
        });
        items.push({
            key: 'base-donnees',
            label: 'BASE DE DONNEES',
            tone: 'dark',
            accent: false,
            size: '11.5rem',
        });
    }
    return items;
});

/** SVG path d’un engrenage (dents + disque). */
const gearPath = (() => {
    const teeth = 14;
    const cx = 60;
    const cy = 60;
    const outer = 54;
    const tip = 48;
    const root = 40;
    const points = [];

    for (let i = 0; i < teeth; i += 1) {
        const a0 = ((i / teeth) * Math.PI * 2) - Math.PI / 2;
        const a1 = a0 + (Math.PI * 2) / teeth / 7;
        const a2 = a0 + (Math.PI * 2) / teeth * 0.35;
        const a3 = a0 + (Math.PI * 2) / teeth * 0.55;
        const a4 = a0 + (Math.PI * 2) / teeth * 0.78;
        const a5 = a0 + (Math.PI * 2) / teeth;

        const pt = (angle, r) => [
            cx + Math.cos(angle) * r,
            cy + Math.sin(angle) * r,
        ];

        const p0 = pt(a0, tip);
        const p1 = pt(a1, outer);
        const p2 = pt(a2, outer);
        const p3 = pt(a3, root);
        const p4 = pt(a4, root);
        const p5 = pt(a5, tip);

        if (i === 0) {
            points.push(`M ${p0[0].toFixed(2)} ${p0[1].toFixed(2)}`);
        }
        points.push(
            `L ${p1[0].toFixed(2)} ${p1[1].toFixed(2)}`,
            `L ${p2[0].toFixed(2)} ${p2[1].toFixed(2)}`,
            `L ${p3[0].toFixed(2)} ${p3[1].toFixed(2)}`,
            `L ${p4[0].toFixed(2)} ${p4[1].toFixed(2)}`,
            `L ${p5[0].toFixed(2)} ${p5[1].toFixed(2)}`,
        );
    }

    points.push('Z');
    return points.join(' ');
})();

function selectModule(moduleKey) {
    activeModule.value = activeModule.value === moduleKey ? null : moduleKey;
}
</script>

<style scoped>
.govit-gears-wrap {
    display: flex;
    justify-content: center;
    overflow-x: auto;
    padding: 1.5rem 0.5rem 2rem;
    background:
        radial-gradient(ellipse at 50% 40%, rgba(148, 163, 184, 0.18), transparent 65%),
        linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
    border-radius: 1rem;
    border: 1px solid #e2e8f0;
}

.govit-gears {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 16rem;
    width: min(920px, 100%);
}

.govit-gear {
    position: relative;
    width: var(--gear-size, 12rem);
    height: var(--gear-size, 12rem);
    border: 0;
    background: transparent;
    padding: 0;
    cursor: pointer;
    margin-left: -1.75rem;
    transition: transform 0.35s ease, filter 0.25s ease;
    z-index: 1;
}

.govit-gears > .govit-gear:first-child {
    margin-left: 0;
}

.govit-gear:hover {
    transform: scale(1.05) rotate(8deg);
    z-index: 5;
    filter: drop-shadow(0 10px 18px rgba(15, 23, 42, 0.22));
}

.govit-gear--active {
    z-index: 6;
    filter: drop-shadow(0 12px 22px rgba(163, 24, 31, 0.28));
}

.govit-gear-svg {
    width: 100%;
    height: 100%;
    display: block;
}

.govit-gear--dark .govit-gear-body {
    fill: #4b5563;
    stroke: #374151;
    stroke-width: 1.2;
}

.govit-gear--dark .govit-gear-hub {
    fill: #6b7280;
}

.govit-gear--dark .govit-gear-hole {
    fill: #e5e7eb;
}

.govit-gear--mid .govit-gear-body {
    fill: #9ca3af;
    stroke: #6b7280;
    stroke-width: 1.2;
}

.govit-gear--mid .govit-gear-hub {
    fill: #d1d5db;
}

.govit-gear--mid .govit-gear-hole {
    fill: #f3f4f6;
}

.govit-gear--light .govit-gear-body {
    fill: #d1d5db;
    stroke: #9ca3af;
    stroke-width: 1.2;
}

.govit-gear--light .govit-gear-hub {
    fill: #e5e7eb;
}

.govit-gear--light .govit-gear-hole {
    fill: #f9fafb;
}

.govit-gear--active .govit-gear-body {
    stroke: #a3181f;
    stroke-width: 2;
}

.govit-gear-label {
    position: absolute;
    inset: 22%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 0.35rem;
    font-size: clamp(0.58rem, 1.35vw, 0.78rem);
    font-weight: 800;
    line-height: 1.15;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    color: #1f2937;
    pointer-events: none;
}

.govit-gear--dark .govit-gear-label {
    color: #f9fafb;
}

.govit-gear-label--accent {
    color: #a3181f;
}

.govit-gears--1 .govit-gear {
    margin-left: 0;
}

.govit-gears--2 .govit-gear--pos-0 {
    transform: translateY(-0.5rem);
}

.govit-gears--2 .govit-gear--pos-1 {
    transform: translateY(1rem);
}

.govit-gears--3 .govit-gear--pos-0 {
    transform: translateY(0.75rem);
    z-index: 2;
}

.govit-gears--3 .govit-gear--pos-1 {
    transform: translateY(-1.25rem);
    z-index: 3;
}

.govit-gears--3 .govit-gear--pos-2 {
    transform: translateY(1.5rem);
    z-index: 2;
}

.govit-gears--4 .govit-gear--pos-0 {
    transform: translateY(0.9rem);
    z-index: 2;
}

.govit-gears--4 .govit-gear--pos-1 {
    transform: translateY(-1.35rem);
    z-index: 4;
}

.govit-gears--4 .govit-gear--pos-2 {
    transform: translateY(0.35rem);
    z-index: 3;
}

.govit-gears--4 .govit-gear--pos-3 {
    transform: translateY(1.6rem);
    z-index: 2;
}

.govit-gears--3 .govit-gear--pos-0:hover,
.govit-gears--3 .govit-gear--pos-1:hover,
.govit-gears--3 .govit-gear--pos-2:hover,
.govit-gears--4 .govit-gear--pos-0:hover,
.govit-gears--4 .govit-gear--pos-1:hover,
.govit-gears--4 .govit-gear--pos-2:hover,
.govit-gears--4 .govit-gear--pos-3:hover,
.govit-gears--2 .govit-gear--pos-0:hover,
.govit-gears--2 .govit-gear--pos-1:hover {
    transform: translateY(0) scale(1.06) rotate(8deg);
}

@media (max-width: 640px) {
    .govit-gears {
        min-height: 12rem;
    }

    .govit-gear {
        margin-left: -1.1rem;
        --gear-size: 8.5rem !important;
    }

    .govit-gear-label {
        font-size: 0.52rem;
        inset: 24%;
    }
}
</style>
