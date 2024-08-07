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
    /**
     * Паттер для html тегов
     *
     * @var string
     */
    public const HTML_TAG_PATTERN = '/<\/?([a-z]+)[^>]*>/i';

    /**
     * Паттерн для html тегов вместе с аттрибутами
     *
     * @var string
     */
    public const HTML_TAGS_WITH_ATTRIBUTES_PATTERN = '/<\/?([a-z]+)(?: [^>]+)?>/i';

    /**
     * Паттер html аттрибутов
     *
     * @var string
     */
    public const HTML_ATTRIBUTE_PATTERN = '/([a-zA-Z\-]+)="([^"]+)"/';

    /**
     * Паттерны допустимых html тегов
     *
     * @var array
     */
    private array $allowedTagPatterns;

    /**
     * Паттерны допустимых аттрибутов для html тегов
     *
     * @var array
     */
    private array $allowedAttributePatterns;

    public function __construct(array $allowedTagPatterns, array $allowedAttributePatterns = [])
    {
        $this->allowedTagPatterns = $allowedTagPatterns;
        $this->allowedAttributePatterns = $allowedAttributePatterns;
    }

    /**
     * Валидация html
     *
     * @param string $data
     * @return bool
     */
    public function validate($data): bool
    {
        try {
            $this->checkAllowedTags($data);
            $this->checkTagClosureAndNesting($data);
            $this->checkAttributes($data);
        } catch (AllowedTagException | ClosureTagException | AllowedAttributeException $exception) {
            // здесь можно было бы добавить например логгирование, для наглядного результата работы выведу сообщения об ошибке
            echo '<br> - ' . $exception->getMessage() . ' = ';
            return false;
        }

        return true;
    }

    /**
     * Проверка допустимых html тегов
     *
     * @throws AllowedTagException
     */
    private function checkAllowedTags(string $html): void
    {
        $tags = $this->extractTags($html);
        $combinePattern = $this->combineAllowedTagsPatterns();

        foreach ($tags as $tagMatch) {
            $tag = $tagMatch[0];

            if (preg_match(self::HTML_TAG_PATTERN, $tag, $matches)) {
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
     * Проверка на корректное закрытие и вложенность тегов html
     *
     * @param string $html
     * @throws ClosureTagException
     */
    private function checkTagClosureAndNesting(string $html): void
    {
        $tags = $this->extractTags($html);
        $this->validateTagNesting($tags);
    }

    /**
     * Извлечение всех тегов html
     *
     * @param string $content
     * @return array
     */
    private function extractTags(string $content): array
    {
        preg_match_all(self::HTML_TAGS_WITH_ATTRIBUTES_PATTERN, $content, $matches, PREG_SET_ORDER);
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
     * Проверка допустимых атрибутов html тегов
     *
     * @param string $html
     * @throws AllowedAttributeException
     */
    private function checkAttributes(string $html): void
    {
        $tags = $this->extractTags($html);

        foreach ($tags as $tagMatch) {
            preg_match_all(self::HTML_ATTRIBUTE_PATTERN, $tagMatch[0], $attributeMatches, PREG_SET_ORDER);

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
