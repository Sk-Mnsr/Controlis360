<template>
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold">Utilisateurs</h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ isSuperAdmin ? 'Gestion globale des comptes' : 'Gestion des comptes de votre environnement' }}
            </p>
        </div>

        <RouterView />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const isSuperAdmin = computed(() => auth.user?.profile === 'super_admin');
</script>
