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
