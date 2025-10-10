<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Custom render:
     * - Se il token CSRF è scaduto:
     *   - utente NON autenticato -> vai al login con messaggio
     *   - utente autenticato     -> rigenera token e torna indietro col form pre-compilato
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof TokenMismatchException) {
            if (! $request->user()) {
                // Sessione scaduta: porta al login
                return redirect()->guest(route('login'))
                    ->with('error', 'Sessione scaduta. Accedi di nuovo per continuare.');
            }

            // Utente loggato ma token vecchio: rigenera e riproponi il form
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->with('error', 'La pagina è scaduta. Riprova a inviare il form.');
        }

        return parent::render($request, $e);
    }
}
