/*
====================================
    TARF v1.0
    Regression Motivation Engine
====================================

Menggunakan Enneagram sebagai representasi
pola regresi motivasi (nafsu).

Bukan untuk mengidentifikasi kepribadian,
melainkan memetakan kecenderungan motivasi
ketika seseorang berada dalam tekanan,
konflik, atau ketidakseimbangan.
*/

/*
------------------------------------
Archetype Enum
------------------------------------
*/

const Archetype = Object.freeze({
    NONE: 0,
    REFORMER: 1,
    HELPER: 2,
    ACHIEVER: 3,
    INDIVIDUALIST: 4,
    OBSERVER: 5,
    LOYALIST: 6,
    ENTHUSIAST: 7,
    CHALLENGER: 8,
    PEACEMAKER: 9
});

/*
------------------------------------
Archetype Name
------------------------------------
*/

const ArchetypeName = [
    "Null",
    "Reformer",
    "Helper",
    "Achiever",
    "Individualist",
    "Observer",
    "Loyalist",
    "Enthusiast",
    "Challenger",
    "Peacemaker"
];

/*
------------------------------------
Enneagram Triad
------------------------------------
*/

const Triad = {

    gut: [
        Archetype.CHALLENGER,
        Archetype.PEACEMAKER,
        Archetype.REFORMER
    ],

    heart: [
        Archetype.HELPER,
        Archetype.ACHIEVER,
        Archetype.INDIVIDUALIST
    ],

    head: [
        Archetype.OBSERVER,
        Archetype.LOYALIST,
        Archetype.ENTHUSIAST
    ]

};

/*
------------------------------------
Regression State
------------------------------------
*/

const Regression = {
    current: Archetype.NONE,
    shadow: Archetype.NONE,
    aspiration: Archetype.NONE

};

/*
------------------------------------
Archetype Dictionary
------------------------------------
*/

const ArchetypeDictionary = {

    reformer: {
        id: Archetype.REFORMER,
        title: "Reformer",
        triad: "gut",
        desire: "Integrity",
        fear: "Being corrupt",
        motivation: "Improve",
        regression: "Rigid perfectionism"

    },

    helper: {
        id: Archetype.HELPER,
        triad: "heart",
        desire: "Love",
        fear: "Being unwanted",
        motivation: "Serve",
        regression: "People pleasing"

    },

    achiever: {
        id: Archetype.ACHIEVER,
        triad: "heart",
        desire: "Success",
        fear: "Failure",
        motivation: "Achievement",
        regression: "Image obsession"

    },

    individualist: {
        id: Archetype.INDIVIDUALIST,
        triad: "heart",
        desire: "Identity",
        fear: "Being insignificant",
        motivation: "Authenticity",
        regression: "Self-absorption"

    },

    observer: {
        id: Archetype.OBSERVER,
        triad: "head",
        desire: "Understanding",
        fear: "Incompetence",
        motivation: "Knowledge",
        regression: "Withdrawal"

    },

    loyalist: {
        id: Archetype.LOYALIST,
        triad: "head",
        desire: "Security",
        fear: "Uncertainty",
        motivation: "Safety",
        regression: "Anxiety"

    },

    enthusiast: {
        id: Archetype.ENTHUSIAST,
        triad: "head",
        desire: "Freedom",
        fear: "Pain",
        motivation: "Experience",
        regression: "Escapism"

    },

    challenger: {
        id: Archetype.CHALLENGER,
        triad: "gut",
        desire: "Control",
        fear: "Weakness",
        motivation: "Power",
        regression: "Domination"

    },

    peacemaker: {
        id: Archetype.PEACEMAKER,
        triad: "gut",
        desire: "Harmony",
        fear: "Conflict",
        motivation: "Stability",
        regression: "Avoidance"

    }

};

/*
------------------------------------
Example
------------------------------------

Regression.current = Archetype.LOYALIST;

console.log(
    ArchetypeName[Regression.current]
);

// Loyalist
*/