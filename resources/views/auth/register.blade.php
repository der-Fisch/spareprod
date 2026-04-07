@extends('layouts.app')

@section('title', 'Register | Spare Soko')

@section('content')
  <div class="row auth-center-row auth-static-row">
    <div class="col-md-5 col-sm-8">
      <div class="auth-card auth-card-static">
        <div class="auth-card-topbar">
          <a href="{{ route('home') }}" class="auth-back-icon" aria-label="Kembali ke beranda">
            <i class="fa fa-arrow-left"></i>
          </a>
        </div>
        <form method="POST" action="{{ route('register.store') }}">
          @csrf
          <div class="form-group">
            <label for="register-username">Username</label>
            <input id="register-username" type="text" name="username" class="form-control" value="{{ old('username') }}" required>
            @error('username')<p class="text-danger">{{ $message }}</p>@enderror
          </div>
          <div class="form-group">
            <label for="register-email">Email</label>
            <input id="register-email" type="email" name="email" class="form-control" value="{{ old('email') }}" required>
            @error('email')<p class="text-danger">{{ $message }}</p>@enderror
          </div>
          <div class="form-group">
            <label for="register-password">Password</label>
            <input id="register-password" type="password" name="password" class="form-control" required>
            @error('password')<p class="text-danger">{{ $message }}</p>@enderror
          </div>
          <div class="form-group">
            <label for="register-password-confirmation">Confirm Password</label>
            <input id="register-password-confirmation" type="password" name="password_confirmation" class="form-control" required>
          </div>
          <input type="hidden" name="next" value="{{ $next }}">
          <button class="btn btn-block btn-primary" type="submit">Join</button>
        </form>
        <p class="auth-helper-text">Sudah punya akun? <a href="{{ route('login', ['next' => $next]) }}">Login</a>.</p>
      </div>
    </div>
  </div>
@endsection
