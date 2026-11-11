/* ============================================================================
 * Tabel interaktif #page1 (list Kas) — port dari accounting/pengajuandph.blade.php,
 * lihat docs/new-design-gudang-style-guide.md. Menggantikan DataTable() polos + render
 * gabungan for-loop Blade lama plus loadAll() lama; satu fungsi renderTabel() dipakai
 * untuk paint pertama maupun tiap refresh loadAll() supaya keduanya tidak lagi bisa beda.
 *
 * renderTabel()/pagination di bawah diganti total memakai jQuery DataTables (persis pola
 * accounting/memorialkoreksi.blade.php / accounting/pengajuandph.blade.php), menggantikan
 * pager manual lama (renderPager2/gotoPage2/pgBtn/#footerLabel1/#pagerBtns1) — lihat
 * permintaan "samakan dengan bonsementara/memorialkoreksi" yang sudah diterapkan lebih dulu
 * di accounting/pengajuandph.blade.php, accounting/pengajuandphtunai.blade.php, dan
 * accounting/bank.blade.php (this page's Bank twin, identical conversion). Halaman ini
 * tidak punya filter Periode (rentang tanggal) — sengaja tidak ditambahkan, bukan bagian
 * dari fitur halaman ini.
 * ========================================================================= */
let lastRows = (KAS_INITIAL_ROWS); // dbTrans/dbTransaksi sudah di-GROUP BY per NoBukti di controller
let globalOtorisasi = "2"; // filter modal: 2=Semua, 1=Sudah Otorisasi, 0=Belum Otorisasi
let globalTipeTrans = ""; // filter modal: "" = Semua, "BKK", "BKM"

let tabelLen2 = 10; // dipakai sebagai pageLength DataTables di renderTabel()

var g_href = 'kas';
var g_modeReport = '1';
var gcart_header = [];
var gsum_issubtotal = 0;
var gsum_isgrandtotal = 0;
var gct_desimal_max = 4;

// IsOtorisasi1/OtoUser1/TglOto1 SENGAJA tidak ada di sini — seperti
// memorialkoreksi.blade.php, ketiganya bukan kolom yang bisa digeser/disembunyikan,
// melainkan tiga kolom tetap (Oto/User Oto/Tgl Oto) yang selalu ditambahkan di ujung
// kanan tabel oleh renderTabel(), lihat catatan di sana.
function setDefaultHeader() {
    // [ field, label, visible, type, total, decimals ]
    gcart_header = [
        ['NoBukti', 'No. Bukti', 1, 'varchar', 0, 0],
        ['Tanggal', 'Tanggal', 1, 'date', 0, 0],
        ['TipeTransHd', 'Trans', 1, 'varchar', 0, 0],
        ['Perkiraan', 'Perk.', 1, 'varchar', 0, 0],
        ['Note', 'Ket.', 1, 'varchar', 0, 0],
        ['TotalRp', 'Jumlah Rp', 1, 'float', 0, 2],
    ];
}

function doSetHeader(_modereport, _isReset = false) {
    let _strHeader = (!_isReset) ? doLoadHeader(g_href, _modereport) : "";

    if (_strHeader != "") {
        gcart_header = doGetHeader(_strHeader);
    } else if ($.isFunction(window.setDefaultHeader)) {
        setDefaultHeader();
        doSimpanHeader(g_href, g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);
    }
}

function doLoadHeader(_href, _mode) {
    let _header = "";

    $.ajax({
        url: KAS_ROUTES.globalfunctions_doLoadHeader,
        type: "get",
        async: false,
        data: {
            href: _href,
            mode: _mode
        },
        success: function(res) {
            _header = (res.length > 0) ? res[0].header : "";
            if (res.length > 0) {
                gsum_issubtotal = Number(res[0].issubtotal);
                gsum_isgrandtotal = Number(res[0].isgrandtotal);
            }
        }
    })

    return _header;
}

function doGetHeader(_strHeader) {
    let _cart = [];

    _strHeader.split("||").forEach((item, i) => {
        let temp = [];
        temp.push(item.split(";;")[0]);
        temp.push(item.split(";;")[1]);
        temp.push(Number(item.split(";;")[2]));
        temp.push(item.split(";;")[3]);
        temp.push(Number(item.split(";;")[4]));
        temp.push(Number(item.split(";;")[5]));
        _cart.push(temp);
    });

    return _cart;
}

function doSimpanHeader(_href, _mode, _cart, _issubtotal, _isgrandtotal) {
    let _strHeader = "";

    _cart.forEach((item, i) => {
        if (i != 0) {
            _strHeader += '||';
        }
        _strHeader += item[0] + ';;' + item[1] + ';;' + item[2] + ';;' + item[3] + ';;' + item[4] + ';;' +
            item[5];
    });

    $.ajax({
        url: KAS_ROUTES.globalfunctions_doSimpanHeader,
        type: "get",
        async: false,
        data: {
            href: _href,
            mode: _mode,
            header: _strHeader,
            issubtotal: _issubtotal,
            isgrandtotal: _isgrandtotal
        },
        success: function(res) {
            // nothing to do
        }
    })
}

function doMoveHeader(_from, _to) {
    if (_from < 0 || _to < 0 || _from === _to) {
        return;
    }
    if (_from >= gcart_header.length || _to >= gcart_header.length) {
        return;
    }

    let _moved = gcart_header.splice(_from, 1)[0];
    gcart_header.splice(_to, 0, _moved);

    doSimpanHeader(g_href, g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);
}

function doButtonVisibility(_id) {
    gcart_header[_id][2] = (Number(gcart_header[_id][2]) === 1) ? 0 : 1;

    doSimpanHeader(g_href, g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);
}

function doSetDesimal(_index, _step) {
    let _next = Number(gcart_header[_index][5]) + _step;
    if (_next < 0 || _next > gct_desimal_max) {
        return;
    }

    gcart_header[_index][5] = _next;
    doSimpanHeader(g_href, g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);
}

function doButtonTotal(_index) {
    gcart_header[_index][4] = (Number(gcart_header[_index][4]) === 1) ? 0 : 1;

    doSimpanHeader(g_href, g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);
}

// Ambil field dari row tanpa peduli besar/kecil huruf.
function pickCI(r, key) {
    if (r[key] !== undefined) {
        return r[key];
    }
    let lk = String(key).toLowerCase();
    for (let k in r) {
        if (k.toLowerCase() === lk) {
            return r[k];
        }
    }
    return null;
}

function nullToEmpty(v) {
    return (v === null || v === undefined) ? '' : v;
}

// #modalOtorisasi: 2=Semua, 1=Sudah, 0=Belum — client-side saja.
function filterByOtorisasi(rows, filterVal) {
    if (filterVal === '1') {
        return rows.filter(r => Number(pickCI(r, 'IsOtorisasi1')) === 1);
    }
    if (filterVal === '0') {
        return rows.filter(r => Number(pickCI(r, 'IsOtorisasi1')) === 0);
    }
    return rows;
}

// #modalTipeTrans: "" = Semua, "BKK"/"BKM" — client-side saja.
function filterByTipeTrans(rows, filterVal) {
    if (!filterVal) return rows;
    return rows.filter(r => String(pickCI(r, 'TipeTransHd') || '').trim().toUpperCase() === filterVal);
}

// Satu-satunya tempat yang menentukan tombol Aksi — dipakai renderTabel() untuk paint pertama
// MAUPUN tiap refresh loadAll(), jadi tidak bisa lagi berbeda seperti sebelumnya. Markup +
// pemetaan warna/ikon disalin persis dari renderTabelMk() di memorialkoreksi.blade.php
// (.po-aksi-wrap, cuma title, tanpa tooltip Bootstrap).
function aksiButtonsHtml(r) {
    const nobukti = r.NoBukti;
    let tombolAksi = '<button class="btn btn-warning btn-sm" type="button" title="Detail" onclick="buttonDetail(\'' +
        nobukti + '\', \'detail\')"><i class="bi bi-info"></i></button>';

    if (Number(pickCI(r, 'IsOtorisasi1')) === 1) {
        // Sudah otorisasi — Batal Otorisasi + Cetak
        tombolAksi += '<button class="btn btn-danger btn-sm" type="button" title="Batal Otorisasi" onclick="buttonBatalOtorisasi(\'' +
            nobukti + '\')"><i class="bi bi-key"></i></button>' +
            '<button class="btn btn-primary btn-sm" type="button" title="Cetak" onclick="submitPrint(\'' +
            nobukti + '\')"><i class="bi bi-printer"></i></button>';
    } else {
        // Belum otorisasi — Koreksi + Otorisasi
        tombolAksi += '<button class="btn btn-success btn-sm" type="button" title="Koreksi" onclick="buttonKoreksi(\'' +
            nobukti + '\')"><i class="bi bi-pen"></i></button>' +
            '<button class="btn btn-info btn-sm" type="button" title="Otorisasi" onclick="buttonDetail(\'' +
            nobukti + '\', \'otorisasi\')"><i class="bi bi-key"></i></button>';
    }

    return '<div class="po-aksi-wrap">' + tombolAksi + '</div>';
}

/* Paginasi tabel picker (default 10 baris/halaman; pilihan Tampilkan 10/25/50/100/Semua
   lewat lengthMenu bawaan DataTables, kembali ke 10 tiap picker dibuka). Dipanggil SETELAH <tbody> diisi dan
   SEBELUM modal-nya di-show(). Placeholder ber-colspan dibuang dulu supaya
   DataTables tidak menganggapnya baris data (jumlah kolomnya tidak cocok);
   pesan kosongnya diserahkan ke language.emptyTable.

   Dua tabel sengaja TIDAK memakai ini — tabel_add_list_invoice dan
   tabel_add_list_dphuhtbkm — karena barisnya berisi input yang dibaca lewat
   getElementById() untuk SEMUA baris, sedangkan DataTables melepas baris di luar
   halaman aktif dari DOM. */
function kasInitPicker(idTabel, opsi) {
    var sel = '#' + idTabel;
    if ($.fn.DataTable.isDataTable(sel)) {
        // destroy() (DataTables 1.10) mengosongkan <tbody> lalu memasang kembali baris-baris
        // LAMA dari cache-nya, sehingga baris baru yang barusan diisi pemanggil hilang dan
        // picker menampilkan data sebelumnya (index klik tidak cocok lagi dengan list-nya,
        // mis. listAktiva[index] undefined). Simpan baris baru dulu, pasang lagi setelah destroy.
        var $tbody = $(sel).children('tbody');
        var barisBaru = $tbody.children().detach();
        $(sel).DataTable().destroy();
        $tbody.empty().append(barisBaru);
    }
    if ($(sel + ' tbody td[colspan]').length) {
        $(sel + ' tbody').empty();
    }
    $(sel).DataTable($.extend({
        lengthChange: true,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Semua']],
        paging: true,
        pageLength: 10,
        // Hanya tabel yang di-scroll; info + tombol halaman terkunci di bawahnya
        // (.rt-picker-scroll / .rt-picker-bawah di public/css/report-table.css).
        dom: 'lfr<"rt-picker-scroll"t><"rt-picker-bawah"ip>',
        language: {
            lengthMenu: 'Tampilkan _MENU_',
            emptyTable: 'Tidak ada data',
            zeroRecords: 'Tidak ada data yang cocok dengan pencarian'
        }
    }, opsi || {}));
}

/* Tombol "+ Aktiva baru" di picker Aktiva ditaruh di sebelah dropdown Tampilkan milik
   DataTables. destroy() di kasInitPicker() menghapus blok .dataTables_length beserta isinya,
   jadi tombolnya diparkir dulu ke #holderButtonAddNewAktiva sebelum init ulang. */
function kasParkirTombolAktivaBaru() {
    $('#buttonAddNewAktiva').hide().appendTo('#holderButtonAddNewAktiva');
}

// Hanya untuk BKK - transaksi BKM tidak membuat aktiva baru.
function kasPasangTombolAktivaBaru() {
    let btn = $('#buttonAddNewAktiva');
    let wadah = $('#tabel_add_list_aktiva_wrapper .dataTables_length');
    if (wadah.length) {
        btn.appendTo(wadah);
    }
    btn.toggle($('#input_add_transaksi').val() === 'BKK');
}

// Klik baris aktiva saat BKK (mode tambah) - baris tidak bisa dipilih.
function kasAktivaHanyaTambah() {
    alertify.warning('Hanya bisa tambah aktiva baru');
}

/* Riwayat pane di modal #form. Semua picker di _pickers.blade.php adalah pane di dalam SATU
   modal, jadi picker bertingkat (mis. Lawan -> Aktiva -> Detail -> Akumulasi/Biaya) hanya
   berganti pane. kasBukaPane() mencatat pane sebelumnya supaya tombol Batal / x bisa
   kembali satu langkah lewat buttonAddListKembali(). Pilih baris / Simpan tetap menutup
   semuanya lewat buttonAddListBatal(). */
let kasPaneStack = [];
let kasPaneSekarang = null;

function kasBukaPane(id) {
    let modalTerbuka = $('#form').hasClass('show');
    if (!modalTerbuka) {
        // Modal baru dibuka - mulai riwayat dari nol.
        kasPaneStack = [];
    } else if (kasPaneSekarang && kasPaneSekarang !== id) {
        let idx = kasPaneStack.indexOf(id);
        if (idx !== -1) {
            // Kembali ke pane yang sudah ada di riwayat (mis. setelah pilih Biaya kembali ke form
            // aktiva baru) - buang pane di atasnya.
            kasPaneStack = kasPaneStack.slice(0, idx);
        } else {
            kasPaneStack.push(kasPaneSekarang);
        }
    }
    $('.showhidemodalbodyadd').hide();
    $('#' + id).show();
    kasPaneSekarang = id;
}

// Kembali (footer): kembali ke pane sebelumnya; di pane pertama tutup modalnya.
function buttonAddListKembali() {
    if (!kasPaneStack.length) {
        buttonAddListBatal();
        return;
    }
    let id = kasPaneStack.pop();
    $('.showhidemodalbodyadd').hide();
    $('#' + id).show();
    kasPaneSekarang = id;
}

// Close (header): tutup seluruh modal sekaligus dari pane mana pun, dan kosongkan riwayat
// pane-nya. .modal('hide') (bukan 'toggle') supaya tidak pernah malah membuka modal.
function buttonAddListTutup() {
    kasPaneStack = [];
    kasPaneSekarang = null;
    $('.showhidemodalbodyadd').hide();
    $('#form').modal('hide');
}

/* Bar kolom tersembunyi harus berada tepat di atas tabelnya. DataTables membungkus tabel
   dengan #<id>_wrapper saat init, jadi acuannya ikut berpindah — sama seperti rtPindahBar()
   di bonsementara.blade.php. */
function rtPindahBar(idBar, idTabel) {
    let bar = document.getElementById(idBar);
    let tabel = document.getElementById(idTabel);
    if (!bar || !tabel) { return; }

    let acuan = tabel;
    if ($.fn.DataTable.isDataTable('#' + idTabel)) {
        acuan = document.getElementById(idTabel + '_wrapper') || tabel;
    }

    if (acuan.previousElementSibling !== bar) {
        acuan.parentNode.insertBefore(bar, acuan);
    }
}

function ikatSearch() {
    let input = document.getElementById('searchBox2');
    if (!input || input.dataset.rtBound) { return; }
    input.dataset.rtBound = '1';

    input.addEventListener('input', function() {
        $('#tabel').DataTable().search(input.value).draw();
    });
}

function ikatPanjangHalaman() {
    let sel = document.getElementById('tabelLen2');
    if (!sel || sel.dataset.rtBound) { return; }
    sel.dataset.rtBound = '1';
    sel.value = String(tabelLen2);

    sel.addEventListener('change', function() {
        let n = Number(sel.value);
        tabelLen2 = (n === -1 || n > 0) ? n : 10;
        $('#tabel').DataTable().page.len(tabelLen2).draw();
    });
}

// ReportTable.headHtml() dengan fallback bila report-table.js belum termuat — sama seperti
// mkHeadHtml() di memorialkoreksi.blade.php.
function kasHeadHtml(cols) {
    if (typeof ReportTable !== 'undefined' && ReportTable.headHtml) {
        return ReportTable.headHtml(cols);
    }
    console.warn('report-table.js tidak termuat - fitur geser & sembunyikan kolom dimatikan. Pastikan public/js/report-table.js ada di server.');
    let html = '<tr>';
    cols.forEach((c) => {
        html += `<th style="padding: 4px 12px;" scope="col">${c[1]}</th>`;
    });
    return html + '</tr>';
}

let kasRtSudahInit = false;

function kasInitReportTableSekali() {
    if (kasRtSudahInit || typeof ReportTable === 'undefined') { return; }
    kasRtSudahInit = true;

    ReportTable.init({
        table: '#tabel',
        bar: '#rtBar',
        onChange: renderTabel
    });

    // Sebagian layout memasang penangan klik sendiri di <thead>; teruskan klik pada roda
    // gigi / pegangan geser ke penangan milik ReportTable — sama seperti
    // mkInitReportTableSekali() di memorialkoreksi.blade.php.
    let kasGuardUlangKlik = false;
    let thead = document.getElementById('tabel_header');
    if (thead) {
        thead.addEventListener('click', function(e) {
            if (kasGuardUlangKlik) { return; }
            let interaktif = e.target && e.target.closest && e.target.closest('.th-gear, .th-grip');
            if (!interaktif) { return; }

            e.stopPropagation();
            e.preventDefault();

            kasGuardUlangKlik = true;
            let ulang = new MouseEvent('click', { bubbles: false, cancelable: true, view: window });
            Object.defineProperty(ulang, 'target', { value: interaktif, configurable: true });
            thead.dispatchEvent(ulang);
            kasGuardUlangKlik = false;
        }, true);
    }
}

// Tinggi tabel mengikuti sisa ruang layar - sama seperti mkAturTinggiTabel() di
// memorialkoreksi.blade.php.
function kasAturTinggiTabel() {
    let page = document.getElementById('page1');
    if (!page || page.offsetParent === null) { return; }

    let area = document.getElementById('content');
    let wrap = document.querySelector('#page1 .po-table-wrap');
    if (!area || !wrap) { return; }

    wrap.style.maxHeight = 'none';

    let padBawah = parseFloat(getComputedStyle(area).paddingBottom) || 0;
    let batasBawah = area.getBoundingClientRect().bottom - padBawah;
    let kotak = wrap.getBoundingClientRect();
    let bawah = page.getBoundingClientRect().bottom - kotak.bottom;

    let sisa = batasBawah - kotak.top - bawah - 4;
    wrap.style.maxHeight = Math.max(200, Math.floor(sisa)) + 'px';
}

// IsOtorisasi1/OtoUser1/TglOto1 dikeluarkan dari cols meski masih tersimpan di susunan
// kolom lama (sebelum perubahan ini) — ketiganya sekarang kolom tetap (Oto/User Oto/Tgl
// Oto) yang ditambahkan sendiri di bawah, bukan kolom geser/sembunyi.
function renderTabel() {
    const cols = gcart_header.filter(c => c[2] === 1 &&
        c[0] !== 'IsOtorisasi1' && c[0] !== 'OtoUser1' && c[0] !== 'TglOto1');

    if ($.fn.DataTable.isDataTable('#tabel')) {
        $('#tabel').DataTable().destroy();
    }

    let thead = document.getElementById('tabel_header');
    thead.innerHTML = kasHeadHtml(cols);
    let baris = thead.querySelector('tr');
    if (baris) {
        baris.insertAdjacentHTML('afterbegin', '<th style="padding: 4px 12px;" scope="col">Actions</th>');
        baris.insertAdjacentHTML('beforeend', `
            <th style="padding: 4px 12px;" scope="col">Oto</th>
            <th style="padding: 4px 12px;" scope="col">User Oto</th>
            <th style="padding: 4px 12px;" scope="col">Tgl Oto</th>
        `);
    }

    const rows = filterByTipeTrans(
        filterByOtorisasi(lastRows, globalOtorisasi),
        globalTipeTrans
    );

    let rowTable = '';
    rows.forEach(function(r) {
        let isOtorisasi = Number(pickCI(r, 'IsOtorisasi1')) || 0;
        let otoUser = pickCI(r, 'OtoUser1');
        let tglOto = pickCI(r, 'TglOto1');

        rowTable += '<tr><td class="text-center">' + aksiButtonsHtml(r) + '</td>';
        rowTable += cols.map(function(c) {
            const v = pickCI(r, c[0]);
            if (c[3] === 'date') {
                return '<td>' + (v ? formatDate(v) : '') + '</td>';
            }
            if (c[3] === 'float') {
                return '<td style="text-align: right;">' +
                    formatAngka(parseFloat(v || 0).toFixed(Number(c[5]) || 0)) + '</td>';
            }
            return '<td>' + nullToEmpty(v) + '</td>';
        }).join('');
        rowTable += `
            ${isOtorisasi ?
                '<td class="text-success text-center"><i class="bi bi-check2" style="-webkit-text-stroke-width: 2px;"></i></td>'
              :
                '<td class="text-danger text-center"><i class="bi bi-x" style="-webkit-text-stroke-width: 2px;"></i></td>'
            }
            <td>${otoUser || ''}</td>
            <td>${tglOto ? formatDate(tglOto) : ''}</td>
        </tr>`;
    });

    document.getElementById('tabel_data').innerHTML = rowTable;

    $('#tabel').DataTable({
        lengthChange: false,
        pageLength: tabelLen2,
        order: [],
        columnDefs: [{ targets: [0], orderable: false }],
        dom: "<'po-table-wrap't><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        language: {
            emptyTable: 'Tidak ada data',
            zeroRecords: 'Tidak ada data yang cocok dengan pencarian'
        }
    });

    rtPindahBar('rtBar', 'tabel');
    ikatSearch();
    ikatPanjangHalaman();
    ikatPeriode();

    let inputSearch = document.getElementById('searchBox2');
    if (inputSearch && inputSearch.value) {
        $('#tabel').DataTable().search(inputSearch.value).draw();
    }
    kasAturTinggiTabel();
}

// Diikat lewat JS (bukan onchange="loadAll()" inline lagi) supaya rentang yang belum lengkap
// tidak memicu request, dan urutan tanggal terbalik ditolak dengan peringatan — sama seperti
// ikatPeriode() di accounting/pengajuandph.blade.php.
function ikatPeriode() {
    let awal = document.getElementById('inputDate1');
    let akhir = document.getElementById('inputDate2');
    if (!awal || !akhir || awal.dataset.rtBound) { return; }
    awal.dataset.rtBound = '1';

    // Refresh biasa (F5) membuat browser mengembalikan tanggal terakhir yang dipilih tanpa
    // memicu change, padahal data awal dirender server untuk rentang bawaan (atribut value) —
    // kembalikan ke nilai bawaan supaya filter dan isi tabel selalu cocok.
    awal.value = awal.defaultValue;
    akhir.value = akhir.defaultValue;

    let onUbah = function() {
        if (!awal.value || !akhir.value) { return; }
        if (awal.value > akhir.value) {
            alertify.warning('Tanggal awal tidak boleh melebihi tanggal akhir');
            return;
        }
        loadAll();
    };
    awal.addEventListener('change', onUbah);
    akhir.addEventListener('change', onUbah);
}

/* -- FILTER MODAL (Otorisasi + Tipe Transaksi) -- */
function updateFilterBadge() {
    let count = ($('#modalOtorisasi').val() !== '2') ? 1 : 0;
    count += ($('#modalTipeTrans').val() !== '') ? 1 : 0;
    $('#filterBadge').text(count + ' aktif');
}

function resetAllFilters() {
    $('#modalOtorisasi').val('2');
    $('#modalTipeTrans').val('');
    updateFilterBadge();
}

$(document).on('show.bs.modal', '#modalFilter', function() {
    $('#modalOtorisasi').val(globalOtorisasi);
    $('#modalTipeTrans').val(globalTipeTrans);
    updateFilterBadge();
});

$(document).on('change', '#modalFilter select.rt-native', updateFilterBadge);

function applyModalFilter() {
    globalOtorisasi = $('#modalOtorisasi').val();
    globalTipeTrans = $('#modalTipeTrans').val();
    renderTabel();
    $('#modalFilter').modal('hide');
}

let listInvoice = []
// let tempNoBukti = ''
let tipemodalaktiva = 0
let flagtunai = 1
let tipeformdet = ''
let listtunai = []
let xislocalorexim = 0
// Supplier yang sudah dipilih untuk Pelunasan Hutang — dipakai tombol #buttonBukaTunai
// supaya bisa kembali ke daftar faktur tanpa memilih ulang Lawan dan supplier.
let tunaiKodeCustsupp = ''
// Nama customer/supplier per kode, diisi bukaListCustSuppTunai() — ditampilkan di header modal
// pelunasan. Lewat lookup (bukan argumen onclick) supaya nama ber-tanda kutip tidak merusak HTML.
let tunaiNamaCustsupp = {}
let xaktiva = {}
let listData = []
let listAktiva = []
let dataLawanx = {}
let listCosting = []
let listSubCosting = []
let listPerkiraan = []
let listLawan = []
let listValas = []
let listDepartemen = []
let listDevisi = []
let xlawan = {}

let listBon = []
let listDPH = []
let listDPP = []

let listUMB = []

let tempDPPDPH = {}


let listBarang = []
let tempBarangAddAdd = {}
let tempBarangAddEdit = {}
let dataBarang = []
let tipeform = ''


jQuery(function($) {
  $('.input-partial-number').autoNumeric('init',
    {
      minimumValue : '0',
      // negativeSignCharacter: 'z'
     }
  );
});

$(document).ready(function(){
      doSetHeader(g_modeReport);
      kasInitReportTableSekali();
      renderTabel();
      kasMuatDevisiValas();

      // Picker tabel_add_list_* tidak lagi di-init di sini terhadap baris statis "-"
      // bawaan blade — kasInitPicker() menginisialisasi ulang tiap tabel picker saat
      // dibuka (lihat masing-masing buttonAddListXxx()).
});

function buttonAddPickCustSuppX (kodecustsupp, agent) {
  let _token = $("#_token").val();
  let lawan = $("#AddAddLawan").val();
  let nobukti = $("#input_add_nobukti").val();
  let nourut = $("#input_add_nourut").val();

  $.ajax({
    url: KAS_ROUTES.kaslisttunai,
    type: "post",
    async: false,
    data: {
      _token,
      nobukti: nobukti + nourut,
      // NoBukti saja, untuk menandai pelunasan bukti ini (lihat KasController::tunaiRows()).
      nobuktiview: nobukti,
      lawan,
      kodecustsupp,
    //   namacustsupp,
      agent
    },
    success: function(res) {
      console.log(res)

      if (!res.length) {

        alertify.warning("Tidak ada transaksi ditemukkan")
        return
      }
      flagtunai = 1
      tunaiKodeCustsupp = kodecustsupp

      let ui = tunaiUI()
      document.getElementById(ui.nobukti).value=nobukti
      document.getElementById(ui.custsupp).value=kodecustsupp
      document.getElementById(ui.namacustsupp).value=tunaiNamaCustsupp[kodecustsupp] || ''

      renderTunai(res)

      kasBukaFormTunai()


    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
    }

  })

}

/* Buka modal pelunasan yang aktif (#formTunai / #formPiutang, lihat tunaiUI()). Kalau picker
   #form masih terbuka, tutup dulu dan tunggu sampai benar-benar tertutup: bila keduanya
   dianimasikan bersamaan, event 'hidden' milik #form mencabut class modal-open dari <body>
   setelah modal pelunasan tampil, sehingga halaman di belakang ikut ter-scroll. */
function kasBukaFormTunai () {
  let ui = tunaiUI()
  // Tiap kali dibuka mulai tanpa pencarian.
  $(ui.search).val('')
  filterTunai()

  if ($('#form').hasClass('show')) {
    $('#form').one('hidden.bs.modal', function () {
      $(ui.modal).modal('show')
    })
    $('#form').modal('hide')
  } else {
    $(ui.modal).modal('show')
  }
}

// Selesai / x: hanya menutup. Tiap +/dobel-klik baris sudah langsung tersimpan ke dbTempHutPiut
// (spTempHutPiut); pelunasan itu baru dibukukan saat item disimpan (spAdd), yang lalu
// mengosongkan dbTempHutPiut. Jadi tidak ada yang perlu disimpan atau dibatalkan di sini.
function selesaiTunai () {

  $(tunaiUI().modal).modal('hide')


}

function submitAddAktivaX () {
  tipemodalaktiva = 0
    let noaktiva = $("#input_aktivax_noaktiva").val()
    let devisi = $("#input_aktivax_devisi").val()
    let keterangan = $("#input_aktivax_keterangan").val()
    let kuantum = $("#input_aktivax_kuantum").val()
    let persen = $("#input_aktivax_susut").val()
    let tglpemakaian = $("#input_aktivax_tglpemakaian").val()
    let metodepenyusutan = $("#input_aktivax_metodepenyusutan").val()
    let akumulasi = $("#input_aktivax_akumulasi").val()
    let groupaktiva = $("#input_aktivax_groupaktiva").val()
    let nobelakang = $("#input_aktivax_nobelakang").val()
    let biaya1 = $("#input_aktivax_biaya1").val()
    let biaya2 = $("#input_aktivax_biaya2").val()
    let biaya3 = $("#input_aktivax_biaya3").val()
    let persen1 = $("#input_aktivax_persen1").val()
    let persen2 = $("#input_aktivax_persen2").val()
    let persen3 = $("#input_aktivax_persen3").val()
    let tipeaktiva = $("#input_aktivax_tipeaktiva").val()
    let tglperolehan = $("#input_aktivax_tglperolehan").val()
    let choice = 'I'

    console.log({
      noaktiva ,
        devisi ,
        keterangan ,
        kuantum ,
        persen ,
        tglpemakaian ,
        metodepenyusutan ,
        akumulasi ,
        groupaktiva ,
        nobelakang ,
        biaya1 ,
        biaya2 ,
        biaya3 ,
        persen1 ,
        persen2 ,
        persen3 ,
        tipeaktiva ,
        tglperolehan ,
      })


      let _token = $("#_token").val()
      $.ajax({
        url: KAS_ROUTES.kasspaddnewaktiva,
        type: "post",
        async: false,
        data: {
          _token,
          noaktiva ,
            devisi ,
            keterangan ,
            kuantum ,
            persen ,
            tglpemakaian  ,
            metodepenyusutan ,
            akumulasi ,
            groupaktiva ,
            nobelakang ,
            biaya1 ,
            biaya2 ,
            biaya3 ,
            persen1 ,
            persen2 ,
            persen3 ,
            tipeaktiva ,
            tglperolehan , choice

        },
        success: function(res) {
          if (res == 2) {
            setNewNoAktiva(groupaktiva)

            alertify.warning("NoBelakang sudah direfresh, silahkan submit ulang")
          }
          if (res == 1) {
            document.getElementById("AddAddKodeLawan").value = dataLawanx.Kode
            document.getElementById("AddAddKeteranganLawan").value = dataLawanx.Keterangan

            document.getElementById("AddAddLawan").value = dataLawanx.Perkiraan

            // Sama seperti submitAddAktiva(): Keterangan aktiva baru disalin ke Keterangan item
            // (menimpa), hanya bila aktiva berhasil disimpan; tetap bisa diubah setelahnya.
            document.getElementById("AddAddKeterangan").value = keterangan

            $("#form").modal('toggle')
          }
          console.log(res)
          // document.getElementById("input_add_nobukti").value = res[0].Nobukti
          // document.getElementById("input_add_nourut").value = res[0].Nourut
          // document.getElementById("input_aktivax_namagroupaktiva").value = xlawan.Keterangan
          // document.getElementById("input_aktivax_groupaktiva").value = xlawan.Perkiraan
          // document.getElementById("input_aktivax_devisi").value = $('#AddAddKodeDevisi').val();
          // document.getElementById("input_aktivax_namadevisi").value = $('#AddAddNamaDevisi').val();
          // document.getElementById("input_aktivax_noaktiva").value = xlawan.Perkiraan + '.' + res[0].NoUrut
          // document.getElementById("input_aktivax_nobelakang").value = res[0].NoUrut
          // cari nourut

          // $('.showhidemodalbodyadd').hide();
          // $('#modalAddListAktivaDetailX').show();
        },
        error: function (err) {
          console.log(err)
          alertify.warning('Terjadi kesalahan silahkan refresh browser')
        }
      })



}

function onclickKembaliUang () {
  let _token = $("#_token").val()
  let nobukti = $("#input_add_nobukti").val()
  let nobon = $("#input_add_bon").val()
  let tempClick = document.getElementById("checkBoxKembaliUang").checked
  let kembaliUang = 'Y'
  if (!nobon) {
    document.getElementById("checkBoxKembaliUang").checked = false
    alertify.warning("Tidak ada bon dipilih")
    return
  }
  if (nobon == '-') {
    document.getElementById("checkBoxKembaliUang").checked = false
    alertify.warning("Tidak ada bon dipilih")
    return
  }
  if (tempClick) {

    kembaliUang = 'Y'




  } else {

    kembaliUang = 'T'
  }








    $.ajax({
      url: KAS_ROUTES.kaschangekembaliuang,
      type: "post",
      async: false,
      data: {
        _token,
        kembaliUang,
        nobukti,
        nobon
      },
      success: function(res) {
        console.log(res)
        if (res == 1) {
          refreshDataTable(nobukti)
          alertify.success("Berhasil update kembali uang")
        }

      },
      error: function (err) {
        console.log(err)
        alertify.warning('Terjadi kesalahan silahkan refresh browser')
      }

    })




}


function buttonAddPickBiayaX (id , perkiraan) {

  // let _token = $("#_token").val();
  document.getElementById(`${id}`).value = perkiraan

  $('.showhidemodalbodyadd').hide();
  kasBukaPane('modalAddListAktivaDetailX');
}


function buttonAddListXBiaya (id) {

  // let _token = $("#_token").val();
  $.ajax({
    url: KAS_ROUTES.kaslistbiayainput,
    type: "get",
    async: false,
    data: {
    },
    success: function(res) {
      console.log(res)
      let rowTable = ``
      res.forEach((item, i) => {
        rowTable += `
        <tr class="pick-row" onclick="buttonAddPickBiayaX('${id}','${item.Perkiraan}' )">
        <td>${item.Perkiraan}</td>
        <td>${item.Keterangan}</td>
        </tr>`
      });


      document.getElementById("tabel_data_add_list_akumulasibiaya").innerHTML = rowTable
      kasInitPicker('tabel_add_list_akumulasibiaya', {
        columnDefs: [{ type: 'string', targets: 0 }]
      });

      if (res.length) {

        $('.showhidemodalbodyadd').hide();
        kasBukaPane('modalAddListAkumulasiBiaya');
      } else {
        alertify.warning("Biaya tidak ditemukkan")
      }


    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
    }

  })
}

function buttonAddListXAkumulasi () {

  // let _token = $("#_token").val();
  $.ajax({
    url: KAS_ROUTES.kaslistakumulasiinput,
    type: "get",
    async: false,
    data: {
    },
    success: function(res) {
      console.log(res)
      let rowTable = ``
      res.forEach((item, i) => {
        rowTable += `
        <tr class="pick-row" onclick="buttonAddPickBiayaX('input_aktivax_akumulasi' ,'${item.Perkiraan}' )">
        <td>${item.Perkiraan}</td>
        <td>${item.Keterangan}</td>
        </tr>`
      });


      document.getElementById("tabel_data_add_list_akumulasibiaya").innerHTML = rowTable
      kasInitPicker('tabel_add_list_akumulasibiaya', {
        columnDefs: [{ type: 'string', targets: 0 }]
      });

      if (res.length) {

        $('.showhidemodalbodyadd').hide();
        kasBukaPane('modalAddListAkumulasiBiaya');
      } else {
        alertify.warning("Biaya tidak ditemukkan")
      }


    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
    }

  })
}

function buttonAddListCosting () {
  listCosting = []

  console.log('buttonAddListCosting')


  let _token = $("#_token").val();
  let lawan = $("#AddAddLawan").val();
  if(!lawan) {
    alertify.warning("Pilih lawan terlebih dahulu")
    return
  }
  console.log(lawan)

  $.ajax({
    url: KAS_ROUTES.banklistcosting,
    type: "post",
    async: false,
    data: {
      _token,
      kodelawan: lawan,
    },
    success: function(res) {
      console.log(res)
      listCosting  = res
      let rowTable = ``
      res.forEach((item, i) => {
        rowTable += `
        <tr class="pick-row" onclick="buttonAddPickCosting(${i},'${item.KodeCost}' , '${item.NamaCost}' )">
        <td>${item.KodeCost}</td>
        <td>${item.NamaCost}</td>
        </tr>`
      });


      document.getElementById("tabel_data_add_list_costing").innerHTML = rowTable
      kasInitPicker('tabel_add_list_costing');

      if (res.length) {

        $('.showhidemodalbodyadd').hide();
        kasBukaPane('modalAddListCosting');
        $("#form").modal('toggle')
      } else {
        alertify.warning("Costing tidak ditemukkan")
      }


    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
    }

  })


}



function buttonAddListSubCosting () {
  listSubCosting = []

  console.log('buttonAddListSubCosting')


  let _token = $("#_token").val();
  let costing = $("#AddAddKodeCosting").val();
  if(!costing) {
    alertify.warning("Pilih costing terlebih dahulu")
    return
  }

  $.ajax({
    url: KAS_ROUTES.banklistsubcosting,
    type: "post",
    async: false,
    data: {
      _token,
      kodecosting: costing,
    },
    success: function(res) {
      console.log(res)
      listSubCosting  = res
      let rowTable = ``
      res.forEach((item, i) => {
        rowTable += `
        <tr class="pick-row" onclick="buttonAddPickSubCosting(${i},'${item.KodeSubCost}' , '${item.NamaSubCost}' )">
        <td>${item.KodeSubCost}</td>
        <td>${item.NamaSubCost}</td>
        </tr>`
      });


      document.getElementById("tabel_data_add_list_subcosting").innerHTML = rowTable
      kasInitPicker('tabel_add_list_subcosting');

      if (res.length) {

        $('.showhidemodalbodyadd').hide();
        kasBukaPane('modalAddListSubCosting');
        $("#form").modal('toggle')
      } else {
        alertify.warning("Sub Costing tidak ditemukkan")
      }


    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
    }

  })


}


function buttonAddPickCosting (index, kode, nama) {


  console.log('buttonAddPickCosting')
  document.getElementById("AddAddNamaCosting").value = nama
  document.getElementById("AddAddKodeCosting").value = kode
  document.getElementById("AddAddNamaSubCosting").value = ''
  document.getElementById("AddAddKodeSubCosting").value = ''

  // $('.showhideitem').hide();
  buttonAddListBatal()
  // $("#form").modal('toggle')
}

function buttonAddPickSubCosting (index, kode, nama) {


  console.log('buttonAddPickSubCosting')
  document.getElementById("AddAddNamaSubCosting").value = nama
  document.getElementById("AddAddKodeSubCosting").value = kode

  // $('.showhideitem').hide();
  buttonAddListBatal()
  // $("#form").modal('toggle')
}



function formatAngkaParse (angka) {

        return parseFloat(angka).toFixed(2)
      }

      function formatAngkaVal (angka) {
        return Number(angka.split(',').join(''))
      }

      // Untuk elemen ber-class .input-partial-number (autoNumeric, lihat jQuery(function($){...})
      // di atas) — set langsung ke .value tidak memicu format ribuan autoNumeric karena tidak
      // lewat event input/keyup-nya. Sama seperti setNum() di accounting/pengajuandpp.blade.php.
      function setNum (id, v) {
        let el = document.getElementById(id)
        if (!el) return
        let n = formatAngkaVal(v == null ? '' : String(v))
        if ($(el).data('autoNumeric')) {
          $(el).autoNumeric('set', n)
        } else {
          el.value = formatAngka(n.toFixed(2))
        }
      }


function onChangeTransaksi () {
  document.getElementById("input_add_kodeperkiraan").value = ''
  document.getElementById("input_add_keteranganperkiraan").value = ''
  document.getElementById("input_add_nobukti").value = ''
  document.getElementById("input_add_tanggal").valueAsDate = new Date()
  document.getElementById("input_add_kepadaterima").value = ''

  document.getElementById("input_add_bon").value = ''
  setNum('input_add_nilaibon', '0.00')

    console.log("onChangeTransaksi")
    $('.showhideitem').hide();
    $('.showhidePart').hide();
    let value = $("#input_add_transaksi").val()
    console.log(value)
    $(`.part${value}`).show();


}

function setNewNoBukti () {
  console.log('setNewNoBukti')
  let simbol  = $("#input_add_simbol").val()
  let _token  = $("#_token").val()
  let kode  = $("#input_add_transaksi").val()
  $.ajax({
    url: KAS_ROUTES.spnobuktisimbol,
    type: "post",
    async: false,
    data: {
      _token,
      simbol,
      kode

    },
    success: function(res) {

      console.log(res)
      document.getElementById("input_add_nobukti").value = res[0].Nobukti
      document.getElementById("input_add_nourut").value = res[0].Nourut

    }})
}


function cleanFormAddAdd () {

  // Jumlah dikunci hanya oleh Lawan pelunasan (lihat buttonAddPickLawan/buttonAddEditItem).
  document.getElementById("AddAddJumlah").readOnly = false

  if (tipeform == 'add') {
    kasPilihOpsi('AddAddKodeDevisi', '01', '01 - Accounting')
    document.getElementById("AddAddNamaDevisi").value = 'Accounting'
    kasPilihOpsi('AddAddValas', 'IDR')
    document.getElementById("AddAddKurs").value = '1.00'

    document.getElementById("AddAddKodeDepartemen").value = ''
    document.getElementById("AddAddNamaDepartemen").value = ''


  }

  document.getElementById("AddAddKodeCosting").value = ''
  document.getElementById("AddAddNamaCosting").value = ''

  document.getElementById("AddAddKodeSubCosting").value = ''
  document.getElementById("AddAddNamaSubCosting").value = ''

  document.getElementById("AddAddKodeCustsupp").value = ''
  document.getElementById("AddAddNamaCustsupp").value = ''
  resetTitipan()

  document.getElementById("AddAddLawan").value = ''
  $('#buttonBukaTunai').hide()
  document.getElementById("AddAddKeteranganLawan").value = ''
  document.getElementById("AddAddJumlah").value = '0.00'
  document.getElementById("AddAddKeterangan").value = ''
  document.getElementById("AddAddKeteranganDetail").value = ''





}


function closeShowHideAdd () {
  $('.showhide').hide();

}

function cleanFormAdd (tipe = 0) {

  if (tipe == 0) {
    document.getElementById("input_add_transaksi").value = 'BKK'
    onChangeTransaksi()
  }


  document.getElementById("input_add_kodeperkiraan").value = ''
  document.getElementById("input_add_keteranganperkiraan").value = ''
  document.getElementById("input_add_nobukti").value = ''
  document.getElementById("input_add_tanggal").valueAsDate = new Date()
  document.getElementById("input_add_kepadaterima").value = ''

  document.getElementById("input_add_bon").value = ''
  setNum('input_add_nilaibon', '0.00')



}


function submitAdd () {
  console.log("submitAdd")
  let _token  = $("#_token").val()
  let nobukti  = $("#input_add_nobukti").val()

  let tanggal  = $("#input_add_tanggal").val()
  let nopajak  = $("#input_add_nopajak").val()

  console.log(nobukti , tanggal , nopajak)

}


function submitAddAdd () {

  let checkDate = new Date($("#input_add_tanggal").val())

  let periode_bulan = document.getElementById("periode_bulan").value
  let periode_tahun = document.getElementById("periode_tahun").value

  if ( checkDate.getFullYear()  !== Number(periode_tahun)  || (checkDate.getMonth() +1) !== Number(periode_bulan) ) {

      alertify.warning("Tanggal tidak sesuai periode");
      return
  }

  let _token  = $("#_token").val()
  let nobukti  = $("#input_add_nobukti").val()
  let nourut  = $("#input_add_nourut").val()
  let transaksi  = $("#input_add_transaksi").val()
  let note  = $("#input_add_kepadaterima").val()
  let kodeperkiraan  = $("#input_add_kodeperkiraan").val()
  let tanggal = $("#input_add_tanggal").val()

  let lampiran = 0
  let keterangan2 = ''
  let choice = "I"


  let kodedevisi  = $("#AddAddKodeDevisi").val()
  let valas  = $("#AddAddValas").val()
  let kurs  = $("#AddAddKurs").val()
  let lawan  = $("#AddAddLawan").val()
  // let kodelawan  = $("#AddAddKodeLawan").val()
  let jumlah  = unformatAngka($("#AddAddJumlah").val())
  let keterangan  = $("#AddAddKeterangan").val()
  let keterangandetail  = $("#AddAddKeteranganDetail").val()
  let kodedepartemen  = $("#AddAddKodeDepartemen").val()

  let kredit = 0
  let kreditrp = 0

  let tphc = 'C'

  if (!kodedevisi || !valas || !lawan || !kodedepartemen || !keterangan) {
    alertify.warning("Data tidak lengkap")
    return
  }

  // Lawan diketik tapi belum dipilih (Enter / daftar) - lihat onInputLawan().
  if (!$("#AddAddKeteranganLawan").val()) {
    alertify.warning("Lawan belum dipilih, tekan Enter atau pilih dari daftar")
    return
  }

  if (Number(jumlah) <= 0) {
    alertify.warning("Jumlah <= 0")
    return
  }
  let nilaibon = formatAngkaVal($("#input_add_nilaibon").val())
  let nobon = $("#input_add_bon").val()
  if (nobon && nobon != '-') {
    if (transaksi == 'BKK') {
      if (Number(jumlah) > Number(nilaibon)) {
        alertify.warning("Melebihi nilai bon")
        return
      }


    }

  }



  let jumlahrp = Number(jumlah) * Number(kurs)


  let urut = 0

  let custsuppP = ''
  let custsuppL = ''
  let noaktivaP = ''
  let noaktivaL = ''
  let statusaktivaP = ''
  let statusaktivaL = ''


  let kodebag = '-'

  let kodeP = ''
  let kodeL = ''
  let statusgiro = ''
  let simbol = $("#input_add_simbol").val()
  let flagsimbol = ''
  let kodecost = $("#AddAddKodeCosting").val()
  let kodesubcost = $("#AddAddKodeSubCosting").val()
  let nodph = ''
  let urutdph = 0
  let dppdph = ''
  let tp = ''
  let ppklx = ''
  let nofaktur = ''
  let plok = 0
  let nobons = $("#input_add_bon").val()
  let jmlrecord = tipeform == 'add' ? 0 : 1
  let notitipan = ''
  let uruttitipan = 0
  let perkiraanx = transaksi == 'BKK' ? lawan : kodeperkiraan
  let lawanx = transaksi == 'BKK' ? kodeperkiraan : lawan

  let kodeFlag = $("#AddAddKodeLawan").val()
  let custsupp = ''






  if (kodeFlag == "HT" || kodeFlag == 'UHT') {
    if (transaksi == 'BKK') {
      kodeP = kodeFlag
      statusaktivaP = "HT-"
      custsuppP = tempDPPDPH.KODECUSTSUPP
      custsupp = tempDPPDPH.KODECUSTSUPP
      dppdph = 'DPH'
      nodph = tempDPPDPH.Nobukti

    } else if (transaksi == 'BKM' && kodeFlag == 'HT') {
      kodeL = kodeFlag
      statusaktivaL = "HT-"
      custsuppL = tempDPPDPH.KODECUSTSUPP
      custsupp = tempDPPDPH.KODECUSTSUPP
      dppdph = 'DPP'
      nodph = tempDPPDPH.Nobukti

    }
  }


  if ( kodeFlag == "UHT" && transaksi == "BKM") {


    custsuppL =  $("#input_dphuhtbkm_kodecustsupp").val();
    custsupp = $("#input_dphuhtbkm_kodecustsupp").val();
    statusAktivaL = 'UHT-'
    kodeL = kodeFlag
  }

  if (lawan == '113400') {

    // BKK memakai titipan yang dipilih lewat No Titipan - disimpan supaya Sisa titipan tsb
    // berkurang (lihat KasController::getNoTitipan(), subquery E).
    if (transaksi == 'BKK') {
      notitipan = $("#AddAddNoTitipan").val()
      uruttitipan = Number($("#AddAddUrutTitipan").val() || 0)
      if (!notitipan || !uruttitipan) {
        alertify.warning("Pilih No Titipan")
        return
      }
    }

    custsupp = $("#AddAddKodeCustsupp").val()
    if (!custsupp) {
      alertify.warning("Pilih customer")
      return
    }
    custsuppP = custsupp
    custsuppL = custsupp

  }


  if (transaksi == 'BKK' && kodeFlag != 'AKV' && kodeFlag != 'AKM') {
    kodeP = $("#AddAddKodeLawan").val()
    if (kodeP == "HT") {
      statusaktivaP = "HT-"
    }
  } else {
    kodeL = $("#AddAddKodeLawan").val()
    if (kodeL == "HT") {
      statusaktivaL = "HT-"
    }

  }

  let xaktivagroupperkiraan = $("#input_aktiva_noaktiva").val();
  let xaktivatanggalperolehan = $("#input_aktiva_tglperolehan").val();
  let xaktivatanggalpemakaian= $("#input_aktiva_tglpemakaian").val();


  if (tipemodalaktiva == 0) {
     xaktivagroupperkiraan = $("#input_aktivax_noaktiva").val();
     xaktivatanggalperolehan = $("#input_aktivax_tglperolehan").val();
     xaktivatanggalpemakaian= $("#input_aktivax_tglpemakaian").val();



  }



  if (kodeFlag == 'AKV' ) {
    // kalo keluar dia masuk P
    // kalo masuk masuk ke L
    if (transaksi == "BKK") {
      custsuppP = $("#AddAddKodeCustsupp").val()

        noaktivaP = xaktivagroupperkiraan

      statusaktivaP = 'AKV+'
      kodeP = 'AKV'
      kodeL = ''
    } else {
      custsuppL = $("#AddAddKodeCustsupp").val()

        noaktivaL = xaktivagroupperkiraan

      statusaktivaL = 'AKV-'
      kodeL='AKV'
      kodeP = ''
    }



  }


    if (kodeFlag == 'AKM' ) {
      // kalo keluar dia masuk P
      // kalo masuk masuk ke L
      if (transaksi == "BKK") {
        custsuppP = $("#AddAddKodeCustsupp").val()
        noaktivaP = xaktivagroupperkiraan
        statusaktivaP = 'AKM+'
        kodeP = 'AKM'
        kodeL = ''
      } else {
        custsuppL = $("#AddAddKodeCustsupp").val()
        noaktivaL = xaktivagroupperkiraan
        statusaktivaL = 'AKM-'
        kodeL='AKM'
        kodeP = ''
      }



    }



  // console.log({tipeform,
  // xaktivagroupperkiraan,
  // xaktivatanggalpemakaian,
  // xaktivatanggalperolehan,
  // choice,
  // _token,
  // nobukti ,
  // nourut,
  // transaksi,
  // note,
  // kodeperkiraan ,
  // tanggal,
  //
  // lampiran ,
  // keterangan2 ,
  // perkiraanx,
  // lawanx,
  //
  // kodedevisi ,
  // valas ,
  // kurs  ,
  // lawan ,
  // jumlah,
  // keterangan  ,
  // keterangandetail ,
  // kodedepartemen  ,
  //
  // kredit,
  // kreditrp,
  //
  // tphc,
  // jumlahrp ,
  //
  //
  // urut ,
  //
  // custsuppP ,
  // custsuppL,
  // noaktivaP ,
  // noaktivaL ,
  // statusaktivaP ,
  // statusaktivaL ,
  //
  // nobon ,
  // kodebag ,
  //
  // kodeP ,
  // kodeL ,
  // statusgiro ,
  // simbol,
  // flagsimbol ,
  // kodecost ,
  // kodesubcost ,
  // nodph ,
  // urutdph,
  // dppdph,
  // tp,
  // ppklx ,
  // nofaktur,
  // plok ,
  // nobons,
  // jmlrecord,
  // notitipan ,
  // uruttitipan,
  // custsupp})



  if ((transaksi == 'BKK' || transaksi == 'BKM') && xislocalorexim == 1) {
    console.log("tunai add")
    $.ajax({
        url: KAS_ROUTES.kasspadd,
        type: "post",
        async: false,
        data: {
          xislocalorexim,
          tipeform,
          choice,
          _token,
          nobukti ,
          nourut,
          transaksi,
          note,
          kodeperkiraan ,
          tanggal,

          lampiran ,
          keterangan2 ,
          perkiraanx,
          lawanx,

          kodedevisi ,
          valas ,
          kurs  ,
          lawan ,
          jumlah,
          keterangan  ,
          keterangandetail ,
          kodedepartemen  ,

          kredit,
          kreditrp,

          tphc,
          jumlahrp ,


          urut ,

          custsuppP ,
          custsuppL,
          noaktivaP ,
          noaktivaL ,
          statusaktivaP ,
          statusaktivaL ,

          nobon ,
          kodebag ,

          kodeP ,
          kodeL ,
          statusgiro ,
          simbol,
          flagsimbol ,
          kodecost ,
          kodesubcost ,
          nodph ,
          urutdph,
          dppdph,
          tp,
          ppklx ,
          nofaktur,
          plok ,
          nobons,
          jmlrecord,
          notitipan ,
          uruttitipan,
          custsupp
        },
        success: function(res) {
          console.log(res ,'!')

          if (res == 1) {
            // $("#form").modal('toggle')
            alertify.success('Kas telah ditambah');
            lockFormAdd()
            $('.showhideitem').hide();
            loadAll()
            // buttonCloseForm()
            tipeform = 'edit'
            // document.getElementById("buttonAddListCustomer").disabled = true
            // document.getElementById("input_add_tanggal").disabled = true

            refreshDataTable(nobukti)

            // $("#form").modal('toggle')

          }
          if (res == 2) {
            setNewNoBukti()
            alertify.warning('Nobukti telah di refresh, silahkan submit ulang');
          }
          //
          // if (res == 3 ) {
          //   alertify.warning('Stok gudang tidak mencukupi');
          // }

        },
        error: function (err) {
          console.log(err)
          alertify.warning('Terjadi kesalahan silahkan refresh browser')
        }
      })

  } else if (transaksi == 'BKM' && kodeFlag == 'UHT') {
    $.ajax({
        url: KAS_ROUTES.kasspadd,
        type: "post",
        async: false,
        data: {
          tipeform,
          choice,
          _token,
          nobukti ,
          nourut,
          transaksi,
          note,
          kodeperkiraan ,
          tanggal,

          lampiran ,
          keterangan2 ,
          perkiraanx,
          lawanx,

          kodedevisi ,
          valas ,
          kurs  ,
          lawan ,
          jumlah,
          keterangan  ,
          keterangandetail ,
          kodedepartemen  ,

          kredit,
          kreditrp,

          tphc,
          jumlahrp ,


          urut ,

          custsuppP ,
          custsuppL,
          noaktivaP ,
          noaktivaL ,
          statusaktivaP ,
          statusaktivaL ,

          nobon ,
          kodebag ,

          kodeP ,
          kodeL ,
          statusgiro ,
          simbol,
          flagsimbol ,
          kodecost ,
          kodesubcost ,
          nodph ,
          urutdph,
          dppdph,
          tp,
          ppklx ,
          nofaktur,
          plok ,
          nobons,
          jmlrecord,
          notitipan ,
          uruttitipan,
          custsupp
        },
        success: function(res) {
          console.log(res ,'!')

          if (res == 1) {
            // $("#form").modal('toggle')
            alertify.success('Kas telah ditambah');
            lockFormAdd()
            $('.showhideitem').hide();
            loadAll()
            // buttonCloseForm()
            tipeform = 'edit'
            // document.getElementById("buttonAddListCustomer").disabled = true
            // document.getElementById("input_add_tanggal").disabled = true

            refreshDataTable(nobukti)

            // $("#form").modal('toggle')

          }
          if (res == 2) {
            setNewNoBukti()
            alertify.warning('Nobukti telah di refresh, silahkan submit ulang');
          }
          //
          // if (res == 3 ) {
          //   alertify.warning('Stok gudang tidak mencukupi');
          // }

        },
        error: function (err) {
          console.log(err)
          alertify.warning('Terjadi kesalahan silahkan refresh browser')
        }
      })

  } else if (kodeFlag == 'HT' || kodeFlag == 'UHT') {
    $.ajax({
        url: KAS_ROUTES.kasspadddppdph,
        type: "post",
        async: false,
        data: {
          tipeform,
          choice,
          _token,
          nobukti ,
          nourut,
          transaksi,
          note,
          kodeperkiraan ,
          tanggal,

          lampiran ,
          keterangan2 ,
          perkiraanx,
          lawanx,

          kodedevisi ,
          valas ,
          kurs  ,
          lawan ,
          jumlah,
          keterangan  ,
          keterangandetail ,
          kodedepartemen  ,

          kredit,
          kreditrp,

          tphc,
          jumlahrp ,


          urut ,

          custsuppP ,
          custsuppL,
          noaktivaP ,
          noaktivaL ,
          statusaktivaP ,
          statusaktivaL ,

          nobon ,
          kodebag ,

          kodeP ,
          kodeL ,
          statusgiro ,
          simbol,
          flagsimbol ,
          kodecost ,
          kodesubcost ,
          nodph ,
          urutdph,
          dppdph,
          tp,
          ppklx ,
          nofaktur,
          plok ,
          nobons,
          jmlrecord,
          notitipan ,
          uruttitipan,
          custsupp
        },
        success: function(res) {
          console.log(res ,'!')

          if (res == 1) {
            // $("#form").modal('toggle')
            alertify.success('Kas telah ditambah');
            loadAll()
            // buttonCloseForm()
            $('.showhideitem').hide();
            tipeform = 'edit'
            lockFormAdd()
            // document.getElementById("buttonAddListCustomer").disabled = true
            // document.getElementById("input_add_tanggal").disabled = true

            refreshDataTable(nobukti)

            // $("#form").modal('toggle')

          }
          if (res == 2) {
            setNewNoBukti()
            alertify.warning('Nobukti telah di refresh, silahkan submit ulang');
          }

          if (res == 3) {
            alertify.warning('Jumlah melebihi total dokumen DPH/DPP yang dipilih');
          }
          //
          // if (res == 3 ) {
          //   alertify.warning('Stok gudang tidak mencukupi');
          // }

        },
        error: function (err) {
          console.log(err)
          alertify.warning('Terjadi kesalahan silahkan refresh browser')
        }
      })
  } else if (kodeFlag == 'AKV') {
    $.ajax({
        url: KAS_ROUTES.kasspaddaktiva,
        type: "post",
        async: false,
        data: {
          tipeform,
          xaktivagroupperkiraan,
          xaktivatanggalpemakaian,
          xaktivatanggalperolehan,
          choice,
          _token,
          nobukti ,
          nourut,
          transaksi,
          note,
          kodeperkiraan ,
          tanggal,

          lampiran ,
          keterangan2 ,
          perkiraanx,
          lawanx,

          kodedevisi ,
          valas ,
          kurs  ,
          lawan ,
          jumlah,
          keterangan  ,
          keterangandetail ,
          kodedepartemen  ,

          kredit,
          kreditrp,

          tphc,
          jumlahrp ,
          urut ,
          custsuppP ,
          custsuppL,
          noaktivaP ,
          noaktivaL ,
          statusaktivaP ,
          statusaktivaL ,

          nobon ,
          kodebag ,

          kodeP ,
          kodeL ,
          statusgiro ,
          simbol,
          flagsimbol ,
          kodecost ,
          kodesubcost ,
          nodph ,
          urutdph,
          dppdph,
          tp,
          ppklx ,
          nofaktur,
          plok ,
          nobons,
          jmlrecord,
          notitipan ,
          uruttitipan,
          custsupp
        },
        success: function(res) {
          console.log(res ,'!')

          if (res == 1) {
            // $("#form").modal('toggle')
            alertify.success('Kas telah ditambah');
            lockFormAdd()
            $('.showhideitem').hide();
            loadAll()
            // buttonCloseForm()
            tipeform = 'edit'
            // document.getElementById("buttonAddListCustomer").disabled = true
            // document.getElementById("input_add_tanggal").disabled = true

            refreshDataTable(nobukti)

            // $("#form").modal('toggle')

          }
          if (res == 2) {
            setNewNoBukti()
            alertify.warning('Nobukti telah di refresh, silahkan submit ulang');
          }
          //
          // if (res == 3 ) {
          //   alertify.warning('Stok gudang tidak mencukupi');
          // }

        },
        error: function (err) {
          console.log(err)
          alertify.warning('Terjadi kesalahan silahkan refresh browser')
        }
      })
  } else  {
    $.ajax({
        url: KAS_ROUTES.kasspadd,
        type: "post",
        async: false,
        data: {
          tipeform,
          choice,
          _token,
          nobukti ,
          nourut,
          transaksi,
          note,
          kodeperkiraan ,
          tanggal,

          lampiran ,
          keterangan2 ,
          perkiraanx,
          lawanx,

          kodedevisi ,
          valas ,
          kurs  ,
          lawan ,
          jumlah,
          keterangan  ,
          keterangandetail ,
          kodedepartemen  ,

          kredit,
          kreditrp,

          tphc,
          jumlahrp ,


          urut ,

          custsuppP ,
          custsuppL,
          noaktivaP ,
          noaktivaL ,
          statusaktivaP ,
          statusaktivaL ,

          nobon ,
          kodebag ,

          kodeP ,
          kodeL ,
          statusgiro ,
          simbol,
          flagsimbol ,
          kodecost ,
          kodesubcost ,
          nodph ,
          urutdph,
          dppdph,
          tp,
          ppklx ,
          nofaktur,
          plok ,
          nobons,
          jmlrecord,
          notitipan ,
          uruttitipan,
          custsupp
        },
        success: function(res) {
          console.log(res ,'!')

          if (res == 1) {
            // $("#form").modal('toggle')
            alertify.success('Kas telah ditambah');
            lockFormAdd()
            $('.showhideitem').hide();
            loadAll()
            // buttonCloseForm()
            tipeform = 'edit'
            // document.getElementById("buttonAddListCustomer").disabled = true
            // document.getElementById("input_add_tanggal").disabled = true

            refreshDataTable(nobukti)

            // $("#form").modal('toggle')

          }
          if (res == 2) {
            setNewNoBukti()
            alertify.warning('Nobukti telah di refresh, silahkan submit ulang');
          }
          //
          // if (res == 3 ) {
          //   alertify.warning('Stok gudang tidak mencukupi');
          // }

        },
        error: function (err) {
          console.log(err)
          alertify.warning('Terjadi kesalahan silahkan refresh browser')
        }
      })
  }












}

function submitAddEdit () {



  let checkDate = new Date($("#input_add_tanggal").val())

  let periode_bulan = document.getElementById("periode_bulan").value
  let periode_tahun = document.getElementById("periode_tahun").value

  if ( checkDate.getFullYear()  !== Number(periode_tahun)  || (checkDate.getMonth() +1) !== Number(periode_bulan) ) {

      alertify.warning("Tanggal tidak sesuai periode");
      return
  }
  let choice = "U"
  let _token  = $("#_token").val()
  let nobukti  = $("#input_add_nobukti").val()
  let nourut  = $("#input_add_nourut").val()
  let transaksi  = $("#input_add_transaksi").val()
  let note  = $("#input_add_kepadaterima").val()
  let kodeperkiraan  = $("#input_add_kodeperkiraan").val()
  let tanggal = $("#input_add_tanggal").val()

  let lampiran = 0
  let keterangan2 = ''


  let kodedevisi  = $("#AddAddKodeDevisi").val()
  let valas  = $("#AddAddValas").val()
  let kurs  = $("#AddAddKurs").val()
  let lawan  = $("#AddAddLawan").val()
  let jumlah  = unformatAngka($("#AddAddJumlah").val())
  let keterangan  = $("#AddAddKeterangan").val()
  let keterangandetail  = $("#AddAddKeteranganDetail").val()
  let kodedepartemen  = $("#AddAddKodeDepartemen").val()

  let kredit = 0
  let kreditrp = 0

  let tphc = 'C'

  if (!kodedevisi || !valas || !lawan || !kodedepartemen || !keterangan) {
    alertify.warning("Data tidak lengkap")
    return
  }

  if (jumlah < 0) {
    alertify.warning("Jumlah < 0")
    return
  }

  let jumlahrp = Number(jumlah) * Number(kurs)


  let urut = tempBarangAddEdit.Urut

  let custsuppP = tempBarangAddEdit.CustSuppP
  let custsuppL = tempBarangAddEdit.CustSuppL
  let noaktivaP = tempBarangAddEdit.NoAktivaP
  let noaktivaL = tempBarangAddEdit.NoAktivaL
  let statusaktivaP = tempBarangAddEdit.StatusAktivaP
  let statusaktivaL = tempBarangAddEdit.StatusAktivaL

  let nobon = $("#input_add_bon").val()
  let kodebag = '-'

  let kodeP = tempBarangAddEdit.KodeP
  let kodeL = tempBarangAddEdit.KodeL
  let statusgiro = tempBarangAddEdit.StatusGiro
  let simbol = $("#input_add_simbol").val()
  let flagsimbol = ''
  let kodecost = $("#AddAddKodeCosting").val()
  let kodesubcost = $("#AddAddKodeSubCosting").val()
  let nodph = tempBarangAddEdit.NODPH
  let urutdph = tempBarangAddEdit.urutDPH
  let dppdph = ''
  let tp = ''
  let ppklx = ''
  let nofaktur = ''
  let plok = 0
  let nobons = $("#input_add_bon").val()
  let jmlrecord = tipeform == 'add' ? 0 : 1
  let notitipan = tempBarangAddEdit.notitipan
  let uruttitipan = tempBarangAddEdit.URUTTITIPAN
  let perkiraanx = transaksi == 'BKK' ? lawan : kodeperkiraan
  let lawanx = transaksi == 'BKK' ? kodeperkiraan : lawan

  let nilaibon = formatAngkaVal($("#input_add_nilaibon").val())




  if (nobon && nobon != '-') {
    if (transaksi == 'BKK') {
      if (Number(jumlah) > Number(nilaibon) + Number(tempBarangAddEdit.Debet)) {
        alertify.warning("Melebihi nilai bon")
        return
      }


    }

  }


  console.log({
    tipeform,
    _token,
    nobukti ,
    nourut,
    transaksi,
    note,
    kodeperkiraan ,
    tanggal,
    lampiran ,
    keterangan2 ,
    kodedevisi ,
    valas ,
    kurs  ,
    lawan ,
    jumlah,
    keterangan  ,
    keterangandetail ,
    kodedepartemen  ,
    kredit,
    kreditrp,
    tphc,
    jumlahrp ,
    urut ,
    custsuppP ,
    custsuppL,
    noaktivaP ,
    noaktivaL ,
    statusaktivaP ,
    statusaktivaL ,
    nobon ,
    kodebag ,
    kodeP ,
    kodeL ,
    statusgiro ,
    simbol,
    flagsimbol ,
    kodecost ,
    kodesubcost ,
    nodph ,
    urutdph,
    dppdph,
    tp,
    ppklx ,
    nofaktur,
    plok ,
    nobons,
    jmlrecord,
    notitipan ,
    uruttitipan
  })



  $.ajax({
      url: KAS_ROUTES.kasspadd,
      type: "post",
      async: false,
      data: {
        choice,
        tipeform,
        _token,
        nobukti ,
        nourut,
        transaksi,
        note,
        kodeperkiraan ,
        tanggal,

        lampiran ,
        keterangan2 ,
        perkiraanx,
        lawanx,

        kodedevisi ,
        valas ,
        kurs  ,
        lawan ,
        jumlah,
        keterangan  ,
        keterangandetail ,
        kodedepartemen  ,

        kredit,
        kreditrp,

        tphc,
        jumlahrp ,


        urut ,

        custsuppP ,
        custsuppL,
        noaktivaP ,
        noaktivaL ,
        statusaktivaP ,
        statusaktivaL ,

        nobon ,
        kodebag ,

        kodeP ,
        kodeL ,
        statusgiro ,
        simbol,
        flagsimbol ,
        kodecost ,
        kodesubcost ,
        nodph ,
        urutdph,
        dppdph,
        tp,
        ppklx ,
        nofaktur,
        plok ,
        nobons,
        jmlrecord,
        notitipan ,
        uruttitipan
      },
      success: function(res) {
        console.log(res ,'!')

        if (res == 1) {
          // $("#form").modal('toggle')
          alertify.success('Kas telah ditambah');
          loadAll()
          // buttonCloseForm()
          lockFormAdd()
          tipeform = 'edit'
          // document.getElementById("buttonAddListCustomer").disabled = true
          // document.getElementById("input_add_tanggal").disabled = true
          $('.showhideitem').hide();
          refreshDataTable(nobukti)

          // $("#form").modal('toggle')

        }
        if (res == 2) {
          setNewNoBukti()
          alertify.warning('Nobukti telah di refresh, silahkan submit ulang');
        }
        //
        // if (res == 3 ) {
        //   alertify.warning('Stok gudang tidak mencukupi');
        // }

      },
      error: function (err) {
        console.log(err)
        alertify.warning('Terjadi kesalahan silahkan refresh browser')
      }
    })









}



function buttonAddDelete (index) {


    tempBarangAddEdit = listData[index]

    // Kelas 'ajs-app-buttons' + 'is-danger' ditempel ke root dialog supaya CSS tombol
    // OK/Cancel di public/css/report-table.css (blok "gaya bersama") ikut jalan;
    // 'is-danger' bikin tombol OK merah karena hapus item aksi merusak.
    var dlgHapusItem = alertify.confirm('Hapus Item', 'Apakah yakin ingin menghapus Item ?',
        function() {

          let choice = "D"
          let _token  = $("#_token").val()
          let nobukti  = $("#input_add_nobukti").val()
          let nourut  = $("#input_add_nourut").val()
          let transaksi  = $("#input_add_transaksi").val()
          let note  = $("#input_add_kepadaterima").val()
          let kodeperkiraan  = $("#input_add_kodeperkiraan").val()
          let tanggal = $("#input_add_tanggal").val()

          let lampiran = 0
          let keterangan2 = ''


          let kodedevisi  = $("#AddAddKodeDevisi").val()
          let valas  = $("#AddAddValas").val()
          let kurs  = $("#AddAddKurs").val()
          let lawan  = $("#AddAddLawan").val()
          let jumlah  = unformatAngka($("#AddAddJumlah").val())
          let keterangan  = $("#AddAddKeterangan").val()
          let keterangandetail  = $("#AddAddKeteranganDetail").val()
          let kodedepartemen  = $("#AddAddKodeDepartemen").val()

          let kredit = 0
          let kreditrp = 0

          let tphc = 'C'


          let jumlahrp = 0


          let urut = tempBarangAddEdit.Urut

          let custsuppP = tempBarangAddEdit.CustSuppP
          let custsuppL = tempBarangAddEdit.CustSuppL
          let noaktivaP = tempBarangAddEdit.NoAktivaP
          let noaktivaL = tempBarangAddEdit.NoAktivaL
          let statusaktivaP = tempBarangAddEdit.StatusAktivaP
          let statusaktivaL = tempBarangAddEdit.StatusAktivaL

          let nobon = $("#input_add_bon").val()
          let kodebag = '-'

          let kodeP = tempBarangAddEdit.KodeP
          let kodeL = tempBarangAddEdit.KodeL
          let statusgiro = tempBarangAddEdit.StatusGiro
          let simbol = $("#input_add_simbol").val()
          let flagsimbol = ''
          let kodecost = ''
          let kodesubcost = ''
          let nodph = tempBarangAddEdit.NODPH
          let urutdph = tempBarangAddEdit.urutDPH
          let dppdph = ''
          let tp = ''
          let ppklx = ''
          let nofaktur = ''
          let plok = 0
          let nobons = $("#input_add_bon").val()
          let jmlrecord = tipeform == 'add' ? 0 : 1
          let notitipan = tempBarangAddEdit.notitipan
          let uruttitipan = tempBarangAddEdit.URUTTITIPAN
          let perkiraanx = transaksi == 'BKK' ? lawan : kodeperkiraan
          let lawanx = transaksi == 'BKK' ? kodeperkiraan : lawan

          console.log({
            tipeform,
            _token,
            nobukti ,
            nourut,
            transaksi,
            note,
            kodeperkiraan ,
            tanggal,
            lampiran ,
            keterangan2 ,
            kodedevisi ,
            valas ,
            kurs  ,
            lawan ,
            jumlah,
            keterangan  ,
            keterangandetail ,
            kodedepartemen  ,
            kredit,
            kreditrp,
            tphc,
            jumlahrp ,
            urut ,
            custsuppP ,
            custsuppL,
            noaktivaP ,
            noaktivaL ,
            statusaktivaP ,
            statusaktivaL ,
            nobon ,
            kodebag ,
            kodeP ,
            kodeL ,
            statusgiro ,
            simbol,
            flagsimbol ,
            kodecost ,
            kodesubcost ,
            nodph ,
            urutdph,
            dppdph,
            tp,
            ppklx ,
            nofaktur,
            plok ,
            nobons,
            jmlrecord,
            notitipan ,
            uruttitipan
          })



          $.ajax({
              url: KAS_ROUTES.kasspadd,
              type: "post",
              async: false,
              data: {
                choice,
                tipeform,
                _token,
                nobukti ,
                nourut,
                transaksi,
                note,
                kodeperkiraan ,
                tanggal,

                lampiran ,
                keterangan2 ,
                perkiraanx,
                lawanx,

                kodedevisi ,
                valas ,
                kurs  ,
                lawan ,
                jumlah,
                keterangan  ,
                keterangandetail ,
                kodedepartemen  ,

                kredit,
                kreditrp,

                tphc,
                jumlahrp ,


                urut ,

                custsuppP ,
                custsuppL,
                noaktivaP ,
                noaktivaL ,
                statusaktivaP ,
                statusaktivaL ,

                nobon ,
                kodebag ,

                kodeP ,
                kodeL ,
                statusgiro ,
                simbol,
                flagsimbol ,
                kodecost ,
                kodesubcost ,
                nodph ,
                urutdph,
                dppdph,
                tp,
                ppklx ,
                nofaktur,
                plok ,
                nobons,
                jmlrecord,
                notitipan ,
                uruttitipan
              },
              success: function(res) {
                console.log(res ,'!')

                if (res == 1) {
                  // $("#form").modal('toggle')
                  alertify.success('Kas telah dihapus');
                  loadAll()
                  // buttonCloseForm()
                  tipeform = 'edit'
                  // document.getElementById("buttonAddListCustomer").disabled = true
                  // document.getElementById("input_add_tanggal").disabled = true
                  $('.showhideitem').hide();
                  refreshDataTable(nobukti)

                  // $("#form").modal('toggle')

                }
                if (res == 2) {
                  setNewNoBukti()
                  alertify.warning('Nobukti telah di refresh, silahkan submit ulang');
                }

              },
              error: function (err) {
                console.log(err)
                alertify.warning('Terjadi kesalahan silahkan refresh browser')
              }
            })
        }
      ,function(){
        console.log('no')
      });
  dlgHapusItem.elements.root.classList.add('ajs-app-buttons', 'is-danger');

}



function buttonAddPickInvoice () {
  let checkDate = new Date($("#input_add_tanggal").val())
  let periode_bulan = document.getElementById("periode_bulan").value
  let periode_tahun = document.getElementById("periode_tahun").value
  let nobukti = $("#input_add_nobukti").val();
  let nourut = $("#input_add_nourut").val();

  let tanggal = $("#input_add_tanggal").val();
    let kodecustsupp = $("#input_add_kodecustomer").val();

  if ( checkDate.getFullYear()  !== Number(periode_tahun)  || (checkDate.getMonth() +1) !== Number(periode_bulan) ) {

      alertify.warning("Tanggal tidak sesuai periode");
      return
  }
  console.log(nourut)
  let _token = $("#_token").val();
  console.log("buttonAddPickInvoice")
  let tempData = []
  // let checkQnt = 0
  let checkMinus = 0
  console.log(listInvoice)
    listInvoice.forEach((item, i) => {
      console.log(document.getElementById(`add_checkbox${i}`).checked)
      if (document.getElementById(`add_checkbox${i}`).checked) {

        let checkNilai = $(`#add_inputQnt${i}`).val();
        let checkKurs = $(`#add_inputKurs${i}`).val();
        let checkNilaiRp = $(`#add_inputQntRp${i}`).val();
        // add_inputKeterangan
        listInvoice[i].Keterangan = $(`#add_inputKeterangan${i}`).val();
        listInvoice[i].inputNilai = checkNilai
        listInvoice[i].inputKurs = checkKurs
        listInvoice[i].inputNilaiRp = checkNilaiRp
        if (Number(checkNilai) < 0 || Number(checkKurs) < 0 ) {
          checkMinus = 1
        }

        tempData.push(listInvoice[i])

      }


    });
    console.log(tempData)

    if (!tempData.length) {
      alertify.warning("Tidak ada item dipilih");
      return
    }

    if (checkMinus) {
      alertify.warning("Qnt <= 0");
      return
    }

    $.ajax({
        url: KAS_ROUTES.kreditnotespadd,
        type: "post",
        async: false,
        data: {
          _token : _token,
          tempData,
          tanggal: tanggal,
          nobukti,
          nourut,
          kodecustsupp,
          tipeform,
          nourut
        },
        success: function(res) {
          console.log(res ,'!')

          if (res == 1) {
            // $("#form").modal('toggle')
            alertify.success('KN telah ditambah');
            loadAll()
            // buttonCloseForm()
            tipeform = 'edit'
            document.getElementById("buttonAddListCustomer").disabled = true
            document.getElementById("input_add_tanggal").disabled = true

            refreshDataTable(nobukti)

            $("#form").modal('toggle')

          }
          if (res == 2) {
            setNewNoBukti()
            alertify.warning('Nobukti telah di refresh, silahkan submit ulang');
          }
          //
          // if (res == 3 ) {
          //   alertify.warning('Stok gudang tidak mencukupi');
          // }

        },
        error: function (err) {
          console.log(err)
          alertify.warning('Terjadi kesalahan silahkan refresh browser')
        }
      })




}

// Ambil daftar Lawan untuk perkiraan header. null = gagal / perkiraan belum dipilih.
function muatLawan () {
  let _token = $("#_token").val();
  let perkiraan = $("#input_add_kodeperkiraan").val();
  let transaksi = $("#input_add_transaksi").val();
  if(!perkiraan ) {
    alertify.warning("Pilih perkiraan terlebih dahulu")
    return null
  }

  let rows = null
  $.ajax({
    url: KAS_ROUTES.kaslistlawan,
    type: "post",
    async: false,
    data: {
      _token,
      perkiraan,
      transaksi
    },
    success: function(res) {
      console.log(res)
      rows = res
    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
    }

  })
  return rows
}

// cari: teks awal kotak Search DataTables ('' = tampilkan semua).
function bukaListLawan (rows, cari) {
  if (rows === null) {
    return
  }

  listLawan  = rows
  let rowTable = ``
  rows.forEach((item, i) => {
    rowTable += `
        <tr class="pick-row" onclick="buttonAddPickLawan(${i},'${item.Perkiraan}' , '${item.Keterangan}' , '${item.Simbol}', '${item.Kode}', '${item.iscost}' , '${item.IsLokalOrExim}' )">
        <td>${item.Perkiraan}</td>
        <td>${item.Keterangan}</td>
        <td>${item.Simbol}</td>
        </tr>`
  });

  document.getElementById("tabel_data_add_list_lawan").innerHTML = rowTable

  kasInitPicker('tabel_add_list_lawan', {
    columnDefs: [{ type: 'string', targets: 0 }]
  });
  if (cari) {
    $('#tabel_add_list_lawan').DataTable().search(cari).draw()
  }

  if (rows.length) {
    $('.showhidemodalbodyadd').hide();
    kasBukaPane('modalAddListLawan');
    $("#form").modal('toggle')
  } else {
    alertify.warning("Perkiraan tidak ditemukkan")
  }
}

function buttonAddListLawan () {
  listLawan = []

  console.log('buttonAddListLawan')

  bukaListLawan(muatLawan(), '')
}

// Enter: kosong -> daftar lengkap; kode Perkiraan persis cocok -> langsung dipilih (alur
// buttonAddPickLawan tetap jalan, mis. Aktiva/DPH/Titipan membuka modal lanjutannya); selain itu
// -> daftar disaring dengan teks yang diketik. Sama seperti onKeyPressNoTitipan().
function onKeyPressLawan (e) {
  if (e.which !== 13) {
    return
  }
  e.preventDefault()

  let cari = ($("#AddAddLawan").val() || '').trim()
  let rows = muatLawan()
  if (rows === null) {
    return
  }

  if (cari) {
    let idx = rows.findIndex(item => String(item.Perkiraan || '').trim().toLowerCase() === cari.toLowerCase())
    if (idx !== -1) {
      let item = rows[idx]
      listLawan = rows
      // String(...) supaya argumennya sama dengan yang dikirim onclick baris di bukaListLawan().
      buttonAddPickLawan(idx, String(item.Perkiraan), String(item.Keterangan), String(item.Simbol),
        String(item.Kode), String(item.iscost), String(item.IsLokalOrExim))
      return
    }
  }

  bukaListLawan(rows, cari)
}

// Teks Lawan diubah: kode & keterangan Lawan lama tidak berlaku lagi sampai dipilih ulang
// (Enter / daftar). submitAddAdd() menolak Lawan tanpa keterangan.
function onInputLawan () {
  document.getElementById("AddAddKodeLawan").value = ''
  document.getElementById("AddAddKeteranganLawan").value = ''
}

function modalDPP (dataLawan) {
  listDPP = []

  console.log('modalDPP')

  console.log(dataLawan)


  let _token = $("#_token").val();
  let valas = $("#AddAddValas").val();

  $.ajax({
    url: KAS_ROUTES.kaslistdpp,
    type: "get",
    async: false,
    data: {
      _token,
      valas
    },
    success: function(res) {
      console.log(res)
      listDPP = res
      let rowTable = ``
      res.forEach((item, i) => {
        rowTable += `
        <tr class="pick-row" onclick="buttonAddPickDPP(${i} , '${dataLawan.Perkiraan}', '${dataLawan.Kode}' , '${dataLawan.Keterangan}')">
        <td>${item.Nobukti}</td>
        <td>${item.KODECUSTSUPP}</td>
        <td>${item.NAMACUSTSUPP}</td>
        <td class="text-right">${item.DIBAYAR ? formatAngka(parseFloat(item.DIBAYAR).toFixed(2)) : '0.00'}</td>
        <td class="text-right">${item.KL ? formatAngka(parseFloat(item.KL).toFixed(2)) : '0.00'}</td>
        <td class="text-right">${item.LB ? formatAngka(parseFloat(item.LB).toFixed(2)) : '0.00'}</td>
        </tr>`
      });






      // if(!res.length) {
      //   rowTable= `<tr><td class="text-center" colspan=5>Tidak ada data</td></tr>`
      // }
      document.getElementById("tabel_data_add_list_dpp").innerHTML = rowTable
      kasInitPicker('tabel_add_list_dpp');

      if (res.length) {

        $('.showhidemodalbodyadd').hide();
        kasBukaPane('modalAddListDPP');
        // $("#form").modal('toggle')
      } else {
        alertify.warning("DPP tidak ditemukkan")
      }


    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
    }

  })


}

function modalDPHUHTBKM (dataLawan) {


  console.log('modalDPHUHTBKM')

  console.log(dataLawan)


  let _token = $("#_token").val();
  let valas = $("#AddAddValas").val();

  $.ajax({
    url: KAS_ROUTES.kaslistcustsuppumb,
    type: "get",
    async: false,
    data: {
      _token,
    },
    success: function(res) {
      console.log(res)
      let rowTable = ``
      res.forEach((item, i) => {
        rowTable += `
        <tr class="pick-row" onclick="buttonAddPickCustDPHUHTBKM( '${item.KODESUPP}', '${item.NAMACUSTSUPP}','${dataLawan.Perkiraan}', '${dataLawan.Kode}' , '${dataLawan.Keterangan}')">
        <td>${item.KODESUPP}</td>
        <td>${item.NAMACUSTSUPP}</td>
        </tr>`
      });






      // if(!res.length) {
      //   rowTable= `<tr><td class="text-center" colspan=5>Tidak ada data</td></tr>`
      // }
      document.getElementById("tabel_data_add_list_dphuhtbkm_custsupp").innerHTML = rowTable
      kasInitPicker('tabel_add_list_dphuhtbkm_custsupp');
      document.getElementById("input_dphuhtbkm_namacustsupp").value = ''
      document.getElementById("input_dphuhtbkm_kodecustsupp").value = ''

      document.getElementById("tabel_data_add_list_dphuhtbkm").innerHTML = `
        <tr>
          <td colspan=10 class="text-center">Data tidak ditemukkan</td>
        </tr>
      `


      if (res.length) {

        $('.showhidemodalbodyadd').hide();
        kasBukaPane('modalAddListDPHUHTBKM');
        // $("#form").modal('toggle')
      } else {
        alertify.warning("C tidak ditemukkan")
      }


    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
    }

  })


}

function modalDPHUHT (dataLawan) {
  listDPH = []

  console.log('modalDPHUHT')

  console.log(dataLawan)


  let _token = $("#_token").val();
  let valas = $("#AddAddValas").val();

  $.ajax({
    url: KAS_ROUTES.kaslistdphuht,
    type: "get",
    async: false,
    data: {
      _token,
      valas
    },
    success: function(res) {
      console.log(res)
      listDPH = res
      let rowTable = ``
      res.forEach((item, i) => {
        rowTable += `
        <tr class="pick-row" onclick="buttonAddPickDPH(${i} , '${dataLawan.Perkiraan}', '${dataLawan.Kode}' , '${dataLawan.Keterangan}')">
        <td>${item.Nobukti}</td>
        <td>${item.NOUM}</td>
        <td>${item.KODECUSTSUPP}</td>
        <td>${item.NAMACUSTSUPP}</td>
        <td class="text-right">${item.DIBAYAR ? formatAngka(parseFloat(item.DIBAYAR).toFixed(2)) : '0.00'}</td>
        <td class="text-right">${item.KL ? formatAngka(parseFloat(item.KL).toFixed(2)) : '0.00'}</td>
        <td class="text-right">${item.LB ? formatAngka(parseFloat(item.LB).toFixed(2)) : '0.00'}</td>
        </tr>`
      });






      // if(!res.length) {
      //   rowTable= `<tr><td class="text-center" colspan=5>Tidak ada data</td></tr>`
      // }
      document.getElementById("tabel_data_add_list_dphuht").innerHTML = rowTable
      kasInitPicker('tabel_add_list_dphuht');

      if (res.length) {

        $('.showhidemodalbodyadd').hide();
        kasBukaPane('modalAddListDPHUHT');
        // $("#form").modal('toggle')
      } else {
        alertify.warning("DPH tidak ditemukkan")
      }


    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
    }

  })


}

function submitAddAktiva () {
  //  dataLawanx
  //

  console.log(dataLawanx)
  console.log(xaktiva)

  document.getElementById("AddAddKodeLawan").value = dataLawanx.Kode
  document.getElementById("AddAddKeteranganLawan").value = dataLawanx.Keterangan

  document.getElementById("AddAddLawan").value = dataLawanx.Perkiraan

  // Keterangan di jendela Aktiva (terisi keterangan aktiva, boleh diubah) disalin ke Keterangan
  // item saat Simpan - menimpa isi sebelumnya; setelah itu Keterangan item tetap bisa diubah.
  // Batal tidak menyalin apa-apa.
  document.getElementById("AddAddKeterangan").value = $("#input_aktiva_keterangan").val()

  $("#form").modal('toggle')




}

// function lockFormAktivaDetail (value = true) {
//   document.getElementById("input_aktiva_tglpemakaian").disabled = value
//   document.getElementById("input_aktiva_tglperolehan").disabled = value
//   document.getElementById("input_aktiva_keterangan").disabled = value
// }

function cleanFormAktivaDetailX () {
  document.getElementById("input_aktivax_keterangan").value = ''
  // document.getElementById("input_aktivax_keterangan").disabled = false

  // console.log(listAktiva[index])
  // xaktiva = listAktiva[index]
  // xkodelawan
  document.getElementById("input_aktivax_noaktiva").value = ''
  document.getElementById("input_aktivax_groupaktiva").value = ''
  document.getElementById("input_aktivax_namagroupaktiva").value = ''
  // document.getElementById("input_aktivax_noaktiva").value = xaktiva.Perkiraan
  document.getElementById("input_aktivax_devisi").value = ''
  document.getElementById("input_aktivax_namadevisi").value = ''

  document.getElementById("input_aktivax_keterangan").value = ''
  document.getElementById("input_aktivax_tipeaktiva").value = 0
  document.getElementById("input_aktivax_metodepenyusutan").value = 'L'

  document.getElementById("input_aktivax_kuantum").value =  '1.00'
  document.getElementById("input_aktivax_susut").value = '0.00'
  document.getElementById("input_aktivax_akumulasi").value = ''

  document.getElementById("input_aktivax_biaya1").value = '-'
  document.getElementById("input_aktivax_persen1").value = '0.00'

  document.getElementById("input_aktivax_biaya2").value = ''
  document.getElementById("input_aktivax_persen2").value = '0.00'

  document.getElementById("input_aktivax_biaya3").value = ''
  document.getElementById("input_aktivax_persen3").value = '0.00'
  document.getElementById("input_aktivax_tglperolehan").value = formatDate(new Date())
  document.getElementById("input_aktivax_tglpemakaian").value = formatDate(new Date())


}


function setNewNoAktiva (groupaktiva) {
  let _token = $("#_token").val()
  $.ajax({
    url: KAS_ROUTES.kasgetnourutaktiva,
    type: "post",
    async: false,
    data: {
      nomuka : xlawan.Perkiraan,
      _token

    },
    success: function(res) {

      console.log(res)
      // document.getElementById("input_add_nobukti").value = res[0].Nobukti
      // document.getElementById("input_add_nourut").value = res[0].Nourut
      document.getElementById("input_aktivax_noaktiva").value = xlawan.Perkiraan + '.' + res[0].NoUrut
      document.getElementById("input_aktivax_nobelakang").value = res[0].NoUrut
      // cari nourut

    }})
}

/* Nilai awal form aktiva baru = setting aktiva terakhir di group yang sama (lihat
   KasController::getNoUrutAktiva()). Kolom yang NULL (group belum punya aktiva) dilewati,
   jadi form tetap memakai default dari cleanFormAktivaDetailX(). */
function kasIsiSettingAktivaBaru(r) {
  if (!r) { return }
  let isiTeks = (id, v) => { if (v !== null && v !== undefined) { document.getElementById(id).value = String(v).trim() } }
  let isiPersen = (id, v) => { if (v !== null && v !== undefined) { document.getElementById(id).value = parseFloat(v || 0).toFixed(2) } }

  isiPersen('input_aktivax_susut', r.Persen)
  let metode = (r.Tipe || '').trim().toUpperCase()
  if (['L', 'M', 'P'].includes(metode)) {
    document.getElementById('input_aktivax_metodepenyusutan').value = metode
  }
  isiTeks('input_aktivax_akumulasi', r.Akumulasi)
  isiTeks('input_aktivax_biaya1', r.Biaya)
  isiPersen('input_aktivax_persen1', r.PersenBiaya1)
  isiTeks('input_aktivax_biaya2', r.Biaya2)
  isiPersen('input_aktivax_persen2', r.PersenBiaya2)
  isiTeks('input_aktivax_biaya3', r.biaya3)
  isiPersen('input_aktivax_persen3', r.persenbiaya3)
}

function buttonAddNewAktiva () {
  console.log('buttonAddNewAktiva')
  cleanFormAktivaDetailX()
  let _token = $("#_token").val()
  $.ajax({
    url: KAS_ROUTES.kasgetnourutaktiva,
    type: "post",
    async: false,
    data: {
      nomuka : xlawan.Perkiraan,
      _token

    },
    success: function(res) {

      console.log(res)
      // document.getElementById("input_add_nobukti").value = res[0].Nobukti
      // document.getElementById("input_add_nourut").value = res[0].Nourut
      document.getElementById("input_aktivax_namagroupaktiva").value = xlawan.Keterangan
      document.getElementById("input_aktivax_groupaktiva").value = xlawan.Perkiraan
      document.getElementById("input_aktivax_devisi").value = $('#AddAddKodeDevisi').val();
      document.getElementById("input_aktivax_namadevisi").value = $('#AddAddNamaDevisi').val();
      document.getElementById("input_aktivax_noaktiva").value = xlawan.Perkiraan + '.' + res[0].NoUrut
      document.getElementById("input_aktivax_nobelakang").value = res[0].NoUrut
      kasIsiSettingAktivaBaru(res[0])
      // cari nourut

      $('.showhidemodalbodyadd').hide();
      kasBukaPane('modalAddListAktivaDetailX');
    }})


}

function modalAktiva (dataLawan) {
  listAktiva = []

  console.log('modalAktiva')
  dataLawanx = dataLawan
  console.log(dataLawan)
  kasParkirTombolAktivaBaru();

  let _token = $("#_token").val();
  let devisi = $("#AddAddKodeDevisi").val();
  let lawan = dataLawan.Perkiraan
  let xkodelawan = dataLawan.Kode

  if (xkodelawan == 'AKV') {
    console.log("masuk AKV")
    $.ajax({
      url: KAS_ROUTES.kaslistaktiva,
      type: "post",
      async: false,
      data: {
        _token,
        devisi,
        perkiraan: lawan
      },
      success: function(res) {
        console.log(res)
        listAktiva = res
        let rowTable = ``
        // BKK + AKV = mode tambah: baris hanya informasi, lanjutnya lewat "+ Aktiva baru".
        let hanyaTambah = $('#input_add_transaksi').val() === 'BKK'
        res.forEach((item, i) => {
          rowTable += `
          ${hanyaTambah
            ? `<tr onclick="kasAktivaHanyaTambah()">`
            : `<tr class="pick-row" onclick="buttonAddPickAktiva(${i} , '${dataLawan.Perkiraan}', '${dataLawan.Kode}' , '${dataLawan.Keterangan}')">`}
          <td>${item.Perkiraan}</td>
          <td>${item.Keterangan}</td>
          <td>${formatDate(item.Tanggal)}</td>
          </tr>`
        });






        // if(!res.length) {
        //   rowTable= `<tr><td class="text-center" colspan=5>Tidak ada data</td></tr>`
        // }

        document.getElementById("tabel_data_add_list_aktiva").innerHTML = rowTable

        if (res.length) {


          // $("#form").modal('toggle')
        } else {
          // alertify.warning("Aktiva tidak ditemukkan")
          document.getElementById("tabel_data_add_list_aktiva").innerHTML = `
            <tr>
              <td colspan=3>Belum ada data</td>
            </tr>
          `


        }
        kasInitPicker('tabel_add_list_aktiva');
        kasPasangTombolAktivaBaru();

        $('.showhidemodalbodyadd').hide();
        kasBukaPane('modalAddListAktiva');


      },
      error: function (err) {
        console.log(err)
        alertify.warning('Terjadi kesalahan silahkan refresh browser')
      }

    })
  } else {
    console.log("masuk AKM")
    $.ajax({
      url: KAS_ROUTES.kaslistakumulasi,
      type: "post",
      async: false,
      data: {
        _token,
        perkiraan: lawan
      },
      success: function(res) {
        console.log(res)
        listAktiva = res
        let rowTable = ``
        res.forEach((item, i) => {
          rowTable += `
          <tr class="pick-row" onclick="buttonAddPickAktiva(${i} , '${dataLawan.Perkiraan}', '${dataLawan.Kode}' , '${dataLawan.Keterangan}')">
          <td>${item.Perkiraan}</td>
          <td>${item.Keterangan}</td>
          <td>${formatDate(item.Tanggal)}</td>
          </tr>`
        });






        // if(!res.length) {
        //   rowTable= `<tr><td class="text-center" colspan=5>Tidak ada data</td></tr>`
        // }

        document.getElementById("tabel_data_add_list_aktiva").innerHTML = rowTable
        kasInitPicker('tabel_add_list_aktiva');


        if (res.length) {

          $('.showhidemodalbodyadd').hide();
          kasBukaPane('modalAddListAktiva');
          // $("#form").modal('toggle')
        } else {
          alertify.warning("Akumulasi tidak ditemukkan")
        }


      },
      error: function (err) {
        console.log(err)
        alertify.warning('Terjadi kesalahan silahkan refresh browser')
      }

    })
  }




}

function modalDPH (dataLawan) {
  listDPH = []

  console.log('modalDPH')

  console.log(dataLawan)


  let _token = $("#_token").val();
  let valas = $("#AddAddValas").val();

  $.ajax({
    url: KAS_ROUTES.kaslistdph,
    type: "get",
    async: false,
    data: {
      _token,
      valas
    },
    success: function(res) {
      console.log(res)
      listDPH = res
      let rowTable = ``
      res.forEach((item, i) => {
        rowTable += `
        <tr class="pick-row" onclick="buttonAddPickDPH(${i} , '${dataLawan.Perkiraan}', '${dataLawan.Kode}' , '${dataLawan.Keterangan}')">
        <td>${item.Nobukti}</td>
        <td>${item.KODECUSTSUPP}</td>
        <td>${item.NAMACUSTSUPP}</td>
        <td class="text-right">${item.DIBAYAR ? formatAngka(parseFloat(item.DIBAYAR).toFixed(2)) : '0.00'}</td>
        <td class="text-right">${item.KL ? formatAngka(parseFloat(item.KL).toFixed(2)) : '0.00'}</td>
        <td class="text-right">${item.LB ? formatAngka(parseFloat(item.LB).toFixed(2)) : '0.00'}</td>
        </tr>`
      });






      // if(!res.length) {
      //   rowTable= `<tr><td class="text-center" colspan=5>Tidak ada data</td></tr>`
      // }
      document.getElementById("tabel_data_add_list_dph").innerHTML = rowTable
      kasInitPicker('tabel_add_list_dph');

      if (res.length) {

        $('.showhidemodalbodyadd').hide();
        kasBukaPane('modalAddListDPH');
        // $("#form").modal('toggle')
      } else {
        alertify.warning("DPH tidak ditemukkan")
      }


    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
    }

  })


}


function buttonMinusUMB (index) {

  let _token = $("#_token").val();
  let umb = listUMB[index]
  let nobukti = $("#input_add_nobukti").val();
  $.ajax({
    url: KAS_ROUTES.kasspdeletetemprumjual,
    type: "post",
    async: false,
    data: {
      _token,
      noumb: umb.NOBUKTI,
      noretur: umb.NORETUR ,
      tanggal: umb.TANGGAL ,
      noso: umb.NOSO ,
      valas: umb.VALAS ,
      kurs: umb.KURS ,
      dpp: umb.DPP ,
      ppn: umb.PPN ,
      kodesupp: umb.KODESUPP ,
      subtotal: umb.SUBTOTAL ,
      nobukti: nobukti,
      urut: 0
    },
    success: function(res) {
      console.log(res)
      listUMB = res
      let rowTable = ``
      res.forEach((item, i) => {
        if (item.NORETUR == '') {
          rowTable += `
          <tr>
          <td>${item.NOBUKTI}</td>
          <td>${item.NORETUR ? item.NORETUR : '' }</td>
          <td>${item.TANGGAL ? formatDate(item.TANGGAL) : '' }</td>
          <td>${item.NOSO}</td>
          <td>${item.VALAS}</td>
          <td>${item.KURS}</td>

          <td class="text-center"><input id="add_inputDPPUMBQnt${i}" style="height:30px; min-width: 130px" type="number" value='${parseFloat(item.DPP).toFixed(2)}' class="form-control text-right" onBlur="onChangeDPPUMB(${i})"></td>

          <td class="text-right">${parseFloat(item.PPN).toFixed(2)}</td>
          <td class="text-right">${parseFloat(item.SUBTOTAL).toFixed(2)}</td>

          <td class="text-center"><button class="btn btn-primary btn-sm" onclick="buttonPlusUMB(${i}  )" type="button" ><i class="bi bi-plus"></i></button></td>

          </tr>`


        } else {
          rowTable += `
          <tr style="background-color: #FF746C">
          <td>${item.NOBUKTI}</td>
          <td>${item.NORETUR ? item.NORETUR : '' }</td>
          <td>${item.TANGGAL ? formatDate(item.TANGGAL) : '' }</td>
          <td>${item.NOSO}</td>
          <td>${item.VALAS}</td>
          <td>${item.KURS}</td>
          <td class="text-center"><input id="add_inputDPPUMBQnt${i}" style="height:30px; min-width: 130px" type="number" value='${parseFloat(item.DPP).toFixed(2)}' class="form-control text-right" onBlur="onChangeDPPUMB(${i})"></td>
          <td class="text-right">${parseFloat(item.PPN).toFixed(2)}</td>
          <td class="text-right">${parseFloat(item.SUBTOTAL).toFixed(2)}</td>

          <td class="text-center"><button class="btn btn-danger btn-sm" onclick="buttonMinusUMB(${i}  )" type="button" ><i class="bi bi-trash"></i></button></td>

          </tr>`

        }

      });






      // if(!res.length) {
      //   rowTable= `<tr><td class="text-center" colspan=5>Tidak ada data</td></tr>`
      // }
      document.getElementById("tabel_data_add_list_dphuhtbkm").innerHTML = rowTable

      if (res.length) {
        document.getElementById("AddAddJumlah").value = formatAngka(parseFloat(res[0].totalqntx).toFixed(2))
      } else {
        document.getElementById("AddAddJumlah").value = '0.00'
        document.getElementById("tabel_data_add_list_dphuhtbkm").innerHTML = `
          <tr>
            <td colspan=10 class="text-center">Data tidak ditemukkan</td>
          </tr>
        `
        alertify.warning("Data tidak ditemukkan")
      }


    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
    }

  })


}


function buttonPlusUMB (index) {

  let _token = $("#_token").val();
  let umb = listUMB[index]
  let nobukti = $("#input_add_nobukti").val();
  $.ajax({
    url: KAS_ROUTES.kasspaddtemprumjual,
    type: "post",
    async: false,
    data: {
      _token,
      noumb: umb.NOBUKTI,
      noretur: "" ,
      tanggal: umb.TANGGAL ,
      noso: umb.NOSO ,
      valas: umb.VALAS ,
      kurs: umb.KURS ,
      dpp: umb.DPP ,
      ppn: umb.PPN ,
      kodesupp: umb.KODESUPP ,
      subtotal: umb.SUBTOTAL ,
      nobukti: nobukti,
      urut: 0
    },
    success: function(res) {
      console.log(res)
      listUMB = res
      let rowTable = ``
      res.forEach((item, i) => {
        if (item.NORETUR == '') {
          rowTable += `
          <tr>
          <td>${item.NOBUKTI}</td>
          <td>${item.NORETUR ? item.NORETUR : '' }</td>
          <td>${item.TANGGAL ? formatDate(item.TANGGAL) : '' }</td>
          <td>${item.NOSO}</td>
          <td>${item.VALAS}</td>
          <td>${item.KURS}</td>
          <td class="text-center"><input id="add_inputDPPUMBQnt${i}" style="height:30px; min-width: 130px" type="number" value='${parseFloat(item.DPP).toFixed(2)}' class="form-control text-right" onBlur="onChangeDPPUMB(${i})"></td>

          <td class="text-center"><input id="add_inputPPNUMBQnt${i}" style="height:30px; min-width: 130px" type="number" value='${parseFloat(item.PPN).toFixed(2)}' class="form-control text-right"  disabled></td>
          <td class="text-center"><input id="add_inputSUBTOTALUMBQnt${i}" style="height:30px; min-width: 130px" type="number" value='${parseFloat(item.SUBTOTAL).toFixed(2)}' class="form-control text-right"  disabled></td>

          <td class="text-center"><button class="btn btn-primary btn-sm" onclick="buttonPlusUMB(${i}  )" type="button" ><i class="bi bi-plus"></i></button></td>

          </tr>`


        } else {
          rowTable += `
          <tr style="background-color: #FF746C">
          <td>${item.NOBUKTI}</td>
          <td>${item.NORETUR ? item.NORETUR : '' }</td>
          <td>${item.TANGGAL ? formatDate(item.TANGGAL) : '' }</td>
          <td>${item.NOSO}</td>
          <td>${item.VALAS}</td>
          <td>${item.KURS}</td>
          <td class="text-center"><input id="add_inputDPPUMBQnt${i}" style="height:30px; min-width: 130px" type="number" value='${parseFloat(item.DPP).toFixed(2)}' class="form-control text-right" onBlur="onChangeDPPUMB(${i})"></td>

          <td class="text-center"><input id="add_inputPPNUMBQnt${i}" style="height:30px; min-width: 130px" type="number" value='${parseFloat(item.PPN).toFixed(2)}' class="form-control text-right"  disabled></td>
          <td class="text-center"><input id="add_inputSUBTOTALUMBQnt${i}" style="height:30px; min-width: 130px" type="number" value='${parseFloat(item.SUBTOTAL).toFixed(2)}' class="form-control text-right"  disabled></td>

          <td class="text-center"><button class="btn btn-danger btn-sm" onclick="buttonMinusUMB(${i}  )" type="button" ><i class="bi bi-trash"></i></button></td>

          </tr>`

        }

      });






      // if(!res.length) {
      //   rowTable= `<tr><td class="text-center" colspan=5>Tidak ada data</td></tr>`
      // }
      document.getElementById("tabel_data_add_list_dphuhtbkm").innerHTML = rowTable

      if (res.length) {
        document.getElementById("AddAddJumlah").value = formatAngka(parseFloat(res[0].totalqntx).toFixed(2))
      } else {
        document.getElementById("AddAddJumlah").value = '0.00'
        document.getElementById("tabel_data_add_list_dphuhtbkm").innerHTML = `
          <tr>
            <td colspan=10 class="text-center">Data tidak ditemukkan</td>
          </tr>
        `
        alertify.warning("Data tidak ditemukkan")
      }


    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
    }

  })


}



function onChangeDPPUMB (index) {

  let qnt = $(`#add_inputDPPUMBQnt${index}`).val();

  if (Number(qnt) < 0) {
    document.getElementById(`add_inputDPPUMBQnt${index}`).value = '0.00'
    document.getElementById(`add_inputPPNUMBQnt${index}`).value = '0.00'
    document.getElementById(`add_inputSUBTOTALUMBQnt${index}`).value = '0.00'
    alertify.warning('Qnt < 0')
    return
  }

  let ppn = listUMB[index].ppnx

  let ppnx = Number(qnt) * Number(ppn)
  let subtotalx = Number(ppnx) + Number(qnt)





  let _token = $("#_token").val();
  let umb = listUMB[index]
  let nobukti = $("#input_add_nobukti").val();

  console.log(qnt, ppnx)
  $.ajax({
    url: KAS_ROUTES.kasspupdatetemprumjual,
    type: "post",
    async: false,
    data: {
      _token,
      noumb: umb.NOBUKTI,
      noretur: umb.NORETUR ,
      tanggal: umb.TANGGAL ,
      noso: umb.NOSO ,
      valas: umb.VALAS ,
      kurs: umb.KURS ,
      dpp: qnt ,
      ppn: ppnx ,
      kodesupp: umb.KODESUPP ,
      subtotal: subtotalx ,
      nobukti: nobukti,
      urut: 0
    },
    success: function(res) {
      console.log(res)
      listUMB = res
      let rowTable = ``
      res.forEach((item, i) => {
        if (item.NORETUR == '') {
          rowTable += `
          <tr>
          <td>${item.NOBUKTI}</td>
          <td>${item.NORETUR ? item.NORETUR : '' }</td>
          <td>${item.TANGGAL ? formatDate(item.TANGGAL) : '' }</td>
          <td>${item.NOSO}</td>
          <td>${item.VALAS}</td>
          <td>${item.KURS}</td>
          <td class="text-center"><input id="add_inputDPPUMBQnt${i}" style="height:30px; min-width: 130px" type="number" value='${parseFloat(item.DPP).toFixed(2)}' class="form-control text-right" onBlur="onChangeDPPUMB(${i})"></td>

          <td class="text-center"><input id="add_inputPPNUMBQnt${i}" style="height:30px; min-width: 130px" type="number" value='${parseFloat(item.PPN).toFixed(2)}' class="form-control text-right"  disabled></td>
          <td class="text-center"><input id="add_inputSUBTOTALUMBQnt${i}" style="height:30px; min-width: 130px" type="number" value='${parseFloat(item.SUBTOTAL).toFixed(2)}' class="form-control text-right"  disabled></td>

          <td class="text-center"><button class="btn btn-primary btn-sm" onclick="buttonPlusUMB(${i}  )" type="button" ><i class="bi bi-plus"></i></button></td>

          </tr>`


        } else {
          rowTable += `
          <tr style="background-color: #FF746C">
          <td>${item.NOBUKTI}</td>
          <td>${item.NORETUR ? item.NORETUR : '' }</td>
          <td>${item.TANGGAL ? formatDate(item.TANGGAL) : '' }</td>
          <td>${item.NOSO}</td>
          <td>${item.VALAS}</td>
          <td>${item.KURS}</td>
          <td class="text-center"><input id="add_inputDPPUMBQnt${i}" style="height:30px; min-width: 130px" type="number" value='${parseFloat(item.DPP).toFixed(2)}' class="form-control text-right" onBlur="onChangeDPPUMB(${i})"></td>

          <td class="text-center"><input id="add_inputPPNUMBQnt${i}" style="height:30px; min-width: 130px" type="number" value='${parseFloat(item.PPN).toFixed(2)}' class="form-control text-right"  disabled></td>
          <td class="text-center"><input id="add_inputSUBTOTALUMBQnt${i}" style="height:30px; min-width: 130px" type="number" value='${parseFloat(item.SUBTOTAL).toFixed(2)}' class="form-control text-right"  disabled></td>

          <td class="text-center"><button class="btn btn-danger btn-sm" onclick="buttonMinusUMB(${i}  )" type="button" ><i class="bi bi-trash"></i></button></td>

          </tr>`

        }

      });






      // if(!res.length) {
      //   rowTable= `<tr><td class="text-center" colspan=5>Tidak ada data</td></tr>`
      // }
      document.getElementById("tabel_data_add_list_dphuhtbkm").innerHTML = rowTable

      if (res.length) {
        document.getElementById("AddAddJumlah").value = formatAngka(parseFloat(res[0].totalqntx).toFixed(2))
      } else {
        document.getElementById("AddAddJumlah").value = '0.00'
        document.getElementById("tabel_data_add_list_dphuhtbkm").innerHTML = `
          <tr>
            <td colspan=10 class="text-center">Data tidak ditemukkan</td>
          </tr>
        `
        alertify.warning("Data tidak ditemukkan")
      }


    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
    }

  })





}


function buttonAddPickCustDPHUHTBKM (kode, nama,perkiraanlawan, kodelawan , keteranganlawan) {




  document.getElementById("input_dphuhtbkm_namacustsupp").value = nama
  document.getElementById("input_dphuhtbkm_kodecustsupp").value = kode

  document.getElementById("AddAddLawan").value = perkiraanlawan
  document.getElementById("AddAddKodeLawan").value = kodelawan
  document.getElementById("AddAddKeteranganLawan").value = keteranganlawan



  let _token = $("#_token").val();

  $.ajax({
    url: KAS_ROUTES.kasprosesumb,
    type: "post",
    async: false,
    data: {
      _token,
      custsupp: kode
    },
    success: function(res) {
      console.log(res)
      listUMB = res
      let rowTable = ``
      res.forEach((item, i) => {
        if (item.NORETUR == '') {
          rowTable += `
          <tr>
          <td>${item.NOBUKTI}</td>
          <td>${item.NORETUR ? item.NORETUR : '' }</td>
          <td>${item.TANGGAL ? formatDate(item.TANGGAL) : '' }</td>
          <td>${item.NOSO}</td>
          <td>${item.VALAS}</td>
          <td>${item.KURS}</td>
          <td class="text-center"><input id="add_inputDPPUMBQnt${i}" style="height:30px; min-width: 130px" type="number" value='${parseFloat(item.DPP).toFixed(2)}' class="form-control text-right" onBlur="onChangeDPPUMB(${i})"></td>

          <td class="text-center"><input id="add_inputPPNUMBQnt${i}" style="height:30px; min-width: 130px" type="number" value='${parseFloat(item.PPN).toFixed(2)}' class="form-control text-right"  disabled></td>
          <td class="text-center"><input id="add_inputSUBTOTALUMBQnt${i}" style="height:30px; min-width: 130px" type="number" value='${parseFloat(item.SUBTOTAL).toFixed(2)}' class="form-control text-right"  disabled></td>

          <td class="text-center"><button class="btn btn-primary btn-sm" onclick="buttonPlusUMB(${i}  )" type="button" ><i class="bi bi-plus"></i></button></td>

          </tr>`


        } else {
          rowTable += `
          <tr style="background-color: #FF746C">
          <td>${item.NOBUKTI}</td>
          <td>${item.NORETUR ? item.NORETUR : '' }</td>
          <td>${item.TANGGAL ? formatDate(item.TANGGAL) : '' }</td>
          <td>${item.NOSO}</td>
          <td>${item.VALAS}</td>
          <td>${item.KURS}</td>
          <td class="text-center"><input id="add_inputDPPUMBQnt${i}" style="height:30px; min-width: 130px" type="number" value='${parseFloat(item.DPP).toFixed(2)}' class="form-control text-right" onBlur="onChangeDPPUMB(${i})"></td>

          <td class="text-center"><input id="add_inputPPNUMBQnt${i}" style="height:30px; min-width: 130px" type="number" value='${parseFloat(item.PPN).toFixed(2)}' class="form-control text-right"  disabled></td>
          <td class="text-center"><input id="add_inputSUBTOTALUMBQnt${i}" style="height:30px; min-width: 130px" type="number" value='${parseFloat(item.SUBTOTAL).toFixed(2)}' class="form-control text-right"  disabled></td>
<td class="text-center"><button class="btn btn-danger btn-sm" onclick="buttonMinusUMB(${i})" type="button" ><i class="bi bi-dash-lg"></i></button></td>

          </tr>`

        }

      });






      // if(!res.length) {
      //   rowTable= `<tr><td class="text-center" colspan=5>Tidak ada data</td></tr>`
      // }
      document.getElementById("tabel_data_add_list_dphuhtbkm").innerHTML = rowTable



      if (res.length) {

        document.getElementById("AddAddJumlah").value = formatAngka(parseFloat(res[0].totalqntx).toFixed(2))
      } else {
        document.getElementById("AddAddJumlah").value = '0.00'
        document.getElementById("tabel_data_add_list_dphuhtbkm").innerHTML = `
          <tr>
            <td colspan=10 class="text-center">Data tidak ditemukkan</td>
          </tr>
        `
        alertify.warning("Data tidak ditemukkan")
      }


    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
    }

  })



}


function buttonAddPickDPH (indexDPH , perkiraanlawan, kodelawan , keteranganlawan) {


  tempDPPDPH = listDPH[indexDPH]

  document.getElementById("AddAddLawan").value = perkiraanlawan
  document.getElementById("AddAddKodeLawan").value = kodelawan
  document.getElementById("AddAddKeteranganLawan").value = keteranganlawan
  document.getElementById("AddAddJumlah").value = formatAngka(parseFloat(tempDPPDPH.DIBAYAR).toFixed(2))
  console.log(tempDPPDPH)

  // $('.showhideitem').hide();
  buttonAddListBatal()

}


function buttonAddPickDPP (indexDPP , perkiraanlawan, kodelawan , keteranganlawan) {


  tempDPPDPH = listDPP[indexDPP]

  document.getElementById("AddAddLawan").value = perkiraanlawan
  document.getElementById("AddAddKodeLawan").value = kodelawan
  document.getElementById("AddAddKeteranganLawan").value = keteranganlawan
  document.getElementById("AddAddJumlah").value = formatAngka(parseFloat(tempDPPDPH.DIBAYAR).toFixed(2))
  console.log(tempDPPDPH)

  // $('.showhideitem').hide();
  buttonAddListBatal()

}


function buttonAddListDepartemen () {
  listDepartemen = []

  console.log('buttonAddListDepartemen')


  let _token = $("#_token").val();

  $.ajax({
    url: KAS_ROUTES.kaslistdepartemen,
    type: "get",
    async: false,
    data: {
      _token,
    },
    success: function(res) {
      console.log(res)
      listDepartemen  = res
      let rowTable = ``
      res.forEach((item, i) => {
        rowTable += `
        <tr class="pick-row" onclick="buttonAddPickDepartemen(${i},'${item.KDDEP}' , '${item.NMDEP}'  )">
        <td>${item.KDDEP}</td>
        <td>${item.NMDEP}</td>
        </tr>`
      });






      // if(!res.length) {
      //   rowTable= `<tr><td class="text-center" colspan=5>Tidak ada data</td></tr>`
      // }
      document.getElementById("tabel_data_add_list_departemen").innerHTML = rowTable
      kasInitPicker('tabel_add_list_departemen');

      if (res.length) {

        $('.showhidemodalbodyadd').hide();
        kasBukaPane('modalAddListDepartemen');
        $("#form").modal('toggle')
      } else {
        alertify.warning("Departemen tidak ditemukkan")
      }


    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
    }

  })


}

function buttonAddListValas () {
  listValas= []

  console.log('buttonAddListValas')


  let _token = $("#_token").val();


  $.ajax({
    url: KAS_ROUTES.kaslistvalas,
    type: "get",
    async: false,
    data: {
      _token,
    },
    success: function(res) {
      console.log(res)
      listValas = res
      let rowTable = ``
      res.forEach((item, i) => {
        rowTable += `
        <tr class="pick-row" onclick="buttonAddPickValas(${i},'${item.KODEVLS}' , '${item.NAMAVLS}' )">
        <td>${item.KODEVLS}</td>
        <td>${item.NAMAVLS}</td>
        </tr>`
      });






      // if(!res.length) {
      //   rowTable= `<tr><td class="text-center" colspan=5>Tidak ada data</td></tr>`
      // }
      // Bug fix: sebelumnya menulis ke tabel_data_add_list_perkiraan / #modalAddListPerkiraan
      // (copy-paste dari picker Perkiraan) — baris Valas jadi tidak pernah muncul di modal
      // Valas-nya sendiri. Diperbaiki ke id Valas yang benar.
      document.getElementById("tabel_data_add_list_valas").innerHTML = rowTable
      kasInitPicker('tabel_add_list_valas');

      if (res.length) {

        $('.showhidemodalbodyadd').hide();
        kasBukaPane('modalAddListValas');
        $("#form").modal('toggle')
      } else {
        alertify.warning("Valas tidak ditemukkan")
      }


    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
    }

  })


}



function buttonAddListDevisi () {
  listDevisi= []

  console.log('buttonAddListDevisi')


  let _token = $("#_token").val();


  $.ajax({
    url: KAS_ROUTES.kaslistdevisi,
    type: "get",
    async: false,
    data: {
      _token,
    },
    success: function(res) {
      console.log(res)
      listDevisi = res
      let rowTable = ``
      res.forEach((item, i) => {
        rowTable += `
        <tr class="pick-row" onclick="buttonAddPickDevisi(${i},'${item.Devisi}' , '${item.NamaDevisi}' )">
        <td>${item.Devisi}</td>
        <td>${item.NamaDevisi}</td>
        </tr>`
      });






      // if(!res.length) {
      //   rowTable= `<tr><td class="text-center" colspan=5>Tidak ada data</td></tr>`
      // }
      document.getElementById("tabel_data_add_list_devisi").innerHTML = rowTable
      kasInitPicker('tabel_add_list_devisi');

      if (res.length) {

        $('.showhidemodalbodyadd').hide();
        kasBukaPane('modalAddListDevisi');
        $("#form").modal('toggle')
      } else {
        alertify.warning("Devisi tidak ditemukkan")
      }


    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
    }

  })


}

// diam = true: jangan tampilkan peringatan kalau kosong (dipakai buttonBukaTunai(), yang
// lalu jatuh ke daftar supplier/customer). Mengembalikan true bila dbTempHutPiut berisi data.
function refreshDataTableTunai (diam) {
  let nobukti = $("#input_add_nobukti").val()
  let adaData = false
  $.ajax({
    url: KAS_ROUTES.kaslisttunaix,
    type: "get",
    async: false,
    data: {
      nobukti
    },
    success: function(res) {
      console.log(res)

      if (!res.length) {

        if (!diam) {
          alertify.warning("Tidak ada transaksi ditemukkan")
        }
        return
      }
      adaData = true
      renderTunai(res)

    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
    }

  })

  return adaData
}

/* Isi tabel modal pelunasan yang aktif (lihat tunaiUI()) dari KasController::tunaiRows():
   baris 'F' = satu faktur (pelunasan bukti lain sudah ikut dijumlahkan), baris 'L' = pelunasan
   bukti ini, tepat di bawah fakturnya. Saldo baris F = sisa sebelum pelunasan bukti ini, baris
   L = sisa sesudahnya. Jumlah diisi total pelunasan bukti ini. Dipakai buttonAddPickCustSuppX()
   dan refreshDataTableTunai() supaya kedua render sama. */
function renderTunai (res) {
  listTunai = res
  let rowTable = ``
  let jumlah = 0
  let saldo = 0
  res.forEach((item, i) => {
    if (item.Baris == 'L') {
      jumlah += nominalLunasTunai(item)
      saldo += saldoTampilTunai(item)
    } else {
      saldo = saldoTampilTunai(item)
    }
    rowTable += tunaiRowHtml(item, i, saldo)
  })

  document.getElementById("AddAddJumlah").value = formatAngka(parseFloat(jumlah).toFixed(2))
  document.getElementById(tunaiUI().tbody).innerHTML = rowTable
  filterTunai()
}

/* Baris tabel Pelunasan Hutang/Piutang — mengikuti tabel kartu hutang Memorial Koreksi
   (mkKartuRender() di accounting/memorialkoreksi.blade.php):
     - Baris faktur ('F'): tombol + di kolom Action -> tanya nominal lewat prompt
       (buttonTambahTunai); dobel-klik baris -> langsung lunasi sebesar sisa saldo faktur tanpa
       prompt (buttonLunasCepatTunai). Tombol + mati dan dobel-klik tidak aktif kalau sisa saldo
       <= 0 atau faktur sudah punya pelunasan bukti ini (satu pelunasan per faktur, lihat
       KasController::spTempHutPiut()).
     - Baris pelunasan bukti ini ('L'): ditandai merah (.tunai-baris-lunas), tombol hapus, dan
       dobel-klik = hapus - keduanya lewat konfirmasi (buttonHapusTunai).
   Tombol di kolom Action menghentikan dblclick-nya supaya klik cepat dua kali pada tombol
   tidak ikut memicu aksi dobel-klik barisnya. */
function tunaiRowHtml (item, i, saldo) {
  let aksi = ``
  let kelas = ``

  if (item.Baris == 'L') {
    aksi = `<button class="btn btn-danger btn-tunai-hapus" type="button" onclick="buttonHapusTunai(${i})" ondblclick="event.stopPropagation()" title="Hapus"><i class="bi bi-trash"></i></button>`
    kelas = ` class="tunai-baris-lunas tunai-bisa-dobel" ondblclick="buttonHapusTunai(${i})"`
  } else {
    let bisaLunas = !fakturLunasTunai(item) && sisaSaldoFakturTunai(item) > 0
    aksi = `<button class="btn btn-tunai-lunas" type="button" onclick="buttonTambahTunai(${i})" ondblclick="event.stopPropagation()" title="Pelunasan"${bisaLunas ? '' : ' disabled'}><i class="bi bi-plus-lg"></i></button>`
    if (bisaLunas) {
      kelas = ` class="tunai-bisa-dobel" ondblclick="buttonLunasCepatTunai(${i})"`
    }
  }

  return `
        <tr${kelas} data-idx="${i}">
        <td class="kolom-tunai-action">${aksi}</td>
        <td>${item.NoFaktur}</td>
        <td>${formatDate(item.Tanggal)}</td>
        <td class="text-right">${formatAngka(parseFloat(item.Debet).toFixed(2))}</td>
        <td class="text-right">${formatAngka(parseFloat(item.Kredit).toFixed(2))}</td>
        <td class="text-right">${formatAngka(parseFloat(saldo).toFixed(2))}</td>
        <td>${item.Valas}</td>
        <td class="text-right">${formatAngka(parseFloat(item.Kurs).toFixed(2))}</td>
        </tr>`
}

// Satu faktur = NoFaktur + KodeCustSupp (kunci pengelompokan di KasController::tunaiRows()).
function kunciFakturTunai (item) {
  return item.NoFaktur + '|' + item.KodeCustSupp
}

// Pencarian client-side tabel pelunasan (#searchTunai / #searchPiutang): NoFaktur, Tanggal, dan
// NoBukti. Baris hanya di-hide/show, tidak di-render ulang, supaya index listTunai di
// onclick/ondblclick tetap cocok. Satu faktur tampil utuh (baris faktur + pelunasannya) bila
// salah satu barisnya cocok; faktur yang punya pelunasan bukti ini (merah) selalu tampil.
function filterTunai () {
  let ui = tunaiUI()
  let q = ($(ui.search).val() || '').trim().toLowerCase()
  let fakturTampil = new Set()

  listTunai.forEach((item) => {
    let teks = [item.NoFaktur, formatDate(item.Tanggal), item.NoBukti].join(' ').toLowerCase()
    if (!q || item.Baris == 'L' || teks.includes(q)) {
      fakturTampil.add(kunciFakturTunai(item))
    }
  })

  $('#' + ui.tbody + ' > tr').each(function () {
    let item = listTunai[$(this).data('idx')]
    if (item === undefined) {
      return
    }
    $(this).toggle(fakturTampil.has(kunciFakturTunai(item)))
  })
}

// Sisa saldo satu faktur = jumlah saldoBarisTunai() baris fakturnya + pelunasan bukti ini.
function sisaSaldoFakturTunai (data) {
  let kunci = kunciFakturTunai(data)
  let sisasaldo = 0
  listTunai.forEach((item) => {
    if (kunciFakturTunai(item) == kunci) {
      sisasaldo += saldoBarisTunai(item)
    }
  })
  return Number(sisasaldo.toFixed(2))
}

// Faktur ini sudah punya pelunasan bukti ini (baris merah)?
function fakturLunasTunai (data) {
  let kunci = kunciFakturTunai(data)
  return listTunai.some((item) => item.Baris == 'L' && kunciFakturTunai(item) == kunci)
}

// Dobel-klik baris faktur: langsung lunasi SELURUH sisa saldonya tanpa prompt nominal
// (sama seperti mkKartuLunasCepat() di Memorial Koreksi). Untuk pelunasan sebagian, pakai +.
function buttonLunasCepatTunai (index) {
  let data = listTunai[index]
  if (!data || data.Baris == 'L') {
    return
  }
  if (fakturLunasTunai(data)) {
    alertify.warning('Sudah terdapat pelunasan')
    return
  }

  let sisasaldo = sisaSaldoFakturTunai(data)
  if (sisasaldo <= 0) {
    alertify.warning("Saldo habis")
    return
  }

  simpanTambahTunai(data, sisasaldo)
}

// Tombol hapus / dobel-klik baris pelunasan (merah): konfirmasi dulu, lalu batalkan
// (buttonDeleteTunai).
function buttonHapusTunai (index) {
  let data = listTunai[index]
  if (!data || data.Baris != 'L') {
    return
  }

  if ($("#input_add_nobukti").val() != data.NoBukti) {
    alertify.warning("Nobukti berbeda")
    return
  }

  lepasFokusModalTunai()
  let dlgHapus = alertify.confirm('Hapus Pelunasan',
    'Hapus pelunasan faktur ' + data.NoFaktur + ' sebesar ' +
      formatAngka(nominalLunasTunai(data).toFixed(2)) + '?',
    function () {
      buttonDeleteTunai(index)
    },
    function () {})
  // Sama seperti dialog Hapus Item: 'is-danger' membuat tombol OK merah.
  dlgHapus.elements.root.classList.add('ajs-app-buttons', 'is-danger')
  dlgHapus.set('onclose', pulihkanFokusModalTunai)
}

// Bootstrap 4.0 menarik fokus kembali ke modal yang sedang terbuka (#formTunai/#formPiutang)
// setiap kali fokus pindah ke luar modal, sehingga dialog alertify (dipasang di <body>) tidak
// bisa diketik atau dipakai lewat keyboard. Matikan SEBELUM dialog dibuka, pulihkan lewat
// onclose dialog.
function lepasFokusModalTunai () {
  $(document).off('focusin.bs.modal')
}

function pulihkanFokusModalTunai () {
  let modalId = tunaiUI().modal
  let modalTunai = $(modalId).data('bs.modal')
  if (modalTunai && $(modalId).hasClass('show') && typeof modalTunai._enforceFocus === 'function') {
    modalTunai._enforceFocus()
  }
}

/* "+" pada satu faktur: tanya nominal yang dibayar (default = sisa saldo faktur; ketik angka
   lebih kecil untuk pelunasan sebagian). Jumlah terisi otomatis dari total pelunasan lewat
   renderTunai(). */
function buttonTambahTunai (index) {
  let data = listTunai[index]
  if (!data || data.Baris == 'L') {
    return
  }
  if (fakturLunasTunai(data)) {
    alertify.warning('Sudah terdapat pelunasan')
    return
  }

  let sisasaldo = sisaSaldoFakturTunai(data)

  if (sisasaldo <= 0) {
    alertify.warning("Saldo habis")
    return
  }

  lepasFokusModalTunai()

  let dlgNominal = alertify.prompt('Pelunasan ' + data.NoFaktur,
    'Nominal dibayar (sisa saldo ' + formatAngka(sisasaldo.toFixed(2)) + ')',
    formatAngka(sisasaldo.toFixed(2)),
    function (evt, value) {
      let nominal = Number(unformatAngka(value).toFixed(2))
      if (!(nominal > 0)) {
        alertify.warning('Nominal harus lebih dari 0')
        return false
      }
      if (nominal > sisasaldo) {
        alertify.warning('Nominal melebihi sisa saldo')
        return false
      }
      simpanTambahTunai(data, nominal)
    },
    function () {})
  dlgNominal.elements.root.classList.add('ajs-app-buttons')
  dlgNominal.set('onclose', pulihkanFokusModalTunai)

  let inputNominal = dlgNominal.elements.content.querySelector('input')
  if (inputNominal && !inputNominal.dataset.formatAngka) {
    inputNominal.dataset.formatAngka = '1'
    inputNominal.classList.add('text-right')
    inputNominal.addEventListener('input', function () { formatAngkaKetik(inputNominal) })
  }
}

// Baris faktur sudah dikelompokkan, jadi yang dikirim hanya NoFaktur + KodeCustSupp; kolom
// lain untuk sp_TempHutPiut diambil server dari baris faktur itu (KasController::spTempHutPiut()).
function simpanTambahTunai (data, nominal) {
  let _token = $("#_token").val()

  let choice = 'I'
  let tipetrans = 'L'
  let nobukti = $("#input_add_nobukti").val()
  let urut = 0

  // Kas/bank keluar -> Debet, kas/bank masuk -> Kredit (lihat isTunaiMasuk()).
  let debet = isTunaiMasuk() ? 0 : nominal
  let kredit = isTunaiMasuk() ? nominal : 0

  let noinvoice = 'LNS'

  $.ajax({
      url: KAS_ROUTES.kassptemphutpiut,
      type: "post",
      async: false,
      data: {
        choice,
        _token,
        nofaktur: data.NoFaktur,
        kodecustsupp: data.KodeCustSupp,
        tipetrans,
        nobukti,
        urut,
        kredit,
        debet,
        noinvoice

      },
      success: function(res) {
        console.log(res ,'!')

        if (res == 1) {
          alertify.success('Pelunasan telah ditambah');
          refreshDataTableTunai()

        }
        if (res == 2) {
          alertify.warning('Sudah terdapat pelunasan');
        }
        if (res == 0) {
          alertify.warning('Faktur tidak ditemukan, pilih ulang customer/supplier');
        }

      },
      error: function (err) {
        console.log(err)
        alertify.warning('Terjadi kesalahan silahkan refresh browser')
      }
    })


}


function buttonDeleteTunai (index) {
  console.log("buttonDeleteTunai")
  let _token = $("#_token").val()
  let data = listTunai[index]
  console.log(data)

  let choice = 'D'
  let tipetrans = 'L'
  let nobukti = $("#input_add_nobukti").val()
  let urut = data.Urut

  let kredit = 0
  // Dulu min(sisa Jumlah yang diketik, Kredit); tanpa anggaran Jumlah lagi, kirim Kredit apa
  // adanya (nilai yang sama yang dulu terkirim setiap kali sisa Jumlah masih mencukupi).
  let debet = data.Kredit

  if(nobukti != data.NoBukti) {

    alertify.warning("Nobukti berbeda")
    return
  }

  let noinvoice = 'LNS'

  $.ajax({
      url: KAS_ROUTES.kassptemphutpiut,
      type: "post",
      async: false,
      data: {
        choice,
        _token,
        data,
        tipetrans,
        nobukti,
        urut,
        kredit,
        debet,
        noinvoice

      },
      success: function(res) {
        console.log(res ,'!')

        if (res == 1) {
          alertify.success('Pelunasan telah dihapus');
          refreshDataTableTunai()

        }


      },
      error: function (err) {
        console.log(err)
        alertify.warning('Terjadi kesalahan silahkan refresh browser')
      }
    })


}

/* Pelunasan Hutang / Piutang — saat menambah
   item. Alurnya: pilih Lawan -> daftar supplier/customer (pane Customer di modal #form yang sama)
   -> modal pelunasan: #formPiutang (Pelunasan Piutang) untuk Lawan 113100, selain itu #formTunai
   (Pelunasan Hutang). Jumlah tidak perlu diketik lebih dulu; tombol #buttonBukaTunai di samping
   Jumlah membuka ulang alurnya setelah ditutup. */
// Apakah Lawan yang dipilih masuk alur pelunasan (lihat isLawanTunai di bawah).
function isLawanTunai (trans, perkiraan, islocalorexim) {
  if (trans == 'BKK') {
    return islocalorexim == 1
  }
  if (trans == 'BKM') {
    return perkiraan == '21201' || perkiraan == '113100'
  }
  return false
}

function isModeTunai () {
  let trans = $("#input_add_transaksi").val()
  return (trans == 'BKK' || trans == 'BKM') && xislocalorexim == 1 && tipeformdet == 'add'
}

// Lawan 113100 (Piutang Usaha) memakai modal Pelunasan Piutang.
function isLawanPiutangTunai () {
  return $("#AddAddLawan").val() == '113100'
}

// Elemen modal pelunasan yang sedang dipakai.
function tunaiUI () {
  if (isLawanPiutangTunai()) {
    return {
      modal: '#formPiutang',
      tbody: 'tabel_data_add_list_piutang',
      search: '#searchPiutang',
      nobukti: 'input_piutang_nobukti',
      namacustsupp: 'input_piutang_namacustsupp',
      custsupp: 'input_piutang_custsupp'
    }
  }
  return {
    modal: '#formTunai',
    tbody: 'tabel_data_add_list_tunai',
    search: '#searchTunai',
    nobukti: 'input_tunai_nobukti',
    namacustsupp: 'input_tunai_namacustsupp',
    custsupp: 'input_tunai_custsupp'
  }
}

// Arah pelunasan hanya ditentukan keluar/masuk, untuk hutang maupun piutang:
//   BKK (keluar): pelunasan di Debet, sisa faktur = Kredit - Debet
//   BKM (masuk) : pelunasan di Kredit, sisa faktur = Debet - Kredit
function isTunaiMasuk () {
  return $("#input_add_transaksi").val() == 'BKM'
}

// Nominal pelunasan satu baris sesuai arah (dipakai untuk baris 'L').
function nominalLunasTunai (item) {
  return Number(isTunaiMasuk() ? item.Kredit : item.Debet)
}

// Saldo satu baris menurut arah pelunasan (> 0 = masih bisa dilunasi).
// Saldo yang DITAMPILKAN di kolom Saldo (renderTunai). Sama dengan saldoBarisTunai() kecuali
// BKK + Lawan 113100: ditampilkan Debet - Kredit. Hanya tampilan - sisa faktur untuk tombol +,
// nominal default dan batas nominal tetap memakai saldoBarisTunai().
function saldoTampilTunai (item) {
  if ($("#input_add_transaksi").val() == 'BKK' && isLawanPiutangTunai()) {
    return Number(item.Debet) - Number(item.Kredit)
  }
  return saldoBarisTunai(item)
}

function saldoBarisTunai (item) {
  return isTunaiMasuk() ? Number(item.Debet) - Number(item.Kredit) : Number(item.Kredit) - Number(item.Debet)
}

function aturTombolBukaTunai () {
  $('#buttonBukaTunai').toggle(isModeTunai())
}

// Isi daftar supplier lalu tampilkan pane-nya. Dipanggil dari buttonAddPickLawan() saat
// #form masih terbuka (hanya ganti pane; Batal/x kembali ke pane Lawan) atau dari
// #buttonBukaTunai saat #form tertutup (modalnya dibuka).
function bukaListCustSuppTunai () {
  let _token = $("#_token").val()
  let lawan = $("#AddAddLawan").val();

  $.ajax({
    url: KAS_ROUTES.kaslistcustsupptunai,
    type: "post",
    async: false,
    data: {
      _token,
      lawan
    },
    success: function(res) {
      console.log(res)

      let rowTable = ``
      tunaiNamaCustsupp = {}
      res.forEach((item, i) => {

        tunaiNamaCustsupp[item.KODECUSTSUPP] = item.NAMACUSTSUPP
        rowTable += `
        <tr class="pick-row" onclick="buttonAddPickCustSuppX('${item.KODECUSTSUPP}', '${item.Agent}')">

          <td>${item.KODECUSTSUPP}</td>
          <td>${item.NAMACUSTSUPP}</td>
          <td>${item.ALAMAT}</td>
          <td>${item.NAMAKOTA}</td>
      </tr>
        `
      });

      document.getElementById("tabel_data_add_list_customer").innerHTML = rowTable
      kasInitPicker('tabel_add_list_customer');

      kasBukaPane('modalAddListCustomer');
      if (!$('#form').hasClass('show')) {
        $("#form").modal('show')
      }

    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
    }

  })
}

// Tombol di samping Jumlah: kembali ke daftar faktur supplier yang sudah dipilih, atau ke
// daftar supplier kalau belum ada yang dipilih / datanya sudah habis (mis. item sudah disimpan).
// Daftar faktur dibaca ulang lewat refreshDataTableTunai() yang membaca dbTempHutPiut apa
// adanya — BUKAN lewat buttonAddPickCustSuppX(): listTunai() di controller mengosongkan
// dbTempHutPiut lebih dulu, sehingga pelunasan yang sudah diklik (item belum disimpan) hilang.
function buttonBukaTunai () {
  if (!isModeTunai()) {
    return
  }
  if (flagtunai == 1 && tunaiKodeCustsupp && refreshDataTableTunai(true)) {
    kasBukaFormTunai()
  } else {
    bukaListCustSuppTunai()
  }
}

function buttonAddListPerkiraan () {
  listPerkiraan= []

  console.log('buttonAddListPerkiraan')

  let _token = $("#_token").val();

  $.ajax({
    url: KAS_ROUTES.kaslistkasheader,
    type: "get",
    async: false,
    data: {
      _token,
    },
    success: function(res) {
      console.log(res)
      listPerkiraan = res
      let rowTable = ``
      // Klik baris langsung, bukan tombol "+" — lihat docs/new-cust-supp-modal-guide.md.
      // buttonAddPickPerkiraan() dipanggil persis sama seperti sebelumnya (argumen tak
      // berubah), cuma pemicunya pindah dari onclick tombol ke onclick <tr>.
      res.forEach((item, i) => {
        rowTable += `
        <tr class="pick-row" onclick="buttonAddPickPerkiraan(${i},'${item.Perkiraan}' , '${item.Keterangan}' , '${item.Simbol}' , '${item.Kode}' ,'${item.iscost}', '${item.IsLokalOrExim}' )">
        <td>${item.Perkiraan}</td>
        <td>${item.Keterangan}</td>
        <td>${item.Simbol}</td>
        </tr>`
      });






      // if(!res.length) {
      //   rowTable= `<tr><td class="text-center" colspan=5>Tidak ada data</td></tr>`
      // }
      document.getElementById("tabel_data_add_list_perkiraan").innerHTML = rowTable
      kasInitPicker('tabel_add_list_perkiraan');

      if (res.length) {

        $('.showhidemodalbodyadd').hide();
        kasBukaPane('modalAddListPerkiraan');
        $("#form").modal('toggle')
      } else {
        alertify.warning("Perkiraan tidak ditemukkan")
      }
    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
    }
  })
}


function buttonAddListBon () {
  listBon = []

  console.log('buttonAddListBon')


  let _token = $("#_token").val();
  let kodeperkiraan = $("#input_add_kodeperkiraan").val()

  if(!kodeperkiraan) {
      alertify.warning("Pilih Perkiraan terlebih dahulu")
      return
  }

  $.ajax({
    url: KAS_ROUTES.kaslistbon,
    type: "post",
    async: false,
    data: {
      _token,
      kodeperkiraan
    },
    success: function(res) {
      console.log(res)
      listBon = res
      let rowTable = ``
      // Klik baris langsung, bukan tombol "+" — lihat docs/new-cust-supp-modal-guide.md.
      // buttonAddPickBon() dipanggil persis sama seperti sebelumnya (argumen tak berubah),
      // cuma pemicunya pindah dari onclick tombol ke onclick <tr>.
      res.forEach((item, i) => {
        rowTable += `
        <tr class="pick-row" onclick="buttonAddPickBon(${i},'${item.NoBukti}' , '${item.Debet}'  )">
        <td>${item.NoBukti}</td>
        <td>${item.Penerima}</td>
        <td>${item.Keterangan}</td>
        <td>${item.Perkiraan}</td>
        <td class="text-right">${formatAngka(parseFloat(item.Debet).toFixed(2))}</td>
        </tr>`
      });






      // if(!res.length) {
      //   rowTable= `<tr><td class="text-center" colspan=5>Tidak ada data</td></tr>`
      // }
      document.getElementById("tabel_data_add_list_bon").innerHTML = rowTable
      kasInitPicker('tabel_add_list_bon');
      if (res.length) {

        $('.showhidemodalbodyadd').hide();
        kasBukaPane('modalAddListBon');
        $("#form").modal('toggle')
      } else {
        alertify.warning("Bon tidak ditemukkan")
      }


    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
    }

  })


}


function buttonAddListInvoice () {
  listInvoice = []

  console.log('buttonAddListInvoice')


  let _token = $("#_token").val();
  let kodecustsupp = $("#input_add_kodecustomer").val();

  if (!kodecustsupp  ) {
    alertify.warning("Pilih customer terlebih dahulu")
    return
  }

  $.ajax({
    url: KAS_ROUTES.kreditnotelistinvoice,
    type: "post",
    async: false,
    data: {
      _token,
      kodecustsupp,
    },
    success: function(res) {
      console.log(res)
      listInvoice = res
      let rowTable = ``
      res.forEach((item, i) => {
        rowTable += `
        <tr>
        <td class="text-center"><input class="" type="checkbox" value="" id="add_checkbox${i}"></td>
        <td>${item.NoFaktur}</td>
        <td>${formatDate(item.Tanggal,'/')}</td>
        <td>${formatDate(item.JatuhTempo,'/')}</td>
        <td>${item.KodeVls}</td>
        <td><input id="add_inputQnt${i}" style="height:30px; min-width: 130px" type="number" value='0.00' class="form-control text-right" onBlur="onChangeNilaiKurs(${i})"></td>

        <td><input id="add_inputKurs${i}" style="height:30px; min-width: 90px" type="number" value='1.00' class="form-control text-right" onBlur="onChangeNilaiKurs(${i})"></td>
        <td><input style="height:30px; min-width: 130px" id="add_inputQntRp${i}" type="number" value='0.00' class="form-control text-right"  disabled></td>

        <td class="text-right">${formatAngka(parseFloat(item.SaldoD).toFixed(2))}</td>
        <td class="text-right">${formatAngka(parseFloat(item.Saldo).toFixed(2))}</td>

        <td><input style="height:30px; min-width: 200px" id="add_inputKeterangan${i}" type="text" value='' class="form-control text-left" ></td>


        </tr>`
      });






      // if(!res.length) {
      //   rowTable= `<tr><td class="text-center" colspan=5>Tidak ada data</td></tr>`
      // }
      document.getElementById("tabel_data_add_list_invoice").innerHTML = rowTable

      if (res.length) {

        $('.showhidemodalbodyadd').hide();
        kasBukaPane('modalAddListInvoice');
        $("#form").modal('toggle')
      } else {
        alertify.warning("Tidak ada invoice untuk ditambah")
      }


    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
    }

  })


}



function buttonAddListNoBeli () {


  let _token = $("#_token").val();
  let kodecustsupp = $("#input_add_kodecustomer").val();
  let noinvoice = $("#input_add_noinvoice").val();
  let noso = $("#input_add_noso").val();
  let kodebrg = $("#AddAddKodeBrg").val();

  if (!kodebrg ) {
    alertify.warning("Pilih barang terlebih dahulu")
    return
  }

  $('#tabel_add_list_nobeli').DataTable().destroy();
  $.ajax({
    url: KAS_ROUTES.perintahreturjuallistnobeli,
    type: "post",
    async: false,
    data: {
      _token,
      kodebrg,
      noso
    },
    success: function(res) {
      let rowTable = ``
      rowTable += `<tr>
      <td>-</td>
      <td class="text-center"><button class="btn btn-primary btn-sm" onclick="buttonAddPickNoBeli('-' , 0)" type="button" ><i class="bi bi-plus"></i></button></td>

      </tr>`
      res.forEach((item, i) => {
        rowTable += `
        <tr>
        <td>${item.NOBUKTI}</td>
        <td class="text-center"><button class="btn btn-primary btn-sm" onclick="buttonAddPickNoBeli('${item.NOBUKTI}' ,${item.urut} )" type="button" ><i class="bi bi-plus"></i></button></td>

        </tr>`
      });





      // if(!res.length) {
      //   rowTable= `<tr><td class="text-center" colspan=5>Tidak ada data</td></tr>`
      // }
      document.getElementById("tabel_data_add_list_nobeli").innerHTML = rowTable
      $("#tabel_add_list_nobeli").DataTable({
        "lengthChange": false,
          "paging": false ,
    });
      $('.showhidemodalbodyadd').hide();
      kasBukaPane('modalAddListNoBeli');
      $("#form").modal('toggle')

    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
    }

  })


}

function onChangeNilaiKursItem () {
  console.log('onChangeNilaiKursItem' )
  let onChangeQnt = $(`#AddEditNilai`).val();
  let onChangeKurs = $(`#AddEditKurs`).val();

  document.getElementById(`AddEditNilaiRp`).value = parseFloat(Number(onChangeQnt) * Number(onChangeKurs)).toFixed(2)

}

function onChangeNilaiKurs (index) {
  console.log('onChangeNilaiKurs' , index)
  let onChangeQnt = $(`#add_inputQnt${index}`).val();
  let onChangeKurs = $(`#add_inputKurs${index}`).val();

  document.getElementById(`add_inputQntRp${index}`).value = parseFloat(Number(onChangeQnt) * Number(onChangeKurs)).toFixed(2)

}

function buttonAddListNoInvoice () {


  let _token = $("#_token").val();
  let kodecustsupp = $("#input_add_kodecustomer").val();

  if (!kodecustsupp) {
    alertify.warning("Pilih customer terlebih dahulu")
    return
  }

  $('#tabel_add_list_noinvoice').DataTable().destroy();
  $.ajax({
    url: KAS_ROUTES.perintahreturjuallistnoinvoice,
    type: "post",
    async: false,
    data: {
      _token,
      kodecustsupp
    },
    success: function(res) {
      let rowTable = ``
      res.forEach((item, i) => {
        rowTable += `
        <tr>
        <td>${item.NOBUKTI}</td>
        <td>${item.TANGGAL}</td>
        <td>${item.NoSO}</td>
        <td>${item.NAMAGDG}</td>
        <td class="text-center"><button class="btn btn-primary btn-sm" onclick="buttonAddPickNoInvoice('${item.NOBUKTI}' , '${item.NoSO}' , '${item.KODEGDG}', ${item.flagtipe}, ${item.ppn})" type="button" ><i class="bi bi-plus"></i></button></td>

        </tr>`
      });




      // if(!res.length) {
      //   rowTable= `<tr><td class="text-center" colspan=5>Tidak ada data</td></tr>`
      // }
      document.getElementById("tabel_data_add_list_noinvoice").innerHTML = rowTable
      $("#tabel_add_list_noinvoice").DataTable({
        "lengthChange": false,
          "paging": false ,
    });
      $('.showhidemodalbodyadd').hide();
      kasBukaPane('modalAddListNoInvoice');
      $("#form").modal('toggle')

    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
    }

  })


}

function buttonAddListCustsupp () {
  console.log('buttonAddListCustsupp')
  $.ajax({
    url: KAS_ROUTES.kaslistcustsupp,
    type: "get",
    async: false,
    data: {

    },
    success: function(res) {
      console.log(res)
      let rowTable = ``
      res.forEach((item, i) => {
        rowTable += `
        <tr class="pick-row" onclick="buttonAddPickCustsupp('${item.kodecustsupp}' , '${item.namacustsupp}' , '${item.alamat1}')">
        <td>${item.kodecustsupp}</td>
        <td>${item.namacustsupp}</td>
        <td>${item.alamat1}</td>
        </tr>`
      });




      if(!res.length) {
        rowTable= `<tr><td class="text-center" colspan=4>Tidak ada data</td></tr>`
      }
      document.getElementById("tabel_data_add_list_custsupp").innerHTML = rowTable
      kasInitPicker('tabel_add_list_custsupp');
      $('.showhidemodalbodyadd').hide();
      kasBukaPane('modalAddListCustsupp');
      $("#form").modal('toggle')

    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
    }

  })


}

/* No Titipan — hanya BKK dengan Lawan 113400 (titipan customer). Picker bisa dibuka 3 cara:
   langsung saat Lawan 113400 dipilih (buttonAddPickLawan), Enter di input No Titipan
   (onKeyPressNoTitipan), atau tombol cari (buttonAddListTitipan). Baris yang dipilih mengisi
   No Titipan, Jumlah (= JumlahRp) dan Custsupp; NoTitipan/UrutTitipan ikut disimpan sebagai
   notitipan/uruttitipan supaya Sisa titipan tsb berkurang. Data: KasController::getNoTitipan(). */
let listTitipanKas = []

function muatTitipan () {
  let rows = null
  $.ajax({
    url: KAS_ROUTES.kasnotitipan,
    type: "post",
    async: false,
    data: {
      _token: $("#_token").val()
    },
    success: function(res) {
      rows = res
    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
    }
  })
  return rows
}

// cari: teks awal kotak Search DataTables ('' = tampilkan semua). Kalau modal #form masih
// terbuka (lanjut dari picker Lawan) hanya ganti pane, sama seperti bukaListCustSuppTunai().
function bukaListTitipan (rows, cari) {
  if (rows === null) {
    return
  }
  if (!rows.length) {
    alertify.warning("No Titipan tidak ditemukkan")
    if ($('#form').hasClass('show')) {
      buttonAddListBatal()
    }
    return
  }

  listTitipanKas = rows
  let rowTable = ``
  rows.forEach((item, i) => {
    rowTable += `
        <tr class="pick-row" onclick="buttonAddPickTitipan(${i})">
        <td>${item.NOBUKTI}</td>
        <td>${formatDate(item.TANGGAL)}</td>
        <td>${item.namaCustSupp}</td>
        <td>${item.Keterangan}</td>
        <td class="text-right">${formatAngka(parseFloat(item.JumlahRp).toFixed(2))}</td>
        <td class="text-right">${formatAngka(parseFloat(item.Sisa).toFixed(2))}</td>
        </tr>`
  });

  document.getElementById("tabel_data_add_list_titipan").innerHTML = rowTable
  kasInitPicker('tabel_add_list_titipan');
  if (cari) {
    $('#tabel_add_list_titipan').DataTable().search(cari).draw()
  }

  kasBukaPane('modalAddListTitipan');
  if (!$('#form').hasClass('show')) {
    $("#form").modal('show')
  }
}

function buttonAddListTitipan () {
  bukaListTitipan(muatTitipan(), '')
}

// Enter: kosong -> daftar lengkap; NoBukti persis cocok -> langsung dipilih; selain itu ->
// daftar disaring dengan teks yang diketik (sama seperti resolveBarang() di permintaanpemakaian).
function onKeyPressNoTitipan (e) {
  if (e.which !== 13) {
    return
  }
  e.preventDefault()

  let cari = ($("#AddAddNoTitipan").val() || '').trim()
  let rows = muatTitipan()
  if (rows === null) {
    return
  }

  if (cari) {
    let idx = rows.findIndex(item => String(item.NOBUKTI || '').trim().toLowerCase() === cari.toLowerCase())
    if (idx !== -1) {
      listTitipanKas = rows
      buttonAddPickTitipan(idx)
      return
    }
  }

  bukaListTitipan(rows, cari)
}

function buttonAddPickTitipan (index) {
  let item = listTitipanKas[index]
  if (!item) {
    return
  }

  document.getElementById("AddAddNoTitipan").value = item.NOBUKTI
  document.getElementById("AddAddUrutTitipan").value = item.URUT
  document.getElementById("AddAddJumlah").value = formatAngka(parseFloat(item.Sisa).toFixed(2))
  document.getElementById("AddAddKodeCustsupp").value = item.KodeCustSupp
  document.getElementById("AddAddNamaCustsupp").value = item.namaCustSupp

  if ($('#form').hasClass('show')) {
    buttonAddListBatal()
  }
}

function resetTitipan () {
  document.getElementById("AddAddNoTitipan").value = ''
  document.getElementById("AddAddUrutTitipan").value = ''
  document.getElementById("AddAddNoTitipan").disabled = false
  document.getElementById("buttonAddListTitipan").disabled = false
  $('#rowNoTitipan').hide();
}



function buttonAddPickAktiva (index) {

  tipemodalaktiva = 1

  document.getElementById("input_aktiva_keterangan").value = ''
  document.getElementById("input_aktiva_keterangan").disabled = true

  console.log(listAktiva[index])
  xaktiva = listAktiva[index]
  document.getElementById("input_aktiva_noaktiva").value = xaktiva.Perkiraan
  document.getElementById("input_aktiva_groupaktiva").value = xaktiva.NoMuka
  document.getElementById("input_aktiva_namagroupaktiva").value = xaktiva.NamaGroupAktiva
  // document.getElementById("input_aktiva_noaktiva").value = xaktiva.Perkiraan
  document.getElementById("input_aktiva_devisi").value = xaktiva.Devisi
  document.getElementById("input_aktiva_namadevisi").value = xaktiva.NamaDevisi

  document.getElementById("input_aktiva_keterangan").value = xaktiva.Keterangan
  document.getElementById("input_aktiva_tipeaktiva").value = Number(xaktiva.TipeAktiva)
  document.getElementById("input_aktiva_metodepenyusutan").value = xaktiva.Tipe

  document.getElementById("input_aktiva_kuantum").value = Number(xaktiva.Quantity) ? parseFloat(xaktiva.Quantity).toFixed(2) : '0.00'
  document.getElementById("input_aktiva_susut").value = Number(xaktiva.Persen) ? parseFloat(xaktiva.Persen).toFixed(2) : '0.00'
  document.getElementById("input_aktiva_akumulasi").value = xaktiva.Akumulasi

  document.getElementById("input_aktiva_biaya1").value = xaktiva.Biaya
  document.getElementById("input_aktiva_persen1").value = Number(xaktiva.PersenBiaya1) ? parseFloat(xaktiva.PersenBiaya1).toFixed(2) : '0.00'

  document.getElementById("input_aktiva_biaya2").value = xaktiva.Biaya2
  document.getElementById("input_aktiva_persen2").value = Number(xaktiva.PersenBiaya2) ? parseFloat(xaktiva.PersenBiaya2).toFixed(2) : '0.00'

  document.getElementById("input_aktiva_biaya3").value = xaktiva.biaya3
  document.getElementById("input_aktiva_persen3").value = Number(xaktiva.persenbiaya3) ? parseFloat(xaktiva.persenbiaya3).toFixed(2) : '0.00'
  document.getElementById("input_aktiva_tglperolehan").value = formatDate(new Date())
  document.getElementById("input_aktiva_tglpemakaian").value = xaktiva.Tanggal ? formatDate(xaktiva.Tanggal) : ''


  $('.showhidemodalbodyadd').hide();
  kasBukaPane('modalAddListAktivaDetail');



}

/* Devisi & Valas di form item kini dropdown (lihat kas/_form.blade.php), bukan modal picker.
   Opsinya dimuat sekali saat halaman dibuka dari endpoint yang sama dengan picker lamanya
   (kaslistdevisi / kaslistvalas); nilai yang sedang terpilih dipertahankan. Opsi dibuat lewat
   DOM (bukan string HTML) supaya nama berisi tanda kutip tidak merusak atribut.
   buttonAddListDevisi()/buttonAddListValas() + pane-nya di _pickers.blade.php tidak dipakai lagi. */
function kasMuatDevisiValas () {
  let _token = $("#_token").val();

  $.ajax({
    url: KAS_ROUTES.kaslistdevisi,
    type: "get",
    data: {
      _token,
    },
    success: function(res) {
      listDevisi = res
      let sel = document.getElementById("AddAddKodeDevisi")
      let nilai = sel.value
      sel.innerHTML = ''
      res.forEach((item) => {
        let opsi = document.createElement('option')
        opsi.value = item.Devisi
        opsi.textContent = item.Devisi + ' - ' + item.NamaDevisi
        opsi.dataset.nama = item.NamaDevisi
        sel.appendChild(opsi)
      });
      kasPilihOpsi('AddAddKodeDevisi', nilai)
    },
    error: function (err) {
      console.log(err)
      alertify.warning('Daftar Devisi gagal dimuat, silahkan refresh browser')
    }
  })

  $.ajax({
    url: KAS_ROUTES.kaslistvalas,
    type: "get",
    data: {
      _token,
    },
    success: function(res) {
      listValas = res
      let sel = document.getElementById("AddAddValas")
      let nilai = sel.value
      sel.innerHTML = ''
      res.forEach((item) => {
        let kurs = Number(item.KURS) || 0
        let opsi = document.createElement('option')
        opsi.value = item.KODEVLS
        opsi.textContent = item.KODEVLS + ' - ' + item.NAMAVLS
        opsi.dataset.kurs = kurs
        sel.appendChild(opsi)
      });
      kasPilihOpsi('AddAddValas', nilai)
    },
    error: function (err) {
      console.log(err)
      alertify.warning('Daftar Valas gagal dimuat, silahkan refresh browser')
    }
  })
}

// Pilih nilai di dropdown Devisi/Valas. Kalau nilainya tidak ada di daftar (mis. data lama
// saat edit), opsinya ditambahkan dulu supaya tetap tampil dan tetap terkirim apa adanya.
function kasPilihOpsi (idSelect, nilai, label) {
  let sel = document.getElementById(idSelect)
  if (!sel) {
    return
  }
  nilai = nilai == null ? '' : String(nilai)
  let ada = Array.prototype.some.call(sel.options, (o) => o.value === nilai)
  if (!ada && nilai !== '') {
    let opsi = document.createElement('option')
    opsi.value = nilai
    opsi.textContent = label || nilai
    sel.appendChild(opsi)
  }
  sel.value = nilai
}

function onChangeDevisi () {
  let opsi = document.getElementById("AddAddKodeDevisi").selectedOptions[0]
  document.getElementById("AddAddNamaDevisi").value = opsi ? (opsi.dataset.nama || '') : ''

  // Sama seperti buttonAddPickDevisi() lama di halaman ini: ganti Devisi mengosongkan Lawan.
  document.getElementById("AddAddKodeLawan").value = ''
  document.getElementById("AddAddLawan").value = ''
  document.getElementById("AddAddKeteranganLawan").value = ''
}

// Efek sama seperti buttonAddPickValas() lama, minus menutup modal.
function onChangeValas () {
  let opsi = document.getElementById("AddAddValas").selectedOptions[0]
  let kurs = opsi ? (Number(opsi.dataset.kurs) || 0) : 0

  $('#rowCustsupp').hide();
  document.getElementById("AddAddKurs").value = kurs.toFixed(2)

  tempDPPDPH = {}

  document.getElementById("AddAddLawan").value = ''
  document.getElementById("AddAddKodeLawan").value = ''
  document.getElementById("AddAddKeteranganLawan").value = ''
}

function buttonAddPickDevisi (index, kode, nama) {
  console.log('buttonAddPickDevisi')
  document.getElementById("AddAddKodeDevisi").value = kode
  document.getElementById("AddAddNamaDevisi").value = nama


  document.getElementById("AddAddKodeLawan").value = ''
  document.getElementById("AddAddLawan").value = ''
  document.getElementById("AddAddKeteranganLawan").value = ''
  // document.getElementById("input_add_nobukti").value = simbol

  // $('.showhideitem').hide();
  // setNewNoBukti(simbol)
  buttonAddListBatal()
  // $("#form").modal('toggle')
}

function buttonAddPickCustsupp (kode, nama) {
  console.log('buttonAddPickCustsupp')
  document.getElementById("AddAddKodeCustsupp").value = kode
  document.getElementById("AddAddNamaCustsupp").value = nama
  // document.getElementById("input_add_nobukti").value = simbol

  // $('.showhideitem').hide();
  // setNewNoBukti(simbol)
  buttonAddListBatal()
  // $("#form").modal('toggle')
}

function buttonAddPickValas (index, kode, nama , kurs) {
  console.log('buttonAddPickValas')




  $('#rowCustsupp').hide();

  document.getElementById("AddAddValas").value = kode
  document.getElementById("AddAddKurs").value = parseFloat(kurs).toFixed(2)

  tempDPPDPH = {}

  document.getElementById("AddAddLawan").value = ''
  document.getElementById("AddAddKodeLawan").value = ''
  document.getElementById("AddAddKeteranganLawan").value = ''
  // document.getElementById("input_add_nobukti").value = simbol

  // $('.showhideitem').hide();
  // setNewNoBukti(simbol)
  buttonAddListBatal()
  // $("#form").modal('toggle')
}


function buttonAddPickPerkiraan (index, perkiraan, keterangan , simbol) {
  console.log('buttonAddPickPerkiraan')
  document.getElementById("input_add_kodeperkiraan").value = perkiraan
  document.getElementById("input_add_keteranganperkiraan").value = keterangan
  document.getElementById("input_add_simbol").value = simbol
  // document.getElementById("input_add_nobukti").value = simbol
  document.getElementById("input_add_bon").value = ''
  setNum('input_add_nilaibon', '0.00')
  $('.showhideitem').hide();
  setNewNoBukti(simbol)
  buttonAddListBatal()
  // $("#form").modal('toggle')
}

function buttonAddPickBon (index, nobon, nilaibon) {
  console.log('buttonAddPickBon')
  document.getElementById("input_add_bon").value = nobon
  setNum('input_add_nilaibon', parseFloat(nilaibon).toFixed(2))
  $('.showhideitem').hide();
  buttonAddListBatal()
  // $("#form").modal('toggle')
}

function buttonAddPickLawan (index, perkiraan, keterangan , simbol, kode, iscost, islocalorexim) {
  let trans =  $("#input_add_transaksi").val();
  console.log(trans)
  // BKK: pelunasan hanya bila IsLokalOrExim = 1. BKM: flag diabaikan - hanya Lawan 21201 dan 113100
  // yang selalu masuk pelunasan (dianggap 1 walau flagnya 0); Lawan lain jalur biasa. Nilai ini
  // juga yang dikirim ke spAdd dan menentukan cabang simpan, jadi dinormalkan di sini.
  let tunaiLawan = isLawanTunai(trans, perkiraan, islocalorexim)
  xislocalorexim = trans == 'BKM' ? (tunaiLawan ? 1 : 0) : islocalorexim
  // Jumlah read-only selama Lawan hutang 21201 / piutang 113100 memakai alur pelunasan: nilainya
  // diisi dari total pelunasan, bukan diketik. Lawan lain membukanya lagi.
  document.getElementById("AddAddJumlah").readOnly = tunaiLawan && (perkiraan == '21201' || perkiraan == '113100')
  xlawan = listLawan[index]
  aturTombolBukaTunai()

  document.getElementById("AddAddJumlah").value = '0.00'
  document.getElementById("AddAddKodeCosting").value = ''
  document.getElementById("AddAddNamaCosting").value = ''
  document.getElementById("AddAddNamaSubCosting").value = ''
  document.getElementById("AddAddKodeSubCosting").value = ''
  // No Titipan hanya untuk BKK + 113400 (cabang di bawah); Lawan lain membersihkannya.
  resetTitipan()


  if (tunaiLawan) {

    // Cabang UHT BKM di bawah mematikan Jumlah; hidupkan lagi kalau Lawan diganti ke sini.
    document.getElementById("AddAddJumlah").disabled = false
    flagtunai = 0
    listtunai = []
    tunaiKodeCustsupp = ''
    console.log(index, perkiraan, keterangan , simbol, kode)
    document.getElementById("AddAddLawan").value = perkiraan
    document.getElementById("AddAddKodeLawan").value = kode
    document.getElementById("AddAddKeteranganLawan").value = keterangan
    if (isModeTunai()) {
      // Langsung ke daftar supplier (ganti pane di modal yang sama) — Jumlah tidak perlu
      // diketik dulu. Lihat bukaListCustSuppTunai().
      bukaListCustSuppTunai()
    } else {
      buttonAddListBatal()
    }
    return

  }


  if (kode == 'UHT' && trans == 'BKM') {

    modalDPHUHTBKM(listLawan[index])

    document.getElementById("AddAddJumlah").disabled = true
    document.getElementById("AddAddJumlah").value = '0.00'



    return
  }

  document.getElementById("AddAddJumlah").disabled = false
  document.getElementById("AddAddJumlah").value = '0.00'




  if ( kode == 'HT' ) {
    modalDPH(listLawan[index])

    return
  }



  if (kode == 'UHT' && trans == 'BKK') {
    modalDPHUHT(listLawan[index])

    return
  }

  if (kode == 'AKV' ) {
    modalAktiva(listLawan[index])

    return
  }
  if (kode == 'AKM' ) {
    modalAktiva(listLawan[index])

    return
  }

  if (perkiraan == '113400') {

    document.getElementById("AddAddKodeCustsupp").value = ''
    document.getElementById("AddAddNamaCustsupp").value = ''
    document.getElementById("AddAddLawan").value = perkiraan
    document.getElementById("AddAddKodeLawan").value = kode
    document.getElementById("AddAddKeteranganLawan").value = keterangan

    $('#rowCustsupp').show();

    if (trans == 'BKK') {
      // Langsung ke daftar No Titipan (ganti pane di modal yang sama), sama seperti Hutang
      // Usaha langsung ke daftar supplier. Lihat bukaListTitipan().
      $('#rowNoTitipan').show();
      buttonAddListTitipan()
    } else {
      buttonAddListBatal()
    }

    return
  }

  $('#rowCustsupp').hide();

  $('#rowCosting').hide();
  $('#rowSubCosting').hide();


  if(Number(iscost) > 0 ) {
    $('#rowCosting').show();
    $('#rowSubCosting').show();
  }


  if (kode == 'PT' ) {
    modalDPP(listLawan[index])

    return
  }
  console.log('buttonAddPickLawan')
  console.log(index, perkiraan, keterangan , simbol, kode)
  document.getElementById("AddAddLawan").value = perkiraan
  document.getElementById("AddAddKodeLawan").value = kode
  document.getElementById("AddAddKeteranganLawan").value = keterangan

  // $('.showhideitem').hide();
  buttonAddListBatal()
  // $("#form").modal('toggle')
}



function buttonAddPickDepartemen (index, kode, nama) {


  console.log('buttonAddPickDepartemen')
  document.getElementById("AddAddNamaDepartemen").value = nama
  document.getElementById("AddAddKodeDepartemen").value = kode

  // $('.showhideitem').hide();
  buttonAddListBatal()
  // $("#form").modal('toggle')
}

function buttonAddPickCustomer (kode, nama , alamat) {
  console.log('buttonAddPickCustomer')
  console.log(kode,nama,alamat)
  document.getElementById("AddAddKodeCustsupp").value = kode
  document.getElementById("AddAddNamaCustsupp").value = nama

  $('.showhideitem').hide();
  buttonAddListBatal()
  // $("#form").modal('toggle')
}
function onChangeQtyEdit () {

  console.log('onChangeQtyEdit')
  console.log('tempBarangAddEdit' , tempBarangAddEdit)

  let qty = $("#AddEditInputQty").val();
  let nosat = $("#AddEditInputNosat").val();
  if (jQuery.isEmptyObject(tempBarangAddEdit)) {
    console.log('g ada barang')
  } else {

    console.log('ada barang')
    let tempIsi = nosat == 1 ? tempBarangAddEdit.ISI1 : tempBarangAddEdit.ISI2
    console.log(tempIsi)
    let tempTotalQty = Number(tempIsi) * Number(qty)

    document.getElementById("AddEditInputQty1").value = tempTotalQty / tempBarangAddEdit.ISI1
    document.getElementById("AddEditInputQty2").value = tempTotalQty / tempBarangAddEdit.ISI2


  }
}

function onChangeQty () {

  console.log('onChangeQty')
  console.log('tempBarangAddAdd' , tempBarangAddAdd)
  let qty = $("#AddAddInputQty").val();
  let nosat = $("#AddAddInputNosat").val();
  console.log('qty' , qty)
  console.log('nosat' , nosat)

  if (jQuery.isEmptyObject(tempBarangAddAdd)) {
    console.log('g ada barang')
  } else {

    console.log('ada barang')
    let tempIsi = nosat == 1 ? tempBarangAddAdd.Isi1 : tempBarangAddAdd.Isi2
    console.log(tempIsi)
    let tempTotalQty = Number(tempIsi) * Number(qty)

    document.getElementById("AddAddInputQty1").value = tempTotalQty / tempBarangAddAdd.Isi1
    document.getElementById("AddAddInputQty2").value = tempTotalQty / tempBarangAddAdd.Isi2


  }

}

function buttonAddPickBarang (index) {
  console.log('buttonAddPickBarang')
  tempBarangAddAdd = listBarang[index]

  console.log('tempBarangAddAdd', tempBarangAddAdd)
  document.getElementById("AddAddKodeBrg").value = tempBarangAddAdd.KodeBrg
  document.getElementById("AddAddNamaBrg").value = tempBarangAddAdd.NamaBrg ? tempBarangAddAdd.NamaBrg : tempBarangAddAdd.NamaBrgx
  document.getElementById("AddAddInputQty").value = tempBarangAddAdd.QntSisa
  document.getElementById("AddAddInputQty1").value = tempBarangAddAdd.Qnt1Sisa
  document.getElementById("AddAddInputQty2").value = tempBarangAddAdd.Qnt2Sisa

  document.getElementById("AddAddInputSat1").value = tempBarangAddAdd.SAT1
  document.getElementById("AddAddInputSat2").value = tempBarangAddAdd.SAT2

  let selectOption = ''
  if (tempBarangAddAdd.SAT1) {
    selectOption += `<option value=1 ${tempBarangAddAdd.NoSat == 1 ? 'selected' : ''}>SAT1 - ${tempBarangAddAdd.SAT1}</option>`
  }
  if (tempBarangAddAdd.SAT2) {
    selectOption += `<option value=2 ${tempBarangAddAdd.NoSat == 2 ? 'selected' : ''}>SAT2 - ${tempBarangAddAdd.SAT2}</option>`
  }
  document.getElementById("AddAddInputNosat").innerHTML = selectOption






  buttonAddListBatal()
  // $("#form").modal('toggle')
}



function buttonAddPickNoInvoice (nobukti, noso , kodegdg, flagtipe, ppn) {
  console.log('buttonAddPickNoInvoice')
  document.getElementById("input_add_noinvoice").value = nobukti
  document.getElementById("input_add_noso").value = noso
  document.getElementById("input_add_gudang").value = kodegdg
  document.getElementById("input_add_flagtipe").value = flagtipe
  document.getElementById("input_add_ppn").value = ppn
  $('.showhideitem').hide();
  buttonAddListBatal()
  // $("#form").modal('toggle')
}


function buttonAddBatal () {

  $('.showhideitem').hide();
}

function buttonAddListBatal () {
  $('.showhidemodalbodyadd').hide();
  // $('#modalBodyAddMain').show();

  $("#form").modal('toggle')
}

// function buttonAddListCustomer () {
//
//   $('.showhidemodalbodyadd').hide();
//   $('#modalBodyAddListValas').show();
//
//   $("#form").modal('toggle')
// }


function closeShowHideItem () {
  $('.showhideitem').hide();

}

function unlockFormAdd () {
  document.getElementById("input_add_catatan").disabled = false
  document.getElementById("input_add_tanggal").disabled = false


  document.getElementById("buttonAddListCustomer").disabled = false
  document.getElementById("buttonAddListNoInvoice").disabled = false
  document.getElementById("checkBoxKembaliUang").disabled = true
}

function lockFormAdd () {
  document.getElementById("input_add_tanggal").disabled = true
  document.getElementById("buttonAddListBon").disabled = true
  document.getElementById("input_add_kepadaterima").disabled = true
  document.getElementById("buttonAddListPerkiraan").disabled = true
  document.getElementById("input_add_transaksi").disabled = true

  document.getElementById("checkBoxKembaliUang").disabled = false
}

function lockFormAddAdd () {
  document.getElementById("buttonAddListDepartemen").disabled = true
  document.getElementById("AddAddLawan").disabled = true
  document.getElementById("buttonAddListLawan").disabled = true
  document.getElementById("AddAddValas").disabled = true
  document.getElementById("AddAddKodeDevisi").disabled = true

}

function unlockFormAddAdd () {
  document.getElementById("buttonAddListDepartemen").disabled = false
  document.getElementById("AddAddLawan").disabled = false
  document.getElementById("buttonAddListLawan").disabled = false
  document.getElementById("AddAddValas").disabled = false
  document.getElementById("AddAddKodeDevisi").disabled = false
}





function refreshDataTable (nobukti) {
  console.log('refreshDataTable' , nobukti)
  let _token = $("#_token").val();
  $.ajax({
    url: KAS_ROUTES.kasspdetail,
    type: "post",
    async: false,
    data: {
      _token,
      nobukti

    },
    success: function(res) {
      console.log(res)
      listData = res
      // console.log(res)
      if (!res.length) {
          alertify.success('Data Habis')
          // $("#form").modal('toggle')
          $('#page2').hide();
          $('#page1').show();
          return
      }
      // dataTableAdd = res

      let rowTable = ``
      listData.forEach((item, i) => {

        // <td>${item.TipeTrans == 'BKK' ? item.Lawan : item.Perkiraan}</td>
        // <td>${item.TipeTrans == 'BKK' ? item.NamaLawan : item.NamaPerkiraan}</td>
        // <td>${item.TipeTrans == 'BKK' ? item.Perkiraan : item.Lawan }</td>
        // <td>${item.TipeTrans == 'BKK' ?  item.NamaPerkiraan : item.NamaLawan }</td>

              rowTable += `
                <tr>
                  <td>${item.Devisi}</td>

                  <td>${item.Perkiraan}</td>
                  <td>${item.NamaPerkiraan}</td>
                  <td>${item.Lawan }</td>
                  <td>${item.NamaLawan }</td>




                  <td class="text-right">${item.DebetRp ?  formatAngka(parseFloat(item.DebetRp).toFixed(2)) : '0.00'}</td>
                  <td>${item.Keterangan }</td>
                  <td>${item.NamaCost ? item.NamaCost : '' }</td>
                  <td>${item.NamaSubCost ? item.NamaSubCost : ''}</td>
                  <td class="text-center">
                    <button class="btn btn-action-sm btn-action-success" type="button" onclick="buttonAddEditItem(${i})"><i class="bi bi-pen"></i></button>
                    <button class="btn btn-action-sm btn-action-danger" type="button" onclick="buttonAddDelete(${i}  )"><i class="bi bi-trash"></i></button>
                  </td>
                </tr>

              `
      });

      document.getElementById("addTableData").innerHTML = rowTable


        document.getElementById("input_add_transaksi").value = listData[0].TipeTransHD
        document.getElementById("input_add_kodeperkiraan").value = listData[0].PerkiraanHd
        document.getElementById("input_add_keteranganperkiraan").value = listData[0].NamaPerkiraanHd
        document.getElementById("input_add_kepadaterima").value = listData[0].Note
        document.getElementById("input_add_nobukti").value = listData[0].NoBukti
        document.getElementById("input_add_bon").value = listData[0].NobonS
        setNum('input_add_nilaibon', listData[0].nilaibon ? parseFloat(listData[0].nilaibon).toFixed(2) : '0.00')
        if (listData[0].BonKembaliUang == 'Y') {
          document.getElementById("checkBoxKembaliUang").checked = true


        } else {
          document.getElementById("checkBoxKembaliUang").checked = false

        }
        if (Number(listData[0].nilaibon) <= 0 && listData[0].BonKembaliUang == 'T') {
          document.getElementById("checkBoxKembaliUang").disabled = true

        }

        // document.getElementById("input_add_transaksi").value = listData[0].NamaCustSupp
        // document.getElementById("input_add_alamatcustomer").value = listData[0].Alamat1
        // document.getElementById("input_add_nobukti").value = listData[0].NoBukti
        console.log(listData[0].Tanggal)
        console.log(new Date(listData[0].Tanggal))
        document.getElementById("input_add_tanggal").value = formatDate(listData[0].Tanggal)










    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
      resRefresh = 0;
    }

  })
}


function refreshDataTableDetail (nobukti) {
  console.log('refreshDataDetail' , nobukti)
  let _token = $("#_token").val();
  $.ajax({
    url: KAS_ROUTES.kasspdetail,
    type: "post",
    async: false,
    data: {
      _token,
      nobukti

    },
    success: function(res) {
      console.log(res)
      listData = res
      // console.log(res)
      if (!res.length) {
          alertify.success('Data Habis')
          // $("#form").modal('toggle')
          $('#page3').hide();
          // $('#page3').hide();
          $('#page1').show();
          return
      }
      // dataTableAdd = res

      let rowTable = ``
      listData.forEach((item, i) => {

        // <td>${item.TipeTrans == 'BKK' ? item.Lawan : item.Perkiraan}</td>
        // <td>${item.TipeTrans == 'BKK' ? item.NamaLawan : item.NamaPerkiraan}</td>
        // <td>${item.TipeTrans == 'BKK' ? item.Perkiraan : item.Lawan }</td>
        // <td>${item.TipeTrans == 'BKK' ?  item.NamaPerkiraan : item.NamaLawan }</td>

              rowTable += `
                <tr>
                  <td>${item.Devisi}</td>

                  <td>${item.Perkiraan}</td>
                  <td>${item.NamaPerkiraan}</td>
                  <td>${item.Lawan }</td>
                  <td>${item.NamaLawan }</td>




                  <td class="text-right">${item.DebetRp ?  formatAngka(parseFloat(item.DebetRp).toFixed(2)) : '0.00'}</td>
                  <td>${item.Keterangan }</td>
                  <td>${item.NamaCost ? item.NamaCost : '' }</td>
                  <td>${item.NamaSubCost ? item.NamaSubCost : ''}</td>

                </tr>

              `
      });

      document.getElementById("detailTableData").innerHTML = rowTable


        document.getElementById("input_detail_transaksi").value = listData[0].TipeTransHD
        document.getElementById("input_detail_kodeperkiraan").value = listData[0].PerkiraanHd
        document.getElementById("input_detail_keteranganperkiraan").value = listData[0].NamaPerkiraanHd
        document.getElementById("input_detail_kepadaterima").value = listData[0].Note
        document.getElementById("input_detail_nobukti").value = listData[0].NoBukti

        document.getElementById("input_detail_bon").value = listData[0].NobonS
        document.getElementById("input_detail_nilaibon").value = listData[0].nilaibon ? formatAngka(parseFloat(listData[0].nilaibon).toFixed(2)) : '0.00'
        // document.getElementById("input_detail_transaksi").value = listData[0].NamaCustSupp
        // document.getElementById("input_detail_alamatcustomer").value = listData[0].Alamat1
        // document.getElementById("input_detail_nobukti").value = listData[0].NoBukti
        document.getElementById("input_detail_tanggal").value = formatDate(listData[0].Tanggal)










    },
    error: function (err) {
      console.log(err)
      alertify.warning('Terjadi kesalahan silahkan refresh browser')
      resRefresh = 0;
    }

  })
}



function submitOtorisasi () {

  let _token = $("#_token").val();
  let nobukti = $("#input_detail_nobukti").val();
  $.ajax({
    url: KAS_ROUTES.kasspotorisasi,
    type: "post",
    async: false,
    data: {
      _token,
      nobukti

    },
    success: function(res) {
      alertify.success('Berhasil update otorisasi')
      loadAll()
      buttonCloseForm()




    },
    error: function (err) {
      console.log(err)
      alertify.warning(err.status == 403 ? err.responseText : 'Terjadi kesalahan silahkan refresh browser')
    }

  })


}

function buttonMintaOtorisasi (nobukti) {
  var dlgMinta = alertify.confirm('Minta Otorisasi',
    'Anda tidak berhak otorisasi. Kirim permintaan otorisasi Kas ' + nobukti + ' ke approver?',
    function() {
      $.ajax({
        url: KAS_ROUTES.otorisasirequest,
        type: "post",
        data: {
          _token: $("#_token").val(),
          menu: 'kas',
          nobukti
        },
        success: function(res) {
          alertify.success(res.message)
        },
        error: function (err) {
          console.log(err)
          alertify.warning((err.responseJSON && err.responseJSON.message) || 'Terjadi kesalahan silahkan refresh browser')
        }
      })
    },
    function(){});
  dlgMinta.elements.root.classList.add('ajs-app-buttons');
}

// function buttonDetail (nobukti) {
//   document.getElementById("divOto").style.display = "none";
//
//   let _token = $("#_token").val();
//   $.ajax({
//     url: KAS_ROUTES.kreditnotespdetail,
//     type: "post",
//     async: false,
//     data: {
//       _token,
//       nobukti
//
//     },
//     success: function(res) {
//       console.log(res)
//       // listData = res
//       // console.log(res)
//       if (!res.length) {
//           alertify.success('Data tidak ditemukkan')
//           // $("#form").modal('toggle')
//           return
//       }
//       // dataTableAdd = res
//
//       let rowTable = ``
//       res.forEach((item, i) => {
//               rowTable += `
//                 <tr>
//                   <td>${item.NoInv}</td>
//                   <td>${item.Keterangan}</td>
//                   <td class="text-right">${item.Nilai ? formatAngka(parseFloat(item.Nilai).toFixed(2)) : '0.00'}</td>
//                   <td class="text-right">${item.Saldo ?  formatAngka(parseFloat(item.Saldo).toFixed(2)) : '0.00'}</td>
//
//                   <td>${item.kodeVls}</td>
//                   <td class="text-right">${item.Kurs ?  formatAngka(parseFloat(item.Kurs).toFixed(2)) : '0.00'}</td>
//                   <td class="text-right">${item.NilaiRp ? formatAngka(parseFloat(item.NilaiRp).toFixed(2)) : '0.00'}</td>
//                   <td class="text-right">${item.Saldo ?  formatAngka(parseFloat(item.Saldo).toFixed(2)) : '0.00'}</td>
//
//
//
//                 </tr>
//
//               `
//       });
//
//       document.getElementById("detailTableData").innerHTML = rowTable
//
//
//         document.getElementById("input_detail_kodecustomer").value = res[0].KodeSupp
//         document.getElementById("input_detail_namacustomer").value = res[0].NamaCustSupp
//         document.getElementById("input_detail_alamatcustomer").value = res[0].Alamat1
//         document.getElementById("input_detail_nobukti").value = res[0].NoBukti
//         document.getElementById("input_detail_tanggal").valueAsDate = new Date(res[0].tanggal)
//
//         $('#modalDetail').show();
//         $('.mainpage').hide();
//         $('#page3').show();
//
//
//
//
//
//
//
//
//     },
//     error: function (err) {
//       console.log(err)
//       alertify.warning('Terjadi kesalahan silahkan refresh browser')
//       resRefresh = 0;
//     }
//
//   })
//
// }



function buttonDetail (nobukti , tipe = 'detail') {
  console.log('buttonkoreksi' , nobukti )

  // Tanpa hak otorisasi: tidak membuka form otorisasi, tapi kirim permintaan ke semua
  // approver Kas (push PWA) - lihat app/Services/OtorisasiService.php.
  if (tipe == 'otorisasi' && !Number($("#akses_isotorisasi1").val())) {
    buttonMintaOtorisasi(nobukti)
    return
  }






  refreshDataTableDetail(nobukti)


  if (!listData.length) {
    alertify.warning("Data tidak ditemukkan")
    return
  }

  $('.showhidepage3').hide();

  if (tipe == 'otorisasi') {
    $('.page3otorisasi').show();
  } else {
    $('.page3detail').show();
  }


  // $('.showhideitem').hide();
  $('.showhidePart').hide();
  let value = $("#input_detail_transaksi").val()
  $(`.part${value}`).show();


  // $('#formAdd').show();
  $('.mainpage').hide();
  $('#page3').show();
}



function buttonKoreksi (nobukti ) {
  console.log('buttonKoreksi' , nobukti )

  let akses = $("#akses_iskoreksi").val();

  if (!Number(akses)) {
    alertify.warning('No access')
    return
  }


  tipeform = 'edit'
  cleanFormAdd()



  document.getElementById("input_add_tanggal").disabled = true
  document.getElementById("buttonAddListBon").disabled = true
  document.getElementById("input_add_kepadaterima").disabled = true
  document.getElementById("buttonAddListPerkiraan").disabled = true
  document.getElementById("input_add_transaksi").disabled = true
  document.getElementById("checkBoxKembaliUang").disabled = false

  refreshDataTable(nobukti)


  if (!listData.length) {
    alertify.warning("Data tidak ditemukkan")
    return
  }
  console.log(listData)
  console.log(listData[0].isOtorisasi1)
  if (listData[0].IsOtorisasi1 == 1) {
    alertify.warning("Kas sudah diotorisasi")
    return
  }


  $('.showhideitem').hide();
  $('.showhidePart').hide();
  let value = $("#input_add_transaksi").val()
  $(`.part${value}`).show();


  $('#formAdd').show();
  $('.mainpage').hide();
  $('#page2').show();

  kasPilihOpsi('AddAddKodeDevisi', '01', '01 - Accounting')
  document.getElementById("AddAddNamaDevisi").value = 'Accounting'
  kasPilihOpsi('AddAddValas', 'IDR')
  document.getElementById("AddAddKurs").value = '1.00'

  document.getElementById("AddAddKodeDepartemen").value = ''
  document.getElementById("AddAddNamaDepartemen").value = ''


}

function buttonAdd (nobukti) {
  console.log('buttonAdd' , nobukti)

  let akses = $("#akses_istambah").val();

  if (!Number(akses)) {
    alertify.warning('No access')
    return
  }

  document.getElementById("input_add_tanggal").disabled = false
  document.getElementById("buttonAddListBon").disabled = false
  document.getElementById("input_add_kepadaterima").disabled = false
  document.getElementById("buttonAddListPerkiraan").disabled = false
  document.getElementById("input_add_transaksi").disabled = false

  document.getElementById("checkBoxKembaliUang").disabled = true
  document.getElementById("checkBoxKembaliUang").checked = false



  document.getElementById("addTableData").innerHTML = `<td colspan=12 class="text-center">Belum ada data</td>`
  tipeform = 'add'
  // unlockFormAdd()
  $('.showhideitem').hide();
  // $('.showhideform').hide();
  $('#formAdd').show();
  // $("#form").modal('toggle')

  // input_add_nobukti
  // document.getElementById("input_add_nobukti").value = nobukti

  cleanFormAdd()
  // setNewNoBukti()
  document.getElementById("input_add_transaksi").value = 'BKK'
  onChangeTransaksi()

  $('.mainpage').hide();
  $('#page2').show();

}



function buttonAddAddItem () {
    let value = $("#input_add_kodeperkiraan").val();
    if(!value) {
      alertify.warning("Pilih perkiraan terlebih dahulu")
      return
    }

    tipeformdet = 'add'
    flagtunai = 0
    $('#buttonSubmitAddAdd').show();
    $('#buttonSubmitAddEdit').hide();

    $('#labelAddAddItem').show();
    $('#labelAddEditItem').hide();
    $('#rowCustsupp').hide();
    $('#rowSubCosting').hide();
    $('#rowCosting').hide();
    // $('#rowCustsupp').show();
    // document.getElementById("buttonSubmitAddAdd").style.display = "block";
    // // document.getElementById("buttonSubmitAddEdit").style.display = "none";
    // document.getElementById("labelAddAddItem").style.display = "block";
    // document.getElementById("labelAddEditItem").style.display = "none";
    unlockFormAddAdd()
    $('.showhideitem').hide();
    document.getElementById("buttonAddListCosting").disabled = false
    document.getElementById("buttonAddListSubCosting").disabled = false
    document.getElementById("buttonAddListCustsupp").disabled = false

    cleanFormAddAdd()
    $('#formAddAdd').show();

}

function buttonAddEditItem (i) {

  tipeformdet = 'edit'
  tempBarangAddEdit = listData[i]
  console.log(tempBarangAddEdit)
  lockFormAddAdd()
  cleanFormAddAdd()
  let value = $("#input_add_transaksi").val();

  console.log(value, tempBarangAddEdit.KodeL )
  if (value == 'BKM' && tempBarangAddEdit.KodeL == 'UHT' ) {
    document.getElementById("AddAddJumlah").disabled = true
  } else {
    document.getElementById("AddAddJumlah").disabled = false
  }

  // Item Lawan pelunasan (hutang 21201 / piutang 113100): Jumlah berasal dari pelunasan, jadi
  // tidak boleh diubah sendiri. Lawan disimpan di Perkiraan untuk BKK, di Lawan untuk lainnya.
  let lawanEdit = tempBarangAddEdit.TipeTrans == 'BKK' ? tempBarangAddEdit.Perkiraan : tempBarangAddEdit.Lawan
  document.getElementById("AddAddJumlah").readOnly = (lawanEdit == '21201' || lawanEdit == '113100')

  kasPilihOpsi('AddAddKodeDevisi', tempBarangAddEdit.Devisi,
    tempBarangAddEdit.Devisi + ' - ' + (tempBarangAddEdit.NamaDevisi || ''))
  document.getElementById("AddAddNamaDevisi").value = tempBarangAddEdit.NamaDevisi

  document.getElementById("AddAddKodeCosting").value = tempBarangAddEdit.KODECOST ? tempBarangAddEdit.KODECOST : ''
  document.getElementById("AddAddNamaCosting").value = tempBarangAddEdit.NamaCost ? tempBarangAddEdit.NamaCost : ''

  document.getElementById("AddAddKodeSubCosting").value = tempBarangAddEdit.KODESUBCOST ? tempBarangAddEdit.KODESUBCOST : ''
  document.getElementById("AddAddNamaSubCosting").value = tempBarangAddEdit.NamaSubCost ? tempBarangAddEdit.NamaSubCost : ''

  kasPilihOpsi('AddAddValas', tempBarangAddEdit.Valas)
  document.getElementById("AddAddKurs").value = parseFloat(tempBarangAddEdit.Kurs).toFixed(2)

  document.getElementById("AddAddLawan").value = tempBarangAddEdit.TipeTrans == 'BKK' ? tempBarangAddEdit.Perkiraan : tempBarangAddEdit.Lawan
  document.getElementById("AddAddKeteranganLawan").value = tempBarangAddEdit.TipeTrans == 'BKK' ? tempBarangAddEdit.NamaPerkiraan : tempBarangAddEdit.NamaLawan

  console.log(tempBarangAddEdit.Debet)
  document.getElementById("AddAddJumlah").value = formatAngka(parseFloat(tempBarangAddEdit.Debet).toFixed(2))
  document.getElementById("AddAddKeterangan").value = tempBarangAddEdit.Keterangan
  document.getElementById("AddAddKeteranganDetail").value = tempBarangAddEdit.KetDetail

  document.getElementById("AddAddKodeDepartemen").value = tempBarangAddEdit.KodeBag
  document.getElementById("AddAddNamaDepartemen").value = tempBarangAddEdit.NMDEP


  // $('.showhideitem').hide();
  $('#rowCustsupp').hide();
  $('#rowSubCosting').hide();
  $('#rowCosting').hide();

  document.getElementById("buttonAddListCosting").disabled = true
  document.getElementById("buttonAddListSubCosting").disabled = true
  document.getElementById("buttonAddListCustsupp").disabled = true


  if (tempBarangAddEdit.CustSuppP) {
    document.getElementById("AddAddKodeCustsupp").value = tempBarangAddEdit.CustSuppP
    document.getElementById("AddAddNamaCustsupp").value = tempBarangAddEdit.namacustsuppP
    $('#rowCustsupp').show();
  }

  if (tempBarangAddEdit.CustSuppL) {
    document.getElementById("AddAddKodeCustsupp").value = tempBarangAddEdit.CustSuppL
    document.getElementById("AddAddNamaCustsupp").value = tempBarangAddEdit.namacustsuppL

  }

  // Titipan yang sudah tersimpan hanya ditampilkan (terkunci, sama seperti Custsupp di mode
  // edit); submitAddEdit() tetap mengirim notitipan/uruttitipan item ini apa adanya.
  if (tempBarangAddEdit.TipeTrans == 'BKK' && tempBarangAddEdit.NOTITIPAN) {
    document.getElementById("AddAddNoTitipan").value = tempBarangAddEdit.NOTITIPAN
    document.getElementById("AddAddUrutTitipan").value = tempBarangAddEdit.URUTTITIPAN || ''
    document.getElementById("AddAddNoTitipan").disabled = true
    document.getElementById("buttonAddListTitipan").disabled = true
    $('#rowNoTitipan').show();
  }

  if (tempBarangAddEdit.KODECOST) {
    document.getElementById("AddAddKodeCosting").value = tempBarangAddEdit.KODECOST
    document.getElementById("AddAddNamaCosting").value = tempBarangAddEdit.NamaCost

    $('#rowCosting').show();
  }

  if (tempBarangAddEdit.KODESUBCOST) {
    document.getElementById("AddAddKodeSubCosting").value = tempBarangAddEdit.KODESUBCOST
    document.getElementById("AddAddNamaSubCosting").value = tempBarangAddEdit.NamaSubCost

    $('#rowSubCosting').show();
  }

  $('#buttonSubmitAddAdd').hide();
  $('#buttonSubmitAddEdit').show();

  $('#labelAddAddItem').hide();
  $('#labelAddEditItem').show();



  $('.showhideitem').hide();
  $('#formAddAdd').show();

}


function buttonCloseForm () {
  $('.mainpage').hide();
  // $('#page2').hide();
  $('#page1').show();
  // #page1 bisa saja sudah dirender saat masih disembunyikan (loadAll() dipanggil dari
  // page2/3/4) - kasAturTinggiTabel() melewati perhitungan tinggi saat itu, jadi dihitung
  // ulang sekarang setelah #page1 benar-benar terlihat lagi.
  kasAturTinggiTabel();

}

function loadAll () {

  document.getElementById('tabel_data').innerHTML =
    '<tr><td colspan="20" class="text-center">' + loadingHtml('Memuat data...') + '</td></tr>';

  let date1 = $('#inputDate1').val();
  let date2 = $('#inputDate2').val();

  $.ajax({
    url: KAS_ROUTES.kasloadall,
    type: "get",
    async: true,
    data: {
      date1,
      date2
    },
    success: function(res) {
      lastRows = res.tempOutstanding || [];
      renderTabel();
    },
    error: function (err) {
      console.log(err)
      alertify.warning('Gagal memuat data terbaru, menampilkan data terakhir')
    }})

}

function submitPrint (nobukti) {
    // for (var i = 0; i < 30; i++) {
    //   dataPrint.push(dataPrint[0])
    // }
    let _token = $('#_token').val()
    $.ajax({
      url: KAS_ROUTES.kasdetailCetak,
      type: "post",
      async: false,
      data: {
        _token : _token,
        NOBUKTI: nobukti
      },
      success: function(res) {
        console.log(res)

        dataPrint = res
        console.log(res[0])
        console.log(res[0][0])

        // console.log(res[0][0].IsOtorisasi1)

      }
    })

    let arrayDataPrint = []
    for (let i = 0; i < dataPrint.length; i+=8) {
      let tempArray = dataPrint.slice(i,i+8)
      arrayDataPrint.push(tempArray)
    }

    let printContent = ''
    let imageContent = document.getElementById(`imagecontainer`).innerHTML;
    let css = ''
    let hdr = ''
    let str= ''
    let ftr= ''
    let tanggalOnly = dataPrint[0].Tanggal.split(' ')[0];

    css = `<style type="text/css">
      body {
        font-family: sans-serif;
        font-size: 11px !important;
      }

      table {
        margin: 20px auto;
        border-collapse: collapse;
      }

      table th,
      table td {
        border: 1px solid #3c3c3c;
        height: 24px;
        padding: 1px 5px 0px;
        overflow: hidden;
      }

      a {
        background: blue;
        color: #fff;
        padding: 8px 10px;
        text-decoration: none;
        border-radius: 2px;
      }

      .ttd-place {
        height: 80px;
        text-align: center;
      }

      #ttd {
        width: 1000px;
        border: none;
      }

      .ttd-header {
        padding-top: 40px;
      }

      .body-main-print {
        padding: 1rem;
        padding-top: 1rem;

      }

      .header-ba {
        margin-bottom: 2rem;
        text-decoration: underline;
        margin-top: 2rem;
      }

      .detail-spb-table {
        margin: 0;
      }

      .no-border {
        border: none;
      }

      .detail-ba-div {
      }

      .vertical-align-baseline {
        vertical-align: baseline;
      }

      .mt-2rem {
        margin-top: 2rem;
      }

      .mb-3 {
        margin-bottom: 0.5rem;
      }

      .fw-bold {
        font-weight: bold;
      }

      .mb-1 {
        margin-bottom: 0.25rem;
      }

      .mb-2 {
        margin-bottom: 0.5rem;
      }

      .mb-3 {
        margin-bottom: 1rem;
      }

      .mb-4 {
        margin-bottom: 1.5rem;
      }

      .mb-5 {
        margin-bottom: 3rem;
      }

      .mt-1 {
        margin-top: 0.25rem;
      }

      .mt-2 {
        margin-top: 0.5rem;
      }

      .mt-3 {
        margin-top: 1rem;
      }

      .mt-4 {
        margin-top: 1.5rem;
      }

      .mt-5 {
        margin-top: 3rem;
      }

      .ms-1 {
        margin-left: 0.25rem;
      }

      .ms-2 {
        margin-left: 0.5rem;
      }

      .ms-3 {
        margin-left: 1rem;
      }

      .ms-4 {
        margin-left: 1.5rem;
      }

      .ms-5 {
        margin-left: 3rem;
      }

      .me-1 {
        margin-right: 0.25rem;
      }

      .me-2 {
        margin-right: 0.5rem;
      }

      .me-3 {
        margin-right: 1rem;
      }

      .me-4 {
        margin-right: 1.5rem;
      }

      .me-5 {
        margin-right: 3rem;
      }

      .my-1 {
        margin-top: 0.25rem;
        margin-bottom: 0.25rem;
      }

      .my-2 {
        margin-top: 0.5rem;
        margin-bottom: 0.5rem;
      }

      .my-3 {
        margin-top: 1rem;
        margin-bottom: 1rem;
      }

      .my-4 {
        margin-top: 1.5rem;
        margin-bottom: 1.5rem;
      }

      .my-5 {
        margin-top: 3rem;
        margin-bottom: 3rem;
      }

      .pb-1 {
        padding-bottom: 0.25rem;
      }

      .pb-2 {
        padding-bottom: 0.5rem;
      }

      .pb-3 {
        padding-bottom: 1rem;
      }

      .pb-4 {
        padding-bottom: 1.5rem;
      }

      .pb-5 {
        padding-bottom: 3rem;
      }

      .pt-1 {
        padding-top: 0.25rem;
      }

      .pt-2 {
        padding-top: 0.5rem;
      }

      .pt-3 {
        padding-top: 1rem;
      }

      .pt-4 {
        padding-top: 1.5rem;
      }

      .pt-5 {
        padding-top: 3rem;
      }

      .ps-0 {
        padding-left: 0;
      }

      .ps-1 {
        padding-left: 0.25rem;
      }

      .ps-2 {
        padding-left: 0.5rem;
      }

      .ps-3 {
        padding-left: 1rem;
      }

      .ps-4 {
        padding-left: 1.5rem;
      }

      .ps-5 {
        padding-left: 3rem;
      }

      .pe-1 {
        padding-right: 0.25rem;
      }

      .pe-2 {
        padding-right: 0.5rem;
      }

      .pe-3 {
        padding-right: 1rem;
      }

      .pe-4 {
        padding-right: 1.5rem;
      }

      .pe-5 {
        padding-right: 3rem;
      }

      .py-1 {
        padding-top: 0.25rem;
        padding-bottom: 0.25rem;
      }

      .py-1-5 {
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
      }

      .py-2 {
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
      }

      .py-3 {
        padding-top: 1rem;
        padding-bottom: 1rem;
      }

      .py-4 {
        padding-top: 1.5rem;
        padding-bottom: 1.5rem;
      }

      .py-5 {
        padding-top: 3rem;
        padding-bottom: 3rem;
      }

      .px-1 {
        padding-left: 0.25rem;
        padding-right: 0.25rem;
      }

      .px-1-5 {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
      }

      .px-2 {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
      }

      .px-3 {
        padding-left: 1rem;
        padding-right: 1rem;
      }

      .px-4 {
        padding-left: 1.5rem;
        padding-right: 1.5rem;
      }

      .px-5 {
        padding-left: 3rem;
        padding-right: 3rem;
      }

      .text-left {
        text-align: left;
      }

      .text-center {
        text-align: center;
      }

      .text-right {
        text-align: right;
      }

      .text-decoration-underline {
        text-decoration: underline;
      }

      ul {
        margin: 0;
        padding-left: 10px;
      }

      .note {
        width: 75%;
      }

      .w-15 {
        width: 16%;
      }

      .w-25 {
        width: 30%;
      }

      .w-10 {
        width: 4%;
      }

      .w-1 {
        width: 1%;
      }

      .m-0 {
        margin: 0;
      }

      .body-main-prints {
        width: 21cm;
        height: 13.5cm;
        position: relative;
      }

      .footer-sign {
        padding-top: 5px;
        position: absolute;
        width: 100%;
        bottom: 12px;
      }

      .footer-print-date {
        position: absolute;
        width: 100%;
        bottom: 5px;
      }

       .solid{
        border-left: 0px red solid;
        height: 225px;
        width: 0px;
        display: inline-block;
        padding-left: 0px;
        }

      </style>`;
   	 hdr = `<table style="width:100%; border-collapse:collapse; font-family:sans-serif; font-size:10px;">
            <thead>
            <!-- JUDUL -->
              <tr>
                <td colspan="4" rowspan="2" style="border:1px solid; text-align:center; font-weight:bold; font-size:18px;">
                  BUKTI KAS KELUAR
                </td>
                <td style="border:1px solid; width:15%;">No. Bukti</td>
                <td style="border:1px solid; width:25%;">${dataPrint[0].NoBukti}</td>
              </tr>

              <!-- TANGGAL -->
              <tr>
                <td style="border:1px solid;">Tanggal</td>
                <td style="border:1px solid;">${tanggalOnly}</td>
              </tr>

              <!-- KEPADA -->
              <tr>
                <td style="border:1px solid;">Kepada</td>
                <td colspan="3" style="border:1px solid;">${dataPrint[0].Note ? dataPrint[0].Note : '-'}</td>
                <td colspan="2" style="border:1px solid;">
                  ${dataPrint[0].ketperk} (${dataPrint[0].lawan})
                </td>
              </tr>
                  <tr>
                    <td rowspan="2" class="text-center" style="width: 1%">No.</td>
                    <td rowspan="2" class="text-center" style="width: 10%">KODE</td>
                    <td rowspan="2" class="text-center" style="width: 20%">NAMA</td>
                    <td rowspan="2" class="text-center" style="width: 40%">KETERANGAN</td>
                    <td rowspan="2" class="text-center" style="width: 10%">SUB COST/SKB</td>
                    <td rowspan="2" class="text-center" style="width: 10%">JUMLAH</td>
                  </tr>
                </thead> `;


    let z = 0
    let maxRow = 8;
    let tempPrintStr = ``
    // buat hitung grandtotal
    let grandTotalJumlah = 0;

    dataPrint.forEach(item => {

      if (item.JumlahRp) {
        grandTotalJumlah += Number(item.JumlahRp) || 0;
      }

    });
    // end
    tempPrintStr += `<html>
    <head>
      <title></title>
    </head>

    <body onload="window.print()">
      ` + css

      arrayDataPrint.forEach((item, i) => {
        console.log('arrayDataPrint' , i)
        if (i == 0) {

          tempPrintStr +=  `<div class="body-main-prints" style="break-inside: avoid; margin-left: 7px; margin-top:5px">`
        // } else if ( i < 1) {
        //   tempPrintStr +=  `<div class="body-main-prints" style="break-inside: avoid; margin-left: 7px; padding-top:15px; page-break-before: always">`
        } else {
          tempPrintStr +=  `<div class="body-main-prints" style="break-inside: avoid; margin-left: 7px;padding-top:7px; ">`
        }
        tempPrintStr += hdr
        tempPrintStr += `<tbody border="1">`;
        item.forEach((itemSub, j) => {
          tempPrintStr += ``



         tempPrintStr += `
         <tr>
         <td class="text-align: center"
               style="width: 1%; ">${z+1}</td>
         <td class="text-align: left"
               style="width: 10%;  ">${itemSub.Perkiraan}</td>
         <td class="text-align: left"
               style="width: 20%;">${itemSub.ketlawan}</td>
         <td class="text-align: left"
               style="width: 40%;">${itemSub.keterangan}</td>
         <td class="text-align: left"
               style="width: 10%;">${itemSub.costSKB}</td>
         <td style="width: 10%; text-align: right;">
            ${itemSub.JumlahRp
              ? Number(itemSub.JumlahRp).toLocaleString('id-ID', {
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2
                })
              : ''}
          </td>
         </tr>`;

           z++;

        });

	// TAMBAHAN
        let sisaRow = maxRow - item.length;

        for (let k = 0; k < sisaRow; k++) {
          tempPrintStr += `
          <tr>
            <td style="border-top:none; border-bottom:none;">&nbsp;</td>
  	    <td style="border-top:none; border-bottom:none;"></td>
  	    <td style="border-top:none; border-bottom:none;"></td>
  	    <td style="border-top:none; border-bottom:none;"></td>
  	    <td style="border-top:none; border-bottom:none;"></td>
  	    <td style="border-top:none; border-bottom:none;"></td>
          </tr>`;
        }

        tempPrintStr += `
        <tr>
          <td colspan="4" style="border:1px solid; padding:5px; font-weight:bold;">
            Cetak ke : ${(item && item.length > 0) ? item[0].Cetakke : '-'}
          </td>
          <td style="border:1px solid; text-align:right; font-weight:bold;">
            Total :
          </td>
          <td style="border:1px solid; text-align:right; font-weight:bold;">
            ${grandTotalJumlah.toLocaleString('id-ID', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2
            })}
          </td>
        </tr>`;

         tempPrintStr += `</tbody>`;

         tempPrintStr += `</table>


         <div class="footer-sign font-family: sans-serif;
           font-size: 10px ">

         <div class="row mt-3" style="text-align: left;font-family: sans-serif;
         font-size: 12px ">
         <span style="float: left; display: block; clear: left;">
         </span>


         <div style="width:100%; display:flex; font-weight:bold; margin-top:5px;">

          </div>

         </div>


         <table
            class="detail-spb-table mb-2"
            style="
              width: 100%;
              margin-top: 20px;
              font-family: sans-serif;
              font-size: 10px;
              border-collapse: collapse;">
            <tr>
              <td style="width: 20%; border: 1px solid; text-align: center;">Menyetujui</td>
              <td style="width: 20%; border: 1px solid; text-align: center;">Mengetahui</td>
              <td style="width: 20%; border: 1px solid; text-align: center;">Kasir</td>
              <td style="width: 20%; border: 1px solid; text-align: center;">Penerima</td>
            </tr>

            <tr style="height: 60px;">
              <td style="border: 1px solid;"></td>
              <td style="border: 1px solid;"></td>
              <td style="border: 1px solid;"></td>
              <td style="border: 1px solid;"></td>
            </tr>
          </table>
         </div>


         <div class="footer-print-date">
           <table class="m-0" style="width: 100% ; font-family: sans-serif;
           font-size: 10px ">
             <tr>
               <td class="no-border"></td>
               <td class="no-border text-right">Page ${i+1} of ${arrayDataPrint.length}</td>
             </tr>
           </table>

         </div>`


        tempPrintStr += `</div>`
      });


      tempPrintStr +=  `</body></html>`



    w=window.open(' ')
    w.document.write(tempPrintStr)

    w.print()
    w.close()

  }

function buttonBatalOtorisasi (nobukti) {

  console.log(nobukti)



  let akses = $("#akses_isotorisasi1").val();
  if (!Number(akses)) {
    alertify.warning('No access')
    return
  }


  // Kelas 'ajs-app-buttons' + 'is-danger' ditempel ke root dialog supaya CSS tombol
  // OK/Cancel di public/css/report-table.css (blok "gaya bersama") ikut jalan;
  // 'is-danger' bikin tombol OK merah karena batal otorisasi aksi merusak.
  var dlgBatalOtorisasi = alertify.prompt('Batal Otorisasi', 'Masukkan alasan batal otorisasi Kas ' + nobukti, '',
      function(evt, value) {
        if (!value || value.trim() == '') {
          alertify.warning('Alasan harus diisi.');
          return false;
        }
        let _token = $("#_token").val();

        $.ajax({
          url: KAS_ROUTES.kasspbatalotorisasi,
          type: "post",
          async: false,
          data: {
            _token,
            nobukti,
            pket: value.trim()

          },
          success: function(res) {
            alertify.success('Berhasil batal otorisasi')
            loadAll()

          },
          error: function (err) {
            console.log(err)
            alertify.warning(err.status == 403 ? err.responseText : 'Terjadi kesalahan silahkan refresh browser')
          }

        })
      }
    ,function(){
      console.log('no')
    });
  dlgBatalOtorisasi.elements.root.classList.add('ajs-app-buttons', 'is-danger');

}




function formatDate(date , pemisah = '-') {
    var d = new Date(date),
        month = '' + (d.getMonth() + 1),
        day = '' + d.getDate(),
        year = d.getFullYear();

    if (month.length < 2)
        month = '0' + month;
    if (day.length < 2)
        day = '0' + day;

    return [year, month, day].join(pemisah);
}
function formatAngka (angkaString) {
  console.log('formatAngka' , angkaString);
  let tempAngka = angkaString.split('.')
  let temp1 = ''
  for (let i = 0; i < tempAngka[0].length; i++) {
    if (i != 0 && i % 3 == 0) {
      temp1 = ',' + temp1
    }
    temp1 = tempAngka[0][tempAngka[0].length - i -1] + temp1
    // console.log(i, temp1)
  }
  temp1 += '.' + tempAngka[1]
  return temp1
}

function unformatAngka (angka) {
  if (!angka) return 0
  return parseFloat(String(angka).replace(/,/g, '')) || 0
}

function formatAngkaInput (el) {
  el.value = formatAngka(unformatAngka(el.value).toFixed(2))
}

// Dipasang di oninput #AddAddJumlah supaya separator ribuan langsung muncul sambil mengetik,
// tidak menunggu pindah fokus (onblur formatAngkaInput() tetap jalan untuk menormalkan ke 2
// desimal). Tidak memakai formatAngka() biasa karena nilai yang sedang diketik boleh belum
// punya titik desimal atau baru diketik sebagian - formatAngka() mengasumsikan keduanya sudah
// lengkap. Posisi kursor dihitung ulang dari jarak ke kanan supaya tidak melompat ke ujung
// setiap kali jumlah koma bertambah. Disalin dari accounting/memorialkoreksi.blade.php
// (pola sama seperti public/js/bank.js).
function formatAngkaKetik (el) {
  let posDariKanan = el.value.length - el.selectionStart
  let minus = el.value.trim().startsWith('-') ? '-' : ''
  let raw = el.value.replace(/[^0-9.]/g, '')

  let titikIndex = raw.indexOf('.')
  let bulat = titikIndex === -1 ? raw : raw.slice(0, titikIndex)
  let desimal = titikIndex === -1 ? '' : raw.slice(titikIndex + 1).replace(/\./g, '').slice(0, 2)

  bulat = bulat.replace(/^0+(?=\d)/, '')
  if (bulat === '') { bulat = '0' }

  let bulatFormatted = ''
  for (let i = 0; i < bulat.length; i++) {
    if (i != 0 && (bulat.length - i) % 3 == 0) { bulatFormatted += ',' }
    bulatFormatted += bulat[i]
  }

  el.value = minus + bulatFormatted + (titikIndex !== -1 ? '.' + desimal : '')

  let posBaru = Math.max(0, el.value.length - posDariKanan)
  el.setSelectionRange(posBaru, posBaru)
}


