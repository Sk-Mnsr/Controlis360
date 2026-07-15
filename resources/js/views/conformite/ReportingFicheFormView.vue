<template>
    <div class="reporting-page">
        <form class="reporting-form" @submit.prevent="saveFiche">
            <div class="form-scroll">
            <div class="doc-header">
                <div>
                    <h1>Fiche de Reporting Réglementaire</h1>
                    <p>Cartographie des obligations déclaratives — LBC/FT/FP</p>
                </div>
                <div class="doc-ref">
                    N° fiche :
                    <input
                        v-model="form.fiche_number"
                        type="text"
                        class="fiche-id-input"
                        placeholder="ex : LBC-2026-01"
                    >
                </div>
            </div>

            <div class="block">
                <div class="band">
                    <div class="band-title">Type de reporting</div>
                    <div class="band-title gold">Destinataire du reporting</div>
                </div>
                <div class="band">
                    <div class="band-body">
                        <label class="field-label" for="typeReporting">Intitulé complet du rapport / de la déclaration</label>
                        <textarea
                            id="typeReporting"
                            v-model="form.type_reporting"
                            placeholder="Ex : Le rapport Annuel de lutte contre le Blanchiment de Capitaux, le Financement du terrorisme et le Financement de la Prolifération"
                        />
                    </div>
                    <div class="band-body gold-panel">
                        <div class="tag-input-list">
                            <div
                                v-for="(value, index) in form.destinataires"
                                :key="`destinataire-${index}`"
                                class="tag-row"
                            >
                                <select v-model="form.destinataires[index]">
                                    <option value="">Destinataire...</option>
                                    <option v-for="option in DESTINATAIRES" :key="option" :value="option">
                                        {{ option }}
                                    </option>
                                </select>
                                <button type="button" class="remove-row" @click="removeListItem('destinataires', index)">×</button>
                            </div>
                        </div>
                        <button type="button" class="add-row-btn" @click="addListItem('destinataires')">
                            + ajouter un destinataire
                        </button>
                    </div>
                </div>
            </div>

            <div class="block">
                <div class="band wide-only">
                    <div class="band-title">Références réglementaires</div>
                </div>
                <div class="band">
                    <div class="band-body">
                        <label class="field-label" for="reference">Article, instruction, loi, décision...</label>
                        <textarea
                            id="reference"
                            v-model="form.reference"
                            placeholder="Ex : Art. 12 de l'Instruction n°001-03-2025 du 18 mars 2025 portant modalités de mise en œuvre par les IF de leurs obligations en matière d'organisation de contrôle interne et de conformité"
                        />
                    </div>
                    <div class="pj-panel">
                        <div class="pj-content">
                            <label class="pj-check">
                                <input v-model="form.pj_required" type="checkbox">
                                Pièce jointe requise (pj)
                            </label>

                            <div v-if="existingAttachments.length" class="pj-existing">
                                <div
                                    v-for="path in existingAttachments"
                                    :key="path"
                                    class="pj-file-row"
                                >
                                    <button
                                        type="button"
                                        class="pj-file-link"
                                        @click="openAttachment(path)"
                                    >
                                        {{ fileName(path) }}
                                    </button>
                                    <button
                                        type="button"
                                        class="remove-row"
                                        title="Retirer"
                                        @click="removeExistingAttachment(path)"
                                    >
                                        ×
                                    </button>
                                </div>
                            </div>

                            <div class="pj-slots">
                                <div
                                    v-for="(slot, index) in attachmentSlots"
                                    :key="slot.key"
                                    class="pj-file-row"
                                >
                                    <input
                                        type="file"
                                        class="pj-file-input"
                                        @change="onAttachmentSelected(index, $event)"
                                    >
                                    <button
                                        v-if="attachmentSlots.length > 1"
                                        type="button"
                                        class="remove-row"
                                        @click="removeAttachmentSlot(index)"
                                    >
                                        ×
                                    </button>
                                </div>
                            </div>

                            <button type="button" class="add-row-btn" @click="addAttachmentSlot">
                                + ajouter une pièce jointe
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="block">
                <div class="triple">
                    <div><div class="band-title">Eléments de reporting</div></div>
                    <div><div class="band-title">Canal de transmission</div></div>
                    <div><div class="band-title">Périodicité</div></div>
                </div>
                <div class="triple">
                    <div class="band-body gold-panel">
                        <div class="tag-input-list">
                            <div
                                v-for="(value, index) in form.elements"
                                :key="`element-${index}`"
                                class="tag-row"
                            >
                                <select v-model="form.elements[index]">
                                    <option value="">Elément...</option>
                                    <option v-for="option in ELEMENTS" :key="option" :value="option">
                                        {{ option }}
                                    </option>
                                </select>
                                <button type="button" class="remove-row" @click="removeListItem('elements', index)">×</button>
                            </div>
                        </div>
                        <button type="button" class="add-row-btn" @click="addListItem('elements')">
                            + ajouter un élément
                        </button>
                    </div>
                    <div class="band-body gold-panel">
                        <div class="tag-input-list">
                            <div
                                v-for="(value, index) in form.canals"
                                :key="`canal-${index}`"
                                class="tag-row"
                            >
                                <select v-model="form.canals[index]">
                                    <option value="">Canal...</option>
                                    <option v-for="option in CANALS" :key="option" :value="option">
                                        {{ option }}
                                    </option>
                                </select>
                                <button type="button" class="remove-row" @click="removeListItem('canals', index)">×</button>
                            </div>
                        </div>
                        <button type="button" class="add-row-btn" @click="addListItem('canals')">
                            + ajouter un canal
                        </button>
                    </div>
                    <div class="band-body gold-panel">
                        <div class="tag-input-list">
                            <div
                                v-for="(value, index) in form.periodicites"
                                :key="`periodicite-${index}`"
                                class="tag-row"
                            >
                                <select v-model="form.periodicites[index]">
                                    <option value="">Périodicité...</option>
                                    <option v-for="option in PERIODICITES" :key="option" :value="option">
                                        {{ option }}
                                    </option>
                                </select>
                                <button type="button" class="remove-row" @click="removeListItem('periodicites', index)">×</button>
                            </div>
                        </div>
                        <button type="button" class="add-row-btn" @click="addListItem('periodicites')">
                            + ajouter une périodicité
                        </button>
                    </div>
                </div>
            </div>

            <div class="block">
                <div class="duo">
                    <div><div class="band-title">Déposant</div></div>
                    <div><div class="band-title">Etabli par</div></div>
                </div>
                <div class="duo">
                    <div class="band-body">
                        <select v-model="form.deposant_entity_id">
                            <option value="">Sélectionner...</option>
                            <option
                                v-for="entity in departments"
                                :key="`deposant-${entity.id}`"
                                :value="String(entity.id)"
                            >
                                {{ entity.name }}
                            </option>
                        </select>
                    </div>
                    <div class="band-body">
                        <select v-model="form.etabli_par_entity_id">
                            <option value="">Sélectionner...</option>
                            <option
                                v-for="entity in departments"
                                :key="`etabli-${entity.id}`"
                                :value="String(entity.id)"
                            >
                                {{ entity.name }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="block">
                <div class="duo">
                    <div><div class="band-title">Date de validation interne</div></div>
                    <div><div class="band-title">Délai de transmission</div></div>
                </div>
                <div class="duo">
                    <div class="band-body">
                        <input v-model="form.date_validation" type="date">
                    </div>
                    <div class="band-body">
                        <input v-model="form.delai_transmission" type="date">
                    </div>
                </div>
            </div>

            <section v-if="contributions.length" class="department-responses">
                <div class="responses-header">
                    <h3>Réponses du département</h3>
                    <p>Compléments saisis par le responsable « Établi par ».</p>
                </div>
                <div class="responses-list">
                    <div
                        v-for="item in contributions"
                        :key="item.id"
                        class="response-card"
                    >
                        <p class="response-meta">
                            {{ item.creator?.name || 'Utilisateur' }}
                            <span v-if="item.created_at"> — {{ formatDateTime(item.created_at) }}</span>
                        </p>
                        <div class="response-grid">
                            <div>
                                <span class="field-label">Valeur</span>
                                <p>{{ item.valeur || '—' }}</p>
                            </div>
                            <div>
                                <span class="field-label">Date</span>
                                <p>{{ formatContributionDate(item.date) }}</p>
                            </div>
                            <div class="full">
                                <span class="field-label">Contenu</span>
                                <p class="whitespace-pre-wrap">{{ item.contenu || '—' }}</p>
                            </div>
                            <div v-if="attachmentItems(item).length" class="full">
                                <span class="field-label">Pièces jointes</span>
                                <ul class="response-pj-list">
                                    <li
                                        v-for="(file, fileIndex) in attachmentItems(item)"
                                        :key="`${item.id}-${fileIndex}`"
                                    >
                                        <button
                                            v-if="file.path"
                                            type="button"
                                            class="response-pj-link"
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
            </section>
            </div>

            <div class="form-footer">
                <p v-if="statusMessage" class="status-msg">{{ statusMessage }}</p>
                <p v-if="errorMessage" class="status-msg error">{{ errorMessage }}</p>
                <div class="footer-actions">
                    <button type="submit" class="btn" :disabled="saving">
                        {{ saving ? 'Enregistrement...' : (isEdit ? 'Mettre à jour' : 'Enregistrer') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../api/client';
import { useAuthStore } from '../../stores/auth';
import {
    attachmentFileName,
    downloadAttachment,
    previewAttachment,
    isPreviewableAttachment,
} from '../../utils/attachments';

const DESTINATAIRES = [
    'BCEAO',
    'COBA',
    'DRS',
    'CENTIF / ANIF',
    'Ministère des Finances',
    'Autre régulateur',
];

const ELEMENTS = [
    'Rapport',
    'Rapport global',
    'Programme',
    'Déclaration',
    'Compte-rendu',
    'Etat statistique',
];

const CANALS = [
    'Support électronique',
    'Support physique',
    'Support éléctronique ou physique',
    'Courrier électronique sécurisé',
    'Plateforme dédiée',
];

const PERIODICITES = [
    'Ponctuelle',
    'Mensuelle',
    'Trimestrielle',
    'Semestrielle',
    'Annuelle',
];

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const saving = ref(false);
const loading = ref(false);
const statusMessage = ref('');
const errorMessage = ref('');
const existingAttachments = ref([]);
const attachmentSlots = ref([{ key: 1, file: null }]);
const departments = ref([]);
const contributions = ref([]);
let attachmentSlotKey = 1;

const isEdit = computed(() => Boolean(route.params.id));

const form = reactive({
    fiche_number: '',
    type_reporting: '',
    destinataires: [''],
    reference: '',
    pj_required: false,
    elements: [''],
    canals: [''],
    periodicites: [''],
    deposant_entity_id: '',
    etabli_par_entity_id: '',
    date_validation: '',
    delai_transmission: '',
});

watch(
    [existingAttachments, attachmentSlots],
    () => {
        const hasFiles = existingAttachments.value.length > 0
            || attachmentSlots.value.some((slot) => slot.file);
        if (hasFiles) {
            form.pj_required = true;
        }
    },
    { deep: true },
);

function ensureList(values) {
    if (Array.isArray(values) && values.length) {
        return [...values];
    }

    return [''];
}

function addListItem(field) {
    form[field].push('');
}

function removeListItem(field, index) {
    if (form[field].length <= 1) {
        form[field][0] = '';
        return;
    }

    form[field].splice(index, 1);
}

function cleanList(values) {
    return values.map((value) => String(value ?? '').trim()).filter(Boolean);
}

function fileName(path) {
    return attachmentFileName(path);
}

function formatDateTime(value) {
    if (!value) return '';
    return new Date(value).toLocaleString('fr-FR');
}

function formatContributionDate(value) {
    if (!value) return '—';
    const date = String(value).slice(0, 10);
    const [year, month, day] = date.split('-');
    return year && month && day ? `${day}/${month}/${year}` : date;
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

async function openAttachment(path) {
    try {
        if (isPreviewableAttachment(path)) {
            await previewAttachment(path);
        } else {
            await downloadAttachment(path);
        }
    } catch {
        errorMessage.value = 'Impossible d\'ouvrir la pièce jointe.';
    }
}

function addAttachmentSlot() {
    attachmentSlotKey += 1;
    attachmentSlots.value.push({ key: attachmentSlotKey, file: null });
}

function removeAttachmentSlot(index) {
    attachmentSlots.value.splice(index, 1);
    if (!attachmentSlots.value.length) {
        addAttachmentSlot();
    }
}

function onAttachmentSelected(index, event) {
    attachmentSlots.value[index].file = event.target.files?.[0] ?? null;
}

function removeExistingAttachment(path) {
    existingAttachments.value = existingAttachments.value.filter((item) => item !== path);
}

function selectedAttachmentFiles() {
    return attachmentSlots.value.map((slot) => slot.file).filter(Boolean);
}

function appendList(fd, key, values) {
    const cleaned = cleanList(values);
    // JSON string is more reliable than key[] with multipart FormData in Laravel.
    fd.append(key, JSON.stringify(cleaned));
}

function buildFormData() {
    const fd = new FormData();
    const append = (key, value) => {
        if (value !== null && value !== undefined && value !== '') {
            fd.append(key, value);
        }
    };

    append('fiche_number', form.fiche_number.trim());
    append('type_reporting', form.type_reporting.trim());
    append('reference', form.reference.trim());
    append('pj_required', form.pj_required ? '1' : '0');
    append('deposant_entity_id', form.deposant_entity_id);
    append('etabli_par_entity_id', form.etabli_par_entity_id);
    append('date_validation', form.date_validation);
    append('delai_transmission', form.delai_transmission);

    appendList(fd, 'destinataires', form.destinataires);
    appendList(fd, 'elements', form.elements);
    appendList(fd, 'canals', form.canals);
    appendList(fd, 'periodicites', form.periodicites);

    existingAttachments.value.forEach((path) => fd.append('keep_attachment_paths[]', path));
    if (isEdit.value) {
        fd.append('attachments_synced', '1');
    }
    selectedAttachmentFiles().forEach((file) => fd.append('attachments[]', file));

    return fd;
}

function extractError(err) {
    const data = err.response?.data;
    if (!data) {
        return 'Erreur lors de l\'enregistrement.';
    }

    const errors = data.errors ?? data.data;
    if (errors && typeof errors === 'object') {
        return Object.values(errors).flat().join(' ');
    }

    return data.message ?? 'Erreur lors de l\'enregistrement.';
}

function extractFiche(payload) {
    const data = payload?.data ?? payload;
    return data?.data ?? data;
}

function resetAttachmentSlots() {
    attachmentSlotKey = 1;
    attachmentSlots.value = [{ key: 1, file: null }];
}

function extractList(payload) {
    if (Array.isArray(payload?.data?.data)) return payload.data.data;
    if (Array.isArray(payload?.data)) return payload.data;
    if (Array.isArray(payload)) return payload;
    return [];
}

async function loadDepartments() {
    try {
        const environmentId = auth.user?.environment_ids?.[0];
        let items = [];

        if (environmentId) {
            const { data } = await api.get(`/entities/by-environment/${environmentId}`);
            items = extractList(data);
        } else {
            const { data } = await api.get('/referentials/entities-departments');
            items = extractList(data);
        }

        departments.value = items
            .filter((entity) => entity.type === 'department' || entity.type_fr === 'Département')
            .sort((a, b) => String(a.name).localeCompare(String(b.name), 'fr'));
    } catch (err) {
        console.error('Impossible de charger les départements', err);
        departments.value = [];
        errorMessage.value = 'Impossible de charger la liste des départements.';
    }
}

async function loadFiche() {
    if (!isEdit.value) {
        return;
    }

    loading.value = true;
    errorMessage.value = '';

    try {
        const { data } = await api.get(`/regulatory-reporting-fiches/${route.params.id}`);
        const fiche = extractFiche(data);

        form.fiche_number = fiche.fiche_number ?? '';
        form.type_reporting = fiche.type_reporting ?? '';
        form.destinataires = ensureList(fiche.destinataires);
        form.reference = fiche.reference ?? '';
        form.pj_required = Boolean(fiche.pj_required);
        form.elements = ensureList(fiche.elements);
        form.canals = ensureList(fiche.canals);
        form.periodicites = ensureList(fiche.periodicites);
        form.deposant_entity_id = fiche.deposant_entity_id ? String(fiche.deposant_entity_id) : '';
        form.etabli_par_entity_id = fiche.etabli_par_entity_id ? String(fiche.etabli_par_entity_id) : '';
        form.date_validation = fiche.date_validation?.slice?.(0, 10) ?? fiche.date_validation ?? '';
        form.delai_transmission = fiche.delai_transmission?.slice?.(0, 10) ?? fiche.delai_transmission ?? '';
        existingAttachments.value = [...(fiche.attachment_paths ?? [])];
        contributions.value = Array.isArray(fiche.contributions) ? fiche.contributions : [];
        resetAttachmentSlots();
    } catch (err) {
        errorMessage.value = extractError(err);
    } finally {
        loading.value = false;
    }
}

async function saveFiche() {
    saving.value = true;
    statusMessage.value = '';
    errorMessage.value = '';

    try {
        if (isEdit.value) {
            const { data } = await api.post(
                `/regulatory-reporting-fiches/${route.params.id}`,
                buildFormData(),
            );
            const fiche = extractFiche(data);
            existingAttachments.value = [...(fiche.attachment_paths ?? [])];
            contributions.value = Array.isArray(fiche.contributions) ? fiche.contributions : [];
            resetAttachmentSlots();
            statusMessage.value = 'Fiche mise à jour avec succès.';
        } else {
            const { data } = await api.post('/regulatory-reporting-fiches', buildFormData());
            const fiche = extractFiche(data);
            statusMessage.value = 'Fiche enregistrée et transmise au département « Établi par ».';
            if (fiche?.id) {
                setTimeout(() => {
                    router.push({ name: 'conformite.reporting.history' });
                }, 1000);
            }
        }
    } catch (err) {
        errorMessage.value = extractError(err);
    } finally {
        saving.value = false;
    }
}

onMounted(async () => {
    await loadDepartments();
    await loadFiche();
});
</script>

<style scoped>
.reporting-page {
    --red: #a3181f;
    --red-dark: #7d1218;
    --gold: #b9902b;
    --gold-light: #f4e9d3;
    --grey: #6b6b6b;
    --ink: #2b2320;
    --paper: #fbf9f5;
    --line: #ddd3c2;
    margin: 0;
    padding: 0;
    background: #e9e3d8;
    font-family: Georgia, 'Times New Roman', serif;
    color: var(--ink);
    height: 100%;
    min-height: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.reporting-form {
    width: 100%;
    max-width: none;
    margin: 0;
    background: var(--paper);
    border: none;
    border-left: 1px solid var(--line);
    box-shadow: none;
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.form-scroll {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
}

.form-footer {
    flex-shrink: 0;
    border-top: 1px solid var(--line);
    background: var(--paper);
}

.department-responses {
    border-top: 1px solid var(--line);
    background: #f3f1ec;
    padding: 14px 20px;
    font-family: Arial, sans-serif;
}

.responses-header h3 {
    margin: 0;
    font-size: 0.95rem;
    color: var(--red-dark);
}

.responses-header p {
    margin: 4px 0 0;
    font-size: 0.75rem;
    color: var(--grey);
}

.responses-list {
    margin-top: 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.response-card {
    border: 1px solid var(--line);
    border-radius: 8px;
    background: #fff;
    padding: 12px;
}

.response-meta {
    margin: 0 0 8px;
    font-size: 0.72rem;
    color: var(--grey);
}

.response-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.response-grid .full {
    grid-column: 1 / -1;
}

.response-grid p {
    margin: 0;
    font-size: 0.88rem;
    color: var(--ink);
}

.response-pj-list {
    margin: 0;
    padding: 0;
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.response-pj-link {
    border: none;
    background: transparent;
    color: var(--red-dark);
    font-size: 0.78rem;
    cursor: pointer;
    padding: 0;
    text-decoration: underline;
    text-align: left;
}

@media (max-width: 900px) {
    .response-grid {
        grid-template-columns: 1fr;
    }
}

.doc-header {
    padding: 12px 20px 10px;
    border-bottom: 3px solid var(--red);
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 12px;
    flex-wrap: wrap;
}

.doc-header h1 {
    margin: 0;
    font-size: 1.15rem;
    letter-spacing: 0.03em;
    color: var(--red-dark);
}

.doc-header p {
    margin: 2px 0 0;
    font-family: Arial, sans-serif;
    font-size: 0.72rem;
    color: var(--grey);
}

.doc-ref {
    font-family: Arial, sans-serif;
    font-size: 0.72rem;
    color: var(--grey);
    text-align: right;
}

.fiche-id-input {
    width: 130px;
    display: inline-block;
    font-size: 0.72rem;
    padding: 4px 6px;
}

.block {
    border-top: 1px solid var(--line);
}

.block:first-of-type {
    border-top: none;
}

.band {
    display: grid;
    grid-template-columns: minmax(0, 1.7fr) minmax(280px, 1fr);
}

.band.wide-only {
    grid-template-columns: 1fr;
}

.band-title {
    background: var(--red);
    color: #fff;
    font-family: Arial, sans-serif;
    font-weight: 700;
    font-size: 0.78rem;
    letter-spacing: 0.03em;
    text-align: center;
    padding: 7px 10px;
}

.band-title.gold {
    background: var(--gold);
}

.band-body {
    padding: 10px 14px;
}

.band-body.gold-panel {
    background: var(--gold-light);
    padding: 10px 12px;
}

textarea,
input[type='text'],
input[type='date'],
select {
    width: 100%;
    font-family: inherit;
    font-size: 0.88rem;
    color: var(--ink);
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 3px;
    padding: 7px 9px;
}

textarea {
    min-height: 52px;
    line-height: 1.35;
    resize: vertical;
}

select {
    font-size: 0.85rem;
    padding: 6px 8px;
}

.field-label {
    display: block;
    font-family: Arial, sans-serif;
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--grey);
    margin-bottom: 4px;
}

.tag-input-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.tag-row {
    display: flex;
    gap: 6px;
}

.tag-row select {
    flex: 1;
}

.remove-row {
    border: none;
    background: #c94b4b;
    color: #fff;
    width: 28px;
    border-radius: 3px;
    cursor: pointer;
    font-size: 0.9rem;
    line-height: 1;
    flex-shrink: 0;
}

.add-row-btn {
    margin-top: 6px;
    border: 1px dashed var(--gold);
    background: transparent;
    color: var(--gold);
    font-family: Arial, sans-serif;
    font-size: 0.7rem;
    padding: 4px 8px;
    border-radius: 3px;
    cursor: pointer;
}

.add-row-btn:hover {
    background: var(--gold-light);
}

.pj-panel {
    background: #efefef;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 10px 12px;
}

.pj-content {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.pj-check {
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: Arial, sans-serif;
    font-size: 0.78rem;
    color: var(--ink);
    cursor: pointer;
}

.pj-existing,
.pj-slots {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.pj-file-row {
    display: flex;
    align-items: center;
    gap: 6px;
}

.pj-file-input {
    flex: 1;
    font-family: Arial, sans-serif;
    font-size: 0.7rem;
    padding: 3px;
    border: 1px solid var(--line);
    border-radius: 3px;
    background: #fff;
}

.pj-file-link {
    flex: 1;
    text-align: left;
    border: 1px solid var(--line);
    background: #fff;
    border-radius: 3px;
    padding: 5px 8px;
    font-family: Arial, sans-serif;
    font-size: 0.7rem;
    color: var(--red-dark);
    cursor: pointer;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.pj-file-link:hover {
    text-decoration: underline;
}

.triple {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.triple > div {
    border-left: 1px solid var(--line);
}

.triple > div:first-child {
    border-left: none;
}

.duo {
    display: grid;
    grid-template-columns: 1fr 1fr;
}

.duo > div {
    border-left: 1px solid var(--line);
}

.duo > div:first-child {
    border-left: none;
}

.footer-actions {
    padding: 10px 20px 12px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    font-family: Arial, sans-serif;
}

.btn {
    padding: 10px 22px;
    border-radius: 3px;
    border: 1px solid var(--red);
    background: var(--red);
    color: #fff;
    font-size: 0.85rem;
    cursor: pointer;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.status-msg {
    font-family: Arial, sans-serif;
    font-size: 0.78rem;
    color: var(--grey);
    padding: 8px 20px 0;
    margin: 0;
}

.status-msg.error {
    color: #a3181f;
}

@media (max-width: 900px) {
    .band {
        grid-template-columns: 1fr;
    }

    .triple,
    .duo {
        grid-template-columns: 1fr;
    }

    .triple > div,
    .duo > div {
        border-left: none;
        border-top: 1px solid var(--line);
    }

    .triple > div:first-child,
    .duo > div:first-child {
        border-top: none;
    }
}

@media print {
    .reporting-page {
        height: auto;
        overflow: visible;
    }

    .reporting-form {
        overflow: visible;
        height: auto;
    }

    .form-scroll {
        overflow: visible;
    }

    .form-footer,
    .add-row-btn,
    .remove-row {
        display: none !important;
    }

    textarea,
    input,
    select {
        border: none;
        background: transparent;
    }
}
</style>
