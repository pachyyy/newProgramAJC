@extends('report.masterreport2')

<style>
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
                    <div class="page-title">Ubah Kemasan</div>
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
                        oninput="applyFilters()" style="width:180px">
                </div>

                <!-- Actions: tampilkan + export (tidak ada modal Filter -- satu-satunya isinya,
                     Order By, sudah dipindah ke switcher di bar atas tabel) -->
                <div class="action-group">
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

            <!-- Bar kolom tersembunyi + Order By (diisi oleh report-table.js / ReportTable) -->
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
                Seret judul kolom untuk mengurutkan. Klik <i class="bi bi-gear"></i> pada judul kolom untuk sembunyikan
                kolom atau atur desimal &amp; total.
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
        // Otorisasi & Report Mode (Detail/Rekap) tidak pernah punya kontrol UI sungguhan di
        // halaman lama (dropdown-nya berstatus `hidden`) -- dihilangkan dari UI di migrasi ini.
        // globalOtorisasi tetap dikirim ke SP tapi tidak pernah berubah dari "2", sama seperti
        // sebelum migrasi.
        let globalOtorisasi = "2";
        let globalOrderBy = "N"; // default: Nomor Bukti

        let lastRows = []; // hasil fetch terakhir (dipakai render / export / search)
        let currentGroupby = 'Nobukti'; // groupby aktif untuk render ulang saat search

        const reportUrl = "{{ url('laporanubahkemasan_doReport') }}";

        var modereport_nobukti = 0,
            modereport_barang = 1;
        g_modeReport = modereport_nobukti;

        // Order By: N (Nomor Bukti) / B (Nomor Barang) -- parameter nyata (Ordr) yang dikirim
        // ke proc. Sebelum ini "B" tidak mengubah kolom/groupby sama sekali (bug pra-migrasi,
        // sudah dilaporkan) -- diperbaiki di setOrderBy(): kedua nilai sekarang benar-benar
        // menukar g_modeReport/susunan kolom (lihat setDefaultHeader()) & groupby (makeTable()).
        const ORDER_OPTIONS = [
            { value: 'N', label: 'Nomor Bukti', desc: 'Dikelompokkan per No Bukti' },
            { value: 'B', label: 'Nomor Barang', desc: 'Dikelompokkan per Kode Barang' },
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
            showPeriode();
            setDefaultHeader();
            setOrderBy(globalOrderBy);

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

        // order by: ikut menentukan groupby & susunan kolom (lewat setModeReport)
        function setOrderBy(val) {
            globalOrderBy = val;
            setModeReport();
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
                const v = pickCI(r, c[0]);
                if (c[3] === 'date') return format_date(v);
                if (c[3] === 'float' || c[3] === 'int') return currencyNormalizer(v);
                return (v == null ? '' : v);
            }));
            const rows = [header].concat(body);
            ReportExport.fromRows(rows, fmt, { customName: 'UbahKemasan_' + (globalDate1 || '') + '_' + (globalDate2 || '') });
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

        function pickCI(r, key) {
            if (r[key] !== undefined) return r[key];
            const lk = String(key).toLowerCase();
            for (const k in r) {
                if (k.toLowerCase() === lk) return r[k];
            }
            return undefined;
        }

        function setModeReport() {
            g_modeReport = (globalOrderBy == "B") ? modereport_barang : modereport_nobukti;

            doSetHeader(g_modeReport);
            doShowCustomize();
        }

        function setDefaultHeader() {
            if (g_modeReport == modereport_nobukti) {
                gcart_header = [
                    ['Nobukti', 'No Bukti', 1, 'varchar', 0, 0],
                    ['tanggal', 'Tanggal', 1, 'date', 0, 0],
                    ['kodebrg', 'Kode Barang', 1, 'varchar', 0, 0],
                    ['namaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
                    ['Kodegdg', 'Gdg', 1, 'varchar', 0, 0],
                    ['Harga', 'Harga', 1, 'float', 1, 2],
                    ['Satuan', 'Sat', 1, 'varchar', 0, 0],
                    ['Qntdb', 'Qty Debet', 1, 'float', 1, 2],
                    ['HrgAdi', 'HPP X Debet', 1, 'float', 1, 2],
                    ['QntCr', 'Qty Kredit', 1, 'float', 1, 2],
                    ['HrgADO', 'HPP X Kredit', 1, 'float', 1, 2]
                ];
                gsum_issubtotal = 1;
                gsum_isgrandtotal = 1;
            } else {
                gcart_header = [
                    ['Nobukti', 'No Bukti', 1, 'varchar', 0, 0],
                    ['tanggal', 'Tanggal', 1, 'date', 0, 0],
                    ['kodebrg', 'Kode Barang', 1, 'varchar', 0, 0],
                    ['namaBrg', 'Nama Barang', 1, 'varchar', 0, 0],
                    ['Kodegdg', 'Gdg', 1, 'varchar', 0, 0],
                    ['Harga', 'Harga', 1, 'float', 1, 2],
                    ['Satuan', 'Sat', 1, 'varchar', 0, 0],
                    ['Qntdb', 'Qty Debet', 1, 'float', 1, 2],
                    ['HrgAdi', 'HPP X Debet', 1, 'float', 1, 2],
                    ['QntCr', 'Qty Kredit', 1, 'float', 1, 2],
                    ['HrgADO', 'HPP X Kredit', 1, 'float', 1, 2]
                ];
                gsum_issubtotal = 1;
                gsum_isgrandtotal = 1;
            }
        }

        function makeTable(_mode) {
            let groupby = (globalOrderBy == "B") ? "kodebrg" : "Nobukti";
            let _date1 = $("#inputDate1").val();
            let _date2 = $("#inputDate2").val();

            setDefaultHeader();
            if (typeof doSetHeader === 'function') {
                doSetHeader(g_modeReport);
            }

            let data = {
                date1: _date1,
                date2: _date2,
                inputOto: globalOtorisasi,
                inputOrd: globalOrderBy
            };

            document.getElementById('footerLabel').innerHTML = loadingHtml('Memuat data...');

            $.ajax({
                url: reportUrl,
                type: 'get',
                data: data,
                success: function(res) {
                    lastRows = res || [];
                    currentGroupby = groupby;
                    $('#searchBox2').val('');
                    render();
                },
                error: function(xhr) {
                    console.error('laporanubahkemasan_doReport gagal:', xhr.status, xhr.responseText);
                    showToast('⚠️', 'Gagal memuat data (' + xhr.status + ')');
                    lastRows = [];
                    currentGroupby = groupby;
                    render();
                }
            });
        }

        // === RENDER KE TABEL STYLED (.tb-report #mainTable) ===
        // Kolom dibangun DINAMIS dari gcart_header (hanya kolom yang terlihat / item[2]===1,
        // sesuai urutan simpanan).
        function render() {
            const cols = gcart_header.filter(c => c[2] === 1); // kolom terlihat, terurut
            const keys = cols.filter(c => c[4] === 1).map(c => c[0]); // kolom yang di-subtotal
            const thead = document.querySelector('#mainTable thead');
            const tbody = document.getElementById('tableBody');
            const showSub = (gsum_issubtotal === 1);
            const showGrand = (gsum_isgrandtotal === 1);

            const search = ($('#searchBox2').val() || '').trim().toLowerCase();
            const rows = !search ? (lastRows || []) : (lastRows || []).filter(function(r) {
                return rowSearchText(r, cols).indexOf(search) !== -1;
            });

            // HEADER dinamis — dibangun report-table.js (ReportTable) supaya kolom bisa diseret
            // untuk diurutkan & punya menu roda gigi (sembunyikan / desimal / total).
            thead.innerHTML = ReportTable.headHtml(cols);

            if (!rows.length) {
                tbody.innerHTML = '<tr class="empty-row"><td colspan="' + cols.length +
                    '">Tidak ada data ditemukan.</td></tr>';
                document.getElementById('footerLabel').textContent = 'Tidak ada data';
                return;
            }

            let html = '',
                prev = null;
            let sub = {},
                grand = {};
            keys.forEach(k => {
                sub[k] = 0;
                grand[k] = 0;
            });

            rows.forEach(function(r, i) {
                const now = pickCI(r, currentGroupby);

                // subtotal saat nilai grup berganti (kalau toggle Subtotal aktif)
                if (showSub && i !== 0 && prev !== now) {
                    html += totalRowTotal('Subtotal', sub, cols, keys, 'subtotal-row');
                    keys.forEach(k => {
                        sub[k] = 0;
                    });
                }

                keys.forEach(function(k) {
                    const v = currencyNormalizer(pickCI(r, k));
                    sub[k] += v;
                    grand[k] += v;
                });

                // satu sel per kolom terlihat, format menurut tipe (item[3]) & desimal (item[5])
                html += '<tr class="data-row">' + cols.map(function(c) {
                    const key = c[0],
                        type = c[3];
                    if (type === 'date') return '<td>' + format_date(pickCI(r, key)) + '</td>';
                    if (type === 'float' || type === 'int') return '<td class="num">' + format_number(
                        currencyNormalizer(pickCI(r, key)), c[5]) + '</td>';
                    return '<td>' + nullToEmpty(pickCI(r, key)) + '</td>';
                }).join('') + '</tr>';

                prev = now;
            });

            // subtotal grup terakhir + grand total — mengikuti toggle di modal Customize Table
            if (showSub) html += totalRowTotal('Subtotal', sub, cols, keys, 'subtotal-row');
            if (showGrand) html += totalRowTotal('GRAND TOTAL', grand, cols, keys, 'grand-total');

            tbody.innerHTML = html;
            document.getElementById('footerLabel').textContent = 'Menampilkan ' + rows.length + ' baris';
        }

        // Baris total: nilai di kolom yang di-subtotal (item[4]===1), label di kolom pertama
        // non-total yang masih terlihat, sel lain dikosongkan.
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

        // Gabungan teks satu baris dari kolom terlihat (tanggal pakai format tampil
        // dd/mm/yyyy) supaya pencarian cocok dengan apa yang user lihat di tabel.
        function rowSearchText(r, cols) {
            return cols.map(function(c) {
                const v = pickCI(r, c[0]);
                if (c[3] === 'date') return format_date(v);
                return (v == null ? '' : String(v));
            }).join(' ').toLowerCase();
        }
    </script>
@endsection
