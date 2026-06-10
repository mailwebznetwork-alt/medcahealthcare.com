<?php

namespace App\Services\Content;

final class ServiceTokenPattern
{
    public const string PATTERN = '/\{\{\s*service\s*:\s*([^}]+?)\s*\}\}/';
}
