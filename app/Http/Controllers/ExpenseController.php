<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(): Response
    {
        $expenses = Expense::latest('incurred_on')->get();
        $invoices = Invoice::latest('issued_on')->get()->map(fn (Invoice $i) => [
            'id' => $i->id,
            'number' => $i->number,
            'partner' => $i->partner,
            'trial_code' => $i->trial_code,
            'amount' => (float) $i->amount,
            'currency' => $i->currency,
            'status' => $i->status,
            'status_label' => Invoice::STATUS_LABELS[$i->status] ?? $i->status,
            'issued_on' => $i->issued_on?->format('d/m/Y'),
            'due_on' => $i->due_on?->format('d/m/Y'),
        ]);

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'invoices' => $invoices,
            'totals' => [
                'expenses' => (float) $expenses->sum('amount'),
                'invoices' => (float) Invoice::sum('amount'),
                'unpaid' => (float) Invoice::whereIn('status', ['sent', 'overdue'])->sum('amount'),
                'overdue' => Invoice::where('status', 'overdue')->count(),
            ],
        ]);
    }

    public function storeExpense(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:160'],
            'category' => ['nullable', 'string', 'max:60'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'incurred_on' => ['required', 'date'],
            'trial_code' => ['nullable', 'string', 'max:60'],
            'partner' => ['nullable', 'string', 'max:120'],
        ]);
        Expense::create($data);

        return redirect()->route('expenses.index')->with('success', 'Charge ajoutée.');
    }

    public function storeInvoice(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:60'],
            'partner' => ['required', 'string', 'max:120'],
            'trial_code' => ['nullable', 'string', 'max:60'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'status' => ['required', 'in:'.implode(',', Invoice::STATUSES)],
            'issued_on' => ['required', 'date'],
            'due_on' => ['nullable', 'date'],
        ]);
        Invoice::create($data);

        return redirect()->route('expenses.index')->with('success', 'Facture ajoutée.');
    }

    public function updateInvoiceStatus(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:'.implode(',', Invoice::STATUSES)]]);
        $invoice->update($data);

        return redirect()->route('expenses.index')->with('success', 'Statut mis à jour.');
    }
}
