// node build_audio.mjs   -> audio/*.wav (cache), narration.wav, timeline.js
// Narasi memakai suara neural Microsoft Edge (online). Ganti suara: VOICE=id-ID-ArdiNeural node build_audio.mjs
import { MsEdgeTTS, OUTPUT_FORMAT } from 'msedge-tts';
import ffmpeg from '@ffmpeg-installer/ffmpeg';
import { execFileSync } from 'child_process';
import { fileURLToPath } from 'url';
import crypto from 'crypto';
import fs from 'fs';
import path from 'path';

const HERE = path.dirname(fileURLToPath(import.meta.url));
const OUT = path.join(HERE, 'audio');
fs.mkdirSync(OUT, { recursive: true });
const VOICE = process.env.VOICE || 'id-ID-GadisNeural';
const RATE_ADJ = process.env.RATE || '+4%';
const SR = 44100;
const LEAD = 0.7, GAP = 0.45, TAIL = 1.1;

const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

async function synth(text, mp3) {
  for (let attempt = 1; attempt <= 4; attempt++) {
    try {
      const tts = new MsEdgeTTS();
      await tts.setMetadata(VOICE, OUTPUT_FORMAT.AUDIO_24KHZ_96KBITRATE_MONO_MP3);
      const { audioStream } = tts.toStream(esc(text), { rate: RATE_ADJ });
      const chunks = [];
      await new Promise((res, rej) => {
        audioStream.on('data', (d) => chunks.push(d));
        audioStream.on('end', res);
        audioStream.on('close', res);
        audioStream.on('error', rej);
      });
      tts.close();
      const buf = Buffer.concat(chunks);
      if (buf.length < 2000) throw new Error('audio kosong');
      fs.writeFileSync(mp3, buf);
      return;
    } catch (e) {
      console.warn(`  retry ${attempt}: ${e.message}`);
      await new Promise((r) => setTimeout(r, 1500 * attempt));
    }
  }
  throw new Error('TTS gagal: ' + text);
}

function readPcm(wavPath) {
  const b = fs.readFileSync(wavPath);
  let o = 12;
  while (o < b.length) {
    const id = b.toString('ascii', o, o + 4), size = b.readUInt32LE(o + 4);
    if (id === 'data') return new Int16Array(b.buffer.slice(b.byteOffset + o + 8, b.byteOffset + o + 8 + size));
    o += 8 + size + (size % 2);
  }
  throw new Error('no data chunk: ' + wavPath);
}

// buang hening di awal/akhir hasil TTS supaya jeda antarkalimat konsisten
function trim(pcm) {
  const th = 300, pad = Math.round(0.04 * SR);
  let a = 0, b = pcm.length - 1;
  while (a < pcm.length && Math.abs(pcm[a]) < th) a++;
  while (b > a && Math.abs(pcm[b]) < th) b--;
  return pcm.subarray(Math.max(0, a - pad), Math.min(pcm.length, b + pad));
}

const script = JSON.parse(fs.readFileSync(path.join(HERE, 'script.json'), 'utf8'));
const parts = [];
let t = 0;
const scenes = [];
const silence = (sec) => { const n = Math.round(sec * SR); parts.push(new Int16Array(n)); t += n / SR; };

for (const sc of script) {
  const start = t;
  silence(LEAD);
  const lines = [];
  for (let li = 0; li < sc.lines.length; li++) {
    const ln = sc.lines[li];
    const text = ln.tts || ln.cap;
    const key = crypto.createHash('sha1').update(`${VOICE}|${RATE_ADJ}|${text}`).digest('hex').slice(0, 14);
    const mp3 = path.join(OUT, key + '.mp3'), wav = path.join(OUT, key + '.wav');
    if (!fs.existsSync(wav)) {
      console.log(`TTS ${sc.id}#${li}: ${text.slice(0, 60)}…`);
      await synth(text, mp3);
      execFileSync(ffmpeg.path, ['-y', '-loglevel', 'error', '-i', mp3, '-ac', '1', '-ar', String(SR), '-c:a', 'pcm_s16le', wav]);
    }
    const pcm = trim(readPcm(wav));
    const dur = pcm.length / SR;
    lines.push({ start: +t.toFixed(3), end: +(t + dur).toFixed(3), cap: ln.cap });
    parts.push(pcm); t += dur;
    silence(li < sc.lines.length - 1 ? GAP : TAIL);
  }
  scenes.push({ id: sc.id, title: sc.title, start: +start.toFixed(3), dur: +(t - start).toFixed(3), lines });
}

const total = parts.reduce((n, p) => n + p.length, 0);
const hdr = Buffer.alloc(44);
hdr.write('RIFF', 0); hdr.writeUInt32LE(36 + total * 2, 4); hdr.write('WAVE', 8);
hdr.write('fmt ', 12); hdr.writeUInt32LE(16, 16); hdr.writeUInt16LE(1, 20); hdr.writeUInt16LE(1, 22);
hdr.writeUInt32LE(SR, 24); hdr.writeUInt32LE(SR * 2, 28); hdr.writeUInt16LE(2, 32); hdr.writeUInt16LE(16, 34);
hdr.write('data', 36); hdr.writeUInt32LE(total * 2, 40);
fs.writeFileSync(path.join(HERE, 'narration.wav'), Buffer.concat([hdr, ...parts.map((p) => Buffer.from(p.buffer, p.byteOffset, p.byteLength))]));
fs.writeFileSync(path.join(HERE, 'timeline.js'), 'window.TL = ' + JSON.stringify({ total: +t.toFixed(3), scenes }) + ';\n');

console.log(`total ${t.toFixed(1)}s, ${scenes.length} scenes`);
for (const s of scenes) console.log(`  ${s.id.padEnd(11)} ${s.start.toFixed(1).padStart(7)} +${s.dur.toFixed(1)}`);
