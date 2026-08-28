<template>
    <div class="sit-page">
        <div class="sit-sheet">
            <div class="sit-top">
                <button type="button" class="sit-top-btn sit-top-progress" @click="openQuestions('generic')">
                    <span class="sit-top-label">Questions génériques IT</span>
                    <span class="sit-top-bar">
                        <span
                            class="sit-top-fill"
                            :class="{ full: genericRate === 100, empty: genericRate === 0 }"
                            :style="progressFillStyle(genericRate)"
                        />
                        <span class="sit-top-pct" :class="{ 'on-full': genericRate === 100 }">{{ genericRate }}%</span>
                    </span>
                </button>

                <button type="button" class="sit-top-btn sit-top-progress" @click="openQuestions('security')">
                    <span class="sit-top-label">Questions Sécurité IT</span>
                    <span class="sit-top-bar">
                        <span
                            class="sit-top-fill"
                            :class="{ full: securityRate === 100, empty: securityRate === 0 }"
                            :style="progressFillStyle(securityRate)"
                        />
                        <span class="sit-top-pct" :class="{ 'on-full': securityRate === 100 }">{{ securityRate }}%</span>
                    </span>
                </button>

                <RouterLink
                    :to="{ name: 'cartographie-applications.contrats' }"
                    class="sit-top-btn sit-top-solid sit-top-red"
                >
                    Contrats IT
                </RouterLink>
                <RouterLink
                    :to="{ name: 'cartographie-applications.projets' }"
                    class="sit-top-btn sit-top-solid sit-top-blue"
                >
                    Projets IT
                </RouterLink>
            </div>

            <p v-if="error" class="sit-error">{{ error }}</p>
            <div v-if="loading" class="sit-empty">Chargement…</div>

            <div v-else class="sit-table-wrap">
                <table class="sit-table">
                    <thead>
                        <tr>
                            <th colspan="17" class="sit-banner">Services IT</th>
                        </tr>
                        <tr>
                            <th>Taux de remplissage</th>
                            <th>Application</th>
                            <th>Existant</th>
                            <th>Nom de la solution</th>
                            <th>Editeur</th>
                            <th>Importance</th>
                            <th>Version</th>
                            <th>Dernière version</th>
                            <th>SLA existant</th>
                            <th>SaaS ou on-site</th>
                            <th># users</th>
                            <th># licences</th>
                            <th>Type de licences</th>
                            <th>Niveau de personnalisation</th>
                            <th>Sauvegardes</th>
                            <th>ETP Support</th>
                            <th>Archi HO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in services"
                            :key="row.application_type_id"
                            class="sit-row"
                            @dblclick="openServiceEdit(row)"
                        >
                            <td
                                class="sit-cell-rate"
                                :class="rateClass(row.fill_rate)"
                                :title="`${row.answered_count}/${row.questions_count} questions — cliquer pour répondre`"
                                @click="openQuestions('type', row)"
                            >
                                {{ row.fill_rate }}%
                            </td>
                            <td class="sit-cell-app">
                                <button
                                    type="button"
                                    class="sit-type-badge"
                                    :style="badgeStyle(row.accent_color)"
                                    :title="row.name"
                                    @click="openQuestions('type', row)"
                                >
                                    {{ row.code }}
                                </button>
                            </td>
                            <td class="sit-cell-yn" :class="ynClass(field(row, 'exists_flag'))" @click="openServiceEdit(row)">
                                {{ displayYn(field(row, 'exists_flag')) }}
                            </td>
                            <td class="sit-cell-text" @click="openServiceEdit(row)">{{ cell(field(row, 'solution_name')) }}</td>
                            <td class="sit-cell-text" @click="openServiceEdit(row)">{{ cell(field(row, 'editor')) }}</td>
                            <td @click="openServiceEdit(row)">{{ cell(field(row, 'importance')) }}</td>
                            <td @click="openServiceEdit(row)">{{ cell(field(row, 'version')) }}</td>
                            <td @click="openServiceEdit(row)">{{ cell(field(row, 'last_version')) }}</td>
                            <td class="sit-cell-yn" :class="ynClass(field(row, 'sla_exists'))" @click="openServiceEdit(row)">
                                {{ displayYn(field(row, 'sla_exists')) }}
                            </td>
                            <td @click="openServiceEdit(row)">{{ cell(field(row, 'hosting_mode')) }}</td>
                            <td class="sit-cell-text" @click="openServiceEdit(row)">{{ cell(field(row, 'users_count')) }}</td>
                            <td class="sit-cell-text" @click="openServiceEdit(row)">{{ cell(field(row, 'licenses_count')) }}</td>
                            <td @click="openServiceEdit(row)">{{ cell(field(row, 'license_type')) }}</td>
                            <td @click="openServiceEdit(row)">{{ cell(field(row, 'customization_level')) }}</td>
                            <td class="sit-cell-yn" :class="ynClass(field(row, 'backups'))" @click="openServiceEdit(row)">
                                {{ displayYn(field(row, 'backups')) }}
                            </td>
                            <td class="sit-etp" @click="openServiceEdit(row)">{{ cell(field(row, 'etp_support')) }}</td>
                            <td class="sit-cell-yn" :class="ynClass(field(row, 'archi_ho'))" @click="openServiceEdit(row)">
                                {{ displayYn(field(row, 'archi_ho')) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Questionnaire -->
        <div v-if="showQuestions" class="sit-modal-backdrop" @click.self="closeQuestions">
            <form class="sit-modal" @submit.prevent="saveQuestions">
                <header class="sit-modal-head">
                    <div>
                        <p class="sit-modal-kicker">Questionnaire</p>
                        <h3>{{ questionsTitle }}</h3>
                        <p class="sit-modal-meta">
                            Remplissage :
                            <strong>{{ questionsFill.rate ?? 0 }}%</strong>
                            ({{ questionsFill.answered ?? 0 }}/{{ questionsFill.total ?? 0 }})
                        </p>
                    </div>
                    <button type="button" class="sit-btn-secondary" @click="closeQuestions">Fermer</button>
                </header>

                <div v-if="questionsLoading" class="sit-empty">Chargement des questions…</div>
                <div v-else class="sit-questions">
                    <label v-for="q in questionItems" :key="q.id" class="sit-question">
                        <span class="sit-question-label">{{ q.label }}</span>
                        <select
                            v-if="q.input_type === 'yes_no'"
                            v-model="answerDraft[q.id]"
                            class="sit-input"
                        >
                            <option value="">—</option>
                            <option value="oui">Oui</option>
                            <option value="non">Non</option>
                        </select>
                        <textarea
                            v-else-if="q.input_type === 'textarea'"
                            v-model="answerDraft[q.id]"
                            rows="2"
                            class="sit-input"
                        />
                        <input v-else v-model="answerDraft[q.id]" type="text" class="sit-input" />
                    </label>
                    <p v-if="!questionItems.length" class="sit-empty">Aucune question pour ce périmètre.</p>
                </div>

                <p v-if="questionsError" class="sit-error">{{ questionsError }}</p>

                <div class="sit-modal-actions">
                    <button type="button" class="sit-btn-secondary" @click="closeQuestions">Annuler</button>
                    <button type="submit" class="sit-btn-primary" :disabled="questionsSaving">
                        {{ questionsSaving ? 'Enregistrement…' : 'Enregistrer les réponses' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Édition service -->
        <div v-if="showServiceForm" class="sit-modal-backdrop" @click.self="closeServiceEdit">
            <form class="sit-modal sit-modal-wide" @submit.prevent="saveService">
                <header class="sit-modal-head">
                    <div>
                        <p class="sit-modal-kicker">Fiche service</p>
                        <h3>
                            <span
                                class="sit-type-badge sit-type-badge-inline"
                                :style="badgeStyle(editingRow?.accent_color)"
                            >{{ editingRow?.code }}</span>
                            {{ editingRow?.name }}
                        </h3>
                    </div>
                    <button type="button" class="sit-btn-secondary" @click="closeServiceEdit">Fermer</button>
                </header>
                <div class="sit-form-grid">
                    <label>
                        <span>Existant</span>
                        <select v-model="serviceForm.exists_flag" class="sit-input">
                            <option value="">—</option>
                            <option value="oui">Oui</option>
                            <option value="non">Non</option>
                        </select>
                    </label>
                    <label>
                        <span>Nom de la solution</span>
                        <input v-model="serviceForm.solution_name" class="sit-input" />
                    </label>
                    <label>
                        <span>Editeur</span>
                        <input v-model="serviceForm.editor" class="sit-input" />
                    </label>
                    <label>
                        <span>Importance</span>
                        <select v-model="serviceForm.importance" class="sit-input">
                            <option value="">—</option>
                            <option value="Faible">Faible</option>
                            <option value="Moyen">Moyen</option>
                            <option value="Haut">Haut</option>
                            <option value="Très haut">Très haut</option>
                            <option value="Primordial">Primordial</option>
                            <option value="Critique">Critique</option>
                        </select>
                    </label>
                    <label>
                        <span>Version</span>
                        <input v-model="serviceForm.version" class="sit-input" />
                    </label>
                    <label>
                        <span>Dernière version</span>
                        <input v-model="serviceForm.last_version" class="sit-input" />
                    </label>
                    <label>
                        <span>SLA existant</span>
                        <select v-model="serviceForm.sla_exists" class="sit-input">
                            <option value="">—</option>
                            <option value="oui">Oui</option>
                            <option value="non">Non</option>
                        </select>
                    </label>
                    <label>
                        <span>SaaS ou on-site</span>
                        <select v-model="serviceForm.hosting_mode" class="sit-input">
                            <option value="">—</option>
                            <option value="On-site">On-site</option>
                            <option value="Cloud">Cloud</option>
                            <option value="SaaS">SaaS</option>
                            <option value="Hybride">Hybride</option>
                        </select>
                    </label>
                    <label>
                        <span># users</span>
                        <input v-model="serviceForm.users_count" class="sit-input" />
                    </label>
                    <label>
                        <span># licences</span>
                        <input v-model="serviceForm.licenses_count" class="sit-input" />
                    </label>
                    <label>
                        <span>Type de licences</span>
                        <input v-model="serviceForm.license_type" class="sit-input" />
                    </label>
                    <label>
                        <span>Niveau de personnalisation</span>
                        <select v-model="serviceForm.customization_level" class="sit-input">
                            <option value="">—</option>
                            <option value="Faible">Faible</option>
                            <option value="Moyen">Moyen</option>
                            <option value="Haut">Haut</option>
                            <option value="Très haut">Très haut</option>
                            <option value="Non">Non</option>
                        </select>
                    </label>
                    <label>
                        <span>Sauvegardes</span>
                        <select v-model="serviceForm.backups" class="sit-input">
                            <option value="">—</option>
                            <option value="oui">Oui</option>
                            <option value="non">Non</option>
                        </select>
                    </label>
                    <label>
                        <span>Archi HO</span>
                        <select v-model="serviceForm.archi_ho" class="sit-input">
                            <option value="">—</option>
                            <option value="oui">Oui</option>
                            <option value="non">Non</option>
                        </select>
                    </label>
                    <label class="sit-span-2">
                        <span>ETP Support</span>
                        <textarea v-model="serviceForm.etp_support" rows="3" class="sit-input" />
                    </label>
                </div>

                <p v-if="serviceError" class="sit-error">{{ serviceError }}</p>

                <div class="sit-modal-actions">
                    <button type="button" class="sit-btn-secondary" @click="closeServiceEdit">Annuler</button>
                    <button type="submit" class="sit-btn-primary" :disabled="serviceSaving">
                        {{ serviceSaving ? 'Enregistrement…' : 'Enregistrer' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import api from '../../api/client';

const loading = ref(true);
const error = ref('');
const services = ref([]);
const genericFill = ref({ rate: 0, answered: 0, total: 0 });
const securityFill = ref({ rate: 0, answered: 0, total: 0 });

const showQuestions = ref(false);
const questionsLoading = ref(false);
const questionsSaving = ref(false);
const questionsError = ref('');
const questionsScope = ref('generic');
const questionsType = ref(null);
const questionItems = ref([]);
const questionsFill = ref({ rate: 0, answered: 0, total: 0 });
const answerDraft = reactive({});

const showServiceForm = ref(false);
const serviceSaving = ref(false);
const serviceError = ref('');
const editingRow = ref(null);
const serviceForm = reactive(emptyServiceForm());

const genericRate = computed(() => genericFill.value.rate ?? 0);
const securityRate = computed(() => securityFill.value.rate ?? 0);

const questionsTitle = computed(() => {
    if (questionsScope.value === 'generic') return 'Questions génériques IT';
    if (questionsScope.value === 'security') return 'Questions Sécurité IT';
    return `Questions — ${questionsType.value?.code || 'type'}`;
});

function emptyServiceForm() {
    return {
        exists_flag: '',
        solution_name: '',
        editor: '',
        importance: '',
        version: '',
        last_version: '',
        sla_exists: '',
        hosting_mode: '',
        users_count: '',
        licenses_count: '',
        license_type: '',
        customization_level: '',
        backups: '',
        etp_support: '',
        archi_ho: '',
    };
}

function progressFillStyle(rate) {
    const value = Number(rate) || 0;
    const width = value === 0 ? 100 : Math.max(value, 12);
    return { width: width + '%' };
}

function cell(value) {
    return value && String(value).trim() !== '' ? value : '';
}

function field(row, key) {
    if (!row) return null;
    if (row[key] != null && row[key] !== '') return row[key];
    return row.service?.[key] ?? row.it_service?.[key] ?? null;
}

function displayYn(value) {
    if (!value) return '';
    const v = String(value).toLowerCase();
    if (v === 'oui' || v === 'yes') return 'oui';
    if (v === 'non' || v === 'no') return 'non';
    return value;
}

function ynClass(value) {
    const v = displayYn(value);
    if (v === 'oui') return 'is-yes';
    if (v === 'non') return 'is-no';
    return '';
}

function rateClass(rate) {
    if (rate >= 100) return 'is-full';
    if (rate >= 80) return 'is-high';
    if (rate > 0) return 'is-mid';
    return 'is-empty';
}

function badgeStyle(color) {
    const base = color || '#0f4c81';
    return {
        background: `linear-gradient(180deg, ${lighten(base, 18)} 0%, ${base} 48%, ${darken(base, 12)} 100%)`,
        borderColor: darken(base, 18),
        boxShadow: `inset 0 1px 0 ${lighten(base, 35)}, 0 1px 2px rgba(0,0,0,.25)`,
    };
}

function lighten(hex, percent) {
    return mix(hex, '#ffffff', percent);
}

function darken(hex, percent) {
    return mix(hex, '#000000', percent);
}

function mix(hex, target, percent) {
    const a = parseHex(hex);
    const b = parseHex(target);
    if (!a || !b) return hex;
    const p = Math.min(100, Math.max(0, percent)) / 100;
    const r = Math.round(a.r + (b.r - a.r) * p);
    const g = Math.round(a.g + (b.g - a.g) * p);
    const bl = Math.round(a.b + (b.b - a.b) * p);
    return `#${toHex(r)}${toHex(g)}${toHex(bl)}`;
}

function parseHex(hex) {
    const raw = String(hex || '').replace('#', '');
    if (raw.length !== 6) return null;
    return {
        r: parseInt(raw.slice(0, 2), 16),
        g: parseInt(raw.slice(2, 4), 16),
        b: parseInt(raw.slice(4, 6), 16),
    };
}

function toHex(n) {
    return n.toString(16).padStart(2, '0');
}

function applyDashboard(payload) {
    const root = payload?.data ?? payload;
    const dashboard = root?.services ? root : (root?.data ?? root);
    const rows = dashboard?.services ?? [];
    services.value = Array.isArray(rows) ? rows : Object.values(rows || {});
    genericFill.value = dashboard?.generic_fill_rate ?? { rate: 0, answered: 0, total: 0 };
    securityFill.value = dashboard?.security_fill_rate ?? { rate: 0, answered: 0, total: 0 };
}

async function loadDashboard() {
    loading.value = true;
    error.value = '';
    try {
        const { data } = await api.get('/it-services/dashboard');
        applyDashboard(data);
    } catch {
        error.value = 'Impossible de charger les Services IT.';
        services.value = [];
    } finally {
        loading.value = false;
    }
}

async function openQuestions(scope, row = null) {
    questionsScope.value = scope;
    questionsType.value = row;
    questionsError.value = '';
    showQuestions.value = true;
    questionsLoading.value = true;
    questionItems.value = [];
    Object.keys(answerDraft).forEach((k) => delete answerDraft[k]);

    try {
        const { data } = await api.get('/it-services/questions', {
            params: {
                scope,
                application_type_id: scope === 'type' ? row.application_type_id : undefined,
            },
        });
        const root = data?.data ?? data;
        questionItems.value = root?.questions ?? [];
        questionsFill.value = root?.fill_rate ?? { rate: 0, answered: 0, total: 0 };
        for (const q of questionItems.value) {
            answerDraft[q.id] = q.value ?? '';
        }
    } catch {
        questionsError.value = 'Impossible de charger les questions.';
    } finally {
        questionsLoading.value = false;
    }
}

function closeQuestions() {
    showQuestions.value = false;
    questionsError.value = '';
}

async function saveQuestions() {
    questionsSaving.value = true;
    questionsError.value = '';

    const answers = questionItems.value.map((q) => ({
        question_id: q.id,
        value: answerDraft[q.id] ?? '',
    }));

    try {
        const { data } = await api.post('/it-services/answers', {
            scope: questionsScope.value,
            application_type_id:
                questionsScope.value === 'type' ? questionsType.value?.application_type_id : undefined,
            answers,
        });
        const root = data?.data ?? data;
        if (root?.dashboard) applyDashboard({ data: root.dashboard });
        if (root?.fill_rate) questionsFill.value = root.fill_rate;
        closeQuestions();
    } catch (err) {
        questionsError.value =
            err.response?.data?.message ||
            Object.values(err.response?.data?.errors || {}).flat()[0] ||
            'Erreur lors de l’enregistrement.';
    } finally {
        questionsSaving.value = false;
    }
}

function openServiceEdit(row) {
    editingRow.value = row;
    Object.assign(serviceForm, emptyServiceForm(), {
        exists_flag: field(row, 'exists_flag') ?? '',
        solution_name: field(row, 'solution_name') ?? '',
        editor: field(row, 'editor') ?? '',
        importance: field(row, 'importance') ?? '',
        version: field(row, 'version') ?? '',
        last_version: field(row, 'last_version') ?? '',
        sla_exists: field(row, 'sla_exists') ?? '',
        hosting_mode: field(row, 'hosting_mode') ?? '',
        users_count: field(row, 'users_count') ?? '',
        licenses_count: field(row, 'licenses_count') ?? '',
        license_type: field(row, 'license_type') ?? '',
        customization_level: field(row, 'customization_level') ?? '',
        backups: field(row, 'backups') ?? '',
        etp_support: field(row, 'etp_support') ?? '',
        archi_ho: field(row, 'archi_ho') ?? '',
    });
    serviceError.value = '';
    showServiceForm.value = true;
}

function closeServiceEdit() {
    showServiceForm.value = false;
    editingRow.value = null;
}

async function saveService() {
    if (!editingRow.value) return;
    serviceSaving.value = true;
    serviceError.value = '';

    try {
        const { data } = await api.put(`/it-services/${editingRow.value.application_type_id}`, {
            ...serviceForm,
        });
        const root = data?.data ?? data;
        if (root?.dashboard) applyDashboard({ data: root.dashboard });
        closeServiceEdit();
    } catch (err) {
        serviceError.value =
            err.response?.data?.message ||
            Object.values(err.response?.data?.errors || {}).flat()[0] ||
            'Erreur lors de l’enregistrement.';
    } finally {
        serviceSaving.value = false;
    }
}

onMounted(loadDashboard);
</script>

<style scoped>
.sit-page {
    display: flex;
    flex-direction: column;
    min-height: 100%;
    background: #eef2f5;
}

.sit-sheet {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    padding: 0.65rem 0.75rem 1rem;
    flex: 1;
    min-width: 0;
}

.sit-top {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.45rem;
}

.sit-top-btn {
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 3.6rem;
    border: 1px solid #94a3b8;
    border-radius: 0.2rem;
    text-decoration: none;
    cursor: pointer;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.35);
}

.sit-top-progress {
    background: #f8fafc;
    padding: 0.35rem 0.45rem 0.4rem;
    text-align: left;
}

.sit-top-label {
    font-size: 0.78rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
    margin-bottom: 0.28rem;
}

.sit-top-bar {
    position: relative;
    display: block;
    height: 1.55rem;
    background: #dbe3ea;
    border: 1px solid #94a3b8;
    overflow: hidden;
}

.sit-top-fill {
    display: block;
    height: 100%;
    background: linear-gradient(180deg, #4ade80 0%, #22c55e 55%, #16a34a 100%);
    transition: width 0.2s ease;
}

.sit-top-fill.empty {
    background: #e8eef3;
}

.sit-top-fill.full {
    background: linear-gradient(180deg, #4ade80 0%, #16a34a 100%);
}

.sit-top-pct {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 800;
    color: #0f172a;
    pointer-events: none;
}

.sit-top-pct.on-full {
    color: #fff;
    text-shadow: 0 1px 0 rgba(0, 0, 0, 0.3);
}

.sit-top-solid {
    align-items: center;
    color: #fff;
    font-size: 1.05rem;
    font-weight: 800;
    letter-spacing: 0.01em;
}

.sit-top-red {
    background: linear-gradient(180deg, #9f2a2a 0%, #7f1d1d 55%, #641616 100%);
    border-color: #4c1010;
}

.sit-top-blue {
    background: linear-gradient(180deg, #334e68 0%, #1e3a5f 55%, #152c48 100%);
    border-color: #0f2138;
}

.sit-table-wrap {
    overflow: auto;
    flex: 1;
    background: #fff;
    border: 1px solid #64748b;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
}

.sit-table {
    width: max-content;
    min-width: 100%;
    border-collapse: collapse;
    font-size: 0.78rem;
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
}

.sit-table th,
.sit-table td {
    border: 1px solid #94a3b8;
    padding: 0.28rem 0.45rem;
    text-align: center;
    vertical-align: middle;
    white-space: nowrap;
}

.sit-banner {
    background: linear-gradient(180deg, #1a7a7a 0%, #0f6666 100%) !important;
    color: #fff !important;
    font-size: 1.05rem !important;
    font-weight: 800 !important;
    letter-spacing: 0.02em;
    padding: 0.45rem !important;
    text-align: center !important;
}

.sit-table thead tr:last-child th {
    background: #0f4c81;
    color: #fff;
    font-size: 0.72rem;
    font-weight: 700;
    white-space: normal;
    min-width: 5.5rem;
    max-width: 9rem;
    line-height: 1.2;
    padding: 0.4rem 0.35rem;
}

.sit-row:nth-child(even) td {
    background: #f3f6f8;
}

.sit-row:hover td {
    background: #e8f1f8;
}

.sit-cell-rate {
    font-weight: 800;
    cursor: pointer;
    min-width: 4.5rem;
}

.sit-cell-rate.is-full {
    background: #22c55e !important;
    color: #052e16;
}

.sit-cell-rate.is-high {
    background: #86efac !important;
    color: #14532d;
}

.sit-cell-rate.is-mid {
    background: #fde68a !important;
    color: #78350f;
}

.sit-cell-rate.is-empty {
    background: #e2e8f0 !important;
    color: #334155;
}

.sit-cell-app {
    min-width: 7rem;
}

.sit-type-badge {
    display: inline-block;
    border: 1px solid transparent;
    border-radius: 0.45rem;
    padding: 0.28rem 0.7rem;
    color: #fff;
    font-weight: 800;
    font-size: 0.74rem;
    letter-spacing: 0.01em;
    cursor: pointer;
    text-shadow: 0 1px 0 rgba(0, 0, 0, 0.35);
    white-space: nowrap;
}

.sit-type-badge-inline {
    margin-right: 0.45rem;
    vertical-align: middle;
    cursor: default;
}

.sit-cell-yn {
    font-weight: 700;
    text-transform: lowercase;
    cursor: pointer;
    min-width: 3.2rem;
}

.sit-cell-yn.is-yes {
    background: #86efac !important;
    color: #14532d;
}

.sit-cell-yn.is-no {
    background: #fecaca !important;
    color: #7f1d1d;
}

.sit-cell-text {
    text-align: left;
    white-space: normal;
    min-width: 8rem;
    max-width: 14rem;
    cursor: pointer;
}

.sit-etp {
    text-align: left;
    white-space: normal;
    min-width: 12rem;
    max-width: 18rem;
    font-size: 0.72rem;
    line-height: 1.25;
    cursor: pointer;
}

.sit-empty {
    padding: 1.5rem;
    text-align: center;
    color: #64748b;
    background: #fff;
    border: 1px solid #cbd5e1;
}

.sit-error {
    margin: 0;
    color: #b91c1c;
    font-size: 0.85rem;
    font-weight: 600;
}

.sit-btn-primary,
.sit-btn-secondary {
    border-radius: 0.4rem;
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    padding: 0.55rem 0.85rem;
}

.sit-btn-primary {
    border: 0;
    background: #0f4c81;
    color: #fff;
}

.sit-btn-secondary {
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #334155;
}

.sit-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 60;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background: rgba(15, 23, 42, 0.45);
}

.sit-modal {
    width: min(40rem, 100%);
    max-height: 92vh;
    overflow: auto;
    border-radius: 0.65rem;
    background: #fff;
    padding: 1.15rem 1.25rem;
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
}

.sit-modal-wide {
    width: min(48rem, 100%);
}

.sit-modal-head {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
}

.sit-modal-kicker {
    margin: 0;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: #0f6666;
}

.sit-modal h3 {
    margin: 0.2rem 0 0;
    font-size: 1.05rem;
}

.sit-modal-meta {
    margin: 0.35rem 0 0;
    font-size: 0.82rem;
    color: #64748b;
}

.sit-questions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.sit-question {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}

.sit-question-label {
    font-size: 0.82rem;
    font-weight: 600;
    color: #334155;
}

.sit-input {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 0.4rem;
    padding: 0.5rem 0.7rem;
    font-size: 0.875rem;
    background: #fff;
}

.sit-form-grid {
    display: grid;
    gap: 0.75rem;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.sit-form-grid label {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    font-size: 0.78rem;
    font-weight: 600;
    color: #475569;
}

.sit-span-2 {
    grid-column: span 2;
}

.sit-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 1rem;
}

@media (max-width: 900px) {
    .sit-top {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 640px) {
    .sit-top {
        grid-template-columns: 1fr;
    }

    .sit-form-grid {
        grid-template-columns: 1fr;
    }

    .sit-span-2 {
        grid-column: span 1;
    }
}
</style>
