<?php

namespace App\Livewire\Auth;

use App\Enums\AccountStatus;
use App\Mail\EmailVerificationMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Register extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|max:255|unique:users,email')]
    public string $email = '';

    #[Validate('required|string|regex:/^(\+62|08)[0-9]{7,12}$/')]
    public string $phone = '';

    #[Validate('required|string|min:8|max:64')]
    public string $password = '';

    #[Validate('required|same:password')]
    public string $passwordConfirmation = '';

    #[Validate('required|string')]
    public string $address = '';

    #[Validate('required|string|max:100')]
    public string $city = '';

    #[Validate('required|string|max:100')]
    public string $province = '';

    public function register(): mixed
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => ['required', 'string', 'regex:/^(\+62|08)[0-9]{7,12}$/'],
            'password' => 'required|string|min:8|max:64',
            'passwordConfirmation' => 'required|same:password',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'phone.regex' => 'Format nomor telepon tidak valid (contoh: 08123456789).',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'passwordConfirmation.same' => 'Konfirmasi kata sandi tidak cocok.',
            'address.required' => 'Alamat rumah wajib diisi.',
            'city.required' => 'Kota wajib diisi.',
            'province.required' => 'Provinsi wajib diisi.',
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => Hash::make($this->password),
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'account_status' => AccountStatus::Active,
        ]);

        // Send email verification
        $verificationUrl = URL::temporarySignedRoute(
            'api.v1.email.verify',
            Carbon::now()->addHours(24),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        try {
            Mail::to($user->email)->queue(new EmailVerificationMail($user, $verificationUrl));
        } catch (\Throwable $e) {
            // Ignore email queue error for local testing
        }

        auth()->login($user);

        session()->flash('success', 'Registrasi berhasil! Silakan periksa email Anda untuk verifikasi.');

        return $this->redirect('/', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
