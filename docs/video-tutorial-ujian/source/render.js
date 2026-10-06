// node render.js snap 1,2,3      -> snaps/t_<t>.png
// node render.js video out.mp4   -> full render with narration
const { chromium } = require('playwright-core');
const { spawn, execFileSync } = require('child_process');
const path = require('path');
const fs = require('fs');

const FF = execFileSync(path.join(__dirname, 'venv/bin/python'), ['-c', 'import imageio_ffmpeg;print(imageio_ffmpeg.get_ffmpeg_exe())']).toString().trim();
const FPS = 25;
const [mode, arg] = process.argv.slice(2);

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1280, height: 720 }, deviceScaleFactor: 1 });
  page.on('pageerror', (e) => console.error('PAGE ERROR', e.message));
  await page.goto('file://' + path.join(__dirname, 'tutorial.html'));
  await page.evaluate(() => window.ready);
  const total = await page.evaluate(() => TL.total);
  const draw = (t) => page.evaluate(async (t) => {
    render(t);
    await Promise.all([...document.images].filter((i) => !i.complete).map((i) => new Promise((r) => { i.onload = i.onerror = r; })));
  }, t);

  if (mode === 'snap') {
    fs.mkdirSync(path.join(__dirname, 'snaps'), { recursive: true });
    for (const t of arg.split(',').map(Number)) {
      await draw(t);
      await page.screenshot({ path: path.join(__dirname, 'snaps', `t_${t}.png`) });
    }
  } else {
    const out = arg || 'out.mp4';
    const ff = spawn(FF, ['-y', '-loglevel', 'error', '-f', 'image2pipe', '-framerate', String(FPS), '-c:v', 'mjpeg', '-i', '-',
      '-i', path.join(__dirname, 'narration.wav'),
      '-c:v', 'libx264', '-preset', 'medium', '-crf', '20', '-pix_fmt', 'yuv420p', '-c:a', 'aac', '-b:a', '128k', '-shortest', '-movflags', '+faststart', out], { stdio: ['pipe', 'inherit', 'inherit'] });
    const n = Math.ceil(total * FPS);
    for (let i = 0; i < n; i++) {
      await draw(i / FPS);
      const buf = await page.screenshot({ type: 'jpeg', quality: 92 });
      if (!ff.stdin.write(buf)) await new Promise((r) => ff.stdin.once('drain', r));
      if (i % 500 === 0) console.log(`frame ${i}/${n}`);
    }
    ff.stdin.end();
    await new Promise((r) => ff.on('close', r));
    console.log('done', out);
  }
  await browser.close();
})();
