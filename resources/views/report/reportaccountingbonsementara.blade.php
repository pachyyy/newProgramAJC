@extends('report.masterreport2')

{{-- Table styling lives in public/css/report-table.css (loaded via report/newmaster2.blade.php).
     Bon Sementara: single date (toolbar) + Perkiraan (moved into the "Filter Laporan" modal per
     docs/new-filter-modal-ui-guide.md -- Perkiraan is a forced choice, akun pertama default,
     tidak ada opsi "Semua", jadi TIDAK dihitung di badge; pola sama persis dengan Divisi di
     reportaccountinglabarugi.blade.php). No Rp/Valas mode, no saldo footer.
     Header INTERAKTIF (drag kolom, gear sembunyikan/total, bar kolom tersembunyi) via
     ReportTable.init() + ReportTable.headHtml() in renderRows() -- see docs/new-slider-table-guide.md.
     Flat (single-row) header, tidak ada band, jadi headHtml() dipakai langsung (tidak seperti
     reportaccountinglaporanarus/aktiva yang membangun thead manual untuk header dua tingkat). --}}
<style>
    /* Beri tinggi awal pada area tabel supaya dropdown/modal tidak terpotong
       container pendek saat data belum/masih sedikit. */
    .tb-report .table-wrap {
        min-height: 10vh;
    }
</style>

@section('header2')
    <div class="tb-report main">
        <div class="content">

            <!-- TOOLBAR -->
            <div class="toolbar">
                {{-- <div>
                    <div class="page-title">Bon Sementara</div>
                </div> --}}

                <!-- Tanggal (snapshot date) -->
                <div class="filter-wrap">
                    <label>Tanggal</label>
                    <input type="date" class="filter-inp" id="inputDate2" value="{!! date('Y-m-d') !!}">
                </div>

                <!-- Seach -->
                <div>
                    <input class="search-inp" type="text" id="searchBox2" placeholder="Cari data..."
                        oninput="applyFilters()" style="width:180px">
                </div>

                <!-- Actions: row-level search + filter + customize + tampilkan + export -->
                <div class="action-group">
                    {{-- Dibuka lewat plugin jQuery (Bootstrap 4), BUKAN data-bs-toggle (Bootstrap 5) —
                         lihat aturan dua-Bootstrap di new-design-all-report-guide.md §5.1. --}}
                    <button class="btn-load" type="button" onclick="$('#modalFilter').modal('show')">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    {{-- <button class="btn-load" onclick="doShowFormCustomizeTable()" title="Customize Table"><i
                            class="fas fa-cog"></i> Customize Table</button> --}}
                    <button class="btn-load" onclick="makeTable('REPORT')" title="Tampilkan laporan"><i
                            class="fas fa-check"></i> Tampilkan</button>
                    <div class="export-wrap" id="exportWrap">
                        <button class="export-btn" onclick="toggleExport()"><i class="bi bi-arrow-down"></i> Export <i
                                class="bi bi-caret-down-fill"></i></button>
                        <div class="export-drop" id="exportDrop">
                            <div class="export-opt" onclick="doExport('Excel')"><i class="bi bi-journals text-success"></i>
                                Ekspor ke <span class="ext">XLSX</span></div>
                            <div class="export-opt" onclick="doExport('CSV')"><i class="bi bi-clipboard"></i> Ekspor ke
                                <span class="ext">CSV</span>
                            </div>
                            <div class="export-opt" onclick="doExport('Print')"><i
                                    class="bi bi-printer-fill text-warning"></i> Cetak Laporan</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bar kolom tersembunyi (diisi oleh report-table.js / ReportTable) -->
            <div id="rtBar"></div>

            <!-- TABLE (header + rows rendered dynamically from gcart_header) -->
            <div class="table-outer">
                <div class="table-wrap">
                    <table class="tb" id="mainTable">
                        <thead>
                            <tr>
                                <th>No Bukti</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <tr class="empty-row">
                                <td>Atur filter lalu klik <b>Tampilkan</b> untuk memuat laporan.</td>
                            </tr>
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
                        {{-- <div class="rt-group-label">Pengaturan Laporan</div> --}}
                        <div class="rt-grid-1">
                            <div>
                                <label class="rt-field-label" for="modalPerkiraan">Perkiraan</label>
                                {{-- Diisi dari reportaccountingbonsementara_loadperkiraan (loadPerkiraanDropdown()).
                                     Selalu punya nilai (tidak ada opsi "Semua") -- pilihan wajib, bukan filter yang
                                     bisa dimatikan, jadi TIDAK dihitung di badge (lihat updateFilterBadge()). --}}
                                <select class="rt-native" id="modalPerkiraan"></select>
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
    {{-- Shared formatters (fmtRp/fmtN) + window.ReportTable live in public/js/report-table.js,
         already loaded once by report/masterreport2.blade.php -- do not include it again here,
         it registers document/window-level listeners (voucher drill, gear menu, drag) that would
         otherwise fire twice. --}}
    <script type="text/javascript">
        let globalDate2 = "{!! date('Y-m-d') !!}";

        let g_reportTitle = "";
        let g_date2 = "",
            g_inputPerkiraan = "";

        let lastRows = []; // hasil fetch terakhir (dipakai renderRows / search)
        let currentGroupby = 'NoBukti'; // tidak dipakai untuk subtotal (subtotal off), hanya placeholder

        let globalPerkiraan = '-'; // diisi loadPerkiraanDropdown() saat page load (selalu wajib diisi)

        // Report satu mode saja (bon sementara per tanggal).
        // CATATAN: mode di-bump ke 3 supaya header TERSIMPAN versi lama halaman ini
        // (kolom Jumlah tanpa total, grand total mati) tidak dipakai lagi. Mode 3 mulai
        // bersih dari setDefaultHeader() (Grand Total aktif), lalu tersimpan seperti biasa.
        var modereport_detail = 3;
        g_modeReport = modereport_detail;
        var jenisreport = 0;

        const reportUrl = "{{ url('reportaccountingbonsementara_doReport') }}";

        $(document).ready(function() {
            setDefaultHeader();
            doSetHeader(g_modeReport); // muat susunan kolom (default / hasil kustomisasi user) + gsum flags
            loadPerkiraanDropdown(); // isi dropdown Perkiraan (default: Semua)

            // Header interaktif: seret judul kolom untuk mengurutkan, gear per kolom untuk
            // sembunyikan/atur total, + bar "Reset kolom"/kolom tersembunyi.
            ReportTable.init({
                table: '#mainTable',
                bar: '#rtBar',
                onChange: function() {
                    if (lastRows.length) { applyFilters(); } else { renderRows([], currentGroupby); }
                }
            });

            // setTimeout(() => {
            //     makeTable('REPORT');
            // }, 100);
        });

        /* ── kolom (gcart_header). Tabel styled DI-RENDER dari sini, jadi hasil
              "Customize Table" (show/hide + urutan kolom) langsung ikut tampil.
              Kolom Jumlah (debet) ditandai kolom total (item[4]=1) sehingga baris
              Grand Total (gsum_isgrandtotal) menjumlahkannya. ── */
        function setDefaultHeader() {
            gcart_header = [
                ['NoBukti', 'No Bukti', 1, 'varchar', 0, 0],
                ['Tanggal', 'Tanggal', 1, 'date', 0, 0],
                ['Penerima', 'Penerima', 1, 'varchar', 0, 0],
                ['Keterangan', 'Keperluan', 1, 'varchar', 0, 0],
                ['debet', 'Jumlah', 1, 'float', 1, 2],
            ];
            gsum_issubtotal = 0;
            gsum_isgrandtotal = 1;
        }

        /* ── periode ── */
        function showPeriode() {
            globalDate2 = $('#inputDate2').val();
        }

        /* ── EXPORT ── */
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
            if (fmt === 'Print') {
                window.print();
                return;
            }
            exportDelimited(fmt);
        }

        function exportDelimited(fmt) {
            const cols = gcart_header.filter(c => c[2] === 1);
            const header = cols.map(c => c[1]);
            const body = (lastRows || []).map(r => cols.map(function(c) {
                if (c[3] === 'date') return format_date(r[c[0]]);
                if (c[3] === 'float' || c[3] === 'int') return currencyNormalizer(r[c[0]]);
                return (r[c[0]] == null ? '' : r[c[0]]);
            }));
            const rows = [header].concat(body);
            ReportExport.fromRows(rows, fmt, { customName: 'BonSementara_' + (g_date2 || '') });
            showToast('📄', 'Data diekspor sebagai ' + fmt);
        }

        /* ── LOAD DATA (Sp_ReportBon; doReport mengembalikan array biasa) ── */
        function makeTable(_mode) {
            const _date2 = $('#inputDate2').val();
            const _perk = globalPerkiraan || '-';

            g_reportTitle = 'REPORT ACCOUNTING BON SEMENTARA';
            g_date2 = _date2;
            g_inputPerkiraan = _perk;

            // Muat susunan kolom (default atau hasil "Customize Table" tersimpan)
            if (typeof doSetHeader === 'function') {
                doSetHeader(g_modeReport);
            }

            document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

            $.ajax({
                url: reportUrl,
                type: 'get',
                data: {
                    date2: _date2,
                    inputPerkiraan: _perk
                },
                success: function(res) {
                    lastRows = Array.isArray(res) ? res : ((res && res.res1) ? res.res1 : []);
                    $('#searchBox2').val('');
                    renderRows(lastRows, currentGroupby);
                },
                error: function() {
                    lastRows = [];
                    renderRows([], currentGroupby);
                }
            });
        }

        /* ── RENDER KE TABEL STYLED (.tb-report #mainTable) ──
           Kolom dibangun DINAMIS dari gcart_header (hanya kolom terlihat / item[2]===1).
           Grand Total menjumlahkan kolom numerik ber-total (item[4]===1), mengikuti toggle
           Customize Table (#buttonGrandtotal -> gsum_isgrandtotal). ── */
        function renderRows(rows, groupby) {
            const cols = gcart_header.filter(c => c[2] === 1);
            const thead = document.querySelector('#mainTable thead');
            const tbody = document.getElementById('tableBody');

            const totalCols = cols.filter(c => (c[3] === 'float' || c[3] === 'int') && c[4] === 1);
            const totalKeys = totalCols.map(c => c[0]);
            const hasTotal = totalCols.length > 0;
            const showSub = hasTotal && (gsum_issubtotal === 1);
            const showGrand = hasTotal && (gsum_isgrandtotal === 1);

            // HEADER interaktif (drag/gear); headHtml() menyegarkan #rtBar sendiri.
            thead.innerHTML = ReportTable.headHtml(cols);

            if (!rows || !rows.length) {
                tbody.innerHTML = '<tr class="empty-row"><td colspan="' + cols.length +
                    '">Tidak ada data ditemukan.</td></tr>';
                document.getElementById('footerLabel').textContent = 'Tidak ada data';
                return;
            }

            let html = '',
                prev = null;
            let sub = {},
                grand = {};
            totalKeys.forEach(k => {
                sub[k] = 0;
                grand[k] = 0;
            });

            rows.forEach(function(r, i) {
                const now = pickCI(r, groupby);

                if (showSub && i !== 0 && prev !== now) {
                    html += totalRow('Subtotal', sub, cols, totalKeys, 'subtotal-row');
                    totalKeys.forEach(k => sub[k] = 0);
                }

                totalKeys.forEach(function(k) {
                    const v = currencyNormalizer(r[k]);
                    sub[k] += v;
                    grand[k] += v;
                });

                html += '<tr class="data-row">' + cols.map(function(c) {
                    const key = c[0],
                        type = c[3];
                    if (type === 'date') return '<td>' + format_date(r[key]) + '</td>';
                    if (type === 'float' || type === 'int') return '<td class="num">' + format_number(
                        currencyNormalizer(r[key]), c[5]) + '</td>';
                    return '<td>' + nullToEmpty(r[key]) + '</td>';
                }).join('') + '</tr>';

                prev = now;
            });

            if (showSub) html += totalRow('Subtotal', sub, cols, totalKeys, 'subtotal-row');
            if (showGrand) html += totalRow('GRAND TOTAL', grand, cols, totalKeys, 'grand-total');

            tbody.innerHTML = html;
            document.getElementById('footerLabel').textContent = 'Menampilkan ' + rows.length + ' baris';
        }

        // Baris total: nilai di tiap kolom numerik yang ditotal; label di kolom pertama non-total.
        function totalRow(label, sums, cols, totalKeys, cls) {
            const labelIdx = cols.findIndex(c => totalKeys.indexOf(c[0]) === -1);
            const tds = cols.map(function(c, idx) {
                if (totalKeys.indexOf(c[0]) !== -1) return '<td class="num">' + format_number(sums[c[0]], c[5]) +
                    '</td>';
                if (idx === labelIdx) return '<td>' + label + '</td>';
                return '<td></td>';
            });
            return '<tr class="' + cls + '">' + tds.join('') + '</tr>';
        }

        // Ambil properti baris tanpa peduli besar/kecil huruf (proc mencampur casing).
        function pickCI(r, key) {
            if (r[key] !== undefined) return r[key];
            const lk = String(key).toLowerCase();
            for (const k in r) {
                if (k.toLowerCase() === lk) return r[k];
            }
            return undefined;
        }

        /* ── PENCARIAN SISI-KLIEN: saring lastRows lalu render ulang tabel styled. ── */
        function applyFilters() {
            if (!lastRows.length) return;
            const term = ($('#searchBox2').val() || '').trim().toLowerCase();
            if (!term) {
                renderRows(lastRows, currentGroupby);
                return;
            }

            const cols = gcart_header.filter(c => c[2] === 1);
            const filtered = lastRows.filter(r => rowSearchText(r, cols).indexOf(term) !== -1);
            renderRows(filtered, currentGroupby);
        }

        function rowSearchText(r, cols) {
            return cols.map(function(c) {
                const v = r[c[0]];
                if (c[3] === 'date') return format_date(v);
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

        /* ── Filter Data engine (opsional): kolom yang dipakai modal "Filter Data" ── */
        function getKolomFilter() {
            return ['NoBukti', 'Tanggal'];
        }

        /* ── SELECT PERKIRAAN (modal Filter Laporan) ──
              Diisi sekali dari reportaccountingbonsementara_loadperkiraan saat page load. Memilih
              item hanya menyetel globalPerkiraan; laporan baru dimuat saat klik Tampilkan
              (konsisten dgn filter Tanggal). ── */
        function loadPerkiraanDropdown() {
            let list = [];
            $.ajax({
                url: "{!! url('reportaccountingbonsementara_loadperkiraan') !!}",
                type: "get",
                async: false,
                success: function(res) {
                    list = res || [];
                }
            });

            let html = '';
            list.forEach((item) => {
                const ket = (item.Keterangan != null ? String(item.Keterangan) : '');
                html += '<option value="' + item.Perkiraan + '">' + item.Perkiraan + ' - ' + esc(ket) + '</option>';
            });
            $("#modalPerkiraan").html(html);

            // default: akun pertama (tanpa memuat ulang — laporan dimuat saat klik "Tampilkan")
            if (list.length) { setPerkiraan(list[0].Perkiraan); }
        }

        function setPerkiraan(kode) {
            globalPerkiraan = kode;
            $("#modalPerkiraan").val(kode);
        }

        // HTML-escape teks bebas (keterangan perkiraan bisa diisi user).
        function esc(v) {
            return String(v == null ? '' : v)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        /* ── FILTER MODAL ──
              Satu-satunya field di sini adalah Perkiraan. Ia TIDAK ikut dihitung di badge karena
              tidak punya opsi "Semua" — wajib selalu diisi, jadi bukan "filter yang dinyalakan"
              (aturan sama seperti Divisi di reportaccountinglabarugi). ── */
        function updateFilterBadge() {
            $('#filterBadge').text('0 aktif');
        }

        function resetAllFilters() {
            if ($('#modalPerkiraan option').length) {
                $('#modalPerkiraan').prop('selectedIndex', 0);
            }
            updateFilterBadge();
        }

        $('#modalFilter').on('show.bs.modal', function() {
            $('#modalPerkiraan').val(globalPerkiraan);
            updateFilterBadge();
        });

        $('#modalFilter').on('change', 'select.rt-native', updateFilterBadge);

        function applyModalFilter() {
            setPerkiraan($('#modalPerkiraan').val());
            $('#modalFilter').modal('hide');
        }
    </script>
@endsection
