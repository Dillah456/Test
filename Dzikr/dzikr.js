// === Stats & Variables ===
let Hamdalah = 0;
let Tasbih = 0;
let Takbir = 0;
let Istigfar = 0;

let INT = 8;
let WIS = 7;
let arousal = 4;

let Ijtihad = 0;
let MP = 0;

// === Add Dzikr Function ===
function addDzikr(type) {
  switch(type) {
    case 'Hamdalah': Hamdalah++; updateCounter("hamdalah", Hamdalah); break;
    case 'Tasbih': Tasbih++; updateCounter("tasbih", Tasbih); break;
    case 'Takbir': Takbir++; updateCounter("takbir", Takbir); break;
    case 'Istigfar': 
      Istigfar++; 
      updateCounter("istigfar", Istigfar); 
      istigfarBonus();
      break;
  }
  checkIjtihad();
}

// === Istigfar Bonus Logic ===
function istigfarBonus() {
  // Every 100 Istigfar add 50 to Ijtihad
  while (Istigfar >= 100) {
    Ijtihad += 50;
    Istigfar -= 100;
  }

  // Every 50 Istigfar = 5 WIS
  let wisGain = Math.floor(Istigfar / 50) * 5;
  WIS += wisGain;
  updateStat("wisStat", WIS);
  updateCounter("ijtihad", Ijtihad);
  updateCounter("mp", MP);
}

// === Ijtihad & MP ===
function checkIjtihad() {
  let totalDzikr = Hamdalah + Tasbih + Takbir;
  while (totalDzikr >= 33) {
    Ijtihad += 1;
    MP += 33;
    totalDzikr -= 33;

    // reset counters proportionally
    Hamdalah = totalDzikr; Tasbih = totalDzikr; Takbir = totalDzikr;
    updateCounter("hamdalah", Hamdalah);
    updateCounter("tasbih", Tasbih);
    updateCounter("takbir", Takbir);
    updateCounter("ijtihad", Ijtihad);
    updateCounter("mp", MP);
  }
}

// === Arousal Functions ===
function awake() {
  arousal = Math.floor(Math.random() * 6) + 1;
  updateStat("arousalStat", arousal);
}

function defineArousal() {
  let val = parseInt(prompt("Enter arousal level (1–6):", arousal));
  if (!isNaN(val) && val >= 1 && val <= 6) {
    arousal = val;
    updateStat("arousalStat", arousal);
  }
}

// === Fujur Logic ===
function Fujur() {
  if (arousal === 1 && Istigfar < 50) alert("Need Istigfar at least 50");
  if (arousal === 2 && Istigfar < 100) alert("Need Istigfar at least 100");
  if (arousal === 3 && Istigfar < 500) alert("Need Istigfar above 500");

  if (arousal === 1) drawCON(6);
  if (arousal === 2) drawCON(13);
  if (arousal === 3) drawCON(17);
}

// === CON Save ===
function drawCON(threshold) {
  let roll = Math.floor(Math.random() * 20) + 1;
  if (roll >= threshold) alert(`CON Save Passed: ${roll}`);
  else alert(`CON Save Failed: ${roll}`);
}

// === Utility Functions ===
function updateCounter(id, value) { document.getElementById(id).innerText = value; }
function updateStat(id, value) { document.getElementById(id).innerText = value; }
