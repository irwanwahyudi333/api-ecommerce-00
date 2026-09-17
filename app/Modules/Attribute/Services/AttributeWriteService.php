<?php

declare(strict_types=1);

namespace App\Modules\Attribute\Services;

use App\Models\Attribute;
use App\Modules\Attribute\Actions\CreateAttributeAction;
use App\Modules\Attribute\Actions\DeleteAttributeAction;
use App\Modules\Attribute\Actions\ImportAttributesAction;
use App\Modules\Attribute\Actions\UpdateAttributeAction;
use App\Modules\Attribute\DTO\AttributeData;
use App\Models\User;

final class AttributeWriteService
{
    public function __construct(
        private readonly CreateAttributeAction $createAttributeAction,
        private readonly UpdateAttributeAction $updateAttributeAction,
        private readonly DeleteAttributeAction $deleteAttributeAction,
        private readonly ImportAttributesAction $importAttributesAction,
    ) {}

    public function createAttribute(AttributeData $data): Attribute
    {
        return $this->createAttributeAction->execute($data);
    }

    public function updateAttribute(Attribute $attribute, AttributeData $data): Attribute
    {
        return $this->updateAttributeAction->execute($attribute, $data);
    }

    public function deleteAttribute(Attribute $attribute): void
    {
        $this->deleteAttributeAction->execute($attribute);
    }

    public function importAttributes(\Illuminate\Http\UploadedFile $file, int $shopId, User $user): void
    {
        $this->importAttributesAction->execute($file, $shopId, $user);
    }
}
