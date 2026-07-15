<template>
    <div class="reception-page">
        <div v-if="loading" class="p-8 text-center text-sm text-slate-500">Chargement...</div>
        <div v-else-if="error && !fiche" class="m-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            {{ error }}
        </div>

        <template v-else-if="fiche">
            <div class="reception-toolbar">
                <RouterLink
                    :to="{ name: 'conformite.reporting.reception' }"
                    class="text-sm font-medium text-slate-600 hover:text-slate-900"
                >
                    ← Retour à la réception
                </RouterLink>
                <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-medium text-slate-700">
                    Lecture seule — {{ fiche.etabli_par || 'Département' }}
                </span>
            </div>

            <div class="reporting-form readonly">
                <div class="doc-header">
                    <div>
                        <h1>Fiche de Reporting Réglementaire</h1>
                        <p>Cartographie des obligations déclaratives — LBC/FT/FP</p>
                    </div>
                    <div class="doc-ref">N° fiche : <strong>{{ fiche.fiche_number || '—' }}</strong></div>
                </div>

                <div class="block">
                    <div class="band">
                        <div class="band-title">Type de reporting</div>
                        <div class="band-title gold">Destinataire du reporting</div>
                    </div>
                    <div class="band">
                        <div class="band-body grey">
                            <p class="field-label">Intitulé complet</p>
                            <p class="readonly-value">{{ fiche.type_reporting || '—' }}</p>
                        </div>
                        <div class="band-body gold-panel grey">
                            <p class="field-label">Destinataires</p>
                            <p class="readonly-value">{{ listText(fiche.destinataires) }}</p>
                        </div>
                    </div>
                </div>

                <div class="block">
                    <div class="band wide-only">
                        <div class="band-title">Références réglementaires</div>
                    </div>
                    <div class="band">
                        <div class="band-body grey">
                            <p class="field-label">Référence</p>
                            <p class="readonly-value">{{ fiche.reference || '—' }}</p>
                        </div>
                        <div class="pj-panel grey">
                            <p class="field-label">Pièces jointes</p>
                            <ul v-if="fiche.attachment_paths?.length" class="pj-list">
                                <li v-for="path in fiche.attachment_paths" :key="path">
                                    <button type="button" class="pj-file-link" @click="openAttachment(path)">
                                        {{ fileName(path) }}
                                    </button>
                                </li>
                            </ul>
                            <p v-else class="readonly-value">{{ fiche.pj_required ? 'Requise — aucune jointe' : 'Non requise' }}</p>
                        </div>
                    </div>
                </div>

                <div class="block">
                    <div class="triple">
                        <div><div class="band-title">Eléments</div></div>
                        <div><div class="band-title">Canal</div></div>
                        <div><div class="band-title">Périodicité</div></div>
                    </div>
                    <div class="triple">
                        <div class="band-body gold-panel grey">{{ listText(fiche.elements) }}</div>
                        <div class="band-body gold-panel grey">{{ listText(fiche.canals) }}</div>
                        <div class="band-body gold-panel grey">{{ listText(fiche.periodicites) }}</div>
                    </div>
                </div>

                <div class="block">
                    <div class="duo">
                        <div><div class="band-title">Déposant</div></div>
                        <div><div class="band-title">Etabli par</div></div>
                    </div>
                    <div class="duo">
                        <div class="band-body grey">{{ fiche.deposant || '—' }}</div>
                        <div class="band-body grey">{{ fiche.etabli_par || '—' }}</div>
                    </div>
                </div>

                <div class="block">
                    <div class="duo">
                        <div><div class="band-title">Date de validation interne</div></div>
                        <div><div class="band-title">Délai de transmission</div></div>
                    </div>
                    <div class="duo">
                        <div class="band-body grey">{{ formatDate(fiche.date_validation) }}</div>
                        <div class="band-body grey">{{ formatDate(fiche.delai_transmission) }}</div>
                    </div>
                </div>
            </div>

            <section class="contribution-panel">
                <div class="contribution-header">
                    <div>
                        <h3>Compléments du département</h3>
                        <p>Ajoutez les éléments de traitement demandés.</p>
                    </div>
                    <button
                        v-if="fiche.can_contribute"
                        type="button"
                        class="plus-btn"
                        title="Ajouter une ligne"
                        @click="addRow"
                    >
                        +
                    </button>
                </div>

                <div v-if="contributions.length" class="contribution-list">
                    <div
                        v-for="item in contributions"
                        :key="item.id"
                        class="contribution-card"
                    >
                        <p class="meta">
                            {{ item.creator?.name || 'Utilisateur' }}
                            <span v-if="item.created_at"> — {{ formatDateTime(item.created_at) }}</span>
                        </p>
                        <div class="grid">
                            <div>
                                <span class="field-label">Valeur</span>
                                <p>{{ item.valeur || '—' }}</p>
                            </div>
                            <div>
                                <span class="field-label">Date</span>
                                <p>{{ formatDate(item.date) }}</p>
                            </div>
                            <div class="full">
                                <span class="field-label">Contenu</span>
                                <p class="whitespace-pre-wrap">{{ item.contenu || '—' }}</p>
                            </div>
                            <div v-if="attachmentItems(item).length" class="full">
                                <span class="field-label">Pièces jointes</span>
                                <ul class="pj-list">
                                    <li v-for="(file, fileIndex) in attachmentItems(item)" :key="`${item.id}-${fileIndex}`">
                                        <button
                                            v-if="file.path"
                                            type="button"
                                            class="pj-file-link"
                                            @click="openAttachment(file.path)"
                                        >
                                            {{ file.nom || fileName(file.path) }}
                                        </button>
                                        <span v-else>{{ file.nom || '—' }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="rows.length" class="contribution-rows">
                    <div
                        v-for="(row, index) in rows"
                        :key="row.key"
                        class="contribution-editor"
                    >
                        <div class="editor-top">
                            <span class="field-label">Nouvelle ligne</span>
                            <button
                                v-if="rows.length > 1"
                                type="button"
                                class="remove-btn"
                                @click="removeRow(index)"
                            >
                                ×
                            </button>
                        </div>
                        <div class="editor-grid">
                            <div>
                                <label class="field-label">Valeur</label>
                                <input v-model="row.valeur" type="text" placeholder="Valeur">
                            </div>
                            <div>
                                <label class="field-label">Date</label>
                                <input v-model="row.date" type="date">
                            </div>
                            <div class="full">
                                <label class="field-label">Contenu</label>
                                <textarea v-model="row.contenu" rows="3" placeholder="Contenu" />
                            </div>
                            <div class="full attachments-block">
                                <div class="attachments-header">
                                    <span class="field-label">Pièces jointes</span>
                                    <button
                                        type="button"
                                        class="plus-btn small"
                                        title="Ajouter une pièce jointe"
                                        @click="addAttachmentSlot(index)"
                                    >
                                        +
                                    </button>
                                </div>
                                <div
                                    v-for="(slot, slotIndex) in row.attachments"
                                    :key="slot.key"
                                    class="attachment-slot"
                                >
                                    <div>
                                        <label class="field-label">Nom</label>
                                        <input
                                            v-model="slot.nom"
                                            type="text"
                                            placeholder="Nom de la pièce jointe"
                                        >
                                    </div>
                                    <div>
                                        <label class="field-label">Pièce jointe</label>
                                        <div class="file-row">
                                            <input
                                                type="file"
                                                @change="onFileSelected(index, slotIndex, $event)"
                                            >
                                            <button
                                                v-if="row.attachments.length > 1"
                                                type="button"
                                                class="remove-btn"
                                                title="Retirer cette pièce"
                                                @click="removeAttachmentSlot(index, slotIndex)"
                                            >
                                                ×
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="save-row">
                        <p v-if="saveError" class="error">{{ saveError }}</p>
                        <p v-if="saveSuccess" class="success">{{ saveSuccess }}</p>
                        <button
                            type="button"
                            class="save-btn"
                            :disabled="saving"
                            @click="saveRows"
                        >
                            {{ saving ? 'Enregistrement...' : 'Enregistrer les compléments' }}
                        </button>
                    </div>
                </div>

                <p v-else-if="!contributions.length" class="empty-hint">
                    Cliquez sur <strong>+</strong> pour renseigner valeur, contenu et autant de pièces jointes que nécessaire.
                </p>
            </section>
        </template>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../api/client';
import {
    attachmentFileName,
    downloadAttachment,
    isPreviewableAttachment,
    previewAttachment,
} from '../../utils/attachments';

const route = useRoute();
const loading = ref(true);
const saving = ref(false);
const error = ref('');
const saveError = ref('');
const saveSuccess = ref('');
const fiche = ref(null);
const rows = ref([]);
let rowKey = 1;
let attachmentKey = 1;

const contributions = computed(() => fiche.value?.contributions ?? []);

function listText(values) {
    if (!Array.isArray(values) || !values.length) return '—';
    return values.join(', ');
}

function formatDate(value) {
    if (!value) return '—';
    const date = String(value).slice(0, 10);
    const [year, month, day] = date.split('-');
    return year && month && day ? `${day}/${month}/${year}` : date;
}

function formatDateTime(value) {
    if (!value) return '';
    return new Date(value).toLocaleString('fr-FR');
}

function fileName(path) {
    return attachmentFileName(path);
}

function attachmentItems(item) {
    if (Array.isArray(item?.attachment_items) && item.attachment_items.length) {
        return item.attachment_items;
    }
    if (Array.isArray(item?.attachments) && item.attachments.length) {
        return item.attachments;
    }
    if (item?.attachment_path || item?.nom) {
        return [{ nom: item.nom, path: item.attachment_path }];
    }
    return [];
}

function extractFiche(payload) {
    const data = payload?.data ?? payload;
    return data?.data ?? data;
}

function createAttachmentSlot() {
    attachmentKey += 1;
    return { key: attachmentKey, nom: '', file: null };
}

function addRow() {
    rowKey += 1;
    rows.value.push({
        key: rowKey,
        valeur: '',
        contenu: '',
        date: todayIso(),
        attachments: [createAttachmentSlot()],
    });
}

function todayIso() {
    const now = new Date();
    const offsetMs = now.getTimezoneOffset() * 60 * 1000;
    return new Date(now.getTime() - offsetMs).toISOString().slice(0, 10);
}

function removeRow(index) {
    rows.value.splice(index, 1);
}

function addAttachmentSlot(rowIndex) {
    rows.value[rowIndex].attachments.push(createAttachmentSlot());
}

function removeAttachmentSlot(rowIndex, slotIndex) {
    const slots = rows.value[rowIndex].attachments;
    slots.splice(slotIndex, 1);
    if (!slots.length) {
        slots.push(createAttachmentSlot());
    }
}

function onFileSelected(rowIndex, slotIndex, event) {
    rows.value[rowIndex].attachments[slotIndex].file = event.target.files?.[0] ?? null;
}

async function openAttachment(path) {
    try {
        if (isPreviewableAttachment(path)) {
            await previewAttachment(path);
        } else {
            await downloadAttachment(path);
        }
    } catch {
        error.value = 'Impossible d\'ouvrir la pièce jointe.';
    }
}

async function loadFiche() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await api.get(`/regulatory-reporting-fiches/${route.params.id}`);
        fiche.value = extractFiche(data);
    } catch (err) {
        const payload = err.response?.data;
        error.value = payload?.message
            || Object.values(payload?.errors ?? payload?.data ?? {}).flat().join(' ')
            || 'Impossible de charger la fiche.';
        fiche.value = null;
    } finally {
        loading.value = false;
    }
}

async function saveRows() {
    if (!fiche.value?.can_contribute || !rows.value.length) return;

    saving.value = true;
    saveError.value = '';
    saveSuccess.value = '';

    try {
        for (const row of rows.value) {
            const fd = new FormData();
            if (row.valeur?.trim()) fd.append('valeur', row.valeur.trim());
            if (row.contenu?.trim()) fd.append('contenu', row.contenu.trim());
            if (row.date) fd.append('date', row.date);

            row.attachments.forEach((slot, slotIndex) => {
                fd.append(`attachment_names[${slotIndex}]`, slot.nom?.trim() || '');
                if (slot.file) {
                    fd.append(`attachment_files[${slotIndex}]`, slot.file);
                }
            });

            await api.post(`/regulatory-reporting-fiches/${fiche.value.id}/contributions`, fd);
        }

        rows.value = [];
        saveSuccess.value = 'Compléments enregistrés.';
        await loadFiche();
    } catch (err) {
        const payload = err.response?.data;
        saveError.value = payload?.message
            || Object.values(payload?.errors ?? payload?.data ?? {}).flat().join(' ')
            || 'Erreur lors de l\'enregistrement.';
    } finally {
        saving.value = false;
    }
}

onMounted(loadFiche);
</script>

<style scoped>
.reception-page {
    --red: #a3181f;
    --red-dark: #7d1218;
    --gold: #b9902b;
    --gold-light: #f4e9d3;
    --grey: #6b6b6b;
    --ink: #2b2320;
    --paper: #fbf9f5;
    --line: #ddd3c2;
    background: #e9e3d8;
    min-height: 100%;
    padding: 16px;
    font-family: Georgia, 'Times New Roman', serif;
    color: var(--ink);
}

.reception-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
    font-family: Arial, sans-serif;
}

.reporting-form {
    background: var(--paper);
    border: 1px solid var(--line);
}

.doc-header {
    padding: 14px 18px 12px;
    border-bottom: 3px solid var(--red);
    display: flex;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}

.doc-header h1 {
    margin: 0;
    font-size: 1.15rem;
    color: var(--red-dark);
}

.doc-header p,
.doc-ref {
    margin: 2px 0 0;
    font-family: Arial, sans-serif;
    font-size: 0.72rem;
    color: var(--grey);
}

.block { border-top: 1px solid var(--line); }
.band {
    display: grid;
    grid-template-columns: minmax(0, 1.7fr) minmax(240px, 1fr);
}
.band.wide-only { grid-template-columns: 1fr; }
.band-title {
    background: var(--red);
    color: #fff;
    font-family: Arial, sans-serif;
    font-weight: 700;
    font-size: 0.78rem;
    text-align: center;
    padding: 7px 10px;
}
.band-title.gold { background: var(--gold); }
.band-body { padding: 10px 14px; }
.band-body.gold-panel { background: var(--gold-light); }
.band-body.grey,
.pj-panel.grey {
    background: #ececec;
    color: #5a5a5a;
}
.triple, .duo { display: grid; }
.triple { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.duo { grid-template-columns: 1fr 1fr; }
.triple > div, .duo > div { border-left: 1px solid var(--line); }
.triple > div:first-child, .duo > div:first-child { border-left: none; }
.field-label {
    display: block;
    font-family: Arial, sans-serif;
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--grey);
    margin-bottom: 4px;
}
.readonly-value {
    margin: 0;
    white-space: pre-wrap;
    font-size: 0.9rem;
}
.pj-panel { padding: 10px 12px; }
.pj-list { margin: 0; padding: 0; list-style: none; }
.pj-file-link {
    border: none;
    background: transparent;
    color: var(--red-dark);
    font-family: Arial, sans-serif;
    font-size: 0.78rem;
    cursor: pointer;
    padding: 0;
    text-decoration: underline;
}

.contribution-panel {
    margin-top: 16px;
    background: #fff;
    border: 1px solid var(--line);
    padding: 16px;
    font-family: Arial, sans-serif;
}

.contribution-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
}

.contribution-header h3 {
    margin: 0;
    font-size: 1rem;
    color: var(--ink);
}

.contribution-header p {
    margin: 4px 0 0;
    font-size: 0.8rem;
    color: var(--grey);
}

.plus-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: 1px solid #b9902b;
    background: #f4e9d3;
    color: #7d1218;
    font-size: 1.25rem;
    font-weight: 700;
    cursor: pointer;
}

.plus-btn.small {
    width: 28px;
    height: 28px;
    font-size: 1rem;
}

.attachments-block {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.attachments-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.attachment-slot {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    padding: 10px;
    border: 1px dashed #ddd3c2;
    border-radius: 8px;
    background: #fff;
}

.file-row {
    display: flex;
    align-items: center;
    gap: 6px;
}

.file-row input[type='file'] {
    flex: 1;
}

.contribution-list,
.contribution-rows {
    margin-top: 14px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.contribution-card,
.contribution-editor {
    border: 1px solid #ddd3c2;
    border-radius: 8px;
    background: #f8f7f4;
    padding: 12px;
}

.contribution-card .meta {
    margin: 0 0 8px;
    font-size: 0.72rem;
    color: var(--grey);
}

.grid,
.editor-grid {
    display: grid;
    gap: 10px;
    grid-template-columns: 1fr 1fr;
}

.grid .full,
.editor-grid .full {
    grid-column: 1 / -1;
}

.editor-top {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.contribution-editor input,
.contribution-editor textarea {
    width: 100%;
    border: 1px solid #ddd3c2;
    border-radius: 6px;
    padding: 8px 10px;
    font-size: 0.875rem;
    background: #fff;
}

.remove-btn {
    border: none;
    background: #c94b4b;
    color: #fff;
    width: 28px;
    border-radius: 4px;
    cursor: pointer;
}

.save-row {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.save-btn {
    border: 1px solid var(--red);
    background: var(--red);
    color: #fff;
    border-radius: 4px;
    padding: 10px 18px;
    font-size: 0.85rem;
    cursor: pointer;
}

.save-btn:disabled { opacity: 0.6; }
.error { color: #a3181f; font-size: 0.8rem; margin: 0; }
.success { color: #047857; font-size: 0.8rem; margin: 0; }
.empty-hint {
    margin: 16px 0 0;
    font-size: 0.85rem;
    color: var(--grey);
}

@media (max-width: 900px) {
    .band, .triple, .duo, .grid, .editor-grid, .attachment-slot {
        grid-template-columns: 1fr;
    }
    .triple > div, .duo > div {
        border-left: none;
        border-top: 1px solid var(--line);
    }
}
</style>
