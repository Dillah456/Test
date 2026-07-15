<?php
// ─── Backend API ───────────────────────────────────────────────────────────────
$JSON_FILE = __DIR__ . '/Link.json';

function readJson($path) {
    if (!file_exists($path)) return [];
    $raw = file_get_contents($path);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function writeJson($path, $data) {
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['api'])) {
    header('Content-Type: application/json');
    $action = $_GET['api'];
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];

    switch ($action) {

        // Load: return current Link.json contents
        case 'load':
            echo json_encode(['ok' => true, 'data' => readJson($JSON_FILE)]);
            exit;

        // Save: append new links to Link.json, never overwrite existing entries
        case 'save':
            $newLinks = $body['links'] ?? [];
            $existing = readJson($JSON_FILE);
            $byLink   = [];
            foreach ($existing as $e) {
                if (!empty($e['Link'])) $byLink[$e['Link']] = true;
            }
            $nextId = $existing ? (max(array_column($existing, 'Id') ?: [0]) + 1) : 1;
            $added  = 0;
            foreach ($newLinks as $url) {
                if (!isset($byLink[$url])) {
                    $existing[] = ['Id' => $nextId++, 'Link' => $url];
                    $byLink[$url] = true;
                    $added++;
                }
            }
            $ok = writeJson($JSON_FILE, array_values($existing));
            echo json_encode(['ok' => $ok !== false, 'total' => count($existing), 'added' => $added]);
            exit;

        // Delete: remove entries whose Link matches the posted urls
        case 'delete':
            $toRemove = array_flip($body['links'] ?? []);
            $existing = readJson($JSON_FILE);
            $filtered = array_values(array_filter($existing, fn($e) => !isset($toRemove[$e['Link'] ?? ''])));
            $removed  = count($existing) - count($filtered);
            $ok = writeJson($JSON_FILE, $filtered);
            echo json_encode(['ok' => $ok !== false, 'removed' => $removed]);
            exit;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Unknown action']);
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pixiv Bookmark Extractor</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap');

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg:        #0d0d0f;
    --surface:   #16161a;
    --surface2:  #1e1e24;
    --surface3:  #252530;
    --border:    rgba(255,255,255,0.08);
    --border2:   rgba(255,255,255,0.14);
    --text:      #e8e8ee;
    --muted:     #888896;
    --accent:    #7b6ef6;
    --accent2:   #a89dff;
    --accent-bg: rgba(123,110,246,0.12);
    --accent-bd: rgba(123,110,246,0.35);
    --danger:    #ef4c60;
    --danger-bg: rgba(239,76,96,0.1);
    --danger-bd: rgba(239,76,96,0.35);
    --success:   #4caf7d;
    --success-bg:rgba(76,175,125,0.1);
    --mono:      'JetBrains Mono', monospace;
    --sans:      'Inter', sans-serif;
    --r-sm: 6px;
    --r-md: 10px;
    --r-lg: 14px;
  }

  body { font-family: var(--sans); background: var(--bg); color: var(--text); min-height: 100vh; font-size: 14px; line-height: 1.6; }

  .shell { max-width: 1100px; margin: 0 auto; padding: 2.5rem 1.5rem 4rem; }

  header { display: flex; align-items: center; gap: 14px; margin-bottom: 2.5rem; }
  .logo-mark {
    width: 40px; height: 40px;
    background: var(--accent-bg);
    border: 1px solid var(--accent-bd);
    border-radius: var(--r-md);
    display: flex; align-items: center; justify-content: center;
  }
  .logo-mark svg { width: 20px; height: 20px; color: var(--accent2); }
  header h1 { font-size: 20px; font-weight: 600; letter-spacing: -0.3px; }
  header p  { font-size: 13px; color: var(--muted); margin-top: 1px; }

  .dropzone {
    border: 1.5px dashed var(--border2);
    border-radius: var(--r-lg);
    padding: 3rem 2rem;
    text-align: center;
    color: var(--muted);
    cursor: pointer;
    transition: border-color .2s, background .2s;
    margin-bottom: 1.5rem;
  }
  .dropzone:hover, .dropzone.over { border-color: var(--accent); background: var(--accent-bg); color: var(--accent2); }
  .dropzone svg { width: 32px; height: 32px; margin-bottom: .75rem; display: block; margin-inline: auto; }
  .dropzone span { display: block; font-size: 14px; margin-bottom: 4px; }
  .dropzone small { font-size: 12px; }

  .file-row { display: flex; align-items: center; gap: 10px; margin-bottom: 2rem; }
  .file-label {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px;
    border: 1px solid var(--border2);
    border-radius: var(--r-sm);
    cursor: pointer; font-size: 13px; color: var(--muted);
    transition: border-color .15s, color .15s;
  }
  .file-label:hover { border-color: var(--accent); color: var(--accent2); }
  #fileInput { display: none; }
  #fileName  { font-size: 13px; color: var(--muted); }

  .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; margin-bottom: 1.5rem; }
  .stat { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-md); padding: .875rem 1.125rem; }
  .stat-label { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
  .stat-val   { font-size: 26px; font-weight: 600; letter-spacing: -1px; }
  .stat-val.accent { color: var(--accent2); }

  .toolbar { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 1.25rem; }
  .btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 13px;
    border-radius: var(--r-sm);
    border: 1px solid var(--border2);
    background: var(--surface);
    color: var(--text);
    font: 13px/1 var(--sans);
    cursor: pointer;
    transition: background .14s, border-color .14s, color .14s;
    white-space: nowrap;
  }
  .btn:hover  { background: var(--surface3); }
  .btn:active { transform: scale(0.98); }
  .btn svg    { width: 15px; height: 15px; flex-shrink: 0; }
  .btn-accent { background: var(--accent-bg); border-color: var(--accent-bd); color: var(--accent2); }
  .btn-accent:hover { background: rgba(123,110,246,0.2); }
  .btn-success { background: var(--success-bg); border-color: rgba(76,175,125,.4); color: var(--success); }
  .btn-success:hover { background: rgba(76,175,125,0.2); }
  .btn-danger { background: var(--danger-bg); border-color: var(--danger-bd); color: var(--danger); }
  .btn-danger:hover { background: rgba(239,76,96,0.2); }
  .btn:disabled { opacity: .45; cursor: not-allowed; pointer-events: none; }

  .sep { width: 1px; background: var(--border); align-self: stretch; margin: 0 2px; }

  .table-wrap { border: 1px solid var(--border); border-radius: var(--r-lg); overflow: hidden; overflow-x: auto; }
  table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 600px; }
  thead th {
    background: var(--surface); padding: 10px 12px;
    text-align: left; font-size: 11px; font-weight: 500;
    color: var(--muted); text-transform: uppercase; letter-spacing: .5px;
    border-bottom: 1px solid var(--border); white-space: nowrap;
  }
  th:nth-child(1) { width: 44px; }
  th:nth-child(2) { width: 48px; }
  th:nth-child(3) { min-width: 340px; }
  th:nth-child(4) { width: 130px; }
  th:nth-child(5) { width: 160px; }

  tbody tr { border-bottom: 1px solid var(--border); transition: background .12s; }
  tbody tr:last-child { border-bottom: none; }
  tbody tr:hover { background: var(--surface2); }
  tbody tr.in-json { background: rgba(76,175,125,0.05); }
  tbody tr.in-json td:first-child::after { content: '✓'; color: var(--success); font-size: 11px; margin-left: 4px; }

  td { padding: 10px 12px; vertical-align: middle; }
  .idx { color: var(--muted); font-family: var(--mono); font-size: 12px; }
  .link-cell { font-family: var(--mono); font-size: 12px; color: var(--accent2); max-width: 340px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .link-cell a { color: inherit; text-decoration: none; }
  .link-cell a:hover { text-decoration: underline; }
  .id-badge { display: inline-block; padding: 3px 9px; background: rgba(123,110,246,0.1); border: 1px solid rgba(123,110,246,0.25); border-radius: 99px; font-family: var(--mono); font-size: 11px; color: var(--accent2); }
  .row-actions { display: flex; gap: 6px; }
  .empty-row td { padding: 4rem; text-align: center; color: var(--muted); font-size: 14px; }
  .empty-row svg { width: 36px; height: 36px; margin: 0 auto 1rem; display: block; opacity: .35; }

  input[type=checkbox] { width: 15px; height: 15px; accent-color: var(--accent); cursor: pointer; }

  /* Modal */
  .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.65); z-index: 100; align-items: center; justify-content: center; padding: 1rem; }
  .modal-overlay.open { display: flex; }
  .modal { background: var(--surface); border: 1px solid var(--border2); border-radius: var(--r-lg); padding: 1.75rem; width: 460px; max-width: 100%; animation: modal-in .18s ease; }
  @keyframes modal-in { from { opacity: 0; transform: translateY(10px) scale(.98); } to { opacity: 1; transform: none; } }
  .modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
  .modal-title  { font-size: 16px; font-weight: 600; }
  .modal-close  { background: none; border: none; cursor: pointer; color: var(--muted); padding: 4px; border-radius: var(--r-sm); transition: color .12s; }
  .modal-close:hover { color: var(--text); }
  .modal-close svg { width: 18px; height: 18px; display: block; }
  .modal-info { background: var(--accent-bg); border: 1px solid var(--accent-bd); border-radius: var(--r-sm); padding: .75rem 1rem; font-size: 13px; color: var(--accent2); margin-bottom: 1.25rem; }
  .field { margin-bottom: 1rem; }
  .field label { display: block; font-size: 12px; color: var(--muted); margin-bottom: 6px; font-weight: 500; }
  .field input, .field select { width: 100%; padding: 9px 12px; background: var(--surface2); border: 1px solid var(--border2); border-radius: var(--r-sm); color: var(--text); font: 13px var(--sans); outline: none; transition: border-color .15s; }
  .field input:focus, .field select:focus { border-color: var(--accent); }
  .field select option { background: var(--surface2); }
  .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
  .modal-footer { display: flex; justify-content: flex-end; gap: 8px; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border); }

  /* Confirm */
  .confirm-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.7); z-index: 200; align-items: center; justify-content: center; padding: 1rem; }
  .confirm-overlay.open { display: flex; }
  .confirm { background: var(--surface); border: 1px solid var(--border2); border-radius: var(--r-lg); padding: 1.75rem; width: 380px; max-width: 100%; animation: modal-in .18s ease; }
  .confirm-icon { width: 44px; height: 44px; background: var(--danger-bg); border: 1px solid var(--danger-bd); border-radius: var(--r-md); display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; }
  .confirm-icon svg { width: 22px; height: 22px; color: var(--danger); }
  .confirm h3 { font-size: 16px; font-weight: 600; margin-bottom: .5rem; }
  .confirm p  { font-size: 13px; color: var(--muted); line-height: 1.7; }
  .confirm-footer { display: flex; justify-content: flex-end; gap: 8px; margin-top: 1.5rem; }

  /* Toast */
  .toast-wrap { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 300; display: flex; flex-direction: column; gap: 8px; pointer-events: none; }
  .toast { background: var(--surface2); border: 1px solid var(--border2); border-radius: var(--r-md); padding: .75rem 1.125rem; font-size: 13px; display: flex; align-items: center; gap: 8px; min-width: 240px; max-width: 320px; animation: toast-in .2s ease; pointer-events: auto; }
  .toast.success { border-color: rgba(76,175,125,.4); }
  .toast.error   { border-color: var(--danger-bd); }
  .toast svg { width: 16px; height: 16px; flex-shrink: 0; }
  .toast.success svg { color: var(--success); }
  .toast.error   svg { color: var(--danger); }
  @keyframes toast-in { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }

  /* Progress */
  .progress-wrap  { margin-bottom: 1rem; display: none; }
  .progress-label { display: flex; justify-content: space-between; font-size: 12px; color: var(--muted); margin-bottom: 6px; }
  .progress-bar   { height: 4px; background: var(--surface3); border-radius: 99px; overflow: hidden; }
  .progress-fill  { height: 100%; background: var(--accent); border-radius: 99px; transition: width .25s; width: 0%; }

  /* JSON status badge */
  .json-status { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 99px; font-size: 12px; background: var(--surface2); border: 1px solid var(--border); color: var(--muted); }
  .json-status.loaded { background: var(--success-bg); border-color: rgba(76,175,125,.4); color: var(--success); }
  .json-status-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

  @media (max-width: 640px) {
    .toolbar { gap: 6px; }
    .btn span { display: none; }
    .field-row { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>
<div class="shell">

  <!-- Header -->
  <header>
    <div class="logo-mark">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
      </svg>
    </div>
    <div>
      <h1>Pixiv Bookmark Extractor</h1>
      <p>Extract, manage and submit artwork links from exported bookmark files</p>
    </div>
  </header>

  <!-- Drop zone -->
  <div class="dropzone" id="dropzone">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
      <polyline points="17 8 12 3 7 8"/>
      <line x1="12" y1="3" x2="12" y2="15"/>
    </svg>
    <span>Drop your bookmark HTML here</span>
    <small>or click the button below to browse, then press Extract Links</small>
  </div>

  <div class="file-row">
    <label class="file-label" for="fileInput">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
      </svg>
      Choose HTML file
    </label>
    <input type="file" id="fileInput" accept=".html">
    <span id="fileName">No file selected</span>
    <button class="btn btn-accent" id="extractBtn" onclick="runExtract()" style="display:none">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      Extract Links
    </button>
  </div>

  <!-- Stats -->
  <div class="stats">
    <div class="stat">
      <div class="stat-label">Total links</div>
      <div class="stat-val" id="totalCount">0</div>
    </div>
    <div class="stat">
      <div class="stat-label">Selected</div>
      <div class="stat-val accent" id="selCount">0</div>
    </div>
    <div class="stat">
      <div class="stat-label">In JSON</div>
      <div class="stat-val" id="jsonCount">0</div>
    </div>
    <div class="stat">
      <div class="stat-label">Submitted</div>
      <div class="stat-val" id="submittedCount">0</div>
    </div>
  </div>

  <!-- Toolbar -->
  <div class="toolbar">
    <button class="btn" onclick="selectAll()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/><polyline points="9 12 11 14 15 10"/></svg>
      <span>Select all</span>
    </button>
    <button class="btn" onclick="selectNone()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/></svg>
      <span>Deselect all</span>
    </button>

    <div class="sep"></div>

    <button class="btn" onclick="copyLinks()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
      <span>Copy selected</span>
    </button>
    <button class="btn" onclick="downloadTxt()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      <span>Export TXT</span>
    </button>
    <button class="btn" onclick="openSelected()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
      <span>Open selected (max 20)</span>
    </button>

    <div class="sep"></div>

    <button class="btn btn-accent" onclick="openApiModal()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
      <span>Submit to API</span>
    </button>

    <div class="sep"></div>

    <!-- JSON controls -->
    <button class="btn btn-success" onclick="loadFromJson()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/><polyline points="7 9 12 4 17 9"/><line x1="12" y1="4" x2="12" y2="16"/></svg>
      <span>Load JSON</span>
    </button>
    <button class="btn btn-danger" onclick="deleteFromJson()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
      <span>Delete from JSON</span>
    </button>
    <button class="btn" onclick="saveToJson()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
      <span>Save to JSON</span>
    </button>
    <span class="json-status" id="jsonStatus">
      <span class="json-status-dot"></span>
      <span id="jsonStatusText">Link.json not loaded</span>
    </span>
  </div>

  <!-- Table -->
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th><input type="checkbox" id="masterCheck" title="Select all" onchange="toggleAll(this.checked)"></th>
          <th>#</th>
          <th>Artwork URL</th>
          <th>Artwork ID</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="tableBody">
        <tr class="empty-row">
          <td colspan="5">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
              <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
            </svg>
            Drop a Pixiv bookmark HTML file above to get started
          </td>
        </tr>
      </tbody>
    </table>
  </div>

</div><!-- /shell -->

<!-- API Modal -->
<div class="modal-overlay" id="apiModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Submit to API</span>
      <button class="modal-close" onclick="closeApiModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-info" id="apiSelInfo"></div>
    <div class="field-row">
      <div class="field"><label>District</label><input type="number" id="fDistrict" value="18"></div>
      <div class="field"><label>Name</label><input type="text" id="fName" value="NaN"></div>
    </div>
    <div class="field"><label>Sites</label><input type="text" id="fSites" placeholder="e.g. pixiv"></div>
    <div class="field">
      <label>State</label>
      <select id="fState">
        <option value="Devour">Devour</option>
        <option value="Pending">Pending</option>
        <option value="Done">Done</option>
      </select>
    </div>
    <div class="progress-wrap" id="progressWrap">
      <div class="progress-label">
        <span id="progressText">Submitting…</span>
        <span id="progressPct">0%</span>
      </div>
      <div class="progress-bar"><div class="progress-fill" id="progressFill"></div></div>
    </div>
    <div class="modal-footer">
      <button class="btn" onclick="closeApiModal()" id="cancelBtn">Cancel</button>
      <button class="btn btn-accent" onclick="submitToApi()" id="submitBtn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        Submit
      </button>
    </div>
  </div>
</div>

<!-- Confirm dialog -->
<div class="confirm-overlay" id="confirmDel">
  <div class="confirm">
    <div class="confirm-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
    </div>
    <h3>Remove submitted links?</h3>
    <p id="confirmMsg"></p>
    <div class="confirm-footer">
      <button class="btn" onclick="closeConfirm(false)">Keep them</button>
      <button class="btn btn-danger" onclick="closeConfirm(true)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
        Remove
      </button>
    </div>
  </div>
</div>

<div class="toast-wrap" id="toastWrap"></div>

<script>
  let links          = [];
  let jsonLinks      = new Set(); // links currently saved in Link.json on server
  let totalSubmitted = 0;
  let pendingDelete  = [];
  let confirmCallback = null;

  // ── API helpers ────────────────────────────────────────────────────────────
  async function api(action, body = {}) {
    const r = await fetch('?api=' + action, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    return r.json();
  }

  // ── Extract ────────────────────────────────────────────────────────────────
  let pendingHtml = null; // holds file content until user clicks Extract

  function extractPixivLinks(html) {
    const s  = new Set();
    const re = /https?:\/\/www\.pixiv\.net\/(?:en\/)?artworks\/(\d+)/g;
    let m;
    while ((m = re.exec(html)) !== null)
      s.add('https://www.pixiv.net/en/artworks/' + m[1]);
    return [...s];
  }

  function stageFile(html, name) {
    pendingHtml = html;
    document.getElementById('fileName').textContent = name;
    document.getElementById('extractBtn').style.display = '';
    toast('File ready — click Extract Links to proceed', 'success');
  }

  function runExtract() {
    if (!pendingHtml) { toast('No file loaded', 'error'); return; }
    const extracted = extractPixivLinks(pendingHtml);
    // Merge: add only links not already in the list
    let added = 0;
    const existing = new Set(links);
    for (const url of extracted) {
      if (!existing.has(url)) { links.push(url); added++; }
    }
    renderTable();
    updateCounts();
    toast(`Extracted ${extracted.length} links — ${added} new added`, 'success');
    pendingHtml = null;
    document.getElementById('extractBtn').style.display = 'none';
    document.getElementById('fileName').textContent = 'No file selected';
    document.getElementById('fileInput').value = '';
  }

  // ── Table ──────────────────────────────────────────────────────────────────
  function renderTable() {
    const tbody = document.getElementById('tableBody');
    if (!links.length) {
      tbody.innerHTML = `<tr class="empty-row"><td colspan="5">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
          <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
        </svg>
        No links extracted yet
      </td></tr>`;
      return;
    }
    tbody.innerHTML = links.map((url, i) => {
      const id   = (url.match(/artworks\/(\d+)/) || [])[1] || '—';
      const safe = url.replace(/'/g, '%27');
      const inJ  = jsonLinks.has(url) ? 'in-json' : '';
      return `<tr class="${inJ}">
        <td><input type="checkbox" class="row-check" data-i="${i}" onchange="updateCounts()"></td>
        <td class="idx">${i + 1}</td>
        <td class="link-cell" title="${url}"><a href="${url}" target="_blank" rel="noopener">${url}</a></td>
        <td><span class="id-badge">${id}</span></td>
        <td class="row-actions">
          <button class="btn" style="padding:5px 10px;font-size:12px" onclick="window.open('${safe}','_blank')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            Open
          </button>
          <button class="btn btn-danger" style="padding:5px 10px;font-size:12px" onclick="removeSingle(${i})">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
          </button>
        </td>
      </tr>`;
    }).join('');
  }

  function updateCounts() {
    const checked = getChecked();
    document.getElementById('totalCount').textContent     = links.length;
    document.getElementById('selCount').textContent       = checked.length;
    document.getElementById('jsonCount').textContent      = jsonLinks.size;
    document.getElementById('submittedCount').textContent = totalSubmitted;
    const master = document.getElementById('masterCheck');
    if (!links.length) { master.checked = false; master.indeterminate = false; return; }
    if (checked.length === 0)          { master.checked = false; master.indeterminate = false; }
    else if (checked.length === links.length) { master.checked = true;  master.indeterminate = false; }
    else                               { master.checked = false; master.indeterminate = true;  }
  }

  function getChecked()  { return [...document.querySelectorAll('.row-check:checked')].map(c => parseInt(c.dataset.i)); }
  function selectAll()   { document.querySelectorAll('.row-check').forEach(c => c.checked = true);  updateCounts(); }
  function selectNone()  { document.querySelectorAll('.row-check').forEach(c => c.checked = false); updateCounts(); }
  function toggleAll(v)  { document.querySelectorAll('.row-check').forEach(c => c.checked = v);     updateCounts(); }

  function removeSingle(i) { links.splice(i, 1); renderTable(); updateCounts(); }

  function getTargetLinks() {
    const idxs = getChecked();
    return idxs.length ? idxs.map(i => links[i]) : [...links];
  }

  // ── Clipboard / export ─────────────────────────────────────────────────────
  function copyLinks() {
    const out = getTargetLinks().join('\n');
    navigator.clipboard.writeText(out).then(() => toast('Copied ' + getTargetLinks().length + ' links', 'success'));
  }

  function downloadTxt() {
    const out = getTargetLinks().join('\n');
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([out], { type: 'text/plain' }));
    a.download = 'pixiv_links.txt';
    a.click();
    toast('Exported TXT', 'success');
  }

  function openSelected() {
    const toOpen = getTargetLinks().slice(0, 20);
    toOpen.forEach(u => window.open(u, '_blank'));
    toast('Opened ' + toOpen.length + ' link' + (toOpen.length !== 1 ? 's' : ''), 'success');
  }

  // ── JSON functions (server-side via PHP) ───────────────────────────────────
  function setJsonStatus(loaded, count) {
    const el   = document.getElementById('jsonStatus');
    const text = document.getElementById('jsonStatusText');
    if (loaded) {
      el.className   = 'json-status loaded';
      text.textContent = `Link.json — ${count} entr${count !== 1 ? 'ies' : 'y'}`;
    } else {
      el.className   = 'json-status';
      text.textContent = 'Link.json not loaded';
    }
  }

  async function loadFromJson() {
    try {
      const res = await api('load');
      if (!res.ok) throw new Error('Server error');
      jsonLinks = new Set(res.data.map(e => e.Link).filter(Boolean));

      // Merge new links into the list
      let added = 0;
      for (const url of jsonLinks) {
        if (!links.includes(url)) { links.push(url); added++; }
      }

      renderTable();
      updateCounts();
      setJsonStatus(true, res.data.length);
      toast(`Loaded ${res.data.length} entr${res.data.length !== 1 ? 'ies' : 'y'} from Link.json — ${added} new link${added !== 1 ? 's' : ''} added`, 'success');
    } catch (e) {
      toast('Failed to load Link.json: ' + e.message, 'error');
    }
  }

  async function saveToJson() {
    const target = getTargetLinks();
    if (!target.length) { toast('No links to save', 'error'); return; }
    // Only send links not already in JSON
    const toAdd = target.filter(u => !jsonLinks.has(u));
    if (!toAdd.length) { toast('All selected links are already in Link.json', 'success'); return; }
    try {
      const res = await api('save', { links: toAdd });
      if (!res.ok) throw new Error('Write failed');
      toAdd.forEach(u => jsonLinks.add(u));
      renderTable();
      updateCounts();
      setJsonStatus(true, res.total);
      toast(`Added ${res.added} new link${res.added !== 1 ? 's' : ''} — Link.json now has ${res.total} entr${res.total !== 1 ? 'ies' : 'y'}`, 'success');
    } catch (e) {
      toast('Failed to save: ' + e.message, 'error');
    }
  }

  async function deleteFromJson() {
    const target = getTargetLinks();
    if (!target.length) { toast('No links selected/loaded', 'error'); return; }
    const inJson = target.filter(u => jsonLinks.has(u));
    if (!inJson.length) { toast('None of the selected links are in Link.json', 'error'); return; }

    try {
      const res = await api('delete', { links: inJson });
      if (!res.ok) throw new Error('Write failed');
      inJson.forEach(u => jsonLinks.delete(u));
      renderTable();
      updateCounts();
      setJsonStatus(jsonLinks.size > 0, jsonLinks.size);
      toast(`Deleted ${res.removed} link${res.removed !== 1 ? 's' : ''} from Link.json`, 'success');
    } catch (e) {
      toast('Failed to delete: ' + e.message, 'error');
    }
  }

  // ── API Modal ──────────────────────────────────────────────────────────────
  function openApiModal() {
    if (!links.length) { toast('No links to submit', 'error'); return; }
    const idxs = getChecked();
    const n    = idxs.length || links.length;
    document.getElementById('apiSelInfo').textContent =
      idxs.length ? `Submitting ${n} selected link${n > 1 ? 's' : ''}` : `No selection — submitting all ${n} link${n > 1 ? 's' : ''}`;
    document.getElementById('progressWrap').style.display = 'none';
    document.getElementById('progressFill').style.width   = '0%';
    document.getElementById('apiModal').classList.add('open');
  }

  function closeApiModal() { document.getElementById('apiModal').classList.remove('open'); }

  async function submitToApi() {
    const toSubmit = getTargetLinks();
    const district = parseInt(document.getElementById('fDistrict').value) || 18;
    const name     = document.getElementById('fName').value || 'NaN';
    const sites    = document.getElementById('fSites').value || '';
    const state    = document.getElementById('fState').value;

    const btn   = document.getElementById('submitBtn');
    const cancel = document.getElementById('cancelBtn');
    const pwrap = document.getElementById('progressWrap');
    const pfill = document.getElementById('progressFill');
    const ptext = document.getElementById('progressText');
    const ppct  = document.getElementById('progressPct');

    btn.disabled = true; btn.innerHTML = 'Submitting…';
    cancel.disabled = true; pwrap.style.display = 'block';

    const API = 'https://x8ki-letl-twmt.n7.xano.io/api:X6h8irt0/hunt';
    let ok = 0, fail = 0;

    for (let i = 0; i < toSubmit.length; i++) {
      const url = toSubmit[i];
      ptext.textContent = `Submitting ${i + 1} of ${toSubmit.length}…`;
      const pct = Math.round(((i + 1) / toSubmit.length) * 100);
      ppct.textContent = pct + '%'; pfill.style.width = pct + '%';
      try {
        const r = await fetch(API, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ District: district, Name: name, Path: url, Sites: sites, State: state })
        });
        if (r.ok) ok++; else fail++;
      } catch { fail++; }
    }

    btn.disabled = false;
    btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Submit';
    cancel.disabled = false;
    closeApiModal();

    if (ok > 0) {
      totalSubmitted += ok;
      pendingDelete = toSubmit.slice(0, ok + fail);
      document.getElementById('confirmMsg').textContent =
        `${ok} link${ok > 1 ? 's were' : ' was'} submitted successfully` +
        (fail ? ` (${fail} failed)` : '') +
        `. Remove ${ok > 1 ? 'these links' : 'this link'} from your list?`;
      document.getElementById('confirmDel').classList.add('open');
      confirmCallback = (yes) => {
        if (yes) {
          links = links.filter(l => !pendingDelete.includes(l));
          renderTable(); updateCounts();
          toast(`Removed ${pendingDelete.length} submitted link${pendingDelete.length > 1 ? 's' : ''}`, 'success');
        } else {
          toast(`${ok} submitted — kept in list`, 'success');
        }
      };
    } else {
      toast('All submissions failed — check your connection', 'error');
    }
  }

  function closeConfirm(yes) {
    document.getElementById('confirmDel').classList.remove('open');
    if (confirmCallback) confirmCallback(yes);
    confirmCallback = null;
  }

  // ── Toast ──────────────────────────────────────────────────────────────────
  function toast(msg, type = 'success') {
    const wrap = document.getElementById('toastWrap');
    const el   = document.createElement('div');
    el.className = 'toast ' + type;
    const icon = type === 'success'
      ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>'
      : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
    el.innerHTML = icon + '<span>' + msg + '</span>';
    wrap.appendChild(el);
    setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .3s'; setTimeout(() => el.remove(), 300); }, 3000);
  }

  // ── File input ─────────────────────────────────────────────────────────────
  document.getElementById('fileInput').addEventListener('change', e => {
    const f = e.target.files[0]; if (!f) return;
    const r = new FileReader();
    r.onload = () => stageFile(r.result, f.name);
    r.readAsText(f);
  });

  // ── Drag & drop ────────────────────────────────────────────────────────────
  const dz = document.getElementById('dropzone');
  dz.addEventListener('dragover',  e => { e.preventDefault(); dz.classList.add('over'); });
  dz.addEventListener('dragleave', ()  => dz.classList.remove('over'));
  dz.addEventListener('drop', e => {
    e.preventDefault(); dz.classList.remove('over');
    const f = e.dataTransfer.files[0]; if (!f) return;
    const r = new FileReader();
    r.onload = () => stageFile(r.result, f.name);
    r.readAsText(f);
  });
  dz.addEventListener('click', () => document.getElementById('fileInput').click());

  document.getElementById('apiModal').addEventListener('click',  e => { if (e.target === e.currentTarget) closeApiModal(); });
  document.getElementById('confirmDel').addEventListener('click', e => { if (e.target === e.currentTarget) closeConfirm(false); });
</script>
</body>
</html>