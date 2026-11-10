{{-- Filter modal tersimpan per user — lihat public/js/user-filter.js & UserFilterController.
     Tidak melakukan apa-apa sampai halaman memanggil UserFilter.restore()/save(). --}}
<script>
  window.USER_FILTER_CFG = {
    token: "{{ csrf_token() }}",
    getUrl: "{{ url('userfilter_get') }}",
    saveUrl: "{{ url('userfilter_save') }}"
  };
</script>
<script src="{!! URL::asset('js/user-filter.js') !!}?v={{ @filemtime(public_path('js/user-filter.js')) ?: '1' }}"></script>
