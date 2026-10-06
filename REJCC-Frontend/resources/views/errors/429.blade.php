@extends('errors.layout', ['code' => 429])

@section('titre', 'Un peu trop vite !')
@section('message', 'Vous avez fait beaucoup de demandes en peu de temps. Patientez une minute puis réessayez.')
