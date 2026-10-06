@extends('errors.layout', ['code' => $exception->getStatusCode()])

@section('titre', 'Un problème est survenu')
@section('message', 'Une erreur inattendue s’est produite de notre côté. Réessayez dans quelques instants.')
