<!DOCTYPE html>
<html lang="">

<head>
    <meta charset="UTF-8">
    <link rel="icon" href="{{ asset('/logo_jateng.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Barlow+Condensed:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('/codebase/css/codebase.min-5.11.css') }}">
</head>

<body>
    <div id="app"></div>
    @vite(['resources/js/app.js'])
    <script src="{{ asset('/js-loading-overlay/js-loading-overlay-custom.js') }}"></script>
    <script src="{{ asset('/codebase/js/codebase.app.min-5.11.js') }}"></script>
    <script>
        @php
            $wilayahAktif = trim((string) config('wilayah.active'));
            $wilayahInfo = $wilayahAktif !== ''
                ? \App\Support\Wilayah::find($wilayahAktif)
                : \App\Support\Wilayah::province();
            $namaWilayah = $wilayahInfo['found'] ? $wilayahInfo['name'] : 'Jawa Tengah';
            $platDaerah = \App\Support\Wilayah::plateCodes($wilayahAktif !== '' ? $wilayahAktif : null);
        @endphp
        window.appConfig = {
            appName: "{{ config('app.name') }}",
            year: "{{ date('Y') }}",
            kabkota: "{{ $namaWilayah }}",
            winnersPerPage: {{ (int) config('wilayah.winners_per_page', 5) }},
            // Kode plat daerah aktif (dari .env APP_KABKOTA) untuk animasi
            // pengundian — tersedia sejak halaman dimuat.
            platDaerah: {!! json_encode($platDaerah) !!},
            platJateng: `{!! json_encode(config('plat_jateng')) !!}`
        };
    </script>
</body>

</html>