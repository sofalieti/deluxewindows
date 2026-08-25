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

    public function isSkipped(string $name): bool
    {
        $normalized = strtolower($name);

        foreach (['spam', 'trash', 'junk', 'draft', 'chat', 'scheduled', 'snoozed', 'bin', 'deleted'] as $skip) {
            if (str_contains($normalized, $skip)) {
                return true;
            }
        }

        return false;
    }

    public function shouldSync(string $name, string $inbox = 'INBOX'): bool
    {
        $name = trim($name);
        if ($name === '' || $this->isSkipped($name)) {
            return false;
        }

        if (strcasecmp($name, $inbox) === 0 || strcasecmp($name, 'INBOX') === 0) {
            return true;
        }

        if ($this->isSent($name) || $this->isAllMail($name)) {
            return true;
        }

        $normalized = strtolower($name);
        foreach (['lead', 'google', 'form', 'quote', 'client'] as $hint) {
            if (str_contains($normalized, $hint)) {
                return true;
            }
        }

        return false;
    }

    public function priority(string $name): int
    {
        $normalized = strtolower($name);

        if ($this->isAllMail($name)) {
            return 100;
        }
        if (strcasecmp($name, 'INBOX') === 0) {
            return 90;
        }
        if (str_contains($normalized, 'google') && str_contains($normalized, 'lead')) {
            return 85;
        }
        if ($this->isSent($name)) {
            return 70;
        }
        if (str_contains($normalized, 'lead') || str_contains($normalized, 'form')) {
            return 60;
        }

        return 10;
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
