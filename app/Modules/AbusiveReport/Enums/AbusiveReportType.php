<?php

declare(strict_types=1);

namespace App\Modules\AbusiveReport\Enums;

use App\Models\Question;
use App\Models\Review;
use InvalidArgumentException;

enum AbusiveReportType: string
{
    case REVIEW = 'Review';
    case QUESTION = 'Question';

    /**
     * @return string[]
     */
    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return class-string<Question|Review>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::REVIEW => Review::class,
            self::QUESTION => Question::class,
        };
    }

    public static function resolve(string $type): self
    {
        return self::from($type);
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function fromModelClass(string $class): string
    {
        return match ($class) {
            Review::class => self::REVIEW->value,
            Question::class => self::QUESTION->value,
            default => throw new InvalidArgumentException('Model tidak didukung.'),
        };
    }

    public function label(): string
    {
        return $this->value;
    }
}
