/** Identité plateforme client — EnchèreSN (indépendante du reste de Controlis360). */
export const ENCHERES_SN = {
    name: 'EnchèreSN',
    tagline: 'Enchères sécurisées au Sénégal et en Afrique',
    colors: {
        primary: '#0F172A',
        secondary: '#2563EB',
        accent: '#F59E0B',
        surface: '#F8FAFC',
    },
};

export const ENCHERES_CATEGORIES = [
    {
        id: 'vehicules',
        label: 'Véhicules',
        icon: '🚗',
        columns: [
            {
                title: 'Voitures',
                links: ['Toyota', 'Mercedes', 'BMW', 'Peugeot', 'Renault', 'Hyundai', 'Voitures', 'SUV', '4x4'],
            },
            {
                title: 'Utilitaires',
                links: ['Pick-up', 'Camions', 'Bus', 'Minibus', 'Fourgonnettes', 'Véhicules société', 'Utilitaires'],
            },
            {
                title: 'Autres',
                links: ['Véhicules accidentés', 'Véhicules de saisie', 'Véhicules d\'occasion', 'Véhicules d\'entreprise'],
            },
        ],
        subcategories: ['Voitures', 'SUV', '4x4', 'Pick-up', 'Camions', 'Bus', 'Minibus', 'Utilitaires', 'Véhicules de société', 'Véhicules accidentés', 'Véhicules d\'occasion'],
    },
    {
        id: 'motos',
        label: 'Motos',
        icon: '🏍️',
        columns: [
            { title: 'Deux-roues', links: ['Motos', 'Scooters', 'Tricycles', 'Quads'] },
            { title: 'Usage', links: ['Motos de livraison', 'Motos d\'occasion'] },
        ],
        subcategories: ['Motos', 'Scooters', 'Tricycles', 'Quads', 'Motos de livraison', 'Motos d\'occasion'],
    },
    {
        id: 'maisons',
        label: 'Maisons',
        icon: '🏠',
        columns: [
            { title: 'Résidentiel', links: ['Maisons', 'Villas', 'Appartements', 'Studios', 'Résidences'] },
        ],
        subcategories: ['Maisons', 'Villas', 'Appartements', 'Studios', 'Résidences'],
    },
    {
        id: 'immeubles',
        label: 'Immeubles',
        icon: '🏢',
        columns: [
            { title: 'Immeubles', links: ['Immeubles', 'Résidences', 'Copropriétés'] },
        ],
        subcategories: ['Immeubles', 'Résidences'],
    },
    {
        id: 'terrains',
        label: 'Terrains',
        icon: '🌳',
        columns: [
            { title: 'Terrains', links: ['Terrains', 'Terrains agricoles', 'Terrains industriels'] },
        ],
        subcategories: ['Terrains', 'Terrains agricoles', 'Terrains industriels'],
    },
    {
        id: 'locaux',
        label: 'Locaux commerciaux',
        icon: '🏗️',
        columns: [
            { title: 'Professionnel', links: ['Locaux commerciaux', 'Bureaux', 'Entrepôts'] },
        ],
        subcategories: ['Locaux commerciaux', 'Bureaux', 'Entrepôts'],
    },
    {
        id: 'engins',
        label: 'Engins & Machines',
        icon: '🚜',
        columns: [
            { title: 'Engins', links: ['Tracteurs', 'Engins BTP', 'Pelleteuses', 'Bulldozers'] },
            { title: 'Industrie', links: ['Groupes électrogènes', 'Machines industrielles', 'Matériel agricole'] },
        ],
        subcategories: ['Tracteurs', 'Engins BTP', 'Pelleteuses', 'Bulldozers', 'Groupes électrogènes', 'Machines industrielles', 'Matériel agricole'],
    },
    {
        id: 'electronique',
        label: 'Électronique',
        icon: '📱',
        columns: [
            { title: 'IT & réseau', links: ['Ordinateurs', 'Téléphones', 'Télévisions', 'Imprimantes', 'Serveurs', 'Équipements réseau'] },
        ],
        subcategories: ['Ordinateurs', 'Téléphones', 'Télévisions', 'Imprimantes', 'Serveurs', 'Équipements réseau'],
    },
    {
        id: 'mobilier',
        label: 'Mobilier',
        icon: '🪑',
        columns: [
            { title: 'Mobilier', links: ['Meubles', 'Bureaux', 'Chaises', 'Armoires', 'Mobilier professionnel', 'Mobilier de maison'] },
        ],
        subcategories: ['Meubles', 'Bureaux', 'Chaises', 'Armoires', 'Mobilier professionnel', 'Mobilier de maison'],
    },
    {
        id: 'materiel-pro',
        label: 'Matériel professionnel',
        icon: '💼',
        columns: [{ title: 'Pro', links: ['Matériel professionnel', 'Équipements de bureau'] }],
        subcategories: ['Matériel professionnel'],
    },
    {
        id: 'bijoux',
        label: 'Bijoux & objets de valeur',
        icon: '💎',
        columns: [{ title: 'Valeur', links: ['Bijoux', 'Montres', 'Objets de collection'] }],
        subcategories: ['Bijoux', 'Objets de valeur'],
    },
    {
        id: 'divers',
        label: 'Divers',
        icon: '📦',
        columns: [{ title: 'Divers', links: ['Lots divers', 'Stock', 'Invendus'] }],
        subcategories: ['Divers'],
    },
    {
        id: 'judiciaires',
        label: 'Ventes judiciaires',
        icon: '🔨',
        columns: [{ title: 'Judiciaire', links: ['Ventes judiciaires', 'Saisies', 'Liquidations'] }],
        subcategories: ['Ventes judiciaires', 'Saisies', 'Liquidations'],
    },
    {
        id: 'banques',
        label: 'Ventes de banques',
        icon: '🏦',
        columns: [{ title: 'Banques', links: ['Ventes bancaires', 'Recouvrement', 'Actifs saisis'] }],
        subcategories: ['Ventes bancaires'],
    },
    {
        id: 'entreprises',
        label: 'Ventes d\'entreprises',
        icon: '🏢',
        columns: [{ title: 'Entreprises', links: ['Ventes d\'entreprises', 'Fonds de commerce', 'Actifs'] }],
        subcategories: ['Ventes d\'entreprises'],
    },
    {
        id: 'autres',
        label: 'Autres enchères',
        icon: '⭐',
        columns: [{ title: 'Autres', links: ['Ventes institutionnelles', 'Autres enchères'] }],
        subcategories: ['Autres enchères', 'Ventes institutionnelles'],
    },
];

export const ENCHERES_REGIONS = ['Dakar', 'Thiès', 'Saint-Louis', 'Kaolack', 'Ziguinchor', 'Louga', 'Tambacounda'];

export const ENCHERES_STATUSES = {
    live: 'Enchère en cours',
    ending: 'Se termine bientôt',
    ended: 'Terminée',
    pending: 'À venir',
};
