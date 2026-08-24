<?php

namespace App\Livewire\Auth;

use App\Enums\AccountStatus;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public string $errorMessage = '';

    public function login(): mixed
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $this->errorMessage = '';

        $user = User::where('email', $this->email)->first();

        // Account Lockout Check (Requirement 5.8)
        if ($user && $user->locked_until && $user->locked_until->isFuture()) {
            $minutesLeft = (int) ceil(Carbon::now()->diffInSeconds($user->locked_until) / 60);
            $this->errorMessage = "Akun Anda terkunci sementara karena 5 kali percobaan login yang gagal. Silakan coba lagi dalam {$minutesLeft} menit.";
            return null;
        }

        if (! auth()->attempt(['email' => $this->email, 'password' => $this->password])) {
            if ($user) {
                $attempts = $user->failed_login_attempts + 1;
                $updateData = ['failed_login_attempts' => $attempts];

                if ($attempts >= 5) {
                    $updateData['locked_until'] = Carbon::now()->addMinutes(15);
                }

                $user->update($updateData);

                if ($attempts >= 5) {
                    $this->errorMessage = 'Akun Anda terkunci sementara selama 15 menit karena 5 kali percobaan login yang gagal.';
                    return null;
                }
            }

            $this->errorMessage = 'Kredensial yang Anda masukkan tidak valid.';
            return null;
        }

        /** @var User $authUser */
        $authUser = auth()->user();

        if ($authUser->account_status === AccountStatus::Deactivated) {
            auth()->logout();
            $this->errorMessage = 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.';
            return null;
        }

        // Reset failed login counter on success
        $authUser->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        session()->flash('success', 'Selamat datang kembali, ' . $authUser->name . '!');

        return $this->redirect('/', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
