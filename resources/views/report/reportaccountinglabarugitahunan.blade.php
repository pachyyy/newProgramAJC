@extends('report.masterreport2')

{{-- Table styling lives in public/css/report-table.css (loaded via report/newmaster2.blade.php).
     Laporan Laba Rugi Tahunan (annual income statement): styled .tb-report dengan header DUA
     TINGKAT — Keterangan + 13 band (Januari..Desember + Total), masing-masing Rp. + %. Header
     dua tingkat dibangun manual per docs/new-slider-table-guide.md TANPA drag
     (ReportTable.headHtml() tidak bisa render rowspan/colspan) — tiap kolom tetap punya menu
     roda gigi (sembunyikan/total), lihat buildGroupedThead(); pola sama persis dengan
     reportaccountinglabarugi.blade.php (band bulan di sana cuma 3: Bulan Lalu/Ini/s.d Ini, di
     sini 13: 12 bulan + Total). Halaman ini sebelumnya @extends('report.masterreport4') memakai
     engine header-nested miliknya sendiri (item[6]/item[7] di gcart_header) — diganti total ke
     masterreport2 + report-table.js supaya gear-nya identik dengan reportaccountinglabarugi.
     Mode "Rekap by NoBukti" yang lama (tidak pernah bisa dicapai user — dropdown Report Mode-nya
     selalu `hidden`) sudah dihapus bersamaan dengan migrasi ini.
     Filter: Bulan/Tahun tetap di toolbar; Divisi dipindah ke modal "Filter Laporan" (satu-satunya
     field di sana, wajib diisi, tidak dihitung di badge) — dropdown polos (bukan entity-picker
     modalAccountingJurnal lagi), diisi dari endpoint loadDivisi controller ini sendiri.
     Tidak ada kolom No Bukti → tanpa panel voucher.
     Alur: triggerSp (ambil totalA/B/C dari dbLRHPP, endpoint dipakai bareng dengan labarugi
     bulanan) → sp_ReportLabaRugiTH (baris per bulan + % memakai total itu).
     Data hanya dimuat setelah klik "Tampilkan".
     Di atas tabel: 2 chart dibangun sisi-klien dari lastRows untuk Januari..bulan terpilih —
     kiri bar Pendapatan (Kelompok=3) vs Beban (Kelompok=4) per bulan (rumus sama dengan
     labarugi bulanan: dijumlah dulu, baru abs); kanan line Laba / Rugi per bulan dari baris
     Persen='D' (tanda dipertahankan, rugi tampil di bawah nol). --}}

<!-- Chart.js v4 (di-bundle lokal: public/plugins/chart.js/chart.umd.min.js) -->
<script src="{!! URL::asset('plugins/chart.js/chart.umd.min.js') !!}?v={{ @filemtime(base_path('public/plugins/chart.js/chart.umd.min.js')) ?: '1' }}"></script>

<style>
  /* tinggi awal area tabel supaya dropdown tidak terpotong container pendek */
  .tb-report .table-wrap { min-height: 10vh; }

  /* Tidak ada drag-reorder di halaman ini (header dua tingkat/grouped tidak bisa
     menoleransi kolom pindah band) -- timpa cursor:grab bawaan .th-inner supaya
     tidak menyiratkan kolom bisa diseret. Gear (hide/total) tetap aktif. */
  .tb-report .tb thead th.rt-th .th-inner { cursor: default; }

  /* Header dua tingkat 13-band (12 bulan + Total) jauh lebih lebar dari layar --
     biarkan scroll horizontal alami di dalam .table-wrap (sudah overflow:auto). */
  .tb-report .tb thead th.th-group,
  .tb-report .tb thead th.rt-th { white-space: nowrap; }

  /* ── Chart section ── */
  .tb-report .chart-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;
  }
  @media (max-width: 900px) { .tb-report .chart-grid { grid-template-columns: 1fr; } }
  .tb-report .chart-box {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
    padding: 16px 20px; box-shadow: 0 1px 4px rgba(0,0,0,.06);
  }
  .tb-report .chart-box h3 {
    font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 12px;
  }
  .tb-report .chart-holder { position: relative; height: 260px; }
  .tb-report .chart-holder canvas { max-height: 260px; }
</style>

@section('header2')
<div class="tb-report main">
  <div class="content">

    <!-- TOOLBAR -->
    <div class="toolbar">

      <!-- Period selector (populated dynamically by populatePeriodSelectors) -->
      <div class="period-select-wrap">
        <label>Periode</label>
        <select class="period-select" id="periodBulan" onchange="changePeriodParts()"></select>
        <select class="period-select" id="periodTahun" onchange="changePeriodParts()"></select>
      </div>

      {{-- Search --}}
      <div>
        <input class="search-inp" type="text" id="searchBox2" placeholder="Cari data..." oninput="applyFilters()" style="width:160px">
      </div>

      {{-- Divisi pindah ke modal "Filter Laporan" -- lihat docs/new-filter-modal-ui-guide.md.
           Nilai sebenarnya: globalDivisi (var JS). --}}

      <!-- Actions: search + filter + tampilkan + export -->
      <div class="action-group">
        {{-- Dibuka lewat plugin jQuery (Bootstrap 4), BUKAN data-bs-toggle (Bootstrap 5) —
             lihat aturan dua-Bootstrap di new-design-all-report-guide.md §5.1. --}}
        <button class="btn-load" type="button" onclick="$('#modalFilter').modal('show')">
          <i class="fas fa-filter"></i> Filter
        </button>
        <button class="btn-load" onclick="makeTable('REPORT')" title="Tampilkan laporan"><i class="fas fa-check"></i> Tampilkan</button>
        <div class="export-wrap" id="exportWrap">
          <button class="export-btn" onclick="toggleExport()"><i class="bi bi-arrow-down"></i> Export <i class="bi bi-caret-down-fill"></i></button>
          <div class="export-drop" id="exportDrop">
            <div class="export-opt" onclick="doExport('Excel')"><i class="bi bi-journals text-success"></i> Ekspor ke <span class="ext">XLSX</span></div>
            <div class="export-opt" onclick="doExport('CSV')"><i class="bi bi-clipboard"></i> Ekspor ke <span class="ext">CSV</span></div>
            <div class="export-opt" onclick="doExport('Print')"><i class="bi bi-printer-fill text-warning"></i> Cetak Laporan</div>
          </div>
        </div>
      </div>
    </div>

    <!-- CHARTS (dibangun sisi-klien dari lastRows, Januari..bulan terpilih) -->
    <div class="chart-grid">
      <div class="chart-box">
        <h3>Pendapatan vs Beban</h3>
        <div class="chart-holder"><canvas id="lrtChart1"></canvas></div>
      </div>
      <div class="chart-box">
        <h3>Laba / Rugi per Bulan</h3>
        <div class="chart-holder"><canvas id="lrtChart2"></canvas></div>
      </div>
    </div>

    <!-- Bar kolom tersembunyi (diisi oleh report-table.js / ReportTable) -->
    <div id="rtBar"></div>

    <!-- TABLE — header dua tingkat (band per bulan) dibangun oleh buildGroupedThead() -->
    <div class="table-outer">
      <div class="table-wrap">
        <table class="tb" id="mainTable">
          <thead>
            <tr>
              <th rowspan="2" style="min-width:260px">Keterangan</th>
              <th colspan="2" class="th-group">Januari</th>
              <th colspan="2" class="th-group">Februari</th>
              <th colspan="2" class="th-group">Maret</th>
              <th colspan="2" class="th-group">April</th>
              <th colspan="2" class="th-group">Mei</th>
              <th colspan="2" class="th-group">Juni</th>
              <th colspan="2" class="th-group">Juli</th>
              <th colspan="2" class="th-group">Agustus</th>
              <th colspan="2" class="th-group">September</th>
              <th colspan="2" class="th-group">Oktober</th>
              <th colspan="2" class="th-group">November</th>
              <th colspan="2" class="th-group">Desember</th>
              <th colspan="2" class="th-group">Total</th>
            </tr>
            <tr>
              @for ($i = 0; $i < 13; $i++)
                <th class="num" style="min-width:130px">Rp.</th>
                <th class="num" style="min-width:70px">%</th>
              @endfor
            </tr>
          </thead>
          <tbody id="tableBody">
            <tr class="empty-row"><td colspan="27">Atur filter lalu klik <b>Tampilkan</b> untuk memuat laporan.</td></tr>
          </tbody>
        </table>
      </div>
      <div class="table-footer">
        <span id="footerLabel">Belum ada data dimuat</span>
      </div>
    </div>

    <div class="rt-hint">
      <i class="bi bi-info-circle"></i>
      Klik <i class="bi bi-gear"></i> pada judul kolom untuk sembunyikan kolom atau atur total.
    </div>

  </div><!-- /content -->

  <!-- TOAST -->
  <div class="toast" id="toast"><span id="ti"></span><span id="tm"></span></div>

</div><!-- /tb-report -->

{{-- Modal DILETAKKAN DI LUAR .tb-report supaya reset `.tb-report *{margin:0;padding:0}`
     di report-table.css tidak merusak padding/margin modal Bootstrap. --}}

<!-- modal filter -->
<div class="modal fade rt-filter" id="modalFilter">
  <div class="modal-dialog modal-md">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-filter"></i> Filter Laporan
          <span class="rt-active-badge" id="filterBadge">0 aktif</span>
        </h5>
        {{-- data-dismiss (BS4) = yang benar-benar menutup, karena modal ini dibuka lewat
             $.fn.modal milik BS4 (jQuery baru dimuat SESUDAH bundle BS5 di masterreport2).
             data-bs-dismiss dibiarkan untuk jaga-jaga. --}}
        <button type="button" class="btn-close" aria-label="Close" data-dismiss="modal" data-bs-dismiss="modal"
                onclick="$('#modalFilter').modal('hide')"></button>
      </div>

      <div class="modal-body">

        <div class="rt-section">
          <div class="rt-group-label">Pengaturan Laporan</div>
          <div class="rt-grid-1">
            <div>
              <label class="rt-field-label" for="modalDivisi">Divisi</label>
              {{-- Diisi dari laporanaccountinglabarugitahunan_loaddivisi (loadDivisiDropdown()).
                   Selalu punya nilai (tidak ada opsi "Semua") -- pilihan wajib, bukan filter yang
                   bisa dimatikan, jadi TIDAK dihitung di badge (lihat updateFilterBadge()). --}}
              <select class="rt-native" id="modalDivisi"></select>
            </div>
          </div>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="rt-reset-link" onclick="resetAllFilters()">Reset semua</button>
        <div class="rt-footer-buttons">
          <button type="button" class="rt-btn rt-btn-ghost" data-dismiss="modal" data-bs-dismiss="modal"
                  onclick="$('#modalFilter').modal('hide')">Batal</button>
          <button type="button" class="rt-btn rt-btn-primary" onclick="applyModalFilter()">Terapkan</button>
        </div>
      </div>

    </div>
  </div>
</div>
@endsection


@section('jsreport')
<script type="text/javascript">
  let defaultBulan = new Date().getMonth() + 1;  // 1–12
  let defaultTahun = new Date().getFullYear();

  let g_reportTitle = "";
  let lastRows = [];   // hasil fetch terakhir (dipakai render / search)
  let chartBulan = defaultBulan;   // jumlah bulan di chart (Januari..N), dikunci saat klik "Tampilkan"

  let globalDivisi = "-";  // diisi loadDivisiDropdown() saat page load (selalu wajib diisi)

  // Base total untuk kolom % (diisi triggerSp sebelum doReport). Dipakai bareng dengan
  // laporanaccountinglabarugi (bulanan) -- lihat triggerSp().
  let tempTotalA = 0, tempTotalB = 0, tempTotalC = 0;

  // Report mode dipakai engine masterreport2 (doSetHeader) — cukup satu int, halaman ini
  // cuma satu mode (mode "Rekap by NoBukti" yang lama sudah dihapus, tidak pernah bisa
  // dicapai user).
  g_modeReport = 25;

  const reportUrl  = "{{ url('laporanaccountinglabarugitahunan_doReport') }}";
  const triggerUrl = "{{ url('laporanaccountinglabarugi_triggerSp') }}";

  const NAMA_BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

  // Susunan kolom tabel (urutan mengikuti header dua tingkat di markup, dan TETAP -- tidak ada
  // drag di halaman ini). Hanya kolom Rp (bln1..bln12, bln) yang ditotal; kolom % (P1..P13)
  // tidak ditotal. `band` menandai kolom yang ada di bawah band bulan/Total di baris pertama
  // thead (null = kolom rowspan-2 biasa, cuma Keterangan); `short` = teks kolom di baris kedua
  // thead (band-nya sudah menjelaskan konteks). Label PENUH (c.label) dipakai untuk
  // gcart_header / export / Atur Kolom supaya tidak ambigu ("Rp."/"%" saja muncul 13x kalau
  // tanpa prefiks band).
  const COLS = [
    { key: 'keterangan', label: 'Keterangan', type: 'str', dec: 0, total: false, band: null },
  ];
  NAMA_BULAN.forEach((nama, i) => {
    const n = i + 1;
    COLS.push({ key: 'bln' + n, label: nama + ' Rp.', type: 'num', dec: 2, total: true,  band: 'm' + n, short: 'Rp.' });
    COLS.push({ key: 'P' + n,   label: nama + ' %',   type: 'num', dec: 2, total: false, band: 'm' + n, short: '%'  });
  });
  COLS.push({ key: 'bln', label: 'Total Rp.', type: 'num', dec: 2, total: true,  band: 'total', short: 'Rp.' });
  COLS.push({ key: 'P13', label: 'Total %',   type: 'num', dec: 2, total: false, band: 'total', short: '%'  });

  function bandLabel(band) {
    if (band === 'total') { return 'Total'; }
    return NAMA_BULAN[parseInt(band.slice(1), 10) - 1];
  }

  $(document).ready(function () {
    doSetHeader(g_modeReport);   // muat gcart_header (default / hasil Reset kolom tersimpan)
    populatePeriodSelectors();
    loadDivisiDropdown();   // isi dropdown Divisi (default: divisi pertama)

    // Header tabel interaktif TANPA drag (lihat komentar di atas file): gear per kolom untuk
    // sembunyikan/total, + bar "Reset kolom"/kolom tersembunyi. Tidak ada "Tampilan" switcher --
    // halaman ini cuma satu mode.
    ReportTable.init({
      table: '#mainTable',
      bar: '#rtBar',
      onChange: function () { if (lastRows.length) { applyFilters(); } else { render(); } }
    });

    // Chart dipaint kosong sebelum data pertama dimuat.
    buildCharts([]);

    // Sengaja TIDAK memuat data saat halaman dibuka — laporan hanya dimuat setelah
    // pengguna klik tombol "Tampilkan".
  });

  // Header sederhana untuk menjaga engine masterreport2 tetap terinisialisasi
  // (doSetHeader memanggil ini bila belum ada header tersimpan). Tabel styled
  // di-render sendiri oleh render() dari gcart_header (dibangun dari COLS di sini).
  function setDefaultHeader() {
    gcart_header = COLS.map(c => [c.key, c.label, 1, (c.type === 'num' ? 'float' : c.type), (c.total ? 1 : 0), c.dec]);
    gsum_issubtotal = 1; gsum_isgrandtotal = 1;
  }

  /* ── PERIODE (Bulan / Tahun) ── */
  function populatePeriodSelectors() {
    const selB = document.getElementById('periodBulan');
    const selT = document.getElementById('periodTahun');
    selB.innerHTML = NAMA_BULAN.map((nama, i) =>
      `<option value="${i + 1}" ${(i + 1) == defaultBulan ? 'selected' : ''}>${nama}</option>`).join('');
    const thisYear = new Date().getFullYear();
    let years = '';
    for (let y = thisYear; y >= thisYear - 6; y--) {
      years += `<option value="${y}" ${y == defaultTahun ? 'selected' : ''}>${y}</option>`;
    }
    selT.innerHTML = years;
  }
  // Hanya perbarui nilai periode; TIDAK memuat data (tunggu klik "Tampilkan").
  function changePeriodParts() {
    defaultBulan = parseInt(document.getElementById('periodBulan').value, 10);
    defaultTahun = parseInt(document.getElementById('periodTahun').value, 10);
  }

  /* ── EXPORT ── */
  function toggleExport() { document.getElementById('exportDrop').classList.toggle('open'); }
  document.addEventListener('click', function (e) {
    const wrap = document.getElementById('exportWrap');
    if (wrap && !wrap.contains(e.target)) { document.getElementById('exportDrop').classList.remove('open'); }
  });
  function doExport(fmt) {
    document.getElementById('exportDrop').classList.remove('open');
    if (fmt === 'Print') { window.print(); return; }
    exportDelimited(fmt);
  }
  function exportDelimited(fmt) {
    // Hanya kolom yang sedang terlihat (gcart_header[i][2]===1) yang ikut diekspor -- konsisten
    // dengan yang tampil di layar setelah kolom disembunyikan lewat gear. Label sudah lengkap
    // dengan prefiks band (mis. "Januari Rp.") jadi tidak ambigu tanpa perlu traversal khusus.
    const cols = gcart_header.filter(c => c[2] === 1);
    const header = cols.map(c => c[1]);
    const body = (lastRows || []).map(r => cols.map(function (c) {
      const v = pickCI(r, c[0]);
      if (c[3] === 'float' || c[3] === 'int') return currencyNormalizer(v);
      return (v == null ? '' : v);
    }));
    const rows = [header].concat(body);
    ReportExport.fromRows(rows, fmt, { customName: 'LabaRugiTahunan_' + defaultTahun });
    showToast('📄', 'Data diekspor sebagai ' + fmt);
  }

  /* ── LOAD DATA: triggerSp (base total, endpoint dipakai bareng labarugi bulanan)
     → sp_ReportLabaRugiTH ── */
  function triggerSp(bulan, tahun, divisi) {
    tempTotalA = 0; tempTotalB = 0; tempTotalC = 0;
    $.ajax({
      url: triggerUrl, type: 'get', async: false,
      data: { bulan: bulan, tahun: tahun, divisi: divisi },
      success: function (res) {
        if (res && res[0]) {
          tempTotalA = res[0].totalA || 0;
          tempTotalB = res[0].totalB || 0;
          tempTotalC = res[0].totalC || 0;
        }
      }
    });
  }

  function makeTable(_mode) {
    g_reportTitle = 'REPORT LABA RUGI TAHUNAN';
    let _divisi = globalDivisi || '-';

    // muat gcart_header (default / hasil Reset kolom tersimpan)
    if (typeof doSetHeader === 'function') { doSetHeader(g_modeReport); }

    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');
    chartBulan = defaultBulan;

    // 1) ambil base total untuk kolom %  2) ambil baris laba rugi tahunan
    triggerSp(defaultBulan, defaultTahun, _divisi);

    const data = {
      inputBulan: defaultBulan, inputTahun: defaultTahun, divisi: _divisi,
      totalA: tempTotalA, totalB: tempTotalB, totalC: tempTotalC
    };

    $.ajax({
      url: reportUrl, type: 'get', data: data,
      success: function (res) {
        lastRows = Array.isArray(res) ? res : ((res && res.res1) ? res.res1 : []);
        $('#searchBox2').val('');
        render();
        buildCharts(lastRows);
      },
      error: function () {
        lastRows = [];
        render();
        buildCharts(lastRows);
      }
    });
  }

  /* ── helpers ── */
  function num(v) { if (v === null || v === undefined || v === '') return 0; const n = parseFloat(v); return isNaN(n) ? 0 : n; }
  function str(v) { return (v == null ? '' : String(v)).trim(); }
  function pickCI(r, key) {
    if (r[key] !== undefined) return r[key];
    const lk = String(key).toLowerCase();
    for (const k in r) { if (k.toLowerCase() === lk) return r[k]; }
    return undefined;
  }
  // HTML-escape teks bebas (nama divisi bisa diisi user).
  function esc(v) {
    return String(v == null ? '' : v)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  /* ── CHARTS (dibangun sisi-klien dari lastRows; kolom Persen/Kelompok dari SP) ── */
  let _charts = {};

  function fmtShort(v) {
    v = Math.round(num(v)); const a = Math.abs(v);
    if (a >= 1e9) return (v / 1e9).toFixed(1).replace(/\.0$/, '') + ' M';
    if (a >= 1e6) return (v / 1e6).toFixed(1).replace(/\.0$/, '') + ' jt';
    if (a >= 1e3) return (v / 1e3).toFixed(0) + ' rb';
    return String(v);
  }
  function _destroyChart(id) { if (_charts[id]) { _charts[id].destroy(); delete _charts[id]; } }

  /* ── Chart 1 (kiri): Pendapatan (Kelompok 3) vs Beban (Kelompok 4), dijumlah per bulan
     Januari..chartBulan (tanpa kolom Total).
     Chart 2 (kanan): Laba / Rugi per bulan dari baris Persen='D', tanda dipertahankan. ── */
  function buildCharts(rows) {
    if (typeof Chart === 'undefined') return;   // gagal muat Chart.js → lewati (tabel tetap jalan)
    try {
      Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
      Chart.defaults.font.size = 12;
      Chart.defaults.color = '#64748b';

      const months = [];
      for (let m = 1; m <= chartBulan; m++) { months.push(m); }
      const labels = months.map(m => NAMA_BULAN[m - 1]);

      // Dijumlah dulu apa adanya, baru tandanya dibuang — supaya total bersih
      // (mis. beban yang saling menghapus) tidak melar akibat abs per baris.
      const sumMonth = (kelompok, m) => Math.abs((rows || [])
        .filter(r => str(pickCI(r, 'Kelompok')) === kelompok)
        .reduce((acc, r) => acc + currencyNormalizer(pickCI(r, 'bln' + m)), 0));

      _destroyChart('lrt1');
      _charts.lrt1 = new Chart(document.getElementById('lrtChart1'), {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [
            { label: 'Pendapatan', data: months.map(m => sumMonth('3', m)), backgroundColor: '#4F46E5', borderRadius: 6 },
            { label: 'Beban',      data: months.map(m => sumMonth('4', m)), backgroundColor: '#DB2777', borderRadius: 6 },
          ]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: {
            legend: { position: 'bottom' },
            tooltip: { callbacks: { label: (c) => ' ' + c.dataset.label + ': ' + fmtShort(c.parsed.y) } }
          },
          scales: { y: { ticks: { callback: (v) => fmtShort(v) } } }
        }
      });

      // Baris LABA / RUGI (Persen='D'). Nilai negatif (rugi) sengaja TIDAK di-abs.
      const lrRow = (rows || []).find(r => str(pickCI(r, 'Persen')).toUpperCase() === 'D');
      const labaRugi = months.map(m => lrRow ? currencyNormalizer(pickCI(lrRow, 'bln' + m)) : 0);

      _destroyChart('lrt2');
      _charts.lrt2 = new Chart(document.getElementById('lrtChart2'), {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: 'Laba / Rugi',
            data: labaRugi,
            borderColor: '#16a34a',
            backgroundColor: 'rgba(22,163,74,.12)',
            fill: true, tension: 0.3,
            pointRadius: 4, pointHoverRadius: 6,
            pointBackgroundColor: labaRugi.map(v => v < 0 ? '#dc2626' : '#16a34a')
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: (c) => ' ' + (c.parsed.y < 0 ? 'Rugi' : 'Laba') + ': ' + fmtShort(c.parsed.y) } }
          },
          scales: {
            y: {
              ticks: { callback: (v) => fmtShort(v) },
              // garis nol ditebalkan supaya bulan rugi (di bawah nol) mudah dibedakan
              grid: {
                color: (ctx) => (ctx.tick && ctx.tick.value === 0) ? '#94a3b8' : 'rgba(0,0,0,.06)',
                lineWidth: (ctx) => (ctx.tick && ctx.tick.value === 0) ? 1.5 : 1
              }
            }
          }
        }
      });
    } catch (e) { console.error('buildCharts', e); }
  }

  /* ── HEADER DUA TINGKAT (band per bulan + Total), TANPA drag ──
     ReportTable.headHtml() cuma bisa satu baris flat, jadi header dibangun manual di sini --
     tapi tiap kolom tetap dapat tombol roda gigi (data-rtgear) yang didengarkan oleh listener
     yang sama dipasang ReportTable.init() (event delegation di elemen <thead>, lihat
     docs/new-slider-table-guide.md). Kolom TIDAK bisa diseret (tidak ada .th-inner[draggable]/
     .th-grip di markup) supaya band tidak pernah pecah. Index (idx) yang dipakai
     `data-rtgear`/`data-gidx` adalah posisi di gcart_header, yang urutannya SELALU sama dengan
     COLS karena tidak ada drag yang bisa mengubahnya. ── */
  function leafTh(idx, label, col, rowspan2) {
    const isNum = (col[3] === 'float' || col[3] === 'int');
    return '<th class="rt-th' + (isNum ? ' num' : '') + '"' + (rowspan2 ? ' rowspan="2"' : '') +
      ' data-gidx="' + idx + '">' +
      '<div class="th-inner">' +
      '<span class="th-label">' + label + '</span>' +
      '<button type="button" class="th-gear" data-rtgear="' + idx + '" title="Setting kolom"><i class="bi bi-gear"></i></button>' +
      '</div></th>';
  }

  function buildGroupedThead() {
    let row1 = '', row2 = '';
    let i = 0;
    while (i < COLS.length) {
      const c = COLS[i];
      const col = gcart_header[i];

      if (!c.band) {
        if (Number(col[2]) === 1) { row1 += leafTh(i, c.label, col, true); }
        i++;
        continue;
      }

      // kumpulkan run kolom berurutan dalam band yang sama (band SELALU kontigu karena tidak ada drag)
      const band = c.band;
      let j = i, count = 0;
      while (j < COLS.length && COLS[j].band === band) {
        const bcol = gcart_header[j];
        if (Number(bcol[2]) === 1) { count++; row2 += leafTh(j, COLS[j].short || COLS[j].label, bcol, false); }
        j++;
      }
      if (count > 0) { row1 += '<th colspan="' + count + '" class="th-group">' + bandLabel(band) + '</th>'; }
      i = j;
    }
    return '<tr>' + row1 + '</tr><tr>' + row2 + '</tr>';
  }

  /* ── RENDER: baris laba rugi apa adanya (urutan dari SP) + Grand Total pada kolom Rp. Kolom
     terlihat & urutan dari gcart_header (item[2]===1) supaya show/hide lewat gear benar-benar
     berpengaruh; urutan itu sendiri tetap sama dengan COLS karena tidak ada drag. ── */
  function render() {
    const cols  = gcart_header.filter(c => c[2] === 1);
    const thead = document.querySelector('#mainTable thead');
    const tbody = document.getElementById('tableBody');
    const search = ($('#searchBox2').val() || '').trim().toLowerCase();

    thead.innerHTML = buildGroupedThead();
    ReportTable.refresh();   // segarkan #rtBar (biasanya efek samping headHtml(), tapi di sini header dibangun manual)

    const rows = (lastRows || []).filter(r => !search || rowSearchText(r, cols).indexOf(search) !== -1);

    if (!rows.length) {
      tbody.innerHTML = '<tr class="empty-row"><td colspan="' + cols.length + '">Tidak ada data ditemukan.</td></tr>';
      document.getElementById('footerLabel').textContent = 'Tidak ada data';
      return;
    }

    const totalCols = cols.filter(c => (c[3] === 'float' || c[3] === 'int') && c[4] === 1);
    const totalKeys = totalCols.map(c => c[0]);
    const totals = {}; totalKeys.forEach(k => totals[k] = 0);

    let html = '';
    rows.forEach(r => {
      totalKeys.forEach(k => { totals[k] += currencyNormalizer(pickCI(r, k)); });
      html += '<tr class="data-row">' + cols.map(function (c) {
        const v = pickCI(r, c[0]);
        if (c[3] === 'float' || c[3] === 'int') {
          const n = currencyNormalizer(v);
          return '<td class="num">' + (n === 0 ? '' : format_number(n, c[5])) + '</td>';
        }
        return '<td>' + nullToEmpty(v) + '</td>';
      }).join('') + '</tr>';
    });

    if (gsum_isgrandtotal === 0) html += totalRow('GRAND TOTAL', totals, cols, totalKeys, 'grand-total');

    tbody.innerHTML = html;
    document.getElementById('footerLabel').textContent = 'Menampilkan ' + rows.length + ' baris';
  }

  // Baris total: nilai di tiap kolom numerik yang ditotal; label membentang seluruh kolom
  // non-total yang BERURUTAN mulai dari kolom non-total pertama (bukan cuma satu sel sempit).
  function totalRow(label, sums, cols, totalKeys, cls) {
    const labelIdx = cols.findIndex(c => totalKeys.indexOf(c[0]) === -1);
    if (labelIdx === -1) {
      return '<tr class="' + cls + '">' + cols.map(c => '<td class="num">' + format_number(sums[c[0]], c[5]) + '</td>').join('') + '</tr>';
    }
    let span = 1;
    while (labelIdx + span < cols.length && totalKeys.indexOf(cols[labelIdx + span][0]) === -1) { span++; }

    const tds = [];
    let idx = 0;
    while (idx < cols.length) {
      if (idx === labelIdx) {
        tds.push('<td colspan="' + span + '">' + label + '</td>');
        idx += span;
        continue;
      }
      const c = cols[idx];
      tds.push(totalKeys.indexOf(c[0]) !== -1 ? '<td class="num">' + format_number(sums[c[0]], c[5]) + '</td>' : '<td></td>');
      idx++;
    }
    return '<tr class="' + cls + '">' + tds.join('') + '</tr>';
  }

  /* ── PENCARIAN SISI-KLIEN ── */
  function applyFilters() { render(); }

  function rowSearchText(r, cols) {
    return cols.map(function (c) {
      const v = pickCI(r, c[0]);
      return (v == null ? '' : String(v));
    }).join(' ').toLowerCase();
  }

  /* ── TOAST ── */
  function showToast(icon, msg) {
    const t = document.getElementById('toast');
    document.getElementById('ti').textContent = icon;
    document.getElementById('tm').textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
  }

  function getKolomFilter() { return ['keterangan']; }

  /* ── SELECT DIVISI (modal Filter Laporan) ──
        Diisi sekali dari laporanaccountinglabarugitahunan_loaddivisi saat page load. Memilih
        item hanya menyetel globalDivisi; laporan baru dimuat saat klik Tampilkan (konsisten
        dgn filter Periode). ── */
  function loadDivisiDropdown() {
    let list = [];
    $.ajax({
      url: "{!! url('laporanaccountinglabarugitahunan_loaddivisi') !!}",
      type: "get", async: false,
      success: function (res) { list = res || []; }
    });

    let html = '';
    list.forEach((item) => {
      const nama = (item.NamaDevisi != null ? String(item.NamaDevisi) : '');
      html += '<option value="' + item.Devisi + '">' + item.Devisi + ' - ' + esc(nama) + '</option>';
    });
    $("#modalDivisi").html(html);

    // default: divisi pertama (tanpa memuat ulang — laporan dimuat saat klik "Tampilkan")
    if (list.length) { setDivisi(list[0].Devisi); }
  }

  function setDivisi(kode) {
    globalDivisi = kode;
    $("#modalDivisi").val(kode);
  }

  /* ── FILTER MODAL ──
        Satu-satunya field di sini adalah Divisi. Ia TIDAK ikut dihitung di badge karena
        tidak punya opsi "Semua" — wajib selalu diisi, jadi bukan "filter yang dinyalakan"
        (aturan sama seperti Divisi di reportaccountinglabarugi). ── */
  function updateFilterBadge() {
    $('#filterBadge').text('0 aktif');
  }

  function resetAllFilters() {
    if ($('#modalDivisi option').length) {
      $('#modalDivisi').prop('selectedIndex', 0);
    }
    updateFilterBadge();
  }

  $('#modalFilter').on('show.bs.modal', function () {
    $('#modalDivisi').val(globalDivisi);
    updateFilterBadge();
  });

  $('#modalFilter').on('change', 'select.rt-native', updateFilterBadge);

  function applyModalFilter() {
    setDivisi($('#modalDivisi').val());
    $('#modalFilter').modal('hide');
  }
</script>
@endsection
