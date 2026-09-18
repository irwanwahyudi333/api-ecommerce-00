<?php

declare(strict_types=1);

namespace App\Modules\Author\Services;

use App\Models\Author;
use App\Modules\Author\Actions\CreateAuthorAction;
use App\Modules\Author\Actions\UpdateAuthorAction;
use App\Modules\Author\DTO\AuthorData;

final class AuthorWriteService
{
    public function __construct(
        private readonly CreateAuthorAction $createAuthorAction,
        private readonly UpdateAuthorAction $updateAuthorAction,
    ) {}

    public function createAuthor(AuthorData $data): Author
    {
        return $this->createAuthorAction->execute($data);
    }

    public function updateAuthor(Author $author, AuthorData $data): Author
    {
        return $this->updateAuthorAction->execute($author, $data);
    }

    public function deleteAuthor(Author $author): void
    {
        $author->delete();
    }
}
