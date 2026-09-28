<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockAlert;
use App\Rules\IvorianPhoneNumber;
use App\Rules\PassesTurnstile;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * "Me prévenir" on a sold-out product or variant (EX-17): one phone number or e-mail, one alert per contact
 * and product (a repeated request is not stored twice).
 */
class StockAlertController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'contact' => ['required', 'string', 'max:255'],
            'cf-turnstile-response' => [new PassesTurnstile],
        ], ['contact.required' => 'Indiquez votre numéro de téléphone ou votre e-mail.']);

        $product = Product::with('variants')->findOrFail($data['product_id']);
        $variant = isset($data['variant_id']) ? $product->variants->firstWhere('id', (int) $data['variant_id']) : null;

        abort_unless($product->is_active && (! isset($data['variant_id']) || $variant), 404);

        [$phone, $email] = $this->contact($data['contact']);

        if ($phone === null && $email === null) {
            return back()->with('notice_error', 'Indiquez un numéro à 10 chiffres (07 01 02 03 04) ou une adresse e-mail valide.');
        }

        if ($this->available($product, $variant)) {
            return back()->with('notice', 'Bonne nouvelle : ce produit est disponible, vous pouvez le commander dès maintenant.');
        }

        StockAlert::firstOrCreate(
            ['product_id' => $product->id, 'product_variant_id' => $variant?->id, 'phone' => $phone, 'email' => $email, 'notified_at' => null],
            ['user_id' => $request->user()?->getKey()],
        );

        return back()->with('notice', 'C’est noté : nous vous prévenons dès que « '.$product->name.' » est de nouveau disponible.');
    }

    /**
     * An e-mail when the value contains "@", an Ivorian phone number otherwise.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function contact(string $value): array
    {
        $value = trim($value);

        if (Str::contains($value, '@')) {
            return Validator::make(['email' => $value], ['email' => ['email']])->passes() ? [null, Str::lower($value)] : [null, null];
        }

        return Validator::make(['phone' => $value], ['phone' => [new IvorianPhoneNumber]])->passes() ? [PhoneNumber::normalize($value), null] : [null, null];
    }

    private function available(Product $product, ?ProductVariant $variant): bool
    {
        return $variant ? $variant->stock > 0 : $product->variants->contains(fn (ProductVariant $item) => $item->stock > 0);
    }
}
