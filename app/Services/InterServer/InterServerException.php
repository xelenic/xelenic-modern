<?php

namespace App\Services\InterServer;

use RuntimeException;

/**
 * InterServer said no (or couldn't be reached). The message is fit to show
 * the team; clients see a gentler one.
 */
class InterServerException extends RuntimeException {}
