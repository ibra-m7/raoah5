<x-layouts.admin-auth :title="$title">
    <div class="card auth-card w-100 p-4 p-md-5">
        <div class="text-center mb-4">
            <img src="{{ asset('images/logo.png') }}" alt="{{ $strings::APP_NAME }}" style="width: min(220px, 70vw); height: auto;">
            <div class="text-muted mt-2">{{ $strings::LOGIN_SUBTITLE }}</div>
        </div>

        <form method="POST" action="{{ route('admin.login') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">{{ $strings::EMAIL }}</label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label class="form-label">{{ $strings::PASSWORD }}</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label" for="remember">{{ $strings::REMEMBER_ME }}</label>
            </div>
            <button class="btn btn-brand w-100">{{ $strings::LOGIN_BUTTON }}</button>
        </form>
        <div class="text-center mt-4">
            <a href="{{ route('home') }}" class="text-decoration-none" style="color: var(--color-primary-dark); font-weight: 700;">
                {{ $strings::BACK_TO_SITE }}
            </a>
        </div>
    </div>
</x-layouts.admin-auth>
