<?php

declare(strict_types=1);

namespace Xchert\Schema;

interface SchemaProvider
{
    public function getSchema(string $name): ?ObjectSchema;

    /**
     * @return list<string>
     */
    public function getSchemaNames(): array;
}
