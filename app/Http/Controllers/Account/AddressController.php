<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Commune;
use App\Rules\IvorianPhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Address book (F-071): several addresses, one default, used to prefill the checkout.
 */
class AddressController extends Controller
{
    public const MAX_ADDRESSES = 10;

    public function index(Request $request): View
    {
        return view('pages.account.addresses', [
            'addresses' => $request->user()->addresses()->with('commune')->get(),
            'communes' => $this->communes(),
            'editing' => null,
        ]);
    }

    public function edit(Request $request, Address $address): View
    {
        $this->authorizeOwner($request, $address);

        return view('pages.account.addresses', [
            'addresses' => $request->user()->addresses()->with('commune')->get(),
            'communes' => $this->communes(),
            'editing' => $address,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->addresses()->count() >= self::MAX_ADDRESSES) {
            return back()->with('account_error', 'Vous avez atteint le nombre maximum d’adresses ('.self::MAX_ADDRESSES.').');
        }

        $data = $this->validated($request);
        // The first address is the default one.
        $data['is_default'] = $data['is_default'] || $user->addresses()->doesntExist();

        $user->addresses()->create($data);

        return redirect()->route('account.addresses.index')->with('account_status', 'Adresse ajoutée.');
    }

    public function update(Request $request, Address $address): RedirectResponse
    {
        $this->authorizeOwner($request, $address);

        $data = $this->validated($request);
        $data['is_default'] = $data['is_default'] || $address->is_default;
        $address->update($data);

        return redirect()->route('account.addresses.index')->with('account_status', 'Adresse modifiée.');
    }

    public function makeDefault(Request $request, Address $address): RedirectResponse
    {
        $this->authorizeOwner($request, $address);
        $address->update(['is_default' => true]);

        return back()->with('account_status', 'Adresse par défaut modifiée.');
    }

    public function destroy(Request $request, Address $address): RedirectResponse
    {
        $this->authorizeOwner($request, $address);
        $address->delete();

        return redirect()->route('account.addresses.index')->with('account_status', 'Adresse supprimée.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', new IvorianPhoneNumber],
            'commune_id' => ['required', 'integer', 'exists:communes,id'],
            'district' => ['required', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
        ], [], [
            'label' => 'nom de l’adresse',
            'recipient_name' => 'destinataire',
            'phone' => 'téléphone',
            'commune_id' => 'commune',
            'district' => 'quartier',
            'landmark' => 'repère',
        ]);

        return [...$data, 'is_default' => $request->boolean('is_default')];
    }

    private function authorizeOwner(Request $request, Address $address): void
    {
        abort_unless($address->user_id === $request->user()->getKey(), 404);
    }

    /**
     * Every commune, deliverable or not: an address can be saved before its zone opens.
     *
     * @return Collection<string, Collection<int, Commune>>
     */
    private function communes(): Collection
    {
        return Commune::with('zone')->orderBy('name')->get()->groupBy(fn (Commune $commune) => $commune->zone?->name ?? 'Autres');
    }
}
