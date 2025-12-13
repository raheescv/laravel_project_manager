import React, { useMemo, useState } from "react";
import { router } from "@inertiajs/react";
import {
    useReactTable,
    getCoreRowModel,
    getFilteredRowModel,
    getSortedRowModel,
    getPaginationRowModel,
    flexRender
} from "@tanstack/react-table";

export default function SavedMeasurements({ saved = [] }) {
    const [globalFilter, setGlobalFilter] = useState("");

    const columns = useMemo(() => [
        {
            header: "#",
            cell: ({ row }) => row.index + 1
        },
        {
            header: "Customer",
            accessorFn: row => row.customer?.name
        },
        {
            header: "Category",
            accessorFn: row => row.template?.category?.name
        },
        {
            header: "Measurement",
            accessorFn: row => row.template?.name
        },
        {
            header: "Value",
            accessorFn: row => row.values?.value ?? ""
        },
        {
            header: "Action",
            cell: ({ row }) => (
                <>
                    <button
                        className="btn btn-warning btn-sm me-2"
                        onClick={() =>
                            router.get(
                                `/settings/category/measurement/edit/${row.original.customer_id}/${row.original.category_id}`
                            )
                        }
                    >
                        Edit
                    </button>

                    <button
                        className="btn btn-danger btn-sm"
                        onClick={() => {
                            if (!confirm("Delete?")) return;
                            router.delete(
                                `/settings/category/measurement/delete/${row.original.id}`
                            );
                        }}
                    >
                        Delete
                    </button>
                </>
            )
        }
    ], []);

    const table = useReactTable({
        data: saved,
        columns,
        state: {
            globalFilter
        },
        onGlobalFilterChange: setGlobalFilter,
        getCoreRowModel: getCoreRowModel(),
        getFilteredRowModel: getFilteredRowModel(),
        getSortedRowModel: getSortedRowModel(),
        getPaginationRowModel: getPaginationRowModel()
    });

    return (
        <>
            {/* 🔍 Global Search */}
            <input
                className="form-control mb-3"
                placeholder="Search..."
                value={globalFilter ?? ""}
                onChange={e => setGlobalFilter(e.target.value)}
            />

            <table className="table table-bordered">
                <thead>
                    {table.getHeaderGroups().map(headerGroup => (
                        <tr key={headerGroup.id}>
                            {headerGroup.headers.map(header => (
                                <th
                                    key={header.id}
                                    onClick={header.column.getToggleSortingHandler()}
                                    style={{ cursor: "pointer" }}
                                >
                                    {flexRender(
                                        header.column.columnDef.header,
                                        header.getContext()
                                    )}
                                    {header.column.getIsSorted() === "asc" && " 🔼"}
                                    {header.column.getIsSorted() === "desc" && " 🔽"}
                                </th>
                            ))}
                        </tr>
                    ))}
                </thead>

                <tbody>
                    {table.getRowModel().rows.map(row => (
                        <tr key={row.id}>
                            {row.getVisibleCells().map(cell => (
                                <td key={cell.id}>
                                    {flexRender(
                                        cell.column.columnDef.cell,
                                        cell.getContext()
                                    )}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>

            {/* 📄 Pagination */}
            <div className="d-flex justify-content-between align-items-center mt-3">
                <div>
                    <button
                        className="btn btn-sm btn-secondary me-2"
                        onClick={() => table.previousPage()}
                        disabled={!table.getCanPreviousPage()}
                    >
                        Prev
                    </button>

                    <button
                        className="btn btn-sm btn-secondary"
                        onClick={() => table.nextPage()}
                        disabled={!table.getCanNextPage()}
                    >
                        Next
                    </button>
                </div>

                <select
                    className="form-select w-auto"
                    value={table.getState().pagination.pageSize}
                    onChange={e => table.setPageSize(Number(e.target.value))}
                >
                    {[10, 25, 50, 100].map(size => (
                        <option key={size} value={size}>
                            Show {size}
                        </option>
                    ))}
                </select>
            </div>
        </>
    );
}
