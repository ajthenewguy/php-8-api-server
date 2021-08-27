@extends('layouts.initial')

@section('content')
    <div class="container">
        <div class="columns">
            <div class="column is-3">
                @include('components.sidebar', [
                    'sections' => [
                        [
                            'title' => 'Account',
                            'links' => [
                                ['title' => 'Profile', 'href' => '/account'],
                                ['title' => 'Edit', 'href' => '/account/profile']
                            ]
                        ]
                    ]
                ])
            </div>
            <div class="column is-9">

                <section>
                @include('components.breadcrumb', ['crumbs' => [['title' => 'Account', 'href' => '/account']]])
                @include('components.notifications')
                
                <div class="box">
                    <h4 id="const" class="title is-3">Profile Details</h4>
                    <article>

                        @php
                        printf("Welcome %s.\n", Session()->get('User')->name_first)
                        @endphp

                    </article>
                </div>
                </section>
            </div>
        </div>
    </div>
@endsection