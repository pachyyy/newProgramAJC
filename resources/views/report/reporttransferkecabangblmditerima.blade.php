@extends('report.masterreport2')

{{-- Table styling lives in public/css/report-table.css.
     Layout dirapikan menggunakan .tb-report --}}

@section('header2')
<div class="tb-report main">
  <div class="content">

    <!-- TOOLBAR -->
    <div class="toolbar">
      {{-- <div>
        <div class="page-title">Laporan Transfer Ke Cabang Belum Diterima</div>
      </div> --}}

      <!-- Jenis laporan: Semua (0)/Diterima (1)/Outstanding (2). Tiap Jenis punya slot kolom
           tersimpan sendiri (g_modeReport diikutkan Jenis, lihat setJenis()) -- sama seperti
           Non Outstanding vs Outstanding di reportpengadaanpopo.blade.php (OUT_MODE_OFFSET),
           supaya "Customize Table" (show/hide, urutan, desimal, subtotal/grand total) bisa
           diatur independen per Jenis. Ketiga Jenis defaultnya SAMA (lihat setDefaultHeader()).
           Outstanding = snapshot "per tanggal2" -- tanggal1 disembunyikan & dipatok controller
           sendiri (date1 = date2), lihat setJenis(). -->
      <div class="filter-wrap">
        <label>Jenis</label>
        <select class="filter-inp" id="inputJenis" onchange="setJenis(this.value)">
          <option value="0">Semua</option>
          <option value="1">Diterima</option>
          <option value="2">Outstanding</option>
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

      {{-- Otorisasi & Order By TIDAK ada di sini: input hidden-nya di halaman lama tidak pernah
           dibaca JS manapun (globalOtorisasi/globalOrderBy sudah hardcode "2"/"N" di JS-nya),
           dan controller (doReport) tidak pernah membacanya. Tidak ada yang genuinely bisa
           difilter selain Jenis & Tanggal, jadi tombol "Filter Data" & "Customize Table" (modal
           lama) tidak ada lagi di halaman ini -- digantikan #rtBar untuk atur kolom. --}}

      <!-- search + tampilkan + export -->
      <div class="action-group">
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
      Seret judul kolom untuk mengurutkan. Klik <i class="bi bi-gear"></i> pada judul kolom untuk sembunyikan kolom atau atur desimal &amp; total.
    </div>

  </div><!-- /content -->

  <!-- TOAST -->
  <div class="toast" id="toast"><span id="ti"></span><span id="tm"></span></div>

</div><!-- /tb-report -->
@endsection

@section('jsreport')
<script type="text/javascript">
  let globalDate1 = "{!! date('Y-m-d') !!}";
  let globalDate2 = "{!! date('Y-m-d') !!}";
  let globalJenis = "0"; // 0 Semua, 1 Diterima, 2 Outstanding -- lihat setJenis()
  // Otorisasi & Order By tidak pernah punya kontrol UI sungguhan (input hidden-nya di halaman
  // lama tidak dibaca JS manapun), dan doReport() di controller tidak pernah membacanya --
  // dipertahankan sebagai nilai tetap yang dikirim (harmless, tidak dibaca server) sama seperti
  // sebelum migrasi.
  let globalOtorisasi = "2";
  let globalOrderBy = "N";

  let lastRows = []; // hasil fetch terakhir (dipakai render / export / search)
  let currentGroupby = 'NOBUKTI';

  // g_modeReport diikutkan Jenis (dulu dipakai utk Order By No Bukti/Barang yang tidak pernah
  // punya kontrol UI di halaman ini -- direpurpose di sini, lihat setJenis()).
  var modereport_semua = 0, modereport_diterima = 1, modereport_outstanding = 2;
  g_modeReport = modereport_semua;

  const reportUrl = "{{ url('laporantransferkecabangblmditerima_doReport') }}";

  $(document).ready(function () {
    // Tabel bergaya lama dari engine masterreport2 (#showTableReport) tidak pernah dipakai
    // halaman ini (kita punya #mainTable/.tb-report sendiri) -- dikosongkan & disembunyikan,
    // sama seperti reportaccountingkasharian.blade.php.
    $("#showTableReport").empty().hide();

    // setJenis() sendiri sudah memanggil setDefaultHeader()/doSetHeader()/doShowCustomize()
    // utk slot kolom Jenis default (Semua), jadi tidak perlu dipanggil terpisah di sini.
    setJenis(globalJenis);

    // Header tabel interaktif: drag-reorder + gear (sembunyikan/desimal/total) + bar
    // "Reset kolom"/kolom tersembunyi. Tidak ada "Tampilan" switcher -- Order By tidak
    // pernah punya kontrol UI sungguhan di halaman ini (selalu "N"), sama seperti sebelum migrasi.
    ReportTable.init({
      table: '#mainTable',
      bar: '#rtBar',
      onChange: function () {
        if (lastRows.length) { applyFilters(); } else { renderRows([], currentGroupby); }
      }
    });

    // setTimeout(() => { makeTable('REPORT'); }, 100);
  });

  // Dipanggil oleh doSetHeader() HANYA saat slot kolom Jenis yang aktif (g_modeReport) belum
  // pernah disimpan (DBSIMPANHEADER) -- lihat setJenis(). Satu array HARDCODE per Jenis (bukan
  // satu array dibagi bertiga) supaya susunan/visibilitas kolom bawaan tiap Jenis bisa diatur
  // langsung di kode ini -- ubah elemen ke-3 tiap baris (1 = tampil, 0 = sembunyi bawaan) atau
  // urutan barisnya sesuai kebutuhan Jenis itu. Ini HANYA dipakai sekali sebagai bibit pertama
  // kali; setelah itu user bisa mengubahnya sendiri lewat gear kolom (tersimpan independen per
  // Jenis) -- utk memaksa reset ke bawaan lagi, gunakan "Reset Kolom" di #rtBar.
  // [ field, label, tampil bawaan (1/0), tipe, pakai total (1/0), desimal ]
  function setDefaultHeader() {
    if (g_modeReport == modereport_diterima) {
      gcart_header = [
        ['NOBUKTI', 'No Bukti', 1, 'varchar', 0, 0],
        ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
        ['KODEBRG', 'Kode Barang', 1, 'varchar', 0, 0],
        ['NAMABRG', 'Nama Barang', 1, 'varchar', 0, 0],
        ['GDGASAL', 'Asal', 1, 'varchar', 0, 0],
        ['GDGTUJUAN', 'Tujuan', 1, 'varchar', 0, 0],
        ['SATUAN', 'Satuan', 1, 'varchar', 0, 0],
        ['QNT', 'Qty', 1, 'float', 1, 2],
        // ['QNTTERIMA', 'Qty Terima', 1, 'float', 1, 2],
        // ['SISA', 'Qty Sisa', 1, 'float', 1, 2],
        ['hpp', 'HPP', 1, 'float', 1, 2],
        ['TOTHARGA', 'Total', 1, 'float', 1, 2]
      ];
    } else if (g_modeReport == modereport_outstanding) {
      gcart_header = [
        ['NOBUKTI', 'No Bukti', 1, 'varchar', 0, 0],
        ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
        ['KODEBRG', 'Kode Barang', 1, 'varchar', 0, 0],
        ['NAMABRG', 'Nama Barang', 1, 'varchar', 0, 0],
        ['GDGASAL', 'Asal', 1, 'varchar', 0, 0],
        ['GDGTUJUAN', 'Tujuan', 1, 'varchar', 0, 0],
        ['SATUAN', 'Satuan', 1, 'varchar', 0, 0],
        ['QNT', 'Qty', 1, 'float', 1, 2],
        ['QNTTERIMA', 'Qty Terima', 1, 'float', 1, 2],
        ['SISA', 'Qty Sisa', 1, 'float', 1, 2],
        ['hpp', 'HPP', 1, 'float', 1, 2],
        ['TOTHARGA', 'Total', 1, 'float', 1, 2]
      ];
    } else { // modereport_semua (default)
      gcart_header = [
        ['NOBUKTI', 'No Bukti', 1, 'varchar', 0, 0],
        ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
        ['nobuktiterima', 'No Bukti Terima', 1, 'varchar', 0, 0],
        ['tglterima', 'Tanggal Terima', 1, 'date', 0, 0],
        ['KODEBRG', 'Kode Barang', 1, 'varchar', 0, 0],
        ['NAMABRG', 'Nama Barang', 1, 'varchar', 0, 0],
        ['GDGASAL', 'Asal', 1, 'varchar', 0, 0],
        ['GDGTUJUAN', 'Tujuan', 1, 'varchar', 0, 0],
        ['SATUAN', 'Satuan', 1, 'varchar', 0, 0],
        ['QNT', 'Qty', 1, 'float', 1, 2],
        ['QNTTERIMA', 'Qty Terima', 1, 'float', 1, 2],
        ['SISA', 'Qty Sisa', 1, 'float', 1, 2],
        ['hpp', 'HPP', 1, 'float', 1, 2],
        ['TOTHARGA', 'Total', 1, 'float', 1, 2]
      ];
    }
    gsum_issubtotal = 1;
    gsum_isgrandtotal = 1;
  }

  // Jenis laporan: Semua (0)/Diterima (1)/Outstanding (2) -- lihat komentar di toolbar. g_modeReport
  // diikutkan Jenis supaya tiap Jenis punya slot kolom tersimpan sendiri (DBSIMPANHEADER dikunci
  // per href+reportmode) -- sama seperti Non Outstanding vs Outstanding di
  // reportpengadaanpopo.blade.php (OUT_MODE_OFFSET).
  function setJenis(val) {
    globalJenis = val;
    const isOut = (val === '2');

    // date1 tidak dikirim di mode Outstanding -- controller mematok tgl1 = tgl2 sendiri.
    $('#inputDate1').toggle(!isOut);
    $('#dateSep').toggle(!isOut);
    $('#periodeLabel').text(isOut ? 'Sampai Tanggal' : 'Periode');

    g_modeReport = Number(val);
    if (typeof doSetHeader === 'function') { doSetHeader(g_modeReport); }
    if (typeof doShowCustomize === 'function') { doShowCustomize(); }

    // Ganti jenis tidak langsung fetch ulang -- tabel dikosongkan, user tekan Tampilkan. Pakai
    // renderRows([]) supaya header (sesuai slot kolom Jenis yang baru) & footer ikut disegarkan.
    lastRows = [];
    renderRows(lastRows, currentGroupby);
  }

  // EXPORT ENGINE
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
    const cols = gcart_header.filter(c => c[2] === 1);
    const header = cols.map(c => c[1]);
    const body = (lastRows || []).map(r => cols.map(function (c) {
      const v = (c[0] === 'TOTHARGA') ? pickTotal(r) : pickCI(r, c[0]);
      if (c[3] === 'date') return format_date(v);
      if (c[3] === 'float' || c[3] === 'int') return currencyNormalizer(v);
      return (v == null ? '' : v);
    }));
    const rows = [header].concat(body);
    ReportExport.fromRows(rows, fmt, { customName: (globalJenis === '2') ? 'TransferCabangOS_' + (globalDate2 || '') : 'TransferCabang_' + (globalDate1 || '') + '_' + (globalDate2 || '') });
    showToast('📄', 'Data diekspor sebagai ' + fmt);
  }

  // MAKE TABLE ENGINE
  function makeTable(_mode) {
    globalDate1 = $("#inputDate1").val();
    globalDate2 = $("#inputDate2").val();
    const isOut = (globalJenis === '2');

    setDefaultHeader();
    if (typeof doSetHeader === 'function') { doSetHeader(g_modeReport); }

    // date1 sengaja tidak dikirim di mode Outstanding -- controller mematok tgl1 = tgl2 sendiri.
    const filterData = { date2: globalDate2, inputJenis: globalJenis };
    if (!isOut) { filterData.date1 = globalDate1; }

    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

    $.ajax({
      url: reportUrl, type: 'get', data: filterData,
      success: function (res) {
        lastRows = Array.isArray(res) ? res : ((res && res.res1) ? res.res1 : []);
        $('#searchBox2').val('');
        renderRows(lastRows, currentGroupby);
      },
      error: function () {
        lastRows = [];
        renderRows([], currentGroupby);
      }
    });
  }

  function renderRows(rows, groupby) {
    const cols  = gcart_header.filter(c => c[2] === 1);
    const thead = document.querySelector('#mainTable thead');
    const tbody = document.getElementById('tableBody');

    const totalCols = cols.filter(c => (c[3] === 'float' || c[3] === 'int') && c[4] === 1);
    const totalKeys = totalCols.map(c => c[0]);
    const hasTotal  = totalCols.length > 0;
    const showSub   = hasTotal && (gsum_issubtotal === 1);
    const showGrand = hasTotal && (gsum_isgrandtotal === 1);

    // Header dinamis -- dibangun report-table.js (ReportTable) supaya kolom bisa diseret
    // untuk diurutkan & punya menu roda gigi (sembunyikan / desimal / total).
    thead.innerHTML = ReportTable.headHtml(cols);

    if (!rows || !rows.length) {
      tbody.innerHTML = '<tr class="empty-row"><td colspan="' + cols.length + '">Tidak ada data ditemukan.</td></tr>';
      document.getElementById('footerLabel').textContent = 'Tidak ada data';
      return;
    }

    let html = '', prev = null;
    let sub = {}, grand = {};
    totalKeys.forEach(k => { sub[k] = 0; grand[k] = 0; });

    rows.forEach(function (r, i) {
      const now = pickCI(r, groupby);

      if (showSub && i !== 0 && prev !== now) {
        html += totalRow('Subtotal', sub, cols, totalKeys, 'subtotal-row');
        totalKeys.forEach(k => sub[k] = 0);
      }

      totalKeys.forEach(function (k) {
        const v = currencyNormalizer(k === 'TOTHARGA' ? pickTotal(r) : pickCI(r, k));
        sub[k] += v; grand[k] += v;
      });

      html += '<tr class="data-row">' + cols.map(function (c) {
        const key = c[0], type = c[3];
        if (key === 'TOTHARGA') return '<td class="num">' + format_number(currencyNormalizer(pickTotal(r)), c[5]) + '</td>';
        if (type === 'date') return '<td>' + format_date(pickCI(r, key)) + '</td>';
        if (type === 'float' || type === 'int') return '<td class="num">' + format_number(currencyNormalizer(pickCI(r, key)), c[5]) + '</td>';
        return '<td>' + nullToEmpty(pickCI(r, key)) + '</td>';
      }).join('') + '</tr>';

      prev = now;
    });

    if (showSub)   html += totalRow('Subtotal', sub, cols, totalKeys, 'subtotal-row');
    if (showGrand) html += totalRow('GRAND TOTAL', grand, cols, totalKeys, 'grand-total');

    tbody.innerHTML = html;
    document.getElementById('footerLabel').textContent = 'Menampilkan ' + rows.length + ' baris';
  }

  function totalRow(label, sums, cols, totalKeys, cls) {
    const labelIdx = cols.findIndex(c => totalKeys.indexOf(c[0]) === -1);
    const tds = cols.map(function (c, idx) {
      if (totalKeys.indexOf(c[0]) !== -1) return '<td class="num">' + format_number(sums[c[0]], c[5]) + '</td>';
      if (idx === labelIdx) return '<td>' + label + '</td>';
      return '<td></td>';
    });
    return '<tr class="' + cls + '">' + tds.join('') + '</tr>';
  }

  function pickCI(r, key) {
    if (r[key] !== undefined) return r[key];
    const lk = String(key).toLowerCase();
    for (const k in r) { if (k.toLowerCase() === lk) return r[k]; }
    return undefined;
  }

  // Kolom Total (key 'TOTHARGA' di gcart_header, label "Total") sengaja dipertahankan sebagai key
  // generik di ketiga slot kolom (satu per Jenis, lihat setJenis()) -- field ASLI dari
  // SP_TransferBlmTerima beda nama per Jenis (hppkirim utk Semua/Outstanding, TOTHPP utk
  // Diterima). Resolusi field asli dilakukan di sini berdasarkan Jenis yang sedang aktif,
  // terlepas dari slot kolom (g_modeReport) mana yang sedang ditampilkan/dikustomisasi.
  function pickTotal(r) {
    return (globalJenis === '1') ? pickCI(r, 'TOTHPP') : pickCI(r, 'hppkirim');
  }

  function applyFilters() {
    if (!lastRows.length) return;
    const term = ($('#searchBox2').val() || '').trim().toLowerCase();
    if (!term) { renderRows(lastRows, currentGroupby); return; }

    const cols = gcart_header.filter(c => c[2] === 1);
    const filtered = lastRows.filter(r => rowSearchText(r, cols).indexOf(term) !== -1);
    renderRows(filtered, currentGroupby);
  }

  function rowSearchText(r, cols) {
    return cols.map(function (c) {
      const v = pickCI(r, c[0]);
      if (c[3] === 'date') return format_date(v);
      return (v == null ? '' : String(v));
    }).join(' ').toLowerCase();
  }

  function showToast(icon, msg) {
    const t = document.getElementById('toast');
    document.getElementById('ti').textContent = icon;
    document.getElementById('tm').textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
  }
</script>
@endsection
