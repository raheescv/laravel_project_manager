<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Parent\FindStudentAction;
use App\Actions\Parent\GetBillAction;
use App\Actions\Parent\ListBillsAction;
use App\Actions\Parent\ListStudentsAction;
use App\Actions\QPay\HandleReturnAction;
use App\Actions\Student\GetStatementAction;
use App\Actions\Student\Guardian\SendInviteAction;
use App\Actions\Student\Guardian\SetPasswordAction as GuardianSetPasswordAction;
use App\Actions\V1\Parent\BlockCardAction;
use App\Actions\V1\Parent\ForgotPasswordAction;
use App\Actions\V1\Parent\GetStudentAction;
use App\Actions\V1\Parent\GetTopupAction;
use App\Actions\V1\Parent\LoginAction;
use App\Actions\V1\Parent\SetPasswordAction;
use App\Actions\V1\Parent\StartTopupAction;
use App\Exceptions\ParentPortalException;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Parent\BlockCardRequest;
use App\Http\Requests\V1\Parent\ForgotPasswordRequest;
use App\Http\Requests\V1\Parent\LoginRequest;
use App\Http\Requests\V1\Parent\PeriodRequest;
use App\Http\Requests\V1\Parent\SetPasswordRequest;
use App\Http\Requests\V1\Parent\StartTopupRequest;
use App\Http\Resources\V1\Parent\BillDetailResource;
use App\Http\Resources\V1\Parent\BillResource;
use App\Http\Resources\V1\Parent\GuardianResource;
use App\Http\Resources\V1\Parent\TopupResource;
use App\Models\Guardian;
use App\Services\EmailTemplateRenderer;
use App\Services\Payment\QPayClient;
use App\Support\Student\StudentSettings;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The parent portal API (routes/api_v1_parent.php), consumed by the standalone
 * parent_portal app. Every student, bill and top-up is reached through the
 * signed-in parent, so a guessed id is a 404.
 */
#[Group('Parent Portal')]
class ParentPortalController extends Controller
{
    use ApiResponseTrait;

    /**
     * School branding.
     *
     * Public. The school's name, logo, theme colour and currency — what the portal
     * paints before anyone signs in.
     */
    public function school(): JsonResponse
    {
        $base = base_currency();

        return $this->sendSuccess([
            'name' => tenant_cache('company_name', '') ?: config('app.name'),
            'logo' => tenant_cache('logo', null) ?: null,
            'accent' => EmailTemplateRenderer::accent(),
            'currency' => [
                'code' => $base['code'] ?? 'QAR',
                'decimals' => currency_decimals(),
            ],
            'contact' => [
                'mobile' => tenant_cache('mobile', null) ?: null,
                'email' => tenant_cache('email', null) ?: null,
            ],
        ], 'School retrieved successfully');
    }

    /**
     * Sign in.
     *
     * Mobile number (or email) and password. Returns a bearer token that expires
     * after 30 days with `remember`, otherwise after 12 hours. 422 for wrong
     * details, 429 after five failed tries.
     */
    public function login(LoginRequest $request, LoginAction $action): JsonResponse
    {
        try {
            return $this->sendSuccess($action->execute($request), 'Signed in');
        } catch (ValidationException $e) {
            return $this->validationFailure($e);
        } catch (\Throwable $e) {
            return $this->failure($e, 'We could not sign you in. Please try again.');
        }
    }

    /**
     * Forgot password.
     *
     * Sends a new set-password link by email / WhatsApp when the number belongs to
     * an active parent. The answer never says whether it does.
     */
    public function forgotPassword(ForgotPasswordRequest $request, ForgotPasswordAction $action): JsonResponse
    {
        try {
            $action->execute($request);

            return $this->sendSuccess(null, 'If this mobile number is registered with the school, a link to set a new password is on its way by email or WhatsApp.');
        } catch (ValidationException $e) {
            return $this->validationFailure($e);
        } catch (\Throwable $e) {
            return $this->failure($e, 'We could not send the link. Please try again.');
        }
    }

    /**
     * Check a set-password link.
     *
     * Whether the link from the invite / reset email still works, and the parent's
     * name to greet them with.
     */
    public function passwordLink(string $token): JsonResponse
    {
        $guardian = GuardianSetPasswordAction::findByToken($token);

        return $this->sendSuccess([
            'valid' => (bool) $guardian,
            'name' => $guardian?->name,
            'valid_days' => SendInviteAction::VALID_DAYS,
        ], $guardian ? 'Link is valid' : 'This link has expired or was already used.');
    }

    /**
     * Set password.
     *
     * Redeems the one-time link, ends the parent's other sign-ins and signs them in
     * (same response as Sign in).
     */
    public function setPassword(SetPasswordRequest $request, SetPasswordAction $action): JsonResponse
    {
        try {
            return $this->sendSuccess($action->execute($request), 'Your password is set. Welcome!');
        } catch (ParentPortalException $e) {
            return $this->sendError($e->getMessage(), [], 422);
        } catch (\Throwable $e) {
            return $this->failure($e, 'We could not save your password. Please try again.');
        }
    }

    /**
     * The signed-in parent.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->sendSuccess(new GuardianResource($this->guardian($request)), 'Parent retrieved successfully');
    }

    /**
     * Sign out.
     *
     * Revokes the token used for this request.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->guardian($request)->currentAccessToken()->delete();

        return $this->sendSuccess(null, 'Signed out');
    }

    /**
     * My children.
     *
     * Each child linked to the parent, with card status, balance and what the card
     * can spend now (balance + the school's overdraft limit).
     */
    public function students(Request $request): JsonResponse
    {
        try {
            return $this->sendSuccess((new ListStudentsAction())->execute($this->guardian($request)), 'Students retrieved successfully');
        } catch (\Throwable $e) {
            return $this->failure($e, 'We could not load your children. Please try again.');
        }
    }

    /**
     * One child.
     *
     * The home summary plus the overdraft limit, when the card was blocked, and the
     * online top-up range (`topup.enabled` is false when the school has not set it up).
     */
    public function student(Request $request, int $account, GetStudentAction $action): JsonResponse
    {
        try {
            return $this->sendSuccess($action->execute($this->guardian($request), $account), 'Student retrieved successfully');
        } catch (NotFoundHttpException) {
            return $this->sendNotFoundError('Student not found');
        } catch (\Throwable $e) {
            return $this->failure($e, 'We could not load this child. Please try again.');
        }
    }

    /**
     * Bills.
     *
     * The child's completed purchases in a month (`month=YYYY-MM`, default this
     * month), newest first, 15 per page, from every canteen.
     */
    public function bills(PeriodRequest $request, int $account): JsonResponse
    {
        try {
            $student = (new FindStudentAction())->execute($this->guardian($request), $account);
            [$from, $to] = $request->period();
            $bills = (new ListBillsAction())->execute($student->id, $from, $to);

            return $this->sendSuccess([
                'data' => BillResource::collection($bills->items()),
                'pagination' => [
                    'current_page' => $bills->currentPage(),
                    'last_page' => $bills->lastPage(),
                    'per_page' => $bills->perPage(),
                    'total' => $bills->total(),
                    'has_more_pages' => $bills->hasMorePages(),
                ],
                'period' => ['from' => $from, 'to' => $to],
            ], 'Bills retrieved successfully');
        } catch (NotFoundHttpException) {
            return $this->sendNotFoundError('Student not found');
        } catch (\Throwable $e) {
            return $this->failure($e, 'We could not load the bills. Please try again.');
        }
    }

    /**
     * One bill.
     *
     * Its lines, discount, tax, total and how it was paid.
     */
    public function bill(Request $request, int $account, int $sale): JsonResponse
    {
        try {
            $student = (new FindStudentAction())->execute($this->guardian($request), $account);

            return $this->sendSuccess(new BillDetailResource((new GetBillAction())->execute($student->id, $sale)), 'Bill retrieved successfully');
        } catch (NotFoundHttpException) {
            return $this->sendNotFoundError('Bill not found');
        } catch (\Throwable $e) {
            return $this->failure($e, 'We could not load this bill. Please try again.');
        }
    }

    /**
     * Card statement.
     *
     * The balance brought forward, every top-up, purchase, return and refund in the
     * month with a running balance, and the closing balance.
     */
    public function statement(PeriodRequest $request, int $account): JsonResponse
    {
        try {
            $student = (new FindStudentAction())->execute($this->guardian($request), $account);
            [$from, $to] = $request->period();
            $statement = (new GetStatementAction())->execute($student->id, $from, $to);

            return $this->sendSuccess([
                'opening' => $statement['opening'],
                'closing' => $statement['closing'],
                'credit' => $statement['credit'],
                'debit' => $statement['debit'],
                'rows' => array_map(fn (array $row) => [
                    'id' => $row['id'],
                    'date' => $row['date'],
                    'type' => $row['type'],
                    'source' => $row['source'],
                    'description' => $row['description'],
                    'credit' => $row['credit'],
                    'debit' => $row['debit'],
                    'balance' => $row['balance'],
                ], $statement['rows']),
                'period' => ['from' => $from, 'to' => $to],
            ], 'Statement retrieved successfully');
        } catch (NotFoundHttpException) {
            return $this->sendNotFoundError('Student not found');
        } catch (\Throwable $e) {
            return $this->failure($e, 'We could not load the statement. Please try again.');
        }
    }

    /**
     * Block a lost card.
     *
     * Refused at the canteen from the next tap. A parent can block but never
     * unblock — only the school office can. Returns the child as in "One child".
     */
    public function blockCard(BlockCardRequest $request, int $account, BlockCardAction $action, GetStudentAction $student): JsonResponse
    {
        try {
            $guardian = $this->guardian($request);
            $message = $action->execute($guardian, $account, $request->validated('reason'));

            return $this->sendSuccess($student->execute($guardian, $account), $message);
        } catch (NotFoundHttpException) {
            return $this->sendNotFoundError('Student not found');
        } catch (ParentPortalException $e) {
            return $this->sendError($e->getMessage(), [], 422);
        } catch (\Throwable $e) {
            return $this->failure($e, 'We could not block the card. Please call the school office.');
        }
    }

    /**
     * Start a top-up.
     *
     * Opens a QPay payment and returns the form to post to QPay (`payment.url` +
     * `payment.fields`). QPay returns the parent to the portal's `#/topups/{pun}`.
     * 422 when the amount is outside the school's range, an earlier top-up is still
     * being confirmed, or online top-up is not set up.
     */
    public function startTopup(StartTopupRequest $request, int $account, StartTopupAction $action): JsonResponse
    {
        try {
            return $this->sendSuccess($action->execute($request, $this->guardian($request), $account), 'Top-up started', 201);
        } catch (NotFoundHttpException) {
            return $this->sendNotFoundError('Student not found');
        } catch (ParentPortalException $e) {
            return $this->sendError($e->getMessage(), [], 422);
        } catch (\Throwable $e) {
            return $this->failure($e, 'We could not start the payment. Please try again.');
        }
    }

    /**
     * Top-up result.
     *
     * Where a top-up stands. Poll while `status` is `pending`: QPay's result can
     * arrive a moment after the parent is back. `balance` is the card's new balance
     * once it succeeded.
     */
    public function topup(Request $request, string $pun, GetTopupAction $action): JsonResponse
    {
        try {
            return $this->sendSuccess(new TopupResource($action->execute($this->guardian($request), $pun)), 'Top-up retrieved successfully');
        } catch (ModelNotFoundException) {
            return $this->sendNotFoundError('Top-up not found');
        } catch (\Throwable $e) {
            return $this->failure($e, 'We could not load this payment. Please try again.');
        }
    }

    /**
     * QPay return.
     *
     * QPay posts the payment result here from the parent's browser (no token: it is
     * authenticated by QPay's secure hash and re-checked with the inquiry API), and
     * the browser is sent on to the portal's result page.
     */
    public function qpayReturn(Request $request): Response
    {
        $raw = (string) $request->getContent();
        $pun = QPayClient::parseResponse($raw)['values']['PUN'] ?? null;

        $failed = false;
        try {
            $transaction = (new HandleReturnAction())->execute($raw);
        } catch (\Throwable $e) {
            // Left pending: the scheduled inquiry (qpay:inquire-pending) settles it,
            // and the result page keeps checking.
            report($e);
            [$transaction, $failed] = [null, true];
        }

        $link = StudentSettings::current()->portalLink(match (true) {
            (bool) $transaction => 'topups/'.$transaction->pun,
            $failed && filled($pun) => 'topups/'.rawurlencode($pun),
            default => '?payment=unmatched',
        });

        if (! $link) {
            Log::warning('QPay return with no parent portal address to send the parent to', ['pun' => $pun]);

            return response('Payment received. Open the parent portal to see the result.', 200)->header('Content-Type', 'text/plain');
        }

        return redirect()->away($link);
    }

    private function guardian(Request $request): Guardian
    {
        return $request->user('parent');
    }

    private function validationFailure(ValidationException $e): JsonResponse
    {
        return $this->sendError((string) collect($e->errors())->flatten()->first(), $e->errors(), $e->status);
    }

    private function failure(\Throwable $e, string $message): JsonResponse
    {
        report($e);

        return $this->sendServerError($message);
    }
}
