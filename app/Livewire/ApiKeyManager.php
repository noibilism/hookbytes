<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ApiKeyManager extends Component
{
    public $showApiKey = false;
    public $generatedApiKey = null;
    public $confirmRegenerate = false;

    public function mount()
    {
        $this->showApiKey = false;
        $this->generatedApiKey = null;
        $this->confirmRegenerate = false;
    }

    public function generateApiKey()
    {
        /** @var User $user */
        $user = Auth::user();
        
        if ($user->hasApiKey()) {
            $this->confirmRegenerate = true;
            return;
        }

        $apiKey = $user->generateApiKey();
        $this->generatedApiKey = $apiKey;
        $this->showApiKey = true;
        
        session()->flash('success', 'API key generated successfully!');
    }

    public function regenerateApiKey()
    {
        /** @var User $user */
        $user = Auth::user();
        $apiKey = $user->regenerateApiKey();
        
        $this->generatedApiKey = $apiKey;
        $this->showApiKey = true;
        $this->confirmRegenerate = false;
        
        session()->flash('success', 'API key regenerated successfully! Make sure to update your applications with the new key.');
    }

    public function revokeApiKey()
    {
        /** @var User $user */
        $user = Auth::user();
        $user->api_key = null;
        $user->api_key_created_at = null;
        $user->api_key_last_used_at = null;
        $user->save();
        
        $this->showApiKey = false;
        $this->generatedApiKey = null;
        $this->confirmRegenerate = false;
        
        session()->flash('success', 'API key revoked successfully!');
    }

    public function hideApiKey()
    {
        $this->showApiKey = false;
        $this->generatedApiKey = null;
    }

    public function cancelRegenerate()
    {
        $this->confirmRegenerate = false;
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        
        return view('livewire.api-key-manager', [
            'hasApiKey' => $user->hasApiKey(),
            'apiKeyCreatedAt' => $user->api_key_created_at,
            'apiKeyLastUsedAt' => $user->api_key_last_used_at,
        ]);
    }
}