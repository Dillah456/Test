// ─────────────────────────────────────────────────────────────────────────────
//  main.js — logika frontend untuk Inventaris (AJAX ke /api/dido, native Node)
//  dido.php sudah tidak dipakai lagi — semua CRUD ditangani server.js.
// ─────────────────────────────────────────────────────────────────────────────

const API = '/api/dido';

const viewEl  = document.getElementById('view');
const toastEl = document.getElementById('toast-area');

let currentQuery = '';

// ── Utils ─────────────────────────────────────────────────────────────────────
function h(str) {
  return String(str ?? '').replace(/[&<>"']/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  }[c]));
}

function rp(n) {
  const num = Number(n) || 0;
  return 'Rp&nbsp;' + num.toLocaleString('id-ID', { maximumFractionDigits: 0 });
}

function truncate(str, len) {
  str = str ?? '';
  return str.length > len ? str.slice(0, len) + '…' : str;
}

function showToast(message, type = 'success') {
  toastEl.innerHTML = `
    <div class="toast ${type}">
      <span>${type === 'success' ? '✓' : '✕'}</span>
      <span>${message}</span>
    </div>`;
  clearTimeout(showToast._t);
  showToast._t = setTimeout(() => { toastEl.innerHTML = ''; }, 4000);
}

// ── API layer (REST) ──────────────────────────────────────────────────────────
async function apiList(q = '') {
  const url = q ? `${API}?q=${encodeURIComponent(q)}` : API;
  const res = await fetch(url);
  return res.json(); // { success, items, stats, q }
}

async function apiGet(id) {
  const res = await fetch(`${API}/${id}`);
  return res.json(); // { success, item }
}

async function apiAdd(payload) {
  const res = await fetch(API, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  return res.json(); // { success, message, item, stats }
}

async function apiEdit(id, payload) {
  const res = await fetch(`${API}/${id}`, {
    method: 'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  return res.json(); // { success, message, item, stats }
}

async function apiDelete(id) {
  const res = await fetch(`${API}/${id}`, { method: 'DELETE' });
  return res.json(); // { success, message, stats }
}

// ── List view ─────────────────────────────────────────────────────────────────
async function loadList(q = '') {
  currentQuery = q;
  viewEl.innerHTML = `<div class="loading">Memuat data…</div>`;

  const res = await apiList(q);
  if (!res.success) {
    viewEl.innerHTML = `<div class="loading">Gagal memuat data.</div>`;
    return;
  }
  renderList(res.items, res.stats, q);
}

function renderList(rows, stats, q) {
  const rowsHtml = rows.length
    ? rows.map((r) => `
        <tr>
          <td><span class="idc">#${r.Id}</span></td>
          <td><strong>${h(r.Item_Name)}</strong></td>
          <td>
            ${r.Jenis && r.Jenis !== '-'
              ? `<span class="badge bjenis">${h(r.Jenis)}</span>`
              : `<span style="color:var(--muted)">—</span>`}
          </td>
          <td>
            <span class="badge ${Number(r.Quantity) <= 5 ? 'bqlow' : 'bqok'}">
              ${Number(r.Quantity).toLocaleString('id-ID')}
            </span>
          </td>
          <td class="price">${rp(r.HPP)}</td>
          <td class="tdket">${h(truncate(r.Keterangan, 45))}</td>
          <td>
            <div class="acts">
              <button class="btn btn-blue btn-sm" data-edit="${r.Id}">✎ Edit</button>
              <button class="btn btn-danger btn-sm" data-del="${r.Id}" data-name="${h(r.Item_Name)}">✕</button>
            </div>
          </td>
        </tr>`).join('')
    : `<tr><td colspan="7">
        <div class="empty">
          ${q
            ? `Tidak ada hasil untuk "<strong>${h(q)}</strong>"`
            : `Belum ada data.<br>Klik <strong>+ Tambah Item</strong> untuk mulai.`}
        </div>
      </td></tr>`;

  viewEl.innerHTML = `
    <div class="stats">
      <div class="sc c1"><div class="sl">Total Item</div><div class="sv">${stats.totalItems}</div></div>
      <div class="sc c2"><div class="sl">Total Qty</div><div class="sv">${stats.totalQty.toLocaleString('id-ID')}</div></div>
      <div class="sc c3"><div class="sl">Kategori</div><div class="sv">${stats.totalJenis}</div></div>
      <div class="sc c4"><div class="sl">Total HPP</div><div class="sv">${rp(stats.totalHPP)}</div></div>
    </div>

    <div class="toolbar">
      <div class="toolbar-title">Daftar Item</div>
      <div class="sbox">
        <span>⌕</span>
        <input type="text" id="search-input" placeholder="Cari nama, jenis..." value="${h(q)}">
      </div>
      <button class="btn" id="btn-search">Cari</button>
      ${q ? `<button class="btn" id="btn-reset">✕ Reset</button>` : ''}
      <button class="btn btn-primary" id="btn-add">+ Tambah Item</button>
    </div>

    <div class="twrap">
      <div class="tscroll">
        <table>
          <thead>
            <tr>
              <th>#</th><th>Nama Item</th><th>Jenis</th><th>Qty</th>
              <th>HPP</th><th>Keterangan</th><th>Aksi</th>
            </tr>
          </thead>
          <tbody>${rowsHtml}</tbody>
        </table>
      </div>
      <div class="tfoot">
        <span>${rows.length} record${q ? `&nbsp;·&nbsp;filter: "${h(q)}"` : ''}</span>
        <span>Qty ≤ 5 → <span style="color:#fca5a5">stok rendah</span></span>
      </div>
    </div>

    <p class="hint">💾 Data: <code>data/session-market.json</code> · mode AJAX</p>
  `;

  // ── Event bindings (list view) ──
  document.getElementById('btn-add').addEventListener('click', () => showForm('add'));
  document.getElementById('btn-search').addEventListener('click', () => {
    loadList(document.getElementById('search-input').value.trim());
  });
  document.getElementById('search-input').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') loadList(e.target.value.trim());
  });
  const resetBtn = document.getElementById('btn-reset');
  if (resetBtn) resetBtn.addEventListener('click', () => loadList(''));

  viewEl.querySelectorAll('[data-edit]').forEach((btn) => {
    btn.addEventListener('click', () => showForm('edit', btn.dataset.edit));
  });
  viewEl.querySelectorAll('[data-del]').forEach((btn) => {
    btn.addEventListener('click', () => deleteItem(btn.dataset.del, btn.dataset.name));
  });
}

// ── Form view (add / edit) ──────────────────────────────────────────────────
async function showForm(mode, id = null) {
  let item = { Item_Name: '', Jenis: '', Quantity: 0, HPP: 0, Keterangan: '' };

  if (mode === 'edit') {
    viewEl.innerHTML = `<div class="loading">Memuat item…</div>`;
    const res = await apiGet(id);
    if (!res.success) {
      showToast(res.message || 'Item tidak ditemukan.', 'error');
      return loadList(currentQuery);
    }
    item = res.item;
  }

  viewEl.innerHTML = `
    <div style="margin-bottom:16px">
      <button class="btn" id="btn-back">← Kembali</button>
    </div>

    <div class="fc">
      <h2>
        ${mode === 'add' ? '+ Tambah' : '✎ Edit'} <b>Item Inventaris</b>
        ${mode === 'edit' ? `<span style="font-size:11px;color:var(--muted);font-weight:400">#${item.Id}</span>` : ''}
      </h2>

      <form id="item-form">
        <div class="fg">
          <div class="field ff">
            <label>Nama Item *</label>
            <input type="text" name="Item_Name" required maxlength="100"
                   value="${h(item.Item_Name)}" placeholder="Contoh: Baut M8 × 30mm">
          </div>

          <div class="field">
            <label>Jenis / Kategori</label>
            <input type="text" name="Jenis" maxlength="50"
                   value="${h(item.Jenis)}" placeholder="Mekanik, Elektrik, ...">
          </div>

          <div class="field">
            <label>Quantity</label>
            <input type="number" name="Quantity" min="0" value="${Number(item.Quantity) || 0}">
          </div>

          <div class="field ff">
            <label>HPP — Harga Pokok (Rp)</label>
            <input type="number" name="HPP" min="0" step="1" value="${Number(item.HPP) || 0}" placeholder="0">
          </div>

          <div class="field ff">
            <label>Keterangan</label>
            <textarea name="Keterangan" placeholder="Catatan tambahan tentang item ini...">${h(item.Keterangan)}</textarea>
          </div>
        </div>

        <div class="fa">
          <button type="submit" class="btn btn-primary">
            ${mode === 'add' ? '+ Simpan Item' : '✓ Update Item'}
          </button>
          <button type="button" class="btn" id="btn-cancel">Batal</button>
        </div>
      </form>
    </div>
  `;

  document.getElementById('btn-back').addEventListener('click', () => loadList(currentQuery));
  document.getElementById('btn-cancel').addEventListener('click', () => loadList(currentQuery));

  document.getElementById('item-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const payload = Object.fromEntries(fd.entries());

    const res = mode === 'edit' ? await apiEdit(item.Id, payload) : await apiAdd(payload);
    await loadList(mode === 'edit' ? currentQuery : '');
    showToast(res.message, res.success ? 'success' : 'error');
  });
}

// ── Delete ────────────────────────────────────────────────────────────────────
async function deleteItem(id, name) {
  if (!confirm(`Hapus item "${name}"?`)) return;
  const res = await apiDelete(id);
  showToast(res.message, res.success ? 'success' : 'error');
  loadList(currentQuery);
}

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => loadList(''));
