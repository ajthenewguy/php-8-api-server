<nav class="navbar is-white">
    
    <div class="container">
        <!-- Left side -->
        <div class="navbar-brand">
            <a class="navbar-item brand-text" href="/">Server Admin</a>
            <div class="navbar-burger burger" data-target="navMenu">
                <span>1</span>
                <span>2</span>
                <span>3</span>
            </div>
        </div>
        <div class="navbar-menu">
            <div class="navbar-start">
            </div>
        </div>

        <!-- Right side -->
        <div class="navbar-end">
            @php
                if (Request()->is('/login')) {
                    echo '<a href="/register" class="navbar-item">Register</a>';
                }
                
                if (Request()->is('/register')) {
                    echo '<a href="/login" class="navbar-item">Login</a>';
                }

                if (Session()->has('User')) {
                    echo '<a href="/account" class="navbar-item">' . Session()->get('User')->name_first . ' ' . Session()->get('User')->name_last . '</a>';
                    echo '<a href="/logout" class="navbar-item">Logout</a>';
                }
            @endphp
        </div>
    </div>

    
    
</nav>