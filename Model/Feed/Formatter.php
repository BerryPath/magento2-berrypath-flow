<?php

declare(strict_types=1);

namespace BerryPath\Flow\Model\Feed;

class Formatter
{
    /**
     * @param array<string, mixed> $feed
     */
    public function toXml(array $feed): string
    {
        $writer = new \XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString('  ');
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('berrypath_feed');

        $this->writeNode($writer, 'config', $feed['config'] ?? []);

        $writer->startElement('products');
        foreach (($feed['products'] ?? []) as $product) {
            if (is_array($product)) {
                $this->writeNode($writer, 'product', $product);
            }
        }
        $writer->endElement();

        $writer->endElement();
        $writer->endDocument();

        return (string)$writer->outputMemory();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function writeNode(\XMLWriter $writer, string $name, array $data): void
    {
        $writer->startElement($this->sanitizeElementName($name));

        foreach ($data as $key => $value) {
            $key = is_string($key) ? $key : 'item';

            if (is_array($value)) {
                $writer->startElement($this->sanitizeElementName($key));
                foreach ($value as $item) {
                    if (is_array($item)) {
                        $this->writeNode($writer, $this->singularize($key), $item);
                    } else {
                        $writer->startElement($this->singularize($key));
                        $this->writeValue($writer, $item);
                        $writer->endElement();
                    }
                }
                $writer->endElement();
                continue;
            }

            $writer->startElement($this->sanitizeElementName($key));
            $this->writeValue($writer, $value);
            $writer->endElement();
        }

        $writer->endElement();
    }

    private function writeValue(\XMLWriter $writer, mixed $value): void
    {
        if (is_bool($value)) {
            $writer->text($value ? 'true' : 'false');
            return;
        }

        if (is_scalar($value) || $value instanceof \Stringable) {
            $writer->text((string)$value);
        }
    }

    private function sanitizeElementName(string $name): string
    {
        $name = (string)preg_replace('/[^A-Za-z0-9_.-]/', '_', $name);
        if ($name === '' || preg_match('/^[A-Za-z_]/', $name) !== 1) {
            $name = 'item_' . $name;
        }

        return $name;
    }

    private function singularize(string $name): string
    {
        return match ($name) {
            'products' => 'product',
            'categories' => 'category',
            'category_ids' => 'category_id',
            default => 'item',
        };
    }
}
