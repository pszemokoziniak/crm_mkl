<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CreateUserPassword extends Mailable
{
    use Queueable, SerializesModels;

    public $email;
    public $password;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($email, $password)
    {
        $this->email = $email;
        $this->password = $password;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->from('hrm@mkl.pl', 'Admin - Nowy Użytkownik')
            ->to($this->email)
            ->cc('natalia.paldyna@mkl.pl')
            ->bcc('krzysztof.kielczykowski@mkl.pl')
            ->subject('Utowrzono konto')
            ->markdown('emails.userLoginMail');
    }
}
