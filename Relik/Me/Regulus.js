/*
====================================
Regulus v1.0
Behavioral Boundary Engine
====================================
*/

/*
Category Index
0 = Impuls
1 = Keraguan
2 = Perselisihan
3 = Kebohongan
*/

const Category = [
    "Impuls",
    "Keraguan",
    "Perselisihan",
    "Kebohongan"
];

/*
------------------------------------
Demon Dictionary
------------------------------------
*/

const DemonDictionary = {

    khannas: {
        name: "Khannas",
        category: 1,
        description: "Waswas yang menanamkan keraguan dalam hati.",
        source: "Quran",
        reliability: "Sahih"
    },

    walhan: {
        name: "Walhan",
        category: 1,
        description: "Waswas ketika bersuci.",
        source: "Hadith",
        reliability: "Hasan"
    },

    khinzab: {
        name: "Khinzab",
        category: 1,
        description: "Mengganggu shalat dan kekhusyukan.",
        source: "Hadith",
        reliability: "Sahih"
    },

    zalanbur: {
        name: "Zalanbur",
        category: 2,
        description: "Menghasut perselisihan dalam transaksi.",
        source: "Hadith",
        reliability: "Sahih"
    },

    awar: {
        name: "A'war",
        category: 0,
        description: "Menghias syahwat dan hawa nafsu.",
        source: "Weak",
        reliability: "Lemah"
    },

    miswat: {
        name: "Miswat",
        category: 3,
        description: "Mendorong dusta dan tipu daya.",
        source: "Weak",
        reliability: "Lemah"
    }

};

/*
------------------------------------
Category Description
------------------------------------
*/

const Regulus = [

    {
        name: "Impuls",
        description:
            "Setiap waswas yang mendorong resonansi hawa nafsu sebelum adanya pertimbangan."
    },

    {
        name: "Keraguan",
        description:
            "Setiap waswas yang mengurangi iman, keyakinan, dan kepastian amal."
    },

    {
        name: "Perselisihan",
        description:
            "Setiap waswas yang mengintervensi komunikasi sehingga memicu konflik."
    },

    {
        name: "Kebohongan",
        description:
            "Setiap waswas yang mendistorsi realitas melalui tipu daya dan pembenaran."
    }

];

/*
------------------------------------
Boundary Object
------------------------------------
*/

const AntiValue = {

    Violence: {

        regulus: [0, 2],

        values: [
            "Terorisme",
            "Anarkisme",
            "Radikalisme"
        ]
    },

    Despair: {

        regulus: [1],

        values: [
            "Fatalisme",
            "Nihilisme-Pesimis",
            "Absurdism"
        ]
    },

    SpiritualDeviation: {

        regulus: [1, 3],

        values: [
            "Gnosticism",
            "Atheism",
            "Polytheism",
            "Satanism"
        ]
    },

    Oppression: {

        regulus: [2, 3],

        values: [
            "Fasisme"
        ]
    }

};

/*
------------------------------------
Boundary Engine
------------------------------------
*/

const AlphaLeo = [

    [
        "Violence"
    ],

    [
        "Despair",
        "SpiritualDeviation"
    ],

    [
        "Violence",
        "Oppression"
    ],

    [
        "SpiritualDeviation",
        "Oppression"
    ]

];