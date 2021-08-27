@extends('layouts.initial')

@section('content')

<article class="has-text-centered">
    @include('components.notifications')

    <div class="columns">
        <div class="column"></div>
        <div class="column">
            <section class="has-text-centered shrink">
                <div>
                    <p class="heading">API Server</p>
                    <p class="title">Administration Login</p>
                </div>
                <form class="box has-text-left" method="post">
                    @csrf
                    <div class="field">
                        <label class="label">Email</label>
                        <div class="control">
                            <input class="input" type="email" name="email" placeholder="e.g. alex@example.com">
                        </div>
                    </div>

                    <div class="field">
                        <label class="label">Password</label>
                        <div class="control">
                            <input class="input" type="password" name="password" placeholder="********">
                        </div>
                    </div>

                    <button class="button is-info">Sign in</button>
                </form>
            </section>
        </div>
        <div class="column"></div>
    </div>

</article>

@endsection