<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <script src="https://kit.fontawesome.com/e47a10189c.js" crossorigin="anonymous"></script>

    <!-- Scripts -->
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Styles -->
    @livewireStyles
</head>
@viteReactRefresh
@vite(['resources/css/app.css', 'resources/sass/app.scss', 'resources/js/bootstrap.js', 'resources/js/components/app.jsx'])
<body>
    <div id="app"></div>
</body>
