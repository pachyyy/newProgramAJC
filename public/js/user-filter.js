/*
 * UserFilter — simpan & pulihkan pilihan filter modal (select.rt-native) per user.
 * Disimpan di server (DBUSERFILTER via UserFilterController), jadi ikut ke device lain.
 *
 * Pemakaian di halaman (lihat reportaccountingkasharian.blade.php):
 *   UserFilter.restore('#modalFilter', function (values) { ...sinkron variabel global... });
 *   UserFilter.save('#modalFilter');   // panggil di handler tombol Terapkan
 *
 * - Hanya select.rt-native ber-id di dalam modal; tambahkan data-nosave untuk mengecualikan.
 * - Nilai tersimpan yang opsinya sudah tidak ada di dropdown dilewati (default halaman tetap).
 * - Config (token, url) dari window.USER_FILTER_CFG — partials/user-filter.blade.php.
 */
(function (window, $) {
  'use strict';

  var cfg = window.USER_FILTER_CFG || {};

  function pageHref() {
    return (window.location.pathname || '').replace(/^\/+/, '').replace(/\/+$/, '');
  }

  function modalKey($modal) {
    return $modal.attr('id') || 'default';
  }

  function fields($modal) {
    return $modal.find('select.rt-native[id]').not('[data-nosave]');
  }

  function hasOption($select, value) {
    var found = false;
    $select.find('option').each(function () {
      if (String(this.value) === String(value)) { found = true; return false; }
    });
    return found;
  }

  /**
   * Ambil filter tersimpan & terapkan ke select di modal.
   * Sinkron (async:false) supaya nilai sudah terpasang sebelum kode halaman berikutnya
   * berjalan — sama seperti pola loadXxxDropdown yang sudah ada.
   * onRestored(values) hanya dipanggil dengan nilai yang benar-benar terpasang.
   */
  function restore(modalSelector, onRestored) {
    var $modal = $(modalSelector);
    if (!$modal.length || !cfg.getUrl) { return {}; }

    var saved = {};
    $.ajax({
      url: cfg.getUrl,
      type: 'get',
      async: false,
      data: { href: pageHref(), key: modalKey($modal) },
      success: function (res) { saved = (res && typeof res === 'object') ? res : {}; },
      error: function (xhr) { console.warn('UserFilter: gagal memuat filter tersimpan', xhr.status); }
    });

    var applied = {};
    fields($modal).each(function () {
      var $s = $(this);
      var id = this.id;
      if (Object.prototype.hasOwnProperty.call(saved, id) && saved[id] !== null && hasOption($s, saved[id])) {
        $s.val(String(saved[id]));
        applied[id] = String(saved[id]);
      }
    });

    if (typeof onRestored === 'function') { onRestored(applied); }
    return applied;
  }

  /** Simpan nilai select di modal (background, gagal diam-diam). */
  function save(modalSelector) {
    var $modal = $(modalSelector);
    if (!$modal.length || !cfg.saveUrl) { return; }

    var values = {};
    fields($modal).each(function () {
      var v = $(this).val();
      if (v !== null && v !== undefined && !Array.isArray(v)) { values[this.id] = String(v); }
    });

    $.ajax({
      url: cfg.saveUrl,
      type: 'post',
      data: {
        _token: cfg.token,
        href: pageHref(),
        key: modalKey($modal),
        filters: JSON.stringify(values)
      },
      error: function (xhr) { console.warn('UserFilter: gagal menyimpan filter', xhr.status); }
    });
  }

  window.UserFilter = { restore: restore, save: save };
})(window, window.jQuery);
