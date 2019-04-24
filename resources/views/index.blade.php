@extends('layouts.sidebar')

@section('content')
<router-view :key="$route.fullPath"></router-view>
@endsection
