<?php

namespace App\Actions\V1\Technician\Concerns;

/**
 * Bridges the shared web actions (which return `['success','message','data']`)
 * into the throwing style the V1 actions use.
 */
trait RunsSharedActions
{
    /**
     * Unwrap a shared action's `['success','message','data']` result, throwing
     * on failure so the ApiLog records `failed` and the controller responds 422.
     *
     * @param  array<string, mixed>  $result
     * @return mixed the shared action's `data`
     */
    protected function runShared(array $result): mixed
    {
        if (! ($result['success'] ?? false)) {
            throw new \RuntimeException($result['message'] ?? 'Operation failed.');
        }

        return $result['data'] ?? null;
    }
}
