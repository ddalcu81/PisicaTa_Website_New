<?php

namespace App\Support;

use RuntimeException;

class LegacyEditorialExport
{
    private const ALIASES = [
        '4-lucruri-inedite-despre-ghiarele-pisicii' => '4-lucruri-inedite-despre-ghearele-pisicii',
        'alergiile-la-mancare' => 'pisica-e-alergica-la-mancare',
        'cum-dresezi-pisica-sa-acepte-mangaieri-si-luat-in-brate' => 'cum-dresezi-pisica-sa-accepte-mangaieri-si-luat-in-brate',
        'cutia-cu-nisip' => 'litiera-pisicii---ce-sa-alegem',
        'leucemia-felina' => 'leucemia-la-pisici',
        'siguranta-hranei-gatite' => 'hrana-gatita-pentru-pisica',
    ];

    public function __construct(private ArticleHtml $html) {}

    public function fromSql(string $sql): array
    {
        $categories = [];
        $articles = [];

        foreach ($this->rows($sql) as [$table, $row]) {
            if ($table === 'catart') {
                $categories[] = [
                    'legacy_id' => (int) $row['idcat'],
                    'parent_legacy_id' => (int) $row['idp'] ?: null,
                    'name' => $this->text($row['nume']),
                    'slug' => $this->text($row['titluseo']),
                    'description' => $this->text($row['descriere']),
                    'position' => (int) $row['index'],
                ];

                continue;
            }

            $articles[] = [
                'legacy_id' => (int) $row['id'],
                'category_legacy_id' => (int) $row['idcat'],
                'title' => $this->text($row['titlu']),
                'slug' => $this->text($row['titluseo']),
                'summary' => $this->text($row['descriere']),
                'body_html' => $this->html->sanitize($row['continut']),
                'published_at' => $row['data'],
            ];
        }

        if ($categories === [] || $articles === []) {
            throw new RuntimeException('No editorial records found in the SQL export.');
        }

        $aliases = self::ALIASES;
        foreach ($articles as $article) {
            $slug = $article['slug'];
            if (str_contains($slug, 'â€“')) {
                $aliases[str_replace('â€“', '–', $slug)] = $slug;
            }
        }

        return ['schema' => 1, 'categories' => $categories, 'articles' => $articles, 'aliases' => $aliases];
    }

    private function rows(string $sql): iterable
    {
        preg_match_all('/^INSERT INTO `(articole|catart)` \(([^\n]+)\) VALUES\s*/m', $sql, $inserts, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        foreach ($inserts as [$match, $table, $columns]) {
            preg_match_all('/`([a-z0-9_]+)`/i', $columns[0], $names);
            $offset = $match[1] + strlen($match[0]);

            do {
                $offset += strspn($sql, " \t\r\n", $offset);
                if ($sql[$offset++] !== '(') {
                    throw new RuntimeException('Invalid SQL row.');
                }

                $values = [];
                foreach ($names[1] as $index => $name) {
                    $offset += strspn($sql, " \t\r\n", $offset);
                    $values[$name] = $this->readValue($sql, $offset);
                    $offset += strspn($sql, " \t\r\n", $offset);
                    if ($sql[$offset++] !== ($index === count($names[1]) - 1 ? ')' : ',')) {
                        throw new RuntimeException('Invalid SQL field separator.');
                    }
                }

                yield [$table[0], $values];
                $offset += strspn($sql, " \t\r\n", $offset);
            } while ($sql[$offset++] === ',');
        }
    }

    private function readValue(string $sql, int &$offset): ?string
    {
        if ($sql[$offset] !== "'") {
            $length = strcspn($sql, ",) \t\r\n", $offset);
            $value = substr($sql, $offset, $length);
            $offset += $length;

            return $value === 'NULL' ? null : $value;
        }

        $offset++;
        $value = '';
        while ($offset < strlen($sql)) {
            $length = strcspn($sql, "'\\", $offset);
            $value .= substr($sql, $offset, $length);
            $offset += $length;
            if ($offset >= strlen($sql)) {
                break;
            }
            if ($sql[$offset++] === "'") {
                if (($sql[$offset] ?? '') !== "'") {
                    return $value;
                }

                $value .= $sql[$offset++];

                continue;
            }

            $char = $sql[$offset++];
            $value .= match ($char) {
                '0' => "\0", 'n' => "\n", 'r' => "\r", 't' => "\t", 'b' => "\x08", 'Z' => "\x1a",
                default => $char,
            };
        }

        throw new RuntimeException('Unterminated SQL string.');
    }

    private function text(?string $value): string
    {
        return trim(str_replace("\0", '', $value ?? ''));
    }
}
