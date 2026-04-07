@extends('layouts.app')

@section('title', 'Login | Spare Soko')

@section('content')
  <div class="row auth-center-row auth-static-row">
    <div class="col-md-5 col-sm-8">
      <div class="auth-card auth-card-static">
        <div class="auth-card-topbar">
          <a href="{{ route('home') }}" class="auth-back-icon" aria-label="Kembali ke beranda">
            <i class="fa fa-arrow-left"></i>
          </a>
        </div>
        <form method="POST" action="{{ route('login.attempt') }}">
          @csrf
          <div class="form-group">
            <label for="username">Username</label>
            <input id="username" type="text" name="username" class="form-control" value="{{ old('username') }}" required>
            @error('username')<p class="text-danger">{{ $message }}</p>@enderror
          </div>
          <div class="form-group">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" class="form-control" required>
          </div>
          <input type="hidden" name="next" value="{{ $next }}">
          <button class="btn btn-block btn-primary" type="submit">Submit</button>
        </form>
        <p class="auth-helper-text">Not member? <a href="{{ route('register', ['next' => $next]) }}">Register</a>.</p>
      </div>
    </div>
  </div>
@endsection
