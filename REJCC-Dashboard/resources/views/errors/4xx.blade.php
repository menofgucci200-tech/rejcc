@extends('errors.layout', ['code' => $exception->getStatusCode()])

@section('titre', 'Cette demande n’a pas abouti')
@section('message', 'La page n’a pas pu être affichée. Vérifiez l’adresse ou repartez de l’accueil.')
