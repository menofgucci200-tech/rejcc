@extends('errors.layout', ['code' => 503])

@section('titre', 'Maintenance en cours')
@section('message', 'La plateforme REJCC est en cours d’amélioration. Elle sera de retour très bientôt : merci de votre patience.')

@section('actions')
    <button type="button" onclick="location.reload()" class="inline-flex items-center gap-2 rounded-full bg-accent px-5 py-2.5 text-[13.5px] font-bold text-white shadow-sm hover:bg-accent-600">Réessayer</button>
@endsection
