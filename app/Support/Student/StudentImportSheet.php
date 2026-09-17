<?php

namespace App\Support\Student;

use App\Actions\Student\CreateAction;
use App\Actions\Student\Guardian\SyncAction as GuardianSyncAction;
use App\Models\Account;
use App\Models\Guardian;
use App\Models\StudentDetail;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Unique;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * How a student import sheet is read.
 *
 * Shared by the import screen (column matching and the row check) and the queued
 * App\Imports\StudentImport, so a row the screen calls ready is a row the job
 * saves, and a row it flags is a row the job leaves out.
 *
 * Fields are keyed by the StudentImportTemplate headings. `$mappings` points each
 * field at a heading key of the uploaded sheet: Maatwebsite's slugged heading
 * ("Father Mobile" → "father_mobile"), or the column index when the header cell
 * is blank.
 *
 * The object is stateful on purpose: the job reviews its file chunk by chunk, and
 * an admission number or card repeated in a later chunk must still be caught.
 */
class StudentImportSheet
{
    /** Rows one file may hold; the screen checks every one of them before import. */
    public const MAX_ROWS = 10000;

    public const REQUIRED = ['admission_no', 'name'];

    public const GROUPS = [
        'student' => ['label' => 'Student', 'fields' => [
            'admission_no' => 'Admission No',
            'name' => 'Student name',
            'grade' => 'Grade',
            'section' => 'Section',
            'gender' => 'Gender',
            'dob' => 'Date of birth',
            'qid' => 'QID',
            'nationality' => 'Nationality',
            'student_mobile' => 'Mobile',
            'student_email' => 'Email',
            'status' => 'Status',
            'card_uid' => 'Card UID',
        ]],
        'parent' => ['label' => 'First parent', 'fields' => [
            'parent_name' => 'Name',
            'parent_mobile' => 'Mobile',
            'parent_email' => 'Email',
            'parent_relation' => 'Relation',
        ]],
        'second_parent' => ['label' => 'Second parent', 'fields' => [
            'second_parent_name' => 'Name',
            'second_parent_mobile' => 'Mobile',
            'second_parent_email' => 'Email',
            'second_parent_relation' => 'Relation',
        ]],
    ];

    /**
     * Header spellings per field, lower-cased with everything but letters and digits
     * removed. Order matters: a column is given to the first field that claims it.
     */
    private const ALIASES = [
        'admission_no' => ['admissionno', 'admissionnumber', 'admission', 'admno', 'admnumber', 'adm', 'studentid', 'studentno', 'studentnumber', 'enrollmentno', 'enrolmentno', 'enrollmentnumber', 'enrolmentnumber', 'grno', 'grnumber', 'registrationno', 'regno'],
        'name' => ['name', 'studentname', 'fullname', 'student', 'studentfullname', 'nameofstudent', 'pupilname', 'childname'],
        'grade' => ['grade', 'class', 'standard', 'std', 'year', 'yeargroup', 'classname', 'gradelevel'],
        'section' => ['section', 'division', 'div', 'classsection', 'sectionname'],
        'gender' => ['gender', 'sex'],
        'dob' => ['dob', 'dateofbirth', 'birthdate', 'birthday', 'dobddmmyyyy'],
        'qid' => ['qid', 'qidno', 'qatarid', 'idno', 'idnumber', 'nationalid', 'nationalidno', 'civilid', 'iqama', 'emiratesid', 'cpr'],
        'nationality' => ['nationality', 'country', 'citizenship'],
        'student_mobile' => ['studentmobile', 'studentphone', 'studentcontact', 'studentmobileno'],
        'student_email' => ['studentemail', 'studentmail'],
        'status' => ['status', 'studentstatus'],
        'card_uid' => ['carduid', 'card', 'cardno', 'cardnumber', 'cardid', 'rfid', 'rfidno', 'rfiduid', 'nfc', 'nfcuid', 'uid', 'canteencard'],
        'parent_name' => ['parentname', 'parent', 'fathername', 'father', 'guardianname', 'guardian', 'primaryparent', 'primaryparentname', 'parent1name', 'parent1', 'firstparentname'],
        'parent_mobile' => ['parentmobile', 'parentphone', 'parentcontact', 'fathermobile', 'fatherphone', 'fathercontact', 'guardianmobile', 'guardianphone', 'parent1mobile', 'firstparentmobile', 'primarymobile', 'mobile', 'phone', 'mobileno', 'phoneno', 'contactno', 'contact'],
        'parent_email' => ['parentemail', 'fatheremail', 'guardianemail', 'parent1email', 'firstparentemail', 'email', 'emailaddress'],
        'parent_relation' => ['parentrelation', 'relation', 'relationship', 'parent1relation', 'firstparentrelation', 'guardianrelation'],
        'second_parent_name' => ['secondparentname', 'secondparent', 'mothername', 'mother', 'parent2name', 'parent2', 'secondaryparentname'],
        'second_parent_mobile' => ['secondparentmobile', 'mothermobile', 'motherphone', 'mothercontact', 'parent2mobile', 'secondaryparentmobile'],
        'second_parent_email' => ['secondparentemail', 'motheremail', 'parent2email'],
        'second_parent_relation' => ['secondparentrelation', 'parent2relation', 'motherrelation'],
    ];

    public const PARENTS = ['parent' => 'First parent', 'second_parent' => 'Second parent'];

    private const ATTRIBUTES = [
        'admission_no' => 'Admission No',
        'name' => 'Student name',
        'mobile' => 'Student mobile',
        'email' => 'Student email',
        'gender' => 'Gender',
        'grade' => 'Grade',
        'section' => 'Section',
        'status' => 'Status',
        'card_uid' => 'Card UID',
    ];

    private const MESSAGES = [
        'required' => ':Attribute is required.',
        'max' => ':Attribute is longer than :max characters.',
        'email' => ':Attribute ":input" is not an email address.',
        'gender.in' => 'Gender ":input" should be male or female.',
        'status.in' => 'Status ":input" should be active, inactive or graduated.',
        'relation.in' => ':Attribute ":input" should be father, mother or guardian.',
    ];

    /** @var array<string, string> field => heading key */
    private array $mappings;

    /** @var array<string, string> parent prefix => relation used when the cell is blank */
    private array $relationDefaults;

    /** @var array<string, int> lower-cased admission no => the line that first used it */
    private array $admissionLines = [];

    /** @var array<string, int> card uid => the line that first used it */
    private array $cardLines = [];

    /** @var array<string, true> parent mobiles on rows that will be saved */
    private array $parentMobiles = [];

    private ?array $rowRules = null;

    /**
     * @param  array<string, string|int|null>  $mappings  field => heading key; empty means the template's own headings
     */
    public function __construct(array $mappings = [], private string $duplicateStrategy = 'skip')
    {
        $this->mappings = $mappings ? self::cleanMappings($mappings) : array_combine(self::fields(), self::fields());
        $this->relationDefaults = self::relationDefaults($this->mappings);
    }

    /** @return list<string> every field, in template order */
    public static function fields(): array
    {
        return array_merge(...array_map(fn (array $group) => array_keys($group['fields']), array_values(self::GROUPS)));
    }

    /** @return array<string, string> mapped fields only, keys as strings */
    public static function cleanMappings(array $mappings): array
    {
        $clean = [];
        foreach (self::fields() as $field) {
            $key = $mappings[$field] ?? null;
            if ($key !== null && $key !== '') {
                $clean[$field] = (string) $key;
            }
        }

        return $clean;
    }

    /**
     * Pick a sheet column for every field from the header text.
     *
     * Exact spellings are tried for every field before looser ones ("Father's
     * Mobile No." → "fathermobile"), so a close match never takes a column
     * another field names exactly.
     *
     * @param  array<int|string, string>  $headers  heading key => header as typed
     * @return array<string, string> field => heading key
     */
    public static function guessMappings(array $headers): array
    {
        $forms = [];
        foreach ($headers as $key => $label) {
            $forms[] = [(string) $key, self::headerForms((string) $label)];
        }

        $mappings = [];
        foreach ([true, false] as $exact) {
            foreach (self::ALIASES as $field => $aliases) {
                if (isset($mappings[$field])) {
                    continue;
                }
                foreach ($forms as [$key, $variants]) {
                    if (in_array($key, $mappings, true)) {
                        continue;
                    }
                    $candidates = $exact ? array_slice($variants, 0, 1) : array_slice($variants, 1);
                    if (array_intersect($candidates, $aliases)) {
                        $mappings[$field] = $key;
                        break;
                    }
                }
            }
        }

        return array_intersect_key(array_replace(array_flip(self::fields()), $mappings), $mappings);
    }

    /** @return list<string> the header as typed, then looser spellings ("Mother's Mobile No." → "mothermobile") */
    private static function headerForms(string $label): array
    {
        $exact = preg_replace('/[^a-z0-9]/', '', strtolower($label));
        $singular = preg_replace('/(father|mother|parent|student|guardian)s/', '$1', $exact);
        $bare = preg_replace('/(number|num|no)$/', '', $singular);

        return array_values(array_unique([$exact, $singular, $bare]));
    }

    /**
     * The relation used for a parent whose relation cell is blank: "father" or
     * "mother" when that parent's own columns say so ("Mother Mobile"), otherwise
     * "guardian".
     *
     * @return array<string, string> parent prefix => relation
     */
    public static function relationDefaults(array $mappings): array
    {
        $defaults = [];
        foreach (array_keys(self::PARENTS) as $prefix) {
            $keys = strtolower(implode(' ', [$mappings["{$prefix}_name"] ?? '', $mappings["{$prefix}_mobile"] ?? '', $mappings["{$prefix}_email"] ?? '']));
            $defaults[$prefix] = str_contains($keys, 'father') ? 'father' : (str_contains($keys, 'mother') ? 'mother' : 'guardian');
        }

        return $defaults;
    }

    /**
     * The Student\CreateAction / UpdateAction payload for one row.
     *
     * @param  array<int|string, mixed>  $row  heading key => cell
     */
    public function rowData(array $row): array
    {
        $data = [
            'admission_no' => $this->cell($row, 'admission_no'),
            'name' => $this->cell($row, 'name'),
            'grade' => $this->cell($row, 'grade'),
            'section' => $this->cell($row, 'section'),
            'gender' => self::gender($this->cell($row, 'gender')),
            'dob' => self::date($this->cell($row, 'dob')),
            'id_no' => $this->cell($row, 'qid'),
            'nationality' => $this->cell($row, 'nationality'),
            'mobile' => $this->cell($row, 'student_mobile'),
            'email' => $this->cell($row, 'student_email'),
            'status' => strtolower($this->cell($row, 'status')) ?: 'active',
            'card_uid' => $this->cell($row, 'card_uid'),
        ];

        // No parent columns filled means "leave the parents alone" on an update.
        if ($parents = $this->parents($row)) {
            $data['guardians'] = array_values($parents);
        }

        return $data;
    }

    /**
     * Check rows without saving anything.
     *
     * Each entry carries a `status` — new, update, skip or error — the `issues`
     * that make it an error, `notes` about what will quietly not happen, the
     * `data` to save and the mapped cell `values` (for the issues download).
     *
     * @param  array<int, array>  $rows  sheet line number => row (heading key => cell)
     * @return list<array> one entry per non-blank row
     */
    public function review(array $rows): array
    {
        $parsed = [];
        foreach ($rows as $line => $row) {
            $values = [];
            foreach ($this->mappings as $field => $key) {
                $values[$field] = $this->cell($row, $field);
            }
            if (implode('', $values) !== '') {
                $parsed[$line] = ['row' => $row, 'data' => $this->rowData($row), 'values' => $values];
            }
        }

        [$byAdmission, $byCard] = $this->existingStudents($parsed);

        $entries = [];
        foreach ($parsed as $line => ['row' => $row, 'data' => $data, 'values' => $values]) {
            $admissionKey = mb_strtolower($data['admission_no']);
            $existing = $admissionKey !== '' ? ($byAdmission[$admissionKey] ?? null) : null;
            $card = StudentDetail::normalizeCardUid($data['card_uid']);
            $parents = $this->parents($row);
            $issues = [];
            $notes = [];

            if ($existing && $this->duplicateStrategy !== 'update') {
                $entries[] = $this->entry($line, 'skip', $existing, $data, $values, $parents, [], []);

                continue;
            }

            $issues = $this->validate(CreateAction::accountData($data) + CreateAction::detailData($data), $this->rowRules(), self::ATTRIBUTES);

            $mobiles = [];
            foreach ($parents as $prefix => $parent) {
                $label = self::PARENTS[$prefix];
                $parent['mobile'] = GuardianSyncAction::normalizeMobile($parent['mobile']);
                array_push($issues, ...$this->validate($parent, GuardianSyncAction::rules(), [
                    'name' => "{$label} name", 'mobile' => "{$label} mobile", 'email' => "{$label} email", 'relation' => "{$label} relation",
                ]));
                if ($parent['mobile'] !== '' && isset($mobiles[$parent['mobile']])) {
                    $issues[] = "Both parents have the mobile {$parent['mobile']}.";
                }
                $mobiles[$parent['mobile']] = true;
            }

            if ($admissionKey !== '' && isset($this->admissionLines[$admissionKey])) {
                $issues[] = "Admission No {$data['admission_no']} is already used on row {$this->admissionLines[$admissionKey]}.";
            }

            if ($card !== null && $existing) {
                if ($card !== $existing->card_uid) {
                    $notes[] = "Card left as it is — change a student's card from their Card tab.";
                }
            } elseif ($card !== null && isset($this->cardLines[$card])) {
                $issues[] = "Card {$card} is already used on row {$this->cardLines[$card]}.";
            } elseif ($card !== null && isset($byCard[$card])) {
                $owner = $byCard[$card];
                $issues[] = "Card {$card} is already linked to {$owner->admission_no}".($owner->account ? " ({$owner->account->name})" : '').'.';
            }

            if (($values['dob'] ?? '') !== '' && $data['dob'] === null) {
                $notes[] = "Date of birth \"{$values['dob']}\" was not understood and is left blank.";
            }
            if (! $parents && ! $existing) {
                $notes[] = 'No parent — nobody can sign in to the parent portal for this student.';
            }

            // Only a row that will be saved holds its admission number, card and parents,
            // so fixing a bad row and keeping its twin does not flag the twin.
            if (! $issues) {
                if ($admissionKey !== '') {
                    $this->admissionLines[$admissionKey] = $line;
                }
                if ($card !== null && ! $existing) {
                    $this->cardLines[$card] = $line;
                }
                foreach (array_keys($mobiles) as $mobile) {
                    if ((string) $mobile !== '') {
                        $this->parentMobiles[(string) $mobile] = true;
                    }
                }
            }

            $entries[] = $this->entry($line, $issues ? 'error' : ($existing ? 'update' : 'new'), $existing, $data, $values, $parents, $issues, $notes);
        }

        return $entries;
    }

    /** @return array{new: int, existing: int} parent logins among the rows reviewed as saveable */
    public function parentLogins(): array
    {
        $mobiles = array_map('strval', array_keys($this->parentMobiles));
        $existing = 0;
        foreach (array_chunk($mobiles, 1000) as $chunk) {
            $existing += Guardian::query()->whereIn('mobile', $chunk)->count();
        }

        return ['new' => count($mobiles) - $existing, 'existing' => $existing];
    }

    /** Excel serial numbers, day-first dates (14/03/2015) or anything Carbon reads; null when it is not a sensible date. */
    public static function date($value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                $date = (float) $value >= 1 && (float) $value < 2958466 ? ExcelDate::excelToDateTimeObject((float) $value) : null;
            } elseif (preg_match('#^(\d{1,2})[/.\-](\d{1,2})[/.\-](\d{4})$#', (string) $value, $m)) {
                $date = checkdate((int) $m[2], (int) $m[1], (int) $m[3]) ? Carbon::create((int) $m[3], (int) $m[2], (int) $m[1]) : null;
            } else {
                $date = Carbon::parse((string) $value);
            }
        } catch (\Throwable) {
            return null;
        }

        return $date && (int) $date->format('Y') >= 1900 && (int) $date->format('Y') <= (int) date('Y') + 1 ? $date->format('Y-m-d') : null;
    }

    private static function gender(string $value): string
    {
        $value = strtolower($value);

        return match ($value) {
            'm', 'boy' => 'male',
            'f', 'girl' => 'female',
            default => $value,
        };
    }

    /** @return array<string, array{name: string, mobile: string, email: ?string, relation: string, is_primary: bool}> */
    private function parents(array $row): array
    {
        $parents = [];
        foreach (array_keys(self::PARENTS) as $prefix) {
            $name = $this->cell($row, "{$prefix}_name");
            $mobile = $this->cell($row, "{$prefix}_mobile");
            if ($name === '' && $mobile === '') {
                continue;
            }
            $parents[$prefix] = [
                'name' => $name,
                'mobile' => $mobile,
                'email' => $this->cell($row, "{$prefix}_email") ?: null,
                'relation' => strtolower($this->cell($row, "{$prefix}_relation")) ?: $this->relationDefaults[$prefix],
                'is_primary' => $prefix === 'parent',
            ];
        }

        return $parents;
    }

    private function cell(array $row, string $field): string
    {
        $key = $this->mappings[$field] ?? null;
        if ($key === null || ! array_key_exists($key, $row)) {
            return '';
        }

        $value = $row[$key];
        // Whole numbers read as floats ("55123456.0") must not come back in exponent form.
        if (is_float($value) && floor($value) === $value && abs($value) < 1e15) {
            return sprintf('%.0f', $value);
        }

        return trim((string) $value);
    }

    /**
     * Students already in the school, by admission number and by card.
     *
     * @return array{0: array<string, StudentDetail>, 1: array<string, StudentDetail>}
     */
    private function existingStudents(array $parsed): array
    {
        $admissions = array_values(array_unique(array_filter(array_map(fn ($p) => $p['data']['admission_no'], $parsed), 'strlen')));
        $cards = array_values(array_unique(array_filter(array_map(fn ($p) => StudentDetail::normalizeCardUid($p['data']['card_uid']), $parsed))));

        $students = collect();
        foreach ([['admission_no', $admissions], ['card_uid', $cards]] as [$column, $values]) {
            foreach (array_chunk($values, 1000) as $chunk) {
                $students = $students->merge(StudentDetail::query()->whereIn($column, $chunk)->with('account:id,name')->get(['id', 'account_id', 'admission_no', 'card_uid']));
            }
        }

        $byAdmission = [];
        $byCard = [];
        foreach ($students as $student) {
            $byAdmission[mb_strtolower($student->admission_no)] = $student;
            if ($student->card_uid) {
                $byCard[$student->card_uid] = $student;
            }
        }

        return [$byAdmission, $byCard];
    }

    private function entry(int $line, string $status, ?StudentDetail $existing, array $data, array $values, array $parents, array $issues, array $notes): array
    {
        if (isset($values['dob']) && $data['dob'] !== null) {
            $values['dob'] = $data['dob'];
        }

        return [
            'line' => $line,
            'status' => $status,
            'account_id' => $existing?->account_id,
            'admission_no' => $data['admission_no'],
            'name' => $data['name'],
            'class' => implode(' - ', array_filter([$data['grade'], $data['section']], 'strlen')),
            'gender' => $data['gender'],
            'card_uid' => StudentDetail::normalizeCardUid($data['card_uid']),
            'parents' => array_values(array_map(fn ($p) => Arr::only($p, ['name', 'mobile', 'relation']), $parents)),
            'issues' => $issues,
            'notes' => $notes,
            'values' => $values,
            'data' => $data,
        ];
    }

    /** The same rules Student\CreateAction applies, minus uniqueness — that is checked across the file and the school. */
    private function rowRules(): array
    {
        return $this->rowRules ??= array_map(
            fn ($set) => array_values(array_filter((array) $set, fn ($rule) => ! $rule instanceof Unique)),
            Arr::only(Account::rules(), ['name', 'mobile', 'email']) + Arr::except(StudentDetail::rules(), ['account_id'])
        );
    }

    /** @return list<string> */
    private function validate(array $data, array $rules, array $attributes): array
    {
        return Validator::make($data, $rules, self::MESSAGES, $attributes)->errors()->all();
    }
}
