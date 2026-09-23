<?php

namespace App\Http\Controllers;

use App\Models\Account;

class StudentController extends Controller
{
    public function index()
    {
        return view('student.index');
    }

    public function page($id = null)
    {
        if ($id) {
            abort_unless(Account::student()->whereKey($id)->exists(), 404);
        }

        return view('student.page', compact('id'));
    }

    public function import()
    {
        return view('student.import');
    }

    /** Every student's card balance, with the movement over a period. */
    public function walletReport()
    {
        return view('report.student-wallet');
    }

    /** QPay top-ups and refunds across all students. */
    public function rechargeReport()
    {
        return view('report.student-recharges');
    }

    public function canteenMenu()
    {
        return view('student.canteen-menu');
    }

    public function view($id)
    {
        abort_unless(Account::student()->whereKey($id)->exists(), 404);

        return view('student.view', compact('id'));
    }

    /** Every parent portal login, across all students, with their linked children. */
    public function guardians()
    {
        return view('student.guardians');
    }
}
