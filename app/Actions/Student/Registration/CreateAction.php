<?php

namespace App\Actions\Student\Registration;

use App\Actions\Student\CreateAction as StudentCreateAction;
use App\Actions\Student\Guardian\SyncAction;
use App\Models\Branch;
use App\Models\Guardian;
use App\Models\StudentDetail;
use App\Support\Student\StudentSettings;
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
 * With automatic approval on (Settings → Student Cards → Registration) a form
 * from a NEW parent is approved straight away and `approved` comes back true, so
 * the caller sends the set-password link. A form naming an existing parent's
 * mobile always waits for the office: approving it on the spot would let anyone
 * who knows a parent's number add a child to that family's portal. The one
 * exception is that parent themselves: signed in to the portal ([$signedInGuardianId])
 * and registering under their own number, their children are approved at once and
 * no link is needed, since they already have a password. Without a portal address
 * there is no link to send, so a new parent's form waits too.
 *
 * `existing_parent` is true when the form waits because the number belongs to
 * another parent, so the portal can say so without showing whose it is.
 *
 * `school` is the id of the branch picked (a showcase branch): the child is
 * attached to it, which is their school.
 *
 * @param  array{school: string|int, parent: array{name: string, qid: string, phone: string, email: string}, students: array<int, array{name: string, qid: string, class: string, div: string, reg: string}>}  $data
 */
class CreateAction
{
    public function execute(array $data, ?int $signedInGuardianId = null): array
    {
        try {
            $return = DB::transaction(function () use ($data, $signedInGuardianId): array {
                $typed = [
                    'name' => self::properName($data['parent']['name']),
                    'mobile' => SyncAction::normalizeMobile($data['parent']['phone']),
                    'email' => trim($data['parent']['email']),
                    'id_no' => $data['parent']['qid'],
                ];

                $branch = Branch::query()->findOrFail((int) $data['school']);
                $existing = Guardian::where('mobile', $typed['mobile'])->first();
                $guardian = $existing ?? $this->newPendingParent($typed);
                $ownFamily = $existing && $signedInGuardianId === $existing->id;
                $note = $existing && ! $ownFamily ? Str::limit("Registered online. Parent entered: {$typed['name']}, QID {$typed['id_no']}, {$typed['email']}", 250) : null;

                foreach ($data['students'] as $student) {
                    $response = (new StudentCreateAction())->execute([
                        'name' => self::properName($student['name']),
                        'id_no' => $student['qid'],
                        'grade' => $student['class'],
                        'section' => $student['div'],
                        'admission_no' => trim($student['reg']),
                        'branch_id' => $branch->id,
                        'status' => StudentDetail::PENDING,
                        'description' => $note,
                        'guardians' => [[...$guardian->only(['name', 'mobile', 'email', 'id_no']), 'is_primary' => true]],
                    ], null);
                    if (! $response['success']) {
                        throw new Exception($response['message'], 1);
                    }
                }

                $approved = $ownFamily
                    ? StudentSettings::current()->registrationAutoApprove
                    : ! $existing && $this->approvesOnTheSpot();
                if ($approved) {
                    $response = (new ApproveAction())->execute($guardian->id, null);
                    if (! $response['success']) {
                        throw new Exception($response['message'], 1);
                    }
                    $guardian->refresh();
                }

                return [
                    'success' => true,
                    'message' => 'Registration received',
                    'data' => $guardian,
                    'approved' => $approved,
                    'existing_parent' => $existing && ! $ownFamily,
                ];
            });
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    /**
     * "thameem rahman" or "THAMEEM RAHMAN" → "Thameem Rahman". A name typed with its
     * own capitals ("McDonald") is kept as typed; scripts without case are unchanged.
     */
    public static function properName(string $name): string
    {
        $name = preg_replace('/\s+/u', ' ', trim($name));

        return in_array($name, [mb_strtolower($name), mb_strtoupper($name)], true) ? mb_convert_case(mb_strtolower($name), MB_CASE_TITLE) : $name;
    }

    private function approvesOnTheSpot(): bool
    {
        $settings = StudentSettings::current();

        return $settings->registrationAutoApprove && $settings->portalLink() !== null;
    }

    private function newPendingParent(array $typed): Guardian
    {
        $row = $typed + ['status' => Guardian::PENDING];
        validationHelper(Guardian::rules(), $row);

        return Guardian::create($row);
    }
}
