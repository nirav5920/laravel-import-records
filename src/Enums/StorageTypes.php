<?php
namespace Codebyray\ImportRecords\Enums;

enum StorageTypes: string
{
    case S3 = 's3';
    case LOCAL = 'local';
    case PUBLIC = 'public';
}
