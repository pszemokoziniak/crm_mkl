@component('mail::message')
# Dane do logowania

**Adres strony:** [https://hrm.mkl.pl/login](https://hrm.mkl.pl/login)

**Twój login:** {{ $email }}

**Hasło jednorazowe:** {{ $password }}

Przy pierwszym logowaniu system poprosi o ustawienie własnego hasła.

Zespół HRM
@endcomponent
