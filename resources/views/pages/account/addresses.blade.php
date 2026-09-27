@extends('layouts.storefront')

@php
    use App\Support\PhoneNumber;

    $value = fn (string $name, $default = null) => old($name, $editing?->{$name} ?? $default);
@endphp

@section('title', 'Mes adresses')

@section('content')
    <x-account-layout title="Mes adresses">
        @if ($addresses->isNotEmpty())
            <div class="row g-3 mb--40">
                @foreach ($addresses as $address)
                    <div class="col-md-6">
                        <div @class(['border rbt-radius p-3 h-100', 'border-primary' => $address->is_default])>
                            <div class="d-flex justify-content-between gap-2 mb--8">
                                <strong>{{ $address->label }}</strong>
                                @if ($address->is_default)<span class="b4 rbt-text-bold">Par défaut</span>@endif
                            </div>
                            <p class="b3 mb--12">{{ $address->recipient_name }} · {{ PhoneNumber::format($address->phone) }}<br>{{ $address->summary() }}</p>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="rbt-btn rbt-btn-sm rbt-btn-border" href="{{ route('account.addresses.edit', $address) }}">Modifier</a>
                                @unless ($address->is_default)
                                    <form method="POST" action="{{ route('account.addresses.default', $address) }}">
                                        @csrf
                                        <button type="submit" class="rbt-btn rbt-btn-sm rbt-btn-border">Par défaut</button>
                                    </form>
                                @endunless
                                <form method="POST" action="{{ route('account.addresses.destroy', $address) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rbt-btn rbt-btn-sm rbt-btn-border" aria-label="Supprimer l’adresse {{ $address->label }}">Supprimer</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <h2 class="h5 mb--16">{{ $editing ? 'Modifier l’adresse « '.$editing->label.' »' : 'Ajouter une adresse' }}</h2>
        <form method="POST" action="{{ $editing ? route('account.addresses.update', $editing) : route('account.addresses.store') }}" class="row g-3" novalidate>
            @csrf
            @if ($editing) @method('PUT') @endif
            <div class="col-md-4">
                <label class="rbt-field-label" for="label">Nom de l’adresse<span class="rbt-text-color-danger">*</span></label>
                <input class="rbt-input-field" id="label" name="label" value="{{ $value('label') }}" placeholder="Maison, Bureau…" required>
                @error('label')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
            </div>
            <div class="col-md-4">
                <label class="rbt-field-label" for="recipient_name">Destinataire<span class="rbt-text-color-danger">*</span></label>
                <input class="rbt-input-field" id="recipient_name" name="recipient_name" value="{{ $value('recipient_name', auth()->user()->name) }}" required>
                @error('recipient_name')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
            </div>
            <div class="col-md-4">
                <label class="rbt-field-label" for="phone">Téléphone<span class="rbt-text-color-danger">*</span></label>
                <input class="rbt-input-field" id="phone" name="phone" type="tel" value="{{ old('phone', $editing ? PhoneNumber::format($editing->phone) : (auth()->user()->phone ? PhoneNumber::format(auth()->user()->phone) : '')) }}" required>
                @error('phone')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
            </div>
            <div class="col-md-4">
                <label class="rbt-field-label" for="commune_id">Commune<span class="rbt-text-color-danger">*</span></label>
                <select id="commune_id" name="commune_id" class="form-select" required>
                    <option value="">Choisir</option>
                    @foreach ($communes as $zone => $zoneCommunes)
                        <optgroup label="{{ $zone }}">
                            @foreach ($zoneCommunes as $commune)
                                <option value="{{ $commune->id }}" @selected((int) $value('commune_id') === $commune->id)>{{ $commune->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @error('commune_id')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
            </div>
            <div class="col-md-4">
                <label class="rbt-field-label" for="district">Quartier<span class="rbt-text-color-danger">*</span></label>
                <input class="rbt-input-field" id="district" name="district" value="{{ $value('district') }}" required>
                @error('district')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
            </div>
            <div class="col-md-4">
                <label class="rbt-field-label" for="landmark">Repère</label>
                <input class="rbt-input-field" id="landmark" name="landmark" value="{{ $value('landmark') }}">
                @error('landmark')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
            </div>
            <div class="col-12">
                <div class="rbt-check-group">
                    <input type="checkbox" id="is_default" name="is_default" value="1" @checked(old('is_default', $editing?->is_default))>
                    <label for="is_default">Utiliser comme adresse par défaut</label>
                </div>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="rbt-btn rbt-btn-sm">{{ $editing ? 'Enregistrer' : 'Ajouter l’adresse' }}</button>
                @if ($editing)<a class="rbt-btn rbt-btn-sm rbt-btn-border" href="{{ route('account.addresses.index') }}">Annuler</a>@endif
            </div>
        </form>
    </x-account-layout>
@endsection
