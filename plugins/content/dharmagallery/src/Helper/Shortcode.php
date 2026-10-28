<?php

namespace Dharma\Plugin\Content\DharmaGallery\Helper;

\defined('_JEXEC') or die;

/**
 * Разбор кода вставки {dharmagallery id=1 limit=12 columns=3}. Класс не зависит от Joomla, поэтому проверяется
 * отдельными тестами.
 */
final class Shortcode
{
    /** Допустимые колонки совпадают с настройкой «Колонки» компонента. */
    public const COLUMNS = [1, 2, 3, 4, 6];

    public const MAX_LIMIT = 100;

    /**
     * Код вместе с обёртывающим абзацем: редактор кладёт одиночную строку в <p>, а блочный вывод внутри <p> недопустим.
     * Группа 1 — открывающий <p>, 2 — атрибуты, 3 — закрывающий </p>.
     */
    public const PATTERN = '~(<p(?:\s[^>]*)?>\s*)?\{dharmagallery(?:\s+([^{}]*?))?\s*\}(\s*</p>)?~iu';

    public static function contains(string $text): bool
    {
        return stripos($text, '{dharmagallery') !== false;
    }

    /**
     * @return array{id: int, limit: int, columns: int}  нули означают «значение не задано»
     */
    public static function parse(string $attributes): array
    {
        $result = ['id' => 0, 'limit' => 0, 'columns' => 0];

        if (!preg_match_all('~([a-z]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|(\S+))~iu', $attributes, $matches, PREG_SET_ORDER)) {
            return $result;
        }

        foreach ($matches as $match) {
            $name  = strtolower($match[1]);
            $value = $match[4] ?? '';

            if ($value === '') {
                $value = ($match[2] ?? '') !== '' ? $match[2] : ($match[3] ?? '');
            }

            $value = trim($value);

            if (!isset($result[$name]) || !preg_match('~^\d{1,6}$~', $value)) {
                continue;
            }

            $result[$name] = (int) $value;
        }

        $result['limit']   = $result['limit'] > 0 ? min(self::MAX_LIMIT, $result['limit']) : 0;
        $result['columns'] = \in_array($result['columns'], self::COLUMNS, true) ? $result['columns'] : 0;

        return $result;
    }

    /**
     * Заменяет каждый код результатом $renderer(array $attributes): string. Вокруг блочного результата абзац снимается,
     * если он состоял только из кода.
     */
    public static function replace(string $text, callable $renderer): string
    {
        return (string) preg_replace_callback(
            self::PATTERN,
            static function (array $match) use ($renderer): string {
                $html = (string) $renderer(self::parse($match[2] ?? ''));

                $open  = ($match[1] ?? '') !== '';
                $close = ($match[3] ?? '') !== '';

                if ($open && !$close) {
                    // «<p>{код} текст</p>»: блок идёт перед абзацем, остаток текста остаётся в новом <p>.
                    return $html . $match[1];
                }

                if ($close && !$open) {
                    // «<p>текст {код}</p>»: абзац закрывается до блока.
                    return $match[3] . $html;
                }

                // Код занимал абзац целиком (или стоял без абзаца): обёртка снимается.
                return $html;
            },
            $text
        );
    }

    /** Убирает коды без вывода (индексация, рекурсия, отключённый компонент). */
    public static function strip(string $text): string
    {
        return self::replace($text, static fn (array $attributes): string => '');
    }
}
