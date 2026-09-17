<?php

namespace App\Livewire\Student;

use App\Actions\Student\CreateAction;
use App\Actions\Student\UpdateAction;
use App\Models\Account;
use App\Models\Country;
use App\Models\Guardian;
use App\Models\StudentDetail;
use App\Traits\OptimizesUploadedImage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Add / edit a student: the account fields, the school fields, and the parents
 * who get a portal login. The card is linked here on create; afterwards it is
 * managed from the student's Card tab (replace, block, unblock).
 */
class Page extends Component
{
    use OptimizesUploadedImage;
    use WithFileUploads;

    public $account_id;

    public $student = [];

    public $guardians = [];

    public $photo;

    public $image;

    public function mount($id = null)
    {
        $this->account_id = $id;
        $this->student = [
            'name' => '',
            'admission_no' => '',
            'gender' => '',
            'dob' => '',
            'grade' => '',
            'section' => '',
            'status' => 'active',
            'mobile' => '',
            'email' => '',
            'id_no' => '',
            'nationality' => '',
            'description' => '',
            'card_uid' => '',
        ];
        $this->guardians = [$this->blankGuardian(true)];

        if ($id) {
            $account = Account::student()->with(['studentDetail', 'guardians'])->findOrFail($id);
            $detail = $account->studentDetail;
            $this->image = $account->image;
            $this->student = array_merge($this->student, [
                'name' => $account->name,
                'mobile' => (string) $account->mobile,
                'email' => (string) $account->email,
                'dob' => (string) $account->dob,
                'id_no' => (string) $account->id_no,
                'nationality' => (string) $account->nationality,
                'description' => (string) $account->description,
                'admission_no' => (string) $detail?->admission_no,
                'gender' => (string) $detail?->gender,
                'grade' => (string) $detail?->grade,
                'section' => (string) $detail?->section,
                'status' => $detail?->status ?: 'active',
                'card_uid' => (string) $detail?->card_uid,
            ]);
            $this->guardians = $account->guardians->map(fn (Guardian $guardian) => [
                'name' => $guardian->name,
                'mobile' => $guardian->mobile,
                'email' => (string) $guardian->email,
                'relation' => $guardian->pivot->relation,
                'is_primary' => (bool) $guardian->pivot->is_primary,
            ])->values()->all() ?: [$this->blankGuardian(true)];
        }
    }

    private function blankGuardian(bool $primary = false): array
    {
        return ['name' => '', 'mobile' => '', 'email' => '', 'relation' => 'father', 'is_primary' => $primary];
    }

    public function addGuardian()
    {
        $this->guardians[] = $this->blankGuardian(! count($this->guardians));
    }

    public function removeGuardian($index)
    {
        unset($this->guardians[$index]);
        $this->guardians = array_values($this->guardians);
    }

    public function makePrimary($index)
    {
        foreach ($this->guardians as $i => $guardian) {
            $this->guardians[$i]['is_primary'] = $i === (int) $index;
        }
    }

    public function removePhoto()
    {
        $this->photo = null;
        $this->image = null;
    }

    public function save()
    {
        abort_unless(auth()->user()?->can($this->account_id ? 'student.edit' : 'student.create'), 403);

        $this->validate([
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ], ['photo.image' => 'The photo must be an image']);

        try {
            $data = $this->student;
            $data['image'] = $this->photo ? $this->storeOptimizedImage($this->photo, 'students') : $this->image;
            $data['guardians'] = $this->guardians;

            DB::beginTransaction();
            $response = $this->account_id
                ? (new UpdateAction())->execute($data, (int) $this->account_id, Auth::id())
                : (new CreateAction())->execute($data, Auth::id());
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            DB::commit();

            session()->flash('success', $response['message']);

            return $this->redirect(route('student::view', $response['data']->id));
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.student.page', [
            'countries' => Country::orderBy('name')->pluck('name', 'name')->toArray(),
            'grades' => StudentDetail::whereNotNull('grade')->distinct()->orderBy('grade')->pluck('grade'),
            'sections' => StudentDetail::whereNotNull('section')->distinct()->orderBy('section')->pluck('section'),
            'detail' => $this->account_id ? StudentDetail::where('account_id', $this->account_id)->first() : null,
        ]);
    }
}
