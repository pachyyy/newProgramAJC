@extends('report.masterreport2')

<style>
  .tb-report .table-wrap { min-height: 10vh; }
</style>

@include('report.modalMarketingSO')

@section('header2')
<div class="tb-report main">
  <div class="content">

    <!-- TOOLBAR -->
    <div class="toolbar">
      {{-- <div>
        <div class="page-title">Report Marketing Outstanding SO</div>
      </div> --}}

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

      <!-- Actions: search + filter modal + customize + tampilkan + export -->
      <div class="action-group">
        {{-- Dibuka lewat plugin jQuery (Bootstrap 4), BUKAN data-bs-toggle (Bootstrap 5).
             Halaman ini memuat dua Bootstrap; jQuery dimuat SESUDAH bundle BS5, jadi
             $.fn.modal dipegang BS4. JS di bawah menutup modal ini dengan
             $('#modalFilter').modal('hide'), jadi pembukanya harus API yang sama. --}}
        <button class="btn-load" type="button" onclick="$('#modalFilter').modal('show')">
          <i class="fas fa-filter"></i> Filter
        </button>
        {{-- Digantikan oleh menu roda gigi + bar kolom (#rtBar) di bawah toolbar; JS-nya
             (doShowFormCustomizeTable dst.) tetap dipertahankan, lihat docs/new-design-all-report-guide.md §5.7. --}}
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

    <!-- Bar kolom tersembunyi + Tampilan (diisi oleh report-table.js / ReportTable) -->
    <div id="rtBar"></div>

    <!-- TABLE -->
    <div class="table-outer">
      <div class="table-wrap">
        <table class="tb" id="mainTable">
          <thead>
            <tr><th>No. Bukti</th></tr>
          </thead>
          <tbody id="tableBody">
            <tr class="empty-row"><td>Atur filter lalu klik <b>Tampilkan</b> untuk memuat laporan.</td></tr>
          </tbody>
        </table>
      </div>
      <div class="table-footer">
        <span id="footerLabel">Belum ada data dimuat</span>
      </div>
    </div>

    <div class="rt-hint">
      <i class="bi bi-info-circle"></i>
      Seret judul kolom untuk mengurutkan. Klik <i class="bi bi-gear"></i> pada judul kolom untuk
      sembunyikan kolom atau atur desimal &amp; total.
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
          <i class="fas fa-filter"></i>
          Filter Laporan
          <span class="rt-active-badge" id="filterBadge">0 aktif</span>
        </h5>
        <button type="button" class="btn-close" aria-label="Close"
          data-dismiss="modal" data-bs-dismiss="modal"
          onclick="$('#modalFilter').modal('hide')"></button>
      </div>

      <div class="modal-body">

        <div class="rt-section">
          <div class="rt-group-label">Pengaturan Laporan</div>
          {{-- 'Report' (Detail/Rekap) dipindah ke switcher "Tampilan" di bar (#rtBar, lihat
               ReportTable.init di bawah) supaya bisa diganti tanpa buka modal Filter. Markup lama
               disimpan sebagai referensi -- JANGAN dihapus, lihat docs/new-design-all-report-guide.md §5.7. --}}
          {{--
          <div class="rt-grid-2">
            <div>
              <label class="rt-field-label" for="modalReport">Report</label>
              <select class="rt-native" id="modalReport">
                <option value="0">Detail</option>
                <option value="1">Rekap</option>
              </select>
            </div>
          </div>
          --}}
          <div class="rt-grid-1">
            <div>
              <label class="rt-field-label" for="modalOtorisasi">Otorisasi</label>
              <select class="rt-native" id="modalOtorisasi">
                <option value="0">Non Otorisasi</option>
                <option value="1">Otorisasi</option>
                <option value="2">Semua</option>
              </select>
            </div>
          </div>
          <div class="rt-grid-1">
            <div>
              <label class="rt-field-label" for="modalOrder">Order By</label>
              <select class="rt-native" id="modalOrder">
                <option value="N">Nomor Bukti</option>
                <option value="B">Nomor Barang</option>
                <option value="C">Nomor Customer</option>
                <option value="S">Sales</option>
                <option value="HG">Head Group</option>
                <option value="P">PIC</option>
              </select>
            </div>
          </div>
        </div>

        <div class="rt-section">
          <div class="rt-group-label">Filter Data
            <span class="rt-group-hint">&mdash; klik untuk memilih</span>
          </div>
          <div class="rt-grid-2" id="pickFields"></div>

          {{-- Nilai sebenarnya (dibaca makeTable() & ditulis buttonPilih()) --}}
          <input type="hidden" id="inputCustomer" value="-">
          <input type="hidden" id="inputGroup" value="-">
          <input type="hidden" id="inputPIC" value="-">
          <input type="hidden" id="inputKategori" value="-">
          <input type="hidden" id="inputSubKategori" value="-">
          <input type="hidden" id="inputMerk" value="-">
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
  // Picker (#formSelect di modalMarketingSO.blade.php): baris langsung diklik, tanpa
  // kolom Actions/tombol Select — konsisten dengan Filter modal yang sudah diredesain.
  window.g_pickerV2 = true;

  let globalDate1 = "{!! date('Y-m-d') !!}";
  let globalDate2 = "{!! date('Y-m-d') !!}";
  let globalOtorisasi = "2"; // default: Semua
  let globalOrderBy = "N";   // default: Nomor Bukti
  let globalReportMode = "0"; // default: Detail

  var jenisreport = 0; // ini untuk detail dan rekap
  let lastRows = [];        // hasil fetch terakhir (dipakai render / export / search)
  let currentGroupby = 'NoBukti'; // groupby aktif untuk render ulang saat search

  const reportUrl = "{{ url('laporanmarketinglaporanoutso_doReport') }}";

  $(document).ready(function() {
    setReportMode(globalReportMode);
    setOtorisasi(globalOtorisasi);
    setOrderBy(globalOrderBy);
    showPeriode();
    setDefaultHeader();

    ReportTable.init({
      table: '#mainTable', // the <table class="tb">
      bar: '#rtBar',       // the div from Step 1
      onChange: render,    // the page's OWN render function
      views: {
        label: 'Tampilan',
        options: [
          { value: '0', label: 'Detail', desc: 'Rincian per baris' },
          { value: '1', label: 'Rekap', desc: 'Ringkasan per grup' }
        ],
        get: function() { return String(globalReportMode); },
        set: function(v) {
          // Kolom saja yang berganti (SP tidak menerima parameter detail/rekap,
          // lihat doReport() di LaporanMarketingLaporanOutSoController) -> cukup render ulang.
          setReportMode(String(v));
          if (lastRows.length) { render(); }
        }
      }
    });

    // setTimeout(() => {
    //   makeTable('REPORT');
    // }, 100);
  });

  // periode
  function showPeriode() {
    globalDate1 = $('#inputDate1').val();
    globalDate2 = $('#inputDate2').val();
  }

  // otorisasi
  function setOtorisasi(val) {
    globalOtorisasi = val;
  }

  // order by
  function setOrderBy(val) {
    globalOrderBy = val;
    setModeReport();
  }

  function setReportMode(val) {
    globalReportMode = val;
    jenisreport = Number(val);   // 0 = Detail, 1 = Rekap
    DetOrRekap = Number(val);    // samakan dengan variabel yang ada di setModeReport

    // update g_modeReport sesuai pilihan order & detail/rekap
    // setModeReport() sudah mengatur g_modeReport berdasarkan globalOrderBy dan jenisreport/DetOrRekap
    setModeReport();
  }

  /* -- FILTER MODAL -- */

  // Enam field "Filter Data" (Customer/Group/PIC/Kategori/Sub-Kat/Merk): nilai
  // sebenarnya tetap di input hidden #inputXxx (dibaca makeTable(), ditulis
  // buttonPilih() di modalMarketingSO.blade.php) — kotak .rt-combo di bawah ini
  // hanyalah tampilan di atasnya.
  const PICK_FIELDS = [
    { id: 'inputCustomer', label: 'Customer', modal: 'selectCustomer' },
    { id: 'inputGroup', label: 'Group', modal: 'selectGroup' },
    { id: 'inputPIC', label: 'PIC', modal: 'selectPIC' },
    { id: 'inputKategori', label: 'Kategori', modal: 'selectKategori' },
    { id: 'inputSubKategori', label: 'Sub-Kat', modal: 'selectSubKategori' },
    { id: 'inputMerk', label: 'Merk', modal: 'selectMerk' },
  ];

  function renderPickFields() {
    let html = '';
    PICK_FIELDS.forEach(function(f) {
      const val = $('#' + f.id).val() || '-';
      const isSet = (val !== '-' && val !== '');
      html += '<div>';
      html += '<label class="rt-field-label">' + f.label + '</label>';
      html += '<div class="rt-combo">';
      html += '<div class="rt-combo-input" onclick="pickFromModal(\'' + f.modal + '\')">';
      if (isSet) {
        html += '<span class="rt-combo-tag">' + val +
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

  // Otorisasi ikut dihitung (punya nilai netral: '2'=Semua). Report (Detail/Rekap) pindah
  // ke switcher "Tampilan" di bar (#rtBar), jadi tidak lagi dihitung di sini. Order By TIDAK
  // dihitung: dia wajib memilih salah satu kolom urut, tanpa opsi netral — sama seperti
  // Outstanding di reportmarketinghistoryoutso.
  function updateFilterBadge() {
    let count = 0;
    PICK_FIELDS.forEach(function(f) {
      const val = $('#' + f.id).val();
      if (val && val !== '-') { count++; }
    });
    if ($('#modalOtorisasi').val() !== '2') { count++; }
    $('#filterBadge').text(count + ' aktif');
  }

  function resetAllFilters() {
    $('#modalOtorisasi').val('2');
    $('#modalOrder').val('N');
    PICK_FIELDS.forEach(function(f) { $('#' + f.id).val('-'); });
    renderPickFields();
    updateFilterBadge();
  }

  $('#modalFilter').on('show.bs.modal', function() {
    $("#modalOtorisasi").val(globalOtorisasi);
    $("#modalOrder").val(globalOrderBy);
    renderPickFields();
    updateFilterBadge();
  });

  $('#modalFilter').on('change', 'select.rt-native', updateFilterBadge);

  function applyModalFilter() {
    setOtorisasi($("#modalOtorisasi").val());
    setOrderBy($("#modalOrder").val());

    $('#modalFilter').modal('hide');
  }

  // Jembatan ke modal pilih entitas (#formSelect di modalMarketingSO.blade.php):
  // sembunyikan modal Filter dulu (hindari Bootstrap stacked-modal), lalu buka lagi
  // setelah modal pilih ditutup (buttonPilih() di modalMarketingSO sudah hide #formSelect).
  let g_reopenFilter = false;
  function pickFromModal(idModal) {
    g_reopenFilter = true;
    $('#modalFilter').modal('hide');
    buttonSelect(idModal);
  }
  $(document).on('hidden.bs.modal', '#formSelect', function() {
    if (g_reopenFilter) {
      g_reopenFilter = false;
      $('#modalFilter').modal('show');
      // #modalFilter 'show.bs.modal' juga memanggil ini, tapi panggil lagi di sini
      // supaya kotak .rt-combo langsung terupdate walau show.bs.modal tidak sempat
      // fire ulang (mis. modal masih dalam proses transisi tampil).
      renderPickFields();
      updateFilterBadge();
    }
  });

  /* -- EXPORT -- */
  function toggleExport() {
    document.getElementById('exportDrop').classList.toggle('open');
  }
  document.addEventListener('click', function(e) {
    const wrap = document.getElementById('exportWrap');
    if (wrap && !wrap.contains(e.target)) {
      document.getElementById('exportDrop').classList.remove('open');
    }
  });
  function doExport(fmt) {
    document.getElementById('exportDrop').classList.remove('open');
    if (fmt === 'Print') { window.print(); return; }
    exportDelimited(fmt);
  }
  function exportDelimited(fmt) {
    const cols = gcart_header.filter(c => c[2] === 1);
    const header = cols.map(c => c[1]);
    const body = (lastRows || []).map(r => cols.map(function(c) {
      const v = pickCI(r, c[0]);
      if (c[3] === 'date') return format_date(v);
      if (c[3] === 'float' || c[3] === 'int') return currencyNormalizer(v);
      return (v == null ? '' : v);
    }));
    const rows = [header].concat(body);
    ReportExport.fromRows(rows, fmt, { customName: 'OutstandingSO_' + (globalDate1 || '') + '_' + (globalDate2 || '') });
    showToast('📄', 'Data diekspor sebagai ' + fmt);
  }

  /* -- TOAST -- */
  function showToast(icon, msg) {
    const t = document.getElementById('toast');
    document.getElementById('ti').textContent = icon;
    document.getElementById('tm').textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
  }

  function pickCI(r, key) {
    if (r[key] !== undefined) return r[key];
    const lk = String(key).toLowerCase();
    for (const k in r) { if (k.toLowerCase() === lk) return r[k]; }
    return undefined;
  }

  var modereport_detailnobukti = 0, modereport_detailbarang = 1, modereport_detailcustomer = 2 ;
  var modereport_rekapnobukti = 3, modereport_rekapbarang = 4, modereport_rekapcustomer = 5 ;
  var modereport_detailsales = 6, modereport_rekapsales = 7;
  var modereport_detailhg = 8, modereport_rekaphg = 9;
  var modereport_detailcb = 10, modereport_rekapcb = 11;
  var modereport_detailpic = 12, modereport_rekappic = 13;
  g_modeReport = modereport_detailnobukti;

  function setDefaultHeader() {
    if (g_modeReport == modereport_detailnobukti) {
      gcart_header = [
        ['NoBukti', 'No. Bukti', 1, 'varchar', 0, 0],
        ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
        ['NoSPB', 'PO. Cust', 1, 'varchar', 0, 0],
        ['NamaKebun', 'Lokasi Penerima', 1, 'varchar', 0, 0],
        ['NAMACUSTSUPP', 'Nama Customer', 1, 'varchar', 0, 0],
        ['NamaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
        ['Satuan', 'Sat', 1, 'varchar', 0, 0],
        ['QNT', 'Qnt', 1, 'float', 0, 0],
        ['HARGA', 'Harga', 1, 'float', 0, 0],
        ['NDPPRPZX', 'DPP IDR', 1, 'float', 1, 0],
        ['NPPNRPX', 'PPN IDR', 1, 'float', 1, 0],
        ['NNETRPZX', 'Total IDR', 1, 'float', 1, 0],
        ['TglPO', 'Tgl. PO', 1, 'date', 0, 0],
      ];

      gsum_issubtotal = 0; gsum_isgrandtotal = 1;

    } else if (g_modeReport == modereport_detailbarang){
      gcart_header = [
        ['NoBukti', 'No. Bukti', 1, 'varchar', 0, 0],
        ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
        ['NAMACUSTSUPP', 'Nama Customer', 1, 'varchar', 0, 0],
        ['NamaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
        ['Satuan', 'Sat', 1, 'varchar', 0, 0],
        ['QNT', 'Qnt', 1, 'float', 0, 0],
        ['HARGA', 'Harga', 1, 'float', 0, 0],
        ['DiscTot', 'Diskon Total', 1, 'float', 0, 0],
        ['KODEVLS', 'Valas', 1, 'varchar', 0, 0],
        ['KURS', 'Kurs', 1, 'float', 0, 0],
        ['DPPVLSZX', 'DPP Valas', 1, 'float', 1, 0],
        ['NDPPRPZX', 'DPP IDR', 1, 'float', 1, 0],
        ['NPPNRPX', 'PPN IDR', 1, 'float', 1, 0],
        ['NNETRPZX', 'Total IDR', 1, 'float', 1, 0],
        ['TglPO', 'Tgl. PO', 1, 'date', 0, 0]
      ];
      gsum_issubtotal = 0; gsum_isgrandtotal = 1;

    } else if(g_modeReport == modereport_detailcustomer){
      gcart_header = [
        ['NoBukti', 'No. Bukti', 1, 'varchar', 0, 0],
        ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
        ['NAMACUSTSUPP', 'Nama Customer', 1, 'varchar', 0, 0],
        ['NamaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
        ['Satuan', 'Sat', 1, 'varchar', 0, 0],
        ['QNT', 'Qnt', 1, 'float', 0, 0],
        ['HARGA', 'Harga', 1, 'float', 0, 0],
        ['DiscTot', 'Diskon Total', 1, 'float', 0, 0],
        ['KODEVLS', 'Valas', 1, 'varchar', 0, 0],
        ['KURS', 'Kurs', 1, 'float', 0, 0],
        ['DPPVLSZX', 'DPP Valas', 1, 'float', 1, 0],
        ['NDPPRPZX', 'DPP IDR', 1, 'float', 1, 0],
        ['NPPNRPX', 'PPN IDR', 1, 'float', 1, 0],
        ['NNETRPZX', 'Total IDR', 1, 'float', 1, 0],
        ['TglPO', 'Tgl. PO', 1, 'date', 0, 0]
      ];
      gsum_issubtotal = 0; gsum_isgrandtotal = 1;

    } else if(g_modeReport == modereport_detailsales){
      gcart_header = [
        ['NoBukti', 'No. Bukti', 1, 'varchar', 0, 0],
        ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
        ['NAMACUSTSUPP', 'Nama Customer', 1, 'varchar', 0, 0],
        ['NamaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
        ['Satuan', 'Sat', 1, 'varchar', 0, 0],
        ['QNT', 'Qnt', 1, 'float', 0, 0],
        ['HARGA', 'Harga', 1, 'float', 0, 0],
        ['DiscTot', 'Diskon Total', 1, 'float', 0, 0],
        ['DPPVLSZX', 'DPP Valas', 1, 'float', 1, 0],
        ['NDPPRPZX', 'DPP IDR', 1, 'float', 1, 0],
        ['NPPNRPX', 'PPN IDR', 1, 'float', 1, 0],
        ['NNETRPZX', 'Total IDR', 1, 'float', 1, 0],
        ['TglPO', 'Tgl. PO', 1, 'date', 0, 0]
      ];
      gsum_issubtotal = 0; gsum_isgrandtotal = 1;

    } else if(g_modeReport == modereport_detailhg){
      gcart_header = [
        ['NoBukti', 'No. Bukti', 1, 'varchar', 0, 0],
        ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
        ['NAMACUSTSUPP', 'Nama Customer', 1, 'varchar', 0, 0],
        ['NamaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
        ['Satuan', 'Sat', 1, 'varchar', 0, 0],
        ['QNT', 'Qnt', 1, 'float', 0, 0],
        ['HARGA', 'Harga', 1, 'float', 0, 0],
        ['DiscTot', 'Diskon Total', 1, 'float', 0, 0],
        ['DPPVLSZX', 'DPP Valas', 1, 'float', 1, 0],
        ['NDPPRPZX', 'DPP IDR', 1, 'float', 1, 0],
        ['NPPNRPX', 'PPN IDR', 1, 'float', 1, 0],
        ['NNETRPZX', 'Total IDR', 1, 'float', 1, 0],
        ['TglPO', 'Tgl. PO', 1, 'date', 0, 0]
      ];
      gsum_issubtotal = 0; gsum_isgrandtotal = 1;

    } else if(g_modeReport == modereport_detailcb){
      gcart_header = [
        ['NoBukti', 'No. Bukti', 1, 'varchar', 0, 0],
        ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
        ['NAMACUSTSUPP', 'Nama Customer', 1, 'varchar', 0, 0],
        ['NamaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
        ['Satuan', 'Sat', 1, 'varchar', 0, 0],
        ['QNT', 'Qnt', 1, 'float', 0, 0],
        ['HARGA', 'Harga', 1, 'float', 0, 0],
        ['DiscTot', 'Diskon Total', 1, 'float', 0, 0],
        ['DPPVLSZX', 'DPP Valas', 1, 'float', 1, 0],
        ['NDPPRPZX', 'DPP IDR', 1, 'float', 1, 0],
        ['NPPNRPX', 'PPN IDR', 1, 'float', 1, 0],
        ['NNETRPZX', 'Total IDR', 1, 'float', 1, 0],
        ['TglPO', 'Tgl. PO', 1, 'date', 0, 0]
      ];
      gsum_issubtotal = 0; gsum_isgrandtotal = 1;

    } else if(g_modeReport == modereport_detailpic){
      gcart_header = [
        ['NoBukti', 'No. Bukti', 1, 'varchar', 0, 0],
        ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
        ['NAMACUSTSUPP', 'Nama Customer', 1, 'varchar', 0, 0],
        ['NamaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
        ['Satuan', 'Sat', 1, 'varchar', 0, 0],
        ['QNT', 'Qnt', 1, 'float', 0, 0],
        ['HARGA', 'Harga', 1, 'float', 0, 0],
        ['DiscTot', 'Diskon Total', 1, 'float', 0, 0],
        ['KODEVLS', 'Valas', 1, 'varchar', 0, 0],
        ['KURS', 'Kurs', 1, 'float', 0, 0],
        ['DPPVLSZX', 'DPP Valas', 1, 'float', 1, 0],
        ['NDPPRPZX', 'DPP IDR', 1, 'float', 1, 0],
        ['NPPNRPX', 'PPN IDR', 1, 'float', 1, 0],
        ['NNETRPZX', 'Total IDR', 1, 'float', 1, 0],
        ['TglPO', 'Tgl. PO', 1, 'date', 0, 0]
      ];
      gsum_issubtotal = 0; gsum_isgrandtotal = 1;

    } else if(g_modeReport == modereport_rekapnobukti){
      gcart_header = [
        ['NoBukti', 'No Bukti', 1, 'varchar', 0, 0],
        ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
        ['KodeCustSupp', 'Kode', 1, 'varchar', 0, 0],
        ['NAMACUSTSUPP', 'Nama Supplier', 1, 'varchar', 0, 0],
        ['NDPPRPZX', 'DPP IDR', 1, 'float', 1, 2],
        ['NPPNRPX', 'PPN IDR', 1, 'float', 1, 2],
        ['NNETRPZX', 'Total IDR', 1, 'float', 1, 2],
        ['KODEVLS', 'Vls', 1, 'varchar', 0, 0],
        ['kurs', 'Kurs', 1, 'varchar', 0, 0],
        ['DPPVLSZX', 'DPP $', 1, 'float', 1, 2],
        ['NPPNRPVLSX', 'PPN $', 1, 'float', 1, 2],
        ['NNETRPVLSZX', 'Total $', 1, 'float', 1, 2]
      ];
      gsum_issubtotal = 0; gsum_isgrandtotal = 1;

    } else if(g_modeReport == modereport_rekapbarang){
      gcart_header = [
        ['NoBukti', 'No. Bukti', 1, 'varchar', 0, 0],
        ['NamaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
        ['QNT', 'Qnt', 1, 'float', 0, 0],
        ['Satuan', 'Sat', 1, 'varchar', 0, 0],
        ['NDPPRPZX', 'DPP PPN', 1, 'float', 1, 0],
        ['NPPNRPX', 'PPN IDR', 1, 'float', 1, 0],
        ['NNETRPZX', 'Total IDR', 1, 'float', 1, 0]
      ];
      gsum_issubtotal = 0; gsum_isgrandtotal = 1;

    } else if(g_modeReport == modereport_rekapcustomer){
      gcart_header = [
        ['NoBukti', 'No Bukti', 1, 'varchar', 0, 0],
        ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
        ['KodeCustSupp', 'Kode', 1, 'varchar', 0, 0],
        ['NAMACUSTSUPP', 'Nama Supplier', 1, 'varchar', 0, 0],
        ['NDPPRPZX', 'DPP IDR', 1, 'float', 1, 2],
        ['NPPNRPX', 'PPN IDR', 1, 'float', 1, 2],
        ['NNETRPZX', 'Total IDR', 1, 'float', 1, 2],
        ['KODEVLS', 'Vls', 1, 'varchar', 0, 0],
        ['kurs', 'Kurs', 1, 'varchar', 0, 0],
        ['DPPVLSZX', 'DPP $', 1, 'float', 1, 2],
        ['NPPNRPVLSX', 'PPN $', 1, 'float', 1, 2],
        ['NNETRPVLSZX', 'Total $', 1, 'float', 1, 2]
      ];
      gsum_issubtotal = 0; gsum_isgrandtotal = 1;

    } else if(g_modeReport == modereport_rekapsales){
      gcart_header = [
        ['NoBukti', 'No. Bukti', 1, 'varchar', 0, 0],
        ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
        ['NAMACUSTSUPP', 'Nama Customer', 1, 'varchar', 0, 0],
        ['NamaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
        ['Satuan', 'Sat', 1, 'varchar', 0, 0],
        ['QNT', 'Qnt', 1, 'float', 0, 0],
        ['HARGA', 'Harga', 1, 'float', 0, 0],
        ['DiscTot', 'Diskon Total', 1, 'float', 0, 0],
        ['DPPVLSZX', 'DPP Valas', 1, 'float', 1, 0],
        ['NDPPRPZX', 'DPP IDR', 1, 'float', 1, 0],
        ['NPPNRPX', 'PPN IDR', 1, 'float', 1, 0],
        ['NNETRPZX', 'Total IDR', 1, 'float', 1, 0],
        ['TglPO', 'Tgl. PO', 1, 'date', 0, 0]
      ];
      gsum_issubtotal = 0; gsum_isgrandtotal = 1;

    } else if(g_modeReport == modereport_rekaphg){
      gcart_header = [
        ['NoBukti', 'No Bukti', 1, 'varchar', 0, 0],
        ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
        ['KodeCustSupp', 'Kode', 1, 'varchar', 0, 0],
        ['NAMACUSTSUPP', 'Nama Supplier', 1, 'varchar', 0, 0],
        ['NDPPRPZX', 'DPP IDR', 1, 'float', 1, 2],
        ['NPPNRPX', 'PPN IDR', 1, 'float', 1, 2],
        ['NNETRPZX', 'Total IDR', 1, 'float', 1, 2],
        ['KODEVLS', 'Vls', 1, 'varchar', 0, 0],
        ['kurs', 'Kurs', 1, 'varchar', 0, 0],
        ['DPPVLSZX', 'DPP $', 1, 'float', 1, 2],
        ['NPPNRPVLSX', 'PPN $', 1, 'float', 1, 2],
        ['NNETRPVLSZX', 'Total $', 1, 'float', 1, 2]
      ];
      gsum_issubtotal = 0; gsum_isgrandtotal = 1;

    } else if(g_modeReport == modereport_rekapcb){
      gcart_header = [
        ['NoBukti', 'No Bukti', 1, 'varchar', 0, 0],
        ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
        ['KodeCustSupp', 'Kode', 1, 'varchar', 0, 0],
        ['NAMACUSTSUPP', 'Nama Supplier', 1, 'varchar', 0, 0],
        ['NDPPRPZX', 'DPP IDR', 1, 'float', 1, 2],
        ['NPPNRPX', 'PPN IDR', 1, 'float', 1, 2],
        ['NNETRPZX', 'Total IDR', 1, 'float', 1, 2],
        ['KODEVLS', 'Vls', 1, 'varchar', 0, 0],
        ['kurs', 'Kurs', 1, 'varchar', 0, 0],
        ['DPPVLSZX', 'DPP $', 1, 'float', 1, 2],
        ['NPPNRPVLSX', 'PPN $', 1, 'float', 1, 2],
        ['NNETRPVLSZX', 'Total $', 1, 'float', 1, 2]
      ];
      gsum_issubtotal = 0; gsum_isgrandtotal = 1;

    } else if(g_modeReport == modereport_rekappic){
      gcart_header = [
        ['NoBukti', 'No. Bukti', 1, 'varchar', 0, 0],
        ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
        ['NAMACUSTSUPP', 'Nama Customer', 1, 'varchar', 0, 0],
        ['NamaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
        ['Satuan', 'Sat', 1, 'varchar', 0, 0],
        ['QNT', 'Qnt', 1, 'float', 0, 0],
        ['HARGA', 'Harga', 1, 'float', 0, 0],
        ['DiscTot', 'Diskon Total', 1, 'float', 0, 0],
        ['KODEVLS', 'Valas', 1, 'varchar', 0, 0],
        ['KURS', 'Kurs', 1, 'float', 0, 0],
        ['DPPVLSZX', 'DPP Valas', 1, 'float', 1, 0],
        ['NDPPRPZX', 'DPP IDR', 1, 'float', 1, 0],
        ['NPPNRPX', 'PPN IDR', 1, 'float', 1, 0],
        ['NNETRPZX', 'Total IDR', 1, 'float', 1, 0],
        ['TglPO', 'Tgl. PO', 1, 'date', 0, 0]
      ];
      gsum_issubtotal = 0; gsum_isgrandtotal = 1;

    }

  }

  function makeTable(_mode) {
    // nilai groupby adalah nama kolom (sesuai database) untuk pengelompokan subtotal
    // mode report menentukan kolom yang dipakai
    let groupby = '';
    let _date1    = $("#inputDate1").val();
    let _date2    = $("#inputDate2").val();
    let inputOto = globalOtorisasi;
    let _inputCustomer = $("#inputCustomer").val();
    let _inputGroup = $("#inputGroup").val();
    let _inputPIC = $("#inputPIC").val();
    let _inputKategori = $("#inputKategori").val();
    let _inputSubKategori = $("#inputSubKategori").val();
    let _inputMerk = $("#inputMerk").val();
    let input_order = globalOrderBy;

      if (input_order == "N") {
        groupby = 'NoBukti';
      } else if (input_order == "B") {
        groupby = 'KODEBRG';
      } else if( input_order == "C"){
        groupby = 'KodeCustSupp';
      } else if( input_order == "S"){
        groupby = 'KodeSls';
      } else if( input_order == "HG"){
        groupby = 'KodeCustSupp';
      } else if( input_order == "P"){
        groupby = 'KodeCustSupp';
      }

    setDefaultHeader();
    if (typeof doSetHeader === 'function') {
      doSetHeader(g_modeReport);
    }

    let data = {
      date1    : _date1,
      date2    : _date2,
      inputCustomer : _inputCustomer,
      inputGroup : _inputGroup,
      inputPIC : _inputPIC,
      inputKategori: _inputKategori,
      inputSubKategori: _inputSubKategori,
      inputMerk : _inputMerk,
      inputOto : inputOto,
      inputOrd : input_order,
    };

    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

    $.ajax({
      url: reportUrl,
      type: 'get',
      data: data,
      success: function(res) {
        lastRows = res || [];
        currentGroupby = groupby;
        $('#searchBox2').val('');
        render();
      },
      error: function() {
        lastRows = [];
        currentGroupby = groupby;
        render();
      }
    });
  }

  // === RENDER KE TABEL STYLED (.tb-report #mainTable) ===
  // Kolom dibangun DINAMIS dari gcart_header (hanya kolom yang terlihat / item[2]===1,
  // sesuai urutan simpanan) -> mode-agnostic, jadi ke-14 mode report (item[4]===1
  // menandai kolom uang yang di-subtotal) langsung terpakai tanpa daftar kolom hardcode.
  function render() {
    const cols = gcart_header.filter(c => c[2] === 1); // kolom terlihat, terurut
    const keys = cols.filter(c => c[4] === 1).map(c => c[0]); // kolom uang yang di-subtotal
    const thead = document.querySelector('#mainTable thead');
    const tbody = document.getElementById('tableBody');
    const showSub = (gsum_issubtotal === 1);
    const showGrand = (gsum_isgrandtotal === 1);

    const search = ($('#searchBox2').val() || '').trim().toLowerCase();
    const rows = !search ? (lastRows || []) : (lastRows || []).filter(function(r) {
      return rowSearchText(r, cols).indexOf(search) !== -1;
    });

    // HEADER dinamis dari gcart_header — dibangun report-table.js (ReportTable) supaya kolom
    // bisa diseret untuk diurutkan & punya menu roda gigi (sembunyikan / desimal / total).
    // Juga menyegarkan #rtBar (daftar kolom tersembunyi + Tampilan).
    thead.innerHTML = ReportTable.headHtml(cols);

    if (!rows.length) {
      tbody.innerHTML = '<tr class="empty-row"><td colspan="' + cols.length + '">Tidak ada data ditemukan.</td></tr>';
      document.getElementById('footerLabel').textContent = 'Tidak ada data';
      return;
    }

    let html = '', prev = null;
    let sub = {}, grand = {};
    keys.forEach(k => { sub[k] = 0; grand[k] = 0; });

    rows.forEach(function(r, i) {
      const now = r[currentGroupby];

      // subtotal saat nilai grup berganti (kalau toggle Subtotal aktif)
      if (showSub && i !== 0 && prev !== now) {
        html += totalRowTotal('Subtotal', sub, cols, keys, 'subtotal-row');
        keys.forEach(k => { sub[k] = 0; });
      }

      keys.forEach(function(k) {
        const v = currencyNormalizer(pickCI(r, k));
        sub[k] += v;
        grand[k] += v;
      });

      // satu sel per kolom terlihat, format menurut tipe (item[3]) & desimal (item[5])
      html += '<tr class="data-row">' + cols.map(function(c) {
        const key = c[0], type = c[3];
        if (type === 'date') return '<td>' + format_date(pickCI(r, key)) + '</td>';
        if (type === 'float' || type === 'int') return '<td class="num">' + format_number(currencyNormalizer(pickCI(r, key)), c[5]) + '</td>';
        return '<td>' + nullToEmpty(pickCI(r, key)) + '</td>';
      }).join('') + '</tr>';

      prev = now;
    });

    // subtotal grup terakhir + grand total   mengikuti toggle di modal Customize Table
    if (showSub) html += totalRowTotal('Subtotal', sub, cols, keys, 'subtotal-row');
    if (showGrand) html += totalRowTotal('GRAND TOTAL', grand, cols, keys, 'grand-total');

    tbody.innerHTML = html;
    document.getElementById('footerLabel').textContent = 'Menampilkan ' + rows.length + ' baris';
  }

  // Baris total: nilai di kolom uang (item[4]===1), label di kolom pertama non-uang
  // yang masih terlihat, sel lain dikosongkan   mengikuti urutan kolom terlihat saat ini.
  function totalRowTotal(label, total, cols, keys, cls) {
    const labelIdx = cols.findIndex(c => keys.indexOf(c[0]) === -1);

    const tds = cols.map(function(c, idx) {
      if (keys.indexOf(c[0]) !== -1) {
        return '<td class="num">' + format_number(total[c[0]], c[5]) + '</td>';
      }
      if (idx === labelIdx) return '<td>' + label + '</td>';
      return '<td></td>';
    });

    return '<tr class="' + cls + '">' + tds.join('') + '</tr>';
  }

  // === PENCARIAN SISI-KLIEN ===
  function applyFilters() {
    if (!lastRows.length) return; // belum ada data dimuat
    render();
  }

  // Gabungan teks satu baris dari kolom terlihat (tanggal pakai format tampil
  // dd/mm/yyyy) supaya pencarian cocok dengan apa yang user lihat di tabel.
  function rowSearchText(r, cols) {
    return cols.map(function(c) {
      const v = pickCI(r, c[0]);
      if (c[3] === 'date') return format_date(v);
      return (v == null ? '' : String(v));
    }).join(' ').toLowerCase();
  }

  function getKolomFilter() {
    // tentukan kolom (sesuai database & gcart_header) yang mau ditampilkan
    // mode report menentukan kolom yang dipakai
    // berapa pun bisa asal dalam bentuk array

    let data = [];
    if (globalOrderBy == "N")
    {
      data = ['NoBukti', 'Tanggal'];
    } else if (globalOrderBy == "B")
    {
      data = ['KODEBRG', 'NAMABRG'];
    } else if (globalOrderBy == "C")
    {
      data = ['KodeCustSupp', 'NAMACUSTSUPP'];
    } else if (globalOrderBy == "S")
    {
      data = ['KodeCustSupp', 'NAMACUSTSUPP'];
    } else if (globalOrderBy == "HG")
    {
      data = ['NoBukti', 'NAMACUSTSUPP'];
    } else if (globalOrderBy == "P")
    {
      data = ['NoBukti', 'NAMACUSTSUPP'];
    }

    return data;
  }

  function setModeReport () {
    if (globalOrderBy == "N") {
      if (jenisreport === 0) {
        g_modeReport = modereport_detailnobukti;
      } else {
        g_modeReport = modereport_rekapnobukti;
      }
    } else if (globalOrderBy == "B") {
      if (jenisreport === 0) {
        g_modeReport = modereport_detailbarang;
      } else {
        g_modeReport = modereport_rekapbarang;
      }
    } else if (globalOrderBy == "C") {
      if (jenisreport === 0) {
        g_modeReport = modereport_detailcustomer;
      } else {
        g_modeReport = modereport_rekapcustomer;
      }
    } else if (globalOrderBy == "S") {
      if (jenisreport === 0) {
        g_modeReport = modereport_detailsales;
      } else {
        g_modeReport = modereport_rekapsales;
      }
    } else if (globalOrderBy == "HG") {
      if (jenisreport === 0) {
        g_modeReport = modereport_detailcb;
      } else {
        g_modeReport = modereport_rekapcb;
      }
    } else if (globalOrderBy == "P") {
      if (jenisreport === 0) {
        g_modeReport = modereport_detailpic;
      } else {
        g_modeReport = modereport_rekappic;
      }
    }

    doSetHeader(g_modeReport);
    doShowCustomize();
  }

</script>

@endsection
