<?php return array (
  'App\\Providers\\EventServiceProvider' => 
  array (
    'Illuminate\\Auth\\Events\\Registered' => 
    array (
      0 => 'Illuminate\\Auth\\Listeners\\SendEmailVerificationNotification',
      1 => 'App\\Listeners\\CheckAchievementsListener',
    ),
    'App\\Events\\InstitutionAdminCreated' => 
    array (
      0 => 'App\\Listeners\\SendInstitutionAdminCreationNotification',
    ),
    'App\\Events\\CareerMilestoneCreated' => 
    array (
      0 => 'App\\Listeners\\CheckAchievementsListener',
    ),
    'App\\Events\\UserProfileUpdated' => 
    array (
      0 => 'App\\Listeners\\CheckAchievementsListener',
    ),
    'App\\Events\\ConnectionAccepted' => 
    array (
      0 => 'App\\Listeners\\CheckAchievementsListener',
    ),
    'App\\Events\\PostCreated' => 
    array (
      0 => 'App\\Listeners\\CheckAchievementsListener',
    ),
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
  ),
);