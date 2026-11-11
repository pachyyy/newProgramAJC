@extends('report.masterreport2')

{{-- Table styling lives in public/css/report-table.css (loaded via report/newmaster2.blade.php).
     Laporan Pengadaan HIS PO: daftar flat (No Bukti/Tanggal/Nama Supplier/Nama Barang/Qnt PO/
     No. Po/Tgl. LPB/Qnt. Inv/DUEDATE), TIDAK ada grouping -- header dibangun lewat
     ReportTable.headHtml() bawaan (drag-reorder + gear per kolom), sama seperti
     reportaccountingneracapenunjang.blade.php/reportpengadaanosp.blade.php.
     Halaman ini sebelumnya masih pakai toolbar dropdown-icon lama + engine BAWAAN masterreport2
     (doMakeTable()/doShowReport(), render ke #tabel/#showTableReport) -- bukan tabel styled
     .tb-report yang dipakai laporan lain. Diganti total ke pola .tb-report + render() sendiri.
     Mode "Rekap" yang lama SUDAH DIHAPUS -- gcart_header-nya nyaris identik dengan mode "Detail"
     (cuma beda tipe kolom TGLBELI, jelas typo copy-paste) dan tidak ada UI yang pernah bisa
     memicunya (tidak ada tombol reportMode() di toolbar manapun, live atau dead).
     Dua modal entity-picker terpisah (formSelectCustomer/formSelectLokasi, masing-masing modal
     penuh + fungsi sendiri) DIGABUNG jadi SATU modal #formSelect per
     docs/new-cust-supp-modal-guide.md (page-local, jadi aman -- bukan blade @include bersama),
     gaya ungated (baris diklik langsung, tanpa kolom Actions/tombol Select). Keduanya lalu jadi
     field .rt-combo di modal "Filter Laporan" (PICK_FIELDS), persis contoh di
     docs/new-filter-modal-ui-guide.md §4 (yang literally memakai inputCustomer/inputLokasi
     sebagai contohnya). Field "Customer" (#inputCust) sebenarnya SUPPLIER (loadCustomer() query
     `IsSupplier=1`, kolom tabelnya "Nama Supplier") -- label ditulis "Supplier" di sini (cuma
     teks tampilan, bukan wiring) supaya konsisten dgn kolom tabel; id/nama fungsi/endpoint TETAP
     "Customer" (tidak diubah).
     Tidak ada grouping/subtotal (SP dulu dipanggil dgn groupby="" -> baris subtotal lama tidak
     pernah benar-benar muncul) -- render() di sini cuma satu baris Grand Total (opsional, ikut
     toggle gsum_isgrandtotal), tanpa subtotal per grup.
     Filter Data (row-picker lama) & Customize Table dihapus, superseded oleh gear+bar baru --
     pola sama dengan reportpengadaanpr.blade.php/reportpengadaanosp.blade.php.
     Sumber: SP_REPORTHISPO :date1,:date2,:inputCust,:inputLokasi,:iduser,:tipetrans. Data hanya
     dimuat setelah klik "Tampilkan". --}}

<style>
  /* tinggi awal area tabel supaya dropdown tidak terpotong container pendek */
  .tb-report .table-wrap { min-height: 10vh; }
</style>

@section('header2')
<div class="tb-report main">
  <div class="content">

    <!-- TOOLBAR -->
    <div class="toolbar">

      <!-- Periode (date range) -->
      <div class="filter-wrap">
        <label>Periode</label>
        <input type="date" class="filter-inp" id="inputDate1" value="{!! date('Y-m-d') !!}">
        <span class="filter-sep">s/d</span>
        <input type="date" class="filter-inp" id="inputDate2" value="{!! date('Y-m-d') !!}">
      </div>

      {{-- Search --}}
      <div>
        <input class="search-inp" type="text" id="searchBox2" placeholder="Cari data..." oninput="applyFilters()" style="width:180px">
      </div>

      {{-- Supplier & Lokasi pindah ke modal "Filter Laporan" sebagai field .rt-combo (lihat
           docs/new-filter-modal-ui-guide.md §4) -- nilai sebenarnya: hidden input #inputCust /
           #inputLokasi (dibaca makeTable(), ditulis buttonPilih() lewat modal picker #formSelect). --}}

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

    <!-- Bar kolom tersembunyi (diisi oleh report-table.js / ReportTable) -->
    <div id="rtBar"></div>

    <!-- TABLE — header satu tingkat (tanpa band), dibangun oleh ReportTable.headHtml() di
         render() (drag-reorder + gear aktif seperti biasa). -->
    <div class="table-outer">
      <div class="table-wrap">
        <table class="tb" id="mainTable">
          <thead>
            <tr>
              <th style="min-width:130px">No Bukti</th>
              <th style="min-width:90px">Tanggal</th>
              <th style="min-width:160px">Nama Supplier</th>
              <th style="min-width:160px">Nama Barang</th>
              <th class="num" style="min-width:90px">Qty PO</th>
              <th style="min-width:110px">No. LPB</th>
              <th style="min-width:90px">Tgl. LPB</th>
              <th class="num" style="min-width:90px">Qty LPB</th>
              <th style="min-width:100px">Tgl. Kirim</th>
            </tr>
          </thead>
          <tbody id="tableBody">
            <tr class="empty-row"><td colspan="9">Atur filter lalu klik <b>Tampilkan</b> untuk memuat laporan.</td></tr>
          </tbody>
        </table>
      </div>
      <div class="table-footer">
        <span id="footerLabel">Belum ada data dimuat</span>
      </div>
    </div>

    <div class="rt-hint">
      <i class="bi bi-info-circle"></i>
      Seret judul kolom untuk mengurutkan. Klik <i class="bi bi-gear"></i> untuk sembunyikan kolom atau atur total.
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
        {{-- data-dismiss (BS4) = jaga-jaga; BS5 (data-bs-dismiss) yang benar-benar menutup di
             halaman Class A ini -- lihat aturan dua-Bootstrap di new-design-all-report-guide.md §5.1. --}}
        <button type="button" class="btn-close" aria-label="Close" data-dismiss="modal" data-bs-dismiss="modal"
                onclick="$('#modalFilter').modal('hide')"></button>
      </div>

      <div class="modal-body">

        <div class="rt-section">
          <div class="rt-group-label">Filter Data
            <span class="rt-group-hint">&mdash; klik untuk memilih</span>
          </div>
          <div class="rt-grid-2" id="pickFields"></div>

          {{-- Nilai sebenarnya (dibaca makeTable() & ditulis buttonPilih()) --}}
          <input type="hidden" id="inputCust" value="-">
          <input type="hidden" id="inputLokasi" value="-">
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

{{-- Picker gabungan (Supplier + Lokasi) per docs/new-cust-supp-modal-guide.md — SATU modal
     #formSelect dipakai ulang untuk kedua entity via buttonSelect('selectCustomer'/'selectLokasi'),
     menggantikan formSelectCustomer + formSelectLokasi yang dulu terpisah. Page-local (bukan
     @include bersama) jadi aman digabung tanpa dampak ke halaman lain. --}}
<div class="modal fade" id="formSelect" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 1200px">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Select</h5>
        <button type="button" class="btn-close" data-dismiss="modal" data-bs-dismiss="modal"
                onclick="$('#formSelect').modal('hide')" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <table id="tabelSelect" class="table table-bordered table-striped">
          {{-- Baris placeholder WAJIB ada: DataTables 1.10 langsung meng-init tabel saat
               $('#tabelSelect').DataTable() dipanggil, dan tabel tanpa <th> = 0 kolom -> init-nya
               melempar di _fnSortFlatten (aoColumns[0] undefined). Isi asli ditimpa oleh
               pickerHeadHtml() di loadSelectCustomer()/loadSelectLokasi(). --}}
          <thead id="tabelHeader" class="text-center">
            <tr><th scope="col"></th></tr>
          </thead>
          <tbody id="tabel_dataSelect" class="text-left">
            <tr><td></td></tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal"
                onclick="$('#formSelect').modal('hide')">Batal</button>
      </div>
    </div>
  </div>
</div>
{{-- Picker gabungan --}}
@endsection


@section('jsreport')
<script type="text/javascript">
  let g_reportTitle = "";
  let lastRows = [];   // hasil fetch terakhir (dipakai render / export / search)

  // Report mode dipakai engine masterreport2 (doSetHeader) — cukup satu int, halaman ini
  // cuma satu mode (mode "Rekap" yang lama sudah dihapus, tidak pernah bisa dicapai user).
  g_modeReport = 28;

  const reportUrl = "{{ url('laporanhispo_doReport') }}";

  // Susunan kolom tabel (urutan mengikuti thead di markup, dan TETAP kecuali di-drag manual oleh
  // user -- ReportTable.headHtml() dukung drag standar karena tidak ada band di halaman ini).
  // Tidak ada groupby alami (SP dulu dipanggil dgn groupby="" di engine lama) -- cuma Grand Total,
  // tanpa subtotal per grup.
  const COLS = [
    { key: 'NOPO',         label: 'No Bukti',      type: 'str',  dec: 0, total: false },
    { key: 'TGLPO',        label: 'Tanggal',        type: 'date', dec: 0, total: false },
    { key: 'NAMACUSTSUPP', label: 'Nama Supplier', type: 'str',  dec: 0, total: false },
    { key: 'NAMABRG',      label: 'Nama Barang',   type: 'str',  dec: 0, total: false },
    { key: 'QNTPO',        label: 'Qty PO',        type: 'num',  dec: 2, total: true  },
    { key: 'NOBELI',       label: 'No. LPB',        type: 'str',  dec: 0, total: false },
    { key: 'TGLBELI',      label: 'Tgl. LPB',      type: 'date', dec: 0, total: false },
    { key: 'QNTBELI',      label: 'Qty LPB',      type: 'num',  dec: 2, total: true  },
    { key: 'tglkirim',     label: 'Tgl. Kirim',       type: 'date', dec: 0, total: false },
  ];

  $(document).ready(function () {
    doSetHeader(g_modeReport);   // muat gcart_header (default / hasil Reset kolom tersimpan)
    loadPickerDefaults();        // isi #inputCust/#inputLokasi default "-"

    // Header tabel interaktif standar (drag-reorder + gear per kolom + bar "kolom
    // tersembunyi"/"Reset kolom") -- tidak ada band di halaman ini jadi drag aman.
    ReportTable.init({
      table: '#mainTable',
      bar: '#rtBar',
      onChange: function () { if (lastRows.length) { applyFilters(); } else { render(); } }
    });

    // Sengaja TIDAK memuat data saat halaman dibuka — laporan hanya dimuat setelah
    // pengguna klik tombol "Tampilkan".
  });

  function setDefaultHeader() {
    gcart_header = COLS.map(c => [c.key, c.label, 1, (c.type === 'num' ? 'float' : c.type), (c.total ? 1 : 0), c.dec]);
    gsum_issubtotal = 0; gsum_isgrandtotal = 0;
  }

  function loadPickerDefaults() {
    $('#inputCust').val('-');
    $('#inputLokasi').val('-');
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
    // dengan yang tampil di layar setelah kolom disembunyikan lewat gear, dan urutan mengikuti
    // hasil drag (gcart_header).
    const cols = gcart_header.filter(c => c[2] === 1);
    const header = cols.map(c => c[1]);
    const body = (lastRows || []).map(r => cols.map(function (c) {
      const v = pickCI(r, c[0]);
      if (c[3] === 'date') return format_date(v);
      if (c[3] === 'float' || c[3] === 'int') return currencyNormalizer(v);
      return (v == null ? '' : v);
    }));
    const rows = [header].concat(body);
    ReportExport.fromRows(rows, fmt, { customName: 'PengadaanHisPO_' + ($('#inputDate1').val() || '') });
    showToast('📄', 'Data diekspor sebagai ' + fmt);
  }

  /* ── LOAD DATA: SP_REPORTHISPO ── */
  function makeTable(_mode) {
    g_reportTitle = 'REPORT PENGADAAN HIS PO';

    if (typeof doSetHeader === 'function') { doSetHeader(g_modeReport); }

    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

    const data = {
      date1: $('#inputDate1').val(),
      date2: $('#inputDate2').val(),
      inputCust: $('#inputCust').val() || '-',
      inputLokasi: $('#inputLokasi').val() || '-'
    };

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
  function str(v) { return (v == null ? '' : String(v)).trim(); }
  function pickCI(r, key) {
    if (r[key] !== undefined) return r[key];
    const lk = String(key).toLowerCase();
    for (const k in r) { if (k.toLowerCase() === lk) return r[k]; }
    return undefined;
  }

  /* ── RENDER: daftar flat apa adanya (urutan dari SP) + Grand Total opsional pada kolom
     numerik bertotal (Qnt PO/Qnt. Inv). Tidak ada subtotal per grup -- lihat catatan di atas
     file. Kolom terlihat & urutan dari gcart_header (item[2]===1) supaya show/hide DAN drag
     lewat gear benar-benar berpengaruh. ── */
  function render() {
    const cols  = gcart_header.filter(c => c[2] === 1);
    const thead = document.querySelector('#mainTable thead');
    const tbody = document.getElementById('tableBody');
    const search = ($('#searchBox2').val() || '').trim().toLowerCase();

    thead.innerHTML = ReportTable.headHtml(cols);

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
        if (c[3] === 'date') return '<td>' + format_date(v) + '</td>';
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
      if (c[3] === 'date') return format_date(v);
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

  // getKolomFilter() milik ENGINE LAMA (modal "Filter Data" / doShowFormFilterData()), yang
  // TIDAK dipakai lagi di halaman ini (tombolnya sudah dihapus). Stub ini cuma jaga-jaga supaya
  // base script masterreport2 tidak error kalau memanggilnya.
  function getKolomFilter() { return []; }

  /* ══════════════════ PICKER GABUNGAN (#formSelect) ══════════════════
     docs/new-cust-supp-modal-guide.md — SATU modal dipakai ulang untuk Supplier & Lokasi.
     Ungated (page-local): baris langsung diklik, tanpa kolom Actions/tombol Select. ── */

  // Picker gaya baru: TANPA kolom Actions / tombol Select, baris langsung diklik.
  function pickerHeadHtml(cols) {
    return '<tr>' + cols.map(c => '<th>' + c + '</th>').join('') + '</tr>';
  }
  function pickerRowHtml(idPart, kode, cellsHtml) {
    return '<tr class="pick-row" onclick="buttonPilih(\'' + idPart + '\',\'' + String(kode).replace(/'/g, "\\'") + '\')">' +
      cellsHtml + '</tr>';
  }

  // Hanya destroy kalau tabelnya MEMANG sudah ter-init. $('#x').DataTable() pada tabel yang belum
  // ter-init akan MEMBUAT DataTable baru (bukan no-op) -- kalau thead-nya lagi kosong, init itu
  // error dan menyisakan settings setengah jadi yang bikin destroy() berikutnya error
  // "nTableWrapper is null".
  function resetPickerTable() {
    if ($.fn.DataTable.isDataTable('#tabelSelect')) {
      $('#tabelSelect').DataTable().destroy();
    }
  }

  function buttonSelect(idModal) {
    $("#formSelect").addClass('rt-picker-v2');

    if (idModal === 'selectCustomer') {
      $('#exampleModalLabel').text('Select Supplier');
      loadSelectCustomer();
    } else if (idModal === 'selectLokasi') {
      $('#exampleModalLabel').text('Select Lokasi');
      loadSelectLokasi();
    }

    $("#formSelect").modal('toggle');
  }

  function buttonPilih(idPart, kode) {
    $('#' + idPart).val(kode);
    $('#formSelect').modal('hide');
    renderPickFields();
    updateFilterBadge();
  }

  function loadSelectCustomer() {
    let dataRefresh = [];
    $.ajax({
      url: "{!! url('laporanhispo_loadcustomer') !!}",
      type: "get", async: false,
      success: function (res) { dataRefresh = res || []; }
    });

    resetPickerTable();
    $("#tabelHeader").html(pickerHeadHtml(['Kode', 'Nama', 'Kota']));

    let rowTable = '';
    dataRefresh.forEach((item) => {
      rowTable += pickerRowHtml('inputCust', item.KodeCustSupp,
        `<td>${item.KodeCustSupp}</td><td>${item.NamaCustSupp}</td><td>${item.NamaKota}</td>`);
    });
    document.getElementById("tabel_dataSelect").innerHTML = rowTable;
    $("#tabelSelect").DataTable({ "lengthChange": false, "paging": true });
  }

  function loadSelectLokasi() {
    let dataRefresh = [];
    $.ajax({
      url: "{!! url('laporanhispo_loadlokasi') !!}",
      type: "get", async: false,
      success: function (res) { dataRefresh = res || []; }
    });

    resetPickerTable();
    $("#tabelHeader").html(pickerHeadHtml(['Kode Kebun', 'Nama Kebun']));

    let rowTable = '';
    dataRefresh.forEach((item) => {
      // Nilai yang disimpan adalah NAMA kebun (namaKebun), bukan kode -- perilaku dipertahankan
      // persis seperti sebelumnya (buttonPilihLokasi() lama juga menyimpan nama, bukan kode).
      rowTable += pickerRowHtml('inputLokasi', item.namaKebun,
        `<td>${item.KodeKebun}</td><td>${item.namaKebun}</td>`);
    });
    document.getElementById("tabel_dataSelect").innerHTML = rowTable;
    $("#tabelSelect").DataTable({ "lengthChange": false, "paging": true });
  }

  /* ══════════════════ FILTER MODAL (PICK_FIELDS: Supplier + Lokasi) ══════════════════
     docs/new-filter-modal-ui-guide.md §4/§5/§6 -- kedua field cuma commit ke hidden input saat
     dipilih (buttonPilih), TIDAK ada select "pending-until-Terapkan" di halaman ini, jadi tidak
     perlu guard g_reopeningFilter dari §6 (itu cuma perlu kalau ada select lain yang di-resync
     dari global setiap show.bs.modal). ── */
  const PICK_FIELDS = [
    { id: 'inputCust',    label: 'Supplier', modal: 'selectCustomer' },
    { id: 'inputLokasi',  label: 'Lokasi',   modal: 'selectLokasi' },
  ];

  function renderPickFields() {
    let html = '';
    PICK_FIELDS.forEach(function (f) {
      const val = $('#' + f.id).val() || '-';
      const isSet = (val !== '-' && val !== '');
      html += '<div>';
      html += '<label class="rt-field-label">' + f.label + '</label>';
      html += '<div class="rt-combo">';
      html += '<div class="rt-combo-input" onclick="pickFromModal(\'' + f.modal + '\')">';
      if (isSet) {
        html += '<span class="rt-combo-tag">' + esc(val) +
          '<button type="button" onclick="event.stopPropagation(); clearPickField(\'' + f.id +
          '\')">&times;</button></span>';
      } else {
        html += '<span class="rt-combo-placeholder">Pilih ' + f.label.toLowerCase() + '...</span>';
      }
      html += '<span class="rt-combo-chevron">' +
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>' +
        '</span>';
      html += '</div></div></div>';
    });
    $('#pickFields').html(html);
  }

  function clearPickField(id) {
    $('#' + id).val('-');
    renderPickFields();
    updateFilterBadge();
  }

  // HTML-escape teks bebas (nama supplier/lokasi bisa berisi karakter apa saja).
  function esc(v) {
    return String(v == null ? '' : v)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function updateFilterBadge() {
    let count = 0;
    PICK_FIELDS.forEach(function (f) {
      const val = $('#' + f.id).val();
      if (val && val !== '-') { count++; }
    });
    $('#filterBadge').text(count + ' aktif');
  }

  function resetAllFilters() {
    PICK_FIELDS.forEach(function (f) { $('#' + f.id).val('-'); });
    renderPickFields();
    updateFilterBadge();
  }

  $('#modalFilter').on('show.bs.modal', function () {
    renderPickFields();
    updateFilterBadge();
  });

  function applyModalFilter() {
    // PICK_FIELDS sudah commit langsung ke hidden input saat dipilih (buttonPilih) -- tidak ada
    // field lain di modal ini yang perlu disinkronkan, jadi Terapkan cukup menutup modal.
    $('#modalFilter').modal('hide');
  }

  /* Buka picker dari dalam modal Filter: modal Bootstrap tidak bertumpuk rapi, jadi modal Filter
     disembunyikan dulu dan dibuka lagi setelah picker ditutup (lihat new-filter-modal-ui-guide.md
     §6). ── */
  let g_reopenFilter = false;

  function pickFromModal(idModal) {
    g_reopenFilter = true;
    $('#modalFilter').modal('hide');
    buttonSelect(idModal);
  }

  $(document).on('hidden.bs.modal', '#formSelect', function () {
    if (g_reopenFilter) {
      g_reopenFilter = false;
      $('#modalFilter').modal('show');
      renderPickFields();
      updateFilterBadge();
    }
  });
</script>
@endsection
