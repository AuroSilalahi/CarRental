<?php

namespace App\Livewire;

use App\Enums\IdentityDocumentFileType;
use App\Enums\IdentityDocumentStatus;
use App\Models\IdentityDocument;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class UserProfile extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public string $city = '';
    public string $province = '';

    public $ktpDocument;

    public array $provinces = [];
    public array $cities = [];

    public function mount(): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->phone = $user->phone ?? '';
        $this->address = $user->address ?? '';
        $this->city = $user->city ?? '';
        $this->province = $user->province ?? '';

        $this->provinces = \App\Models\Province::orderBy('name')->pluck('name', 'id')->toArray();

        if ($this->province) {
            $prov = \App\Models\Province::where('name', $this->province)->first();
            if ($prov) {
                $this->cities = $prov->cities()->orderBy('name')->pluck('name', 'name')->toArray();
            }
        }
    }

    public function updatedProvince($value): void
    {
        $this->city = '';
        $this->cities = [];

        if ($value) {
            $prov = \App\Models\Province::where('name', $value)->first();
            if ($prov) {
                $this->cities = $prov->cities()->orderBy('name')->pluck('name', 'name')->toArray();
            }
        }
    }

    public function updateProfile(): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
        ]);

        $user->update([
            'name' => $this->name,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
        ]);

        session()->flash('profile_success', 'Profil berhasil diperbarui!');
    }

    public function uploadKtp(): void
    {
        $this->validate([
            'ktpDocument' => 'required|file|mimes:jpeg,jpg,png,pdf|max:5120',
        ], [
            'ktpDocument.required' => 'Dokumen identitas (KTP) wajib dipilih.',
            'ktpDocument.mimes' => 'Format file harus berupa JPEG, PNG, atau PDF.',
            'ktpDocument.max' => 'Ukuran file maksimal adalah 5MB.',
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();

        $extension = strtolower($this->ktpDocument->getClientOriginalExtension());
        $fileType = match ($extension) {
            'pdf' => IdentityDocumentFileType::Pdf,
            'png' => IdentityDocumentFileType::Png,
            default => IdentityDocumentFileType::Jpeg,
        };

        $disk = config('filesystems.default', 'local');
        $filename = "ktp-user-{$user->id}-" . time() . ".{$extension}";
        $path = $this->ktpDocument->storeAs('identity-documents/' . $user->id, $filename, $disk);

        IdentityDocument::create([
            'customer_id' => $user->id,
            'file_path' => $path,
            'file_type' => $fileType,
            'status' => IdentityDocumentStatus::PendingReview,
        ]);

        $this->reset('ktpDocument');
        session()->flash('ktp_success', 'Dokumen KTP berhasil diunggah dan sedang dalam peninjauan admin.');
    }

    public function render()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $latestKtp = $user->identityDocuments()->latest()->first();

        return view('livewire.user-profile', [
            'user' => $user,
            'latestKtp' => $latestKtp,
        ]);
    }
}
