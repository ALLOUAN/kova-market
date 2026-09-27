<?php

namespace App\Services\Account;

use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Right to erasure (F-075, law 2013-450): the account is anonymised rather than deleted. Orders are kept for
 * the accounts, without the customer's personal data; addresses and the cart are deleted.
 */
class AccountEraser
{
    public const ANONYMOUS = 'Client supprimé';

    /**
     * @throws RuntimeException while an order is still being processed
     */
    public function erase(User $user): void
    {
        $open = $user->orders()->whereNotIn('status', [OrderStatus::Delivered, OrderStatus::Cancelled])->exists();

        if ($open) {
            throw new RuntimeException('Une commande est en cours : vous pourrez supprimer votre compte une fois qu’elle sera livrée ou annulée.');
        }

        DB::transaction(function () use ($user): void {
            $user->orders()->each(fn (Order $order) => $order->update([
                'customer_name' => self::ANONYMOUS,
                'phone' => '',
                'email' => null,
                'district' => '—',
                'landmark' => null,
                'note' => null,
            ]));

            $user->addresses()->delete();
            $user->tokens()->delete();
            Cart::where('user_id', $user->getKey())->delete();

            $user->forceFill([
                'name' => self::ANONYMOUS,
                'email' => null,
                'phone' => null,
                'password' => Str::random(40),
                'marketing_opt_in' => false,
                'remember_token' => null,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
            ])->save();

            activity()->performedOn($user)->log('Compte client anonymisé à la demande du client');
        });
    }

    /**
     * Everything the store keeps about the customer, as a downloadable file (right of access).
     *
     * @return array<string, mixed>
     */
    public function export(User $user): array
    {
        return [
            'exporte_le' => now()->toIso8601String(),
            'compte' => $user->only(['name', 'email', 'phone', 'marketing_opt_in', 'created_at']),
            'adresses' => $user->addresses()->with('commune')->get()->map(fn ($address) => [
                'libelle' => $address->label,
                'destinataire' => $address->recipient_name,
                'telephone' => $address->phone,
                'commune' => $address->commune?->name,
                'quartier' => $address->district,
                'repere' => $address->landmark,
            ])->all(),
            'commandes' => $user->orders()->with('items')->get()->map(fn (Order $order) => [
                'numero' => $order->number,
                'date' => $order->created_at->toIso8601String(),
                'statut' => $order->status->getLabel(),
                'total_fcfa' => $order->total,
                'livraison' => "{$order->district}, {$order->commune_name}",
                'articles' => $order->items->map(fn ($item) => "{$item->quantity} × {$item->product_name}")->all(),
            ])->all(),
        ];
    }
}
