@extends('layouts.app')

@section('content')
<div class="flex-center">
    <div class="form-accueil-style ">
        <div class="logo-circle"></div>
        <div class="form-accueil-titre">
            <h1>amadeo.</h1>
            <h3>Outil de suivi des achats</h3>
        </div>
        
        <div class="form-accueil-corps">
            <h2>Mot de passe oublié</h2>

            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif
            
            <form method="POST" action="{{ route('password.email') }}">
                {{ csrf_field() }}

                <div class="{{ $errors->has('email') ? ' has-error' : '' }}">
                    <label class="label_text">Identifiant <input id="name" type="text" class="form-control" name="name" value="{{ $name or old('name') }}" required autofocus></label>
                    @if ($errors->has('name'))
                        <span class="help-block">
                            <strong>{{ $errors->first('name') }}</strong>
                        </span>
                    @endif
                </div>

                <div class="button-section">
                    {!! Form::submit('RÉINITIALISER') !!}
                    {!! Form::button('RETOUR', ['onclick' => 'window.location="/"']) !!}
                </div>

            </form>
        </div>
    
    </div>
</div>
@endsection
