@extends('report.masterreport2')

{{-- Table styling lives in public/css/report-table.css (loaded via report/newmaster2.blade.php).
     Laporan Aktiva (aktiva tetap): styled .tb-report dengan header DUA TINGKAT — kolom awal
     (No. Aktiva, Keterangan, Tgl Perolehan, Qnt, Susut), lalu dua band: "Nilai Perolehan Aktiva
     Tetap" (4 kolom) & "Akumulasi Aktiva Tetap" (4 kolom), lalu "Nilai Buku". Header dua tingkat
     dibangun manual per docs/new-slider-table-guide.md TANPA drag (ReportTable.headHtml() tidak
     bisa render rowspan/colspan) — tiap kolom tetap punya menu roda gigi (sembunyikan/desimal/
     total) yang delegasi ke doButtonVisibility/doSetDesimal/doButtonTotal seperti biasa, lihat
     buildGroupedThead(). Customize Table TETAP dipakai untuk toggle Subtotal/Grand Total (tidak
     ada UI lain untuk itu). Baris dikelompokkan per GrpPerkiraan (label "GrpPerkiraan -
     NamaPerkiraan") dengan Subtotal per grup + Grand Total, mengikuti toggle Customize Table
     (gsum_issubtotal/gsum_isgrandtotal). Filter: Bulan/Tahun (dropdown) + Divisi (modal Filter
     Laporan). Tidak ada kolom No Bukti/No Nota → tanpa panel voucher. Sumber: sp_LapAktiva
     :bulan,:tahun,:divisi. Data hanya dimuat setelah klik "Tampilkan". --}}
<style>
  /* tinggi awal area tabel supaya dropdown tidak terpotong container pendek */
  .tb-report .table-wrap { min-height: 10vh; }

  /* Tidak ada drag-reorder di halaman ini (header dua tingkat/grouped tidak bisa
     menoleransi kolom pindah band) -- timpa cursor:grab bawaan .th-inner supaya
     tidak menyiratkan kolom bisa diseret. Gear (hide/desimal/total) tetap aktif. */
  .tb-report .tb thead th.rt-th .th-inner { cursor: default; }
</style>

@section('header2')
<div class="tb-report main">
  <div class="content">

    <!-- TOOLBAR -->
    <div class="toolbar">
      {{-- <div>
        <div class="page-title">Laporan Aktiva</div>
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
        {{-- Dibuka lewat plugin jQuery (Bootstrap 4), BUKAN data-bs-toggle (Bootstrap 5) --
             lihat catatan di modal Filter di bawah. --}}
        <button class="btn-load" type="button" onclick="$('#modalFilter').modal('show')">
          <i class="fas fa-filter"></i> Filter
        </button>
        {{-- <button class="btn-load" onclick="doShowFormCustomizeTable()" title="Customize Table"><i class="fas fa-cog"></i> Customize Table</button> --}}
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

    <!-- Bar kolom tersembunyi (diisi oleh report-table.js / ReportTable) -->
    <div id="rtBar"></div>

    <!-- TABLE — header dua tingkat (band Perolehan / Akumulasi) dibangun oleh buildGroupedThead();
         baris di-render dari render() -->
    <div class="table-outer">
      <div class="table-wrap">
        <table class="tb" id="mainTable">
          <thead>
            <tr>
              <th rowspan="2" style="min-width:90px">No. Aktiva</th>
              <th rowspan="2" style="min-width:160px">Keterangan</th>
              <th rowspan="2" style="min-width:100px">Tanggal</th>
              <th rowspan="2" class="num" style="min-width:60px">Qty</th>
              <th rowspan="2" class="num" style="min-width:60px">Susut</th>
              <th colspan="4" class="th-group">Nilai Perolehan Aktiva Tetap</th>
              <th colspan="4" class="th-group">Akumulasi Aktiva Tetap</th>
              <th rowspan="2" class="num" style="min-width:120px">Nilai Buku</th>
            </tr>
            <tr>
              <th class="num" style="min-width:120px">Bulan Lalu</th>
              <th class="num" style="min-width:120px">Penambahan</th>
              <th class="num" style="min-width:120px">Pengurangan</th>
              <th class="num" style="min-width:120px">Bulan Ini</th>
              <th class="num" style="min-width:120px">Bulan Lalu</th>
              <th class="num" style="min-width:120px">Penambahan</th>
              <th class="num" style="min-width:120px">Pengurangan</th>
              <th class="num" style="min-width:120px">Bulan Ini</th>
            </tr>
          </thead>
          <tbody id="tableBody">
            <tr class="empty-row"><td colspan="14">Atur filter lalu klik <b>Tampilkan</b> untuk memuat laporan.</td></tr>
          </tbody>
        </table>
      </div>
      <div class="table-footer">
        <span id="footerLabel">Belum ada data dimuat</span>
      </div>
    </div>

    <div class="rt-hint">
      <i class="bi bi-info-circle"></i>
      Klik <i class="bi bi-gear"></i> pada judul kolom untuk sembunyikan kolom atau atur desimal &amp; total.
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
              {{-- Diisi dari laporanaccountingaktiva_loaddivisi (loadDivisiDropdown()). Selalu
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
<!-- modal filter -->
@endsection


@section('jsreport')
<script type="text/javascript">
  let defaultBulan = new Date().getMonth() + 1;  // 1–12
  let defaultTahun = new Date().getFullYear();

  let g_reportTitle = "";
  let lastRows = [];   // hasil fetch terakhir (dipakai render / search)

  let globalDivisi = "-";  // diisi loadDivisiDropdown() saat page load (selalu wajib diisi)

  // Report mode dipakai engine masterreport2 (doSetHeader) — cukup satu int.
  g_modeReport = 21;

  const reportUrl = "{{ url('laporanaccountingaktiva_doReport') }}";

  // Susunan kolom tabel (urutan mengikuti header dua tingkat di markup, dan TETAP -- tidak ada
  // drag di halaman ini). total=true → ikut Grand Total. Catatan: "Susut" (Persen) TIDAK ditotal
  // (persentase). `band` menandai kolom yang ada di bawah band "Nilai Perolehan"/"Akumulasi" di
  // baris pertama thead (null = kolom rowspan-2 biasa); `short` = teks kolom di baris kedua thead
  // (band-nya sudah menjelaskan konteks, jadi tidak perlu label penuh di sana). Label PENUH
  // (c.label) tetap dipakai untuk gcart_header / export / Atur Kolom supaya tidak ambigu
  // ("Bulan Lalu" saja muncul dua kali kalau tanpa prefiks Perolehan/Akumulasi).
  const COLS = [
    { key: 'perkiraan',  label: 'No. Aktiva',                     type: 'str',  dec: 0, total: false, band: null },
    { key: 'Keterangan', label: 'Keterangan',                     type: 'str',  dec: 0, total: false, band: null },
    { key: 'Tanggal',    label: 'Tgl. Perolehan',                 type: 'date', dec: 0, total: false, band: null },
    { key: 'Quantity',   label: 'Qnt',                            type: 'num',  dec: 0, total: true,  band: null },
    { key: 'Persen',     label: 'Susut',                          type: 'num',  dec: 0, total: false, band: null },
    { key: 'awal',       label: 'Perolehan Bulan Lalu',           type: 'num',  dec: 2, total: true,  band: 'perolehan', short: 'Bulan Lalu' },
    { key: 'MD',         label: 'Perolehan Penambahan Bulan Ini', type: 'num',  dec: 2, total: true,  band: 'perolehan', short: 'Penambahan' },
    { key: 'MK',         label: 'Perolehan Pengurangan Bulan Ini',type: 'num',  dec: 2, total: true,  band: 'perolehan', short: 'Pengurangan' },
    { key: 'akhir',      label: 'Perolehan Bulan Ini',            type: 'num',  dec: 2, total: true,  band: 'perolehan', short: 'Bulan Ini' },
    { key: 'awalSusut',  label: 'Akumulasi Bulan Lalu',           type: 'num',  dec: 2, total: true,  band: 'akumulasi', short: 'Bulan Lalu' },
    { key: 'SK',         label: 'Akumulasi Penambahan Bulan Ini', type: 'num',  dec: 2, total: true,  band: 'akumulasi', short: 'Penambahan' },
    { key: 'SD',         label: 'Akumulasi Pengurangan Bulan Ini',type: 'num',  dec: 2, total: true,  band: 'akumulasi', short: 'Pengurangan' },
    { key: 'AkhirSusut', label: 'Akumulasi Bulan Ini',            type: 'num',  dec: 2, total: true,  band: 'akumulasi', short: 'Bulan Ini' },
    { key: 'NilaiAk',    label: 'Nilai Buku',                     type: 'num',  dec: 0, total: true,  band: null },
  ];

  function bandLabel(band) {
    return band === 'perolehan' ? 'Nilai Perolehan Aktiva Tetap' : 'Akumulasi Aktiva Tetap';
  }

  $(document).ready(function () {
    doSetHeader(g_modeReport);   // muat gsum flags tersimpan (toggle Subtotal/Grand Total)
    populatePeriodSelectors();
    loadDivisiDropdown();   // isi dropdown Divisi (default: divisi pertama)

    // Header tabel interaktif TANPA drag (lihat komentar di atas file): gear per kolom untuk
    // sembunyikan/desimal/total, + bar "Reset kolom"/kolom tersembunyi. Tidak ada "Tampilan"
    // switcher -- halaman ini cuma satu mode.
    ReportTable.init({
      table: '#mainTable',
      bar: '#rtBar',
      onChange: function () { if (lastRows.length) { applyFilters(); } else { render(); } }
    });

    // Sengaja TIDAK memuat data saat halaman dibuka — laporan hanya dimuat setelah
    // pengguna klik tombol "Tampilkan" (atau memilih filter lalu Tampilkan).
  });

  // Header sederhana untuk menjaga engine masterreport2 tetap terinisialisasi
  // (doSetHeader memanggil ini bila belum ada header tersimpan). Tabel styled
  // di-render sendiri oleh render() dari COLS, jadi gcart_header tidak dipakai untuk layout.
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
    // dengan yang tampil di layar setelah kolom disembunyikan lewat gear.
    const cols = gcart_header.filter(c => c[2] === 1);
    const header = ['Grup Aktiva'].concat(cols.map(c => c[1]));
    const body = (lastRows || []).map(r => [groupLabel(r)].concat(cols.map(function (c) {
      const v = pickCI(r, c[0]);
      if (c[3] === 'date') return format_date(v);
      if (c[3] === 'float' || c[3] === 'int') return currencyNormalizer(v);
      return (v == null ? '' : v);
    })));
    const rows = [header].concat(body);
    ReportExport.fromRows(rows, fmt, { customName: 'Aktiva_' + defaultBulan + '-' + defaultTahun });
    showToast('📄', 'Data diekspor sebagai ' + fmt);
  }

  /* ── LOAD DATA (sp_LapAktiva; doReport mengembalikan array biasa) ── */
  function makeTable(_mode) {
    g_reportTitle = 'REPORT AKTIVA';

    let _divisi = globalDivisi || '-';

    // muat gsum flags (default / hasil toggle Customize Table tersimpan)
    if (typeof doSetHeader === 'function') {
      doSetHeader(g_modeReport);
    }

    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

    const data = { inputBulan: defaultBulan, inputTahun: defaultTahun, divisi: _divisi };

    $.ajax({
      url: reportUrl, type: 'get', data: data,
      success: function (res) {
        lastRows = Array.isArray(res) ? res : ((res && res.res1) ? res.res1 : []);
        $('#searchBox2').val('');
        render();
      },
      error: function () { lastRows = []; render(); }
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

  // Label grup: "GrpPerkiraan - NamaPerkiraan" (fallback ke GrpPerkiraan saja bila NamaPerkiraan kosong).
  function groupLabel(r) {
    const kode = str(pickCI(r, 'GrpPerkiraan'));
    const nama = str(pickCI(r, 'NamaPerkiraan'));
    if (kode === '' && nama === '') return '(Tanpa Grup)';
    return nama !== '' ? (kode + ' - ' + nama) : kode;
  }

  /* ── HEADER DUA TINGKAT (band Perolehan/Akumulasi), TANPA drag ──
     ReportTable.headHtml() cuma bisa satu baris flat, jadi header dibangun manual di sini --
     tapi tiap kolom tetap dapat tombol roda gigi (data-rtgear) yang didengarkan oleh listener
     yang sama dipasang ReportTable.init() (event delegation di elemen <thead>, lihat
     docs/new-slider-table-guide.md). Kolom TIDAK bisa diseret (tidak ada .th-inner[draggable]/
     .th-grip di markup) supaya band Perolehan/Akumulasi tidak pernah pecah. Index (idx) yang
     dipakai `data-rtgear`/`data-gidx` adalah posisi di gcart_header, yang urutannya SELALU sama
     dengan COLS karena tidak ada drag yang bisa mengubahnya. ── */
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

  /* ── RENDER: dikelompokkan per GrpPerkiraan (label "GrpPerkiraan - NamaPerkiraan"), Subtotal
     per grup + Grand Total — mengikuti toggle Customize Table (gsum_issubtotal/gsum_isgrandtotal).
     Kolom terlihat & urutan dari gcart_header (item[2]===1) supaya show/hide lewat gear
     benar-benar berpengaruh; urutan itu sendiri tetap sama dengan COLS karena tidak ada drag. ── */
  function render() {
    const cols  = gcart_header.filter(c => c[2] === 1);
    const thead = document.querySelector('#mainTable thead');
    const tbody = document.getElementById('tableBody');
    const search = ($('#searchBox2').val() || '').trim().toLowerCase();

    thead.innerHTML = buildGroupedThead();
    ReportTable.refresh();   // segarkan #rtBar (biasanya efek samping headHtml(), tapi di sini header dibangun manual)

    const totalCols = cols.filter(c => (c[3] === 'float' || c[3] === 'int') && c[4] === 1);
    const totalKeys = totalCols.map(c => c[0]);
    const showSub = (gsum_issubtotal === 1);
    const showGrand = (gsum_isgrandtotal === 1);

    // kelompokkan per GrpPerkiraan, pertahankan urutan kemunculan
    const order = [], buckets = {}, labels = {};
    (lastRows || []).forEach(r => {
      if (search && rowSearchText(r, cols).indexOf(search) === -1) return;
      const gkey = str(pickCI(r, 'GrpPerkiraan'));
      if (!(gkey in buckets)) { buckets[gkey] = []; order.push(gkey); labels[gkey] = groupLabel(r); }
      buckets[gkey].push(r);
    });

    if (!order.length) {
      tbody.innerHTML = '<tr class="empty-row"><td colspan="' + cols.length + '">Tidak ada data ditemukan.</td></tr>';
      document.getElementById('footerLabel').textContent = 'Tidak ada data';
      return;
    }

    let html = '';
    const grand = {}; totalKeys.forEach(k => grand[k] = 0);
    let visible = 0;

    order.forEach(gkey => {
      const rows = buckets[gkey];
      const label = labels[gkey];

      html += '<tr class="group-row"><td colspan="' + cols.length + '">' + label +
        ' <span style="font-size:11px;font-weight:600;opacity:.7;margin-left:8px">(' + rows.length + ' aktiva)</span></td></tr>';

      const sub = {}; totalKeys.forEach(k => sub[k] = 0);
      rows.forEach(r => {
        totalKeys.forEach(k => { const v = currencyNormalizer(pickCI(r, k)); sub[k] += v; grand[k] += v; });
        html += '<tr class="data-row">' + cols.map(function (c) {
          const v = pickCI(r, c[0]);
          if (c[3] === 'date') return '<td>' + format_date(v) + '</td>';
          if (c[3] === 'float' || c[3] === 'int') return '<td class="num">' + format_number(currencyNormalizer(v), c[5]) + '</td>';
          return '<td>' + nullToEmpty(v) + '</td>';
        }).join('') + '</tr>';
        visible++;
      });

      if (showSub) html += totalRow('Subtotal ' + label, sub, cols, totalKeys, 'subtotal-row');
    });

    if (showGrand) html += totalRow('GRAND TOTAL', grand, cols, totalKeys, 'grand-total');

    tbody.innerHTML = html;
    document.getElementById('footerLabel').textContent = 'Menampilkan ' + visible + ' baris';
  }

  // Baris total: nilai di tiap kolom numerik yang ditotal; label membentang seluruh kolom
  // non-total yang BERURUTAN mulai dari kolom non-total pertama (bukan cuma satu sel sempit)
  // supaya label yang panjang ("Subtotal Nilai Perolehan Aktiva Tetap...") tidak wrap.
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
    let s = str(pickCI(r, 'GrpPerkiraan')) + ' ' + str(pickCI(r, 'NamaPerkiraan')); // ikutkan label grup
    s += ' ' + cols.map(function (c) {
      const v = pickCI(r, c[0]);
      return (c[3] === 'date') ? format_date(v) : (v == null ? '' : String(v));
    }).join(' ');
    return s.toLowerCase();
  }

  /* ── TOAST ── */
  function showToast(icon, msg) {
    const t = document.getElementById('toast');
    document.getElementById('ti').textContent = icon;
    document.getElementById('tm').textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
  }

  function getKolomFilter() { return ['perkiraan', 'Keterangan']; }

  /* ── SELECT DIVISI (modal Filter Laporan) ──
        Diisi sekali dari laporanaccountingaktiva_loaddivisi saat page load. Memilih item
        hanya menyetel globalDivisi; laporan baru dimuat saat klik Tampilkan (konsisten
        dgn filter Periode). ── */
  function loadDivisiDropdown() {
    let list = [];
    $.ajax({
      url: "{!! url('laporanaccountingaktiva_loaddivisi') !!}",
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

  // HTML-escape teks bebas (nama divisi bisa diisi user).
  function esc(v) {
    return String(v == null ? '' : v)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  /* ── FILTER MODAL ──
        Satu-satunya field di sini adalah Divisi. Ia TIDAK ikut dihitung di badge karena
        tidak punya opsi "Semua" — wajib selalu diisi, jadi bukan "filter yang dinyalakan"
        (aturan sama seperti Perkiraan di reportaccountingkasharian). ── */
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
