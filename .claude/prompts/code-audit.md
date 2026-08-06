# Codebase audit against the project skills

Audit this codebase against its own documented conventions and produce a single HTML report.

## Skills to load first

Read all of these before looking at any code — they are the standard you are auditing against:

`action-layer`, `multi-tenancy-and-branches`, `livewire-components`, `accounting-journals`,
`mobile-api-v1`, `permissions-and-routes`, `print-and-pdf`, `blade-ui-conventions`,
`imports-exports-and-jobs`, plus the existing `laravel-best-practices`.

They live in `.claude/skills/*/SKILL.md`. Every finding must trace back to a specific rule in one
of them, or to a correctness/security defect. If code disagrees with a skill but the code is right,
say so — the skill is a description of the codebase, not scripture, and a wrong skill is a finding.

## Scope

Cover the whole application, but spend effort in proportion to risk. Work through it in this order
and do not stop early:

1. **Money and data integrity** — `app/Actions/**/JournalEntryAction.php`, `app/Actions/Journal/`,
   security deposits, GRNI/LPO, payments, reversals, stock updates.
2. **Tenant and branch isolation** — every `DB::table`/`DB::select`/raw join, every queued job and
   console command, every bulk `insert()`, every report aggregate, every `Rule::unique`.
3. **Authorization** — routes without `->can()`, Livewire write methods without `abort_unless`,
   permissions used in code but absent from `config/permissions.php` (and vice versa), unguarded
   menu entries.
4. **Action layer conformance** — `app/Actions/` (~383 files): signature, return shape, unchecked
   nested `success`, actions opening their own transactions, callers not wrapping in one.
5. **Livewire layer** — `app/Livewire/` (~355 files): missing rollback, missing pagination theme,
   unqualified sort fields, side effects before commit, hardcoded tenant wording.
6. **API v1** — V1 actions reimplementing web logic instead of delegating, session reads on API
   paths, missing duplicate guards on creates, Resource/Dart model drift.
7. **Print/PDF** — DomPDF used for anything that can contain Arabic or rich text, unsupported CSS
   per engine, non-embedded assets under Browsershot.
8. **Blade/UI** — FA5/6 icon prefixes, ad-hoc selects instead of the shared components, premium
   design systems with no dark-mode block, non-theme-derived hardcoded colours.
9. **Flutter** — `mobileApp/`, `technicianApp/`: screens constructing services directly instead of
   the registered abstract repository, endpoint literals outside `EndPoints`.

For the large directories, do not claim you read every file. Grep for the specific anti-pattern,
count the hits, then read enough of them to be sure. State how you sampled.

## Evidence rules

- Every finding cites `path/to/file.php:LINE`. No finding without a location.
- Verify before asserting. Open the file and confirm the pattern is really there and really wrong
  in context — a `DB::table` on a non-tenant table is fine; a `Model::create` inside a seeder is fine.
- Distinguish **confirmed** (you read it and it is definitely wrong) from **suspected** (it looks
  wrong but depends on a caller or runtime condition you could not check). Label each.
- If a whole area is clean, say so explicitly. A short "no findings" section is a real result.
- Do not invent severity to pad the report. An honest list of 12 real problems beats 60 nitpicks.

## Output

Write one self-contained HTML file to `docs/code-audit.html`. Do not modify any application code —
this is read-only analysis.

For **every** finding the report must answer four things, in this order:

1. **What the code does now** — the actual snippet, with its file:line.
2. **Why it is a problem** — the concrete failure. Name the scenario: which user, which tenant,
   which input, and what breaks. "Violates convention" alone is not a reason; if the only cost is
   inconsistency, say that plainly and rank it low.
3. **What the better approach is** — the corrected snippet, matching the pattern the skill documents
   and the surrounding code already uses.
4. **Why that is better, and the trade-off** — including when the current code would actually be the
   right choice. Where two valid options exist (e.g. DomPDF vs Browsershot, HolderCubit vs plain
   Cubit, tenant-wide `Configuration` vs per-user `user_preferences`), give a short comparison and a
   recommendation rather than a blanket rule.

Structure the page as:

- **Summary** — findings by severity and by area, the three things worth fixing first, and an honest
  statement of what the audit did *not* cover.
- **Findings** — grouped by the nine areas above, ordered most severe first within each group.
  Each collapsible, with the four-part structure and a confirmed/suspected badge.
- **Comparison tables** — the recurring "which is better" decisions, as reference for future work.
- **Skill corrections** — anywhere a SKILL.md is wrong, stale, or contradicts good code.

HTML requirements: single file, no CDN or external requests, inline CSS/JS, works in light and dark
(`prefers-color-scheme` plus a manual toggle), wide tables scroll inside their own container so the
page never scrolls sideways, syntax-highlight code blocks with plain inline styling, and make it
skimmable — a reader should get the picture from the summary alone.

Finish by telling me the file path and the top three findings in two sentences.
