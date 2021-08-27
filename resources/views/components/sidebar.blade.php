<aside class="menu is-hidden-mobile">
    @foreach ($sections as $section)
    <p class="menu-label">{{ $section['title'] }}</p>
    <ul class="menu-list">
        @foreach ($section['links'] as $link)
        <li><a href="{{ $link['href'] }}" @if(Request()->is($link['href'])) class="active"@endif>{{ $link['title'] }}</a></li>
        @endforeach
    </ul>
    @endforeach
</aside>