CREATE VIEW ledgers AS
SELECT
    je.id,
    a.id AS account_id,
    a.name AS account_name,
    j.date,
    j.branch_id,
    j.description,
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
    journals j ON je.journal_id = j.id
WHERE
    j.deleted_at IS NULL
ORDER BY
    j.date, je.id;
