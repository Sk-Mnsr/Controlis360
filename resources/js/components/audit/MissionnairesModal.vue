<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
        @click.self="close"
    >
        <div class="flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
            <header class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <h3 class="text-lg font-semibold text-slate-900">Missionnaires</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Renseignez les missionnaires et leur responsable / équipe.
                </p>
            </header>

            <div class="flex-1 space-y-4 overflow-y-auto px-5 py-4 sm:px-6">
                <div
                    v-for="(row, index) in draft"
                    :key="row._key"
                    class="rounded-xl border border-slate-200 bg-slate-50/60 p-4"
                >
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-800">
                            Missionnaire {{ index + 1 }}
                        </p>
                        <button
                            v-if="draft.length > 1"
                            type="button"
                            class="text-xs font-medium text-red-600 hover:text-red-800"
                            @click="removeRow(index)"
                        >
                            Retirer
                        </button>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Nom *</label>
                            <input
                                v-model="row.nom"
                                type="text"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                                placeholder="Nom complet"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">E-mail (@) *</label>
                            <input
                                v-model="row.email"
                                type="email"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                                placeholder="prenom.nom@exemple.com"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Téléphone (num)</label>
                            <input
                                v-model="row.telephone"
                                type="tel"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                                placeholder="+221 …"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Poste</label>
                            <input
                                v-model="row.poste"
                                type="text"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                                placeholder="Fonction / poste"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Entité *</label>
                            <select
                                v-model="row.entite_type"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                            >
                                <option value="interne">Interne</option>
                                <option value="externe">Externe</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">
                                Responsable / équipe *
                            </label>
                            <input
                                v-model="row.responsable_equipe"
                                type="text"
                                list="missionnaire-responsable-options"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                                placeholder="Choisir ou saisir"
                            />
                        </div>
                    </div>
                </div>

                <datalist id="missionnaire-responsable-options">
                    <option v-for="option in responsableOptions" :key="option" :value="option" />
                </datalist>

                <button
                    type="button"
                    class="w-full rounded-xl border border-dashed border-slate-300 px-4 py-3 text-sm font-medium text-slate-700 hover:border-slate-400 hover:bg-slate-50"
                    @click="addRow"
                >
                    + Ajouter un missionnaire
                </button>

                <p v-if="localError" class="text-sm text-red-600">{{ localError }}</p>
            </div>

            <footer class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 sm:px-6">
                <button
                    type="button"
                    class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100"
                    @click="close"
                >
                    Annuler
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800"
                    @click="confirm"
                >
                    Valider
                </button>
            </footer>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    modelValue: { type: Array, default: () => [] },
    responsableOptions: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue', 'update:open', 'close']);

let keySeq = 1;

function emptyRow() {
    keySeq += 1;
    return {
        _key: keySeq,
        nom: '',
        email: '',
        telephone: '',
        poste: '',
        entite_type: 'interne',
        responsable_equipe: '',
    };
}

const draft = ref([emptyRow()]);
const localError = ref('');

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;
        localError.value = '';
        const source = Array.isArray(props.modelValue) ? props.modelValue : [];
        draft.value = source.length
            ? source.map((row) => ({
                _key: ++keySeq,
                nom: row.nom ?? '',
                email: row.email ?? '',
                telephone: row.telephone ?? '',
                poste: row.poste ?? '',
                entite_type: row.entite_type === 'externe' ? 'externe' : 'interne',
                responsable_equipe: row.responsable_equipe ?? '',
            }))
            : [emptyRow()];
    },
);

function addRow() {
    draft.value.push(emptyRow());
}

function removeRow(index) {
    draft.value.splice(index, 1);
}

function close() {
    emit('update:open', false);
    emit('close');
}

function confirm() {
    localError.value = '';
    const cleaned = draft.value
        .map((row) => ({
            nom: row.nom.trim(),
            email: row.email.trim(),
            telephone: row.telephone.trim(),
            poste: row.poste.trim(),
            entite_type: row.entite_type === 'externe' ? 'externe' : 'interne',
            responsable_equipe: row.responsable_equipe.trim(),
        }))
        .filter((row) => row.nom || row.email || row.telephone || row.poste || row.responsable_equipe);

    for (let i = 0; i < cleaned.length; i += 1) {
        const row = cleaned[i];
        const label = `Missionnaire #${i + 1}`;

        if (!row.nom) {
            localError.value = `${label} : le nom est obligatoire.`;
            return;
        }
        if (!row.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(row.email)) {
            localError.value = `${label} : un e-mail valide est obligatoire.`;
            return;
        }
        if (!row.responsable_equipe) {
            localError.value = `${label} : choisissez un responsable / une équipe.`;
            return;
        }
    }

    emit('update:modelValue', cleaned);
    close();
}
</script>
