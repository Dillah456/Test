/*
================
 Rigel v1.0
 Needs Hierarchy Engine
================
*/

const Need={
    NONE:0,
    PHYSIOLOGICAL:1,
    SAFETY:2,
    LOVE:3,
    ESTEEM:4,
    SELF_ACTUALIZATION:5
}

const NeedName=[
    "Null",
    "Physiological",
    "Safety",
    "Love & Belonging",
    "Esteem",
    "Self Actualization"
]

const GoodsCategory={
    FOOD:0,
    SHELTER:1,
    HEALTH:2,
    SECURITY:3,
    SOCIAL:4,
    EDUCATION:5,
    PRODUCTIVITY:6,
    ENTERTAINMENT:7,
    STATUS:8
}

const NeedDictionary={

    physiological:{
        id:Need.PHYSIOLOGICAL,
        priority:1,
        needs:["Makan","Minum","Tidur","Udara","Istirahat"],
        goods:[
            GoodsCategory.FOOD,
            GoodsCategory.HEALTH
        ]
    },

    safety:{
        id:Need.SAFETY,
        priority:2,
        needs:["Keamanan","Kesehatan","Rumah","Pendapatan"],
        goods:[
            GoodsCategory.SECURITY,
            GoodsCategory.SHELTER,
            GoodsCategory.HEALTH
        ]
    },

    love:{
        id:Need.LOVE,
        priority:3,
        needs:["Keluarga","Teman","Pasangan","Komunitas"],
        goods:[
            GoodsCategory.SOCIAL
        ]
    },

    esteem:{
        id:Need.ESTEEM,
        priority:4,
        needs:["Prestasi","Pengakuan","Status"],
        goods:[
            GoodsCategory.STATUS
        ]
    },

    selfActualization:{
        id:Need.SELF_ACTUALIZATION,
        priority:5,
        needs:["Belajar","Berkarya","Mencipta","Berkontribusi"],
        goods:[
            GoodsCategory.EDUCATION,
            GoodsCategory.PRODUCTIVITY
        ]
    }

}