@extends('report.masterreportGudang')
{{-- @include('report.modalbrowsemaster') dihapus: sudah tidak dipakai, halaman ini punya
     picker sendiri (.modal-picker / openPickMaster()) yang menggantikan popup #formBrowseMaster
     bawaan -- lihat komentar di dalam <script> jsreport. --}}

{{-- @section('reportname')
      <h3>Report Fast, Slow, Dead Moving</h3>
@endsection --}}


@section('header2')
<style>
  #tabel thead th {
    font-size: 13px !important;
    font-weight: 700 !important;
    padding: 12px 14px !important;
    white-space: nowrap;
  }
  #tabel tbody td {
    font-size: 13.5px !important;
    padding: 11px 14px !important;
    line-height: 1.5 !important;
  }
  #tabel tbody tr td {
    border-bottom: 1px solid #EEF1F8 !important;
  }

  /* Popup "Pilih Data" (Gudang) dari dalam modal Filter. Dibuat manual */
  .modal-picker-backdrop {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0, 0, 0, .5);
    z-index: 1071;
  }
  .modal-picker-backdrop.show { display: block; }
  .modal-picker {
    display: none;
    position: fixed; inset: 0;
    z-index: 1072;
    overflow-x: hidden; overflow-y: auto;
    outline: 0;
  }
  .modal-picker.show { display: block; }
  .modal-picker .modal-dialog {
    margin: 1.75rem auto;
  }
</style>

<div class="tb-report main" style="font-family: 'Segoe UI', sans-serif;">
  <div class="content" style="padding: 20px 24px 0;">

    <div hidden>
      <button type="button" id="buttonMode0" onclick="doReportMode(0)"></button>
      <button type="button" id="buttonMode1" onclick="doReportMode(1)"></button>

      {{-- Jenis laporan sekarang dipilih lewat switcher "Tampilan" di #rtBar
           (ReportTable.init({ views: {...} }), lihat docs/new-slider-table-guide.md #5).
           Select ini disimpan tersembunyi supaya tetap ada satu elemen yang menyimpan
           nilainya -- setTipe() menjaganya sinkron (aturan 4 di guide tsb). --}}
      <select id="inputTipe">
        <option value="0">Fast Moving</option>
        <option value="1">Slow Moving</option>
        <option value="2">Dead Moving</option>
      </select>
    </div>

    <!-- TOOLBAR -->
    <div class="toolbar">

      <!-- Periode -->
      <div class="filter-wrap">
        <label>Periode</label>
        <input type="date" class="filter-inp" id="inputDate1" value="{!! date('Y-m-d') !!}">
      </div>

      {{-- Search --}}
      <div>
        <input class="search-inp" type="text" id="searchBox2" placeholder="Cari data..." oninput="applyFilters()" style="width:180px">
      </div>

      <!-- filter + filter data + tampilkan + export -->
      <div class="action-group">
        <button
          class="btn-load"
          type="button"
          onclick="$('#modalFilter').modal('show')">
          <i class="fas fa-filter"></i> Filter
        </button>
        <button class="btn-load" onclick="doShowFormFilterData()" title="Filter Data"><i class="fas fa-magnifying-glass"></i> Filter Data</button>
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

    <!-- Bar kolom tersembunyi + Reset kolom + switcher "Tampilan" (Fast/Slow/Dead
         Moving), semuanya diisi report-table.js / ReportTable. -->
    <div id="rtBar"></div>

    <div class="table-outer">
      <div class="table-wrap" id="showTableReport">
        <table class="tb" id="tabel">
          <thead id="tabel_header">
            <tr>
              <th>KD BARANG</th>
              <th>NAMA BARANG</th>
              <th>KATEGORI</th>
              <th>P / 3 BULAN</th>
              <th>SAT</th>
              <th>STOCK</th>
              <th>HARGA</th>
            </tr>
          </thead>
          <tbody id="tabel_data">
            <tr class="empty-row">
              <td colspan="100%">Atur filter lalu klik <b>Tampilkan</b> untuk memuat laporan.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer Tabel (Belum ada data dimuat) -->
      <div class="table-footer">
        <span id="footerLabel">Belum ada data dimuat</span>
      </div>

    </div>

    <div class="rt-hint">
      <i class="bi bi-info-circle"></i>
      Seret judul kolom untuk mengurutkan. Klik <i class="bi bi-gear"></i> pada judul kolom untuk
      sembunyikan kolom atau atur total.
    </div>

  </div>
</div>

<!-- modal filter -->
<div class="modal fade rt-filter" id="modalFilter">
  <div class="modal-dialog modal-md">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-filter"></i>
          Filter Laporan
          <span class="rt-active-badge" id="filterBadge">0 aktif</span>
        </h5>

        <button
          type="button"
          class="btn-close"
          aria-label="Close"
          data-bs-dismiss="modal">
        </button>
      </div>

      <div class="modal-body">

        <div class="rt-section">
          <div class="rt-group-label">Filter Data
            <span class="rt-group-hint">&mdash; klik untuk memilih</span>
          </div>

          {{-- Combo diisi renderPickFields() dari PICK_FIELDS -- lihat
               docs/new-filter-modal-ui-guide.md #4. Combo hanya tampilan di atas input
               hidden di bawah; yang menyimpan nilai asli tetap input hidden-nya, jadi
               makeTable() dan pickMasterSelect() tidak perlu tahu combo ini ada. --}}
          <div class="rt-grid-2" id="pickFields"></div>

          <input type="hidden" id="inputGudang" value="-">
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="rt-reset-link" onclick="resetAllFilters()">Reset semua</button>
        <div class="rt-footer-buttons">
          <button type="button" class="rt-btn rt-btn-ghost" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="rt-btn rt-btn-primary" data-bs-dismiss="modal">Terapkan</button>
        </div>
      </div>

    </div>
  </div>
</div>
<!-- modal filter -->

<!-- modal pilih data master (Gudang) -->
{{-- Picker gaya baru (docs/new-cust-supp-modal-guide.md): tanpa kolom Actions / tombol
     Select, seluruh baris bisa diklik. Skin-nya class .rt-picker-v2 -- class generik,
     bukan id-scoped (lihat catatan di public/css/report-table.css sekitar baris 1552),
     jadi id #modalPickMaster boleh tetap dipakai, sama seperti #formFilterData di
     masterreportGudang. Modal ini milik halaman ini sendiri, bukan blade shared, jadi
     tidak perlu digating window.g_pickerV2. --}}
<div class="modal-picker-backdrop" id="modalPickMasterBackdrop" onclick="closePickMaster()"></div>
<div class="modal-picker rt-picker-v2" id="modalPickMaster" tabindex="-1" role="dialog" aria-labelledby="modalPickMasterLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 900px">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalPickMasterLabel">Pilih Data</h5>
        <button type="button" class="btn-close" aria-label="Close" onclick="closePickMaster()"></button>
      </div>
      <div class="modal-body">
        <table id="tabelPickMaster" class="table table-bordered table-striped">
          <thead id="tabelPickMaster_header" class="text-center"></thead>
          <tbody id="tabelPickMaster_data" class="text-left"></tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closePickMaster()">Batal</button>
      </div>
    </div>
  </div>
</div>
<!-- modal pilih data master -->

@endsection


@section('jsreport')
<script type="text/javascript">
  g_modeReport = 0;

  const reportGroupBy = "KodeBrg";

  // Jenis laporan (parameter ke-2 SP_REPORTMOVING). Bukan filter yang bisa dimatikan --
  // selalu ada satu yang terpilih -- jadi TIDAK dihitung di badge modal Filter, sesuai
  // aturan di docs/new-filter-modal-ui-guide.md #5. Dipakai sebagai switcher "Tampilan"
  // di #rtBar; kolomnya sama untuk ketiga jenis, yang berbeda cuma baris dari SP-nya.
  const TIPE_OPTIONS = [
    { value: '0', label: 'Fast Moving', desc: 'Perputaran cepat' },
    { value: '1', label: 'Slow Moving', desc: 'Perputaran lambat' },
    { value: '2', label: 'Dead Moving', desc: 'Tidak bergerak' }
  ];

  let globalTipe = '0';

  function setTipe(v) {
    globalTipe = String(v);
    if ($('#inputTipe').length) { $('#inputTipe').val(globalTipe); }
  }

  function tipeLabel() {
    const _o = TIPE_OPTIONS.find(function (x) { return x.value === globalTipe; });
    return _o ? _o.label : '';
  }

  // Judul laporan ikut jenis yang dipilih, sama seperti versi lama
  // ("REPORT " + teks option yang terpilih, huruf besar).
  function reportTitle() {
    return "REPORT " + tipeLabel().toUpperCase();
  }

  // Baris yang sedang ditampilkan saat pencarian aktif; null = tampil semua (gcart_res).
  let gRowsShown = null;

  function rowsShown() { return gRowsShown || gcart_res || []; }

  if (typeof loadingHtml !== 'function') {
    window.loadingHtml = function (msg) {
      return '<span style="display:inline-flex;align-items:center;gap:6px;color:#5A6A85;">' +
             '<i class="fas fa-spinner fa-spin"></i> ' + (msg || 'Memuat...') + '</span>';
    };
  }

  // Daftar field picker di modal Filter. Combo (.rt-combo) cuma tampilan; nilai aslinya
  // disimpan di input hidden ber-id sama -- lihat docs/new-filter-modal-ui-guide.md #4.
  const PICK_FIELDS = [
    { id: 'inputGudang', label: 'Gudang', url: '{!! $gudang !!}', title: 'Pilih Gudang' }
  ];

  function renderPickFields() {
    let html = '';
    PICK_FIELDS.forEach(function (f) {
      const val = $('#' + f.id).val() || '-';
      const isSet = (val !== '-' && val !== '');
      html += '<div>';
      html += '<label class="rt-field-label">' + f.label + '</label>';
      html += '<div class="rt-combo">';
      html += '<div class="rt-combo-input" onclick="openPickMaster(\'' + f.id + '\')">';
      if (isSet) {
        html += '<span class="rt-combo-tag">' + htmlEscape(val) +
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

  /* modal "Pilih Data" (Gudang)
     Menggantikan popup shared #formBrowseMaster (search + Submit). Gaya picker baru:
     tanpa kolom Actions / tombol Select, seluruh baris diklik --
     lihat docs/new-cust-supp-modal-guide.md. */

  // Picker gaya baru: TANPA kolom Actions / tombol Select, baris langsung diklik.
  function pickerHeadHtml(cols) {
    return '<tr>' + cols.map(c => '<th>' + c + '</th>').join('') + '</tr>';
  }

  function pickerRowHtml(idPart, kode, cellsHtml) {
    return '<tr class="pick-row" onclick="pickMasterSelect(\'' + jsQuote(idPart) + '\',\'' + jsQuote(kode) + '\')">' +
      cellsHtml + '</tr>';
  }

  // kode master bisa mengandung tanda kutip / backslash. Nilainya masuk ke dalam string
  // JS yang ada DI DALAM atribut onclick="...", jadi harus di-escape dua kali: dulu
  // untuk JS (backslash + petik satu), lalu untuk atribut HTML (& dan petik dua).
  function jsQuote(_val) {
    return String(_val)
      .replace(/\\/g, '\\\\')
      .replace(/'/g, "\\'")
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;');
  }

  function htmlEscape(_val) {
    return String(_val)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  let pickerTargetInput = "";

  function openPickMaster(targetInputId) {
    const f = PICK_FIELDS.find(function (x) { return x.id === targetInputId; });
    if (!f) { return; }

    pickerTargetInput = targetInputId;
    const url = f.url;
    $("#modalPickMasterLabel").text(f.title);

    try {
      if ($.fn.DataTable.isDataTable('#tabelPickMaster')) {
        $('#tabelPickMaster').DataTable().destroy();
      }
    } catch (e) {
      console.error('openPickMaster: gagal destroy DataTable sebelumnya', e);
    }

    $("#tabelPickMaster_header").html("");
    $("#tabelPickMaster_data").html('<tr><td>' + loadingHtml('Memuat data...') + '</td></tr>');

    $('#modalPickMasterBackdrop').addClass('show');
    $('#modalPickMaster').addClass('show').attr('aria-hidden', 'false');

    $.ajax({
      url: url,
      type: 'get',
      success: function (res) { renderPickMaster(res); },
      error: function () {
        $("#tabelPickMaster_data").html('<tr><td class="text-center">Gagal memuat data.</td></tr>');
      }
    });
  }

  function closePickMaster() {
    $('#modalPickMaster').removeClass('show').attr('aria-hidden', 'true');
    $('#modalPickMasterBackdrop').removeClass('show');
  }

  $(document).on('keydown', function (e) {
    if (e.key === 'Escape' && $('#modalPickMaster').hasClass('show')) {
      closePickMaster();
    }
  });

  function renderPickMaster(res) {
    const kolom = (res && res.kolom) || [];
    const rows = (res && res.table) || [];

    $("#tabelPickMaster_header").html(pickerHeadHtml(kolom.map(function (k) { return k[1]; })));

    let bodyHtml = '';
    if (rows.length) {
      rows.forEach(function (item) {
        let cellsHtml = '';
        kolom.forEach(function (k) {
          let val;
          if (k[2] === 'date') { val = format_date(item[k[0]]); }
          else if (k[2] === 'float') { val = format_number(currencyNormalizer(item[k[0]]), k[3]); }
          else { val = nullToEmpty(item[k[0]]); }
          cellsHtml += '<td>' + val + '</td>';
        });
        const kode = kolom.length ? item[kolom[0][0]] : '';
        bodyHtml += pickerRowHtml(pickerTargetInput, kode, cellsHtml);
      });
    } else {
      bodyHtml = '<tr><td colspan="' + kolom.length + '" class="text-center">Tidak ada data ditemukan</td></tr>';
    }
    $("#tabelPickMaster_data").html(bodyHtml);

    try {
      $('#tabelPickMaster').DataTable({
        lengthChange: false,
        paging: rows.length > 10
      });
    } catch (e) {
      console.error('renderPickMaster: gagal inisialisasi DataTable', e);
    }
  }

  // padanan buttonPilih(idPart, kode) di docs/new-cust-supp-modal-guide.md: menulis nilai
  // terpilih ke input hidden milik field itu, lalu menutup picker.
  function pickMasterSelect(idPart, kode) {
    if (idPart) { $('#' + idPart).val(kode); }
    closePickMaster();
    renderPickFields();
    updateFilterBadge();
  }

  // Badge "N aktif" di judul modal Filter. Field dengan nilai netral ("-") tidak
  // dihitung. Jenis (globalTipe) juga tidak dihitung: pilihan wajib tanpa opsi netral,
  // lihat new-filter-modal-ui-guide.md #5.
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

  $('#modalFilter').on('show.bs.modal', function () { renderPickFields(); updateFilterBadge(); });

  $(document).ready(function() {
    $("#gButtonCustomizeTable").hide();

    setTipe(globalTipe);
    renderPickFields();

    $("#btnFilterData").on("click", function() {
      if (typeof doShowFormFilterData === "function") doShowFormFilterData();
      else alert(" Fungsi doShowFormFilterData belum tersedia.");
    });

    $("#btnCustomizeTable").on("click", function() {
      if (typeof doShowFormCustomizeTable === "function") doShowFormCustomizeTable();
      else alert(" Fungsi doShowFormCustomizeTable belum tersedia.");
    });

    $("#btnSubmitReport").on("click", function() {
      makeTable('REPORT');
    });

    // Header interaktif (drag / gigi / sembunyikan kolom) + switcher "Tampilan" untuk
    // jenis laporan -- lihat docs/new-slider-table-guide.md #5.
    ReportTable.init({
      table: '#tabel',
      bar: '#rtBar',
      onChange: renderCachedReport,
      views: {
        label: 'Tampilan',
        options: TIPE_OPTIONS,
        get: function () { return globalTipe; },
        set: function (v) {
          setTipe(v);
          // SP_REPORTMOVING mengembalikan BARIS yang berbeda per jenis (kolomnya sama),
          // jadi harus query ulang, bukan sekadar render ulang -- aturan 3 di guide.
          if (gcart_res && gcart_res.length) { makeTable('REPORT'); }
        }
      }
    });
  });

  // Render ulang tabel dari data yang sudah dimuat, dipakai sebagai onChange oleh
  // ReportTable saat kolom di-drag/disembunyikan/direset. Hasil pencarian yang sedang
  // aktif ikut dipertahankan.
  function renderCachedReport() {
    if (!gcart_res || !gcart_res.length) { return; }
    doShowReport(rowsShown(), reportTitle(), reportGroupBy, $("#inputDate1").val());
  }

  function setDefaultHeader() {
    gcart_header = [
      ['KodeBrg', 'Kd Barang', 1, 'varchar', 0, 0],
      ['NAMABRG', 'Nama Barang', 1, 'varchar', 0, 0],
      ['NamaSubGrp', 'Kategori', 1, 'varchar', 0, 0],
      ['QNT', 'P / 3 bulan', 1, 'float', 0, 2],
      ['SAT1', 'Sat', 1, 'varchar', 0, 0],
      ['SQLDOQNT', 'Stock', 1, 'float', 0, 2],
      ['hrg', 'Harga', 1, 'float', 0, 2]
    ];

    gsum_issubtotal = 0; gsum_isgrandtotal = 0;
  }

  function setRowHeader(_rowHeader) {
    // Header interaktif -- lihat docs/new-slider-table-guide.md.
    // cols HARUS berasal dari gcart_header.filter(...), bukan .map()/copy, supaya
    // ReportTable.headHtml() bisa memetakan tiap kolom balik ke index globalnya.
    const cols = gcart_header.filter(c => c[2] === 1);
    return _rowHeader + ReportTable.headHtml(cols);
  }

  function makeTable(_mode, _callback) {
    // nilai groupby adalah nama kolom (sesuai database) untuk pengelompokan subtotal
    let groupby = reportGroupBy;
    let _date1  = $("#inputDate1").val();

    let data = {
      date1            : _date1,
      inputGudang      : $("#inputGudang").val(),
      inputTipe        : globalTipe,
    };

    // Mode FILTER dipanggil doShowFormFilterData() di layout, yang sekarang membaca
    // gcart_filter lewat _callback (doMakeTable() sudah async -- lihat catatan di
    // masterreportGudang.blade.php), jadi teruskan saja _callback apa adanya.
    if (_mode !== 'REPORT') {
      doMakeTable(_mode, groupby, data, reportTitle(), _date1, null, _callback);
      return;
    }

    gRowsShown = null;          // data baru: batalkan hasil pencarian sebelumnya
    $('#searchBox2').val('');

    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

    // doMakeTable() sekarang async (dulu async:false yang membekukan tab sampai response
    // datang tanpa sempat menggambar "Memuat data..." di atas) -- jadi bagian yang
    // membaca gcart_res harus jalan di callback ini, bukan lagi lewat trik nested rAF.
    doMakeTable(_mode, groupby, data, reportTitle(), _date1, null, function () {
      let footerMsg = (gcart_res && gcart_res.length > 0)
        ? "Menampilkan " + gcart_res.length + " baris"
        : "Belum ada data dimuat";
      document.getElementById('footerLabel').textContent = footerMsg;

      if (typeof _callback === 'function') { _callback(); }
    });
  }

  function getKolomFilter() {
    // tentukan kolom (sesuai database & gcart_header) yang mau ditampilkan
    // berapa pun bisa asal dalam bentuk array
    return ['KodeBrg', 'NAMABRG'];
  }

  // Pencarian cepat di sisi client atas data yang sudah dimuat (gcart_res).
  function applyFilters() {
    if (typeof gcart_res === 'undefined' || !gcart_res.length) { return; }
    const term = ($('#searchBox2').val() || '').trim().toLowerCase();
    const cols = gcart_header.filter(c => c[2] === 1);

    gRowsShown = !term
      ? null
      : gcart_res.filter(r => cols.map(c => String(r[c[0]] || '')).join(' ').toLowerCase().indexOf(term) !== -1);

    doShowReport(rowsShown(), reportTitle(), reportGroupBy, $("#inputDate1").val());

    document.getElementById('footerLabel').textContent =
      rowsShown().length > 0 ? "Menampilkan " + rowsShown().length + " baris" : "Tidak ada data ditemukan";
  }

  function toggleExport() { document.getElementById('exportDrop').classList.toggle('open'); }
  document.addEventListener('click', function (e) {
    const wrap = document.getElementById('exportWrap');
    if (wrap && !wrap.contains(e.target)) { document.getElementById('exportDrop').classList.remove('open'); }
  });
  function doExport(fmt) {
    document.getElementById('exportDrop').classList.remove('open');
    if (fmt === 'Print') { window.print(); return; }
    if (typeof gcart_res !== 'undefined') {
      const cols = gcart_header.filter(c => c[2] === 1);
      const header = cols.map(c => c[1]);
      const body = rowsShown().map(r => cols.map(c => c[3] === 'float' || c[3] === 'int' ? currencyNormalizer(r[c[0]]) : (r[c[0]] || '')));
      ReportExport.fromRows([header].concat(body), fmt, { customName: 'Laporan' + tipeLabel().replace(/\s+/g, '') });
    }
  }
</script>

@endsection
