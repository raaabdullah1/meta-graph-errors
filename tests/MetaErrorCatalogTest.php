<?php

declare(strict_types=1);

namespace MetaGraphErrors\Tests;

use MetaGraphErrors\Confidence;
use MetaGraphErrors\ErrorCategory;
use MetaGraphErrors\MetaErrorCatalog;
use MetaGraphErrors\RecoveryAction;
use MetaGraphErrors\Retryability;
use PHPUnit\Framework\TestCase;

final class MetaErrorCatalogTest extends TestCase
{
    public function test_every_row_is_well_formed(): void
    {
        $count = 0;

        foreach (MetaErrorCatalog::entries() as $table => $rows) {
            foreach ($rows as $key => $row) {
                $count++;
                $label = "{$table}[{$key}]";

                $this->assertTrue($row[1] instanceof ErrorCategory, "$label category");
                $this->assertTrue($row[2] instanceof Retryability, "$label retryability");
                $this->assertTrue($row[3] instanceof RecoveryAction, "$label recovery");

                $hasConfidence = $row[4] instanceof Confidence;
                $hint          = $hasConfidence ? $row[5] : $row[4];
                $source        = $hasConfidence ? $row[6] : $row[5];

                $this->assertIsString($hint, "$label hint");
                $this->assertNotSame('', trim($hint), "$label hint is empty");
                $this->assertIsString($source, "$label source");
                $this->assertNotSame('', trim($source), "$label source is empty");
            }
        }

        $this->assertSame(109, $count);
    }

    public function test_pair_and_subcode_rows_declare_confidence_and_code_rows_do_not(): void
    {
        $tables = MetaErrorCatalog::entries();

        foreach (['pairs', 'subcodes'] as $table) {
            foreach ($tables[$table] as $key => $row) {
                $this->assertInstanceOf(Confidence::class, $row[4], "{$table}[{$key}]");
            }
        }

        foreach ($tables['codes'] as $key => $row) {
            $this->assertNotInstanceOf(Confidence::class, $row[4], "codes[{$key}]");
        }
    }

    public function test_pair_keys_are_code_colon_subcode(): void
    {
        foreach (array_keys(MetaErrorCatalog::entries()['pairs']) as $key) {
            $this->assertMatchesRegularExpression('/^-?\d+:\d+$/', (string) $key);
        }
    }

    public function test_no_private_project_references_leak_into_the_data(): void
    {
        $source = (string) file_get_contents(__DIR__ . '/../src/MetaErrorCatalog.php');

        $this->assertStringNotContainsStringIgnoringCase('syncors', $source);
        $this->assertStringNotContainsString('docs/meta/', $source);
    }

    public function test_lookups_return_null_when_there_is_no_match(): void
    {
        $this->assertNull(MetaErrorCatalog::pair(null, '463'));
        $this->assertNull(MetaErrorCatalog::pair('190', null));
        $this->assertNull(MetaErrorCatalog::pair('999999', '1'));
        $this->assertNull(MetaErrorCatalog::subcode(null));
        $this->assertNull(MetaErrorCatalog::subcode('0'));
        $this->assertNull(MetaErrorCatalog::code(null));
        $this->assertNull(MetaErrorCatalog::code('999999'));
    }
}
