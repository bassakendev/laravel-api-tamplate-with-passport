@extends('adminlte::page')

@section('title', 'Users')

@section('content_header')
    <h1>{{ $user->first_name . ' ' . $user->last_name }} credentials</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="w-100">
                <form action="{{ route('update.users', $user->id) }}" method="post" class="w-50 bg-secondary p-5 rounded-sm"
                    style="margin:80px auto;">
                    @csrf
                    @method('POST')
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group label-floating">
                                <label class="control-label">First Name</label>
                                <input name="first_name" type="text" class="form-control" required
                                    value="{{ old('first_name', $user->first_name) }}">

                                @error('first_name')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group label-floating">
                                <label class="control-label">Last Name</label>
                                <input name="last_name" type="text" class="form-control" required
                                    value="{{ old('last_name', $user->last_name) }}">
                                @error('last_name')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="form-group label-floating">
                                <label class="control-label">Phone</label>
                                <input name="phone" type="tel" class="form-control" required
                                    value="{{ old('phone', $user->phone) }}">

                                @error('phone')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group label-floating">
                                <label class="control-label">Email</label>
                                <input name="email" type="email" class="form-control" required
                                    value="{{ old('email', $user->email) }}">
                                @error('email')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- <div class="col-sm-6">
                            <div class="form-group label-floating">
                                <label class="control-label">Category</label>
                                <input name="phone" type="tel" class="form-control" required
                                    value="{{ old('phone', $user->phone) }}">

                                @error('phone')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="form-group label-floating">
                                <label class="control-label">City</label>
                                <input name="email" type="email" class="form-control" required
                                    value="{{ old('email', $user->email) }}">
                                @error('email')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div> --}}
                    </div>
                    <div class="mt-4" style="display: flex; justify-content: space-between;">
                        <button type="submit" class="btn btn-primary w-25">Validate</button>
                        <a href="{{route('all.users')}}" class="btn btn-danger w-25">Back</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="/css/admin_custom.css">
@stop

@section('js')
    <script>
        console.log('Hi!');
    </script>
@stop
