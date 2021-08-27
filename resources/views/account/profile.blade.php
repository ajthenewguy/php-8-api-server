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
                                ['title' => 'Profile', 'href' => '/account/profile'],
                                ['title' => 'Back', 'href' => '/account']
                            ]
                        ]
                    ]
                ])

            </div>
            <div class="column is-9">

                @include('components.breadcrumb', ['crumbs' => [['title' => 'Account', 'href' => '/account'], ['title' => 'Profile']]])
                @include('components.notifications')
                
                <div class="box">
                    <h4 id="const" class="title is-3">Edit Your Profile Details</h4>
                    <article class="message is-primary">

                        <form class="box has-text-left" method="post">
                            @csrf
                            <div class="field">
                                <label class="label">First Name</label>
                                <div class="control">
                                    <input class="input" type="text" name="name_first" value="{{ Session()->get('User')->name_first }}" placeholder="First Name">
                                </div>
                            </div>
                            <div class="field">
                                <label class="label">Last Name</label>
                                <div class="control">
                                    <input class="input" type="text" name="name_last" value="{{ Session()->get('User')->name_last }}" placeholder="Last Name">
                                </div>
                            </div>
                            <div class="field is-grouped">
                                <div class="control">
                                    <button type="submit" class="button is-link">Submit</button>
                                </div>
                            </div>
                        </form>

                    </article>
                </div>
            </div>
        </div>
    </div>
@endsection