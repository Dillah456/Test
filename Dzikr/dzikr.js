// Stats
let INT=0, WIS=0, Arousal=0, CON=0;
let Hamdalah=0, Tasbih=0, Takbir=0, Istigfar=0;
let Ijtihad=0, MP=0;

// Add dzikr
function addDzikr(type){
  if(type==="Hamdalah") Hamdalah++; 
  if(type==="Tasbih") Tasbih++; 
  if(type==="Takbir") Takbir++; 
  if(type==="Istigfar") Istigfar++;
  
  document.getElementById("hamdalah").innerText=Hamdalah;
  document.getElementById("tasbih").innerText=Tasbih;
  document.getElementById("takbir").innerText=Takbir;
  document.getElementById("istigfar").innerText=Istigfar;

  // Istigfar bonus
  while(Istigfar>=100){
    Ijtihad+=50;
    Istigfar-=100;
    notify("Ijtihad +50 from Istigfar");
  }

  // Total dzikr 33 pts
  let dzikrTotal=Hamdalah+Tasbih+Takbir;
  while(dzikrTotal>=33){
    Ijtihad+=1;
    MP+=33;
    dzikrTotal-=33;
  }
  document.getElementById("ijtihad").innerText=Ijtihad;
  updateMP(MP);
}

// Sync MP
function updateMP(val){
  MP=val;
  document.getElementById("mp").innerText=MP;
  document.getElementById("mp2").innerText=MP;
}

// Update stat
function updateStat(id,val){ document.getElementById(id).innerText=val; }

// Awake
function awake(){
  if(Arousal>=5) Arousal=Math.floor(Math.random()*4)+1;
  else Arousal=Math.floor(Math.random()*5)+1;
  updateStat("arousalStat",Arousal);
  if(Arousal>=5 && Ijtihad>50) notify("Guilt");
}

// Define Arousal
function defineArousal(){
  let val=prompt("Enter Arousal (1-6)","");
  val=parseInt(val);
  if(isNaN(val)||val<1) val=1;
  if(val>6) val=6;
  Arousal=val;
  updateStat("arousalStat",Arousal);
}

// Save INT/WIS
function saveStats(){
  INT=Math.floor(Math.random()*20)+1;
  WIS=Math.floor(Math.random()*20)+1;
  updateStat("intStat",INT);
  updateStat("wisStat",WIS);
  notify(`INT: ${INT}, WIS: ${WIS}`);
}

// Heal
function Heal(){
  if(MP>=9){
    MP-=9;
    INT+=10; WIS+=10;
    updateStat("intStat",INT);
    updateStat("wisStat",WIS);
    updateMP(MP);
    notify("Healed INT/WIS by 10 (-9 MP)");
  } else notify("Not enough MP!");
}

// Fight
function Fight(){
  if(INT<5 && WIS<5 && MP<5){
    let choice=confirm("Low stats! OK: Devour State, Cancel: Master State");
    if(choice){
      window.open("https://urakawahanako.github.io/D18/Aset/lost_data.html","_blank");
      Arousal=4;
    }else{
      window.open("https://urakawahanako.github.io/D18/Aset/lost_data_r.html","_blank"); // placeholder Master link
      Arousal=5;
    }
    updateStat("arousalStat",Arousal);
    notify("Arousal changed due to state selection");
    return;
  }

  if(Arousal<3){
    if(Arousal==1){ INT=Math.max(0,INT-10); WIS=Math.max(0,WIS-10); notify("Fight: INT/WIS -10"); }
    if(Arousal==2){ INT=Math.max(0,INT-20); WIS=Math.max(0,WIS-20); notify("Fight: INT/WIS -20"); }
    updateStat("intStat",INT);
    updateStat("wisStat",WIS);
  }
}

// Firebase v12 modular imports (satu kali, tidak duplikat)
  import { initializeApp } from "https://www.gstatic.com/firebasejs/12.3.0/firebase-app.js";
  import { getAnalytics } from "https://www.gstatic.com/firebasejs/12.3.0/firebase-analytics.js";
  import { getDatabase, ref, set, get, update, remove } from "https://www.gstatic.com/firebasejs/12.3.0/firebase-database.js";

  // === CONFIG: gunakan nilai yang benar dari project Firebase-mu ===
  // Aku ambil dari filemu; periksa kembali storageBucket jika perlu
  const firebaseConfig = {
    apiKey: "AIzaSyDHA88x1Xdkwz9mKkD_Mo3gMV7V8by7cwY",
    authDomain: "shangri-la-e2628.firebaseapp.com",
    databaseURL: "https://shangri-la-e2628-default-rtdb.firebaseio.com",
    projectId: "shangri-la-e2628",
    // Catatan: biasanya storageBucket berakhiran 'appspot.com'
    storageBucket: "shangri-la-e2628.appspot.com",
    messagingSenderId: "270526568319",
    appId: "1:270526568319:web:5e61baa01f91e9efa2ec4a",
    measurementId: "G-4WE22LBJ7V"
  };

  // Initialize Firebase (sekali)
  const app = initializeApp(firebaseConfig);
  // analytics mungkin gagal pada environment tertentu — bungkus try/catch
  try { getAnalytics(app); } catch (e) { console.warn("Analytics init failed:", e); }

  // Database
  const db = getDatabase(app);

  // Pastikan variabel aplikasi ada; ambil dari DOM jika tersedia
  // Jika dzikr.js sudah mengelola variabel ini, ini hanya fallback agar tidak undefined
  window.Ijtihad = typeof window.Ijtihad !== 'undefined' ? window.Ijtihad : parseInt(document.getElementById("ijtihad")?.innerText || "0", 10);
  window.MP = typeof window.MP !== 'undefined' ? window.MP : parseInt(document.getElementById("mp")?.innerText || document.getElementById("mp2")?.innerText || "0", 10);

  // Utility: update MP pada DOM (dan sinkronisasi kecil)
  function updateMPDisplay(newMP) {
    window.MP = Number(newMP) || 0;
    const el1 = document.getElementById("mp");
    const el2 = document.getElementById("mp2");
    if (el1) el1.innerText = window.MP;
    if (el2) el2.innerText = window.MP;
  }
  // expose supaya file lain bisa pakai
  window.updateMP = updateMPDisplay;

  // --- Firebase operations ---
  // Save progress (overwrite at path progress/1)
  async function saveProgress() {
    try {
      const payload = {
        ijtihad: Number(window.Ijtihad) || 0,
        mp: Number(window.MP) || 0,
        updatedAt: Date.now()
      };
      await set(ref(db, "progress/1"), payload);
      notify("Data saved to Firebase!");
    } catch (err) {
      console.error("saveProgress error:", err);
      notify("Gagal menyimpan: " + (err.message || err));
    }
  }

  // Load progress (jika ada)
  async function loadProgress() {
    try {
      const snapshot = await get(ref(db, "progress/1"));
      if (snapshot.exists()) {
        const data = snapshot.val();
        window.Ijtihad = Number(data.ijtihad) || 0;
        updateMPDisplay(Number(data.mp) || 0);

        // update DOM ijtihad jika ada
        const ijEl = document.getElementById("ijtihad");
        if (ijEl) ijEl.innerText = window.Ijtihad;

        notify("Data loaded from Firebase!");
      } else {
        notify("Tidak ada data yang ditemukan di Firebase.");
      }
    } catch (err) {
      console.error("loadProgress error:", err);
      notify("Gagal memuat: " + (err.message || err));
    }
  }

  // Update sebagian (contoh: hanya MP)
  async function updateMPFirebase(newMP) {
    try {
      newMP = Number(newMP) || 0;
      await update(ref(db, "progress/1"), { mp: newMP, updatedAt: Date.now() });
      updateMPDisplay(newMP);
      notify("MP updated in Firebase!");
    } catch (err) {
      console.error("updateMPFirebase error:", err);
      notify("Gagal update MP: " + (err.message || err));
    }
  }

  // Hapus data
  async function deleteProgress() {
    try {
      await remove(ref(db, "progress/1"));
      // reset lokal
      window.Ijtihad = 0;
      updateMPDisplay(0);
      const ijEl = document.getElementById("ijtihad");
      if (ijEl) ijEl.innerText = "0";
      notify("Progress deleted from Firebase!");
    } catch (err) {
      console.error("deleteProgress error:", err);
      notify("Gagal menghapus: " + (err.message || err));
    }
  }

  // Expose fungsi agar bisa dipanggil dari HTML tombol
  window.saveProgress = saveProgress;
  window.loadProgress = loadProgress;
  window.updateMPFirebase = updateMPFirebase;
  window.deleteProgress = deleteProgress;

  // OPTIONAL: load on start (jika mau otomatis load saat halaman dibuka)
  // Uncomment baris ini bila ingin auto-load:
  // loadProgress();


