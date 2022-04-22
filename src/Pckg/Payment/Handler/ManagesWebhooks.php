<?php

namespace Pckg\Payment\Handler;

interface ManagesWebhooks
{
    public function getWebhooks(): array;

    public function postWebhook(): bool;
}
