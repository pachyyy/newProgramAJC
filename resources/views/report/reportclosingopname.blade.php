@extends('report.masterreport2')

{{-- @include('report/modalMarketingSO') dihapus: mati di halaman ini. Modal itu menulis
     hasilnya ke #inputCustomer/#inputGroup/#inputPIC/#inputKategori/#inputSubKategori/
     #inputMerk (lihat buttonPilih() di modalMarketingSO.blade.php) -- tidak satu pun ada
     di halaman ini, tidak ada pemanggilan buttonSelect(), dan Sp_ReportOpnameBarang cuma
     menerima date1/date2/inputOto/inputOrd. Blade modal-nya sendiri tidak diubah, jadi 19
     halaman lain yang meng-include-nya tidak terpengaruh. --}}

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
        <input class="search-inp" type="text" id="searchBox2" placeholder="Cari data..."
               oninput="applyFilters()" style="width:180px">
      </div>

      <div class="action-group">
        {{-- Dibuka lewat plugin jQuery, BUKAN data-bs-toggle. Halaman masterreport2 memuat
             dua Bootstrap; tombol Batal/Terapkan menutupnya dengan $('#modalFilter').modal('hide')
             DAN membawa kedua atribut dismiss, jadi pembuka & penutup pasti lewat library
             yang sama -- lihat docs/new-design-all-report-guide.md #5.1. JANGAN pasang data-toggle
             dan data-bs-toggle bersamaan (dobel instance + dobel backdrop). --}}
        <button class="btn-load" type="button" onclick="$('#modalFilter').modal('show')">
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

    {{-- Bar kolom tersembunyi + Reset kolom + switcher "Tampilan" (Order By), diisi
         report-table.js / ReportTable. Order By memang mengubah susunan kolom
         (setDefaultHeader punya dua urutan) sekaligus parameter SP, jadi cocok jadi
         "Tampilan" -- lihat docs/new-slider-table-guide.md #5. --}}
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

        <button type="button" class="btn-close" aria-label="Close"
                data-dismiss="modal" data-bs-dismiss="modal"
                onclick="$('#modalFilter').modal('hide')"></button>
      </div>

      <div class="modal-body">

        <div class="rt-section">
          <div class="rt-group-label">Pengaturan Lain</div>
          <div class="rt-grid-2">
            <div>
              {{-- Punya opsi netral ("Semua"), jadi IKUT dihitung di badge begitu diubah
                   dari netral -- docs/new-filter-modal-ui-guide.md #5. --}}
              <label class="rt-field-label" for="modalOtorisasi">Otorisasi</label>
              <select id="modalOtorisasi" class="rt-native" onchange="setOtorisasi(this.value)">
                <option value="0">Non Otorisasi</option>
                <option value="1">Otorisasi</option>
                <option value="2" selected>Semua</option>
              </select>
            </div>
          </div>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="rt-reset-link" onclick="resetAllFilters()">Reset semua</button>
        <div class="rt-footer-buttons">
          <button type="button" class="rt-btn rt-btn-ghost"
                  data-dismiss="modal" data-bs-dismiss="modal"
                  onclick="$('#modalFilter').modal('hide')">Batal</button>
          <button type="button" class="rt-btn rt-btn-primary"
                  onclick="applyModalFilter()">Terapkan</button>
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
  let globalOtorisasi = "2";  // default: Semua
  let globalOrderBy = "N";    // default: Nomor Bukti
  let globalReportMode = "0"; // default: Detail

  var jenisreport = 0; // ini untuk detail dan rekap

  var modereport_nobukti = 0, modereport_barang = 1;
  g_modeReport = modereport_nobukti;

  let lastRows = [];                  // hasil fetch terakhir (dipakai render / search / export)
  let currentGroupby = 'Nobukti';     // groupby aktif, untuk render ulang saat search

  const reportUrl = "{{ url('laporanclosingopname_doReport') }}";

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

  $(document).ready(function() {
    // setDefaultHeader() HARUS lebih dulu: setReportMode/setOrderBy memanggil
    // setModeReport() -> doSetHeader(), yang memuat layout kolom tersimpan user dari
    // DBSIMPANHEADER. Kalau setDefaultHeader() dipanggil belakangan, layout tersimpan itu
    // langsung ketimpa default di memori dan bar kolom menampilkan susunan yang salah.
    setDefaultHeader();
    setReportMode(globalReportMode);
    setOtorisasi(globalOtorisasi);
    setOrderBy(globalOrderBy);
    showPeriode();
    updateFilterBadge();

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
          // Order By bukan cuma menukar susunan kolom: nilainya ikut dikirim ke
          // Sp_ReportOpnameBarang (parameter Ordr), jadi harus query ulang --
          // aturan 3 di docs/new-slider-table-guide.md #5.
          if (lastRows.length) { makeTable('REPORT'); }
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
    globalDate2 = $('#inputDate2').val();
  }

  // otorisasi -- sekarang sebuah <select> di modal Filter, bukan dropdown bercentang
  function setOtorisasi(val) {
    globalOtorisasi = String(val);
    if ($('#modalOtorisasi').length) { $('#modalOtorisasi').val(globalOtorisasi); }
    updateFilterBadge();
  }

  // order by -- sekarang switcher "Tampilan" di #rtBar
  function setOrderBy(val) {
    globalOrderBy = String(val);
    setModeReport();
  }

  function setReportMode(val) {
    globalReportMode = String(val);
    jenisreport = Number(val);   // 0 = Detail, 1 = Rekap
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
        ['Nobukti', 'No Bukti', 1, 'varchar', 0, 0],
        ['tanggal', 'Tanggal', 1, 'date', 0, 0],
        ['kodebrg', 'Kode Barang', 1, 'varchar', 0, 0],
        ['namaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
        ['KodeGrp', 'Grp', 1, 'varchar', 0, 0],
        ['NamaGDG', 'Gdg', 1, 'varchar', 0, 0],
        ['Satuan', 'Satuan', 1, 'varchar', 0, 0],
        ['SaldoComp', 'Saldo', 1, 'float', 1, 0],
        ['QntOpname', 'Qnt', 1, 'float', 1, 0],
        ['Qntdb', 'Qnt Debet', 1, 'float', 1, 0],
        ['QntCr', 'Qnt Kredit', 1, 'float', 1, 0],
        ['Selisih', 'Selisih', 1, 'float', 1, 0],
        ['HPP', 'HPP', 1, 'float', 1, 0],
        ['Total', 'TOTAL', 1, 'float', 1, 0]
      ];
      gsum_issubtotal = 1; gsum_isgrandtotal = 1;
    } else if (g_modeReport == modereport_barang){
      gcart_header = [
        ['kodebrg', 'Kode Barang', 1, 'varchar', 0, 0],
        ['namaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
        ['Nobukti', 'No Bukti', 1, 'varchar', 0, 0],
        ['tanggal', 'Tanggal', 1, 'date', 0, 0],
        ['KodeGrp', 'Grp', 1, 'varchar', 0, 0],
        ['NamaGDG', 'Gdg', 1, 'varchar', 0, 0],
        ['Satuan', 'Satuan', 1, 'varchar', 0, 0],
        ['SaldoComp', 'Saldo', 1, 'float', 1, 0],
        ['QntOpname', 'Qnt', 1, 'float', 1, 0],
        ['Qntdb', 'Qnt Debet', 1, 'float', 1, 0],
        ['QntCr', 'Qnt Kredit', 1, 'float', 1, 0],
        ['Selisih', 'Selisih', 1, 'float', 1, 0],
        ['HPP', 'HPP', 1, 'float', 1, 0],
        ['Total', 'TOTAL', 1, 'float', 1, 0]
      ];
      gsum_issubtotal = 1; gsum_isgrandtotal = 1;
    }
  }

  /* ---------------- modal Filter ---------------- */

  // Badge "N aktif". Otorisasi punya opsi netral ("2" = Semua), jadi dihitung saat diubah.
  function updateFilterBadge() {
    let count = 0;
    if (globalOtorisasi !== '2') { count++; }
    $('#filterBadge').text(count + ' aktif');
  }

  function resetAllFilters() {
    setOtorisasi('2');
  }

  function applyModalFilter() {
    $('#modalFilter').modal('hide');
    makeTable('REPORT');
  }

  /* ---------------- data ---------------- */

  function makeTable(_mode) {
    // nilai groupby adalah nama kolom (sesuai database) untuk pengelompokan subtotal
    let groupby = (g_modeReport == modereport_nobukti) ? "Nobukti" : "kodebrg";
    let _date1  = $("#inputDate1").val();
    let _date2  = $("#inputDate2").val();

    globalDate1 = _date1;
    globalDate2 = _date2;

    setDefaultHeader();
    if (typeof doSetHeader === 'function') { doSetHeader(g_modeReport); }

    let data = {
      date1    : _date1,
      date2    : _date2,
      inputOto : globalOtorisasi,
      inputOrd : globalOrderBy,
    };

    // Mode FILTER dipanggil doShowFormFilterData() di layout, yang langsung membaca
    // gcart_filter begitu makeTable() kembali -- jalur itu tetap lewat doMakeTable()
    // (async:false) supaya urutannya tidak berubah.
    if (_mode !== 'REPORT') {
      doMakeTable(_mode, groupby, data, "REPORT CLOSING OPNAME", _date1, _date2);
      return;
    }

    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

    $.ajax({
      url: reportUrl,
      type: 'get',
      data: data,
      success: function (res) {
        lastRows = res || [];
        currentGroupby = groupby;
        $('#searchBox2').val('');
        render();
      },
      error: function () {
        lastRows = [];
        currentGroupby = groupby;
        render();
      }
    });
  }

  function getKolomFilter() {
    // tentukan kolom (sesuai database & gcart_header) yang mau ditampilkan
    // mode report menentukan kolom yang dipakai
    let data = [];
    if (g_modeReport == modereport_nobukti) {
      data = ['Nobukti', 'tanggal'];
    } else {
      data = ['kodebrg', 'namaBrg'];
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
    const rows = !search ? (lastRows || []) : (lastRows || []).filter(function (r) {
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

    let html = '', prev = null;
    let sub = {}, grand = {};
    keys.forEach(k => { sub[k] = 0; grand[k] = 0; });

    rows.forEach(function (r, i) {
      const now = pickCI(r, currentGroupby);

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
    const body = (lastRows || []).map(r => cols.map(function (c) {
      const v = pickCI(r, c[0]);
      if (c[3] === 'float' || c[3] === 'int') return currencyNormalizer(v);
      if (c[3] === 'date') return format_date(v);
      return (v == null ? '' : v);
    }));

    ReportExport.fromRows([header].concat(body), fmt, { customName: 'ReportClosingOpname' });
  }
</script>

@endsection
