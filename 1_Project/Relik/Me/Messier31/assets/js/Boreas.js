/*
================
Modul : Boreas v.2
Intelektualisasi Impuls syahwat
================

Disclaimer :
 1. Didasarkan pada riset yang ada
 2. Modul diusahakan menggeser ekspresi Behavioral Response ke Sistemik Response
 3. Boreas termasuk modul Original untuk area Personal karena pembahasan akademik mempertanyakan moral
 4. 
*/
 
/*
===============================

Fase Deklarasi

===============================
Pada fase ini fokus nya tentu pada pembentukan prosedur.
*/
let Trust = 0; 
// Konstanta Inisiasi yang berperan sebagai nilai Toleransi Trigger, dan Intimacy Form
let Sanity = 0; 
// Hasil Rasio dari Kondisi Ideal terhadap kondisi Entitas
let Guilt = 0; 
// Inisiasi Konstanta, semakin besar nilai variabel ini, semakin besar 'Selisih' dari Sanity terhadap Kondisi Ideal
let dmgVal = 0;
// Variabel basis D20, menggunakan basis D20 terhadap efek yang dibawa

// Pembentukan fase
const Phase = ['Excite', 'Enggage','Plateu', 'Climax', 'Resolution']
const State = ['Gather','Hunt','Market','Devour'] // Dekomposisi fase Excite


// Triger aktifasi -> Jenis Trigger:Rule

/*
    Trigger -> Nilai aktivasi Boreas oleh User
    Para_System -> 
        MEEF based Module, untuk simulasi Historical pada 2nd Brain
*/
const Trigger = {
    High_Stress:0, 
    Behavioral:0,
    Accumulated:0,
    Social_Initiation:0
}

const IntimacyForm = {
    Intimacy:{
        desc:'Intimacy',
        value:Trust
    },
    Consentual:{
        desc:'Consentual',
        value:Trust
    },
    Power_Play:{
        desc:'Power-Play',
        value:Trust
    },
    Non_Consentual:{
        desc:'Non-Consentual',
        value:Trust
    },
    Violation:{
        desc:'Violation',
        value:Trust
    }
}

/*
    Boreas ini menjadi Modul rekayasa afinitas antara Entitas Fiksi dengan abstraksi
    Teknik, Info, data terhadap User
*/

const ActivForm = {
    1:{
        'Category':'Compassionate',
        'Trigger':Trigger.Social_Initiation,
        'Form': IntimacyForm.Intimacy
    },
    2:{
        'Category':'Compassionate',
        'Trigger':Trigger.Accumulated,
        'Form': IntimacyForm.Consentual
    },
    3:{
        'Category':'Compassionate',
        'Trigger': Trigger.Social_Initiation,
        'Form': IntimacyForm.Power_Play
    },
    4:{
        'Category':'Aggresive',
        'Trigger': Trigger.Accumulated,
        'Form': IntimacyForm.Power_Play
    },
    5:{
        'Category':'Aggresive',
        'Trigger': Trigger.Behavioral,
        'Form': IntimacyForm.Power_Play
    },
    6:{
        'Category':'Aggresive',
        'Trigger': Trigger.Behavioral,
        'Form': IntimacyForm.Non_Consentual
    },
    7:{
        'Category':'Aggresive',
        'Trigger': Trigger.High_Stress,
        'Form': IntimacyForm.Non_Consentual
    },
    8:{
        'Category':'Aggresive',
        'Trigger': Trigger.High_Stress,
        'Form': IntimacyForm.Violation
    },
    9:{
        'Category':'Aggresive',
        'Trigger': Trigger.Behavioral,
        'Form': IntimacyForm.Violation
    },
    10:{
        'Category':'Detached',
        'Trigger': Trigger.High_Stress,
        'Form': IntimacyForm.Power_Play
    },
    11:{
        'Category':'Detached',
        'Trigger': Trigger.High_Stress,
        'Form': IntimacyForm.Non_Consentual
    }
}

/*
==============

Fase Abstraksi

==============
Pada fase Abstraksi ini difokuskan pada pemetaan 
Trust, Sanity, Guilt kepada Presentation layer
*/

// Pemetaan Tipe Konten
const MediaForm = {
    Image:{
        type:['jpg','png','jpeg'],
        dmgVal:{
            min:6,
            avg:10,
            max:16
        }
    },
    Video:{
        type:['mp4','mkv','avi'],
        dmgVal:{
            min:6,
            avg:10,
            max:18
        }
    },
    Interactive:{
        type:['html','app','exe'],
        dmgVal:{
            min:10,
            avg:17,
            max:20
        }
    },
    Audio:{
        type:['mp3','wav','mp4a'],
        dmgVal:{
            min:5,
            avg:10,
            max:15
        }
    }
}

// Catatan 100% konten dari sumber luar yang di internalisasi

/*
Fase Ruminasi (Log : 0627 20260710)
Masalah -> Pemetaan entitas terhadap Konten tidak diketahui
Solusi -> Penggunaan Modul abstrak dari imajinasi Replicia sebelumnya
Konklusi : 
    1. Boreas sebagai Modul kehilangan Deppendency tanpa modul GJ3991 
    2. Boreas hanya sebuah alat klasifikasi pada pengukuran Entitas
    3. dan Menyimpan ActivForm yang bisa diterjemahkan ke Transaksi mental

Deklarasi Ulang : 
    1. Batas Boreas adalah Simplifikasi kompleksitas hubungan dengan T18
    2. T18 masih dianggap Random, dan Kompleks tapi disederhanakan Boreas
    3. Boreas hanya memetakan kompleksitas tersebut sebagai batas mental
    4. Aksi Entitas diluar kontrol Boreas
*/


/*
    Para System dipakai sebagai media klasifikasi Entitas berdasar 4 Kategori.
    Pada masa Awal T18 di tahun 2022 sering dipakai untuk mendefinisikan 3 komponen tersebut,
    Saintyfy -> Entitas tidak boleh sama sekali disentuh secara Moral
    Sucubi -> Efek dari media luar, Entitas sulit untuk dipertahankan nilai moralnya
    Valkyrie -> proses pemberian trust modul/relik kepada entitas afinitas biasanya Sahabat dekat jika di House 12
    eve -> Entitas yang dianggap ideal sebagai keluarga di House 12 bukan House 4, House 4 harus Konkret
*/
const Para_System = {
    saintfy:{
        trust:10,
        guilt:10,
        sanity:10
    },
    sucubi:{
        trust:3,
        guilt:3,
        sanity:2
    },
    valkyrie:{
        trust:6,
        guilt:4,
        sanity:5
    },
    eve:{
        trust:10,
        guilt:1,
        sanity:10
    }
}
