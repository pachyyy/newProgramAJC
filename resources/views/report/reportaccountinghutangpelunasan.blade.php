@extends('report.masterreport2')

{{-- Table styling lives in public/css/report-table.css (loaded via report/newmaster2.blade.php).
     Hutang Pelunasan: styled .tb-report, dikelompokkan per supplier (nama) dengan subtotal +
     grand total (termasuk kolom Selisih/Umur). Kolom (gcart_header) interaktif lewat ReportTable
     (seret/gear/Reset kolom). Mode IDR / $ (valas), Perkiraan, Supplier Awal/Akhir & Urut pindah
     ke modal "Filter Laporan". --}}
<style>
    /* tinggi awal area tabel supaya dropdown tidak terpotong container pendek */
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
                    <div class="page-title">Hutang Pelunasan</div>
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
                    <input class="search-inp" type="text" id="searchBox2" placeholder="Cari data..."
                        oninput="applyFilters()" style="width:160px">
                </div>

                {{-- Mode Valas, Kurs Valas, Perkiraan, Supplier Awal/Akhir & Urut pindah ke modal
                     "Filter Laporan" -- lihat docs/new-filter-modal-ui-guide.md. Nilai sebenarnya
                     tetap di variabel/hidden input yang sama: globalReportMode, #valas_value,
                     #inputPerkiraan, #inputSuppAwal/#inputSuppAkhir, #inputOrd (di dalam modal). --}}

                <!-- Actions: search + filter + tampilkan + export -->
                <div class="action-group">
                    {{-- Dibuka lewat plugin jQuery, BUKAN data-bs-toggle -- lihat catatan di modal Filter. --}}
                    <button class="btn-load" type="button" onclick="$('#modalFilter').modal('show')">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    {{-- <button class="btn-load" onclick="doShowFormCustomizeTable()" title="Customize Table"><i class="fas fa-cog"></i> Customize Table</button> --}}
                    <button class="btn-load" onclick="makeTable('REPORT')" title="Tampilkan laporan"><i
                            class="fas fa-check"></i> Tampilkan</button>
                    <div class="export-wrap" id="exportWrap">
                        <button class="export-btn" onclick="toggleExport()"><i class="bi bi-arrow-down"></i> Export <i
                                class="bi bi-caret-down-fill"></i></button>
                        <div class="export-drop" id="exportDrop">
                            <div class="export-opt" onclick="doExport('Excel')"><i
                                    class="bi bi-journals text-success"></i> Ekspor ke <span class="ext">XLSX</span>
                            </div>
                            <div class="export-opt" onclick="doExport('CSV')"><i class="bi bi-clipboard"></i> Ekspor ke
                                <span class="ext">CSV</span></div>
                            <div class="export-opt" onclick="doExport('Print')"><i
                                    class="bi bi-printer-fill text-warning"></i> Cetak Laporan</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bar kolom tersembunyi (diisi oleh report-table.js / ReportTable) -->
            <div id="rtBar"></div>

            <!-- TABLE (header + rows rendered dynamically from gcart_header; grouped per supplier) -->
            <div class="table-outer">
                <div class="table-wrap">
                    <table class="tb" id="mainTable">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
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
                Seret judul kolom untuk mengurutkan. Klik <i class="bi bi-gear"></i> pada judul kolom untuk sembunyikan kolom atau atur desimal &amp; total.
            </div>

        </div><!-- /content -->

        <!-- TOAST -->
        <div class="toast" id="toast"><span id="ti"></span><span id="tm"></span></div>

    </div><!-- /tb-report -->

    {{-- Modal-modal DILETAKKAN DI LUAR .tb-report supaya reset `.tb-report *{margin:0;padding:0}`
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
                        <div class="rt-group-label">Mode &amp; Urutan</div>
                        <div class="rt-grid-2">
                            <div>
                                <label class="rt-field-label" for="modalReportMode">Mode Valas</label>
                                {{-- Selalu punya nilai (IDR = default) -- pilihan wajib, TIDAK dihitung di
                                     badge. Ganti langsung memuat ulang susunan kolom (gcart_header) mode ini,
                                     sama seperti perilaku dropdown lama. --}}
                                <select class="rt-native" id="modalReportMode" onchange="setReportMode(this.value)">
                                    <option value="IDR">IDR</option>
                                    <option value="$">$ (Valas)</option>
                                </select>
                            </div>
                            <div>
                                <label class="rt-field-label" for="modalOrder">Urut</label>
                                <select class="rt-native" id="modalOrder" onchange="setOrderBy(this.value)">
                                    <option value="0">Tanggal</option>
                                    <option value="1">No.Nota</option>
                                </select>
                            </div>
                        </div>

                        <!-- Kurs Valas (hanya tampil saat Mode Valas = $) -->
                        <div class="rt-grid-1" id="modalValasWrap" style="display:none; margin-top:10px">
                            <label class="rt-field-label">Kurs Valas</label>
                            <div class="rt-combo">
                                <div class="rt-combo-input" onclick="pickValas()" id="valasPickField"></div>
                            </div>
                        </div>
                        <input type="hidden" id="valas_value" value="IDR">
                        <input type="hidden" id="inputOrd" value="0">
                    </div>

                    <div class="rt-section">
                        <div class="rt-group-label">Perkiraan</div>
                        <div class="rt-grid-1">
                            {{-- Diisi dari *_loadperkiraan (loadPerkiraanDropdown()). Selalu punya nilai
                                 (default akun HT pertama) -- wajib, TIDAK dihitung di badge. Memilih akun
                                 lain otomatis menyusun ulang rentang Supplier Awal/Akhir (autoSelectSuppRange). --}}
                            <select class="rt-native" id="modalPerkiraan" onchange="setPerkiraan(this.value, $(this).find(':selected').data('ket'))"></select>
                        </div>
                        <input type="hidden" id="inputPerkiraan" value="-">
                    </div>

                    <div class="rt-section">
                        <div class="rt-group-label">Supplier
                            <span class="rt-group-hint">&mdash; klik untuk memilih</span>
                        </div>
                        {{-- Rentang otomatis (supplier pertama/terakhir dari akun terpilih) -- wajib punya
                             nilai, TIDAK dihitung di badge, sama seperti Perkiraan. --}}
                        <div class="rt-grid-2" id="pickFields"></div>
                        <input type="hidden" id="inputSuppAwal" value="-">
                        <input type="hidden" id="inputSuppAkhir" value="-">
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

    <!-- modal select valas -->
    <div class="modal fade rt-picker-v2" id="formSelectValas" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 1000px">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Valas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table id="tabelSelectValas">
                        <thead>
                            <tr>
                                <th>Valas</th>
                                <th>Keterangan</th>
                                <th>Kurs</th>
                            </tr>
                        </thead>
                        <tbody id="tabel_dataSelectValas"></tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </div>
        </div>
    </div>

    <!-- modal select supplier awal -->
    <div class="modal fade rt-picker-v2" id="formSelectSuppAwal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 1200px">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Supplier Awal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table id="tabelSelectSuppAwal">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Alamat</th>
                                <th>Telpon</th>
                            </tr>
                        </thead>
                        <tbody id="tabel_dataSelectSuppAwal"></tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </div>
        </div>
    </div>

    <!-- modal select supplier akhir -->
    <div class="modal fade rt-picker-v2" id="formSelectSuppAkhir" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 1200px">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Supplier Akhir</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table id="tabelSelectSuppAkhir">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Alamat</th>
                                <th>Telpon</th>
                            </tr>
                        </thead>
                        <tbody id="tabel_dataSelectSuppAkhir"></tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('jsreport')
    <script type="text/javascript">
        let globalDate1 = "{!! date('Y-m-d') !!}";
        let globalDate2 = "{!! date('Y-m-d') !!}";
        let globalOrderBy = "0"; // default: Tanggal
        let globalReportMode = "IDR"; // default: IDR

        let g_reportTitle = "";
        let g_inputPerkiraan = "";

        let lastRows = []; // hasil fetch terakhir (dipakai render / search)

        // Mode NUMERIK (bukan 'IDR'/'$'): DBSIMPANHEADER.reportmode itu kolom integer, jadi
        // mode string membuat header (termasuk toggle Subtotal/Grand Total) TIDAK tersimpan.
        var modereport_detail = 7,
            modereport_rekap = 8;
        g_modeReport = modereport_detail;
        var jenisreport = 0,
            DetOrRekap = 0;

        const reportUrl = "{{ url('reportaccountinghutangpelunasan_doReport') }}";

        // Bottom voucher panel endpoints (report-table.js is loaded via masterreport2).
        // Clicking No LPB / No Bukti Bayar calls openVoucher(no, jenisFromNo(no)); the
        // Jenis is parsed from the number (2nd segment), with LPB→BPL via jenisFromNo.
        window.ReportTableConfig = {
            kasUrl    : "{{ url('reportaccountinghutangpelunasan_doKasharian') }}",
            invoiceUrl: "{{ url('reportaccountinghutangpelunasan_doInvoice') }}",
            lpbUrl    : "{{ url('reportaccountinghutangpelunasan_doLpb') }}",
            bpUrl     : "{{ url('reportaccountinghutangpelunasan_doBp') }}"
        };

        $(document).ready(function() {
            setReportMode(globalReportMode); // set mode + muat gcart_header
            setOrderBy(globalOrderBy);
            loadPerkiraanDropdown(); // isi dropdown Perkiraan (default akun HT pertama)

            // Header tabel interaktif: seret kolom, menu roda gigi (sembunyikan/desimal/total).
            ReportTable.init({
                table: '#mainTable',
                bar: '#rtBar',
                onChange: function () { applyFilters(); }
            });

        });

        /* ── kolom (gcart_header) per mode. Tabel styled DI-RENDER dari sini (Customize Table).
              Kolom uang (Pembayaran/Pembayaran $) dan Selisih (Umur) ditandai total (item[4]=1),
              jadi ikut dijumlah di Subtotal & Grand Total. ── */
        function setDefaultHeader() {
            if (g_modeReport == modereport_detail) {
                gcart_header = [
                    ['nofaktur', 'No LPB', 1, 'varchar', 0, 0],
                    ['tgllpb', 'Tanggal LPB', 1, 'date', 0, 0],
                    ['debet', 'Pembayaran', 1, 'float', 1, 2],
                    ['catatan', 'Catatan', 1, 'varchar', 0, 0],
                    ['nobukti', 'No Bukti Bayar', 1, 'varchar', 0, 0],
                    ['Tanggal', 'Tanggal Bayar', 1, 'date', 0, 0],
                    ['NoInvoice', 'No Nota', 1, 'varchar', 0, 0],
                    ['TglInvoice', 'Tanggal Nota', 1, 'date', 0, 0],
                    ['Umur', 'Selisih', 1, 'float', 1, 2], // ikut dijumlah di Subtotal & Grand Total
                ];
                gsum_issubtotal = 1;
                gsum_isgrandtotal = 1;
            } else {
                gcart_header = [
                    ['Tanggal', 'Tanggal', 1, 'date', 0, 0],
                    ['nofaktur', 'No Nota', 1, 'varchar', 0, 0],
                    ['debet', 'Pembayaran', 1, 'float', 1, 2],
                    ['debetd', 'Pembayaran $', 1, 'float', 1, 2],
                    ['catatan', 'Catatan', 1, 'varchar', 0, 0],
                    ['nobukti', 'No Bukti', 1, 'varchar', 0, 0],
                    ['bank', 'Bank', 1, 'varchar', 0, 0],
                    ['nogiro', 'No Giro', 1, 'varchar', 0, 0],
                    ['tglgiro', 'Tanggal Giro', 1, 'date', 0, 0],
                ];
                gsum_issubtotal = 1;
                gsum_isgrandtotal = 1;
            }
        }

        /* ── toolbar controls ── */
        function showPeriode() {
            globalDate1 = $('#inputDate1').val();
            globalDate2 = $('#inputDate2').val();
        }

        // val: 'IDR' / '$'. Dipanggil oleh <select id="modalReportMode"> (modal Filter Laporan) —
        // ganti langsung memuat ulang gcart_header mode ini (sama seperti perilaku dropdown lama).
        function setReportMode(val) {
            globalReportMode = val;
            $('#modalReportMode').val(val);

            if (val === 'IDR') {
                jenisreport = 0;
                DetOrRekap = 0;
                $('#valas_value').val('IDR');
                $('#modalValasWrap').hide();
            } else {
                jenisreport = 1;
                DetOrRekap = 1;
                $('#valas_value').val('-');   // '-' = belum dipilih (Kurs Valas wajib diisi mode $)
                $('#modalValasWrap').show();
            }
            renderPickFields();
            updateFilterBadge();

            setModeReport();
        }

        function setModeReport() {
            g_modeReport = (globalReportMode === 'IDR') ? modereport_detail : modereport_rekap;
            doSetHeader(g_modeReport); // muat susunan kolom mode ini (default / kustomisasi tersimpan)
            doShowCustomize();
        }

        // val: '0' (Tanggal) / '1' (No.Nota). Dipanggil oleh <select id="modalOrder">.
        function setOrderBy(val) {
            globalOrderBy = val;
            $('#inputOrd').val(val);
            $('#modalOrder').val(val);
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
            const header = ['Supplier'].concat(cols.map(c => c[1]));
            const body = (lastRows || []).map(r => [str(pickCI(r, 'nama'))].concat(cols.map(function(c) {
                const v = pickCI(r, c[0]);
                if (c[3] === 'date') return format_date(v);
                if (c[3] === 'float' || c[3] === 'int') return currencyNormalizer(v);
                return (v == null ? '' : v);
            })));
            const rows = [header].concat(body);
            ReportExport.fromRows(rows, fmt, { customName: 'HutangPelunasan_' + (globalDate1 || '') + '_' + (globalDate2 || '') });
            showToast('📄', 'Data diekspor sebagai ' + fmt);
        }

        /* ── LOAD DATA (Sp_ReportPelunasanHutang; doReport mengembalikan array biasa) ── */
        function makeTable(_mode) {
            globalDate1 = $('#inputDate1').val();
            globalDate2 = $('#inputDate2').val();
            g_reportTitle = 'REPORT ACCOUNTING HUTANG PELUNASAN';

            let _perk = $('#inputPerkiraan').val() || '-';
            let _suppAw = $('#inputSuppAwal').val() || '-';
            let _suppAk = $('#inputSuppAkhir').val() || '-';
            let _ord = $('#inputOrd').val();
            let _valas = $('#valas_value').val();

            if (typeof doSetHeader === 'function') {
                doSetHeader(g_modeReport);
            }

            document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

            const data = {
                date1: globalDate1,
                date2: globalDate2,
                inputSuppAwal: _suppAw,
                inputSuppAkhir: _suppAk,
                inputOrd: _ord,
                inputPerkiraan: _perk,
                valas_value: _valas
            };

            $.ajax({
                url: reportUrl,
                type: 'get',
                data: data,
                success: function(res) {
                    lastRows = Array.isArray(res) ? res : ((res && res.res1) ? res.res1 : []);
                    $('#searchBox2').val('');
                    render();
                },
                error: function() {
                    lastRows = [];
                    render();
                }
            });
        }

        /* ── helpers ── */
        function num(v) {
            if (v === null || v === undefined || v === '') return 0;
            const n = parseFloat(v);
            return isNaN(n) ? 0 : n;
        }

        function str(v) {
            return (v == null ? '' : String(v)).trim();
        }

        function pickCI(r, key) {
            if (r[key] !== undefined) return r[key];
            const lk = String(key).toLowerCase();
            for (const k in r) {
                if (k.toLowerCase() === lk) return r[k];
            }
            return undefined;
        }

        // HTML-escape teks bebas (nama supplier bisa diisi user).
        function esc(v) {
            return String(v == null ? '' : v)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        // No LPB / No Bukti Bayar cell: clickable only for a real voucher number (has
        // '/', and is not an opening "Saldo Awal"/"AWL" row). Opens the bottom voucher
        // panel (report-table.js), dispatching by the Jenis parsed from the number.
        function isVoucherNo(v) {
            const s = str(v);
            if (!s || s.indexOf('/') === -1) return false;
            return s.toUpperCase().indexOf('SALDO AWAL') === -1;
        }

        function voucherCell(v) {
            const s = str(v);
            if (!isVoucherNo(s)) return '<td>' + nullToEmpty(v) + '</td>';
            const jn = (typeof jenisFromNo === 'function') ? jenisFromNo(s) : '';
            const ttl = (typeof jenisTitle === 'function') ? jenisTitle(jn) : 'Voucher';
            const esc = s.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
            const jsc = String(jn).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
            return '<td class="kas-clickable" style="cursor:pointer;color:#0d6efd;text-decoration:underline" ' +
                'title="Klik untuk lihat ' + ttl + ' ' + s + '" ' +
                'onclick="openVoucher(\'' + esc + '\',\'' + jsc + '\')">' + nullToEmpty(v) + '</td>';
        }

        /* ── RENDER: dikelompokkan per supplier (kolom `nama`). Subtotal & Grand Total
           menjumlahkan semua kolom ber-total (item[4]=1), termasuk Selisih/Umur. ── */
        function render() {
            const cols = gcart_header.filter(c => c[2] === 1);
            const thead = document.querySelector('#mainTable thead');
            const tbody = document.getElementById('tableBody');

            const totalCols = cols.filter(c => (c[3] === 'float' || c[3] === 'int') && c[4] === 1);
            const totalKeys = totalCols.map(c => c[0]);
            const hasTotal = totalCols.length > 0;
            const showSub = hasTotal && (gsum_issubtotal === 1);
            const showGrand = hasTotal && (gsum_isgrandtotal === 1);
            const search = ($('#searchBox2').val() || '').trim().toLowerCase();

            // HEADER dinamis — dibangun report-table.js (ReportTable) supaya kolom bisa diseret
            // untuk diurutkan & punya menu roda gigi (sembunyikan / desimal / total).
            thead.innerHTML = ReportTable.headHtml(cols);

            // kelompokkan per supplier (nama), pertahankan urutan kemunculan
            const order = [],
                buckets = {};
            (lastRows || []).forEach(r => {
                if (search && rowSearchText(r, cols).indexOf(search) === -1) return;
                const gkey = str(pickCI(r, 'nama'));
                if (!(gkey in buckets)) {
                    buckets[gkey] = [];
                    order.push(gkey);
                }
                buckets[gkey].push(r);
            });

            if (!order.length) {
                tbody.innerHTML = '<tr class="empty-row"><td colspan="' + cols.length +
                    '">Tidak ada data ditemukan.</td></tr>';
                document.getElementById('footerLabel').textContent = 'Tidak ada data';
                return;
            }

            let html = '';
            const grand = {};
            totalKeys.forEach(k => grand[k] = 0);
            let visible = 0;

            order.forEach(gkey => {
                const rows = buckets[gkey];
                const label = (gkey !== '' ? gkey : '(Tanpa Nama)');

                html += '<tr class="group-row"><td colspan="' + cols.length + '">' + esc(label) +
                    ' <span style="font-size:11px;font-weight:600;opacity:.7;margin-left:8px">(' + rows.length +
                    ' transaksi)</span></td></tr>';

                const sub = {};
                totalKeys.forEach(k => sub[k] = 0);
                rows.forEach(r => {
                    totalKeys.forEach(k => {
                        const v = currencyNormalizer(pickCI(r, k));
                        sub[k] += v;
                        grand[k] += v;
                    });
                    html += '<tr class="data-row">' + cols.map(function(c) {
                        const key = c[0],
                            type = c[3];
                        const v = pickCI(r, key);
                        if (type === 'date') return '<td>' + format_date(v) + '</td>';
                        if (type === 'float' || type === 'int') return '<td class="num">' +
                            format_number(currencyNormalizer(v), c[5]) + '</td>';
                        const kl = String(key).toLowerCase();
                        if (kl === 'nofaktur' || kl === 'nobukti') return voucherCell(v);
                        return '<td>' + nullToEmpty(v) + '</td>';
                    }).join('') + '</tr>';
                    visible++;
                });

                if (showSub) html += totalRow('Subtotal', sub, cols, totalKeys, 'subtotal-row');
            });

            if (showGrand) html += totalRow('GRAND TOTAL', grand, cols, totalKeys, 'grand-total');

            tbody.innerHTML = html;
            document.getElementById('footerLabel').textContent = 'Menampilkan ' + visible + ' baris';
        }

        // Baris total: nilai di tiap kolom pada `sumKeys`; label menempati SELURUH kolom non-sum
        // yang berurutan mulai dari kolom non-sum pertama (bukan cuma satu sel sempit), supaya
        // tidak wrap.
        function totalRow(label, sums, cols, sumKeys, cls) {
            const labelIdx = cols.findIndex(c => sumKeys.indexOf(c[0]) === -1);
            let span = 0;
            for (let i = labelIdx; i < cols.length && sumKeys.indexOf(cols[i][0]) === -1; i++) { span++; }

            const tds = [];
            for (let idx = 0; idx < cols.length; idx++) {
                const c = cols[idx];
                if (sumKeys.indexOf(c[0]) !== -1) { tds.push('<td class="num">' + format_number(sums[c[0]], c[5]) + '</td>'); continue; }
                if (idx === labelIdx) { tds.push('<td colspan="' + span + '">' + label + '</td>'); idx += span - 1; continue; }
                tds.push('<td></td>');
            }
            return '<tr class="' + cls + '">' + tds.join('') + '</tr>';
        }

        /* ── PENCARIAN SISI-KLIEN ── */
        function applyFilters() {
            render();
        }

        function rowSearchText(r, cols) {
            let s = str(pickCI(r, 'nama')); // ikutkan nama supplier
            cols.forEach(function(c) {
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

        function getKolomFilter() {
            return ['Tanggal', 'nofaktur'];
        }

        /* ── PERKIRAAN (akun HT; default akun pertama) — modal Filter Laporan, <select id="modalPerkiraan"> ── */
        function loadPerkiraanDropdown() {
            let list = [];
            $.ajax({
                url: "{!! url('reportaccountinghutangpelunasan_loadperkiraan') !!}",
                type: "get",
                async: false,
                success: function(res) {
                    list = res || [];
                }
            });

            let html = '';
            list.forEach((item) => {
                const ket = (item.Keterangan != null ? String(item.Keterangan) : '');
                html += '<option value="' + item.Perkiraan + '" data-ket="' + esc(ket) + '">' +
                    item.Perkiraan + ' - ' + esc(ket) + '</option>';
            });
            $("#modalPerkiraan").html(html);

            if (list.length) {
                setPerkiraan(list[0].Perkiraan, list[0].Keterangan != null ? list[0].Keterangan : '');
            }
        }

        function setPerkiraan(kode, ket) {
            $("#inputPerkiraan").val(kode);
            $("#modalPerkiraan").val(kode);
            g_inputPerkiraan = kode + (ket ? ' - ' + ket : '');

            // supplier difilter per perkiraan → auto-pilih rentang: Awal = supplier pertama, Akhir = terakhir
            autoSelectSuppRange();
            renderPickFields();
            updateFilterBadge();
        }

        /* ── AUTO-PILIH RENTANG SUPPLIER ──
           Isi Supp Awal = supplier pertama, Supp Akhir = supplier terakhir dari list akun
           (perkiraan) terpilih. List diurut per KodeCustsupp di endpoint, jadi pertama = kode
           terendah, terakhir = kode tertinggi. Dipanggil saat load & setiap ganti Perkiraan. ── */
        function autoSelectSuppRange() {
            let perkiraan = $("#inputPerkiraan").val();
            let list = [];
            $.ajax({
                url: "{!! url('reportaccountinghutangpelunasan_loadsuppawal') !!}",
                type: "get", async: false, data: { perkiraan: perkiraan },
                success: function(res) { list = res || []; }
            });

            if (list.length) {
                $('#inputSuppAwal').val(list[0].KodeCustsupp);
                $('#inputSuppAkhir').val(list[list.length - 1].KodeCustsupp);
            } else {
                $('#inputSuppAwal').val('-');
                $('#inputSuppAkhir').val('-');
            }
        }

        /* ── MODAL SUPPLIER AWAL ── */
        function buttonSelectSuppAwal() {
            loadSelectSuppAwal();
            $("#formSelectSuppAwal").modal('toggle');
        }

        function buttonPilihSuppAwal(kode) {
            $("#inputSuppAwal").val(kode);
            $("#formSelectSuppAwal").modal('hide');
            renderPickFields(); updateFilterBadge();
        }

        function loadSelectSuppAwal() {
            let perkiraan = $("#inputPerkiraan").val();
            let dataRefresh = [];
            if ($.fn.DataTable.isDataTable('#tabelSelectSuppAwal')) {
                $('#tabelSelectSuppAwal').DataTable().destroy();
            }

            $.ajax({
                url: "{!! url('reportaccountinghutangpelunasan_loadsuppawal') !!}",
                type: "get",
                async: false,
                data: {
                    perkiraan: perkiraan
                },
                success: function(res) {
                    dataRefresh = res || [];
                }
            });

            let rowTable = "";
            dataRefresh.forEach((item) => {
                rowTable += `<tr class="pick-row" onclick="buttonPilihSuppAwal('${item.KodeCustsupp}')">
        <td>${esc(item.KodeCustsupp)}</td>
        <td>${esc(item.NamaCust)}</td>
        <td>${esc(item.Alamat ?? '')}</td>
        <td>${esc(item.Telpon ?? '')}</td>
      </tr>`;
            });
            document.getElementById("tabel_dataSelectSuppAwal").innerHTML = rowTable;
            $("#tabelSelectSuppAwal").DataTable({
                "lengthChange": false,
                "paging": true
            });
        }

        /* ── MODAL SUPPLIER AKHIR ── */
        function buttonSelectSuppAkhir() {
            loadSelectSuppAkhir();
            $("#formSelectSuppAkhir").modal('toggle');
        }

        function buttonPilihSuppAkhir(kode) {
            $("#inputSuppAkhir").val(kode);
            $("#formSelectSuppAkhir").modal('hide');
            renderPickFields(); updateFilterBadge();
        }

        function loadSelectSuppAkhir() {
            let perkiraan = $("#inputPerkiraan").val();
            let dataRefresh = [];
            if ($.fn.DataTable.isDataTable('#tabelSelectSuppAkhir')) {
                $('#tabelSelectSuppAkhir').DataTable().destroy();
            }

            $.ajax({
                url: "{!! url('reportaccountinghutangpelunasan_loadsuppawal') !!}",
                type: "get",
                async: false,
                data: {
                    perkiraan: perkiraan
                },
                success: function(res) {
                    dataRefresh = res || [];
                }
            });

            let rowTable = "";
            dataRefresh.forEach((item) => {
                rowTable += `<tr class="pick-row" onclick="buttonPilihSuppAkhir('${item.KodeCustsupp}')">
        <td>${esc(item.KodeCustsupp)}</td>
        <td>${esc(item.NamaCust)}</td>
        <td>${esc(item.Alamat ?? '')}</td>
        <td>${esc(item.Telpon ?? '')}</td>
      </tr>`;
            });
            document.getElementById("tabel_dataSelectSuppAkhir").innerHTML = rowTable;
            $("#tabelSelectSuppAkhir").DataTable({
                "lengthChange": false,
                "paging": true
            });
        }

        /* ── MODAL VALAS ── */
        function buttonSelectValas() {
            loadSelectValas();
            $("#formSelectValas").modal('toggle');
        }

        function buttonPilihValas(kode) {
            $('#valas_value').val(kode);
            $('#formSelectValas').modal('hide');
            renderPickFields(); updateFilterBadge();
        }

        function loadSelectValas() {
            let dataRefresh = [];
            if ($.fn.DataTable.isDataTable('#tabelSelectValas')) {
                $('#tabelSelectValas').DataTable().destroy();
            }

            $.ajax({
                url: "{!! url('reportaccountinghutangpelunasan_loadvalas') !!}",
                type: "get",
                async: false,
                success: function(res) {
                    dataRefresh = res || [];
                }
            });

            let rowTable = "";
            dataRefresh.forEach((item) => {
                rowTable += `<tr class="pick-row" onclick="buttonPilihValas('${item.Kodevls}')">
        <td>${esc(item.Kodevls)}</td>
        <td>${esc(item.NamaVls)}</td>
        <td>${esc(item.Kurs)}</td>
      </tr>`;
            });
            document.getElementById("tabel_dataSelectValas").innerHTML = rowTable;
            $("#tabelSelectValas").DataTable({
                "lengthChange": false,
                "paging": true
            });
        }

        /* ── MODAL FILTER LAPORAN ──
              Supplier Awal/Akhir & Kurs Valas dipilih lewat modal picker halaman ini sendiri
              (formSelectSuppAwal/Akhir/Valas) -- BUKAN modal bersama. Membuka salah satunya
              menyembunyikan modal Filter dulu (BS4/BS5 tidak menumpuk modal dengan bersih),
              lalu dibuka lagi begitu picker ditutup. ── */
        let g_reopenFilter = false;

        function pickSuppAwal()  { g_reopenFilter = true; $('#modalFilter').modal('hide'); buttonSelectSuppAwal(); }
        function pickSuppAkhir() { g_reopenFilter = true; $('#modalFilter').modal('hide'); buttonSelectSuppAkhir(); }
        function pickValas()     { g_reopenFilter = true; $('#modalFilter').modal('hide'); buttonSelectValas(); }

        $(document).on('hidden.bs.modal', '#formSelectSuppAwal, #formSelectSuppAkhir, #formSelectValas', function () {
            if (g_reopenFilter) {
                g_reopenFilter = false;
                $('#modalFilter').modal('show');
                renderPickFields();
                updateFilterBadge();
            }
        });

        // Supplier Awal/Akhir & Perkiraan SELALU punya nilai (auto-pilih / default akun pertama) --
        // tidak ada opsi "Semua", jadi TIDAK dihitung di badge. Kurs Valas ('-' = belum dipilih)
        // PUNYA nilai netral -> dihitung saat mode $ & sudah dipilih.
        function renderPickFields() {
            let html = '';

            html += pickFieldHtml('Supplier Awal',  $('#inputSuppAwal').val(),  'pickSuppAwal');
            html += pickFieldHtml('Supplier Akhir', $('#inputSuppAkhir').val(), 'pickSuppAkhir');

            $('#pickFields').html(html);

            // Kurs Valas: tampil & diisi hanya saat Mode Valas = $
            const valasVal = $('#valas_value').val() || '-';
            const valasSet = (globalReportMode === '$' && valasVal !== '-' && valasVal !== '' && valasVal !== 'IDR');
            let vhtml = '';
            if (valasSet) {
                vhtml += '<span class="rt-combo-tag">' + esc(valasVal) +
                    '<button type="button" onclick="event.stopPropagation(); clearValasField()">&times;</button></span>';
            } else {
                vhtml += '<span class="rt-combo-placeholder">Pilih valas...</span>';
            }
            vhtml += '<span class="rt-combo-chevron">' +
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>' +
                '</span>';
            $('#valasPickField').html(vhtml);
        }

        function pickFieldHtml(label, val, pickFn) {
            const display = (val && val !== '-') ? val : '-';
            let html = '<div>';
            html += '<label class="rt-field-label">' + label + '</label>';
            html += '<div class="rt-combo">';
            html += '<div class="rt-combo-input" onclick="' + pickFn + '()">';
            html += '<span class="rt-combo-tag">' + esc(display) + '</span>';
            html += '<span class="rt-combo-chevron">' +
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>' +
                '</span>';
            html += '</div></div></div>';
            return html;
        }

        function clearValasField() {
            $('#valas_value').val('-');
            renderPickFields();
            updateFilterBadge();
        }

        function updateFilterBadge() {
            let count = 0;
            const valasVal = $('#valas_value').val() || '-';
            if (globalReportMode === '$' && valasVal !== '-' && valasVal !== '' && valasVal !== 'IDR') { count++; }
            $('#filterBadge').text(count + ' aktif');
        }

        function resetAllFilters() {
            setOrderBy('0');
            setReportMode('IDR');   // juga menyembunyikan & mengosongkan Kurs Valas
            if ($('#modalPerkiraan option').length) {
                setPerkiraan($('#modalPerkiraan option').eq(0).val(), $('#modalPerkiraan option').eq(0).data('ket'));
            }
        }

        $('#modalFilter').on('show.bs.modal', function () {
            renderPickFields();
            updateFilterBadge();
        });

        // Mode Valas / Urut / Perkiraan sudah menerapkan diri sendiri langsung lewat onchange
        // (sama seperti perilaku dropdown lama) -- Terapkan hanya menutup modal.
        function applyModalFilter() {
            $('#modalFilter').modal('hide');
        }
    </script>
@endsection
