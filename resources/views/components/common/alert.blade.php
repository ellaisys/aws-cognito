@if (session('status') && session('status') === 'success')
    <div class="alert alert-success" role="alert">
        {{ session('message') }}
    </div>
@endif

@if (session('status') && session('status') === 'error')
    <div class="alert alert-danger" role="alert">
        {{ session('message') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        @if (!$errors->has('error'))
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
