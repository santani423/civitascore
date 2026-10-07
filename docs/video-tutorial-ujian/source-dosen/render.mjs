// node render.mjs snap 10,20.5,33      -> snaps/t_<t>.png
// node render.mjs video out.mp4 [workers] -> full render 1920x1080 + narasi
// Butuh Microsoft Edge (atau set BROWSER=chrome) — tidak perlu download browser Playwright.
import { chromium } from 'playwright-core';
import ffmpeg from '@ffmpeg-installer/ffmpeg';
import { spawn } from 'child_process';
import { fileURLToPath, pathToFileURL } from 'url';
import fs from 'fs';
import os from 'os';
import path from 'path';

const HERE = path.dirname(fileURLToPath(import.meta.url));
const FF = ffmpeg.path;
const FPS = 25, SCALE = 1.5; // 1280x720 CSS -> 1920x1080
const [mode, arg, workersArg] = process.argv.slice(2);
const channel = process.env.BROWSER === 'chrome' ? 'chrome' : 'msedge';

async function openPage(browser) {
  const page = await browser.newPage({ viewport: { width: 1280, height: 720 }, deviceScaleFactor: mode === 'snap' ? 1 : SCALE });
  page.on('pageerror', (e) => console.error('PAGE ERROR', e.message));
  await page.goto(pathToFileURL(path.join(HERE, 'tutorial.html')).href);
  await page.evaluate(() => window.ready);
  return page;
}
const draw = (page, t) => page.evaluate((t) => window.render(t), t);
const run = (args) => new Promise((res, rej) => { const p = spawn(FF, args, { stdio: ['ignore', 'inherit', 'inherit'] }); p.on('close', (c) => (c ? rej(new Error('ffmpeg ' + c)) : res())); });

const browser = await chromium.launch({ channel });
if (mode === 'snap') {
  const page = await openPage(browser);
  fs.mkdirSync(path.join(HERE, 'snaps'), { recursive: true });
  for (const t of arg.split(',').map(Number)) {
    // putar adegan dari awal sampai t supaya state (cache kursor dsb.) sama seperti saat render penuh
    const tl = await page.evaluate(() => TL);
    const sc = tl.scenes.find((s) => t >= s.start && t < s.start + s.dur) || tl.scenes[tl.scenes.length - 1];
    for (let x = sc.start; x < t; x += 1 / FPS) await draw(page, x);
    await draw(page, t);
    await page.screenshot({ path: path.join(HERE, 'snaps', `t_${t}.png`) });
  }
} else {
  const out = path.resolve(arg || 'out.mp4');
  const W = Math.max(1, Number(workersArg) || Math.min(6, os.cpus().length - 1));
  const probe = await openPage(browser);
  const tl = await probe.evaluate(() => TL);
  await probe.close();
  const n = Math.ceil(tl.total * FPS);
  const tmp = path.join(HERE, 'segments');
  fs.mkdirSync(tmp, { recursive: true });
  const t0 = Date.now();
  let done = 0;
  const seg = async (w) => {
    const a = Math.floor((n * w) / W), b = Math.floor((n * (w + 1)) / W);
    const page = await openPage(browser);
    const ta = a / FPS;
    const sc = tl.scenes.find((s) => ta >= s.start && ta < s.start + s.dur) || tl.scenes[0];
    for (let x = sc.start; x < ta; x += 1 / FPS) await draw(page, x); // warm-up
    const file = path.join(tmp, `seg_${String(w).padStart(2, '0')}.mp4`);
    const ff = spawn(FF, ['-y', '-loglevel', 'error', '-f', 'image2pipe', '-framerate', String(FPS), '-c:v', 'mjpeg', '-i', '-',
      '-c:v', 'libx264', '-preset', 'medium', '-crf', '20', '-pix_fmt', 'yuv420p', '-r', String(FPS), file], { stdio: ['pipe', 'inherit', 'inherit'] });
    const closed = new Promise((r) => ff.on('close', r));
    for (let i = a; i < b; i++) {
      await draw(page, i / FPS);
      const buf = await page.screenshot({ type: 'jpeg', quality: 92 });
      if (!ff.stdin.write(buf)) await new Promise((r) => ff.stdin.once('drain', r));
      if (++done % 500 === 0) {
        const el = (Date.now() - t0) / 1000;
        console.log(`frame ${done}/${n}  ${el.toFixed(0)}s  eta ${((el / done) * (n - done)).toFixed(0)}s`);
      }
    }
    ff.stdin.end();
    await closed;
    await page.close();
    return file;
  };
  const files = await Promise.all(Array.from({ length: W }, (_, w) => seg(w)));
  fs.writeFileSync(path.join(tmp, 'list.txt'), files.map((f) => `file '${f.replace(/\\/g, '/')}'`).join('\n'));
  await run(['-y', '-loglevel', 'error', '-f', 'concat', '-safe', '0', '-i', path.join(tmp, 'list.txt'), '-i', path.join(HERE, 'narration.wav'),
    '-c:v', 'copy', '-af', 'loudnorm=I=-16:TP=-1.5:LRA=11', '-ar', '44100', '-c:a', 'aac', '-b:a', '160k', '-shortest', '-movflags', '+faststart', out]);
  console.log(`done ${out} in ${((Date.now() - t0) / 1000).toFixed(0)}s`);
}
await browser.close();
