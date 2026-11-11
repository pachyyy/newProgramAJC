@extends('report.masterreport2')
@include('report.modalAccountingJurnal')

{{-- Table styling lives in public/css/report-table.css (loaded via report/newmaster2.blade.php).
     Laporan Biaya: styled .tb-report, satu baris per Perkiraan (akun beban) — tabel RATA (tanpa grup)
     + Grand Total. Filter: Bulan/Tahun (dropdown), Divisi (dropdown, default divisi pertama),
     Perkiraan Awal/Akhir (rentang akun, modal). Tidak ada kolom No Bukti/No Nota → tanpa panel voucher.
     Sumber: Sp_LapBiaya :divisi,:bulan,:tahun,:perk1,:perk2. --}}
<style>
  /* tinggi awal area tabel supaya dropdown tidak terpotong container pendek */
  .tb-report .table-wrap { min-height: 10vh; }
</style>

@section('header2')
<div class="tb-report main">
  <div class="content">

    <!-- TOOLBAR -->
    <div class="toolbar">
      {{-- <div>
        <div class="page-title">Laporan Biaya</div>
      </div> --}}

      <!-- Period selector (populated dynamically by populatePeriodSelectors) -->
      <div class="period-select-wrap">
        <label>Periode</label>
        <select class="period-select" id="periodBulan" onchange="changePeriodParts()"></select>
        <select class="period-select" id="periodTahun" onchange="changePeriodParts()"></select>
      </div>

      {{-- Divisi & Perkiraan (awal/akhir) pindah ke modal "Filter Laporan" -- lihat
           docs/new-filter-modal-ui-guide.md. Nilai sebenarnya: globalDivisi (var JS) +
           #inputPerkiraan1 / #inputPerkiraan2 (hidden input di dalam modal). --}}

      <!-- Actions: search + filter + tampilkan + export -->
      <div class="action-group">
        <input class="search-inp" type="text" id="searchBox2" placeholder="Cari data..." oninput="applyFilters()" style="width:160px">
        {{-- Dibuka lewat plugin jQuery (Bootstrap 4), BUKAN data-bs-toggle (Bootstrap 5) --
             lihat catatan di modal Filter di bawah. --}}
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

    <!-- TABLE (kolom di-render dari gcart_header; tabel rata + grand total) -->
    <div class="table-outer">
      <div class="table-wrap">
        <table class="tb" id="mainTable">
          <thead>
            <tr><th>Perkiraan</th></tr>
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
        {{-- data-dismiss (BS4) = yang benar-benar menutup, karena modal ini dibuka lewat
             $.fn.modal milik BS4 (jQuery baru dimuat SESUDAH bundle BS5 di masterreport2).
             data-bs-dismiss dibiarkan untuk jaga-jaga. --}}
        <button type="button" class="btn-close" aria-label="Close" data-dismiss="modal" data-bs-dismiss="modal"
                onclick="$('#modalFilter').modal('hide')"></button>
      </div>

      <div class="modal-body">

        <div class="rt-section">
          <div class="rt-group-label">Pengaturan Laporan</div>
          <div class="rt-grid-1">
            <div>
              <label class="rt-field-label" for="modalDivisi">Divisi</label>
              {{-- Diisi dari laporanaccountingbiaya_loaddivisi (loadDivisiDropdown()). Selalu
                   punya nilai (tidak ada opsi "Semua") -- pilihan wajib, bukan filter yang bisa
                   dimatikan, jadi TIDAK dihitung di badge (lihat updateFilterBadge()). --}}
              <select class="rt-native" id="modalDivisi"></select>
            </div>
          </div>
        </div>

        <div class="rt-section">
          <div class="rt-group-label">Perkiraan
            <span class="rt-group-hint">&mdash; klik untuk memilih</span>
          </div>
          <div class="rt-grid-2" id="pickFields"></div>

          {{-- Nilai sebenarnya (dibaca makeTable() & ditulis modalAccountingJurnal's buttonPilih()) --}}
          <input type="hidden" id="inputPerkiraan1" value="-">
          <input type="hidden" id="inputPerkiraan2" value="-">
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
  let defaultBulan = new Date().getMonth() + 1;  // 1–12
  let defaultTahun = new Date().getFullYear();

  let g_reportTitle = "";
  let lastRows = [];   // hasil fetch terakhir (dipakai render / search)

  let globalDivisi = "-";  // diisi loadDivisiDropdown() saat page load (selalu wajib diisi)

  // Report mode dipakai engine masterreport2 (doSetHeader) — cukup satu int.
  g_modeReport = 20;

  const reportUrl = "{{ url('laporanaccountingbiaya_doReport') }}";

  $(document).ready(function () {
    // #printTime tidak ada di markup halaman ini (blok page-title/page-sub dikomentari) --
    // dijaga null supaya TIDAK melempar exception yang membatalkan sisa ready() (populatePeriodSelectors
    // & loadDivisiDropdown di bawah tidak akan jalan kalau baris ini error).
    var pt = document.getElementById('printTime');
    if (pt) pt.textContent = new Date().toLocaleString('id-ID');

    populatePeriodSelectors();
    loadDivisiDropdown();   // isi dropdown Divisi (default: divisi pertama)

    // Header tabel interaktif: seret kolom, menu roda gigi (sembunyikan/desimal/total).
    // Tidak ada "Tampilan" switcher -- halaman ini cuma satu mode (g_modeReport tetap).
    ReportTable.init({
      table: '#mainTable',
      bar: '#rtBar',
      onChange: function () { if (lastRows.length) { applyFilters(); } else { render(); } }
    });

    // Sengaja TIDAK memuat data saat halaman dibuka — laporan hanya dimuat setelah
    // pengguna klik tombol "Tampilkan" (atau memilih filter lalu Tampilkan).
  });

  /* ── kolom (gcart_header). Satu baris per Perkiraan (akun beban). Kolom uang bertanda
        total (item[4]=1) ikut Grand Total. Catatan: mengikuti setelan lama — "Bulan Ini"
        (BulanKini) TIDAK ditotal, sedangkan Bulan Lalu / Naik-Turun / Sampai Bulan Ini ditotal. ── */
  function setDefaultHeader() {
    gcart_header = [
      ['perkiraan', 'Perkiraan', 1, 'varchar', 0, 0],
      ['keterangan', 'Keterangan', 1, 'varchar', 0, 0],
      ['BulanLalu', 'Bulan Lalu', 1, 'float', 1, 2],
      ['Persen', 'Naik/Turun', 1, 'float', 1, 2],
      ['BulanKini', 'Bulan Ini', 1, 'float', 0, 0],
      ['sdBulanini', 'Sampai Bulan Ini', 1, 'float', 1, 0],
    ];
    gsum_issubtotal = 1; gsum_isgrandtotal = 1;
  }

  /* ── PERIODE (Bulan / Tahun) ── */
  const NAMA_BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
  function populatePeriodSelectors() {
    const selB = document.getElementById('periodBulan');
    const selT = document.getElementById('periodTahun');
    selB.innerHTML = NAMA_BULAN.map((nama, i) =>
      `<option value="${i + 1}" ${(i + 1) == defaultBulan ? 'selected' : ''}>${nama}</option>`).join('');
    const thisYear = new Date().getFullYear();
    let years = '';
    for (let y = thisYear; y >= thisYear - 6; y--) {
      years += `<option value="${y}" ${y == defaultTahun ? 'selected' : ''}>${y}</option>`;
    }
    selT.innerHTML = years;
  }
  // Hanya perbarui nilai periode; TIDAK memuat data (tunggu klik "Tampilkan").
  function changePeriodParts() {
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
    const cols = gcart_header.filter(c => c[2] === 1);
    const header = cols.map(c => c[1]);
    const body = (lastRows || []).map(r => cols.map(function (c) {
      const v = pickCI(r, c[0]);
      if (c[3] === 'float' || c[3] === 'int') return currencyNormalizer(v);
      return (v == null ? '' : v);
    }));
    const rows = [header].concat(body);
    ReportExport.fromRows(rows, fmt, { customName: 'Biaya_' + defaultBulan + '-' + defaultTahun });
    showToast('📄', 'Data diekspor sebagai ' + fmt);
  }

  /* ── LOAD DATA (Sp_LapBiaya; doReport mengembalikan array biasa) ── */
  function makeTable(_mode) {
    g_reportTitle = 'REPORT BIAYA';

    let _perk1 = $('#inputPerkiraan1').val() || '-';
    let _perk2 = $('#inputPerkiraan2').val() || '-';
    let _divisi = globalDivisi || '-';

    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

    const data = {
      inputBulan: defaultBulan, inputTahun: defaultTahun,
      divisi: _divisi, inputPerkiraan1: _perk1, inputPerkiraan2: _perk2
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
  function num(v) { if (v === null || v === undefined || v === '') return 0; const n = parseFloat(v); return isNaN(n) ? 0 : n; }
  function str(v) { return (v == null ? '' : String(v)).trim(); }
  function pickCI(r, key) {
    if (r[key] !== undefined) return r[key];
    const lk = String(key).toLowerCase();
    for (const k in r) { if (k.toLowerCase() === lk) return r[k]; }
    return undefined;
  }

  /* ── RENDER: tabel RATA (satu baris per Perkiraan, tanpa grup) + Grand Total.
     Kolom dinamis dari gcart_header (item[2]===1). Grand Total menjumlahkan kolom
     ber-total (item[4]=1) dan mengikuti gsum_isgrandtotal. ── */
  function render() {
    const cols  = gcart_header.filter(c => c[2] === 1);
    const thead = document.querySelector('#mainTable thead');
    const tbody = document.getElementById('tableBody');

    const totalCols = cols.filter(c => (c[3] === 'float' || c[3] === 'int') && c[4] === 1);
    const totalKeys = totalCols.map(c => c[0]);
    const hasTotal  = totalCols.length > 0;
    const showGrand = hasTotal && (gsum_isgrandtotal === 1);
    const search = ($('#searchBox2').val() || '').trim().toLowerCase();

    // HEADER dinamis — dibangun report-table.js (ReportTable) supaya kolom bisa diseret
    // untuk diurutkan & punya menu roda gigi (sembunyikan / desimal / total).
    thead.innerHTML = ReportTable.headHtml(cols);

    const rows = (lastRows || []).filter(r => !search || rowSearchText(r, cols).indexOf(search) !== -1);

    if (!rows.length) {
      tbody.innerHTML = '<tr class="empty-row"><td colspan="' + cols.length + '">Tidak ada data ditemukan.</td></tr>';
      document.getElementById('footerLabel').textContent = 'Tidak ada data';
      return;
    }

    let html = '';
    const grand = {}; totalKeys.forEach(k => grand[k] = 0);

    rows.forEach(r => {
      totalKeys.forEach(k => { grand[k] += currencyNormalizer(pickCI(r, k)); });
      html += '<tr class="data-row">' + cols.map(function (c) {
        const type = c[3];
        const v = pickCI(r, c[0]);
        if (type === 'date') return '<td>' + format_date(v) + '</td>';
        if (type === 'float' || type === 'int') return '<td class="num">' + format_number(currencyNormalizer(v), c[5]) + '</td>';
        return '<td>' + nullToEmpty(v) + '</td>';
      }).join('') + '</tr>';
    });

    if (showGrand) html += totalRow('GRAND TOTAL', grand, cols, totalKeys, 'grand-total');

    tbody.innerHTML = html;
    document.getElementById('footerLabel').textContent = 'Menampilkan ' + rows.length + ' baris';
  }

  // Baris total: nilai di tiap kolom pada `sumKeys`; label di kolom pertama non-sum.
  function totalRow(label, sums, cols, sumKeys, cls) {
    const labelIdx = cols.findIndex(c => sumKeys.indexOf(c[0]) === -1);
    const tds = cols.map(function (c, idx) {
      if (sumKeys.indexOf(c[0]) !== -1) return '<td class="num">' + format_number(sums[c[0]], c[5]) + '</td>';
      if (idx === labelIdx) return '<td>' + label + '</td>';
      return '<td></td>';
    });
    return '<tr class="' + cls + '">' + tds.join('') + '</tr>';
  }

  /* ── PENCARIAN SISI-KLIEN ── */
  function applyFilters() { render(); }

  function rowSearchText(r, cols) {
    let s = '';
    cols.forEach(function (c) {
      const v = pickCI(r, c[0]);
      s += ' ' + (c[3] === 'date' ? format_date(v) : (v == null ? '' : String(v)));
    });
    return s.toLowerCase();
  }

  /* ── TOAST ── */
  function showToast(icon, msg) {
    const t = document.getElementById('toast');
    document.getElementById('ti').textContent = icon;
    document.getElementById('tm').textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
  }

  function getKolomFilter() { return ['perkiraan', 'keterangan']; }

  /* ── SELECT DIVISI (modal Filter Laporan) ──
        Diisi sekali dari laporanaccountingbiaya_loaddivisi saat page load. Memilih item
        hanya menyetel globalDivisi; laporan baru dimuat saat klik Tampilkan (konsisten
        dgn filter Periode/Perkiraan). ── */
  function loadDivisiDropdown() {
    let list = [];
    $.ajax({
      url: "{!! url('laporanaccountingbiaya_loaddivisi') !!}",
      type: "get", async: false,
      success: function (res) { list = res || []; }
    });

    let html = '';
    list.forEach((item) => {
      const nama = (item.NamaDevisi != null ? String(item.NamaDevisi) : '');
      html += '<option value="' + item.Devisi + '">' + item.Devisi + ' - ' + esc(nama) + '</option>';
    });
    $("#modalDivisi").html(html);

    // default: divisi pertama (tidak ada opsi "Semua")
    if (list.length) { setDivisi(list[0].Devisi); }
  }

  function setDivisi(kode) {
    globalDivisi = kode;
    $("#modalDivisi").val(kode);
  }

  // HTML-escape teks bebas (nama divisi bisa diisi user).
  function esc(v) {
    return String(v == null ? '' : v)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  /* ── FILTER MODAL ──
        Perkiraan Awal/Akhir memakai picker modal bersama (buttonSelect di
        modalAccountingJurnal.blade.php -- selectPerkiraan1/selectPerkiraan2), yang langsung
        menulis ke #inputPerkiraan1 / #inputPerkiraan2. '-' = tidak dibatasi -> punya nilai
        netral, jadi DIHITUNG di badge saat diisi. Divisi TIDAK ada opsi "Semua" -> wajib,
        jadi TIDAK dihitung. ── */
  const PICK_FIELDS = [
    { id: 'inputPerkiraan1', label: 'Perkiraan Awal',  modal: 'selectPerkiraan1' },
    { id: 'inputPerkiraan2', label: 'Perkiraan Akhir', modal: 'selectPerkiraan2' },
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

  function updateFilterBadge() {
    let count = 0;
    PICK_FIELDS.forEach(function (f) {
      const val = $('#' + f.id).val();
      if (val && val !== '-') { count++; }
    });
    $('#filterBadge').text(count + ' aktif');
  }

  function resetAllFilters() {
    if ($('#modalDivisi option').length) {
      $('#modalDivisi').prop('selectedIndex', 0);
    }
    PICK_FIELDS.forEach(function (f) { $('#' + f.id).val('-'); });
    renderPickFields();
    updateFilterBadge();
  }

  $('#modalFilter').on('show.bs.modal', function () {
    $('#modalDivisi').val(globalDivisi);
    renderPickFields();
    updateFilterBadge();
  });

  $('#modalFilter').on('change', 'select.rt-native', updateFilterBadge);

  function applyModalFilter() {
    setDivisi($('#modalDivisi').val());
    $('#modalFilter').modal('hide');
  }

  // Buka picker Perkiraan (modal bersama) dari dalam modal Filter: BS4/BS5 tidak menumpuk
  // modal dengan bersih, jadi Filter disembunyikan dulu & dibuka lagi setelah picker ditutup.
  let g_reopenFilter = false;

  function pickFromModal(idModal) {
    g_reopenFilter = true;
    $('#modalFilter').modal('hide');
    buttonSelect(idModal);   // buka #formSelect (modalAccountingJurnal)
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
