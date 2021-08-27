<nav class="breadcrumb" aria-label="breadcrumbs">
    <ul>
        @foreach($crumbs as $crumb)
        @if (!$loop->last)
        <li><a href="{{ $crumb['href'] }}">{{ $crumb['title'] }}</a></li>
        @else
        <li class="is-active"><a href="#" aria-current="page">{{ $crumb['title'] }}</a></li>
        @endif
        @endforeach
    </ul>
</nav>