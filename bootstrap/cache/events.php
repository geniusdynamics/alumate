<?php return array (
  'App\\Providers\\EventServiceProvider' => 
  array (
  ),
  'Illuminate\\Foundation\\Support\\Providers\\EventServiceProvider' => 
  array (
    'Illuminate\\Auth\\Events\\Login' => 
    array (
      0 => 'App\\Listeners\\LogUserActivity@handleLogin',
    ),
    'Illuminate\\Auth\\Events\\Logout' => 
    array (
      0 => 'App\\Listeners\\LogUserActivity@handleLogout',
    ),
    'Illuminate\\Auth\\Events\\Registered' => 
    array (
      0 => 'App\\Listeners\\LogUserActivity@handleRegistration',
    ),
    'App\\Events\\UserProfileUpdated' => 
    array (
      0 => 'App\\Listeners\\LogUserActivity@handleUserProfileUpdated',
    ),
  ),
);