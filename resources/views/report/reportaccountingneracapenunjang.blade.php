@extends('report.masterreport2')

{{-- Table styling lives in public/css/report-table.css (loaded via report/newmaster2.blade.php).
     Laporan Neraca Penunjang: daftar dua kolom flat (Keterangan, Saldo Bulan Ini), TIDAK ada
     grouping/band -- jadi TIDAK butuh header dua tingkat manual seperti reportaccountinglabarugi/
     reportaccountingaktiva/reportaccountingneraca. Header dibangun lewat ReportTable.headHtml()
     bawaan (drag-reorder + gear per kolom aktif seperti biasa; tidak ada alasan mematikan drag di
     sini karena tidak ada band yang bisa pecah). Halaman ini sebelumnya
     @extends('report.masterreport4') dengan mode "Rekap" (kolom NoBukti/Sales/dst, tidak related
     sama sekali dengan bentuk data 2-kolom di atas) -- dropdown Report Mode-nya `hidden` dan
     tidak ada kode yang meng-unhide-nya, jadi mode itu tidak pernah bisa dicapai user & dihapus
     di sini (sama seperti migrasi reportaccountinglabarugitahunan/reportaccountingneraca).
     Kolom "Saldo Bulan Ini" (BulanIni) di versi lama bertipe 'varchar' (tidak diformat sebagai
     angka) -- diganti ke 'float' di sini karena namanya jelas nilai saldo (uang), konsisten
     dengan kolom Saldo/Jumlah lain di seluruh aplikasi ini; murni perubahan format tampilan
     (pemisah ribuan/desimal), bukan perubahan data.
     Filter: Bulan/Tahun tetap di toolbar; Divisi dipindah ke modal "Filter Laporan" (dropdown
     polos, bukan entity-picker modalAccountingJurnal lagi -- endpoint loadDivisi controller ini
     sendiri, sebelumnya tidak pernah di-routing). Tidak ada kolom No Bukti → tanpa panel voucher.
     Sumber: sp_LapNeracaPenunjang :divisi,:bulan,:tahun. Data hanya dimuat setelah klik
     "Tampilkan". --}}

<style>
  /* tinggi awal area tabel supaya dropdown tidak terpotong container pendek */
  .tb-report .table-wrap { min-height: 10vh; }
</style>

@section('header2')
<div class="tb-report main">
  <div class="content">

    <!-- TOOLBAR -->
    <div class="toolbar">

      <!-- Period selector (populated dynamically by populatePeriodSelectors) -->
      <div class="period-select-wrap">
        <label>Periode</label>
        <select class="period-select" id="periodBulan" onchange="changePeriodParts()"></select>
        <select class="period-select" id="periodTahun" onchange="changePeriodParts()"></select>
      </div>

      {{-- Search --}}
      <div>
        <input class="search-inp" type="text" id="searchBox2" placeholder="Cari data..." oninput="applyFilters()" style="width:160px">
      </div>

      {{-- Divisi pindah ke modal "Filter Laporan" -- lihat docs/new-filter-modal-ui-guide.md.
           Nilai sebenarnya: globalDivisi (var JS). --}}

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

    <!-- TABLE — header satu tingkat (tanpa band), dibangun oleh ReportTable.headHtml() bawaan
         (drag-reorder + gear aktif seperti biasa). -->
    <div class="table-outer">
      <div class="table-wrap">
        <table class="tb" id="mainTable">
          <thead>
            <tr>
              <th style="min-width:260px">Keterangan</th>
              <th class="num" style="min-width:160px">Saldo Bulan Ini</th>
            </tr>
          </thead>
          <tbody id="tableBody">
            <tr class="empty-row"><td colspan="2">Atur filter lalu klik <b>Tampilkan</b> untuk memuat laporan.</td></tr>
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
              {{-- Diisi dari laporanaccountingneracapenunjang_loaddivisi (loadDivisiDropdown()).
                   Selalu punya nilai (tidak ada opsi "Semua") -- pilihan wajib, bukan filter yang
                   bisa dimatikan, jadi TIDAK dihitung di badge (lihat updateFilterBadge()). --}}
              <select class="rt-native" id="modalDivisi"></select>
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
@endsection


@section('jsreport')
<script type="text/javascript">
  let defaultBulan = new Date().getMonth() + 1;  // 1–12
  let defaultTahun = new Date().getFullYear();

  let g_reportTitle = "";
  let lastRows = [];   // hasil fetch terakhir (dipakai render / search)

  let globalDivisi = "-";  // diisi loadDivisiDropdown() saat page load (selalu wajib diisi)

  // Report mode dipakai engine masterreport2 (doSetHeader) — cukup satu int, halaman ini
  // cuma satu mode (mode "Rekap" yang lama sudah dihapus, tidak pernah bisa dicapai user).
  g_modeReport = 27;

  const reportUrl = "{{ url('laporanaccountingneracapenunjang_doReport') }}";

  $(document).ready(function () {
    doSetHeader(g_modeReport);   // muat gcart_header (default / hasil Reset kolom tersimpan)
    populatePeriodSelectors();
    loadDivisiDropdown();   // isi dropdown Divisi (default: divisi pertama)

    // Header tabel interaktif standar (drag-reorder + gear per kolom + bar "kolom tersembunyi"/
    // "Reset kolom") -- tidak ada band di halaman ini jadi drag aman, tidak perlu header manual.
    ReportTable.init({
      table: '#mainTable',
      bar: '#rtBar',
      onChange: function () { if (lastRows.length) { applyFilters(); } else { render(); } }
    });

    // Sengaja TIDAK memuat data saat halaman dibuka — laporan hanya dimuat setelah
    // pengguna klik tombol "Tampilkan".
  });

  function setDefaultHeader() {
    gcart_header = [
      ['keterangan', 'Keterangan', 1, 'varchar', 0, 0],
      ['BulanIni', 'Saldo Bulan Ini', 1, 'float', 1, 2],
    ];
    gsum_issubtotal = 0; gsum_isgrandtotal = 1;
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
    // Hanya kolom yang sedang terlihat (gcart_header[i][2]===1) yang ikut diekspor -- konsisten
    // dengan yang tampil di layar setelah kolom disembunyikan lewat gear, dan urutan mengikuti
    // hasil drag (gcart_header, bukan urutan deklarasi tetap seperti halaman ber-band).
    const cols = gcart_header.filter(c => c[2] === 1);
    const header = cols.map(c => c[1]);
    const body = (lastRows || []).map(r => cols.map(function (c) {
      const v = pickCI(r, c[0]);
      if (c[3] === 'float' || c[3] === 'int') return currencyNormalizer(v);
      return (v == null ? '' : v);
    }));
    const rows = [header].concat(body);
    ReportExport.fromRows(rows, fmt, { customName: 'NeracaPenunjang_' + defaultBulan + '-' + defaultTahun });
    showToast('📄', 'Data diekspor sebagai ' + fmt);
  }

  /* ── LOAD DATA: sp_LapNeracaPenunjang ── */
  function makeTable(_mode) {
    g_reportTitle = 'REPORT NERACA PENUNJANG';
    let _divisi = globalDivisi || '-';

    // muat gcart_header (default / hasil Reset kolom tersimpan)
    if (typeof doSetHeader === 'function') { doSetHeader(g_modeReport); }

    document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

    const data = { inputBulan: defaultBulan, inputTahun: defaultTahun, divisi: _divisi };

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
  // HTML-escape teks bebas (nama divisi bisa diisi user).
  function esc(v) {
    return String(v == null ? '' : v)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  /* ── RENDER: daftar flat apa adanya (urutan dari SP) + Grand Total pada kolom Saldo Bulan Ini.
     Kolom terlihat & urutan dari gcart_header (item[2]===1) supaya show/hide DAN drag lewat gear
     benar-benar berpengaruh. ── */
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

  function getKolomFilter() { return ['keterangan']; }

  /* ── SELECT DIVISI (modal Filter Laporan) ──
        Diisi sekali dari laporanaccountingneracapenunjang_loaddivisi saat page load. Memilih
        item hanya menyetel globalDivisi; laporan baru dimuat saat klik Tampilkan (konsisten
        dgn filter Periode). ── */
  function loadDivisiDropdown() {
    let list = [];
    $.ajax({
      url: "{!! url('laporanaccountingneracapenunjang_loaddivisi') !!}",
      type: "get", async: false,
      success: function (res) { list = res || []; }
    });

    let html = '';
    list.forEach((item) => {
      const nama = (item.NamaDevisi != null ? String(item.NamaDevisi) : '');
      html += '<option value="' + item.Devisi + '">' + item.Devisi + ' - ' + esc(nama) + '</option>';
    });
    $("#modalDivisi").html(html);

    // default: divisi pertama (tanpa memuat ulang — laporan dimuat saat klik "Tampilkan")
    if (list.length) { setDivisi(list[0].Devisi); }
  }

  function setDivisi(kode) {
    globalDivisi = kode;
    $("#modalDivisi").val(kode);
  }

  /* ── FILTER MODAL ──
        Satu-satunya field di sini adalah Divisi. Ia TIDAK ikut dihitung di badge karena
        tidak punya opsi "Semua" — wajib selalu diisi, jadi bukan "filter yang dinyalakan"
        (aturan sama seperti Divisi di reportaccountinglabarugi). ── */
  function updateFilterBadge() {
    $('#filterBadge').text('0 aktif');
  }

  function resetAllFilters() {
    if ($('#modalDivisi option').length) {
      $('#modalDivisi').prop('selectedIndex', 0);
    }
    updateFilterBadge();
  }

  $('#modalFilter').on('show.bs.modal', function () {
    $('#modalDivisi').val(globalDivisi);
    updateFilterBadge();
  });

  $('#modalFilter').on('change', 'select.rt-native', updateFilterBadge);

  function applyModalFilter() {
    setDivisi($('#modalDivisi').val());
    $('#modalFilter').modal('hide');
  }
</script>
@endsection
