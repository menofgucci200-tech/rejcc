@extends('errors.layout', ['code' => 413])

@section('titre', 'Fichier trop volumineux')
@section('message', 'Le fichier envoyé dépasse la taille autorisée. Réduisez sa taille (ou compressez-le) puis réessayez.')
