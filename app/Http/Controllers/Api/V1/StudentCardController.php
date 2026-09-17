<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Student\Card\AssignAction;
use App\Actions\Student\Card\FindByUidAction;
use App\Actions\Student\GetBalanceAction;
use App\Http\Controllers\Controller;
use App\Livewire\Student\Table as StudentTable;
use App\Models\StudentDetail;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

#[Group('Mobile - Student Cards')]
class StudentCardController extends Controller
{
    use ApiResponseTrait;

    /**
     * Look up a tapped card.
     *
     * Returns the student the card belongs to, with photo, class, card balance,
     * the school's overdraft limit and what can be spent now. 404 for an unknown
     * card; 422 (with the student in `data`) for a blocked card or an inactive student.
     */
    public function show(string $uid): JsonResponse
    {
        try {
            $student = (new FindByUidAction())->execute($uid);
            if (! $student) {
                return $this->sendNotFoundError('This card is not linked to any student.');
            }
            if ($student['card_status'] === StudentDetail::CARD_BLOCKED) {
                return $this->sendError($student['name'].'\'s card is blocked. Ask the school office.', $student, 422);
            }
            if ($student['status'] !== 'active') {
                return $this->sendError($student['name'].' is not an active student.', $student, 422);
            }

            return $this->sendSuccess($student, 'Card found');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to read the card: '.$e->getMessage());
        }
    }

    /**
     * Search students (to link a card).
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $students = StudentTable::filteredQuery(['search' => (string) $request->query('search', ''), 'status' => 'active'])
                ->select('accounts.id', 'accounts.name', 'accounts.image', 'student_details.admission_no', 'student_details.grade', 'student_details.section', 'student_details.card_uid', 'student_details.card_status')
                ->orderBy('accounts.name')
                ->limit(30)
                ->get();
            $balances = (new GetBalanceAction())->many($students->pluck('id')->all());

            return $this->sendSuccess($students->map(fn ($student) => [
                'account_id' => $student->id,
                'name' => $student->name,
                'image_url' => $student->image ? $student->image_url : null,
                'admission_no' => $student->admission_no,
                'grade' => $student->grade,
                'section' => $student->section,
                'card_uid' => $student->card_uid,
                'card_status' => $student->card_status,
                'balance' => $balances[$student->id] ?? 0.0,
            ])->values(), 'Students retrieved');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to search students: '.$e->getMessage());
        }
    }

    /**
     * Link a tapped card to a student (or replace a lost one).
     */
    public function link(Request $request, int $account): JsonResponse
    {
        $request->validate(['cardUid' => ['required', 'string', 'max:40']]);

        try {
            $response = DB::transaction(function () use ($request, $account) {
                $response = (new AssignAction())->execute($account, $request->input('cardUid'), (int) $request->user()->id);
                if (! $response['success']) {
                    throw new \RuntimeException($response['message']);
                }

                return $response;
            });

            return $this->sendSuccess(FindByUidAction::summary($response['data']->load('account')), $response['message']);
        } catch (\RuntimeException $e) {
            return $this->sendError($e->getMessage(), [], 422);
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to link the card: '.$e->getMessage());
        }
    }
}
