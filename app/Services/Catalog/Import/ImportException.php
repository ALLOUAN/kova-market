<?php

namespace App\Services\Catalog\Import;

use RuntimeException;

/**
 * A file or a line that cannot be imported; the message is shown to the manager as is.
 */
class ImportException extends RuntimeException {}
