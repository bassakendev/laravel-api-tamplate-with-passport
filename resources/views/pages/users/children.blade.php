@extends('adminlte::page')

@section('title', 'Users')

@section('content_header')
    <h1>Children of {{$user->first_name.' '.$user->last_name }}</h1>
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
    <div class="row">

        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div
                        class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3 pl-6  d-flex justify-content-between">
                        <a href="{{route('all.users')}}" class="btn bg-white ml-3">Return to all users</a>
                    </div>
                </div>
                <div class="card-body px-4 pb-4 customerTable actif">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0 ">
                            <thead>
                                <tr>
                                    <th
                                        class="text-justify text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        User</th>
                                    <th
                                        class="text-justify text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        Phone</th>
                                    <th
                                        class="text-justify text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        Category</th>
                                    <th
                                        class="text-justify text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        City</th>
                                    <th
                                        class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        Action</th>
                                </tr>
                            </thead>
                            <tbody class="">
                                @foreach ($users as $user)
                                    <tr class="user">
                                        <td class="text-justify">
                                            <div class="d-flex px-2 py-1">
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="mb-0 text-sm first_name">
                                                        {{ $user->first_name }}
                                                    </h6>
                                                    <h6 class="mb-0 text-sm first_name">
                                                        {{ $user->last_name }}
                                                    </h6>
                                                    <p class="text-xs text-secondary mb-0">
                                                        {{ $user->email }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-justify">
                                            {{ $user->phone }}
                                        </td>
                                        <td class="text-justify">
                                            {{ $user->mainCategory->name ?? 'NA' }}
                                        </td>
                                        <td class="text-justify">
                                            {{ $user->city->name ?? 'NA' }}
                                        </td>
                                        <td class="text-justify">
                                            <div class="d-flex flex-wrap justify-content-between">
                                                <div class="btn-group mr-2">
                                                    <a href="{{ route('show.users', $user->id) }}"
                                                        class="btn btn-success">Show</a>
                                                    <a href="{{ route('children.users', $user->id) }}"
                                                        class="btn btn-info">Children</a>
                                                </div>
                                                <a href="{{ route('delete.users', $user->id) }}"
                                                    class="btn bg-gradient-dark">Delete</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="pagination justify-content-center">
                            {{ $users->links('vendor.pagination.custom-pagination') }}
                        </div>
                    </div>
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
    <script>
        $(document).ready(function() {
            $('table').DataTable({
                order: []
            });
        });
    </script>
@stop
