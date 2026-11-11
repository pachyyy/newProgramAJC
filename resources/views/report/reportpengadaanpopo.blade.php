@extends('report.masterreport2')

<style>
    .tb-report .table-wrap { min-height: 10vh; }
</style>

@section('header2')
  <div class="tb-report main">
      <div class="content">

        <!-- TOOLBAR -->
        <div class="toolbar">
          {{-- <div>
            <div class="page-title">PO</div>
          </div> --}}

          <!-- Jenis laporan: Non Outstanding (ke Sp_ReportPODet, tombol Filter aktif --
               Otorisasi & Diterima bisa diatur, dua tanggal) atau Outstanding (ke
               Sp_reportoutStandingPOdet, tombol Filter disembunyikan -- proc ini tidak punya
               Otorisasi/Diterima -- HANYA tanggal kedua; LaporanPenerimaanGudangOSPOController
               mematok tgl1 sendiri). Order By (No Bukti/Barang/Supplier) jadi switcher "Tampilan"
               di #rtBar, SAMA di kedua mode. -->
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

          <!-- Actions: search + filter modal + tampilkan + export -->
          <div class="action-group">
            <input class="search-inp" type="text" id="searchBox2" placeholder="Cari data..." oninput="applyFilters()" style="width:180px">
            {{-- Dibuka lewat plugin jQuery (Bootstrap 4), BUKAN data-bs-toggle (Bootstrap 5).
                 Halaman ini memuat dua Bootstrap; jQuery dimuat SESUDAH bundle BS5, jadi
                 $.fn.modal dipegang BS4. applyModalFilter() menutup modal ini dengan
                 $('#modalFilter').modal('hide'), jadi pembukanya harus API yang sama. --}}
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

        <!-- Bar kolom tersembunyi (diisi oleh report-table.js / ReportTable) -->
        <div id="rtBar"></div>

        <!-- TABLE -->
        <div class="table-outer">
          <div class="table-wrap">
            <table class="tb" id="mainTable">
              <thead>
                <tr>
                  <th>No. Bukti</th>
                </tr>
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
          Seret judul kolom untuk mengurutkan. Klik <i class="bi bi-gear"></i> pada judul kolom untuk sembunyikan
          kolom atau atur desimal &amp; total.
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
                {{-- data-dismiss (BS4) = yang benar-benar menutup, karena modal ini dibuka lewat
                     $.fn.modal milik BS4. data-bs-dismiss dibiarkan untuk jaga-jaga. --}}
                <button type="button" class="btn-close" aria-label="Close" data-dismiss="modal" data-bs-dismiss="modal"
                    onclick="$('#modalFilter').modal('hide')"></button>
            </div>

            <div class="modal-body">

                <div class="rt-section">
                    {{-- Report (Detail/Rekap) & VALAS (IDR/VLS) dibuang -- keduanya sudah dipatok
                         Detail/IDR (lihat setHeaderPo()); controller tidak pernah membaca
                         inputDetOrRekap/inputValas sama sekali, jadi keduanya cuma toggle
                         tampilan klien yang tidak lagi dipakai. --}}
                    <div class="rt-grid-2">
                        <div>
                            <label class="rt-field-label" for="modalOtorisasi">Otorisasi</label>
                            <select class="rt-native" id="modalOtorisasi">
                                <option value="2">Semua</option>
                                <option value="1">Belum Otorisasi</option>
                                <option value="0">Sudah Otorisasi</option>
                            </select>
                        </div>
                        {{-- Status Diterima -- dipisah dari Otorisasi (dulu numpang di nilai 3-6
                             field yang sama, disaring lewat getStatusDiterima() setelah fetch,
                             sama seperti wrapOtorisasi/wrapStatus di reportpengadaanpr.blade.php). --}}
                        <div>
                            <label class="rt-field-label" for="modalDiterima">Diterima</label>
                            <select class="rt-native" id="modalDiterima">
                                <option value="Semua">Semua</option>
                                <option value="Menunggu">Menunggu</option>
                                <option value="Sebagian">Sebagian</option>
                                <option value="Diterima">Diterima</option>
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
  let globalDiterima = "Semua"; // default: Semua (Menunggu/Sebagian/Diterima)
  let globalOrderBy = "N";   // No Bukti/Barang/Supplier -- dipakai kedua mode, lihat resolveModeAndGroupby()
  let lastRows = [];         // hasil fetch terakhir (dipakai renderRows / export / search)
  let currentGroupby = 'NoBukti'; // groupby aktif untuk render ulang saat search

  // "0" = Non Outstanding (Sp_ReportPODet), "1" = Outstanding (Sp_reportoutStandingPOdet)
  let globalMode = "0";

  // Offset mode report Outstanding supaya kolom tersimpan (DBSIMPANHEADER, dikunci per
  // href+reportmode) tidak bentrok dengan mode Non Outstanding di href yang sama.
  const OUT_MODE_OFFSET = 20;

  const reportUrlPo  = "{{ url('laporanpurchaseorderpo_doReport') }}";
  const reportUrlOut = "{{ url('laporanpenerimaangudangospo_doReport') }}";

  // Report (Detail/Rekap) & VALAS (IDR/VLS) sudah dibuang (lihat modal filter) -- Non
  // Outstanding sekarang hanya py Detail/IDR, dikelompokkan per Order By (No Bukti/Barang/
  // Supplier) sama seperti Outstanding & seperti reportpengadaanpr.blade.php.
  var modereport_nobukti = 0, modereport_barang = 1, modereport_supplier = 2;
  var modereport_out_nobukti = 0, modereport_out_barang = 1, modereport_out_supplier = 2;

  g_modeReport = modereport_nobukti;

  // Switcher "Tampilan" (Order By) -- SAMA di kedua mode sekarang (No Bukti/Barang/Supplier),
  // ditukar lewat cfg.options + refresh(), BUKAN init() ulang.
  const VIEW_OPTIONS = [
    { value: 'N', label: 'No Bukti',  desc: 'Dikelompokkan per No Bukti' },
    { value: 'B', label: 'Barang',    desc: 'Dikelompokkan per Nama Barang' },
    { value: 'S', label: 'Supplier',  desc: 'Dikelompokkan per Nama Supplier' }
  ];
  const viewsCfg = {
    label: 'Order By',
    options: VIEW_OPTIONS,
    get: function () { return globalOrderBy; },
    set: function (v) {
      setOrderBy(String(v));
      if (lastRows.length) { makeTable('REPORT'); }
    }
  };

  $(document).ready(function () {
      setOtorisasi(globalOtorisasi);
      setDiterima(globalDiterima);
      setOrderBy(globalOrderBy);

      // Menu lama boleh mengarahkan ke /laporanpurchaseorderpo?mode=out supaya langsung terbuka
      // di mode Outstanding (lihat rencana retire halaman lama).
      if ("{{ request('mode') }}" === "out") {
        $('#inputMode').val('1');
        setMode('1');
      }

      setDefaultHeader();
      doSetHeader(g_modeReport);
      doShowCustomize();

      // Header tabel interaktif: drag-reorder + gear (sembunyikan/desimal/total) + bar
      // "Reset kolom"/kolom tersembunyi, plus switcher "Tampilan" (Order By) yang sekarang
      // sama di kedua mode (lihat viewsCfg). Otorisasi & Diterima tetap di modal Filter
      // sebagai dua field independen (lihat wrapOtorisasi/wrapStatus di reportpengadaanpr.blade.php).
      ReportTable.init({
        table: '#mainTable',
        bar: '#rtBar',
        onChange: function () {
          if (lastRows.length) { applyFilters(); } else { renderRows([], currentGroupby); }
        },
        views: viewsCfg
      });
  });

  $('#modalFilter').on('show.bs.modal', function () {
    $("#modalOtorisasi").val(globalOtorisasi);
    $("#modalDiterima").val(globalDiterima);
    updateFilterBadge();
  });

  $('#modalFilter').on('change', 'select.rt-native', updateFilterBadge);

  function updateFilterBadge() {
    let count = 0;
    if ($('#modalOtorisasi').val() !== '2') { count++; }
    if ($('#modalDiterima').val() !== 'Semua') { count++; }
    $('#filterBadge').text(count + ' aktif');
  }

  function resetAllFilters() {
    $('#modalOtorisasi').val('2');
    $('#modalDiterima').val('Semua');
    updateFilterBadge();
  }

  function applyModalFilter() {

    setOtorisasi($("#modalOtorisasi").val());
    setDiterima($("#modalDiterima").val());

    $('#modalFilter').modal('hide');
  }

  // periode
  function showPeriode() {
    globalDate1 = $('#inputDate1').val();
    globalDate2 = $('#inputDate2').val();
  }

  // otorisasi
  function setOtorisasi(val) {
    globalOtorisasi = val;
  }

  // status diterima -- disaring di klien setelah fetch (lihat makeTable()), Sp_ReportPODet
  // tidak punya parameter untuk ini
  function setDiterima(val) {
    globalDiterima = val;
  }

  // order by (UI-nya switcher "Tampilan" di #rtBar) -- dipakai kedua mode
  function setOrderBy(val) {
    globalOrderBy = val;
  }

  // Jenis laporan: "0" Non Outstanding (Sp_ReportPODet, tombol Filter aktif, dua tanggal) atau
  // "1" Outstanding (Sp_reportoutStandingPOdet, tombol Filter disembunyikan, HANYA tanggal
  // kedua -- lihat komentar di toolbar).
  function setMode(val) {
    globalMode = val;
    const isOut = (val === '1');

    // date1 tidak dikirim di mode Outstanding -- LaporanPenerimaanGudangOSPOController sudah
    // mematok tgl1 sendiri, jadi tidak perlu fallback apapun di sini.
    $('#inputDate1').toggle(!isOut);
    $('#dateSep').toggle(!isOut);
    $('#periodeLabel').text(isOut ? 'Sampai Tanggal' : 'Periode');

    // Modal filter jadi tidak terjangkau sama sekali di mode Outstanding (satu-satunya
    // pembukanya disembunyikan), jadi Otorisasi/Diterima dipaksa balik ke default supaya tidak
    // ada nilai basi dari mode Non Outstanding yang diam-diam ikut terpakai (Sp_reportoutStandingPOdet
    // tidak mengembalikan NeedOtorisasi/Qnt/qntLPB/QntBatal sama sekali).
    $('#btnFilter').toggle(!isOut);
    if (isOut) {
      $('#modalOtorisasi').val('2');
      setOtorisasi('2');
      $('#modalDiterima').val('Semua');
      setDiterima('Semua');
    }

    // Order By (No Bukti/Barang/Supplier) sama di kedua mode -- cuma segarkan bar switcher.
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
  // doExport() sebelumnya HANYA menangani Print -- XLSX/CSV tidak melakukan apa-apa karena
  // exportDelimited() belum pernah ada di halaman ini. Diperbaiki di sini dengan trio
  // doExport/exportDelimited standar (lihat reportlaporanmarketingspbhrgso).
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
      if (key === 'DiTerima') return getStatusDiterima(r);
      if (c[3] === 'date') return format_date(v);
      if (c[3] === 'float' || c[3] === 'int') return currencyNormalizer(v);
      return (v == null ? '' : v);
    }));
    const rows = [header].concat(body);
    ReportExport.fromRows(rows, fmt, { customName: (globalMode === '1') ? 'OutstandingPO_' + (globalDate2 || '') : 'LaporanPO_' + (globalDate1 || '') + '_' + (globalDate2 || '') });
    showToast('📄', 'Data diekspor sebagai ' + fmt);
  }

  // Menentukan g_modeReport & groupby dari globalMode + globalOrderBy yang berlaku sekarang --
  // dipakai makeTable() (sebelum fetch) dan setMode() (supaya bar kolom & modal Customize
  // langsung ikut mode baru tanpa menunggu Tampilkan). Kedua sisi bernomor mulai dari 0, jadi
  // Outstanding digeser OUT_MODE_OFFSET supaya kolom tersimpan (DBSIMPANHEADER, dikunci per
  // href+reportmode) tidak bentrok dengan Non Outstanding di href yang sama.
  function resolveModeAndGroupby() {
    if (globalMode === '1') {
      if (globalOrderBy == "N") {
        g_modeReport = modereport_out_nobukti + OUT_MODE_OFFSET;
        return 'NoBukti';
      } else if (globalOrderBy == "B") {
        g_modeReport = modereport_out_barang + OUT_MODE_OFFSET;
        return 'NamaBrg';
      } else {
        g_modeReport = modereport_out_supplier + OUT_MODE_OFFSET;
        return 'NAMACUSTSUPP';
      }
    }

    // Non Outstanding: groupby & susunan kolom berubah sesuai Order By, sama seperti
    // reportpengadaanpr.blade.php.
    if (globalOrderBy == "N") {
      g_modeReport = modereport_nobukti;
      return 'NoBukti';
    } else if (globalOrderBy == "B") {
      g_modeReport = modereport_barang;
      return 'NamaBrg';
    } else {
      g_modeReport = modereport_supplier;
      return 'NAMACUSTSUPP';
    }
  }

  // Dispatcher: kedua SP punya set kolom yang berbeda total meski penomoran mode-nya sama-sama
  // 0-2 (POPO & Outstanding masing-masing punya numbering sendiri) -- tetap dipisah jadi dua fungsi, BUKAN
  // digabung, supaya g_modeReport (dengan offset di sisi Outstanding) tidak salah dibaca. Nama
  // fungsi ini ("setDefaultHeader") tetap dipertahankan karena masterreport2's doSetHeader()
  // memanggilnya lewat nama ini.
  function setDefaultHeader() {
    const isOut = (globalMode === '1');
    const base = isOut ? (g_modeReport - OUT_MODE_OFFSET) : g_modeReport;
    if (isOut) {
      setHeaderOut(base);
    } else {
      setHeaderPo(base);
    }
  }

  // Report (Detail/Rekap) & VALAS (IDR/VLS) dibuang -- selalu Detail/IDR sekarang. Tiga cabang
  // di bawah dikelompokkan per Order By (No Bukti/Barang/Supplier), sama seperti setHeaderPr()
  // di reportpengadaanpr.blade.php: Barang naikkan Nama Barang jadi kolom pertama & matikan
  // kedua total; Supplier pertahankan susunan kolom tapi cuma tampilkan Grand Total.
  function setHeaderPo(base) {
    if (base == modereport_nobukti) {
      gcart_header = [
        ['NoBukti', 'No Bukti', 1, 'varchar', 0, 0],
        ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
        ['NAMACUSTSUPP', 'Nama Supplier', 1, 'varchar', 0, 0],
        ['NamaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
        ['Satuan', 'Sat', 1, 'varchar', 0, 0],
        ['Qnt', 'Qty', 1, 'float', 1, 2],
        ['QntBatal', 'Qty Batal', 1, 'float', 1, 2],
        ['HARGA', 'Harga', 1, 'float', 1, 2],
        ['KODEVLS', 'VLS', 1, 'varchar', 0, 0],
        ['DISCP', 'Disc', 1, 'float', 1, 2],
        ['NDPP', 'DPP', 1, 'float', 1, 2],
        ['NPPN', 'PPN', 1, 'float', 1, 2],
        ['TotalIDR', 'Total', 1, 'float', 1, 2],
        ['NeedOtorisasi', 'Otorisasi', 1, 'varchar', 0, 0],
        ['DiTerima', 'Di Terima', 1, 'varchar', 0, 0]
      ];
      gsum_issubtotal = 1; gsum_isgrandtotal = 1;

    } else if (base == modereport_barang) {
      gcart_header = [
        ['NoBukti', 'No Bukti', 1, 'varchar', 0, 0],
        ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
        ['NAMACUSTSUPP', 'Nama Supplier', 1, 'varchar', 0, 0],
        ['NamaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
        ['Satuan', 'Sat', 1, 'varchar', 0, 0],
        ['Qnt', 'Qty', 1, 'float', 1, 2],
        ['QntBatal', 'Qty Batal', 1, 'float', 1, 2],
        ['HARGA', 'Harga', 1, 'float', 1, 2],
        ['KODEVLS', 'VLS', 1, 'varchar', 0, 0],
        ['DISCP', 'Disc', 1, 'float', 1, 2],
        ['NDPP', 'DPP', 1, 'float', 1, 2],
        ['NPPN', 'PPN', 1, 'float', 1, 2],
        ['TotalIDR', 'Total', 1, 'float', 1, 2],
        ['NeedOtorisasi', 'Otorisasi', 1, 'varchar', 0, 0],
        ['DiTerima', 'Di Terima', 1, 'varchar', 0, 0]
      ];
      gsum_issubtotal = 1; gsum_isgrandtotal = 0;

    } else {
      gcart_header = [
        ['NoBukti', 'No Bukti', 1, 'varchar', 0, 0],
        ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
        ['NAMACUSTSUPP', 'Nama Supplier', 1, 'varchar', 0, 0],
        ['NamaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
        ['Satuan', 'Sat', 1, 'varchar', 0, 0],
        ['Qnt', 'Qty', 1, 'float', 1, 2],
        ['QntBatal', 'Qty Batal', 1, 'float', 1, 2],
        ['HARGA', 'Harga', 1, 'float', 1, 2],
        ['KODEVLS', 'VLS', 1, 'varchar', 0, 0],
        ['DISCP', 'Disc', 1, 'float', 1, 2],
        ['NDPP', 'DPP', 1, 'float', 1, 2],
        ['NPPN', 'PPN', 1, 'float', 1, 2],
        ['TotalIDR', 'Total', 1, 'float', 1, 2],
        ['NeedOtorisasi', 'Otorisasi', 1, 'varchar', 0, 0],
        ['DiTerima', 'Di Terima', 1, 'varchar', 0, 0]
      ];
      gsum_issubtotal = 1; gsum_isgrandtotal = 1;
    }
  }

  // Kolom NYATA dari Sp_reportoutStandingPOdet, dipindah dari
  // reportpengadaanpgoutstandingpo.blade.php -- HANYA tiga cabang Detail (per Order By). Tiga
  // cabang Rekap dari halaman sumber (KOdeCustSupp/OS) DIBUANG di sini: proc ini tidak pernah
  // mengembalikan kolom itu & controllernya tidak meneruskan inputDetOrRekap sama sekali --
  // Rekap sudah mati sejak sebelum merge ini (lihat setMode()).
  function setHeaderOut(base) {
    if (base === modereport_out_nobukti) {
      gcart_header = [
        ['NoBukti', 'No Bukti', 1, 'varchar', 0, 0],
        ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
        ['NAMACUSTSUPP', 'Nama Supplier', 1, 'varchar', 0, 0],
        ['KodeBrg', 'Kode Barang', 1, 'varchar', 0, 0],
        ['NamaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
        ['NOPOCUST', 'PO Cust', 1, 'varchar', 0, 0],
        ['NAMACUSTOMER', 'Nama Customer', 1, 'varchar', 0, 0],
        ['Satuan', 'Satuan', 1, 'varchar', 0, 0],
        ['QntPO', 'Qty PO', 1, 'float', 1, 0],
        ['QntBatal', 'Qty Batal', 1, 'float', 1, 0],
        ['QntBeli', 'Qty Terima', 1, 'float', 1, 0],
        ['QNTOS', 'Qty Sisa', 1, 'float', 1, 0],
        ['LeadTime', 'Lead Time', 1, 'varchar', 0, 0],
        ['HARGA', 'Harga', 1, 'float', 1, 2],
        ['NDPP', 'DPP', 1, 'float', 1, 2],
        ['NNET', 'Total', 1, 'float', 1, 2]
      ];
      gsum_issubtotal = 1; gsum_isgrandtotal = 1;

    } else if (base === modereport_out_barang) {
      gcart_header = [
        ['NoBukti', 'No Bukti', 1, 'varchar', 0, 0],
        ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
        ['NAMACUSTSUPP', 'Nama Supplier', 1, 'varchar', 0, 0],
        ['KodeBrg', 'Kode Barang', 1, 'varchar', 0, 0],
        ['NamaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
        ['NOPOCUST', 'PO Cust', 1, 'varchar', 0, 0],
        ['NAMACUSTOMER', 'Nama Customer', 1, 'varchar', 0, 0],
        ['Satuan', 'Satuan', 1, 'varchar', 0, 0],
        ['QntPO', 'Qty PO', 1, 'float', 1, 0],
        ['QntBatal', 'Qty Batal', 1, 'float', 1, 0],
        ['QntBeli', 'Qty Terima', 1, 'float', 1, 0],
        ['QNTOS', 'Qty Sisa', 1, 'float', 1, 0],
        ['LeadTime', 'Lead Time', 1, 'varchar', 0, 0],
        ['HARGA', 'Harga', 1, 'float', 1, 2],
        ['NDPP', 'DPP', 1, 'float', 1, 2],
        ['NNET', 'Total', 1, 'float', 1, 2]
      ];
      gsum_issubtotal = 1; gsum_isgrandtotal = 0;

    } else {
      gcart_header = [
        ['NoBukti', 'No Bukti', 1, 'varchar', 0, 0],
        ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
        ['NAMACUSTSUPP', 'Nama Supplier', 1, 'varchar', 0, 0],
        ['KodeBrg', 'Kode Barang', 1, 'varchar', 0, 0],
        ['NamaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
        ['NOPOCUST', 'PO Cust', 1, 'varchar', 0, 0],
        ['NAMACUSTOMER', 'Nama Customer', 1, 'varchar', 0, 0],
        ['Satuan', 'Satuan', 1, 'varchar', 0, 0],
        ['QntPO', 'Qty PO', 1, 'float', 1, 0],
        ['QntBatal', 'Qty Batal', 1, 'float', 1, 0],
        ['QntBeli', 'Qty Terima', 1, 'float', 1, 0],
        ['QNTOS', 'Qty Sisa', 1, 'float', 1, 0],
        ['LeadTime', 'Lead Time', 1, 'varchar', 0, 0],
        ['HARGA', 'Harga', 1, 'float', 1, 2],
        ['NDPP', 'DPP', 1, 'float', 1, 2],
        ['NNET', 'Total', 1, 'float', 1, 2]
      ];
      gsum_issubtotal = 1; gsum_isgrandtotal = 0;
    }
  }

  function makeTable(_mode) {
    showPeriode();
    const isOut = (globalMode === '1');
    let _date1 = $("#inputDate1").val();
    let _date2 = $("#inputDate2").val();

    const groupby = resolveModeAndGroupby();

    if (typeof doSetHeader === 'function') {
      doSetHeader(g_modeReport);
    }

    let url, data;
    if (isOut) {
      // date1 sengaja tidak dikirim -- LaporanPenerimaanGudangOSPOController mematok tgl1
      // sendiri. Tidak ada inputOto: Sp_reportoutStandingPOdet tidak punya parameter untuk itu.
      url  = reportUrlOut;
      data = {
        date2    : _date2,
        inputOrd : globalOrderBy,
      };
    } else {
      url  = reportUrlPo;
      data = {
        date1: _date1,
        date2: _date2,
        inputOto: globalOtorisasi,
        inputOrd: globalOrderBy
      };
    }

    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

    // Ambil data SEKALI, lalu render langsung ke tabel styled baru (#tableBody).
    $.ajax({
      url    : url,
      type   : 'get',
      data   : data,
      success: function (res) {
      let rows = res || [];

        // Filter status Diterima (Menunggu/Sebagian/Diterima) HANYA berlaku Non Outstanding --
        // Sp_reportoutStandingPOdet tidak mengembalikan Qnt/qntLPB/QntBatal sama sekali
        // (setMode() juga sudah memaksa globalDiterima balik ke 'Semua', ini jaga-jaga kalau
        // ada nilai basi). Tidak dikirim ke server -- Sp_ReportPODet tidak punya parameter
        // untuk ini, jadi disaring di klien dari hasil fetch penuh.
        if (!isOut && globalDiterima !== 'Semua') {
          rows = rows.filter(r => getStatusDiterima(r) === globalDiterima);
        }

        lastRows = rows;
        currentGroupby = groupby;
        $('#searchBox2').val('');
        renderRows(lastRows, groupby);
      },
      error  : function (xhr) {
        console.error(url + ' gagal:', xhr.status, xhr.responseText);
        showToast('⚠️', 'Gagal memuat data (' + xhr.status + ')');
        lastRows = [];
        currentGroupby = groupby;
        renderRows(lastRows, groupby);
      }
    });
  }

  function getStatusDiterima (r) {
    const qnt      = currencyNormalizer(r.Qnt);
    const qntLPB   = currencyNormalizer(r.qntLPB);
    const qntBatal = currencyNormalizer(r.QntBatal);

    // Batal
    if (qnt === 0 && qntLPB === 0 && qntBatal > 0) {
      return "Batal";
    }

    // Diterima (habis)
    if (
      (qnt === qntLPB && qnt > 0) ||
      (qnt === 0 && qntLPB > 0)
    ) {
      return "Diterima";
    }

    // Menunggu
    if (qnt > 0 && qntLPB === 0) {
      return "Menunggu";
    }

    // Sebagian
    if (qnt > qntLPB && qntLPB > 0) {
      return "Sebagian";
    }
    return "";
  }

  // === RENDER KE TABEL STYLED (.tb-report #mainTable) ===
  // Kolom dibangun DINAMIS dari gcart_header (hanya kolom yang terlihat /
  // item[2]===1, sesuai urutan simpanan). Jadi hasil "Customize Table"
  // (show/hide + urutan kolom) langsung tampil. <thead> dibangun oleh
  // ReportTable.headHtml() (drag-reorder + gear). Subtotal/Grand Total =
  // jumlah tiap kolom yang ditandai total (item[4]===1), dikelompokkan per
  // `groupby`. (Data sudah terurut dari proc sesuai inputOrd, jadi cukup
  // deteksi pergantian nilai grup.)
  function renderRows(rows, groupby) {
    const cols  = gcart_header.filter(c => c[2] === 1); // kolom terlihat, terurut
    const keys  = cols.filter(c => c[4] === 1).map(c => c[0]); // kolom yang di-subtotal
    const thead = document.querySelector('#mainTable thead');
    const tbody = document.getElementById('tableBody');
    // Baris total tampil kalau ADA kolom yang ditandai total (item[4]===1) -- generik,
    // menggantikan gate lama yang cuma cek DPP (NDPP/Ndppusd) secara spesifik. Kolom Rekap
    // Outstanding (QntPO/NNET) tidak pernah punya DPP sama sekali, jadi gate lama akan
    // menyembunyikan GRAND TOTAL-nya total; gate generik ini dipakai sama di kedua mode.
    // Baris Subtotal & Grand Total mengikuti toggle di modal Customize Table
    // (#buttonSubtotal -> gsum_issubtotal, #buttonGrandtotal -> gsum_isgrandtotal).
    // gsum_* dimuat oleh doSetHeader() saat klik Tampilkan, jadi pilihan user
    // (sudah tersimpan) langsung berlaku.
    const showSub   = keys.length > 0 && (gsum_issubtotal === 1);
    const showGrand = keys.length > 0 && (gsum_isgrandtotal === 1);

    // HEADER dinamis — dibangun report-table.js (ReportTable) supaya kolom bisa diseret
    // untuk diurutkan & punya menu roda gigi (sembunyikan / desimal / total).
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
        sub[k] += v; grand[k] += v;
      });

      // satu sel per kolom terlihat, format menurut tipe (item[3]) & desimal (item[5])
      html += '<tr class="data-row">' + cols.map(function (c) {
      const key = c[0], type = c[3];

      // Status Otorisasi
      if (key === 'NeedOtorisasi') {
        return `<td> ${r.NeedOtorisasi == 1 ? '<span class="sp-badge is-inactive">Belum</span>' : '<span class="sp-badge is-active">Sudah</span>'} </td>`;
      }

      // Status diterima
      if (key === 'DiTerima') {

      const status = getStatusDiterima(r);

      switch (status) {
        case 'Diterima':
          return '<td><span class="sp-badge is-active">Diterima</span></td>';

        case 'Menunggu':
          return '<td><span class="sp-badge is-user">Menunggu</span></td>';

        case 'Sebagian':
          return '<td><span class="sp-badge is-supervisor">Sebagian</span></td>';

        case 'Batal':
          return '<td><span class="sp-badge is-inactive">Batal</span></td>';

        default:
          return '<td></td>';
        }
      }

        if (type === 'date') return '<td>' + format_date(r[key]) + '</td>';
        if (type === 'float' || type === 'int') return '<td class="num">' + format_number(currencyNormalizer(r[key]), c[5]) + '</td>';
        if (key === 'NamaBrg') return '<td style="white-space: nowrap;">' + nullToEmpty(r[key]) + '</td>';
        if (key === 'NAMACUSTSUPP') return '<td style="white-space: nowrap;">' + nullToEmpty(r[key]) + '</td>';
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

  // Baris total: nilai di kolom yang di-subtotal (item[4]===1), label di kolom pertama
  // non-total yang masih terlihat, sel lain dikosongkan.
  function totalRowTotal(label, total, cols, keys, cls) {
    const labelIdx = cols.findIndex(c => keys.indexOf(c[0]) === -1);

    const tds = cols.map(function (c, idx) {
      if (keys.indexOf(c[0]) !== -1) {
        return '<td class="num">' + format_number(total[c[0]], c[5]) + '</td>';
      }
      if (idx === labelIdx) return '<td>' + label + '</td>';
      return '<td></td>';
    });

    return '<tr class="' + cls + '">' + tds.join('') + '</tr>';
  }

  // === PENCARIAN SISI-KLIEN ===
  // Menyaring data yang SUDAH dimuat (lastRows) berdasarkan teks pencarian,
  // dicocokkan ke semua kolom yang sedang terlihat, lalu render ulang tabel
  // styled (renderRows menghitung ulang subtotal/grand total untuk hasil saring).
  function applyFilters() {
    if (!lastRows.length) return;        // belum ada data dimuat

    const term = ($('#searchBox2').val() || '').trim().toLowerCase();
    if (!term) { renderRows(lastRows, currentGroupby); return; }   // kosong -> tampilkan semua

    const cols = gcart_header.filter(c => c[2] === 1); // kolom yang terlihat
    const filtered = lastRows.filter(function (r) {
      return rowSearchText(r, cols).indexOf(term) !== -1;
    });

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

  /* -- TOAST -- */
  function showToast(icon, msg) {
    const t = document.getElementById('toast');
    document.getElementById('ti').textContent = icon;
    document.getElementById('tm').textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
  }

</script>

@endsection
