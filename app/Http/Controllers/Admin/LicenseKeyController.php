<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LicenseKey;
use App\Models\User;
use Illuminate\Http\Request;

class LicenseKeyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = LicenseKey::with('user');
        
        // Apply search filter
        if ($request->has('search') && !empty($request->search)) {
            $query->where('key', 'like', '%' . $request->search . '%')
                  ->orWhere('notes', 'like', '%' . $request->search . '%')
                  ->orWhereHas('user', function ($q) use ($request) {
                      $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('email', 'like', '%' . $request->search . '%');
                  });
        }
        
        // Apply status filter
        if ($request->has('status')) {
            if ($request->status == 'active') {
                $query->where('is_active', true);
            } elseif ($request->status == 'inactive') {
                $query->where('is_active', false);
            }
        }
        
        // Apply assignment filter
        if ($request->has('assigned')) {
            if ($request->assigned == 'assigned') {
                $query->whereNotNull('user_id');
            } elseif ($request->assigned == 'unassigned') {
                $query->whereNull('user_id');
            }
        }
        
        // Apply expiry filter
        if ($request->has('expiry')) {
            if ($request->expiry == 'expired') {
                $query->whereNotNull('expires_at')
                      ->where('expires_at', '<', now());
            } elseif ($request->expiry == 'expiring_soon') {
                $query->whereNotNull('expires_at')
                      ->where('expires_at', '>=', now())
                      ->where('expires_at', '<=', now()->addDays(7));
            } elseif ($request->expiry == 'unlimited') {
                $query->whereNull('expires_at');
            }
        }

        $licenseKeys = $query->orderBy('created_at', 'desc')->paginate(10);
        
        return view('admin.license-keys.index', compact('licenseKeys'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.license-keys.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:100',
            'expires_at' => 'nullable|date|after:today',
            'max_documents' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);
        
        $quantity = $request->quantity;
        $created = 0;
        
        for ($i = 0; $i < $quantity; $i++) {
            LicenseKey::create([
                'key' => LicenseKey::generateKey(),
                'created_by' => auth()->id(),
                'expires_at' => $request->expires_at,
                'max_documents' => $request->max_documents,
                'notes' => $request->notes,
                'is_active' => true,
            ]);
            $created++;
        }
        
        return redirect()->route('admin.license-keys.index')
            ->with('success', "{$created} license key mới đã được tạo thành công.");
    }

    /**
     * Display the specified resource.
     */
    public function show(LicenseKey $licenseKey)
    {
        $licenseKey->load('user');
        return view('admin.license-keys.show', compact('licenseKey'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LicenseKey $licenseKey)
    {
        $users = User::all();
        return view('admin.license-keys.edit', compact('licenseKey', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LicenseKey $licenseKey)
    {
        $request->validate([
            'expires_at' => 'nullable|date|after:today',
            'max_documents' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:500',
            'user_id' => 'nullable|exists:users,id',
        ]);
        
        $licenseKey->expires_at = $request->expires_at;
        $licenseKey->max_documents = $request->max_documents;
        $licenseKey->notes = $request->notes;
        
        if ($request->has('user_id') && $request->user_id != $licenseKey->user_id) {
            $licenseKey->user_id = $request->user_id ?: null;
            
            if ($request->user_id) {
                $licenseKey->activated_at = now();
            } else {
                $licenseKey->activated_at = null;
            }
        }
        
        $licenseKey->save();
        
        return redirect()->route('admin.license-keys.index')
            ->with('success', 'License key đã được cập nhật thành công.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LicenseKey $licenseKey)
    {
        $licenseKey->delete();
        
        return redirect()->route('admin.license-keys.index')
            ->with('success', 'License key đã được xóa thành công.');
    }
    
    /**
     * Toggle the active status of the license key.
     */
    public function toggleStatus(LicenseKey $licenseKey)
    {
        $licenseKey->is_active = !$licenseKey->is_active;
        $licenseKey->save();
        
        $status = $licenseKey->is_active ? 'kích hoạt' : 'vô hiệu hóa';
        
        return redirect()->back()
            ->with('success', "License key đã được {$status} thành công.");
    }
    
    /**
     * Batch activate license keys
     */
    public function batchActivate(Request $request)
    {
        $request->validate([
            'licenses' => 'required|array',
            'licenses.*' => 'exists:license_keys,id',
        ]);
        
        $count = LicenseKey::whereIn('id', $request->licenses)
            ->update(['is_active' => true]);
            
        return redirect()->route('admin.license-keys.index')
            ->with('success', "{$count} license keys đã được kích hoạt thành công.");
    }
    
    /**
     * Batch deactivate license keys
     */
    public function batchDeactivate(Request $request)
    {
        $request->validate([
            'licenses' => 'required|array',
            'licenses.*' => 'exists:license_keys,id',
        ]);
        
        $count = LicenseKey::whereIn('id', $request->licenses)
            ->update(['is_active' => false]);
            
        return redirect()->route('admin.license-keys.index')
            ->with('success', "{$count} license keys đã được vô hiệu hóa thành công.");
    }
    
    /**
     * Batch delete license keys
     */
    public function batchDelete(Request $request)
    {
        $request->validate([
            'licenses' => 'required|array',
            'licenses.*' => 'exists:license_keys,id',
        ]);
        
        $count = LicenseKey::whereIn('id', $request->licenses)->delete();
            
        return redirect()->route('admin.license-keys.index')
            ->with('success', "{$count} license keys đã được xóa thành công.");
    }
}