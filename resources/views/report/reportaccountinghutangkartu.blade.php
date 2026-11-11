@extends('report.masterreport2')

{{-- Table styling lives in public/css/report-table.css (loaded via report/newmaster2.blade.php).
     Hutang Kartu (kartu hutang supplier): styled .tb-report, dikelompokkan per supplier (nama)
     dengan subtotal per supplier + grand total. Perkiraan / Urut / mode Valas (IDR/$) & Kurs
     Valas / Supp Awal / Supp Akhir semua ada di modal "Filter Laporan"; "Tampilan" di bar tabel
     interaktif mencerminkan mode Valas. Supplier & Valas tetap pakai modal pilih (data banyak). --}}
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
                    <div class="page-title">Hutang Kartu</div>
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

                {{-- Mode Valas (IDR/$), Kurs Valas, Perkiraan, Urut & Supp Awal/Akhir dipindah ke
                     modal "Filter Laporan" (lihat di luar .tb-report). Nilai sebenarnya tetap di
                     input hidden #valas_value / #inputPerkiraan / #inputOrd / #inputSuppAwal /
                     #inputSuppAkhir, dibaca makeTable(). --}}
                <input type="hidden" id="valas_value" value="IDR">
                <input type="hidden" id="inputPerkiraan" value="-">
                <input type="hidden" id="inputOrd" value="0">
                <input type="hidden" id="inputSuppAwal" value="-">
                <input type="hidden" id="inputSuppAkhir" value="-">

                <!-- Actions: search + filter modal + customize + tampilkan + export -->
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
                                Ekspor ke <span class="ext">XLSX</span>
                            </div>
                            <div class="export-opt" onclick="doExport('CSV')"><i class="bi bi-clipboard"></i> Ekspor ke
                                <span class="ext">CSV</span>
                            </div>
                            <div class="export-opt" onclick="doExport('Print')"><i
                                    class="bi bi-printer-fill text-warning"></i> Cetak Laporan</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bar kolom tersembunyi + Tampilan (diisi oleh report-table.js / ReportTable) -->
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
                Seret judul kolom untuk mengurutkan. Klik <i class="bi bi-gear"></i> pada judul kolom untuk
                sembunyikan kolom atau atur desimal &amp; total.
            </div>

        </div><!-- /content -->

        <!-- TOAST -->
        <div class="toast" id="toast"><span id="ti"></span><span id="tm"></span></div>

    </div><!-- /tb-report -->

    {{-- Modal-modal DILETAKKAN DI LUAR .tb-report supaya reset `.tb-report *{margin:0;padding:0}`
     di report-table.css tidak merusak padding/margin modal Bootstrap. --}}

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
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 1300px; ">
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
                         $.fn.modal milik BS4 (jQuery dimuat sesudah bundle BS5). data-bs-dismiss
                         dibiarkan untuk jaga-jaga. Lihat new-design-all-report-guide.md §5.1. --}}
                    <button type="button" class="btn-close" aria-label="Close" data-dismiss="modal"
                        data-bs-dismiss="modal" onclick="$('#modalFilter').modal('hide')"></button>
                </div>

                <div class="modal-body">

                    <div class="rt-section">
                        <div class="rt-group-label">Pengaturan Laporan</div>
                        {{-- Perkiraan & Urut satu kolom penuh (bukan rt-grid-2 — tak ada kelas
                             "1 kolom" di report-table.css, jadi child tunggal di grid 2-kolom
                             cuma mengisi setengah). --}}
                        <div style="margin-bottom:10px">
                            <label class="rt-field-label" for="modalPerkiraan">Perkiraan</label>
                            <select class="rt-native" id="modalPerkiraan"></select>
                        </div>
                        <div>
                            <label class="rt-field-label" for="modalOrder">Urut</label>
                            <select class="rt-native" id="modalOrder">
                                <option value="0">Tanggal</option>
                                <option value="1">No.Nota</option>
                            </select>
                        </div>
                        <div class="rt-grid-2" style="margin-top:10px">
                            <div>
                                <label class="rt-field-label" for="modalReportMode">Valas</label>
                                <select class="rt-native" id="modalReportMode">
                                    <option value="IDR">IDR</option>
                                    <option value="$">$ (Valas)</option>
                                </select>
                            </div>
                            {{-- Muncul di sebelah Valas hanya saat mode $ dipilih (lihat 'change'
                                 handler #modalReportMode di jsreport). --}}
                            <div id="modalValasWrap" style="display:none;">
                                <label class="rt-field-label">Kurs Valas</label>
                                <div id="modalValasCombo"></div>
                            </div>
                        </div>
                    </div>

                    <div class="rt-section">
                        <div class="rt-group-label">Filter Data
                            <span class="rt-group-hint">&mdash; klik untuk memilih</span>
                        </div>
                        <div class="rt-grid-2" id="pickFields"></div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="rt-reset-link" onclick="resetAllFilters()">Reset semua</button>
                    <div class="rt-footer-buttons">
                        <button type="button" class="rt-btn rt-btn-ghost" data-dismiss="modal" data-bs-dismiss="modal"
                            onclick="$('#modalFilter').modal('hide')">Batal</button>
                        <button type="button" class="rt-btn rt-btn-primary"
                            onclick="applyModalFilter()">Terapkan</button>
                    </div>
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
        let perkiraanList = []; // daftar akun HT dari loadPerkiraanDropdown (dipakai resetAllFilters)

        // Mode string: 'IDR' (detail) / '$' (valas)
        // Mode NUMERIK (bukan 'IDR'/'$'): DBSIMPANHEADER.reportmode itu kolom integer, jadi
        // mode string membuat header (termasuk toggle Subtotal/Grand Total) TIDAK tersimpan.
        var modereport_detail = 5,
            modereport_rekap = 6;
        g_modeReport = modereport_detail;
        var jenisreport = 0,
            DetOrRekap = 0;

        const reportUrl = "{{ url('reportaccountinghutangkartu_doReport') }}";

        // Bottom voucher panel endpoints (report-table.js is loaded via masterreport2).
        // Clicking a No Nota / No Bukti calls openVoucher(no, jenisFromNo(no)); the Jenis
        // is parsed from the number (2nd segment), with LPB→BPL handled by jenisFromNo.
        window.ReportTableConfig = {
            kasUrl: "{{ url('reportaccountinghutangkartu_doKasharian') }}",
            invoiceUrl: "{{ url('reportaccountinghutangkartu_doInvoice') }}",
            lpbUrl: "{{ url('reportaccountinghutangkartu_doLpb') }}",
            bpUrl: "{{ url('reportaccountinghutangkartu_doBp') }}"
        };

        $(document).ready(function() {
            setReportMode(globalReportMode); // set mode + muat gcart_header
            setOrderBy(globalOrderBy);
            loadPerkiraanDropdown(); // isi dropdown Perkiraan (default akun HT pertama)

            // Header tabel interaktif (drag/gear/hide/decimal/total). "Tampilan" di sini hanya
            // MENCERMINKAN select "Valas" di modal Filter Laporan — keduanya lewat
            // setReportMode(), tidak ada state baru.
            ReportTable.init({
                table: '#mainTable',
                bar: '#rtBar',
                onChange: render,
                views: {
                    label: 'Tampilan',
                    options: [{
                            value: 'IDR',
                            label: 'IDR',
                            desc: 'Rincian dalam Rupiah'
                        },
                        {
                            value: '$',
                            label: 'Valas ($)',
                            desc: 'Termasuk kolom $ & kurs'
                        }
                    ],
                    get: function() {
                        return globalReportMode;
                    },
                    set: function(v) {
                        setReportMode(String(v));
                        // valas_value ikut dikirim ke Sp_ReportKartuHutang (beda hasil per mode),
                        // jadi ganti Tampilan perlu query ulang — bukan cuma susun ulang kolom.
                        if (lastRows.length) {
                            makeTable('REPORT');
                        }
                    }
                }
            });
        });

        /* ── kolom (gcart_header) per mode. Tabel styled DI-RENDER dari sini (Customize Table).
              Kolom nominal ditandai total (item[4]=1); Subtotal per supplier & Grand Total ikut
              toggle Customize Table (gsum_issubtotal/gsum_isgrandtotal). ── */
        function setDefaultHeader() {
            if (g_modeReport == modereport_detail) {
                gcart_header = [
                    ['Tanggal', 'Tanggal', 1, 'date', 0, 0],
                    ['NoFaktur', 'No Nota', 1, 'varchar', 0, 0],
                    ['NoBukti', 'No Bukti', 1, 'varchar', 0, 0],
                    ['debet1', 'Jumlah Rp', 1, 'float', 1, 2],
                    ['kredit1', 'Bayar Rp', 1, 'float', 1, 2],
                    ['SelisihKurs', 'Selisih Kurs', 1, 'float', 1, 2],
                    ['SaldoRp', 'Saldo Rp', 1, 'float', 1, 2],
                    ['hari', 'Hari', 1, 'varchar', 0, 0],
                ];
                gsum_issubtotal = 1;
                gsum_isgrandtotal = 1;
            } else {
                gcart_header = [
                    ['Tanggal', 'Tanggal', 1, 'date', 0, 0],
                    ['NoFaktur', 'No Nota', 1, 'varchar', 0, 0],
                    ['NoBukti', 'No Bukti', 1, 'varchar', 0, 0],
                    ['debet1', 'Jumlah Rp', 1, 'float', 1, 2],
                    ['kredit1', 'Bayar Rp', 1, 'float', 1, 2],
                    ['SelisihKurs', 'Selisih Kurs', 1, 'float', 1, 2],
                    ['SaldoRp', 'Saldo Rp', 1, 'float', 1, 2],
                    ['debetd1', 'Jumlah $', 1, 'float', 1, 2],
                    ['kreditd1', 'Bayar $', 1, 'float', 1, 2],
                    ['SaldoD', 'Saldo $', 1, 'float', 1, 2],
                    ['kurs', 'Kurs', 1, 'float', 1, 2],
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

        function setReportMode(val) {
            globalReportMode = val;

            if (val === 'IDR') {
                jenisreport = 0;
                DetOrRekap = 0;
                $('#valas_value').val('IDR');
            } else {
                jenisreport = 1;
                DetOrRekap = 1;
                // kosongkan supaya user wajib pilih ulang mata uang lewat modal Filter setiap
                // kali pindah ke mode $ (perilaku lama, dipertahankan)
                $('#valas_value').val('');
            }

            setModeReport();
        }

        function setModeReport() {
            g_modeReport = (globalReportMode === 'IDR') ? modereport_detail : modereport_rekap;
            doSetHeader(g_modeReport); // muat susunan kolom mode ini (default / kustomisasi tersimpan)
            doShowCustomize();
        }

        function setOrderBy(val) {
            globalOrderBy = val;
            $('#inputOrd').val(val);
        }

        /* ── FILTER MODAL (Perkiraan, Urut, Supp Awal/Akhir) ──
           Nilai sebenarnya tetap di input hidden #inputPerkiraan / #inputOrd / #inputSuppAwal /
           #inputSuppAkhir (dibaca makeTable(), ditulis di sini / buttonPilihSuppAwal /
           buttonPilihSuppAkhir). Kontrol di modal (#modalPerkiraan, #modalOrder, .rt-combo) hanya
           tampilan pending di atasnya sampai "Terapkan" diklik. ── */
        const PICK_FIELDS = [{
                id: 'inputSuppAwal',
                label: 'Supp Awal',
                open: 'suppAwal'
            },
            {
                id: 'inputSuppAkhir',
                label: 'Supp Akhir',
                open: 'suppAkhir'
            },
        ];

        function renderPickFields() {
            let html = '';
            PICK_FIELDS.forEach(function(f) {
                const val = $('#' + f.id).val() || '-';
                const isSet = (val !== '-' && val !== '');
                html += '<div>';
                html += '<label class="rt-field-label">' + f.label + '</label>';
                html += '<div class="rt-combo">';
                html += '<div class="rt-combo-input" onclick="pickFromModal(\'' + f.open + '\')">';
                if (isSet) {
                    html += '<span class="rt-combo-tag">' + val +
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

        // Kotak Kurs Valas (di sebelah select Valas, hanya tampil saat mode $) — pola sama
        // dengan renderPickFields(), tapi nilainya #valas_value (bukan salah satu PICK_FIELDS)
        // dan langsung ter-commit begitu dipilih (bukan menunggu Terapkan), sama seperti
        // Supp Awal/Akhir.
        function renderValasPick() {
            const val = $('#valas_value').val() || '';
            const isSet = (val !== '' && val !== '-' && val !== 'IDR');
            let html = '<div class="rt-combo">';
            html += '<div class="rt-combo-input" onclick="pickFromModal(\'valas\')">';
            if (isSet) {
                html += '<span class="rt-combo-tag">' + val +
                    '<button type="button" onclick="event.stopPropagation(); clearValasPick()">&times;</button></span>';
            } else {
                html += '<span class="rt-combo-placeholder">Pilih kurs valas...</span>';
            }
            html += '<span class="rt-combo-chevron">' +
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>' +
                '</span>';
            html += '</div></div>';
            $('#modalValasCombo').html(html);
        }

        function clearValasPick() {
            $('#valas_value').val('');
            renderValasPick();
            updateFilterBadge();
        }

        // Perkiraan & Urut: pilihan wajib (tanpa opsi netral seperti "Semua") — sengaja tidak
        // dihitung, sama seperti Order By di reportmarketinglaporanoutso. Valas: IDR = netral
        // (default), jadi cuma dihitung saat pindah ke $.
        function updateFilterBadge() {
            let count = 0;
            PICK_FIELDS.forEach(function(f) {
                const val = $('#' + f.id).val();
                if (val && val !== '-') {
                    count++;
                }
            });
            if ($('#modalReportMode').val() !== 'IDR') {
                count++;
            }
            $('#filterBadge').text(count + ' aktif');
        }

        function resetAllFilters() {
            $('#modalOrder').val('0');
            if (perkiraanList.length) {
                $('#modalPerkiraan').val(perkiraanList[0].Perkiraan);
            }
            $('#modalReportMode').val('IDR');
            $('#modalValasWrap').hide();
            $('#valas_value').val('IDR');
            PICK_FIELDS.forEach(function(f) {
                $('#' + f.id).val('-');
            });
            renderPickFields();
            renderValasPick();
            updateFilterBadge();
        }

        // Saat modal Filter dibuka ulang otomatis sesudah picker Supp Awal/Akhir/Valas ditutup
        // (lihat pickFromModal / hidden.bs.modal di bawah), JANGAN timpa ulang pilihan pending
        // (Perkiraan/Urut/Valas) dari nilai yang sudah di-Terapkan — kalau tidak, pilihan
        // Perkiraan yang belum di-Terapkan hilang begitu user selesai memilih Supp Awal/Akhir.
        // g_reopeningFilter ditandai true sesaat sebelum modal dibuka ulang di jalur itu saja.
        let g_reopeningFilter = false;

        $('#modalFilter').on('show.bs.modal', function() {
            if (!g_reopeningFilter) {
                $('#modalPerkiraan').val($('#inputPerkiraan').val());
                $('#modalOrder').val(globalOrderBy);
                $('#modalReportMode').val(globalReportMode);
                $('#modalValasWrap').toggle(globalReportMode !== 'IDR');
            }
            g_reopeningFilter = false;
            renderPickFields();
            renderValasPick();
            updateFilterBadge();
        });

        $('#modalFilter').on('change', 'select.rt-native', updateFilterBadge);

        // Ganti Perkiraan (pending, belum Terapkan) membatalkan pilihan Supp Awal/Akhir yang
        // sedang pending juga, sama seperti perilaku lama saat Perkiraan diganti di toolbar —
        // daftar supplier tergantung Perkiraan yang dipilih.
        $('#modalFilter').on('change', '#modalPerkiraan', function() {
            $('#inputSuppAwal').val('-');
            $('#inputSuppAkhir').val('-');
            renderPickFields();
            updateFilterBadge();
        });

        // Ganti mode Valas (pending, belum Terapkan): tampilkan/sembunyikan kotak Kurs Valas
        // di sebelahnya secara langsung.
        $('#modalFilter').on('change', '#modalReportMode', function() {
            $('#modalValasWrap').toggle($(this).val() !== 'IDR');
            renderValasPick();
        });

        function applyModalFilter() {
            const kode = $('#modalPerkiraan').val();
            const ket = $('#modalPerkiraan option:selected').data('ket') || '';
            setPerkiraan(kode, ket);
            if ($('#modalOrder').length) {
                setOrderBy($('#modalOrder').val());
            }
            if ($('#modalReportMode').length) {
                setReportMode($('#modalReportMode').val());
            }
            $('#modalFilter').modal('hide');
        }

        // Jembatan ke modal pilih Supp Awal/Akhir/Valas: sembunyikan modal Filter dulu (hindari
        // Bootstrap stacked-modal), lalu buka lagi setelah modal pilih ditutup.
        let g_reopenFilter = false;

        function pickFromModal(which) {
            g_reopenFilter = true;
            $('#modalFilter').modal('hide');
            if (which === 'suppAwal') {
                buttonSelectSuppAwal();
            } else if (which === 'suppAkhir') {
                buttonSelectSuppAkhir();
            } else if (which === 'valas') {
                buttonSelectValas();
            }
        }

        $(document).on('hidden.bs.modal', '#formSelectSuppAwal, #formSelectSuppAkhir, #formSelectValas',
            function() {
                if (g_reopenFilter) {
                    g_reopenFilter = false;
                    g_reopeningFilter = true;
                    $('#modalFilter').modal('show');
                    // 'show.bs.modal' juga memanggil ini, tapi panggil lagi di sini supaya kotak
                    // .rt-combo langsung terupdate walau modal masih dalam proses transisi tampil.
                    renderPickFields();
                    renderValasPick();
                    updateFilterBadge();
                }
            });

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
            assignRunning(lastRows); // saldo berjalan (Saldo Rp / Saldo $) samakan dengan tampilan tabel
            const body = (lastRows || []).map(r => [str(pickCI(r, 'nama'))].concat(cols.map(function(c) {
                const v = pickCI(r, c[0]);
                if (c[3] === 'date') return format_date(v);
                if (c[3] === 'float' || c[3] === 'int') return (isRunningKey(c[0]) && r._run) ? r._run[c[
                    0]] : currencyNormalizer(v);
                return (v == null ? '' : v);
            })));
            const rows = [header].concat(body);
            ReportExport.fromRows(rows, fmt, { customName: 'HutangKartu_' + (globalDate1 || '') + '_' + (globalDate2 || '') });
            showToast('📄', 'Data diekspor sebagai ' + fmt);
        }

        /* ── LOAD DATA (Sp_ReportKartuHutang; doReport mengembalikan array biasa) ── */
        function makeTable(_mode) {
            globalDate1 = $('#inputDate1').val();
            globalDate2 = $('#inputDate2').val();
            g_reportTitle = 'REPORT ACCOUNTING HUTANG KARTU';

            let _perk = $('#inputPerkiraan').val() || '-';
            let _suppAw = $('#inputSuppAwal').val() || '-';
            let _suppAk = $('#inputSuppAkhir').val() || '-';
            let _ord = $('#inputOrd').val();
            let _valas = $('#valas_value').val();

            // muat susunan kolom (default / kustomisasi tersimpan) + gsum flags
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

        /* ── SALDO BERJALAN (running balance) ──
           Kolom Saldo Rp / Saldo $ dihitung ulang di sisi klien seperti drill "Saldo Berjalan"
           di Trial Balance. Per supplier: baris pertama memakai Saldo (Saldo Awal) dari SP
           sebagai saldo pembuka, baris berikutnya = saldo sebelumnya + debet - kredit
           (Selisih Kurs diabaikan). Fungsi bersama: assignRunningSaldo / isRunningSaldo di
           public/js/report-table.js (stempel r._run atas SELURUH data per supplier, tak
           terpengaruh pencarian, agar render & export konsisten). */
        function isRunningKey(key) {
            return isRunningSaldo(key);
        }

        function assignRunning(rows) {
            return assignRunningSaldo(rows);
        }

        // No Nota / No Bukti cell: clickable only for a real voucher number (has '/', and
        // is not an opening "Saldo Awal"/"AWL" row). Opens the bottom voucher panel
        // (report-table.js), dispatching by the Jenis parsed from the number itself.
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

        /* ── RENDER: dikelompokkan per supplier (kolom `nama`) ──
           Kolom dinamis dari gcart_header (item[2]===1). Setiap supplier: baris header
           (nama), transaksi, lalu Subtotal; di bawah semua ada Grand Total. Subtotal &
           Grand Total mengikuti toggle Customize Table (gsum_issubtotal/gsum_isgrandtotal). ── */
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

            // HEADER dinamis + interaktif (drag/gear/hide/desimal/total) lewat ReportTable
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

            // hitung saldo berjalan (Saldo Rp / Saldo $) atas seluruh data per supplier
            assignRunning(lastRows);

            let html = '';
            const grand = {};
            totalKeys.forEach(k => grand[k] = 0);
            let visible = 0;

            order.forEach(gkey => {
                const rows = buckets[gkey];
                const label = (gkey !== '' ? gkey : '(Tanpa Nama)');

                // header supplier
                html += '<tr class="group-row"><td colspan="' + cols.length + '">' + label +
                    ' <span style="font-size:11px;font-weight:600;opacity:.7;margin-left:8px">(' + rows.length +
                    ' transaksi)</span></td></tr>';

                const sub = {};
                totalKeys.forEach(k => sub[k] = 0);
                const endRun = {}; // saldo akhir supplier: saldo berjalan baris terakhir yang tampil
                rows.forEach(r => {
                    // kolom saldo berjalan tak dijumlah; kolom lain dijumlah seperti biasa
                    totalKeys.forEach(k => {
                        if (isRunningKey(k)) return;
                        const v = currencyNormalizer(pickCI(r, k));
                        sub[k] += v;
                        grand[k] += v;
                    });
                    totalKeys.forEach(k => {
                        if (isRunningKey(k) && r._run) endRun[k] = r._run[k];
                    });
                    html += '<tr class="data-row">' + cols.map(function(c) {
                        const key = c[0],
                            type = c[3];
                        const v = pickCI(r, key);
                        if (type === 'date') return '<td>' + format_date(v) + '</td>';
                        if (type === 'float' || type === 'int') {
                            const val = (isRunningKey(key) && r._run) ? r._run[key] :
                                currencyNormalizer(v);
                            return '<td class="num">' + format_number(val, c[5]) + '</td>';
                        }
                        const kl = String(key).toLowerCase();
                        if (kl === 'nofaktur' || kl === 'nobukti') return voucherCell(v);
                        return '<td>' + nullToEmpty(v) + '</td>';
                    }).join('') + '</tr>';
                    visible++;
                });

                // Subtotal & Grand Total kolom saldo = saldo akhir (bukan penjumlahan)
                totalKeys.forEach(k => {
                    if (isRunningKey(k)) {
                        sub[k] = endRun[k] || 0;
                        grand[k] += endRun[k] || 0;
                    }
                });

                if (showSub) html += totalRow('Subtotal', sub, cols, totalKeys, 'subtotal-row');
            });

            if (showGrand) html += totalRow('GRAND TOTAL', grand, cols, totalKeys, 'grand-total');

            tbody.innerHTML = html;
            document.getElementById('footerLabel').textContent = 'Menampilkan ' + visible + ' baris';
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
            return ['Tanggal', 'NoFaktur'];
        }

        /* ── DROPDOWN PERKIRAAN (akun HT; default akun pertama) ── */
        function loadPerkiraanDropdown() {
            let list = [];
            $.ajax({
                url: "{!! url('reportaccountinghutangkartu_loadperkiraan') !!}",
                type: "get",
                async: false,
                success: function(res) {
                    list = res || [];
                }
            });

            perkiraanList = list;

            let html = '';
            list.forEach((item) => {
                const ket = (item.Keterangan != null ? String(item.Keterangan) : '');
                html += '<option value="' + item.Perkiraan + '" data-ket="' + ket.replace(/"/g, '&quot;') + '">' +
                    item.Perkiraan + ' - ' + ket + '</option>';
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
        }

        /* ── MODAL SUPPLIER AWAL ── */
        function buttonSelectSuppAwal() {
            loadSelectSuppAwal();
            $("#formSelectSuppAwal").modal('toggle');
        }

        function buttonPilihSuppAwal(kode) {
            $("#inputSuppAwal").val(kode);
            $("#inputSuppAkhir").val(kode);
            $("#formSelectSuppAwal").modal('hide');
        }

        function loadSelectSuppAwal() {
            // Baca dari #modalPerkiraan (pending, belum Terapkan), bukan #inputPerkiraan yang
            // sudah di-commit — picker dibuka dari dalam modal Filter sebelum Terapkan diklik.
            let perkiraan = $("#modalPerkiraan").length ? $("#modalPerkiraan").val() : $("#inputPerkiraan")
                .val();
            let dataRefresh = [];
            if ($.fn.DataTable.isDataTable('#tabelSelectSuppAwal')) {
                $('#tabelSelectSuppAwal').DataTable().destroy();
            }

            $.ajax({
                url: "{!! url('reportaccountinghutangkartu_loadsuppawal') !!}",
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
        <td>${item.KodeCustsupp}</td>
        <td>${item.NamaCust}</td>
        <td>${item.Alamat ?? ''}</td>
        <td>${item.Telpon ?? ''}</td>
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
        }

        function loadSelectSuppAkhir() {
            // Baca dari #modalPerkiraan (pending, belum Terapkan), bukan #inputPerkiraan yang
            // sudah di-commit — picker dibuka dari dalam modal Filter sebelum Terapkan diklik.
            let perkiraan = $("#modalPerkiraan").length ? $("#modalPerkiraan").val() : $("#inputPerkiraan")
                .val();
            let dataRefresh = [];
            if ($.fn.DataTable.isDataTable('#tabelSelectSuppAkhir')) {
                $('#tabelSelectSuppAkhir').DataTable().destroy();
            }

            $.ajax({
                url: "{!! url('reportaccountinghutangkartu_loadsuppawal') !!}",
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
        <td>${item.KodeCustsupp}</td>
        <td>${item.NamaCust}</td>
        <td>${item.Alamat ?? ''}</td>
        <td>${item.Telpon ?? ''}</td>
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
        }

        function loadSelectValas() {
            let dataRefresh = [];
            if ($.fn.DataTable.isDataTable('#tabelSelectValas')) {
                $('#tabelSelectValas').DataTable().destroy();
            }

            $.ajax({
                url: "{!! url('reportaccountinghutangkartu_loadvalas') !!}",
                type: "get",
                async: false,
                success: function(res) {
                    dataRefresh = res || [];
                }
            });

            let rowTable = "";
            dataRefresh.forEach((item) => {
                rowTable += `<tr class="pick-row" onclick="buttonPilihValas('${item.Kodevls}')">
        <td>${item.Kodevls}</td>
        <td>${item.NamaVls}</td>
        <td>${item.Kurs}</td>
      </tr>`;
            });
            document.getElementById("tabel_dataSelectValas").innerHTML = rowTable;
            $("#tabelSelectValas").DataTable({
                "lengthChange": false,
                "paging": true
            });
        }
    </script>
@endsection
