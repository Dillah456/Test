/* ============================================================
   MESSIER 31 — main.js
   Boundary Value Observatory

   Data models below are carried over from:
     - Regulus.js  (Behavioral Boundary Engine — defines AntiValue)
     - Rigel.js    (Needs Hierarchy Engine)
     - Tarf.js     (Regression Motivation Engine / Enneagram)
     - Sertan.js   (Life Domain / House map)

   Boreas.js is intentionally NOT included. It is not part of
   this dashboard's brief.
   ============================================================ */

/* ---------- REGULUS: Behavioral Boundary Engine ---------- */

const Category = ["Impuls", "Keraguan", "Perselisihan", "Kebohongan"];

const CategoryDescription = {
  Impuls: "Setiap waswas yang mendorong resonansi hawa nafsu sebelum adanya pertimbangan.",
  Keraguan: "Setiap waswas yang mengurangi iman, keyakinan, dan kepastian amal.",
  Perselisihan: "Setiap waswas yang mengintervensi komunikasi sehingga memicu konflik.",
  Kebohongan: "Setiap waswas yang mendistorsi realitas melalui tipu daya dan pembenaran."
};

const DemonDictionary = {
  khannas:  { name: "Khannas", category: 1, description: "Waswas yang menanamkan keraguan dalam hati.", source: "Quran", reliability: "Sahih" },
  walhan:   { name: "Walhan", category: 1, description: "Waswas ketika bersuci.", source: "Hadith", reliability: "Hasan" },
  khinzab:  { name: "Khinzab", category: 1, description: "Mengganggu shalat dan kekhusyukan.", source: "Hadith", reliability: "Sahih" },
  zalanbur: { name: "Zalanbur", category: 2, description: "Menghasut perselisihan dalam transaksi.", source: "Hadith", reliability: "Sahih" },
  awar:     { name: "A'war", category: 0, description: "Menghias syahwat dan hawa nafsu.", source: "Weak", reliability: "Lemah" },
  miswat:   { name: "Miswat", category: 3, description: "Mendorong dusta dan tipu daya.", source: "Weak", reliability: "Lemah" }
};

const AntiValue = {
  Violence:            { regulus: [0, 2], values: ["Terorisme", "Anarkisme", "Radikalisme"] },
  Despair:             { regulus: [1],    values: ["Fatalisme", "Nihilisme-Pesimis", "Absurdism"] },
  SpiritualDeviation:  { regulus: [1, 3], values: ["Gnosticism", "Atheism", "Polytheism", "Satanism"] },
  Oppression:          { regulus: [2, 3], values: ["Fasisme"] }
};

// AlphaLeo[categoryIndex] -> which AntiValue groups that category can feed
const AlphaLeo = [
  ["Violence"],
  ["Despair", "SpiritualDeviation"],
  ["Violence", "Oppression"],
  ["SpiritualDeviation", "Oppression"]
];

/* ---------- RIGEL: Needs Hierarchy Engine ---------- */

const Need = { NONE: 0, PHYSIOLOGICAL: 1, SAFETY: 2, LOVE: 3, ESTEEM: 4, SELF_ACTUALIZATION: 5 };
const NeedName = ["Null", "Physiological", "Safety", "Love & Belonging", "Esteem", "Self Actualization"];

const NeedDictionary = {
  physiological:     { id: Need.PHYSIOLOGICAL, priority: 1, needs: ["Makan", "Minum", "Tidur", "Udara", "Istirahat"] },
  safety:            { id: Need.SAFETY, priority: 2, needs: ["Keamanan", "Kesehatan", "Rumah", "Pendapatan"] },
  love:              { id: Need.LOVE, priority: 3, needs: ["Keluarga", "Teman", "Pasangan", "Komunitas"] },
  esteem:            { id: Need.ESTEEM, priority: 4, needs: ["Prestasi", "Pengakuan", "Status"] },
  selfActualization: { id: Need.SELF_ACTUALIZATION, priority: 5, needs: ["Belajar", "Berkarya", "Mencipta", "Berkontribusi"] }
};

/* ---------- TARF: Regression Motivation Engine ---------- */

const Archetype = Object.freeze({
  NONE: 0, REFORMER: 1, HELPER: 2, ACHIEVER: 3, INDIVIDUALIST: 4,
  OBSERVER: 5, LOYALIST: 6, ENTHUSIAST: 7, CHALLENGER: 8, PEACEMAKER: 9
});

const ArchetypeName = ["Null", "Reformer", "Helper", "Achiever", "Individualist", "Observer", "Loyalist", "Enthusiast", "Challenger", "Peacemaker"];

const ArchetypeDictionary = {
  reformer:      { id: Archetype.REFORMER, triad: "gut", desire: "Integrity", fear: "Being corrupt", motivation: "Improve", regression: "Rigid perfectionism" },
  helper:        { id: Archetype.HELPER, triad: "heart", desire: "Love", fear: "Being unwanted", motivation: "Serve", regression: "People pleasing" },
  achiever:      { id: Archetype.ACHIEVER, triad: "heart", desire: "Success", fear: "Failure", motivation: "Achievement", regression: "Image obsession" },
  individualist: { id: Archetype.INDIVIDUALIST, triad: "heart", desire: "Identity", fear: "Being insignificant", motivation: "Authenticity", regression: "Self-absorption" },
  observer:      { id: Archetype.OBSERVER, triad: "head", desire: "Understanding", fear: "Incompetence", motivation: "Knowledge", regression: "Withdrawal" },
  loyalist:      { id: Archetype.LOYALIST, triad: "head", desire: "Security", fear: "Uncertainty", motivation: "Safety", regression: "Anxiety" },
  enthusiast:    { id: Archetype.ENTHUSIAST, triad: "head", desire: "Freedom", fear: "Pain", motivation: "Experience", regression: "Escapism" },
  challenger:    { id: Archetype.CHALLENGER, triad: "gut", desire: "Control", fear: "Weakness", motivation: "Power", regression: "Domination" },
  peacemaker:    { id: Archetype.PEACEMAKER, triad: "gut", desire: "Harmony", fear: "Conflict", motivation: "Stability", regression: "Avoidance" }
};

/* ---------- SERTAN: Life Domain map ---------- */

const Domain = [
  "Self", "Resource", "Learning", "Home", "Creativity", "Routine",
  "Relationship", "Crisis", "Philosophy", "Career", "Society", "InnerWorld"
];

/* ============================================================
   APP STATE + PERSISTENCE
   ============================================================ */

const STORAGE_KEY = "messier31.entries";
const DRAFT_KEY = "messier31.draft";

function loadEntries() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch (e) {
    console.error("Messier31: failed to load entries", e);
    return [];
  }
}

function saveEntries(entries) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(entries));
}

function loadDraft() {
  try {
    const raw = localStorage.getItem(DRAFT_KEY);
    return raw ? JSON.parse(raw) : null;
  } catch (e) {
    return null;
  }
}

function saveDraft(draft) {
  if (draft) {
    localStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
  } else {
    localStorage.removeItem(DRAFT_KEY);
  }
}

let entries = loadEntries();
let editingId = null;

/* ============================================================
   RENDER: BOUNDARY VALUE EXPLAINER
   ============================================================ */

function renderBoundaryExplainer() {
  const catWrap = document.getElementById("category-list");
  catWrap.innerHTML = "";
  Category.forEach((cat, i) => {
    const demonsInCat = Object.values(DemonDictionary).filter(d => d.category === i);
    const feeds = AlphaLeo[i] || [];
    const el = document.createElement("div");
    el.className = "cat-card";
    el.innerHTML = `
      <div class="cat-index">${String(i).padStart(2, "0")}</div>
      <h3>${cat}</h3>
      <p class="cat-desc">${CategoryDescription[cat]}</p>
      <div class="cat-demons">
        ${demonsInCat.map(d => `
          <div class="demon-chip" title="${d.description}">
            <span class="demon-name">${d.name}</span>
            <span class="demon-rel rel-${d.reliability.toLowerCase()}">${d.reliability}</span>
          </div>`).join("") || '<span class="muted">— no entries —</span>'}
      </div>
      <div class="feeds-row">
        <span class="feeds-label">feeds →</span>
        ${feeds.map(f => `<span class="feed-tag tag-${f}">${f}</span>`).join("")}
      </div>
    `;
    catWrap.appendChild(el);
  });

  const avWrap = document.getElementById("antivalue-list");
  avWrap.innerHTML = "";
  Object.entries(AntiValue).forEach(([key, av]) => {
    const el = document.createElement("div");
    el.className = `av-card tag-${key}`;
    el.innerHTML = `
      <h3>${key.replace(/([A-Z])/g, " $1").trim()}</h3>
      <div class="av-source">sourced from: ${av.regulus.map(r => Category[r]).join(", ")}</div>
      <ul class="av-values">
        ${av.values.map(v => `<li>${v}</li>`).join("")}
      </ul>
    `;
    avWrap.appendChild(el);
  });
}

/* ============================================================
   RENDER: JOURNAL FORM SELECTS
   ============================================================ */

function populateSelects() {
  const needSelect = document.getElementById("field-need");
  needSelect.innerHTML = '<option value="">— none —</option>' +
    Object.entries(NeedDictionary).map(([key, n]) =>
      `<option value="${key}">${NeedName[n.id]}</option>`).join("");

  const domainSelect = document.getElementById("field-domain");
  domainSelect.innerHTML = '<option value="">— none —</option>' +
    Domain.map((d, i) => `<option value="${d}">House ${i + 1} — ${d}</option>`).join("");

  const archetypeSelect = document.getElementById("field-archetype");
  archetypeSelect.innerHTML = '<option value="">— none —</option>' +
    Object.entries(ArchetypeDictionary).map(([key, a]) =>
      `<option value="${key}">${ArchetypeName[a.id]} (${a.triad})</option>`).join("");

  const tagWrap = document.getElementById("antivalue-tags");
  tagWrap.innerHTML = Object.keys(AntiValue).map(key => `
    <label class="tag-check tag-${key}">
      <input type="checkbox" name="antivalue" value="${key}">
      <span>${key.replace(/([A-Z])/g, " $1").trim()}</span>
    </label>
  `).join("");
}

/* ============================================================
   FORM / ENTRY LOGIC
   ============================================================ */

function readForm() {
  const checked = Array.from(document.querySelectorAll('input[name="antivalue"]:checked')).map(i => i.value);
  return {
    id: editingId || (Date.now().toString(36) + Math.random().toString(36).slice(2, 6)),
    date: document.getElementById("field-date").value,
    title: document.getElementById("field-title").value.trim(),
    body: document.getElementById("field-body").value.trim(),
    need: document.getElementById("field-need").value,
    domain: document.getElementById("field-domain").value,
    archetype: document.getElementById("field-archetype").value,
    antivalues: checked,
    status: document.getElementById("field-status").value,
    updatedAt: new Date().toISOString()
  };
}

function fillForm(entry) {
  document.getElementById("field-date").value = entry.date;
  document.getElementById("field-title").value = entry.title;
  document.getElementById("field-body").value = entry.body;
  document.getElementById("field-need").value = entry.need || "";
  document.getElementById("field-domain").value = entry.domain || "";
  document.getElementById("field-archetype").value = entry.archetype || "";
  document.getElementById("field-status").value = entry.status || "incomplete";
  document.querySelectorAll('input[name="antivalue"]').forEach(cb => {
    cb.checked = (entry.antivalues || []).includes(cb.value);
  });
  editingId = entry.id;
  document.getElementById("form-mode").textContent = "CONTINUING ENTRY — " + entry.date;
  document.getElementById("submit-label").textContent = "Update Entry";
}

function resetForm() {
  document.getElementById("journal-form").reset();
  document.getElementById("field-date").value = todayISO();
  editingId = null;
  document.getElementById("form-mode").textContent = "NEW ENTRY";
  document.getElementById("submit-label").textContent = "Save Entry";
  saveDraft(null);
}

function todayISO() {
  return new Date().toISOString().slice(0, 10);
}

function upsertEntry(entry) {
  const idx = entries.findIndex(e => e.id === entry.id);
  if (idx >= 0) entries[idx] = entry;
  else entries.unshift(entry);
  saveEntries(entries);
}

function deleteEntry(id) {
  entries = entries.filter(e => e.id !== id);
  saveEntries(entries);
  renderEntries();
}

/* ============================================================
   RENDER: ENTRY LOG
   ============================================================ */

function renderEntries() {
  const list = document.getElementById("entry-list");
  const incompleteWrap = document.getElementById("incomplete-list");
  list.innerHTML = "";
  incompleteWrap.innerHTML = "";

  const sorted = [...entries].sort((a, b) => (a.date < b.date ? 1 : -1));
  const incomplete = sorted.filter(e => e.status === "incomplete");
  const complete = sorted.filter(e => e.status !== "incomplete");

  document.getElementById("incomplete-count").textContent = incomplete.length;

  if (incomplete.length === 0) {
    incompleteWrap.innerHTML = '<p class="muted">No open threads. Every entry accounted for.</p>';
  }

  incomplete.forEach(e => incompleteWrap.appendChild(renderEntryCard(e, true)));
  complete.forEach(e => list.appendChild(renderEntryCard(e, false)));

  if (complete.length === 0) {
    list.innerHTML = '<p class="muted">No archived entries yet.</p>';
  }
}

function renderEntryCard(entry, isIncomplete) {
  const el = document.createElement("div");
  el.className = "entry-card" + (isIncomplete ? " entry-open" : "");
  const needLabel = entry.need ? NeedName[NeedDictionary[entry.need].id] : null;
  const archLabel = entry.archetype ? ArchetypeName[ArchetypeDictionary[entry.archetype].id] : null;

  el.innerHTML = `
    <div class="entry-head">
      <span class="entry-date">${entry.date}</span>
      <span class="entry-status status-${entry.status}">${entry.status}</span>
    </div>
    <h4 class="entry-title">${entry.title || "(untitled)"}</h4>
    <p class="entry-body">${escapeHtml(entry.body).slice(0, 220)}${entry.body.length > 220 ? "…" : ""}</p>
    <div class="entry-meta">
      ${entry.domain ? `<span class="meta-chip">House: ${entry.domain}</span>` : ""}
      ${needLabel ? `<span class="meta-chip">Need: ${needLabel}</span>` : ""}
      ${archLabel ? `<span class="meta-chip">Pattern: ${archLabel}</span>` : ""}
      ${(entry.antivalues || []).map(av => `<span class="meta-chip tag-${av}">${av}</span>`).join("")}
    </div>
    <div class="entry-actions">
      ${isIncomplete ? `<button class="btn-continue" data-id="${entry.id}">Continue</button>` : `<button class="btn-reopen" data-id="${entry.id}">Reopen</button>`}
      <button class="btn-delete" data-id="${entry.id}">Delete</button>
    </div>
  `;
  return el;
}

function escapeHtml(str) {
  const div = document.createElement("div");
  div.textContent = str;
  return div.innerHTML;
}

/* ============================================================
   INIT
   ============================================================ */

function init() {
  renderBoundaryExplainer();
  populateSelects();

  document.getElementById("field-date").value = todayISO();

  const draft = loadDraft();
  if (draft) {
    fillForm(draft);
  }

  document.getElementById("journal-form").addEventListener("submit", e => {
    e.preventDefault();
    const entry = readForm();
    if (!entry.title && !entry.body) return;
    upsertEntry(entry);
    resetForm();
    renderEntries();
  });

  document.getElementById("journal-form").addEventListener("input", () => {
    saveDraft(readForm());
  });

  document.getElementById("btn-clear-form").addEventListener("click", () => resetForm());

  document.getElementById("entry-list").addEventListener("click", handleEntryClick);
  document.getElementById("incomplete-list").addEventListener("click", handleEntryClick);

  renderEntries();
  updateDriftMeter();
}

function handleEntryClick(e) {
  const id = e.target.dataset.id;
  if (!id) return;
  if (e.target.classList.contains("btn-continue") || e.target.classList.contains("btn-reopen")) {
    const entry = entries.find(en => en.id === id);
    if (entry) {
      fillForm(entry);
      document.getElementById("journal-panel").scrollIntoView({ behavior: "smooth" });
    }
  } else if (e.target.classList.contains("btn-delete")) {
    if (confirm("Delete this entry permanently?")) deleteEntry(id);
    updateDriftMeter();
  }
}

/* Drift meter: proportion of recent entries carrying an AntiValue flag,
   a rough signal of unconscious drift toward the boundary. */
function updateDriftMeter() {
  const recent = [...entries].sort((a, b) => (a.date < b.date ? 1 : -1)).slice(0, 14);
  const flagged = recent.filter(e => (e.antivalues || []).length > 0).length;
  const pct = recent.length ? Math.round((flagged / recent.length) * 100) : 0;
  const bar = document.getElementById("drift-bar");
  const label = document.getElementById("drift-label");
  bar.style.width = pct + "%";
  label.textContent = `${pct}% of last ${recent.length || 0} entries flagged a boundary`;
  bar.classList.toggle("drift-high", pct >= 40);
}

document.addEventListener("DOMContentLoaded", init);
