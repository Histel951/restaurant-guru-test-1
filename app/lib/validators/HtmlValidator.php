<?php

declare(strict_types=1);

namespace App\Lib\Validators;

use App\Lib\Exceptions\AllowedTagException;
use App\Lib\Exceptions\ClosureTagException;
use App\Lib\Exceptions\AllowedAttributeException;

require_once "Validator.php";

if (
    file_exists(__DIR__ . "/../exceptions/AllowedTagException.php")
    || file_exists(__DIR__ . "/../exceptions/AllowedAttributeException.php")
    || file_exists(__DIR__ . "/../exceptions/ClosureTagException.php")
) {
    require_once __DIR__ . "/../exceptions/AllowedTagException.php";
    require_once __DIR__ . "/../exceptions/AllowedAttributeException.php";
    require_once __DIR__ . "/../exceptions/ClosureTagException.php";
} else {
    echo "Exception files not found.";
    exit;
}

class HtmlValidator implements Validator
{
    public const HTML_TAGS_PATTERN = '/<\/?([a-z]+)(?: [^>]+)?>/i';

    private array $allowedTagPatterns;
    private array $allowedAttributePatterns;

    public function __construct(array $allowedTagPatterns, array $allowedAttributePatterns = [])
    {
        $this->allowedTagPatterns = $allowedTagPatterns;
        $this->allowedAttributePatterns = $allowedAttributePatterns;
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
            $this->checkAttributes($requestData);
        } catch (AllowedTagException | ClosureTagException | AllowedAttributeException $exception) {
            // здесь можно было бы добавить например логгирование, для наглядного результата работы выведу сообщения об ошибке
            echo '<br> - ' . $exception->getMessage() . ' = ';
            return false;
        }

        return true;
    }

    /**
     * Проверка допустимых HTML тегов
     *
     * @throws AllowedTagException
     */
    private function checkAllowedTags(string $requestData): void
    {
        $tags = $this->extractTags($requestData);
        $combinePattern = $this->combineAllowedTagsPatterns();

        foreach ($tags as $tagMatch) {
            $tag = $tagMatch[0];

            if (preg_match('/<\/?([a-z]+)[^>]*>/i', $tag, $matches)) {
                $tagName = $matches[1];

                if (!preg_match($combinePattern, "<$tagName>") && !preg_match($combinePattern, "</$tagName>")) {
                    throw new AllowedTagException("Недопустимый тег: '$tagName'");
                }
            }
        }
    }

    /**
     * Соединение всех переданных паттернов для тегов в один для проверки
     *
     * @return string
     */
    private function combineAllowedTagsPatterns(): string
    {
        $combinedPattern = implode('|', $this->allowedTagPatterns);
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
     * Проверяет данные на незакрытые теги и корректность вложенности
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
                    throw new ClosureTagException("Недопустимое закрытие или вложенность тега: '$tagName'");
                }
            } else {
                $unclosedTagStack[] = $tagName;
            }
        }

        if (!empty($unclosedTagStack)) {
            throw new ClosureTagException("Есть незакрытые теги: '" . implode('\', \'', $unclosedTagStack) . '\'');
        }
    }

    /**
     * Проверяет, что последний открытый тег соответствует текущему закрывающему тегу
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

    /**
     * Проверка допустимых атрибутов HTML тегов
     *
     * @param string $requestData
     * @throws AllowedAttributeException
     */
    private function checkAttributes(string $requestData): void
    {
        $tags = $this->extractTags($requestData);

        foreach ($tags as $tagMatch) {
            preg_match_all('/([a-zA-Z\-]+)="([^"]+)"/', $tagMatch[0], $attributeMatches, PREG_SET_ORDER);

            foreach ($attributeMatches as $attributeMatch) {
                $attributeName = $attributeMatch[1];
                $attributeValue = $attributeMatch[2];

                if (!$this->isValidAttribute($attributeName, $attributeValue)) {
                    throw new AllowedAttributeException("Недопустимый атрибут '$attributeName' для тега '$tagMatch[1]'");
                }
            }
        }
    }

    /**
     * Проверяет, является ли атрибут допустимым для данного тега
     *
     * @param string $attributeName
     * @param string $attributeValue
     * @return bool
     */
    private function isValidAttribute(string $attributeName, string $attributeValue): bool
    {
        foreach ($this->allowedAttributePatterns as $pattern) {
            if (preg_match($pattern, "$attributeName=\"$attributeValue\"")) {
                return true;
            }
        }

        return false;
    }
}
