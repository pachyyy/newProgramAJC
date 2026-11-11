@extends('report.newmaster2x')
<!-- Font Awesome CDN -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" 
rel="stylesheet">
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap JS (include Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

@section('buttons')
@endsection
@section('content')
@yield('header2')
<div class="container-fluid">
  <div class="row">
    <div class="col-6 text-left"> 
      @yield('reportname')
    </div>
  </div>
</div>

<div id="printContainer" style="display:none">

</div>
<div id="contentContainer" class="container-fluid" style="margin-top:-150px;">

        <div class="row">

        </div>
        {{-- <div class="row justify-content-center">
          <div class="card w-75">
            <div class="card-body">
              <div class="container-fluid">

                @yield('input')

                <br>
                <br>

                <div class="row pr-3" style="display: flex; justify-content: right;">
                  <button type="button" class="btn btn-primary" style="
                  height: 30px; 
                  padding: 4px 12px; 
                  border-radius: 20px; 
                  font-size: 0.75rem; 
                  margin-right: 8px;
                  font-weight: 600; 
                  text-transform: uppercase; 
                  transition: background-color 0.3s, box-shadow 0.3s;
                  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);" onclick="doShowFormFilterData()">Filter Data</button>
                  <button type="button" class="btn btn-primary" style="
                  height: 30px; 
                  padding: 4px 12px; 
                  border-radius: 20px; 
                  font-size: 0.75rem; 
                  font-weight: 600; 
                  margin-right: 8px;
                  text-transform: uppercase; 
                  transition: background-color 0.3s, box-shadow 0.3s;
                  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);" onclick="doShowFormCustomizeTable()">Customize Table</button>
                  <button type="button" class="btn btn-primary" style="
                  height: 30px; 
                  padding: 4px 12px; 
                  border-radius: 20px; 
                  font-size: 0.75rem; 
                  font-weight: 600; 
                  margin-right: 8px;
                  text-transform: uppercase; 
                  transition: background-color 0.3s, box-shadow 0.3s;
                  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);" onclick="makeTable('REPORT')">Submit</button>
                </div>

              </div>
            </div>
          </div>
        </div> --}}

        <div class="container-fluid">
          <div id="showTableReport" style="display:none; background-color: white; padding: 10px" class="row mt-4 rounded">
            <div class="col-12 text-right">
              <button type="button" class="btn btn-success" style="
                height: 30px; 
                padding: 4px 12px; 
                border-radius: 20px; 
                font-size: 0.75rem; 
                font-weight: 600; 
                text-transform: uppercase; 
                transition: background-color 0.3s, box-shadow 0.3s;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);" onclick="doExportTableToExcel('tabel')">Export to Excel</button>
              <button type="button" class="btn btn-success" style="
                height: 30px; 
                padding: 4px 12px; 
                border-radius: 20px; 
                font-size: 0.75rem; 
                font-weight: 600; 
                text-transform: uppercase; 
                transition: background-color 0.3s, box-shadow 0.3s;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);" onclick="doExportTableToCsv('tabel')">Export to CSV</button>
              <button type="button" class="btn btn-danger" style="
                height: 30px; 
                padding: 4px 12px; 
                border-radius: 20px; 
                font-size: 0.75rem; 
                font-weight: 600; 
                text-transform: uppercase; 
                transition: background-color 0.3s, box-shadow 0.3s;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);" onclick="doCloseTable()">Close Table</button>
            </div>
            <div class="col-12 mt-4" style="overflow:auto;">
              <div class="">
                <table id="tabel" class="table table-bordered">

                  <thead id="tabel_header" class="text-left" >
                  </thead>

                  <tbody id="tabel_data" class="text-center bg-dark text-white"  style="border: 1px solid black; text-align: center;">
                  </tbody>

                </table>
              </div>
            </div>
          </div>
        </div>


        <!-- start modal customize table -->
        <div class="modal fade"  id="formCustomizeTable" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-sm modal-dialog-centered"  role="document" style="max-width: 40%">
            <div class="modal-content">
              <div class="modal-header text-right">
                <h5 class="modal-title" id="formCustomizeTableLabel">Customize Table</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="doCloseFormCustomizeTable()">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <div class="container-fluid">
                  <div class="row mt-3">
                    <div class="col-12" id="tabelcustomize_data" style="overflow:auto;">
                      
                    </div>
                  </div>
                  
                  <div id="buttonSubtotal" class="row mt-3" style="display: flex; justify-content: center;">
                  </div>
                  <div id="buttonGrandtotal" class="row mt-3" style="display: flex; justify-content: center;">
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="doResetHeader()" title="Reset">Reset</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="doCloseFormCustomizeTable()" title="Tutup (Esc)">Tutup</button>
              </div>
            </div>
          </div>
        </div>
        <!-- End modal customize table -->


        <!-- start modal setting total -->
        <div class="modal fade"  id="formSettingTotal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-sm modal-dialog-centered"  role="document" style="max-width: 40%">
            <div class="modal-content">
              <div class="modal-header text-right">
                <h5 class="modal-title" id="formSettingTotalLabel">Setting Total</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="doCloseFormSettingTotal()">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <div class="container-fluid">
                  <div class="row">
                    <div class="col-7 text-left">
                      <div class="form-group text-left">
                        <label class="text-left">Jumlah desimal di belakang koma</label>
                      </div>
                    </div>
                    <div class="col-2">
                      <div class="form-group">
                        <input type="number" id="setTotalDesimal" class="form-control" name="setTotalDesimal">
                      </div>
                    </div>
                  </div>

                  <div id="buttonSetTotal" class="row mt-3" style="display: flex; justify-content: center;">
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="doCloseFormSettingTotal()" title="Batal (Esc)">Batal</button>
                <button type="button" class="btn btn-secondary" onclick="doSimpanFormSettingTotal()" title="Simpan">Simpan</button>
              </div>
            </div>
          </div>
        </div>
        <!-- End modal setting total -->


        <!-- start modal filter data -->
        <div class="modal fade"  id="formFilterData" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-sm modal-dialog-centered"  role="document" style="max-width: 50%">
            <div class="modal-content">
              <div class="modal-header text-right">
                <h5 class="modal-title" id="formFilterDataLabel">Filter Data</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="doCloseFormFilterData()">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <div class="container-fluid">
                  <div class="row" style="display: flex; justify-content: right;">
                    <label id="tabelfilter_totalrow"></label>
                  </div>
                  <div class="row mt-3">
                    <div class="col-12" style="overflow:auto;">
                      <div class="">
                            <table id="tabelfilter" class="table table-bordered table-striped"  >
                              <thead id="tabelfilter_header" class="text-center">
                                <tr>
                                  <th>Nomor Bukti</th>
                                  <th>Tanggal</th>
                                </tr>
                              </thead>
                              <tbody id="tabelfilter_data" class="text-center" >
                              </tbody>
                            </table>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="doCloseFormFilterData()" title="Batal (Esc)">Batal</button>
                <button type="button" class="btn btn-primary" onclick="doShowReportFilter()" title="Submit (Ctrl+F)">Submit</button>
              </div>
            </div>
          </div>
        </div>
        <!-- End modal filter data -->
</div>
@endsection

@section('js')
{{-- Sama seperti masterreport2.blade.php: window.ReportTable + loadingHtml/fmtRp datang dari
     sini. Sebelumnya TIDAK dimuat di layout ini, jadi makeTable() di halaman yang sudah dipakaikan
     ReportTable.init()/headHtml() (mis. reportlaporanmarketingspb) gagal duluan di baris
     loadingHtml('Memuat data...') -- ReferenceError sebelum sempat memanggil $.ajax(). --}}
<script src="{!! URL::asset('js/report-table.js') !!}?v={{ @filemtime(base_path('public/js/report-table.js')) ?: '1' }}"></script>
@yield('jsreport')
<script src="{{ asset('js/exceljs.min.js') }}?v={{ filemtime(public_path('js/exceljs.min.js')) }}"></script>
<script src="{{ asset('js/report-export.js') }}?v={{ filemtime(public_path('js/report-export.js')) }}"></script>
<script type="text/javascript">
  var g_href = '{!! $akses['href'] !!}';

  var g_modeModal = "";
  var gmodal_customizetable = "customizetable", gmodal_settingtotal = "settingtotal";
  var gmodal_filterdata = "filterdata";
  
  // === GRAND TOTAL FINAL  ===
  var gsum_isfinalgrandtotal = 1;
  var gsum_colFinal = {}; 

  var g_modeReport;
  var gcart_header = [];
  var gsum_issubtotal = 0, gsum_isgrandtotal = 0;
  var gsum_colCart = {}, gsum_posArray = [], gsum_rowSubtotal = 0;

  var gsettotal_index = 0;
  var gsettotal_nowtotal = 0;

  var gcart_filter = [];
  var gcart_filterShow = [];
  var gfilter_lastrow = -1, gfilter_totalrow = 0;
  var gfilter_title, gfilter_groupby, gfilter_date1, gfilter_date2;

  var gxls_filename = ""; // berikan nilai di Blade jika ingin custom file name excel



  $(document).ready(function(){
    doSetHeader(g_modeReport);   // doSetHeader() sudah memanggil doButtonSubtotal/doButtonGrandtotal sendiri

    $("#tabelfilter").DataTable({
      "lengthChange": false,
      "paging": false,
    });
  });

  document.onkeydown = function(e) {
    if(event.keyCode == 13 && e.ctrlKey){ return doCtrlEnter(); }
    if(event.keyCode == 38 && e.ctrlKey){ return doGodown(); }
    if(event.keyCode == 27){ return doEscButton(); }
  }

  function doCtrlEnter() {
    switch (g_modeModal) {
      case gmodal_settingtotal :
                 doSimpanFormSettingTotal();
                 break;
                    
      case gmodal_filterdata :
                 doShowReportFilter();
                 break;
                              
      default :
                 makeTable('REPORT');
                 break;
    }
  }

  function doGodown() {
    document.getElementById("showTableReport").scrollIntoView({ behavior : "smooth" });
  }

  function doEscButton() {
    switch (g_modeModal) {
      case gmodal_customizetable :
                 doCloseFormCustomizeTable();
                 break;
                     
      case gmodal_settingtotal :
                 doCloseFormSettingTotal();
                 break;
                     
      case gmodal_filterdata :
                 doCloseFormFilterData();
                 break;
     
      default :
                return;
    }
  }



  /* ============== START CUSTOMIZE HEADER  ============== */

  function doShowFormCustomizeTable() {
    g_modeModal = gmodal_customizetable;
    doShowCustomize();
    $("#formCustomizeTable").modal('toggle');
  }

  function doCloseFormCustomizeTable() {
    g_modeModal = "";
    $("#formCustomizeTable").modal('toggle');
  }

  function doResetHeader() {
    alertify.confirm('Reset Customize Table', 'Apakah yakin ingin mengembalikan kolom tabel ke pengaturan awal?',
    function() {
        doSetHeader(g_modeReport, true);
        doShowCustomize();
        doButtonSubtotal(gsum_issubtotal);
        doButtonGrandtotal(gsum_isgrandtotal);
        alertify.success("Kolom tabel sudah kembali ke pengaturan awal");
      }, function(){}
    );
  }

  function doSetHeader(_modereport, _isReset = false) {
    let _strHeader = (!_isReset) ? doLoadHeader('{!! $akses['href'] !!}', _modereport) : "";

    if (_strHeader != "") {
      gcart_header = doGetHeader(_strHeader);
    } else {
      // cek apakah function setDefaultHeader ada
      if ($.isFunction(window.setDefaultHeader)) {
        setDefaultHeader();
        doSimpanHeader('{!! $akses['href'] !!}', g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);
      }
    }
    doButtonSubtotal(gsum_issubtotal);
    doButtonGrandtotal(gsum_isgrandtotal);
  }

  function doLoadHeader(_href, _mode) {
    // window.g_headerStore (diisi newmaster2x dari $akses['simpanheader']) memuat
    // seluruh baris DBSIMPANHEADER milik user+href ini -- baca dari situ dulu
    // supaya tidak perlu AJAX sinkron (yang mengunci main thread) tiap kali
    // doSetHeader() dipanggil, termasuk panggilan kedua dari ready master layout.
    // Sekaligus memperbaiki bug lama: res[0].issubtotal diakses tanpa guard,
    // yang melempar error saat halaman belum pernah punya header tersimpan.
    let _key = String(_mode);
    let _row = window.g_headerStore ? window.g_headerStore[_key] : undefined;

    if (_row === undefined) {   // undefined = belum pernah dicek -> baru ambil dari server
      $.ajax({
        url     : "{!! url('globalfunctions_doLoadHeader') !!}",
        type    : "get",
        async   : false,
        data    : {
          href : _href,
          mode : _mode
        },
        success: function(res) {
          _row = (res.length > 0)
            ? { header: res[0].header, issubtotal: toInteger(res[0].issubtotal), isgrandtotal: toInteger(res[0].isgrandtotal) }
            : null;   // null = sudah dicek ke server, memang tidak ada baris tersimpan
        }
      })

      if (window.g_headerStore) { window.g_headerStore[_key] = _row; }
    }

    if (!_row) { return ""; }

    gsum_issubtotal = Number(_row.issubtotal);
    gsum_isgrandtotal = Number(_row.isgrandtotal);
    return _row.header;
  }

  function doGetHeader(_strHeader) {
    let _cart = [];

    _strHeader.split("||").forEach((item, i) => {
      let temp = [];
      temp.push(item.split(";;")[0]);             // nama kolom
      temp.push(item.split(";;")[1]);             // nama header
      temp.push(toInteger(item.split(";;")[2]));  // muncul / tidak muncul
      temp.push(item.split(";;")[3]);             // tipe data
      temp.push(toInteger(item.split(";;")[4]));  // 0 = tanpa total, 1 = pakai total (khusus numeric)
      temp.push(toInteger(item.split(";;")[5]));  // jumlah desimal di belakang koma
      _cart.push(temp);
    });

    return _cart;
  }

  function doSimpanHeader(_href, _mode, _cart, _issubtotal, _isgrandtotal) {
    let _strHeader = "";

    _cart.forEach((item, i) => {
      if (i != 0) { _strHeader += '||'; }
      _strHeader += item[0] + ';;' + item[1] + ';;' + item[2] + ';;' + item[3] + ';;' + item[4] + ';;' + item[5];
    });

    // Lewati request kalau isinya sama persis dengan yang terakhir diketahui
    // tersimpan (dari g_headerStore) -- doSetHeader() memanggil ini tiap page
    // load walau tidak ada perubahan sama sekali, jadi ini menghapus AJAX
    // sinkron yang percuma.
    let _key = String(_mode), _store = window.g_headerStore;
    let _prev = _store ? _store[_key] : undefined;

    if (_prev && _prev.header === _strHeader
        && Number(_prev.issubtotal) === Number(_issubtotal)
        && Number(_prev.isgrandtotal) === Number(_isgrandtotal)) {
      return;
    }

    $.ajax({
      url     : "{!! url('globalfunctions_doSimpanHeader') !!}",
      type    : "get",
      async   : false,
      data    : {
        href : _href,
        mode : _mode,
        header : _strHeader,
        issubtotal : _issubtotal,
        isgrandtotal : _isgrandtotal
      },
      success: function(res) {
        // nothing to do
      }
    })

    if (_store) {
      _store[_key] = { header: _strHeader, issubtotal: Number(_issubtotal), isgrandtotal: Number(_isgrandtotal) };
    }
  }

  function doShowCustomize() {
    let str = "";
    let tempcart = gcart_header;

    tempcart.forEach((item, i) => {
      let _checked = (item[2]) ? 'btn-success' : 'btn-outline-danger';
      let _icon_eye = (item[2]) ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
      str += '<div class="row justify-content-center text-center">';
      str += '  <div class="col-2 ' + _checked + ' text-center header-toggle" id="buttonHeader' + i + '" onclick="doButtonVisibility(' + i + ')">' + _icon_eye + '</div>';

      if (item[3] == "float" || item[3] == "int") {
        str += '  <div class="col-5 btn-outline-success text-center header-toggle" draggable="true" onclick="doShowFormSettingTotal(' + i + ')">' + item[1] + '</div>';
      } else {
        str += '  <div class="col-5 btn-outline-dark text-center header-toggle disabled" draggable="true">' + item[1] + '</div>';
      }
      str += '  <div class="col-2 btn-primary text-center header-toggle" id="buttonUp' + i + '" onclick="doButtonUpDown(' + i + ', 0)"><i class="bi bi-arrow-up"></i></div>';
      str += '  <div class="col-2 btn-primary text-center header-toggle" id="buttonDown' + i + '" onclick="doButtonUpDown(' + i + ', 1)"><i class="bi bi-arrow-down"></i></div>';
      str += '</div>';
    });

    $("#tabelcustomize_data").html(str);
  }

  function doButtonVisibility(_id) {
    if (gcart_header[_id][2] == 1) {
      $("#buttonHeader" + _id).removeClass("btn-success");
      $("#buttonHeader" + _id).addClass("btn-outline-danger");
      $("#buttonHeader" + _id).html('<i class="bi bi-eye-slash"></i>');
      gcart_header[_id][2] = 0;
    } else {
      $("#buttonHeader" + _id).removeClass("btn-outline-danger");
      $("#buttonHeader" + _id).addClass("btn-success");
      $("#buttonHeader" + _id).html('<i class="bi bi-eye"></i>');
      gcart_header[_id][2] = 1;
    }

    doSimpanHeader('{!! $akses['href'] !!}', g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);
  }

  function doButtonUpDown(_id, _mode) {
    // mode = 0 UP, 1 DOWN
    let temp = [];
    let _idx = (_mode == 0) ? _id-1 : _id+1; // idx adalah index tujuan

    let _isNotEdge = (_mode == 0) ? (_id > 0) : (_id < gcart_header.length-1);

    if (_isNotEdge) {
      // masukkan data yang sudah ada di index tujuan ke temp
      temp.push(gcart_header[_idx][0]);
      temp.push(gcart_header[_idx][1]);
      temp.push(gcart_header[_idx][2]);
      temp.push(gcart_header[_idx][3]);
      temp.push(gcart_header[_idx][4]);
      temp.push(gcart_header[_idx][5]);

      // masukkan data index asal ke index tujuan
      gcart_header[_idx][0] = gcart_header[_id][0];
      gcart_header[_idx][1] = gcart_header[_id][1];
      gcart_header[_idx][2] = gcart_header[_id][2];
      gcart_header[_idx][3] = gcart_header[_id][3];
      gcart_header[_idx][4] = gcart_header[_id][4];
      gcart_header[_idx][5] = gcart_header[_id][5];

      // masukkan data dari temp ke index asal
      gcart_header[_id] = temp;

      doSimpanHeader('{!! $akses['href'] !!}', g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);
      doShowCustomize();
    }
  }

  function doButtonSubtotal(_mode) {
    let _str = '';
    if (_mode === 0) {
      // TANPA SUBTOTAL
      _str += '<div class="col-6 btn-primary text-center tombol-toggle" onclick="doButtonSubtotal(0)">Tanpa Subtotal</div>';
      _str += '<div class="col-6 btn-outline-primary text-center tombol-toggle" onclick="doButtonSubtotal(1)">Pakai Subtotal</div>';
    } else {
      // PAKAI SUBTOTAL
      _str += '<div class="col-6 btn-outline-primary text-center tombol-toggle" onclick="doButtonSubtotal(0)">Tanpa Subtotal</div>';
      _str += '<div class="col-6 btn-primary text-center tombol-toggle" onclick="doButtonSubtotal(1)">Pakai Subtotal</div>';
    }
    $("#buttonSubtotal").html(_str);

    gsum_issubtotal = _mode;
    
    doSimpanHeader('{!! $akses['href'] !!}', g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);
  }

  function doButtonGrandtotal(_mode) {
    let _str = '';
    if (_mode === 0) {
      // TANPA GRANDTOTAL
      _str += '<div class="col-6 btn-primary text-center tombol-toggle" onclick="doButtonGrandtotal(0)">Tanpa Grand Total</div>';
      _str += '<div class="col-6 btn-outline-primary text-center tombol-toggle" onclick="doButtonGrandtotal(1)">Pakai Grand Total</div>';
    } else {
      // PAKAI GRANDTOTAL
      _str += '<div class="col-6 btn-outline-primary text-center tombol-toggle" onclick="doButtonGrandtotal(0)">Tanpa Grand Total</div>';
      _str += '<div class="col-6 btn-primary text-center tombol-toggle" onclick="doButtonGrandtotal(1)">Pakai Grand Total</div>';
    }
    $("#buttonGrandtotal").html(_str);

    gsum_isgrandtotal = _mode;
    
    doSimpanHeader('{!! $akses['href'] !!}', g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);
  }

  /* ============== END OF CUSTOMIZE HEADER ============== */



  /* ==============   START SETTING TOTAL   ============== */

  function doShowFormSettingTotal(_index) {
    g_modeModal = gmodal_settingtotal;
    
    gsettotal_index = _index;
    $("#setTotalDesimal").val(gcart_header[_index][5]);
    doButtonTotal(gcart_header[_index][4]);

    $("#formCustomizeTable").css('opacity', '0.6');
    $("#formSettingTotalLabel").html("Setting Kolom " + gcart_header[_index][1]);
    $("#formSettingTotal").modal('toggle');
  }

  function doCloseFormSettingTotal() {
    g_modeModal = gmodal_customizetable;
    $("#formCustomizeTable").css('opacity', '1');
    $("#formSettingTotal").modal('toggle');
  }

  function doButtonTotal(_mode) {
    let _str = '';
    if (_mode === 0) {
      // TANPA TOTAL
      _str += '<div class="col-6 btn-primary text-center tombol-toggle" onclick="doButtonTotal(0)">Tanpa Total</div>';
      _str += '<div class="col-6 btn-outline-primary text-center tombol-toggle" onclick="doButtonTotal(1)">Pakai Total</div>';
    } else {
      // PAKAI TOTAL
      _str += '<div class="col-6 btn-outline-primary text-center tombol-toggle" onclick="doButtonTotal(0)">Tanpa Total</div>';
      _str += '<div class="col-6 btn-primary text-center tombol-toggle" onclick="doButtonTotal(1)">Pakai Total</div>';
    }
    $("#buttonSetTotal").html(_str);

    gsettotal_nowtotal = _mode;    
  }

  function doSimpanFormSettingTotal() {
    if ($("#setTotalDesimal").val() == "") { $("#setTotalDesimal").val(0); }
    if (toInteger($("#setTotalDesimal").val()) < 0) {
      alertify.warning("Jumlah desimal tidak boleh lebih kecil dari nol");
      return;
    }
    gcart_header[gsettotal_index][4] = gsettotal_nowtotal;
    gcart_header[gsettotal_index][5] = toInteger($("#setTotalDesimal").val());
    doSimpanHeader('{!! $akses['href'] !!}', g_modeReport, gcart_header, gsum_issubtotal, gsum_isgrandtotal);

    doCloseFormSettingTotal();
  }

  /* ==============  END OF SETTING TOTAL   ============== */



  /* ==============    START MAIN REPORT    ============== */

  function doCloseTable() {
    document.getElementById("showTableReport").style.display = "none"
  }

  function doReportMode(_mode) {
    if (g_modeReport != _mode) {
      let prev_mode = g_modeReport;
      g_modeReport = _mode;

      $("#buttonMode" + prev_mode).removeClass("btn-primary");
      $("#buttonMode" + prev_mode).addClass("btn-outline-primary");

      $("#buttonMode" + g_modeReport).removeClass("btn-outline-primary");
      $("#buttonMode" + g_modeReport).addClass("btn-primary");

      doSetHeader(g_modeReport);
      doShowCustomize();
    }
  }

  function doMakeTable(_mode, _groupby, _data, _title, _date1, _date2 = null, inputdetailorRekap) {
    let url = "{!! url('" + g_href + "_doReport') !!}";

    $.ajax({
      url     : url,
      type    : "get",
      async   : false,
      data    : _data,
      success: function(res) {
        console.log(res)
        if (_mode == "REPORT") {
          document.getElementById("showTableReport").style.display = "block";
          doShowReport(res, _title, _groupby, _date1, _date2);
          // alertify.success("Report ditampilkan");
        } else if (_mode == "FILTER") {
          gcart_filter = res;
          gfilter_title = _title;
          gfilter_groupby = _groupby;
          gfilter_date1 = _date1;
          gfilter_date2 = _date2;
        }
      }
    })
  }

  function getColumnType(col) {
    for (let i = 0; i < col.length; i++) {
        let v = (col[i] + "").toLowerCase();
        if (v === "float" || v === "double" || v === "decimal") return "float";
        if (v === "varchar" || v === "string" || v === "text") return "varchar";
    }
    return "varchar"; 
  }

  function doSetRowTableWithGrouping(data, tempcart) {
    let html = "";

    let isMultiGrouping =
      typeof g_groupingConfig !== "undefined" &&
      Array.isArray(g_groupingConfig) &&
      g_groupingConfig.length > 0;

    // ===== INIT FINAL GRAND TOTAL =====
    if (gsum_isfinalgrandtotal === 1) {
      tempcart.forEach(col => {
        if (col[4] === 1 && gsum_colFinal["ftot" + col[0]] === undefined) {
          gsum_colFinal["ftot" + col[0]] = 0;
        }
      });
    }

    // ================= MULTI GROUPING ========================
    if (isMultiGrouping) {

      const LEVEL_CUSTOMER = 0;
      const LEVEL_LOKASI   = 1;
      const LEVEL_BULAN    = 2;

      let prevRow = null;

      data.forEach((r, i) => {

        let changedLevel = -1;

        if (!prevRow) {
          changedLevel = 0;
        } else {
          g_groupingConfig.forEach((lvl, idx) => {
            if (changedLevel === -1 && r[lvl.field] !== prevRow[lvl.field]) {
              changedLevel = idx;
            }
          });
        }

        if (changedLevel !== -1 && i !== 0) {

          if (changedLevel <= LEVEL_BULAN) {
            html += doSetRowSubtotal(tempcart);
          }

          if (changedLevel <= LEVEL_LOKASI) {
            html += doSetRowGrandtotal(tempcart);

            Object.keys(gsum_colCart).forEach(k => {
              if (k.startsWith("gtot")) gsum_colCart[k] = 0;
            });
          }
        }

        if (changedLevel !== -1) {
          for (let x = changedLevel; x < g_groupingConfig.length; x++) {
            let lvl = g_groupingConfig[x];
            html += `
              <tr style="background:#eee;font-weight:bold">
                <td colspan="${tempcart.length}" style="text-align:left;">
                  ${lvl.header(r)}
                </td>
              </tr>`;
          }
        }

        // ===== DATA ROW =====
        let row = "<tr>";

        tempcart.forEach(col => {
          let kolom = col[0];
          let tipe  = getColumnType(col);
          let val   = r[kolom] ?? "";

          if (tipe === "float") {
            let num = currencyNormalizer(val);

            if (col[4] === 1) {
              gsum_colCart["stot" + kolom] += num;
              gsum_colCart["gtot" + kolom] += num;

              if (gsum_isfinalgrandtotal === 1) {
                gsum_colFinal["ftot" + kolom] += num;
              }
            }

            row += `<td style="text-align:right">${format_number(num, col[5])}</td>`;
          } else {
            row += `<td>${val}</td>`;
          }
        });

        row += "</tr>";
        html += row;

        prevRow = r;
      });

      if (data.length > 0) {
        html += doSetRowSubtotal(tempcart);
        html += doSetRowGrandtotal(tempcart);

        if (gsum_isfinalgrandtotal === 1) {
          html += doSetRowGrandTotalFinal(tempcart);
        }
      }

      return html;
    }

    // ================= SINGLE GROUPING =================

    let lastValue = null;
    let prevGroup = null;

    let needGrouping = (typeof g_needGrouping !== "undefined") ? g_needGrouping : false;

    let avgCol = getAverageColumn(tempcart); 
    let needAverage = avgCol !== null;
    let avgSum = 0;
    let avgCnt = 0;

    data.forEach((r, i) => {

        let key = g_groupField;
        let groupVal = r[key] ?? "";

        if (needGrouping && i !== 0 && groupVal !== prevGroup) {
            html += doSetRowSubtotal(tempcart);
        }

        if (needGrouping && groupVal !== lastValue) {
            html += `
              <tr style="background:#eee; font-weight:bold;">
                <td colspan="${tempcart.length}" style="text-align:left;">
                  ${groupVal}
                </td>
              </tr>`;
            lastValue = groupVal;
        }

        let row = "<tr>";

        tempcart.forEach(col => {
            let kolom = col[0];
            let tipe  = getColumnType(col);
            let val   = r[kolom] ?? "";

            if (tipe === "float") {
                let num = currencyNormalizer(val);

                if (col[4] === 1) {
                    gsum_colCart["stot" + kolom] += num;
                }

                if (needAverage && kolom === avgCol[0]) {
                    avgSum += num;
                    avgCnt++;
                }

                row += `<td style="text-align:right">
                          ${format_number(num, col[5])}
                        </td>`;
            } else {
                row += `<td>${val}</td>`;
            }
        });

        row += "</tr>";
        html += row;

        prevGroup = groupVal;
    });

    if (needGrouping && data.length > 0) {
        html += doSetRowSubtotal(tempcart);
    }

    if (needAverage && avgCnt > 0) {
        html += doSetRowAverageStandalone(tempcart, avgCol, avgValue);
    }

    if (gsum_isgrandtotal === 1) {
        html += doSetRowGrandtotal(tempcart);
    }

    return html;
  }

  function doSetRowAverageStandalone(_tempcart) {
    let rowTable = '';

    if (!gsum_colCart.__AVG_VALUE__) return '';

    rowTable += '<tr id="avgrow" style="font-weight:bold;">';

    let _counter = 0;
    _tempcart.forEach((itemcart) => {
      if (itemcart[2]) {
        _counter++;

        // kolom nilai rata2
        if (itemcart[0] === gsum_colCart.__AVG_COL__) {
          let _decimal = itemcart[5];
          rowTable += `
            <td style="border-top:1px solid black; text-align:right;">
              ${format_number(gsum_colCart.__AVG_VALUE__, _decimal)}
            </td>
          `;

        // kolom tulisan rata2
        } else if (_counter === gsum_colCart.__AVG_LABEL_POS__) {
          rowTable += `
            <td style="border-top:1px solid black;">
              Rata-rata :
            </td>
          `;

        // kolom lain yg kosong
        } else {
          rowTable += '<td style="border-top:1px solid black;"></td>';
        }
      }
    });

    rowTable += '</tr>';
    return rowTable;
  }

  function getAverageColumn(tempcart) {
    for (let col of tempcart) {
        if (
            col.length >= 7 &&
            col[6] &&
            col[6].avg === true &&
            getColumnType(col) === 'float'
        ) {
            return col;
        }
    }
    return null;
  }

  function doSetRowAverage(tempcart, avgCol, avgValue) {
    let html = '<tr style="font-weight:bold;">';

    let avgIndex = tempcart.findIndex(col => col[0] === avgCol[0]);

    tempcart.forEach((col, idx) => {
        // sebelum kolom Selisih
        if (idx === avgIndex - 1) {
            html += `<td style="text-align:right">Rata-rata</td>`;
        }
        // kolom Selisih
        else if (idx === avgIndex) {
            html += `<td style="text-align:right">
                        ${format_number(avgValue, col[5])}
                     </td>`;
        }
        else {
            html += `<td></td>`;
        }
    });

    html += '</tr>';
    return html;
  }


  function formatAngka(value) {
    if (value === null || value === undefined || value === "" || value === ".00" || value === ".000000") {
        return "0";
    }

    let num = Number(value);

    if (isNaN(num)) {
        return "0";
    }

    // format rp
    return num.toLocaleString("id-ID", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
  }

  function doShowReport (_res, _reportTitle, _groupby, _date1, _date2 = null) {
    let tempcart = gcart_header;
    let _cellcount = 0;
    tempcart.forEach((item, i) => {
      _cellcount += item[2];
    });

    gsum_colFinal = {};
    let rowTable = "";
    gsum_rowSubtotal = 0;

    let needGrouping = (typeof g_needGrouping !== "undefined") ? g_needGrouping : false;

    // TABLE HEADER
    doSetColCart(gcart_header);
    $("#tabel_header").html(doSetRowHeader(_reportTitle, tempcart, _cellcount, _date1, _date2));

    // TABLE DATA
    rowTable = '';
    if (_res.length > 0) {
      if (needGrouping === true && typeof g_groupField !== "undefined" && g_groupField) {
          rowTable = doSetRowTableWithGrouping(_res, tempcart, _groupby);
      } else {
          rowTable = doSetRowTable(_res, tempcart, _groupby);
      }
    } else {
        rowTable += "<tr style='text-align: center'>";
        rowTable += '  <td colspan="' + _cellcount + '" style="border: 1px solid black;">Tidak ada data ditemukan</td>';
        for (let i = 0; i < (_cellcount-1); i++) {
          rowTable += '  <td style="display: none;"></td>';
        }
        rowTable += "</tr>";
    }

    $("#tabel_data").html(rowTable);

    // POSISI TULISAN TOTAL & GRAND TOTAL
    if (_res.length > 0) { doSetPosisiTulisanTotal(_cellcount); }

    doGodown();
  }

  function doSetColCart(_tempcart) {
    gsum_colCart = {};
    _tempcart.forEach((item, i) => {
      if ((item[2] === 1) && (item[4] === 1) && (item[3] == "float" || item[3] == "int")) {
        gsum_colCart["stot"+item[0]] = 0;
        gsum_colCart["gtot"+item[0]] = 0;
      }
    });
  }

  function doSetRowHeader (_reportTitle, _tempcart, _cellcount, _date1, _date2 = null) {
  if ($.isFunction(window.setRowHeader)) { return setRowHeader(); }

  let rowHeader = "";
  let posCount = 0;
  gsum_posArray = [];

  rowHeader += '<tr>';
  rowHeader += '  <th colspan="' + _cellcount + '" style="text-align: left; font-weight: bold;">{!! $akses['program'] !!}<br/> ' + _reportTitle + '</th>';

  rowHeader += '</tr>';

  if (_date1 && _date1 !== 'undefined' && _date1 !== '') {
    rowHeader += '<tr>';
    rowHeader += '  <th colspan="' + _cellcount + '" style="text-align: left; font-weight: bold;">PERIODE: ' +
      format_date(_date1, true) +
      ((_date2 == null || _date2 === '' || _date2 === 'undefined') ? '' : ' S.D ' + format_date(_date2, true)) +
      '</th>';
    rowHeader += '</tr>';
  }

  rowHeader += '<tr>';
  rowHeader += '  <th colspan="' + _cellcount + '"  style="text-align: left; font-weight: bold;">Dicetak Oleh :  ' + '  {!! $akses['user'] !!}  //  Tanggal : '+ getDateIndo() +' // Jam : ' + getTimeNow() + '</th>';
  rowHeader += '</tr>';

  rowHeader += '<tr><th colspan="' + _cellcount + '"></th></tr>';

  rowHeader += '<tr class="tabel_header_kolom">';
  _tempcart.forEach((item, i) => {
    if (item[2]) {
      posCount += 1;
      if (item[0] == "Nomor") {
        rowHeader += '  <th scope="col" style="border: 1px solid black;">No</th>';
      } else {
        rowHeader += '  <th scope="col" style="border: 1px solid black;">' + item[1] + '</th>';
        if ((item[4] === 1) && (item[3] == "float" || item[3] == "int")) { 
          gsum_posArray.push(posCount);
        }
      }
    }
  });
  rowHeader += '</tr>';

  return rowHeader;
}

  function doSetRowTable(_res, _tempcart, _groupby) {
    // jika ada table custom, buatlah function setRowTable di Blade
    if ($.isFunction(window.setRowTable)) { return setRowTable(); }

    let _prevdata = "", _nowdata = "";
    let _countSubtotal = 0;
    let rowTable = "";

    _res.forEach((item, i) => {
      _nowdata = item[_groupby];

      // SUBTOTAL
      if (i != 0 && _prevdata != _nowdata) {
        rowTable += doSetRowSubtotal(_tempcart);
      }

      // ROW
      rowTable += "<tr style='text-align: center'>";
      _tempcart.forEach((itemcart, j) => {
        if (itemcart[2]) {
          if (itemcart[3] == "index") {
            rowTable += '  <td class="cellcompact-left" style="border: 1px solid black;">' + (i+1) + '</td>';
          } else if (itemcart[3] == "date") {
            rowTable += '  <td class="cellcompact-left" style="border: 1px solid black;">' + format_date(item[itemcart[0]]) + '</td>';
          } else if (itemcart[3] == "float") {
            let _value = currencyNormalizer(item[itemcart[0]]);
            let _decimal = itemcart[5];
            if (itemcart[4] === 1) { gsum_colCart["stot"+itemcart[0]] += _value; }
            rowTable += '  <td class="cellcompact-right text-right" style="border: 1px solid black; text-align: right;">' + format_number(_value,_decimal) + '</td>';
          } else {
            rowTable += '  <td class="cellcompact-left" style="border: 1px solid black; white-space:nowrap;">' + nullToEmpty(item[itemcart[0]]) + '</td>';
          }
        }
      });
      rowTable += "</tr>";

      _prevdata = item[_groupby];
    })

    // LAST ROW SUBTOTAL
    rowTable += doSetRowSubtotal(_tempcart);

    // ===== RATA-RATA =====
    if (typeof doSetRowAverageStandalone === 'function') {
      rowTable += doSetRowAverageStandalone(_tempcart);
    }

    // GRAND TOTAL
    if (gsum_isgrandtotal === 1) {
      rowTable += doSetRowGrandtotal(_tempcart);
    }

    return rowTable;
  }

  function doSetRowSubtotal(_tempcart) {
    let rowTable = '';

    if (Object.keys(gsum_colCart).length > 0) {

      let _counter = 0;
      gsum_rowSubtotal++;

      /* ================= TAMPILKAN SUBTOTAL ================= */
      if (gsum_issubtotal === 1) {

        rowTable += '<tr id="strow' + gsum_rowSubtotal + '" style="text-align: center">';

        _tempcart.forEach((itemcart, j) => {
          if (itemcart[2] === 1) {
            _counter++;

            if (itemcart[4] === 1) {
              let _value   = gsum_colCart["stot" + itemcart[0]];
              let _decimal = itemcart[5];

              if (itemcart[3] === "float") {
                rowTable +=
                  '  <td class="st' + _counter + ' cellcompact-left" ' +
                  'style="border-bottom: 1px solid black; border-right-style: hidden; ' +
                  'border-left-style: hidden; background-color:#dee2e6; ' +
                  'font-weight: bold; text-align: right;">' +
                  format_number(_value, _decimal) +
                  '</td>';

              } else if (itemcart[3] === "int") {
                rowTable +=
                  '  <td class="st' + _counter + ' cellcompact-left" ' +
                  'style="border-bottom: 1px solid black; background-color:#dee2e6; ' +
                  'border-right-style: hidden; border-left-style: hidden; ' +
                  'font-weight: bold; text-align: right;">' +
                  _value +
                  '</td>';
              }

            } else {
              rowTable +=
                '  <td class="st' + _counter + ' cellcompact-left" ' +
                'style="border-bottom: 1px solid black; border-right-style: hidden; ' +
                'background-color:#dee2e6; border-left-style: hidden; ' +
                'font-weight: bold; text-align: right;"></td>';
            }
          }
        });

        rowTable += "</tr>";
      }

      /* ================= GRAND TOTAL OTOMATIS ================= */
      // Jika multi grouping ada (lebih dari 1) maka jangan update gtot
      let isMultiGrouping = typeof g_groupingConfig !== "undefined" && Array.isArray(g_groupingConfig) && g_groupingConfig.length > 0;

      if (!isMultiGrouping) {
        // single grouping -> update grand total
        _tempcart.forEach((itemcart, j) => {
          if ((itemcart[4] === 1) && (itemcart[3] === "float" || itemcart[3] === "int")) {
            gsum_colCart["gtot"+itemcart[0]] += gsum_colCart["stot"+itemcart[0]];
          }
        });
      }

      /* ================= RESET SUBTOTAL ================= */
      _tempcart.forEach((itemcart, j) => {
        if ((itemcart[4] === 1) && (itemcart[3] === "float" || itemcart[3] === "int")) {
          gsum_colCart["stot"+itemcart[0]] = 0.00;
        }
      });
    }

    return rowTable;
  }

  function doSetRowGrandtotal(_tempcart) {
    let rowTable = '';
    if (Object.keys(gsum_colCart).length > 0) {

      rowTable += '<tr id="gtrow" class="gtrow" style="text-align: center">';

      let _counter = 0;
      _tempcart.forEach((itemcart, j) => {
        if ((itemcart[2])) {
          _counter++;
          if ((itemcart[4] === 1)) {
            let _value = gsum_colCart["gtot"+itemcart[0]];
            let _decimal = itemcart[5];

            if (itemcart[3] == "float") {
              rowTable += '  <td id="gt' + _counter + '" style="border-bottom: 1px solid black; border-right-style: hidden; border-left-style: hidden; font-weight: bold; text-align: right;">' + format_number(_value,_decimal) + '</td>';
            } else if (itemcart[3] == "int") {
              rowTable += '  <td id="gt' + _counter + '" style="border-bottom: 1px solid black; border-right-style: hidden; border-left-style: hidden; font-weight: bold; text-align: right;">' + _value + '</td>';
            } 
          } else {
            rowTable += '  <td id="gt' + _counter + '" style="border-bottom: 1px solid black; border-right-style: hidden; border-left-style: hidden; font-weight: bold; text-align: right;"></td>';
          }
        }
      });
      rowTable += "</tr>";
    }

    return rowTable;
  }


  function doSetRowGrandTotalFinal(_tempcart) {
    let rowTable = '';
    if (Object.keys(gsum_colFinal).length === 0) return rowTable;

    rowTable += `
      <tr class="finalgtrow"
          style="text-align:right;background:#cfe2ff;font-weight:bold">
    `;

    let counter = 0;
    _tempcart.forEach(col => {
      if (col[2]) {
        counter++;

        if (col[4] === 1) {
          let val = gsum_colFinal["ftot" + col[0]];
          let dec = col[5];

          rowTable += `
            <td class="fg${counter}" style="text-align:right">
              ${col[3] === "float" ? format_number(val, dec) : val}
            </td>`;
        } else {
          rowTable += `<td class="fg${counter}"></td>`;
        }
      }
    });

    rowTable += "</tr>";
    return rowTable;
  }

  function doSetPosisiTulisanTotal(_cellcount) {

    let posStrTotal = (gsum_posArray.length > 0)
        ? Math.min(...gsum_posArray) - 2
        : 0;

    if (posStrTotal < 0) return;

    /* ================= SUBTOTAL ================= */
    if (gsum_issubtotal === 1) {

      if (posStrTotal === 0) {
        $(".st1").html("Total :");

      } else if (posStrTotal === (_cellcount - 1)) {
        $(".st" + posStrTotal).html("Total :");

      } else {
        for (let i = 0; i < gsum_rowSubtotal; i++) {
          let _row = document.getElementById("strow" + (i + 1));
          if (_row) _row.deleteCell(posStrTotal);
        }

        $(".st" + posStrTotal)
          .attr("colspan", 2)
          .html("Total :");
      }
    }

    /* ================= GRAND TOTAL ================= */
    if (gsum_isgrandtotal === 1) {

      if (posStrTotal === 0) {

        $(".gtrow").each(function () {
          $(this).find("#gt1").html("Grand Total :");
        });

      } else if (posStrTotal === (_cellcount - 1)) {

        $(".gtrow").each(function () {
          $(this)
            .find("#gt" + posStrTotal)
            .html("Grand Total :");
        });
      } else {

        $(".gtrow").each(function () {
          let _row = this;

          _row.deleteCell(posStrTotal);

          $(_row)
            .find("#gt" + posStrTotal)
            .attr("colspan", 2)
            .html("Grand Total :");
        });
      }
    }

    /* ================= FINAL GRAND TOTAL ================= */
    if (gsum_isfinalgrandtotal === 1) {

      if (posStrTotal === 0) {
        $(".finalgtrow").each(function () {
          $(this).find(".fg1").html("Grand Total Final :");
        });
      } else {
        $(".finalgtrow").each(function () {
          this.deleteCell(posStrTotal);
          $(this)
            .find(".fg" + posStrTotal)
            .attr("colspan", 2)
            .html("Grand Total Final :");
        });
      }
    }

  }

  /* ==============   END OF MAIN REPORT    ============== */



  /* ==============    START FILTER DATA    ============== */

  function doShowFormFilterData() {
    g_modeModal = gmodal_filterdata;

    makeTable("FILTER");
    doShowFilter();
    $("#tabelfilter_totalrow").html("");

    $("#formFilterData").modal('toggle');
  }

  function doCloseFormFilterData() {
    g_modeModal = "";
    gfilter_lastrow = -1;
    gfilter_totalrow = 0;
    $("#formFilterData").modal('toggle');
  }

  function doShowFilter() {
    $('#tabelfilter').DataTable().destroy();

    // PERSIAPKAN KOLOM TABLE FILTER
    let _kolom = getKolomFilter();
    let cart_filterHeader = [];
    _kolom.forEach((item) => {
      let _match = gcart_header.find((itemcart) => item == itemcart[0]);
      if (_match) {
        cart_filterHeader.push(_match);
      }
    });    

    // HEADER TABLE FILTER
    let _str = '<tr>';
    if (cart_filterHeader.length > 0) {
      cart_filterHeader.forEach((item, i) => {
        _str += '<th style="text-align: center;">' + item[1] + '</th>';
      });
    }
    _str += '</tr>';
    $("#tabelfilter_header").html(_str);

    // DATA TABLE FILTER
    _str = "";
    let _prevdata = "", _nowdata = "", _idx = -1;
    gcart_filterShow = [];
    if (gcart_filter.length > 0) {
      gcart_filter.forEach((item, i) => {
        _nowdata = item[cart_filterHeader[0][0]];

        if (_prevdata != _nowdata) {
          _idx += 1;
          item._idx = _idx;
          _str += '<tr id="' + _idx + '-trrowfilter" draggable="true" onclick="doSelectrowfilter(' + _idx + ')">';
          cart_filterHeader.forEach((itemcart, j) => {
            if (itemcart[3] == "index") {
              _str += "  <td>" + (_idx+1) + "</td>";
            } else if (itemcart[3] == "date") {
              _str += '  <td>' + format_date(item[itemcart[0]]) + '</td>';
            } else if (itemcart[3] == "float") {
              let _value = currencyNormalizer(item[itemcart[0]]);
              let _decimal = itemcart[5];
              _str += '  <td>' + format_number(_value,_decimal) + '</td>';
            } else {
              _str += '  <td>' + item[itemcart[0]] + '</td>';
            }
          });
          _str += '</tr>';

          let temp = [];
          temp.push(_idx);  // index
          temp.push(false); // selected or not
          gcart_filterShow.push(temp);
        } else {
          item._idx = _idx;
        }

        _prevdata = _nowdata;
      });
    } else {
        _str += '<tr>';
        _str += '  <td colspan="2" class="text-center">Tidak ada transaksi ditemukan.</td>';
        _str += '  <td style="display: none;"></td>';
        _str += '</tr>';
    }

    $("#tabelfilter_data").html(_str);

    $("#tabelfilter").DataTable({
      "lengthChange": false,
      "paging": false,
    });
  }

  function doSelectrowfilter(_row) {
    let _row_start, _row_end;

    if (!event.shiftKey) {
      _row_start = _row;
      _row_end = _row;
    } else {
      if (_row > gfilter_lastrow) {
        _row_start = gfilter_lastrow + 1;
        _row_end = _row;
      } else if (_row < gfilter_lastrow) {
        _row_start = _row;
        _row_end = gfilter_lastrow - 1;
      } else {
        _row_start = _row;
        _row_end = _row;
      }
    }

    while (_row_start <= _row_end) {
      if (gcart_filterShow[_row_start][1]) {
        // unselect
        $("#"+_row_start+"-trrowfilter").css('background-color', '');
        $("#"+_row_start+"-trrowfilter").css('color', '');
        gfilter_totalrow -= 1;
      } else {
        // select
        $("#"+_row_start+"-trrowfilter").css('background-color', '#0069d9');
        $("#"+_row_start+"-trrowfilter").css('color', 'white');
        gfilter_totalrow += 1;
      }

      gcart_filterShow[_row_start][1] = !gcart_filterShow[_row_start][1];
      _row_start++;
    }

    gfilter_lastrow = _row;
    $("#tabelfilter_totalrow").html("Jumlah baris yang dipilih: " + gfilter_totalrow);
  }

  function doShowReportFilter() {
    let _res = [];
    gcart_filterShow.forEach((item) => {
      if (item[1]) {
        gcart_filter.filter(itemcart => itemcart._idx === item[0])
               .forEach(filteredItem => _res.push(filteredItem));
      }
    });

    doShowReport(_res, gfilter_title, gfilter_groupby, gfilter_date1, gfilter_date2);
    alertify.success("Report ditampilkan");

    doCloseFormFilterData();
  }

  /* ==============   END OF FILTER DATA    ============== */



  // Export Excel (.xlsx) & CSV — logika bersama ada di public/js/report-export.js
  function doExportTableToExcel(tableID, filename = '') {
    ReportExport.toXlsx(tableID || 'tabel', { baseName: @json($akses['xlsfilename']), customName: filename || gxls_filename });
  }

  function doExportTableToCsv(tableID, filename = '') {
    ReportExport.toCsv(tableID || 'tabel', { baseName: @json($akses['xlsfilename']), customName: filename || gxls_filename });
  }

</script>




@endsection
