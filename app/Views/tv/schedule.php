<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Schedule Produksi <?= esc(ucfirst($division)) ?></title>
  <style>
    :root{--bg:#0b1220;--fg:#e8edf5;--mut:#8a96a8;--line:#1f2a3d;--grp:#16223a}
    *{box-sizing:border-box}
    html,body{height:100%;margin:0;background:var(--bg);color:var(--fg);font-family:system-ui,Segoe UI,Roboto,sans-serif;overflow:hidden}
    header{display:flex;justify-content:space-between;align-items:baseline;padding:1.2vh 2vw;font-size:3vh;border-bottom:2px solid var(--line)}
    header small{color:var(--mut);font-size:2.2vh}
    .cols,.row{display:grid;grid-template-columns:1.4fr 1.2fr 1.6fr 1.2fr 1fr 1fr;gap:1vw;padding:0 2vw}
    .cols{color:var(--mut);font-size:2vh;padding-top:1vh;padding-bottom:1vh;text-transform:uppercase;border-bottom:1px solid var(--line)}
    #viewport{height:calc(100vh - 14vh);overflow:hidden}
    #track{will-change:transform}
    .row{font-size:3vh;padding-top:1.4vh;padding-bottom:1.4vh;border-bottom:1px solid var(--line);align-items:center}
    .row span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .grp{padding:1vh 2vw;background:var(--grp);font-size:2.6vh;font-weight:700;letter-spacing:.05em}
    .grp.now{background:#14532d}
    .st{justify-self:start;padding:.3vh 1vw;border-radius:2vh;font-size:2.4vh;font-weight:600}
    .st.info{background:#0c4a6e}.st.success{background:#166534}.st.warning{background:#854d0e}.st.secondary{background:#334155;color:#cbd5e1}
    .empty{padding:6vh 2vw;color:var(--mut);font-size:3vh;text-align:center}
  </style>
</head>
<body>
  <header><strong>SCHEDULE PRODUKSI · <?= esc(strtoupper($division)) ?></strong><small id="meta"></small></header>
  <div class="cols"><span>Mesin</span><span>Order</span><span>Customer</span><span>Proses</span><span>Jam</span><span>Status</span></div>
  <div id="viewport"><div id="track"></div></div>

<script>
const KEY   = <?= json_encode($token) ?>;
const SPEED = Number(new URLSearchParams(location.search).get('speed')) || 40;   // px per detik, ubah lewat &speed=60
const STATUS = {approved:['Siap','info'], running:['Berjalan','success'], paused:['Jeda','warning'], done:['Selesai','secondary']};

const viewport = document.getElementById('viewport');
const track    = document.getElementById('track');
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

let offset = 0, half = 0, scrolling = false, last = 0, lastPayload = '';

function render(data) {
  let html = '', current = null;
  for (const r of data.rows) {
    if (r.shift !== current) {
      current = r.shift;
      const s = data.shifts[current] || {label: current, start: '', end: ''};
      html += `<div class="grp ${current === data.current_shift ? 'now' : ''}">SHIFT ${esc(s.label.toUpperCase())} · ${esc(s.start)}–${esc(s.end)}</div>`;
    }
    const st = STATUS[r.status] || [r.status, 'secondary'];
    html += `<div class="row"><span>${esc(r.machine_code)}</span><span>${esc(r.orders)}</span><span>${esc(r.customers)}</span>`
          + `<span>${esc(r.process)}</span><span>${esc(r.start_at.slice(11,16))}–${esc(r.end_at.slice(11,16))}</span>`
          + `<span class="st ${st[1]}">${esc(st[0])}${r.overshift ? ' · OVERSHIFT' : ''}</span></div>`;
  }
  if (!html) html = '<div class="empty">Belum ada schedule yang disetujui</div>';
  html += '<div style="height:6vh"></div>';   // jeda sebelum kembali ke baris pertama

  track.innerHTML = html;
  const h = track.scrollHeight;
  if (h > viewport.clientHeight) {          // hanya scroll bila isi melebihi layar
    track.innerHTML = html + html;          // salinan kedua agar loop mulus (pagi → malam → pagi)
    half = h; scrolling = true; offset = offset % half;
  } else {
    scrolling = false; offset = 0; track.style.transform = 'none';
  }
  document.getElementById('meta').textContent =
    `${data.date} · shift berjalan: ${(data.shifts[data.current_shift] || {}).label || '-'} · update ${new Date().toLocaleTimeString('id-ID')}`;
}

function frame(t) {
  if (scrolling) {
    const dt = last ? (t - last) / 1000 : 0;
    offset += SPEED * dt;
    if (offset >= half) offset -= half;
    track.style.transform = `translateY(${-offset}px)`;
  }
  last = t;
  requestAnimationFrame(frame);
}

async function refresh() {
  try {
    const res  = await fetch('<?= site_url('display/api/board/' . $division) ?>?key=' + encodeURIComponent(KEY), {cache: 'no-store'});
    if (!res.ok) return;
    const text = await res.text();
    const data = JSON.parse(text);
    const sig  = JSON.stringify([data.date, data.current_shift, data.rows]);
    if (sig !== lastPayload) { lastPayload = sig; render(data); }   // tidak rebuild bila data sama → scroll tidak loncat
  } catch (e) { /* jaringan putus: biarkan data lama tetap tampil */ }
}

refresh(); setInterval(refresh, 30000); requestAnimationFrame(frame);
</script>
</body>
</html>
