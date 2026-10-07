/** Données fictives — remplacées progressivement par l’API Laravel. */

/**
 * Photos thématiques locales (public/images/encheres) alignées sur chaque lot.
 * Évite picsum (aléatoire) et Unsplash (souvent 404).
 */
const THEMED_IMAGES = {
    'land-cruiser': '/images/encheres/land-cruiser.jpg',
    'land-cruiser-2': '/images/encheres/land-cruiser-2.jpg',
    'villa-almadies': '/images/encheres/villa-almadies.jpg',
    'yamaha-mt07': '/images/encheres/yamaha-mt07.jpg',
    'terrain-rufisque': '/images/encheres/terrain-rufisque.jpg',
    'pelleteuse-cat': '/images/encheres/pelleteuse-cat.jpg',
    'lot-informatique': '/images/encheres/lot-informatique.jpg',
};

export function auctionImage(seed) {
    if (THEMED_IMAGES[seed]) {
        return THEMED_IMAGES[seed];
    }
    const label = String(seed || 'Lot').replace(/</g, '');
    return 'data:image/svg+xml,' + encodeURIComponent(`
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="520" viewBox="0 0 800 520">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#0f172a"/>
      <stop offset="100%" stop-color="#2563eb"/>
    </linearGradient>
  </defs>
  <rect width="800" height="520" fill="url(#g)"/>
  <text x="400" y="250" fill="#f8fafc" font-family="Arial,sans-serif" font-size="26" font-weight="700" text-anchor="middle">EnchèreSN</text>
  <text x="400" y="295" fill="#f59e0b" font-family="Arial,sans-serif" font-size="18" text-anchor="middle">${label}</text>
</svg>
`.trim());
}

export const PLACEHOLDER_IMAGE = 'data:image/svg+xml,' + encodeURIComponent(`
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="520" viewBox="0 0 800 520">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#0f172a"/>
      <stop offset="100%" stop-color="#2563eb"/>
    </linearGradient>
  </defs>
  <rect width="800" height="520" fill="url(#g)"/>
  <text x="400" y="250" fill="#f8fafc" font-family="Arial,sans-serif" font-size="28" font-weight="700" text-anchor="middle">EnchèreSN</text>
  <text x="400" y="290" fill="#f59e0b" font-family="Arial,sans-serif" font-size="16" text-anchor="middle">Image indisponible</text>
</svg>
`.trim());

function hoursFromNow(h) {
    return new Date(Date.now() + h * 3600 * 1000).toISOString();
}

function daysFromNow(d, h = 0) {
    return new Date(Date.now() + (d * 24 + h) * 3600 * 1000).toISOString();
}

export const MOCK_AUCTIONS = [
    {
        id: '2026-001',
        lot: 'LOT #2026-001',
        title: 'Toyota Land Cruiser V8',
        category_id: 'vehicules',
        category_label: 'Véhicules',
        subcategory: '4x4',
        location: 'Dakar',
        region: 'Dakar',
        city: 'Dakar',
        starting_price: 15000000,
        current_price: 18500000,
        min_increment: 250000,
        bid_count: 23,
        status: 'live',
        ends_at: hoursFromNow(2.57),
        image: auctionImage('land-cruiser'),
        images: [auctionImage('land-cruiser'), auctionImage('land-cruiser-2')],
        seller_type: 'Banque',
        condition: 'Bon état',
        year: 2019,
        brand: 'Toyota',
        model: 'Land Cruiser',
        description: 'Véhicule entretenu, dossier complet, contrôle technique à jour.',
        bids: [
            { id: 1, user_ref: 'Utilisateur #A821', amount: 18500000, at: '2026-09-15T18:42:00+00:00', status: 'Gagnante' },
            { id: 2, user_ref: 'Utilisateur #B104', amount: 18250000, at: '2026-09-15T18:38:00+00:00', status: 'Dépassée' },
            { id: 3, user_ref: 'Utilisateur #C552', amount: 18000000, at: '2026-09-15T18:20:00+00:00', status: 'Dépassée' },
        ],
    },
    {
        id: '2026-002',
        lot: 'LOT #2026-002',
        title: 'Villa 5 pièces — Almadies',
        category_id: 'maisons',
        category_label: 'Maisons',
        subcategory: 'Villas',
        location: 'Almadies, Dakar',
        region: 'Dakar',
        city: 'Dakar',
        starting_price: 85000000,
        current_price: 92000000,
        min_increment: 500000,
        bid_count: 11,
        status: 'live',
        ends_at: daysFromNow(2, 4),
        image: auctionImage('villa-almadies'),
        images: [auctionImage('villa-almadies')],
        seller_type: 'Particulier',
        condition: 'Excellent',
        description: 'Villa avec piscine, titre foncier disponible.',
        bids: [],
    },
    {
        id: '2026-003',
        lot: 'LOT #2026-003',
        title: 'Yamaha MT-07',
        category_id: 'motos',
        category_label: 'Motos',
        subcategory: 'Motos',
        location: 'Thiès',
        region: 'Thiès',
        city: 'Thiès',
        starting_price: 1200000,
        current_price: 1450000,
        min_increment: 50000,
        bid_count: 8,
        status: 'live',
        ends_at: hoursFromNow(5.2),
        image: auctionImage('yamaha-mt07'),
        images: [auctionImage('yamaha-mt07')],
        seller_type: 'Entreprise',
        condition: 'Très bon état',
        year: 2021,
        brand: 'Yamaha',
        model: 'MT-07',
        bids: [],
    },
    {
        id: '2026-004',
        lot: 'LOT #2026-004',
        title: 'Terrain 500 m² — Rufisque',
        category_id: 'terrains',
        category_label: 'Terrains',
        subcategory: 'Terrains',
        location: 'Rufisque',
        region: 'Dakar',
        city: 'Rufisque',
        starting_price: 25000000,
        current_price: 25000000,
        min_increment: 200000,
        bid_count: 0,
        status: 'live',
        ends_at: daysFromNow(5),
        image: auctionImage('terrain-rufisque'),
        images: [auctionImage('terrain-rufisque')],
        seller_type: 'Notaire',
        bids: [],
    },
    {
        id: '2026-005',
        lot: 'LOT #2026-005',
        title: 'Pelleteuse Caterpillar 320',
        category_id: 'engins',
        category_label: 'Engins & Machines',
        subcategory: 'Pelleteuses',
        location: 'Dakar',
        region: 'Dakar',
        city: 'Dakar',
        starting_price: 45000000,
        current_price: 47200000,
        min_increment: 500000,
        bid_count: 5,
        status: 'live',
        ends_at: daysFromNow(1, 8),
        image: auctionImage('pelleteuse-cat'),
        images: [auctionImage('pelleteuse-cat')],
        seller_type: 'Liquidation',
        bids: [],
    },
    {
        id: '2026-006',
        lot: 'LOT #2026-006',
        title: 'Lot informatique entreprise (30 postes)',
        category_id: 'electronique',
        category_label: 'Électronique',
        subcategory: 'Ordinateurs',
        location: 'Dakar Plateau',
        region: 'Dakar',
        city: 'Dakar',
        starting_price: 3200000,
        current_price: 3650000,
        min_increment: 100000,
        bid_count: 14,
        status: 'live',
        ends_at: hoursFromNow(12),
        image: auctionImage('lot-informatique'),
        images: [auctionImage('lot-informatique')],
        seller_type: 'Entreprise',
        bids: [],
    },
];

export function formatFcfa(amount) {
    return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(amount) + ' FCFA';
}

export function nextMinBid(auction) {
    return (auction.current_price || auction.starting_price) + (auction.min_increment || 100000);
}
