<template>
    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-semibold">Conformité</h2>
            <p class="mt-2 text-slate-600">
                Bienvenue, <strong>{{ auth.user?.name }}</strong>.
                Vous êtes connecté en tant que <strong>{{ auth.user?.profile_fr }}</strong>.
            </p>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <RouterLink
                    v-if="canManageSaisie"
                    :to="{ name: 'conformite.reporting.create' }"
                    class="rounded-xl border border-red-200 bg-red-50 p-5 transition hover:border-red-300 hover:shadow-sm"
                >
                    <p class="text-xs font-semibold uppercase tracking-wide text-red-800">Saisie</p>
                    <p class="mt-2 text-lg font-semibold text-red-950">Fiche de reporting</p>
                    <p class="mt-1 text-sm text-red-900">
                        Créer une fiche de reporting réglementaire LBC/FT/FP.
                    </p>
                </RouterLink>

                <RouterLink
                    v-if="canManageSaisie"
                    :to="{ name: 'conformite.reporting.history' }"
                    class="rounded-xl border border-amber-200 bg-amber-50 p-5 transition hover:border-amber-300 hover:shadow-sm"
                >
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-800">Consultation</p>
                    <p class="mt-2 text-lg font-semibold text-amber-950">Historique</p>
                    <p class="mt-1 text-sm text-amber-900">
                        Consulter et modifier les fiches déjà enregistrées.
                    </p>
                </RouterLink>

                <RouterLink
                    :to="{ name: 'conformite.reporting.reception' }"
                    class="rounded-xl border border-slate-200 bg-slate-50 p-5 transition hover:border-slate-300 hover:shadow-sm"
                >
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-700">Département</p>
                    <p class="mt-2 text-lg font-semibold text-slate-950">Réception</p>
                    <p class="mt-1 text-sm text-slate-700">
                        Fiches reçues selon « Établi par » — compléter avec valeur, contenu, nom et PJ.
                    </p>
                </RouterLink>
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();

const canManageSaisie = computed(() =>
    ['super_admin', 'conformite'].includes(auth.user?.profile),
);
</script>
