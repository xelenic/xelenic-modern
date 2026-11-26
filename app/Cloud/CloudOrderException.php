<?php

namespace App\Cloud;

use RuntimeException;

/**
 * An order that can't go ahead as it is, with a message for whoever placed
 * it (e.g. the domain's taken, or InterServer rejected the configuration).
 */
class CloudOrderException extends RuntimeException {}
