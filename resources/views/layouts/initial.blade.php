<!DOCTYPE html>
<html lang="en">

<head>
    <title>@yield('title')</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/main.css">
</head>

<body>

    @include('components.navigation')

    @yield('content')


    <footer class="footer">
        DEBUG - Session:
        <pre>
            @php
            print_r(Session()->all());
            @endphp
        </pre>
    </footer>
</body>

</html>