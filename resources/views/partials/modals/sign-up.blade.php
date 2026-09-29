<x-modal id="signupModal" dialog-class="rbt-register-form-modal modal-dialog-centered" labelled>
    <div class="rbt-top-folder-shape-wrapper">
        <div class="rbt-login-form rbt-bg-color-white rbt-content-trs-portion">
            <div class="rbt-login-form-inner">
                <div class="rbt-login-form-top">
                    <div class="logo">
                        <a href="{{ route('home') }}">
                            <img src="{{ asset(config('storefront.logo_small')) }}" alt="{{ config('storefront.name') }}" loading="lazy">
                        </a>
                    </div>
                    <h3 class="rbt-title rbt-text-bold mb--16 h6" id="signupModalLabel">Créer un compte</h3>
                    {{-- Phone number required, e-mail optional (decision C-08). --}}
                    <form method="POST" action="{{ route('register.store') }}" novalidate>
                        @csrf
                        <input type="hidden" name="_form" value="signup">
                        @php($signup = old('_form') === 'signup')

                        <div class="rbt-input-field-grp">
                            <label class="rbt-field-label" for="modal_register_name">Nom complet<span class="rbt-text-color-danger">*</span></label>
                            <input class="rbt-input-field" type="text" id="modal_register_name" name="name" value="{{ $signup ? old('name') : '' }}" autocomplete="name" required>
                            @if ($signup)
                                @error('name')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                            @endif
                        </div>
                        <div class="rbt-input-field-grp mt--16">
                            <label class="rbt-field-label" for="modal_register_number">Numéro de téléphone<span class="rbt-text-color-danger">*</span></label>
                            <input class="rbt-input-field" placeholder="07 01 02 03 04" type="tel" id="modal_register_number" name="phone" value="{{ $signup ? old('phone') : '' }}" autocomplete="tel" required>
                            @if ($signup)
                                @error('phone')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                            @endif
                        </div>
                        <div class="rbt-input-field-grp mt--16">
                            <label class="rbt-field-label" for="modal_register_email">E-mail <span class="b4">(facultatif)</span></label>
                            <input class="rbt-input-field" placeholder="vous@exemple.ci" type="email" id="modal_register_email" name="email" value="{{ $signup ? old('email') : '' }}" autocomplete="email">
                            @if ($signup)
                                @error('email')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                            @endif
                        </div>
                        <div class="rbt-input-field-grp mt--16">
                            <label class="rbt-field-label" for="modal_register_password">Mot de passe (8 caractères minimum)<span class="rbt-text-color-danger">*</span></label>
                            <input class="rbt-input-field" type="password" id="modal_register_password" name="password" autocomplete="new-password" required>
                            @if ($signup)
                                @error('password')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
                            @endif
                        </div>
                        <div class="rbt-input-field-grp mt--16">
                            <label class="rbt-field-label" for="modal_register_password_confirmation">Confirmer le mot de passe<span class="rbt-text-color-danger">*</span></label>
                            <input class="rbt-input-field" type="password" id="modal_register_password_confirmation" name="password_confirmation" autocomplete="new-password" required>
                        </div>

                        <x-turnstile class="mt--16" />
                        <button type="submit" class="rbt-btn d-block w-100 mt--24 mb--16">
                            Créer mon compte
                        </button>
                    </form>

                    <div class="rbt-login-system-switch rbt-link-hover">
                        Déjà client ?
                        <button class="rbt-switch-btn" data-bs-toggle="modal" data-bs-target="#signinModal" data-bs-dismiss="modal" aria-label="Se connecter"><span>Se connecter</span></button>
                    </div>
                </div>

                @include('partials.modals.testimonials')

            </div>
        </div>
    </div>
</x-modal>
