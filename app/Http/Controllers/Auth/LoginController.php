<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validate that the fields are filled out
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 2. Check the email first
        if ($request->email !== 'admin@mmsu.edu.ph') {
            return back()->withErrors([
                'email' => 'These credentials do not match our records.'
            ])->onlyInput('email'); // Keeps the email they typed in the box
        }

        // 3. Check the password next
        if ($request->password !== 'password') {
            return back()->withErrors([
                'password' => 'The password you entered is incorrect.'
            ])->onlyInput('email');
        }

        // 4. If both pass, authenticate and redirect
        $request->session()->put('authenticated', true);
        return redirect()->route('dashboard');
    }

    public function destroy(Request $request)
    {
        $request->session()->forget('authenticated');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}