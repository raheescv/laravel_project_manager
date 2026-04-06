<?php

namespace App\Livewire\Property\PropertyLead;

use App\Models\PropertyLead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Livewire\Component;

class Calendar extends Component
{
    public function getData(Request $request)
    {
        $leads = PropertyLead::query()
            ->with('assignee:id,name')
            ->whereNotNull('meeting_datetime')
            ->whereIn('status', ['Visit Scheduled', 'Follow Up', 'Call Back', 'Follow Up For Visit'])
            ->when($request->employee_id, fn ($q, $v) => $q->where('assigned_to', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->type, fn ($q, $v) => $q->where('type', $v))
            ->when(session('branch_id'), fn ($q) => $q->where('branch_id', session('branch_id')))
            ->select('id', 'name', 'mobile', 'assigned_to', 'meeting_datetime', 'status', 'type')
            ->get();

        $events = $leads->map(function ($lead) {
            $start = Carbon::parse($lead->meeting_datetime);
            $end = $start->copy()->addHour();

            return [
                'id' => $lead->id,
                'title' => trim(($lead->name ?? '').' - '.($lead->mobile ?? '').' - '.($lead->assignee?->name ?? '')),
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
                'url' => route('property::lead::edit', $lead->id),
                'classNames' => [$this->cssClass($lead->status)],
                'extendedProps' => [
                    'assignee' => $lead->assignee?->name ?? 'Unassigned',
                    'mobile' => $lead->mobile,
                    'status' => $lead->status,
                    'type' => $lead->type,
                ],
            ];
        });

        return response()->json($events);
    }

    protected function cssClass(?string $status): string
    {
        return match ($status) {
            'Visit Scheduled' => 'lead-event-visit-scheduled',
            'Follow Up' => 'lead-event-follow-up',
            'Call Back' => 'lead-event-call-back',
            'Follow Up For Visit' => 'lead-event-follow-up-visit',
            default => 'lead-event-default',
        };
    }

    public function render()
    {
        $leadsThisMonth = PropertyLead::query()
            ->when(session('branch_id'), fn ($q) => $q->where('branch_id', session('branch_id')))
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $todaysTasks = PropertyLead::query()
            ->when(session('branch_id'), fn ($q) => $q->where('branch_id', session('branch_id')))
            ->whereDate('meeting_date', now()->toDateString())
            ->count();

        return view('livewire.property.property-lead.calendar', [
            'statuses' => [
                'Visit Scheduled' => 'Visit Scheduled',
                'Follow Up' => 'Follow Up',
                'Call Back' => 'Call Back',
                'Follow Up For Visit' => 'Follow Up For Visit',
            ],
            'types' => leadTypes(),
            'salesUsers' => User::orderBy('name')->pluck('name', 'id')->toArray(),
            'leadsThisMonth' => $leadsThisMonth,
            'todaysTasks' => $todaysTasks,
        ]);
    }
}
