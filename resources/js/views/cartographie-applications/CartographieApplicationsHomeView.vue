<template>
    <div class="sit-page">
        <header class="sit-header">
            <div>
                <p class="sit-kicker">Analyse IT Audit Tool · Accueil</p>
                <h2 class="sit-title">Services IT</h2>
                <p class="sit-subtitle">
                    Questionnaires QG / Sécurité et fiches par type (CBS, LOS…).
                    Les données solution se modifient dans l’inventaire Applications.
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
                        v-for="entry in serviceRows"
                        :key="entry.row.inventory_application_id || `type-${entry.row.application_type_id}`"
                        class="sit-row"
                    >
                        <td
                            v-if="entry.isTypeGroupStart"
                            class="sit-cell-rate sit-cell-merged"
                            :rowspan="entry.typeRowspan"
                            :title="entry.groupFillTitle"
                        >
                            <div class="sit-rate">
                                <span class="sit-rate-track">
                                    <span
                                        class="sit-rate-fill"
                                        :class="rateClass(entry.groupFillRate)"
                                        :style="{ width: `${Math.min(100, Number(entry.groupFillRate) || 0)}%` }"
                                    />
                                </span>
                                <span class="sit-rate-pct">{{ entry.groupFillRate }}%</span>
                            </div>
                        </td>
                        <td
                            v-if="entry.isTypeGroupStart"
                            class="sit-cell-app sit-cell-merged"
                            :rowspan="entry.typeRowspan"
                        >
                            <span
                                class="sit-type-badge"
                                :style="badgeStyle(entry.row.accent_color)"
                                :title="applicationTitle(entry.row)"
                            >
                                {{ entry.row.code }}
                            </span>
                        </td>
                        <td
                            v-if="entry.isTypeGroupStart"
                            class="sit-cell-merged"
                            :rowspan="entry.typeRowspan"
                        >
                            <span class="sit-yn" :class="ynClass(entry.groupExistsFlag)">
                                {{ displayYn(entry.groupExistsFlag) || '—' }}
                            </span>
                        </td>
                        <td class="sit-cell-text sit-cell-solution">
                            <button
                                type="button"
                                class="sit-solution-q"
                                :title="'Questionnaire — ' + (cell(field(entry.row, 'solution_name')) || entry.row.code)"
                                @click="openQuestions('type', entry.row)"
                            >
                                {{ cell(field(entry.row, 'solution_name')) || '—' }}
                            </button>
                        </td>
                        <td
                            class="sit-cell-text"
                            :title="cell(field(entry.row, 'editor')) || ''"
                        >
                            {{ cell(field(entry.row, 'editor')) || '—' }}
                        </td>
                        <td>
                            <span
                                v-if="field(entry.row, 'importance')"
                                class="sit-importance"
                                :class="importanceClass(field(entry.row, 'importance'))"
                            >
                                {{ field(entry.row, 'importance') }}
                            </span>
                            <span v-else>—</span>
                        </td>
                        <td class="sit-cell-compact">{{ cell(field(entry.row, 'version')) || '—' }}</td>
                        <td class="sit-cell-compact">{{ cell(field(entry.row, 'last_version')) || '—' }}</td>
                        <td>
                            <span class="sit-yn" :class="ynClass(field(entry.row, 'sla_exists'))">
                                {{ displayYn(field(entry.row, 'sla_exists')) || '—' }}
                            </span>
                        </td>
                        <td
                            class="sit-cell-text"
                            :title="cell(field(entry.row, 'hosting_mode')) || ''"
                        >
                            {{ cell(field(entry.row, 'hosting_mode')) || '—' }}
                        </td>
                        <td
                            class="sit-cell-text"
                            :title="cell(field(entry.row, 'users_count')) || ''"
                        >
                            {{ cell(field(entry.row, 'users_count')) || '—' }}
                        </td>
                        <td
                            class="sit-cell-text"
                            :title="cell(field(entry.row, 'licenses_count')) || ''"
                        >
                            {{ cell(field(entry.row, 'licenses_count')) || '—' }}
                        </td>
                        <td
                            class="sit-cell-text"
                            :title="cell(field(entry.row, 'license_type')) || ''"
                        >
                            {{ cell(field(entry.row, 'license_type')) || '—' }}
                        </td>
                        <td
                            class="sit-cell-text"
                            :title="cell(field(entry.row, 'customization_level')) || ''"
                        >
                            {{ cell(field(entry.row, 'customization_level')) || '—' }}
                        </td>
                        <td>
                            <span class="sit-yn" :class="ynClass(field(entry.row, 'backups'))">
                                {{ displayYn(field(entry.row, 'backups')) || '—' }}
                            </span>
                        </td>
                        <td
                            class="sit-etp"
                            :title="cell(field(entry.row, 'etp_support')) || ''"
                        >
                            {{ cell(field(entry.row, 'etp_support')) || '—' }}
                        </td>
                        <td
                            class="sit-etp"
                            :title="cell(field(entry.row, 'etp_changes')) || ''"
                        >
                            {{ cell(field(entry.row, 'etp_changes')) || '—' }}
                        </td>
                        <td>
                            <span class="sit-yn" :class="ynClass(field(entry.row, 'archi_ho'))">
                                {{ displayYn(field(entry.row, 'archi_ho')) || '—' }}
                            </span>
                        </td>
                    </tr>
                    <tr v-if="!serviceRows.length">
                        <td colspan="18" class="sit-empty-cell">
                            {{ services.length ? 'Aucun service ne correspond aux filtres.' : 'Aucun service IT enregistré.' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="sit-footnote">
            Colonnes hors « Application » = lecture seule (inventaire Applications via le menu).
            Plusieurs solutions du même type → taux fusionné · clic nom de solution → questionnaire.
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
const questionsApplication = ref(null);
const questionItems = ref([]);
const questionsFill = ref({ rate: 0, answered: 0, total: 0 });
const questionsSearch = ref('');
const answerDraft = reactive({});

const genericRate = computed(() => genericFill.value.rate ?? 0);
const securityRate = computed(() => securityFill.value.rate ?? 0);

/** Une ligne par solution inventaire (plusieurs CBS possibles). */
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

/**
 * Fusionne taux / badge / existant par type.
 * Taux = réponses remplies / questions totales sur toutes les solutions du groupe.
 */
const serviceRows = computed(() => {
    const rows = filteredServices.value;
    const spans = new Array(rows.length).fill(1);

    for (let i = 0; i < rows.length; ) {
        let end = i + 1;
        while (
            end < rows.length &&
            rows[end].application_type_id === rows[i].application_type_id
        ) {
            end += 1;
        }
        spans[i] = end - i;
        for (let j = i + 1; j < end; j += 1) {
            spans[j] = 0;
        }
        i = end;
    }

    return rows.map((row, index) => {
        const span = spans[index];
        const isTypeGroupStart = span > 0;
        let groupExistsFlag = field(row, 'exists_flag');
        let groupFillRate = Number(row.fill_rate) || 0;
        let groupFillTitle = `${row.answered_count ?? 0}/${row.questions_count ?? 0} questions`;

        if (isTypeGroupStart) {
            const group = rows.slice(index, index + Math.max(span, 1));
            const flags = group.map((r) => displayYn(field(r, 'exists_flag')));
            if (flags.some((f) => f === 'oui')) groupExistsFlag = 'oui';
            else if (flags.some((f) => f === 'non')) groupExistsFlag = 'non';
            else groupExistsFlag = null;

            const answered = group.reduce((sum, r) => sum + (Number(r.answered_count) || 0), 0);
            const total = group.reduce((sum, r) => sum + (Number(r.questions_count) || 0), 0);
            groupFillRate = total > 0 ? Math.round((answered / total) * 100) : 0;
            groupFillTitle =
                group.length > 1
                    ? `Taux fusionné · ${answered}/${total} sur ${group.length} solutions`
                    : `${answered}/${total} questions`;
        }

        return {
            row,
            isTypeGroupStart,
            typeRowspan: Math.max(span, 1),
            groupExistsFlag,
            groupFillRate,
            groupFillTitle,
        };
    });
});

const questionsTitle = computed(() => {
    if (questionsScope.value === 'generic') return 'Questions génériques IT';
    if (questionsScope.value === 'security') return 'Questions Sécurité IT';
    const typeLabel = `${questionsType.value?.code || 'type'} · ${questionsType.value?.name || ''}`;
    const solution =
        questionsApplication.value?.name ||
        field(questionsType.value, 'solution_name') ||
        '';
    return solution
        ? `Questions — ${typeLabel} · ${solution}`
        : `Questions — ${typeLabel}`;
});

const visibleQuestions = computed(() => {
    const q = questionsSearch.value.trim().toLowerCase();
    if (!q) return questionItems.value;

    return questionItems.value.filter((item) => {
        const haystack = `${item.label || ''} ${item.help || ''}`.toLowerCase();
        return haystack.includes(q);
    });
});

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
    const parts = [row.name || row.code];
    if (row.inventory_application_code) {
        parts.push(`Inventaire ${row.inventory_application_code}`);
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
    questionsApplication.value = null;
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
                application_id: scope === 'type' ? row.inventory_application_id || undefined : undefined,
            },
        });
        const root = data?.data ?? data;
        questionItems.value = root?.questions ?? [];
        questionsFill.value = root?.fill_rate ?? { rate: 0, answered: 0, total: 0 };
        questionsApplication.value = root?.application ?? null;
        if (questionsApplication.value?.id && row) {
            row.inventory_application_id = questionsApplication.value.id;
        }
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
    questionsApplication.value = null;
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
            application_id:
                questionsScope.value === 'type'
                    ? questionsApplication.value?.id || questionsType.value?.inventory_application_id
                    : undefined,
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
    vertical-align: top;
    white-space: normal;
    overflow-wrap: anywhere;
    word-break: break-word;
    max-width: 11rem;
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

.sit-cell-merged {
    vertical-align: middle;
}

.sit-cell-rate {
    min-width: 6.5rem;
    max-width: 8rem;
    white-space: nowrap;
    overflow-wrap: normal;
    word-break: normal;
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
    max-width: 8.5rem;
    vertical-align: middle;
    white-space: nowrap;
    overflow-wrap: normal;
    word-break: normal;
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
    text-shadow: 0 1px 0 rgba(0, 0, 0, 0.35);
    white-space: nowrap;
    line-height: 1.1;
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
    min-width: 7rem;
    max-width: 12rem;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.sit-cell-compact {
    max-width: 6rem;
    white-space: nowrap;
    overflow-wrap: normal;
    word-break: normal;
}

.sit-cell-solution {
    max-width: 14rem;
}

.sit-solution-q {
    border: 0;
    background: transparent;
    padding: 0;
    color: #0f4c81;
    font: inherit;
    font-weight: 600;
    text-align: left;
    cursor: pointer;
    text-decoration: underline;
    text-decoration-color: transparent;
    text-underline-offset: 0.15em;
    max-width: 100%;
    overflow-wrap: anywhere;
    word-break: break-word;
    white-space: normal;
}

.sit-solution-q:hover {
    text-decoration-color: currentColor;
}

.sit-etp {
    white-space: normal;
    min-width: 10rem;
    max-width: 14rem;
    font-size: 0.72rem;
    line-height: 1.3;
    color: #475569;
    overflow-wrap: anywhere;
    word-break: break-word;
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
    text-decoration: none;
    display: inline-flex;
    align-items: center;
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
