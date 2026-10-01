<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Rules\IvorianPhoneNumber;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        $input['phone'] = PhoneNumber::normalize($input['phone'] ?? null) ?? ($input['phone'] ?? null);
        $input['email'] = filled($input['email'] ?? null) ? $input['email'] : null;

        // Changing the phone or the e-mail asks for the password (F-147): they are the keys to "forgotten password",
        // so a hijacked session could otherwise take the account for good.
        $changesContact = $input['phone'] !== $user->phone || $input['email'] !== $user->email;

        Validator::make($input, [
            'current_password' => $changesContact ? ['required', 'string', 'current_password:web'] : ['nullable'],
            'name' => ['required', 'string', 'max:255'],
            // Back-office staff may have no phone number; customers always have one.
            'phone' => [$user->phone ? 'required' : 'nullable', 'string', new IvorianPhoneNumber, Rule::unique('users')->ignore($user->id)],
            'email' => [$user->phone ? 'nullable' : 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ], [
            'phone.unique' => 'Un compte existe déjà avec ce numéro de téléphone.',
            'current_password.required' => 'Saisissez votre mot de passe actuel pour changer de téléphone ou d’e-mail.',
            'current_password.current_password' => 'Mot de passe incorrect.',
        ])->validateWithBag('updateProfileInformation');

        if ($input['email'] !== $user->email &&
            $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'phone' => $input['phone'],
                'email' => $input['email'],
            ])->save();
        }
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, string|null>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'phone' => $input['phone'],
            'email' => $input['email'],
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }
}
