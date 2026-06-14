<template>
    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-semibold">Bienvenue, {{ auth.user?.name }}</h2>
            <p class="mt-2 text-slate-600">
                Vous êtes connecté en tant que <strong>{{ auth.user?.profile_fr }}</strong>.
            </p>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Espace</p>
                    <p class="mt-1 text-lg font-semibold capitalize">{{ auth.workspace }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Environnement</p>
                    <p class="mt-1 text-lg font-semibold">{{ auth.user?.environment?.name ?? '—' }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Entité</p>
                    <p class="mt-1 text-lg font-semibold">{{ auth.user?.entity?.name ?? '—' }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Phase</p>
                    <p class="mt-1 text-lg font-semibold">1 — Fondations</p>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6">
            <h3 class="font-semibold text-emerald-900">Prochaines étapes</h3>
            <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-emerald-900">
                <li v-if="auth.workspace === 'controle'">Saisie et validation des évaluations de risques</li>
                <li v-if="auth.workspace === 'metier'">Consultation selon votre rôle métier</li>
                <li v-if="auth.user?.profile === 'super_admin'">Configuration globale des environnements</li>
                <li v-if="auth.user?.profile === 'admin'">Gestion de votre environnement et de vos utilisateurs</li>
                <li>Consultez les référentiels (échelles, familles de risques, matrice)</li>
            </ul>
        </section>
    </div>
</template>

<script setup>
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
</script>
