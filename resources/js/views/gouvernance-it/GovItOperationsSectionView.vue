<template>
    <div :class="embedded ? 'space-y-4' : 'space-y-6'">
        <section :class="embedded ? '' : 'rounded-2xl border border-slate-200 bg-white p-6 shadow-sm'">
            <div
                v-if="!embedded"
                class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
            >
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">GovStrat IT-R</p>
                    <h2 class="mt-1 text-xl font-semibold text-slate-900">{{ title }}</h2>
                </div>
                <RouterLink
                    :to="{ name: 'gouvernance-it.govstrat-itr' }"
                    class="text-sm font-medium text-slate-500 hover:text-slate-800"
                >
                    ← Retour à GovStrat IT-R
                </RouterLink>
            </div>
            <div v-else class="mb-4">
                <h3 class="text-lg font-semibold text-slate-900">{{ title }}</h3>
            </div>

            <p v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</p>

            <GovItWorkspaceHeader
                v-if="canAccess"
                :filiale="filiale"
                :filiale-code="filialeCode"
                :responsable="responsable"
                :team="team"
                :loading="loading"
                @add="onAdd"
            />

            <div class="mt-6">
                <GovItActivityBoard
                    v-if="!loading && canAccess"
                    ref="boardRef"
                    :module-slug="moduleSlug"
                    :owners="owners"
                />
            </div>
        </section>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../api/client';
import { useAuthStore } from '../../stores/auth';
import GovItWorkspaceHeader from '../../components/gouvernance-it/GovItWorkspaceHeader.vue';
import GovItActivityBoard from '../../components/gouvernance-it/GovItActivityBoard.vue';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    moduleSlug: {
        type: String,
        required: true,
    },
    embedded: {
        type: Boolean,
        default: false,
    },
});

const auth = useAuthStore();
const router = useRouter();

const loading = ref(true);
const error = ref('');
const filiale = ref('—');
const filialeCode = ref('');
const responsable = ref('—');
const team = ref('—');
const owners = ref([]);
const boardRef = ref(null);
const canAccess = ref(false);

function canAccessOperations() {
    return ['super_admin', 'admin', 'agent_it', 'responsable_it'].includes(auth.user?.profile);
}

async function loadContext() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await api.get('/gouvernance-it/context');
        const payload = data.data ?? data;
        filiale.value = payload.filiale ?? '—';
        filialeCode.value = payload.filiale_code ?? '';
        responsable.value = payload.responsable ?? '—';
        team.value = payload.team ?? '—';
        owners.value = payload.owners ?? [];
    } catch (err) {
        error.value = err.response?.data?.message
            ?? 'Impossible de charger le contexte filiale / équipe IT.';
    } finally {
        loading.value = false;
    }
}

function onAdd() {
    boardRef.value?.addEnsemble?.();
}

onMounted(async () => {
    if (!canAccessOperations()) {
        if (!props.embedded) {
            router.replace({ name: 'gouvernance-it.govstrat-itr' });
        }
        return;
    }

    canAccess.value = true;
    await loadContext();
});
</script>
