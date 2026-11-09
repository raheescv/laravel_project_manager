<?php

namespace App\Actions\Student\Registration;

use App\Actions\Student\CreateAction as StudentCreateAction;
use App\Actions\Student\Guardian\SyncAction;
use App\Models\Guardian;
use App\Models\StudentDetail;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Save a form from the public registration page: each child as a pending student,
 * linked to a pending parent, all or nothing.
 *
 * A mobile number that already has a parent keeps that parent's own name, email
 * and QID. The form is public, and taking its email would let whoever typed the
 * number receive the real parent's password link. What they typed goes in the
 * student's staff notes so the office can spot a mismatch.
 *
 * @param  array{school: string, parent: array{name: string, qid: string, phone: string, email: string}, students: array<int, array{name: string, qid: string, class: string, div: string, reg: string}>}  $data
 */
class CreateAction
{
    public function execute(array $data): array
    {
        try {
            $return = DB::transaction(function () use ($data): array {
                $typed = [
                    'name' => trim($data['parent']['name']),
                    'mobile' => SyncAction::normalizeMobile($data['parent']['phone']),
                    'email' => trim($data['parent']['email']),
                    'id_no' => $data['parent']['qid'],
                ];

                $existing = Guardian::where('mobile', $typed['mobile'])->first();
                $guardian = $existing ?? $this->newPendingParent($typed);
                $note = $existing ? Str::limit("Registered online. Parent entered: {$typed['name']}, QID {$typed['id_no']}, {$typed['email']}", 250) : null;

                foreach ($data['students'] as $student) {
                    $response = (new StudentCreateAction())->execute([
                        'name' => trim($student['name']),
                        'id_no' => $student['qid'],
                        'grade' => $student['class'],
                        'section' => $student['div'],
                        'admission_no' => trim($student['reg']),
                        'school' => $data['school'],
                        'status' => StudentDetail::PENDING,
                        'description' => $note,
                        'guardians' => [[...$guardian->only(['name', 'mobile', 'email', 'id_no']), 'is_primary' => true]],
                    ], null);
                    if (! $response['success']) {
                        throw new Exception($response['message'], 1);
                    }
                }

                return ['success' => true, 'message' => 'Registration received', 'data' => $guardian];
            });
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    private function newPendingParent(array $typed): Guardian
    {
        $row = $typed + ['status' => Guardian::PENDING];
        validationHelper(Guardian::rules(), $row);

        return Guardian::create($row);
    }
}
