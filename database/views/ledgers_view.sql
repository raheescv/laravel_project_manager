CREATE VIEW ledgers AS
SELECT
    je.id,
    je.journal_id,

    je.account_id,
    a.name AS account_name,

    je.counter_account_id,

    je.source,
    je.person_name,
    je.date,
    je.branch_id,
    je.description,
    je.journal_remarks,
    je.reference_number,

    je.journal_model,
    je.journal_model_id,

    je.model,
    je.model_id,

    je.remarks,
    je.debit,
    je.credit,
    (
        SUM(je.debit) OVER (PARTITION BY a.id ORDER BY je.date, je.id) -
        SUM(je.credit) OVER (PARTITION BY a.id ORDER BY je.date, je.id)
    ) AS balance
FROM
    journal_entries je
JOIN
    accounts a ON je.account_id = a.id
WHERE
    je.deleted_at IS NULL
ORDER BY
    je.date, je.id;
