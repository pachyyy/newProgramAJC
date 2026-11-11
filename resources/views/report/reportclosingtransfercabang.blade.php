@extends('report.masterreport2')

{{-- @include('report/modalMarketingSO') dihapus: mati di halaman ini. Modal itu menulis
     hasilnya ke #inputCustomer/#inputGroup/#inputPIC/#inputKategori/#inputSubKategori/
     #inputMerk (lihat buttonPilih() di modalMarketingSO.blade.php) -- tidak satu pun ada
     di halaman ini, tidak ada pemanggilan buttonSelect(), dan SP_ClosingTransfer cuma
     menerima bulan & tahun. Blade modal-nya sendiri tidak diubah, jadi halaman lain yang
     meng-include-nya tidak terpengaruh. --}}

@section('header2')
<div class="tb-report main">
  <div class="content">

    <!-- TOOLBAR -->
    <div class="toolbar">

      {{-- SP_ClosingTransfer hanya menerima bulan & tahun: LaporanClosingTransferCabangController
           memanggil getBulan($tgl1) = substr(date,5,2) dan getTahun($tgl1) = substr(date,0,4).
           Dua <select> Bulan/Tahun digabung changePeriodParts() jadi "YYYY-MM" di
           #inputDate1 yang tersembunyi -- diurai persis sama dengan "YYYY-MM-DD", jadi
           controller tidak perlu diubah. Idiom & class .period-select mengikuti
           report/reportstockmutasistock.blade.php. --}}
      <div class="filter-wrap">
        <label>Periode</label>
        <!-- Bulan/Tahun -->
        <select class="period-select" id="periodBulan" onchange="changePeriodParts()"></select>
        <select class="period-select" id="periodTahun" onchange="changePeriodParts()"></select>
        <input type="hidden" id="inputDate1" value="{!! date('Y-m') !!}">
      </div>

      {{-- Search --}}
      <div>
        <input class="search-inp" type="text" id="searchBox2" placeholder="Cari data..."
               oninput="applyFilters()" style="width:180px">
      </div>

      <div class="action-group">
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

    {{-- Bar kolom tersembunyi + Reset kolom + switcher "Tampilan" (Order By), diisi
         report-table.js / ReportTable. Order By di halaman ini murni mengubah susunan
         kolom & pengelompokan subtotal -- SP tidak menerima parameter urutan -- jadi
         tidak perlu query ulang, cukup render ulang. Lihat docs/new-slider-table-guide.md #5. --}}
    <div id="rtBar"></div>

    <!-- TABLE -->
    <div class="table-outer">
      <div class="table-wrap">
        <table class="tb" id="mainTable">
          <thead>
            <tr>
              <th>No Bukti</th>
            </tr>
          </thead>
          <tbody id="tableBody">
            <tr class="empty-row"><td colspan="14">Pilih periode lalu klik <b>Tampilkan</b> untuk memuat laporan.</td></tr>
          </tbody>
        </table>
      </div>
      <div class="table-footer">
        <span id="footerLabel">Belum ada data dimuat</span>
      </div>
    </div>

    <div class="rt-hint">
      <i class="bi bi-info-circle"></i>
      Laporan ini per bulan &mdash; hanya bulan &amp; tahun periode yang dipakai.
      Seret judul kolom untuk mengurutkan. Klik <i class="bi bi-gear"></i> pada judul kolom untuk
      sembunyikan kolom atau atur total.
    </div>

  </div>
</div>

@endsection

@section('jsreport')

<script type="text/javascript">
  let globalDate1 = "{!! date('Y-m') !!}";
  let globalOrderBy = "N";    // default: Nomor Bukti

  var modereport_nobukti = 0, modereport_barang = 1;
  g_modeReport = modereport_nobukti;

  let lastRows = [];                  // hasil fetch terakhir (dipakai render / search / export)

  // Kolom pengelompokan subtotal. DITURUNKAN dari g_modeReport, bukan disimpan, supaya
  // tidak pernah basi kalau "Tampilan" diganti sebelum ada data yang dimuat.
  function groupbyCol() {
    return (g_modeReport == modereport_nobukti) ? "NoBukti" : "KodeBrg";
  }

  const reportUrl = "{{ url('laporanclosingtransfercabang_doReport') }}";

  if (typeof loadingHtml !== 'function') {
    window.loadingHtml = function (msg) {
      return '<span style="display:inline-flex;align-items:center;gap:6px;color:#5A6A85;">' +
             '<i class="fas fa-spinner fa-spin"></i> ' + (msg || 'Memuat...') + '</span>';
    };
  }

  // Order By dipakai sebagai switcher "Tampilan" di #rtBar.
  const ORDER_OPTIONS = [
    { value: 'N', label: 'Nomor Bukti',  desc: 'Urut per nomor bukti' },
    { value: 'B', label: 'Nomor Barang', desc: 'Urut per kode barang' }
  ];

  /* ---------------- periode Bulan/Tahun ---------------- */

  let defaultBulan = new Date().getMonth() + 1;
  let defaultTahun = new Date().getFullYear();
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
    changePeriodParts();
  }

  // Bulan/Tahun -> gabung ke #inputDate1 format "YYYY-MM"
  function changePeriodParts() {
    const selB = document.getElementById('periodBulan');
    const selT = document.getElementById('periodTahun');
    if (!selB || !selT) return;
    defaultBulan = parseInt(selB.value, 10);
    defaultTahun = parseInt(selT.value, 10);
    const mm = String(defaultBulan).padStart(2, '0');
    $('#inputDate1').val(defaultTahun + '-' + mm);
  }

  $(document).ready(function() {
    // setDefaultHeader() HARUS lebih dulu: setOrderBy() memanggil setModeReport() ->
    // doSetHeader(), yang memuat layout kolom tersimpan user dari DBSIMPANHEADER. Kalau
    // setDefaultHeader() dipanggil belakangan, layout tersimpan itu langsung ketimpa
    // default di memori dan bar kolom menampilkan susunan yang salah.
    setDefaultHeader();
    setOrderBy(globalOrderBy);
    populatePeriodSelectors();   // harus sebelum showPeriode(): mengisi #inputDate1
    showPeriode();

    ReportTable.init({
      table: '#mainTable',
      bar: '#rtBar',
      onChange: render,
      views: {
        label: 'Tampilan',
        options: ORDER_OPTIONS,
        get: function () { return globalOrderBy; },
        set: function (v) {
          setOrderBy(String(v));
          // SP_ClosingTransfer tidak menerima parameter urutan, jadi data yang sama
          // cukup dirender ulang -- render() yang mengurutkan & mengelompokkan.
          if (lastRows.length) { render(); }
        }
      }
    });

    // "Filter Data" bawaan layout merender hasilnya lewat doShowReport() ke #tabel_data,
    // yaitu tabel lama yang halaman ini tidak tampilkan lagi. Ditimpa di sini supaya baris
    // terpilih masuk ke lastRows dan lewat render() milik halaman ini. Harus di dalam
    // ready(): script layout (masterreport2 baris 245+) dieksekusi SETELAH blok ini.
    window.doShowReportFilter = function () {
      let _res = [];
      gcart_filterShow.forEach(function (item) {
        if (item[1]) {
          gcart_filter.filter(function (c) { return c._idx === item[0]; })
                      .forEach(function (f) { _res.push(f); });
        }
      });

      lastRows = _res;
      $('#searchBox2').val('');
      render();
      alertify.success("Report ditampilkan");
      doCloseFormFilterData();
    };
  });

  // periode
  function showPeriode() {
    globalDate1 = $('#inputDate1').val();
  }

  // order by -- sekarang switcher "Tampilan" di #rtBar
  function setOrderBy(val) {
    globalOrderBy = String(val);
    setModeReport();
  }

  function setModeReport() {
    g_modeReport = (globalOrderBy == "N") ? modereport_nobukti : modereport_barang;

    // tiap mode punya layout kolom tersimpan sendiri di DBSIMPANHEADER
    if (typeof doSetHeader === 'function') { doSetHeader(g_modeReport); }
    if (typeof doShowCustomize === 'function') { doShowCustomize(); }
  }

  function setDefaultHeader() {
    if (g_modeReport == modereport_nobukti) {
      gcart_header = [
        ['NOBUKTI', 'No Bukti', 1, 'varchar', 0, 0],
        ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
        ['Tglbatal', 'Tgl Batal', 1, 'date', 0, 0],
        ['KODEBRG', 'Kode Barang', 1, 'varchar', 0, 0],
        ['NAMABRG', 'Nama Barang', 1, 'varchar', 0, 0],
        ['GDGASAL', 'Asal', 1, 'varchar', 0, 0],
        ['GDGTUJUAN', 'Tujuan', 1, 'varchar', 0, 0],
        ['SATUAN', 'Satuan', 1, 'varchar', 0, 0],
        ['QNT', 'Qnt', 1, 'float', 1, 2],
        ['QNTTERIMA', 'Qnt Terima', 1, 'float', 1, 2],
        ['SISA', 'Qnt Sisa', 1, 'float', 1, 2],
        ['QntBatal', 'Qnt Batal', 1, 'float', 1, 2],
        ['hpp', 'HPP', 1, 'float', 1, 2],
        ['tothpp', 'Total', 1, 'float', 1, 2]
      ];
      gsum_issubtotal = 1; gsum_isgrandtotal = 1;
    } else {
      gcart_header = [
        ['KODEBRG', 'Kode Barang', 1, 'varchar', 0, 0],
        ['NAMABRG', 'Nama Barang', 1, 'varchar', 0, 0],
        ['NOBUKTI', 'No Bukti', 1, 'varchar', 0, 0],
        ['TANGGAL', 'Tanggal', 1, 'date', 0, 0],
        ['Tglbatal', 'Tgl Batal', 1, 'date', 0, 0],
        ['GDGASAL', 'Asal', 1, 'varchar', 0, 0],
        ['GDGTUJUAN', 'Tujuan', 1, 'varchar', 0, 0],
        ['SATUAN', 'Satuan', 1, 'varchar', 0, 0],
        ['QNT', 'Qnt', 1, 'float', 1, 2],
        ['QNTTERIMA', 'Qnt Terima', 1, 'float', 1, 2],
        ['SISA', 'Qnt Sisa', 1, 'float', 1, 2],
        ['QntBatal', 'Qnt Batal', 1, 'float', 1, 2],
        ['hpp', 'HPP', 1, 'float', 1, 2],
        ['tothpp', 'Total', 1, 'float', 1, 2]
      ];
      gsum_issubtotal = 1; gsum_isgrandtotal = 1;
    }
  }

  /* ---------------- data ---------------- */

  function makeTable(_mode) {
    // nilai groupby adalah nama kolom (sesuai database) untuk pengelompokan subtotal
    let groupby = groupbyCol();
    let _date1  = $("#inputDate1").val();

    globalDate1 = _date1;

    setDefaultHeader();
    if (typeof doSetHeader === 'function') { doSetHeader(g_modeReport); }

    let data = {
      date1    : _date1,
      inputOrd : (g_modeReport == modereport_nobukti) ? "N" : "B",
    };

    // Mode FILTER dipanggil doShowFormFilterData() di layout, yang langsung membaca
    // gcart_filter begitu makeTable() kembali -- jalur itu tetap lewat doMakeTable()
    // (async:false) supaya urutannya tidak berubah.
    if (_mode !== 'REPORT') {
      doMakeTable(_mode, groupby, data, "REPORT CLOSING TRANSFER CABANG", _date1);
      return;
    }

    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

    $.ajax({
      url: reportUrl,
      type: 'get',
      data: data,
      success: function (res) {
        lastRows = res || [];
        $('#searchBox2').val('');
        render();
      },
      error: function () {
        lastRows = [];
        render();
      }
    });
  }

  function getKolomFilter() {
    // tentukan kolom (sesuai database & gcart_header) yang mau ditampilkan
    // mode report menentukan kolom yang dipakai
    let data = [];
    if (g_modeReport == modereport_nobukti) {
      data = ['NOBUKTI', 'TANGGAL'];
    } else {
      data = ['KODEBRG', 'NAMABRG'];
    }

    return data;
  }

  /* ---------------- render ---------------- */

  function pickCI(r, key) {
    if (r[key] !== undefined) return r[key];
    const lk = String(key).toLowerCase();
    for (const k in r) { if (k.toLowerCase() === lk) return r[k]; }
    return undefined;
  }

  function rowSearchText(r, cols) {
    return cols.map(function (c) {
      const v = pickCI(r, c[0]);
      if (c[3] === 'date') return format_date(v);
      return (v == null ? '' : String(v));
    }).join(' ').toLowerCase();
  }

  // Urutan baris datang dari SP (per nomor bukti). Untuk mode "Nomor Barang" baris harus
  // diurutkan ulang di sisi klien, kalau tidak subtotal per kode barang akan pecah jadi
  // banyak potongan. Mode "Nomor Bukti" TIDAK diurut ulang supaya urutan asli SP terjaga.
  function orderedRows(rows) {
    if (g_modeReport != modereport_barang) { return rows; }

    return rows.slice().sort(function (a, b) {
      let _ka = pickCI(a, 'KODEBRG'), _kb = pickCI(b, 'KODEBRG');
      _ka = (_ka == null) ? '' : String(_ka);
      _kb = (_kb == null) ? '' : String(_kb);
      return _ka.localeCompare(_kb);
    });
  }

  function render() {
    const cols  = gcart_header.filter(c => c[2] === 1);
    const thead = document.querySelector('#mainTable thead');
    const tbody = document.getElementById('tableBody');

    // kolom uang yang ikut dijumlah: item[4] === 1, sama seperti doSetColCart()
    // di masterreport2 (baris 1088) dan akumulasi di baris 1167 / 1256.
    const keys      = cols.filter(c => (c[3] === 'float' || c[3] === 'int') && c[4] === 1).map(c => c[0]);
    const showSub   = (gsum_issubtotal === 1) && keys.length > 0;
    const showGrand = (gsum_isgrandtotal === 1) && keys.length > 0;

    const search = ($('#searchBox2').val() || '').trim().toLowerCase();
    const base = orderedRows(lastRows || []);
    const rows = !search ? base : base.filter(function (r) {
      return rowSearchText(r, cols).indexOf(search) !== -1;
    });

    // HEADER dinamis dari gcart_header -- dibangun report-table.js (ReportTable) supaya
    // kolom bisa diseret untuk diurutkan & punya menu roda gigi.
    thead.innerHTML = ReportTable.headHtml(cols);

    if (!rows.length) {
      tbody.innerHTML = '<tr class="empty-row"><td colspan="' + cols.length + '">Tidak ada data ditemukan.</td></tr>';
      document.getElementById('footerLabel').textContent = 'Tidak ada data';
      return;
    }

    const groupby = groupbyCol();
    let html = '', prev = null;
    let sub = {}, grand = {};
    keys.forEach(k => { sub[k] = 0; grand[k] = 0; });

    rows.forEach(function (r, i) {
      const now = pickCI(r, groupby);

      // subtotal saat nilai grup berganti (kalau toggle Subtotal aktif)
      if (showSub && i !== 0 && prev !== now) {
        html += totalRowTotal('Subtotal', sub, cols, keys, 'subtotal-row');
        keys.forEach(k => { sub[k] = 0; });
      }

      keys.forEach(function (k) {
        const v = currencyNormalizer(pickCI(r, k));
        sub[k] += v; grand[k] += v;
      });

      html += '<tr class="data-row">' + cols.map(function (c) {
        const key = c[0], type = c[3];
        if (type === 'date') return '<td>' + format_date(pickCI(r, key)) + '</td>';
        if (type === 'float' || type === 'int') {
          return '<td class="num">' + format_number(currencyNormalizer(pickCI(r, key)), c[5]) + '</td>';
        }
        return '<td>' + nullToEmpty(pickCI(r, key)) + '</td>';
      }).join('') + '</tr>';

      prev = now;
    });

    // subtotal grup terakhir + grand total, mengikuti toggle di modal Customize Table
    if (showSub)   html += totalRowTotal('Subtotal', sub, cols, keys, 'subtotal-row');
    if (showGrand) html += totalRowTotal('GRAND TOTAL', grand, cols, keys, 'grand-total');

    tbody.innerHTML = html;
    document.getElementById('footerLabel').textContent = 'Menampilkan ' + rows.length + ' baris';
  }

  // Baris total: nilai di kolom uang (item[4]===1), label di kolom pertama non-uang
  // yang masih terlihat, sel lain dikosongkan -- mengikuti urutan kolom terlihat saat ini.
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
  function applyFilters() {
    if (!lastRows.length) return;   // belum ada data dimuat
    render();
  }

  /* ---------------- export ---------------- */

  function toggleExport() { document.getElementById('exportDrop').classList.toggle('open'); }

  document.addEventListener('click', function (e) {
    const wrap = document.getElementById('exportWrap');
    if (wrap && !wrap.contains(e.target)) { document.getElementById('exportDrop').classList.remove('open'); }
  });

  function doExport(fmt) {
    document.getElementById('exportDrop').classList.remove('open');
    if (fmt === 'Print') { window.print(); return; }

    const cols = gcart_header.filter(c => c[2] === 1);
    const header = cols.map(c => c[1]);
    const body = orderedRows(lastRows || []).map(r => cols.map(function (c) {
      const v = pickCI(r, c[0]);
      if (c[3] === 'float' || c[3] === 'int') return currencyNormalizer(v);
      if (c[3] === 'date') return format_date(v);
      return (v == null ? '' : v);
    }));

    ReportExport.fromRows([header].concat(body), fmt, { customName: 'ReportClosingTransferCabang' });
  }
</script>

@endsection
