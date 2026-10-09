<?php

namespace App\Services\Delivery;

use App\Models\CourierRemittance;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Receipt of a courier's payment (PDF, F-126): who handed over how much, how, to whom, and which orders it covers;
 * signed by both sides when the cash changes hands.
 */
class RemittanceReceipt
{
    public function pdf(CourierRemittance $remittance): string
    {
        return Pdf::loadHTML($this->html($remittance))->setPaper('a4')->output();
    }

    public function html(CourierRemittance $remittance): string
    {
        $remittance->loadMissing(['courier.user', 'receivedBy', 'orders']);

        return view('pdf.remittance', [
            'remittance' => $remittance,
        ])->render();
    }

    public function filename(CourierRemittance $remittance): string
    {
        return "versement-{$remittance->number()}.pdf";
    }
}
