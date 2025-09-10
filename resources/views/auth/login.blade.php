<x-guest-layout>
    <h2 class="login-title">Login</h2>
    
    <!-- Session Status -->
    @if (session('status'))
        <div style="margin-bottom: 20px; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; color: #155724; border-radius: 8px; font-size: 14px;">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Username/Email -->
        <div class="form-group">
            <label for="email" class="form-label">Username</label>
            <input id="email" 
                   type="email" 
                   name="email" 
                   value="{{ old('email') }}" 
                   required 
                   autofocus 
                   autocomplete="username"
                   class="form-input"
                   placeholder="Type your username">
            @error('email')
                <div style="color: #dc3545; font-size: 12px; margin-top: 5px;">{{ $message }}</div>
            @enderror
        </div>

        <!-- Password -->
        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <input id="password" 
                   type="password" 
                   name="password" 
                   required 
                   autocomplete="current-password"
                   class="form-input"
                   placeholder="Type your password">
            @error('password')
                <div style="color: #dc3545; font-size: 12px; margin-top: 5px;">{{ $message }}</div>
            @enderror
            
            @if (Route::has('password.request'))
                <div class="forgot-password">
                    <a href="{{ route('password.request') }}">Forgot password?</a>
                </div>
            @endif
        </div>

        <!-- Login Button -->
        <button type="submit" class="login-btn">LOGIN</button>
    </form>
</x-guest-layout>
