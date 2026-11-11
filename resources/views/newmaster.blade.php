{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html dir="ltr" lang="en-US">
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="author" content="SemiColonWeb" />

  {{-- PWA (notifikasi push permintaan otorisasi) - lihat public/js/otorisasi-push.js --}}
  <link rel="manifest" href="{!! URL::asset('manifest.webmanifest') !!}" />
  <meta name="theme-color" content="#2563eb" />
  <link rel="apple-touch-icon" href="{!! URL::asset('img/pwa-192.png') !!}" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-title" content="AJC" />
  <link rel="stylesheet" href="{!! URL::asset('css/otorisasi-push.css') !!}?v={{ @filemtime(base_path('public/css/otorisasi-push.css')) ?: '1' }}" />
  <link rel="stylesheet" href="{!! URL::asset('css/header-mobile.css') !!}?v={{ @filemtime(base_path('public/css/header-mobile.css')) ?: '1' }}" />

  {{-- Fonts --}}
  <link href="https://fonts.googleapis.com/css?family=Lato:300,400,400i,700|Poppins:300,400,500,600,700|PT+Serif:400,400i&display=swap"
        rel="stylesheet" type="text/css" />

  {{-- Vendor CSS --}}
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/jquery.dataTables.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

  {{-- <link rel="stylesheet" href="{{ asset('css/semantic.css') }}">
  <link rel="stylesheet" href="{{ asset('css/select2.min.css') }}">
  <link rel="stylesheet" href="{{ asset('css/datatables.min.css') }}">
  <link rel="stylesheet" href="{{ asset('css/jquery-ui.min.css') }}">
  <link rel="stylesheet" href="{{ asset('css/canvas/bootstrap.css') }}">
  <link rel="stylesheet" href="{{ asset('css/canvas/style.css') }}">
  <link rel="stylesheet" href="{{ asset('css/canvas/dark.css') }}">
  <link rel="stylesheet" href="{{ asset('css/canvas/font-icons.css') }}">
  <link rel="stylesheet" href="{{ asset('css/canvas/animate.css') }}">
  <link rel="stylesheet" href="{{ asset('css/canvas/magnific-popup.css') }}">
  <link rel="stylesheet" href="{{ asset('css/canvas/custom.css') }}">
  <link rel="stylesheet" href="{{ asset('css/alertify.css') }}"> --}}

  <link rel="stylesheet" href="{!! URL::asset('css/newmaster.css') !!}?v={{ filemtime(base_path('public/css/newmaster.css')) ?: '1' }}">
  <link rel="stylesheet" href="{!! URL::asset('css/tableMaster.css') !!}">

  <title>AnekaJC</title>


<style>

  .report-card-has-sub {
  position: relative;
  padding-right: 34px;
  }

  .report-card-arrow {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 14px;
    height: 14px;
    opacity: 0.5;
  }
    .hover-tooltip {
    position: relative;
  }

  .hover-tooltip::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 125%;
    left: 50%;
    transform: translateX(-50%);
    background-color: black;
    color: white;
    padding: 6px 8px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s, visibility 0.3s;
    z-index: 1000;
    pointer-events: none;
  }

  .hover-tooltip::before {
    content: '';
    position: absolute;
    bottom: 115%;
    left: 50%;
    transform: translateX(-50%);
    border: 5px solid transparent;
    border-top-color: black;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s, visibility 0.3s;
    z-index: 1000;
    pointer-events: none;
  }

  .hover-tooltip:hover::after,
  .hover-tooltip:hover::before {
    opacity: 1;
    visibility: visible;
  }
  </style>

    {{-- Submenu flyout styles (L0=2 hover popout) --}}
    <style>

  /* newmaster.css (the shared design) still has the CSS-hover behavior this
     project deliberately removed elsewhere — .nav-group:hover>.nav-item
     .nav-chevron and .nav-group:hover .nav-children (moving the cursor down
     the collapsed rail flashes every folder open in turn). Its hover
     selectors have HIGHER specificity than a plain override (`:hover` counts
     as a class), so `!important` is required, not just later-in-source-wins.
     Direct-child selectors so nesting can't leak .open state (see
     docs/sidebar-navigation-migration.md §6 and §8.5). This keeps expansion
     driven only by a click on the label/chevron (toggleModuleSubmenu), while
     the icon's own onclick (showModuleHome) still navigates as before. */
  .nav-group > .nav-item .nav-chevron {
    transform: none !important;
  }

  .nav-group.open > .nav-item .nav-chevron {
    transform: rotate(90deg) !important;
  }

  .nav-children {
    max-height: 0 !important;
  }

  .sidebar:hover .nav-group.open > .nav-children {
    max-height: 2000px !important;
  }

  .sidebar-footer {
    margin-top: auto;
    border-top: 1px solid rgba(255,255,255,0.08);
    padding-top: 4px;
  }

  .nav-report-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    cursor: pointer;
    color: rgba(255,255,255,0.85);
    transition: background 0.12s ease;
  }

  .nav-report-item:hover,
  .nav-report-item.active {
    background: rgba(255,255,255,0.08);
  }

  .nav-report-item .nav-icon {
    display: flex;
    width: 18px;
    height: 18px;
  }

  /* Report page layout (prototype) */
  .report-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: none;
    border: none;
    color: #ff0000;
    font-size: 14px;
    cursor: pointer;
    margin-bottom: 16px;
    padding: 0;
    transition: color 0.4s ease
  }

  .report-back-btn:hover {
    color: #730202;
  }

  .report-back-btn svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
  }

  .report-category {
    margin-bottom: 28px;
  }

  .report-category-title {
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #888;
    margin-bottom: 10px;
  }

  .report-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 14px;
  }

  .report-card {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #fff;
    border: 1px solid rgba(0,0,0,0.08);
    border-radius: 8px;
    padding: 14px;
    cursor: pointer;
    transition: box-shadow 0.12s ease, transform 0.12s ease;
  }

  .report-card:hover {
    box-shadow: 0 4px 14px rgba(0,0,0,0.08);
    transform: translateY(-1px);
  }

  .report-card-icon {
    width: 34px;
    height: 34px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    background: rgba(0,0,0,0.04);
  }

  .report-card-label {
    font-size: 14px;
    font-weight: 500;
    color: #222;
  }

    #sidebar.flyout-pinned {
      width: var(--sidebar-exp, 240px) !important;
    }
    #sidebar.flyout-pinned .logo-text,
    #sidebar.flyout-pinned .nav-label,
    #sidebar.flyout-pinned .nav-chevron {
      opacity: 1 !important;
    }

    #sidebar.flyout-pinned .nav-group.flyout-owner .nav-children {
      max-height: 600px !important;
    }

    #sidebar.flyout-pinned .nav-group.flyout-owner > .nav-item .nav-chevron {
      transform: rotate(90deg);
      color: rgba(255, 255, 255, 0.6);
    }

    .nav-child.has-sub {
      justify-content: flex-start; /* was space-between */
    }

    .nav-child.has-sub .nav-child-label {
      flex: 1;
      text-align: left;
    }

    .nav-child.has-sub .nav-child-arrow {
      margin-left: auto;
    }

    .nav-child-arrow {
      width: 14px;
      height: 14px;
      flex-shrink: 0;
      opacity: 0.6;
      transition: transform 0.15s ease, opacity 0.15s ease;
    }

    .nav-child.has-sub:hover .nav-child-arrow {
      opacity: 1;
    }

    .nav-flyout {
      position: fixed !important;
      min-width: 200px;
      max-width: 280px;
      background: #fff;
      border: 1px solid rgba(0, 0, 0, 0.08);
      border-radius: 8px;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
      padding: 6px;
      z-index: 9999 !important;
      margin: 0;

      /* hidden by default; JS toggles a .flyout-visible class on hover */
      opacity: 0;
      visibility: hidden;
      transform: translateX(-4px);
      transition: opacity 0.12s ease, transform 0.12s ease, visibility 0.12s ease;
      pointer-events: none;
    }

    .nav-flyout.flyout-visible {
      opacity: 1;
      visibility: visible;
      transform: translateX(0);
      pointer-events: auto;
    }

    .nav-flyout-item {
      padding: 8px 12px;
      border-radius: 6px;
      font-size: 13px;
      white-space: nowrap;
      color: #333;
      cursor: pointer;
      transition: background 0.12s ease;
    }

    .nav-flyout-item:hover {
      background: rgba(0, 0, 0, 0.06);
    }
  </style>

  @yield('css')
</head>

<body>

  {{-- SIDEBAR --}}
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo" onclick="goHome()" style="cursor:pointer;">
      {{-- Ikon sidebar memakai Logo NPWP 1 dari menu Set Nomor Transaksi
           (storage/app/gambar-perusahaan/logoperusahaan/npwp1.*). Belum ada logo = kotak "SPL" lama. --}}
      @php
        $fileLogoSidebar = \App\Http\Controllers\Berkas\SetNomorTransaksiController::fileLogoSidebar();
      @endphp
      @if ($fileLogoSidebar)
        <div class="logo-icon" style="background:#fff; overflow:hidden;">
          <img src="{{ url('setnomortransaksilogo') }}?v={{ @filemtime($fileLogoSidebar) }}" alt="Logo" style="width:100%; height:100%; object-fit:contain;">
        </div>
      @else
        <div class="logo-icon">SPL</div>
      @endif
      {{-- Nama di sidebar mengikuti Inisial Perusahaan di menu Set Nomor Transaksi (DBNOMOR.ALIAS),
           bukan hardcode lagi. Kalau query gagal, tetap tampil teks lama supaya layout tidak error. --}}
      @php
        try {
          $inisialPerusahaan = trim((string) (\DB::connection('SML')->select('select top 1 ALIAS from DBNOMOR')[0]->ALIAS ?? ''));
        } catch (\Throwable $e) {
          $inisialPerusahaan = '';
        }
      @endphp
      <span class="logo-text">{{ $inisialPerusahaan !== '' ? $inisialPerusahaan : 'PT. SPL' }}</span>
    </div>
    <nav class="sidebar-nav" id="nav"></nav>

    <div class="sidebar-footer" id="sidebar-footer">
      <!-- filled in by JS, see renderSidebarFooter() below -->
    </div>
  </aside>

  {{-- MAIN --}}
  <div class="main">

    <header class="header">
      <div class='titleText' id="breadcrumb"></div>

      <div class="header-right">
        <button type="button" id="otoPushToggle" class="oto-bell" title="Aktifkan notifikasi otorisasi di perangkat ini" style="display:none">
          <i class="bi bi-bell-slash"></i>
        </button>
        <a href="{{ url('otorisasiinbox') }}" class="oto-bell" title="Permintaan otorisasi">
          <i class="bi bi-bell"></i>
          <span id="otoBellCount" class="oto-bell-count" style="display:none">0</span>
        </a>
        @php
          $namaBulanHeader = [
              1 => 'Januari',
              2 => 'Februari',
              3 => 'Maret',
              4 => 'April',
              5 => 'Mei',
              6 => 'Juni',
              7 => 'Juli',
              8 => 'Agustus',
              9 => 'September',
              10 => 'Oktober',
              11 => 'November',
              12 => 'Desember',
          ][(int) $periode->bulan] ?? '';
        @endphp
        {{-- .pb-full (layar lebar) / .pb-short (mobile < 768px) - lihat css/header-mobile.css.
             berkas/newsetupperiodekerja.blade.php menulis ulang isi badge ini dengan pola yang sama. --}}
        <div class="period-badge" id="period-badge"><span class="pb-full">Username: {{ Auth::user()->username }}
          &nbsp;–&nbsp;
          Periode: {{ $namaBulanHeader }}
          {{ $periode->tahun }}</span><span class="pb-short">{{ mb_substr($namaBulanHeader, 0, 3) }} {{ $periode->tahun }}</span></div>
        <div class="avatar">{{ strtoupper(substr(Auth::user()->username, 0, 1)) }}</div>
        <a class="logout-link" href="{{ route('logout') }}" title="Log Out">
          <i class="bi bi-power"></i><span class="logout-text"> Log Out</span>
        </a>
      </div>
    </header>

<section class="content">
  <div id="content-dynamic" class="nm-ui" style="display:none;"></div>
  <div id="content-report" class="nm-ui" style="display:none;"></div>
  <div id="content-blade">
    @if (request()->routeIs('home'))
      {{-- Ringkasan permintaan otorisasi - HANYA mobile (< 768px, lihat css/otorisasi-push.css);
           di layar lebar cukup lonceng header. Tujuan klik sama dengan lonceng header. --}}
      <div class="oto-home" id="otoHome">
        <a class="oto-home-main" href="{{ url('otorisasiinbox') }}">
          <div class="oto-home-head">
            <span class="oto-home-label"><i class="bi bi-shield-check"></i>Otorisasi</span>
            <span class="oto-home-time" id="otoHomeTime"></span>
          </div>
          <div class="oto-home-stats" id="otoHomeStats">
            <div class="oto-home-stat is-pending"><b id="otoHomePending">–</b><span>Menunggu persetujuan Anda</span></div>
            <div class="oto-home-stat is-hasil"><b id="otoHomeHasil">–</b><span>Hasil permintaan baru</span></div>
          </div>
          <div class="oto-home-clear" id="otoHomeClear" style="display:none">
            <i class="bi bi-check2-circle"></i>
            <div>Tidak ada permintaan otorisasi<small>Semua sudah ditindaklanjuti</small></div>
          </div>
        </a>
        <div class="oto-home-enable" id="otoHomeEnable" style="display:none">
          <i class="bi bi-bell"></i>
          <span id="otoHomeEnableText"></span>
          <button type="button" id="otoHomeEnableBtn" style="display:none">Aktifkan</button>
        </div>
        <a class="oto-home-cta" href="{{ url('otorisasiinbox') }}">
          <span>Buka kotak otorisasi</span><i class="bi bi-arrow-right"></i>
        </a>
      </div>
    @endif
    <div class="container-fluid clearfix">
      <div class="row gutter-40 col-mb-80">
        @yield('content')
      </div>
    </div>
  </div>
</section>

<!-- start modal select modal open ( 1 modal buat beberapa fungsi, jadi tinggal inject data ) -->
      <div class="modal fade" id="formModalOpen" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 1200px">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="namaModalOpen"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <table id="tabelModalOpen" class="table table-bordered table-striped">
                <thead id='theadOpen' class="text-center bg-primary text-white">
                  <tr></tr>
                </thead>
                <tbody id="tabel_dataModalOpen" class="text-left">
                  <tr></tr>
                </tbody>
              </table>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            </div>
          </div>
        </div>
      </div>
      <!-- End modal select modal open ( 1 modal buat beberapa fungsi, jadi tinggal inject data )-->

  </div>

  {{-- SCRIPTS — jQuery loaded once, then vendors --}}<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<!-- Select2 (needs CSS too) -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Popper -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.8/umd/popper.min.js"></script>

<!-- Bootstrap -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.min.js"></script>

<script>
  // Bootstrap 5 dropped jQuery entirely, so it never registers the old $.fn.modal
  // plugin -- but this app's pages (so.blade.php, suratjalan.blade.php,
  // penawaranso.blade.php, and others using this layout) still open every picker/
  // filter modal the Bootstrap-4 jQuery-plugin way: $('#id').modal('show'|'hide'|
  // 'toggle') or $('#id').modal({backdrop:'static', ...}). Without this shim those
  // calls throw "$(...).modal is not a function" and silently abort (no visible
  // error since this layout's own keydown handler below blocks F12/Ctrl+Shift+I),
  // which is why picker modals stopped opening. Event bindings like
  // .on('shown.bs.modal', fn) don't need a shim -- Bootstrap 5 already dispatches
  // those as real DOM events that bubble, which jQuery's .on() picks up natively;
  // only the .modal(command) invocation itself needs bridging to bootstrap.Modal.
  if (window.jQuery && window.bootstrap && !jQuery.fn.modal) {
    jQuery.fn.modal = function (option) {
      return this.each(function () {
        var el = this;
        var instance = bootstrap.Modal.getOrCreateInstance(el, typeof option === 'object' ? option : {});
        if (typeof option !== 'string') { return; }

        var wasShown = el.classList.contains('show');
        instance[option]();

        // Bootstrap 5's show()/hide() set their internal "shown" state (and fire
        // data-bs-dismiss/Esc/backdrop-click wiring) synchronously right away --
        // that part always works. But the actual DOM reveal/hide only happens
        // inside a callback deferred until a CSS transitionend fires on the
        // backdrop/dialog. If that event never fires (any CSS on the page nudging
        // the fade transition can cause this), the callback -- and the visible
        // change -- never runs: the backdrop still appears (dimmed page) but the
        // dialog itself stays invisible, with no error. Force the DOM state here
        // instead of waiting on that event; idempotent and harmless if Bootstrap's
        // own callback already did it first.
        if (option === 'show' || (option === 'toggle' && !wasShown)) {
          el.style.display = 'block';
          el.removeAttribute('aria-hidden');
          el.setAttribute('aria-modal', 'true');
          void el.offsetHeight; // force reflow so .show still transitions in
          el.classList.add('show');
        } else if (option === 'hide' || (option === 'toggle' && wasShown)) {
          el.classList.remove('show');
          setTimeout(function () {
            el.style.display = 'none';
            el.setAttribute('aria-hidden', 'true');
            el.removeAttribute('aria-modal');
          }, 200);
        }
      });
    };
  }
</script>

<!-- Alertify (needs CSS too) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/alertifyjs@1.14.0/build/css/alertify.min.css"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/alertifyjs@1.14.0/build/css/themes/default.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/alertifyjs@1.14.0/build/alertify.min.js"></script>

<!-- AutoNumeric -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/autonumeric/4.6.0/autoNumeric.min.js"></script>

<!-- DataTables (needs CSS too) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/css/jquery.dataTables.min.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/jquery.dataTables.min.js"></script>

<!-- jQuery UI -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>

<!-- QRCode -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<!-- JsBarcode -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>

<!-- Sidebar/card icon dictionary (icons object + icon() helper) -->
<script src="{!! URL::asset('js/sidebar-icons.js') !!}?v={{ filemtime(public_path('js/sidebar-icons.js')) ?: '1' }}"></script>

 <script>
  // ── Keyboard shortcut lockdown ────────────────────────────────────────────
  document.addEventListener('keydown', function (e) {
    if (e.key === 'F12') { e.preventDefault(); return false; }
    if (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'J')) { e.preventDefault(); return false; }
    if (e.ctrlKey && e.key === 'u') { e.preventDefault(); return false; }
  });

  function round(value, precision) {
    const multiplier = Math.pow(10, precision || 0);
    return Math.round(value * multiplier) / multiplier;
  }

  function format_date(date) {
    if (!date) return '';
    const [y, m, d] = date.split('-');
    return `${d}/${m}/${y}`;
  }

  function formatNumber(input) {
      let value = input.value.replace(/[^\d.]/g, '');
      let parts = value.split('.');
      parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      input.value = parts.join('.');
  }

  // Like formatNumber, but for display-only contexts (table cells, template
  // strings) rather than live <input> elements: takes a raw value and
  // returns a comma-formatted string instead of mutating input.value.
  function formatNumberDisplay(value) {
      if (value === null || value === undefined || value === '') return '';
      let parts = String(value).replace(/[^\d.]/g, '').split('.');
      parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      return parts.join('.');
  }

  function format_timestamp(date) {
    if (!date) return '';
    const [tgl, waktu] = date.split(' ');
    const [y, m, d] = tgl.split('-');
    return `${d}/${m}/${y} ${waktu}`;
  }

  // ── Icon SVGs ────────────────────────────────────────────────────────
  // Moved to public/js/sidebar-icons.js (loaded above, before this block) --
  // the `icons` object and `icon(name)` helper are globals from that file.

  // ── Color palette cycling for cards ─────────────────────────────────
  const cardColors = ['c-blue','c-green','c-orange','c-purple','c-teal','c-pink','c-yellow','c-red','c-indigo','c-cyan'];

  // ── Per-child icon mapping (by partial label keyword) ────────────────
  const childIconMap = [
    ['valas',         'dollar'],
    ['devisi',        'layers'],
    ['perkiraan',     'layers'],
    ['aktiva',        'package'],
    ['hutang',        'credit-card'],
    ['piutang',       'credit-card'],
    ['giro',          'repeat'],
    ['laba',          'trending-up'],
    ['neraca',        'bar-chart'],
    ['costing',       'sliders'],
    ['posting',       'send'],
    ['supplier',      'truck'],
    ['gudang',        'warehouse'],
    ['group',         'grid'],
    ['merk',          'tag'],
    ['bahan',         'package'],
    ['barang',        'box'],
    ['jasa',          'clipboard'],
    ['lokasi',        'map-pin'],
    ['satuan',        'sliders'],
    ['area',          'map-pin'],
    ['kota',          'map-pin'],
    ['customer',      'users'],
    ['sales',         'trending-up'],
    ['expedisi',      'truck'],
    ['departemen',    'grid'],
    ['jabatan',       'layers'],
    ['karyawan',      'users'],
    ['biaya',         'dollar'],
    ['pajak',         'percent'],
    ['kendaraan',     'truck'],
    ['sopir',         'truck'],
    ['periode',       'settings'],
    ['kunci',         'lock'],
    ['nomor',         'settings'],
    ['pemakai',       'users'],
    ['password',      'lock'],
    ['kalkulator',    'sliders'],
    ['log',           'file-text'],
    ['jurnal',        'file-text'],
    ['kas',           'dollar'],
    ['bank',          'credit-card'],
    ['bon',           'clipboard'],
    ['memorial',      'file-text'],
    ['koreksi',       'rotate-ccw'],
    ['pelunasan',     'check-square'],
    ['permintaan',    'clipboard'],
    ['penerimaan',    'package'],
    ['inspeksi',      'check-square'],
    ['invoice',       'file-text'],
    ['retur',         'rotate-ccw'],
    ['debet',         'dollar'],
    ['penawaran',     'tag'],
    ['verifikasi',    'check-square'],
    ['uang muka',     'dollar'],
    ['surat jalan',   'send'],
    ['closing',       'lock'],
    ['performance',   'trending-up'],
    ['opname',        'check-square'],
    ['transfer',      'repeat'],
    ['sample',        'package'],
    ['konsinyasi',    'package'],
    ['kasir',         'dollar'],
    ['laporan',       'bar-chart'],
    ['dashboard',     'bar-chart'],
    ['hitung',        'sliders'],
    ['proses',        'zap'],
    ['aktivitas',     'file-text'],
    ['cascade',       'layers'],
    ['tile',          'grid'],
    ['arrange',       'grid'],
    ['po',            'clipboard'],
    ['so',            'clipboard'],
    ['faktur',        'file-text'],
    ['nota',          'file-text'],
    ['kredit',        'credit-card'],
    ['pemakaian',     'package'],
    ['informasi',     'layers'],
    ['cetak',         'printer'],
  ];

  // Prefer the DB-provided icon column (row.icon) when present; only
  // fall back to keyword guessing against the label when it's null —
  // most rows have icon=NULL today, so the fallback still does most
  // of the work, but as `icon` gets populated in DBMENU this will
  // automatically prefer that instead.
  function getChildIcon(label, dbIcon) {
    if (dbIcon && icons[dbIcon]) return dbIcon;
    const l = (label || '').toLowerCase();
    for (const [kw, ic] of childIconMap) {
      if (l.includes(kw)) return ic;
    }
    return 'box';
  }

  // ── Menu state ───────────────────────────────────────────────────────
  let modules = [];
  let activeModuleKey = null;

  const moduleIcons = {
    'berkas':          'archive',
    'master data':     'users',
    'accounting':      'bar-chart',
    'pengadaan':       'credit-card',
    'marketing':       'shopping-cart',
    'gudang':          'warehouse',
    'pos':             'trending-up',
    'laporan-laporan': 'file-text',
    'utilitas':        'tool',
    'jendela':         'monitor',
  };

  function getModuleIcon(label, dbIcon) {
    if (dbIcon && icons[dbIcon]) return dbIcon;
    const k = (label || '').toLowerCase().trim();
    if (moduleIcons[k]) return moduleIcons[k];
    for (const [pattern, iconName] of Object.entries(moduleIcons)) {
      if (k.includes(pattern)) return iconName;
    }
    return 'box';
  }

  // Prefer the DB-provided color column (row.color) when present and a
  // known cardColors class; otherwise cycle through cardColors by the
  // card's position among its siblings, same as before this override existed.
  function getCardColor(dbColor, index) {
    if (dbColor && cardColors.includes(dbColor)) return dbColor;
    return cardColors[index % cardColors.length];
  }

  // ── Build menu tree: consume the ALREADY-NESTED tree from the backend ─
  // HomeController@GetMenu($headermenu) now returns $menul0, where each
  // L0 module row already has a `.child` array of L1 cards, and each L1
  // card already has its own `.child` array of L2 submenu rows (built
  // server-side via KODEMENU prefix matching in PHP). So no client-side
  // prefix-matching is needed anymore — just map field names into the
  // shape the rest of this file expects (key/label/href/access/icon +
  // recursive children).
  function mapMenuNode(row) {
    return {
      key: row.KODEMENU,
      label: row.Keterangan,
      href: row.href,
      access: row.ACCESS,
      icon: row.icon || null, // DB-provided icon name, if any; falls back to keyword guessing below
      color: row.color || null, // DB-provided cardColors class name, if any; falls back to i%10 cycling below
      children: (row.child || []).map(mapMenuNode)
    };
  }

  function buildMenu(rows) {
    return (rows || []).map(mapMenuNode);
  }

  // ── Expand/collapse a module's L1 list in the sidebar (click, not
  // hover -- the .nav-group.open CSS this drives already existed for this
  // purpose, it just wasn't wired to anything before). Bound to clicks on
  // the label/chevron portion of .nav-item; the icon has its own onclick
  // (showModuleHome) that stops propagation before it reaches this. ──────
  function toggleModuleSubmenu(moduleKey) {
    const ng = document.getElementById('ng-' + moduleKey);
    if (!ng) return;
    const willOpen = !ng.classList.contains('open');
    document.querySelectorAll('.nav-group.open').forEach(g => g.classList.remove('open'));
    if (willOpen) ng.classList.add('open');
  }

  // ── Render the card-grid home for a module ───────────────────────────
  function showModuleHome(moduleKey) {
    closeReportPage();
    const mod = modules.find(m => m.key === moduleKey);
    if (!mod) return;

    activeModuleKey = moduleKey;

    // Mark nav active
    document.querySelectorAll('.nav-group').forEach(g => g.classList.remove('active'));
    const ng = document.getElementById('ng-' + moduleKey);
    if (ng) ng.classList.add('active');

    // Breadcrumb
    document.getElementById('breadcrumb').innerHTML =
      `<span class="bc-sep"></span><b>${mod.label}</b>`;

    // Build card grid (L0=1 cards)
    const cards = mod.children.map((c, i) => {
      const color    = getCardColor(c.color, i);
      const iconName = getChildIcon(c.label, c.icon);
      return `
        <div class="card" onclick="navToChild('${moduleKey}','${c.key}','${encodeURIComponent(c.href || '')}')">
          <div class="card-icon-wrap ${color}">${icon(iconName)}</div>
          <div class="card-label">${c.label}</div>
        </div>`;
    }).join('');

    // Show dynamic content, hide blade content
    const dyn   = document.getElementById('content-dynamic');
    const blade = document.getElementById('content-blade');
    if (blade) blade.style.display = 'none';
    dyn.style.display = 'block';
    dyn.innerHTML = `
      <div class="page-subtitle">
      <button class="report-back-btn" onclick="goHome()">
        ${icon('arrow-left')} Kembali
      </button></div>
      <div class="page-title">${mod.label}</div>
      <div class="page-subtitle">${mod.subtitle ?? ''}</div>
      <div class="card-grid">${cards}</div>
    `;
  }

  // ── Navigate to a child page (redirects to its href) ─────────────────
  function navToChild(moduleKey, cardKey, encodedHref) {
    const href = decodeURIComponent(encodedHref);
    if (href && href !== 'undefined' && href !== '') {
      window.location.href = href;
    }
  }

  // ── Navigate from sidebar items (prefixes the app base URL) ──────────
  function goTo(encodedHref) {
    const href = decodeURIComponent(encodedHref);
    if (href && href !== 'undefined' && href !== '') {
      window.location.href = '{{ url('') }}/' + href.replace(/^\//, '');
    }
  }

  function renderNav() {
    const nav = document.getElementById('nav');
    nav.innerHTML = modules.map(m => `
      <div class="nav-group" id="ng-${m.key}">
        <div class="nav-item" onclick="toggleModuleSubmenu('${m.key}')">
          <span class="nav-icon" onclick="event.stopPropagation(); showModuleHome('${m.key}')">${icon(getModuleIcon(m.label, m.icon))}</span>
          <span class="nav-label">${m.label}</span>
          <span class="nav-chevron">${icon('chevron')}</span>
        </div>
        <div class="nav-children">
          ${m.children.map(c => {
            const hasSub = c.children && c.children.length > 0;
            return `
            <div class="nav-child ${hasSub ? 'has-sub' : ''}"
                 data-flyout-id="${hasSub ? 'flyout-' + c.key : ''}"
                 data-access="${c.access ?? ''}"
                 onclick="event.stopPropagation(); ${hasSub ? '' : `goTo('${encodeURIComponent(c.href || '')}')`}">
              <span class="nav-child-label">${c.label}</span>
              ${hasSub ? `<span class="nav-child-arrow">${icon('chevron')}</span>` : ''}
            </div>`;
          }).join('')}
        </div>
      </div>
    `).join('');

    // Build all flyout panels into one detached container appended to
    // <body>, completely outside the sidebar's clipped DOM subtree.
    let flyoutRoot = document.getElementById('flyout-root');
    if (!flyoutRoot) {
      flyoutRoot = document.createElement('div');
      flyoutRoot.id = 'flyout-root';
      document.body.appendChild(flyoutRoot);
    }
    flyoutRoot.innerHTML = modules.flatMap(m =>
      m.children.filter(c => c.children && c.children.length > 0).map(c => `
        <div class="nav-flyout" id="flyout-${c.key}">
          ${c.children.map(sub => `
            <div class="nav-flyout-item"
                 data-access="${sub.access ?? ''}"
                 onclick="goTo('${encodeURIComponent(sub.href || '')}')">${sub.label}</div>
          `).join('')}
        </div>
      `)
    ).join('');

    attachFlyoutHoverHandlers();
  }

  // ── Position + show/hide flyouts on hover using real coordinates ─────
  function attachFlyoutHoverHandlers() {
    const allFlyouts = Array.from(document.querySelectorAll('.nav-flyout'));
    const hideTimers = new Map(); // flyoutEl -> timer id, one per flyout
    const HIDE_DELAY = 400; // ms grace period to travel from row to flyout
    const sidebarEl = document.getElementById('sidebar');

    function anyFlyoutOpen() {
      return allFlyouts.some(f => f.classList.contains('flyout-visible'));
    }

    function syncSidebarPin() {
      if (!sidebarEl) return;
      const open = anyFlyoutOpen();
      sidebarEl.classList.toggle('flyout-pinned', open);
      // Only the nav-group owning the currently-open flyout should expand
      // its accordion; every other nav-group's "flyout-owner" class gets
      // cleared so they don't pop open along with it.
      document.querySelectorAll('.nav-group.flyout-owner').forEach(g => {
        if (!open) g.classList.remove('flyout-owner');
      });
    }

    function hideAllExcept(keepEl) {
      allFlyouts.forEach(f => {
        if (f !== keepEl) {
          clearTimeout(hideTimers.get(f));
          f.classList.remove('flyout-visible');
        }
      });
      syncSidebarPin();
    }

    document.querySelectorAll('.nav-child.has-sub').forEach(rowEl => {
      const flyoutId = rowEl.getAttribute('data-flyout-id');
      const flyoutEl = document.getElementById(flyoutId);
      if (!flyoutEl) return;

      function showFlyout() {
        clearTimeout(hideTimers.get(flyoutEl));
        hideAllExcept(flyoutEl);

        // Mark the .nav-group that owns this row so its accordion stays
        // expanded while the flyout is pinned open (see syncSidebarPin
        // and the #sidebar.flyout-pinned .nav-group.flyout-owner CSS).
        const ownerGroup = rowEl.closest('.nav-group');
        if (ownerGroup) ownerGroup.classList.add('flyout-owner');

        const rect = rowEl.getBoundingClientRect();
        const OVERLAP = 6;

        // ✅ Temporarily make it invisible-but-laid-out so offsetWidth is real
        flyoutEl.style.visibility = 'hidden';
        flyoutEl.style.opacity = '0';
        flyoutEl.style.display = 'block'; // force layout

        const flyoutWidth  = flyoutEl.offsetWidth  || 220;
        const flyoutHeight = flyoutEl.offsetHeight || 0;

        // Reset display (transition will handle the rest)
        flyoutEl.style.display = '';
        flyoutEl.style.visibility = '';
        flyoutEl.style.opacity = '';

        let left = rect.right - OVERLAP;
        let top  = rect.top;

        if (left + flyoutWidth > window.innerWidth) {
          left = rect.left - flyoutWidth + OVERLAP;
        }
        if (top + flyoutHeight > window.innerHeight) {
          top = Math.max(8, window.innerHeight - flyoutHeight - 8);
        }

        flyoutEl.style.left = left + 'px';
        flyoutEl.style.top  = top  + 'px';
        flyoutEl.classList.add('flyout-visible');
        syncSidebarPin();
      }

      function scheduleHide() {
        const t = setTimeout(() => {
          flyoutEl.classList.remove('flyout-visible');
          syncSidebarPin();
        }, HIDE_DELAY);
        hideTimers.set(flyoutEl, t);
      }

      rowEl.addEventListener('mouseenter', showFlyout);
      rowEl.addEventListener('mouseleave', scheduleHide);
      flyoutEl.addEventListener('mouseenter', () => clearTimeout(hideTimers.get(flyoutEl)));
      flyoutEl.addEventListener('mouseleave', scheduleHide);
    });

    // No sidebar-level "mouseleave closes everything" listener here on
    // purpose: the flyout lives outside #sidebar in the DOM (appended to
    // body), so moving the cursor from the row into the flyout already
    // counts as leaving #sidebar. A sidebar-wide listener would force-close
    // the flyout the moment you tried to enter it. The row's own
    // mouseleave + the flyout's own mouseleave already cover every real
    // "user moved away" case between them.
  }

  // ── Boot ─────────────────────────────────────────────────────────────
  // Breadcrumb starts blank otherwise -- nothing sets it until a module is
  // clicked (showModuleHome/showReportPage/goHome all set it, but none of
  // those run on initial load). Set it directly here so "Beranda" shows
  // immediately, without waiting on the async menu fetch below.
  document.getElementById('breadcrumb').innerHTML = `<span>Beranda</span>`;

  // headermenu=1 selects the "no offset" branch in GetMenu($headermenu):
  // L0=0 for modules, L0=1 for cards, L0=2 for submenu items. Adjust this
  // value later if a different header context needs the L0=1/2/(2) branch.
  $.get('{{ url('getmenu/1') }}', function (data) {
    modules = buildMenu(data);
    console.log(data)
    renderNav();
    renderSidebarFooter();
  }).fail(function () {
    console.error('Failed to load menu from /getmenu');
  });

let reportCategories = [];

function renderSidebarFooter() {
  const footer = document.getElementById('sidebar-footer');
  if (!footer) return;
  footer.innerHTML = `
    <div class="nav-report-item" id="nav-report-item" onclick="showReportPage()">
      <span class="nav-icon">${icon('bar-chart')}</span>
      <span class="nav-label">Report</span>
    </div>
  `;
}

let reportViewStack = []; // breadcrumb trail of nodes drilled into, e.g. [Master Accounting, Daftar Perkiraan]

// A node is worth showing only if it — or something underneath it —
// actually has a real href. Pure "#" folders with no working leaf
// anywhere below them get filtered out entirely.
function hasLeafDescendant(node) {
  if (node.href && node.href !== '#' && node.href !== '') return true;
  return (node.children || []).some(hasLeafDescendant);
}

function loadReportMenu(callback) {
  $.get('{{ url("getmenureport/1") }}', function (data) {
    const tree = buildMenu(data); // reuse the same mapMenuNode/buildMenu as the sidebar
    reportCategories = tree.filter(hasLeafDescendant);
    if (callback) callback();
  }).fail(function () {
    console.error('Failed to load report menu from /getmenureport');
    reportCategories = [];
    if (callback) callback();
  });
}

function showReportPage() {
  activeModuleKey = null;
  reportViewStack = [];

  document.querySelectorAll('.nav-group').forEach(g => g.classList.remove('active'));
  const reportItem = document.getElementById('nav-report-item');
  if (reportItem) reportItem.classList.add('active');

  document.getElementById('breadcrumb').innerHTML =
    `<span class="bc-sep"></span><b>Report</b>`;

  const blade  = document.getElementById('content-blade');
  const dyn    = document.getElementById('content-dynamic');
  const report = document.getElementById('content-report');

  if (blade) blade.style.display = 'none';
  if (dyn)   dyn.style.display = 'none';

  report.style.display = 'block';
  report.innerHTML = `
    <div class="container-fluid clearfix">
      <button class="report-back-btn" id="report-back-btn" onclick="reportGoBack()">
        ${icon('arrow-left')} Kembali
      </button>
      <div id="report-crumb" class="page-subtitle"></div>
      <div id="report-categories-container" class="text-muted">Memuat data laporan...</div>
    </div>
  `;

  loadReportMenu(renderReportView);
}

// Drill into a folder node — stays on the Report page, just changes
// what's shown, same pattern as navToChild()/showModuleHome() elsewhere.
function reportDrillInto(node) {
  reportViewStack.push(node);
  renderReportView();
}

// One level up. If already at the root, exit the Report page entirely
// (same behavior the old "Kembali" button had).
function reportGoBack() {
  if (reportViewStack.length > 0) {
    reportViewStack.pop();
    renderReportView();
  } else {
    closeReportPage();
  }
}

function renderReportView() {
  const container = document.getElementById('report-categories-container');
  const crumbEl   = document.getElementById('report-crumb');
  if (!container) return;

  // Breadcrumb trail: Master Accounting > Daftar Perkiraan ...
  if (crumbEl) {
    const trail = reportViewStack.map(n => n.label);
    crumbEl.innerHTML = trail.length
      ? trail.map((label, i) => i === trail.length - 1 ? `<b>${label}</b>` : `${label} <span class="bc-sep"></span> `).join('')
      : '';
  }

  const currentNode = reportViewStack[reportViewStack.length - 1] || null;

  if (currentNode) {
    // Inside a folder: show its children as one flat grid.
    const children = (currentNode.children || []).filter(hasLeafDescendant);
    container.className = '';
    container.innerHTML = `<div class="report-grid">${renderReportCards(children)}</div>`;
    return;
  }

  // Root: show each L0=1 category as its own titled section.
  if (!reportCategories.length) {
    container.className = 'text-muted';
    container.innerHTML = `Tidak ada laporan tersedia.`;
    return;
  }

  container.className = '';
  container.innerHTML = reportCategories.map(cat => {
    const children = (cat.children || []).filter(hasLeafDescendant);
    return `
      <div class="report-category">
        <div class="report-category-title">${cat.label}</div>
        <div class="report-grid">${renderReportCards(children)}</div>
      </div>`;
  }).join('');
}

function renderReportCards(nodes) {
  return nodes.map((node, i) => {
    const color    = getCardColor(node.color, i);
    const iconName = getChildIcon(node.label, node.icon);
    const subChildren = (node.children || []).filter(hasLeafDescendant);
    const hasSub   = subChildren.length > 0;

    if (hasSub) {
      // Folder card: clicking drills into it, staying on this page.
      return `
        <div class="report-card report-card-has-sub" onclick='reportDrillInto(${JSON.stringify(node).replace(/'/g, "&#39;")})'>
          <div class="report-card-icon ${color}">${icon(iconName)}</div>
          <div class="report-card-label">${node.label}</div>
          <span class="report-card-arrow">${icon('chevron')}</span>
        </div>`;
    }

    // Leaf card: clicking navigates straight to the report.
    return `
      <div class="report-card" onclick="openReport('${encodeURIComponent(node.href)}')">
        <div class="report-card-icon ${color}">${icon(iconName)}</div>
        <div class="report-card-label">${node.label}</div>
      </div>`;
  }).join('');
}

function closeReportPage() {
  const reportItem = document.getElementById('nav-report-item');
  if (reportItem) reportItem.classList.remove('active');

  reportViewStack = [];

  document.getElementById('content-report').style.display = 'none';
  document.getElementById('content-blade').style.display = 'block';

  document.getElementById('breadcrumb').innerHTML = `<span>Beranda</span>`;
}

function openReport(encodedHref) {
  goTo(encodedHref);
}

function goHome() {
  closeReportPage();

  activeModuleKey = null;
  document.querySelectorAll('.nav-group').forEach(g => g.classList.remove('active'));

  const dyn = document.getElementById('content-dynamic');
  if (dyn) {
    dyn.style.display = 'none';
    dyn.innerHTML = '';
  }

  document.getElementById('content-blade').style.display = 'block';
  document.getElementById('breadcrumb').innerHTML = `<span>Beranda</span>`;
}

</script>

  <script>
    window.OTO_PUSH = {
      vapidKey: @json(config('services.webpush.public_key')),
      swUrl: "{!! URL::asset('sw.js') !!}",
      subscribeUrl: "{!! url('pushsubscribe') !!}",
      unsubscribeUrl: "{!! url('pushunsubscribe') !!}",
      countUrl: "{!! url('otorisasicount') !!}",
      token: "{!! csrf_token() !!}",
    };
  </script>
  @if (request()->routeIs('home'))
  <script>
    // Panel #otoHome (Beranda, mobile). Didaftarkan SEBELUM otorisasi-push.js supaya
    // event pertamanya (otopush:count / otopush:status) tidak terlewat.
    (function () {
      function setStat(id, n) {
        var b = document.getElementById(id);
        b.textContent = n > 99 ? '99+' : n;
        b.parentNode.classList.toggle('has', n > 0);
      }

      document.addEventListener('otopush:count', function (e) {
        var pending = e.detail.pending || 0, hasil = e.detail.hasil || 0;
        setStat('otoHomePending', pending);
        setStat('otoHomeHasil', hasil);
        var empty = !pending && !hasil;
        document.getElementById('otoHomeStats').style.display = empty ? 'none' : '';
        document.getElementById('otoHomeClear').style.display = empty ? '' : 'none';
        var now = new Date();
        document.getElementById('otoHomeTime').textContent = 'Diperbarui ' +
          ('0' + now.getHours()).slice(-2) + ':' + ('0' + now.getMinutes()).slice(-2);
      });

      var ENABLE = {
        off: ['Notifikasi belum aktif di HP ini.', true],
        'ios-need-install': ['Di iPhone: tekan Share lalu "Add to Home Screen", buka AJC dari ikonnya untuk menerima notifikasi.', false],
        denied: ['Notifikasi diblokir. Izinkan lewat pengaturan situs di browser.', false],
      };

      document.addEventListener('otopush:status', function (e) {
        var d = ENABLE[e.detail];
        document.getElementById('otoHomeEnable').style.display = d ? '' : 'none';
        if (!d) return;
        document.getElementById('otoHomeEnableText').textContent = d[0];
        document.getElementById('otoHomeEnableBtn').style.display = d[1] ? '' : 'none';
      });

      document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('otoHomeEnableBtn').addEventListener('click', function () {
          OtoPush.subscribe().catch(function (err) {
            if (window.alertify) alertify.warning(err.message); else alert(err.message);
          });
        });
      });
    })();
  </script>
  @endif
  <script src="{!! URL::asset('js/otorisasi-push.js') !!}?v={{ @filemtime(base_path('public/js/otorisasi-push.js')) ?: '1' }}"></script>

  @include('partials.ajax-error-logger')
  @include('partials.user-filter')
  @yield('js')
</body>
</html>
