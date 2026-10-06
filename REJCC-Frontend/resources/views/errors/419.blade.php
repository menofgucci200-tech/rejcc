@extends('errors.layout', ['code' => 419])

@section('titre', 'Votre session a expiré')
@section('message', 'Pour protéger votre compte, la page a expiré après un moment d’inactivité. Rechargez-la pour continuer : vos informations enregistrées ne sont pas perdues.')

@section('actions')
    <button type="button" onclick="location.reload()" class="inline-flex items-center gap-2 rounded-full bg-accent px-5 py-2.5 text-[13.5px] font-bold text-white shadow-sm hover:bg-accent-600">Recharger la page</button>
@endsection
