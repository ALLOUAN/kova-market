<?php

namespace App\Services\Admin;

use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Enums\Permission;
use App\Enums\ReviewStatus;
use App\Filament\Pages\DeliveryDashboard;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\Couriers\CourierResource;
use App\Filament\Resources\NewsletterCampaigns\NewsletterCampaignResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\ProductReviews\ProductReviewResource;
use App\Filament\Resources\Products\ProductResource;
use App\Models\ContactMessage;
use App\Models\NewsletterCampaign;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Services\Delivery\CashSettlement;
use App\Services\Delivery\DeliveryDispatcher;
use App\Services\Orders\SalesFigures;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * What the back-office home shows to one person: the day's figures and, above all, what needs them now (each item
 * only for the roles allowed to act on it, and only when there is something to do), with a link to do it.
 */
class DashboardSummary
{
    public function __construct(
        private SalesFigures $sales,
        private DeliveryDispatcher $dispatcher,
    ) {}

    /**
     * Sales of today against yesterday, the last 7 days against the 7 before, average basket over 30 days, 7-day trend.
     *
     * @return array<string, mixed>
     */
    public function figures(): array
    {
        $today = [today(), now()];
        $yesterday = [today()->subDay(), today()->subSecond()];
        $month = [today()->subDays(29), now()];
        $monthCount = $this->sales->count(...$month);
        $week = $this->sales->daily(7);

        return [
            'week_total' => (int) $week->sum('revenue'),
            'week_total_before' => $this->sales->revenue(today()->subDays(13), today()->subDays(6)->subSecond()),
            'revenue' => $this->sales->revenue(...$today),
            'revenue_before' => $this->sales->revenue(...$yesterday),
            'orders' => $this->sales->count(...$today),
            'orders_before' => $this->sales->count(...$yesterday),
            'basket' => $monthCount > 0 ? intdiv($this->sales->revenue(...$month), $monthCount) : 0,
            'month_orders' => $monthCount,
            'week_revenue' => $week->pluck('revenue')->values()->all(),
            'week_orders' => $week->pluck('orders')->values()->all(),
        ];
    }

    /**
     * What needs doing now, most urgent first.
     *
     * @return Collection<int, array{key: string, label: string, hint: string, count: string, tone: string, icon: string, url: string}>
     */
    public function todo(User $user): Collection
    {
        $items = collect();
        $add = function (string $key, int|string $count, string $label, string $hint, string $tone, string $icon, string $url) use ($items): void {
            if ($count !== 0 && $count !== '') {
                $items->push(compact('key', 'label', 'hint', 'tone', 'icon', 'url') + ['count' => (string) $count]);
            }
        };

        if ($user->can(Permission::ViewOrders->value)) {
            $count = $this->sales->toHandle();
            $add('orders', $count, $count > 1 ? 'commandes à traiter' : 'commande à traiter', 'Reçues, confirmées ou en préparation', 'orange', 'heroicon-o-shopping-bag',
                OrderResource::getUrl('index', ['filters' => ['status' => ['values' => [OrderStatus::Received->value, OrderStatus::Confirmed->value, OrderStatus::Preparing->value]]]]));
        }

        if ($user->can(Permission::ManageDelivery->value)) {
            $count = $this->dispatcher->unassigned()->count();
            $add('dispatch', $count, $count > 1 ? 'livraisons à attribuer' : 'livraison à attribuer', 'Commandes d’Abidjan sans livreur', 'orange', 'heroicon-o-truck', DeliveryDashboard::getUrl());
        }

        if ($user->can(Permission::ManageOrders->value)) {
            $count = Payment::query()->toRefund()->count();
            $add('refunds', $count, $count > 1 ? 'remboursements dus' : 'remboursement dû', 'Payés en ligne puis annulés', 'red', 'heroicon-o-receipt-refund',
                PaymentResource::getUrl('index', ['tab' => 'to_refund']));

            $count = ContactMessage::query()->whereNull('handled_at')->count();
            $add('messages', $count, $count > 1 ? 'messages clients' : 'message client', 'Formulaire de contact, sans réponse', 'navy', 'heroicon-o-chat-bubble-left-right',
                ContactMessageResource::getUrl());
        }

        if ($user->can(Permission::ViewCatalog->value)) {
            $soldOut = Product::active()->where('stock', 0)->count();
            $add('sold_out', $soldOut, $soldOut > 1 ? 'produits en rupture' : 'produit en rupture', 'En ligne avec un stock à 0', 'red', 'heroicon-o-no-symbol',
                ProductResource::getUrl('index'));

            $threshold = (int) config('storefront.product_card.limited_stock_threshold');
            $low = Product::active()->lowStock($threshold)->count();
            $add('low_stock', $low, $low > 1 ? 'produits en stock bas' : 'produit en stock bas', "{$threshold} unités ou moins", 'gold', 'heroicon-o-archive-box-arrow-down',
                ProductResource::getUrl('index'));

            $reviews = ProductReview::query()->where('status', ReviewStatus::Pending)->count();
            $add('reviews', $reviews, $reviews > 1 ? 'avis à valider' : 'avis à valider', 'Avant leur publication', 'navy', 'heroicon-o-star',
                ProductReviewResource::getUrl('index', ['tab' => 'pending']));
        }

        if ($user->can(Permission::RecordRemittances->value)) {
            $cash = (int) CashSettlement::pending(Order::query())->selectRaw(CashSettlement::owedSql().' as owed')->value('owed');
            $add('cash', $cash > 0 ? Money::format($cash) : 0, 'chez les livreurs', 'Argent encaissé, à reverser', 'gold', 'heroicon-o-banknotes',
                CourierResource::getUrl());
        }

        if ($user->can(Permission::ManagePromotions->value)) {
            $sending = NewsletterCampaign::query()->where('status', CampaignStatus::Sending)->count();
            $add('campaigns', $sending, $sending > 1 ? 'campagnes en cours d’envoi' : 'campagne en cours d’envoi', 'Newsletter', 'navy', 'heroicon-o-megaphone',
                NewsletterCampaignResource::getUrl());
        }

        return $items;
    }

    /**
     * Best sellers of the last 30 days by revenue (cancelled orders left out).
     *
     * @return Collection<int, array{name: string, image: ?string, orders: int, revenue: int}>
     */
    public function topProducts(int $limit = 5): Collection
    {
        return OrderItem::query()
            ->whereHas('order', fn ($query) => $query->where('status', '!=', OrderStatus::Cancelled)->where('created_at', '>=', Carbon::today()->subDays(29)))
            ->selectRaw('product_id, MAX(product_name) as name, MAX(image) as image, COUNT(DISTINCT order_id) as orders, SUM(line_total) as revenue')
            ->groupBy('product_id')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->toBase()
            ->get()
            ->map(fn (object $row) => [
                'name' => (string) $row->name,
                'image' => $row->image,
                'orders' => (int) $row->orders,
                'revenue' => (int) $row->revenue,
            ]);
    }
}
