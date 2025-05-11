CREATE VIEW ledgers AS
SELECT
    je.id,
    je.journal_id,

    je.account_id,
    a.name AS account_name,

    je.counter_account_id,
    ca.name AS counter_account_name,

    j.source,
    j.person_name,
    j.date,
    j.branch_id,
    j.description,
    j.remarks as journal_remarks,
    j.reference_number,
    j.model,
    j.model_id,
    je.remarks,
    je.debit,
    je.credit,
    (
        SUM(je.debit) OVER (PARTITION BY a.id ORDER BY j.date, je.id) -
        SUM(je.credit) OVER (PARTITION BY a.id ORDER BY j.date, je.id)
    ) AS balance
FROM
    journal_entries je
JOIN
    accounts a ON je.account_id = a.id
JOIN
    accounts ca ON je.counter_account_id = ca.id
JOIN
    journals j ON je.journal_id = j.id
WHERE
    j.deleted_at IS NULL
ORDER BY
    j.date, je.id;
