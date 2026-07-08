import http from "http";
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";

//=============
// server.js Versi Dok 5 (Xano dilepas, pakai data.json lokal)
//=============

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const PORT = 3000;
const ROOT = __dirname;
const DATA_FILE = path.join(ROOT, "data.json");

const MIME = {
  ".html": "text/html",
  ".css": "text/css",
  ".js": "text/javascript",
  ".json": "application/json",
  ".png": "image/png",
  ".jpg": "image/jpeg",
  ".jpeg": "image/jpeg",
  ".gif": "image/gif",
  ".svg": "image/svg+xml",
  ".mp4": "video/mp4",
  ".m4a": "audio/mp4",
  ".mp4a": "audio/mp4",
  ".webm": "video/webm",
  ".mp3": "audio/mpeg",
  ".pdf": "application/pdf",
  ".txt": "text/plain",
  ".md": "text/markdown",
};

// =========================
// 🔧 HELPER: baca body request (untuk PATCH/POST)
// =========================
function readBody(req) {
  return new Promise((resolve, reject) => {
    let body = "";
    req.on("data", (chunk) => (body += chunk));
    req.on("end", () => resolve(body));
    req.on("error", reject);
  });
}

function readData() {
  if (!fs.existsSync(DATA_FILE)) return [];
  const raw = fs.readFileSync(DATA_FILE, "utf-8");
  return raw.trim() ? JSON.parse(raw) : [];
}

function writeData(data) {
  fs.writeFileSync(DATA_FILE, JSON.stringify(data, null, 2), "utf-8");
}

function sendJSON(res, statusCode, payload) {
  const body = JSON.stringify(payload);
  res.writeHead(statusCode, {
    "Content-Type": "application/json",
    "Content-Length": Buffer.byteLength(body),
    "Access-Control-Allow-Origin": "*",
    "Access-Control-Allow-Methods": "GET, PATCH, POST, OPTIONS",
    "Access-Control-Allow-Headers": "Content-Type",
  });
  res.end(body);
}

// =========================
// 🗂️ API LOKAL (pengganti Xano) — /api/formalhault/:id
// =========================
async function handleApi(req, res, reqPath) {
  const match = reqPath.match(/^\/api\/formalhault(?:\/(\d+))?$/);
  if (!match) return false;

  const id = match[1] ? parseInt(match[1], 10) : null;

  if (req.method === "OPTIONS") {
    sendJSON(res, 204, {});
    return true;
  }

  const data = readData();

  if (req.method === "GET") {
    if (id === null) {
      sendJSON(res, 200, data);
    } else {
      const record = data.find((r) => r.id === id);
      if (!record) return sendJSON(res, 404, { error: "Record not found" }), true;
      sendJSON(res, 200, record);
    }
    return true;
  }

  if (req.method === "PATCH") {
    if (id === null) {
      sendJSON(res, 400, { error: "ID diperlukan untuk update" });
      return true;
    }
    const idx = data.findIndex((r) => r.id === id);
    if (idx === -1) {
      sendJSON(res, 404, { error: "Record not found" });
      return true;
    }

    let payload = {};
    try {
      const raw = await readBody(req);
      payload = raw ? JSON.parse(raw) : {};
    } catch (e) {
      sendJSON(res, 400, { error: "Body JSON tidak valid" });
      return true;
    }

    data[idx] = { ...data[idx], ...payload };
    writeData(data);
    sendJSON(res, 200, data[idx]);
    return true;
  }

  if (req.method === "POST") {
    let payload = {};
    try {
      const raw = await readBody(req);
      payload = raw ? JSON.parse(raw) : {};
    } catch (e) {
      sendJSON(res, 400, { error: "Body JSON tidak valid" });
      return true;
    }
    const newId = data.length ? Math.max(...data.map((r) => r.id)) + 1 : 1;
    const newRecord = {
      id: newId,
      created_at: Date.now(),
      Nama: payload.Nama || "",
      Saldo: payload.Saldo || 0,
      RegNo: payload.RegNo ?? newId,
    };
    data.push(newRecord);
    writeData(data);
    sendJSON(res, 201, newRecord);
    return true;
  }

  sendJSON(res, 405, { error: "Method tidak didukung" });
  return true;
}

http
  .createServer(async (req, res) => {
    let reqPath = decodeURIComponent(req.url.split("?")[0]);

    // ✅ Cek dulu apakah ini request ke API lokal (pengganti Xano)
    const handled = await handleApi(req, res, reqPath);
    if (handled) return;

    if (reqPath === "/") reqPath = "/index.html";

    const filePath = path.join(ROOT, reqPath);

    // ❌ kalau file tidak ada
    if (!fs.existsSync(filePath) || fs.statSync(filePath).isDirectory()) {
      res.writeHead(404);
      return res.end("404 Not Found");
    }

    const ext = path.extname(filePath).toLowerCase();
    const mime = MIME[ext] || "application/octet-stream";

    const stat = fs.statSync(filePath);
    const fileSize = stat.size;

    const range = req.headers.range;

    // =========================
    // 🎧 AUDIO & VIDEO (FIX SKIP)
    // =========================
    if (mime.startsWith("audio/") || mime.startsWith("video/")) {
      if (range) {
        const parts = range.replace(/bytes=/, "").split("-");
        const start = parseInt(parts[0], 10);
        const end = parts[1] ? parseInt(parts[1], 10) : fileSize - 1;

        const chunkSize = end - start + 1;

        res.writeHead(206, {
          "Content-Range": `bytes ${start}-${end}/${fileSize}`,
          "Accept-Ranges": "bytes",
          "Content-Length": chunkSize,
          "Content-Type": mime,
        });

        fs.createReadStream(filePath, { start, end }).pipe(res);
      } else {
        res.writeHead(200, {
          "Content-Length": fileSize,
          "Content-Type": mime,
          "Accept-Ranges": "bytes",
        });

        fs.createReadStream(filePath).pipe(res);
      }

      return;
    }

    // =========================
    // 📄 PDF (SCROLL FIX)
    // =========================
    if (mime === "application/pdf") {
      if (range) {
        const parts = range.replace(/bytes=/, "").split("-");
        const start = parseInt(parts[0], 10);
        const end = parts[1] ? parseInt(parts[1], 10) : fileSize - 1;

        const chunkSize = end - start + 1;

        res.writeHead(206, {
          "Content-Range": `bytes ${start}-${end}/${fileSize}`,
          "Accept-Ranges": "bytes",
          "Content-Length": chunkSize,
          "Content-Type": mime,
        });

        fs.createReadStream(filePath, { start, end }).pipe(res);
      } else {
        res.writeHead(200, {
          "Content-Length": fileSize,
          "Content-Type": mime,
          "Accept-Ranges": "bytes",
          "Cache-Control": "public, max-age=86400",
        });

        fs.createReadStream(filePath).pipe(res);
      }

      return;
    }

    // =========================
    // 📦 FILE LAIN (NORMAL)
    // =========================
    res.writeHead(200, {
      "Content-Length": fileSize,
      "Content-Type": mime,
    });

    fs.createReadStream(filePath).pipe(res);
  })
  .listen(PORT, "0.0.0.0", () => {
    console.log(`Server running at http://localhost:${PORT}`);
  });
