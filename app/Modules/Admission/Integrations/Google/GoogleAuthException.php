<?php

namespace Modules\Admission\Integrations\Google;

use RuntimeException;

/**
 * Kegagalan pertukaran code atau validasi ID token Google. Pesannya teknis
 * (dicatat di log), tidak pernah ditampilkan ke pendaftar.
 */
class GoogleAuthException extends RuntimeException {}
