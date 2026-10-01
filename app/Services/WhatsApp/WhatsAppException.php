<?php

namespace App\Services\WhatsApp;

use RuntimeException;

/**
 * A message Meta did not accept (bad token, template not approved, number not on WhatsApp…).
 */
class WhatsAppException extends RuntimeException {}
