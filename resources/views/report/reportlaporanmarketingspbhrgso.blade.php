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
        <div class="page-title">Report Marketing - SPB Harga SO</div>
      </div> --}}

      <!-- Jenis laporan: Non Outstanding (ke Sp_ReportSPBDet, tombol Filter aktif -- Otorisasi,
           Tgl. Terima & Status semua bisa diatur, kedua tanggal dikirim) atau Outstanding (ke
           Sp_ReportOutSpbDet, tombol Filter disembunyikan -- proc ini tidak punya kolom Status
           & tidak ada UI untuk mengubah Otorisasi/Tgl. Terima di mode ini, sama seperti halaman
           lama; Otorisasi dipaksa balik ke Semua saat masuk mode ini. Hanya satu tanggal yang
           dipakai di mode ini, diambil dari #inputDate2 (bukan #inputDate1) -- Sp_ReportOutSpbDet
           cuma memfilter `Tanggal <= @tgl1` dan mengabaikan @tgl2 sama sekali (apa pun @Ordr-nya),
           jadi #inputDate1 disembunyikan & tidak dikirim; LaporanMarketingOutSPBHrgSoController
           TIDAK diubah, nilai #inputDate2 tetap dikirim sebagai request key `date1` apa adanya
           -- lihat makeTable()). -->
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

      <!-- Actions: search + filter modal + tampilkan + export -->
      <div class="action-group">
        {{-- Dibuka lewat plugin jQuery (Bootstrap 4), BUKAN data-bs-toggle (Bootstrap 5).
             Halaman ini memuat dua Bootstrap; jQuery dimuat SESUDAH bundle BS5, jadi
             $.fn.modal dipegang BS5. JS di bawah menutup modal ini dengan
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

    <!-- Bar kolom tersembunyi + Order By (diisi oleh report-table.js / ReportTable) -->
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
          {{-- Report (Detail/Rekap) dihapus (dulu switcher "Tampilan" di bar atas tabel).
               Urutkan juga tidak ada di sini: sudah jadi switcher "Order By" di bar atas
               tabel (ReportTable.init views), lihat setOrderBy(). --}}
          <div class="rt-grid-2">
            <div>
              <label class="rt-field-label" for="modalOtorisasi">Otorisasi</label>
              {{-- Nilai = nilai kolom NeedOtorisasi apa adanya (dipakai SP): 0 = semua level
                   otorisasi sudah lengkap (Sudah), 1 = masih butuh otorisasi (Belum), 2 = semua. --}}
              <select class="rt-native" id="modalOtorisasi">
                <option value="2">Semua</option>
                <option value="0">Sudah Otorisasi</option>
                <option value="1">Belum Otorisasi</option>
              </select>
            </div>
            <div id="wrapTerima">
              <label class="rt-field-label" for="modalTerima">Tgl. Terima</label>
              <select class="rt-native" id="modalTerima">
                <option value="2">Semua</option>
                <option value="0">Tgl. Terima</option>
                <option value="1">Non Tgl. Terima</option>
              </select>
            </div>
            {{-- Status kirim: filter sisi-klien murni (Sp_ReportSPBDet tidak menerimanya, dan
                 Sp_ReportOutSpbDet tidak mengembalikan kolomnya sama sekali).
                 0 = Terkirim, 1 = Belum, 2 = Semua. --}}
            <div id="wrapStatus">
              <label class="rt-field-label" for="modalOutstanding">Status</label>
              <select class="rt-native" id="modalOutstanding">
                <option value="2">Semua</option>
                <option value="0">Terkirim</option>
                <option value="1">Belum</option>
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
  let globalTerima = "2";    // default: Semua
  let globalOutstanding = "2"; // default: Semua
  let globalOrderBy = "N";   // default: Nomor Bukti

  let lastRows = [];        // hasil fetch terakhir (dipakai render / export / search)
  let currentGroupby = 'NOBUKTI'; // groupby aktif untuk render ulang saat search

  // "0" = Non Outstanding (Sp_ReportSPBDet), "1" = Outstanding (Sp_ReportOutSpbDet)
  let globalMode = "0";

  // Offset mode report Outstanding supaya kolom tersimpan (DBSIMPANHEADER, dikunci per
  // href+reportmode) tidak bentrok dengan mode Non Outstanding di href yang sama.
  const OUT_MODE_OFFSET = 20;

  const reportUrlHrg = "{{ url('laporanmarketingspbhrgso_doReport') }}";
  const reportUrlOut = "{{ url('laporanmarketingoutspbhrgso_doReport') }}";

  // Order By: N/B/C, sama di kedua mode (dikonfirmasi manual terhadap Sp_ReportSPBDet &
  // Sp_ReportOutSpbDet, bukan ditebak). Kolom yang ditampilkan TIDAK berubah per ordering
  // (lihat setHeaderHrg()/setHeaderOut()) -- hanya groupby/subtotal & urutan SP yang berubah.
  const ORDER_OPTIONS = [
    { value: 'N', label: 'Nomor Bukti', desc: 'Dikelompokkan per No Bukti' },
    { value: 'B', label: 'Nomor Barang', desc: 'Dikelompokkan per Barang' },
    { value: 'C', label: 'Nomor Customer', desc: 'Dikelompokkan per Customer' },
  ];
  let viewsCfg = {
    label: 'Order By',
    options: ORDER_OPTIONS,
    get: function() { return globalOrderBy; },
    set: function(v) {
      setOrderBy(String(v));
      if (lastRows.length) { makeTable('REPORT'); } // re-fetch: inputOrd adalah parameter SP
    }
  };

  $(document).ready(function() {
    setOtorisasi(globalOtorisasi);
    setTerima(globalTerima);
    setOutstanding(globalOutstanding);
    setOrderBy(globalOrderBy);
    showPeriode();

    // Menu lama boleh mengarahkan ke /laporanmarketingspbhrgso?mode=out supaya langsung
    // terbuka di mode Outstanding (lihat rencana retire halaman lama).
    if ("{{ request('mode') }}" === "out") {
      $('#inputMode').val('1');
      setMode('1');
    }

    setDefaultHeader();

    // Switcher "Order By" di #rtBar (Detail/Rekap sudah dihapus sebelumnya).
    ReportTable.init({
      table: '#mainTable',
      bar: '#rtBar',
      onChange: render,
      views: viewsCfg
    });
  });

  // periode
  function showPeriode() {
    globalDate1 = $('#inputDate1').val();
    globalDate2 = $('#inputDate2').val();
  }

  // otorisasi / tgl. terima: filter query, dibaca langsung oleh makeTable()
  function setOtorisasi(val) {
    globalOtorisasi = val;
  }

  function setTerima(val) {
    globalTerima = val;
  }

  // status kirim: filter sisi-klien saja, tidak ikut dikirim ke SP
  function setOutstanding(val) {
    globalOutstanding = val;
  }

  // Jenis laporan: "0" Non Outstanding (Sp_ReportSPBDet, tombol Filter aktif, dua tanggal) atau
  // "1" Outstanding (Sp_ReportOutSpbDet, tombol Filter disembunyikan, HANYA satu tanggal, diambil
  // dari #inputDate2 -- lihat komentar di toolbar dan makeTable()).
  function setMode(val) {
    globalMode = val;
    const isOut = (val === '1');

    // Modal filter jadi tidak terjangkau sama sekali di mode Outstanding (satu-satunya
    // pembukanya disembunyikan), jadi ketiganya dipaksa balik ke Semua supaya tidak ada nilai
    // basi dari mode Non Outstanding yang diam-diam ikut terpakai di request Outstanding.
    $('#btnFilter').toggle(!isOut);
    if (isOut) {
      $('#modalOtorisasi').val('2');
      setOtorisasi('2');
      $('#modalTerima').val('2');
      setTerima('2');
      $('#modalOutstanding').val('2');
      setOutstanding('2');
    }

    // #inputDate1 disembunyikan di mode Outstanding -- yang dipakai & dikirim adalah
    // #inputDate2 (lihat makeTable()). LaporanMarketingOutSPBHrgSoController TIDAK diubah:
    // nilai #inputDate2 tetap dikirim dengan request key `date1` apa adanya.
    $('#inputDate1').toggle(!isOut);
    $('#dateSep').toggle(!isOut);
    $('#periodeLabel').text(isOut ? 'Per Tanggal' : 'Periode');

    lastRows = [];
    currentGroupby = 'NOBUKTI';
    $('#tableBody').html('<tr class="empty-row"><td>Atur filter lalu klik <b>Tampilkan</b> untuk memuat laporan.</td></tr>');
    $('#footerLabel').text('Belum ada data dimuat');

    setModeReport();
    updateFilterBadge();
  }

  /* -- FILTER MODAL -- */

  function updateFilterBadge() {
    let count = 0;
    if ($('#modalOtorisasi').val() !== '2') { count++; }
    if ($('#modalTerima').val() !== '2') { count++; }
    if ($('#modalOutstanding').val() !== '2') { count++; }
    $('#filterBadge').text(count + ' aktif');
  }

  function resetAllFilters() {
    $('#modalOtorisasi').val('2');
    $('#modalTerima').val('2');
    $('#modalOutstanding').val('2');
    updateFilterBadge();
  }

  $('#modalFilter').on('show.bs.modal', function() {
    $('#modalOtorisasi').val(globalOtorisasi);
    $('#modalTerima').val(globalTerima);
    $('#modalOutstanding').val(globalOutstanding);
    updateFilterBadge();
  });

  $('#modalFilter').on('change', 'select.rt-native', updateFilterBadge);

  function applyModalFilter() {
    setOtorisasi($('#modalOtorisasi').val());
    setTerima($('#modalTerima').val());
    setOutstanding($('#modalOutstanding').val());

    $('#modalFilter').modal('hide');
  }

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
      if (c[0] === 'outstanding') return outstandingText(v);
      if (c[3] === 'date') return format_date(v);
      if (c[3] === 'float' || c[3] === 'int') return currencyNormalizer(v);
      return (v == null ? '' : v);
    }));
    const rows = [header].concat(body);
    ReportExport.fromRows(rows, fmt, { customName: (globalMode === '1') ? 'OutstandingSPBHrgSO_' + (globalDate2 || '') : 'LaporanSPBHrgSO_' + (globalDate1 || '') + '_' + (globalDate2 || '') });
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

  // outstanding = qnt yang sudah terkirim (dari Sp_ReportSPBDet). null/kosong = belum ada
  // pengiriman sama sekali -> BELUM; ada nilainya -> TERKIRIM. Tanpa status "Sebagian",
  // jadi QNT tidak ikut dibandingkan.
  function outstandingText(v) {
    if (v == null || v === '') return 'Belum';
    return 'Terkirim';
  }

  // Filter sisi-klien untuk kolom outstanding (#modalOutstanding):
  // 0 = Terkirim, 1 = Belum, 2/lainnya = Semua.
  function filterByOutstanding(rows, filterVal) {
    switch (String(filterVal)) {
      case '0': return rows.filter(r => outstandingText(pickCI(r, 'outstanding')) === 'Terkirim');
      case '1': return rows.filter(r => outstandingText(pickCI(r, 'outstanding')) === 'Belum');
      default:  return rows;
    }
  }

  function pickCI(r, key) {
    if (r[key] !== undefined) return r[key];
    const lk = String(key).toLowerCase();
    for (const k in r) { if (k.toLowerCase() === lk) return r[k]; }
    return undefined;
  }

  // Kedua mode di halaman ini sama-sama berasal dari VwreportOutSPBHRGSO (Non Outstanding
  // lewat `select *`, Outstanding lewat query ber-alias di Sp_ReportOutSpbDet) --
  // keduanya punya NOSAT/SAT_1/SAT_2, tapi nama kolom qty-nya beda: QNT/QNT2 di Non
  // Outstanding, QntOut1/QntOut2 di Outstanding. NOSAT=1 -> pasangan pertama, 2 atau 3 ->
  // kedua (ikut CASE HAVING di SP-nya sendiri), sama seperti reportlaporanmarketingspb.blade.php
  // & reportmarketingso.blade.php. Dihitung sekali di sini (bukan di render()) supaya render,
  // subtotal, pencarian, dan export semua membaca field yang sama.
  function decorateRows(rows, isOut) {
    const q1 = isOut ? 'QntOut1' : 'QNT';
    const q2 = isOut ? 'QntOut2' : 'QNT2';
    (rows || []).forEach(function(r) {
      const nosat = String(pickCI(r, 'NOSAT'));
      const pakaiKedua = (nosat === '2' || nosat === '3');
      r.QTY = pickCI(r, pakaiKedua ? q2 : q1);
      r.Satuan = pickCI(r, pakaiKedua ? 'SAT_2' : 'SAT_1');
    });
    return rows || [];
  }

  // Satu slot per ordering (Detail/Rekap sudah dihapus) supaya kolom tersimpan tiap user
  // (DBSIMPANHEADER, doSetHeader()) tidak bleed antar ordering, biarpun kolomnya sama sekarang
  // (lihat setHeaderHrg()/setHeaderOut() -- tidak bercabang per ordering).
  var modereport_nobukti = 0,
      modereport_barang = 1,
      modereport_customer = 2;
  g_modeReport = modereport_nobukti;

  // Dispatcher: kedua SP punya set kolom berbeda -- tetap dipisah jadi dua fungsi supaya
  // g_modeReport (dengan offset di sisi Outstanding) tidak salah dibaca. Nama fungsi ini
  // ("setDefaultHeader") tetap dipertahankan karena masterreport2's doSetHeader() memanggilnya
  // lewat nama ini.
  function setDefaultHeader() {
    if (globalMode === '1') {
      setHeaderOut();
    } else {
      setHeaderHrg();
    }
  }

  // Nama kolom di bawah ini adalah kolom NYATA dari Sp_ReportSPBDet (dikonfirmasi lewat
  // reportlaporanmarketingspb.blade.php, yang memanggil SP yang sama). Sebelumnya halaman ini
  // meminta NoPesanan/NamaBarangX (tidak ada -> selalu kosong) dan NoSo/TanggalSO (kolom itu
  // milik Sp_ReportOutSpbDet, SP lain sama sekali) -- sudah dikoreksi di sini. Kolom 'Satuan'
  // & 'QTY' BUKAN kolom mentah -- itu hasil gabungan SAT_1/SAT_2 & QNT/QNT2 oleh
  // decorateRows() (nosat=1 -> pasangan pertama, 2/3 -> kedua). QTY tidak di-subtotal karena
  // satuan bisa beda-beda per baris.
  function setHeaderHrg() {
    gcart_header = [
      ['NOBUKTI', 'No. Bukti', 1, 'varchar', 0, 0],
      ['Tanggal', 'Tanggal', 1, 'date', 0, 0],
      ['NAMACUSTSUPP', 'Nama Customer', 1, 'varchar', 0, 0],
      ['NoPOCustomer', 'No. PO Customer', 1, 'varchar', 0, 0],
      ['KODEBRG', 'Kode Barang', 1, 'varchar', 0, 0],
      ['NAMABRG', 'Nama Barang', 1, 'varchar', 0, 0],
      ['Satuan', 'Sat', 1, 'varchar', 0, 0],
      ['QTY', 'QTY', 1, 'float', 0, 0],
      ['HARGA', 'Harga', 1, 'float', 0, 0],
      ['NDPPRp', 'Total', 1, 'float', 1, 2],
      ['outstanding', 'Status', 1, 'varchar', 0, 0],
    ];
    gsum_issubtotal = 1; gsum_isgrandtotal = 1;
  }

  // Kolom NYATA dari Sp_ReportOutSpbDet, dipindah dari reportmarketingoutspbhrgso.blade.php.
  // Tidak ada kolom 'outstanding' -- proc ini tidak mengembalikan status kirim sama sekali.
  // Kolom 'Satuan' & 'QTY' BUKAN kolom mentah -- itu hasil gabungan SAT_1/SAT_2 &
  // QntOut1/QntOut2 oleh decorateRows() (nosat=1 -> pasangan pertama, 2/3 -> kedua), sama
  // seperti di setHeaderHrg(). QTY tidak di-subtotal karena satuan bisa beda-beda per baris.
  function setHeaderOut() {
    gcart_header = [
      ['NoBukti', 'No. Bukti', 1, 'varchar', 0, 0],
      ['Tanggal', 'Tanggal', 1, 'date', 0, 0],
      ['kodeCustSupp', 'Kode', 1, 'varchar', 0, 0],
      ['NAMACUSTSUPP', 'Nama Customer', 1, 'varchar', 0, 0],
      ['KodeBrg', 'Kode Barang', 1, 'varchar', 0, 0],
      ['Namabrg', 'Nama Barang', 1, 'varchar', 0, 0],
      ['NOPOCUstomer', 'No. PO. Cust', 1, 'varchar', 0, 0],
      ['NoSo', 'No. SO', 1, 'varchar', 0, 0],
      ['TanggalSO', 'Tgl. SO', 1, 'date', 0, 0],
      ['Satuan', 'Sat', 1, 'varchar', 0, 0],
      ['QTY', 'QTY', 1, 'float', 0, 0],
      ['HARGA', 'Harga', 1, 'float', 1, 0],
      ['NDPPRPZX', 'Total', 1, 'float', 1, 0],
    ];
    gsum_issubtotal = 1; gsum_isgrandtotal = 1;
  }

  function makeTable(_mode) {
    let _date1 = $("#inputDate1").val();
    let _date2 = $("#inputDate2").val();
    const isOut = (globalMode === '1');

    setDefaultHeader();
    if (typeof doSetHeader === 'function') {
      doSetHeader(g_modeReport);
    }

    // Sp_ReportOutSpbDet cuma memfilter `Tanggal <= @tgl1` dan mengabaikan @tgl2 sepenuhnya,
    // jadi cuma satu tanggal yang berpengaruh di mode ini -- diambil dari #inputDate2 (bukan
    // #inputDate1, lihat setMode()). LaporanMarketingOutSPBHrgSoController: request key `date1`
    // dibaca apa adanya, jadi _date2 (nilai #inputDate2) tetap dikirim dengan key `date1`
    // -- JANGAN ganti key ini jadi `date2`.
    let url, data;
    if (isOut) {
      url = reportUrlOut;
      data = {
        date1: _date2,
        inputOto: globalOtorisasi,
        inputOrd: globalOrderBy,
      };
    } else {
      url = reportUrlHrg;
      data = {
        date1: _date1,
        date2: _date2,
        inputOto: globalOtorisasi,
        inputTerima: globalTerima,
        inputOrd: globalOrderBy,
      };
    }

    // Kolom yang dikembalikan dua proc ini beda casing (mis. NOBUKTI vs NoBukti, KODEBRG vs
    // KodeBrg) -- groupby (dibaca render() sebagai r[currentGroupby] apa adanya) harus
    // mengikuti casing masing-masing proc, bukan satu tabel bersama.
    let groupbyKey;
    if (isOut) {
      if (globalOrderBy == "N") { groupbyKey = 'NoBukti'; }
      else if (globalOrderBy == "B") { groupbyKey = 'KodeBrg'; }
      else { groupbyKey = 'kodeCustSupp'; }
    } else {
      if (globalOrderBy == "N") { groupbyKey = 'NOBUKTI'; }
      else if (globalOrderBy == "B") { groupbyKey = 'KODEBRG'; }
      else { groupbyKey = 'KodeCustSupp'; }
    }

    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

    $.ajax({
      url: url,
      type: 'get',
      data: data,
      success: function(res) {
        lastRows = decorateRows(res || [], isOut);
        currentGroupby = groupbyKey;
        $('#searchBox2').val('');
        render();
      },
      error: function() {
        lastRows = [];
        currentGroupby = groupbyKey;
        render();
      }
    });
  }

  // === RENDER KE TABEL STYLED (.tb-report #mainTable) ===
  // Kolom dibangun DINAMIS dari gcart_header (hanya kolom yang terlihat / item[2]===1,
  // sesuai urutan simpanan) -> mode-agnostic, jadi Detail & Rekap langsung terpakai
  // tanpa daftar kolom hardcode.
  function render() {
    const cols = gcart_header.filter(c => c[2] === 1); // kolom terlihat, terurut
    const keys = cols.filter(c => c[4] === 1).map(c => c[0]); // kolom yang di-subtotal
    const thead = document.querySelector('#mainTable thead');
    const tbody = document.getElementById('tableBody');
    const showSub = (gsum_issubtotal === 1);
    const showGrand = (gsum_isgrandtotal === 1);

    const search = ($('#searchBox2').val() || '').trim().toLowerCase();
    const searched = !search ? (lastRows || []) : (lastRows || []).filter(function(r) {
      return rowSearchText(r, cols).indexOf(search) !== -1;
    });
    // pakai globalOutstanding (nilai yang sudah di-Terapkan), BUKAN nilai select modal:
    // kalau user mengubah dropdown lalu menekan Batal, select tetap memegang nilai yang
    // dibatalkan itu dan akan ikut terpakai di render berikutnya (mis. saat cari).
    // Sp_ReportOutSpbDet tidak mengembalikan kolom 'outstanding' sama sekali -- filter ini
    // dilewati total di mode Outstanding (setMode() juga sudah memaksa globalOutstanding='2',
    // ini jaga-jaga kalau ada nilai basi).
    const rows = (globalMode === '1') ? searched : filterByOutstanding(searched, globalOutstanding);

    // HEADER dinamis dari gcart_header — dibangun report-table.js (ReportTable) supaya kolom
    // bisa diseret untuk diurutkan & punya menu roda gigi (sembunyikan / desimal / total).
    // Juga menyegarkan #rtBar (daftar kolom tersembunyi + Order By).
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
        // outstanding kosong = belum dikirim (merah), ada isinya = terkirim (hijau)
        if (key === 'outstanding') {
          const txtOut = outstandingText(pickCI(r, key));
          const clsOut = (txtOut === 'Terkirim') ? 'is-active' : 'is-inactive';
          return '<td><span class="sp-badge ' + clsOut + '">' + txtOut + '</span></td>';
        }
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

  // Baris total: nilai di kolom yang di-subtotal (item[4]===1), label di kolom pertama non-total
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

  // Gabungan teks satu baris dari kolom terlihat (tanggal pakai format tampil dd/mm/yyyy)
  // supaya pencarian cocok dengan apa yang user lihat di tabel.
  function rowSearchText(r, cols) {
    return cols.map(function(c) {
      const v = pickCI(r, c[0]);
      // kolom badge dicari lewat teksnya ("terkirim"/"belum"), bukan nilai mentah
      if (c[0] === 'outstanding') return outstandingText(v);
      if (c[3] === 'date') return format_date(v);
      return (v == null ? '' : String(v));
    }).join(' ').toLowerCase();
  }

  // order by: ikut menentukan slot kolom tersimpan (lewat setModeReport), bukan susunan
  // kolom itu sendiri -- lihat setHeaderHrg()/setHeaderOut().
  function setOrderBy(val) {
    globalOrderBy = val;
    setModeReport();
  }

  function setModeReport() {
    if (globalOrderBy == "N") {
      g_modeReport = modereport_nobukti;
    } else if (globalOrderBy == "B") {
      g_modeReport = modereport_barang;
    } else {
      g_modeReport = modereport_customer;
    }

    // Geser OUT_MODE_OFFSET di mode Outstanding supaya kolom tersimpan (DBSIMPANHEADER,
    // dikunci per href+reportmode) tidak bentrok dengan mode Non Outstanding di href yang sama.
    if (globalMode === '1') {
      g_modeReport += OUT_MODE_OFFSET;
    }

    doSetHeader(g_modeReport);
    doShowCustomize();
  }

</script>

@endsection
