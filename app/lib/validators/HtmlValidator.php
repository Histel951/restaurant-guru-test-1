<?php

declare(strict_types=1);

namespace App\Lib\Validators;

use App\Lib\Exceptions\AllowedTagException;
use App\Lib\Exceptions\ClosureTagException;

if (file_exists(__DIR__ . "/../exceptions/AllowedTagException.php")) {
    require_once __DIR__ . "/../exceptions/AllowedTagException.php";
    require_once __DIR__ . "/../exceptions/ClosureTagException.php";
} else {
    echo "Exception files not founded.";
    exit;
}

require_once "Validator.php";

class HtmlValidator implements Validator
{
    public const HTML_TAGS_PATTERN = '/<\/?([a-z]+)(?: [^>]+)?>/i';

    private array $allowedPatterns;

    public function __construct(...$allowedPatterns)
    {
        $this->allowedPatterns = $allowedPatterns;
    }

    /**
     * Валидация HTML
     *
     * @param string $requestData
     * @return bool
     */
    public function validate(string $requestData): bool
    {
        try {
            $this->checkAllowedTags($requestData);
            $this->checkTagClosureAndNesting($requestData);
        } catch (AllowedTagException|ClosureTagException $exception) {
            return false;
        }

        return true;
    }

    /**
     * Проверку допустимых HTML тегов
     *
     * @throws AllowedTagException
     */
    private function checkAllowedTags(string $requestData): void
    {
        if (!preg_match_all($this->combineAllowedTagsPatterns(), $requestData)) {
            throw new AllowedTagException();
        }
    }

    /**
     * Соединение всех переданных паттернов для тегов в 1 для проверки
     *
     * @return string
     */
    private function combineAllowedTagsPatterns(): string
    {
        $combinedPattern = implode('|', $this->allowedPatterns);
        return '/(' . $combinedPattern . ')/i';
    }

    /**
     * Проверка на корректное закрытие и вложенность тегов HTML
     *
     * @param string $requestData
     * @throws ClosureTagException
     */
    private function checkTagClosureAndNesting(string $requestData): void
    {
        $tags = $this->extractTags($requestData);
        $this->validateTagNesting($tags);
    }

    /**
     * Извлечение всех тегов HTML
     *
     * @param string $content
     * @return array
     */
    private function extractTags(string $content): array
    {
        preg_match_all(self::HTML_TAGS_PATTERN, $content, $matches, PREG_SET_ORDER);

        return $matches;
    }

    /**
     * Проверяет данные на не закрытые теги и корректность вложенности
     *
     * @throws ClosureTagException
     */
    private function validateTagNesting(array $tags): void
    {
        $unclosedTagStack = [];

        foreach ($tags as $tagMatch) {
            $tagName = $tagMatch[1];
            if ($this->isClosingTag($tagMatch[0])) {
                if (empty($unclosedTagStack) || $this->checkLastClosedTagMatchCurrentTag($unclosedTagStack, $tagName)) {
                    throw new ClosureTagException("Недопустимое закрытие или вложенность тега.");
                }
            } else {
                $unclosedTagStack[] = $tagName;
            }
        }

        if (!empty($unclosedTagStack)) {
            throw new ClosureTagException("Есть не закрытые теги: " . implode(', ', $unclosedTagStack));
        }
    }

    /**
     * Проверяет что последний открытый тег соответствует текущему закрывающему тегу
     *
     * @param array $unclosedTagStack
     * @param string $tagName
     * @return bool
     */
    private function checkLastClosedTagMatchCurrentTag(array &$unclosedTagStack, string $tagName): bool
    {
        return array_pop($unclosedTagStack) !== $tagName;
    }

    /**
     * Проверка является ли тег закрывающим
     *
     * @param string $tag
     * @return bool
     */
    private function isClosingTag(string $tag): bool
    {
        return $tag[1] === '/';
    }
}