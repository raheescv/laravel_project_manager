<?php

namespace App\Http\Controllers\Property;

use App\Enums\RentOut\AgreementType;
use App\Http\Controllers\Controller;

class RentOutReportController extends Controller
{
    public function customerProperty(?string $agreement_type = null)
    {
        $type = $agreement_type ? AgreementType::tryFrom($agreement_type) : null;
        $this->authorizeReport('customer property report', $type);

        return view('property.report.customer-property', [
            'agreementType' => $type->value ?? '',
            'typeLabel' => $type?->label(),
        ]);
    }

    public function security(?string $agreement_type = null)
    {
        $type = $agreement_type ? AgreementType::tryFrom($agreement_type) : null;
        $this->authorizeReport('security report', $type);

        return view('property.report.security', [
            'agreementType' => $type->value ?? '',
            'typeLabel' => $type?->label(),
        ]);
    }

    public function serviceCharge()
    {
        return view('property.report.service-charge');
    }

    public function roi()
    {
        return view('property.report.roi');
    }

    public function commissions()
    {
        return view('property.report.commissions');
    }

    public function commission()
    {
        return view('property.report.commission');
    }

    public function daybook(?string $agreement_type = null)
    {
        $type = $agreement_type ? AgreementType::tryFrom($agreement_type) : null;
        $this->authorizeReport('daybook', $type);

        return view('property.report.daybook', [
            'agreementType' => $type->value ?? '',
            'typeLabel' => $type?->label(),
        ]);
    }

    /**
     * A report shared by rental and sale needs that type's report permission;
     * without a type, either one opens it.
     */
    protected function authorizeReport(string $report, ?AgreementType $type): void
    {
        $abilities = match ($type) {
            AgreementType::Rental => ["rent out.{$report}"],
            AgreementType::Lease => ["rent out lease.{$report}"],
            default => ["rent out.{$report}", "rent out lease.{$report}"],
        };

        abort_unless(auth()->user()?->canAny($abilities), 403);
    }
}
