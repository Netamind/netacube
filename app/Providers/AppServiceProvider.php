<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}
     
 public function boot()
{
    View::composer('*', function ($view) {
        if (session('auth_user_email')) {
            $sessionUser = new \App\Models\SessionUser([
                'id'                             => session('auth_user_id'),
                'name'                           => session('auth_user_name'),
                'phone'                          => session('auth_user_phone'),
                'email'                          => session('auth_user_email'),
                'profile_picture'                => session('auth_user_profile_picture'),
                'dob'                            => session('auth_user_dob'),
                'idtype'                         => session('auth_user_idtype'),
                'idnumber'                       => session('auth_user_idnumber'),
                'role'                           => session('auth_user_role'),
                'branch'                         => session('auth_user_branch'),
                'department'                     => session('auth_user_department'),
                'position'                       => session('auth_user_position'),
                'gross_salary'                   => session('auth_user_gross_salary'),
                'started_on'                     => session('auth_user_started_on'),
                'entered_on'                     => session('auth_user_entered_on'),
                'active'                         => session('auth_user_active'),
                'employment_type'                => session('auth_user_employment_type'),
                'contract_end_date'              => session('auth_user_contract_end_date'),
                'on_paye'                        => session('auth_user_on_paye'),
                'home_address'                   => session('auth_user_home_address'),
                'current_residence'              => session('auth_user_current_residence'),
                'bank_name'                      => session('auth_user_bank_name'),
                'bank_account_name'              => session('auth_user_bank_account_name'),
                'bank_account_number'            => session('auth_user_bank_account_number'),
                'bank_branch'                    => session('auth_user_bank_branch'),
                'bank_account_type'              => session('auth_user_bank_account_type'),
                'nextofkin_name'                 => session('auth_user_nextofkin_name'),
                'nextofkin_relationship'         => session('auth_user_nextofkin_relationship'),
                'nextofkin_physical_address'     => session('auth_user_nextofkin_physical_address'),
                'nextofkin_contact'              => session('auth_user_nextofkin_contact'),
            ]);

            Auth::setUser($sessionUser);
        }
    });
}
}