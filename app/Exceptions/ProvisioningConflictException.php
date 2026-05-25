<?php

namespace App\Exceptions;

/**
 * Thrown when a provisioning command conflicts with existing state
 * (e.g. a slug that is already taken). Rendered as HTTP 409 for the manage API.
 */
class ProvisioningConflictException extends \RuntimeException
{
}
