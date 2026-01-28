<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntryCounterAccount extends Model
{
    protected $fillable = [
        'tenant_id',
        'branch_id',
        'journal_id',
        'journal_entry_id',
        'counter_account_id',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function counterAccount()
    {
        return $this->belongsTo(Account::class, 'counter_account_id');
    }
}
