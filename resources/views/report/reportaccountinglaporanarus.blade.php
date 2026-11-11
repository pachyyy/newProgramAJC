@extends('report.masterreport2')

{{-- Table styling lives in public/css/report-table.css (loaded via report/newmaster2.blade.php).
     Laporan Mutasi Kas & Bank: styled .tb-report table with an INTERACTIVE two-level header
     (Sampai Dengan Bulan Ini / Bulan Ini) built manually per docs/new-slider-table-guide.md
     TANPA drag (ReportTable.headHtml() tidak bisa render rowspan/colspan) — tiap kolom tetap
     punya menu roda gigi (sembunyikan/total) lewat buildGroupedThead(); pola sama persis dengan
     reportaccountingaktiva.blade.php. Subtotal per jenis akun (kolom MK) + Grand Total tetap
     dikendalikan gsum_issubtotal/gsum_isgrandtotal, TAPI tombol Customize Table sengaja
     dikomentari di sini sehingga kedua flag itu SELALU aktif (default setDefaultHeader()) —
     tidak ada UI untuk mematikannya di halaman ini. --}}
<style>
  #inputPerkiraanBtn {
    border: 0; background: none; padding: 0; box-shadow: none;
    color: #495057; font-weight: 600;
  }
  #inputPerkiraanBtn:hover, #inputPerkiraanBtn:focus { color: #0d6efd; box-shadow: none; }

  /* tinggi awal area tabel supaya dropdown Divisi tidak terpotong container pendek */
  .tb-report .table-wrap { min-height: 10vh; }

  /* Tidak ada drag-reorder di halaman ini (header dua tingkat/grouped tidak bisa
     menoleransi kolom pindah band) -- timpa cursor:grab bawaan .th-inner supaya
     tidak menyiratkan kolom bisa diseret. Gear (hide/total) tetap aktif. */
  .tb-report .tb thead th.rt-th .th-inner { cursor: default; }
</style>

@section('header2')
<div class="tb-report main">
  <div class="content">

    <!-- TOOLBAR -->
    <div class="toolbar">
      {{-- <div>
        <div class="page-title">Laporan Arus Kas &amp; Bank</div>
      </div> --}}

      <!-- Periode (Bulan + Tahun, seperti Trial Balance) -->
      <div class="period-select-wrap">
        <label>Periode</label>
        <select class="period-select" id="periodBulan" onchange="setPeriodPart()"></select>
        <select class="period-select" id="periodTahun" onchange="setPeriodPart()"></select>
      </div>

      {{-- Search --}}
      <div>
        <input class="search-inp" type="text" id="searchBox2" placeholder="Cari akun..." oninput="applyFilters()" style="width:180px">
      </div>

      <!-- Divisi (dropdown; diisi dari reportaccountinglaporanarus_loadperkiraan) -->
      <div class="filter-wrap">
        <label>Divisi</label>
        <input type="hidden" id="inputPerkiraan" value="-">
        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="inputPerkiraanBtn"
                data-bs-toggle="dropdown" aria-expanded="false"><span id="perkiraanLabel">-</span></button>
        <ul class="dropdown-menu" id="dropdownPerkiraan" aria-labelledby="inputPerkiraanBtn"
            style="max-height:320px; overflow:auto;"></ul>
      </div>

      <!-- Actions: search + customize + tampilkan + export -->
      <div class="action-group">
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

    <!-- TABLE — header dua tingkat (band Sampai Dengan Bulan Ini / Bulan Ini) dibangun oleh
         buildGroupedThead(); baris (placeholder awal sebelum JS jalan) -->
    <div class="table-outer">
      <div class="table-wrap">
        <table class="tb" id="mainTable">
          <thead>
            <tr>
              <th rowspan="2" style="width:90px">ACC</th>
              <th rowspan="2">Keterangan</th>
              <th colspan="3" class="th-group" style="min-width:300px">Sampai Dengan Bulan Ini</th>
              <th colspan="3" class="th-group" style="min-width:300px">Bulan Ini</th>
            </tr>
            <tr>
              <th class="num" style="min-width:110px">Nilai Jurnal</th>
              <th class="num" style="min-width:100px">R/K</th>
              <th class="num" style="min-width:110px">Nilai Akhir</th>
              <th class="num" style="min-width:110px">Nilai Jurnal</th>
              <th class="num" style="min-width:100px">R/K</th>
              <th class="num" style="min-width:110px">Nilai Akhir</th>
            </tr>
          </thead>
          <tbody id="tableBody">
            <tr class="empty-row"><td colspan="8">Pilih Periode &amp; Divisi lalu klik <b>Tampilkan</b>.</td></tr>
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
@endsection


@section('jsreport')
<script type="text/javascript">
  let defaultBulan = new Date().getMonth() + 1;  // 1-12
  let defaultTahun = new Date().getFullYear();
  let currentPeriod = '';

  let g_reportTitle = "REPORT ACCOUNTING LAPORAN MUTASI KAS & BANK";
  let g_date2 = "", g_inputPerkiraan = "";

  let lastRows = [];   // hasil fetch terakhir (dipakai render / search)

  // Mode di-bump ke 29 supaya header TERSIMPAN versi lama (tanpa tabel interaktif/gcart_header
  // per-kolom) tidak dipakai lagi; semua pengguna mulai bersih dari setDefaultHeader().
  g_modeReport = 29;

  const reportUrl = "{{ url('reportaccountinglaporanarus_doReport') }}";

  // Susunan kolom tabel (urutan mengikuti header dua tingkat di markup). Hanya kolom Rp
  // (Awal/RK/Debet/awlsd/rksd/sldsd) yang ditotal. `band` menandai kolom yang ada di bawah band
  // Sampai Dengan Bulan Ini/Bulan Ini di baris pertama thead (null = kolom rowspan-2 biasa, ACC &
  // Keterangan); `short` = teks kolom di baris kedua thead (band-nya sudah menjelaskan konteks).
  // Label PENUH (c.label) dipakai untuk gcart_header / export / Atur Kolom supaya tidak ambigu.
  const COLS = [
    { key: 'Perkiraan',  label: 'ACC',                     type: 'str', dec: 0, total: false, band: null },
    { key: 'Keterangan', label: 'Keterangan',               type: 'str', dec: 0, total: false, band: null },
    { key: 'Awal',  label: 'S/D Bln - Nilai Jurnal', type: 'num', dec: 2, total: true, band: 'sd',  short: 'Nilai Jurnal' },
    { key: 'RK',    label: 'S/D Bln - R/K',          type: 'num', dec: 2, total: true, band: 'sd',  short: 'R/K' },
    { key: 'Debet', label: 'S/D Bln - Nilai Akhir',  type: 'num', dec: 2, total: true, band: 'sd',  short: 'Nilai Akhir' },
    { key: 'awlsd', label: 'Bln Ini - Nilai Jurnal', type: 'num', dec: 2, total: true, band: 'bln', short: 'Nilai Jurnal' },
    { key: 'rksd',  label: 'Bln Ini - R/K',          type: 'num', dec: 2, total: true, band: 'bln', short: 'R/K' },
    { key: 'sldsd', label: 'Bln Ini - Nilai Akhir',  type: 'num', dec: 2, total: true, band: 'bln', short: 'Nilai Akhir' },
  ];

  function bandLabel(band) {
    return band === 'sd' ? 'Sampai Dengan Bulan Ini' : 'Bulan Ini';
  }

  $(document).ready(function () {
    setDefaultHeader();
    doSetHeader(g_modeReport);      // muat gsum flags (toggle Subtotal/Grand Total tersimpan)
    populatePeriodSelectors();
    loadDivisiDropdown();           // isi dropdown Divisi + pilih divisi pertama

    // Header tabel interaktif TANPA drag (lihat komentar di atas file): gear per kolom untuk
    // sembunyikan/total, + bar "Reset kolom"/kolom tersembunyi.
    ReportTable.init({
      table: '#mainTable',
      bar: '#rtBar',
      onChange: function () { if (lastRows.length) { applyFilters(); } else { render(); } }
    });

    // setTimeout(() => { makeTable('REPORT'); }, 100);
  });

  /* ── gcart_header: dipakai modal Customize Table + persistensi gsum, dan sekarang JUGA
        dipakai langsung oleh render() lewat buildGroupedThead(). Kolom numerik ditandai total
        (item[4]=1). Subtotal per jenis akun & Grand Total dikendalikan gsum_issubtotal/
        gsum_isgrandtotal — tombol Customize Table dikomentari di halaman ini, jadi kedua flag
        ini SELALU 1 (default di bawah). ── */
  function setDefaultHeader() {
    gcart_header = COLS.map(c => [c.key, c.label, 1, (c.type === 'num' ? 'float' : c.type), (c.total ? 1 : 0), c.dec]);
    gsum_issubtotal = 1; gsum_isgrandtotal = 1;
  }

  /* ── PERIOD PICKER (Bulan + Tahun, sama seperti Trial Balance) ── */
  const NAMA_BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

  function populatePeriodSelectors() {
    const selB = document.getElementById('periodBulan');
    const selT = document.getElementById('periodTahun');
    if (!selB || !selT) return;

    selB.innerHTML = NAMA_BULAN.map((nama, i) =>
      `<option value="${i + 1}" ${(i + 1) == defaultBulan ? 'selected' : ''}>${nama}</option>`).join('');

    const thisYear = new Date().getFullYear();
    let years = '';
    for (let y = thisYear; y >= thisYear - 6; y--) {
      years += `<option value="${y}" ${y == defaultTahun ? 'selected' : ''}>${y}</option>`;
    }
    selT.innerHTML = years;
  }

  // Hanya set state; laporan dimuat saat klik Tampilkan (konsisten dgn Divisi).
  function setPeriodPart() {
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
    const header = cols.map(c => c[1]);
    const body = (lastRows || []).map(r => cols.map(function (c) {
      const v = pickCI(r, c[0]);
      if (c[3] === 'float' || c[3] === 'int') return currencyNormalizer(v);
      return (v == null ? '' : v);
    }));
    const rows = [header].concat(body);
    ReportExport.fromRows(rows, fmt, { customName: 'MutasiKasBank_' + currentPeriod.replace('/', '-') });
    showToast('📄', 'Data diekspor sebagai ' + fmt);
  }

  /* ── LOAD DATA (sp_LapPenerimaanKeuangan; doReport array biasa) ── */
  function makeTable(_mode) {
    const bulan  = defaultBulan, tahun = defaultTahun;
    const divisi = $('#inputPerkiraan').val() || '-';
    const date2  = tahun + '-' + String(bulan).padStart(2, '0');   // YYYY-MM utk controller

    g_date2 = date2; g_inputPerkiraan = divisi;
    currentPeriod = bulan + '/' + tahun;

    // muat gsum flags (default; tetap 1/1 karena tidak ada UI untuk mematikannya di halaman ini)
    if (typeof doSetHeader === 'function') { doSetHeader(g_modeReport); }

    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

    $.ajax({
      url: reportUrl, type: 'get', data: { date2: date2, inputPerkiraan: divisi },
      success: function (res) {
        lastRows = Array.isArray(res) ? res : ((res && res.res1) ? res.res1 : []);
        $('#searchBox2').val('');
        render();
      },
      error: function () { lastRows = []; render(); document.getElementById('footerLabel').textContent = 'Gagal memuat data laporan.'; }
    });
  }

  /* ── helpers ── */
  function num(v) { if (v === null || v === undefined || v === '') return 0; const n = parseFloat(v); return isNaN(n) ? 0 : n; }
  function str(v) { return (v == null ? '' : String(v)).trim(); }
  // Baca kolom tanpa peduli huruf besar/kecil -- proc mengembalikan casing yang tidak selalu
  // sama persis dengan key di COLS (lihat docs/new-slider-table-guide.md §5, "blank cells").
  function pickCI(r, key) {
    if (r[key] !== undefined) return r[key];
    const lk = String(key).toLowerCase();
    for (const k in r) { if (k.toLowerCase() === lk) return r[k]; }
    return undefined;
  }

  /* ── HEADER DUA TINGKAT (band Sampai Dengan Bulan Ini/Bulan Ini), TANPA drag ──
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

  /* ── RENDER ──
     Baris dikelompokkan berdasarkan NILAI kolom MK (klasifikasi dari SP), dalam urutan
     kemunculannya. Subtotal per grup & Grand Total mengikuti gsum_issubtotal/gsum_isgrandtotal
     (selalu 1 di halaman ini). Kolom terlihat & urutan dari gcart_header (item[2]===1) supaya
     show/hide lewat gear benar-benar berpengaruh; urutan itu sendiri tetap sama dengan COLS
     karena tidak ada drag. ── */
  function render() {
    const cols  = gcart_header.filter(c => c[2] === 1);
    const thead = document.querySelector('#mainTable thead');
    const tbody = document.getElementById('tableBody');
    const showSub   = (gsum_issubtotal === 1);
    const showGrand = (gsum_isgrandtotal === 1);
    const search = ($('#searchBox2').val() || '').trim().toLowerCase();

    thead.innerHTML = buildGroupedThead();
    ReportTable.refresh();   // segarkan #rtBar (biasanya efek samping headHtml(), tapi di sini header dibangun manual)

    const totalCols = cols.filter(c => (c[3] === 'float' || c[3] === 'int') && c[4] === 1);
    const totalKeys = totalCols.map(c => c[0]);

    // kelompokkan per nilai kolom MK, pertahankan urutan kemunculan.
    // Label grup diambil dari kolom KET (bukan MK-nya, bukan "Aset" dst).
    const order = [];
    const buckets = {};
    const labels = {};
    (lastRows || []).forEach(r => {
      const code = str(pickCI(r, 'Perkiraan')), name = str(pickCI(r, 'Keterangan'));
      if (search && code.toLowerCase().indexOf(search) === -1 && name.toLowerCase().indexOf(search) === -1) return;
      const gkey = str(pickCI(r, 'MK'));            // klasifikasi dari kolom MK
      if (!(gkey in buckets)) { buckets[gkey] = []; order.push(gkey); labels[gkey] = str(pickCI(r, 'KET')); }
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
      const label = (labels[gkey] !== '' ? labels[gkey] : (gkey !== '' ? gkey : 'LAINNYA'));

      // group header (label dari kolom KET)
      html += '<tr class="group-row"><td colspan="' + cols.length + '">' + label +
        ' <span style="font-size:11px;font-weight:600;opacity:.7;margin-left:8px">(' + rows.length + ' akun)</span></td></tr>';

      const sub = {}; totalKeys.forEach(k => sub[k] = 0);
      rows.forEach(r => {
        totalKeys.forEach(k => { const v = currencyNormalizer(pickCI(r, k)); sub[k] += v; grand[k] += v; });
        html += '<tr class="data-row">' + cols.map(function (c) {
          const v = pickCI(r, c[0]);
          if (c[3] === 'float' || c[3] === 'int') return '<td class="num">' + format_number(currencyNormalizer(v), c[5]) + '</td>';
          return '<td>' + nullToEmpty(v) + '</td>';
        }).join('') + '</tr>';
        visible++;
      });

      if (showSub) html += totalRow('Subtotal ' + label, sub, cols, totalKeys, 'subtotal-row');
    });

    if (showGrand) html += totalRow('TOTAL KESELURUHAN', grand, cols, totalKeys, 'grand-total');

    tbody.innerHTML = html;
    document.getElementById('footerLabel').textContent = 'Menampilkan ' + visible + ' akun';
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

  function applyFilters() { render(); }

  /* ── DROPDOWN DIVISI (Bootstrap; diisi dari loadperkiraan) ── */
  function loadDivisiDropdown() {
    let list = [];
    $.ajax({
      url: "{!! url('reportaccountinglaporanarus_loadperkiraan') !!}",
      type: "get", async: false,
      success: function (res) { list = Array.isArray(res) ? res : []; }
    });

    let html = '';
    list.forEach((item) => {
      const nama = (item.NamaDevisi != null ? String(item.NamaDevisi) : '').replace(/"/g, '&quot;');
      html += '<li><a class="dropdown-item divisi-item" style="cursor:pointer" ' +
        'data-value="' + item.Devisi + '" data-nama="' + nama + '">' +
        item.Devisi + ' - ' + (item.NamaDevisi != null ? item.NamaDevisi : '') +
        ' <span class="checkmark-red" style="display:none">&#10003;</span></a></li>';
    });
    $("#dropdownPerkiraan").html(html);

    if (list.length) { setDivisi(list[0].Devisi, list[0].NamaDevisi != null ? list[0].NamaDevisi : ''); }
  }

  function setDivisi(kode, nama) {
    $("#inputPerkiraan").val(kode);
    $("#perkiraanLabel").text(kode);
    $("#inputPerkiraanBtn").attr('title', kode + (nama ? ' - ' + nama : ''));
    $('#dropdownPerkiraan .checkmark-red').hide();
    $(`#dropdownPerkiraan .divisi-item[data-value='${kode}'] .checkmark-red`).show();
  }

  $(document).on('click', '#dropdownPerkiraan .divisi-item', function () {
    setDivisi($(this).data('value'), $(this).data('nama'));
  });

  /* ── TOAST ── */
  function showToast(icon, msg) {
    const t = document.getElementById('toast');
    document.getElementById('ti').textContent = icon;
    document.getElementById('tm').textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
  }

  /* ── Filter Data engine (opsional) ── */
  function getKolomFilter() { return ['Perkiraan', 'Keterangan']; }
</script>
@endsection
