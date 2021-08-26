<div class="has-text-left">
@if(isset($session['errors']))
    @foreach($session['errors'] as $field => $errors)
        @foreach($errors as $error)
            <div class="notification is-danger">
                <!-- <button class="delete"></button> -->
                {!! $error !!}
            </div>
        @endforeach
    @endforeach
@endif
</div>