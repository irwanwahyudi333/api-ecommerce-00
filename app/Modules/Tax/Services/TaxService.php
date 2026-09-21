<?php

namespace App\Modules\Tax\Services;

use App\Models\Tax;
use Illuminate\Database\Eloquent\Collection;

class TaxService
{
    /**
     * @return Collection<int, Tax>
     */
    public function getAll(): Collection
    {
        return Tax::all();
    }

    public function find(int $id): Tax
    {
        return Tax::findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Tax
    {
        return Tax::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Tax
    {
        $tax = Tax::findOrFail($id);
        $tax->update($data);

        return $tax->refresh();
    }

    public function delete(int $id): void
    {
        $tax = Tax::findOrFail($id);
        $tax->delete();
    }
}
