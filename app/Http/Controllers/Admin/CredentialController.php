<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Credential;
use Illuminate\Http\Request;

class CredentialController extends Controller
{
    public function index()
    {
        $credentials = Credential::latest()->paginate(10);
        return view('admin.credentials', compact('credentials'));
    }

    public function edit($id)
    {
        $credential = Credential::findOrFail($id);
        return view('admin.edit-credential', compact('credential'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'refresh_token' => 'nullable|string',
            'access_token' => 'nullable|string',
        ]);

        $credential = Credential::findOrFail($id);
        $credential->update([
            'name' => $request->name,
            'refresh_token' => $request->refresh_token,
            'access_token' => $request->access_token,
        ]);

        return redirect()->route('admin.credentials')->with('success', 'Credential updated successfully!');
    }

    public function destroy($id)
    {
        $credential = Credential::findOrFail($id);
        $credential->delete();

        return redirect()->route('admin.credentials')->with('success', 'Credential deleted successfully!');
    }

    public function create()
    {
        return view('admin.create-credential');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:credentials',
            'refresh_token' => 'nullable|string',
            'access_token' => 'nullable|string',
        ]);

        Credential::create([
            'name' => $request->name,
            'refresh_token' => $request->refresh_token,
            'access_token' => $request->access_token,
        ]);

        return redirect()->route('admin.credentials')->with('success', 'Credential created successfully!');
    }
}