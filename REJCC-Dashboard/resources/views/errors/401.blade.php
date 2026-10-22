@extends('errors.layout', ['code' => 401])

@section('titre', 'Connexion nécessaire')
@section('message', 'Le dashboard est réservé à l\'administration et aux départements du Bureau. Connectez-vous pour y accéder.')

@section('actions')
    <a href="{{ url('/connexion') }}" class="inline-flex items-center gap-2 rounded-full bg-accent px-5 py-2.5 text-[13.5px] font-bold text-white shadow-sm hover:bg-accent-600">Se connecter</a>
@endsection
