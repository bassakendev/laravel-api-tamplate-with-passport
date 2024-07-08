@extends('adminlte::page')

@section('title', 'Users')

@section('content_header')
    <h1>{{ $user->first_name . ' ' . $user->last_name }} credentials</h1>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success text-center text-white messages">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger text-center text-white messages">
            {{ session('error') }}
        </div>
    @endif
    <div class="col-12">
        <div class="card my-4">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3 pl-6">
                    <h6 class="text-white text-capitalize ps-3 animated-underline underline 1">
                        {{ $user->first_name }} {{ $user->last_name }}</h6>
                </div>
            </div>
            <div class="card-body px-4 pb-4 customerTable actif">
                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0 ">
                        <tbody>
                            <tr>
                                <th class="text-primary ">First Name</th>
                                <td style="">{{ $user->first_name }}</td>
                            </tr>
                            <tr>
                            <tr>
                                <th class="text-primary ">Last Name</th>
                                <td style="">{{ $user->last_name }}</td>
                            </tr>
                            <tr>
                                <th class="text-primary ">Phone</th>
                                <td style="">{{ $user->phone }}</td>
                            </tr>
                            <tr>
                                <th class="text-primary ">Email</th>
                                <td style="">{{ $user->email }}</td>
                            </tr>
                            <tr>
                                <th class="text-primary ">City</th>
                                <td style="">{{ $user->city->name ?? 'NA' }}</td>
                            </tr>
                            <tr>
                                <th class="text-primary ">Category</th>
                                <td style="">{{ $user->mainCategory->name ?? 'NA' }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <div style="display: flex; justify-content: space-between; margin-top: 20px">
                        <a href="{{ route('edite.users', $user->id) }}" class="btn btn-info w-25">Update</a>
                        <a href="{{ route('all.users') }}" class="btn bg-gradient-dark w-25">Back</a>
                    </div>
                </div>
            </div>
        </div>

    @stop

    @section('css')
        <link rel="stylesheet" href="/css/admin_custom.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    @stop

    @section('js')
        <script>
            console.log('Hi!');
        </script>
        <script src="//cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    @stop
