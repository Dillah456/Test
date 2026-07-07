import http from "http";
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";

//=============
// server.js Versi Dok 4
//=============

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const PORT = 3000;
const ROOT = __dirname;

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

http.createServer((req, res) => {
  let reqPath = decodeURIComponent(req.url.split("?")[0]);

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
      // fallback (penting biar browser tahu bisa seek)
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

}).listen(PORT, "0.0.0.0", () => {
  console.log(`Server running at http://localhost:${PORT}`);
});