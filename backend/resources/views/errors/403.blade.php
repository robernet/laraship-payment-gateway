@extends('errors.layout')

@section('title', '403')

@section('content')
    <h1 style="color: orangered;">403</h1>
    <div class="title m-b-md">
        Forbidden<strong>!</strong>
    </div>

    <div class="links">
        <a href="{{ route('login') }}">Log in</a>
    </div>
@endsection