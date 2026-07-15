<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
        @click.self="$emit('close')"
    >
        <div class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
            <div class="shrink-0 border-b border-slate-200 px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Importer des missions</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Chargez un fichier Excel (.xlsx) avec les mêmes champs que le formulaire de création.
                            Les entités et responsables sont affectés automatiquement.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                        @click="$emit('close')"
                    >
                        ✕
                    </button>
                </div>
            </div>

            <div class="flex-1 space-y-5 overflow-y-auto p-6">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-medium text-slate-800">Étape 1 — Télécharger le modèle</p>
                    <p class="mt-1 text-xs text-slate-500">
                        Le modèle inclut des listes déroulantes pour le type de mission, le risque, la priorité et le statut
                        (mêmes valeurs que le formulaire). Renseignez aussi les codes entités (séparés par ;), les dates et le libellé recommandation.
                    </p>
                    <button
                        type="button"
                        class="mt-3 rounded-lg border border-emerald-300 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50"
                        :disabled="downloading"
                        @click="downloadTemplate"
                    >
                        {{ downloading ? 'Téléchargement...' : 'Télécharger le modèle Excel' }}
                    </button>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-sm font-medium text-slate-800">Étape 2 — Importer le fichier</p>
                    <input
                        ref="fileInput"
                        type="file"
                        accept=".xlsx,.xls"
                        class="mt-3 block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-700 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-emerald-800"
                        @change="onFileChange"
                    />
                    <p v-if="selectedFile" class="mt-2 text-xs text-slate-500">Fichier sélectionné : {{ selectedFile.name }}</p>
                </div>

                <div v-if="error" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    {{ error }}
                </div>

                <div v-if="result" class="space-y-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                    <p class="text-sm font-semibold text-emerald-900">
                        Import terminé : {{ result.imported }} mission(s) créée(s), {{ result.failed }} erreur(s).
                    </p>

                    <div v-if="result.missions?.length" class="space-y-2">
                        <p class="text-xs font-medium uppercase tracking-wide text-emerald-800">Missions créées</p>
                        <ul class="max-h-40 space-y-2 overflow-y-auto text-sm text-emerald-900">
                            <li
                                v-for="item in result.missions"
                                :key="item.reference"
                                class="rounded-lg bg-white/80 px-3 py-2"
                            >
                                <span class="font-semibold">{{ item.reference }}</span> — {{ item.title }}
                                <span class="block text-xs text-slate-600">
                                    Entités : {{ (item.entities ?? []).join(', ') || '—' }}
                                    · Notifiés : {{ (item.notified ?? []).join(', ') || 'aucun responsable' }}
                                </span>
                            </li>
                        </ul>
                    </div>

                    <div v-if="result.errors?.length" class="space-y-2">
                        <p class="text-xs font-medium uppercase tracking-wide text-red-800">Erreurs par ligne</p>
                        <ul class="max-h-32 space-y-1 overflow-y-auto text-sm text-red-800">
                            <li v-for="(err, index) in result.errors" :key="index">
                                Ligne {{ err.row }} : {{ err.message }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="flex shrink-0 justify-end gap-3 border-t border-slate-200 px-6 py-4">
                <button
                    type="button"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    @click="$emit('close')"
                >
                    Fermer
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800 disabled:opacity-50"
                    :disabled="!selectedFile || importing"
                    @click="submitImport"
                >
                    {{ importing ? 'Import en cours...' : 'Lancer l\'import' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import api from '../../api/client';

const props = defineProps({
    open: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'imported']);

const fileInput = ref(null);
const selectedFile = ref(null);
const downloading = ref(false);
const importing = ref(false);
const error = ref('');
const result = ref(null);

watch(() => props.open, (isOpen) => {
    if (!isOpen) {
        resetState();
    }
});

function resetState() {
    selectedFile.value = null;
    error.value = '';
    result.value = null;
    importing.value = false;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function onFileChange(event) {
    const [file] = event.target.files ?? [];
    selectedFile.value = file ?? null;
    error.value = '';
    result.value = null;
}

async function downloadTemplate() {
    downloading.value = true;
    error.value = '';
    try {
        const { data } = await api.get('/missions/import/template', { responseType: 'blob' });
        const url = window.URL.createObjectURL(new Blob([data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'modele-import-missions.xlsx');
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (err) {
        error.value = 'Impossible de télécharger le modèle.';
    } finally {
        downloading.value = false;
    }
}

async function submitImport() {
    if (!selectedFile.value) return;

    importing.value = true;
    error.value = '';
    result.value = null;

    const formData = new FormData();
    formData.append('file', selectedFile.value);

    try {
        const { data } = await api.post('/missions/import', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        result.value = data?.data ?? data;
        if (result.value?.imported > 0) {
            emit('imported');
        }
    } catch (err) {
        const message = err.response?.data?.message;
        if (Array.isArray(message)) {
            error.value = message[0];
        } else if (typeof message === 'string') {
            error.value = message;
        } else {
            error.value = 'Import impossible. Vérifiez le format du fichier.';
        }
    } finally {
        importing.value = false;
    }
}
</script>
