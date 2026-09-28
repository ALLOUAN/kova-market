<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddressRequest;
use App\Models\Address;
use App\Models\Commune;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Address book (F-071): several addresses, one default, used to prefill the checkout.
 */
class AddressController extends Controller
{
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

    public function store(AddressRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->canAddAddress()) {
            return back()->with('account_error', 'Vous avez atteint le nombre maximum d’adresses ('.Address::MAX_PER_CUSTOMER.').');
        }

        $data = $request->details();
        // The first address is the default one.
        $data['is_default'] = $data['is_default'] || $user->addresses()->doesntExist();

        $user->addresses()->create($data);

        return redirect()->route('account.addresses.index')->with('account_status', 'Adresse ajoutée.');
    }

    public function update(AddressRequest $request, Address $address): RedirectResponse
    {
        $this->authorizeOwner($request, $address);

        $data = $request->details();
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
