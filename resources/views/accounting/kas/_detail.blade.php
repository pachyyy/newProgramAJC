  <div id="page3" style="display: none" class="mainpage container-fluid">

      <div class="row" style="margin-top: 0px" id="contentContainer">
          <div class="col-8 text-left">
              {{-- <h2 class="showhidepage3 page3detail">Detail Kas</h2>
              <h2 class="showhidepage3 page3otorisasi">Otorisasi Kas</h2> --}}
          </div>
          <div class="col-4 text-right">
              <button type="button" class="btn btn-danger btn-action-danger btn-pill-primary"
                  onclick="buttonCloseForm()">CLOSE</button>
          </div>
      </div>

      <div id= "" class="">
          <div id="formBsGrid" class="">
              <div class="">
                  <div class="container-fluid">
                      <div class="row">
                          <div class="col-12 col-lg-3">
                              <div class="row">
                                  <div class="col-md-5">
                                      <div class="form-group">
                                          <label>Transaksi</label>
                                      </div>
                                  </div>

                                  <div class="col-md-7">
                                      <select id="input_detail_transaksi" class="form-control form-select-lg mb-3"
                                          aria-label=".form-select-lg example" disabled>
                                          <option value='BKK' selected>BKK</option>
                                          <option value='BKM'>BKM</option>
                                      </select>
                                  </div>
                              </div>

                              <div class="row kas-row-tight">
                                  <div class="col-md-5">
                                      <div class="form-group">
                                          <label>Kas</label>
                                      </div>
                                  </div>
                                  <div class="col-md-7">
                                      <div class="form-group input-group">
                                          <input type="hidden" class="form-control" id="input_detail_simbol"
                                              placeholder="" disabled>
                                          <input type="text" class="form-control" id="input_detail_kodeperkiraan"
                                              placeholder="" disabled>
                                          <!-- <button class="btn btn-primary btn-sm text-right" id="buttonAddListPerkiraan" onclick="buttonAddListPerkiraan()"><i class="bi bi-plus"></i></button> -->
                                      </div>
                                  </div>
                                  <div class="col-md-12" style="margin-top:-10px">
                                      <div class="form-group">
                                          <textarea style="width: 100%; resize: none" rows=1 class="form-control" id="input_detail_keteranganperkiraan" disabled></textarea>
                                      </div>
                                  </div>
                              </div>
                          </div>
                          <div class="col-12 col-lg-6">
                              <div class="row">
                                  <div class="col-md-6">
                                      <div class="row">
                                          <div class="col-md-12">
                                              <div class="row">
                                                  <div class="col-md-4">
                                                      <div class="form-group">
                                                          <label>No Bukti</label>
                                                      </div>
                                                  </div>
                                                  <div class="col-md-8">
                                                      <div class="form-group">
                                                          <input type="text" class="form-control"
                                                              id="input_detail_nobukti" placeholder="No Bukti" disabled>
                                                      </div>
                                                  </div>
                                              </div>
                                          </div>
                                      </div>
                                  </div>
                                  <div class="col-md-6">
                                      <div class="row">
                                          <div class="col-md-12">
                                              <div class="row">
                                                  <div class="col-md-4">
                                                      <div class="form-group">
                                                          <label>Tgl</label>
                                                      </div>
                                                  </div>
                                                  <div class="col-md-8">
                                                      <div class="form-group">
                                                          <input type="date" class="form-control text-center"
                                                              id="input_detail_tanggal" value="{!! date('Y-m-d') !!}"
                                                              disabled>
                                                      </div>
                                                  </div>
                                              </div>
                                          </div>
                                      </div>
                                  </div>
                              </div>

                              <div class="row kas-row-tight">
                                  <div class="col-md-12">
                                      <div class="row">
                                          <div class="col-md-2">
                                              <div class="form-group">
                                                  <label class="partBKK showhidePart">Kepada</label>
                                                  <label class="partBKM showhidePart">Terima</label>
                                              </div>
                                          </div>
                                          <div class="col-md-6">
                                              <div class="form-group">
                                                  <input type="text" class="form-control"
                                                      id="input_detail_kepadaterima" placeholder="" disabled>
                                              </div>
                                          </div>
                                      </div>
                                  </div>
                              </div>

                              <div class="row kas-row-tight">
                                  <div class="col-md-12">
                                      <div class="row">
                                          <div class="col-md-2">
                                              <div class="form-group">
                                                  <label class="partBKK showhidePart">Bon</label>
                                              </div>
                                          </div>
                                          <div class="col-md-4">
                                              <div class="form-group">
                                                  <input type="text" class="form-control partBKK showhidePart"
                                                      id="input_detail_bon" placeholder="" disabled>
                                              </div>
                                          </div>
                                          <div class="col-md-2">
                                              <div class="form-group">
                                                  <label class="partBKK showhidePart">Nilai Bon</label>
                                              </div>
                                          </div>
                                          <div class="col-md-4">
                                              <div class="form-group">
                                                  <input type="text"
                                                      class="form-control text-right partBKK showhidePart"
                                                      id="input_detail_nilaibon" value="0.00" disabled>
                                              </div>
                                          </div>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>

                  <div class="container-fluid">
                      <hr />
                  </div>

                  <div class="container-fluid mt-4" style="overflow-x: auto; padding:0; margin:0;">
                      <div class="dph-table-outer">
                          <div class="dph-table-wrap">
                              <table id="detailTable" class="dph-tb">
                                  <thead>
                                      <tr>
                                          <th scope="col">Devisi</th>
                                          <th scope="col">Perk.</th>
                                          <th scope="col">Ket. Perk</th>
                                          <th scope="col">Lawan</th>
                                          <th scope="col">Ket. Lawan</th>
                                          <th scope="col" class="num">Jumlah</th>
                                          <th scope="col">Keterangan</th>
                                          <th scope="col">Costing</th>
                                          <th scope="col">Sub Cost</th>
                                      </tr>
                                  </thead>

                                  <tbody id="detailTableData" class="">
                                      <tr>
                                          <td colspan="8" class="text-center">Belum ada data</td>
                                      </tr>
                                  </tbody>
                              </table>
                          </div>
                      </div>
                  </div>

                  <div class="col-md-12 mt-2 text-right">

                  </div>

                  <div class="col-md-12 mt-2 text-right showhidepage3 page3otorisasi" id="contentContainer">
                      <button id="buttonOtorisasi" type="button"
                          class="btn btn-primary btn-action-primary btn-pill-primary"
                          onclick="submitOtorisasi()">Otorisasi</button>
                  </div>
              </div>
          </div>
      </div>
  </div>
  </div>
  </div>
