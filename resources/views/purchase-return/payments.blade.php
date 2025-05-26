@extends('layouts.app')

@section('title', __('Purchase Return Payments'))

@section('content')
    <div class="container-fluid">
        @livewire('purchase-return.payments')
    </div>
@endsection
