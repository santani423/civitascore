import json, os, subprocess, wave, array
import imageio_ffmpeg

FF = imageio_ffmpeg.get_ffmpeg_exe()
HERE = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.join(HERE, "audio")
os.makedirs(OUT, exist_ok=True)
RATE = 44100
LEAD, GAP, TAIL = 0.7, 0.45, 1.1

script = json.load(open(os.path.join(HERE, "script.json")))
pcm = array.array("h")
t = 0.0
scenes = []

def silence(sec):
    pcm.extend([0] * int(sec * RATE))

for si, sc in enumerate(script):
    start = t
    silence(LEAD); t += LEAD
    lines = []
    for li, ln in enumerate(sc["lines"]):
        base = os.path.join(OUT, f"{si:02d}_{li:02d}")
        if not os.path.exists(base + ".wav"):
            subprocess.run(["say", "-v", "Damayanti", "-r", "168", "-o", base + ".aiff", ln.get("tts", ln["cap"])], check=True)
            subprocess.run([FF, "-y", "-loglevel", "error", "-i", base + ".aiff", "-ac", "1", "-ar", str(RATE), base + ".wav"], check=True)
        with wave.open(base + ".wav") as w:
            data = array.array("h", w.readframes(w.getnframes()))
        dur = len(data) / RATE
        lines.append({"start": round(t, 3), "end": round(t + dur, 3), "cap": ln["cap"]})
        pcm.extend(data); t += dur
        gap = GAP if li < len(sc["lines"]) - 1 else TAIL
        silence(gap); t += gap
    scenes.append({"id": sc["id"], "title": sc["title"], "start": round(start, 3), "dur": round(t - start, 3), "lines": lines})

with wave.open(os.path.join(HERE, "narration.wav"), "wb") as w:
    w.setnchannels(1); w.setsampwidth(2); w.setframerate(RATE); w.writeframes(pcm.tobytes())

with open(os.path.join(HERE, "timeline.js"), "w") as f:
    f.write("window.TL = " + json.dumps({"total": round(t, 3), "scenes": scenes}, ensure_ascii=False) + ";\n")
print(f"total {t:.1f}s, {len(scenes)} scenes")
for s in scenes: print(f"  {s['id']:10s} {s['start']:7.1f} +{s['dur']:.1f}")
