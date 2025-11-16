<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaymentPlan;

class BulkInstallmentUpdateController extends Controller
{
    public function update(Request $request, PaymentPlan $plan)
    {
        $validated = $request->validate([
            'bulk_amount' => 'required|numeric|min:0',
        ]);

        $plan->installments()->update([
            'amount' => $validated['bulk_amount']
        ]);

        return back()->with('success', 'Todas las cuotas del plan han sido actualizadas.');
    }
}