/*
================
Modul : Boreas v.2
Intelektualisasi Impuls syahwat
================

Disclaimer :
 1. Didasarkan pada riset yang ada
 2. Modul diusahakan menggeser ekspresi Behavioral Response ke Sistemik Response
*/

// Pemetaan Tipe Konten
const MediaForm = {
    Image:{
        type:['jpg','png','jpeg']
    },
    Video:{
        type:['mp4','mkv','avi']
    },
    Interactive:{
        type:['html','app','exe']
    },
    Audio:{
        type:['mp3','wav','mp4a']
    }
}

// Pembentukan fase
const Phase = ['Excite', 'Enggage','Plateu', 'Climax', 'Resolution']
const State = ['Gather','Hunt','Market','Devour'] // Dekomposisi fase Excite

// Triger aktifasi -> Jenis Trigger:Rule
const Trigger = {
    High_Stress:0, 
    Behavioral:0,
    Accumulated:0,
    Social_Initiation:0
}

// Bentuk Aktifasi
const Activation = ['Compasionate', 'Aggresive', 'Detached']
const IntimacyForm = ['Intimacy','Consentual','Power-Play','Non-Consentual','Violation']

/*
    Boreas ini menjadi Modul rekayasa afinitas antara Entitas Fiksi dengan abstraksi
    Teknik, Info, data terhadap User
*/