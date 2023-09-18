@component('mail::message')
    #Dane do logowania

    ## Adres strony: https://hrm.mkl.pl/login

    Twój login {{ $email }}
    Jednorazowe hasło: {{ $password }}

    Zespół HRM
@endcomponent
