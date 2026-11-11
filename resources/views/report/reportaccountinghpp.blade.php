@extends('report.masterreport4')

{{-- Table styling lives in public/css/report-table.css (di-link di report/newmaster2x.blade.php).
     Laporan HPP: styled .tb-report dengan header DUA TINGKAT — Keterangan + 3 band (Bulan Lalu /
     Bulan Ini / s/d Bulan Ini), masing-masing Rp. + %. Header dua tingkat dibangun manual per
     docs/new-slider-table-guide.md TANPA drag (ReportTable.headHtml() tidak bisa render
     rowspan/colspan) — tiap kolom tetap punya menu roda gigi (sembunyikan/total), lihat
     buildGroupedThead(); pola sama persis dengan reportaccountinglabarugi.blade.php.
     Baris di-render apa adanya (urutan dari SP) + Grand Total pada kolom Rp saja. Filter: Bulan/
     Tahun tetap di toolbar; Divisi dipindah ke modal "Filter Laporan" (satu-satunya field di sana,
     wajib diisi, tidak dihitung di badge). Tidak ada kolom No Bukti → tanpa panel voucher.
     Alur: triggerSp (ambil totalA/B/C dari dbLRHPP) → sp_ReportLabaRugi (baris + % memakai total itu).
     Data hanya dimuat setelah klik "Tampilkan".
     Di atas tabel: KPI strip (Pendapatan/HPP/Laba Kotor/Laba Bersih dari baris Persen A-D) +
     2 bar chart (Pendapatan vs Beban per periode; Komposisi Beban per akun Kelompok=4) dibangun
     sisi-klien dari lastRows (kolom Persen/Kelompok dari SP, sebelumnya tidak dipakai).

     Catatan halaman ini vs Laba Rugi: masterreport4 TIDAK memuat public/js/report-table.js
     (masterreport2 memuatnya), jadi script-nya dimuat di halaman ini — sama seperti
     reportaccountingneracalajur.blade.php yang juga memakai masterreport4. --}}

<!-- Chart.js v4 (di-bundle lokal: public/plugins/chart.js/chart.umd.min.js) -->
<script src="{!! URL::asset('plugins/chart.js/chart.umd.min.js') !!}?v={{ @filemtime(base_path('public/plugins/chart.js/chart.umd.min.js')) ?: '1' }}"></script>

<style>
  /* tinggi awal area tabel supaya dropdown tidak terpotong container pendek */
  .tb-report .table-wrap { min-height: 10vh; }

  /* Tidak ada drag-reorder di halaman ini (header dua tingkat/grouped tidak bisa
     menoleransi kolom pindah band) -- timpa cursor:grab bawaan .th-inner supaya
     tidak menyiratkan kolom bisa diseret. Gear (hide/total) tetap aktif. */
  .tb-report .tb thead th.rt-th .th-inner { cursor: default; }

  /* ── KPI strip ── */
  .tb-report .kpi-strip {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px;
  }
  @media (max-width: 900px) { .tb-report .kpi-strip { grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 560px) { .tb-report .kpi-strip { grid-template-columns: 1fr; } }
  .tb-report .kpi-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 18px;
    box-shadow: 0 1px 4px rgba(0,0,0,.06); display: flex; align-items: flex-start; gap: 12px;
  }
  .tb-report .kpi-ic {
    width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center;
    justify-content: center; flex-shrink: 0; font-size: 18px;
  }
  .tb-report .kpi-label { font-size: 12px; color: #64748b; margin-bottom: 4px; }
  .tb-report .kpi-val { font-size: 19px; font-weight: 700; color: #1e293b; }

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
      {{-- <div>
        <div class="page-title">Laporan HPP</div>
      </div> --}}

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
        {{-- Dibuka lewat plugin jQuery ($.fn.modal), BUKAN data-bs-toggle —
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

    <!-- KPI STRIP (Pendapatan/HPP/Laba Kotor/Laba Bersih; dibangun dari lastRows) -->
    <div class="kpi-strip" id="kpiStrip"></div>

    <!-- CHARTS (dibangun sisi-klien dari lastRows) -->
    <div class="chart-grid">
      <div class="chart-box">
        <h3>Pendapatan vs Beban</h3>
        <div class="chart-holder"><canvas id="lrChart1"></canvas></div>
      </div>
      <div class="chart-box">
        <h3>Komposisi Beban</h3>
        <div class="chart-holder"><canvas id="lrChart2"></canvas></div>
      </div>
    </div>

    <!-- Bar kolom tersembunyi (diisi oleh report-table.js / ReportTable) -->
    <div id="rtBar"></div>

    <!-- TABLE — header dua tingkat (band per periode) dibangun oleh buildGroupedThead() -->
    <div class="table-outer">
      <div class="table-wrap">
        <table class="tb" id="mainTable">
          <thead>
            <tr>
              <th rowspan="2" style="min-width:260px">Keterangan</th>
              <th colspan="2" class="th-group">Bulan Lalu</th>
              <th colspan="2" class="th-group">Bulan Ini</th>
              <th colspan="2" class="th-group">s/d Bulan Ini</th>
            </tr>
            <tr>
              <th class="num" style="min-width:130px">Rp.</th>
              <th class="num" style="min-width:70px">%</th>
              <th class="num" style="min-width:130px">Rp.</th>
              <th class="num" style="min-width:70px">%</th>
              <th class="num" style="min-width:130px">Rp.</th>
              <th class="num" style="min-width:70px">%</th>
            </tr>
          </thead>
          <tbody id="tableBody">
            <tr class="empty-row"><td colspan="7">Atur filter lalu klik <b>Tampilkan</b> untuk memuat laporan.</td></tr>
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
        {{-- Dua atribut dismiss sekaligus + onclick: halaman ini memuat Bootstrap 4 (newmaster2x)
             DAN Bootstrap 5 (masterreport4), jadi yang memiliki $.fn.modal tergantung urutan
             eksekusi. Pasang keduanya supaya tombol tutup selalu benar-benar menutup — lihat
             new-design-all-report-guide.md §5.1 (dua atribut dismiss bersamaan aman). --}}
        <button type="button" class="btn-close" aria-label="Close" data-dismiss="modal" data-bs-dismiss="modal"
                onclick="$('#modalFilter').modal('hide')"></button>
      </div>

      <div class="modal-body">

        <div class="rt-section">
          <div class="rt-group-label">Pengaturan Laporan</div>
          <div class="rt-grid-1">
            <div>
              <label class="rt-field-label" for="modalDivisi">Divisi</label>
              {{-- Diisi dari laporanaccountinghpp_loaddivisi (loadDivisiDropdown()). Selalu
                   punya nilai (tidak ada opsi "Semua") -- pilihan wajib, bukan filter yang bisa
                   dimatikan, jadi TIDAK dihitung di badge (lihat updateFilterBadge()). --}}
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

{{-- masterreport4 tidak memuat report-table.js (beda dengan masterreport2), jadi dimuat di sini --
     sama seperti reportaccountingneracalajur.blade.php. Menyediakan window.ReportTable,
     loadingHtml(), dsb. --}}
<script src="{!! URL::asset('js/report-table.js') !!}?v={{ @filemtime(base_path('public/js/report-table.js')) ?: '1' }}"></script>

<script type="text/javascript">
  let defaultBulan = new Date().getMonth() + 1;  // 1–12
  let defaultTahun = new Date().getFullYear();

  let g_reportTitle = "";
  let lastRows = [];   // hasil fetch terakhir (dipakai render / search)

  let globalDivisi = "-";  // diisi loadDivisiDropdown() saat page load (selalu wajib diisi)

  // Base total untuk kolom % (diisi triggerSp sebelum doReport).
  let tempTotalA = 0, tempTotalB = 0, tempTotalC = 0;

  // Report mode dipakai engine masterreport4 (doSetHeader) — cukup satu int. Nomor SENGAJA
  // berbeda dari 0 yang dipakai versi lama halaman ini: layout kolom tersimpan di
  // DBSIMPANHEADER dikunci per (href, mode), dan baris mode 0 milik pengguna lama masih
  // berisi header 8-elemen bergaya lama (ada entri 'group') yang tidak cocok dengan COLS di
  // bawah. Dengan nomor baru, setDefaultHeader() di sini yang dipakai.
  g_modeReport = 25;

  const reportUrl  = "{{ url('laporanaccountinghpp_doReport') }}";
  const triggerUrl = "{{ url('laporanaccountinghpp_triggerSp') }}";

  // Susunan kolom tabel (urutan mengikuti header dua tingkat di markup, dan TETAP -- tidak ada
  // drag di halaman ini). Hanya kolom Rp (TotalA/TotalB/TotalC) yang ditotal; kolom % (P1/P2/P3)
  // tidak ditotal. `band` menandai kolom yang ada di bawah band Bulan Lalu/Bulan Ini/s.d Bulan Ini
  // di baris pertama thead (null = kolom rowspan-2 biasa, cuma Keterangan); `short` = teks kolom
  // di baris kedua thead (band-nya sudah menjelaskan konteks). Label PENUH (c.label) dipakai untuk
  // gcart_header / export / Atur Kolom supaya tidak ambigu ("Rp."/"%" saja muncul tiga kali kalau
  // tanpa prefiks band).
  const COLS = [
    { key: 'keterangan', label: 'Keterangan',       type: 'str', dec: 0, total: false, band: null },
    { key: 'TotalA',     label: 'Bulan Lalu Rp.',    type: 'num', dec: 2, total: true,  band: 'a', short: 'Rp.' },
    { key: 'P1',         label: 'Bulan Lalu %',      type: 'num', dec: 2, total: false, band: 'a', short: '%'  },
    { key: 'TotalB',     label: 'Bulan Ini Rp.',     type: 'num', dec: 2, total: true,  band: 'b', short: 'Rp.' },
    { key: 'P2',         label: 'Bulan Ini %',       type: 'num', dec: 2, total: false, band: 'b', short: '%'  },
    { key: 'TotalC',     label: 's/d Bulan Ini Rp.', type: 'num', dec: 2, total: true,  band: 'c', short: 'Rp.' },
    { key: 'P3',         label: 's/d Bulan Ini %',   type: 'num', dec: 2, total: false, band: 'c', short: '%'  },
  ];

  function bandLabel(band) {
    return band === 'a' ? 'Bulan Lalu' : band === 'b' ? 'Bulan Ini' : 's/d Bulan Ini';
  }

  $(document).ready(function () {
    doSetHeader(g_modeReport);   // muat gcart_header (default / hasil Reset kolom tersimpan)

    // Tabel styled .tb-report adalah satu-satunya tabel di laporan ini. Kosongkan markup tabel
    // bawaan engine masterreport4 dan biarkan tersembunyi (doMakeTable tidak pernah dipanggil
    // di sini). Elemennya tetap ada supaya target scroll doGodown()/Ctrl+Up masih resolve.
    // Template masterreport4 sendiri tidak disentuh, jadi laporan lain tidak terpengaruh.
    $("#showTableReport").empty().hide();

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

    // KPI + chart dipaint kosong (Rp 0 / chart kosong) sebelum data pertama dimuat.
    buildKpis([]);
    buildCharts([]);

    // Sengaja TIDAK memuat data saat halaman dibuka — laporan hanya dimuat setelah
    // pengguna klik tombol "Tampilkan".
  });

  // Header sederhana untuk menjaga engine masterreport4 tetap terinisialisasi
  // (doSetHeader memanggil ini bila belum ada header tersimpan). Tabel styled
  // di-render sendiri oleh render() dari gcart_header (dibangun dari COLS di sini).
  function setDefaultHeader() {
    gcart_header = COLS.map(c => [c.key, c.label, 1, (c.type === 'num' ? 'float' : c.type), (c.total ? 1 : 0), c.dec]);
    gsum_issubtotal = 1; gsum_isgrandtotal = 1;
  }

  /* ── PERIODE (Bulan / Tahun) ── */
  const NAMA_BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
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
    // dengan prefiks band (mis. "Bulan Lalu Rp.") jadi tidak ambigu tanpa perlu traversal khusus.
    const cols = gcart_header.filter(c => c[2] === 1);
    const header = cols.map(c => c[1]);
    const body = (lastRows || []).map(r => cols.map(function (c) {
      const v = pickCI(r, c[0]);
      if (c[3] === 'float' || c[3] === 'int') return currencyNormalizer(v);
      return (v == null ? '' : v);
    }));
    const rows = [header].concat(body);
    ReportExport.fromRows(rows, fmt, { customName: 'HPP_' + defaultBulan + '-' + defaultTahun });
    showToast('📄', 'Data diekspor sebagai ' + fmt);
  }

  /* ── LOAD DATA: triggerSp (base total) → sp_ReportLabaRugi ── */
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
    g_reportTitle = 'REPORT HPP';
    let _divisi = globalDivisi || '-';

    // muat gcart_header (default / hasil Reset kolom tersimpan)
    if (typeof doSetHeader === 'function') { doSetHeader(g_modeReport); }

    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

    // 1) ambil base total untuk kolom %  2) ambil baris HPP
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
        buildKpis(lastRows);
        buildCharts(lastRows);
      },
      error: function () {
        lastRows = [];
        render();
        buildKpis(lastRows);
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

  /* ── KPI + CHARTS (dibangun sisi-klien dari lastRows; kolom Persen/Kelompok dari SP) ── */
  const CHART_PALETTE = ['#4F46E5','#7C3AED','#DB2777','#2563eb','#16a34a','#ca8a04','#ea580c','#0891b2','#e11d48','#65a30d'];
  // [label, Persen, warna latar ikon, class <i> untuk ikon Bootstrap]
  const KPI_DEFS = [
    ['Pendapatan',  'A', '#ede9fe', 'kpi-icon-pendapatan bi bi-currency-dollar'],
    ['HPP',         'B', '#fee2e2', 'kpi-icon-hpp bi bi-briefcase-fill'],
    ['Laba Kotor',  'C', '#dcfce7', 'kpi-icon-labakotor bi bi-graph-up'],
    ['Laba Bersih', 'D', '#dbeafe', 'kpi-icon-lababersih bi bi-bar-chart-fill'],
  ];
  let _charts = {};

  // Nilai negatif ditampilkan sebagai positif di KPI & chart (tabel tetap apa adanya).
  function absNum(v) { const n = currencyNormalizer(v); return n < 0 ? n * -1 : n; }

  function fmtShort(v) {
    v = Math.round(num(v)); const a = Math.abs(v);
    if (a >= 1e9) return (v / 1e9).toFixed(1).replace(/\.0$/, '') + ' M';
    if (a >= 1e6) return (v / 1e6).toFixed(1).replace(/\.0$/, '') + ' jt';
    if (a >= 1e3) return (v / 1e3).toFixed(0) + ' rb';
    return String(v);
  }
  function _destroyChart(id) { if (_charts[id]) { _charts[id].destroy(); delete _charts[id]; } }

  /* ── KPI: TotalB dari baris Persen A/B/C/D ── */
  function buildKpis(rows) {
    const byPersen = {};
    (rows || []).forEach(r => {
      const p = str(pickCI(r, 'Persen')).toUpperCase();
      if (p) byPersen[p] = r;
    });

    let html = '';
    KPI_DEFS.forEach(function (d) {
      const label = d[0], persen = d[1], tint = d[2], iconCls = d[3];
      const row = byPersen[persen];
      const val = row ? absNum(pickCI(row, 'TotalB')) : 0;
      html += '<div class="kpi-card">' +
        '<div class="kpi-ic" style="background:' + tint + '"><i class="' + iconCls + '"></i></div>' +
        '<div><div class="kpi-label">' + label + '</div>' +
        '<div class="kpi-val">Rp ' + fmtShort(val) + '</div></div>' +
        '</div>';
    });
    document.getElementById('kpiStrip').innerHTML = html;
  }

  /* ── Chart 1 (kiri): Pendapatan (Kelompok 3) vs Beban (Kelompok 4), dijumlah per periode.
     Chart 2 (kanan): Komposisi Beban — satu bar per baris Kelompok 4 (TotalB, tanpa dijumlah). ── */
  function buildCharts(rows) {
    if (typeof Chart === 'undefined') return;   // gagal muat Chart.js → lewati (tabel tetap jalan)
    try {
      Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
      Chart.defaults.font.size = 12;
      Chart.defaults.color = '#64748b';

      // Dijumlah dulu apa adanya, baru tandanya dibuang — supaya total bersih
      // (mis. beban yang saling menghapus) tidak melar akibat abs per baris.
      const sum = (kelompok, key) => Math.abs((rows || [])
        .filter(r => str(pickCI(r, 'Kelompok')) === kelompok)
        .reduce((acc, r) => acc + currencyNormalizer(pickCI(r, key)), 0));

      const pendapatan = [sum('3', 'TotalA'), sum('3', 'TotalB'), sum('3', 'TotalC')];
      const beban      = [sum('4', 'TotalA'), sum('4', 'TotalB'), sum('4', 'TotalC')];

      _destroyChart('lr1');
      _charts.lr1 = new Chart(document.getElementById('lrChart1'), {
        type: 'bar',
        data: {
          labels: ['Bulan Lalu', 'Bulan Ini', 's/d Bulan Ini'],
          datasets: [
            { label: 'Pendapatan', data: pendapatan, backgroundColor: '#4F46E5', borderRadius: 6 },
            { label: 'Beban',      data: beban,      backgroundColor: '#DB2777', borderRadius: 6 },
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

      // Baris Keterangan == "HPP" (persis, case-sensitive) dikeluarkan dari chart —
      // "HPP Barang Terjual" dkk tetap ikut karena bukan match persis.
      const bebanRows = (rows || []).filter(r =>
        str(pickCI(r, 'Kelompok')) === '4' &&
        currencyNormalizer(pickCI(r, 'TotalB')) !== 0 &&
        str(pickCI(r, 'Keterangan')) !== 'HPP');

      _destroyChart('lr2');
      _charts.lr2 = new Chart(document.getElementById('lrChart2'), {
        type: 'bar',
        data: {
          labels: bebanRows.map(r => str(pickCI(r, 'Keterangan'))),
          datasets: [{
            label: 'Beban',
            data: bebanRows.map(r => absNum(pickCI(r, 'TotalB'))),
            backgroundColor: bebanRows.map((r, i) => CHART_PALETTE[i % CHART_PALETTE.length]),
            borderRadius: 6
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: { callbacks: { title: (items) => items[0].label, label: (c) => ' ' + fmtShort(c.parsed.y) } }
          },
          scales: {
            x: { ticks: { display: false } },
            y: { ticks: { callback: (v) => fmtShort(v) } }
          }
        }
      });
    } catch (e) { console.error('buildCharts', e); }
  }

  /* ── HEADER DUA TINGKAT (band Bulan Lalu/Bulan Ini/s.d Bulan Ini), TANPA drag ──
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

  /* ── RENDER: baris HPP apa adanya (urutan dari SP) + Grand Total pada kolom Rp. Kolom
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

    if (gsum_isgrandtotal === 1) html += totalRow('GRAND TOTAL', totals, cols, totalKeys, 'grand-total');

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
        Diisi sekali dari laporanaccountinghpp_loaddivisi saat page load. Memilih item
        hanya menyetel globalDivisi; laporan baru dimuat saat klik Tampilkan (konsisten
        dgn filter Periode). ── */
  function loadDivisiDropdown() {
    let list = [];
    $.ajax({
      url: "{!! url('laporanaccountinghpp_loaddivisi') !!}",
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
