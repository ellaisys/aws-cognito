@if (session('status'))
    <div class="alert alert-success" role="alert">
        {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        @if (count($errors) > 1)
        <p>You have {{ count($errors) }} error(s):</p>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        @else
            @error('error')
                <div class="text-danger fw-bold">{{ $message }}</div>
            @enderror
        @endif
    </div>
@endif
