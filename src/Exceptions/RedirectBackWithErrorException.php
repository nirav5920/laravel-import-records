<?php

namespace Codebyray\ImportRecords\Exceptions;


use Exception;
use Illuminate\Http\RedirectResponse;

class RedirectBackWithErrorException extends Exception
{
    public function __construct(
        protected string $errorMessage
    ) {
    }

    public function render(): RedirectResponse
    {
        return back()->with('error', $this->errorMessage);
    }

    public function report(): void
    {
        // We do not wish to report this exception.
    }
}
