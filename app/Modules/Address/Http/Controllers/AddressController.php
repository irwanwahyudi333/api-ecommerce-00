<?php

declare(strict_types=1);

namespace App\Modules\Address\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Address;
use App\Models\User;
use App\Modules\Address\Actions\CreateAddressAction;
use App\Modules\Address\Actions\DeleteAddressAction;
use App\Modules\Address\Actions\GetUserAddressesQuery;
use App\Modules\Address\Actions\UpdateAddressAction;
use App\Modules\Address\DTO\AddressData;
use App\Modules\Address\Http\Requests\AddressRequest;
use App\Modules\Address\Http\Resources\AddressResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final class AddressController extends BaseController
{
    public function __construct(
        private readonly GetUserAddressesQuery $getUserAddressesQuery,
        private readonly CreateAddressAction $createAddressAction,
        private readonly UpdateAddressAction $updateAddressAction,
        private readonly DeleteAddressAction $deleteAddressAction,
    ) {}

    /**
     * Daftar alamat milik user yang sedang login.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $addresses = $this->getUserAddressesQuery->execute(
            user: $user,
            perPage: min($request->integer('per_page', 15), 100)
        );

        return AddressResource::collection($addresses);
    }

    /**
     * Membuat alamat baru.
     */
    public function store(AddressRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var array{title:string, type:string, default?:bool, address:array<string, mixed>, location?:array<string, mixed>|null} $validated */
        $validated = $request->validated();

        $address = $this->createAddressAction->execute(
            $user,
            AddressData::fromRequest($validated)
        );

        return (new AddressResource($address))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Menampilkan detail alamat (otorisasi: pemilik atau admin).
     */
    public function show(Address $address): AddressResource
    {
        $this->authorize('view', $address);

        return new AddressResource($address);
    }

    /**
     * Memperbarui alamat (otorisasi: pemilik atau admin).
     */
    public function update(AddressRequest $request, Address $address): AddressResource
    {
        $this->authorize('update', $address);

        /** @var array{title:string, type:string, default?:bool, address:array<string, mixed>, location?:array<string, mixed>|null} $validated */
        $validated = $request->validated();

        $updated = $this->updateAddressAction->execute(
            $address,
            AddressData::fromRequest($validated)
        );

        return new AddressResource($updated);
    }

    /**
     * Menghapus alamat (otorisasi: pemilik atau admin).
     */
    public function destroy(Address $address): JsonResponse
    {
        $this->authorize('delete', $address);

        $this->deleteAddressAction->execute($address);

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully.',
        ]);
    }
}
