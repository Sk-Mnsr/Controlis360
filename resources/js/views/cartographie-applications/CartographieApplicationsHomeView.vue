<template>
    <div class="sit-page">
        <header class="sit-header">
            <div>
                <p class="sit-kicker">Analyse IT Audit Tool · Accueil</p>
                <h2 class="sit-title">Services IT</h2>
                <p class="sit-subtitle">
                    Page d’accueil du référentiel — questionnaires QG / Sécurité, fiches par type applicatif (CBS, LOS…).
                </p>
            </div>
            <div class="sit-header-actions">
                <button type="button" class="sit-btn-secondary" @click="loadDashboard">
                    Actualiser
                </button>
            </div>
        </header>

        <section class="sit-metrics">
            <button type="button" class="sit-metric" @click="openQuestions('generic')">
                <div class="sit-metric-top">
                    <span class="sit-metric-label">Questions génériques IT</span>
                    <span class="sit-metric-value">{{ genericRate }}%</span>
                </div>
                <div class="sit-metric-track">
                    <span class="sit-metric-fill" :style="{ width: `${genericRate}%` }" />
                </div>
                <span class="sit-metric-hint">
                    Onglet QG — {{ genericFill.answered ?? 0 }}/{{ genericFill.total ?? 0 }} répondue(s)
                </span>
            </button>

            <button type="button" class="sit-metric" @click="openQuestions('security')">
                <div class="sit-metric-top">
                    <span class="sit-metric-label">Questions Sécurité IT</span>
                    <span class="sit-metric-value">{{ securityRate }}%</span>
                </div>
                <div class="sit-metric-track">
                    <span class="sit-metric-fill sit-metric-fill-security" :style="{ width: `${securityRate}%` }" />
                </div>
                <span class="sit-metric-hint">
                    Onglet Secu — {{ securityFill.answered ?? 0 }}/{{ securityFill.total ?? 0 }} répondue(s)
                </span>
            </button>

            <RouterLink
                :to="{ name: 'gouvernance-it.cartographie-applications.contrats' }"
                class="sit-metric sit-metric-link sit-metric-contrats"
            >
                <div class="sit-metric-top">
                    <span class="sit-metric-label">Contrats IT</span>
                    <span class="sit-metric-arrow">→</span>
                </div>
                <span class="sit-metric-hint">Onglet Contracts — prestataires, SLA, coûts</span>
            </RouterLink>

            <RouterLink
                :to="{ name: 'gouvernance-it.cartographie-applications.projets' }"
                class="sit-metric sit-metric-link sit-metric-projets"
            >
                <div class="sit-metric-top">
                    <span class="sit-metric-label">Projets IT</span>
                    <span class="sit-metric-arrow">→</span>
                </div>
                <span class="sit-metric-hint">Onglets Projets — priorité, avancement, owner</span>
            </RouterLink>
        </section>

        <div class="sit-toolbar">
            <input
                v-model="search"
                type="search"
                class="sit-search"
                placeholder="Rechercher (code, nom, solution, éditeur…)"
            />
            <select v-model="filterExists" class="sit-select">
                <option value="all">Tous les statuts</option>
                <option value="oui">Existants</option>
                <option value="non">Non prévus</option>
                <option value="empty">Non renseignés</option>
            </select>
        </div>

        <p v-if="error" class="sit-error">{{ error }}</p>
        <div v-if="loading" class="sit-empty">Chargement…</div>

        <div v-else class="sit-table-wrap">
            <table class="sit-table">
                <thead>
                    <tr class="sit-group-row">
                        <th colspan="18" class="sit-group-id">Services IT — Accueil</th>
                    </tr>
                    <tr class="sit-col-row">
                        <th>Taux de remplissage</th>
                        <th>Application</th>
                        <th>Existant</th>
                        <th>Nom de la solution</th>
                        <th>Editeur</th>
                        <th>Importance</th>
                        <th>Version</th>
                        <th>Dernière version</th>
                        <th>SLA existant</th>
                        <th>SaaS ou sur site</th>
                        <th># users</th>
                        <th># licences</th>
                        <th>Type de licences</th>
                        <th>Niveau de personnalisation</th>
                        <th>Sauvegardes</th>
                        <th>ETP Support</th>
                        <th>ETP Changes</th>
                        <th>Archi HD</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in filteredServices"
                        :key="row.application_type_id"
                        class="sit-row"
                        @dblclick="openServiceEdit(row)"
                    >
                        <td class="sit-cell-rate" @click="openQuestions('type', row)">
                            <div class="sit-rate" :title="`${row.answered_count}/${row.questions_count} questions`">
                                <span class="sit-rate-track">
                                    <span
                                        class="sit-rate-fill"
                                        :class="rateClass(row.fill_rate)"
                                        :style="{ width: `${Math.min(100, Number(row.fill_rate) || 0)}%` }"
                                    />
                                </span>
                                <span class="sit-rate-pct">{{ row.fill_rate }}%</span>
                            </div>
                        </td>
                        <td class="sit-cell-app">
                            <button
                                type="button"
                                class="sit-type-badge"
                                :style="badgeStyle(row.accent_color)"
                                :title="applicationTitle(row)"
                                @click="openQuestions('type', row)"
                            >
                                {{ row.code }}
                            </button>
                        </td>
                        <td>
                            <span class="sit-yn" :class="ynClass(field(row, 'exists_flag'))" @click="openServiceEdit(row)">
                                {{ displayYn(field(row, 'exists_flag')) || '—' }}
                            </span>
                        </td>
                        <td class="sit-cell-text" @click="openServiceEdit(row)">
                            {{ cell(field(row, 'solution_name')) || '—' }}
                        </td>
                        <td class="sit-cell-text" @click="openServiceEdit(row)">
                            {{ cell(field(row, 'editor')) || '—' }}
                        </td>
                        <td @click="openServiceEdit(row)">
                            <span
                                v-if="field(row, 'importance')"
                                class="sit-importance"
                                :class="importanceClass(field(row, 'importance'))"
                            >
                                {{ field(row, 'importance') }}
                            </span>
                            <span v-else>—</span>
                        </td>
                        <td @click="openServiceEdit(row)">{{ cell(field(row, 'version')) || '—' }}</td>
                        <td @click="openServiceEdit(row)">{{ cell(field(row, 'last_version')) || '—' }}</td>
                        <td>
                            <span class="sit-yn" :class="ynClass(field(row, 'sla_exists'))" @click="openServiceEdit(row)">
                                {{ displayYn(field(row, 'sla_exists')) || '—' }}
                            </span>
                        </td>
                        <td @click="openServiceEdit(row)">{{ cell(field(row, 'hosting_mode')) || '—' }}</td>
                        <td class="sit-cell-text" @click="openServiceEdit(row)">
                            {{ cell(field(row, 'users_count')) || '—' }}
                        </td>
                        <td class="sit-cell-text" @click="openServiceEdit(row)">
                            {{ cell(field(row, 'licenses_count')) || '—' }}
                        </td>
                        <td @click="openServiceEdit(row)">{{ cell(field(row, 'license_type')) || '—' }}</td>
                        <td @click="openServiceEdit(row)">{{ cell(field(row, 'customization_level')) || '—' }}</td>
                        <td>
                            <span class="sit-yn" :class="ynClass(field(row, 'backups'))" @click="openServiceEdit(row)">
                                {{ displayYn(field(row, 'backups')) || '—' }}
                            </span>
                        </td>
                        <td class="sit-etp" @click="openServiceEdit(row)">
                            {{ cell(field(row, 'etp_support')) || '—' }}
                        </td>
                        <td class="sit-etp" @click="openServiceEdit(row)">
                            {{ cell(field(row, 'etp_changes')) || '—' }}
                        </td>
                        <td>
                            <span class="sit-yn" :class="ynClass(field(row, 'archi_ho'))" @click="openServiceEdit(row)">
                                {{ displayYn(field(row, 'archi_ho')) || '—' }}
                            </span>
                        </td>
                    </tr>
                    <tr v-if="!filteredServices.length">
                        <td colspan="18" class="sit-empty-cell">
                            {{ services.length ? 'Aucun service ne correspond aux filtres.' : 'Aucun service IT enregistré.' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="sit-footnote">
            Colonnes hors « Application » = données de l’inventaire Applications (type lié).
            Clic taux/badge → questionnaire · double-clic ligne → fiche inventaire
        </p>

        <!-- Questionnaire -->
        <div v-if="showQuestions" class="sit-modal-backdrop" @click.self="closeQuestions">
            <form class="sit-modal sit-modal-questions" @submit.prevent="saveQuestions">
                <header class="sit-modal-head">
                    <div>
                        <p class="sit-modal-kicker">Questionnaire application</p>
                        <h3>{{ questionsTitle }}</h3>
                        <p class="sit-modal-meta">
                            Remplissage :
                            <strong>{{ questionsFill.rate ?? 0 }}%</strong>
                            ({{ questionsFill.answered ?? 0 }}/{{ questionsFill.total ?? 0 }})
                            — format Analyse IT Audit Tool (Réponse + Détails)
                        </p>
                    </div>
                    <button type="button" class="sit-btn-secondary" @click="closeQuestions">Fermer</button>
                </header>

                <input
                    v-model="questionsSearch"
                    type="search"
                    class="sit-input sit-questions-search"
                    placeholder="Filtrer les questions…"
                />

                <div v-if="questionsLoading" class="sit-empty">Chargement des questions…</div>
                <div v-else class="sit-questions">
                    <article
                        v-for="(q, index) in visibleQuestions"
                        :key="q.id"
                        class="sit-question"
                    >
                        <div class="sit-question-head">
                            <span class="sit-question-index">{{ index + 1 }}</span>
                            <div>
                                <p v-if="q.help" class="sit-question-section">{{ q.help }}</p>
                                <span class="sit-question-label">{{ q.label }}</span>
                            </div>
                        </div>
                        <div class="sit-question-fields">
                            <label>
                                <span>Réponse</span>
                                <select
                                    v-if="q.input_type === 'yes_no'"
                                    v-model="answerDraft[q.id].value"
                                    class="sit-input"
                                >
                                    <option value="">—</option>
                                    <option value="oui">Oui</option>
                                    <option value="non">Non</option>
                                </select>
                                <textarea
                                    v-else-if="q.input_type === 'textarea'"
                                    v-model="answerDraft[q.id].value"
                                    rows="2"
                                    class="sit-input"
                                />
                                <input
                                    v-else
                                    v-model="answerDraft[q.id].value"
                                    type="text"
                                    class="sit-input"
                                />
                            </label>
                            <label>
                                <span>Détails</span>
                                <textarea
                                    v-model="answerDraft[q.id].details"
                                    rows="2"
                                    class="sit-input"
                                    placeholder="Complément / justification…"
                                />
                            </label>
                        </div>
                    </article>
                    <p v-if="!visibleQuestions.length" class="sit-empty">
                        {{ questionItems.length ? 'Aucune question ne correspond au filtre.' : 'Aucune question pour ce périmètre.' }}
                    </p>
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
                        <p class="sit-modal-kicker">Fiche inventaire</p>
                        <h3>
                            <span
                                class="sit-type-badge sit-type-badge-inline"
                                :style="badgeStyle(editingRow?.accent_color)"
                            >{{ editingRow?.code }}</span>
                            {{ editingRow?.name }}
                        </h3>
                        <p class="sit-modal-meta">
                            Les données sont enregistrées dans
                            <strong>Applications</strong>
                            <template v-if="editingRow?.inventory_application_code">
                                ({{ editingRow.inventory_application_code }})
                            </template>
                            .
                        </p>
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
                        <span>Éditeur</span>
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
                    <label class="sit-span-2">
                        <span>ETP Support</span>
                        <textarea v-model="serviceForm.etp_support" rows="3" class="sit-input" />
                    </label>
                    <label class="sit-span-2">
                        <span>ETP Changes</span>
                        <textarea v-model="serviceForm.etp_changes" rows="3" class="sit-input" />
                    </label>
                    <label>
                        <span>Archi HD</span>
                        <select v-model="serviceForm.archi_ho" class="sit-input">
                            <option value="">—</option>
                            <option value="oui">Oui</option>
                            <option value="non">Non</option>
                        </select>
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
const search = ref('');
const filterExists = ref('all');
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
const questionsSearch = ref('');
const answerDraft = reactive({});

const showServiceForm = ref(false);
const serviceSaving = ref(false);
const serviceError = ref('');
const editingRow = ref(null);
const serviceForm = reactive(emptyServiceForm());

const genericRate = computed(() => genericFill.value.rate ?? 0);
const securityRate = computed(() => securityFill.value.rate ?? 0);

const filteredServices = computed(() => {
    const q = search.value.trim().toLowerCase();
    const existsFilter = filterExists.value;

    return services.value.filter((row) => {
        const yn = displayYn(field(row, 'exists_flag'));
        if (existsFilter === 'oui' && yn !== 'oui') return false;
        if (existsFilter === 'non' && yn !== 'non') return false;
        if (existsFilter === 'empty' && yn) return false;

        if (!q) return true;

        const haystack = [
            row.code,
            row.name,
            field(row, 'solution_name'),
            field(row, 'editor'),
            field(row, 'importance'),
            field(row, 'hosting_mode'),
        ]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();

        return haystack.includes(q);
    });
});

const questionsTitle = computed(() => {
    if (questionsScope.value === 'generic') return 'Questions génériques IT';
    if (questionsScope.value === 'security') return 'Questions Sécurité IT';
    return `Questions — ${questionsType.value?.code || 'type'} · ${questionsType.value?.name || ''}`;
});

const visibleQuestions = computed(() => {
    const q = questionsSearch.value.trim().toLowerCase();
    if (!q) return questionItems.value;

    return questionItems.value.filter((item) => {
        const haystack = `${item.label || ''} ${item.help || ''}`.toLowerCase();
        return haystack.includes(q);
    });
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
        etp_changes: '',
        archi_ho: '',
    };
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
    return 'is-empty';
}

function rateClass(rate) {
    if (rate >= 100) return 'is-full';
    if (rate >= 80) return 'is-high';
    if (rate > 0) return 'is-mid';
    return 'is-empty';
}

function importanceClass(value) {
    const v = String(value || '').toLowerCase();
    if (v.includes('critique') || v.includes('primordial')) return 'is-critical';
    if (v.includes('très haut') || v.includes('haut')) return 'is-high';
    if (v.includes('moyen')) return 'is-mid';
    return 'is-low';
}

function badgeStyle(color) {
    const base = color || '#0f4c81';
    return {
        background: `linear-gradient(180deg, ${lighten(base, 22)} 0%, ${base} 45%, ${darken(base, 14)} 100%)`,
        borderColor: darken(base, 20),
        boxShadow: `inset 0 1px 0 ${lighten(base, 40)}, inset 0 -1px 0 ${darken(base, 18)}, 0 1px 2px rgba(0,0,0,.28)`,
    };
}

function applicationTitle(row) {
    const parts = [row.name || row.code, 'Ouvrir le questionnaire'];
    if (row.inventory_application_code) {
        parts.splice(1, 0, `Inventaire ${row.inventory_application_code}`);
    } else {
        parts.splice(1, 0, 'Aucune app inventaire liée');
    }
    return parts.join(' — ');
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
    questionsSearch.value = '';
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
            answerDraft[q.id] = {
                value: q.value ?? '',
                details: q.details ?? '',
            };
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
    questionsSearch.value = '';
}

async function saveQuestions() {
    questionsSaving.value = true;
    questionsError.value = '';

    const answers = questionItems.value.map((q) => ({
        question_id: q.id,
        value: answerDraft[q.id]?.value ?? '',
        details: answerDraft[q.id]?.details ?? '',
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
        etp_changes: field(row, 'etp_changes') ?? '',
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
    gap: 1rem;
    padding: 1rem 1.25rem 1.5rem;
}

.sit-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
}

.sit-kicker {
    margin: 0;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #0f4c81;
}

.sit-title {
    margin: 0.2rem 0 0;
    font-size: 1.35rem;
    font-weight: 700;
    color: #0f172a;
}

.sit-subtitle {
    margin: 0.3rem 0 0;
    font-size: 0.875rem;
    color: #64748b;
    max-width: 42rem;
}

.sit-header-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.sit-metrics {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.75rem;
}

.sit-metric {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    text-align: left;
    border: 1px solid #e2e8f0;
    border-radius: 0.65rem;
    background: #fff;
    padding: 0.85rem 1rem;
    cursor: pointer;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
    text-decoration: none;
    color: inherit;
}

.sit-metric:hover {
    border-color: #94a3b8;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
}

.sit-metric-link {
    justify-content: space-between;
    min-height: 5.5rem;
}

.sit-metric-contrats {
    border-color: #fecaca;
    background: linear-gradient(180deg, #fff 0%, #fef2f2 100%);
}

.sit-metric-projets {
    border-color: #bfdbfe;
    background: linear-gradient(180deg, #fff 0%, #eff6ff 100%);
}

.sit-metric-arrow {
    font-size: 1.1rem;
    font-weight: 700;
    color: #0f4c81;
}

.sit-metric-top {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.75rem;
}

.sit-metric-label {
    font-size: 0.8rem;
    font-weight: 700;
    color: #334155;
}

.sit-metric-value {
    font-size: 1.15rem;
    font-weight: 800;
    color: #0f4c81;
    font-variant-numeric: tabular-nums;
}

.sit-metric-track {
    height: 0.45rem;
    border-radius: 999px;
    background: #e2e8f0;
    overflow: hidden;
}

.sit-metric-fill {
    display: block;
    height: 100%;
    border-radius: inherit;
    background: #0f4c81;
    min-width: 0;
    transition: width 0.25s ease;
}

.sit-metric-fill-security {
    background: #0f6666;
}

.sit-metric-hint,
.sit-metric-stats {
    font-size: 0.72rem;
    color: #64748b;
}

.sit-metric-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem 1rem;
}

.sit-metric-stats strong {
    color: #0f172a;
}

.sit-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
}

.sit-search,
.sit-select,
.sit-input {
    border: 1px solid #cbd5e1;
    border-radius: 0.55rem;
    padding: 0.55rem 0.75rem;
    font-size: 0.875rem;
    background: #fff;
}

.sit-search {
    flex: 1;
    min-width: 14rem;
    max-width: 28rem;
}

.sit-select {
    min-width: 11rem;
}

.sit-table-wrap {
    overflow: auto;
    border: 1px solid #e2e8f0;
    border-radius: 0.65rem;
    background: #fff;
}

.sit-table {
    width: max-content;
    min-width: 100%;
    border-collapse: collapse;
    font-size: 0.78rem;
}

.sit-table th,
.sit-table td {
    padding: 0.45rem 0.65rem;
    border: 1px solid #e2e8f0;
    text-align: left;
    vertical-align: middle;
    white-space: nowrap;
}

.sit-group-row th {
    text-align: center;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    color: #fff;
    padding: 0.4rem 0.5rem;
}

.sit-group-id {
    background: #0f4c81;
}

.sit-group-sol {
    background: #c41e3a;
}

.sit-group-lic {
    background: #c9a227;
    color: #1e293b !important;
}

.sit-group-ops {
    background: #0f6666;
}

.sit-col-row th {
    background: #f8fafc;
    font-size: 0.68rem;
    font-weight: 700;
    color: #475569;
    white-space: normal;
    min-width: 5.5rem;
    max-width: 10rem;
    line-height: 1.25;
}

.sit-row:hover td {
    background: #f8fafc;
}

.sit-cell-rate {
    cursor: pointer;
    min-width: 6.5rem;
}

.sit-rate {
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.sit-rate-track {
    flex: 1;
    height: 0.4rem;
    border-radius: 999px;
    background: #e2e8f0;
    overflow: hidden;
    min-width: 2.5rem;
}

.sit-rate-fill {
    display: block;
    height: 100%;
    border-radius: inherit;
}

.sit-rate-fill.is-full { background: #16a34a; }
.sit-rate-fill.is-high { background: #65a30d; }
.sit-rate-fill.is-mid { background: #ca8a04; }
.sit-rate-fill.is-empty { background: #cbd5e1; }

.sit-rate-pct {
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    color: #334155;
    min-width: 2.4rem;
}

.sit-cell-app {
    text-align: center;
    min-width: 7.5rem;
    vertical-align: middle;
}

.sit-type-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 5.4rem;
    border: 1px solid transparent;
    border-radius: 0.55rem;
    padding: 0.38rem 0.85rem;
    color: #fff;
    font-weight: 800;
    font-size: 0.78rem;
    letter-spacing: 0.02em;
    cursor: pointer;
    text-shadow: 0 1px 0 rgba(0, 0, 0, 0.35);
    white-space: nowrap;
    line-height: 1.1;
}

.sit-type-badge:hover {
    filter: brightness(1.06);
}

.sit-type-badge:active {
    transform: translateY(1px);
}

.sit-type-badge-inline {
    margin-right: 0.45rem;
    vertical-align: middle;
    cursor: default;
    min-width: auto;
}

.sit-yn {
    display: inline-block;
    min-width: 2.4rem;
    text-align: center;
    padding: 0.12rem 0.45rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
    cursor: pointer;
    background: #f1f5f9;
    color: #64748b;
}

.sit-yn.is-yes {
    background: #dcfce7;
    color: #166534;
}

.sit-yn.is-no {
    background: #fee2e2;
    color: #991b1b;
}

.sit-importance {
    display: inline-block;
    padding: 0.12rem 0.45rem;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 700;
}

.sit-importance.is-critical {
    background: #fee2e2;
    color: #991b1b;
}

.sit-importance.is-high {
    background: #ffedd5;
    color: #9a3412;
}

.sit-importance.is-mid {
    background: #fef9c3;
    color: #854d0e;
}

.sit-importance.is-low {
    background: #f1f5f9;
    color: #475569;
}

.sit-cell-text {
    white-space: normal;
    min-width: 8rem;
    max-width: 14rem;
    cursor: pointer;
}

.sit-etp {
    white-space: normal;
    min-width: 11rem;
    max-width: 16rem;
    font-size: 0.72rem;
    line-height: 1.3;
    color: #475569;
    cursor: pointer;
}

.sit-empty,
.sit-empty-cell {
    padding: 1.5rem;
    text-align: center;
    color: #64748b;
}

.sit-empty {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.65rem;
}

.sit-error {
    margin: 0;
    color: #b91c1c;
    font-size: 0.85rem;
    font-weight: 600;
}

.sit-footnote {
    margin: 0;
    font-size: 0.75rem;
    color: #94a3b8;
}

.sit-btn-primary,
.sit-btn-secondary {
    border-radius: 0.55rem;
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

.sit-modal-questions {
    width: min(56rem, 100%);
}

.sit-questions-search {
    margin-bottom: 0.85rem;
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
    color: #0f4c81;
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
    gap: 0.85rem;
    max-height: 58vh;
    overflow: auto;
    padding-right: 0.25rem;
}

.sit-question {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.55rem;
    padding: 0.75rem 0.85rem;
    background: #f8fafc;
}

.sit-question-head {
    display: flex;
    gap: 0.65rem;
    align-items: flex-start;
}

.sit-question-index {
    flex: 0 0 auto;
    min-width: 1.6rem;
    height: 1.6rem;
    border-radius: 999px;
    background: #0f4c81;
    color: #fff;
    font-size: 0.72rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.sit-question-section {
    margin: 0 0 0.2rem;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #0f6666;
}

.sit-question-label {
    font-size: 0.84rem;
    font-weight: 600;
    color: #0f172a;
    line-height: 1.35;
}

.sit-question-fields {
    display: grid;
    gap: 0.65rem;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.sit-question-fields label {
    display: flex;
    flex-direction: column;
    gap: 0.28rem;
    font-size: 0.72rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.sit-input {
    width: 100%;
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

@media (max-width: 1100px) {
    .sit-metrics {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 640px) {
    .sit-metrics {
        grid-template-columns: 1fr;
    }

    .sit-form-grid,
    .sit-question-fields {
        grid-template-columns: 1fr;
    }

    .sit-span-2 {
        grid-column: span 1;
    }
}
</style>
