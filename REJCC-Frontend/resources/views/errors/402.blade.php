@extends('errors.layout', ['code' => 402])

@section('titre', 'Abonnement requis')
@section('message', 'Cette fonctionnalité est réservée aux membres à jour de leur abonnement annuel.')

@section('actions')
    <a href="{{ url('/espace-membre/abonnement') }}" class="inline-flex items-center gap-2 rounded-full bg-accent px-5 py-2.5 text-[13.5px] font-bold text-white shadow-sm hover:bg-accent-600">Voir mon abonnement</a>
@endsection
