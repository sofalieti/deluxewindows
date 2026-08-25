<?php

declare(strict_types=1);

namespace App\Services\Mailbox;

final class MailboxFolderPicker
{
    public function name(mixed $folder): string
    {
        if (! is_object($folder)) {
            return '';
        }

        foreach (['full_name', 'path', 'name'] as $property) {
            $value = trim((string) ($folder->{$property} ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    public function shouldSync(string $name, string $inbox = 'INBOX'): bool
    {
        $name = trim($name);
        if ($name === '') {
            return false;
        }

        if (strcasecmp($name, $inbox) === 0 || strcasecmp($name, 'INBOX') === 0) {
            return true;
        }

        return $this->isSent($name) || $this->isAllMail($name);
    }

    public function isSent(string $name): bool
    {
        $normalized = strtolower($name);

        return str_contains($normalized, 'sent')
            && ! str_contains($normalized, 'spam')
            && ! str_contains($normalized, 'trash');
    }

    public function isAllMail(string $name): bool
    {
        $normalized = strtolower(str_replace(['_', '-'], ' ', $name));

        return str_contains($normalized, 'all mail')
            || str_contains($normalized, 'allmail');
    }
}
