<div class="has-text-left">
@if(Session()->has('errors'))
    @foreach(Session()->get('errors') as $field => $errors)
        @foreach($errors as $error)
            <div class="notification is-danger">
                <!-- <button class="delete"></button> -->
                {!! $error !!}
            </div>
        @endforeach
    @endforeach
@endif
@if(Session()->has('notifications'))
    @foreach(Session()->get('notifications') as $notification)
    <div class="notification is-{{ $notification['type'] }}">
        <!-- <button class="delete"></button> -->
        {!! $notification['message'] !!}
    </div>
    @endforeach
@endif
</div>