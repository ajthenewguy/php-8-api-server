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
</div>