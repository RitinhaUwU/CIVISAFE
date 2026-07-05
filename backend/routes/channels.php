<?php

use App\Models\Incident;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('DonationStocks', function (User $user) {
   return $user->hasRole(['admin', 'module_donations']);
});

Broadcast::channel('Incident.{id}', function (User $user, $id) {
    return $user->hasRole(['admin', 'module_incidents']) && Incident::find($id)->exists();
});
