<?php

namespace App\Http\Controllers\Courier;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\CourierRemittance;
use App\Services\Delivery\CourierFinances;
use App\Services\Delivery\RemittanceReceipt;
use App\Services\Finance\FinancePeriod;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * "Mes encaissements" (F-126): the courier's own deliveries, cash collected and handed over, what they still owe,
 * and the receipt of each payment. Only their own: another courier's payment answers 404. No earnings: the
 * platform pays couriers no commission for now.
 */
class MoneyController extends Controller
{
    /** Periods offered to the courier: the ones they think in. */
    private const PERIODS = ['today' => 'Aujourd’hui', 'week' => 'Cette semaine', 'month' => 'Ce mois'];

    public function index(Request $request, CourierFinances $finances): View
    {
        $courier = $this->courier($request);
        $preset = array_key_exists((string) $request->query('periode'), self::PERIODS) ? (string) $request->query('periode') : 'month';
        $period = FinancePeriod::make($preset);

        return view('courier.money', [
            'courier' => $courier,
            'periods' => self::PERIODS,
            'preset' => $preset,
            'statement' => $finances->statement($courier, $period->from, $period->to),
            'due' => $finances->statement($courier)['due'],
            'history' => $finances->history($courier, 20),
            'remittances' => $courier->remittances()->with('receivedBy')->latest('received_at')->limit(10)->get(),
        ]);
    }

    public function receipt(Request $request, CourierRemittance $remittance, RemittanceReceipt $receipt): Response
    {
        abort_unless($remittance->courier_id === $this->courier($request)->getKey(), 404);

        return response($receipt->pdf($remittance), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$receipt->filename($remittance).'"',
        ]);
    }

    private function courier(Request $request): Courier
    {
        return $request->user()->courier;
    }
}
