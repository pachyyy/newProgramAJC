@extends('report.masterreport2')

<style>
  /* tinggi awal area tabel supaya dropdown tidak terpotong container pendek */
  .tb-report .table-wrap { min-height: 12vh; }
</style>

@section('header2')
<div class="tb-report main">
      <div class="content">

        <!-- TOOLBAR -->
        <div class="toolbar">
          {{-- <div>
            <div class="page-title">Pengadaan PR</div>
            <!-- <div class="page-sub">Dicetak oleh: {{ $akses['user'] }} &nbsp;&middot;&nbsp; <span id="printTime"></span></div> -->
          </div> --}}

          <!-- Jenis laporan: Non Outstanding (ke Sp_ReportPurchasingReqDet, tombol Filter aktif,
               dua tanggal, Order By No Bukti/Barang/Customer) atau Outstanding (ke
               Sp_reportoutStandingPR, tombol Filter disembunyikan -- proc ini tidak punya kolom
               Otorisasi/Status PO -- HANYA tanggal kedua, Order By cuma No Bukti/Barang;
               LaporanPurchaseOrderOSPController mematok tgl1 sendiri). -->
          <div class="filter-wrap">
            <label>Jenis</label>
            <select class="filter-inp" id="inputMode" onchange="setMode(this.value)">
              <option value="0">Semua</option>
              <option value="1">Outstanding</option>
            </select>
          </div>

          <!-- Periode (date range) -->
          <div class="filter-wrap">
            <label id="periodeLabel">Periode</label>
            <input type="date" class="filter-inp" id="inputDate1" value="{!! date('Y-m-d') !!}">
            <span class="filter-sep" id="dateSep">s/d</span>
            <input type="date" class="filter-inp" id="inputDate2" value="{!! date('Y-m-d') !!}">
          </div>

          {{-- Search --}}
          <div>
            <input class="search-inp" type="text" id="searchBox2" placeholder="Cari data..." oninput="applyFilters()" style="width:180px">
          </div>

          {{-- Otorisasi pindah ke modal "Filter Laporan" (lihat docs/new-filter-modal-ui-guide.md).
               Order By (No Bukti/Barang/Customer) jadi "Tampilan" switcher di bar tabel (diisi
               ReportTable.init({ views: ... }), lihat docs/new-slider-table-guide.md §Step 5) --
               keduanya sebelumnya dropdown toolbar yang di-comment total, tidak pernah terpasang. --}}

          <!-- Actions: search + filter + tampilkan + export -->
          <div class="action-group">
            {{-- Dibuka lewat plugin jQuery (Bootstrap 4), BUKAN data-bs-toggle (Bootstrap 5) —
                 lihat aturan dua-Bootstrap di new-design-all-report-guide.md §5.1. --}}
            <button class="btn-load" type="button" id="btnFilter" onclick="$('#modalFilter').modal('show')">
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

        <!-- Bar kolom tersembunyi + Tampilan (Order By) (diisi oleh report-table.js / ReportTable) -->
        <div id="rtBar"></div>

        <!-- TABLE — header satu tingkat (tanpa band), dibangun oleh ReportTable.headHtml() di
             renderRows() (drag-reorder + gear aktif seperti biasa). -->
        <div class="table-outer">
          <div class="table-wrap">
            <table class="tb" id="mainTable">
              <thead>
                <tr>
                  <th style="min-width:130px">No. Bukti</th>
                  <th style="min-width:90px">Tanggal</th>
                  <th style="min-width:130px">Customer</th>
                  <th style="min-width:80px">Kode Barang</th>
                  <th style="min-width:130px">Nama Barang</th>
                  <th class="num" style="min-width:10px">Sat</th>
                  <th class="num" style="min-width:10px">Qty</th>
                  <th class="num" style="min-width:10px">Qty PO</th>
                  <th>Keterangan</th>
                  <th>Otorisasi</th>
                  <th>Qty Close</th>
                  <th>PO</th>
                </tr>
              </thead>
              <tbody id="tableBody">
                <tr class="empty-row"><td colspan="11">Atur filter lalu klik <b>Tampilkan</b> untuk memuat laporan.</td></tr>
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
          <div class="rt-grid-2">
            {{-- Otorisasi & Status (PO) dipisah jadi dua field, sama seperti
                 reportmarketingso.blade.php (wrapOtorisasi/wrapStatus). Masing-masing selalu
                 punya opsi "Semua" -> DIHITUNG independen di badge saat ≠ '2' (lihat
                 updateFilterBadge()). --}}
            <div id="wrapOtorisasi">
              <label class="rt-field-label" for="modalOtorisasi">Otorisasi</label>
              <select class="rt-native" id="modalOtorisasi">
                <option value="2">Semua</option>
                <option value="1">Belum</option>
                <option value="0">Sudah</option>
              </select>
            </div>
            <div id="wrapStatus">
              <label class="rt-field-label" for="modalStatusPO">Status</label>
              <select class="rt-native" id="modalStatusPO">
                <option value="2">Semua</option>
                <option value="1">Belum PO</option>
                <option value="0">Sudah PO</option>
              </select>
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
  let globalDate1 = "{!! date('Y-m-d') !!}";
  let globalDate2 = "{!! date('Y-m-d') !!}";
  let globalOtorisasi = "2"; // default: Semua
  let globalStatusPO = "2";  // default: Semua
  let globalOrderBy = "N";   // default: Nomor Bukti
  let lastRows = [];         // hasil fetch terakhir (dipakai renderRows / export / search)
  let currentGroupby = 'NoBukti'; // groupby aktif untuk render ulang saat search

  // "0" = Non Outstanding (Sp_ReportPurchasingReqDet), "1" = Outstanding (Sp_reportoutStandingPR)
  let globalMode = "0";

  // Offset mode report Outstanding supaya kolom tersimpan (DBSIMPANHEADER, dikunci per
  // href+reportmode) tidak bentrok dengan mode Non Outstanding di href yang sama.
  const OUT_MODE_OFFSET = 20;

  const reportUrlPr  = "{{ url('laporanpengadaanpr_doReport') }}";
  const reportUrlOut = "{{ url('laporanpurchaseorderosp_doReport') }}";

  // Opsi switcher "Order By" per mode -- Sp_reportoutStandingPR tidak punya pengelompokan
  // Customer, jadi Outstanding cuma dapat dua opsi. Ditukar lewat cfg.options + refresh(),
  // BUKAN init() ulang (lihat setMode()).
  const VIEW_OPTIONS_PR = [
    { value: 'N', label: 'No Bukti', desc: 'Dikelompokkan per No Bukti' },
    { value: 'B', label: 'Barang',   desc: 'Dikelompokkan per Kode Barang' },
    { value: 'S', label: 'Customer', desc: 'Dikelompokkan per Customer' }
  ];
  const VIEW_OPTIONS_OUT = [
    { value: 'N', label: 'No Bukti', desc: 'Dikelompokkan per No Bukti' },
    { value: 'B', label: 'Barang',   desc: 'Dikelompokkan per Kode Barang' }
  ];
  const viewsCfg = {
    label: 'Order By',
    options: VIEW_OPTIONS_PR,
    get: function () { return globalOrderBy; },
    set: function (v) {
      setOrderBy(String(v));
      if (lastRows.length) { makeTable('REPORT'); }
    }
  };

  $(document).ready(function () {
      setOtorisasi(globalOtorisasi);
      setStatusPO(globalStatusPO);
      setOrderBy(globalOrderBy);

      // Menu lama boleh mengarahkan ke /laporanpengadaanpr?mode=out supaya langsung terbuka di
      // mode Outstanding (lihat rencana retire halaman lama).
      if ("{{ request('mode') }}" === "out") {
        $('#inputMode').val('1');
        setMode('1');
      }

      // Header tabel interaktif standar (drag-reorder + gear per kolom + bar "kolom
      // tersembunyi"/"Reset kolom"), plus "Tampilan" switcher untuk Order By -- halaman ini
      // SUDAH punya mode yang menukar susunan kolom (g_modeReport/setDefaultHeader(), lihat
      // makeTable()), cuma dropdown pemicunya dulu di-comment total dan tidak pernah terpasang.
      // "Order By" di sini menukar SUSUNAN KOLOM (gcart_header) & harus di-query ulang ke server
      // (inputOrd menentukan urutan sortir dari SP, dipakai deteksi pergantian grup di
      // renderRows()) -- makanya set() di bawah memanggil makeTable('REPORT'), bukan render().
      ReportTable.init({
        table: '#mainTable',
        bar: '#rtBar',
        onChange: function () { applyFilters(); },
        views: viewsCfg
      });
  });

  $('#modalFilter').on('show.bs.modal', function () {
    $("#modalOtorisasi").val(globalOtorisasi);
    $("#modalStatusPO").val(globalStatusPO);
    updateFilterBadge();
  });

  $('#modalFilter').on('change', 'select.rt-native', updateFilterBadge);

  function applyModalFilter() {
    setOtorisasi($("#modalOtorisasi").val());
    setStatusPO($("#modalStatusPO").val());
    $('#modalFilter').modal('hide');
    applyFilters(); // langsung update tabel dari data yg sudah dimuat, tanpa nunggu Tampilkan
  }

  /* ── FILTER MODAL ──
        Otorisasi & Status (PO) masing-masing punya opsi "Semua" -> DIHITUNG independen di
        badge saat nilainya ≠ '2' (aturan sama seperti Otorisasi/Status di reportmarketingso,
        lihat docs/new-filter-modal-ui-guide.md §5).
        Order By TIDAK dihitung -- itu jadi "Tampilan" switcher di bar tabel, bukan field
        filter modal, dan lagipula forced-choice (tidak ada opsi netral). ── */
  function updateFilterBadge() {
    let count = 0;
    if ($('#modalOtorisasi').val() !== '2') { count++; }
    if ($('#modalStatusPO').val() !== '2') { count++; }
    $('#filterBadge').text(count + ' aktif');
  }

  function resetAllFilters() {
    $('#modalOtorisasi').val('2');
    $('#modalStatusPO').val('2');
    updateFilterBadge();
  }

  // periode
  function showPeriode() {
    globalDate1 = $('#inputDate1').val();
    globalDate2 = $('#inputDate2').val();
    // alertify.success(`Periode: ${globalDate1} s/d ${globalDate2}`);
  }

  // otorisasi (nilai sebenarnya -- tidak ada UI toolbar terpisah untuk ini lagi, hanya modal Filter Laporan)
  function setOtorisasi(val) {
    globalOtorisasi = val;
  }

  // status PO (nilai sebenarnya -- disaring di klien setelah data dimuat, lihat makeTable())
  function setStatusPO(val) {
    globalStatusPO = val;
  }

  // Jenis laporan: "0" Non Outstanding (Sp_ReportPurchasingReqDet, tombol Filter aktif, dua
  // tanggal) atau "1" Outstanding (Sp_reportoutStandingPR, tombol Filter disembunyikan, HANYA
  // tanggal kedua -- lihat komentar di toolbar).
  function setMode(val) {
    globalMode = val;
    const isOut = (val === '1');

    // date1 tidak dikirim di mode Outstanding -- LaporanPurchaseOrderOSPController sudah
    // mematok tgl1 sendiri, jadi tidak perlu fallback apapun di sini.
    $('#inputDate1').toggle(!isOut);
    $('#dateSep').toggle(!isOut);
    $('#periodeLabel').text(isOut ? 'Sampai Tanggal' : 'Periode');

    // Modal filter jadi tidak terjangkau sama sekali di mode Outstanding (satu-satunya
    // pembukanya disembunyikan), jadi keduanya dipaksa balik ke Semua supaya tidak ada nilai
    // basi dari mode Non Outstanding yang diam-diam ikut terpakai saat menyaring hasil
    // Outstanding (Sp_reportoutStandingPR tidak mengembalikan NeedOtorisasi sama sekali).
    $('#btnFilter').toggle(!isOut);
    if (isOut) {
      $('#modalOtorisasi').val('2');
      setOtorisasi('2');
      $('#modalStatusPO').val('2');
      setStatusPO('2');
    }

    // Sp_reportoutStandingPR tidak punya pengelompokan Customer -- tukar opsi Order By, lalu
    // jatuhkan balik ke No Bukti kalau nilai sekarang sudah tidak ada di daftar baru.
    viewsCfg.options = isOut ? VIEW_OPTIONS_OUT : VIEW_OPTIONS_PR;
    if (!viewsCfg.options.some(function (o) { return o.value === globalOrderBy; })) {
      setOrderBy('N');
    }
    if (typeof ReportTable !== 'undefined' && ReportTable.refresh) {
      ReportTable.refresh();
    }

    // Ganti mode tidak langsung fetch ulang -- tabel dikosongkan, user tekan Tampilkan.
    lastRows = [];
    currentGroupby = 'NoBukti';
    $('#tableBody').html('<tr class="empty-row"><td>Atur filter lalu klik <b>Tampilkan</b> untuk memuat laporan.</td></tr>');
    $('#footerLabel').text('Belum ada data dimuat');

    // Segarkan susunan kolom (bar kolom tersembunyi + modal Customize) sesuai mode & Order By
    // yang berlaku sekarang, supaya tidak menampilkan sisa dari mode sebelumnya.
    resolveModeAndGroupby();
    if (typeof doSetHeader === 'function') { doSetHeader(g_modeReport); }
    if (typeof doShowCustomize === 'function') { doShowCustomize(); }

    updateFilterBadge();
  }

  /* -- EXPORT -- */
  function toggleExport() { document.getElementById('exportDrop').classList.toggle('open'); }
  document.addEventListener('click', function (e) {
    const wrap = document.getElementById('exportWrap');
    if (wrap && !wrap.contains(e.target)) { document.getElementById('exportDrop').classList.remove('open'); }
  });
  // doExport() sebelumnya TIDAK PERNAH terdefinisi di halaman ini (juga di reportpengadaanosp
  // sumbernya) -- ketiga opsi Export selalu error ReferenceError. Diperbaiki di sini dengan
  // trio doExport/exportDelimited/showToast standar (lihat reportlaporanmarketingspbhrgso).
  function doExport(fmt) {
    document.getElementById('exportDrop').classList.remove('open');
    if (fmt === 'Print') { window.print(); return; }
    exportDelimited(fmt);
  }
  function exportDelimited(fmt) {
    const cols = gcart_header.filter(c => c[2] === 1);
    const header = cols.map(c => c[1]);
    const body = (lastRows || []).map(r => cols.map(function (c) {
      const key = c[0], v = r[key];
      if (key === 'NeedOtorisasi') return (v == 1 ? 'Belum' : 'Sudah');
      if (key === 'StatusPO') return (currencyNormalizer(r.QNTPO || 0) > 0 ? 'Sudah' : 'Belum');
      if (c[3] === 'date') return format_date(v);
      if (c[3] === 'float' || c[3] === 'int') return currencyNormalizer(v);
      return (v == null ? '' : v);
    }));
    const rows = [header].concat(body);
    ReportExport.fromRows(rows, fmt, { customName: (globalMode === '1') ? 'OutstandingPR_' + (globalDate2 || '') : 'LaporanPengadaanPR_' + (globalDate1 || '') + '_' + (globalDate2 || '') });
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

  // order by (nilai sebenarnya -- UI-nya "Tampilan" switcher di #rtBar, lihat ReportTable.init() di atas)
  function setOrderBy(val) {
    globalOrderBy = val;
  }


  var modereport_nobukti = 0, modereport_barang = 1, modereport_customer = 2;
  var modereport_out_nobukti = 0, modereport_out_barang = 1;
  g_modeReport = modereport_nobukti;

  // Menentukan g_modeReport & groupby dari globalMode + globalOrderBy yang berlaku sekarang --
  // dipakai makeTable() (sebelum fetch) dan setMode() (supaya bar kolom & modal Customize
  // langsung ikut mode baru tanpa menunggu Tampilkan). Kedua sisi bernomor mulai dari 0, jadi
  // Outstanding digeser OUT_MODE_OFFSET supaya kolom tersimpan (DBSIMPANHEADER, dikunci per
  // href+reportmode) tidak bentrok dengan Non Outstanding di href yang sama.
  function resolveModeAndGroupby() {
    const isOut = (globalMode === '1');
    let groupby;

    if (isOut) {
      if (globalOrderBy == "N") {
        g_modeReport = modereport_out_nobukti + OUT_MODE_OFFSET;
        groupby = 'Nobukti';
      } else {
        g_modeReport = modereport_out_barang + OUT_MODE_OFFSET;
        groupby = 'kodebrg';
      }
    } else {
      if (globalOrderBy == "N") {
        g_modeReport = modereport_nobukti;
        groupby = 'NoBukti';
      } else if (globalOrderBy == "B") {
        g_modeReport = modereport_barang;
        groupby = 'KodeBrg';
      } else {
        g_modeReport = modereport_customer;
        groupby = 'NAMACUSTSUPP';
      }
    }

    return groupby;
  }

  // Dispatcher: kedua SP punya set kolom berbeda tapi penomoran mode yang SAMA (0=No
  // Bukti/Detail, 1=Barang/Rekap) -- tetap dipisah jadi dua fungsi, BUKAN digabung, supaya
  // g_modeReport (dengan offset di sisi Outstanding) tidak salah dibaca. Nama fungsi ini
  // ("setDefaultHeader") tetap dipertahankan karena masterreport2's doSetHeader() memanggilnya
  // lewat nama ini.
  function setDefaultHeader() {
    const isOut = (globalMode === '1');
    const base = isOut ? (g_modeReport - OUT_MODE_OFFSET) : g_modeReport;
    if (isOut) {
      setHeaderOut(base);
    } else {
      setHeaderPr(base);
    }
  }

  function setHeaderPr(base) {
    if (base == modereport_nobukti) {
      gcart_header = [
        ['NoBukti', 'No Bukti', 1, 'varchar', 0, 0],
        ['Tanggal', 'Tanggal', 1, 'date', 0, 0],
        // ['NAMACUSTSUPP', 'Customer', 1, 'varchar', 0, 0],
        ['KodeBrg', 'Kode Barang', 1, 'varchar', 0, 0],
        ['NamaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
        ['Satuan', 'Sat', 1, 'varchar', 0, 0],
        ['Qnt', 'Qty', 1, 'float', 1, 2],
        ['QntBatal', 'Qty Close', 1, 'float', 1, 2],
        ['QNTPO', 'Qty PO', 1, 'float', 1, 2],
        ['Keterangan', 'Keterangan', 1, 'varchar', 0, 0],
        ['NeedOtorisasi', 'Otorisasi', 1, 'varchar', 0, 0],
        ['StatusPO', 'PO', 1, 'varchar', 0, 0]
      ];
      gsum_issubtotal = 1; gsum_isgrandtotal = 0;

    } else if (base == modereport_barang){
      gcart_header = [
        ['NoBukti', 'No Bukti', 1, 'varchar', 0, 0],
        ['Tanggal', 'Tanggal', 1, 'date', 0, 0],
        // ['NAMACUSTSUPP', 'Customer', 1, 'varchar', 0, 0],
        ['KodeBrg', 'Kode Barang', 1, 'varchar', 0, 0],
        ['NamaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
        ['Satuan', 'Sat', 1, 'varchar', 0, 0],
        ['Qnt', 'Qty', 1, 'float', 1, 2],
        ['QntBatal', 'Qty Close', 1, 'float', 1, 2],
        ['QNTPO', 'Qty PO', 1, 'float', 1, 2],
        ['Keterangan', 'Keterangan', 1, 'varchar', 0, 0],
        ['NeedOtorisasi', 'Otorisasi', 1, 'varchar', 0, 0],
        ['StatusPO', 'PO', 1, 'varchar', 0, 0]
      ];
      gsum_issubtotal = 0; gsum_isgrandtotal = 0;

    } else {
      gcart_header = [
        ['NoBukti', 'No Bukti', 1, 'varchar', 0, 0],
        ['Tanggal', 'Tanggal', 1, 'date', 0, 0],
        // ['NAMACUSTSUPP', 'Customer', 1, 'varchar', 0, 0],
        ['KodeBrg', 'Kode Barang', 1, 'varchar', 0, 0],
        ['NamaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
        ['Satuan', 'Sat', 1, 'varchar', 0, 0],
        ['Qnt', 'Qty', 1, 'float', 1, 2],
        ['QntBatal', 'Qty Close', 1, 'float', 1, 2],
        ['QNTPO', 'Qty PO', 1, 'float', 1, 2],
        ['Keterangan', 'Keterangan', 1, 'varchar', 0, 0],
        ['NeedOtorisasi', 'Otorisasi', 1, 'varchar', 0, 0],
        ['StatusPO', 'PO', 1, 'varchar', 0, 0]
      ];
      gsum_issubtotal = 0; gsum_isgrandtotal = 1;
    }
  }

  // Kolom NYATA dari Sp_reportoutStandingPR, dipindah dari reportpengadaanosp.blade.php. Tidak
  // ada Keterangan/NeedOtorisasi/StatusPO -- proc ini tidak mengembalikannya sama sekali. Kedua
  // cabang identik di halaman sumbernya (proc selalu mengembalikan field yang sama apa pun
  // Ordr) -- tetap dipertahankan sebagai dua cabang supaya No Bukti & Barang masing-masing
  // punya slot kolom tersimpan sendiri.
  function setHeaderOut(base) {
    if (base === modereport_out_nobukti) {
      gcart_header = [
        ['Nobukti', 'No Bukti', 1, 'varchar', 0, 0],
        ['Tanggal', 'Tanggal', 1, 'date', 0, 0],
        ['NMDEP', 'Departement', 1, 'varchar', 0, 0],
        ['kodebrg', 'Kode Barang', 1, 'varchar', 0, 0],
        ['NamaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
        ['sat', 'Satuan', 1, 'varchar', 0, 0],
        ['QNTPR', 'QTYPR', 1, 'float', 1, 2],
        ['QntBatal', 'Qty Close', 1, 'float', 1, 2],
        ['QNTPO', 'QTYPO', 1, 'float', 1, 2],
        ['Qnt', 'Qty', 1, 'float', 1, 2]
      ];
      gsum_issubtotal = 1; gsum_isgrandtotal = 0;

    } else {
      gcart_header = [
        ['Nobukti', 'No Bukti', 1, 'varchar', 0, 0],
        ['Tanggal', 'Tanggal', 1, 'date', 0, 0],
        ['NMDEP', 'Departement', 1, 'varchar', 0, 0],
        ['kodebrg', 'Kode Barang', 1, 'varchar', 0, 0],
        ['NamaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
        ['sat', 'Satuan', 1, 'varchar', 0, 0],
        ['QNTPR', 'QTYPR', 1, 'float', 1, 2],
        ['QntBatal', 'Qty Close', 1, 'float', 1, 2],
        ['QNTPO', 'QTYPO', 1, 'float', 1, 2],
        ['Qnt', 'Qty', 1, 'float', 1, 2]
      ];
      gsum_issubtotal = 1; gsum_isgrandtotal = 0;
    }
  }

  function makeTable(_mode) {
    showPeriode();
    const isOut = (globalMode === '1');
    let _date1  = $("#inputDate1").val();
    let _date2  = $("#inputDate2").val();
    let input_order = globalOrderBy;

    const groupby = resolveModeAndGroupby();

    // Muat susunan kolom (gcart_header) untuk mode ini   termasuk hasil
    // kustomisasi user dari modal "Customize Table" (doShowFormCustomizeTable):
    // show/hide kolom + urutannya. Tabel styled di-render DARI gcart_header,
    // jadi pilihan kolom user langsung ikut tampil saat klik Tampilkan.
    if (typeof doSetHeader === 'function') {
      doSetHeader(g_modeReport);
    }

    let url, data;
    if (isOut) {
      // date1 sengaja tidak dikirim -- LaporanPurchaseOrderOSPController mematok tgl1 sendiri.
      // Tidak ada inputOto: Sp_reportoutStandingPR tidak punya parameter Otorisasi yang bisa
      // diatur dari sini (hardcode di controller).
      url  = reportUrlOut;
      data = {
        date2    : _date2,
        inputOrd : input_order,
      };
    } else {
      url  = reportUrlPr;
      data = {
        date1    : _date1,
        date2    : _date2,
        // Selalu ambil Semua dari sp -- Otorisasi & Status PO disaring di klien (lihat
        // renderFiltered()) supaya "Terapkan" di modal Filter bisa langsung memperbarui tabel
        // dari data yang sudah dimuat, tanpa fetch ulang ke server.
        inputOto : "2",
        inputOrd : input_order,
      };
    }

    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

    // Ambil data SEKALI, lalu render langsung ke tabel styled baru (#tableBody).
    $.ajax({
      url    : url,
      type   : 'get',
      data   : data,
      success: function (res) {
        lastRows = res || [];
        currentGroupby = groupby;        // simpan utk render ulang saat search
        $('#searchBox2').val('');        // reset kotak cari tiap muat data baru
        renderFiltered(lastRows);        // <-- terapkan filter Otorisasi/Status lalu render
      },
      error  : function () {
        lastRows = [];
        currentGroupby = groupby;
        renderRows([], groupby);
      }
    });

    // --- ENGINE LAMA (tabel #tabel pada masterreport2) DIMATIKAN ---
    // Baris di bawah inilah yang dulu menampilkan data ke tabel LAMA
    // (#showTableReport/#tabel) sekaligus melakukan pemanggilan data KEDUA.
    // Dikomentari supaya data hanya tampil di tabel styled baru di atas.
    // Aktifkan lagi kalau mau memunculkan tabel lama.
    // doMakeTable(_mode, groupby, data, "REPORT PENGADAAN PR", _date1, _date2);
  }

  // === RENDER KE TABEL STYLED (.tb-report #mainTable) ===
  // Kolom dibangun DINAMIS dari gcart_header (hanya kolom yang terlihat /
  // item[2]===1, sesuai urutan simpanan). Jadi hasil "Customize Table"
  // (show/hide + urutan kolom) langsung tampil. <thead> ditulis ulang tiap
  // render. Subtotal/Grand Total = jumlah kolom yang ditandai item[4]===1,
  // dikelompokkan per `groupby` (mengikuti gear per-kolom di modal Customize
  // Table, bukan daftar kolom hardcode -- kedua mode punya set total berbeda).
  // (Data sudah terurut dari proc sesuai inputOrd, jadi cukup deteksi pergantian
  // nilai grup. Jika semua kolom total disembunyikan, baris total tidak ditampilkan.)
  function renderRows(rows, groupby) {
    const cols  = gcart_header.filter(c => c[2] === 1); // kolom terlihat, terurut
    const keys  = cols.filter(c => c[4] === 1).map(c => c[0]); // kolom yang di-subtotal
    const thead = document.querySelector('#mainTable thead');
    const tbody = document.getElementById('tableBody');
    // Baris Subtotal & Grand Total mengikuti toggle di modal Customize Table
    // (#buttonSubtotal -> gsum_issubtotal, #buttonGrandtotal -> gsum_isgrandtotal).
    // gsum_* dimuat oleh doSetHeader() saat klik Tampilkan, jadi pilihan user
    // (sudah tersimpan) langsung berlaku. Total hanya tampil bila ada kolom total.
    const showSub   = keys.length > 0 && (gsum_issubtotal === 1);
    const showGrand = keys.length > 0 && (gsum_isgrandtotal === 1);

    // HEADER dinamis dari gcart_header — ReportTable.headHtml() (drag-reorder + gear per
    // kolom), bukan lagi <th> polos manual.
    thead.innerHTML = ReportTable.headHtml(cols);

    if (!rows || !rows.length) {
      tbody.innerHTML = '<tr class="empty-row"><td colspan="' + cols.length + '">Tidak ada data ditemukan.</td></tr>';
      document.getElementById('footerLabel').textContent = 'Tidak ada data';
      return;
    }

    let html = '', prev = null;
    let sub = {}, grand = {};
    keys.forEach(k => { sub[k] = 0; grand[k] = 0; });

    rows.forEach(function (r, i) {
      const now = r[groupby];

      // subtotal saat nilai grup berganti (kalau toggle Subtotal aktif)
      if (showSub && i !== 0 && prev !== now) {
        html += totalRowTotal('Subtotal', sub, cols, keys, 'subtotal-row');
        keys.forEach(k => { sub[k] = 0; });
      }

      keys.forEach(function (k) {
        const v = currencyNormalizer(r[k]);
        sub[k]   += v;
        grand[k] += v;
      });

      // satu sel per kolom terlihat, format menurut tipe (item[3]) & desimal (item[5])
      html += '<tr class="data-row">' + cols.map(function (c) {
        const key = c[0], type = c[3];
        // Status Otorisasi
        if (key === 'NeedOtorisasi') {
          return `<td> ${r.NeedOtorisasi == 1 ? '<span class="sp-badge is-inactive">Belum</span>' : '<span class="sp-badge is-active">Sudah</span>'} </td>`;
        }

        // Status PO
        if (key === 'StatusPO') {
          const qntpo = currencyNormalizer(r.QNTPO || 0);
          return `<td> ${qntpo > 0 ? '<span class="sp-badge is-active">Sudah</span>' : '<span class="sp-badge is-inactive">Belum</span>'} </td>`;
        }

        if (type === 'date') return '<td>' + format_date(r[key]) + '</td>';
        if (type === 'float' || type === 'int') return '<td class="num">' + format_number(currencyNormalizer(r[key]), c[5]) + '</td>';
        if (key === 'NamaBrg') return '<td style="white-space: nowrap;">' + nullToEmpty(r[key]) + '</td>';
        return '<td>' + nullToEmpty(r[key]) + '</td>';
      }).join('') + '</tr>';

      prev = now;
    });

    // subtotal grup terakhir + grand total   mengikuti toggle di modal
    if (showSub)   html += totalRowTotal('Subtotal', sub, cols, keys, 'subtotal-row');
    if (showGrand) html += totalRowTotal('GRAND TOTAL', grand, cols, keys, 'grand-total');

    tbody.innerHTML = html;
    document.getElementById('footerLabel').textContent = 'Menampilkan ' + rows.length + ' baris';
  }

  // Baris total: nilai di kolom yang di-subtotal (item[4]===1), label di kolom pertama non-total
  // yang masih terlihat, sel lain dikosongkan   mengikuti urutan kolom terlihat saat ini.
  function totalRowTotal(label, total, cols, keys, cls) {
    const labelIdx = cols.findIndex(c => keys.indexOf(c[0]) === -1);

    const tds = cols.map(function (c, idx) {
        if (keys.indexOf(c[0]) !== -1) {
            return '<td class="num">' + format_number(total[c[0]], c[5]) + '</td>';
        }
        if (idx === labelIdx)
            return '<td>' + label + '</td>';
        return '<td></td>';
    });

    return '<tr class="' + cls + '">' + tds.join('') + '</tr>';
  }

  // === PENCARIAN SISI-KLIEN ===
  // Menyaring data yang SUDAH dimuat (lastRows) berdasarkan teks pencarian, dicocokkan ke
  // semua kolom yang sedang terlihat, lalu terapkan filter Otorisasi/Status (renderFiltered)
  // dan render ulang tabel styled (renderRows menghitung ulang subtotal/grand total).
  function applyFilters() {
    if (!lastRows.length) return;        // belum ada data dimuat

    const term = ($('#searchBox2').val() || '').trim().toLowerCase();
    if (!term) { renderFiltered(lastRows); return; }   // kosong -> semua baris yg sudah dimuat

    const cols = gcart_header.filter(c => c[2] === 1); // kolom yang terlihat
    const searched = lastRows.filter(function (r) {
      return rowSearchText(r, cols).indexOf(term) !== -1;
    });

    renderFiltered(searched);
  }

  // Menyaring berdasarkan Otorisasi & Status PO (pilihan modal Filter Laporan) lalu render.
  // Dipanggil dari applyFilters() (search) dan langsung dari applyModalFilter() (Terapkan) --
  // keduanya menyaring dari lastRows yang sudah dimuat, jadi tidak perlu fetch ulang ke server.
  // Dilewati total di mode Outstanding -- Sp_reportoutStandingPR tidak mengembalikan
  // NeedOtorisasi sama sekali (setMode() juga sudah memaksa globalOtorisasi/globalStatusPO
  // balik ke '2', ini jaga-jaga kalau ada nilai basi).
  function renderFiltered(rows) {
    let filtered = rows;

    if (globalMode !== '1') {
      if (globalOtorisasi === '1') {
        filtered = filtered.filter(r => r.NeedOtorisasi == 1); // Belum
      } else if (globalOtorisasi === '0') {
        filtered = filtered.filter(r => r.NeedOtorisasi == 0); // Sudah
      }

      if (globalStatusPO === '1') {
        filtered = filtered.filter(r => currencyNormalizer(r.QNTPO || 0) == 0); // Belum PO
      } else if (globalStatusPO === '0') {
        filtered = filtered.filter(r => currencyNormalizer(r.QNTPO || 0) > 0);  // Sudah PO
      }
    }

    renderRows(filtered, currentGroupby);
  }

  // Gabungan teks satu baris dari kolom terlihat (tanggal pakai format tampil
  // dd/mm/yyyy) supaya pencarian cocok dengan apa yang user lihat di tabel.
  function rowSearchText(r, cols) {
    return cols.map(function (c) {
      const v = r[c[0]];
      if (c[3] === 'date') return format_date(v);
      return (v == null ? '' : String(v));
    }).join(' ').toLowerCase();
  }

  // getKolomFilter() milik ENGINE LAMA (modal "Filter Data" / doShowFormFilterData()), yang
  // TIDAK dipakai halaman ini (Filter Laporan sendiri sudah punya modal #modalFilter). Stub ini
  // cuma jaga-jaga supaya base script masterreport2 tidak error kalau memanggilnya.
  function getKolomFilter() { return []; }
</script>

@endsection
