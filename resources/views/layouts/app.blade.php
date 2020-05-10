<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>amadeo.</title>

    <!-- Styles -->
    <link href="{{ asset('css/commun/commun.css') }}" rel="stylesheet">
    <link href="{{ asset('css/accueil/accueil.css') }}" rel="stylesheet">
</head>
<body>
    <div id="app" style="display: initial;">
        @yield('content')
    </div>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}"></script>
</body>
<footer>
    <p>Produit développé par Elia-digital</p>
</footer>
</html>
