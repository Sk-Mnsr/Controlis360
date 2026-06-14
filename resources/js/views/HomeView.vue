<template>
    <div class="home-page" :style="pageBackgroundStyle">
        <div class="home-board">
            <!-- Méthodologie -->
            <nav class="home-zone home-methodology" aria-label="Documentation méthodologique">
                <HomeMenuButton
                    v-for="item in methodologyItems"
                    :key="item.id"
                    variant="methodology"
                    @click="openMethodology(item)"
                >
                    {{ item.label }}
                </HomeMenuButton>
            </nav>

            <!-- Espace laissé pour le cadran RISK (arrière-plan) -->
            <div class="home-spacer" aria-hidden="true" />

            <!-- Départements -->
            <section class="home-zone home-departments" aria-label="Départements">
                <HomeMenuButton
                    :variant="selectedDepartment === 'DASHBOARD' ? 'active' : 'default'"
                    @click="selectDepartment('DASHBOARD')"
                >
                    Dashboard
                </HomeMenuButton>

                <div class="home-departments-grid">
                    <div class="home-dept-col">
                        <HomeMenuButton
                            v-for="dept in departmentsLeft"
                            :key="dept"
                            :variant="selectedDepartment === dept ? 'active' : 'default'"
                            @click="selectDepartment(dept)"
                        >
                            {{ dept }}
                        </HomeMenuButton>
                    </div>
                    <div class="home-dept-col">
                        <HomeMenuButton
                            v-for="dept in departmentsRight"
                            :key="dept"
                            :variant="selectedDepartment === dept ? 'active' : 'default'"
                            @click="selectDepartment(dept)"
                        >
                            {{ dept }}
                        </HomeMenuButton>
                    </div>
                </div>
            </section>

            <!-- Cartographie -->
            <aside class="home-zone home-cartographie">
                <HomeMenuButton variant="cartographie" @click="openCartographie">
                    <span class="cartographie-banner" aria-hidden="true">
                        <span class="cartographie-text">Cartographie</span>
                    </span>
                    <span class="sr-only">Cartographie</span>
                </HomeMenuButton>
            </aside>
        </div>

        <p v-if="statusMessage" class="home-status">
            {{ statusMessage }}
        </p>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import HomeMenuButton from '../components/home/HomeMenuButton.vue';

const router = useRouter();
const selectedDepartment = ref('DASHBOARD');
const statusMessage = ref('');
const riskImageUrl = '/risk.png';

const pageBackgroundStyle = computed(() => ({
    backgroundImage: `linear-gradient(90deg, rgba(15,23,42,0.08) 0%, rgba(255,255,255,0.05) 40%, rgba(255,255,255,0.18) 100%), url('${riskImageUrl}')`,
    backgroundSize: 'cover',
    backgroundPosition: 'center',
    backgroundRepeat: 'no-repeat',
}));

const methodologyItems = [
    { id: 'definitions', label: 'Définitions & Objectifs', slug: 'definitions-objectifs' },
    { id: 'preambule', label: 'Préambule', slug: 'preambule' },
    { id: 'principes', label: 'Principes', slug: 'principes' },
    { id: 'echelle-pg', label: 'Echelle P & G', route: 'referentials' },
    { id: 'echelle-controle', label: 'Echelle de contrôle', route: 'referentials' },
    { id: 'matrice', label: 'Matrice des Risques', route: 'referentials' },
    { id: 'lexique', label: 'Lexique', route: 'referentials' },
    { id: 'top-risks', label: 'Plus Gros Risques', route: null },
];

const departmentsLeft = [
    'MARKETING PRODUIT',
    'AGENCES',
    'RH',
    'OPERATIONS',
    'CREDIT',
    'FINANCES & REPORT',
];

const departmentsRight = [
    'IT',
    'RECOUVREMENT',
    'PRODUITS DIGITAUX',
    'GOUVERNANCE & CI',
    'JURIDIQUE & CTX',
    'ACHATS & LOGISTIQUE',
    'CONFORMITE',
];

function openMethodology(item) {
    statusMessage.value = '';

    if (item.slug) {
        router.push({ name: 'methodology.show', params: { slug: item.slug } });
        return;
    }

    if (item.route) {
        router.push({ name: item.route });
        return;
    }

    statusMessage.value = `« ${item.label} » — contenu à intégrer (phase 2).`;
}

function selectDepartment(dept) {
    selectedDepartment.value = dept;
    statusMessage.value = dept === 'DASHBOARD'
        ? ''
        : `Département « ${dept} » — évaluation des risques à venir.`;
}

function openCartographie() {
    statusMessage.value = 'Cartographie des risques — module à venir (phase 2).';
}
</script>

<style scoped>
.home-page {
    position: relative;
    display: flex;
    width: 100%;
    min-height: 100vh;
    flex-direction: column;
    justify-content: center;
    padding: 1.25rem 1.5rem;
}

@media (min-width: 1024px) {
    .home-page {
        padding: 1.5rem 2rem 1.5rem 1.25rem;
    }
}

.home-board {
    display: grid;
    width: 100%;
    align-items: stretch;
    gap: 0.85rem;
}

@media (min-width: 1100px) {
    .home-board {
        grid-template-columns: 11.5rem minmax(14rem, 1.4fr) minmax(18rem, 26rem) 4.25rem;
        gap: 1rem 1.25rem;
        min-height: 26rem;
    }
}

@media (max-width: 1099px) {
    .home-board {
        grid-template-columns: 1fr;
        max-width: 36rem;
        margin: 0 auto;
    }

    .home-spacer {
        display: none;
    }

    .home-methodology {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.45rem;
    }

    .cartographie-banner {
        min-height: 4rem !important;
        border-radius: 0.75rem !important;
    }

    .cartographie-text {
        writing-mode: horizontal-tb !important;
        transform: none !important;
        letter-spacing: 0.18em !important;
    }
}

.home-zone {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
    border-radius: 0.875rem;
    border: 1px solid rgba(255, 255, 255, 0.35);
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(10px);
    padding: 0.65rem;
    box-shadow: 0 8px 32px rgba(15, 23, 42, 0.1);
}

.home-spacer {
    min-height: 1px;
}

.home-methodology {
    align-self: center;
}

.home-departments {
    align-self: center;
}

.home-departments-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.45rem 0.55rem;
}

.home-dept-col {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.home-cartographie {
    padding: 0.35rem;
    background: transparent;
    border: none;
    box-shadow: none;
    backdrop-filter: none;
}

.cartographie-banner {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    min-height: 100%;
    border-radius: 0.75rem;
    background: linear-gradient(180deg, rgba(74, 222, 74, 0.95) 0%, rgba(22, 163, 22, 0.98) 100%);
    border: 1px solid rgba(255, 255, 255, 0.35);
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.35),
        0 4px 20px rgba(22, 163, 22, 0.4);
}

.cartographie-text {
    color: #ffffff;
    font-size: 0.9rem;
    font-weight: 800;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    writing-mode: vertical-rl;
    text-orientation: mixed;
    transform: rotate(180deg);
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

.home-status {
    position: relative;
    z-index: 1;
    margin-top: 1rem;
    text-align: center;
    font-size: 0.8125rem;
    color: #f8fafc;
    text-shadow: 0 1px 4px rgba(15, 23, 42, 0.6);
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
</style>
